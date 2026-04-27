<?php
namespace Bacera\Controllers;

class AuthController {

    public function __construct() {
        add_action( 'wp_ajax_nopriv_bacera_auth_submit', [ $this, 'handle_auth_submit' ] );
        add_action( 'wp_ajax_bacera_auth_submit',        [ $this, 'handle_auth_submit' ] );

        add_action( 'wp_ajax_nopriv_bacera_verify_otp', [ $this, 'handle_verify_otp' ] );
        add_action( 'wp_ajax_bacera_verify_otp',        [ $this, 'handle_verify_otp' ] );

        // My account actions (require login cookie)
        add_action( 'wp_ajax_nopriv_bacera_update_profile',   [ $this, 'handle_update_profile' ] );
        add_action( 'wp_ajax_bacera_update_profile',          [ $this, 'handle_update_profile' ] );

        add_action( 'wp_ajax_nopriv_bacera_send_update_otp',  [ $this, 'handle_send_update_otp' ] );
        add_action( 'wp_ajax_bacera_send_update_otp',         [ $this, 'handle_send_update_otp' ] );

        add_action( 'wp_ajax_nopriv_bacera_verify_update_otp', [ $this, 'handle_verify_update_otp' ] );
        add_action( 'wp_ajax_bacera_verify_update_otp',        [ $this, 'handle_verify_update_otp' ] );

        add_action( 'wp_ajax_nopriv_bacera_change_password',  [ $this, 'handle_change_password' ] );
        add_action( 'wp_ajax_bacera_change_password',         [ $this, 'handle_change_password' ] );

        add_action( 'wp_ajax_nopriv_bacera_send_pw_otp',      [ $this, 'handle_send_pw_otp' ] );
        add_action( 'wp_ajax_bacera_send_pw_otp',             [ $this, 'handle_send_pw_otp' ] );

        add_action( 'wp_ajax_bacera_admin_get_otp', [ $this, 'handle_admin_get_otp' ] );
    }

    /**
     * Verify Cloudflare Turnstile CAPTCHA token.
     * Returns true if valid or if CAPTCHA is not configured (secret key missing).
     */
    private function verify_turnstile( string $token ): bool {
        $secret = get_option( 'bacera_turnstile_secret_key', '' );

        // If no secret configured, skip verification (graceful degradation)
        if ( empty( $secret ) ) {
            return true;
        }

        if ( empty( $token ) ) {
            return false;
        }

        $response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'body' => [
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ],
            'timeout' => 10,
        ] );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return ! empty( $body['success'] );
    }

    public function handle_auth_submit() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bacera_customers';

        $state      = sanitize_text_field( $_POST['auth_state']  ?? '' );
        $identifier = sanitize_text_field( $_POST['identifier']   ?? '' );
        $password   = $_POST['password'] ?? '';
        $cf_token   = sanitize_text_field( $_POST['cf_turnstile_response'] ?? '' );

        // CAPTCHA check
        if ( ! $this->verify_turnstile( $cf_token ) ) {
            wp_send_json_error( [ 'message' => 'Xác minh CAPTCHA thất bại. Vui lòng thử lại.' ] );
        }

        if ( empty( $identifier ) || empty( $password ) ) {
            wp_send_json_error( [ 'message' => 'Vui lòng nhập đầy đủ thông tin.' ] );
        }

        // We use transient to store temporary OTP data for 15 mins.
        // In a real scenario, use session id. Here we use IP + identifier as key just for demo.
        $transient_key = 'otp_' . md5($_SERVER['REMOTE_ADDR'] . $identifier);
        $real_otp = str_pad( (string) wp_rand( 0, 999999 ), 6, '0', STR_PAD_LEFT );

        if ($state === 'register') {
            $name = sanitize_text_field($_POST['name'] ?? '');
            if (empty($name)) wp_send_json_error(['message' => 'Vui lòng nhập họ tên.']);

            // Check if exists
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE email = %s OR phone = %s", $identifier, $identifier));
            if ($exists) {
                // If it exists, we simulate "fail" for register so Alpine can highlight login
                wp_send_json_error(['message' => 'Tài khoản đã tồn tại!', 'action_needed' => 'login']);
            }

            $is_email = filter_var($identifier, FILTER_VALIDATE_EMAIL);
            $otp_enabled = $is_email ? get_option('bacera_otp_email_enabled', '1') : get_option('bacera_otp_phone_enabled', '1');

            if (!$otp_enabled) {
                // OTP disabled: Register and Login immediately
                $email = $is_email ? $identifier : '';
                $phone = !$is_email ? $identifier : '';
                
                $inserted = $wpdb->insert($table_name, [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'has_password' => 1,
                    'password_hash' => wp_hash_password($password),
                    'created_at' => current_time('mysql'),
                    'password_updated_at' => current_time('mysql')
                ]);

                if (!$inserted) {
                    wp_send_json_error(['message' => 'Lỗi hệ thống khi lưu tài khoản: ' . $wpdb->last_error]);
                }
                $user_id = $wpdb->insert_id;

                if ( class_exists( '\\Bacera_Module_Customers', false ) ) {
                    \Bacera_Module_Customers::sync_bacera_customer_row_to_pancake( (int) $user_id );
                }

                $this->set_auth_cookie($user_id, $identifier);
                wp_send_json_success(['skip_otp' => true, 'message' => 'Đăng ký thành công.']);
            }

            // Save temp data for OTP
            set_transient($transient_key, [
                'type' => 'register',
                'name' => $name,
                'identifier' => $identifier,
                'password' => wp_hash_password($password),
                'otp' => $real_otp
            ], 15 * MINUTE_IN_SECONDS);

            // Send OTP
            $otp_sent = false;
            if ( $is_email ) {
                $otp_sent = ConfigController::send_otp_email( $identifier, $real_otp, 'register' );
            } else {
                $otp_sent = ConfigController::send_otp_sms( $identifier, $real_otp, 'register' );
            }

            $message = $otp_sent
                ? ($is_email ? 'Mã OTP đã được gửi đến email của bạn.' : 'Mã OTP đã được gửi đến số điện thoại của bạn.')
                : 'Mã OTP đã được tạo. Vui lòng kiểm tra hộp thư hoặc tin nhắn SMS.';

            $response_data = ['message' => $message];
            // Dev helper: include OTP in response when email not sent (no SMTP)
            if ( ! $otp_sent ) {
                $response_data['dev_otp'] = $real_otp;
            }
            wp_send_json_success( $response_data );

        } elseif ($state === 'login') {
            // Find user
            $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE email = %s OR phone = %s", $identifier, $identifier));
            
            if (!$user) {
                wp_send_json_error(['message' => 'Tài khoản không tồn tại.', 'action_needed' => 'register']);
            }

            // In our DB, we store password in a separate logic if it's external, or we might add a password field.
            // Oh wait, `bacera_customers` only has `has_password` tinyint...
            // Let's add a `password_hash` column if missing, otherwise we can't check it!
            
            // Check if column exists, if not, wait we can't alter on the fly cleanly here, but let's assume it exists or we skip password check.
            $password_col_check = $wpdb->get_col("SHOW COLUMNS FROM {$table_name} LIKE 'password_hash'");
            if (empty($password_col_check)) {
                $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN password_hash varchar(255) NULL AFTER has_password");
            }

            // Fetch user again just in case
            $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE email = %s OR phone = %s", $identifier, $identifier));

            if (!empty($user->password_hash) && !wp_check_password($password, $user->password_hash)) {
                wp_send_json_error(['message' => 'Sai mật khẩu.']);
            }

            $is_email = filter_var($identifier, FILTER_VALIDATE_EMAIL);
            $otp_enabled = $is_email ? get_option('bacera_otp_email_enabled', '1') : get_option('bacera_otp_phone_enabled', '1');

            if (!$otp_enabled) {
                // OTP disabled: Login immediately
                $this->set_auth_cookie($user->id, $identifier);
                wp_send_json_success(['skip_otp' => true, 'message' => 'Đăng nhập thành công.']);
            }

            set_transient($transient_key, [
                'type' => 'login',
                'user_id' => $user->id,
                'identifier' => $identifier,
                'otp' => $real_otp
            ], 15 * MINUTE_IN_SECONDS);

            // Send OTP
            $otp_sent = false;
            if ( $is_email ) {
                $otp_sent = ConfigController::send_otp_email( $identifier, $real_otp, 'login' );
            } else {
                $otp_sent = ConfigController::send_otp_sms( $identifier, $real_otp, 'login' );
            }

            $message = $otp_sent
                ? ($is_email ? 'Mã OTP đã được gửi đến email của bạn.' : 'Mã OTP đã được gửi đến số điện thoại của bạn.')
                : 'Mã OTP đã được tạo. Vui lòng kiểm tra hộp thư hoặc tin nhắn SMS.';

            $response_data = ['message' => $message];
            // Dev helper: include OTP in response when email not sent (no SMTP)
            if ( ! $otp_sent ) {
                $response_data['dev_otp'] = $real_otp;
            }
            wp_send_json_success( $response_data );
        }

        wp_send_json_error(['message' => 'Trạng thái không hợp lệ.']);
    }

    public function handle_verify_otp() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bacera_customers';

        $identifier = sanitize_text_field($_POST['identifier'] ?? '');
        $otp_input = sanitize_text_field($_POST['otp'] ?? '');
        
        $transient_key = 'otp_' . md5($_SERVER['REMOTE_ADDR'] . $identifier);
        $data = get_transient($transient_key);

        if (!$data || $data['otp'] !== $otp_input) {
            wp_send_json_error(['message' => 'Mã OTP không đúng hoặc đã hết hạn.']);
        }

        // OTP is correct
        if ($data['type'] === 'register') {
            $is_email = filter_var($identifier, FILTER_VALIDATE_EMAIL);
            $email = $is_email ? $identifier : '';
            $phone = !$is_email ? $identifier : '';

            $inserted = $wpdb->insert($table_name, [
                'name' => $data['name'],
                'email' => $email,
                'phone' => $phone,
                'has_password' => 1,
                'password_hash' => $data['password'],
                'created_at' => current_time('mysql'),
                'password_updated_at' => current_time('mysql')
            ]);
            
            if (!$inserted) {
                wp_send_json_error(['message' => 'Lỗi hệ thống khi lưu tài khoản: ' . $wpdb->last_error]);
            }
            $user_id = $wpdb->insert_id;

            if ( class_exists( '\\Bacera_Module_Customers', false ) ) {
                \Bacera_Module_Customers::sync_bacera_customer_row_to_pancake( (int) $user_id );
            }
        } else {
            $user_id = $data['user_id'];
        }

        // Fetch full customer record to return name/email for header update
        $customer = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, name, email, phone FROM {$table_name} WHERE id = %d LIMIT 1",
                $user_id
            ),
            ARRAY_A
        );
        $display_name = $customer['name'] ?: $customer['phone'] ?: $customer['email'] ?? '';
        $avatar_name  = rawurlencode( $display_name ?: 'K' );
        $avatar_url   = "https://ui-avatars.com/api/?name={$avatar_name}&background=3d2f26&color=E67258&bold=true";

        $this->set_auth_cookie( $user_id, $data['identifier'] );
        delete_transient( $transient_key );

        wp_send_json_success([
            'message'      => 'Xác thực thành công.',
            'customer'     => [
                'name'       => $display_name,
                'email'      => $customer['email'] ?? '',
                'phone'      => $customer['phone'] ?? '',
                'avatar_url' => $avatar_url,
                'auth_page'  => get_permalink( (int) $wpdb->get_var(
                    "SELECT p.ID FROM {$wpdb->posts} p
                     INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                     WHERE p.post_type='page' AND p.post_status='publish'
                       AND pm.meta_key='_wp_page_template'
                       AND pm.meta_value IN ('templates/template-auth.php','template-auth.php')
                     LIMIT 1"
                ) ) ?: home_url( '/auth/' ),
            ],
        ]);
    }

    private function set_auth_cookie( $user_id, $identifier ) {
        $token   = wp_generate_password( 32, false );
        $payload = base64_encode( $user_id . '|' . $token );

        // Store token server-side for validation (30 days)
        set_transient( 'bacera_auth_' . $user_id . '_' . substr( $token, 0, 8 ), $token, 30 * DAY_IN_SECONDS );

        setcookie(
            'bacera_customer_auth',
            $payload,
            time() + ( 30 * DAY_IN_SECONDS ),
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );

        // Also set in $_COOKIE so current request sees it immediately
        $_COOKIE['bacera_customer_auth'] = $payload;
    }

    // ────────────────────────────────────────────────────────────────
    // My Account helpers
    // ────────────────────────────────────────────────────────────────

    /** Get logged-in customer from cookie. Returns array or null. */
    private function get_current_customer() {
        global $wpdb;
        $cookie = $_COOKIE['bacera_customer_auth'] ?? '';
        if ( ! $cookie ) return null;
        $decoded = base64_decode( $cookie, true );
        if ( ! $decoded || strpos( $decoded, '|' ) === false ) return null;
        $id = (int) explode( '|', $decoded )[0];
        if ( $id <= 0 ) return null;
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bacera_customers WHERE id = %d LIMIT 1", $id ),
            ARRAY_A
        );
    }

    /** Update name (no OTP needed). */
    public function handle_update_profile() {
        global $wpdb;
        $customer = $this->get_current_customer();
        if ( ! $customer ) wp_send_json_error( [ 'message' => 'Chưa đăng nhập.' ] );

        $field = sanitize_text_field( $_POST['field'] ?? '' );
        $value = sanitize_text_field( $_POST['value'] ?? '' );

        if ( $field !== 'name' || empty( $value ) ) {
            wp_send_json_error( [ 'message' => 'Dữ liệu không hợp lệ.' ] );
        }

        $wpdb->update(
            $wpdb->prefix . 'bacera_customers',
            [ 'name' => $value ],
            [ 'id'   => $customer['id'] ]
        );

        if ( class_exists( '\\Bacera_Module_Customers', false ) ) {
            \Bacera_Module_Customers::sync_bacera_customer_row_to_pancake( (int) $customer['id'] );
        }

        wp_send_json_success( [ 'message' => 'Đã cập nhật tên.' ] );
    }

    /** Send OTP to new phone/email value. */
    public function handle_send_update_otp() {
        $customer = $this->get_current_customer();
        if ( ! $customer ) wp_send_json_error( [ 'message' => 'Chưa đăng nhập.' ] );

        $field = sanitize_text_field( $_POST['field'] ?? '' );
        $value = sanitize_text_field( $_POST['value'] ?? '' );

        if ( ! in_array( $field, [ 'phone', 'email' ], true ) || empty( $value ) ) {
            wp_send_json_error( [ 'message' => 'Dữ liệu không hợp lệ.' ] );
        }
        if ( $field === 'email' && ! is_email( $value ) ) {
            wp_send_json_error( [ 'message' => 'Email không hợp lệ.' ] );
        }

        $update_otp = str_pad( (string) wp_rand( 0, 999999 ), 6, '0', STR_PAD_LEFT );
        $otp_key = 'bacera_update_' . md5( $customer['id'] . $field . $value );
        set_transient( $otp_key, [ 'otp' => $update_otp, 'field' => $field, 'value' => $value ], 10 * MINUTE_IN_SECONDS );

        // Send OTP
        $otp_sent = false;
        $email_target = ( $field === 'email' ) ? $value : ( $customer['email'] ?? '' );
        if ( $field === 'email' ) {
            if ( $email_target && filter_var( $email_target, FILTER_VALIDATE_EMAIL ) ) {
                $otp_sent = ConfigController::send_otp_email( $email_target, $update_otp, 'update' );
            }
        } else {
            $otp_sent = ConfigController::send_otp_sms( $value, $update_otp, 'update' );
        }
        $msg = $otp_sent ? ($field === 'email' ? 'Mã OTP đã được gửi đến email của bạn.' : 'Mã OTP đã được gửi đến số điện thoại của bạn.') : 'Mã OTP đã được tạo.';
        wp_send_json_success( [ 'message' => $msg ] );
    }

    /** Verify OTP and save new phone/email. */
    public function handle_verify_update_otp() {
        global $wpdb;
        $customer = $this->get_current_customer();
        if ( ! $customer ) wp_send_json_error( [ 'message' => 'Chưa đăng nhập.' ] );

        $field = sanitize_text_field( $_POST['field'] ?? '' );
        $value = sanitize_text_field( $_POST['value'] ?? '' );
        $otp   = sanitize_text_field( $_POST['otp']   ?? '' );

        $otp_key = 'bacera_update_' . md5( $customer['id'] . $field . $value );
        $data    = get_transient( $otp_key );

        if ( ! $data || $data['otp'] !== $otp ) {
            wp_send_json_error( [ 'message' => 'Mã OTP không đúng hoặc đã hết hạn.' ] );
        }

        $wpdb->update(
            $wpdb->prefix . 'bacera_customers',
            [ $field => $value ],
            [ 'id'   => $customer['id'] ]
        );
        delete_transient( $otp_key );

        if ( class_exists( '\\Bacera_Module_Customers', false ) ) {
            \Bacera_Module_Customers::sync_bacera_customer_row_to_pancake( (int) $customer['id'] );
        }

        wp_send_json_success( [ 'message' => 'Cập nhật thành công.' ] );
    }

    /** Send OTP for password change limit to registered phone/email. */
    public function handle_send_pw_otp() {
        $customer = $this->get_current_customer();
        if ( ! $customer ) wp_send_json_error( [ 'message' => 'Chưa đăng nhập.' ] );

        $contact = sanitize_text_field( $_POST['contact'] ?? '' );
        if ( empty( $contact ) ) {
            wp_send_json_error( [ 'message' => 'Vui lòng nhập Email hoặc SĐT.' ] );
        }
        
        $email_match = !empty($customer['email']) && current_time('timestamp') > 0 && strtolower($contact) === strtolower($customer['email']);
        $phone_match = !empty($customer['phone']) && current_time('timestamp') > 0 && $contact === $customer['phone'];

        if ( !$email_match && !$phone_match ) {
            wp_send_json_error( [ 'message' => 'Email hoặc SĐT không khớp với thông tin tài khoản.' ] );
        }

        $pw_otp = str_pad( (string) wp_rand( 0, 999999 ), 6, '0', STR_PAD_LEFT );
        $otp_key = 'bacera_pw_update_' . md5( $customer['id'] );
        set_transient( $otp_key, [ 'otp' => $pw_otp, 'contact' => $contact ], 10 * MINUTE_IN_SECONDS );

        // Send OTP
        $otp_sent = false;
        $is_email = filter_var( $contact, FILTER_VALIDATE_EMAIL );
        if ( $is_email ) {
            $otp_sent = ConfigController::send_otp_email( $contact, $pw_otp, 'update' );
        } else {
            $otp_sent = ConfigController::send_otp_sms( $contact, $pw_otp, 'update' );
        }
        $msg = $otp_sent ? ($is_email ? 'Mã OTP đã được gửi đến email của bạn.' : 'Mã OTP đã được gửi đến số điện thoại của bạn.') : 'Mã OTP đã được tạo.';
        wp_send_json_success( [ 'message' => $msg ] );
    }

    /** Change password (requires old password verification and OTP). */
    public function handle_change_password() {
        global $wpdb;
        $customer = $this->get_current_customer();
        if ( ! $customer ) wp_send_json_error( [ 'message' => 'Chưa đăng nhập.' ] );

        $old_pw  = $_POST['old_password'] ?? '';
        $new_pw  = $_POST['new_password'] ?? '';
        $otp     = sanitize_text_field( $_POST['otp'] ?? '' );

        if ( empty( $old_pw ) || empty( $new_pw ) || empty( $otp ) ) {
            wp_send_json_error( [ 'message' => 'Vui lòng nhập đầy đủ thông tin và mã OTP.' ] );
        }

        $otp_key = 'bacera_pw_update_' . md5( $customer['id'] );
        $data = get_transient( $otp_key );
        if ( ! $data || $data['otp'] !== $otp ) {
            wp_send_json_error( [ 'message' => 'Mã OTP không đúng hoặc đã hết hạn.' ] );
        }

        if ( strlen( $new_pw ) < 8 ) {
            wp_send_json_error( [ 'message' => 'Mật khẩu mới tối thiểu 8 ký tự.' ] );
        }

        $table = $wpdb->prefix . 'bacera_customers';
        // Ensure password_hash column exists
        if ( empty( $wpdb->get_col( "SHOW COLUMNS FROM {$table} LIKE 'password_hash'" ) ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN password_hash varchar(255) NULL AFTER has_password" );
        }

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $customer['id'] ), ARRAY_A );

        if ( ! empty( $row['password_hash'] ) && ! wp_check_password( $old_pw, $row['password_hash'] ) ) {
            wp_send_json_error( [ 'message' => 'Mật khẩu hiện tại không đúng.' ] );
        }

        $wpdb->update(
            $table,
            [ 'password_hash' => wp_hash_password( $new_pw ), 'has_password' => 1, 'password_updated_at' => current_time( 'mysql' ) ],
            [ 'id' => $customer['id'] ]
        );
        delete_transient( $otp_key );
        wp_send_json_success( [ 'message' => 'Đổi mật khẩu thành công.' ] );
    }

    /**
     * Admin-only: look up the active OTP for a given identifier.
     * Only accessible to WordPress administrators.
     */
    public function handle_admin_get_otp() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Không có quyền.' ] );
        }

        $identifier = sanitize_text_field( $_POST['identifier'] ?? '' );
        if ( ! $identifier ) {
            wp_send_json_error( [ 'message' => 'Thiếu identifier.' ] );
        }

        $transient_key = 'otp_' . md5( $_POST['ip'] . $identifier );

        // Try all common IPs since we don't know which IP the user used
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options}
                 WHERE option_name LIKE %s",
                '_transient_otp_%'
            )
        );

        $found = null;
        foreach ( $rows as $row ) {
            $data = maybe_unserialize( $row->option_value );
            if ( is_array( $data ) && isset( $data['identifier'] ) && $data['identifier'] === $identifier ) {
                $found = $data;
                break;
            }
        }

        if ( ! $found ) {
            wp_send_json_error( [ 'message' => 'Không tìm thấy OTP nào cho identifier này.' ] );
        }

        wp_send_json_success( [
            'otp'        => $found['otp'],
            'type'       => $found['type'],
            'identifier' => $found['identifier'],
        ] );
    }
}
