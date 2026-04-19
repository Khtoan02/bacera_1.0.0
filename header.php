<?php
$is_homepage = is_front_page() || is_page_template('templates/template-home-page.php');
$homepage_js = $is_homepage ? 'true' : 'false';

// ── Bacera Customer Session ──────────────────────────────────────────────────
global $wpdb;
$bacera_customer    = null;
$bacera_auth_cookie = $_COOKIE['bacera_customer_auth'] ?? '';
$bacera_cust_table  = $wpdb->prefix . 'bacera_customers';
if ( $bacera_auth_cookie && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $bacera_cust_table ) ) === $bacera_cust_table ) {
    $decoded = base64_decode( $bacera_auth_cookie, true );
    if ( $decoded && strpos( $decoded, '|' ) !== false ) {
        $customer_id = (int) explode( '|', $decoded )[0];
        if ( $customer_id > 0 ) {
            $bacera_customer = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, name, phone, email FROM `{$bacera_cust_table}` WHERE id = %d LIMIT 1",
                    $customer_id
                ),
                ARRAY_A
            );
        }
    }
}
$cust_name   = $bacera_customer ? ( $bacera_customer['name'] ?: $bacera_customer['phone'] ?: $bacera_customer['email'] ) : '';
$cust_email  = $bacera_customer['email']  ?? '';
$cust_phone  = $bacera_customer['phone']  ?? '';
$cust_sub    = $cust_email ?: $cust_phone;
$avatar_name = rawurlencode( $cust_name ?: 'Khach hang' );
$avatar_url  = "https://ui-avatars.com/api/?name={$avatar_name}&background=3d2f26&color=E67258&bold=true";

// Find auth page by template via direct DB query (safe before wp() runs)
$auth_page_id = $wpdb->get_var(
    "SELECT p.ID FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
     WHERE p.post_type = 'page'
       AND p.post_status = 'publish'
       AND pm.meta_key = '_wp_page_template'
       AND pm.meta_value IN ('templates/template-auth.php','template-auth.php')
     LIMIT 1"
);
$auth_page = $auth_page_id ? get_permalink( (int) $auth_page_id ) : home_url( '/auth/' );

// Find About Us page by template
$about_page_id = $wpdb->get_var(
    "SELECT p.ID FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
     WHERE p.post_type = 'page'
       AND p.post_status = 'publish'
       AND pm.meta_key = '_wp_page_template'
       AND pm.meta_value IN ('templates/template-about.php','template-about.php')
     LIMIT 1"
);
$about_page = $about_page_id ? get_permalink( (int) $about_page_id ) : home_url( '/about-us/' );

$bacera_hdr_shop_url     = class_exists( 'Bacera_Utils' ) ? Bacera_Utils::get_shop_page_url() : home_url( '/' );
$bacera_hdr_cart_url     = class_exists( 'Bacera_Utils' ) ? Bacera_Utils::get_cart_page_url() : home_url( '/cart/' );
$bacera_hdr_workshop_url = get_post_type_archive_link( 'workshop' ) ?: home_url( '/workshop/' );
$bacera_hdr_blog_url     = get_post_type_archive_link( 'post' ) ?: home_url( '/blog/' );
$bacera_hdr_contact_url  = home_url( '/contact/' );

// Find Our Process page
$proc_page_id = $wpdb->get_var(
    "SELECT p.ID FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
     WHERE p.post_type = 'page' AND p.post_status = 'publish'
       AND pm.meta_key = '_wp_page_template'
       AND pm.meta_value IN ('templates/template-our-process.php','template-our-process.php')
     LIMIT 1"
);
$proc_page = $proc_page_id ? get_permalink( (int) $proc_page_id ) : home_url( '/our-process/' );

// Find Sustainability page
$sustain_page_id = $wpdb->get_var(
    "SELECT p.ID FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
     WHERE p.post_type = 'page' AND p.post_status = 'publish'
       AND pm.meta_key = '_wp_page_template'
       AND pm.meta_value IN ('templates/template-sustainability.php','template-sustainability.php')
     LIMIT 1"
);
$sustain_page = $sustain_page_id ? get_permalink( (int) $sustain_page_id ) : home_url( '/sustainability/' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..800&family=Gowun+Batang:wght@400;700&family=Inter:opsz,wght@14..32,100..900&display=swap" rel="stylesheet">
    <script defer src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/js/alpine.min.js"></script>
    <style>
    /* ═══════════════════════════════════════════════════════
       HEADER — CSS-NATIVE SYSTEM
       Logic:
         #site-header.is-hero  → transparent (homepage, above hero)
         #site-header          → white (scrolled OR other pages)
         #site-header.is-hero:hover → white INSTANTLY (CSS, zero JS lag)
    ═══════════════════════════════════════════════════════ */

    /* ── Background layer ── */
    .hdr-bg {
        position: absolute; inset: 0; z-index: -1;
        background: #fff;
        box-shadow: 0 1px 12px rgba(0,0,0,.06);
        opacity: 1;
        transition: opacity .4s ease, box-shadow .4s ease;
    }
    #site-header.is-hero .hdr-bg {
        opacity: 0;
        box-shadow: none;
        transition: none; /* no transition when going TO hero state */
    }
    #site-header.is-hero:hover .hdr-bg {
        opacity: 1;
        box-shadow: 0 1px 12px rgba(0,0,0,.06);
        transition: none; /* INSTANT on hover — zero JS involved */
    }

    /* ── Logo ── */
    .hdr-logo { transition: filter .3s ease; }
    #site-header.is-hero .hdr-logo { filter: brightness(0) invert(1); transition: none; }
    #site-header.is-hero:hover .hdr-logo { filter: brightness(1); transition: none; }

    /* ── Nav links / text ── */
    .hdr-link {
        color: #57534e;   /* stone-600 */
        transition: color .15s ease;
        text-decoration: none;
    }
    #site-header.is-hero .hdr-link { color: rgba(231,229,228,.9); transition: none; }
    #site-header.is-hero:hover .hdr-link { color: #57534e; transition: none; }
    .hdr-link:hover { color: #d95f47 !important; } /* accent-500 */

    /* ── Icon buttons (search, cart) ── */
    .hdr-icon {
        color: #57534e;
        transition: color .15s ease;
    }
    #site-header.is-hero .hdr-icon { color: rgba(231,229,228,.9); transition: none; }
    #site-header.is-hero:hover .hdr-icon { color: #57534e; transition: none; }
    .hdr-icon:hover { color: #d95f47; }

    /* ── Avatar border ── */
    .hdr-avatar {
        border: 2px solid #e7e5e4; /* stone-200 */
    }
    #site-header.is-hero .hdr-avatar { border-color: rgba(255,255,255,.3); }
    #site-header.is-hero:hover .hdr-avatar { border-color: #e7e5e4; }

    /* ── Chevron SVGs (color inherits from parent .hdr-link) ── */
    .hdr-chevron { transition: transform .2s ease; }
    .hdr-nav-item.open .hdr-chevron { transform: rotate(180deg); }

    /* ═══ MEGA PANELS ═══
       CSS transition only — no x-transition, no Alpine delay.
       Shown/hidden by toggling .is-active class via JS.
    ════════════════════ */
    .mega-panel {
        position: absolute;
        top: 76px;
        left: 0; right: 0;
        background: #fff;
        box-shadow: 0 8px 40px rgba(0,0,0,.08);
        opacity: 0;
        pointer-events: none;
        transform: translateY(-6px);
        transition: opacity .18s ease, transform .18s ease;
        z-index: 60;
    }
    .mega-panel.lang-panel {
        left: auto;
        width: 11rem;
        right: 0;
        border-radius: 0 0 1rem 1rem;
    }
    .mega-panel.is-active {
        opacity: 1;
        pointer-events: auto;
        transform: translateY(0);
    }

    /* ── Lang dropdown (nested inside trigger, position:relative) ── */
    .lang-dropdown {
        position: absolute;
        top: calc(100% + 2px);
        right: 0;
        width: 11rem;
        background: #fff;
        border-radius: 0.75rem;
        box-shadow: 0 8px 32px rgba(0,0,0,.10);
        border: 1px solid #e7e5e4;
        opacity: 0;
        pointer-events: none;
        transform: translateY(-4px);
        transition: opacity .18s ease, transform .18s ease;
        z-index: 70;
        overflow: hidden;
    }
    #lang-trigger:hover .lang-dropdown {
        opacity: 1;
        pointer-events: auto;
        transform: translateY(0);
    }
    #lang-trigger:hover .hdr-chevron {
        transform: rotate(180deg);
    }
    /* ── Account dropdown ── */
    .acct-panel {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        width: 15rem;
        background: #fff;
        border: 1px solid #e7e5e4;
        border-radius: 1rem;
        box-shadow: 0 8px 32px rgba(0,0,0,.1);
        opacity: 0;
        pointer-events: none;
        transform: translateY(-4px);
        transition: opacity .18s ease, transform .18s ease;
        z-index: 80;
    }
    #acct-trigger:hover .acct-panel {
        opacity: 1;
        pointer-events: auto;
        transform: translateY(0);
    }
    #acct-trigger:hover #acct-chevron {
        transform: rotate(180deg);
    }

    /* ── Hover Bridge ──
       Invisible safe area so hover isn't lost when moving mouse
       from trigger to panel across the 10px gap. */
    #lang-trigger::after,
    #acct-trigger::after {
        content: '';
        position: absolute;
        top: 100%; left: -10px; right: -10px;
        height: 15px;
    }

    /* ── Search overlay ── */
    .search-overlay {
        position: fixed;
        top: 0; left: 0; right: 0;
        background: rgba(255,255,255,0.98);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        z-index: 200;
        padding: 0 1.5rem;
        height: 76px;
        display: flex;
        align-items: center;
        gap: 1rem;
        opacity: 0;
        transform: translateY(-100%);
        transition: opacity .25s ease, transform .25s ease;
        pointer-events: none;
        box-shadow: 0 4px 24px rgba(0,0,0,.08);
    }
    .search-overlay.is-active {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }
    .search-overlay input {
        flex: 1;
        height: 48px;
        border: 1.5px solid #e7e5e4;
        border-radius: 0.75rem;
        padding: 0 1rem;
        font-size: 15px;
        font-family: inherit;
        color: #292524;
        outline: none;
        background: #fafaf9;
        transition: border-color .15s ease;
    }
    .search-overlay input:focus { border-color: #d95f47; }
    .search-overlay input::placeholder { color: #a8a29e; }
    </style>

    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-neutral-100 font-sans antialiased overflow-x-hidden'); ?>>
<?php wp_body_open(); ?>

<div id="page" class="flex flex-col min-h-screen">

<header id="site-header" class="fixed top-0 left-0 w-full z-50">

    <div class="hdr-bg"></div>

    <div class="hidden lg:grid h-[76px] w-full" style="grid-template-columns: 1fr minmax(0, 1232px) 1fr;">

        <a href="<?php echo esc_url(home_url('/')); ?>"
           class="flex items-center justify-center pr-6 xl:pr-8">
            <img src="<?php echo esc_url( bacera_get_brand_logo_url() ); ?>"
                 class="hdr-logo h-9 w-auto object-contain"
                 alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
        </a>

        <div class="flex items-stretch justify-between">

            <nav class="flex items-stretch" id="hdr-nav">

                <?php
                $nav_items = [
                    ['key'=>'shop',     'label'=>'Shop',     'has_sub'=>true,  'url'=>$bacera_hdr_shop_url],
                    ['key'=>'workshop', 'label'=>'Workshop', 'has_sub'=>true,  'url'=>$bacera_hdr_workshop_url],
                    ['key'=>'about',    'label'=>'About us', 'has_sub'=>true,  'url'=>$about_page],
                    ['key'=>'blog',     'label'=>'Blog',     'has_sub'=>false, 'url'=>$bacera_hdr_blog_url],
                    ['key'=>'contact',  'label'=>'Contact',  'has_sub'=>false, 'url'=>$bacera_hdr_contact_url],
                ];
                foreach ($nav_items as $item):
                ?>
                <div class="hdr-nav-item flex items-center h-full"
                     <?php if ($item['has_sub']): ?>data-menu="<?php echo $item['key']; ?>"<?php endif; ?>>

                    <?php if (!$item['has_sub']): ?>
                    <a href="<?php echo esc_url($item['url']); ?>"
                       class="hdr-link flex items-center h-full px-4 text-[15px] font-medium font-sans">
                        <?php echo esc_html($item['label']); ?>
                    </a>
                    <?php else: ?>
                    <a href="<?php echo esc_url( $item['url'] ); ?>"
                       class="hdr-link flex items-center gap-1 px-4 h-full text-[15px] font-medium font-sans cursor-pointer select-none no-underline">
                        <span><?php echo esc_html($item['label']); ?></span>
                        <svg class="hdr-chevron w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

            </nav>

            <div class="hdr-nav-item flex items-center h-full relative" id="lang-trigger">
                <div class="hdr-link flex items-center gap-1.5 px-4 h-full text-[14px] font-medium font-sans cursor-pointer select-none">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m-6.716 2.582A11.953 11.953 0 0112 10.5c2.998 0 5.74-1.1 7.843-2.918"/>
                    </svg>
                    <span id="mst-current-lang">English</span>
                    <svg class="hdr-chevron w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>

                <div class="lang-dropdown" id="lang-panel">
                    <button onclick="mstSwitchLang('en','English')"  class="w-full flex items-center gap-2.5 px-4 py-3 text-[#d95f47] text-[13px] font-medium font-sans hover:bg-neutral-100 transition-colors">🇺🇸 <span>English</span></button>
                    <div class="h-px bg-neutral-100 mx-3"></div>
                    <button onclick="mstSwitchLang('vi','Tiếng Việt')" class="w-full flex items-center gap-2.5 px-4 py-3 text-primary-600 text-[13px] font-sans hover:bg-neutral-100 hover:text-[#d95f47] transition-colors">🇻🇳 <span>Tiếng Việt</span></button>
                    <div class="h-px bg-neutral-100 mx-3"></div>
                    <button onclick="mstSwitchLang('fr','Français')"   class="w-full flex items-center gap-2.5 px-4 py-3 text-primary-600 text-[13px] font-sans hover:bg-neutral-100 hover:text-[#d95f47] transition-colors">🇫🇷 <span>Français</span></button>
                </div>
            </div>

        </div>

        <div class="flex items-center gap-5 pl-6 xl:pl-8 pr-6 xl:pr-10">

            <button id="search-btn" class="hdr-icon w-9 h-9 flex items-center justify-center hover:scale-110 transition-transform focus:outline-none" title="Tìm kiếm">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>

            <a href="<?php echo esc_url( $bacera_hdr_cart_url ); ?>" class="relative hdr-icon w-9 h-9 flex items-center justify-center hover:scale-110 transition-transform focus:outline-none no-underline" title="<?php esc_attr_e( 'Giỏ hàng', 'bacera' ); ?>">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <span class="absolute -top-1 -right-1 bg-[#d95f47] min-w-[17px] h-[17px] px-1 rounded-full flex items-center justify-center text-white text-[10px] font-bold">1</span>
            </a>

            <?php if ( $bacera_customer ): ?>
            <div class="relative" id="acct-trigger">
                <button id="acct-btn" class="flex items-center gap-2 hover:opacity-80 transition-opacity focus:outline-none">
                    <div class="hdr-avatar w-8 h-8 rounded-full overflow-hidden">
                        <img src="<?php echo esc_url($avatar_url); ?>"
                             class="w-full h-full object-cover" alt="<?php echo esc_attr($cust_name); ?>">
                    </div>
                    <span class="hidden xl:block hdr-icon text-[13px] font-medium font-sans leading-none truncate max-w-[120px]"><?php echo esc_html($cust_name); ?></span>
                    <svg class="hdr-chevron hdr-icon w-3 h-3 hidden xl:block" id="acct-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div class="acct-panel" id="acct-panel">
                    <div class="px-4 py-3 border-b border-neutral-200">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full overflow-hidden shrink-0">
                                <img src="<?php echo esc_url($avatar_url); ?>" class="w-full h-full" alt="">
                            </div>
                            <div class="min-w-0">
                                <p class="text-primary-800 text-[14px] font-semibold truncate"><?php echo esc_html($cust_name); ?></p>
                                <?php if ($cust_sub): ?>
                                <p class="text-primary-400 text-[12px] truncate"><?php echo esc_html($cust_sub); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php
                    $my_account_url = home_url('/tai-khoan-cua-toi/');
                    $acct_menu = [
                        ['icon'=>'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',  'label'=>'Tài khoản của tôi',   'url'=>$my_account_url],
                        ['icon'=>'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label'=>'Điểm tích lũy',       'url'=> $my_account_url . '#loyalty'],
                        ['icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'label'=>'Đơn hàng của tôi',  'url'=>$my_account_url . '#orders'],
                        ['icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'label'=>'Workshop đã đăng ký', 'url'=>$my_account_url . '#workshops'],
                    ];
                    foreach ($acct_menu as $am): ?>
                    <a href="<?php echo esc_url($am['url']); ?>"
                       class="flex items-center gap-3 px-4 py-2.5 text-primary-600 text-[14px] font-medium font-sans hover:bg-neutral-100 hover:text-[#d95f47] transition-colors group/ai">
                        <div class="w-7 h-7 rounded-lg bg-neutral-100 flex items-center justify-center shrink-0 group-hover/ai:bg-orange-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $am['icon']; ?>"/>
                            </svg>
                        </div>
                        <?php echo esc_html($am['label']); ?>
                    </a>
                    <?php endforeach; ?>
                    <div class="h-px bg-neutral-100 mx-4 my-1.5"></div>
                    <a href="<?php echo esc_url( add_query_arg( 'bacera_logout', '1', home_url( '/' ) ) ); ?>"
                       class="w-full flex items-center gap-3 px-4 py-2.5 text-primary-600 text-[14px] font-medium font-sans hover:bg-red-50 hover:text-red-500 transition-colors mb-1 group/lo">
                        <div class="w-7 h-7 rounded-lg bg-neutral-100 flex items-center justify-center shrink-0 group-hover/lo:bg-red-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </div>
                        Đăng xuất
                    </a>
                </div>
            </div>
            <?php else: ?>
            <a id="bacera-login-btn" href="<?php echo esc_url($auth_page); ?>"
               class="flex items-center gap-2 px-4 py-2 rounded-xl border border-neutral-300 hdr-link text-[13px] font-medium font-sans hover:border-[#d95f47] hover:text-[#d95f47] transition-all">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                Đăng nhập
            </a>
            <?php endif; ?>

        </div>

    </div>

    <div class="mega-panel" id="panel-shop">
        <?php
        // ── Fetch real product categories ─────────────────────────────────────
        $mega_parent_cats = get_terms([
            'taxonomy'   => 'bcm_product_cat',
            'hide_empty' => false,
            'parent'     => 0,
            'orderby'    => 'name',
            'number'     => 8,
        ]);

        // Default SVG paths as fallback icons (cycles through)
        $fallback_icons = [
            'M4 6h16M4 10h16M4 14h16M4 18h16',
            'M3 3h18l-3 18H6L3 3z',
            'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
            'M17 8h1a4 4 0 010 8h-1M3 8h14v9a4 4 0 01-4 4H7a4 4 0 01-4-4V8z',
            'M3 10c0 5.523 4.477 10 10 10s10-4.477 10-10H3z',
            'M4 7h16M4 12h8m-8 5h16',
            'M9 17V7m0 0a3 3 0 106 0v10',
            'M12 2a10 10 0 100 20 10 10 0 000-20zm0 5a5 5 0 110 10A5 5 0 0112 7z',
        ];

        ?>
        <div class="max-w-[1232px] mx-auto px-6 py-8 flex gap-8">
            <div class="w-52 shrink-0 flex flex-col justify-between py-1">
                <div>
                    <h3 class="text-primary-800 text-[17px] font-semibold font-sans mb-2">Mua hàng theo công năng</h3>
                    <p class="text-primary-600 text-[13px] font-sans leading-relaxed">Khám phá sản phẩm theo danh mục để mua sắm nhanh chóng hơn.</p>
                </div>
                <a href="<?php echo esc_url( $bacera_hdr_shop_url ); ?>"
                   class="mt-5 inline-flex items-center justify-center px-5 py-2.5 bg-[#d95f47] hover:bg-[#c0533e] text-white text-[13px] font-medium rounded-xl transition-colors">
                    Xem tất cả
                </a>
            </div>
            <div class="w-px bg-neutral-200 self-stretch shrink-0"></div>

            <?php if (!is_wp_error($mega_parent_cats) && $mega_parent_cats): ?>
            <div class="flex-1 grid grid-cols-4 gap-3">
                <?php foreach ($mega_parent_cats as $i => $cat):
                    $cat_url   = get_term_link($cat);
                    $cat_url   = is_wp_error($cat_url) ? '#' : $cat_url;
                    $img_id    = function_exists('bcm_get_cat_image_id') ? bcm_get_cat_image_id($cat->term_id) : 0;
                    $img_url   = $img_id ? wp_get_attachment_image_url($img_id, 'thumbnail') : '';
                    $icon_path = $fallback_icons[$i % count($fallback_icons)];
                ?>

                <a href="<?php echo esc_url($cat_url); ?>"
                   class="group/sc flex flex-col items-center justify-center gap-2.5 py-3 px-2 rounded-xl border border-neutral-300 bg-white hover:border-[#d95f47] hover:bg-[#fef8f7] transition-all duration-200 h-[100px]">

                    <div class="w-12 h-12 rounded-xl bg-neutral-100 group-hover/sc:bg-[#fff0ec] border border-neutral-300 group-hover/sc:border-[#f5c8be] flex items-center justify-center overflow-hidden shrink-0 transition-all duration-200 shadow-[0_1px_3px_rgba(0,0,0,.06)]">
                        <?php if ($img_url): ?>
                        <img src="<?php echo esc_url($img_url); ?>"
                             class="w-9 h-9 object-contain transition-transform duration-300 group-hover/sc:scale-110"
                             alt="<?php echo esc_attr($cat->name); ?>" loading="lazy">
                        <?php else: ?>
                        <svg class="w-[22px] h-[22px] text-primary-600 group-hover/sc:text-[#d95f47] transition-colors"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $icon_path; ?>"/>
                        </svg>
                        <?php endif; ?>
                    </div>

                    <div class="text-center leading-none">
                        <span class="block text-primary-700 text-[11.5px] font-semibold font-sans group-hover/sc:text-[#d95f47] transition-colors leading-tight line-clamp-2">
                            <?php echo esc_html($cat->name); ?>
                        </span>
                        <?php if ($cat->count > 0): ?>
                        <span class="block text-primary-400 text-[10px] font-sans mt-0.5"><?php echo $cat->count; ?> sản phẩm</span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>

            <?php else: ?>

            <div class="flex-1 flex items-center justify-center">
                <div class="text-center text-primary-400">
                    <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <p class="text-[13px] font-sans">Chưa có danh mục sản phẩm.<br>
                    <a href="<?php echo admin_url('admin.php?page=bacera-product-cats'); ?>" class="text-[#d95f47] underline text-[12px]">Thêm danh mục</a></p>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <div class="mega-panel" id="panel-workshop">
        <div class="max-w-[1232px] mx-auto px-6 py-8 flex gap-8">
            <div class="w-52 shrink-0 flex flex-col justify-between py-1">
                <div>
                    <h3 class="text-primary-800 text-[17px] font-semibold font-sans mb-2">Khám phá workshop</h3>
                    <p class="text-primary-600 text-[13px] font-sans leading-relaxed">Trải nghiệm nghệ thuật làm gốm thủ công đầy cảm hứng cùng chúng tôi.</p>
                </div>
                <a href="<?php echo esc_url( $bacera_hdr_workshop_url ); ?>" class="mt-5 inline-flex items-center justify-center px-5 py-2.5 bg-[#d95f47] hover:bg-[#c0533e] text-white text-[13px] font-medium rounded-xl transition-colors no-underline">Xem tất cả lịch</a>
            </div>
            <div class="w-px bg-neutral-200 self-stretch shrink-0"></div>
            <div class="flex-1 grid grid-cols-2 gap-4">
                <?php
                // Fetch real published workshops from DB
                $mega_wks = get_posts([
                    'post_type'      => 'workshop',
                    'post_status'    => 'publish',
                    'posts_per_page' => 4,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                ]);
                foreach ($mega_wks as $mw_post):
                    $mw_url   = get_permalink($mw_post);
                    $mw_price = get_post_meta($mw_post->ID, '_price', true) ?: 'Liên hệ';
                    $mw_price_fmt = is_numeric(str_replace([',','.'], '', $mw_price))
                        ? number_format((float)preg_replace('/[^0-9.]/', '', $mw_price), 0, ',', '.') . 'đ'
                        : $mw_price;
                    $mw_thumb = get_post_meta($mw_post->ID, '_thumbnail_url', true)
                                ?: 'https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=120';
                    $mw_tagline = get_post_meta($mw_post->ID, '_tagline', true) ?: wp_trim_words($mw_post->post_excerpt ?: strip_tags($mw_post->post_content), 10, '…');
                ?>
                <a href="<?= esc_url($mw_url) ?>" class="group/mw flex items-center gap-4 p-3 rounded-xl hover:bg-neutral-100 transition-colors">
                    <div class="w-[88px] h-[88px] overflow-hidden rounded-xl shrink-0 bg-neutral-200">
                        <img src="<?= esc_url($mw_thumb) ?>"
                             class="w-full h-full object-cover group-hover/mw:scale-105 transition-transform duration-500" alt="<?= esc_attr($mw_post->post_title) ?>">
                    </div>
                    <div class="flex flex-col min-w-0">
                        <h4 class="text-primary-800 text-[14px] font-semibold font-sans mb-1 group-hover/mw:text-[#d95f47] transition-colors leading-snug"><?= esc_html($mw_post->post_title) ?></h4>
                        <p class="text-primary-600 text-[12px] font-sans leading-snug mb-2 line-clamp-2"><?= esc_html($mw_tagline) ?></p>
                        <p class="text-primary-800 text-[13px] font-medium font-sans"><?= esc_html($mw_price_fmt) ?></p>
                    </div>
                </a>
                <?php endforeach;
                if (empty($mega_wks)): ?>
                <div class="col-span-2 flex items-center justify-center text-primary-400 text-[13px] font-sans">Chưa có workshop nào. <a href="<?= admin_url('post-new.php?post_type=workshop') ?>" class="ml-1 text-[#d95f47] underline">Thêm ngay</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="mega-panel" id="panel-about">
        <div class="max-w-[1232px] mx-auto px-6 py-6 flex gap-8">
            <div class="w-52 shrink-0 flex flex-col justify-between py-1">
                <div>
                    <h3 class="text-primary-800 text-[17px] font-semibold font-sans mb-2">Về chúng tôi</h3>
                    <p class="text-primary-600 text-[13px] font-sans leading-relaxed">Xưởng gốm Bacera — nơi nghệ thuật thủ công gặp gỡ tâm hồn.</p>
                </div>
                <a href="<?php echo esc_url($about_page); ?>" class="mt-5 inline-flex items-center justify-center px-5 py-2.5 bg-[#d95f47] hover:bg-[#c0533e] text-white text-[13px] font-medium rounded-xl transition-colors">Đọc thêm</a>
            </div>
            <div class="w-px bg-neutral-200 self-stretch shrink-0"></div>
            <nav class="flex-1 grid grid-cols-3 gap-x-8 gap-y-1 content-start py-1">
                <?php
                $about_links = [
                    ['label'=>'About us',      'desc'=>'Our story & values',     'url'=>$about_page],
                    ['label'=>'Our team',      'desc'=>'Meet the craftspeople',  'url'=>home_url('/our-team/')],
                    ['label'=>'Our video',     'desc'=>'Behind the wheel',       'url'=>home_url('/our-video/')],
                    ['label'=>'Our process',   'desc'=>'From clay to ceramic',   'url'=>$proc_page],
                    ['label'=>'Sustainability','desc'=>'Earth-conscious craft',  'url'=>$sustain_page],
                ];
                foreach ($about_links as $al): ?>
                <a href="<?php echo esc_url($al['url']); ?>"
                   class="flex flex-col gap-0.5 px-3 py-3 rounded-xl hover:bg-neutral-100 transition-colors group/al">
                    <span class="text-primary-800 text-[14px] font-medium font-sans group-hover/al:text-[#d95f47] transition-colors"><?php echo esc_html($al['label']); ?></span>
                    <span class="text-primary-400 text-[12px] font-sans"><?php echo esc_html($al['desc']); ?></span>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <div class="flex lg:hidden items-center justify-between h-[64px] px-4" id="mobile-hdr">
        <a href="<?php echo esc_url(home_url('/')); ?>">
            <img src="<?php echo esc_url( bacera_get_brand_logo_url() ); ?>"
                 class="hdr-logo h-8 w-auto object-contain"
                 alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
        </a>
        <div class="flex items-center gap-3">
            <a href="<?php echo esc_url( $bacera_hdr_cart_url ); ?>" class="relative hdr-icon w-9 h-9 flex items-center justify-center no-underline" title="<?php esc_attr_e( 'Giỏ hàng', 'bacera' ); ?>">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                <span class="absolute -top-1 -right-1 bg-[#d95f47] w-4 h-4 rounded-full flex items-center justify-center text-white text-[9px] font-bold">1</span>
            </a>
            <button id="mobile-toggle" class="hdr-icon w-9 h-9 flex items-center justify-center focus:outline-none">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path id="icon-open"  stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    <path id="icon-close" stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" style="display:none"/>
                </svg>
            </button>
        </div>
        <div id="mobile-menu"
             class="hidden absolute top-[64px] left-0 right-0 bg-white shadow-xl z-[60] py-3">
            <nav class="flex flex-col">
                <?php
                $mobile_links = [
                    'Shop'     => $bacera_hdr_shop_url,
                    'Workshop' => $bacera_hdr_workshop_url,
                    'About us' => $about_page,
                    'Blog'     => $bacera_hdr_blog_url,
                    'Contact'  => $bacera_hdr_contact_url,
                ];
                foreach ($mobile_links as $ml => $ml_url): ?>
                <a href="<?php echo esc_url( $ml_url ); ?>" class="px-5 py-3 text-primary-700 text-[15px] font-medium border-b border-neutral-200 last:border-0 hover:text-[#d95f47] transition-colors no-underline"><?php echo esc_html( $ml ); ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="flex items-center gap-4 px-5 mt-3 pt-3 border-t border-neutral-200">
                <button onclick="mstSwitchLang('vi','Tiếng Việt')" class="text-primary-600 text-sm">🇻🇳 Tiếng Việt</button>
                <span class="text-primary-300">·</span>
                <button onclick="mstSwitchLang('en','English')" class="text-[#d95f47] text-sm font-semibold">🇺🇸 English</button>
            </div>
        </div>
    </div>

</header>

<div class="search-overlay" id="search-overlay" role="search" aria-label="Tìm kiếm">

    <a href="<?php echo esc_url(home_url('/')); ?>" class="shrink-0 flex items-center pr-4">
        <img src="<?php echo esc_url( bacera_get_brand_logo_url() ); ?>" class="h-8 w-auto object-contain" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
    </a>

    <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="flex flex-1 items-center gap-3">
        <input type="search" id="search-input" name="s"
               placeholder="Tìm kiếm sản phẩm, bài viết..."
               value="<?php echo get_search_query(); ?>"
               autocomplete="off">
        <button type="submit"
                class="shrink-0 flex items-center justify-center w-12 h-12 rounded-xl bg-[#d95f47] hover:bg-[#c0533e] text-white transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </button>
    </form>

    <button id="search-close"
            class="shrink-0 w-10 h-10 flex items-center justify-center rounded-xl hover:bg-neutral-100 text-primary-600 hover:text-primary-800 transition-colors"
            aria-label="Đóng tìm kiếm">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>

<script>
/* ═══════════════════════════════════════════════════════
   HEADER JS — pure vanilla, no reactive framework
   • Scroll → toggle .is-hero on <header> (CSS handles visuals)
   • Nav hover → toggle .is-active on mega panels (CSS transitions)
   • Account click → toggle .is-active on acct-panel
═══════════════════════════════════════════════════════ */
(function() {
    const header   = document.getElementById('site-header');
    const isHome   = <?php echo $homepage_js; ?>;
    let threshold  = window.innerHeight * 0.85;

    // ── Scroll: toggle is-hero ──────────────────────────────
    function updateHero() {
        const heroEl = document.getElementById('hero');
        if (heroEl) threshold = heroEl.offsetHeight * 0.85;
        if (isHome) {
            header.classList.toggle('is-hero', window.scrollY <= threshold);
        }
    }
    updateHero();
    window.addEventListener('scroll', updateHero, { passive: true });

    // ── Panels: hover on nav items ──────────────────────────
    let leaveTimer = null;

    function openPanel(key) {
        clearTimeout(leaveTimer);
        document.querySelectorAll('.mega-panel').forEach(p => p.classList.remove('is-active'));
        document.querySelectorAll('.hdr-nav-item').forEach(i => i.classList.remove('open'));
        if (key) {
            const panel = document.getElementById('panel-' + key);
            if (panel) panel.classList.add('is-active');
            const trigger = document.querySelector('[data-menu="' + key + '"]');
            if (trigger) trigger.classList.add('open');
        }
    }

    function scheduleClose() {
        leaveTimer = setTimeout(() => openPanel(null), 80);
    }

    document.querySelectorAll('.hdr-nav-item[data-menu]').forEach(item => {
        item.addEventListener('mouseenter', () => openPanel(item.dataset.menu));
        item.addEventListener('mouseleave', scheduleClose);
    });

    document.querySelectorAll('.mega-panel').forEach(panel => {
        panel.addEventListener('mouseenter', () => {
            clearTimeout(leaveTimer);
            // Re-mark the trigger as open
            const id = panel.id.replace('panel-', '');
            const trigger = document.querySelector('[data-menu="' + id + '"]');
            if (trigger) trigger.classList.add('open');
        });
        panel.addEventListener('mouseleave', scheduleClose);
    });

    // ── Mobile toggle ───────────────────────────────────────
    const mToggle = document.getElementById('mobile-toggle');
    const mMenu   = document.getElementById('mobile-menu');
    const iOpen   = document.getElementById('icon-open');
    const iClose  = document.getElementById('icon-close');
    if (mToggle) {
        mToggle.addEventListener('click', () => {
            const vis = mMenu.classList.toggle('hidden');
            iOpen.style.display  = !vis ? 'none' : '';
            iClose.style.display = !vis ? '' : 'none';
        });
    }

    // ── Search overlay (click toggle) ───────────────────────
    const searchBtn     = document.getElementById('search-btn');
    const searchOverlay = document.getElementById('search-overlay');
    const searchInput   = document.getElementById('search-input');
    const searchClose   = document.getElementById('search-close');

    function openSearch() {
        searchOverlay.classList.add('is-active');
        setTimeout(() => searchInput && searchInput.focus(), 250);
        document.body.style.overflow = 'hidden';
    }
    function closeSearch() {
        searchOverlay.classList.remove('is-active');
        document.body.style.overflow = '';
    }

    if (searchBtn)   searchBtn.addEventListener('click', openSearch);
    if (searchClose) searchClose.addEventListener('click', closeSearch);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSearch(); });
    if (searchOverlay) {
        searchOverlay.addEventListener('click', e => { if (e.target === searchOverlay) closeSearch(); });
    }

    // ── Language switch ─────────────────────────────────────
    window.mstSwitchLang = function(lang, label) {
        document.getElementById('mst-current-lang').textContent = label;
        if (typeof window.mstTranslatePage === 'function') window.mstTranslatePage(lang);
    };
})();

// Global logout — defined outside IIFE so onclick="baceraLogout()" always works
window.baceraLogout = function() {
    // Clear cookie on all common path variants
    const expires = 'expires=Thu, 01 Jan 1970 00:00:00 UTC';
    const domain  = location.hostname;
    document.cookie = 'bacera_customer_auth=; ' + expires + '; path=/;';
    document.cookie = 'bacera_customer_auth=; ' + expires + '; path=/; domain=' + domain + ';';
    document.cookie = 'bacera_customer_auth=; ' + expires + '; path=/; domain=.' + domain + ';';
    window.location.href = '<?php echo esc_js(home_url('/')); ?>';
};
</script>

<?php
