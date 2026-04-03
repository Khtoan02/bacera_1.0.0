<?php
namespace Bacera\Controllers;

class TurnstileSettingsController {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function add_settings_page() {
        add_submenu_page(
            'bacera-main',
            'Cài đặt CAPTCHA',
            'CAPTCHA (Turnstile)',
            'manage_options',
            'bacera-captcha-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings() {
        register_setting( 'bacera_captcha_group', 'bacera_turnstile_site_key',   [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'bacera_captcha_group', 'bacera_turnstile_secret_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );
    }

    public function render_settings_page() {
        $site_key   = get_option( 'bacera_turnstile_site_key', '' );
        $secret_key = get_option( 'bacera_turnstile_secret_key', '' );
        ?>
        <div class="wrap" style="max-width:720px;">
            <h1 style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                <span>&#x1F6E1;&#xFE0F; Cài đặt CAPTCHA</span>
            </h1>
            <p style="color:#6b7280;margin-top:0 0 24px;">
                Sử dụng <strong>Cloudflare Turnstile</strong> — miễn phí, thân thiện với người dùng, không cần checkbox "Tôi không phải Robot".
            </p>

            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:16px 20px;margin-bottom:24px;">
                <strong style="color:#0369a1;">Hướng dẫn lấy Key:</strong>
                <ol style="margin:8px 0 0 16px;color:#0369a1;font-size:13px;line-height:1.7;">
                    <li>Truy cập <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" style="color:#0369a1;">dash.cloudflare.com → Turnstile</a></li>
                    <li>Nhấn <strong>Add site</strong>, nhập tên và domain của bạn</li>
                    <li>Chọn Widget type: <strong>Managed</strong> (khuyến nghị)</li>
                    <li>Copy <strong>Site Key</strong> và <strong>Secret Key</strong> vào ô dưới</li>
                </ol>
            </div>

            <?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ): ?>
            <div style="background:#dcfce7;border:1px solid #86efac;border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#166534;">
                &#x2714; Đã lưu cài đặt thành công!
            </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields( 'bacera_captcha_group' ); ?>

                <table class="form-table" style="background:#fff;border-radius:8px;border:1px solid #e5e7eb;overflow:hidden;">
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <th style="padding:16px 20px;width:200px;font-weight:600;color:#374151;">Site Key <span style="color:#ef4444;">*</span></th>
                        <td style="padding:16px 20px;">
                            <input type="text" name="bacera_turnstile_site_key"
                                   value="<?php echo esc_attr( $site_key ); ?>"
                                   style="width:100%;max-width:480px;height:40px;padding:0 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;font-family:monospace;"
                                   placeholder="0x4AAAAAAA...">
                            <p style="margin:6px 0 0;color:#6b7280;font-size:12px;">Hiển thị trên widget CAPTCHA phía trình duyệt</p>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding:16px 20px;font-weight:600;color:#374151;">Secret Key <span style="color:#ef4444;">*</span></th>
                        <td style="padding:16px 20px;">
                            <input type="password" name="bacera_turnstile_secret_key"
                                   value="<?php echo esc_attr( $secret_key ); ?>"
                                   style="width:100%;max-width:480px;height:40px;padding:0 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;font-family:monospace;"
                                   placeholder="0x4AAAAAAA...">
                            <p style="margin:6px 0 0;color:#6b7280;font-size:12px;">Chỉ dùng phía server để xác minh — không lộ ra ngoài</p>
                        </td>
                    </tr>
                </table>

                <div style="margin-top:20px;display:flex;align-items:center;gap:16px;">
                    <?php submit_button( 'Lưu cài đặt', 'primary', 'submit', false, [ 'style' => 'height:40px;padding:0 24px;border-radius:6px;' ] ); ?>

                    <?php if ( $site_key ): ?>
                    <span style="color:#16a34a;font-size:13px;">&#x2705; CAPTCHA đang hoạt động</span>
                    <?php else: ?>
                    <span style="color:#d97706;font-size:13px;">&#x26A0;&#xFE0F; Chưa cấu hình — CAPTCHA đang bị tắt</span>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ( $site_key ): ?>
            <div style="margin-top:32px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:20px;">
                <h3 style="margin:0 0 12px;font-size:14px;color:#374151;">Xem thử Widget</h3>
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                <div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $site_key ); ?>" data-theme="light"></div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
