<?php
namespace Bacera\Controllers;

/**
 * PaymentController — Frontend AJAX endpoints for:
 *  - Getting active payment methods
 *  - Validating promo codes
 */
class PaymentController {

    public function __construct() {
        add_action('wp_ajax_nopriv_bacera_get_payment_methods', [$this, 'handleGetMethods']);
        add_action('wp_ajax_bacera_get_payment_methods',        [$this, 'handleGetMethods']);

        add_action('wp_ajax_nopriv_bacera_validate_promo', [$this, 'handleValidatePromo']);
        add_action('wp_ajax_bacera_validate_promo',        [$this, 'handleValidatePromo']);
    }

    /**
     * Return all active payment methods.
     */
    public function handleGetMethods() {
        global $wpdb;
        $tm = $wpdb->prefix . 'bacera_payment_methods';

        // Create table if not exists yet
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tm}'") !== $tm) {
            \Bacera\Database\PaymentTables::createTables();
        }

        $methods = $wpdb->get_results(
            "SELECT id, name, code, description, instructions, icon_url FROM {$tm}
             WHERE is_active = 1 ORDER BY sort_order ASC, id ASC",
            ARRAY_A
        );

        wp_send_json_success(['methods' => $methods ?: []]);
    }

    /**
     * Validate a promo code against a given order amount and workshop.
     * POST: code, amount (numeric), workshop_id
     */
    public function handleValidatePromo() {
        $code        = strtoupper(sanitize_text_field($_POST['code'] ?? ''));
        $amount      = (float) ($_POST['amount'] ?? 0);
        $workshop_id = intval($_POST['workshop_id'] ?? 0);

        if (empty($code)) {
            wp_send_json_error(['message' => 'Vui lòng nhập mã khuyến mãi.']);
        }

        global $wpdb;
        $tp = $wpdb->prefix . 'bacera_promo_codes';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$tp}'") !== $tp) {
            \Bacera\Database\PaymentTables::createTables();
        }

        $promo = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$tp} WHERE code = %s AND is_active = 1", $code),
            ARRAY_A
        );

        if (!$promo) {
            wp_send_json_error(['message' => 'Mã khuyến mãi không tồn tại hoặc đã hết hạn.']);
        }

        // Check workshop scope
        if (!empty($promo['workshop_id']) && (int)$promo['workshop_id'] !== $workshop_id) {
            wp_send_json_error(['message' => 'Mã này không áp dụng cho workshop hiện tại.']);
        }

        // Check validity dates
        $now = current_time('timestamp');
        if (!empty($promo['valid_from']) && strtotime($promo['valid_from']) > $now) {
            wp_send_json_error(['message' => 'Mã khuyến mãi chưa đến ngày áp dụng.']);
        }
        if (!empty($promo['valid_to']) && strtotime($promo['valid_to']) < $now) {
            wp_send_json_error(['message' => 'Mã khuyến mãi đã hết hạn sử dụng.']);
        }

        // Check usage limit
        if ((int)$promo['max_uses'] > 0 && (int)$promo['used_count'] >= (int)$promo['max_uses']) {
            wp_send_json_error(['message' => 'Mã khuyến mãi đã được sử dụng hết lượt.']);
        }

        // Check min amount
        if ((float)$promo['min_amount'] > 0 && $amount < (float)$promo['min_amount']) {
            $min_fmt = number_format((float)$promo['min_amount'], 0, ',', '.');
            wp_send_json_error(['message' => "Đơn hàng tối thiểu {$min_fmt}đ để dùng mã này."]);
        }

        // Calculate discount
        $discount = 0;
        if ($promo['discount_type'] === 'percent') {
            $discount = $amount * ((float)$promo['discount_value'] / 100);
            // Cap if max_discount set
            if ((float)$promo['max_discount'] > 0) {
                $discount = min($discount, (float)$promo['max_discount']);
            }
        } else {
            $discount = (float)$promo['discount_value'];
        }
        $discount  = min($discount, $amount); // Can't discount more than amount
        $final     = max(0, $amount - $discount);

        wp_send_json_success([
            'code'          => $promo['code'],
            'description'   => $promo['description'],
            'discount_type' => $promo['discount_type'],
            'discount_value'=> (float)$promo['discount_value'],
            'discount_amount'=> round($discount),
            'final_amount'  => round($final),
        ]);
    }
}
?>
