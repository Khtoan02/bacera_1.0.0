<?php
namespace Bacera\Controllers;

/**
 * Main Controller used for setting up hooks, assets, routing helpers to the views
 */
class MainController {
    public function __construct() {
        // Initialize hooks via models or specific components
        add_action('wp_head', [$this, 'outputCustomMeta']);

        // Initialize AJAX Controllers
        new AuthController();

        // Initialize Admin Custom Controllers
        if ( is_admin() ) {
            new AdminCustomerController();
            new TurnstileSettingsController();
        }
    }

    /**
     * Chuẩn bị dữ liệu sản phẩm để truyền vào Component Product Card
     * Đảm bảo đường dẫn ảnh sạch (SEO) và định dạng giá đúng chuẩn Bacera.
     */
    public function get_product_data($post) {
        if (!$post instanceof \WP_Post) {
            $post = get_post($post);
        }

        // Lấy giá gốc và giá hiện tại từ metadata
        $regular_price = get_post_meta($post->ID, '_regular_price', true);
        $sale_price = get_post_meta($post->ID, '_price', true);
        
        // Tính toán phần trăm giảm giá nếu có
        $discount = '';
        if ($regular_price && $sale_price && (float)$regular_price > (float)$sale_price) {
            $percent = round((($regular_price - $sale_price) / $regular_price) * 100);
            $discount = '-' . $percent . '%';
        }

        return [
            'name'          => get_the_title($post), // Tên sản phẩm
            'brand'         => 'Bacera', // Thương hiệu
            'price'         => number_format($sale_price) . ' VND', // Giá bán
            'originalPrice' => $regular_price ? number_format($regular_price) . ' VND' : '',
            'discount'      => $discount,
            'image'         => home_url("/pancake-img/{$post->post_name}.jpg"), // Đường dẫn ảo SEO
            'class'         => ''
        ];
    }

    public function outputCustomMeta() {
        echo '';
    }
}