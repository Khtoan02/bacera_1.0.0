<?php
namespace Bacera\Database;

class VideoTables {

    const DB_VERSION = '1.0';

    /**
     * Tạo 2 bảng: bacera_video_categories + bacera_videos
     */
    public static function createTables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ── TABLE 1: video_categories ────────────────────────────────
        $t_cats = $wpdb->prefix . 'bacera_video_categories';
        dbDelta( "CREATE TABLE {$t_cats} (
            id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            name        VARCHAR(120)    NOT NULL DEFAULT '',
            slug        VARCHAR(150)    NOT NULL DEFAULT '',
            description TEXT,
            order_index SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            is_active   TINYINT(1)      NOT NULL DEFAULT 1,
            created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) {$charset};" );

        // ── TABLE 2: videos ──────────────────────────────────────────
        $t_vids = $wpdb->prefix . 'bacera_videos';
        dbDelta( "CREATE TABLE {$t_vids} (
            id           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            category_id  INT UNSIGNED    NOT NULL DEFAULT 0,
            title        VARCHAR(255)    NOT NULL DEFAULT '',
            description  TEXT,
            type         VARCHAR(20)     NOT NULL DEFAULT 'upload' COMMENT 'upload|youtube',
            video_url    VARCHAR(1000)   NOT NULL DEFAULT '' COMMENT 'Media URL or YouTube URL',
            thumbnail_url VARCHAR(1000)  NOT NULL DEFAULT '',
            youtube_id   VARCHAR(50)     NOT NULL DEFAULT '',
            duration     VARCHAR(20)     NOT NULL DEFAULT '',
            order_index  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            is_active    TINYINT(1)      NOT NULL DEFAULT 1,
            created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category_id (category_id),
            KEY is_active (is_active)
        ) {$charset};" );
    }

    public static function init(): void {
        add_action( 'admin_init', function () {
            $installed = get_option( 'bacera_videos_db_version', '' );
            if ( $installed !== self::DB_VERSION ) {
                self::createTables();
                update_option( 'bacera_videos_db_version', self::DB_VERSION );
            }
        } );
        add_action( 'after_switch_theme', [ __CLASS__, 'createTables' ] );
    }
}
