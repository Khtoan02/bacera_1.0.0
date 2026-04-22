<?php
/**
 * Bacera functions and definitions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define Constants
define( 'BACERA_THEME_DIR', trailingslashit( get_template_directory() ) );
define( 'BACERA_THEME_URI', trailingslashit( get_template_directory_uri() ) );
define( 'BACERA_THEME_VERSION', '1.0.0' );
/** Tăng khi đổi rewrite theme (vd. /bacera-img/) — tự flush permalink một lần sau deploy. */
define( 'BACERA_IMG_REWRITE_VERSION', '3' );

/* ==========================================================================
   GIAI ĐOẠN 3 & 4: CẤU HÌNH ĐƯỜNG DẪN ẢO (REWRITE RULES)
   ========================================================================== */

add_action( 'init', function() {
    // 1. Đăng ký biến query để WordPress nhận diện slug ảnh 
    add_rewrite_tag( '%bacera_img_slug%', '([^&]+)' );

    // 2. Tạo quy tắc đường dẫn sạch: /pancake-img/ten-san-pham-123
    // Điều hướng về index.php với biến pancake_img_slug để xử lý streaming
    add_rewrite_rule(
        '^bacera-img/([^/]+)/?',
        'index.php?bacera_img_slug=$matches[1]',
        'top'
    );
}, 10 );

/**
 * Sau khi đăng ký rewrite: flush một lần khi version đổi (tránh server mới / WP Pusher không có rule bacera-img).
 */
add_action( 'init', function() {
    if ( ! defined( 'BACERA_IMG_REWRITE_VERSION' ) ) {
        return;
    }
    $stored = get_option( 'bacera_img_rewrite_version', '' );
    if ( (string) $stored === (string) BACERA_IMG_REWRITE_VERSION ) {
        return;
    }
    flush_rewrite_rules( false );
    update_option( 'bacera_img_rewrite_version', (string) BACERA_IMG_REWRITE_VERSION, false );
}, 99 );

// 3. Kích hoạt trạm trung chuyển ảnh khi bắt được đường dẫn ảo (plugin phải có Bacera_Utils)
add_action( 'template_redirect', function() {
    if ( ! get_query_var( 'bacera_img_slug' ) ) {
        return;
    }
    if ( ! class_exists( 'Bacera_Utils' ) || ! is_callable( array( 'Bacera_Utils', 'handle_image_streaming' ) ) ) {
        return;
    }
    Bacera_Utils::handle_image_streaming();
}, 1 );

// 4. Bắt route /our-team/{slug}
add_action( 'template_redirect', function() {
    $member_slug = get_query_var( 'bacera_member_slug' );
    if ( $member_slug ) {
        $template = locate_template( 'templates/template-member-detail.php' );
        if ( $template ) {
            $_GET['member_slug'] = $member_slug;
            include $template;
            exit;
        }
    }
} );

add_action( 'init', function() {
    add_rewrite_tag('%bacera_member_slug%', '([^/]+)');
    add_rewrite_rule(
        '^our-team/([^/]+)/?$',
        'index.php?bacera_member_slug=$matches[1]',
        'top'
    );

    // ── Blog category SEO-friendly route: /blog/category/{slug}/ ──────────
    add_rewrite_tag( '%bacera_blog_cat_slug%', '([^/]+)' );
    add_rewrite_rule(
        '^blog/category/([^/]+)/?$',
        'index.php?bacera_blog_cat_slug=$matches[1]',
        'top'
    );

    register_post_type( 'pancake_product', [
        'labels'      => [ 'name' => 'Pancake Products' ],
        'public'      => true, // Quan trọng để get_page_by_path hoạt động
        'has_archive' => false,
        'supports'    => [ 'title', 'editor', 'custom-fields', 'comments' ],
    ]);

    register_post_type( 'bacera_partner', [
        'labels'      => [ 
            'name'          => 'Partners',
            'singular_name' => 'Partner',
            'menu_name'     => 'Partners',
            'add_new'       => 'Add Partner',
            'add_new_item'  => 'Add New Partner',
        ],
        'public'      => false,
        'show_ui'     => true,
        'show_in_menu'=> 'bacera-main',
        'has_archive' => false,
        'supports'    => [ 'title', 'thumbnail', 'page-attributes' ], // menu_order needed for ordering
    ]);
});
/* ==========================================================================
   AUTHENTICATION & LOGOUT HANDLER
   ========================================================================== */

add_action( 'init', function() {
    if ( empty( $_GET['bacera_logout'] ) ) return;
    
    $params = [
        'expires'  => time() - 3600,
        'path'     => COOKIEPATH ?: '/',
        'domain'   => COOKIE_DOMAIN ?: '',
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    setcookie( 'bacera_customer_auth', '', $params );
    setcookie( 'bacera_customer_auth', '', time() - 3600, '/', '' );
    unset( $_COOKIE['bacera_customer_auth'] );
    wp_safe_redirect( home_url( '/' ) );
    exit;
}, 1 );

// Simple Autoloader for MVC
spl_autoload_register(function ($class) {
    $prefix = 'Bacera\\';
    $base_dir = BACERA_THEME_DIR . 'app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use Bacera\Controllers\MainController;

function bacera_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );
    
    register_nav_menus( array(
        'menu-1' => esc_html__( 'Primary Menu', 'bacera' ),
        'footer-menu' => esc_html__( 'Footer Menu', 'bacera' ),
    ) );
}
add_action( 'after_setup_theme', 'bacera_setup' );

function bacera_enqueue_scripts() {
    wp_enqueue_style( 'bacera-style', get_stylesheet_uri(), array(), BACERA_THEME_VERSION );
    wp_enqueue_style( 'bacera-main-css', BACERA_THEME_URI . 'assets/css/main.css', array(), BACERA_THEME_VERSION );
    wp_enqueue_script( 'bacera-main-js', BACERA_THEME_URI . 'assets/js/main.js', array('jquery'), BACERA_THEME_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'bacera_enqueue_scripts' );

/**
 * Quy tắc rewrite /bacera-img/ đã được ghi vào DB (sau Settings → Permalinks hoặc sau flush tự động của theme).
 *
 * @return bool
 */
function bacera_img_rewrite_is_flushed() {
    $rules = get_option( 'rewrite_rules' );
    if ( ! is_array( $rules ) ) {
        return false;
    }
    foreach ( $rules as $pattern => $query ) {
        if ( is_string( $pattern ) && strpos( $pattern, 'bacera-img' ) !== false ) {
            return true;
        }
        if ( is_string( (string) $query ) && strpos( (string) $query, 'bacera_img_slug' ) !== false ) {
            return true;
        }
    }
    return false;
}

/**
 * Logo sáng (dùng trên nền trắng / header cuộn).
 * Ưu tiên: Admin upload → Pancake proxy → Fallback hardcode.
 *
 * @return string
 */
function bacera_get_brand_logo_url() {
    // 1. Admin upload via Bacera Cấu hình → Giao diện
    $light_id = (int) get_option( 'bacera_logo_light_id', 0 );
    if ( $light_id ) {
        $url = wp_get_attachment_image_url( $light_id, 'full' );
        if ( $url ) return $url;
    }

    // 2. Pancake shop logo proxy
    if ( class_exists( 'Bacera_Utils' ) ) {
        $proxy = Bacera_Utils::get_pancake_shop_logo_proxy_url();
        if ( is_string( $proxy ) && $proxy !== '' ) {
            if ( bacera_img_rewrite_is_flushed() ) return $proxy;
            $direct = get_option( 'bacera_pancake_shop_avatar_source_url', '' );
            if ( is_string( $direct ) && $direct !== '' && function_exists( 'wp_http_validate_url' ) && wp_http_validate_url( $direct ) ) {
                return $direct;
            }
            return $proxy;
        }
    }

    // 3. Hardcode fallback (file upload tháng 3/2026)
    $upload     = wp_upload_dir();
    $upload_rel = '/2026/03/Logo.png';
    $upload_url = isset( $upload['baseurl'] ) ? $upload['baseurl'] . $upload_rel : '';
    return $upload_url;
}

/**
 * Logo tối (dùng trên nền đen / hero / mobile menu tối).
 * Nếu chưa upload dark logo → dùng light logo (filter CSS sẽ đảo màu ở header).
 *
 * @return string
 */
function bacera_get_brand_logo_dark_url() {
    $dark_id = (int) get_option( 'bacera_logo_dark_id', 0 );
    if ( $dark_id ) {
        $url = wp_get_attachment_image_url( $dark_id, 'full' );
        if ( $url ) return $url;
    }
    // Fallback: dùng light logo (header.php sẽ áp dụng filter: brightness(0) invert(1))
    return bacera_get_brand_logo_url();
}

/**
 * Favicon URL từ admin (hoặc WP site icon nếu chưa upload riêng).
 *
 * @return string
 */
function bacera_get_favicon_url() {
    $fav_id = (int) get_option( 'bacera_favicon_id', 0 );
    if ( $fav_id ) {
        $url = wp_get_attachment_image_url( $fav_id, 'full' );
        if ( $url ) return $url;
    }
    // Fallback: WP site icon
    $site_icon_id = (int) get_option( 'site_icon', 0 );
    if ( $site_icon_id ) {
        return wp_get_attachment_image_url( $site_icon_id, 'full' );
    }
    return '';
}

// Boot the main controller
add_action('init', function() {
    new MainController();
});

// Flush rewrite rules khi theme được kích hoạt (để đăng ký route /workshop/{slug})
add_action('after_switch_theme', function() {
    // Trigger rewrite rule registration first
    ( new \Bacera\Controllers\MainController() );
    flush_rewrite_rules();
});

// Flush rewrite rules tự động nếu rule của workshop chưa tồn tại trong DB
add_action('init', function() {
    $rules = get_option('rewrite_rules');
    // Kiểm tra xem rule workshop / our-team / blog-category có tồn tại chưa
    if ( empty($rules)
        || ! isset($rules['^workshop/([^/]+)/?$'])
        || ! isset($rules['^our-team/([^/]+)/?$'])
        || ! array_key_exists( '^blog/category/([^/]+)/?$', (array) $rules )
    ) {
        // Flush vào cuối request này (an toàn và được lưu vào DB ngay)
        add_action('shutdown', function() {
            flush_rewrite_rules(true);
        });
    }
}, 999);

/**
 * Lưu metadata cho review sản phẩm Pancake.
 */
add_action(
    'comment_post',
    function( $comment_id, $comment_approved, $commentdata ) {
        if ( empty( $commentdata['comment_post_ID'] ) ) {
            return;
        }
        $post_id = (int) $commentdata['comment_post_ID'];
        if ( get_post_type( $post_id ) !== 'pancake_product' ) {
            return;
        }

        $rating = isset( $_POST['rating'] ) ? (int) $_POST['rating'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( $rating >= 1 && $rating <= 5 ) {
            update_comment_meta( $comment_id, 'rating', $rating );
        }

        $review_title = isset( $_POST['review_title'] ) ? sanitize_text_field( wp_unslash( $_POST['review_title'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( $review_title !== '' ) {
            update_comment_meta( $comment_id, 'review_title', $review_title );
        }
    },
    10,
    3
);

/**
 * Cho phép đánh giá trên CPT pancake_product (kể cả bài cũ đang tắt comment).
 */
add_filter(
    'comments_open',
    function( $open, $post_id ) {
        if ( get_post_type( (int) $post_id ) === 'pancake_product' ) {
            return true;
        }
        return $open;
    },
    10,
    2
);

/**
 * Sau khi gửi review, quay lại tab đánh giá (?tab=reviews#...).
 * Core mặc định dùng referer, không đọc field redirect_to từ form.
 */
add_filter(
    'comment_post_redirect',
    function( $location, $comment ) {
        if ( empty( $_POST['bacera_review_redirect'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return $location;
        }
        $url = esc_url_raw( wp_unslash( $_POST['bacera_review_redirect'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return wp_validate_redirect( $url, $location );
    },
    10,
    2
);

/* ==========================================================================
   BLOG CATEGORY — SEO-FRIENDLY ROUTE & AJAX HANDLER
   ========================================================================== */


/**
 * Template redirect cho route /blog/category/{slug}/:
 * Serve template-blog-category.php và truyền slug qua query var.
 */
add_action( 'template_redirect', function() {
    $cat_slug = get_query_var( 'bacera_blog_cat_slug' );
    if ( ! $cat_slug ) return;

    // Pass slug để template đọc
    $_GET['cat_slug'] = sanitize_key( $cat_slug );

    $template = locate_template( 'templates/template-blog-category.php' );
    if ( $template ) {
        include $template;
        exit;
    }
} );

/**
 * AJAX handler: trả danh sách bài viết theo danh mục — dùng cho in-page filtering.
 * Action: bacera_get_blog_posts
 */
function bacera_ajax_get_blog_posts(): void {
    check_ajax_referer( 'bacera_blog_cat_nonce', 'nonce' );

    $cat_slug = sanitize_key( $_POST['cat_slug'] ?? '' );
    $page     = max( 1, (int) ( $_POST['page']     ?? 1 ) );
    $per_page = min( 24, max( 1, (int) ( $_POST['per_page'] ?? 9 ) ) );

    $args = [
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => false,
    ];

    if ( $cat_slug ) {
        $cat_obj = get_category_by_slug( $cat_slug );
        if ( $cat_obj ) {
            $args['cat'] = $cat_obj->term_id;
        } else {
            wp_send_json_error( ['message' => 'Category not found'], 404 );
        }
    }

    $query = new WP_Query( $args );

    $posts = [];
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $p_id    = get_the_ID();
            $p_cats  = get_the_category( $p_id );
            $p_thumb = get_the_post_thumbnail_url( $p_id, 'medium_large' )
                ?: 'https://images.unsplash.com/photo-1530018607912-eff2daa1bac4?auto=format&fit=crop&q=80&w=600';
            $excerpt = get_the_excerpt()
                ?: wp_trim_words( strip_tags( get_the_content() ), 18, '…' );

            $posts[] = [
                'id'       => $p_id,
                'title'    => get_the_title(),
                'url'      => get_permalink(),
                'thumb'    => esc_url( $p_thumb ),
                'excerpt'  => $excerpt,
                'date'     => get_the_date( 'd M Y' ),
                'author'   => get_the_author(),
                'cat_name' => $p_cats ? $p_cats[0]->name : '',
                'cat_slug' => $p_cats ? $p_cats[0]->slug : '',
            ];
        }
        wp_reset_postdata();
    }

    wp_send_json_success( [
        'posts'       => $posts,
        'total'       => (int) $query->found_posts,
        'total_pages' => (int) $query->max_num_pages,
        'page'        => $page,
    ] );
}
add_action( 'wp_ajax_bacera_get_blog_posts',        'bacera_ajax_get_blog_posts' );
add_action( 'wp_ajax_nopriv_bacera_get_blog_posts', 'bacera_ajax_get_blog_posts' );

// Load Theme Customizer settings
require_once BACERA_THEME_DIR . 'inc/customizer.php';

/* ==========================================================================
   GTRANSLATE — AUTO-CONFIGURE FOR BACERA
   Tự động set GTranslate settings phù hợp với custom language switcher
   của header Bacera (en, vi, fr). Chỉ update nếu chưa config đúng.
   ========================================================================== */
add_action( 'init', function() {
    if ( ! is_admin() ) return;
    if ( ! function_exists( 'get_option' ) ) return;

    $gt_data = get_option( 'GTranslate' );
    if ( ! is_array( $gt_data ) ) $gt_data = array();

    // Kiểm tra xem đã config đúng chưa (marker key)
    if ( ! empty( $gt_data['bacera_configured_v1'] ) ) return;

    // Set đúng settings cho Bacera theme
    $gt_data['default_language']          = 'en';
    $gt_data['widget_look']               = 'flags_name';
    $gt_data['floating_language_selector'] = 'no';  // Bacera dùng custom dropdown
    $gt_data['show_in_menu']              = '';
    $gt_data['enable_cdn']                = 1;      // Dùng CDN để load nhanh hơn
    $gt_data['detect_browser_language']   = '';
    $gt_data['native_language_names']     = '';
    $gt_data['wrapper_selector']          = '.gtranslate_wrapper';
    $gt_data['flag_style']                = '2d';
    $gt_data['flag_size']                 = 24;

    // Danh sách ngôn ngữ: en, vi, fr (khớp với header Bacera)
    $gt_data['fincl_langs'] = array( 'en', 'vi', 'fr' );
    $gt_data['incl_langs']  = array( 'en', 'vi', 'fr' );

    // Marker để không chạy lại lần sau
    $gt_data['bacera_configured_v1'] = true;

    update_option( 'GTranslate', $gt_data );
}, 20 );

