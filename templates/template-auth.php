<?php
/**
 * Template Name: Auth Page
 */

get_header();

$is_logged_in  = isset($_COOKIE['bacera_customer_auth']);
$initial_state = $is_logged_in ? 'success' : 'login';

$ajax_url        = admin_url('admin-ajax.php');
$home_url        = home_url('/');
$logout_url      = add_query_arg('bacera_logout', '1', home_url('/'));
$social_error    = sanitize_text_field($_GET['social_error'] ?? '');

// Cloudflare Turnstile
$turnstile_site_key = get_option('bacera_turnstile_site_key', '');
$turnstile_enabled  = !empty($turnstile_site_key);

$auth_config = [
    'ajaxUrl'            => $ajax_url,
    'homeUrl'            => $home_url,
    'logoutUrl'          => $logout_url,
    'initialState'       => $initial_state,
    'socialError'        => $social_error,
    'googleEnabled'      => (bool) get_option('bacera_google_enabled', '0'),
    'googleAuthUrl'      => \Bacera\Controllers\SocialLoginController::google_auth_url(),
    'facebookEnabled'    => (bool) get_option('bacera_facebook_enabled', '0'),
    'facebookAuthUrl'    => \Bacera\Controllers\SocialLoginController::facebook_auth_url(),
    'turnstileEnabled'   => $turnstile_enabled,
    'turnstileSiteKey'   => $turnstile_site_key,
];

?>
<style>
.auth-wrap {
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 76px);
    background: #fff;
    font-family: 'Inter', sans-serif;
}
@media (min-width: 1024px) { .auth-wrap { flex-direction: row-reverse; } }

.auth-image {
    display: none;
    position: relative;
    background: #d6d3d1;
}
@media (min-width: 1024px) {
    .auth-image { display: block; width: 45%; flex-shrink: 0; position: sticky; top: 76px; height: calc(100vh - 76px); }
}
@media (min-width: 1280px) { .auth-image { width: 50%; } }
.auth-image img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.auth-image::after { content: ''; position: absolute; inset: 0; background: rgba(0,0,0,0.08); }

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

.auth-screen { display: none; }
.auth-screen.active { display: block; }

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
    gap: 8px;
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

.auth-divider { display: flex; align-items: center; gap: 12px; }
.auth-divider hr { flex: 1; border: none; border-top: 1px solid #e7e5e4; margin: 0; }
.auth-divider span { color: #78716c; font-size: 13px; white-space: nowrap; }

.auth-error {
    background: #fef2f2; border: 1px solid #fecaca;
    color: #dc2626; border-radius: 8px;
    padding: 12px 16px; font-size: 14px; margin-bottom: 1.25rem;
    display: none;
}
.auth-error.visible { display: block; }

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
    display: inline-flex; align-items: center; gap: 8px;
    color: #78716c; background: none; border: none;
    cursor: pointer; font-size: 14px; font-family: inherit;
    padding: 0; margin-bottom: 20px;
    transition: color 0.15s;
}
.auth-back-btn:hover { color: #1c1917; }

.pw-wrap { position: relative; }
.pw-wrap .auth-input { padding-right: 48px; }
.pw-toggle {
    position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer; padding: 4px;
    color: #a8a29e; display: flex; align-items: center; justify-content: center;
    transition: color 0.15s;
}
.pw-toggle:hover { color: #1c1917; }

.auth-spinner {
    width: 18px; height: 18px;
    border: 2px solid rgba(255,255,255,0.4);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.cf-turnstile-wrap { margin-top: 4px; }
</style>

<?php if ($turnstile_enabled): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>

<div class="auth-wrap">

    <div class="auth-image">
        <img src="<?php echo esc_url(home_url('/wp-content/uploads/2026/04/92240808_3497092953639852_3244063483855110144_o.png')); ?>" alt="Bacera Workshop">
    </div>

    <div class="auth-form-panel">
        <div class="auth-box">

            <?php if ($social_error): ?>
            <div class="auth-error visible" style="margin-bottom:20px;">
                ⚠️ <?php echo esc_html($social_error); ?>
            </div>
            <?php endif; ?>

            <!-- ══ LOGIN SCREEN ══ -->
            <div id="screen-login" class="auth-screen <?php echo $initial_state === 'login' ? 'active' : ''; ?>">
                <nav class="auth-breadcrumb">
                    <a href="<?php echo esc_url($home_url); ?>">Homepage</a>
                    <span class="sep">/</span>
                    <span class="current">Sign in</span>
                </nav>
                <h1 class="auth-title">Sign in</h1>

                <div id="error-login" class="auth-error"></div>

                <div class="auth-form-group">
                    <div class="auth-field-group">
                        <p class="auth-section-label">Login details</p>
                        <div>
                            <label style="display:block;font-size:14px;font-weight:500;color:#44403c;margin-bottom:6px;">Phone number or Email</label>
                            <input id="login-identifier" type="text" placeholder="Enter your email or phone" class="auth-input">
                        </div>
                        <div>
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                                <label style="font-size:14px;font-weight:500;color:#44403c;">Password</label>
                                <a href="#" style="font-size:13px;color:#E15D43;text-decoration:none;">Forgot password?</a>
                            </div>
                            <div class="pw-wrap">
                                <input id="login-password" type="password" placeholder="Enter your password" class="auth-input">
                                <button type="button" class="pw-toggle" onclick="baceraTogglePw(this,'login-password')" aria-label="Show password">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <?php if ($turnstile_enabled): ?>
                    <div class="cf-turnstile-wrap">
                        <div class="cf-turnstile" id="turnstile-login"
                             data-sitekey="<?php echo esc_attr($turnstile_site_key); ?>"
                             data-callback="baceraOnTurnstileLogin"
                             data-theme="light"></div>
                    </div>
                    <?php endif; ?>

                    <div class="auth-actions">
                        <button type="button" id="btn-login" class="auth-btn-primary" onclick="baceraDoLogin()">
                            Sign in
                        </button>

                        <div class="auth-divider"><hr><span>Or sign in with</span><hr></div>

                        <div class="auth-socials" id="social-login">
                            <?php if ($auth_config['googleEnabled']): ?>
                            <button type="button" class="auth-btn-social" onclick="window.location.href='<?php echo esc_js($auth_config['googleAuthUrl']); ?>'">
                                <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                                Sign in with Google
                            </button>
                            <?php endif; ?>
                            <?php if ($auth_config['facebookEnabled']): ?>
                            <button type="button" class="auth-btn-social" onclick="window.location.href='<?php echo esc_js($auth_config['facebookAuthUrl']); ?>'">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="#1877F2" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                Sign in with Facebook
                            </button>
                            <?php endif; ?>
                        </div>

                        <p class="auth-link-text">
                            Don't have an account?
                            <button type="button" onclick="baceraShowScreen('register')">Sign up</button>
                        </p>
                    </div>
                </div>
            </div>

            <!-- ══ REGISTER SCREEN ══ -->
            <div id="screen-register" class="auth-screen <?php echo $initial_state === 'register' ? 'active' : ''; ?>">
                <nav class="auth-breadcrumb">
                    <a href="<?php echo esc_url($home_url); ?>">Homepage</a>
                    <span class="sep">/</span>
                    <a href="#" onclick="baceraShowScreen('login');return false;">Sign in</a>
                    <span class="sep">/</span>
                    <span class="current">Sign up</span>
                </nav>
                <h1 class="auth-title">Create account</h1>

                <div id="error-register" class="auth-error"></div>

                <div class="auth-form-group">
                    <div class="auth-field-group">
                        <p class="auth-section-label">Account information</p>
                        <div>
                            <label style="display:block;font-size:14px;font-weight:500;color:#44403c;margin-bottom:6px;">Full name <span style="color:#E15D43;">*</span></label>
                            <input id="reg-name" type="text" placeholder="Enter your full name" class="auth-input">
                        </div>
                        <div>
                            <label style="display:block;font-size:14px;font-weight:500;color:#44403c;margin-bottom:6px;">Phone number</label>
                            <input id="reg-phone" type="tel" placeholder="Enter your phone number" class="auth-input">
                        </div>
                        <div>
                            <label style="display:block;font-size:14px;font-weight:500;color:#44403c;margin-bottom:6px;">Email</label>
                            <input id="reg-email" type="email" placeholder="Enter your email address" class="auth-input">
                        </div>
                        <p style="font-size:12px;color:#78716c;margin:0;">※ Please provide at least one: Phone number or Email.</p>
                    </div>

                    <div class="auth-field-group">
                        <p class="auth-section-label">Password</p>
                        <div class="pw-wrap">
                            <input id="reg-password" type="password" placeholder="Enter your password" class="auth-input">
                            <button type="button" class="pw-toggle" onclick="baceraTogglePw(this,'reg-password')" aria-label="Show password">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                        <div class="pw-wrap">
                            <input id="reg-confirm" type="password" placeholder="Confirm password" class="auth-input">
                            <button type="button" class="pw-toggle" onclick="baceraTogglePw(this,'reg-confirm')" aria-label="Show password">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                    </div>

                    <?php if ($turnstile_enabled): ?>
                    <div class="cf-turnstile-wrap">
                        <div class="cf-turnstile" id="turnstile-register"
                             data-sitekey="<?php echo esc_attr($turnstile_site_key); ?>"
                             data-callback="baceraOnTurnstileRegister"
                             data-theme="light"></div>
                    </div>
                    <?php endif; ?>

                    <div class="auth-actions">
                        <button type="button" id="btn-register" class="auth-btn-primary" onclick="baceraDoRegister()">
                            Sign up
                        </button>

                        <?php if ($auth_config['googleEnabled'] || $auth_config['facebookEnabled']): ?>
                        <div class="auth-divider"><hr><span>Or sign up with</span><hr></div>
                        <div class="auth-socials">
                            <?php if ($auth_config['googleEnabled']): ?>
                            <button type="button" class="auth-btn-social" onclick="window.location.href='<?php echo esc_js($auth_config['googleAuthUrl']); ?>'">
                                <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/></svg>
                                Sign up with Google
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <p class="auth-link-text">
                            Already have an account?
                            <button type="button" onclick="baceraShowScreen('login')">Sign in</button>
                        </p>
                    </div>
                </div>
            </div>

            <!-- ══ OTP SCREEN ══ -->
            <div id="screen-otp" class="auth-screen">
                <button type="button" class="auth-back-btn" onclick="baceraShowScreen(baceraState.prevScreen)">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back
                </button>
                <h1 class="auth-title">OTP Verification</h1>
                <p style="color:#78716c;font-size:14px;margin:0 0 6px;">
                    Enter the 6-digit code sent to<br>
                    <strong id="otp-target" style="color:#1c1917;font-size:15px;"></strong>
                </p>
                <div id="otp-hint" style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;font-size:13px;color:#92400e;margin:12px 0 24px;"></div>

                <div id="error-otp" class="auth-error"></div>

                <div style="display:flex;gap:8px;margin-bottom:28px;" id="otp-boxes">
                    <input type="text" inputmode="numeric" maxlength="1" class="otp-input" autocomplete="one-time-code">
                    <input type="text" inputmode="numeric" maxlength="1" class="otp-input">
                    <input type="text" inputmode="numeric" maxlength="1" class="otp-input">
                    <input type="text" inputmode="numeric" maxlength="1" class="otp-input">
                    <input type="text" inputmode="numeric" maxlength="1" class="otp-input">
                    <input type="text" inputmode="numeric" maxlength="1" class="otp-input">
                </div>

                <div style="display:flex;flex-direction:column;gap:12px;">
                    <button type="button" id="btn-otp" class="auth-btn-primary" onclick="baceraDoOtp()">
                        Verify
                    </button>
                    <p style="text-align:center;font-size:14px;color:#78716c;margin:0;">
                        Didn't receive the code?
                        <button type="button" style="color:#E15D43;font-weight:500;background:none;border:none;cursor:pointer;font-family:inherit;font-size:14px;padding:0;">Resend</button>
                    </p>
                </div>
            </div>

            <!-- ══ SUCCESS SCREEN ══ -->
            <div id="screen-success" class="auth-screen <?php echo $initial_state === 'success' ? 'active' : ''; ?>">
                <div style="width:64px;height:64px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
                    <svg width="32" height="32" fill="none" stroke="#16a34a" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h1 class="auth-title">Welcome!</h1>
                <p style="color:#78716c;font-size:15px;margin:0 0 28px;">You've signed in successfully. Explore Bacera now.</p>

                <div style="display:flex;flex-direction:column;gap:10px;">
                    <a href="<?php echo esc_url($home_url); ?>" class="auth-link-row">
                        <span>Homepage</span>
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a href="#" class="auth-link-row">
                        <span>My Account</span>
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a href="#" class="auth-link-row">
                        <span>Book a Workshop</span>
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a href="<?php echo esc_url($logout_url); ?>"
                       style="margin-top:12px;color:#a8a29e;font-size:14px;text-align:center;display:block;text-decoration:none;"
                       onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#a8a29e'">
                        Sign out
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
(function() {

/* ── CONFIG ─────────────────────────────────────────── */
var AJAX_URL = '<?php echo esc_js($ajax_url); ?>';
var TURNSTILE_ENABLED = <?php echo $turnstile_enabled ? 'true' : 'false'; ?>;

/* ── STATE ──────────────────────────────────────────── */
window.baceraState = {
    identifier: '',
    prevScreen: 'login',
    turnstileLoginToken:    '',
    turnstileRegisterToken: ''
};

/* ── TURNSTILE CALLBACKS ─────────────────────────────── */
window.baceraOnTurnstileLogin    = function(token) { baceraState.turnstileLoginToken    = token; };
window.baceraOnTurnstileRegister = function(token) { baceraState.turnstileRegisterToken = token; };

/* ── SCREEN SWITCHER ────────────────────────────────── */
window.baceraShowScreen = function(name) {
    document.querySelectorAll('.auth-screen').forEach(function(el) {
        el.classList.remove('active');
    });
    var el = document.getElementById('screen-' + name);
    if (el) {
        el.classList.add('active');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

/* ── ERROR HELPERS ──────────────────────────────────── */
function showError(id, msg) {
    var el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg;
    el.classList.add('visible');
}
function clearError(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.textContent = '';
    el.classList.remove('visible');
}

/* ── BUTTON LOADING STATE ───────────────────────────── */
function setLoading(btnId, loading) {
    var btn = document.getElementById(btnId);
    if (!btn) return;
    btn.disabled = loading;
    if (loading) {
        btn.dataset.origText = btn.innerHTML;
        btn.innerHTML = '<span class="auth-spinner"></span>';
    } else {
        if (btn.dataset.origText) btn.innerHTML = btn.dataset.origText;
    }
}

/* ── PASSWORD TOGGLE ────────────────────────────────── */
window.baceraTogglePw = function(btn, inputId) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    btn.setAttribute('aria-label', showing ? 'Hiện mật khẩu' : 'Ẩn mật khẩu');
};

/* ── AJAX HELPER ─────────────────────────────────────── */
function baceraPost(action, data) {
    var fd = new FormData();
    fd.append('action', action);
    Object.keys(data).forEach(function(k) { fd.append(k, data[k]); });
    return fetch(AJAX_URL, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .catch(function() { return { success: false, data: { message: 'Connection error. Please try again.' } }; });
}

/* ── DO LOGIN ────────────────────────────────────────── */
window.baceraDoLogin = function() {
    clearError('error-login');
    var identifier = document.getElementById('login-identifier').value.trim();
    var password   = document.getElementById('login-password').value;

    if (!identifier || !password) {
        showError('error-login', 'Please fill in all fields.');
        return;
    }

    if (TURNSTILE_ENABLED && !baceraState.turnstileLoginToken) {
        showError('error-login', 'Please complete the CAPTCHA verification.');
        return;
    }

    setLoading('btn-login', true);
    baceraPost('bacera_auth_submit', {
        auth_state:            'login',
        identifier:            identifier,
        password:              password,
        cf_turnstile_response: baceraState.turnstileLoginToken
    }).then(function(res) {
        setLoading('btn-login', false);
        if (!res || !res.success) {
            var msg = (res && res.data && res.data.message) ? res.data.message : 'Sign in failed.';
            showError('error-login', msg);
            if (res && res.data && res.data.action_needed === 'register') {
                setTimeout(function() { baceraShowScreen('register'); }, 1500);
            }
        } else {
            if (res.data && res.data.skip_otp) {
                baceraShowScreen('success');
                return;
            }

            baceraState.identifier = identifier;
            baceraState.prevScreen = 'login';
            document.getElementById('otp-target').textContent = identifier;

            var devOtp = res.data && res.data.dev_otp;
            if (devOtp) {
                // No SMTP — show OTP directly on screen (dev mode)
                document.getElementById('otp-hint').innerHTML =
                    '🛠️ <strong>Dev mode</strong>: SMTP not configured, email was not sent.<br>' +
                    'Your OTP code is: <span style="font-size:22px;font-weight:700;color:#1c1917;letter-spacing:6px;display:inline-block;margin-top:6px;">' + devOtp + '</span>';
                document.getElementById('otp-hint').style.cssText = 'background:#fefce8;border:1px solid #fde047;border-radius:8px;padding:12px 14px;font-size:13px;color:#713f12;margin:12px 0 24px;';
                // Auto-fill OTP boxes
                var boxes = document.querySelectorAll('#otp-boxes .otp-input');
                devOtp.split('').forEach(function(d, i) { if (boxes[i]) boxes[i].value = d; });
            } else {
                var isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(identifier);
                document.getElementById('otp-hint').textContent = isEmail
                    ? '📨 OTP code sent to your email. Please check your inbox (including Spam folder).'
                    : '🔒 Your 6-digit OTP code. Please contact admin if signing in by phone number.';
                document.getElementById('otp-hint').style.cssText = '';
            }
            baceraShowScreen('otp');
        }
    });
};

/* ── DO REGISTER ─────────────────────────────────────── */
window.baceraDoRegister = function() {
    clearError('error-register');
    var name     = document.getElementById('reg-name').value.trim();
    var phone    = document.getElementById('reg-phone').value.trim();
    var email    = document.getElementById('reg-email').value.trim();
    var password = document.getElementById('reg-password').value;
    var confirm  = document.getElementById('reg-confirm').value;

    if (!name) {
        showError('error-register', 'Please enter your full name.');
        return;
    }
    if (!phone && !email) {
        showError('error-register', 'Please enter a Phone number or Email.');
        return;
    }
    if (!password) {
        showError('error-register', 'Please enter a password.');
        return;
    }
    if (password !== confirm) {
        showError('error-register', 'Passwords do not match.');
        return;
    }

    if (TURNSTILE_ENABLED && !baceraState.turnstileRegisterToken) {
        showError('error-register', 'Please complete the CAPTCHA verification.');
        return;
    }

    var identifier = email || phone;
    setLoading('btn-register', true);
    baceraPost('bacera_auth_submit', {
        auth_state:            'register',
        identifier:            identifier,
        password:              password,
        name:                  name,
        cf_turnstile_response: baceraState.turnstileRegisterToken
    }).then(function(res) {
        setLoading('btn-register', false);
        if (!res || !res.success) {
            var msg = (res && res.data && res.data.message) ? res.data.message : 'Sign up failed.';
            showError('error-register', msg);
            if (res && res.data && res.data.action_needed === 'login') {
                setTimeout(function() { baceraShowScreen('login'); }, 1500);
            }
        } else {
            if (res.data && res.data.skip_otp) {
                baceraShowScreen('success');
                return;
            }

            baceraState.identifier = identifier;
            baceraState.prevScreen = 'register';
            document.getElementById('otp-target').textContent = identifier;

            var devOtp = res.data && res.data.dev_otp;
            if (devOtp) {
                document.getElementById('otp-hint').innerHTML =
                    '🛠️ <strong>Dev mode</strong>: SMTP not configured, email was not sent.<br>' +
                    'Your OTP code is: <span style="font-size:22px;font-weight:700;color:#1c1917;letter-spacing:6px;display:inline-block;margin-top:6px;">' + devOtp + '</span>';
                document.getElementById('otp-hint').style.cssText = 'background:#fefce8;border:1px solid #fde047;border-radius:8px;padding:12px 14px;font-size:13px;color:#713f12;margin:12px 0 24px;';
                // Auto-fill OTP boxes
                var boxes = document.querySelectorAll('#otp-boxes .otp-input');
                devOtp.split('').forEach(function(d, i) { if (boxes[i]) boxes[i].value = d; });
            } else {
                var isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(identifier);
                document.getElementById('otp-hint').textContent = isEmail
                    ? '📨 OTP code sent to your email. Please check your inbox (including Spam folder).'
                    : '🔒 Your 6-digit OTP code. Please contact admin if registering by phone number.';
                document.getElementById('otp-hint').style.cssText = '';
            }
            baceraShowScreen('otp');
        }
    });
};

/* ── DO OTP ──────────────────────────────────────────── */
window.baceraDoOtp = function() {
    clearError('error-otp');
    var boxes = document.querySelectorAll('#otp-boxes .otp-input');
    var code  = '';
    boxes.forEach(function(b) { code += b.value; });

    if (code.length !== 6) {
        showError('error-otp', 'Please enter all 6 digits.');
        return;
    }

    setLoading('btn-otp', true);
    baceraPost('bacera_verify_otp', {
        identifier: baceraState.identifier,
        otp:        code
    }).then(function(res) {
        setLoading('btn-otp', false);
        if (!res || !res.success) {
            var msg = (res && res.data && res.data.message) ? res.data.message : 'Invalid OTP code.';
            showError('error-otp', msg);
        } else {
            baceraShowScreen('success');
        }
    });
};

/* ── OTP INPUT NAVIGATION ────────────────────────────── */
(function() {
    var boxes = document.querySelectorAll('#otp-boxes .otp-input');
    boxes.forEach(function(box, i) {
        box.addEventListener('input', function() {
            var val = this.value.replace(/[^0-9]/g, '');
            this.value = val.slice(-1);
            if (val && i < 5) boxes[i + 1].focus();
            // Auto-submit when all filled
            var code = '';
            boxes.forEach(function(b) { code += b.value; });
            if (code.length === 6) baceraDoOtp();
        });
        box.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value && i > 0) {
                boxes[i - 1].value = '';
                boxes[i - 1].focus();
            }
        });
    });

    // Paste support on first box
    if (boxes[0]) {
        boxes[0].addEventListener('paste', function(e) {
            e.preventDefault();
            var text   = (e.clipboardData || window.clipboardData).getData('text');
            var digits = text.replace(/[^0-9]/g, '').slice(0, 6).split('');
            digits.forEach(function(d, i) { if (boxes[i]) boxes[i].value = d; });
            if (boxes[digits.length - 1]) boxes[digits.length - 1].focus();
            if (digits.length === 6) baceraDoOtp();
        });
    }
})();

/* ── ENTER KEY on login/register ─────────────────────── */
document.getElementById('login-password').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') baceraDoLogin();
});
document.getElementById('login-identifier').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') baceraDoLogin();
});
document.getElementById('reg-name').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') baceraDoRegister();
});
document.getElementById('reg-confirm').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') baceraDoRegister();
});

})();
</script>

<?php get_footer(); ?>
