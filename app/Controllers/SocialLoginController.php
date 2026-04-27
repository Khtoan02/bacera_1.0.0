<?php
namespace Bacera\Controllers;

/**
 * Social Login Controller — chỉ xử lý OAuth callbacks
 * Admin settings đã được chuyển sang ConfigController.
 *
 * OAuth callback URLs:
 *   Google  → <home_url>/bacera-auth/google/callback
 *   Facebook→ <home_url>/bacera-auth/facebook/callback
 */
class SocialLoginController {

    public function __construct() {
        // Được khởi tạo từ MainController (vốn đã ở trong hook 'init').
        // Gọi trực tiếp để bắt routing ngay, không cần add_action 'init' với priority 5 nữa vì priority 5 đã trôi qua.
        $this->handle_oauth_callbacks();
    }

    /* ── OAuth callback routing ──────────────────────────────────── */

    public function handle_oauth_callbacks() {
        $uri_path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
        
        $callback_google = parse_url( home_url( '/bacera-auth/google/callback' ), PHP_URL_PATH );
        $callback_fb     = parse_url( home_url( '/bacera-auth/facebook/callback' ), PHP_URL_PATH );

        // Normalize trailing slashes
        $uri_path = rtrim( $uri_path, '/' );
        $callback_google = rtrim( $callback_google, '/' );
        $callback_fb = rtrim( $callback_fb, '/' );

        // Chỉ chạy khi đúng path — tránh DB query trên mọi request
        if ( $uri_path === $callback_google ) {
            $this->google_callback();
            exit;
        }
        if ( $uri_path === $callback_fb ) {
            $this->facebook_callback();
            exit;
        }
    }

    /* ══════════════════════════════════════════════════════════════
       GOOGLE OAUTH
    ══════════════════════════════════════════════════════════════ */

    public static function google_auth_url(): string {
        $client_id    = get_option( 'bacera_google_client_id', '' );
        $callback_url = home_url( '/bacera-auth/google/callback' );

        if ( empty( $client_id ) ) return '#';

        $state = wp_create_nonce( 'bacera_google_oauth' );
        set_transient( 'bacera_oauth_state_' . $state, 1, 10 * MINUTE_IN_SECONDS );

        $params = http_build_query( [
            'client_id'     => $client_id,
            'redirect_uri'  => $callback_url,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'online',
        ] );

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    private function google_callback() {
        $code  = sanitize_text_field( $_GET['code']  ?? '' );
        $state = sanitize_text_field( $_GET['state'] ?? '' );
        $error = sanitize_text_field( $_GET['error'] ?? '' );

        $auth_page = $this->get_auth_page_url();

        if ( $error ) {
            wp_safe_redirect( add_query_arg( 'social_error', urlencode( 'Google login bị huỷ.' ), $auth_page ) );
            exit;
        }

        if ( ! $state || ! get_transient( 'bacera_oauth_state_' . $state ) ) {
            wp_safe_redirect( add_query_arg( 'social_error', urlencode( 'Phiên OAuth không hợp lệ.' ), $auth_page ) );
            exit;
        }
        delete_transient( 'bacera_oauth_state_' . $state );

        if ( empty( $code ) ) {
            wp_safe_redirect( add_query_arg( 'social_error', urlencode( 'Không nhận được mã từ Google.' ), $auth_page ) );
            exit;
        }

        $token_resp = wp_remote_post( 'https://oauth2.googleapis.com/token', [
            'body' => [
                'code'          => $code,
                'client_id'     => get_option( 'bacera_google_client_id', '' ),
                'client_secret' => get_option( 'bacera_google_client_secret', '' ),
                'redirect_uri'  => home_url( '/bacera-auth/google/callback' ),
                'grant_type'    => 'authorization_code',
            ],
            'timeout' => 15,
        ] );

        if ( is_wp_error( $token_resp ) ) {
            wp_safe_redirect( add_query_arg( 'social_error', urlencode( 'Lỗi kết nối Google.' ), $auth_page ) );
            exit;
        }

        $token_data   = json_decode( wp_remote_retrieve_body( $token_resp ), true );
        $access_token = $token_data['access_token'] ?? '';

        if ( empty( $access_token ) ) {
            $err = $token_data['error_description'] ?? 'Lỗi lấy access token.';
            wp_safe_redirect( add_query_arg( 'social_error', urlencode( $err ), $auth_page ) );
            exit;
        }

        $user_resp = wp_remote_get( 'https://www.googleapis.com/oauth2/v3/userinfo', [
            'headers' => [ 'Authorization' => 'Bearer ' . $access_token ],
            'timeout' => 10,
        ] );

        if ( is_wp_error( $user_resp ) ) {
            wp_safe_redirect( add_query_arg( 'social_error', urlencode( 'Không lấy được thông tin từ Google.' ), $auth_page ) );
            exit;
        }

        $info  = json_decode( wp_remote_retrieve_body( $user_resp ), true );
        $email = sanitize_email( $info['email'] ?? '' );
        $name  = sanitize_text_field( $info['name']  ?? 'Google User' );

        if ( empty( $email ) ) {
            wp_safe_redirect( add_query_arg( 'social_error', urlencode( 'Không lấy được email từ Google.' ), $auth_page ) );
            exit;
        }

        $user_id = $this->find_or_create_social_customer( $email, $name, 'google' );
        $this->login_and_redirect( $user_id );
    }

    /* ══════════════════════════════════════════════════════════════
       FACEBOOK OAUTH
    ══════════════════════════════════════════════════════════════ */

    public static function facebook_auth_url(): string {
        $app_id       = get_option( 'bacera_facebook_app_id', '' );
        $callback_url = home_url( '/bacera-auth/facebook/callback' );

        if ( empty( $app_id ) ) return '#';

        $state = wp_create_nonce( 'bacera_facebook_oauth' );
        set_transient( 'bacera_oauth_state_' . $state, 1, 10 * MINUTE_IN_SECONDS );

        $params = http_build_query( [
            'client_id'     => $app_id,
            'redirect_uri'  => $callback_url,
            'scope'         => 'email,public_profile',
            'state'         => $state,
            'response_type' => 'code',
        ] );

        return 'https://www.facebook.com/v19.0/dialog/oauth?' . $params;
    }

    private function facebook_callback() {
        $code  = sanitize_text_field( $_GET['code']  ?? '' );
        $state = sanitize_text_field( $_GET['state'] ?? '' );
        $error = sanitize_text_field( $_GET['error'] ?? '' );

        $auth_page = $this->get_auth_page_url();

        if ( $error ) {
            wp_safe_redirect( add_query_arg( 'social_error', urlencode( 'Facebook login bị huỷ.' ), $auth_page ) );
            exit;
        }

        if ( ! $state || ! get_transient( 'bacera_oauth_state_' . $state ) ) {
            wp_safe_redirect( add_query_arg( 'social_error', urlencode( 'Phiên OAuth không hợp lệ.' ), $auth_page ) );
            exit;
        }
        delete_transient( 'bacera_oauth_state_' . $state );

        if ( empty( $code ) ) {
            wp_safe_redirect( add_query_arg( 'social_error', urlencode( 'Không nhận được mã từ Facebook.' ), $auth_page ) );
            exit;
        }

        $token_resp = wp_remote_get( 'https://graph.facebook.com/v19.0/oauth/access_token?' . http_build_query( [
            'client_id'     => get_option( 'bacera_facebook_app_id', '' ),
            'client_secret' => get_option( 'bacera_facebook_app_secret', '' ),
            'redirect_uri'  => home_url( '/bacera-auth/facebook/callback' ),
            'code'          => $code,
        ] ), [ 'timeout' => 15 ] );

        if ( is_wp_error( $token_resp ) ) {
            wp_safe_redirect( add_query_arg( 'social_error', urlencode( 'Lỗi kết nối Facebook.' ), $auth_page ) );
            exit;
        }

        $token_data   = json_decode( wp_remote_retrieve_body( $token_resp ), true );
        $access_token = $token_data['access_token'] ?? '';

        if ( empty( $access_token ) ) {
            wp_safe_redirect( add_query_arg( 'social_error', urlencode( 'Lỗi lấy access token Facebook.' ), $auth_page ) );
            exit;
        }

        $user_resp = wp_remote_get( 'https://graph.facebook.com/me?' . http_build_query( [
            'fields'       => 'id,name,email',
            'access_token' => $access_token,
        ] ), [ 'timeout' => 10 ] );

        if ( is_wp_error( $user_resp ) ) {
            wp_safe_redirect( add_query_arg( 'social_error', urlencode( 'Không lấy được thông tin từ Facebook.' ), $auth_page ) );
            exit;
        }

        $info  = json_decode( wp_remote_retrieve_body( $user_resp ), true );
        $email = sanitize_email( $info['email'] ?? '' );
        $name  = sanitize_text_field( $info['name']  ?? 'Facebook User' );
        $fb_id = sanitize_text_field( $info['id']    ?? '' );

        if ( empty( $email ) && ! empty( $fb_id ) ) {
            $email = $fb_id . '@fb.noemail.invalid';
        }

        if ( empty( $email ) ) {
            wp_safe_redirect( add_query_arg( 'social_error', urlencode( 'Tài khoản Facebook không có email.' ), $auth_page ) );
            exit;
        }

        $user_id = $this->find_or_create_social_customer( $email, $name, 'facebook' );
        $this->login_and_redirect( $user_id );
    }

    /* ── Shared helpers ──────────────────────────────────────────── */

    private function find_or_create_social_customer( string $email, string $name, string $provider ): int {
        global $wpdb;
        $table = $wpdb->prefix . 'bacera_customers';

        // Ensure social_provider column exists
        static $col_checked = false;
        if ( ! $col_checked ) {
            if ( empty( $wpdb->get_col( "SHOW COLUMNS FROM {$table} LIKE 'social_provider'" ) ) ) {
                $wpdb->query( "ALTER TABLE {$table} ADD COLUMN social_provider varchar(50) NULL AFTER has_password" );
            }
            $col_checked = true;
        }

        $existing = $wpdb->get_var(
            $wpdb->prepare( "SELECT id FROM {$table} WHERE email = %s LIMIT 1", $email )
        );

        if ( $existing ) {
            return (int) $existing;
        }

        $wpdb->insert( $table, [
            'email'               => $email,
            'name'                => $name,
            'phone'               => '',
            'has_password'        => 0,
            'social_provider'     => $provider,
            'created_at'          => current_time( 'mysql' ),
            'password_updated_at' => current_time( 'mysql' ),
        ] );

        $new_id = (int) $wpdb->insert_id;
        if ( $new_id && class_exists( '\\Bacera_Module_Customers', false ) ) {
            \Bacera_Module_Customers::sync_bacera_customer_row_to_pancake( $new_id );
        }

        return $new_id;
    }

    private function login_and_redirect( int $user_id ) {
        $token   = wp_generate_password( 32, false );
        $payload = base64_encode( $user_id . '|' . $token );
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

        $_COOKIE['bacera_customer_auth'] = $payload;

        wp_safe_redirect( home_url( '/' ) );
        exit;
    }

    private function get_auth_page_url(): string {
        global $wpdb;
        $page_id = (int) $wpdb->get_var(
            "SELECT p.ID FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type='page' AND p.post_status='publish'
               AND pm.meta_key='_wp_page_template'
               AND pm.meta_value IN ('templates/template-auth.php','template-auth.php')
             LIMIT 1"
        );
        return $page_id ? (string) get_permalink( $page_id ) : home_url( '/auth/' );
    }
}
