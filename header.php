<?php
/**
 * Header
 * Architecture: CSS-native hover (zero-latency), Alpine only for scroll state.
 */
$is_homepage = is_front_page() || is_page_template('templates/template-home-page.php');
$homepage_js = $is_homepage ? 'true' : 'false';
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
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>

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
<body <?php body_class('bg-stone-50 font-sans antialiased overflow-x-hidden'); ?>>
<?php wp_body_open(); ?>

<div id="page" class="flex flex-col min-h-screen">

<header id="site-header" class="fixed top-0 left-0 w-full z-50">

    <!-- BG layer (CSS controlled) -->
    <div class="hdr-bg"></div>

    <!-- ════════ DESKTOP (lg+) ════════ -->
    <div class="hidden lg:grid h-[76px] w-full" style="grid-template-columns: 1fr minmax(0, 1232px) 1fr;">

        <!-- ① LOGO -->
        <a href="<?php echo esc_url(home_url('/')); ?>"
           class="flex items-center justify-center pr-6 xl:pr-8">
            <img src="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/2026/03/Logo.png'); ?>"
                 class="hdr-logo h-9 w-auto object-contain"
                 alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
        </a>

        <!-- ② CENTER NAV CONTAINER -->
        <div class="flex items-stretch justify-between">

            <!-- NAV MENU -->
            <nav class="flex items-stretch" id="hdr-nav">

                <?php
                $nav_items = [
                    ['key'=>'shop',     'label'=>'Shop',     'has_sub'=>true],
                    ['key'=>'workshop', 'label'=>'Workshop', 'has_sub'=>true],
                    ['key'=>'about',    'label'=>'About us', 'has_sub'=>true],
                    ['key'=>'blog',     'label'=>'Blog',     'has_sub'=>false, 'url'=>'#'],
                    ['key'=>'contact',  'label'=>'Contact',  'has_sub'=>false, 'url'=>'#'],
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
                    <div class="hdr-link flex items-center gap-1 px-4 h-full text-[15px] font-medium font-sans cursor-pointer select-none">
                        <span><?php echo esc_html($item['label']); ?></span>
                        <svg class="hdr-chevron w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

            </nav><!-- /nav -->

            <!-- LANGUAGE (position:relative so dropdown anchors to trigger) -->
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
                <!-- Lang dropdown: nested inside trigger → anchors correctly -->
                <div class="lang-dropdown" id="lang-panel">
                    <button onclick="mstSwitchLang('en','English')"  class="w-full flex items-center gap-2.5 px-4 py-3 text-[#d95f47] text-[13px] font-medium font-sans hover:bg-stone-50 transition-colors">🇺🇸 <span>English</span></button>
                    <div class="h-px bg-stone-100 mx-3"></div>
                    <button onclick="mstSwitchLang('vi','Tiếng Việt')" class="w-full flex items-center gap-2.5 px-4 py-3 text-stone-600 text-[13px] font-sans hover:bg-stone-50 hover:text-[#d95f47] transition-colors">🇻🇳 <span>Tiếng Việt</span></button>
                    <div class="h-px bg-stone-100 mx-3"></div>
                    <button onclick="mstSwitchLang('fr','Français')"   class="w-full flex items-center gap-2.5 px-4 py-3 text-stone-600 text-[13px] font-sans hover:bg-stone-50 hover:text-[#d95f47] transition-colors">🇫🇷 <span>Français</span></button>
                </div>
            </div>

        </div><!-- /center -->

        <!-- ③ ACTIONS -->
        <div class="flex items-center gap-5 pl-6 xl:pl-8 pr-6 xl:pr-10">

            <!-- Search -->
            <button id="search-btn" class="hdr-icon w-9 h-9 flex items-center justify-center hover:scale-110 transition-transform focus:outline-none" title="Tìm kiếm">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>

            <!-- Cart -->
            <div class="relative">
                <button class="hdr-icon w-9 h-9 flex items-center justify-center hover:scale-110 transition-transform focus:outline-none">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </button>
                <div class="absolute -top-1 -right-1 bg-[#d95f47] min-w-[17px] h-[17px] px-1 rounded-full flex items-center justify-center text-white text-[10px] font-bold">1</div>
            </div>

            <!-- Account -->
            <div class="relative" id="acct-trigger">
                <button id="acct-btn" class="flex items-center gap-2 hover:opacity-80 transition-opacity focus:outline-none">
                    <div class="hdr-avatar w-8 h-8 rounded-full overflow-hidden">
                        <img src="https://ui-avatars.com/api/?name=Tran+Mai&background=3d2f26&color=E67258&bold=true"
                             class="w-full h-full object-cover" alt="Avatar">
                    </div>
                    <span class="hidden xl:block hdr-icon text-[13px] font-medium font-sans leading-none">Trần Mai</span>
                    <svg class="hdr-chevron hdr-icon w-3 h-3 hidden xl:block" id="acct-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- Account panel (JS toggled) -->
                <div class="acct-panel" id="acct-panel">
                    <div class="px-4 py-3 border-b border-stone-100">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full overflow-hidden">
                                <img src="https://ui-avatars.com/api/?name=Tran+Mai&background=3d2f26&color=E67258&bold=true" class="w-full h-full" alt="">
                            </div>
                            <div>
                                <p class="text-stone-800 text-[14px] font-semibold truncate">Trần Mai</p>
                                <p class="text-stone-400 text-[12px] truncate">tran.mai@email.com</p>
                            </div>
                        </div>
                    </div>
                    <?php
                    $acct_menu = [
                        ['icon'=>'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',  'label'=>'Tài khoản của tôi',  'url'=>'#'],
                        ['icon'=>'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label'=>'Điểm tích lũy',      'url'=>'#'],
                        ['icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'label'=>'Đơn hàng của tôi', 'url'=>'#'],
                        ['icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'label'=>'Workshop đã đăng ký','url'=>'#'],
                    ];
                    foreach ($acct_menu as $am): ?>
                    <a href="<?php echo esc_url($am['url']); ?>"
                       class="flex items-center gap-3 px-4 py-2.5 text-stone-600 text-[14px] font-medium font-sans hover:bg-stone-50 hover:text-[#d95f47] transition-colors group/ai">
                        <div class="w-7 h-7 rounded-lg bg-stone-100 flex items-center justify-center shrink-0 group-hover/ai:bg-orange-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $am['icon']; ?>"/>
                            </svg>
                        </div>
                        <?php echo esc_html($am['label']); ?>
                    </a>
                    <?php endforeach; ?>
                    <div class="h-px bg-stone-100 mx-4 my-1.5"></div>
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>"
                       class="flex items-center gap-3 px-4 py-2.5 text-stone-500 text-[14px] font-medium font-sans hover:bg-red-50 hover:text-red-500 transition-colors mb-1 group/lo">
                        <div class="w-7 h-7 rounded-lg bg-stone-100 flex items-center justify-center shrink-0 group-hover/lo:bg-red-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </div>
                        Đăng xuất
                    </a>
                </div>
            </div><!-- /account -->

        </div><!-- /actions -->

    </div><!-- /desktop grid -->

    <!-- ════════ MEGA PANELS (CSS transition, JS toggled) ════════ -->

    <!-- SHOP -->
    <div class="mega-panel" id="panel-shop">
        <div class="max-w-[1232px] mx-auto px-6 py-8 flex gap-8">
            <div class="w-52 shrink-0 flex flex-col justify-between py-1">
                <div>
                    <h3 class="text-stone-800 text-[17px] font-semibold font-sans mb-2">Mua hàng theo công năng</h3>
                    <p class="text-stone-500 text-[13px] font-sans leading-relaxed">Khám phá sản phẩm theo danh mục để mua sắm nhanh chóng hơn.</p>
                </div>
                <a href="#" class="mt-5 inline-flex items-center justify-center px-5 py-2.5 bg-[#d95f47] hover:bg-[#c0533e] text-white text-[13px] font-medium rounded-xl transition-colors">Xem tất cả</a>
            </div>
            <div class="w-px bg-stone-200 self-stretch shrink-0"></div>
            <div class="flex-1 grid grid-cols-4 gap-3">
                <?php
                $shop_cats = [
                    ['name'=>'Kitchen',   'path'=>'M4 6h16M4 10h16M4 14h16M4 18h16'],
                    ['name'=>'Plates',    'path'=>'M12 2a10 10 0 100 20 10 10 0 000-20zm0 5a5 5 0 110 10A5 5 0 0112 7z'],
                    ['name'=>'Vases',     'path'=>'M3 3h18l-3 18H6L3 3z'],
                    ['name'=>'Storage',   'path'=>'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4'],
                    ['name'=>'Teapots',   'path'=>'M17 8h1a4 4 0 010 8h-1M3 8h14v9a4 4 0 01-4 4H7a4 4 0 01-4-4V8z'],
                    ['name'=>'Drinkware', 'path'=>'M9 17V7m0 0a3 3 0 106 0v10'],
                    ['name'=>'Bowls',     'path'=>'M3 10c0 5.523 4.477 10 10 10s10-4.477 10-10H3z'],
                    ['name'=>'Serveware', 'path'=>'M4 7h16M4 12h8m-8 5h16'],
                ];
                foreach ($shop_cats as $sc): ?>
                <a href="#" class="group/sc flex flex-col items-center justify-center py-4 px-3 h-[96px] rounded-xl border border-stone-200 hover:border-[#d95f47] hover:bg-neutral-50 transition-all">
                    <div class="w-9 h-9 mb-2 bg-stone-100 rounded-lg flex items-center justify-center text-stone-600 group-hover/sc:bg-[#d95f47] group-hover/sc:text-white transition-all">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $sc['path']; ?>"/></svg>
                    </div>
                    <span class="text-stone-600 text-[12px] font-medium font-sans group-hover/sc:text-[#d95f47] transition-colors text-center leading-tight"><?php echo $sc['name']; ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- WORKSHOP -->
    <div class="mega-panel" id="panel-workshop">
        <div class="max-w-[1232px] mx-auto px-6 py-8 flex gap-8">
            <div class="w-52 shrink-0 flex flex-col justify-between py-1">
                <div>
                    <h3 class="text-stone-800 text-[17px] font-semibold font-sans mb-2">Khám phá workshop</h3>
                    <p class="text-stone-500 text-[13px] font-sans leading-relaxed">Trải nghiệm nghệ thuật làm gốm thủ công đầy cảm hứng cùng chúng tôi.</p>
                </div>
                <a href="#" class="mt-5 inline-flex items-center justify-center px-5 py-2.5 bg-[#d95f47] hover:bg-[#c0533e] text-white text-[13px] font-medium rounded-xl transition-colors">Xem tất cả lịch</a>
            </div>
            <div class="w-px bg-stone-200 self-stretch shrink-0"></div>
            <div class="flex-1 grid grid-cols-2 gap-4">
                <?php
                $mega_workshops = [
                    ['title'=>'Pottery Wheel Throwing','desc'=>'A peaceful, hands-on journey for beginners and curious minds.','price'=>'From 950,000 VND','detail'=>'Day:13/16 – Noon: 1/16'],
                    ['title'=>'Hand-building Pottery', 'desc'=>'Shape, carve, and create your own ceramic piece by hand.',     'price'=>'From 950,000 VND','detail'=>'Day:13/16 – Noon: 1/16'],
                    ['title'=>'Hanoi School Class',    'desc'=>'Special courses for students wanting deeper techniques.',       'price'=>'Contact us',       'detail'=>'Book Your School Visit'],
                    ['title'=>'Team-building Pottery', 'desc'=>'A unique bonding experience for your team to create together.','price'=>'Contact us',       'detail'=>'Organise a Group Event'],
                ];
                foreach ($mega_workshops as $mw): ?>
                <a href="#" class="group/mw flex items-center gap-4 p-3 rounded-xl hover:bg-stone-50 transition-colors">
                    <div class="w-[88px] h-[88px] overflow-hidden rounded-xl shrink-0 bg-stone-200">
                        <img src="https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=120"
                             class="w-full h-full object-cover group-hover/mw:scale-105 transition-transform duration-500" alt="">
                    </div>
                    <div class="flex flex-col min-w-0">
                        <h4 class="text-stone-800 text-[14px] font-semibold font-sans mb-1 group-hover/mw:text-[#d95f47] transition-colors leading-snug"><?php echo $mw['title']; ?></h4>
                        <p class="text-stone-500 text-[12px] font-sans leading-snug mb-2 line-clamp-2"><?php echo $mw['desc']; ?></p>
                        <p class="text-stone-800 text-[13px] font-medium font-sans"><?php echo $mw['price']; ?></p>
                        <p class="text-stone-400 text-[11px] font-sans mt-0.5"><?php echo $mw['detail']; ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ABOUT -->
    <div class="mega-panel" id="panel-about">
        <div class="max-w-[1232px] mx-auto px-6 py-6 flex gap-8">
            <div class="w-52 shrink-0 flex flex-col justify-between py-1">
                <div>
                    <h3 class="text-stone-800 text-[17px] font-semibold font-sans mb-2">Về chúng tôi</h3>
                    <p class="text-stone-500 text-[13px] font-sans leading-relaxed">Xưởng gốm Bacera — nơi nghệ thuật thủ công gặp gỡ tâm hồn.</p>
                </div>
                <a href="#" class="mt-5 inline-flex items-center justify-center px-5 py-2.5 bg-[#d95f47] hover:bg-[#c0533e] text-white text-[13px] font-medium rounded-xl transition-colors">Đọc thêm</a>
            </div>
            <div class="w-px bg-stone-200 self-stretch shrink-0"></div>
            <nav class="flex-1 grid grid-cols-3 gap-x-8 gap-y-1 content-start py-1">
                <?php
                $about_links = [
                    ['label'=>'About us',      'desc'=>'Our story & values',    'url'=>'#'],
                    ['label'=>'Our team',      'desc'=>'Meet the craftspeople',  'url'=>'#'],
                    ['label'=>'Our video',     'desc'=>'Behind the wheel',       'url'=>'#'],
                    ['label'=>'Our process',   'desc'=>'From clay to ceramic',   'url'=>'#'],
                    ['label'=>'Sustainability','desc'=>'Earth-conscious craft',   'url'=>'#'],
                ];
                foreach ($about_links as $al): ?>
                <a href="<?php echo esc_url($al['url']); ?>"
                   class="flex flex-col gap-0.5 px-3 py-3 rounded-xl hover:bg-stone-50 transition-colors group/al">
                    <span class="text-stone-800 text-[14px] font-medium font-sans group-hover/al:text-[#d95f47] transition-colors"><?php echo esc_html($al['label']); ?></span>
                    <span class="text-stone-400 text-[12px] font-sans"><?php echo esc_html($al['desc']); ?></span>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>


    <!-- ════════ MOBILE ════════ -->
    <div class="flex lg:hidden items-center justify-between h-[64px] px-4" id="mobile-hdr">
        <a href="<?php echo esc_url(home_url('/')); ?>">
            <img src="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/2026/03/Logo.png'); ?>"
                 class="hdr-logo h-8 w-auto object-contain"
                 alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
        </a>
        <div class="flex items-center gap-3">
            <div class="relative">
                <button class="hdr-icon w-9 h-9 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </button>
                <div class="absolute -top-1 -right-1 bg-[#d95f47] w-4 h-4 rounded-full flex items-center justify-center text-white text-[9px] font-bold">1</div>
            </div>
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
                <?php foreach (['Shop','Workshop','About us','Blog','Contact'] as $ml): ?>
                <a href="#" class="px-5 py-3 text-stone-700 text-[15px] font-medium border-b border-stone-100 last:border-0 hover:text-[#d95f47] transition-colors"><?php echo $ml; ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="flex items-center gap-4 px-5 mt-3 pt-3 border-t border-stone-100">
                <button onclick="mstSwitchLang('vi','Tiếng Việt')" class="text-stone-600 text-sm">🇻🇳 Tiếng Việt</button>
                <span class="text-stone-300">·</span>
                <button onclick="mstSwitchLang('en','English')" class="text-[#d95f47] text-sm font-semibold">🇺🇸 English</button>
            </div>
        </div>
    </div>

</header>

<!-- ════════ SEARCH OVERLAY ════════ -->
<div class="search-overlay" id="search-overlay" role="search" aria-label="Tìm kiếm">
    <!-- Logo inside overlay for context -->
    <a href="<?php echo esc_url(home_url('/')); ?>" class="shrink-0 flex items-center pr-4">
        <img src="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/2026/03/Logo.png'); ?>" class="h-8 w-auto object-contain" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
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
            class="shrink-0 w-10 h-10 flex items-center justify-center rounded-xl hover:bg-stone-100 text-stone-500 hover:text-stone-800 transition-colors"
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
</script>

<!-- Spacer for non-homepage only -->
<?php if (!$is_homepage): ?>
<div class="hidden lg:block h-[76px]"></div>
<div class="block lg:hidden h-[64px]"></div>
<?php endif; ?>

<!-- Main Content Area -->
<?php
