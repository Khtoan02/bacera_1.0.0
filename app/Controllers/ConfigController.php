<?php
namespace Bacera\Controllers;

/**
 * Bacera Config Controller
 * ─────────────────────────────────────────────────────────────────
 * Single admin page "⚙️ Cấu hình" with tabs:
 *   • mail       — SMTP / Email OTP
 *   • google     — Google OAuth 2.0
 *   • facebook   — Facebook Login
 *   • captcha    — Cloudflare Turnstile
 *
 * Option keys:
 *   bacera_smtp_enabled, bacera_email_from_name, bacera_email_from_address,
 *   bacera_smtp_host, bacera_smtp_port, bacera_smtp_user, bacera_smtp_pass, bacera_smtp_secure
 *   bacera_google_enabled, bacera_google_client_id, bacera_google_client_secret
 *   bacera_facebook_enabled, bacera_facebook_app_id, bacera_facebook_app_secret
 *   bacera_turnstile_site_key, bacera_turnstile_secret_key
 */
class ConfigController {

    private array $tabs = [
        'mail'     => ['label' => '✉️ Mail Config',     'icon' => '✉️'],
        'google'   => ['label' => '🔵 Google Login',    'icon' => '🔵'],
        'facebook' => ['label' => '📘 Facebook Login',  'icon' => '📘'],
        'captcha'  => ['label' => '🛡️ CAPTCHA',        'icon' => '🛡️'],
    ];

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_init', [ $this, 'register_all_settings' ] );

        // AJAX: test email (admin only)
        add_action( 'wp_ajax_bacera_send_test_email', [ $this, 'ajax_test_email' ] );
    }

    /* ── Menu ────────────────────────────────────────────────────── */

    public function add_menu() {
        add_submenu_page(
            'bacera-main',
            'Cấu hình',
            'Cấu hình',
            'manage_options',
            'bacera-config',
            [ $this, 'render_page' ]
        );
    }

    /* ── Register settings ───────────────────────────────────────── */

    public function register_all_settings() {
        $all = [
            // Mail
            'bacera_smtp_enabled', 'bacera_email_from_name', 'bacera_email_from_address',
            'bacera_smtp_host', 'bacera_smtp_port', 'bacera_smtp_user',
            'bacera_smtp_pass', 'bacera_smtp_secure',
            // Google
            'bacera_google_enabled', 'bacera_google_client_id', 'bacera_google_client_secret',
            // Facebook
            'bacera_facebook_enabled', 'bacera_facebook_app_id', 'bacera_facebook_app_secret',
            // CAPTCHA
            'bacera_turnstile_site_key', 'bacera_turnstile_secret_key',
        ];
        foreach ( $all as $key ) {
            register_setting( 'bacera_config_group', $key, [
                'sanitize_callback' => 'sanitize_text_field',
            ] );
        }
    }

    /* ── SMTP config (static — hooked from MainController on all requests) ── */

    public static function static_configure_phpmailer( $phpmailer ): void {
        if ( ! get_option( 'bacera_smtp_enabled', '0' ) ) return;

        $host   = get_option( 'bacera_smtp_host', '' );
        $port   = (int) get_option( 'bacera_smtp_port', 587 );
        $user   = get_option( 'bacera_smtp_user', '' );
        $pass   = get_option( 'bacera_smtp_pass', '' );
        $secure = get_option( 'bacera_smtp_secure', 'tls' );
        $from   = get_option( 'bacera_email_from_address', get_option( 'admin_email' ) );
        $name   = get_option( 'bacera_email_from_name', get_bloginfo( 'name' ) );

        if ( empty( $host ) ) return;

        $phpmailer->isSMTP();
        $phpmailer->Host     = $host;
        $phpmailer->SMTPAuth = ! empty( $user );
        $phpmailer->Username = $user;
        $phpmailer->Password = $pass;
        $phpmailer->Port     = $port;
        $phpmailer->From     = $from;
        $phpmailer->FromName = $name;

        if ( $secure === 'ssl' ) {
            $phpmailer->SMTPSecure = 'smtps';
        } elseif ( $secure === 'tls' ) {
            $phpmailer->SMTPSecure = 'tls';
        } else {
            $phpmailer->SMTPSecure  = '';
            $phpmailer->SMTPAutoTLS = false;
        }
    }

    /* ── Static: send OTP email ──────────────────────────────────── */

    public static function send_otp_email( string $to, string $otp, string $type = 'login' ): bool {
        $site   = get_bloginfo( 'name' );
        $labels = [ 'login' => 'đăng nhập', 'register' => 'đăng ký', 'update' => 'xác nhận cập nhật' ];
        $label  = $labels[ $type ] ?? 'xác thực';

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
                <p style='color:#9ca3af;font-size:12px;margin:0;'>Email tự động từ {$site} — vui lòng không trả lời.</p>
            </div>
        </div>";

        $from_n   = get_option( 'bacera_email_from_name', $site );
        $from_e   = get_option( 'bacera_email_from_address', get_option( 'admin_email' ) );
        $headers  = [ 'Content-Type: text/html; charset=UTF-8' ];
        if ( $from_e ) {
            $headers[] = "From: {$from_n} <{$from_e}>";
        }

        return wp_mail( $to, $subject, $body, $headers );
    }

    /* ── AJAX: send test email ───────────────────────────────────── */

    public function ajax_test_email() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Không có quyền.' ] );
        }
        check_ajax_referer( 'bacera_test_email', '_ajax_nonce' );
        $to     = sanitize_email( $_POST['to'] ?? get_option( 'admin_email' ) );
        $result = wp_mail(
            $to,
            '[Bacera] Email test thành công ✅',
            '<p style="font-family:sans-serif;">Chúc mừng! Cấu hình SMTP đang hoạt động tốt.</p>',
            [ 'Content-Type: text/html; charset=UTF-8' ]
        );
        if ( $result ) {
            wp_send_json_success( [ 'message' => "✅ Đã gửi email test tới <strong>{$to}</strong>" ] );
        } else {
            global $phpmailer;
            $err = ! empty( $phpmailer->ErrorInfo ) ? $phpmailer->ErrorInfo : 'Không rõ lỗi.';
            wp_send_json_error( [ 'message' => "❌ Gửi thất bại: {$err}" ] );
        }
    }

    /* ── Enqueue styles for config page only ─────────────────────── */

    public function enqueue_styles( $hook ) {
        if ( strpos( $hook, 'bacera-config' ) === false ) return;
        // No extra files needed — all inline below
    }

    /* ══════════════════════════════════════════════════════════════
       RENDER PAGE
    ══════════════════════════════════════════════════════════════ */

    public function render_page() {
        $active_tab = sanitize_key( $_GET['tab'] ?? 'mail' );
        if ( ! isset( $this->tabs[ $active_tab ] ) ) {
            $active_tab = 'mail';
        }
        $saved = isset( $_GET['settings-updated'] ) && $_GET['settings-updated'];
        $nonce = wp_create_nonce( 'bacera_test_email' );

        // Gather status for sidebar badges
        $smtp_on = get_option('bacera_smtp_enabled') && get_option('bacera_smtp_host');
        $gg_on   = get_option('bacera_google_enabled') && get_option('bacera_google_client_id');
        $fb_on   = get_option('bacera_facebook_enabled') && get_option('bacera_facebook_app_id');
        $ts_on   = (bool) get_option('bacera_turnstile_site_key');

        $nav_items = [
            'mail'     => ['icon' => '✉️',  'label' => 'Mail & SMTP',      'on' => $smtp_on],
            'google'   => ['icon' => '🔵',  'label' => 'Google Login',     'on' => $gg_on],
            'facebook' => ['icon' => '📘',  'label' => 'Facebook Login',   'on' => $fb_on],
            'captcha'  => ['icon' => '🛡️', 'label' => 'CAPTCHA',           'on' => $ts_on],
        ];
        ?>
        <style>
        /* ── Layout ── */
        .bcfg-page   { display:flex; gap:28px; max-width:1060px; padding:16px 0 40px; font-family:"Inter",-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; }
        .bcfg-sidebar { width:216px; flex-shrink:0; }
        .bcfg-content { flex:1; min-width:0; }

        /* ── Sidebar brand block ── */
        .bcfg-brand { background:linear-gradient(135deg,#1c1917,#3d2f26); border-radius:12px; padding:20px; margin-bottom:16px; }
        .bcfg-brand h2 { color:#fff; font-size:15px; font-weight:700; margin:0 0 2px; }
        .bcfg-brand p  { color:#a8a29e; font-size:11px; margin:0; }

        /* ── Sidebar nav ── */
        .bcfg-nav { background:#fff; border:1px solid #eae3d1; border-radius:12px; overflow:hidden; }
        .bcfg-nav a {
            display:flex; align-items:center; gap:10px; padding:13px 16px;
            font-size:13px; font-weight:500; color:#57534e; text-decoration:none;
            border-bottom:1px solid #f5f0e8; transition:all .15s;
        }
        .bcfg-nav a:last-child { border-bottom:none; }
        .bcfg-nav a:hover  { background:#fdf8f4; color:#1c1917; }
        .bcfg-nav a.active { background:#fff6f4; color:#d95f47; font-weight:700; border-left:3px solid #d95f47; padding-left:13px; }
        .bcfg-nav .nav-icon { font-size:16px; width:20px; text-align:center; }
        .bcfg-nav .nav-badge { margin-left:auto; width:8px; height:8px; border-radius:50%; background:#e5e7eb; flex-shrink:0; }
        .bcfg-nav .nav-badge.on { background:#22c55e; }

        /* ── Content header ── */
        .bcfg-ch { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; padding-bottom:16px; border-bottom:1px solid #f0ebe0; }
        .bcfg-ch h1 { font-size:20px; font-weight:700; color:#1c1917; margin:0; display:flex; align-items:center; gap:8px; }
        .bcfg-badge-on  { background:#dcfce7; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
        .bcfg-badge-off { background:#fef3c7; color:#92400e; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }

        /* ── Cards ── */
        .bcfg-card { background:#fff; border:1px solid #eae3d1; border-radius:12px; overflow:hidden; margin-bottom:20px; }
        .bcfg-card-header { background:#faf8f5; border-bottom:1px solid #eae3d1; padding:14px 22px; display:flex; align-items:center; gap:10px; }
        .bcfg-card-header h2 { margin:0; font-size:14px; font-weight:700; color:#1c1917; }
        .bcfg-card-body { padding:22px; }

        /* ── Info / Warn boxes ── */
        .bcfg-info { background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; padding:14px 16px; margin-bottom:20px; font-size:13px; color:#0369a1; line-height:1.7; }
        .bcfg-info strong { color:#0c4a6e; }
        .bcfg-info ol { margin:6px 0 0 18px; }
        .bcfg-info a  { color:#0369a1; }
        .bcfg-warn { background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:13px 16px; margin-bottom:20px; font-size:13px; color:#92400e; line-height:1.6; }
        .bcfg-warn code { background:rgba(0,0,0,.06); padding:1px 5px; border-radius:4px; font-size:12px; }
        .bcfg-warn a { color:#92400e; }

        /* ── Form grid ── */
        .bcfg-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .bcfg-grid.cols1 { grid-template-columns:1fr; }
        .bcfg-field { display:flex; flex-direction:column; gap:6px; }
        .bcfg-field label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#6b5344; }
        .bcfg-field input, .bcfg-field select {
            padding:10px 14px; font-size:14px; color:#3d2f26; background:#fff;
            border:1px solid #ded5cd; border-radius:8px;
            box-shadow:0 1px 2px rgba(0,0,0,.03); transition:all .2s;
            height:42px; width:100%;
        }
        .bcfg-field input:focus, .bcfg-field select:focus {
            border-color:#d95f47; box-shadow:0 0 0 3px rgba(217,95,71,.15); outline:none;
        }
        .bcfg-field input[type=password] { letter-spacing:.1em; }
        .bcfg-hint { font-size:11px; color:#9ca3af; margin-top:3px; line-height:1.5; }
        .bcfg-hint a { color:#9ca3af; }

        /* ── Toggle ── */
        .bcfg-toggle-wrap { display:flex; align-items:center; gap:12px; padding:4px 0; }
        .bcfg-toggle-wrap input[type=checkbox] { width:18px; height:18px; accent-color:#d95f47; cursor:pointer; flex-shrink:0; }
        .bcfg-toggle-wrap span { font-size:13px; color:#3d2f26; }

        /* ── Divider ── */
        .bcfg-divider { border:0; border-top:1px solid #f1ede1; margin:20px 0; }

        /* ── Pill (copy URL) ── */
        .bcfg-pill { display:inline-flex; align-items:center; gap:10px; background:#f5f0e8; border:1px solid #eae3d1; border-radius:8px; padding:8px 14px; font-size:12px; font-family:monospace; color:#57534e; max-width:100%; overflow:hidden; }
        .bcfg-pill span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .bcfg-copy-btn { background:#fff; border:1px solid #ded5cd; border-radius:6px; padding:4px 10px; font-size:11px; cursor:pointer; color:#78716c; transition:all .15s; font-family:sans-serif; white-space:nowrap; }
        .bcfg-copy-btn:hover { background:#d95f47; color:#fff; border-color:#d95f47; }

        /* ── Footer / Actions ── */
        .bcfg-footer { display:flex; align-items:center; gap:14px; padding-top:4px; flex-wrap:wrap; }
        .bcfg-btn-primary { height:42px; padding:0 24px; background:#d95f47; color:#fff; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; transition:background .15s; }
        .bcfg-btn-primary:hover  { background:#b84d38; }
        .bcfg-btn-secondary { height:42px; padding:0 20px; background:#f5f0e8; color:#6b5344; border:1px solid #ded5cd; border-radius:8px; font-size:14px; font-weight:500; cursor:pointer; transition:all .15s; }
        .bcfg-btn-secondary:hover { background:#eae3d1; color:#3d2f26; }
        #bcfg-test-result { font-size:13px; }

        /* ── Success bar ── */
        .bcfg-saved-bar { background:#dcfce7; border:1px solid #86efac; border-radius:10px; padding:12px 18px; margin-bottom:22px; font-size:13px; color:#166534; display:flex; align-items:center; gap:8px; }
        </style>

        <div class="bcfg-page">

            <!-- ── SIDEBAR ── -->
            <aside class="bcfg-sidebar">
                <div class="bcfg-brand">
                    <h2>⚙️ Cấu hình</h2>
                    <p>Bacera Theme v<?php echo BACERA_THEME_VERSION; ?></p>
                </div>
                <nav class="bcfg-nav">
                    <?php foreach ($nav_items as $slug => $item):
                        $url = admin_url('admin.php?page=bacera-config&tab=' . $slug);
                        $cls = $active_tab === $slug ? 'active' : '';
                    ?>
                    <a href="<?php echo esc_url($url); ?>" class="<?php echo $cls; ?>">
                        <span class="nav-icon"><?php echo $item['icon']; ?></span>
                        <?php echo $item['label']; ?>
                        <span class="nav-badge <?php echo $item['on'] ? 'on' : ''; ?>"></span>
                    </a>
                    <?php endforeach; ?>
                </nav>
            </aside>

            <!-- ── CONTENT ── -->
            <div class="bcfg-content">

                <?php if ($saved): ?>
                <div class="bcfg-saved-bar">✅ Đã lưu cài đặt thành công!</div>
                <?php endif; ?>

                <form method="post" action="options.php">
                    <?php settings_fields('bacera_config_group'); ?>
                    <?php if ($active_tab === 'mail')     $this->tab_mail($nonce); ?>
                    <?php if ($active_tab === 'google')   $this->tab_google(); ?>
                    <?php if ($active_tab === 'facebook') $this->tab_facebook(); ?>
                    <?php if ($active_tab === 'captcha')  $this->tab_captcha(); ?>
                </form>

            </div>
        </div>

        <script>
        // Copy-to-clipboard
        document.querySelectorAll('.bcfg-copy-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                navigator.clipboard.writeText(btn.dataset.copy).then(() => {
                    const o = btn.textContent;
                    btn.textContent = '✓ Đã copy';
                    setTimeout(() => btn.textContent = o, 1800);
                });
            });
        });

        // Test email AJAX
        const testBtn = document.getElementById('bcfg-test-email');
        if (testBtn) {
            testBtn.addEventListener('click', () => {
                const result = document.getElementById('bcfg-test-result');
                testBtn.disabled = true; testBtn.textContent = 'Đang gửi…'; result.innerHTML = '';
                fetch(ajaxurl, {
                    method:'POST',
                    body: new URLSearchParams({
                        action:'bacera_send_test_email',
                        to:'<?php echo esc_js(get_option('admin_email')); ?>',
                        _ajax_nonce:'<?php echo esc_js($nonce); ?>'
                    })
                }).then(r=>r.json()).then(data=>{
                    result.innerHTML = data.data?.message || (data.success?'✅ Thành công!':'❌ Thất bại!');
                    result.style.color = data.success?'#16a34a':'#dc2626';
                }).catch(()=>{result.innerHTML='❌ Lỗi kết nối.';result.style.color='#dc2626';})
                .finally(()=>{testBtn.disabled=false;testBtn.textContent='📧 Gửi email test';});
            });
        }

        // Turnstile preview
        const tsKey = document.getElementById('ts-site-key-input');
        if (tsKey) {
            tsKey.addEventListener('change', () => {
                const p = document.getElementById('ts-widget-preview');
                if (p && tsKey.value) p.innerHTML = '<div class="cf-turnstile" data-sitekey="'+tsKey.value+'" data-theme="light"></div>';
            });
        }
        </script>
        <?php
    }

    /* ══════════════════════════════════════════════════════════════
       TAB: MAIL CONFIG
    ══════════════════════════════════════════════════════════════ */
    private function tab_mail(string $nonce) {
        $enabled = get_option('bacera_smtp_enabled', '0');
        $from_n  = get_option('bacera_email_from_name', get_bloginfo('name'));
        $from_e  = get_option('bacera_email_from_address', get_option('admin_email'));
        $host    = get_option('bacera_smtp_host', '');
        $port    = get_option('bacera_smtp_port', '587');
        $user    = get_option('bacera_smtp_user', '');
        $pass    = get_option('bacera_smtp_pass', '');
        $secure  = get_option('bacera_smtp_secure', 'tls');
        $is_on   = $enabled && $host;
        ?>
        <div class="bcfg-ch">
            <h1>✉️ Mail & SMTP</h1>
            <?php if ($is_on): ?>
            <span class="bcfg-badge-on">● Đang hoạt động</span>
            <?php else: ?>
            <span class="bcfg-badge-off">○ Chưa cấu hình</span>
            <?php endif; ?>
        </div>

        <div class="bcfg-card">
            <div class="bcfg-card-header">
                <span style="font-size:17px">✉️</span>
                <h2>Cấu hình Email gửi đi</h2>
            </div>
            <div class="bcfg-card-body">
                <div class="bcfg-warn">
                    💡 <strong>Gmail:</strong> host <code>smtp.gmail.com</code> · port <code>587</code> · TLS · dùng
                    <a href="https://myaccount.google.com/apppasswords" target="_blank">App Password</a>
                    (không phải mật khẩu thường).
                </div>

                <!-- Toggle -->
                <div class="bcfg-field" style="margin-bottom:20px">
                    <label>Trạng thái SMTP</label>
                    <div class="bcfg-toggle-wrap">
                        <input type="checkbox" name="bacera_smtp_enabled" value="1" <?php checked($enabled, '1'); ?>>
                        <span>Bật SMTP — sử dụng cấu hình bên dưới thay cho WP mặc định</span>
                    </div>
                </div>

                <hr class="bcfg-divider">

                <div class="bcfg-grid">
                    <div class="bcfg-field">
                        <label>Tên người gửi</label>
                        <input type="text" name="bacera_email_from_name" value="<?php echo esc_attr($from_n); ?>" placeholder="Bacera Workshop">
                    </div>
                    <div class="bcfg-field">
                        <label>Email người gửi</label>
                        <input type="email" name="bacera_email_from_address" value="<?php echo esc_attr($from_e); ?>" placeholder="no-reply@example.com">
                    </div>
                    <div class="bcfg-field">
                        <label>SMTP Host</label>
                        <input type="text" name="bacera_smtp_host" value="<?php echo esc_attr($host); ?>" placeholder="smtp.gmail.com">
                    </div>
                    <div class="bcfg-field">
                        <label>SMTP Port</label>
                        <input type="number" name="bacera_smtp_port" value="<?php echo esc_attr($port); ?>" placeholder="587">
                    </div>
                    <div class="bcfg-field">
                        <label>Mã hóa (Encryption)</label>
                        <select name="bacera_smtp_secure">
                            <option value="tls"  <?php selected($secure,'tls'); ?>>TLS — port 587 (khuyến nghị)</option>
                            <option value="ssl"  <?php selected($secure,'ssl'); ?>>SSL — port 465</option>
                            <option value="none" <?php selected($secure,'none'); ?>>Không mã hóa</option>
                        </select>
                    </div>
                    <div class="bcfg-field">
                        <label>SMTP Username</label>
                        <input type="text" name="bacera_smtp_user" value="<?php echo esc_attr($user); ?>" placeholder="your@gmail.com">
                    </div>
                    <div class="bcfg-field">
                        <label>SMTP Password / App Password</label>
                        <input type="password" name="bacera_smtp_pass" value="<?php echo esc_attr($pass); ?>" placeholder="••••••••••••••••">
                        <span class="bcfg-hint">Gmail: tạo <a href="https://myaccount.google.com/apppasswords" target="_blank">App Password</a> tại Google Account → Security.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bcfg-footer">
            <button type="submit" class="bcfg-btn-primary">💾 Lưu cài đặt</button>
            <button type="button" id="bcfg-test-email" class="bcfg-btn-secondary">📧 Gửi email test</button>
            <span id="bcfg-test-result"></span>
        </div>
        <?php
    }

    /* ══════════════════════════════════════════════════════════════
       TAB: GOOGLE LOGIN
    ══════════════════════════════════════════════════════════════ */
    private function tab_google() {
        $enabled   = get_option('bacera_google_enabled', '0');
        $client_id = get_option('bacera_google_client_id', '');
        $secret    = get_option('bacera_google_client_secret', '');
        $callback  = home_url('/bacera-auth/google/callback');
        $is_on     = $enabled && $client_id;
        ?>
        <div class="bcfg-ch">
            <h1>
                <svg width="22" height="22" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                Google Login
            </h1>
            <?php echo $is_on ? '<span class="bcfg-badge-on">● Đang hoạt động</span>' : '<span class="bcfg-badge-off">○ Chưa bật</span>'; ?>
        </div>

        <div class="bcfg-card">
            <div class="bcfg-card-header">
                <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                <h2>Google OAuth 2.0</h2>
            </div>
            <div class="bcfg-card-body">
                <div class="bcfg-info">
                    <strong>Hướng dẫn lấy Google Client ID:</strong>
                    <ol>
                        <li>Vào <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console → APIs & Services → Credentials</a></li>
                        <li>Nhấn <strong>+ Create Credentials → OAuth 2.0 Client ID</strong></li>
                        <li>Chọn Application type: <strong>Web application</strong></li>
                        <li>Thêm Authorized redirect URI (copy bên dưới)</li>
                        <li>Copy <strong>Client ID</strong> và <strong>Client Secret</strong> vào các ô bên dưới</li>
                    </ol>
                </div>

                <div class="bcfg-field" style="margin-bottom:20px">
                    <label>Trạng thái</label>
                    <div class="bcfg-toggle-wrap">
                        <input type="checkbox" name="bacera_google_enabled" value="1" <?php checked($enabled, '1'); ?>>
                        <span>Hiện nút "Đăng nhập với Google" trên trang xác thực</span>
                    </div>
                </div>

                <hr class="bcfg-divider">

                <div class="bcfg-grid">
                    <div class="bcfg-field">
                        <label>Client ID</label>
                        <input type="text" name="bacera_google_client_id" value="<?php echo esc_attr($client_id); ?>" placeholder="xxxx.apps.googleusercontent.com">
                    </div>
                    <div class="bcfg-field">
                        <label>Client Secret</label>
                        <input type="password" name="bacera_google_client_secret" value="<?php echo esc_attr($secret); ?>" placeholder="GOCSPX-…">
                    </div>
                    <div class="bcfg-field" style="grid-column:1/-1">
                        <label>Authorized Redirect URI <small style="font-weight:400;text-transform:none;letter-spacing:0;color:#9ca3af">(thêm vào Google Cloud Console)</small></label>
                        <div class="bcfg-pill">
                            <span><?php echo esc_html($callback); ?></span>
                            <button type="button" class="bcfg-copy-btn" data-copy="<?php echo esc_attr($callback); ?>">Copy</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bcfg-footer">
            <button type="submit" class="bcfg-btn-primary">💾 Lưu cài đặt</button>
        </div>
        <?php
    }

    /* ══════════════════════════════════════════════════════════════
       TAB: FACEBOOK LOGIN
    ══════════════════════════════════════════════════════════════ */
    private function tab_facebook() {
        $enabled  = get_option('bacera_facebook_enabled', '0');
        $app_id   = get_option('bacera_facebook_app_id', '');
        $secret   = get_option('bacera_facebook_app_secret', '');
        $callback = home_url('/bacera-auth/facebook/callback');
        $is_on    = $enabled && $app_id;
        ?>
        <div class="bcfg-ch">
            <h1>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                Facebook Login
            </h1>
            <?php echo $is_on ? '<span class="bcfg-badge-on">● Đang hoạt động</span>' : '<span class="bcfg-badge-off">○ Chưa bật</span>'; ?>
        </div>

        <div class="bcfg-card">
            <div class="bcfg-card-header">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                <h2>Facebook Login App</h2>
            </div>
            <div class="bcfg-card-body">
                <div class="bcfg-info">
                    <strong>Hướng dẫn tạo Facebook App:</strong>
                    <ol>
                        <li>Vào <a href="https://developers.facebook.com/apps" target="_blank">Facebook for Developers → My Apps → Create App</a></li>
                        <li>Chọn loại app: <strong>Consumer</strong></li>
                        <li>Trong app vừa tạo, thêm product: <strong>Facebook Login → Settings</strong></li>
                        <li>Thêm Valid OAuth Redirect URI (copy bên dưới)</li>
                        <li>Vào <strong>Settings → Basic</strong>, copy <strong>App ID</strong> và <strong>App Secret</strong></li>
                    </ol>
                </div>

                <div class="bcfg-field" style="margin-bottom:20px">
                    <label>Trạng thái</label>
                    <div class="bcfg-toggle-wrap">
                        <input type="checkbox" name="bacera_facebook_enabled" value="1" <?php checked($enabled, '1'); ?>>
                        <span>Hiện nút "Đăng nhập với Facebook" trên trang xác thực</span>
                    </div>
                </div>

                <hr class="bcfg-divider">

                <div class="bcfg-grid">
                    <div class="bcfg-field">
                        <label>App ID</label>
                        <input type="text" name="bacera_facebook_app_id" value="<?php echo esc_attr($app_id); ?>" placeholder="1234567890123456">
                    </div>
                    <div class="bcfg-field">
                        <label>App Secret</label>
                        <input type="password" name="bacera_facebook_app_secret" value="<?php echo esc_attr($secret); ?>" placeholder="abc1234567890…">
                    </div>
                    <div class="bcfg-field" style="grid-column:1/-1">
                        <label>Valid OAuth Redirect URI <small style="font-weight:400;text-transform:none;letter-spacing:0;color:#9ca3af">(thêm vào Facebook Developer)</small></label>
                        <div class="bcfg-pill">
                            <span><?php echo esc_html($callback); ?></span>
                            <button type="button" class="bcfg-copy-btn" data-copy="<?php echo esc_attr($callback); ?>">Copy</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bcfg-footer">
            <button type="submit" class="bcfg-btn-primary">💾 Lưu cài đặt</button>
        </div>
        <?php
    }

    /* ══════════════════════════════════════════════════════════════
       TAB: CAPTCHA (Cloudflare Turnstile)
    ══════════════════════════════════════════════════════════════ */
    private function tab_captcha() {
        $site_key   = get_option('bacera_turnstile_site_key', '');
        $secret_key = get_option('bacera_turnstile_secret_key', '');
        $is_on      = (bool) $site_key;
        ?>
        <div class="bcfg-ch">
            <h1>🛡️ CAPTCHA</h1>
            <?php echo $is_on ? '<span class="bcfg-badge-on">● Đang hoạt động</span>' : '<span class="bcfg-badge-off">○ Chưa cấu hình</span>'; ?>
        </div>

        <div class="bcfg-card">
            <div class="bcfg-card-header">
                <span style="font-size:17px">🛡️</span>
                <h2>Cloudflare Turnstile</h2>
            </div>
            <div class="bcfg-card-body">
                <div class="bcfg-info">
                    <strong>Cloudflare Turnstile</strong> — miễn phí, không cần checkbox "Tôi không phải robot".
                    <ol>
                        <li>Vào <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank">dash.cloudflare.com → Turnstile</a></li>
                        <li>Nhấn <strong>Add site</strong>, nhập tên và domain</li>
                        <li>Chọn Widget type: <strong>Managed</strong></li>
                        <li>Copy <strong>Site Key</strong> và <strong>Secret Key</strong> vào các ô bên dưới</li>
                    </ol>
                </div>

                <div class="bcfg-grid">
                    <div class="bcfg-field">
                        <label>Site Key <span style="color:#ef4444">*</span></label>
                        <input type="text" id="ts-site-key-input" name="bacera_turnstile_site_key" value="<?php echo esc_attr($site_key); ?>" placeholder="0x4AAAAAAA…">
                        <span class="bcfg-hint">Hiển thị trên widget phía trình duyệt.</span>
                    </div>
                    <div class="bcfg-field">
                        <label>Secret Key <span style="color:#ef4444">*</span></label>
                        <input type="password" name="bacera_turnstile_secret_key" value="<?php echo esc_attr($secret_key); ?>" placeholder="0x4AAAAAAA…">
                        <span class="bcfg-hint">Chỉ dùng phía server — không lộ ra ngoài.</span>
                    </div>
                </div>

                <?php if ($is_on): ?>
                <hr class="bcfg-divider">
                <div>
                    <p style="font-size:13px;font-weight:700;color:#1c1917;margin:0 0 12px;">Xem thử widget:</p>
                    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                    <div id="ts-widget-preview">
                        <div class="cf-turnstile" data-sitekey="<?php echo esc_attr($site_key); ?>" data-theme="light"></div>
                    </div>
                </div>
                <?php else: ?>
                <div id="ts-widget-preview" style="margin-top:16px"></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="bcfg-footer">
            <button type="submit" class="bcfg-btn-primary">💾 Lưu cài đặt</button>
        </div>
        <?php
    }
}
