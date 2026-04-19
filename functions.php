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
define( 'BACERA_IMG_REWRITE_VERSION', '2' );

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
 * Logo header/footer: proxy /bacera-img/… khi rewrite đã flush; nếu chưa flush thì dùng URL gốc Pancake CDN
 * (tránh ảnh vỡ trên server mới). Fallback file upload / theme.
 *
 * @return string
 */
function bacera_get_brand_logo_url() {
    $upload     = wp_upload_dir();
    $upload_rel = '/2026/03/Logo.png';
    $upload_abs = isset( $upload['basedir'] ) ? $upload['basedir'] . $upload_rel : '';
    $upload_url = isset( $upload['baseurl'] ) ? $upload['baseurl'] . $upload_rel : '';

    if ( class_exists( 'Bacera_Utils' ) ) {
        $proxy = Bacera_Utils::get_pancake_shop_logo_proxy_url();
        if ( is_string( $proxy ) && $proxy !== '' ) {
            if ( bacera_img_rewrite_is_flushed() ) {
                return $proxy;
            }
            $direct = get_option( 'bacera_pancake_shop_avatar_source_url', '' );
            if ( is_string( $direct ) && $direct !== '' && function_exists( 'wp_http_validate_url' ) && wp_http_validate_url( $direct ) ) {
                return $direct;
            }
            return $proxy;
        }
    }

    if ( $upload_abs !== '' && file_exists( $upload_abs ) ) {
        return $upload_url;
    }

    return $upload_url;
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
    // Kiểm tra xem rule workshop có tồn tại chưa
    if ( empty($rules) || ! isset($rules['^workshop/([^/]+)/?$']) || ! isset($rules['^our-team/([^/]+)/?$']) ) {
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
