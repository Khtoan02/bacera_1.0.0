<?php
$my_account_url = home_url('/tai-khoan-cua-toi/');
$login_url = home_url('/auth/');
$contact_url = home_url('/contact/');

global $wpdb;
$bacera_auth_cookie = $_COOKIE['bacera_customer_auth'] ?? '';
$is_logged_in = false;
$cust_name = 'Khách hàng';
$cust_sub  = 'Chưa đăng nhập';
$cust_email = '';

if ( $bacera_auth_cookie ) {
    $decoded = base64_decode( $bacera_auth_cookie, true );
    if ( $decoded && strpos( $decoded, '|' ) !== false ) {
        $customer_id = (int) explode( '|', $decoded )[0];
        if ( $customer_id > 0 ) {
            $bacera_cust_table = $wpdb->prefix . 'bacera_customers';
            if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $bacera_cust_table ) ) === $bacera_cust_table ) {
                $row = $wpdb->get_row( $wpdb->prepare("SELECT name, phone, email FROM `{$bacera_cust_table}` WHERE id = %d LIMIT 1", $customer_id), ARRAY_A);
                if ($row) {
                    $is_logged_in = true;
                    $cust_name  = $row['name'] ?: ($row['phone'] ?: 'Thành viên Bacera');
                    $cust_email = $row['email'] ?: $row['phone'] ?: '';
                    $cust_sub   = 'Thành viên Bacera';
                }
            } else {
                $is_logged_in = true;
                $cust_name  = 'Thành viên Bacera';
                $cust_sub   = 'Tài khoản hoạt động';
            }
        }
    }
}

$avatar_initial = strtoupper(mb_substr($cust_name, 0, 2, 'UTF-8'));
$avatar_url = "https://ui-avatars.com/api/?name=" . rawurlencode($cust_name) . "&background=3d2f26&color=E8C5B0&bold=true&size=200";
$close_onclick = "document.getElementById('%s').classList.add('translate-y-full'); document.getElementById('%s').classList.add('opacity-0'); document.getElementById('%s-overlay').classList.add('hidden');";
?>

<style>
.hub-overlay { transition: opacity 0.25s ease; }
.hub-overlay.hidden { pointer-events: none; }
.hub-sheet { transition: transform 0.32s cubic-bezier(.32,1,.6,1), opacity 0.25s ease; }
.hub-menu-row { display: flex; align-items: center; gap: 12px; padding: 13px 16px; border-bottom: 1px solid rgba(141,106,84,.08); transition: background .15s; text-decoration: none; }
.hub-menu-row:last-child { border-bottom: none; }
.hub-menu-row:hover, .hub-menu-row:active { background: rgba(141,106,84,.05); }
.hub-menu-icon { width: 36px; height: 36px; border-radius: 50%; background: rgba(141,106,84,.1); display: flex; align-items: center; justify-content: center; color: var(--bacera-color-accent, #8d6a54); flex-shrink: 0; }
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<!-- ── OVERLAYS ─────────────────────────────── -->
<div id="mobile-account-hub-overlay"
     class="hub-overlay hidden fixed inset-0 z-[108] bg-black/50 backdrop-blur-[2px]"
     onclick="closeHub('mobile-account-hub')"></div>
<div id="mobile-explore-hub-overlay"
     class="hub-overlay hidden fixed inset-0 z-[108] bg-black/50 backdrop-blur-[2px]"
     onclick="closeHub('mobile-explore-hub')"></div>

<!-- ── ACCOUNT BOTTOM SHEET ──────────────────── -->
<div id="mobile-account-hub"
     class="hub-sheet fixed bottom-0 left-0 right-0 z-[110] bg-bgtheme rounded-t-[28px] shadow-2xl transform translate-y-full opacity-0 max-h-[88vh] flex flex-col">
    
    <!-- Handle bar -->
    <div class="flex justify-center pt-3 pb-1 shrink-0">
        <div class="w-10 h-[5px] rounded-full bg-accent/25"></div>
    </div>

    <!-- User Profile Banner -->
    <div class="px-5 pt-3 pb-5 shrink-0">
        <div class="flex items-center gap-4 bg-white rounded-2xl p-4 shadow-sm border border-accent/10">
            <div class="w-[52px] h-[52px] rounded-full overflow-hidden border-2 border-terracotta/30 shrink-0">
                <img src="<?php echo esc_url($avatar_url); ?>" class="w-full h-full object-cover" alt="Avatar" loading="lazy">
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-textmain text-[16px] font-bold leading-tight truncate"><?php echo esc_html($cust_name); ?></p>
                <p class="text-textmuted text-[12px] mt-0.5 truncate"><?php echo esc_html($cust_email ?: $cust_sub); ?></p>
            </div>
            <?php if (!$is_logged_in): ?>
            <a href="<?php echo esc_url($login_url); ?>"
               class="shrink-0 px-3.5 py-2 bg-textmain text-white text-[13px] font-semibold rounded-xl hover:bg-terracotta transition-colors no-underline">
               Đăng nhập
            </a>
            <?php else: ?>
            <button onclick="baceraLogout()" 
                    class="shrink-0 w-9 h-9 bg-red-50 text-red-400 rounded-xl flex items-center justify-center hover:bg-red-100 transition-colors focus:outline-none"
                    title="Đăng xuất">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scrollable content -->
    <div class="overflow-y-auto hide-scrollbar px-5 pb-[calc(12px+env(safe-area-inset-bottom,16px))] flex flex-col gap-5">
        
        <!-- Section 1: Tài khoản cá nhân -->
        <div>
            <p class="text-[10px] font-semibold text-textmuted uppercase tracking-[.1em] mb-2 pl-1">Tài khoản của tôi</p>
            <div class="bg-white rounded-2xl border border-accent/10 overflow-hidden shadow-sm">
                
                <a href="<?php echo esc_url($my_account_url); ?>" class="hub-menu-row">
                    <span class="hub-menu-icon">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </span>
                    <span class="text-textmain font-medium text-[15px] flex-1">Hồ sơ cá nhân</span>
                    <svg class="w-4 h-4 text-accent/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
                
                <a href="<?php echo esc_url($my_account_url . '#orders'); ?>" class="hub-menu-row">
                    <span class="hub-menu-icon">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </span>
                    <span class="text-textmain font-medium text-[15px] flex-1">Đơn hàng của tôi</span>
                    <span class="bg-terracotta text-white text-[10px] font-bold px-2 py-0.5 rounded-full leading-none">Mới</span>
                </a>

                <a href="<?php echo esc_url($my_account_url . '#workshops'); ?>" class="hub-menu-row">
                    <span class="hub-menu-icon">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </span>
                    <span class="text-textmain font-medium text-[15px] flex-1">Workshop đã đăng ký</span>
                    <svg class="w-4 h-4 text-accent/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>

        <!-- Section 2: Khám phá nhanh -->
        <div>
            <p class="text-[10px] font-semibold text-textmuted uppercase tracking-[.1em] mb-2 pl-1">Khám phá Bacera</p>
            <div class="bg-white rounded-2xl border border-accent/10 overflow-hidden shadow-sm">
                <a href="<?php echo esc_url(home_url('/about-us/')); ?>" class="hub-menu-row">
                    <span class="hub-menu-icon">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                    <span class="text-textmain font-medium text-[15px] flex-1">Về Bacera</span>
                    <svg class="w-4 h-4 text-accent/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
                <a href="<?php echo esc_url(home_url('/our-team/')); ?>" class="hub-menu-row">
                    <span class="hub-menu-icon">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </span>
                    <span class="text-textmain font-medium text-[15px] flex-1">Đội ngũ</span>
                    <svg class="w-4 h-4 text-accent/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
                <a href="<?php echo esc_url(home_url('/our-process/')); ?>" class="hub-menu-row">
                    <span class="hub-menu-icon">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </span>
                    <span class="text-textmain font-medium text-[15px] flex-1">Quy trình làm gốm</span>
                    <svg class="w-4 h-4 text-accent/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
                <a href="<?php echo esc_url(home_url('/our-video/')); ?>" class="hub-menu-row">
                    <span class="hub-menu-icon">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </span>
                    <span class="text-textmain font-medium text-[15px] flex-1">Video</span>
                    <svg class="w-4 h-4 text-accent/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
                <a href="<?php echo esc_url(home_url('/blog/')); ?>" class="hub-menu-row">
                    <span class="hub-menu-icon">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                        </svg>
                    </span>
                    <span class="text-textmain font-medium text-[15px] flex-1">Tin tức & Cảm hứng</span>
                    <svg class="w-4 h-4 text-accent/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>

        <!-- Section 3: Hỗ trợ -->
        <div class="mb-2">
            <p class="text-[10px] font-semibold text-textmuted uppercase tracking-[.1em] mb-2 pl-1">Cài đặt & Hỗ trợ</p>
            <div class="bg-white rounded-2xl border border-accent/10 overflow-hidden shadow-sm">
                <a href="<?php echo esc_url($contact_url); ?>" class="hub-menu-row">
                    <span class="hub-menu-icon">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </span>
                    <span class="text-textmain font-medium text-[15px] flex-1">Hỗ trợ khách hàng</span>
                    <svg class="w-4 h-4 text-accent/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
                <button onclick="mstSwitchLang('en','English')" class="hub-menu-row w-full text-left focus:outline-none">
                    <span class="hub-menu-icon">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                        </svg>
                    </span>
                    <span class="text-textmain font-medium text-[15px] flex-1">Switch to English</span>
                </button>
            </div>
        </div>

    </div>
</div>

<!-- ── EXPLORE BOTTOM SHEET ──────────────────── -->
<div id="mobile-explore-hub"
     class="hub-sheet fixed bottom-0 left-0 right-0 z-[110] bg-bgtheme rounded-t-[28px] shadow-2xl transform translate-y-full opacity-0 max-h-[88vh] flex flex-col">

    <!-- Handle bar -->
    <div class="flex justify-center pt-3 pb-1 shrink-0">
        <div class="w-10 h-[5px] rounded-full bg-accent/25"></div>
    </div>

    <!-- Header -->
    <div class="px-5 pt-2 pb-4 shrink-0 flex items-center justify-between">
        <h3 class="text-textmain text-[20px] font-bold font-serif">Khám phá Bacera</h3>
        <button onclick="closeHub('mobile-explore-hub')"
                class="w-8 h-8 rounded-full bg-accent/10 flex items-center justify-center text-textmuted hover:text-textmain transition-colors focus:outline-none">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Grid links -->
    <div class="overflow-y-auto hide-scrollbar px-5 pb-[calc(12px+env(safe-area-inset-bottom,16px))]">
        <div class="grid grid-cols-2 gap-3 mb-3">
            <?php
            $explore_items = [
                ['href' => home_url('/about-us/'), 'label' => 'Về Bacera', 'sub' => 'Câu chuyện xưởng gốm', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
                ['href' => home_url('/our-team/'), 'label' => 'Đội ngũ', 'sub' => 'Gặp gỡ nghệ nhân', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>'],
                ['href' => home_url('/our-process/'), 'label' => 'Quy trình', 'sub' => 'Từ đất sét đến sứ', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>'],
                ['href' => home_url('/our-video/'), 'label' => 'Video', 'sub' => 'Phía sau bánh xe gốm', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>'],
            ];
            foreach ($explore_items as $item): ?>
            <a href="<?php echo esc_url($item['href']); ?>"
               class="bg-white rounded-2xl p-4 border border-accent/10 shadow-sm flex flex-col gap-2.5 hover:border-terracotta/40 hover:shadow-md transition-all active:scale-[.98]">
                <div class="w-10 h-10 rounded-full bg-accent/10 flex items-center justify-center text-terracotta shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><?php echo $item['icon']; ?></svg>
                </div>
                <div>
                    <span class="block text-textmain font-semibold text-[15px] leading-snug"><?php echo esc_html($item['label']); ?></span>
                    <span class="block text-textmuted text-[11px] leading-tight mt-0.5"><?php echo esc_html($item['sub']); ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Blog CTA full-width -->
        <a href="<?php echo esc_url(home_url('/blog/')); ?>"
           class="flex items-center gap-4 bg-textmain text-white rounded-2xl p-4 w-full hover:bg-terracotta transition-colors active:scale-[.99] mb-4">
            <div class="w-12 h-12 rounded-full bg-white/10 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-white font-semibold text-[16px] leading-tight">Tin tức & Cảm hứng</p>
                <p class="text-white/65 text-[12px] leading-tight mt-0.5">Bài viết mới nhất từ studio</p>
            </div>
            <svg class="w-5 h-5 text-white/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <!-- Workshop CTA full-width -->
        <?php 
        $ws_archive = get_post_type_archive_link('workshop') ?: home_url('/workshop/');
        ?>
        <a href="<?php echo esc_url($ws_archive); ?>"
           class="flex items-center gap-4 bg-accent/10 text-textmain rounded-2xl p-4 w-full hover:bg-accent/20 transition-colors active:scale-[.99] mb-2">
            <div class="w-12 h-12 rounded-full bg-terracotta/15 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-terracotta" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-textmain font-semibold text-[16px] leading-tight">Workshop</p>
                <p class="text-textmuted text-[12px] leading-tight mt-0.5">Đăng ký lớp học gốm sứ</p>
            </div>
            <svg class="w-5 h-5 text-accent/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>

<script>
// Robust hub open/close helpers
function openHub(id) {
    const el = document.getElementById(id);
    const ov = document.getElementById(id + '-overlay');
    if (!el || !ov) return;
    el.classList.remove('translate-y-full', 'opacity-0');
    ov.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeHub(id) {
    const el = document.getElementById(id);
    const ov = document.getElementById(id + '-overlay');
    if (!el || !ov) return;
    el.classList.add('translate-y-full', 'opacity-0');
    ov.classList.add('hidden');
    document.body.style.overflow = '';
}
// Keyboard close
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeHub('mobile-account-hub');
        closeHub('mobile-explore-hub');
    }
});
</script>
