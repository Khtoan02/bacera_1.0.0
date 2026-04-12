<?php
namespace Bacera\Controllers;

/**
 * Admin settings page for Email / SMTP configuration.
 * Config keys stored in wp_options:
 *   bacera_email_from_name
 *   bacera_email_from_address
 *   bacera_smtp_host
 *   bacera_smtp_port
 *   bacera_smtp_user
 *   bacera_smtp_pass
 *   bacera_smtp_secure   (tls / ssl / none)
 *   bacera_smtp_enabled  (1 / 0)
 */
class EmailSettingsController {

    public function __construct() {
        add_action( 'admin_menu',  [ $this, 'add_settings_page' ] );
        add_action( 'admin_init',  [ $this, 'register_settings' ] );
        add_action( 'phpmailer_init', [ $this, 'configure_phpmailer' ] );

        // AJAX: send test email
        add_action( 'wp_ajax_bacera_send_test_email', [ $this, 'handle_test_email' ] );
    }

    /* ── Admin menu ──────────────────────────────────────────────── */

    public function add_settings_page() {
        add_submenu_page(
            'bacera-main',
            'Cấu hình Email',
            '✉️ Email Config',
            'manage_options',
            'bacera-email-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    /* ── Settings registration ───────────────────────────────────── */

    public function register_settings() {
        $fields = [
            'bacera_smtp_enabled',
            'bacera_email_from_name',
            'bacera_email_from_address',
            'bacera_smtp_host',
            'bacera_smtp_port',
            'bacera_smtp_user',
            'bacera_smtp_pass',
            'bacera_smtp_secure',
        ];
        foreach ( $fields as $key ) {
            register_setting( 'bacera_email_group', $key, [ 'sanitize_callback' => 'sanitize_text_field' ] );
        }
    }

    /* ── PHPMailer hook: override WP mail with SMTP ──────────────── */

    public function configure_phpmailer( $phpmailer ) {
        if ( ! get_option( 'bacera_smtp_enabled', '0' ) ) {
            return;
        }

        $host    = get_option( 'bacera_smtp_host', '' );
        $port    = (int) get_option( 'bacera_smtp_port', 587 );
        $user    = get_option( 'bacera_smtp_user', '' );
        $pass    = get_option( 'bacera_smtp_pass', '' );
        $secure  = get_option( 'bacera_smtp_secure', 'tls' );
        $from    = get_option( 'bacera_email_from_address', get_option( 'admin_email' ) );
        $name    = get_option( 'bacera_email_from_name', get_bloginfo( 'name' ) );

        if ( empty( $host ) ) return;

        $phpmailer->isSMTP();
        $phpmailer->Host       = $host;
        $phpmailer->SMTPAuth   = ! empty( $user );
        $phpmailer->Username   = $user;
        $phpmailer->Password   = $pass;
        $phpmailer->Port       = $port;
        $phpmailer->From       = $from;
        $phpmailer->FromName   = $name;

        if ( $secure === 'ssl' ) {
            $phpmailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ( $secure === 'tls' ) {
            $phpmailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $phpmailer->SMTPSecure = '';
            $phpmailer->SMTPAutoTLS = false;
        }
    }

    /* ── AJAX: test email ────────────────────────────────────────── */

    public function handle_test_email() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Không có quyền.' ] );
        }
        $to = sanitize_email( $_POST['to'] ?? get_option( 'admin_email' ) );
        $result = wp_mail(
            $to,
            '[Bacera] Email test thành công',
            '<p>Chúc mừng! Cấu hình SMTP đang hoạt động tốt.</p>',
            [ 'Content-Type: text/html; charset=UTF-8' ]
        );
        if ( $result ) {
            wp_send_json_success( [ 'message' => "Đã gửi email test tới {$to}" ] );
        } else {
            global $phpmailer;
            $err = ! empty( $phpmailer->ErrorInfo ) ? $phpmailer->ErrorInfo : 'Không rõ lỗi.';
            wp_send_json_error( [ 'message' => "Gửi thất bại: {$err}" ] );
        }
    }

    /* ── Static helper: send OTP email ──────────────────────────── */

    /**
     * Send an OTP code to the given email address.
     * Called from AuthController.
     *
     * @param string $to    Recipient email.
     * @param string $otp   4-digit OTP string.
     * @param string $type  'login' | 'register' | 'update'
     * @return bool
     */
    public static function send_otp_email( string $to, string $otp, string $type = 'login' ): bool {
        $site    = get_bloginfo( 'name' );
        $labels  = [
            'login'    => 'đăng nhập',
            'register' => 'đăng ký',
            'update'   => 'xác nhận cập nhật',
        ];
        $label = $labels[ $type ] ?? 'xác thực';

        $subject = "[{$site}] Mã OTP {$label} của bạn";
        $body    = "
        <div style='font-family:Inter,sans-serif;max-width:480px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;'>
            <div style='background:#1c1917;padding:24px 32px;'>
                <h1 style='color:#fff;font-size:22px;margin:0;font-weight:600;'>{$site}</h1>
            </div>
            <div style='padding:32px;'>
                <p style='color:#374151;font-size:15px;margin:0 0 16px;'>Chào bạn,</p>
                <p style='color:#374151;font-size:15px;margin:0 0 24px;'>Mã OTP để <strong>{$label}</strong> tài khoản của bạn là:</p>
                <div style='background:#fef3c7;border:2px dashed #f59e0b;border-radius:10px;padding:20px;text-align:center;margin-bottom:24px;'>
                    <span style='font-size:40px;font-weight:700;letter-spacing:12px;color:#1c1917;'>{$otp}</span>
                </div>
                <p style='color:#6b7280;font-size:13px;margin:0;'>Mã có hiệu lực trong <strong>10 phút</strong>. Không chia sẻ mã này với bất kỳ ai.</p>
            </div>
            <div style='background:#f9fafb;padding:16px 32px;border-top:1px solid #e5e7eb;'>
                <p style='color:#9ca3af;font-size:12px;margin:0;'>Email này được gửi tự động từ {$site}. Vui lòng không trả lời.</p>
            </div>
        </div>";

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
        ];

        $from_name    = get_option( 'bacera_email_from_name', $site );
        $from_address = get_option( 'bacera_email_from_address', get_option( 'admin_email' ) );
        if ( $from_address ) {
            $headers[] = "From: {$from_name} <{$from_address}>";
        }

        return wp_mail( $to, $subject, $body, $headers );
    }

    /* ── Render admin page ───────────────────────────────────────── */

    public function render_settings_page() {
        $enabled  = get_option( 'bacera_smtp_enabled', '0' );
        $from_n   = get_option( 'bacera_email_from_name', get_bloginfo( 'name' ) );
        $from_e   = get_option( 'bacera_email_from_address', get_option( 'admin_email' ) );
        $host     = get_option( 'bacera_smtp_host', '' );
        $port     = get_option( 'bacera_smtp_port', '587' );
        $user     = get_option( 'bacera_smtp_user', '' );
        $pass     = get_option( 'bacera_smtp_pass', '' );
        $secure   = get_option( 'bacera_smtp_secure', 'tls' );
        $nonce    = wp_create_nonce( 'bacera_test_email' );
        ?>
        <div class="wrap" style="max-width:760px;">
            <h1 style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                ✉️ Cấu hình Email (SMTP)
            </h1>
            <p style="color:#6b7280;margin:0 0 24px;">
                Cấu hình SMTP để gửi <strong>OTP xác thực</strong> qua email cho người dùng.
                Hỗ trợ Gmail, Outlook, SendGrid, Mailgun, …
            </p>

            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:14px 18px;margin-bottom:24px;font-size:13px;color:#92400e;">
                <strong>💡 Gợi ý nhanh:</strong>
                Gmail: host <code>smtp.gmail.com</code> · port <code>587</code> · TLS · dùng <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color:#b45309;">App Password</a> thay mật khẩu thường.
            </div>

            <?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ): ?>
            <div style="background:#dcfce7;border:1px solid #86efac;border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#166534;">
                ✔ Đã lưu cài đặt thành công!
            </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields( 'bacera_email_group' ); ?>

                <table class="form-table" style="background:#fff;border-radius:8px;border:1px solid #e5e7eb;overflow:hidden;">
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <th style="padding:14px 20px;width:220px;font-weight:600;color:#374151;">Bật SMTP</th>
                        <td style="padding:14px 20px;">
                            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                                <input type="checkbox" name="bacera_smtp_enabled" value="1" <?php checked( $enabled, '1' ); ?> style="width:18px;height:18px;">
                                <span style="font-size:14px;color:#374151;">Sử dụng SMTP (tắt = WP Mail mặc định)</span>
                            </label>
                        </td>
                    </tr>
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <th style="padding:14px 20px;font-weight:600;color:#374151;">Tên người gửi</th>
                        <td style="padding:14px 20px;">
                            <input type="text" name="bacera_email_from_name" value="<?php echo esc_attr( $from_n ); ?>"
                                   style="<?php echo $this->input_style(); ?>" placeholder="Bacera Workshop">
                        </td>
                    </tr>
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <th style="padding:14px 20px;font-weight:600;color:#374151;">Email người gửi</th>
                        <td style="padding:14px 20px;">
                            <input type="email" name="bacera_email_from_address" value="<?php echo esc_attr( $from_e ); ?>"
                                   style="<?php echo $this->input_style(); ?>" placeholder="no-reply@example.com">
                        </td>
                    </tr>
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <th style="padding:14px 20px;font-weight:600;color:#374151;">SMTP Host</th>
                        <td style="padding:14px 20px;">
                            <input type="text" name="bacera_smtp_host" value="<?php echo esc_attr( $host ); ?>"
                                   style="<?php echo $this->input_style(); ?>" placeholder="smtp.gmail.com">
                        </td>
                    </tr>
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <th style="padding:14px 20px;font-weight:600;color:#374151;">SMTP Port</th>
                        <td style="padding:14px 20px;">
                            <input type="number" name="bacera_smtp_port" value="<?php echo esc_attr( $port ); ?>"
                                   style="<?php echo $this->input_style(); ?> max-width:120px;" placeholder="587">
                        </td>
                    </tr>
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <th style="padding:14px 20px;font-weight:600;color:#374151;">Mã hóa</th>
                        <td style="padding:14px 20px;">
                            <select name="bacera_smtp_secure" style="<?php echo $this->input_style(); ?> max-width:160px;">
                                <option value="tls"  <?php selected( $secure, 'tls' ); ?>>TLS (port 587)</option>
                                <option value="ssl"  <?php selected( $secure, 'ssl' ); ?>>SSL (port 465)</option>
                                <option value="none" <?php selected( $secure, 'none' ); ?>>Không mã hóa</option>
                            </select>
                        </td>
                    </tr>
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <th style="padding:14px 20px;font-weight:600;color:#374151;">SMTP Username</th>
                        <td style="padding:14px 20px;">
                            <input type="text" name="bacera_smtp_user" value="<?php echo esc_attr( $user ); ?>"
                                   style="<?php echo $this->input_style(); ?>" placeholder="your@gmail.com">
                        </td>
                    </tr>
                    <tr>
                        <th style="padding:14px 20px;font-weight:600;color:#374151;">SMTP Password</th>
                        <td style="padding:14px 20px;">
                            <input type="password" name="bacera_smtp_pass" value="<?php echo esc_attr( $pass ); ?>"
                                   style="<?php echo $this->input_style(); ?>" placeholder="App Password / SMTP Password">
                            <p style="margin:6px 0 0;color:#6b7280;font-size:12px;">Gmail: tạo <a href="https://myaccount.google.com/apppasswords" target="_blank">App Password</a> tại Google Account → Security.</p>
                        </td>
                    </tr>
                </table>

                <div style="margin-top:20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                    <?php submit_button( 'Lưu cài đặt', 'primary', 'submit', false, [ 'style' => 'height:40px;padding:0 24px;border-radius:6px;' ] ); ?>

                    <button type="button" id="bacera-test-email-btn"
                            style="height:40px;padding:0 20px;border-radius:6px;background:#f3f4f6;border:1px solid #d1d5db;color:#374151;font-size:14px;cursor:pointer;">
                        📧 Gửi email test
                    </button>
                    <span id="bacera-test-email-result" style="font-size:13px;"></span>
                </div>
            </form>
        </div>

        <script>
        document.getElementById('bacera-test-email-btn').addEventListener('click', function() {
            const btn = this;
            const result = document.getElementById('bacera-test-email-result');
            btn.disabled = true;
            btn.textContent = 'Đang gửi…';
            result.textContent = '';
            result.style.color = '';
            fetch(ajaxurl, {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'bacera_send_test_email',
                    to: '<?php echo esc_js( get_option( 'admin_email' ) ); ?>',
                    _ajax_nonce: '<?php echo esc_js( $nonce ); ?>'
                })
            })
            .then(r => r.json())
            .then(data => {
                result.textContent = data.data?.message || (data.success ? 'Thành công!' : 'Thất bại!');
                result.style.color = data.success ? '#16a34a' : '#dc2626';
            })
            .catch(() => { result.textContent = 'Lỗi kết nối.'; result.style.color = '#dc2626'; })
            .finally(() => { btn.disabled = false; btn.textContent = '📧 Gửi email test'; });
        });
        </script>
        <?php
    }

    private function input_style(): string {
        return 'width:100%;max-width:420px;height:40px;padding:0 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;';
    }
}
