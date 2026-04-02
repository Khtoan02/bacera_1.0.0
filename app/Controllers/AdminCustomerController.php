<?php
namespace Bacera\Controllers;

use Bacera\Database\CustomerTable;

class AdminCustomerController {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'init_database' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
    }

    public function init_database() {
        $db_version = get_option('bacera_customers_db_version');
        if ( $db_version !== '1.2' ) {
            CustomerTable::createTable();
            update_option('bacera_customers_db_version', '1.2');
        }
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'bacera-customers') !== false) {
            // Load theme's tailwind & app logic to admin just for this page
            wp_enqueue_style( 'bacera-main-css', BACERA_THEME_URI . 'assets/css/main.css', array(), BACERA_THEME_VERSION );
            wp_enqueue_script( 'bacera-main-js', BACERA_THEME_URI . 'assets/js/main.js', array('jquery'), BACERA_THEME_VERSION, true );
            
            // Need to fix potential conflicts with WP admin styles and Tailwind reset
            $custom_css = "
                #wpcontent { padding-left: 0; }
                .bacera-dashboard {
                    /* Isolate some Tailwind sizing logic inside WP admin */
                    font-family: inherit;
                }
                .bacera-dashboard * {
                    box-sizing: border-box;
                }
                .bacera-dashboard h1 { margin-top: 0; padding-top: 1rem; font-size: 1.5rem; font-weight: bold; line-height: 2rem; }
                .bacera-dashboard th, .bacera-dashboard td {
                    padding: 1rem;
                    text-align: left;
                }
                .bacera-dashboard thead { background-color: #f3f4f6; }
                .bacera-dashboard table { width: 100%; border-collapse: collapse; }
                .bacera-dashboard tr { border-bottom: 1px solid #e5e7eb; }
                .bacera-dashboard tr:hover { background-color: #f9fafb; }
            ";
            wp_add_inline_style('bacera-main-css', $custom_css);
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            'Bacera',
            'Bacera',
            'manage_options',
            'bacera-main',
            [ $this, 'main_page_callback' ],
            'dashicons-admin-generic',
            30
        );

        add_submenu_page(
            'bacera-main',
            'Khách hàng',
            'Khách hàng',
            'manage_options',
            'bacera-customers',
            [ $this, 'customers_page_callback' ]
        );
    }

    public function main_page_callback() {
        echo '<div class="wrap">';
        echo '<h1>Bacera Settings</h1>';
        echo '<p>Welcome to Bacera Theme Settings.</p>';
        echo '</div>';
    }

    public function customers_page_callback() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bacera_customers';

        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name ) {
            echo '<div class="wrap"><h1>Database table missing. Please wait for initialization...</h1></div>';
            return;
        }

        // Search terminology
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $where = "1=1";
        if ( $search ) {
            $wild = '%' . $wpdb->esc_like( $search ) . '%';
            $where .= $wpdb->prepare(" AND (name LIKE %s OR phone LIKE %s OR email LIKE %s)", $wild, $wild, $wild);
        }

        // Stats
        $total_customers = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
        $total_active_pass = $wpdb->get_var("SELECT COUNT(id) FROM $table_name WHERE has_password = 1");
        $recent_users = $wpdb->get_var("SELECT COUNT(id) FROM $table_name WHERE created_at >= '" . date('Y-m-d H:i:s', strtotime('-30 days')) . "'");

        // Pagination
        $per_page = 15;
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($paged - 1) * $per_page;
        $filtered_total = $wpdb->get_var("SELECT COUNT(id) FROM $table_name WHERE $where");
        
        // Fetch users
        $customers = $wpdb->get_results("SELECT * FROM $table_name WHERE $where ORDER BY id DESC LIMIT $per_page OFFSET $offset", ARRAY_A);

        // Load our custom dashboard view
        set_query_var('bacera_dashboard_data', [
            'total_customers' => $total_customers,
            'total_active_pass' => $total_active_pass,
            'recent_users' => $recent_users,
            'customers' => $customers,
            'search' => $search,
            'paged' => $paged,
            'total_pages' => ceil($filtered_total / $per_page),
            'filtered_total' => $filtered_total
        ]);

        get_template_part('app/Views/admin/customer-dashboard');
    }
}
