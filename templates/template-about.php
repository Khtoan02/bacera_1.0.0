<?php
/**
 * Template Name: Về Chúng Tôi
 * Description: About Us — mobile-first, fixed spacing
 */

get_header();

// ── Assets ───────────────────────────────────────────────────────────────────
add_action( 'wp_footer', function() {
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">';
    echo '<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>';
    echo '<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>';
}, 5 );

// ── Reviews data ─────────────────────────────────────────────────────────────
global $wpdb;
$reviews_db = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}bacera_workshop_reviews ORDER BY created_at DESC LIMIT 6",
    ARRAY_A
) ?: [];

$fallback_reviews = [
    ['quote' => "My girlfriend and I signed up for fun. We didn't expect the wheel to make us so quiet, so present. She shaped a bowl. I shaped... something odd. But it felt like we made something together.", 'name' => 'Tran Tuan & Mai Linh', 'course' => 'Hand-building class', 'img' => 'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?auto=format&fit=crop&q=80&w=700'],
    ['quote' => "I bought a tea set from Bacera and it transformed my afternoon tea into a little ceremony. These pieces don't just serve food — they serve feelings.", 'name' => 'Hoang Anh', 'course' => 'Product Purchase', 'img' => 'https://images.unsplash.com/photo-1610701596007-11502861dcfa?auto=format&fit=crop&q=80&w=700'],
    ['quote' => "Every little glaze mark, every tiny curve in the bowl tells a story. I can feel the craftsman's hands in each piece. It's like holding a memory instead of just an object.", 'name' => 'Ngoc Han', 'course' => 'Pottery Workshop', 'img' => 'https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=700'],
    ['quote' => "We booked a team-building workshop and it was unlike anything we'd tried before. Our team came away quieter, closer, and with beautiful pieces we'd made ourselves.", 'name' => 'Minh Tuan', 'course' => 'Team Building Session', 'img' => 'https://images.unsplash.com/photo-1578749556568-bc2c40e68b61?auto=format&fit=crop&q=80&w=700'],
];
$reviews = !empty($reviews_db) ? $reviews_db : $fallback_reviews;

// ── Images ────────────────────────────────────────────────────────────────────
$hero_img     = get_post_meta(get_the_ID(), '_about_hero_image',   true) ?: 'https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=900';
$service1_img = get_post_meta(get_the_ID(), '_about_service1_img', true) ?: 'https://images.unsplash.com/photo-1578749556568-bc2c40e68b61?auto=format&fit=crop&q=80&w=800';
$service2_img = get_post_meta(get_the_ID(), '_about_service2_img', true) ?: 'https://images.unsplash.com/photo-1610701596087-0b1a039735d9?auto=format&fit=crop&q=80&w=800';

// ── URLs ──────────────────────────────────────────────────────────────────────
$workshop_url = get_post_type_archive_link('workshop') ?: home_url('/workshop/');
$shop_url     = class_exists('WooCommerce') ? get_permalink(wc_get_page_id('shop')) : home_url('/shop-demo/');
?>

<style>
/* ═══════════════════════════════════════════════════════
   ABOUT PAGE — scoped styles
   Mobile header = 64px  |  Desktop header = 76px
   IMPORTANT: footer.php already closes </main>, so this
   template must NOT include a closing </main> tag.
═══════════════════════════════════════════════════════ */

/* ── Correct header offset per breakpoint ── */
#au-wrap {
    background: #f8f7f3; /* bg-neutral-100 */
}
@media (min-width: 1024px) {
    #au-wrap { padding-top: 76px; } /* desktop: header height 76px */
}

/* ── Section spacing: tighter on mobile ── */
.au-sec     { padding-top: 3rem; padding-bottom: 3rem; }
.au-sec-lg  { padding-top: 3rem; padding-bottom: 3rem; }
@media (min-width: 768px)  { .au-sec    { padding-top: 4rem;  padding-bottom: 4rem;  } }
@media (min-width: 768px)  { .au-sec-lg { padding-top: 5rem;  padding-bottom: 5rem;  } }
@media (min-width: 1024px) { .au-sec-lg { padding-top: 7rem;  padding-bottom: 7rem;  } }

/* ── Container ── */
.au-wrap { max-width: 1232px; margin-left: auto; margin-right: auto; padding-left: 1.25rem; padding-right: 1.25rem; }
@media (min-width: 1280px) { .au-wrap { padding-left: 0; padding-right: 0; } }

/* ── Arch image ── */
.au-arch {
    border-radius: 999px 999px 1rem 1rem;
    overflow: hidden;
    position: relative;
}
.au-arch-ring {
    position: absolute;
    inset: -10px;
    border: 1px solid #e7e5e4;
    border-radius: 999px 999px 1.25rem 1.25rem;
    pointer-events: none;
}

/* ── Drop cap ── */
.au-dropcap::first-letter {
    font-family: 'Gowun Batang', serif;
    font-size: 2.75rem;
    line-height: 0.8;
    float: left;
    margin-right: 0.3rem;
    color: #292524;
}

/* ── Service image: 4:3 mobile → 4:5 md ── */
.au-svc-img {
    aspect-ratio: 4 / 3;
    overflow: hidden;
    border-radius: 1rem;
    position: relative;
    background: #4d3d32;
}
@media (min-width: 768px) {
    .au-svc-img { aspect-ratio: 4 / 5; }
}

/* ── Commit dividers ── */
@media (min-width: 768px) {
    .au-cmt + .au-cmt { border-left: 1px solid #e7e5e4; }
}

/* ── Reviews header row ── */
.au-rv-hdr {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.75rem;
}

/* ── Swiper: fluid slides ── */
.au-rev-swiper .swiper-slide {
    width: calc(100vw - 2.5rem) !important;
    max-width: 340px;
}
@media (min-width: 480px)  { .au-rev-swiper .swiper-slide { max-width: 380px; } }
@media (min-width: 640px)  { .au-rev-swiper .swiper-slide { max-width: 440px; } }
@media (min-width: 768px)  { .au-rev-swiper .swiper-slide { width: 480px !important; max-width: unset; } }
@media (min-width: 1024px) { .au-rev-swiper .swiper-slide { width: 520px !important; } }

/* ── Buttons ── */
.au-btns { display: flex; flex-wrap: wrap; gap: .75rem; }
@media (max-width: 380px) { .au-btns { flex-direction: column; } }

/* ── Partners ── */
.au-logos {
    display: flex; flex-wrap: wrap;
    justify-content: center; align-items: center;
    gap: 1.5rem 2rem;
    opacity: .55; filter: grayscale(1);
    transition: opacity .7s, filter .7s;
}
@media (min-width: 768px) { .au-logos { gap: 2rem 4rem; } }
.au-logos:hover { opacity: 1; filter: grayscale(0); }
.au-logos img { height: 1.5rem; object-fit: contain; }
@media (min-width: 768px) { .au-logos img { height: 2.25rem; } }
</style>

<?php
/*
 * NOTE: footer.php already has </main> at line 6.
 * So we open <main> equivalent using a plain <div id="au-wrap">
 * and let footer.php close its own </main>.
 * This avoids the double-close bug.
 */
?>

<div id="au-wrap">

<!-- ═══════════════════════════════════════════════════
 1. HERO
═══════════════════════════════════════════════════ -->
<section id="au-hero" class="au-sec-lg bg-neutral-100 overflow-hidden">
<div class="au-wrap">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 mb-8 text-[11px] font-medium font-sans tracking-[0.15em] uppercase text-primary-500">
        <span class="w-5 h-px bg-primary-400 shrink-0"></span>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-accent-500 transition-colors">Homepage</a>
        <span class="opacity-40">/</span>
        <span class="text-stone-700">About us</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-20 items-center">

        <!-- Text -->
        <div class="flex flex-col gap-5 md:gap-7">
            <span class="text-primary-500 text-xs font-medium font-sans tracking-[0.2em] uppercase">Est. 2018 · Hà Nội, Việt Nam</span>

            <h1 class="text-stone-800 font-serif font-normal" style="font-size:clamp(1.9rem,5.5vw,3.75rem);line-height:1.1;">
                Nghệ thuật gốm<br>
                <span class="text-primary-500" style="font-style:italic;font-weight:300;">sáng lập bởi</span><br>
                Thai Unika Co., Ltd.
            </h1>

            <div class="flex flex-col gap-3.5 text-stone-600 font-sans leading-relaxed font-light" style="font-size:15px;max-width:30rem;">
                <p class="au-dropcap">
                    Chúng tôi tổ chức lớp học ngay tại trung tâm thành phố Hà Nội, giúp mọi người không phải mất công di chuyển xa. Bạn sẽ không cần đi 1–1,5 tiếng đến làng gốm Bát Tràng.
                </p>
                <p>
                    Chúng tôi muốn đưa gốm vào cuộc sống thường ngày — để mỗi người có thể trải nghiệm điều gì đó độc đáo, giảm căng thẳng và có những khoảnh khắc thư giãn.
                </p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-3 py-5 border-y border-stone-200">
                <?php foreach ([['8+','Năm KN'], ['1,200+','Học viên'], ['50+','Đối tác']] as $s): ?>
                <div class="flex flex-col gap-1">
                    <span class="text-stone-800 font-serif font-normal" style="font-size:clamp(1.2rem,4vw,1.75rem);"><?php echo $s[0]; ?></span>
                    <span class="text-stone-500 text-xs font-sans leading-tight"><?php echo $s[1]; ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Buttons -->
            <div class="au-btns">
                <a href="<?php echo esc_url($workshop_url); ?>"
                   class="inline-flex items-center gap-2 px-5 py-3 bg-accent-500 hover:bg-accent-600 text-white text-[14px] font-medium font-sans rounded-xl transition-colors shadow-sm">
                    Tham gia Workshop
                </a>
                <a href="<?php echo esc_url($shop_url); ?>"
                   class="inline-flex items-center gap-2 px-5 py-3 border border-stone-300 text-stone-600 text-[14px] font-medium font-sans rounded-xl hover:border-accent-400 hover:text-accent-500 transition-colors">
                    Xem sản phẩm
                </a>
            </div>
        </div>

        <!-- Arch image — extra padding-bottom on mobile for the badge -->
        <div class="relative flex justify-center lg:justify-end pb-10 lg:pb-0 mt-2 lg:mt-0">
            <div class="au-arch-ring" aria-hidden="true"></div>
            <div class="au-arch w-full max-w-[300px] sm:max-w-[360px] lg:max-w-[420px] aspect-[3/4] shadow-2xl" style="position:relative;z-index:1;">
                <img src="<?php echo esc_url($hero_img); ?>"
                     alt="Nghệ nhân làm gốm Bacera"
                     class="w-full h-full object-cover" loading="eager">
                <div class="absolute inset-0 bg-gradient-to-t from-primary-900/20 to-transparent"></div>
            </div>
            <!-- Badge — pinned inside wrapper, won't overflow viewport -->
            <div class="absolute bottom-0 left-0 bg-white rounded-2xl shadow-xl px-3.5 py-3 flex items-center gap-3 z-10">
                <div class="w-8 h-8 rounded-full bg-accent-500 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-stone-800 text-[12px] font-semibold font-sans leading-none mb-0.5">Chất lượng xuất khẩu</div>
                    <div class="text-stone-400 text-[10px] font-sans">EU Export Standard</div>
                </div>
            </div>
        </div>

    </div>
</div>
</section>

<!-- ═══════════════════════════════════════════════════
 2. MỤC TIÊU
═══════════════════════════════════════════════════ -->
<section id="au-goals" class="au-sec bg-white border-t border-stone-200">
<div class="au-wrap">
    <div class="flex flex-col md:flex-row gap-8 md:gap-0 items-start">

        <div class="w-full md:w-1/3 md:pr-10 md:border-r border-stone-200">
            <span class="text-primary-500 text-xs font-medium font-sans tracking-[0.2em] uppercase mb-3 block">Sứ mệnh</span>
            <h2 class="text-stone-800 font-serif font-normal leading-snug" style="font-size:clamp(1.5rem,4vw,2.25rem);">
                Mục tiêu<br><span class="text-primary-500 italic">của chúng tôi</span>
            </h2>
            <p class="mt-3 text-stone-500 text-sm font-sans leading-relaxed font-light">
                Ba giá trị cốt lõi định hình mọi thứ chúng tôi làm — từ lớp học đầu tiên đến sản phẩm cuối cùng.
            </p>
        </div>

        <div class="w-full md:w-2/3 md:pl-10 grid grid-cols-1 sm:grid-cols-3 gap-6 sm:gap-8">
            <?php
            $goals = [
                ['icon' => 'sprout',      'title' => 'Thư giãn & Sáng tạo',     'desc' => 'Giảm căng thẳng, tạo khoảng thời gian thư giãn và sáng tạo cho mọi người.'],
                ['icon' => 'cup-soda',    'title' => 'Dễ tiếp cận',              'desc' => 'Mang đến trải nghiệm gốm thú vị, dễ tiếp cận ngay giữa lòng thành phố.'],
                ['icon' => 'paintbrush-2','title' => 'Cá nhân hóa',              'desc' => 'Tự tay làm sản phẩm gốm chất lượng cao, cá nhân hóa theo sở thích.'],
            ];
            foreach ($goals as $g):
            ?>
            <div class="flex flex-row sm:flex-col gap-4">
                <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-primary-100 flex items-center justify-center shrink-0">
                    <i data-lucide="<?php echo $g['icon']; ?>" class="w-5 h-5 text-primary-600" stroke-width="1.5"></i>
                </div>
                <div>
                    <h3 class="text-stone-800 text-[14px] font-semibold font-sans mb-1"><?php echo $g['title']; ?></h3>
                    <p class="text-stone-500 text-sm font-sans leading-relaxed font-light"><?php echo $g['desc']; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</section>

<!-- ═══════════════════════════════════════════════════
 3. STORY
═══════════════════════════════════════════════════ -->
<section id="au-story" class="au-sec bg-neutral-100 border-t border-stone-200">
<div class="au-wrap">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16 items-center">

        <!-- Image block -->
        <div class="relative">
            <img src="https://images.unsplash.com/photo-1506806732259-39c2d0268443?auto=format&fit=crop&q=80&w=900"
                 alt="Xưởng gốm Bacera"
                 class="w-full object-cover rounded-2xl shadow-lg"
                 style="height: clamp(220px, 45vw, 540px);"
                 loading="lazy">
            <!-- Year badge — top-right, always inside image -->
            <div class="absolute top-4 right-4 w-[4.5rem] h-[4.5rem] md:w-24 md:h-24 bg-accent-500 rounded-full flex flex-col items-center justify-center shadow-xl shadow-accent-500/30">
                <span class="text-white text-[8px] md:text-[10px] font-sans text-center leading-tight">Since</span>
                <strong class="text-white text-base md:text-xl font-serif font-normal">2018</strong>
            </div>
            <!-- Overlay image — desktop only -->
            <div class="absolute -bottom-6 -left-5 w-28 h-28 lg:w-36 lg:h-36 rounded-2xl overflow-hidden border-4 border-white shadow-xl hidden lg:block">
                <img src="https://images.unsplash.com/photo-1590400516641-52481c67d302?auto=format&fit=crop&q=80&w=400"
                     alt="Chi tiết gốm" class="w-full h-full object-cover" loading="lazy">
            </div>
        </div>

        <!-- Text block -->
        <div class="flex flex-col gap-5">
            <span class="text-primary-500 text-xs font-medium font-sans tracking-[0.2em] uppercase">Câu chuyện của chúng tôi</span>
            <h2 class="text-stone-800 font-serif font-normal leading-snug" style="font-size:clamp(1.5rem,4vw,2.25rem);">
                Một ký ức, một hơi thở,<br>
                <span class="text-primary-600 italic">một câu chuyện nhỏ</span>
            </h2>
            <p class="text-stone-600 text-[14px] md:text-[15px] font-sans leading-relaxed opacity-80">
                Đôi tay không vội vàng — lắng nghe đất sét, lắng nghe bánh xe và những khoảng lặng ở giữa. Mỗi tác phẩm được nặn qua đôi bàn tay kiên nhẫn, nung qua kỹ thuật cổ xưa.
            </p>

            <!-- Timeline -->
            <div class="flex flex-col gap-3 py-5 border-y border-stone-200">
                <?php foreach ([
                    ['2018', 'Thành lập xưởng gốm đầu tiên tại Hà Nội'],
                    ['2020', 'Mở rộng workshop & cộng tác với khách sạn 5 sao'],
                    ['2024', 'Ra mắt bộ sưu tập xuất khẩu thị trường Châu Âu'],
                ] as $m): ?>
                <div class="flex items-start gap-3">
                    <span class="shrink-0 text-accent-500 text-[12px] font-serif w-9 mt-0.5"><?php echo $m[0]; ?></span>
                    <div class="w-px bg-stone-300 mt-1 self-stretch shrink-0"></div>
                    <p class="text-stone-600 text-[13px] md:text-[14px] font-sans leading-snug"><?php echo $m[1]; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>
</section>

<!-- ═══════════════════════════════════════════════════
 4. ĐỐI TÁC
═══════════════════════════════════════════════════ -->
<section id="au-partners" class="au-sec bg-white border-t border-stone-200 text-center">
<div class="au-wrap">
    <span class="text-primary-500 text-xs font-medium font-sans tracking-[0.2em] uppercase mb-2 block">Đối tác</span>
    <h2 class="text-stone-800 font-serif font-normal leading-snug max-w-lg mx-auto mb-3"
        style="font-size:clamp(1.35rem,4vw,1.9rem);">
        Chất lượng cao, <span class="text-primary-500 italic">được tin dùng tại</span><br class="hidden sm:block">
        các chuỗi khách sạn &amp; du thuyền nổi tiếng
    </h2>
    <div class="w-10 h-px bg-stone-300 mx-auto mb-8 md:mb-12"></div>
    <div class="au-logos">
        <?php
        $partners = [
            ['name' => 'Marriott',  'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e0/Marriott_Hotels_%26_Resorts_logo.svg/220px-Marriott_Hotels_%26_Resorts_logo.svg.png'],
            ['name' => 'Heritage',  'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/27/Heritage_Cruises_logo.png/220px-Heritage_Cruises_logo.png'],
            ['name' => 'Accor',     'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Accor_Logo.svg/220px-Accor_Logo.svg.png'],
            ['name' => 'The Jahan', 'logo' => 'https://placehold.co/160x52/f8f7f3/8d6a54?text=The+Jahan&font=georgia'],
        ];
        foreach ($partners as $p):
        ?>
        <img src="<?php echo esc_url($p['logo']); ?>"
             alt="<?php echo esc_attr($p['name']); ?>"
             loading="lazy"
             onerror="this.src='https://placehold.co/120x40/f8f7f3/8d6a54?text=<?php echo rawurlencode($p['name']); ?>&font=georgia'">
        <?php endforeach; ?>
    </div>
</div>
</section>

<!-- ═══════════════════════════════════════════════════
 5. DỊCH VỤ
═══════════════════════════════════════════════════ -->
<section id="au-services" class="au-sec bg-primary-900 border-t border-primary-800">
<div class="au-wrap">

    <div class="text-center mb-8 md:mb-12">
        <span class="text-primary-300 text-xs font-medium font-sans tracking-[0.2em] uppercase mb-2 block">Khám phá</span>
        <h2 class="text-neutral-100 font-serif font-normal" style="font-size:clamp(1.75rem,5vw,3rem);">
            Dịch vụ <span class="text-primary-300 italic">của chúng tôi</span>
        </h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 md:gap-10 lg:gap-16 items-start">

        <a href="<?php echo esc_url($shop_url); ?>" class="group flex flex-col gap-4 sm:mt-8">
            <div class="au-svc-img">
                <img src="<?php echo esc_url($service1_img); ?>" alt="Sản phẩm gốm thủ công"
                     class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                <div class="absolute inset-0 bg-gradient-to-t from-primary-900/60 via-transparent to-transparent"></div>
                <div class="absolute bottom-4 left-4">
                    <span class="inline-block bg-accent-500 text-white text-[10px] font-medium font-sans px-2.5 py-1 rounded-full uppercase tracking-wide">Shop</span>
                </div>
            </div>
            <div class="flex justify-between items-start gap-3">
                <div>
                    <h3 class="text-neutral-100 text-[15px] md:text-lg font-serif font-normal mb-1 group-hover:text-primary-300 transition-colors">01. Sản phẩm gốm thủ công</h3>
                    <p class="text-primary-400 text-sm font-sans font-light">Làm thủ công tại làng gốm Bát Tràng</p>
                </div>
                <div class="w-9 h-9 md:w-10 md:h-10 rounded-full border border-primary-600 flex items-center justify-center shrink-0 group-hover:bg-accent-500 group-hover:border-accent-500 transition-all">
                    <i data-lucide="chevron-right" class="w-4 h-4 text-primary-400 group-hover:text-white" stroke-width="1"></i>
                </div>
            </div>
        </a>

        <a href="<?php echo esc_url($workshop_url); ?>" class="group flex flex-col gap-4">
            <div class="au-svc-img">
                <img src="<?php echo esc_url($service2_img); ?>" alt="Workshop làm gốm"
                     class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                <div class="absolute inset-0 bg-gradient-to-t from-primary-900/60 via-transparent to-transparent"></div>
                <div class="absolute bottom-4 left-4">
                    <span class="inline-block bg-primary-600 text-white text-[10px] font-medium font-sans px-2.5 py-1 rounded-full uppercase tracking-wide">Workshop</span>
                </div>
            </div>
            <div class="flex justify-between items-start gap-3">
                <div>
                    <h3 class="text-neutral-100 text-[15px] md:text-lg font-serif font-normal mb-1 group-hover:text-primary-300 transition-colors">02. Workshop làm gốm</h3>
                    <p class="text-primary-400 text-sm font-sans font-light">Workshop dạy làm gốm cho mọi lứa tuổi</p>
                </div>
                <div class="w-9 h-9 md:w-10 md:h-10 rounded-full border border-primary-600 flex items-center justify-center shrink-0 group-hover:bg-accent-500 group-hover:border-accent-500 transition-all">
                    <i data-lucide="chevron-right" class="w-4 h-4 text-primary-400 group-hover:text-white" stroke-width="1"></i>
                </div>
            </div>
        </a>

    </div>
</div>
</section>

<!-- ═══════════════════════════════════════════════════
 6. CAM KẾT CHẤT LƯỢNG
═══════════════════════════════════════════════════ -->
<section id="au-commits" class="au-sec bg-neutral-100 border-t border-stone-200">
<div class="au-wrap">
    <div class="text-center mb-8 md:mb-12">
        <span class="text-primary-500 text-xs font-medium font-sans tracking-[0.2em] uppercase mb-2 block">Tiêu chuẩn</span>
        <h2 class="text-stone-800 font-serif font-normal" style="font-size:clamp(1.5rem,4vw,2.25rem);">
            Cam kết <span class="text-primary-500 italic">chất lượng</span>
        </h2>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4">
        <?php
        $commits = [
            ['icon' => 'shield-check', 'title' => '100% Thủ công', 'desc' => 'Làm thủ công bởi nghệ nhân lành nghề.'],
            ['icon' => 'leaf',          'title' => 'An toàn & Sạch',  'desc' => 'Không hóa chất độc hại, đủ tiêu chuẩn EU.'],
            ['icon' => 'flask-conical', 'title' => 'Men phản ứng',    'desc' => 'Tráng men đặc biệt, màu sắc độc nhất.'],
            ['icon' => 'flame',         'title' => 'Nung 1350°C',     'desc' => 'Nhiệt độ cao tạo độ bền tối ưu.'],
        ];
        foreach ($commits as $i => $c):
            $borderL = ($i === 1 || $i === 3) ? 'border-l border-stone-200' : '';
            $borderT = ($i >= 2) ? 'border-t border-stone-200 md:border-t-0' : '';
        ?>
        <div class="au-cmt flex flex-col items-center gap-3 md:gap-4 text-center px-3 md:px-6 py-5 md:py-6 <?php echo "$borderL $borderT"; ?>">
            <div class="w-12 h-12 md:w-14 md:h-14 rounded-full border border-stone-200 bg-white shadow-sm flex items-center justify-center">
                <i data-lucide="<?php echo $c['icon']; ?>" class="w-5 h-5 text-primary-600" stroke-width="1.2"></i>
            </div>
            <div>
                <h3 class="text-stone-800 text-[13px] md:text-[14px] font-semibold font-sans mb-1"><?php echo $c['title']; ?></h3>
                <p class="text-stone-500 text-[11px] md:text-[13px] font-sans leading-relaxed font-light"><?php echo $c['desc']; ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</section>

<!-- ═══════════════════════════════════════════════════
 7. ĐÁNH GIÁ KHÁCH HÀNG
═══════════════════════════════════════════════════ -->
<section id="au-reviews" class="au-sec bg-white border-t border-stone-200 overflow-hidden">
<div class="au-wrap">

    <div class="au-rv-hdr">
        <div>
            <span class="text-primary-500 text-xs font-medium font-sans tracking-[0.2em] uppercase mb-2 block">Cảm nhận</span>
            <h2 class="text-stone-800 font-serif font-normal" style="font-size:clamp(1.4rem,5vw,2.25rem);">
                Lời tự tình <span class="text-primary-500 italic">từ khách hàng</span>
            </h2>
        </div>
        <div class="flex gap-2 shrink-0">
            <button class="au-prev w-10 h-10 rounded-full bg-neutral-100 hover:bg-primary-100 border border-stone-200 flex items-center justify-center transition-colors" aria-label="Trước">
                <svg class="w-4 h-4 text-stone-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button class="au-next w-10 h-10 rounded-full bg-neutral-100 hover:bg-primary-100 border border-stone-200 flex items-center justify-center transition-colors" aria-label="Sau">
                <svg class="w-4 h-4 text-stone-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>

    <div class="swiper au-rev-swiper overflow-visible">
        <div class="swiper-wrapper">
            <?php foreach ($reviews as $r):
                $rq  = $r['quote']  ?? $r['review_text']   ?? '';
                $rn  = $r['name']   ?? $r['customer_name'] ?? 'Khách hàng';
                $rc  = $r['course'] ?? $r['workshop_name'] ?? 'Workshop';
                $ri  = $r['img']    ?? 'https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=700';
            ?>
            <div class="swiper-slide">
                <div class="flex flex-col rounded-2xl overflow-hidden border border-stone-200 bg-white shadow-sm h-full">
                    <div class="overflow-hidden bg-primary-100 relative shrink-0" style="height:clamp(152px,40vw,240px);">
                        <img src="<?php echo esc_url($ri); ?>" alt="<?php echo esc_attr($rn); ?>"
                             class="w-full h-full object-cover grayscale hover:grayscale-0 transition-all duration-700" loading="lazy">
                        <div class="absolute top-3 left-3 text-5xl font-serif text-white/40 leading-none select-none">"</div>
                    </div>
                    <div class="p-4 md:p-6 flex flex-col gap-3 flex-1">
                        <div class="flex gap-0.5">
                            <?php for ($s = 0; $s < 5; $s++): ?>
                            <svg class="w-3.5 h-3.5 text-accent-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            <?php endfor; ?>
                        </div>
                        <p class="text-stone-700 text-[13px] md:text-[14px] font-sans leading-relaxed italic flex-1">
                            "<?php echo esc_html($rq); ?>"
                        </p>
                        <div class="pt-3 border-t border-stone-100">
                            <span class="block text-stone-800 text-[13px] md:text-[14px] font-semibold font-sans"><?php echo esc_html($rn); ?></span>
                            <span class="text-primary-500 text-[11px] md:text-[12px] font-sans"><?php echo esc_html($rc); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</section>

<!-- ═══════════════════════════════════════════════════
 8. CTA
═══════════════════════════════════════════════════ -->
<section id="au-cta" class="au-sec-lg bg-primary-900 border-t border-primary-800 relative overflow-hidden">
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[300px] md:w-[600px] h-[150px] md:h-[280px] rounded-full opacity-20"
         style="background:radial-gradient(ellipse,#d95f47,transparent 70%)"></div>
    <div class="au-wrap text-center relative z-10">
        <span class="text-primary-300 text-xs font-medium font-sans tracking-[0.2em] uppercase mb-4 block">Bắt đầu hành trình</span>
        <h2 class="text-neutral-100 font-serif font-normal leading-snug mb-3"
            style="font-size:clamp(1.6rem,5vw,2.75rem);">
            Sẵn sàng tạo nên<br>
            <span class="text-primary-300 italic">câu chuyện của bạn?</span>
        </h2>
        <p class="text-primary-300 text-[13px] md:text-[15px] font-sans leading-relaxed opacity-80 max-w-sm mx-auto mb-7 md:mb-10">
            Tham gia workshop gốm hoặc khám phá bộ sưu tập. Mỗi sản phẩm mang một câu chuyện — câu chuyện của bạn.
        </p>
        <div class="au-btns justify-center">
            <a href="<?php echo esc_url($workshop_url); ?>"
               class="inline-flex items-center gap-2 px-5 py-3 md:px-7 md:py-4 bg-accent-500 hover:bg-accent-600 text-white text-[14px] md:text-[15px] font-medium font-sans rounded-xl transition-colors shadow-sm">
                <i data-lucide="calendar-days" class="w-4 h-4 shrink-0" stroke-width="1.5"></i>
                Tham gia Workshop
            </a>
            <a href="<?php echo esc_url($shop_url); ?>"
               class="inline-flex items-center gap-2 px-5 py-3 md:px-7 md:py-4 border border-primary-600 text-neutral-100 text-[14px] md:text-[15px] font-medium font-sans rounded-xl hover:border-neutral-100 hover:bg-white/10 transition-colors">
                <i data-lucide="shopping-bag" class="w-4 h-4 shrink-0" stroke-width="1.5"></i>
                Khám phá sản phẩm
            </a>
        </div>
    </div>
</section>

</div><!-- /#au-wrap — note: footer.php closes </main> -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof Swiper !== 'undefined') {
        new Swiper('.au-rev-swiper', {
            slidesPerView: 1,
            spaceBetween: 16,
            grabCursor: true,
            navigation: { nextEl: '.au-next', prevEl: '.au-prev' },
            breakpoints: {
                480:  { slidesPerView: 1.15, spaceBetween: 16 },
                640:  { slidesPerView: 1.35, spaceBetween: 18 },
                768:  { slidesPerView: 1.8,  spaceBetween: 20 },
                1024: { slidesPerView: 2.3,  spaceBetween: 24 },
            }
        });
    }
});
</script>

<?php get_footer(); ?>
