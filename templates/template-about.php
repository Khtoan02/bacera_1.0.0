<?php
/**
 * Template Name: Về Chúng Tôi
 * Description: About Us - Symphony of Clay
 */

get_header();

// Fetch reviews dynamically if available
global $wpdb;
$reviews_db = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}bacera_workshop_reviews ORDER BY created_at DESC LIMIT 6",
    ARRAY_A
) ?: [];

$fallback_reviews = [
    ['quote' => "We didn't expect the wheel to move so quickly. Holding the misshapen bowl I created... those flaws made it perfect because it's our shared memory.", 'name' => 'The samill KlaiLino', 'course' => 'Experience Course Student', 'img' => 'https://bacera.demo/wp-content/uploads/2026/04/feedback-about-us-1.png'],
    ['quote' => "Buying a teapot from Bacera transformed my afternoon tea into a little ceremony. These pieces don't just hold food — they hold feelings.", 'name' => 'Minh Tri Nguyen', 'course' => 'Ceramics Collector', 'img' => 'https://bacera.demo/wp-content/uploads/2026/04/feedback-about-us-2.png'],
    ['quote' => "Bacera's tiny glass cups, every sip tells a story. Beautiful craftsmanship that definitely elevated the memory and slowed down life.", 'name' => 'Hoang Lan', 'course' => 'Loyal Customer', 'img' => 'https://bacera.demo/wp-content/uploads/2026/04/feedback-about-us-3.png']
];
$reviews = !empty($reviews_db) ? $reviews_db : $fallback_reviews;

// Setup Hero and Service Images with Post Meta & Local Fallbacks
$hero_img       = get_post_meta(get_the_ID(), '_about_hero_image', true) ?: 'https://bacera.demo/wp-content/uploads/2026/04/about-us-3.png';
$collection_img = get_post_meta(get_the_ID(), '_about_service1_img', true) ?: 'https://bacera.demo/wp-content/uploads/2026/04/about-us-1.png';
$workshop_img   = get_post_meta(get_the_ID(), '_about_service2_img', true) ?: 'https://bacera.demo/wp-content/uploads/2026/04/about-us-2.png';

// Setup URLs
$url_workshop = class_exists('WooCommerce') ? get_post_type_archive_link('workshop') : home_url('/workshop/');
$url_shop = class_exists('WooCommerce') ? get_permalink(wc_get_page_id('shop')) : home_url('/shop/');
if (!$url_workshop) $url_workshop = home_url('/workshop/');

?>

<!-- Embed Swiper for Sliders -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<!-- Embed Tailwind runtime specifically for this template's unique design system -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    bgtheme: '#f8f7f3', 
                    textmain: '#3d2f26', 
                    textmuted: '#6b5344', 
                    accent: '#c0a28e', 
                    accentdark: '#8d6a54', 
                    terracotta: '#d95f47' 
                },
                fontFamily: {
                    serif: ['"Gowun Batang"', 'serif'],
                    sans: ['"Bricolage Grotesque"', 'sans-serif'],
                }
            }
        }
    }
</script>

<style>
    /* Hide scrollbar for review slider */
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

    /* Noise grain effect */
    .bg-texture {
        background-color: #F7F6F0;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.03'/%3E%3C/svg%3E");
    }

    /* Vertical Text */
    .vertical-text {
        writing-mode: vertical-rl;
        text-orientation: mixed;
    }

    /* Divider Art */
    .divider-art {
        background-image: linear-gradient(to right, #D0BCA0 50%, transparent 50%);
        background-size: 10px 1px;
        background-repeat: repeat-x;
    }
</style>

<div class="font-sans antialiased bg-texture text-[#2C2824] selection:bg-accentdark selection:text-white w-full overflow-hidden">

    <!-- 1. HERO SECTION -->
    <section class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-12 pt-12 pb-24 relative">
        
        <!-- Decorative Vertical Text -->
        <div class="absolute left-6 top-32 hidden xl:block z-10">
            <p class="vertical-text font-sans text-[10px] tracking-[0.4em] text-textmuted uppercase">
                Est. 2024 — Thai Unika Co., Ltd.
            </p>
        </div>

        <div class="flex flex-col lg:flex-row items-center gap-10 lg:gap-20 mt-10">
            <!-- Text Content -->
            <div class="w-full lg:w-5/12 relative z-10 lg:pl-12">
                <div class="flex items-center gap-4 mb-8">
                    <span class="w-12 h-[1px] bg-accentdark"></span>
                    <span class="text-[10px] uppercase tracking-[0.3em] text-accentdark font-medium">A Symphony of Clay</span>
                </div>
                
                <h1 class="font-serif text-5xl lg:text-7xl leading-[1.1] text-textmain mb-8 relative">
                    Where Earth <br>
                    <span class="italic text-terracotta">Blooms.</span>
                </h1>
                
                <div class="space-y-6 text-sm lg:text-[15px] leading-[1.8] text-textmuted font-light pl-4 border-l border-accent/30">
                    <p>
                        Hidden amidst the bustling heart of Hanoi, Bacera is a tranquil sanctuary where you can touch the breath of nature. Here, inanimate clay is awakened by passion and artistic resonance.
                    </p>
                    <p>
                        We believe that the moment your hands glide across the potter's wheel, all worries give way to a profound inner dialogue. Every crease, every glaze drip is unique, telling the story of its creator.
                    </p>
                </div>

                <div class="mt-12 flex gap-6 items-center">
                    <a href="<?php echo esc_url($url_workshop); ?>" class="group flex items-center gap-3 text-xs uppercase tracking-[0.2em] text-textmain hover:text-terracotta transition-colors">
                        <span class="w-10 h-10 rounded-full border border-accent flex items-center justify-center group-hover:border-terracotta transition-colors">
                            <svg class="w-4 h-4 transform group-hover:translate-y-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                        </span>
                        Explore Workshops
                    </a>
                </div>
            </div>
            
            <!-- Image Section -->
            <div class="w-full lg:w-7/12 relative">
                <div class="absolute -right-10 -bottom-10 w-full h-full bg-[#EBE7DF] rounded-tl-[100px] rounded-br-[100px] -z-10 opacity-70"></div>
                
                <div class="relative w-full aspect-[4/3] lg:aspect-[16/11] overflow-hidden rounded-tl-[100px] rounded-br-[100px] shadow-2xl bg-[#EBE7DF]">
                    <img src="<?php echo esc_url($hero_img); ?>" 
                         alt="Ceramic Artisan" 
                         class="w-full h-full object-cover transition-transform duration-[2s] hover:scale-105">
                </div>

                <!-- Quote Box -->
                <div class="absolute -bottom-8 -left-8 lg:-bottom-12 lg:left-12 bg-white/90 backdrop-blur-md p-8 lg:p-10 rounded-tr-[40px] rounded-bl-[40px] shadow-xl max-w-sm border border-white/20">
                    <svg class="w-8 h-8 text-accent/50 mb-4" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z" /></svg>
                    <p class="font-serif italic text-lg lg:text-xl text-textmain leading-relaxed">
                        "Ceramics are not merely shapes. They are the solidification of time and emotion."
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Art Divider -->
    <div class="w-full max-w-7xl mx-auto px-8 py-12">
        <div class="w-full h-[1px] divider-art opacity-60"></div>
    </div>

    <!-- 2. CRAFTING PHILOSOPHY -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="flex flex-col md:flex-row justify-between items-end mb-20 gap-8">
            <h2 class="font-serif text-4xl lg:text-5xl text-textmain leading-tight">
                Crafting <br>
                <span class="italic text-accentdark">Philosophy</span>
            </h2>
            <p class="text-sm font-light text-textmuted max-w-md border-l border-accent pl-6">
                Four elemental forces breathe life into every creation at Bacera. No rush, only serenity and sublimation.
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 lg:gap-12">
            <!-- Earth -->
            <div class="group relative pt-8 border-t border-accent/40 hover:border-terracotta transition-colors duration-500">
                <span class="absolute top-0 right-0 text-5xl font-serif text-accent/20 font-light -mt-[24px] bg-[#f8f7f3] pl-4 group-hover:text-terracotta/20 transition-colors">01</span>
                <h3 class="font-serif italic text-2xl text-textmain mb-4">Earth</h3>
                <p class="text-xs font-light text-textmuted leading-relaxed uppercase tracking-widest mb-3">The Pristine</p>
                <p class="text-sm text-textmuted leading-relaxed font-light">Selecting the finest essences from mother earth. Each grain of clay carries memories of nature, pure and peaceful.</p>
            </div>
            
            <!-- Water -->
            <div class="group relative pt-8 border-t border-accent/40 hover:border-terracotta transition-colors duration-500 md:mt-12">
                <span class="absolute top-0 right-0 text-5xl font-serif text-accent/20 font-light -mt-[24px] bg-[#f8f7f3] pl-4 group-hover:text-terracotta/20 transition-colors">02</span>
                <h3 class="font-serif italic text-2xl text-textmain mb-4">Water</h3>
                <p class="text-xs font-light text-textmuted leading-relaxed uppercase tracking-widest mb-3">The Fluidity</p>
                <p class="text-sm text-textmuted leading-relaxed font-light">Water soothes the roughness, softens prejudices, and binds scattered dust into a unified, resilient entity.</p>
            </div>

            <!-- Fire -->
            <div class="group relative pt-8 border-t border-accent/40 hover:border-terracotta transition-colors duration-500">
                <span class="absolute top-0 right-0 text-5xl font-serif text-accent/20 font-light -mt-[24px] bg-[#f8f7f3] pl-4 group-hover:text-terracotta/20 transition-colors">03</span>
                <h3 class="font-serif italic text-2xl text-textmain mb-4">Fire</h3>
                <p class="text-xs font-light text-textmuted leading-relaxed uppercase tracking-widest mb-3">The Rebirth</p>
                <p class="text-sm text-textmuted leading-relaxed font-light">At 1200°C, weakness is incinerated, leaving behind a form that endures through time. Fire challenges and bestows the colors of the glaze.</p>
            </div>

            <!-- Soul -->
            <div class="group relative pt-8 border-t border-accent/40 hover:border-terracotta transition-colors duration-500 md:mt-12">
                <span class="absolute top-0 right-0 text-5xl font-serif text-accent/20 font-light -mt-[24px] bg-[#f8f7f3] pl-4 group-hover:text-terracotta/20 transition-colors">04</span>
                <h3 class="font-serif italic text-2xl text-textmain mb-4">Soul</h3>
                <p class="text-xs font-light text-textmuted leading-relaxed uppercase tracking-widest mb-3">The Uniqueness</p>
                <p class="text-sm text-textmuted leading-relaxed font-light">Hands guide the way, but it is the tranquil soul that breathes life into the earth, turning the inanimate into art.</p>
            </div>
        </div>
    </section>

    <!-- 3. THE COLLECTION (Products) -->
    <section id="collection" class="py-24 lg:py-32 relative border-t border-accent/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row items-center gap-16 lg:gap-24">
                
                <!-- Image Section (Left) -->
                <div class="w-full lg:w-1/2 group cursor-pointer relative">
                    <div class="absolute -inset-4 bg-[#EBE7DF] rounded-t-[150px] lg:rounded-t-[250px] rounded-b-md -z-10 opacity-50 transform -rotate-2 group-hover:rotate-0 transition-transform duration-700"></div>
                    <div class="overflow-hidden rounded-t-[150px] lg:rounded-t-[250px] rounded-b-md aspect-[4/5] shadow-xl bg-[#EBE7DF]">
                        <img src="<?php echo esc_url($collection_img); ?>" 
                             alt="Unique Ceramics" class="w-full h-full object-cover transition-transform duration-[2s] group-hover:scale-105">
                    </div>
                </div>

                <!-- Text Section (Right) -->
                <div class="w-full lg:w-1/2">
                    <p class="text-[10px] uppercase tracking-[0.4em] text-accentdark font-medium mb-6 flex items-center gap-3">
                        <span class="w-8 h-[1px] bg-accentdark"></span> The Collection
                    </p>
                    <h2 class="font-serif text-4xl lg:text-6xl text-textmain leading-tight mb-8">
                        A Journey to <br>
                        <span class="italic text-terracotta">Awaken</span> Senses
                    </h2>
                    <p class="text-sm lg:text-base font-light text-textmuted leading-relaxed mb-8 pl-6 border-l border-accent/40">
                        Discover our curated selection of unique, handcrafted ceramics. Each piece is a testament to the raw beauty of earth and the meticulous care of the artisan. Let these vessels bring a touch of mindful elegance to your daily rituals.
                    </p>
                    
                    <!-- Navigation Buttons -->
                    <div class="flex justify-end gap-3 mb-4 pr-1">
                        <button class="col-prev w-10 h-10 rounded-full border border-accent/40 text-textmuted hover:bg-accent hover:text-white hover:border-accent flex items-center justify-center transition-all cursor-pointer"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7"></path></svg></button>
                        <button class="col-next w-10 h-10 rounded-full border border-accent/40 text-textmuted hover:bg-accent hover:text-white hover:border-accent flex items-center justify-center transition-all cursor-pointer"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"></path></svg></button>
                    </div>

                    <!-- Dynamic Product Slider -->
                    <div class="swiper collection-swiper mb-10 w-full overflow-hidden" style="max-width: 100%;">
                        <div class="swiper-wrapper">
                            <?php
                            $pancake_prods = [];
                            if (class_exists('Bacera_Module_Products') && class_exists('Bacera_Utils')) {
                                $response = Bacera_Module_Products::get_products();
                                if (!empty($response['data']) && is_array($response['data'])) {
                                    $pancake_prods = array_slice($response['data'], 0, 8);
                                }
                            }

                            if (!empty($pancake_prods)) {
                                foreach ($pancake_prods as $p_item) {
                                    $name  = $p_item['product']['name'] ?? $p_item['name'] ?? 'Tác phẩm gốm';
                                    $price = $p_item['retail_price'] ?? $p_item['price_at_counter'] ?? 0;
                                    $img   = Bacera_Utils::get_proxy_url($p_item);
                                    $prod_url = Bacera_Utils::get_product_permalink($p_item) ?: $url_shop;
                                    ?>
                                    <div class="swiper-slide h-auto">
                                        <?php
                                        get_template_part('app/Views/components/product-card', null, [
                                            'name'  => $name,
                                            'price' => $price > 0 ? number_format($price, 0, ',', '.') . ' ₫' : 'Liên hệ',
                                            'image' => $img,
                                            'url'   => $prod_url,
                                        ]);
                                        ?>
                                    </div>
                                    <?php
                                }
                            } elseif (class_exists('WooCommerce')) {
                                $prods = wc_get_products(array('limit' => 8, 'status' => 'publish'));
                                if (!empty($prods)) {
                                    foreach ($prods as $prod) {
                                        $img = wp_get_attachment_image_url($prod->get_image_id(), 'medium') ?: 'https://images.unsplash.com/photo-1578308691517-8e68e43425f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                                        ?>
                                        <div class="swiper-slide group cursor-pointer" onclick="window.location='<?php echo esc_url($prod->get_permalink()); ?>'">
                                            <div class="aspect-[4/5] overflow-hidden rounded-md mb-4 bg-[#EBE7DF]">
                                                <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                                            </div>
                                            <h3 class="font-serif text-lg text-textmain truncate border-b border-transparent group-hover:border-accentdark inline-block transition-all"><?php echo esc_html($prod->get_name()); ?></h3>
                                            <p class="text-xs font-sans text-textmuted mt-1 tracking-wider"><?php echo strip_tags($prod->get_price_html()); ?></p>
                                        </div>
                                        <?php
                                    }
                                }
                            } else {
                                // Fallback
                                for($i=1; $i<=3; $i++): ?>
                                <div class="swiper-slide group cursor-pointer">
                                    <div class="aspect-[4/5] overflow-hidden rounded-md mb-4 bg-[#EBE7DF]">
                                        <img src="https://images.unsplash.com/photo-1610701596007-11502861dcfa?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                                    </div>
                                    <h3 class="font-serif text-lg text-textmain truncate">Handcrafted Vase <?php echo $i; ?></h3>
                                    <p class="text-xs font-sans text-textmuted mt-1 tracking-wider">$85.00</p>
                                </div>
                            <?php endfor; } ?>
                        </div>
                    </div>

                    <a href="<?php echo esc_url($url_shop); ?>" class="inline-flex items-center gap-4 group">
                        <span class="text-xs uppercase tracking-[0.2em] text-textmain font-medium group-hover:text-terracotta transition-colors">View Artwork</span>
                        <span class="w-12 h-12 rounded-full border border-accent flex items-center justify-center group-hover:border-terracotta group-hover:bg-terracotta group-hover:text-white transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. THE WORKSHOP (Experience) -->
    <section id="workshop" class="py-24 lg:py-32 relative bg-white/40 border-t border-accent/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row-reverse items-center gap-16 lg:gap-24">
                
                <!-- Image Section (Right) -->
                <div class="w-full lg:w-1/2 group cursor-pointer relative">
                    <!-- Decorative background block -->
                    <div class="absolute -bottom-6 -left-6 w-2/3 h-2/3 bg-accent/20 rounded-md -z-10 group-hover:translate-x-3 group-hover:-translate-y-3 transition-transform duration-700"></div>
                    <div class="overflow-hidden rounded-md aspect-[4/5] lg:aspect-[3/4] shadow-xl bg-[#EBE7DF]">
                        <img src="<?php echo esc_url($workshop_img); ?>" 
                             alt="Workshop Experience" class="w-full h-full object-cover transition-transform duration-[2s] group-hover:scale-105">
                    </div>
                </div>

                <!-- Text Section (Left) -->
                <div class="w-full lg:w-1/2">
                    <p class="text-[10px] uppercase tracking-[0.4em] text-accentdark font-medium mb-6 flex items-center gap-3">
                        <span class="w-8 h-[1px] bg-accentdark"></span> Creative Space
                    </p>
                    <h2 class="font-serif text-4xl lg:text-6xl text-textmain leading-tight mb-8">
                        Shape Your <br>
                        <span class="italic text-terracotta">Own Story</span>
                    </h2>
                    <p class="text-sm lg:text-base font-light text-textmuted leading-relaxed mb-8 pl-6 border-l border-accent/40">
                        Immerse yourself in the meditative process of pottery making. Our workshops offer a tranquil space to disconnect from the noise, connect with the raw earth, and shape your own memories with every turn of the wheel.
                    </p>
                    
                    <!-- Navigation Buttons -->
                    <div class="flex justify-end gap-3 mb-4 pr-1">
                        <button class="ws-prev w-10 h-10 rounded-full border border-accent/40 text-textmuted hover:bg-accent hover:text-white hover:border-accent flex items-center justify-center transition-all cursor-pointer"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7"></path></svg></button>
                        <button class="ws-next w-10 h-10 rounded-full border border-accent/40 text-textmuted hover:bg-accent hover:text-white hover:border-accent flex items-center justify-center transition-all cursor-pointer"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"></path></svg></button>
                    </div>

                    <!-- Dynamic Workshop Slider -->
                    <div class="swiper workshop-swiper mb-10 w-full overflow-hidden" style="max-width: 100%;">
                        <div class="swiper-wrapper">
                            <?php
                            $ws_args = array('post_type' => 'workshop', 'posts_per_page' => 8, 'post_status' => 'publish');
                            $ws_query = new WP_Query($ws_args);
                            if ($ws_query->have_posts()) {
                                global $wpdb;
                                $ts = $wpdb->prefix . 'bacera_workshop_slots';
                                while ($ws_query->have_posts()) {
                                    $ws_query->the_post();
                                    $pid = get_the_ID();
                                    
                                    $slot_agg = $wpdb->get_row($wpdb->prepare(
                                        "SELECT 
                                            COALESCE(SUM(booked_seats), 0) AS total_booked,
                                            COALESCE(SUM(total_seats),  0) AS total_capacity
                                         FROM {$ts}
                                         WHERE workshop_id = %d AND slot_date >= CURDATE() AND status != 'cancelled'", 
                                        $pid
                                    ), ARRAY_A);
                                    
                                    $bookedSlots = (int)($slot_agg['total_booked'] ?? 0);
                                    $totalSlots  = (int)($slot_agg['total_capacity'] ?? 0);
                                    $pricing     = get_post_meta($pid, '_price', true) ?: 'Liên hệ';
                                    $desc        = wp_trim_words(get_post_meta($pid, '_description', true) ?: get_the_excerpt() ?: strip_tags(get_the_content()), 15, '…');
                                    
                                    $img = get_post_meta($pid, '_thumbnail_url', true) 
                                           ?: get_the_post_thumbnail_url($pid, 'large') 
                                           ?: 'https://images.unsplash.com/photo-1565507567756-3211559ee5eb?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                                    ?>
                                    <div class="swiper-slide h-auto group cursor-pointer" onclick="window.location='<?php echo esc_url(get_permalink()); ?>'">
                                        <div class="h-full">
                                            <?php 
                                            get_template_part('app/Views/components/workshop-card', null, [
                                                'title'       => get_the_title(),
                                                'description' => $desc,
                                                'pricing'     => $pricing,
                                                'image'       => $img,
                                                'link'        => get_permalink(),
                                                'bookedSlots' => $bookedSlots,
                                                'totalSlots'  => $totalSlots,
                                            ]); 
                                            ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                                wp_reset_postdata();
                            } else {
                                // Fallback
                                for($i=1; $i<=3; $i++): ?>
                                <div class="swiper-slide group cursor-pointer">
                                    <div class="aspect-[4/3] overflow-hidden rounded-md mb-4 bg-[#EBE7DF]">
                                        <img src="https://images.unsplash.com/photo-1565507567756-3211559ee5eb?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                                    </div>
                                    <h3 class="font-serif text-lg text-textmain truncate">Masterclass <?php echo $i; ?></h3>
                                    <p class="text-[10px] font-sans text-accent mt-1 tracking-[0.2em] uppercase">Experience</p>
                                </div>
                            <?php endfor; } ?>
                        </div>
                    </div>

                    <a href="<?php echo esc_url($url_workshop); ?>" class="inline-flex items-center gap-4 group">
                        <span class="text-xs uppercase tracking-[0.2em] text-textmain font-medium group-hover:text-terracotta transition-colors">Join Workshop</span>
                        <span class="w-12 h-12 rounded-full border border-accent flex items-center justify-center group-hover:border-terracotta group-hover:bg-terracotta group-hover:text-white transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. PARTNERS -->
    <?php get_template_part('app/Views/components/presence-partners'); ?>

    <!-- 6. REVIEWS -->
    <section class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-20 mb-10 relative bg-textmain text-bgtheme rounded-3xl lg:rounded-[60px] overflow-hidden">
        
        <div class="absolute inset-0 bg-texture opacity-10"></div>

        <div class="relative z-10 pt-10 pb-4 px-4 lg:px-12 text-center lg:text-left flex flex-col lg:flex-row justify-between items-end mb-16 gap-8">
            <div>
                <p class="text-[10px] uppercase tracking-[0.4em] text-accent mb-4">Shared Moments</p>
                <h2 class="font-serif text-4xl lg:text-5xl text-bgtheme">
                    Memories of <span class="italic text-accent">Clay</span>
                </h2>
            </div>
            
            <div class="flex gap-3 justify-center lg:justify-start">
                <button id="prevBtn" class="w-12 h-12 rounded-full border border-accent/30 flex items-center justify-center hover:bg-accent hover:text-textmain transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </button>
                <button id="nextBtn" class="w-12 h-12 rounded-full border border-accent/30 flex items-center justify-center hover:bg-accent hover:text-textmain transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </div>
        </div>

        <div id="reviewContainer" class="relative z-10 flex overflow-x-auto snap-x snap-mandatory gap-6 hide-scrollbar text-left pb-12 px-4 lg:px-12">
            
            <?php foreach($reviews as $review): 
                $quote = $review['quote'] ?? $review['review_text'] ?? '';
                $name = $review['name'] ?? $review['customer_name'] ?? '';
                $course = $review['course'] ?? $review['workshop_name'] ?? 'Guest';
                $img = $review['img'] ?? 'https://images.unsplash.com/photo-1522066367507-640a2bb12d08?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
            ?>
            <div class="snap-center shrink-0 w-[90%] md:w-[400px] bg-transparent border-t border-accent/20 flex flex-col pt-8 group cursor-pointer">
                <div class="overflow-hidden rounded-lg mb-8 bg-[#EBE7DF]">
                    <img src="<?php echo esc_url($img); ?>" 
                         alt="Review from <?php echo esc_attr($name); ?>" class="w-full h-56 object-cover hover:scale-105 transition-transform duration-700">
                </div>
                
                <p class="font-serif text-lg leading-relaxed mb-8 italic text-bgtheme/90">
                    "<?php echo esc_html($quote); ?>"
                </p>
                
                <div class="mt-auto">
                    <p class="text-sm font-sans tracking-widest uppercase text-accent mb-1"><?php echo esc_html($name); ?></p>
                    <p class="text-[10px] text-bgtheme/50 uppercase tracking-[0.1em]"><?php echo esc_html($course); ?></p>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
    </section>

</div>

<!-- Script for review slider -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Horizontal Scroll for Reviews
        const container = document.getElementById('reviewContainer');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        if(container && prevBtn && nextBtn) {
            const scrollAmount = 400;

            prevBtn.addEventListener('click', () => {
                container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            });

            nextBtn.addEventListener('click', () => {
                container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            });
        }

        // Initialize Swiper for Collection and Workshop
        if (typeof Swiper !== 'undefined') {
            new Swiper('.collection-swiper', {
                slidesPerView: 1.3,
                spaceBetween: 16,
                grabCursor: true,
                freeMode: true,
                navigation: { nextEl: '.col-next', prevEl: '.col-prev' },
                breakpoints: {
                    640: { slidesPerView: 2.15, spaceBetween: 24 }
                }
            });

            new Swiper('.workshop-swiper', {
                slidesPerView: 1.3,
                spaceBetween: 16,
                grabCursor: true,
                freeMode: true,
                navigation: { nextEl: '.ws-next', prevEl: '.ws-prev' },
                breakpoints: {
                    640: { slidesPerView: 2.15, spaceBetween: 24 }
                }
            });
        }
    });
</script>

<?php get_footer(); ?>
