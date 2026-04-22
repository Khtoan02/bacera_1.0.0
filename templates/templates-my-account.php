<?php
/**
 * Template Name: My Account
 */

// ── Auth guard ───────────────────────────────────────────────────────────────
global $wpdb;
$bacera_customer    = null;
$bacera_auth_cookie = $_COOKIE['bacera_customer_auth'] ?? '';
if ( $bacera_auth_cookie ) {
    $decoded = base64_decode( $bacera_auth_cookie, true );
    if ( $decoded && strpos( $decoded, '|' ) !== false ) {
        $cid = (int) explode( '|', $decoded )[0];
        if ( $cid > 0 ) {
            $bacera_customer = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}bacera_customers WHERE id = %d LIMIT 1",
                    $cid
                ),
                ARRAY_A
            );
        }
    }
}

// Redirect to auth if not logged in
if ( ! $bacera_customer ) {
    $auth_page_id = $wpdb->get_var(
        "SELECT p.ID FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         WHERE p.post_type='page' AND p.post_status='publish'
           AND pm.meta_key='_wp_page_template'
           AND pm.meta_value IN ('templates/template-auth.php','template-auth.php')
         LIMIT 1"
    );
    $auth_url = $auth_page_id ? get_permalink( (int) $auth_page_id ) : home_url( '/auth/' );
    wp_safe_redirect( $auth_url );
    exit;
}

$cust_name   = $bacera_customer['name']  ?: '';
$cust_email  = $bacera_customer['email'] ?: '';
$cust_phone  = $bacera_customer['phone'] ?: '';
$cust_sub    = $cust_email ?: $cust_phone;
$avatar_name = rawurlencode( $cust_name ?: 'K' );
$avatar_url  = "https://ui-avatars.com/api/?name={$avatar_name}&background=3d2f26&color=E67258&bold=true&size=128";
$logout_url  = add_query_arg( 'bacera_logout', '1', home_url( '/' ) );
$ajax_url    = admin_url( 'admin-ajax.php' );

$name_parts = explode(' ', $cust_name, 2);
$first_name = $name_parts[0] ?? '';
$last_name  = $name_parts[1] ?? '';

get_header();
?>

<style>
.ma-wrap { padding-top: 76px; }

/* ── Sidebar tab button ── */
.ma-tab-btn {
    display: flex; align-items: center; justify-content: flex-start; gap: 12px;
    width: 100%; padding: 12px 14px; border-radius: 10px;
    border: none; background: none; cursor: pointer;
    font-size: 14px; font-weight: 500; font-family: inherit;
    color: #78716c; text-align: left;
    transition: background .15s, color .15s;
}
.ma-tab-btn:hover { background: #f5f5f4; color: #1c1917; }
.ma-tab-btn.active { background: #f5f5f4; color: #1c1917; font-weight: 600; }
.ma-tab-btn svg { flex-shrink: 0; }

/* ── Input ── */
.ma-input {
    width: 100%; height: 52px; padding: 0 16px;
    border: 1.5px solid #e7e5e4; border-radius: 10px;
    background: #fff; font-size: 14px; font-family: inherit; color: #1c1917;
    transition: border-color .15s, box-shadow .15s;
    outline: none;
}
.ma-input:focus { border-color: #3d2f26; box-shadow: 0 0 0 3px rgba(61,47,38,.08); }
.ma-input:disabled { background: #fafaf9; color: #a8a29e; cursor: not-allowed; }
.ma-input::placeholder { color: #a8a29e; }

/* ── pw-wrap ── */
.ma-pw-wrap { position: relative; }
.ma-pw-wrap .ma-input { padding-right: 48px; }
.ma-pw-toggle {
    position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer; padding: 4px;
    color: #a8a29e; display: flex; align-items: center; transition: color .15s;
}
.ma-pw-toggle:hover { color: #1c1917; }

/* ── OTP boxes ── */
.ma-otp-box {
    width: 52px; height: 60px; border: 1.5px solid #e7e5e4; border-radius: 10px;
    font-size: 22px; font-weight: 700; text-align: center; font-family: inherit;
    color: #1c1917; background: #fff; outline: none; transition: border-color .15s;
}
.ma-otp-box:focus { border-color: #3d2f26; box-shadow: 0 0 0 3px rgba(61,47,38,.08); }

/* ── Buttons ── */
.ma-btn-primary {
    display: inline-flex; align-items: center; justify-content: center;
    height: 48px; padding: 0 28px; border-radius: 10px;
    background: #e7e5e4; color: #1c1917; font-size: 15px; font-weight: 600;
    font-family: inherit; border: none; cursor: pointer; transition: background .15s, opacity .15s;
}
.ma-btn-primary:not(:disabled):hover { background: #d6d3d1; }
.ma-btn-primary:disabled { opacity: .5; cursor: not-allowed; }
.ma-btn-primary.dark { background: #3d2f26; color: #fff; }
.ma-btn-primary.dark:not(:disabled):hover { background: #2c2018; }
.ma-btn-secondary {
    display: inline-flex; align-items: center; justify-content: center;
    height: 48px; padding: 0 24px; border-radius: 10px;
    background: none; color: #78716c; font-size: 15px; font-weight: 500;
    font-family: inherit; border: 1.5px solid #e7e5e4; cursor: pointer;
    transition: border-color .15s, color .15s;
}
.ma-btn-secondary:hover { border-color: #a8a29e; color: #1c1917; }
.ma-btn-danger {
    display: inline-flex; align-items: center; justify-content:center;
    gap: 8px; width: 100%; padding: 12px 14px; border-radius: 10px;
    font-size: 14px; font-weight: 500; font-family: inherit;
    color: #ef4444; background: none; border: none; cursor: pointer; text-align: left;
    transition: background .15s;
}
.ma-btn-danger:hover { background: #fef2f2; }

/* ── Checkbox custom ── */
.ma-checkbox-wrap {
    display: flex; gap: 12px; align-items: flex-start; cursor: pointer; margin-bottom: 24px;
}
.ma-checkbox-ui {
    width: 20px; height: 20px; border: 1px solid #a8a29e; border-radius: 50%;
    margin-top: 2px; position: relative; display: flex; align-items: center; justify-content: center;
    transition: border-color .15s;
}
input[type="checkbox"] { position: absolute; opacity: 0; width: 100%; height: 100%; cursor: pointer; z-index: 2; margin: 0; }
input[type="checkbox"]:checked ~ .ma-checkbox-ui { border-color: #1c1917; }
input[type="checkbox"]:checked ~ .ma-checkbox-ui::after {
    content: ''; width: 10px; height: 10px; background: #1c1917; border-radius: 50%;
}

/* ── Section UI (Account Tab) ── */
.ma-section-row {
    display: flex; gap: 32px; flex-wrap: wrap; margin-bottom: 32px; padding-bottom: 32px;
    border-bottom: 1px solid #f5f5f4;
}
.ma-section-row:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.ma-section-info { width: 100%; flex-shrink: 0; }
@media (min-width: 1024px) { .ma-section-info { width: 288px; } }
.ma-section-content { flex: 1; min-width: 0; max-width: 440px; }
.ma-section-heading { font-size: 24px; font-weight: 600; color: #1c1917; line-height: 1.3; }
.ma-section-desc { font-size: 15px; color: #57534e; margin-top: 8px; line-height: 1.5; }
.ma-input-row { display: flex; gap: 8px; margin-bottom: 8px; }

/* ── Alert ── */
.ma-alert { padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 500; display: none; }
.ma-alert.success { background: #dcfce7; color: #16a34a; display: block; }
.ma-alert.error   { background: #fee2e2; color: #dc2626; display: block; }

/* ── Tab panels ── */
.ma-panel { display: none; }
.ma-panel.active { display: block; }

/* ── In-input Edit Button ── */
.ma-inline-edit {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer; font-size: 14px; font-weight: 500;
    color: #3d2f26; padding: 4px 8px; border-radius: 6px; transition: background .15s;
    z-index: 10;
}
.ma-inline-edit:hover { background: #f5f5f4; }

/* ── Modal ── */
.ma-modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.4);
    display: flex; align-items: center; justify-content: center;
    z-index: 200; padding: 16px;
    opacity: 0; pointer-events: none; transition: opacity .2s;
}
.ma-modal-overlay.open { opacity: 1; pointer-events: all; }
.ma-modal {
    background: #fff; border-radius: 20px; padding: 32px;
    max-width: 440px; width: 100%; box-shadow: 0 24px 64px rgba(0,0,0,.18);
    transform: translateY(8px); transition: transform .2s;
}
.ma-modal-overlay.open .ma-modal { transform: translateY(0); }
</style>

<div class="ma-wrap">
<main>
<div style="min-height:calc(100vh - 76px);background:#fafaf9;padding:40px 16px 80px;">
<div style="max-width:1200px;margin:0 auto;display:flex;flex-direction:column;gap:32px;">

    <!-- Breadcrumb + heading -->
    <div>
        <div style="display:flex;align-items:center;gap:6px;font-size:14px;font-weight:500;margin-bottom:12px;">
            <a href="<?php echo esc_url(home_url('/')); ?>" style="color:#3d2f26;text-decoration:none;">Homepage</a>
            <span style="color:#d6d3d1;">/</span>
            <span style="color:#a8a29e;">My Account</span>
        </div>
        <h1 style="font-size:clamp(28px,4vw,40px);font-weight:700;color:#1c1917;font-family:'Gowun Batang',serif;margin:0;">
            Hello, <span style="color:#c05a43;"><?php echo esc_html($cust_name ?: 'there'); ?></span>
        </h1>
    </div>

    <!-- Layout: sidebar + content -->
    <div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;">

        <!-- Sidebar -->
        <aside style="width:260px;flex-shrink:0;background:#fff;border:1.5px solid #e7e5e4;border-radius:16px;padding:16px;display:flex;flex-direction:column;gap:4px;position:sticky;top:96px;">

            <!-- Avatar info -->
            <div style="display:flex;align-items:center;gap:12px;padding:12px;margin-bottom:8px;border-bottom:1px solid #f5f5f4;">
                <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($cust_name); ?>"
                     style="width:44px;height:44px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                <div style="min-width:0;">
                    <div style="font-weight:700;font-size:14px;color:#1c1917;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo esc_html($cust_name); ?></div>
                    <div style="font-size:12px;color:#a8a29e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo esc_html($cust_sub); ?></div>
                </div>
            </div>

            <?php
            $sidebar_tabs = [
                'account'   => ['icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'label' => 'Account details'],
                'address'   => ['icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z', 'label' => 'Delivery addresses'],
                'orders'    => ['icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'label' => 'My orders'],
                'workshops' => ['icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'label' => 'Booked workshops'],
            ];
            foreach ( $sidebar_tabs as $key => $tab ): ?>
            <button class="ma-tab-btn <?php echo $key === 'account' ? 'active' : ''; ?>" data-tab="<?php echo $key; ?>">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $tab['icon']; ?>"/>
                </svg>
                <?php echo esc_html($tab['label']); ?>
            </button>
            <?php endforeach; ?>

            <div style="height:1px;background:#f5f5f4;margin:8px 0;"></div>
            <a href="<?php echo esc_url($logout_url); ?>" class="ma-btn-danger">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Sign out
            </a>
        </aside>

        <!-- Main panels -->
        <div style="flex:1;min-width:0;background:#fff;border:1.5px solid #e7e5e4;border-radius:16px;padding:clamp(24px,5vw,56px);">

            <!-- ── PANEL: Thông tin tài khoản ── -->
            <div id="panel-account" class="ma-panel active">
                
                <div id="alert-account-global" class="ma-alert" style="margin-bottom:24px;"></div>

                <!-- Section 1: User Info -->
                <div class="ma-section-row">
                    <div class="ma-section-info">
                        <h2 class="ma-section-heading">Account details</h2>
                        <p class="ma-section-desc">This information is used to sign in and displayed in your profile.</p>
                    </div>
                    <div class="ma-section-content">
                        <form id="form-account">
                            <div class="ma-input-row" style="margin-bottom:8px;">
                                <input type="text" id="input-first-name" class="ma-input" placeholder="First name" value="<?php echo esc_attr($first_name); ?>" style="flex:1;">
                                <input type="text" id="input-last-name" class="ma-input" placeholder="Last name" value="<?php echo esc_attr($last_name); ?>" style="flex:1;">
                            </div>
                            <div style="position:relative; margin-bottom:8px;">
                                <input type="text" class="ma-input" placeholder="Phone number" value="<?php echo esc_attr($cust_phone); ?>" disabled>
                                <button type="button" class="ma-inline-edit" onclick="openEditModal('phone')">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </button>
                            </div>
                            <div style="position:relative; margin-bottom:16px;">
                                <input type="text" class="ma-input" placeholder="Email" value="<?php echo esc_attr($cust_email); ?>" disabled>
                                <button type="button" class="ma-inline-edit" onclick="openEditModal('email')">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </button>
                            </div>
                            <button type="submit" class="ma-btn-primary">Save</button>
                        </form>
                    </div>
                </div>

                <!-- Section 2: Notifications -->
                <div class="ma-section-row">
                    <div class="ma-section-info">
                        <h2 class="ma-section-heading">Email notifications</h2>
                        <p class="ma-section-desc">Customize your email notification preferences for promotions and new products.</p>
                    </div>
                    <div class="ma-section-content">
                        <form id="form-notifications" onsubmit="event.preventDefault()">
                            <label class="ma-checkbox-wrap">
                                <div style="position:relative;margin-top:2px;">
                                    <input type="checkbox">
                                    <div class="ma-checkbox-ui"></div>
                                </div>
                                <span style="font-size:15px;color:#1c1917;line-height:1.5;">Receive email notifications for promotions and new products</span>
                            </label>
                            <button type="submit" class="ma-btn-primary">Save</button>
                        </form>
                    </div>
                </div>

                <!-- Section 3: Password Update -->
                <div class="ma-section-row">
                    <div class="ma-section-info">
                        <h2 class="ma-section-heading">Change password</h2>
                    </div>
                    <div class="ma-section-content">
                        <form id="form-password-change">
                            <div class="ma-pw-wrap" style="margin-bottom:8px;">
                                <input id="old-password" class="ma-input" type="password" placeholder="Current password">
                                <button type="button" class="ma-pw-toggle" onclick="togglePw(this)">
                                    <svg class="eye-on" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg class="eye-off" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                            <div class="ma-pw-wrap" style="margin-bottom:8px;">
                                <input id="new-password" class="ma-input" type="password" placeholder="New password">
                                <button type="button" class="ma-pw-toggle" onclick="togglePw(this)">
                                    <svg class="eye-on" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg class="eye-off" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                            <div class="ma-pw-wrap" style="margin-bottom:8px;">
                                <input id="confirm-password" class="ma-input" type="password" placeholder="Confirm new password">
                                <button type="button" class="ma-pw-toggle" onclick="togglePw(this)">
                                    <svg class="eye-on" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg class="eye-off" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                            <div style="font-size:13px;color:#78716c;margin-bottom:24px;line-height:1.6;opacity:0.8;">
                                Minimum 8 characters<br>At least 1 uppercase and 1 lowercase letter<br>At least 1 special character
                            </div>
                            <button type="submit" class="ma-btn-primary">Save</button>
                        </form>
                    </div>
                </div>

            </div>

            <!-- ── PANEL: Sổ địa chỉ ── -->
            <div id="panel-address" class="ma-panel">
                <div style="margin-bottom:28px;">
                    <p class="ma-section-heading">Delivery addresses</p>
                    <p class="ma-section-desc">Manage your saved delivery addresses.</p>
                </div>
                <div style="display:flex;align-items:center;justify-content:center;flex-direction:column;gap:16px;padding:60px 0;color:#a8a29e;">
                    <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p style="font-size:15px;">You have no saved addresses yet.</p>
                    <button class="ma-btn-primary dark">Add new address</button>
                </div>
            </div>

            <!-- ── PANEL: Đơn hàng ── -->
            <div id="panel-orders" class="ma-panel">
                <div style="margin-bottom:28px;">
                    <p class="ma-section-heading">My orders</p>
                    <p class="ma-section-desc">Your order history.</p>
                </div>
                <div style="display:flex;align-items:center;justify-content:center;flex-direction:column;gap:16px;padding:60px 0;color:#a8a29e;">
                    <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <p style="font-size:15px;">You have no orders yet.</p>
                    <a href="<?php echo esc_url(home_url('/shop')); ?>" class="ma-btn-primary dark">Explore the shop</a>
                </div>
            </div>

            <!-- ── PANEL: Workshop ── -->
            <div id="panel-workshops" class="ma-panel">
                <div style="margin-bottom:28px;">
                    <p class="ma-section-heading">Booked workshops</p>
                    <p class="ma-section-desc">Pottery classes you have booked.</p>
                </div>
                <div style="display:flex;align-items:center;justify-content:center;flex-direction:column;gap:16px;padding:60px 0;color:#a8a29e;">
                    <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <p style="font-size:15px;">You haven't booked any workshops yet.</p>
                    <a href="<?php echo esc_url(home_url('/workshop')); ?>" class="ma-btn-primary dark">View workshops</a>
                </div>
            </div>

        </div><!-- /panels -->
    </div><!-- /layout -->
</div>
</div>
</main>
</div>

<!-- ══════════════════ MODALS ══════════════════ -->

<!-- Modal 1: Chỉnh sửa SĐT / Email -->
<div id="edit-modal" class="ma-modal-overlay" onclick="if(event.target===this)closeEditModal()">
    <div class="ma-modal">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
            <h3 id="modal-title" style="font-size:20px;font-weight:700;color:#1c1917;margin:0;"></h3>
            <button onclick="closeEditModal()" style="background:none;border:none;cursor:pointer;color:#a8a29e;padding:4px;">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="modal-alert" class="ma-alert" style="margin-bottom:16px;"></div>

        <!-- Step 1 -->
        <div id="modal-step1">
            <p style="font-size:15px;color:#57534e;margin-bottom:16px;line-height:1.5;" id="modal-desc"></p>
            <input id="modal-input" class="ma-input" type="text" placeholder="" style="margin-bottom:24px;">
            <div style="display:flex;gap:12px;">
                <button class="ma-btn-primary dark" style="flex:1;" id="modal-send-otp" onclick="sendOtp()">Send OTP</button>
                <button class="ma-btn-secondary" style="flex:1;" onclick="closeEditModal()">Cancel</button>
            </div>
        </div>

        <!-- Step 2 -->
        <div id="modal-step2" style="display:none;">
            <p style="font-size:15px;color:#57534e;margin-bottom:20px;line-height:1.5;">Enter the 4-digit code sent to <strong id="modal-sent-to" style="color:#1c1917;"></strong></p>
            <div style="display:flex;gap:12px;justify-content:center;margin-bottom:24px;" id="modal-otp-boxes">
                <input class="ma-otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                <input class="ma-otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                <input class="ma-otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                <input class="ma-otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*">
            </div>
            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:12px 14px;font-size:14px;color:#92400e;margin-bottom:24px;">
                🔑 Test OTP code: <strong>1234</strong>
            </div>
            <div style="display:flex;gap:12px;">
                <button class="ma-btn-primary dark" style="flex:1;" onclick="verifyOtp()">Verify</button>
                <button class="ma-btn-secondary" style="flex:1;" onclick="document.getElementById('modal-step1').style.display='';document.getElementById('modal-step2').style.display='none';document.getElementById('modal-alert').className='ma-alert';">Go back</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Password change verification -->
<div id="pw-verify-modal" class="ma-modal-overlay" onclick="if(event.target===this)closePwModal()">
    <div class="ma-modal">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
            <h3 style="font-size:20px;font-weight:700;color:#1c1917;margin:0;">Account verification</h3>
            <button onclick="closePwModal()" style="background:none;border:none;cursor:pointer;color:#a8a29e;padding:4px;">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="pw-modal-alert" class="ma-alert" style="margin-bottom:16px;"></div>

        <!-- Step 1: Enter email/phone -->
        <div id="pw-modal-step1">
            <p style="font-size:15px;color:#57534e;margin-bottom:16px;line-height:1.5;">Please enter your registered Email or Phone number to receive a verification code to change your password.</p>
            <input id="pw-verify-contact" class="ma-input" type="text" placeholder="Email or phone" style="margin-bottom:24px;">
            <div style="display:flex;gap:12px;">
                <button class="ma-btn-primary dark" style="flex:1;" id="pw-btn-send" onclick="sendPwOtp()">Send OTP</button>
                <button class="ma-btn-secondary" style="flex:1;" onclick="closePwModal()">Cancel</button>
            </div>
        </div>

        <!-- Step 2: Enter OTP -->
        <div id="pw-modal-step2" style="display:none;">
            <p style="font-size:15px;color:#57534e;margin-bottom:20px;line-height:1.5;">Enter the 4-digit code sent to <strong id="pw-sent-to" style="color:#1c1917;"></strong></p>
            <div style="display:flex;gap:12px;justify-content:center;margin-bottom:24px;" id="pw-otp-boxes">
                <input class="ma-otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                <input class="ma-otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                <input class="ma-otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*">
                <input class="ma-otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*">
            </div>
            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:12px 14px;font-size:14px;color:#92400e;margin-bottom:24px;">
                🔑 Test OTP code: <strong>1234</strong>
            </div>
            <div style="display:flex;gap:12px;">
                <button class="ma-btn-primary dark" style="flex:1;" onclick="submitPwChange()">Verify &amp; Change password</button>
                <button class="ma-btn-secondary" style="flex:1;" onclick="document.getElementById('pw-modal-step1').style.display='';document.getElementById('pw-modal-step2').style.display='none';document.getElementById('pw-modal-alert').className='ma-alert';">Go back</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const AJAX = '<?php echo esc_js($ajax_url); ?>';
    
    // ── Tab switching ──────────────────────────────────────────────
    document.querySelectorAll('.ma-tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.ma-tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.ma-panel').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            const panel = document.getElementById('panel-' + this.dataset.tab);
            if (panel) panel.classList.add('active');
        });
    });

    // ── Password toggle ────────────────────────────────────────────
    window.togglePw = function(btn) {
        const wrap  = btn.closest('.ma-pw-wrap');
        const input = wrap.querySelector('.ma-input');
        const isText = input.type === 'text';
        input.type = isText ? 'password' : 'text';
        btn.querySelector('.eye-on').style.display  = isText ? '' : 'none';
        btn.querySelector('.eye-off').style.display = isText ? 'none' : '';
    };

    function showAlert(elId, msg, type) {
        const el = document.getElementById(elId);
        if (!el) return;
        el.textContent = msg;
        el.className = 'ma-alert ' + type;
    }

    // ── Save Account Info ──────────────────────────────────────────
    document.getElementById('form-account').addEventListener('submit', async function(e) {
        e.preventDefault();
        const first = document.getElementById('input-first-name').value.trim();
        const last  = document.getElementById('input-last-name').value.trim();
        const name  = `${first} ${last}`.trim();
        if (!name) { showAlert('alert-account-global', 'Please enter your name.', 'error'); return; }
        
        const fd = new FormData();
        fd.append('action', 'bacera_update_profile');
        fd.append('field', 'name');
        fd.append('value', name);
        const r = await fetch(AJAX, {method:'POST',body:fd}).then(r=>r.json()).catch(()=>null);
        if (r && r.success) showAlert('alert-account-global','✓ Information saved successfully.','success');
        else showAlert('alert-account-global', r?.data?.message || 'An error occurred.', 'error');
    });

    // ── MODAL: Cập nhật SĐT/Email ──────────────────────────────────
    let editField = null;
    let newValue  = '';
    window.openEditModal = function(field) {
        editField = field;
        const isPhone = field === 'phone';
        document.getElementById('modal-title').textContent = isPhone ? 'Thay đổi số điện thoại' : 'Thay đổi email';
        document.getElementById('modal-desc').textContent  = isPhone
            ? 'Nhập số điện thoại mới. Chúng tôi sẽ gửi mã OTP để xác minh.'
            : 'Nhập địa chỉ email mới. Chúng tôi sẽ gửi mã OTP để xác minh.';
        document.getElementById('modal-input').type = isPhone ? 'tel' : 'email';
        document.getElementById('modal-input').placeholder = isPhone ? '09xxxxxxxx' : 'email@example.com';
        document.getElementById('modal-input').value = '';
        document.getElementById('modal-alert').className = 'ma-alert';
        document.getElementById('modal-step1').style.display = '';
        document.getElementById('modal-step2').style.display = 'none';
        document.getElementById('edit-modal').classList.add('open');
    };
    window.closeEditModal = () => document.getElementById('edit-modal').classList.remove('open');

    // OTP Boxes logic shared
    function setupOtpBoxes(selector) {
        document.querySelectorAll(selector).forEach((box, i, boxes) => {
            box.addEventListener('input', () => {
                box.value = box.value.replace(/\D/g,'').slice(-1);
                if (box.value && i < boxes.length - 1) boxes[i+1].focus();
            });
            box.addEventListener('keydown', e => {
                if (e.key === 'Backspace' && !box.value && i > 0) boxes[i-1].focus();
            });
        });
    }
    setupOtpBoxes('#modal-otp-boxes .ma-otp-box');
    setupOtpBoxes('#pw-otp-boxes .ma-otp-box');

    window.sendOtp = async function() {
        newValue = document.getElementById('modal-input').value.trim();
        if (!newValue) { showAlert('modal-alert', 'Please enter a value.', 'error'); return; }
        const btn = document.getElementById('modal-send-otp');
        btn.disabled = true; btn.textContent = 'Sending...';
        
        const fd = new FormData();
        fd.append('action', 'bacera_send_update_otp');
        fd.append('field', editField);
        fd.append('value', newValue);
        const r = await fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).catch(()=>null);
        
        btn.disabled = false; btn.textContent = 'Send OTP';
        if (r && r.success) {
            document.getElementById('modal-sent-to').textContent = newValue;
            document.getElementById('modal-step1').style.display = 'none';
            document.getElementById('modal-step2').style.display = '';
            document.getElementById('modal-alert').className = 'ma-alert';
            document.querySelector('#modal-otp-boxes .ma-otp-box').focus();
        } else {
            showAlert('modal-alert', r?.data?.message || 'An error occurred.', 'error');
        }
    };

    window.verifyOtp = async function() {
        const otp = [...document.querySelectorAll('#modal-otp-boxes .ma-otp-box')].map(b=>b.value).join('');
        if (otp.length !== 4) { showAlert('modal-alert','Please enter all 4 digits.','error'); return; }
        const fd = new FormData();
        fd.append('action','bacera_verify_update_otp');
        fd.append('field', editField);
        fd.append('value', newValue);
        fd.append('otp',   otp);
        const r = await fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).catch(()=>null);
        if (r && r.success) {
            closeEditModal();
            showAlert('alert-account-global', '✓ Updated successfully! Reloading...', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showAlert('modal-alert', r?.data?.message || 'Invalid OTP code.', 'error');
        }
    };

    // ── MODAL: Đổi mật khẩu ────────────────────────────────────────
    let pwPayload = {};

    document.getElementById('form-password-change').addEventListener('submit', function(e) {
        e.preventDefault();
        const old_pw  = document.getElementById('old-password').value;
        const new_pw  = document.getElementById('new-password').value;
        const conf_pw = document.getElementById('confirm-password').value;
        
        showAlert('alert-account-global', '', ''); // clear old alerts
        
        if (!old_pw || !new_pw || !conf_pw) { showAlert('alert-account-global','Please fill in all password fields.','error'); return; }
        if (new_pw !== conf_pw) { showAlert('alert-account-global','Passwords do not match.','error'); return; }
        if (new_pw.length < 8)  { showAlert('alert-account-global','Password must be at least 8 characters.','error'); return; }
        
        pwPayload = { old_password: old_pw, new_password: new_pw };
        
        // Setup Modal
        document.getElementById('pw-verify-contact').value = '';
        document.getElementById('pw-modal-alert').className = 'ma-alert';
        document.getElementById('pw-modal-step1').style.display = '';
        document.getElementById('pw-modal-step2').style.display = 'none';
        document.getElementById('pw-verify-modal').classList.add('open');
    });

    window.closePwModal = () => document.getElementById('pw-verify-modal').classList.remove('open');

    window.sendPwOtp = async function() {
        const contact = document.getElementById('pw-verify-contact').value.trim();
        if (!contact) { showAlert('pw-modal-alert', 'Please enter your Email or Phone number.', 'error'); return; }
        
        const btn = document.getElementById('pw-btn-send');
        btn.disabled = true; btn.textContent = 'Sending...';

        const fd = new FormData();
        fd.append('action', 'bacera_send_pw_otp');
        fd.append('contact', contact);
        
        const r = await fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).catch(()=>null);
        
        btn.disabled = false; btn.textContent = 'Send OTP';
        if (r && r.success) {
            document.getElementById('pw-sent-to').textContent = contact;
            document.getElementById('pw-modal-step1').style.display = 'none';
            document.getElementById('pw-modal-step2').style.display = '';
            document.getElementById('pw-modal-alert').className = 'ma-alert';
            document.querySelector('#pw-otp-boxes .ma-otp-box').focus();
        } else {
            showAlert('pw-modal-alert', r?.data?.message || 'Có lỗi xảy ra.', 'error');
        }
    };

    window.submitPwChange = async function() {
        const otp = [...document.querySelectorAll('#pw-otp-boxes .ma-otp-box')].map(b=>b.value).join('');
        if (otp.length !== 4) { showAlert('pw-modal-alert','Please enter all 4 digits.','error'); return; }
        
        const btn = event.target;
        btn.disabled = true; btn.textContent = 'Processing...';

        const fd = new FormData();
        fd.append('action', 'bacera_change_password');
        fd.append('old_password', pwPayload.old_password);
        fd.append('new_password', pwPayload.new_password);
        fd.append('otp', otp);
        
        const r = await fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).catch(()=>null);
        
        btn.disabled = false; btn.textContent = 'Verify & Change password';
        if (r && r.success) {
            closePwModal();
            showAlert('alert-account-global', '✓ ' + r.data.message, 'success');
            document.getElementById('form-password-change').reset();
        } else {
            showAlert('pw-modal-alert', r?.data?.message || 'Có lỗi xảy ra.', 'error');
        }
    };
})();
</script>

<?php get_footer(); ?>