<?php
/**
 * Template Name: Trang Chủ
 * Description: Homepage template for Bacera
 */

get_header();

// ─── Enqueue Swiper (chỉ cho trang này) ────────────────────────────────────
add_action( 'wp_footer', function() {
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">';
    echo '<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>';
}, 5 );

// ─── WooCommerce helpers ────────────────────────────────────────────────────
$has_woo = class_exists( 'WooCommerce' );

function hp_shop_url() {
    return $has_woo = class_exists('WooCommerce') ? get_permalink( wc_get_page_id('shop') ) : '#';
}

function hp_demo_products( $n = 6 ) {
    return array_slice([
        ['title'=>'Whisper Cup',  'cat'=>'Drinkware',    'price'=>'$28.00','sale'=>false,'img'=>'https://images.unsplash.com/photo-1610701596007-11502861dcfa?auto=format&fit=crop&q=80&w=600'],
        ['title'=>'Glow Bowl',   'cat'=>'Bowls',         'price'=>'$45.00','sale'=>true, 'img'=>'https://images.unsplash.com/photo-1578749556568-bc2c40e68b61?auto=format&fit=crop&q=80&w=600'],
        ['title'=>'Pebble Mug',  'cat'=>'Drinkware',     'price'=>'$32.00','sale'=>false,'img'=>'https://images.unsplash.com/photo-1610701596087-0b1a039735d9?auto=format&fit=crop&q=80&w=600'],
        ['title'=>'Mist Bowl',   'cat'=>'Bowls',         'price'=>'$55.00','sale'=>false,'img'=>'https://images.unsplash.com/photo-1590400516641-52481c67d302?auto=format&fit=crop&q=80&w=600'],
        ['title'=>'Stone Vase',  'cat'=>'Vases & Decor', 'price'=>'$78.00','sale'=>true, 'img'=>'https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=600'],
        ['title'=>'River Plate', 'cat'=>'Plates',        'price'=>'$38.00','sale'=>false,'img'=>'https://images.unsplash.com/photo-1506806732259-39c2d0268443?auto=format&fit=crop&q=80&w=600'],
    ], 0, $n);
}

function hp_product_card( $p, $size = 'md' ) {
    $h = $size === 'lg' ? 'h-[400px]' : 'h-72';
    if ( is_object($p) && method_exists($p, 'get_id') ) {
        $href  = get_permalink( $p->get_id() ) ?: '#';
        $img   = get_the_post_thumbnail_url( $p->get_id(), 'large' ) ?: 'https://images.unsplash.com/photo-1610701596007-11502861dcfa?auto=format&fit=crop&q=80&w=600';
        $terms = get_the_terms( $p->get_id(), 'product_cat' );
        $cat   = ($terms && !is_wp_error($terms)) ? implode(', ', wp_list_pluck($terms,'name')) : '';
        $name  = $p->get_name();
        $price = $p->get_price_html();
        $sale  = $p->is_on_sale();
    } else {
        $href  = '#'; $img = $p['img']; $cat = $p['cat'];
        $name  = $p['title']; $price = $p['price']; $sale = $p['sale'];
    }
    ?>
    <div class="group flex flex-col gap-3">
        <a href="<?php echo esc_url($href); ?>" class="block relative <?php echo $h; ?> rounded-xl overflow-hidden bg-primary-100">
            <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($name); ?>"
                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
            <?php if ($sale): ?>
            <span class="absolute top-3 left-3 bg-primary-800/80 backdrop-blur-sm text-neutral-100 text-xs font-medium font-sans px-2.5 py-1 rounded-full">Sale</span>
            <?php endif; ?>
            <div class="absolute inset-x-0 bottom-0 p-3 translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                <span class="block w-full py-3 bg-white/90 backdrop-blur-sm text-stone-800 text-sm font-medium font-sans text-center rounded-lg hover:bg-white transition-colors">
                    View Product
                </span>
            </div>
        </a>
        <div class="flex flex-col gap-0.5 px-1">
            <?php if ($cat): ?><span class="text-primary-600 text-xs font-medium font-sans uppercase tracking-wider"><?php echo esc_html($cat); ?></span><?php endif; ?>
            <a href="<?php echo esc_url($href); ?>" class="text-stone-800 text-[15px] font-medium font-sans leading-5 hover:text-accent-500 transition-colors"><?php echo esc_html($name); ?></a>
            <div class="text-stone-700 text-sm font-semibold font-sans"><?php echo $price; ?></div>
        </div>
    </div>
    <?php
}

$products      = $has_woo ? wc_get_products(['limit'=>6,'orderby'=>'date','order'=>'DESC','status'=>'publish']) : hp_demo_products(6);
$featured      = $has_woo ? wc_get_products(['limit'=>4,'orderby'=>'rand','status'=>'publish','featured'=>true]) : [];
if ($has_woo && empty($featured)) $featured = array_slice($products, 0, 4);
if (!$has_woo) $featured = hp_demo_products(4);
$best_sellers  = $has_woo ? wc_get_products(['limit'=>6,'orderby'=>'popularity','order'=>'DESC','status'=>'publish']) : hp_demo_products(6);
$shop_url      = hp_shop_url();
?>

<main id="primary" class="site-main bg-neutral-100">

<!-- ═══════════════════════════════════════════════════════
     1. HERO — Full-screen banner
════════════════════════════════════════════════════════ -->
<section id="hero" class="relative w-full min-h-[90vh] flex items-center overflow-hidden bg-primary-900">
    <div class="absolute inset-0">
        <img src="https://toan.host/wp-content/uploads/2026/03/BG.jpg"
             alt="Bacera Hero" class="w-full h-full object-cover opacity-60" loading="eager">
        <div class="absolute inset-0 bg-gradient-to-r from-primary-900/85 via-primary-900/40 to-transparent"></div>
    </div>

    <div class="relative z-10 w-full max-w-[1232px] mx-auto px-6 lg:px-0 py-32">
        <div class="max-w-xl flex flex-col gap-7 items-start">
            <span class="text-primary-300 text-xs font-medium font-sans tracking-[0.2em] uppercase">Bacera Pottery · Hà Nội · Since 2018</span>
            <h1 class="text-neutral-100 text-5xl md:text-6xl font-serif font-normal leading-tight">
                Discover timeless ceramics crafted by hands and heart
            </h1>
            <p class="text-primary-200 text-body-reg font-sans leading-relaxed max-w-md opacity-90">
                Each piece holds a story — ready to become part of yours. Handcrafted in Bat Trang, Vietnam.
            </p>
            <div class="flex flex-wrap gap-3 mt-2">
                <a href="<?php echo esc_url($shop_url); ?>"
                   class="inline-flex items-center gap-2 px-6 py-4 bg-accent-500 hover:bg-accent-600 text-white text-[15px] font-medium font-sans rounded-xl transition-all hover:-translate-y-0.5 hover:shadow-lg shadow-accent-500/30">
                    Shop our Collection
                </a>
                <a href="#workshop"
                   class="inline-flex items-center gap-2 px-6 py-4 bg-transparent text-neutral-100 text-[15px] font-medium font-sans rounded-xl border border-primary-400 hover:border-neutral-100 hover:bg-white/10 transition-all">
                    Join a Workshop
                </a>
            </div>
        </div>
    </div>

    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce text-primary-300">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     2. CATEGORY BAR — 8 danh mục
════════════════════════════════════════════════════════ -->
<?php
$cats_nav = [
    ['slug'=>'shop-all',    'label'=>'Shop All',     'path'=>'M4 6h16M4 12h16M4 18h16'],
    ['slug'=>'teapots',     'label'=>'Teapots',      'path'=>'M17 8h1a4 4 0 010 8h-1M3 8h14v9a4 4 0 01-4 4H7a4 4 0 01-4-4V8z'],
    ['slug'=>'drinkware',   'label'=>'Drinkware',    'path'=>'M9 17V7m0 0a3 3 0 106 0v10'],
    ['slug'=>'bowls',       'label'=>'Bowls',        'path'=>'M3 10c0 5.523 4.477 10 10 10s10-4.477 10-10H3z'],
    ['slug'=>'kitchen',     'label'=>'Kitchen',      'path'=>'M4 7h16M4 12h10M4 17h6'],
    ['slug'=>'plates',      'label'=>'Plates',       'path'=>'M12 2a10 10 0 100 20 10 10 0 000-20zm0 4a6 6 0 110 12A6 6 0 0112 6z'],
    ['slug'=>'vases-decor', 'label'=>'Vases & Decor','path'=>'M8 21h8m-4-4v4m-4-4a4 4 0 118 0H8zM9 3h6a2 2 0 012 2v8H7V5a2 2 0 012-2z'],
    ['slug'=>'storage',     'label'=>'Storage',      'path'=>'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4'],
];
?>
<section id="category-bar" class="w-full bg-white border-b border-stone-200">
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0 py-4">
        <div class="grid grid-cols-4 md:grid-cols-8 gap-2">
            <?php foreach ($cats_nav as $c):
                $term = $has_woo ? get_term_by('slug', $c['slug'], 'product_cat') : null;
                $url  = ($term && !is_wp_error($term)) ? get_term_link($term) : '#';
            ?>
            <a href="<?php echo esc_url($url); ?>"
               class="group flex flex-col items-center gap-2 px-2 py-3.5 rounded-xl border border-stone-200 hover:border-accent-400 hover:bg-neutral-100 transition-all duration-200 cursor-pointer">
                <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center text-primary-600 group-hover:bg-accent-500 group-hover:text-white transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $c['path']; ?>"/>
                    </svg>
                </div>
                <span class="text-stone-600 text-[12px] font-medium font-sans text-center leading-tight group-hover:text-accent-500 transition-colors"><?php echo esc_html($c['label']); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     3. NEW ARRIVALS — Grid 5 sản phẩm
════════════════════════════════════════════════════════ -->
<section id="new-arrivals" class="py-20 bg-neutral-100">
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0">
        <div class="flex flex-col md:flex-row items-start md:items-end justify-between gap-6 mb-10">
            <div class="flex flex-col gap-2">
                <h2 class="text-stone-800 text-4xl font-serif font-normal leading-10">New Arrivals</h2>
                <p class="text-stone-500 text-body-reg font-sans leading-relaxed max-w-md">Freshly crafted. New stories waiting to be part of your everyday rituals.</p>
            </div>
            <a href="<?php echo esc_url($shop_url); ?>"
               class="shrink-0 px-5 py-3.5 bg-accent-500 hover:bg-accent-600 text-white text-body-small font-medium font-sans rounded-xl transition-colors">
                View all
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <?php foreach (array_slice($products, 0, 5) as $i => $p):
                $span = ($i === 0) ? 'col-span-2 md:col-span-2' : 'col-span-1';
                echo '<div class="' . $span . '">';
                hp_product_card($p, 'md');
                echo '</div>';
            endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     4. NEW COLLECTION — 1 lớn + 3 nhỏ
════════════════════════════════════════════════════════ -->
<section id="new-collection" class="py-20 bg-white border-t border-stone-200">
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0">
        <div class="flex flex-col md:flex-row items-start md:items-end justify-between gap-6 mb-10">
            <div>
                <h2 class="text-stone-800 text-4xl font-serif font-normal leading-10">New Collection</h2>
                <p class="text-stone-500 text-body-reg font-sans mt-1">Loved by many, cherished by more — find your everyday favorites here.</p>
            </div>
            <a href="<?php echo esc_url($shop_url); ?>"
               class="shrink-0 px-5 py-3.5 bg-accent-500 hover:bg-accent-600 text-white text-body-small font-medium font-sans rounded-xl transition-colors">
                View all
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <!-- Large card -->
            <?php if (isset($featured[0])):
                $f0 = $featured[0];
                if (is_object($f0) && method_exists($f0,'get_id')) {
                    $f0h = get_permalink($f0->get_id()) ?: '#';
                    $f0i = get_the_post_thumbnail_url($f0->get_id(),'large') ?: 'https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=800';
                    $f0n = $f0->get_name();
                    $f0p = $f0->get_price_html();
                    $terms = get_the_terms($f0->get_id(),'product_cat');
                    $f0c = ($terms && !is_wp_error($terms)) ? implode(', ', wp_list_pluck($terms,'name')) : '';
                } else {
                    $f0h='#'; $f0i=$f0['img']; $f0n=$f0['title']; $f0p=$f0['price']; $f0c=$f0['cat'];
                }
            ?>
            <div class="md:col-span-5 group">
                <a href="<?php echo esc_url($f0h); ?>" class="block relative h-[440px] md:h-[680px] rounded-xl overflow-hidden bg-primary-200">
                    <img src="<?php echo esc_url($f0i); ?>" alt="<?php echo esc_attr($f0n); ?>"
                         class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                    <div class="absolute inset-0 bg-gradient-to-t from-primary-900/60 via-transparent to-transparent"></div>
                    <div class="absolute bottom-5 left-5 right-5">
                        <?php if ($f0c): ?><p class="text-primary-300 text-xs font-sans uppercase tracking-wider mb-1"><?php echo esc_html($f0c); ?></p><?php endif; ?>
                        <h3 class="text-neutral-100 text-xl font-medium font-sans leading-snug"><?php echo esc_html($f0n); ?></h3>
                        <div class="text-primary-200 text-sm font-sans mt-1"><?php echo $f0p; ?></div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <!-- 3 small cards -->
            <div class="md:col-span-7 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <?php for ($i=1; $i<=3; $i++):
                    if (!isset($featured[$i])) continue;
                    $fi = $featured[$i];
                    if (is_object($fi) && method_exists($fi,'get_id')) {
                        $fih = get_permalink($fi->get_id()) ?: '#';
                        $fii = get_the_post_thumbnail_url($fi->get_id(),'medium_large') ?: 'https://images.unsplash.com/photo-1610701596087-0b1a039735d9?auto=format&fit=crop&q=80&w=600';
                        $fin = $fi->get_name(); $fip = $fi->get_price_html(); $fis = $fi->is_on_sale();
                        $terms = get_the_terms($fi->get_id(),'product_cat');
                        $fic = ($terms && !is_wp_error($terms)) ? implode(', ', wp_list_pluck($terms,'name')) : '';
                    } else {
                        $fih='#'; $fii=$fi['img']; $fin=$fi['title']; $fip=$fi['price']; $fis=$fi['sale']; $fic=$fi['cat'];
                    }
                ?>
                <div class="group flex flex-col gap-3">
                    <a href="<?php echo esc_url($fih); ?>" class="block relative h-52 md:h-[220px] rounded-xl overflow-hidden bg-primary-100">
                        <img src="<?php echo esc_url($fii); ?>" alt="<?php echo esc_attr($fin); ?>"
                             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                        <?php if ($fis): ?><span class="absolute top-2 left-2 bg-primary-800/70 text-neutral-100 text-xs font-sans px-2 py-0.5 rounded-full">Sale</span><?php endif; ?>
                    </a>
                    <div class="flex flex-col gap-0.5 px-1">
                        <?php if ($fic): ?><p class="text-primary-600 text-xs font-sans uppercase tracking-wider"><?php echo esc_html($fic); ?></p><?php endif; ?>
                        <a href="<?php echo esc_url($fih); ?>" class="text-stone-800 text-[15px] font-medium font-sans hover:text-accent-500 transition-colors"><?php echo esc_html($fin); ?></a>
                        <div class="text-stone-700 text-sm font-semibold font-sans"><?php echo $fip; ?></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     5. BEST SELLER — Tab filter + 3 cards
════════════════════════════════════════════════════════ -->
<?php
$bs_tabs = ['Begin Slowly', 'Sip and Pause', 'Tables That Linger', 'Hold a Quiet Space', 'Gift a Moment'];
?>
<section id="best-sellers" class="py-20 bg-neutral-100 border-t border-stone-200" x-data="{ activeTab: 0 }">
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0">
        <div class="flex flex-col md:flex-row items-start md:items-end justify-between gap-6 mb-6">
            <h2 class="text-stone-800 text-4xl font-serif font-normal leading-10">Best Seller</h2>
            <a href="<?php echo esc_url($shop_url); ?>"
               class="shrink-0 px-5 py-3.5 bg-accent-500 hover:bg-accent-600 text-white text-body-small font-medium font-sans rounded-xl transition-colors">
                View all
            </a>
        </div>

        <!-- Tabs -->
        <div class="flex gap-6 border-b border-stone-300 mb-8 overflow-x-auto scrollbar-hide">
            <?php foreach ($bs_tabs as $ti => $tl): ?>
            <button @click="activeTab = <?php echo $ti; ?>"
                    class="shrink-0 pb-3.5 text-[15px] font-sans transition-all"
                    :class="activeTab === <?php echo $ti; ?> ? 'font-semibold text-stone-800 border-b-2 border-stone-800' : 'font-normal text-stone-400 hover:text-stone-600'">
                <?php echo esc_html($tl); ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Products -->
        <?php foreach ($bs_tabs as $ti => $tl): ?>
        <div x-show="activeTab === <?php echo $ti; ?>" x-transition:enter="transition-opacity duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
            <?php
            $offset = ($ti % 2 === 0) ? 0 : 3;
            foreach (array_slice($best_sellers, $offset, 3) as $p) hp_product_card($p, 'lg');
            ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     6. VIDEO BANNER — Cinematic dark section
════════════════════════════════════════════════════════ -->
<section id="video-banner" class="relative w-full min-h-[75vh] flex items-end bg-primary-900 overflow-hidden"
         x-data="{ open: false }">
    <div class="absolute inset-0">
        <img src="https://images.unsplash.com/photo-1506806732259-39c2d0268443?auto=format&fit=crop&q=80&w=1920"
             alt="Video Banner" class="w-full h-full object-cover opacity-50">
        <div class="absolute inset-0 bg-gradient-to-t from-primary-950 via-primary-900/40 to-transparent"></div>
    </div>

    <div class="relative z-10 max-w-[1232px] mx-auto px-6 lg:px-0 pb-16 w-full">
        <div class="max-w-xl mb-12">
            <h2 class="text-neutral-100 text-4xl md:text-5xl font-serif font-normal leading-snug">
                The Art of Patience:<br>Shaping Clay by Hand
            </h2>
            <p class="mt-3 text-primary-300 text-body-reg font-sans leading-relaxed opacity-80">
                In every spin of the wheel and every breath of fire, a quiet story takes shape.
            </p>
        </div>

        <div class="flex items-center justify-between border-t border-white/20 pt-5">
            <button @click="open = true"
                    class="group flex items-center gap-3 text-neutral-200 hover:text-white transition-colors">
                <div class="w-12 h-12 rounded-full border border-primary-400 flex items-center justify-center group-hover:border-white group-hover:bg-white/10 transition-all">
                    <svg class="w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </div>
                <span class="text-body-small font-sans">Play video</span>
            </button>
            <div class="flex gap-3 items-center">
                <div class="w-20 h-0.5 bg-white rounded-full"></div>
                <div class="w-36 h-px bg-white/20 rounded-full"></div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div x-show="open" x-transition.opacity
         class="fixed inset-0 z-[200] bg-black/90 flex items-center justify-center" style="display:none">
        <button @click="open = false" class="absolute top-6 right-6 text-white/70 hover:text-white">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div class="w-full max-w-4xl aspect-video bg-primary-900 rounded-2xl flex items-center justify-center">
            <p class="text-primary-400 text-sm font-sans">Video placeholder — thay bằng YouTube embed của bạn</p>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     7. SHOP BY COLLECTION — 2×2 asymmetric grid
════════════════════════════════════════════════════════ -->
<?php
$cols = [
    ['title'=>'Whispers of Clay',    'sub'=>'Soft forms. Gentle hues. Pieces shaped by quiet hands.',         'img'=>'https://images.unsplash.com/photo-1578749556568-bc2c40e68b61?auto=format&fit=crop&q=80&w=900', 'url'=>'#', 'span'=>'md:col-span-7'],
    ['title'=>'Shared Gatherings',   'sub'=>'Objects crafted for rituals of slowing down.',                   'img'=>'https://images.unsplash.com/photo-1610701596007-11502861dcfa?auto=format&fit=crop&q=80&w=700', 'url'=>'#', 'span'=>'md:col-span-5'],
    ['title'=>'Moments in Stillness','sub'=>'Raw textures, deep glazes — the touch of earth and fire.',       'img'=>'https://images.unsplash.com/photo-1590400516641-52481c67d302?auto=format&fit=crop&q=80&w=700', 'url'=>'#', 'span'=>'md:col-span-5'],
    ['title'=>'Quiet Mornings',      'sub'=>'Ceramics for your slowest mornings — where thoughts wander.',    'img'=>'https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=900', 'url'=>'#', 'span'=>'md:col-span-7'],
];
?>
<section id="shop-by-collection" class="py-20 bg-neutral-100 border-t border-stone-200">
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0">
        <div class="flex flex-col md:flex-row items-start md:items-end justify-between gap-6 mb-10">
            <div>
                <h2 class="text-stone-800 text-4xl font-serif font-normal leading-10">Shop by Collection</h2>
                <p class="text-stone-500 text-body-reg font-sans mt-1 opacity-75">Loved by many, cherished by more — find your everyday favorites here.</p>
            </div>
            <a href="<?php echo esc_url($shop_url); ?>"
               class="shrink-0 px-5 py-3.5 bg-accent-500 hover:bg-accent-600 text-white text-body-small font-medium font-sans rounded-xl transition-colors">
                View all
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-4">
            <?php foreach (array_slice($cols,0,2) as $col): ?>
            <a href="<?php echo esc_url($col['url']); ?>"
               class="<?php echo $col['span']; ?> group relative h-[360px] md:h-[500px] rounded-2xl overflow-hidden bg-primary-200">
                <img src="<?php echo esc_url($col['img']); ?>" alt="<?php echo esc_attr($col['title']); ?>"
                     class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                <div class="absolute inset-0 bg-gradient-to-t from-primary-900/65 to-transparent"></div>
                <div class="absolute bottom-6 left-6 right-6">
                    <h3 class="text-neutral-100 text-2xl font-serif font-normal leading-snug"><?php echo esc_html($col['title']); ?></h3>
                    <p class="text-primary-200 text-sm font-sans opacity-80 mt-1 max-w-xs leading-5"><?php echo esc_html($col['sub']); ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <?php foreach (array_slice($cols,2,2) as $col): ?>
            <a href="<?php echo esc_url($col['url']); ?>"
               class="<?php echo $col['span']; ?> group relative h-[360px] md:h-[500px] rounded-2xl overflow-hidden bg-primary-200">
                <img src="<?php echo esc_url($col['img']); ?>" alt="<?php echo esc_attr($col['title']); ?>"
                     class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                <div class="absolute inset-0 bg-gradient-to-t from-primary-900/65 to-transparent"></div>
                <div class="absolute bottom-6 left-6 right-6">
                    <h3 class="text-neutral-100 text-2xl font-serif font-normal leading-snug"><?php echo esc_html($col['title']); ?></h3>
                    <p class="text-primary-200 text-sm font-sans opacity-80 mt-1 max-w-xs leading-5"><?php echo esc_html($col['sub']); ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     8. WORKSHOP — 4 cards trên nền tối
════════════════════════════════════════════════════════ -->
<?php
$workshops = [
    ['name'=>'Pottery Wheel Throwing','desc'=>'A peaceful, hands-on journey for beginners and curious minds.','price'=>'From 950,000 VND/Person','date'=>'Day: 13/16 · Noon: 1/16','img'=>'https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=600'],
    ['name'=>'Hand-building Pottery', 'desc'=>'Shape, carve, and create your own ceramic piece by hand.',     'price'=>'From 950,000 VND/Person','date'=>'Day: 13/16 · Noon: 1/16','img'=>'https://images.unsplash.com/photo-1610701596087-0b1a039735d9?auto=format&fit=crop&q=80&w=600'],
    ['name'=>'Hanoi School Class',    'desc'=>'Special courses for students wanting deeper techniques.',       'price'=>'Contact us',             'date'=>'Book Your School Visit', 'img'=>'https://images.unsplash.com/photo-1506806732259-39c2d0268443?auto=format&fit=crop&q=80&w=600'],
    ['name'=>'Team-building Pottery', 'desc'=>'A unique bonding experience for your team to create together.','price'=>'Contact us',             'date'=>'Organise a Group Event', 'img'=>'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?auto=format&fit=crop&q=80&w=600'],
];
?>
<section id="workshop" class="py-20 bg-primary-900 border-t border-primary-800">
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0">
        <div class="text-center mb-12">
            <h2 class="text-neutral-100 text-4xl font-serif font-normal leading-10">Shape Your Story with Clay</h2>
            <p class="mt-2 text-primary-300 text-body-reg font-sans opacity-80">Step into a space where time slows, hands listen, and clay breathes.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($workshops as $ws): ?>
            <div class="group flex flex-col rounded-2xl overflow-hidden bg-primary-800/60 border border-white/10 hover:border-white/25 transition-all hover:-translate-y-1 duration-300">
                <div class="relative h-48 overflow-hidden">
                    <img src="<?php echo esc_url($ws['img']); ?>" alt="<?php echo esc_attr($ws['name']); ?>"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                </div>
                <div class="flex flex-col flex-1 gap-4 p-5">
                    <div>
                        <h3 class="text-neutral-100 text-[15px] font-medium font-sans leading-5 mb-1"><?php echo esc_html($ws['name']); ?></h3>
                        <p class="text-primary-400 text-sm font-sans leading-5 opacity-80"><?php echo esc_html($ws['desc']); ?></p>
                    </div>
                    <div class="h-px bg-white/10 w-2/3"></div>
                    <div class="flex items-center justify-between mt-auto">
                        <div>
                            <p class="text-neutral-200 text-[13px] font-medium font-sans"><?php echo esc_html($ws['price']); ?></p>
                            <p class="text-primary-500 text-xs font-sans mt-0.5"><?php echo esc_html($ws['date']); ?></p>
                        </div>
                        <div class="w-9 h-9 rounded-full border border-primary-500 flex items-center justify-center group-hover:border-accent-400 group-hover:bg-accent-500 transition-all">
                            <svg class="w-4 h-4 text-primary-400 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-12 flex justify-center">
            <a href="#" class="px-7 py-4 bg-accent-500 hover:bg-accent-600 text-white text-[15px] font-medium font-sans rounded-xl transition-all hover:-translate-y-0.5 hover:shadow-lg shadow-accent-500/30">
                Join a Workshop
            </a>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     9. ABOUT SNIPPET — Split image + text
════════════════════════════════════════════════════════ -->
<section id="about" class="py-20 bg-white border-t border-stone-200">
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div class="relative">
                <img src="https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=900"
                     alt="Bacera Studio" class="w-full h-[500px] lg:h-[620px] object-cover rounded-2xl" loading="lazy">
                <div class="absolute -top-5 -right-5 w-24 h-24 bg-accent-500 rounded-full flex flex-col items-center justify-center shadow-xl shadow-accent-500/30">
                    <span class="text-white text-[10px] font-sans text-center leading-tight">Since</span>
                    <strong class="text-white text-xl font-serif font-normal">2018</strong>
                </div>
            </div>

            <div class="flex flex-col gap-7">
                <div class="flex flex-col gap-3">
                    <span class="text-primary-500 text-xs font-medium font-sans tracking-[0.2em] uppercase">Our Story</span>
                    <h2 class="text-stone-800 text-4xl font-serif font-normal leading-snug">
                        A memory, a breath, a quiet story waiting to be shaped.
                    </h2>
                    <p class="text-stone-600 text-body-reg font-sans leading-relaxed opacity-80">
                        Our hands move not to rush, but to listen: to the clay, to the wheel, and to the spaces in between. Each creation is shaped through patient hands, fired through ancient techniques, and glazed with emotions that words can hardly capture.
                    </p>
                    <p class="text-stone-600 text-body-reg font-sans leading-relaxed opacity-80">
                        No two pieces are exactly alike — just as no two moments in life are ever the same.
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-4 py-5 border-y border-stone-200">
                    <?php foreach ([['6+','Years of craft'],['500+','Pieces made'],['1200+','Happy customers']] as $stat): ?>
                    <div class="flex flex-col gap-1">
                        <span class="text-stone-800 text-3xl font-serif font-normal"><?php echo $stat[0]; ?></span>
                        <span class="text-stone-500 text-sm font-sans"><?php echo $stat[1]; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <a href="/about"
                   class="self-start px-6 py-4 bg-primary-700 hover:bg-primary-800 text-neutral-100 text-[15px] font-medium font-sans rounded-xl transition-colors">
                    About us
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     10. BLOG — 3 bài viết mới nhất
════════════════════════════════════════════════════════ -->
<?php
$blog_posts = get_posts(['numberposts'=>3, 'post_status'=>'publish']);
?>
<?php if (!empty($blog_posts)): ?>
<section id="blog" class="py-20 bg-neutral-100 border-t border-stone-200">
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0">
        <div class="flex items-end justify-between mb-10">
            <h2 class="text-stone-800 text-4xl font-serif font-normal leading-10">Blog</h2>
            <a href="/blog"
               class="px-5 py-3.5 rounded-xl border border-stone-300 text-stone-500 text-body-small font-medium font-sans hover:border-accent-400 hover:text-accent-500 transition-colors">
                View more
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-7">
            <?php foreach ($blog_posts as $post):
                $thumb = get_the_post_thumbnail_url($post->ID,'large') ?: 'https://images.unsplash.com/photo-1578749556568-bc2c40e68b61?auto=format&fit=crop&q=80&w=600';
                $cats  = get_the_category($post->ID);
                $catn  = !empty($cats) ? $cats[0]->name : 'Article';
            ?>
            <article class="group flex flex-col gap-4">
                <a href="<?php echo esc_url(get_permalink($post->ID)); ?>"
                   class="block relative h-60 rounded-2xl overflow-hidden bg-primary-100">
                    <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($post->post_title); ?>"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                </a>
                <div class="flex flex-col gap-1.5 px-1">
                    <span class="text-primary-500 text-xs font-medium font-sans uppercase tracking-wider"><?php echo esc_html($catn); ?></span>
                    <a href="<?php echo esc_url(get_permalink($post->ID)); ?>"
                       class="text-stone-800 text-[15px] font-medium font-sans leading-5 hover:text-accent-500 transition-colors line-clamp-2">
                        <?php echo esc_html($post->post_title); ?>
                    </a>
                    <p class="text-stone-500 text-sm font-sans leading-5 line-clamp-2">
                        <?php echo wp_trim_words($post->post_content, 18, '...'); ?>
                    </p>
                </div>
            </article>
            <?php endforeach; wp_reset_postdata(); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════
     11. TESTIMONIALS — Swiper carousel
════════════════════════════════════════════════════════ -->
<?php
$reviews = [
    ['text'=>'My girlfriend and I signed up for fun. We didn\'t expect the wheel to make us so quiet, so present. She shaped a bowl. I shaped something odd — but it felt like we made something real together.','name'=>'James & Laura','role'=>'Workshop Participants','stars'=>5,'img'=>'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?auto=format&fit=crop&q=80&w=700'],
    ['text'=>'I\'ve been collecting ceramics for years, but Bacera\'s pieces carry something different — a warmth that you feel the moment you hold them. Every cup tells a quiet story.','name'=>'Linh Nguyen','role'=>'Loyal Customer','stars'=>5,'img'=>'https://images.unsplash.com/photo-1610701596007-11502861dcfa?auto=format&fit=crop&q=80&w=700'],
    ['text'=>'We booked a team-building workshop and it was unlike anything we\'d tried before. Our team came away quieter, closer, and with beautiful pieces we\'d made ourselves.','name'=>'Minh Tuan, Marketing Team','role'=>'Team Building Session','stars'=>5,'img'=>'https://images.unsplash.com/photo-1578749556568-bc2c40e68b61?auto=format&fit=crop&q=80&w=700'],
];
?>
<section id="testimonials" class="py-20 bg-white border-t border-stone-200 overflow-hidden">
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0">
        <div class="flex items-end justify-between mb-10">
            <h2 class="text-stone-800 text-4xl font-serif font-normal leading-10">Testimonials</h2>
            <div class="flex gap-2">
                <button class="testi-prev w-11 h-11 rounded-full bg-stone-100 hover:bg-primary-100 border border-stone-200 flex items-center justify-center transition-colors">
                    <svg class="w-5 h-5 text-stone-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button class="testi-next w-11 h-11 rounded-full bg-stone-100 hover:bg-primary-100 border border-stone-200 flex items-center justify-center transition-colors">
                    <svg class="w-5 h-5 text-stone-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>

        <div class="swiper testi-swiper overflow-visible">
            <div class="swiper-wrapper">
                <?php foreach ($reviews as $r): ?>
                <div class="swiper-slide !w-[300px] md:!w-[580px]">
                    <div class="flex flex-col rounded-2xl overflow-hidden border border-stone-200 bg-white shadow-sm">
                        <div class="h-52 md:h-72 overflow-hidden bg-primary-100">
                            <img src="<?php echo esc_url($r['img']); ?>" alt="<?php echo esc_attr($r['name']); ?>"
                                 class="w-full h-full object-cover" loading="lazy">
                        </div>
                        <div class="p-6 md:p-8 flex flex-col gap-4">
                            <div class="flex gap-1">
                                <?php for ($s=0; $s<$r['stars']; $s++): ?>
                                <svg class="w-4 h-4 text-accent-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                <?php endfor; ?>
                            </div>
                            <p class="text-stone-700 text-body-reg font-sans leading-relaxed italic">"<?php echo esc_html($r['text']); ?>"</p>
                            <div class="pt-3 border-t border-stone-100 flex flex-col gap-0.5">
                                <span class="text-stone-800 text-[15px] font-medium font-sans"><?php echo esc_html($r['name']); ?></span>
                                <span class="text-primary-500 text-sm font-sans"><?php echo esc_html($r['role']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

</main>

<!-- ═══════════════════════════════════════════════════════
     JS — Swiper init (runs after Swiper CDN loaded in footer)
════════════════════════════════════════════════════════ -->
<script>
document.addEventListener( 'DOMContentLoaded', function() {
    if ( typeof Swiper !== 'undefined' ) {
        new Swiper( '.testi-swiper', {
            slidesPerView: 'auto',
            spaceBetween: 24,
            grabCursor: true,
            navigation: { nextEl: '.testi-next', prevEl: '.testi-prev' },
            breakpoints: { 0: { slidesPerView: 1, spaceBetween: 16 }, 768: { slidesPerView: 'auto', spaceBetween: 24 } }
        });
    }
});
</script>

<?php get_footer(); ?>
