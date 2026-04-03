<?php
/**
 * Template Name: Auth Page
 */

get_header();

$is_logged_in = isset($_COOKIE['bacera_customer_auth']);
$initial_state = $is_logged_in ? 'success' : 'login';
?>
<style>
[x-cloak] { display: none !important; }

.auth-wrap {
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 76px);
    background: #fff;
    font-family: 'Inter', sans-serif;
}
@media (min-width: 1024px) {
    .auth-wrap { flex-direction: row-reverse; }
}

.auth-image {
    display: none;
    position: relative;
    background: #d6d3d1;
}
@media (min-width: 1024px) {
    .auth-image { display: block; width: 45%; flex-shrink: 0; position: sticky; top: 76px; height: calc(100vh - 76px); }
}
@media (min-width: 1280px) {
    .auth-image { width: 50%; }
}
.auth-image img {
    position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;
}
.auth-image::after {
    content: ''; position: absolute; inset: 0; background: rgba(0,0,0,0.08);
}

.auth-form-panel {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 1.5rem;
}
@media (min-width: 640px)  { .auth-form-panel { padding: 3rem; } }
@media (min-width: 1024px) { .auth-form-panel { padding: 4rem; } }

.auth-box { width: 100%; max-width: 452px; }

.auth-breadcrumb {
    display: flex; align-items: center; flex-wrap: wrap;
    gap: 4px; margin-bottom: 10px;
    font-size: 14px; color: #78716c; list-style: none; padding: 0; margin-top: 0;
}
.auth-breadcrumb a { color: #78716c; text-decoration: none; }
.auth-breadcrumb a:hover { color: #1c1917; }
.auth-breadcrumb .sep, .auth-breadcrumb .current { color: #a8a29e; }

.auth-title {
    font-family: 'Gowun Batang', serif;
    font-size: 2.25rem; font-weight: 400;
    color: #1c1917; margin: 0 0 2rem 0; line-height: 1.2;
}

.auth-section-label {
    font-size: 15px; font-weight: 600; color: #1c1917; margin: 0 0 0.75rem 0;
}

.auth-input {
    width: 100%; height: 56px; padding: 0 16px;
    border: 1px solid #d6d3d1; border-radius: 8px;
    background: #fff; color: #1c1917;
    font-size: 15px; font-family: inherit;
    outline: none; box-sizing: border-box;
    transition: border-color 0.15s, box-shadow 0.15s; display: block;
}
.auth-input::placeholder { color: #a8a29e; }
.auth-input:focus { border-color: #1c1917; box-shadow: 0 0 0 3px rgba(28,25,23,0.08); }

.auth-btn-primary {
    width: 100%; height: 56px;
    background: #E15D43; color: #fff;
    border: none; border-radius: 8px;
    font-size: 15px; font-weight: 500; font-family: inherit;
    cursor: pointer; transition: background 0.15s;
    display: flex; align-items: center; justify-content: center;
}
.auth-btn-primary:hover { background: #c0533e; }
.auth-btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.auth-btn-social {
    width: 100%; height: 56px;
    background: #fff; border: 1px solid #d6d3d1; border-radius: 8px;
    font-size: 15px; font-weight: 500; font-family: inherit;
    color: #1c1917; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 10px;
    transition: background 0.15s;
}
.auth-btn-social:hover { background: #fafaf9; }

.auth-divider {
    display: flex; align-items: center; gap: 12px;
}
.auth-divider hr { flex: 1; border: none; border-top: 1px solid #e7e5e4; margin: 0; }
.auth-divider span { color: #78716c; font-size: 13px; white-space: nowrap; }

.auth-error {
    background: #fef2f2; border: 1px solid #fecaca;
    color: #dc2626; border-radius: 8px;
    padding: 12px 16px; font-size: 14px; margin-bottom: 1.25rem;
}

.otp-input {
    flex: 1; height: 64px; text-align: center;
    font-size: 1.75rem; font-weight: 600;
    border: 1.5px solid #d6d3d1; border-radius: 10px;
    background: #fff; color: #1c1917; outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
    min-width: 0;
}
.otp-input:focus { border-color: #1c1917; box-shadow: 0 0 0 3px rgba(28,25,23,0.08); }

.auth-link-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 20px; height: 56px;
    border: 1px solid #e7e5e4; border-radius: 8px;
    background: #fafaf9; color: #44403c;
    text-decoration: none; font-weight: 500; font-size: 15px;
    transition: background 0.15s;
}
.auth-link-row:hover { background: #f5f5f4; }
.auth-link-row svg { flex-shrink: 0; color: #a8a29e; transition: transform 0.15s, color 0.15s; }
.auth-link-row:hover svg { color: #E15D43; transform: translateX(4px); }

.auth-form-group { display: flex; flex-direction: column; gap: 32px; }
.auth-field-group { display: flex; flex-direction: column; gap: 12px; }
.auth-actions { display: flex; flex-direction: column; gap: 20px; }
.auth-socials { display: flex; flex-direction: column; gap: 10px; }

.auth-link-text { text-align: center; font-size: 15px; color: #57534e; }
.auth-link-text button {
    color: #E15D43; font-weight: 500; background: none; border: none;
    cursor: pointer; font-size: 15px; font-family: inherit; padding: 0;
}
.auth-link-text button:hover { text-decoration: underline; }

.auth-back-btn {
    display: flex; align-items: center; gap: 8px;
    color: #78716c; background: none; border: none;
    cursor: pointer; font-size: 14px; font-family: inherit;
    padding: 0; margin-bottom: 20px;
    transition: color 0.15s;
}
.auth-back-btn:hover { color: #1c1917; }

.pw-wrap {
    position: relative;
}
.pw-wrap .auth-input {
    padding-right: 48px;
}
.pw-toggle {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    color: #a8a29e;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.15s;
}
.pw-toggle:hover { color: #1c1917; }
</style>

<?php
$auth_config = [
    'ajaxUrl'         => admin_url('admin-ajax.php'),
    'homeUrl'         => home_url('/'),
    'initialState'    => $initial_state,
    'turnstileSiteKey'=> get_option('bacera_turnstile_site_key', ''),
];
?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<script>
var BaceraAuth = <?php echo wp_json_encode($auth_config); ?>;
</script>

<div class="auth-wrap" x-data="authApp">

    <div class="auth-image">
        <img src="<?php echo esc_url(home_url('/wp-content/uploads/2026/04/92240808_3497092953639852_3244063483855110144_o.png')); ?>" alt="Bacera Workshop">
    </div>

    <div class="auth-form-panel">
        <div class="auth-box">

            <div x-show="state === 'login'">
                <nav class="auth-breadcrumb">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Homepage</a>
                    <span class="sep">/</span>
                    <span class="current">Đăng nhập</span>
                </nav>
                <h1 class="auth-title">Đăng nhập</h1>

                <div x-show="error" x-text="error" x-cloak class="auth-error"></div>

                <div class="auth-form-group">
                    <div class="auth-field-group">
                        <p class="auth-section-label">Thông tin đăng nhập</p>
                        <div>
                            <label style="display:block;font-size:14px;font-weight:500;color:#44403c;margin-bottom:6px;">Số điện thoại hoặc Email</label>
                            <input x-model="identifier" type="text" placeholder="Nhập email hoặc SĐT" class="auth-input" @keydown.enter="doLogin()">
                        </div>
                        <div>
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                                <label style="font-size:14px;font-weight:500;color:#44403c;">Mật khẩu</label>
                                <a href="#" style="font-size:13px;color:#E15D43;text-decoration:none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Quên mật khẩu?</a>
                            </div>
                            <div class="pw-wrap" x-data="{show:false}">
                                <input x-model="password" :type="show ? 'text' : 'password'" placeholder="Nhập mật khẩu" class="auth-input" @keydown.enter="doLogin()">
                                <button type="button" class="pw-toggle" @click="show=!show" :aria-label="show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'">
                                    <svg x-show="!show" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="show" x-cloak width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="auth-actions">
                        <div x-show="turnstileSiteKey" x-cloak style="margin-bottom:4px;">
                            <div class="cf-turnstile" data-sitekey data-callback="onCaptchaSuccess" data-theme="light" id="turnstile-login"></div>
                        </div>
                        <button class="auth-btn-primary" :disabled="loading || (turnstileSiteKey && !captchaToken)" @click="doLogin()" x-text="loading ? 'Đang xử lý...' : 'Đăng nhập'"></button>

                        <div class="auth-divider">
                            <hr><span>Hoặc đăng nhập với</span><hr>
                        </div>

                        <div class="auth-socials">
                            <button type="button" class="auth-btn-social">
                                <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                                Đăng nhập với Google
                            </button>
                            <button type="button" class="auth-btn-social">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="#1877F2" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                Đăng nhập với Facebook
                            </button>
                        </div>

                        <p class="auth-link-text">
                            Chưa có tài khoản?
                            <button type="button" @click="switchState('register')">Đăng ký</button>
                        </p>
                    </div>
                </div>
            </div>

            <div x-show="state === 'register'" x-cloak>
                <nav class="auth-breadcrumb">
                    <a href="<?php echo esc_url(home_url('/')); ?>">Homepage</a>
                    <span class="sep">/</span>
                    <a href="#" @click.prevent="switchState('login')">Đăng nhập</a>
                    <span class="sep">/</span>
                    <span class="current">Đăng ký</span>
                </nav>
                <h1 class="auth-title">Đăng ký</h1>

                <div x-show="error" x-text="error" x-cloak class="auth-error"></div>

                <div class="auth-form-group">
                    <div class="auth-field-group">
                        <p class="auth-section-label">Thông tin tài khoản</p>
                        <input x-model="phone" type="text" placeholder="Số điện thoại" class="auth-input">
                        <input x-model="email" type="email" placeholder="Email" class="auth-input">
                    </div>

                    <div class="auth-field-group">
                        <p class="auth-section-label">Mật khẩu</p>
                        <div class="pw-wrap" x-data="{show:false}">
                            <input x-model="password" :type="show ? 'text' : 'password'" placeholder="Nhập mật khẩu của bạn" class="auth-input">
                            <button type="button" class="pw-toggle" @click="show=!show" :aria-label="show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'">
                                <svg x-show="!show" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="show" x-cloak width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                        <div class="pw-wrap" x-data="{show:false}">
                            <input x-model="password_confirm" :type="show ? 'text' : 'password'" placeholder="Nhập lại mật khẩu" class="auth-input">
                            <button type="button" class="pw-toggle" @click="show=!show" :aria-label="show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'">
                                <svg x-show="!show" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="show" x-cloak width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="auth-actions">
                        <div x-show="turnstileSiteKey" x-cloak style="margin-bottom:4px;">
                            <div class="cf-turnstile" data-sitekey data-callback="onCaptchaSuccess" data-theme="light" id="turnstile-register"></div>
                        </div>
                        <button class="auth-btn-primary" :disabled="loading || (turnstileSiteKey && !captchaToken)" @click="doRegister()" x-text="loading ? 'Đang xử lý...' : 'Đăng ký'"></button>

                        <div class="auth-divider">
                            <hr><span>Hoặc đăng nhập với</span><hr>
                        </div>

                        <div class="auth-socials">
                            <button type="button" class="auth-btn-social">
                                <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                                Đăng nhập với Google
                            </button>
                            <button type="button" class="auth-btn-social">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="#1877F2" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                Đăng nhập với Facebook
                            </button>
                        </div>

                        <p class="auth-link-text">
                            Đã có tài khoản?
                            <button type="button" @click="switchState('login')">Đăng nhập</button>
                        </p>
                    </div>
                </div>
            </div>

            <div x-show="state === 'otp'" x-cloak>
                <button type="button" @click="switchState(prev_state)" class="auth-back-btn">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Quay lại
                </button>
                <h1 class="auth-title">Xác thực OTP</h1>
                <p style="color:#78716c;font-size:14px;margin:0 0 6px;">
                    Nhập mã 4 chữ số được gửi tới<br>
                    <strong x-text="identifier" style="color:#1c1917;font-size:15px;"></strong>
                </p>
                <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;font-size:13px;color:#92400e;margin-bottom:24px;margin-top:12px;">
                    &#x1F511; Mã OTP thử nghiệm: <strong>1234</strong>
                </div>

                <div x-show="error" x-text="error" x-cloak class="auth-error"></div>

                <div style="display:flex;gap:12px;margin-bottom:28px;" id="otp-boxes">
                    <input type="text" inputmode="numeric" maxlength="1"
                        x-model="otp[0]"
                        @input="handleOtpInput($event, 0)"
                        @keydown.backspace="handleOtpBackspace($event, 0)"
                        @paste.prevent="handleOtpPaste($event)"
                        class="otp-input" autocomplete="one-time-code">
                    <input type="text" inputmode="numeric" maxlength="1"
                        x-model="otp[1]"
                        @input="handleOtpInput($event, 1)"
                        @keydown.backspace="handleOtpBackspace($event, 1)"
                        class="otp-input">
                    <input type="text" inputmode="numeric" maxlength="1"
                        x-model="otp[2]"
                        @input="handleOtpInput($event, 2)"
                        @keydown.backspace="handleOtpBackspace($event, 2)"
                        class="otp-input">
                    <input type="text" inputmode="numeric" maxlength="1"
                        x-model="otp[3]"
                        @input="handleOtpInput($event, 3)"
                        @keydown.backspace="handleOtpBackspace($event, 3)"
                        class="otp-input">
                </div>

                <div style="display:flex;flex-direction:column;gap:12px;">
                    <button class="auth-btn-primary" :disabled="loading" @click="doOtp()" x-text="loading ? 'Đang xác thực...' : 'Xác nhận'"></button>
                    <p style="text-align:center;font-size:14px;color:#78716c;margin:0;">
                        Chưa nhận được mã?
                        <button type="button" style="color:#E15D43;font-weight:500;background:none;border:none;cursor:pointer;font-family:inherit;font-size:14px;padding:0;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Gửi lại</button>
                    </p>
                </div>
            </div>

            <div x-show="state === 'success'" x-cloak>
                <div style="width:64px;height:64px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
                    <svg width="32" height="32" fill="none" stroke="#16a34a" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h1 class="auth-title">Thành công!</h1>
                <p style="color:#78716c;font-size:15px;margin:0 0 28px;">Bạn đã đăng nhập thành công. Tiếp tục khám phá Bacera.</p>

                <div style="display:flex;flex-direction:column;gap:10px;">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="auth-link-row">
                        <span>Trang chủ</span>
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a href="#" class="auth-link-row">
                        <span>Tài khoản của tôi</span>
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a href="#" class="auth-link-row">
                        <span>Cửa hàng (Shop)</span>
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a href="#" class="auth-link-row">
                        <span>Đăng ký Workshop</span>
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a href="<?php echo esc_url( add_query_arg( 'bacera_logout', '1', home_url( '/' ) ) ); ?>"
                       style="margin-top:12px;color:#a8a29e;background:none;border:none;cursor:pointer;font-size:14px;font-family:inherit;display:block;width:100%;text-align:center;text-decoration:none;"
                       onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#a8a29e'">
                        Đăng xuất
                    </a>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
function onCaptchaSuccess(token) {
    const el = document.querySelector('[x-data="authApp"]');
    if (el && el._x_dataStack) {
        const component = el._x_dataStack[0];
        if (component) component.captchaToken = token;
    }
}

/**
 * Update header account area immediately after login (no page reload).
 * Replaces the "Đăng nhập" anchor with the avatar + dropdown markup.
 */
function updateHeaderLoggedIn(customer) {
    const acctArea = document.getElementById('bacera-login-btn');
    if (!acctArea) return;

    const name       = customer.name || '';
    const avatarUrl  = customer.avatar_url || '';
    const authPage   = customer.auth_page || '#';
    const sub        = customer.email || customer.phone || '';
    const nameEnc    = encodeURIComponent(name || 'K');

    const html = `
    <div class="relative" id="acct-trigger">
        <button id="acct-btn" class="flex items-center gap-2 hover:opacity-80 transition-opacity focus:outline-none">
            <div class="hdr-avatar w-8 h-8 rounded-full overflow-hidden">
                <img src="${avatarUrl}" class="w-full h-full object-cover" alt="${name}">
            </div>
            <span class="hidden xl:block hdr-icon text-[13px] font-medium font-sans leading-none truncate max-w-[120px]">${name}</span>
            <svg class="hdr-chevron hdr-icon w-3 h-3 hidden xl:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div class="acct-panel" id="acct-panel" style="position:absolute;top:calc(100% + 10px);right:0;width:15rem;background:#fff;border:1px solid #e7e5e4;border-radius:1rem;box-shadow:0 8px 32px rgba(0,0,0,.1);opacity:0;pointer-events:none;transform:translateY(-4px);transition:opacity .18s ease,transform .18s ease;z-index:80;">
            <div class="px-4 py-3 border-b border-stone-100">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full overflow-hidden shrink-0">
                        <img src="${avatarUrl}" class="w-full h-full" alt="">
                    </div>
                    <div class="min-w-0">
                        <p class="text-stone-800 text-[14px] font-semibold truncate">${name}</p>
                        ${sub ? `<p class="text-stone-400 text-[12px] truncate">${sub}</p>` : ''}
                    </div>
                </div>
            </div>
            <a href="${authPage}" class="flex items-center gap-3 px-4 py-2.5 text-stone-600 text-[14px] font-medium hover:bg-stone-50 hover:text-[#d95f47] transition-colors">
                <div class="w-7 h-7 rounded-lg bg-stone-100 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                Tài khoản của tôi
            </a>
            <div class="h-px bg-stone-100 mx-4 my-1.5"></div>
            <a href="<?php echo esc_js( add_query_arg( 'bacera_logout', '1', home_url( '/' ) ) ); ?>" class="w-full flex items-center gap-3 px-4 py-2.5 text-stone-500 text-[14px] font-medium hover:bg-red-50 hover:text-red-500 transition-colors mb-1">
                <div class="w-7 h-7 rounded-lg bg-stone-100 flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </div>
                Đăng xuất
            </a>
        </div>
    </div>`;

    const wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();
    const newEl = wrapper.firstChild;
    acctArea.replaceWith(newEl);

    // Re-apply hover behaviour for the new panel
    newEl.addEventListener('mouseenter', () => {
        const p = newEl.querySelector('#acct-panel');
        if (p) { p.style.opacity = '1'; p.style.pointerEvents = 'auto'; p.style.transform = 'translateY(0)'; }
    });
    newEl.addEventListener('mouseleave', () => {
        const p = newEl.querySelector('#acct-panel');
        if (p) { p.style.opacity = '0'; p.style.pointerEvents = 'none'; p.style.transform = 'translateY(-4px)'; }
    });
}

/**
 * Bacera Auth - Alpine.js Component
 * Logic is separated from HTML via Alpine.data() pattern.
 * All server calls go through WordPress AJAX — no credentials stored here.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('authApp', () => ({
        state:            BaceraAuth.initialState,
        prev_state:       'login',
        identifier:       '',
        phone:            '',
        email:            '',
        password:         '',
        password_confirm: '',
        otp:              ['', '', '', ''],
        loading:          false,
        error:            '',
        captchaToken:     '',
        turnstileSiteKey: BaceraAuth.turnstileSiteKey || '',

        init() {
            if (this.turnstileSiteKey) {
                document.querySelectorAll('.cf-turnstile').forEach(el => {
                    el.setAttribute('data-sitekey', this.turnstileSiteKey);
                });
            }
        },

        switchState(s) {
            if (this.state === 'login' || this.state === 'register') {
                this.prev_state = this.state;
            }
            this.state = s;
            this.error = '';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        handleOtpInput(event, index) {
            const val = event.target.value.replace(/[^0-9]/g, '');
            event.target.value = val;
            this.otp[index] = val;
            if (val && index < 3) {
                const boxes = document.querySelectorAll('#otp-boxes .otp-input');
                if (boxes[index + 1]) boxes[index + 1].focus();
            }
        },

        handleOtpBackspace(event, index) {
            if (!this.otp[index] && index > 0) {
                this.otp[index] = '';
                const boxes = document.querySelectorAll('#otp-boxes .otp-input');
                if (boxes[index - 1]) boxes[index - 1].focus();
            }
        },

        handleOtpPaste(event) {
            const text = (event.clipboardData || window.clipboardData).getData('text');
            const digits = text.replace(/[^0-9]/g, '').slice(0, 4).split('');
            digits.forEach((d, i) => { this.otp[i] = d; });
            const boxes = document.querySelectorAll('#otp-boxes .otp-input');
            if (boxes[digits.length - 1]) boxes[digits.length - 1].focus();
        },

        async _post(action, body) {
            this.loading = true;
            this.error   = '';
            try {
                const fd = new FormData();
                fd.append('action', action);
                if (this.turnstileSiteKey) {
                    fd.append('cf_turnstile_response', this.captchaToken);
                }
                Object.entries(body).forEach(([k, v]) => fd.append(k, v));
                const res = await fetch(BaceraAuth.ajaxUrl, { method: 'POST', body: fd });
                return await res.json();
            } catch {
                this.error = 'Lỗi kết nối. Vui lòng thử lại.';
                return null;
            } finally {
                this.loading = false;
                this.captchaToken = '';
                if (window.turnstile && this.turnstileSiteKey) window.turnstile.reset();
            }
        },

        async doLogin() {
            if (!this.identifier || !this.password) {
                this.error = 'Vui lòng điền đầy đủ thông tin.';
                return;
            }
            const res = await this._post('bacera_auth_submit', {
                auth_state: 'login',
                identifier: this.identifier,
                password:   this.password,
            });
            if (!res) return;
            if (!res.success) {
                this.error = res.data.message;
                if (res.data.action_needed) this.switchState(res.data.action_needed);
            } else {
                this.switchState('otp');
            }
        },

        async doRegister() {
            if (!this.phone && !this.email) {
                this.error = 'Vui lòng nhập Số điện thoại hoặc Email.';
                return;
            }
            if (!this.password) {
                this.error = 'Vui lòng nhập mật khẩu.';
                return;
            }
            if (this.password !== this.password_confirm) {
                this.error = 'Mật khẩu nhập lại không khớp.';
                return;
            }
            this.identifier = this.email || this.phone;
            const res = await this._post('bacera_auth_submit', {
                auth_state: 'register',
                identifier: this.identifier,
                password:   this.password,
                name:       'Khách hàng',
            });
            if (!res) return;
            if (!res.success) {
                this.error = res.data.message;
                if (res.data.action_needed) this.switchState(res.data.action_needed);
            } else {
                this.switchState('otp');
            }
        },

        async doOtp() {
            const code = this.otp.join('');
            if (code.length !== 4) {
                this.error = 'Vui lòng nhập đủ 4 số.';
                return;
            }
            const res = await this._post('bacera_verify_otp', {
                identifier: this.identifier,
                otp:        code,
            });
            if (!res) return;
            if (!res.success) {
                this.error = res.data.message;
            } else {
                this.switchState('success');
                // Update header instantly without page reload
                if (res.data && res.data.customer) {
                    updateHeaderLoggedIn(res.data.customer);
                }
            }
        },

        logout() {
            document.cookie = 'bacera_customer_auth=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
            window.location.reload();
        },
    }));
});
</script>

<?php get_footer(); ?>
