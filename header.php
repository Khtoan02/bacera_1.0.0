<?php
$is_search_view = (bool) get_query_var( 'bacera_search_type' );
$is_homepage = is_front_page() || is_page_template('templates/template-home-page.php');
if ( $is_search_view ) {
    $is_homepage = false;
}
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

// Blog: find page using template-blog.php (custom template, not WP posts archive)
$_hdr_blog_page_id = $wpdb->get_var(
    "SELECT p.ID FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
     WHERE p.post_type = 'page' AND p.post_status = 'publish'
       AND pm.meta_key = '_wp_page_template'
       AND pm.meta_value IN ('templates/template-blog.php','template-blog.php')
     LIMIT 1"
);
$bacera_hdr_blog_url = $_hdr_blog_page_id ? get_permalink( (int)$_hdr_blog_page_id ) : home_url( '/blog/' );

// Contact: find page using template or slug
$_hdr_contact_page_id = $wpdb->get_var(
    "SELECT ID FROM {$wpdb->posts}
     WHERE post_type = 'page' AND post_status = 'publish'
       AND (post_name = 'contact' OR post_name = 'lien-he' OR post_name = 'contact-us')
     LIMIT 1"
);
$bacera_hdr_contact_url = $_hdr_contact_page_id ? get_permalink( (int)$_hdr_contact_page_id ) : home_url( '/contact/' );
$bacera_search_ajax_url = admin_url( 'admin-ajax.php' );
$bacera_search_nonce    = wp_create_nonce( 'bacera_header_search_nonce' );
$bacera_search_product_url  = home_url( '/search/product/' );
$bacera_search_blog_url     = home_url( '/search/blog/' );
$bacera_search_workshop_url = home_url( '/search/workshop/' );
$bacera_search_scope = 'global';
if ( is_page_template( 'templates/template-shop.php' ) || is_singular( 'pancake_product' ) ) {
    $bacera_search_scope = 'product';
} elseif ( is_page_template( 'templates/template-workshop.php' ) || is_post_type_archive( 'workshop' ) || is_singular( 'workshop' ) ) {
    $bacera_search_scope = 'workshop';
} elseif ( is_page_template( 'templates/template-blog.php' ) || get_query_var( 'bacera_blog_cat_slug' ) || is_singular( 'post' ) || is_category() ) {
    $bacera_search_scope = 'blog';
} elseif ( is_front_page() || is_page_template( 'templates/template-home-page.php' ) ) {
    $bacera_search_scope = 'global';
}

// ── Fetch Pancake categories + local meta for header mega-panel ──
$hdr_pancake_cats = [];
$hdr_cat_meta_map = [];
if ( class_exists( 'Pancake_API_Client' ) ) {
    // Try transient cache first (5 min) to avoid API call on every page load
    $hdr_cats_cached = get_transient( 'bacera_hdr_categories' );
    if ( false !== $hdr_cats_cached ) {
        $hdr_pancake_cats = $hdr_cats_cached;
    } else {
        try {
            $hdr_api = new Pancake_API_Client();
            $hdr_resp = $hdr_api->request( '/shops/{SHOP_ID}/categories', 'GET' );
            if ( is_array( $hdr_resp ) && ! empty( $hdr_resp['success'] ) && ! empty( $hdr_resp['data'] ) ) {
                $hdr_pancake_cats = array_slice( $hdr_resp['data'], 0, 8 );
                set_transient( 'bacera_hdr_categories', $hdr_pancake_cats, 5 * MINUTE_IN_SECONDS );
            }
        } catch ( \Exception $e ) {}
    }
    // Fetch local meta (images) for these categories
    if ( ! empty( $hdr_pancake_cats ) ) {
        global $wpdb;
        $hdr_cids  = array_map( fn($c) => (string)($c['id']??''), $hdr_pancake_cats );
        $hdr_cids  = array_filter( $hdr_cids );
        if ( $hdr_cids ) {
            $ph   = implode( ',', array_fill( 0, count($hdr_cids), '%s' ) );
            $rows = $wpdb->get_results(
                $wpdb->prepare( "SELECT pancake_category_id, image_url, name_override, is_active FROM {$wpdb->prefix}bacera_category_meta WHERE pancake_category_id IN ($ph)", ...$hdr_cids ),
                ARRAY_A
            ) ?: [];
            foreach ( $rows as $r ) {
                $hdr_cat_meta_map[ $r['pancake_category_id'] ] = $r;
            }
        }
    }
}

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
    .hdr-logo-light { transition: opacity .3s ease; }
    .hdr-logo-dark  { transition: opacity .3s ease; display: none; }
    /* Trên hero: ẩn logo sáng, hiện logo tối */
    #site-header.is-hero .hdr-logo-light { display: none; }
    #site-header.is-hero .hdr-logo-dark  { display: block; }
    /* Khi không có dark logo (chưa upload): dùng filter invert cho light logo */
    #site-header.is-hero .hdr-logo-light.hdr-logo--no-dark { display: block; filter: brightness(0) invert(1); }
    #site-header.is-hero:hover .hdr-logo-light.hdr-logo--no-dark { filter: brightness(1); transition: none; }

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

    /* Force normal header state when search opens */
    #site-header.force-solid .hdr-bg {
        opacity: 1 !important;
        box-shadow: 0 1px 12px rgba(0,0,0,.06) !important;
    }
    #site-header.force-solid .hdr-link,
    #site-header.force-solid .hdr-icon {
        color: #57534e;
    }
    #site-header.force-solid .hdr-logo-dark { display: none !important; }
    #site-header.force-solid .hdr-logo-light { display: block !important; filter: none !important; }

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
        left: 0; right: 0; top: 76px; bottom: 0;
        background: rgba(0, 0, 0, .26);
        z-index: 45;
        opacity: 0;
        pointer-events: none;
        transition: opacity .2s ease;
    }
    .search-overlay.is-active { opacity: 1; pointer-events: auto; }
    .search-overlay-panel {
        background: #f7f6f3;
        transform: translateY(-10px);
        transition: transform .2s ease;
        max-height: calc(100vh - 76px);
        overflow: auto;
    }
    .search-overlay.is-active .search-overlay-panel { transform: translateY(0); }
    .search-overlay-head {
        max-width: 1232px;
        margin: 0 auto;
        padding: 12px 24px 10px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .search-overlay-input {
        width: 100%;
        height: 50px;
        border: 1.5px solid #d6d3d1;
        border-radius: 10px;
        padding: 0 40px 0 14px;
        font-size: 15px;
        color: #292524;
        outline: none;
        background: #fff;
    }
    .search-overlay-input:focus { border-color: #a8a29e; }
    .search-overlay-body {
        max-width: 1232px;
        margin: 0 auto;
        padding: 0 24px 20px;
    }
    .search-default-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        padding-bottom: 12px;
    }
    .search-block { padding: 0; }
    .search-block h4 {
        margin: 0 0 4px;
        color: #a8a29e;
        font-size: 11px;
        letter-spacing: 0;
        text-transform: none;
        font-weight: 500;
    }
    .search-chip-list { display: flex; flex-direction: column; gap: 1px; }
    .search-chip {
        border: 0;
        border-radius: 0;
        padding: 0;
        font-size: 14px;
        color: #292524;
        text-decoration: none;
        background: transparent;
    }
    .search-chip:hover { color: #d95f47; }
    .search-default-cats {
        display: grid;
        grid-template-columns: repeat(8, minmax(0, 1fr));
        gap: 8px;
        margin-top: 6px;
        border-top: 1px solid #eceae6;
        padding-top: 12px;
    }
    .search-default-cat {
        text-decoration: none;
        color: #57534e;
        border: 1px solid #e7e5e4;
        border-radius: 10px;
        background: #fff;
        padding: 8px 6px;
        text-align: center;
        min-height: 78px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    .search-default-cat img {
        width: 24px; height: 24px; object-fit: contain;
    }
    .search-default-cat span { font-size: 11px; line-height: 1.2; }
    .search-loading { font-size: 13px; color: #78716c; padding: 8px 0; }
    .search-empty {
        border: 1px dashed #d6d3d1;
        border-radius: 12px;
        padding: 22px 14px;
        text-align: center;
        color: #78716c;
        font-size: 13px;
    }
    .search-results-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 22px;
        border-top: 1px solid #e8e5df;
        padding-top: 10px;
    }
    .search-panel-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    .search-panel-title h3 {
        margin: 0;
        font-size: 16px;
        color: #292524;
        font-family: "Gowun Batang", Georgia, serif;
    }
    .search-panel-title a { font-size: 12px; color: #78716c; text-decoration: none; }
    .search-panel-title a:hover { color: #d95f47; }
    .search-cards-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
    .search-cards-4 { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
    .search-card { text-decoration: none; color: inherit; display: block; }
    .search-card-thumb {
        width: 100%;
        aspect-ratio: 4/5;
        border-radius: 6px;
        overflow: hidden;
        background: #f5f5f4;
        margin-bottom: 6px;
    }
    .search-card-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .search-card-meta { margin: 0 0 2px; font-size: 11px; color: #a8a29e; }
    .search-card-title { margin: 0 0 2px; font-size: 12px; color: #292524; line-height: 1.35; }
    .search-card-price { margin: 0; font-size: 12px; color: #292524; font-weight: 600; }
    .search-blogs-list { display: flex; flex-direction: column; gap: 9px; }
    .search-blog-item { display: block; text-decoration: none; color: inherit; padding-bottom: 8px; border-bottom: 1px solid #ece8e2; }
    .search-blog-meta { font-size: 10px; color: #a8a29e; margin: 0 0 2px; }
    .search-blog-title { font-size: 13px; line-height: 1.33; color: #292524; margin: 0 0 3px; }
    .search-blog-excerpt { font-size: 11px; color: #78716c; line-height: 1.4; margin: 0; }
    .search-workshop-desc { margin: 0; font-size: 10px; color: #78716c; line-height: 1.35; }
    @media (max-width: 900px) {
        /* Keep mobile search UI legacy */
        .search-overlay {
            top: 0;
            bottom: auto;
            left: 0;
            right: 0;
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            opacity: 0;
            transform: translateY(-100%);
            transition: opacity .25s ease, transform .25s ease;
            pointer-events: none;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            z-index: 200;
        }
        .search-overlay.is-active {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        .search-overlay-panel {
            max-height: none;
            overflow: visible;
            background: transparent;
            transform: none !important;
        }
        .search-overlay-head {
            max-width: none;
            padding: 8px 12px;
            gap: 8px;
        }
        .search-overlay-input {
            height: 46px;
            border-radius: 10px;
            font-size: 14px;
            background: #fafaf9;
        }
        .search-overlay-body { display: none; }
    }
    </style>

    <?php
    // Custom favicon từ Bacera Config → Giao diện
    add_action('wp_head', function() {
        $fav = function_exists('bacera_get_favicon_url') ? bacera_get_favicon_url() : '';
        if ($fav) {
            echo '<link rel="icon" type="image/x-icon" href="' . esc_url($fav) . '">' . "\n";
            echo '<link rel="shortcut icon" href="' . esc_url($fav) . '">' . "\n";
            echo '<link rel="apple-touch-icon" href="' . esc_url($fav) . '">' . "\n";
        }
    }, 1);
    ?>
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
            <?php
            $hp_logo_light = bacera_get_brand_logo_url();
            $hp_logo_dark  = function_exists('bacera_get_brand_logo_dark_url') ? bacera_get_brand_logo_dark_url() : $hp_logo_light;
            $hp_has_dark   = (int)get_option('bacera_logo_dark_id',0) > 0;
            $hp_no_dark_cls = $hp_has_dark ? '' : ' hdr-logo--no-dark';
            ?>
            <?php if ($hp_has_dark): ?>
            <img src="<?php echo esc_url($hp_logo_dark); ?>"
                 class="hdr-logo-dark h-9 w-auto object-contain"
                 alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
            <?php endif; ?>
            <img src="<?php echo esc_url($hp_logo_light); ?>"
                 class="hdr-logo-light<?php echo $hp_no_dark_cls; ?> h-9 w-auto object-contain"
                 alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
        </a>

        <div class="flex items-stretch justify-between">

            <nav class="flex items-stretch" id="hdr-nav">

                <?php
                $nav_items = [
                    ['key'=>'shop',     'label'=>'Shop',     'has_sub'=>true, 'url'=>$bacera_hdr_shop_url],
                    ['key'=>'workshop', 'label'=>'Workshop', 'has_sub'=>true, 'url'=>$bacera_hdr_workshop_url],
                    ['key'=>'about',    'label'=>'About us', 'has_sub'=>true, 'url'=>$about_page],
                    ['key'=>'blog',     'label'=>'Blog',     'has_sub'=>true, 'url'=>$bacera_hdr_blog_url],
                    ['key'=>'contact',  'label'=>'Contact',  'has_sub'=>true, 'url'=>$bacera_hdr_contact_url],
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

            <button id="search-btn" class="hdr-icon w-9 h-9 flex items-center justify-center hover:scale-110 transition-transform focus:outline-none" title="Search">
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
                        ['icon'=>'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',  'label'=>'My Account',        'url'=>$my_account_url],
                        ['icon'=>'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label'=>'Loyalty Points',    'url'=> $my_account_url . '#loyalty'],
                        ['icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'label'=>'My Orders',         'url'=>$my_account_url . '#orders'],
                        ['icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'label'=>'Booked Workshops',  'url'=>$my_account_url . '#workshops'],
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
                        Sign out
                    </a>
                </div>
            </div>
            <?php else: ?>
            <a id="bacera-login-btn" href="<?php echo esc_url($auth_page); ?>"
               class="flex items-center gap-2 px-4 py-2 rounded-xl border border-neutral-300 hdr-link text-[13px] font-medium font-sans hover:border-[#d95f47] hover:text-[#d95f47] transition-all">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                Sign in
            </a>
            <?php endif; ?>

        </div>

    </div>

    <!-- ── Shop mega panel: icon-card style ── -->
    <style>
    /* ── Brand fonts: Bricolage Grotesque (sans) + Gowun Batang (serif) ── */
    .mega-panel, .mega-panel * { font-family: 'Bricolage Grotesque', sans-serif; }

    #panel-shop { background: #fdfaf6; border-top: 1px solid #ede5d8; }
    .msp-wrap { max-width: 1232px; margin: 0 auto; padding: 24px 28px 22px; display: flex; gap: 28px; align-items: stretch; }

    /* ── Left intro ── */
    .msp-intro {
        width: 200px; flex-shrink: 0;
        display: flex; flex-direction: column;
        padding-right: 28px; border-right: 1px solid #e8ddd0;
        justify-content: space-between;
    }
    .msp-eyebrow {
        font-size: 9.5px; font-weight: 800; letter-spacing: .18em;
        text-transform: uppercase; color: #c06b3a; margin-bottom: 6px;
    }
    .msp-heading {
        font-family: 'Gowun Batang', Georgia, serif;
        font-size: 22px; font-weight: 400; color: #2a1f17;
        line-height: 1.18; margin-bottom: 8px;
    }
    .msp-heading em { font-style: italic; color: #c06b3a; }
    .msp-desc { font-size: 12.5px; color: #9a7d68; line-height: 1.6; }
    .msp-all-btn {
        margin-top: 16px;
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 16px; border-radius: 9px;
        background: #3d2f26; color: #fff !important;
        font-size: 12.5px; font-weight: 600;
        text-decoration: none;
        transition: background .2s, box-shadow .2s;
        box-shadow: 0 2px 6px rgba(61,47,38,.2);
    }
    .msp-all-btn:hover { background: #c06b3a; box-shadow: 0 4px 14px rgba(192,107,58,.3); }

    /* ── Icon-card grid ── */
    .msp-cats {
        flex: 1;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
        gap: 8px; align-content: center;
    }

    /* Each category card */
    .msp-ic {
        display: flex; flex-direction: column; align-items: center;
        gap: 8px; padding: 12px 8px 10px;
        border-radius: 12px; border: 1.5px solid transparent;
        text-decoration: none; background: #fff;
        transition: border-color .18s, background .18s, transform .18s, box-shadow .18s;
        cursor: pointer;
    }
    .msp-ic:hover {
        border-color: #d4b896; background: #fdf6ef;
        transform: translateY(-2px);
        box-shadow: 0 4px 14px rgba(61,47,38,.09);
    }

    /* Icon chip */
    .msp-ic-chip {
        width: 52px; height: 52px; border-radius: 14px;
        background: linear-gradient(135deg, #f5ede0, #ede0ce);
        display: flex; align-items: center; justify-content: center;
        overflow: hidden; flex-shrink: 0;
        transition: background .18s, box-shadow .18s;
        box-shadow: 0 1px 3px rgba(61,47,38,.08);
    }
    .msp-ic:hover .msp-ic-chip {
        background: linear-gradient(135deg, #fdecd8, #f5dfc4);
        box-shadow: 0 2px 8px rgba(192,107,58,.18);
    }
    .msp-ic-chip img { width: 36px; height: 36px; object-fit: contain; transition: transform .25s; }
    .msp-ic:hover .msp-ic-chip img { transform: scale(1.12); }
    .msp-ic-svg { color: #c06b3a; transition: color .18s; }

    /* Label + count */
    .msp-ic-label {
        font-size: 12px; font-weight: 700; color: #3d2f26;
        text-align: center; line-height: 1.3; transition: color .18s;
    }
    .msp-ic:hover .msp-ic-label { color: #c06b3a; }
    .msp-ic-count {
        font-size: 10.5px; color: #b5906a; font-weight: 500;
        margin-top: -4px; text-align: center;
    }

    /* Entrance animation */
    .mega-panel.is-active .msp-ic { animation: mspIn .24s ease both; }
    <?php for ($__i = 1; $__i <= 10; $__i++): ?>
    .mega-panel.is-active .msp-cats > :nth-child(<?php echo $__i; ?>) { animation-delay: <?php echo ($__i - 1) * 24; ?>ms; }
    <?php endfor; ?>
    @keyframes mspIn {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    </style>

    <div class="mega-panel" id="panel-shop">
        <div class="msp-wrap">

            <!-- Intro -->
            <div class="msp-intro">
                <div>
                    <div class="msp-eyebrow">Collections</div>
                    <h3 class="msp-heading">Shop by <em>category</em></h3>
                    <p class="msp-desc">Handcrafted ceramics for every moment — explore our full range.</p>
                </div>
                <a href="<?php echo esc_url( $bacera_hdr_shop_url ); ?>" class="msp-all-btn">
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    View all
                </a>
            </div>

            <!-- Icon grid -->
            <div class="msp-cats">

                <?php if (!empty($hdr_pancake_cats)):
                    // Ceramic SVG fallbacks (stroke icons, artisan-appropriate)
                    $hdr__svgs = [
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-4 0-7 3-7 7 0 4 3 7 7 8 4-1 7-4 7-8 0-4-3-7-7-7z"/>',
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M6 8h12v10a2 2 0 01-2 2H8a2 2 0 01-2-2V8zM4 8h16M10 8V5a2 2 0 014 0v3"/>',
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M5 10c0-3 3-6 7-6s7 3 7 6v8H5v-8z"/><path stroke-linecap="round" stroke-linejoin="round" d="M18 10h2a2 2 0 010 4h-2"/>',
                        '<ellipse cx="12" cy="14" rx="8" ry="4" stroke-linecap="round" stroke-linejoin="round"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 14c0-4 3.5-7 8-7s8 3 8 7"/>',
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6l2 5v10a2 2 0 01-2 2H9a2 2 0 01-2-2V8l2-5zM9 3l-1 5h8l-1-5"/>',
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M12 2l3 7H21l-6 4.5 2.3 7L12 17l-5.3 3.5L9 13 3 8.5h6L12 2z"/>',
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M8 5v14l4-3 4 3V5a2 2 0 00-2-2h-4a2 2 0 00-2 2z"/>',
                        '<circle cx="12" cy="12" r="9" stroke-linecap="round"/><path stroke-linecap="round" d="M8.5 12c0-2 1.5-4 3.5-4s3.5 2 3.5 4-1.5 4-3.5 4"/>',
                    ];
                    foreach ($hdr_pancake_cats as $__i => $__cat):
                        $__cid = (string)($__cat['id']??'');
                        if (!$__cid) continue;
                        $__meta = $hdr_cat_meta_map[$__cid] ?? [];
                        if (isset($__meta['is_active']) && !(int)$__meta['is_active']) continue;
                        $__name  = ($__meta['name_override']??'') ?: ($__cat['text']??$__cat['name']??'');
                        $__img   = $__meta['image_url'] ?? '';
                        $__cnt   = intval($__cat['products_count']??$__cat['product_count']??0);
                        $__url   = add_query_arg('filter_collection', $__cid, $bacera_hdr_shop_url);
                        $__svg   = $hdr__svgs[$__i % count($hdr__svgs)];
                ?>
                <a href="<?php echo esc_url($__url); ?>" class="msp-ic">
                    <div class="msp-ic-chip">
                        <?php if ($__img): ?>
                        <img src="<?php echo esc_url($__img); ?>" alt="<?php echo esc_attr($__name); ?>" loading="lazy">
                        <?php else: ?>
                        <svg class="msp-ic-svg" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><?php echo $__svg; ?></svg>
                        <?php endif; ?>
                    </div>
                    <span class="msp-ic-label"><?php echo esc_html($__name); ?></span>
                    <?php if ($__cnt > 0): ?>
                    <span class="msp-ic-count"><?php echo $__cnt; ?> items</span>
                    <?php endif; ?>
                </a>
                <?php endforeach; endif; ?>

            </div><!-- .msp-cats -->
        </div><!-- .msp-wrap -->
    </div>

    <div class="mega-panel" id="panel-workshop">
        <div class="msp-wrap">
            <div class="msp-intro">
                <div>
                    <div class="msp-eyebrow">Experiences</div>
                    <h3 class="msp-heading">Explore <em>workshops</em></h3>
                    <p class="msp-desc">Discover the joy of handcrafted ceramics — a creative experience you won't forget.</p>
                </div>
                <a href="<?php echo esc_url($bacera_hdr_workshop_url); ?>" class="msp-all-btn">
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    View all sessions
                </a>
            </div>
            <div class="flex-1 padding-left" style="padding-left:24px;display:grid;grid-template-columns:repeat(2,1fr);gap:10px;align-content:center;">
                <?php
                $mega_wks = get_posts(['post_type'=>'workshop','post_status'=>'publish','posts_per_page'=>4,'orderby'=>'date','order'=>'DESC']);
                foreach ($mega_wks as $mw_post):
                    $mw_url  = get_permalink($mw_post);
                    $mw_price = get_post_meta($mw_post->ID,'_price',true) ?: '';
                    $mw_price_fmt = ($mw_price && is_numeric(preg_replace('/[^0-9.]/','',$mw_price)))
                        ? number_format((float)preg_replace('/[^0-9.]/','',$mw_price),0,',','.') . 'đ' : $mw_price;
                    $mw_thumb = get_post_meta($mw_post->ID,'_thumbnail_url',true) ?: '';
                    $mw_tagline = get_post_meta($mw_post->ID,'_tagline',true) ?: wp_trim_words(strip_tags($mw_post->post_content),9,'…');
                ?>
                <a href="<?php echo esc_url($mw_url); ?>" style="display:flex;align-items:center;gap:12px;padding:10px;border-radius:12px;border:1.5px solid transparent;background:#fff;text-decoration:none;transition:border-color .18s,background .18s,transform .18s,box-shadow .18s;" onmouseenter="this.style.borderColor='#d4b896';this.style.background='#fdf6ef';this.style.boxShadow='0 4px 14px rgba(61,47,38,.09)';" onmouseleave="this.style.borderColor='transparent';this.style.background='#fff';this.style.boxShadow='none';">
                    <div style="width:68px;height:68px;border-radius:12px;overflow:hidden;flex-shrink:0;background:linear-gradient(135deg,#f5ede0,#ede0ce);">
                        <?php if ($mw_thumb): ?>
                        <img src="<?php echo esc_url($mw_thumb); ?>" style="width:100%;height:100%;object-fit:cover;" alt="<?php echo esc_attr($mw_post->post_title); ?>" loading="lazy">
                        <?php else: ?>
                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;"><svg width="24" height="24" fill="none" stroke="#c06b3a" stroke-width="1.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
                        <?php endif; ?>
                    </div>
                    <div style="min-width:0;flex:1;">
                        <div style="font-size:12.5px;font-weight:700;color:#3d2f26;line-height:1.3;margin-bottom:3px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;"><?php echo esc_html($mw_post->post_title); ?></div>
                        <div style="font-size:11px;color:#9a7d68;line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;margin-bottom:4px;"><?php echo esc_html($mw_tagline); ?></div>
                        <?php if ($mw_price_fmt): ?><div style="font-size:11.5px;font-weight:600;color:#c06b3a;"><?php echo esc_html($mw_price_fmt); ?></div><?php endif; ?>
                    </div>
                </a>
                <?php endforeach;
                if (empty($mega_wks)): ?>
                <div style="grid-column:span 2;display:flex;align-items:center;justify-content:center;color:#9a7d68;font-size:13px;">No workshops found. <a href="<?php echo admin_url('post-new.php?post_type=workshop'); ?>" style="margin-left:6px;color:#c06b3a;text-decoration:underline;">Add one</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="mega-panel" id="panel-about">
        <div class="msp-wrap">
            <div class="msp-intro">
                <div>
                    <div class="msp-eyebrow">Our studio</div>
                    <h3 class="msp-heading">About <em>Bacera</em></h3>
                    <p class="msp-desc">Bacera Pottery Studio — where craftsmanship meets soul.</p>
                </div>
                <a href="<?php echo esc_url($about_page); ?>" class="msp-all-btn">
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    Read our story
                </a>
            </div>
            <div class="msp-cats" style="grid-template-columns:repeat(auto-fill,minmax(130px,1fr));">
                <?php
                $about_links = [
                    ['icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.5 0-3 1-3 2.5S10.5 13 12 13s3 1 3 2.5S13.5 18 12 18m0-10V6m0 12v2"/>', 'label'=>'About us',       'desc'=>'Our story & values',     'url'=>$about_page],
                    ['icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4H6a4 4 0 00-4 4v1h5M12 12a4 4 0 100-8 4 4 0 000 8z"/>',    'label'=>'Our team',       'desc'=>'Meet the craftspeople',  'url'=>home_url('/our-team/')],
                    ['icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 10v4a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',  'label'=>'Our video',      'desc'=>'Behind the wheel',       'url'=>home_url('/our-video/')],
                    ['icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h8M4 18h8"/>',                                                   'label'=>'Our process',    'desc'=>'From clay to ceramic',   'url'=>$proc_page],
                    ['icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>','label'=>'Sustainability', 'desc'=>'Earth-conscious craft',  'url'=>$sustain_page],
                ];
                foreach ($about_links as $al): ?>
                <a href="<?php echo esc_url($al['url']); ?>" class="msp-ic">
                    <div class="msp-ic-chip">
                        <svg class="msp-ic-svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><?php echo $al['icon']; ?></svg>
                    </div>
                    <span class="msp-ic-label"><?php echo esc_html($al['label']); ?></span>
                    <span class="msp-ic-count"><?php echo esc_html($al['desc']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="mega-panel" id="panel-blog">
        <div class="msp-wrap">
            <div class="msp-intro">
                <div>
                    <div class="msp-eyebrow">Stories</div>
                    <h3 class="msp-heading">From the <em>studio</em></h3>
                    <p class="msp-desc">Tips, inspiration, and stories from our potters and makers.</p>
                </div>
                <a href="<?php echo esc_url($bacera_hdr_blog_url); ?>" class="msp-all-btn">
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    View all articles
                </a>
            </div>
            <div class="flex-1" style="padding-left:24px;display:grid;grid-template-columns:repeat(3,1fr);gap:10px;align-content:center;">
                <?php
                $mega_posts = get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>3,'orderby'=>'date','order'=>'DESC']);
                foreach ($mega_posts as $mp):
                    $mp_url   = get_permalink($mp);
                    $mp_thumb = get_the_post_thumbnail_url($mp->ID,'medium') ?: '';
                    $mp_cat   = get_the_category($mp->ID);
                    $mp_cat_n = $mp_cat ? $mp_cat[0]->name : '';
                    $mp_date  = get_the_date('M j, Y', $mp->ID);
                ?>
                <a href="<?php echo esc_url($mp_url); ?>" style="display:flex;flex-direction:column;border-radius:12px;border:1.5px solid transparent;background:#fff;text-decoration:none;overflow:hidden;transition:border-color .18s,background .18s,transform .18s,box-shadow .18s;" onmouseenter="this.style.borderColor='#d4b896';this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 14px rgba(61,47,38,.09)';" onmouseleave="this.style.borderColor='transparent';this.style.transform='';this.style.boxShadow='none';">
                    <?php if ($mp_thumb): ?>
                    <div style="height:80px;overflow:hidden;background:#ede0ce;">
                        <img src="<?php echo esc_url($mp_thumb); ?>" style="width:100%;height:100%;object-fit:cover;" alt="" loading="lazy">
                    </div>
                    <?php else: ?>
                    <div style="height:80px;background:linear-gradient(135deg,#f5ede0,#ede0ce);display:flex;align-items:center;justify-content:center;">
                        <svg width="28" height="28" fill="none" stroke="#c06b3a" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                    </div>
                    <?php endif; ?>
                    <div style="padding:10px 10px 12px;">
                        <?php if ($mp_cat_n): ?><span style="font-size:9.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#c06b3a;"><?php echo esc_html($mp_cat_n); ?></span><?php endif; ?>
                        <div style="font-size:12px;font-weight:700;color:#3d2f26;line-height:1.3;margin-top:3px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;"><?php echo esc_html($mp->post_title); ?></div>
                        <div style="font-size:10px;color:#b5906a;margin-top:4px;"><?php echo esc_html($mp_date); ?></div>
                    </div>
                </a>
                <?php endforeach;
                if (empty($mega_posts)): ?>
                <div style="grid-column:span 3;display:flex;align-items:center;justify-content:center;color:#9a7d68;font-size:13px;">No articles yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="mega-panel" id="panel-contact">
        <div class="msp-wrap">
            <div class="msp-intro">
                <div>
                    <div class="msp-eyebrow">Get in touch</div>
                    <h3 class="msp-heading">Say <em>hello</em></h3>
                    <p class="msp-desc">We'd love to hear from you — questions, custom orders, or just a chat about ceramics.</p>
                </div>
                <a href="<?php echo esc_url($bacera_hdr_contact_url); ?>" class="msp-all-btn">
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    Go to contact
                </a>
            </div>
            <div class="msp-cats" style="grid-template-columns:repeat(auto-fill,minmax(150px,1fr));">
                <?php
                $contact_tiles = [
                    ['icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',                 'label'=>'Email us',      'desc'=>'hello@bacera.vn',                  'url'=>'mailto:hello@bacera.vn'],
                    ['icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>','label'=>'Call us',        'desc'=>'+84 (0)28 xxxx xxxx',               'url'=>'tel:+84028xxxxxxxx'],
                    ['icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>',                                            'label'=>'Visit us',       'desc'=>'Ho Chi Minh City, Vietnam',         'url'=>$bacera_hdr_contact_url],
                    ['icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>',                                                         'label'=>'WhatsApp',       'desc'=>'Chat with us directly',             'url'=>'https://wa.me/84xxxxxxxxx'],
                ];
                foreach ($contact_tiles as $ct): ?>
                <a href="<?php echo esc_url($ct['url'] ?? '#'); ?>" class="msp-ic">
                    <div class="msp-ic-chip">
                        <svg class="msp-ic-svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><?php echo $ct['icon']; ?></svg>
                    </div>
                    <span class="msp-ic-label"><?php echo esc_html($ct['label']); ?></span>
                    <span class="msp-ic-count"><?php echo esc_html($ct['desc']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Mobile Header (App-like: Back / Logo / Search) -->
    <div class="flex lg:hidden items-center justify-between h-[64px] px-4 relative" id="mobile-hdr">
        <button onclick="window.history.length > 1 ? window.history.back() : window.location.href='<?php echo esc_url(home_url('/')); ?>'" 
                class="hdr-icon w-10 h-10 flex items-center justify-center -ml-2 focus:outline-none" aria-label="Back">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
        
        <a href="<?php echo esc_url(home_url('/')); ?>" class="absolute left-1/2 -translate-x-1/2">
            <?php
            $hp_logo_light_m = bacera_get_brand_logo_url();
            $hp_logo_dark_m  = function_exists('bacera_get_brand_logo_dark_url') ? bacera_get_brand_logo_dark_url() : $hp_logo_light_m;
            $hp_has_dark_m   = (int)get_option('bacera_logo_dark_id',0) > 0;
            ?>
            <?php if ($hp_has_dark_m): ?>
            <img src="<?php echo esc_url($hp_logo_dark_m); ?>"
                 class="hdr-logo-dark h-7 w-auto object-contain"
                 alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
            <?php endif; ?>
            <img src="<?php echo esc_url($hp_logo_light_m); ?>"
                 class="hdr-logo-light<?php echo $hp_has_dark_m ? '' : ' hdr-logo--no-dark'; ?> h-7 w-auto object-contain"
                 alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
        </a>
        
        <button onclick="document.getElementById('search-overlay').classList.add('is-active'); document.body.style.overflow='hidden'; document.getElementById('search-input').focus();" 
                class="hdr-icon w-10 h-10 flex items-center justify-center -mr-2 focus:outline-none" aria-label="Search">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </button>
    </div>

</header>

<div class="search-overlay" id="search-overlay" role="dialog" aria-label="Search overlay">
    <div class="search-overlay-panel">
        <div class="search-overlay-head">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="shrink-0 flex items-center pr-2">
                <img src="<?php echo esc_url( bacera_get_brand_logo_url() ); ?>" class="h-8 w-auto object-contain" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
            </a>

            <form id="search-overlay-form" class="flex-1 relative">
                <input
                    type="search"
                    id="search-input"
                    class="search-overlay-input"
                    placeholder="<?php echo esc_attr( $bacera_search_scope === 'product' ? 'Search products...' : ( $bacera_search_scope === 'workshop' ? 'Search workshops...' : ( $bacera_search_scope === 'blog' ? 'Search blogs...' : 'Search products, workshops, blogs...' ) ) ); ?>"
                    autocomplete="off"
                >
                <button type="submit" aria-label="Search" class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-500 hover:text-stone-700">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </form>

            <button id="search-close"
                    class="shrink-0 w-10 h-10 flex items-center justify-center rounded-xl hover:bg-neutral-100 text-primary-600 hover:text-primary-800 transition-colors"
                    aria-label="Close search">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="search-overlay-body">
            <div id="search-default-state">
                <div class="search-default-grid">
                    <div class="search-block">
                        <h4>Popular search</h4>
                        <div class="search-chip-list">
                            <a class="search-chip" href="<?php echo esc_url( add_query_arg( 'q', 'Ceramics', $bacera_search_product_url ) ); ?>">Ceramics</a>
                            <a class="search-chip" href="<?php echo esc_url( add_query_arg( 'q', 'Workshop', $bacera_search_workshop_url ) ); ?>">Workshop</a>
                            <a class="search-chip" href="<?php echo esc_url( add_query_arg( 'q', 'Coffee cup', $bacera_search_product_url ) ); ?>">Coffee cup</a>
                        </div>
                    </div>
                    <div class="search-block">
                        <h4>Quicklink</h4>
                        <div class="search-chip-list">
                            <a class="search-chip" href="<?php echo esc_url( $bacera_hdr_shop_url ); ?>">New arrival</a>
                            <a class="search-chip" href="<?php echo esc_url( add_query_arg( 'sort', 'price_high', $bacera_hdr_shop_url ) ); ?>">Bestseller</a>
                            <a class="search-chip" href="<?php echo esc_url( $bacera_hdr_workshop_url ); ?>">Workshop</a>
                        </div>
                    </div>
                </div>
                <div class="search-default-cats" <?php echo $bacera_search_scope === 'global' || $bacera_search_scope === 'product' ? '' : 'style="display:none;"'; ?>>
                    <a class="search-default-cat" href="<?php echo esc_url( $bacera_hdr_shop_url ); ?>">
                        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                        <span>Shop all</span>
                    </a>
                    <?php foreach ( array_slice( $hdr_pancake_cats, 0, 7 ) as $__cat ) :
                        $__cid = isset( $__cat['id'] ) ? (string) $__cat['id'] : '';
                        $__name = isset( $__cat['text'] ) ? $__cat['text'] : ( $__cat['name'] ?? '' );
                        if ( $__cid === '' || $__name === '' ) { continue; }
                        $__img = '';
                        if ( isset( $hdr_cat_meta_map[ $__cid ]['image_url'] ) ) {
                            $__img = (string) $hdr_cat_meta_map[ $__cid ]['image_url'];
                        }
                    ?>
                        <a class="search-default-cat" href="<?php echo esc_url( add_query_arg( 'filter_collection', $__cid, $bacera_hdr_shop_url ) ); ?>">
                            <?php if ( $__img ) : ?>
                                <img src="<?php echo esc_url( $__img ); ?>" alt="<?php echo esc_attr( $__name ); ?>" loading="lazy" />
                            <?php else : ?>
                                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10c0-3 3-6 7-6s7 3 7 6v8H5v-8z"/><path stroke-linecap="round" stroke-linejoin="round" d="M18 10h2a2 2 0 010 4h-2"/></svg>
                            <?php endif; ?>
                            <span><?php echo esc_html( $__name ); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="search-loading" class="search-loading" style="display:none;">Searching...</div>
            <div id="search-empty" class="search-empty" style="display:none;">No results found.</div>

            <div id="search-results" style="display:none;">
                <div class="search-results-grid">
                    <div id="search-products-section">
                        <div class="search-panel-title">
                            <h3 id="search-products-title">Products</h3>
                            <a id="search-products-view-all" href="<?php echo esc_url( $bacera_search_product_url ); ?>">View all</a>
                        </div>
                        <div id="search-products-list" class="search-cards-3"></div>
                    </div>
                    <div id="search-blogs-section">
                        <div class="search-panel-title">
                            <h3 id="search-blogs-title">Blogs</h3>
                            <a id="search-blogs-view-all" href="<?php echo esc_url( $bacera_search_blog_url ); ?>">View all</a>
                        </div>
                        <div id="search-blogs-list" class="search-blogs-list"></div>
                    </div>
                </div>
                <div id="search-workshops-section" style="margin-top:18px;">
                    <div class="search-panel-title">
                        <h3 id="search-workshops-title">Workshops</h3>
                        <a id="search-workshops-view-all" href="<?php echo esc_url( $bacera_search_workshop_url ); ?>">View all</a>
                    </div>
                    <div id="search-workshops-list" class="search-cards-4"></div>
                </div>
            </div>
        </div>
    </div>
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
    const isSearchViewPage = <?php echo get_query_var( 'bacera_search_type' ) ? 'true' : 'false'; ?>;
    let threshold  = window.innerHeight * 0.85;

    if (isSearchViewPage && header) {
        header.classList.remove('is-hero');
        header.classList.add('force-solid');
    }

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

    openPanel(null);
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

    // ── Search overlay (rewrite full flow) ───────────────────
    const searchBtn = document.getElementById('search-btn');
    const searchOverlay = document.getElementById('search-overlay');
    const searchInput = document.getElementById('search-input');
    const searchClose = document.getElementById('search-close');
    const searchForm = document.getElementById('search-overlay-form');
    const defaultState = document.getElementById('search-default-state');
    const loadingEl = document.getElementById('search-loading');
    const emptyEl = document.getElementById('search-empty');
    const resultsEl = document.getElementById('search-results');
    const productsListEl = document.getElementById('search-products-list');
    const blogsListEl = document.getElementById('search-blogs-list');
    const workshopsListEl = document.getElementById('search-workshops-list');
    const productsSectionEl = document.getElementById('search-products-section');
    const blogsSectionEl = document.getElementById('search-blogs-section');
    const workshopsSectionEl = document.getElementById('search-workshops-section');
    const productsTitleEl = document.getElementById('search-products-title');
    const blogsTitleEl = document.getElementById('search-blogs-title');
    const workshopsTitleEl = document.getElementById('search-workshops-title');
    const productsViewAllEl = document.getElementById('search-products-view-all');
    const blogsViewAllEl = document.getElementById('search-blogs-view-all');
    const workshopsViewAllEl = document.getElementById('search-workshops-view-all');
    const SEARCH_AJAX_URL = '<?php echo esc_js( $bacera_search_ajax_url ); ?>';
    const SEARCH_NONCE = '<?php echo esc_js( $bacera_search_nonce ); ?>';
    const SEARCH_PRODUCT_URL = '<?php echo esc_js( $bacera_search_product_url ); ?>';
    const SEARCH_BLOG_URL = '<?php echo esc_js( $bacera_search_blog_url ); ?>';
    const SEARCH_WORKSHOP_URL = '<?php echo esc_js( $bacera_search_workshop_url ); ?>';
    const SEARCH_SCOPE = '<?php echo esc_js( $bacera_search_scope ); ?>';
    let searchTimer = null;

    function escHtml(v) {
        const d = document.createElement('div');
        d.textContent = String(v || '');
        return d.innerHTML;
    }
    function showDefault() {
        if (defaultState) defaultState.style.display = '';
        if (loadingEl) loadingEl.style.display = 'none';
        if (emptyEl) emptyEl.style.display = 'none';
        if (resultsEl) resultsEl.style.display = 'none';
    }
    function openSearch() {
        if (!searchOverlay) return;
        searchOverlay.classList.add('is-active');
        document.body.style.overflow = 'hidden';
        if (header) header.classList.add('force-solid');
        if (!searchInput || !searchInput.value.trim()) {
            showDefault();
        }
        setTimeout(() => searchInput && searchInput.focus(), 120);
    }
    function closeSearch() {
        if (!searchOverlay) return;
        searchOverlay.classList.remove('is-active');
        document.body.style.overflow = '';
        if (header && !isSearchViewPage) header.classList.remove('force-solid');
    }
    function updateViewAll(query) {
        const q = encodeURIComponent(query || '');
        if (productsViewAllEl) productsViewAllEl.href = SEARCH_PRODUCT_URL + '?q=' + q;
        if (blogsViewAllEl) blogsViewAllEl.href = SEARCH_BLOG_URL + '?q=' + q;
        if (workshopsViewAllEl) workshopsViewAllEl.href = SEARCH_WORKSHOP_URL + '?q=' + q;
    }
    function applyScope(scope) {
        const showProducts = scope === 'global' || scope === 'product';
        const showWorkshops = scope === 'global' || scope === 'workshop';
        const showBlogs = scope === 'global' || scope === 'blog';
        if (productsSectionEl) productsSectionEl.style.display = showProducts ? '' : 'none';
        if (workshopsSectionEl) workshopsSectionEl.style.display = showWorkshops ? '' : 'none';
        if (blogsSectionEl) blogsSectionEl.style.display = showBlogs ? '' : 'none';
    }
    function renderProducts(items) {
        if (!productsListEl) return;
        productsListEl.innerHTML = (items || []).map(item => (
            '<a class="search-card" href="' + escHtml(item.url || '#') + '">' +
                '<div class="search-card-thumb">' +
                    '<img src="' + escHtml(item.image || '') + '" alt="' + escHtml(item.title || '') + '" loading="lazy">' +
                '</div>' +
                '<p class="search-card-meta">Whispers of Clay</p>' +
                '<p class="search-card-title">' + escHtml(item.title || '') + '</p>' +
                '<p class="search-card-price">' + escHtml(item.price_text || '') + '</p>' +
            '</a>'
        )).join('');
    }
    function renderBlogs(items) {
        if (!blogsListEl) return;
        blogsListEl.innerHTML = (items || []).map(item => (
            '<a class="search-blog-item" href="' + escHtml(item.url || '#') + '">' +
                '<p class="search-blog-meta">' + escHtml(item.cat_name || 'Blog') + '</p>' +
                '<p class="search-blog-title">' + escHtml(item.title || '') + '</p>' +
                '<p class="search-blog-excerpt">' + escHtml(item.excerpt || '') + '</p>' +
            '</a>'
        )).join('');
    }
    function renderWorkshops(items) {
        if (!workshopsListEl) return;
        workshopsListEl.innerHTML = (items || []).map(item => (
            '<a class="search-card" href="' + escHtml(item.url || '#') + '">' +
                '<div class="search-card-thumb"><img src="' + escHtml(item.image || '') + '" alt="' + escHtml(item.title || '') + '" loading="lazy"></div>' +
                '<p class="search-card-meta">Workshop</p>' +
                '<p class="search-card-title">' + escHtml(item.title || '') + '</p>' +
                '<p class="search-workshop-desc">' + escHtml(item.description || '') + '</p>' +
                '<p class="search-card-price">' + escHtml(item.price || 'Contact us') + '</p>' +
            '</a>'
        )).join('');
    }
    function runSearch(keyword) {
        const q = (keyword || '').trim();
        if (q.length < 2) {
            showDefault();
            return;
        }
        if (defaultState) defaultState.style.display = 'none';
        if (resultsEl) resultsEl.style.display = 'none';
        if (emptyEl) emptyEl.style.display = 'none';
        if (loadingEl) loadingEl.style.display = '';

        const fd = new FormData();
        fd.append('action', 'bacera_header_search');
        fd.append('nonce', SEARCH_NONCE);
        fd.append('q', q);
        fd.append('scope', SEARCH_SCOPE);

        fetch(SEARCH_AJAX_URL, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (loadingEl) loadingEl.style.display = 'none';
                if (!res || !res.success || !res.data) {
                    if (emptyEl) emptyEl.style.display = '';
                    return;
                }
                const data = res.data;
                const scope = data.scope || SEARCH_SCOPE;
                applyScope(scope);
                renderProducts(data.products || []);
                renderBlogs(data.blogs || []);
                renderWorkshops(data.workshops || []);
                updateViewAll(data.query || q);
                if (productsTitleEl) productsTitleEl.textContent = 'Product search for "' + (data.query || q) + '"';
                if (blogsTitleEl) blogsTitleEl.textContent = 'Blogs';
                if (workshopsTitleEl) workshopsTitleEl.textContent = 'Workshop search for "' + (data.query || q) + '"';
                const hasData = (data.products || []).length || (data.blogs || []).length || (data.workshops || []).length;
                if (!hasData) {
                    if (emptyEl) emptyEl.style.display = '';
                    return;
                }
                if (resultsEl) resultsEl.style.display = '';
            })
            .catch(() => {
                if (loadingEl) loadingEl.style.display = 'none';
                if (emptyEl) emptyEl.style.display = '';
            });
    }

    if (searchBtn) searchBtn.addEventListener('click', openSearch);
    if (searchClose) searchClose.addEventListener('click', closeSearch);
    if (searchOverlay) {
        searchOverlay.addEventListener('click', e => { if (e.target === searchOverlay) closeSearch(); });
    }
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => runSearch(searchInput.value), 280);
        });
    }
    if (searchForm) {
        searchForm.addEventListener('submit', e => {
            e.preventDefault();
            const q = (searchInput && searchInput.value ? searchInput.value : '').trim();
            if (q.length < 2) return;
            if (SEARCH_SCOPE === 'blog') {
                window.location.href = SEARCH_BLOG_URL + '?q=' + encodeURIComponent(q);
            } else if (SEARCH_SCOPE === 'workshop') {
                window.location.href = SEARCH_WORKSHOP_URL + '?q=' + encodeURIComponent(q);
            } else {
                window.location.href = SEARCH_PRODUCT_URL + '?q=' + encodeURIComponent(q);
            }
        });
    }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeSearch();
    });

    // ── Language switch ─────────────────────────────────────
    // Map lang code → display label (cho detect khi load trang)
    var mstLangLabels = { 'en': 'English', 'vi': 'Tiếng Việt', 'fr': 'Français' };

    // Gọi GTranslate engine để dịch trang (dùng doGTranslate API chính thức)
    window.mstTranslatePage = function(lang) {
        var fromLang = 'en'; // ngôn ngữ gốc của trang
        var combo    = fromLang + '|' + lang;

        // GTranslate v3 expose doGTranslate() sau khi widget script load
        if (typeof window.doGTranslate === 'function') {
            window.doGTranslate(combo);
        } else {
            // Nếu widget chưa load xong, chờ thêm và thử lại
            var tries = 0;
            var wait  = setInterval(function() {
                tries++;
                if (typeof window.doGTranslate === 'function') {
                    clearInterval(wait);
                    window.doGTranslate(combo);
                } else if (tries > 30) {
                    clearInterval(wait); // timeout 3s
                }
            }, 100);
        }

        // Lưu lựa chọn để restore khi load trang mới
        try { localStorage.setItem('mst_lang', lang); } catch(e) {}
    };

    window.mstSwitchLang = function(lang, label) {
        // Cập nhật label hiển thị
        var el = document.getElementById('mst-current-lang');
        if (el) el.textContent = label;

        // Đánh dấu button đang active (đổi màu)
        document.querySelectorAll('#lang-panel button').forEach(function(btn) {
            btn.classList.remove('text-[#d95f47]', 'font-medium');
            btn.classList.add('text-primary-600');
        });
        // Tìm button tương ứng lang và highlight
        document.querySelectorAll('#lang-panel button').forEach(function(btn) {
            if (btn.getAttribute('onclick') && btn.getAttribute('onclick').indexOf("'" + lang + "'") !== -1) {
                btn.classList.add('text-[#d95f47]', 'font-medium');
                btn.classList.remove('text-primary-600');
            }
        });

        window.mstTranslatePage(lang);
    };

    // ── Auto-restore ngôn ngữ khi tải trang ─────────────────
    (function() {
        try {
            var savedLang = localStorage.getItem('mst_lang');
            if (savedLang && savedLang !== 'en' && mstLangLabels[savedLang]) {
                // Chờ GTranslate engine sẵn sàng rồi tự động switch
                var waitRestore = setInterval(function() {
                    if (typeof window.doGTranslate === 'function') {
                        clearInterval(waitRestore);
                        // Cập nhật label
                        var el = document.getElementById('mst-current-lang');
                        if (el) el.textContent = mstLangLabels[savedLang];
                        // Gọi dịch
                        window.doGTranslate('en|' + savedLang);
                    }
                }, 150);
                // Stop sau 5s nếu engine không load
                setTimeout(function() { clearInterval(waitRestore); }, 5000);
            }
        } catch(e) {}
    })();
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
