<?php
namespace Bacera\Database;

class WorkshopTables {

    const DB_VERSION = '2.2'; // bumped: added price, reg_start, reg_end to workshop_slots

    /**
     * Create all 4 custom workshop tables using dbDelta
     */
    public static function createTables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ─── TABLE 1: workshops ──────────────────────────────────────
        $t_workshops = $wpdb->prefix . 'bacera_workshops';
        dbDelta("CREATE TABLE $t_workshops (
            id          mediumint(9) NOT NULL AUTO_INCREMENT,
            title       varchar(255) NOT NULL,
            slug        varchar(255) NOT NULL,
            description longtext     NULL,
            price       varchar(100) NULL DEFAULT 'Liên hệ',
            duration    varchar(100) NULL,
            trainer     varchar(255) NULL,
            target      varchar(255) NULL,
            includes    longtext     NULL COMMENT 'JSON array of included items',
            excludes    longtext     NULL COMMENT 'JSON array of excluded items',
            process     longtext     NULL COMMENT 'JSON array of process steps',
            thumbnail   varchar(500) NULL,
            gallery     longtext     NULL COMMENT 'JSON array of image URLs',
            status      varchar(20)  NOT NULL DEFAULT 'active' COMMENT 'active|draft|archived',
            created_at  datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;");

        // ─── TABLE 2: workshop_slots ─────────────────────────────────
        $t_slots = $wpdb->prefix . 'bacera_workshop_slots';
        dbDelta("CREATE TABLE $t_slots (
            id           mediumint(9) NOT NULL AUTO_INCREMENT,
            workshop_id  mediumint(9) NOT NULL,
            slot_date    date         NOT NULL,
            time_start   varchar(10)  NOT NULL DEFAULT '09:00',
            time_end     varchar(10)  NOT NULL DEFAULT '12:00',
            price        varchar(100) NULL COMMENT 'Slot specific price',
            reg_start    datetime     NULL COMMENT 'Registration opens at',
            reg_end      datetime     NULL COMMENT 'Registration closes at',
            total_seats  tinyint(3)   NOT NULL DEFAULT 16,
            booked_seats tinyint(3)   NOT NULL DEFAULT 0,
            status       varchar(20)  NOT NULL DEFAULT 'open' COMMENT 'open|full|cancelled',
            notes        text         NULL,
            created_at   datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY workshop_id (workshop_id)
        ) $charset_collate;");

        // ─── TABLE 3: workshop_bookings ──────────────────────────────
        $t_bookings = $wpdb->prefix . 'bacera_workshop_bookings';
        dbDelta("CREATE TABLE $t_bookings (
            id              mediumint(9)  NOT NULL AUTO_INCREMENT,
            slot_id         mediumint(9)  NOT NULL,
            workshop_id     mediumint(9)  NOT NULL,
            customer_name   varchar(150)  NOT NULL,
            phone           varchar(50)   NOT NULL,
            email           varchar(100)  NULL,
            customer_id     mediumint(9)  NULL,
            city            varchar(100)  NULL,
            seats_selected  varchar(255)  NULL COMMENT 'Comma-separated seat numbers e.g. 1,2,3',
            num_seats       tinyint(3)    NOT NULL DEFAULT 1,
            status          varchar(20)   NOT NULL DEFAULT 'pending' COMMENT 'pending|confirmed|cancelled',
            payment_status  varchar(20)   NOT NULL DEFAULT 'unpaid' COMMENT 'unpaid|deposited|paid',
            payment_method  varchar(50)   NULL DEFAULT 'cod',
            promo_code      varchar(50)   NULL,
            checked_in      tinyint(1)    NOT NULL DEFAULT 0,
            checked_in_at   datetime      NULL,
            notes           text          NULL,
            created_at      datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at      datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY slot_id (slot_id),
            KEY workshop_id (workshop_id),
            KEY status (status)
        ) $charset_collate;");

        // ─── TABLE 4: workshop_reviews ───────────────────────────────
        $t_reviews = $wpdb->prefix . 'bacera_workshop_reviews';
        dbDelta("CREATE TABLE $t_reviews (
            id           mediumint(9) NOT NULL AUTO_INCREMENT,
            workshop_id  mediumint(9) NOT NULL,
            booking_id   mediumint(9) NULL,
            author_name  varchar(150) NOT NULL,
            author_email varchar(100) NULL,
            rating       tinyint(1)   NOT NULL DEFAULT 5,
            review_text  text         NOT NULL,
            status       varchar(20)  NOT NULL DEFAULT 'pending' COMMENT 'pending|approved|rejected',
            created_at   datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY workshop_id (workshop_id)
        ) $charset_collate;");
    }

    public static function init() {
        add_action('admin_init', function () {
            $installed = get_option('bacera_workshops_db_version');
            if ($installed !== self::DB_VERSION) {
                self::createTables();
                update_option('bacera_workshops_db_version', self::DB_VERSION);
            }
        });
        add_action('after_switch_theme', [__CLASS__, 'createTables']);
    }
}
?>
