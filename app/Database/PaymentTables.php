<?php
namespace Bacera\Database;

class PaymentTables {

    const DB_VERSION = '1.1';

    public static function createTables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ─── TABLE 1: payment_methods ────────────────────────────────
        $tm = $wpdb->prefix . 'bacera_payment_methods';
        dbDelta("CREATE TABLE $tm (
            id           mediumint(9)  NOT NULL AUTO_INCREMENT,
            name         varchar(150)  NOT NULL,
            code         varchar(50)   NOT NULL COMMENT 'e.g. bank_transfer, momo, vnpay, cod',
            description  text          NULL,
            instructions longtext      NULL COMMENT 'Payment instructions shown to customer',
            icon_url     varchar(500)  NULL,
            settings     longtext      NULL COMMENT 'JSON settings for API keys, Secrets, etc for automated payment',
            is_active    tinyint(1)    NOT NULL DEFAULT 1,
            sort_order   smallint(5)   NOT NULL DEFAULT 0,
            created_at   datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code)
        ) $charset_collate;");

        // ─── TABLE 2: promo_codes ─────────────────────────────────────
        $tp = $wpdb->prefix . 'bacera_promo_codes';
        dbDelta("CREATE TABLE $tp (
            id              mediumint(9)  NOT NULL AUTO_INCREMENT,
            code            varchar(50)   NOT NULL,
            description     varchar(255)  NULL,
            discount_type   varchar(20)   NOT NULL DEFAULT 'percent' COMMENT 'percent|fixed',
            discount_value  decimal(10,2) NOT NULL DEFAULT 0.00,
            min_amount      decimal(10,2) NOT NULL DEFAULT 0.00,
            max_discount    decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '0 = no cap',
            max_uses        int(11)       NOT NULL DEFAULT 0 COMMENT '0 = unlimited',
            used_count      int(11)       NOT NULL DEFAULT 0,
            workshop_id     mediumint(9)  NULL COMMENT 'NULL = all workshops',
            valid_from      datetime      NULL,
            valid_to        datetime      NULL,
            is_active       tinyint(1)    NOT NULL DEFAULT 1,
            created_at      datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code)
        ) $charset_collate;");

        // ─── Seed default payment methods if none exist ───────────────
        $exists = $wpdb->get_var("SELECT COUNT(*) FROM $tm");
        if (!$exists) {
            $wpdb->insert($tm, [
                'name'         => 'Chuyển khoản ngân hàng',
                'code'         => 'bank_transfer',
                'description'  => 'Chuyển khoản trực tiếp qua tài khoản ngân hàng',
                'instructions' => "Ngân hàng: Vietcombank\nSố TK: 1234567890\nChủ TK: BACERA STUDIO\n\nNội dung chuyển khoản: [Họ tên] + [Mã ghế]\n\nAdmin sẽ xác nhận trong vòng 24h.",
                'icon_url'     => '',
                'settings'     => '{}',
                'is_active'    => 1,
                'sort_order'   => 1,
            ]);
            $wpdb->insert($tm, [
                'name'         => 'Thanh toán MoMo',
                'code'         => 'momo',
                'description'  => 'Thanh toán qua ví điện tử MoMo',
                'instructions' => "Số điện thoại MoMo: 0909090909\nTên: BACERA STUDIO\n\nNội dung: [Họ tên] + [Mã ghế]\n\nChụp màn hình giao dịch và gửi cho admin để xác nhận.",
                'icon_url'     => '',
                'settings'     => '{"partner_code":"","access_key":"","secret_key":""}',
                'is_active'    => 1,
                'sort_order'   => 2,
            ]);
            $wpdb->insert($tm, [
                'name'         => 'Thanh toán tại studio',
                'code'         => 'cod',
                'description'  => 'Thanh toán tiền mặt khi đến học',
                'instructions' => "Bạn sẽ thanh toán trực tiếp khi đến studio vào ngày học.\n\nVui lòng đến đúng giờ và mang theo số ghế đã đặt.",
                'icon_url'     => '',
                'settings'     => '{}',
                'is_active'    => 1,
                'sort_order'   => 3,
            ]);
        }
    }

    public static function init() {
        add_action('admin_init', function () {
            $installed = get_option('bacera_payment_db_version');
            if ($installed !== self::DB_VERSION) {
                self::createTables();
                update_option('bacera_payment_db_version', self::DB_VERSION);
            }
        });
        add_action('after_switch_theme', [__CLASS__, 'createTables']);
    }
}
?>
