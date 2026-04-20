<?php
/**
 * Template Name: Trang Chủ
 * Description: Homepage — products from Pancake API, layout matches design spec.
 */

get_header();

// ─── Swiper CSS/JS ────────────────────────────────────────────────────────────
add_action('wp_footer', function() {
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">';
    echo '<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>';
}, 5);

// ─── URL helpers ──────────────────────────────────────────────────────────────
global $wpdb;

function hp_page_url($tpl, $fallback) {
    global $wpdb;
    $id = $wpdb->get_var($wpdb->prepare(
        "SELECT p.ID FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON p.ID=pm.post_id
         WHERE p.post_type='page' AND p.post_status='publish'
           AND pm.meta_key='_wp_page_template'
           AND pm.meta_value IN (%s,%s) LIMIT 1",
        'templates/' . $tpl, $tpl
    ));
    return $id ? get_permalink((int)$id) : home_url($fallback);
}

$hp_about_url    = hp_page_url('template-about.php',         '/about-us/');
$hp_workshop_url = get_post_type_archive_link('workshop') ?: home_url('/workshop/');
$hp_blog_url     = home_url('/blog/');
$hp_team_url     = home_url('/our-team/');
$hp_shop_url     = class_exists('Bacera_Utils') ? Bacera_Utils::get_shop_page_url() : home_url('/shop/');

// ─── Pancake API — fetch products ─────────────────────────────────────────────
$hp_pancake_ok    = class_exists('Pancake_API_Client') && class_exists('Bacera_Utils');
$hp_api_products  = [];   // raw variations from API
$hp_categories    = [];   // categories from API

if ($hp_pancake_ok) {
    $api = new Pancake_API_Client();

    // Fetch categories
    $cat_resp = $api->request('/shops/{SHOP_ID}/categories', 'GET');
    if (is_array($cat_resp) && !empty($cat_resp['success']) && !empty($cat_resp['data'])) {
        $hp_categories = $cat_resp['data'];
    }

    // Fetch latest products (page 1, up to 12 for homepage sections)
    $prod_resp = $api->request('/shops/{SHOP_ID}/products/variations?page_size=12&page=1', 'GET');
    if (is_array($prod_resp) && !empty($prod_resp['success']) && !empty($prod_resp['data'])) {
        $hp_api_products = $prod_resp['data'];
        // Sync to local WP DB (so detail pages work)
        foreach ($hp_api_products as $item) {
            Bacera_Utils::upsert_external_product($item);
        }
    }
}

/**
 * Convert a Pancake variation array → args for product-card.php component.
 * Uses the EXACT same price-extraction pattern as template-shop.php (lines 1042-1046).
 */
function hp_pancake_card_args(array $p, array $cats = []): array {
    $name = $p['product']['name'] ?? $p['name'] ?? 'Sản phẩm';
    $img  = class_exists('Bacera_Utils') ? Bacera_Utils::get_proxy_url($p) : '';

    // ── Giá: mirror chính xác template-shop.php để không bao giờ thiếu giá ──
    $price_at_counter = isset($p['price_at_counter'])
        ? (float) $p['price_at_counter']
        : (isset($p['variations'][0]['price_at_counter']) ? (float) $p['variations'][0]['price_at_counter'] : 0);

    $retail_price = isset($p['retail_price'])
        ? (float) $p['retail_price']
        : (isset($p['variations'][0]['retail_price']) ? (float) $p['variations'][0]['retail_price'] : 0);

    $price = $price_at_counter > 0 ? $price_at_counter : $retail_price; // giá bán
    $orig  = ($retail_price > $price && $price > 0) ? $retail_price : 0; // giá gốc (khi có KM)
    $disc  = ($orig > 0 && $price < $orig)
             ? '-' . (int) round(($orig - $price) / $orig * 100) . '%' : '';

    // ── Brand: category name ──────────────────────────────────────────────────
    $brand = '';
    if (!empty($p['product']['category']['name'])) {
        $brand = $p['product']['category']['name'];
    } elseif (!empty($p['product']['categories'][0]['name'])) {
        $brand = $p['product']['categories'][0]['name'];
    } elseif (!empty($cats)) {
        $cat_id = $p['product']['category']['id']
               ?? $p['product']['category_id']
               ?? $p['category_id']
               ?? '';
        if ($cat_id) {
            foreach ($cats as $c) {
                $cid = (string)($c['id'] ?? $c['category_id'] ?? '');
                if ($cid === (string)$cat_id) {
                    $brand = $c['text'] ?? $c['name'] ?? '';
                    break;
                }
            }
        }
    }
    if (!$brand) $brand = 'Bacera';

    $url = class_exists('Bacera_Utils') ? Bacera_Utils::get_product_permalink($p) : '#';

    // Hiển thị "Liên hệ" khi không có giá
    $price_display = $price > 0 ? number_format($price, 0, ',', '.') . ' ₫' : 'Liên hệ';
    $orig_display  = $orig  > 0 ? number_format($orig,  0, ',', '.') . ' ₫' : '';

    return [
        'image'         => $img,
        'brand'         => $brand,
        'name'          => $name,
        'price'         => $price_display,
        'originalPrice' => $orig_display,
        'discount'      => $disc,
        'url'           => $url,
        'plp'           => true,
    ];
}

// Prepare product slices
$hp_new_arrivals  = array_slice($hp_api_products, 0, 8);   // New Arrivals slider
$hp_featured      = array_slice($hp_api_products, 0, 5);   // New Collection: 1 big + 4 small (2x2)
$hp_best_sellers  = array_slice($hp_api_products, 0, 8);   // Best Sellers slider

// If Pancake not available → WP_Post fallback
if (empty($hp_new_arrivals)) {
    $fallback_q = new WP_Query(['post_type'=>'pancake_product','posts_per_page'=>8,'post_status'=>'publish','orderby'=>'date','order'=>'DESC']);
    while ($fallback_q->have_posts()) { $fallback_q->the_post(); $hp_new_arrivals[] = get_post(); }
    wp_reset_postdata();
    $hp_featured     = array_slice($hp_new_arrivals, 0, 5);
    $hp_best_sellers = $hp_new_arrivals;
}

// Best-seller tab categories from Pancake categories (up to 5)
$hp_bs_tabs = [];
if (!empty($hp_categories)) {
    foreach (array_slice($hp_categories, 0, 5) as $cat) {
        $hp_bs_tabs[] = [
            'id'    => $cat['id'] ?? $cat['category_id'] ?? '',
            'label' => $cat['text'] ?? $cat['name'] ?? '',
        ];
    }
}
if (empty($hp_bs_tabs)) {
    $hp_bs_tabs = [
        ['id'=>'','label'=>'Begin Slowly'],
        ['id'=>'','label'=>'Sip and Pause'],
        ['id'=>'','label'=>'Tables That Linger'],
        ['id'=>'','label'=>'Hold a Quiet Space'],
        ['id'=>'','label'=>'Gift a Moment'],
    ];
}

// ─── Workshops ────────────────────────────────────────────────────────────────
$hp_workshops_raw = get_posts(['post_type'=>'workshop','post_status'=>'publish','posts_per_page'=>4,'orderby'=>'date','order'=>'DESC']);
$hp_workshops = [];
foreach ($hp_workshops_raw as $wk) {
    $price_raw = get_post_meta($wk->ID, '_price', true) ?: '';
    $price_fmt = is_numeric(str_replace([',','.'],'', $price_raw))
                 ? 'Từ ' . number_format((float)preg_replace('/[^0-9.]/','', $price_raw), 0, ',', '.') . 'đ/Người'
                 : ($price_raw ?: 'Liên hệ');
    $thumb  = get_post_meta($wk->ID,'_thumbnail_url',true)
              ?: get_the_post_thumbnail_url($wk->ID,'large')
              ?: 'https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=800';
    $tagline = get_post_meta($wk->ID,'_tagline',true)
               ?: wp_trim_words($wk->post_excerpt ?: strip_tags($wk->post_content), 15, '…');

    $ts_table = $wpdb->prefix . 'bacera_workshop_slots';
    $bs = 0; $ts_cnt = 20;
    if ($wpdb->get_var("SHOW TABLES LIKE '$ts_table'") === $ts_table) {
        $slot = $wpdb->get_row($wpdb->prepare(
            "SELECT SUM(booked_seats) as bs, SUM(total_seats) as ts FROM $ts_table WHERE workshop_id=%d AND status!='cancelled'",
            $wk->ID
        ));
        if ($slot) { $bs = (int)$slot->bs; if ($slot->ts) $ts_cnt = (int)$slot->ts; }
    }
    $hp_workshops[] = ['image'=>$thumb,'title'=>$wk->post_title,'description'=>$tagline,'pricing'=>$price_fmt,'bookedSlots'=>$bs,'totalSlots'=>$ts_cnt,'link'=>get_permalink($wk->ID)];
}
if (empty($hp_workshops)) {
    $hp_workshops = [
        ['image'=>'https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=800','title'=>'Pottery Wheel Throwing','description'=>'A peaceful, hands-on journey for beginners.','pricing'=>'Từ 950.000đ/Người','bookedSlots'=>13,'totalSlots'=>16,'link'=>$hp_workshop_url],
        ['image'=>'https://images.unsplash.com/photo-1610701596087-0b1a039735d9?auto=format&fit=crop&q=80&w=800','title'=>'Hand-building Pottery','description'=>'Shape and create your own ceramic piece.','pricing'=>'Từ 950.000đ/Người','bookedSlots'=>8,'totalSlots'=>16,'link'=>$hp_workshop_url],
        ['image'=>'https://images.unsplash.com/photo-1506806732259-39c2d0268443?auto=format&fit=crop&q=80&w=800','title'=>'Hanoi School Class','description'=>'Special courses for students.','pricing'=>'Liên hệ','bookedSlots'=>0,'totalSlots'=>20,'link'=>$hp_workshop_url],
        ['image'=>'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?auto=format&fit=crop&q=80&w=800','title'=>'Team-building Pottery','description'=>'A unique bonding experience.','pricing'=>'Liên hệ','bookedSlots'=>0,'totalSlots'=>30,'link'=>$hp_workshop_url],
    ];
}

// ─── Blog posts ───────────────────────────────────────────────────────────────
$hp_blog_posts = get_posts(['numberposts'=>3,'post_status'=>'publish','orderby'=>'date','order'=>'DESC']);

// ─── Stats ────────────────────────────────────────────────────────────────────
$hp_stat_years     = date('Y') - 2018;
$hp_stat_customers = 0;
$hp_stat_products  = count($hp_api_products) > 0 ? count($hp_api_products) : (int)wp_count_posts('pancake_product')->publish;
$cust_tbl = $wpdb->prefix . 'bacera_customers';
if ($wpdb->get_var("SHOW TABLES LIKE '$cust_tbl'") === $cust_tbl) {
    $hp_stat_customers = (int)$wpdb->get_var("SELECT COUNT(*) FROM $cust_tbl");
}
function hp_fmt($n) { return $n >= 1000 ? round($n/1000,1).'K+' : ($n > 0 ? $n.'+' : '—'); }

// ─── Shop collections from Pancake categories ─────────────────────────────────
$hp_collections = [];
$col_imgs = ['https://images.unsplash.com/photo-1578749556568-bc2c40e68b61?auto=format&fit=crop&q=80&w=900','https://images.unsplash.com/photo-1610701596007-11502861dcfa?auto=format&fit=crop&q=80&w=700','https://images.unsplash.com/photo-1590400516641-52481c67d302?auto=format&fit=crop&q=80&w=700','https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=900'];
if (!empty($hp_categories)) {
    foreach (array_slice($hp_categories, 0, 4) as $i => $cat) {
        $cid = $cat['id'] ?? $cat['category_id'] ?? '';
        $hp_collections[] = [
            'title' => $cat['text'] ?? $cat['name'] ?? 'Collection',
            'sub'   => 'Explore our collection',
            'img'   => $col_imgs[$i] ?? $col_imgs[0],
            'url'   => $cid ? add_query_arg('filter_collection', $cid, $hp_shop_url) : $hp_shop_url,
        ];
    }
}
if (empty($hp_collections)) {
    $hp_collections = [
        ['title'=>'Whispers of Clay',    'sub'=>'Soft forms. Gentle hues.','img'=>$col_imgs[0],'url'=>$hp_shop_url],
        ['title'=>'Shared Gatherings',   'sub'=>'Objects for slowing down.','img'=>$col_imgs[1],'url'=>$hp_shop_url],
        ['title'=>'Moments in Stillness','sub'=>'Raw textures, deep glazes.','img'=>$col_imgs[2],'url'=>$hp_shop_url],
        ['title'=>'Quiet Mornings',      'sub'=>'Ceramics for slow mornings.','img'=>$col_imgs[3],'url'=>$hp_shop_url],
    ];
}

// ─── Video ────────────────────────────────────────────────────────────────────
$hp_video_url   = get_option('bacera_homepage_video_url', '');
$hp_video_title = get_option('bacera_homepage_video_title', 'The Art of Patience: Shaping Clay by Hand');
$hp_video_desc  = get_option('bacera_homepage_video_desc',  'In every spin of the wheel and every breath of fire, a quiet story takes shape.');
$hp_video_thumb = get_option('bacera_homepage_video_thumb', 'https://images.unsplash.com/photo-1506806732259-39c2d0268443?auto=format&fit=crop&q=80&w=1920');
if ($hp_video_url && preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/', $hp_video_url, $m)) {
    $hp_video_embed = 'https://www.youtube.com/embed/' . $m[1] . '?autoplay=1&rel=0';
} else {
    $hp_video_embed = $hp_video_url;
}
?>

<style>
@keyframes hero-fade-up { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
@keyframes hero-float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
@keyframes scroll-line { 0%{transform:scaleY(0);transform-origin:top} 50%{transform:scaleY(1);transform-origin:top} 51%{transform:scaleY(1);transform-origin:bottom} 100%{transform:scaleY(0);transform-origin:bottom} }
.hero-fade-1 { animation:hero-fade-up .7s ease both .1s }
.hero-fade-2 { animation:hero-fade-up .7s ease both .25s }
.hero-fade-3 { animation:hero-fade-up .7s ease both .4s }
.hero-fade-4 { animation:hero-fade-up .7s ease both .55s }
.hero-fade-5 { animation:hero-fade-up .7s ease both .7s }
.hero-float  { animation:hero-float 4s ease-in-out infinite }
.scroll-line { animation:scroll-line 2s ease-in-out infinite }
</style>

<main id="primary" class="site-main bg-bgtheme">

<!-- ════════════════ HERO ════════════════════════════════════════════════════ -->
<section id="hero" class="relative w-full min-h-screen flex items-center overflow-hidden bg-textmain">
    <!-- BG image + layered gradient -->
    <div class="absolute inset-0">
        <img src="https://bacera.demo/wp-content/uploads/2026/04/BG.png"
             alt="Bacera Pottery Studio" class="w-full h-full object-cover" loading="eager"
             style="opacity:.5;">
        <div class="absolute inset-0" style="background:linear-gradient(115deg,rgba(30,18,12,.96) 0%,rgba(30,18,12,.7) 45%,rgba(20,12,6,.3) 100%);"></div>
        <!-- Warm noise grain -->
        <div class="absolute inset-0" style="opacity:.035;background-image:url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"200\" height=\"200\"><filter id=\"n\"><feTurbulence type=\"fractalNoise\" baseFrequency=\"0.85\" numOctaves=\"4\" stitchTiles=\"stitch\"/></filter><rect width=\"200\" height=\"200\" filter=\"url(%23n)\"/></svg>');"></div>
    </div>

    <!-- Right decorative circle -->
    <div class="absolute right-0 top-0 h-full w-1/3 pointer-events-none hidden lg:block">
        <div class="absolute top-1/2 right-[-120px] -translate-y-1/2 w-[480px] h-[480px] rounded-full"
             style="background:radial-gradient(circle,rgba(217,95,71,.12) 0%,transparent 70%);"></div>
        <div class="absolute bottom-24 right-16 w-3 h-3 rounded-full bg-terracotta/50 hero-float"></div>
        <div class="absolute top-32 right-40 w-2 h-2 rounded-full bg-accent/40 hero-float" style="animation-delay:.8s"></div>
    </div>

    <div class="relative z-10 w-full bacera-container pt-32 pb-36 lg:pt-40 lg:pb-44">
        <div class="max-w-2xl flex flex-col gap-6 items-start">

            <!-- Eyebrow -->
            <div class="flex items-center gap-3 hero-fade-1">
                <span class="w-10 h-px bg-terracotta"></span>
                <span class="inline-flex items-center gap-2 text-terracotta/90 text-[10.5px] font-medium font-sans tracking-[0.28em] uppercase">
                    <span class="w-1.5 h-1.5 rounded-full bg-terracotta animate-pulse"></span>
                    Bacera Pottery · Hà Nội · Since 2018
                </span>
            </div>

            <!-- Headline -->
            <h1 class="text-white text-5xl md:text-[3.85rem] lg:text-[4.25rem] font-serif font-normal leading-[1.1] tracking-tight hero-fade-2">
                Discover timeless<br>
                ceramics crafted<br>
                <em class="italic" style="color:rgba(var(--color-accent, 206 162 122) / .95)">by hands and heart</em>
            </h1>

            <!-- Sub -->
            <p class="text-stone-300/90 text-[17px] font-sans leading-relaxed max-w-lg hero-fade-3">
                Each piece holds a story — ready to become part of yours.<br class="hidden md:block">
                Handcrafted in Bat Trang, Vietnam.
            </p>

            <!-- CTAs -->
            <div class="flex flex-wrap gap-3 mt-1 hero-fade-4">
                <?php get_template_part('app/Views/components/button', null, [
                    'text'    => 'Shop our Collection',
                    'variant' => 'primary',
                    'link'    => $hp_shop_url,
                    'icon'    => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>',
                ]); ?>
                <?php get_template_part('app/Views/components/button', null, [
                    'text'    => 'Join a Workshop',
                    'variant' => 'outline-light',
                    'link'    => $hp_workshop_url,
                    'icon'    => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
                ]); ?>
            </div>
</div>
    </div>
</section>

<!-- ════════════════ NEW ARRIVALS — Swiper slider ════════════════════════════ -->
<section id="new-arrivals" class="section-pad bg-bgtheme">
    <div class="bacera-container">
        <div class="flex flex-col md:flex-row items-start md:items-end justify-between gap-6 mb-8">
            <div class="flex flex-col gap-1">
                <span class="text-terracotta text-[11px] font-medium font-sans tracking-[0.2em] uppercase">Just landed</span>
                <h2 class="text-textmain text-4xl font-serif font-normal leading-10">New Arrivals</h2>
                <p class="text-textmuted text-body-reg font-sans leading-relaxed max-w-md">Freshly crafted. New stories waiting to be part of your everyday rituals.</p>
            </div>
            <?php get_template_part('app/Views/components/button', null, [
                'text'    => 'View all',
                'variant' => 'primary',
                'link'    => $hp_shop_url,
                'icon'    => '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>',
                'class'   => 'shrink-0',
            ]); ?>
        </div>

        <?php if (!empty($hp_new_arrivals)): ?>
        <!-- Swiper for product slider -->
        <div class="relative">
            <!-- Nav buttons -->
            <button id="na-prev" class="hidden md:flex absolute left-0 top-1/2 -translate-y-1/2 -translate-x-5 z-10 w-10 h-10 rounded-full bg-white border border-accent/20 shadow-md items-center justify-center hover:bg-primary-50 transition-colors">
                <svg class="w-4 h-4 text-stone-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button id="na-next" class="hidden md:flex absolute right-0 top-1/2 -translate-y-1/2 translate-x-5 z-10 w-10 h-10 rounded-full bg-white border border-accent/20 shadow-md items-center justify-center hover:bg-primary-50 transition-colors">
                <svg class="w-4 h-4 text-stone-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>

            <div class="swiper na-swiper">
                <div class="swiper-wrapper">
                    <?php foreach ($hp_new_arrivals as $p): ?>
                    <div class="swiper-slide">
                        <?php
                        $card_args = is_array($p) ? hp_pancake_card_args($p, $hp_categories) : [
                            'image'   => get_the_post_thumbnail_url($p->ID,'large') ?: home_url("/bacera-img/{$p->post_name}.jpg"),
                            'brand'   => 'Bacera',
                            'name'    => $p->post_title,
                            'price'   => number_format((float)get_post_meta($p->ID,'_price',true),0,',','.') . ' ₫',
                            'url'     => get_permalink($p->ID),
                            'plp'     => true,
                        ];
                        get_template_part('app/Views/components/product-card', null, $card_args);
                        ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="py-12 text-center text-textmuted font-sans">No products found. <a href="<?php echo esc_url($hp_shop_url); ?>" class="text-terracotta underline">Visit shop</a></div>
        <?php endif; ?>
    </div>
</section>

<!-- ════════════════ NEW COLLECTION — featured grid ══════════════════════════ -->
<section id="new-collection" class="section-pad bg-white border-t border-accent/20">
    <div class="bacera-container">
        <div class="flex flex-col md:flex-row items-start md:items-end justify-between gap-6 mb-8">
            <div>
                <span class="text-terracotta text-[11px] font-medium font-sans tracking-[0.2em] uppercase">Curated for you</span>
                <h2 class="text-textmain text-4xl font-serif font-normal leading-10 mt-1">New collection</h2>
                <p class="text-textmuted text-body-reg font-sans mt-1">Loved by many, cherished by more — find your everyday favorites here.</p>
            </div>
            <?php get_template_part('app/Views/components/button', null, [
                'text'    => 'View all',
                'variant' => 'primary',
                'link'    => $hp_shop_url,
                'icon'    => '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>',
                'class'   => 'shrink-0',
            ]); ?>
        </div>

        <?php if (!empty($hp_featured)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Left: large hero product -->
            <?php if (isset($hp_featured[0])): ?>
            <div class="md:row-span-2">
                <?php
                $f0 = is_array($hp_featured[0]) ? hp_pancake_card_args($hp_featured[0], $hp_categories) : [
                    'image' => get_the_post_thumbnail_url($hp_featured[0]->ID,'large') ?: home_url("/bacera-img/{$hp_featured[0]->post_name}.jpg"),
                    'brand' => 'Bacera',
                    'name'  => $hp_featured[0]->post_title,
                    'price' => number_format((float)get_post_meta($hp_featured[0]->ID,'_price',true),0,',','.') . ' ₫',
                    'url'   => get_permalink($hp_featured[0]->ID),
                    'plp'   => true,
                ];
                // Override aspect ratio for large card — use inline wrapper trick
                $f0_url  = is_wp_error($f0['url'] ?? '') ? $hp_shop_url : ($f0['url'] ?? $hp_shop_url);
                $f0_img  = esc_url($f0['image'] ?? '');
                $f0_name = esc_html($f0['name'] ?? '');
                $f0_cat  = esc_html($f0['brand'] ?? '');
                $f0_prc  = esc_html($f0['price'] ?? '');
                $f0_disc = $f0['discount'] ?? '';
                ?>
                <a href="<?php echo esc_url($f0_url); ?>" class="group block relative rounded-2xl overflow-hidden bg-primary-100 h-full min-h-[400px]">
                    <img src="<?php echo $f0_img; ?>" alt="<?php echo $f0_name; ?>"
                         class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                    <?php if ($f0_disc): ?>
                    <div class="absolute top-3 left-3 z-10 bg-white/90 backdrop-blur-sm text-textmain text-[11px] font-semibold px-2.5 py-1 rounded-md"><?php echo esc_html($f0_disc); ?></div>
                    <?php endif; ?>
                    <div class="absolute bottom-0 inset-x-0 p-5 bg-gradient-to-t from-black/50 to-transparent">
                        <span class="block text-stone-300 text-[10px] font-medium tracking-widest uppercase mb-1"><?php echo $f0_cat; ?></span>
                        <h3 class="text-white text-lg font-serif font-normal leading-snug"><?php echo $f0_name; ?></h3>
                        <div class="text-stone-200 text-sm mt-1"><?php echo $f0_prc; ?></div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <!-- Right: 2x2 grid of smaller products -->
            <div class="grid grid-cols-2 gap-4">
                <?php for ($i=1; $i<=4; $i++): ?>
                    <?php if (!isset($hp_featured[$i])) continue; ?>
                    <?php
                    $fi_args = is_array($hp_featured[$i]) ? hp_pancake_card_args($hp_featured[$i], $hp_categories) : [
                        'image' => get_the_post_thumbnail_url($hp_featured[$i]->ID,'large') ?: home_url("/bacera-img/{$hp_featured[$i]->post_name}.jpg"),
                        'brand' => 'Bacera',
                        'name'  => $hp_featured[$i]->post_title,
                        'price' => number_format((float)get_post_meta($hp_featured[$i]->ID,'_price',true),0,',','.') . ' ₫',
                        'url'   => get_permalink($hp_featured[$i]->ID),
                        'plp'   => true,
                    ];
                    get_template_part('app/Views/components/product-card', null, $fi_args);
                    ?>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ════════════════ BEST SELLERS — tab + Swiper ═════════════════════════════ -->
<section id="best-sellers" class="section-pad bg-bgtheme border-t border-accent/20">
    <div class="bacera-container">
        <div class="flex flex-col md:flex-row items-start md:items-end justify-between gap-6 mb-6">
            <div>
                <span class="text-terracotta text-[11px] font-medium font-sans tracking-[0.2em] uppercase">Top picks</span>
                <h2 class="text-textmain text-4xl font-serif font-normal leading-10 mt-1">Best seller</h2>
            </div>
            <?php get_template_part('app/Views/components/button', null, [
                'text'    => 'View all',
                'variant' => 'primary',
                'link'    => $hp_shop_url,
                'icon'    => '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>',
                'class'   => 'shrink-0',
            ]); ?>
        </div>

        <!-- Category filter pills -->
        <div class="flex gap-2 flex-wrap mb-8" id="bs-tab-bar">
            <?php foreach ($hp_bs_tabs as $ti => $tab): ?>
            <button data-bs-tab="<?php echo $ti; ?>"
                    class="bs-tab-btn shrink-0 px-4 py-2 rounded-full text-[13px] font-medium font-sans transition-all duration-200
                           <?php echo $ti===0 ? 'bg-textmain text-white shadow-sm' : 'bg-stone-100 text-stone-500 hover:bg-stone-200 hover:text-textmain'; ?>">
                <?php echo esc_html($tab['label']); ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Swiper per tab (pre-rendered, shown/hidden) -->
        <?php foreach ($hp_bs_tabs as $ti => $tab): ?>
        <div class="bs-tab-panel <?php echo $ti===0?'':'hidden'; ?>" data-bs-panel="<?php echo $ti; ?>">
            <div class="relative">
                <button class="bs-prev-<?php echo $ti; ?> hidden md:flex absolute left-0 top-1/2 -translate-y-1/2 -translate-x-5 z-10 w-10 h-10 rounded-full bg-white border border-accent/20 shadow-md items-center justify-center hover:bg-primary-50 transition-colors">
                    <svg class="w-4 h-4 text-stone-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button class="bs-next-<?php echo $ti; ?> hidden md:flex absolute right-0 top-1/2 -translate-y-1/2 translate-x-5 z-10 w-10 h-10 rounded-full bg-white border border-accent/20 shadow-md items-center justify-center hover:bg-primary-50 transition-colors">
                    <svg class="w-4 h-4 text-stone-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div class="swiper bs-swiper-<?php echo $ti; ?>">
                    <div class="swiper-wrapper">
                        <?php
                        // Show products — if we have category-specific data, filter; otherwise show all
                        $bs_show = $hp_best_sellers;
                        foreach ($bs_show as $p):
                            $bsa = is_array($p) ? hp_pancake_card_args($p, $hp_categories) : [
                                'image' => get_the_post_thumbnail_url($p->ID,'large') ?: home_url("/bacera-img/{$p->post_name}.jpg"),
                                'brand' => 'Bacera',
                                'name'  => $p->post_title,
                                'price' => number_format((float)get_post_meta($p->ID,'_price',true),0,',','.') . ' ₫',
                                'url'   => get_permalink($p->ID),
                                'plp'   => true,
                            ];
                        ?>
                        <div class="swiper-slide">
                            <?php get_template_part('app/Views/components/product-card', null, $bsa); ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ════════════════ VIDEO BANNER ════════════════════════════════════════════ -->
<?php
// Detect video type
$hp_video_type    = '';  // 'youtube' | 'mp4' | ''
$hp_yt_video_id   = '';
$hp_mp4_url       = '';
$hp_video_att_id  = (int)get_option('bacera_homepage_video_attachment_id', 0);

if ($hp_video_url) {
    // YouTube detection
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/', $hp_video_url, $ym)) {
        $hp_video_type  = 'youtube';
        $hp_yt_video_id = $ym[1];
    } else {
        // Self-hosted / direct MP4
        $hp_video_type = 'mp4';
        $hp_mp4_url    = $hp_video_url;
    }
} elseif ($hp_video_att_id) {
    // From media library
    $hp_mp4_url    = wp_get_attachment_url($hp_video_att_id);
    $hp_video_type = $hp_mp4_url ? 'mp4' : '';
}
?>
<?php
// Ensure we have the video attachment URL if no URL is set
if (!$hp_video_url && $hp_video_att_id) {
    $hp_mp4_url = wp_get_attachment_url($hp_video_att_id) ?: '';
}
?>
<section id="video-banner" class="relative w-full overflow-hidden bg-textmain" style="min-height:75vh;">

    <!-- ── Background: Poster image (always shown as fallback) ── -->
    <div class="absolute inset-0" id="vb-bg">
        <?php if ($hp_video_thumb): ?>
        <img src="<?php echo esc_url($hp_video_thumb); ?>" alt="Bacera Video"
             class="absolute inset-0 w-full h-full object-cover" id="vb-poster"
             style="opacity:.6;">
        <?php endif; ?>

        <?php if ($hp_video_type === 'youtube'): ?>
        <!-- ── YouTube iframe: full-bleed trick ──
             We create a 16:9 box, then scale it so it always covers the container.
             pointer-events:none prevents any interaction with the iframe. -->
        <div id="vb-yt-wrap" class="absolute inset-0 overflow-hidden" style="pointer-events:none;">
            <div id="vb-player" style="
                position:absolute;
                top:50%; left:50%;
                transform:translate(-50%,-50%) scale(1.05);
                width:max(100%, calc(100vh * 16/9));
                height:max(100%, calc(100vw * 9/16));
                min-width:100%; min-height:100%;
            "></div>
        </div>

        <?php elseif ($hp_video_type === 'mp4'): ?>
        <!-- ── Self-hosted MP4: native object-cover ── -->
        <video id="vb-video"
               autoplay muted loop playsinline preload="metadata"
               poster="<?php echo esc_url($hp_video_thumb); ?>"
               style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.85;">
            <source src="<?php echo esc_url($hp_mp4_url); ?>" type="video/mp4">
        </video>
        <?php endif; ?>

        <!-- Multi-layer gradient for text legibility -->
        <div class="absolute inset-0" style="background:linear-gradient(to right, rgba(12,6,3,.92) 0%, rgba(12,6,3,.7) 40%, rgba(12,6,3,.25) 70%, rgba(0,0,0,.1) 100%);"></div>
        <div class="absolute inset-0" style="background:linear-gradient(to top, rgba(0,0,0,.6) 0%, transparent 50%);"></div>
    </div>

    <!-- ── Foreground content ── -->
    <div class="relative z-10 bacera-container flex flex-col justify-between" style="min-height:75vh; padding-top:5rem; padding-bottom:3rem;">

        <!-- Top: section label -->
        <div>
            <span class="inline-flex items-center gap-2 text-terracotta text-[11px] font-semibold font-sans tracking-[0.28em] uppercase">
                <span class="w-6 h-px bg-terracotta"></span>
                Watch &amp; Learn
            </span>
        </div>

        <!-- Middle: title -->
        <div class="max-w-2xl my-auto py-8">
            <h2 class="text-white text-4xl md:text-5xl lg:text-[3.2rem] font-serif font-normal leading-[1.15] tracking-tight">
                <?php echo esc_html($hp_video_title); ?>
            </h2>
            <?php if ($hp_video_desc): ?>
            <p class="mt-4 text-stone-300/80 text-[16px] font-sans leading-relaxed max-w-lg">
                <?php echo esc_html($hp_video_desc); ?>
            </p>
            <?php endif; ?>
        </div>

        <!-- Bottom: controls row -->
        <div class="flex items-center justify-between gap-4 border-t border-white/10 pt-5">
            <?php if ($hp_video_type): ?>
            <!-- Play/Pause button -->
            <button id="vb-playpause" onclick="vbTogglePlay()"
                    class="group flex items-center gap-4 text-neutral-200 hover:text-white transition-colors"
                    aria-label="Play / Pause video">
                <div class="relative w-14 h-14 rounded-full border border-white/25 flex items-center justify-center
                            group-hover:border-terracotta group-hover:bg-terracotta/15 transition-all duration-300"
                     id="vb-btn-ring">
                    <!-- ripple ring -->
                    <span class="absolute inset-0 rounded-full border border-white/15 group-hover:scale-125 group-hover:opacity-0 transition-all duration-700 scale-100 opacity-100"></span>
                    <svg id="vb-icon-play"  class="w-5 h-5 ml-0.5 hidden" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    <svg id="vb-icon-pause" class="w-5 h-5"             fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                </div>
                <div class="flex flex-col gap-0.5">
                    <span id="vb-btn-label" class="text-white text-[15px] font-medium font-sans">Pause video</span>
                    <span class="text-stone-400/80 text-[11.5px] font-sans">Xem quy trình làm gốm</span>
                </div>
            </button>
            <?php else: ?>
            <a href="<?php echo esc_url($hp_about_url); ?>"
               class="group flex items-center gap-4 text-neutral-200 hover:text-white transition-colors">
                <div class="w-14 h-14 rounded-full border border-white/25 flex items-center justify-center
                            group-hover:border-terracotta group-hover:bg-terracotta/15 transition-all duration-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
                <span class="text-white text-[15px] font-medium font-sans">Our Story</span>
            </a>
            <?php endif; ?>

            <a href="<?php echo esc_url($hp_about_url); ?>"
               class="group inline-flex items-center gap-1.5 text-stone-400/80 text-[13px] font-sans hover:text-white transition-all">
                Our Studio
                <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</section>

<script>
(function() {
    var VB_TYPE  = '<?php echo esc_js($hp_video_type); ?>';
    var VB_YT_ID = '<?php echo esc_js($hp_yt_video_id); ?>';
    var ytPlayer = null;
    var isPlaying = true; // autoplay = starts playing

    function updateBtn(playing) {
        isPlaying = playing;
        var lbl   = document.getElementById('vb-btn-label');
        var play  = document.getElementById('vb-icon-play');
        var pause = document.getElementById('vb-icon-pause');
        if (!lbl) return;
        lbl.textContent = playing ? 'Pause video' : 'Play video';
        if (play)  play.classList.toggle('hidden',  playing);
        if (pause) pause.classList.toggle('hidden', !playing);
    }

    window.vbTogglePlay = function() {
        if (VB_TYPE === 'youtube') {
            if (!ytPlayer) return;
            isPlaying ? ytPlayer.pauseVideo() : ytPlayer.playVideo();
        } else if (VB_TYPE === 'mp4') {
            var v = document.getElementById('vb-video');
            if (!v) return;
            isPlaying ? v.pause() : v.play().catch(function(){});
        }
    };

    <?php if ($hp_video_type === 'youtube'): ?>
    window.onYouTubeIframeAPIReady = function() {
        ytPlayer = new YT.Player('vb-player', {
            videoId: VB_YT_ID,
            playerVars: {
                autoplay:1, mute:1, loop:1, playlist:VB_YT_ID,
                controls:0, showinfo:0, rel:0, modestbranding:1,
                iv_load_policy:3, fs:0, playsinline:1, disablekb:1,
            },
            events: {
                onReady:      function(e) { e.target.playVideo(); updateBtn(true); },
                onStateChange:function(e) {
                    if (e.data === YT.PlayerState.PLAYING) updateBtn(true);
                    if (e.data === YT.PlayerState.PAUSED)  updateBtn(false);
                }
            }
        });
    };
    (function() {
        if (window.YT && window.YT.Player) { window.onYouTubeIframeAPIReady(); return; }
        var s = document.createElement('script');
        s.src = 'https://www.youtube.com/iframe_api';
        document.head.appendChild(s);
    })();
    <?php elseif ($hp_video_type === 'mp4'): ?>
    document.addEventListener('DOMContentLoaded', function() {
        var v = document.getElementById('vb-video');
        if (!v) return;
        v.addEventListener('play',  function() { updateBtn(true);  });
        v.addEventListener('pause', function() { updateBtn(false); });
        if (!v.paused) updateBtn(true);
    });
    <?php endif; ?>
})();
</script>




<!-- ════════════════ SHOP BY COLLECTION ════════════════════════════════════ -->

<section id="shop-by-collection" class="section-pad bg-bgtheme border-t border-accent/20">
    <div class="bacera-container">
        <div class="flex flex-col md:flex-row items-start md:items-end justify-between gap-6 mb-8">
            <div>
                <span class="text-terracotta text-[11px] font-medium font-sans tracking-[0.2em] uppercase">Browse</span>
                <h2 class="text-textmain text-4xl font-serif font-normal leading-10 mt-1">Shop by Collection</h2>
                <p class="text-textmuted text-body-reg font-sans mt-1 opacity-75">Each collection tells a unique story.</p>
            </div>
            <?php get_template_part('app/Views/components/button', null, [
                'text'    => 'View all',
                'variant' => 'primary',
                'link'    => $hp_shop_url,
                'icon'    => '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>',
                'class'   => 'shrink-0',
            ]); ?>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-4">
            <?php $col_spans_r1 = ['md:col-span-7','md:col-span-5']; ?>
            <?php foreach (array_slice($hp_collections,0,2) as $ci => $col): ?>
            <a href="<?php echo esc_url($col['url']); ?>" class="<?php echo $col_spans_r1[$ci] ?? 'md:col-span-6'; ?> group relative h-[320px] md:h-[480px] rounded-2xl overflow-hidden bg-primary-200 block">
                <img src="<?php echo esc_url($col['img']); ?>" alt="<?php echo esc_attr($col['title']); ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                <div class="absolute inset-0 bg-gradient-to-t from-primary-900/70 via-primary-900/10 to-transparent"></div>
                <div class="absolute bottom-6 left-6 right-6">
                    <h3 class="text-white text-2xl font-serif font-normal leading-snug"><?php echo esc_html($col['title']); ?></h3>
                    <div class="mt-3 inline-flex items-center gap-1.5 text-accent text-[13px] font-medium group-hover:gap-2.5 transition-all">
                        Shop now <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php if (count($hp_collections) >= 4): ?>
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <?php $col_spans_r2 = ['md:col-span-5','md:col-span-7']; ?>
            <?php foreach (array_slice($hp_collections,2,2) as $ci => $col): ?>
            <a href="<?php echo esc_url($col['url']); ?>" class="<?php echo $col_spans_r2[$ci] ?? 'md:col-span-6'; ?> group relative h-[320px] md:h-[480px] rounded-2xl overflow-hidden bg-primary-200 block">
                <img src="<?php echo esc_url($col['img']); ?>" alt="<?php echo esc_attr($col['title']); ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                <div class="absolute inset-0 bg-gradient-to-t from-primary-900/70 via-primary-900/10 to-transparent"></div>
                <div class="absolute bottom-6 left-6 right-6">
                    <h3 class="text-white text-2xl font-serif font-normal leading-snug"><?php echo esc_html($col['title']); ?></h3>
                    <div class="mt-3 inline-flex items-center gap-1.5 text-accent text-[13px] font-medium group-hover:gap-2.5 transition-all">
                        Shop now <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ════════════════ WORKSHOPS ═══════════════════════════════════════════════ -->
<section id="workshop" class="section-pad bg-textmain border-t border-accentdark">
    <div class="bacera-container">
        <div class="text-center mb-12">
            <span class="text-terracotta text-[11px] font-medium font-sans tracking-[0.2em] uppercase">Học cùng chúng tôi</span>
            <h2 class="text-white text-4xl font-serif font-normal leading-10 mt-3">Shape Your Story with Clay</h2>
            <p class="mt-2 text-stone-300 text-body-reg font-sans opacity-80 max-w-lg mx-auto">Step into our studio where time slows, hands listen, and clay breathes.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <?php foreach ($hp_workshops as $ws): ?>
            <?php get_template_part('app/Views/components/workshop-card', null, $ws); ?>
            <?php endforeach; ?>
        </div>
        <div class="mt-12 flex justify-center">
            <?php get_template_part('app/Views/components/button', null, [
                'text'    => 'Xem tất cả workshop',
                'variant' => 'primary',
                'link'    => $hp_workshop_url,
                'icon'    => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>',
            ]); ?>
        </div>
    </div>
</section>

<!-- ════════════════ ABOUT / OUR STORY ══════════════════════════════════════ -->
<?php
$_about_img = content_url('uploads/2026/04/about-us-1.png');
?>
<section id="about" class="section-pad bg-white border-t border-accent/20">
    <div class="bacera-container">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 xl:gap-20 items-center">

            <!-- ── IMAGE ── -->
            <div class="relative">
                <div class="rounded-2xl overflow-hidden shadow-xl">
                    <img src="<?php echo esc_url($_about_img); ?>"
                         alt="Bacera Studio — Crafting Ceramics"
                         class="w-full h-[480px] lg:h-[560px] object-cover"
                         loading="lazy">
                </div>
            </div>

            <!-- ── TEXT ── -->
            <div class="flex flex-col gap-7 pt-8 lg:pt-0">

                <div class="flex items-center gap-3">
                    <span class="w-8 h-px bg-terracotta"></span>
                    <span class="text-terracotta text-[11px] font-semibold font-sans tracking-[0.24em] uppercase">Our Story</span>
                </div>

                <h2 class="text-textmain text-3xl md:text-4xl font-serif font-normal leading-[1.25] -mt-2">
                    A memory, a breath,<br>
                    <em class="italic text-terracotta/80">a quiet story</em> waiting to be shaped.
                </h2>

                <div class="flex flex-col gap-4 text-stone-600 text-[15.5px] font-sans leading-relaxed -mt-2">
                    <p>Our hands move not to rush, but to listen — to the clay, to the wheel, and to the spaces in between. Each creation is shaped through patient hands, fired through ancient techniques, and glazed with emotions that words can hardly capture.</p>
                    <p>No two pieces are exactly alike, just as no two moments in life are ever the same.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <?php get_template_part('app/Views/components/button', null, [
                        'text'    => 'Our Story',
                        'variant' => 'primary',
                        'link'    => $hp_about_url,
                        'icon'    => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>',
                    ]); ?>
                    <?php get_template_part('app/Views/components/button', null, [
                        'text'    => 'Meet our team',
                        'variant' => 'outline',
                        'link'    => $hp_team_url,
                    ]); ?>
                </div>
            </div>

        </div>
    </div>
</section>



<!-- ════════════════ PARTNERS ════════════════════════════════════════════════ -->
<?php get_template_part('app/Views/components/presence-partners'); ?>

<!-- ════════════════ BLOG ════════════════════════════════════════════════════ -->
<?php if (!empty($hp_blog_posts)): ?>
<section id="blog" class="section-pad bg-bgtheme border-t border-accent/20">
    <div class="bacera-container">
        <div class="flex items-end justify-between mb-10">
            <div>
                <span class="text-terracotta text-[11px] font-medium font-sans tracking-[0.2em] uppercase">From the studio</span>
                <h2 class="text-textmain text-4xl font-serif font-normal leading-10 mt-2">Latest from Blog</h2>
            </div>
            <?php get_template_part('app/Views/components/button', null, [
                'text'    => 'View all',
                'variant' => 'outline',
                'link'    => $hp_blog_url,
                'icon'    => '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>',
            ]); ?>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($hp_blog_posts as $idx => $post):
                $thumb  = get_the_post_thumbnail_url($post->ID,'large') ?: 'https://images.unsplash.com/photo-1578749556568-bc2c40e68b61?auto=format&fit=crop&q=80&w=600';
                $cats   = get_the_category($post->ID);
                $cat_nm = !empty($cats) ? $cats[0]->name : 'Article';
                $excpt  = wp_trim_words(strip_tags($post->post_content), 18, '...');
                $rd_time = max(1, (int)ceil(str_word_count(strip_tags($post->post_content)) / 200));
            ?>
            <a href="<?php echo esc_url(get_permalink($post->ID)); ?>"
               class="group flex flex-col rounded-2xl overflow-hidden bg-white border border-accent/10 hover:border-accent/30 hover:shadow-lg transition-all duration-300 no-underline <?php echo $idx===0 ? 'md:col-span-1' : ''; ?>">
                <div class="relative overflow-hidden" style="aspect-ratio:16/9">
                    <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($post->post_title); ?>"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <span class="absolute top-3 left-3 px-2.5 py-1 rounded-md text-[10px] font-semibold font-sans uppercase tracking-wider bg-terracotta/90 text-white backdrop-blur-sm">
                        <?php echo esc_html($cat_nm); ?>
                    </span>
                </div>
                <div class="flex flex-col gap-2.5 p-5 flex-1">
                    <div class="flex items-center gap-2 text-stone-400 text-[11px] font-sans">
                        <span><?php echo get_the_date('M j, Y', $post->ID); ?></span>
                        <span>·</span>
                        <span><?php echo $rd_time; ?> min read</span>
                    </div>
                    <h3 class="text-textmain text-[17px] font-semibold font-sans leading-snug group-hover:text-terracotta transition-colors line-clamp-2"><?php echo esc_html($post->post_title); ?></h3>
                    <p class="text-stone-500 text-[13.5px] font-sans leading-relaxed line-clamp-2 flex-1"><?php echo esc_html($excpt); ?></p>
                    <div class="flex items-center gap-1 mt-1 text-terracotta text-[12.5px] font-medium group-hover:gap-2 transition-all">
                        Read more
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </a>
            <?php endforeach; wp_reset_postdata(); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ════════════════ TESTIMONIALS ════════════════════════════════════════════ -->
<?php
$hp_reviews = [
    ['text'=>'My girlfriend and I signed up for fun. We didn\'t expect the wheel to make us so quiet, so present. She shaped a bowl. I shaped something odd — but it felt like we made something real together.','name'=>'James & Laura','role'=>'Workshop Participants','stars'=>5,'img'=>'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?auto=format&fit=crop&q=80&w=700'],
    ['text'=>'I\'ve been collecting ceramics for years, but Bacera\'s pieces carry something different — a warmth that you feel the moment you hold them. Every cup tells a quiet story.','name'=>'Linh Nguyen','role'=>'Loyal Customer','stars'=>5,'img'=>'https://images.unsplash.com/photo-1610701596007-11502861dcfa?auto=format&fit=crop&q=80&w=700'],
    ['text'=>'We booked a team-building workshop and it was unlike anything we\'d tried before. Our team came away quieter, closer, and with beautiful pieces we\'d made ourselves.','name'=>'Minh Tuan','role'=>'Team Building Session','stars'=>5,'img'=>'https://images.unsplash.com/photo-1578749556568-bc2c40e68b61?auto=format&fit=crop&q=80&w=700'],
];
?>
<section id="testimonials" class="section-pad bg-white border-t border-accent/20 overflow-hidden">
    <div class="bacera-container">
        <div class="flex items-end justify-between mb-10">
            <div>
                <span class="text-terracotta text-[11px] font-medium font-sans tracking-[0.2em] uppercase">Kind words</span>
                <h2 class="text-textmain text-4xl font-serif font-normal leading-10 mt-2">What Customers Say</h2>
            </div>
            <div class="flex gap-2">
                <button class="testi-prev w-11 h-11 rounded-full bg-stone-100 hover:bg-primary-100 border border-accent/20 flex items-center justify-center transition-colors">
                    <svg class="w-5 h-5 text-stone-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button class="testi-next w-11 h-11 rounded-full bg-stone-100 hover:bg-primary-100 border border-accent/20 flex items-center justify-center transition-colors">
                    <svg class="w-5 h-5 text-stone-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
        <div class="swiper testi-swiper overflow-visible">
            <div class="swiper-wrapper">
                <?php foreach ($hp_reviews as $r): ?>
                <div class="swiper-slide !w-[300px] md:!w-[520px]">
                    <div class="flex flex-col rounded-3xl overflow-hidden border border-white/20 bg-white/50 backdrop-blur-xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_20px_40px_rgb(0,0,0,0.08)] transition-all duration-500 group">
                        <div class="relative h-44 md:h-56 overflow-hidden bg-primary-100 flex-shrink-0">
                            <img src="<?php echo esc_url($r['img']); ?>" alt="<?php echo esc_attr($r['name']); ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
                        </div>
                        <div class="p-6 md:p-8 flex flex-col gap-4 relative">
                            <span class="absolute -top-2 left-6 text-7xl text-terracotta/10 font-serif leading-none select-none">&ldquo;</span>
                            <div class="flex gap-0.5">
                                <?php for($s=0;$s<$r['stars'];$s++): ?>
                                <svg class="w-3.5 h-3.5 text-terracotta" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                <?php endfor; ?>
                            </div>
                            <p class="text-stone-600 text-[15px] font-sans leading-relaxed italic line-clamp-4">&ldquo;<?php echo esc_html($r['text']); ?>&rdquo;</p>
                            <div class="pt-4 border-t border-stone-100/50 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-terracotta/20 to-terracotta/5 border border-terracotta/20 flex items-center justify-center shrink-0">
                                    <span class="text-terracotta text-[15px] font-serif"><?php echo mb_substr($r['name'],0,1); ?></span>
                                </div>
                                <div>
                                    <span class="block text-textmain text-[14px] font-semibold font-sans leading-tight"><?php echo esc_html($r['name']); ?></span>
                                    <span class="block text-terracotta text-[11.5px] font-sans mt-0.5"><?php echo esc_html($r['role']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════ CTA STRIP ═══════════════════════════════════════════════ -->
<section class="bg-terracotta py-16">
    <div class="bacera-container text-center flex flex-col items-center gap-5">
        <span class="text-white/70 text-[11px] font-medium font-sans tracking-[0.2em] uppercase">Ready to start?</span>
        <h2 class="text-white text-3xl md:text-4xl font-serif font-normal leading-snug max-w-xl">Experience the quiet joy of making something with your hands</h2>
        <div class="flex flex-wrap justify-center gap-3 mt-1">
            <a href="<?php echo esc_url($hp_workshop_url); ?>" class="inline-flex items-center gap-2 px-7 py-4 bg-white text-terracotta text-[15px] font-semibold font-sans rounded-xl hover:bg-stone-100 transition-colors">Book a Workshop</a>
            <a href="<?php echo esc_url($hp_shop_url); ?>" class="inline-flex items-center gap-2 px-7 py-4 bg-white/15 text-white text-[15px] font-medium font-sans rounded-xl border border-white/30 hover:bg-white/25 transition-colors">Shop Online</a>
        </div>
    </div>
</section>

<!-- SEO block -->
<?php get_template_part('app/Views/components/seo-content', null, ['title' => 'Về Bacera']); ?>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Swiper === 'undefined') return;

    // ── New Arrivals slider ──────────────────────────────────────────────────
    new Swiper('.na-swiper', {
        slidesPerView: 2,
        spaceBetween: 16,
        grabCursor: true,
        navigation: { nextEl: '#na-next', prevEl: '#na-prev' },
        breakpoints: {
            0:   { slidesPerView: 2,   spaceBetween: 12 },
            480: { slidesPerView: 2.5, spaceBetween: 16 },
            768: { slidesPerView: 3,   spaceBetween: 20 },
            1024:{ slidesPerView: 4,   spaceBetween: 24 },
        }
    });

    // ── Best Sellers tab + slider ────────────────────────────────────────────
    const tabBtns  = document.querySelectorAll('.bs-tab-btn');
    const tabPanels = document.querySelectorAll('.bs-tab-panel');
    const bsSwipers = {};

    // Init swiper for each tab panel
    tabPanels.forEach(function(panel) {
        const idx = panel.dataset.bsPanel;
        bsSwipers[idx] = new Swiper('.bs-swiper-' + idx, {
            slidesPerView: 2,
            spaceBetween: 16,
            grabCursor: true,
            navigation: { nextEl: '.bs-next-' + idx, prevEl: '.bs-prev-' + idx },
            breakpoints: {
                0:   { slidesPerView: 2,   spaceBetween: 12 },
                480: { slidesPerView: 2.5, spaceBetween: 16 },
                768: { slidesPerView: 3,   spaceBetween: 20 },
                1024:{ slidesPerView: 3,   spaceBetween: 24 },
            }
        });
    });

    tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const idx = btn.dataset.bsTab;
            // Update button styles — pill active
            tabBtns.forEach(function(b) {
                b.classList.remove('bg-textmain','text-white','shadow-sm');
                b.classList.add('bg-stone-100','text-stone-500');
            });
            btn.classList.remove('bg-stone-100','text-stone-500');
            btn.classList.add('bg-textmain','text-white','shadow-sm');

            // Show/hide panels
            tabPanels.forEach(function(p) { p.classList.add('hidden'); });
            const target = document.querySelector('[data-bs-panel="' + idx + '"]');
            if (target) {
                target.classList.remove('hidden');
                if (bsSwipers[idx]) bsSwipers[idx].update();
            }
        });
    });

    // ── Testimonials slider ──────────────────────────────────────────────────
    new Swiper('.testi-swiper', {
        slidesPerView: 'auto',
        spaceBetween: 24,
        grabCursor: true,
        navigation: { nextEl: '.testi-next', prevEl: '.testi-prev' },
        breakpoints: {
            0:   { slidesPerView: 1,    spaceBetween: 16 },
            768: { slidesPerView: 'auto', spaceBetween: 24 }
        }
    });
});
</script>

<?php get_footer(); ?>
