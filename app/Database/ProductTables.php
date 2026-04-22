<?php
namespace Bacera\Database;

/**
 * ProductTables — Schema cho hệ thống quản trị sản phẩm mở rộng của Bacera.
 *
 * Triết lý thiết kế:
 *   - Pancake POS quản lý: tên, giá, mô tả ngắn, SKU, trạng thái bán.
 *   - Bacera DB quản lý: bài viết dài, hình ảnh, đánh giá, metadata danh mục.
 *   - Key kết nối: pancake_product_id / pancake_category_id.
 *
 * Các bảng:
 *   1. bacera_product_meta    — Nội dung mở rộng theo sản phẩm Pancake
 *   2. bacera_product_images  — Thư viện ảnh của sản phẩm
 *   3. bacera_product_reviews — Đánh giá sản phẩm từ khách hàng
 *   4. bacera_category_meta   — Dữ liệu mở rộng danh mục (hình ảnh, mô tả...)
 */
class ProductTables {

    const DB_VERSION = '1.0';

    public static function createTables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        /* ── TABLE 1: product_meta ────────────────────────────────────────
         * Key: pancake_product_id (string/int từ Pancake)
         * Lưu nội dung mở rộng không có trong Pancake.
         * ---------------------------------------------------------------- */
        $t_meta = $wpdb->prefix . 'bacera_product_meta';
        dbDelta( "CREATE TABLE {$t_meta} (
            id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            pancake_product_id  VARCHAR(64)     NOT NULL      COMMENT 'ID sản phẩm từ Pancake POS',
            long_description    LONGTEXT                      COMMENT 'Nội dung bài viết dài (HTML)',
            story               TEXT                          COMMENT 'Câu chuyện sản phẩm ngắn',
            materials           VARCHAR(500)                  COMMENT 'Chất liệu, vật liệu',
            dimensions          VARCHAR(255)                  COMMENT 'Kích thước sản phẩm',
            care_instructions   TEXT                          COMMENT 'Hướng dẫn bảo quản',
            seo_title           VARCHAR(255)                  COMMENT 'Tiêu đề SEO',
            seo_description     VARCHAR(500)                  COMMENT 'Meta description SEO',
            slug_override       VARCHAR(255)                  COMMENT 'Slug tuỳ chỉnh (nếu khác Pancake)',
            is_featured         TINYINT(1)      NOT NULL DEFAULT 0  COMMENT '1 = sản phẩm nổi bật',
            sort_order          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY pancake_product_id (pancake_product_id)
        ) {$charset};" );

        /* ── TABLE 2: product_images ──────────────────────────────────────
         * Thư viện hình ảnh riêng (khác ảnh chính từ Pancake).
         * ---------------------------------------------------------------- */
        $t_images = $wpdb->prefix . 'bacera_product_images';
        dbDelta( "CREATE TABLE {$t_images} (
            id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            pancake_product_id  VARCHAR(64)     NOT NULL,
            attachment_id       INT UNSIGNED    NOT NULL DEFAULT 0  COMMENT 'WP Media Library ID (nếu upload qua WP)',
            image_url           VARCHAR(1000)   NOT NULL DEFAULT '' COMMENT 'URL ảnh (có thể từ CDN ngoài)',
            alt_text            VARCHAR(255)    NOT NULL DEFAULT '',
            caption             VARCHAR(500)    NOT NULL DEFAULT '',
            is_primary          TINYINT(1)      NOT NULL DEFAULT 0  COMMENT '1 = ảnh đại diện chính',
            sort_order          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY pancake_product_id (pancake_product_id),
            KEY sort_order (sort_order)
        ) {$charset};" );

        /* ── TABLE 3: product_reviews ─────────────────────────────────────
         * Đánh giá sản phẩm từ khách hàng Bacera.
         * ---------------------------------------------------------------- */
        $t_reviews = $wpdb->prefix . 'bacera_product_reviews';
        dbDelta( "CREATE TABLE {$t_reviews} (
            id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            pancake_product_id  VARCHAR(64)     NOT NULL,
            customer_id         INT UNSIGNED            DEFAULT NULL  COMMENT 'FK bacera_customers.id',
            customer_name       VARCHAR(150)    NOT NULL DEFAULT '',
            customer_email      VARCHAR(150)    NOT NULL DEFAULT '',
            rating              TINYINT UNSIGNED NOT NULL DEFAULT 5   COMMENT '1–5 sao',
            review_title        VARCHAR(255)    NOT NULL DEFAULT '',
            review_text         TEXT            NOT NULL,
            admin_reply         TEXT                    COMMENT 'Phản hồi của admin',
            status              VARCHAR(20)     NOT NULL DEFAULT 'pending'  COMMENT 'pending|approved|rejected',
            is_verified         TINYINT(1)      NOT NULL DEFAULT 0   COMMENT '1 = đã xác minh mua hàng',
            helpful_count       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY pancake_product_id (pancake_product_id),
            KEY status (status),
            KEY customer_id (customer_id)
        ) {$charset};" );

        /* ── TABLE 4: category_meta ───────────────────────────────────────
         * Dữ liệu mở rộng cho danh mục Pancake (thêm hình ảnh, mô tả...).
         * Pancake không có hình đại diện danh mục → lưu ở đây.
         * ---------------------------------------------------------------- */
        $t_cats = $wpdb->prefix . 'bacera_category_meta';
        dbDelta( "CREATE TABLE {$t_cats} (
            id                   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            pancake_category_id  VARCHAR(64)     NOT NULL      COMMENT 'ID danh mục từ Pancake POS',
            name_override        VARCHAR(150)    NOT NULL DEFAULT '' COMMENT 'Tên hiển thị tuỳ chỉnh (nếu muốn khác Pancake)',
            slug                 VARCHAR(150)    NOT NULL DEFAULT '',
            description          TEXT                          COMMENT 'Mô tả danh mục (hiển thị frontend)',
            attachment_id        INT UNSIGNED    NOT NULL DEFAULT 0  COMMENT 'WP Media Library ID',
            image_url            VARCHAR(1000)   NOT NULL DEFAULT '' COMMENT 'URL ảnh đại diện danh mục',
            banner_url           VARCHAR(1000)   NOT NULL DEFAULT '' COMMENT 'URL banner (hero image)',
            sort_order           SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            is_featured          TINYINT(1)      NOT NULL DEFAULT 0,
            is_active            TINYINT(1)      NOT NULL DEFAULT 1,
            created_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY pancake_category_id (pancake_category_id),
            KEY slug (slug)
        ) {$charset};" );
    }

    public static function init(): void {
        add_action( 'admin_init', function () {
            $installed = get_option( 'bacera_products_db_version', '' );
            if ( $installed !== self::DB_VERSION ) {
                self::createTables();
                update_option( 'bacera_products_db_version', self::DB_VERSION );
            }
        } );
        add_action( 'after_switch_theme', [ __CLASS__, 'createTables' ] );
    }
}
