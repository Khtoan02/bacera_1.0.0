<?php
namespace Bacera\Database;

class CustomerTable {
    public static function createTable() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bacera_customers';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            phone varchar(50) NOT NULL,
            email varchar(100) NOT NULL,
            name varchar(150) NOT NULL,
            has_password tinyint(1) NOT NULL DEFAULT 0,
            password_hash varchar(255) NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            password_updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    public static function init() {
        // Run database creation on theme switch
        add_action( 'after_switch_theme', [__CLASS__, 'createTable'] );

        // Optionally run on admin_init checking an option to ensure it's there
        add_action('admin_init', function() {
            $db_version = get_option('bacera_customers_db_version');
            if ( $db_version !== '1.0' ) {
                self::createTable();
                update_option('bacera_customers_db_version', '1.0');
            }
        });
    }
}
