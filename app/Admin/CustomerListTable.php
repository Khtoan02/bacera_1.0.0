<?php
namespace Bacera\Admin;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class CustomerListTable extends \WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => 'customer',
            'plural'   => 'customers',
            'ajax'     => false
        ] );
    }

    public function get_columns() {
        return [
            'cb'                  => '<input type="checkbox" />',
            'name'                => 'Tên',
            'phone'               => 'SĐT',
            'email'               => 'Email',
            'has_password'        => 'Mật khẩu',
            'created_at'          => 'Ngày tạo',
            'password_updated_at' => 'Ngày cập nhật mk gần nhất'
        ];
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'name':
            case 'phone':
            case 'email':
            case 'created_at':
            case 'password_updated_at':
                return esc_html( $item[ $column_name ] );
            case 'has_password':
                return $item[ $column_name ] ? 'Tồn tại' : 'Không tồn tại';
            default:
                return print_r( $item, true );
        }
    }

    public function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="customer[]" value="%s" />',
            $item['id']
        );
    }

    public function get_sortable_columns() {
        return [
            'name'       => [ 'name', false ],
            'phone'      => [ 'phone', false ],
            'created_at' => [ 'created_at', false ]
        ];
    }

    public function prepare_items() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bacera_customers';
        
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ( $current_page - 1 ) * $per_page;

        $orderby = ( ! empty( $_GET['orderby'] ) ) ? sanitize_text_field( $_GET['orderby'] ) : 'id';
        $order = ( ! empty( $_GET['order'] ) ) ? sanitize_text_field( $_GET['order'] ) : 'DESC';

        // Make sure table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name ) {
            $this->items = [];
            return;
        }

        $total_items = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" );

        $sql = $wpdb->prepare( "SELECT * FROM $table_name ORDER BY %i %i LIMIT %d OFFSET %d", $orderby, $order === 'ASC' ? 'ASC' : 'DESC', $per_page, $offset );

        // Fallback simple query building for order by
        $allowed_orderby = ['id', 'name', 'phone', 'email', 'created_at'];
        if ( !in_array($orderby, $allowed_orderby) ) {
            $orderby = 'id';
        }
        $order_sql = $order === 'ASC' ? 'ASC' : 'DESC';
        $sql = $wpdb->prepare( "SELECT * FROM $table_name ORDER BY $orderby $order_sql LIMIT %d OFFSET %d", $per_page, $offset );
        
        $this->items = $wpdb->get_results( $sql, ARRAY_A );

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page )
        ] );
    }
}
