<?php
/**
 * Header
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
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-stone-50 font-sans antialiased overflow-x-hidden'); ?>>
<?php wp_body_open(); ?>

<div id="page" class="flex flex-col min-h-screen">


<header
    id="site-header"
    x-data="{
        scrolled: false,
        isMenuOpen: false,
        activeMenu: null,
        accountOpen: false,
        isHomepage: <?php echo $homepage_js; ?>,
        init() {
            const heroEl = document.getElementById('hero');
            const threshold = heroEl ? heroEl.offsetHeight * 0.85 : window.innerHeight * 0.85;
            const onScroll = () => { this.scrolled = window.scrollY > threshold; };
            onScroll();
            window.addEventListener('scroll', onScroll, { passive: true });
        },
        get isTransparent() {
            return this.isHomepage && !this.scrolled && !this.isMenuOpen && !this.accountOpen;
        }
    }"
    @keydown.escape.window="activeMenu = null; isMenuOpen = false; accountOpen = false"
    class="fixed top-0 left-0 w-full z-50"
>
    <!--
        Background layer:
        - On HOVER (scrolled=false): transition:none → instant white (syncs with mega menu)
        - On SCROLL (scrolled=true):  transition smooth 400ms → nice fade in
    -->
    <div class="absolute inset-0 -z-10 bg-white"
         :class="isTransparent ? 'opacity-0 shadow-none' : 'opacity-100 shadow-sm'"
         :style="scrolled ? 'transition: opacity 0.4s ease, box-shadow 0.4s ease' : 'transition: none'"
    ></div>
    <div class="hidden lg:grid h-[76px] w-full" style="grid-template-columns: 1fr minmax(0, 1232px) 1fr;">

        
        <a href="<?php echo esc_url(home_url('/')); ?>"
           class="flex items-center justify-center pr-6 xl:pr-8 transition-colors">
            <img src="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/2026/03/Logo.png'); ?>"
                 class="h-9 w-auto object-contain transition-all duration-300"
                 :class="isTransparent ? 'brightness-0 invert' : 'brightness-100'"
                 alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
        </a>

        <!-- ② CENTER — the 1232px container itself (middle grid column) -->
        <div class="flex items-stretch justify-between min-w-0">

                <!-- NAV MENU — left side of container -->
                <nav class="flex items-stretch">

                    <?php
                    // Menu definitions: [key, label, type(mega|dropdown|link)]
                    $nav_items = [
                        ['key'=>'shop',     'label'=>'Shop',     'type'=>'mega'],
                        ['key'=>'workshop', 'label'=>'Workshop', 'type'=>'mega'],
                        ['key'=>'about',    'label'=>'About us', 'type'=>'dropdown'],
                        ['key'=>'blog',     'label'=>'Blog',     'type'=>'link', 'url'=>'#'],
                        ['key'=>'contact',  'label'=>'Contact',  'type'=>'link', 'url'=>'#'],
                    ];
                    foreach ($nav_items as $item):
                        $has_sub = in_array($item['type'], ['mega','dropdown']);
                    ?>
                    <div class="flex items-center h-full <?php echo $has_sub ? 'cursor-pointer' : ''; ?>"
                         <?php if ($has_sub): ?>
                         @mouseenter="isMenuOpen = true; activeMenu = '<?php echo $item['key']; ?>'"
                         @mouseleave="isMenuOpen = false; activeMenu = null"
                         <?php endif; ?>>

                        <?php if ($item['type'] === 'link'): ?>
                        <a href="<?php echo esc_url($item['url']); ?>"
                           class="flex items-center h-full px-4 text-[15px] font-medium font-sans transition-colors"
                           :class="isTransparent ? 'text-stone-200 hover:text-white' : 'text-stone-600 hover:text-accent-500'">
                            <?php echo esc_html($item['label']); ?>
                        </a>
                        <?php else: ?>
                        <div class="flex items-center gap-1 px-4 h-full text-[15px] font-medium font-sans transition-colors select-none"
                             :class="[
                                 isTransparent ? 'text-stone-200 hover:text-white' : 'text-stone-600 hover:text-accent-500',
                                 activeMenu === '<?php echo $item['key']; ?>' ? (isTransparent ? '!text-white' : '!text-accent-500') : ''
                             ]">
                            <span><?php echo esc_html($item['label']); ?></span>
                            <svg class="w-3.5 h-3.5 transition-transform duration-200"
                                 :class="activeMenu === '<?php echo $item['key']; ?>' ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>

                </nav><!-- /nav -->

                <!-- LANGUAGE SWITCHER — right side of container -->
                <div class="flex items-center h-full group relative cursor-pointer"
                     @mouseenter="isMenuOpen = true; activeMenu = 'lang'"
                     @mouseleave="isMenuOpen = false; activeMenu = null">
                    <div class="flex items-center gap-1.5 px-4 h-full text-[14px] font-medium font-sans transition-colors select-none"
                         :class="isTransparent ? 'text-stone-200 hover:text-white' : 'text-stone-600 hover:text-accent-500'">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m-6.716 2.582A11.953 11.953 0 0112 10.5c2.998 0 5.74-1.1 7.843-2.918"/>
                        </svg>
                        <span id="mst-current-lang">English</span>
                        <svg class="w-3 h-3 transition-transform duration-200"
                             :class="activeMenu === 'lang' ? 'rotate-180' : ''"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>

            <!-- /Language -->
        </div><!-- /② center container -->

        <!-- ③ ACTIONS — right column, aligned to LEFT (hugs container) -->
        <div class="flex items-center gap-5 pl-6 xl:pl-8 pr-6 xl:pr-10">

            <!-- Search -->
            <button class="w-9 h-9 flex items-center justify-center rounded-lg transition-all hover:scale-110 focus:outline-none"
                    :class="isTransparent ? 'text-stone-200 hover:text-white' : 'text-stone-600 hover:text-accent-500'">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>

            <!-- Cart -->
            <div class="relative">
                <button class="w-9 h-9 flex items-center justify-center rounded-lg transition-all hover:scale-110 focus:outline-none"
                        :class="isTransparent ? 'text-stone-200 hover:text-white' : 'text-stone-600 hover:text-accent-500'">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </button>
                <div class="absolute -top-1 -right-1 bg-accent-500 min-w-[17px] h-[17px] px-1 rounded-full flex items-center justify-center text-white text-[10px] font-bold shadow-sm">1</div>
            </div>

            <!-- Account -->
            <div class="relative" @click.away="accountOpen = false">
                <button @click="accountOpen = !accountOpen"
                        class="flex items-center gap-2 hover:opacity-80 transition-all focus:outline-none">
                    <div class="w-8 h-8 rounded-full overflow-hidden border-2 transition-colors"
                         :class="isTransparent ? 'border-white/30' : 'border-stone-200'">
                        <img src="https://ui-avatars.com/api/?name=Tran+Mai&background=3d2f26&color=E67258&bold=true"
                             class="w-full h-full object-cover" alt="Avatar">
                    </div>
                    <span class="hidden xl:block text-[13px] font-medium font-sans transition-colors leading-none"
                          :class="isTransparent ? 'text-stone-200' : 'text-stone-700'">Trần Mai</span>
                    <svg class="w-3 h-3 transition-transform duration-200 hidden xl:block"
                         :class="[accountOpen ? 'rotate-180' : '', isTransparent ? 'text-stone-300' : 'text-stone-400']"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- Account dropdown panel -->
                <div x-show="accountOpen"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 translate-y-1"
                     class="absolute right-0 top-full mt-3 w-60 bg-white rounded-2xl shadow-xl border border-stone-200 py-2 z-[80]"
                     style="display:none">
                    <!-- User info -->
                    <div class="px-4 py-3 border-b border-stone-100 mb-1">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full overflow-hidden bg-primary-100 shrink-0">
                                <img src="https://ui-avatars.com/api/?name=Tran+Mai&background=3d2f26&color=E67258&bold=true" class="w-full h-full" alt="">
                            </div>
                            <div class="min-w-0">
                                <p class="text-stone-800 text-[14px] font-semibold truncate">Trần Mai</p>
                                <p class="text-stone-400 text-[12px] truncate">tran.mai@email.com</p>
                            </div>
                        </div>
                    </div>
                    <?php
                    $acct_menu = [
                        ['icon'=>'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',  'label'=>'Tài khoản của tôi',      'url'=>'#'],
                        ['icon'=>'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label'=>'Điểm tích lũy',             'url'=>'#'],
                        ['icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'label'=>'Đơn hàng của tôi',   'url'=>'#'],
                        ['icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',   'label'=>'Workshop đã đăng ký',     'url'=>'#'],
                    ];
                    foreach ($acct_menu as $am): ?>
                    <a href="<?php echo esc_url($am['url']); ?>"
                       class="flex items-center gap-3 px-4 py-2.5 text-stone-600 text-[14px] font-medium font-sans hover:bg-stone-50 hover:text-accent-500 transition-colors group/ai">
                        <div class="w-7 h-7 rounded-lg bg-stone-100 flex items-center justify-center shrink-0 group-hover/ai:bg-accent-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $am['icon']; ?>"/></svg>
                        </div>
                        <?php echo esc_html($am['label']); ?>
                    </a>
                    <?php endforeach; ?>
                    <div class="h-px bg-stone-100 mx-4 my-1.5"></div>
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>"
                       class="flex items-center gap-3 px-4 py-2.5 text-stone-500 text-[14px] font-medium font-sans hover:bg-red-50 hover:text-red-500 transition-colors group/lo">
                        <div class="w-7 h-7 rounded-lg bg-stone-100 flex items-center justify-center shrink-0 group-hover/lo:bg-red-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </div>
                        Đăng xuất
                    </a>
                </div>
            </div><!-- /account -->

        </div><!-- /actions -->

    </div><!-- /desktop grid 3-col -->

    <!-- ══════════════════════════════════════════
         MEGA MENU PANELS
         Full-width panels, content aligned to
         max-w-[1232px] container (same as page)
    ══════════════════════════════════════════ -->

    <!-- SHOP PANEL -->
    <div x-show="activeMenu === 'shop'"
         @mouseenter="isMenuOpen = true; activeMenu = 'shop'"
         @mouseleave="isMenuOpen = false; activeMenu = null"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="absolute top-full left-0 right-0 bg-white shadow-xl z-[60]"
         style="display:none">
        <div class="max-w-[1232px] mx-auto px-6 py-8 flex gap-8">
            <!-- Promo left -->
            <div class="w-52 shrink-0 flex flex-col justify-between py-1">
                <div>
                    <h3 class="text-stone-800 text-[17px] font-semibold font-sans mb-2">Mua hàng theo công năng</h3>
                    <p class="text-stone-500 text-[13px] font-normal font-sans leading-relaxed">Khám phá sản phẩm theo danh mục để mua sắm nhanh chóng hơn.</p>
                </div>
                <a href="#" class="mt-5 inline-flex items-center justify-center px-5 py-2.5 bg-accent-500 hover:bg-accent-600 text-white text-[13px] font-medium rounded-xl transition-colors">Xem tất cả</a>
            </div>
            <div class="w-px bg-stone-200 self-stretch shrink-0"></div>
            <!-- Category grid -->
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
                <a href="#" class="group/sc flex flex-col items-center justify-center py-4 px-3 h-[96px] rounded-xl border border-stone-200 hover:border-accent-400 hover:bg-neutral-100 transition-all">
                    <div class="w-9 h-9 mb-2 bg-primary-100 rounded-lg flex items-center justify-center text-primary-600 group-hover/sc:bg-accent-500 group-hover/sc:text-white transition-all">
                        <svg class="w-4.5 h-4.5 w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $sc['path']; ?>"/></svg>
                    </div>
                    <span class="text-stone-600 text-[12px] font-medium font-sans group-hover/sc:text-accent-500 transition-colors text-center leading-tight"><?php echo $sc['name']; ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- WORKSHOP PANEL -->
    <div x-show="activeMenu === 'workshop'"
         @mouseenter="isMenuOpen = true; activeMenu = 'workshop'"
         @mouseleave="isMenuOpen = false; activeMenu = null"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="absolute top-full left-0 right-0 bg-white shadow-xl z-[60]"
         style="display:none">
        <div class="max-w-[1232px] mx-auto px-6 py-8 flex gap-8">
            <!-- Promo left -->
            <div class="w-52 shrink-0 flex flex-col justify-between py-1">
                <div>
                    <h3 class="text-stone-800 text-[17px] font-semibold font-sans mb-2">Khám phá workshop</h3>
                    <p class="text-stone-500 text-[13px] font-normal font-sans leading-relaxed">Trải nghiệm nghệ thuật làm gốm thủ công đầy cảm hứng cùng chúng tôi.</p>
                </div>
                <a href="#" class="mt-5 inline-flex items-center justify-center px-5 py-2.5 bg-accent-500 hover:bg-accent-600 text-white text-[13px] font-medium rounded-xl transition-colors">Xem tất cả lịch</a>
            </div>
            <div class="w-px bg-stone-200 self-stretch shrink-0"></div>
            <!-- Workshop cards: 2x2 grid -->
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
                    <div class="w-[88px] h-[88px] overflow-hidden rounded-xl shrink-0 bg-primary-200">
                        <img src="https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=120"
                             class="w-full h-full object-cover group-hover/mw:scale-105 transition-transform duration-500" alt="">
                    </div>
                    <div class="flex flex-col min-w-0">
                        <h4 class="text-stone-800 text-[14px] font-semibold font-sans mb-1 group-hover/mw:text-accent-500 transition-colors leading-snug"><?php echo $mw['title']; ?></h4>
                        <p class="text-stone-500 text-[12px] font-sans leading-snug mb-2 line-clamp-2 opacity-80"><?php echo $mw['desc']; ?></p>
                        <p class="text-stone-800 text-[13px] font-medium font-sans"><?php echo $mw['price']; ?></p>
                        <p class="text-stone-400 text-[11px] font-sans mt-0.5"><?php echo $mw['detail']; ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ABOUT PANEL -->
    <div x-show="activeMenu === 'about'"
         @mouseenter="isMenuOpen = true; activeMenu = 'about'"
         @mouseleave="isMenuOpen = false; activeMenu = null"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="absolute top-full left-0 right-0 bg-white shadow-xl z-[60]"
         style="display:none">
        <div class="max-w-[1232px] mx-auto px-6 py-6 flex gap-8">
            <div class="w-52 shrink-0 flex flex-col justify-between py-1">
                <div>
                    <h3 class="text-stone-800 text-[17px] font-semibold font-sans mb-2">Về chúng tôi</h3>
                    <p class="text-stone-500 text-[13px] font-normal font-sans leading-relaxed">Xưởng gốm Bacera — nơi nghệ thuật thủ công gặp gỡ tâm hồn.</p>
                </div>
                <a href="#" class="mt-5 inline-flex items-center justify-center px-5 py-2.5 bg-accent-500 hover:bg-accent-600 text-white text-[13px] font-medium rounded-xl transition-colors">Đọc thêm</a>
            </div>
            <div class="w-px bg-stone-200 self-stretch shrink-0"></div>
            <nav class="flex-1 grid grid-cols-3 gap-x-8 gap-y-1 content-start py-1">
                <?php
                $about_links = [
                    ['label'=>'About us',      'desc'=>'Our story & values',   'url'=>'#'],
                    ['label'=>'Our team',      'desc'=>'Meet the craftspeople', 'url'=>'#'],
                    ['label'=>'Our video',     'desc'=>'Behind the wheel',      'url'=>'#'],
                    ['label'=>'Our process',   'desc'=>'From clay to ceramic',  'url'=>'#'],
                    ['label'=>'Sustainability','desc'=>'Earth-conscious craft',  'url'=>'#'],
                ];
                foreach ($about_links as $al): ?>
                <a href="<?php echo esc_url($al['url']); ?>"
                   class="flex flex-col gap-0.5 px-3 py-3 rounded-xl hover:bg-stone-50 hover:text-accent-500 transition-colors group/al">
                    <span class="text-stone-800 text-[14px] font-medium font-sans group-hover/al:text-accent-500 transition-colors"><?php echo esc_html($al['label']); ?></span>
                    <span class="text-stone-400 text-[12px] font-sans"><?php echo esc_html($al['desc']); ?></span>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <!-- LANGUAGE PANEL -->
    <div x-show="activeMenu === 'lang'"
         @mouseenter="isMenuOpen = true; activeMenu = 'lang'"
         @mouseleave="isMenuOpen = false; activeMenu = null"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="absolute top-full right-0 w-48 bg-white rounded-b-2xl shadow-xl z-[60] py-1.5"
         style="display:none">
        <button onclick="mstSwitchLang('en','English')"  class="w-full flex items-center gap-2.5 px-4 py-2.5 text-accent-500  text-[13px] font-medium font-sans hover:bg-stone-50 transition-colors">🇺🇸 <span>English</span></button>
        <div class="h-px bg-stone-100 mx-3"></div>
        <button onclick="mstSwitchLang('vi','Tiếng Việt')" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-stone-600 text-[13px] font-sans hover:bg-stone-50 hover:text-accent-500 transition-colors">🇻🇳 <span>Tiếng Việt</span></button>
        <div class="h-px bg-stone-100 mx-3"></div>
        <button onclick="mstSwitchLang('fr','Français')" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-stone-600 text-[13px] font-sans hover:bg-stone-50 hover:text-accent-500 transition-colors">🇫🇷 <span>Français</span></button>
    </div>

    <!-- ══════════════════════════════════════════
         MOBILE HEADER
    ══════════════════════════════════════════ -->
    <div class="flex lg:hidden items-center justify-between h-[64px] px-4"
         x-data="{ mobileOpen: false }">
        <a href="<?php echo esc_url(home_url('/')); ?>">
            <img src="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/2026/03/Logo.png'); ?>"
                 class="h-8 w-auto object-contain transition-all duration-300"
                 :class="isTransparent ? 'brightness-0 invert' : 'brightness-100'"
                 alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
        </a>
        <div class="flex items-center gap-3">
            <div class="relative">
                <button class="w-9 h-9 flex items-center justify-center" :class="isTransparent ? 'text-stone-200' : 'text-stone-700'">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </button>
                <div class="absolute -top-1 -right-1 bg-accent-500 w-4 h-4 rounded-full flex items-center justify-center text-white text-[9px] font-bold">1</div>
            </div>
            <button @click="mobileOpen = !mobileOpen" class="w-9 h-9 flex items-center justify-center focus:outline-none"
                    :class="isTransparent ? 'text-stone-200' : 'text-stone-700'">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    <path x-show="mobileOpen"  stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <!-- Mobile menu -->
        <div x-show="mobileOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="absolute top-[64px] left-0 right-0 bg-white border-b border-stone-200 shadow-xl z-[60] py-3"
             style="display:none">
            <nav class="flex flex-col">
                <?php foreach (['Shop','Workshop','About us','Blog','Contact'] as $ml): ?>
                <a href="#" class="px-5 py-3 text-stone-700 text-[15px] font-medium border-b border-stone-100 last:border-0 hover:text-accent-500 transition-colors"><?php echo $ml; ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="flex items-center gap-4 px-5 mt-3 pt-3 border-t border-stone-100">
                <button onclick="mstSwitchLang('vi','Tiếng Việt')" class="text-stone-600 text-sm">🇻🇳 Tiếng Việt</button>
                <span class="text-stone-300">·</span>
                <button onclick="mstSwitchLang('en','English')" class="text-accent-500 text-sm font-semibold">🇺🇸 English</button>
            </div>
        </div>
    </div>

</header>

<!-- Language switch JS -->
<script>
function mstSwitchLang(lang, label) {
    document.getElementById('mst-current-lang').textContent = label;
    if (typeof window.mstTranslatePage === 'function') window.mstTranslatePage(lang);
}
</script>

<!-- Spacer for non-homepage (homepage overlaps with hero) -->
<?php if (!$is_homepage): ?>
<div class="hidden lg:block h-[76px]"></div>
<div class="block lg:hidden h-[64px]"></div>
<?php endif; ?>

<!-- Main Content Area -->
<?php
