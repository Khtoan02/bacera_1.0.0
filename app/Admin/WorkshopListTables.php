<?php
namespace Bacera\Admin;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

// ─── WORKSHOPS LIST ───────────────────────────────────────────────────────────
class WorkshopListTable extends \WP_List_Table {
    public function __construct() {
        parent::__construct(['singular' => 'workshop', 'plural' => 'workshops', 'ajax' => false]);
    }
    public function get_columns() {
        return [
            'cb'         => '<input type="checkbox" />',
            'title'      => 'Tên Workshop',
            'price'      => 'Giá',
            'duration'   => 'Thời lượng',
            'trainer'    => 'Người hướng dẫn',
            'status'     => 'Trạng thái',
            'created_at' => 'Ngày tạo',
        ];
    }
    public function get_sortable_columns() {
        return ['title' => ['title', false], 'created_at' => ['created_at', true]];
    }
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="workshop[]" value="%s" />', $item['id']);
    }
    public function column_title($item) {
        $edit_url = add_query_arg(['page' => 'bacera-workshops', 'tab' => 'workshops', 'action' => 'edit', 'id' => $item['id']], admin_url('admin.php'));
        $delete_url = wp_nonce_url(add_query_arg(['page' => 'bacera-workshops', 'tab' => 'workshops', 'action' => 'delete', 'id' => $item['id']], admin_url('admin.php')), 'delete_workshop_' . $item['id']);
        $actions = [
            'edit'   => "<a href='$edit_url'>✏️ Sửa</a>",
            'delete' => "<a href='$delete_url' onclick='return confirm(\"Xác nhận xóa workshop này?\")'>🗑️ Xóa</a>",
        ];
        return '<strong><a href="' . esc_url($edit_url) . '">' . esc_html($item['title']) . '</a></strong>' . $this->row_actions($actions);
    }
    public function column_status($item) {
        $map = [
            'active'   => ['wks-badge wks-badge-green',  'Đang mở'],
            'draft'    => ['wks-badge wks-badge-gray',   'Nháp'],
            'archived' => ['wks-badge wks-badge-red',    'Đã ẩn'],
        ];
        $s = $map[$item['status']] ?? ['wks-badge wks-badge-gray', $item['status']];
        return '<span class="' . $s[0] . '">' . $s[1] . '</span>';
    }
    public function column_default($item, $col) {
        return esc_html($item[$col] ?? '–');
    }
    public function prepare_items() {
        global $wpdb;
        $table = $wpdb->prefix . 'bacera_workshops';
        $per_page = 15;
        $page = $this->get_pagenum();
        $offset = ($page - 1) * $per_page;
        $search = isset($_GET['s']) ? '%' . $wpdb->esc_like(sanitize_text_field($_GET['s'])) . '%' : '';
        $where = $search ? $wpdb->prepare("WHERE title LIKE %s OR trainer LIKE %s", $search, $search) : '';
        $total = $wpdb->get_var("SELECT COUNT(id) FROM $table $where");
        $this->items = $wpdb->get_results("SELECT * FROM $table $where ORDER BY id DESC LIMIT $per_page OFFSET $offset", ARRAY_A);
        $this->set_pagination_args(['total_items' => $total, 'per_page' => $per_page, 'total_pages' => ceil($total / $per_page)]);
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
    }
}

// ─── SLOTS LIST ───────────────────────────────────────────────────────────────
class SlotListTable extends \WP_List_Table {
    public function __construct() {
        parent::__construct(['singular' => 'slot', 'plural' => 'slots', 'ajax' => false]);
    }
    public function get_columns() {
        return [
            'cb'           => '<input type="checkbox" />',
            'workshop'     => 'Workshop',
            'slot_date'    => 'Ngày',
            'time'         => 'Khung giờ',
            'seats'        => 'Chỗ (Đã đặt / Tổng)',
            'status'       => 'Trạng thái',
        ];
    }
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="slot[]" value="%s" />', $item['id']);
    }
    public function column_workshop($item) {
        global $wpdb;
        $t = $wpdb->prefix . 'bacera_workshops';
        $w = $wpdb->get_var($wpdb->prepare("SELECT title FROM $t WHERE id = %d", $item['workshop_id']));
        return esc_html($w ?: '–');
    }
    public function column_time($item) {
        return esc_html($item['time_start'] . ' – ' . $item['time_end']);
    }
    public function column_seats($item) {
        $remaining = $item['total_seats'] - $item['booked_seats'];
        $color = $remaining === 0 ? 'color:#dc2626' : ($remaining <= 3 ? 'color:#d97706' : 'color:#16a34a');
        return '<strong>' . $item['booked_seats'] . ' / ' . $item['total_seats'] . '</strong> &nbsp;<span style="' . $color . '">(' . $remaining . ' còn trống)</span>';
    }
    public function column_status($item) {
        $map = [
            'open'      => ['wks-badge wks-badge-green', 'Mở đặt'],
            'full'      => ['wks-badge wks-badge-red',   'Hết chỗ'],
            'cancelled' => ['wks-badge wks-badge-gray',  'Đã hủy'],
        ];
        $s = $map[$item['status']] ?? ['wks-badge wks-badge-gray', $item['status']];
        return '<span class="' . $s[0] . '">' . $s[1] . '</span>';
    }
    public function column_default($item, $col) {
        return esc_html($item[$col] ?? '–');
    }
    public function prepare_items() {
        global $wpdb;
        $table = $wpdb->prefix . 'bacera_workshop_slots';
        $filter_wid = isset($_GET['workshop_id']) ? intval($_GET['workshop_id']) : 0;
        $where = $filter_wid ? $wpdb->prepare("WHERE workshop_id = %d", $filter_wid) : '';
        $per_page = 20;
        $page = $this->get_pagenum();
        $offset = ($page - 1) * $per_page;
        $total = $wpdb->get_var("SELECT COUNT(id) FROM $table $where");
        $this->items = $wpdb->get_results("SELECT * FROM $table $where ORDER BY slot_date DESC LIMIT $per_page OFFSET $offset", ARRAY_A);
        $this->set_pagination_args(['total_items' => $total, 'per_page' => $per_page, 'total_pages' => ceil($total / $per_page)]);
        $this->_column_headers = [$this->get_columns(), [], []];
    }
}

// ─── BOOKINGS LIST ────────────────────────────────────────────────────────────
class BookingListTable extends \WP_List_Table {
    public function __construct() {
        parent::__construct(['singular' => 'booking', 'plural' => 'bookings', 'ajax' => false]);
    }
    public function get_columns() {
        return [
            'cb'            => '<input type="checkbox" />',
            'customer_name' => 'Khách hàng',
            'phone'         => 'SĐT',
            'workshop'      => 'Workshop',
            'slot_date'     => 'Lịch',
            'num_seats'     => 'Số chỗ',
            'status'        => 'Trạng thái',
            'created_at'    => 'Đặt lúc',
        ];
    }
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="booking[]" value="%s" />', $item['id']);
    }
    public function column_customer_name($item) {
        $edit_url = add_query_arg(['page' => 'bacera-bookings', 'action' => 'edit', 'id' => $item['id']], admin_url('admin.php'));
        return '<strong><a href="' . esc_url($edit_url) . '">' . esc_html($item['customer_name']) . '</a></strong><br><small style="color:#888">' . esc_html($item['email']) . '</small>';
    }
    public function column_workshop($item) {
        global $wpdb;
        $t = $wpdb->prefix . 'bacera_workshops';
        $w = $wpdb->get_var($wpdb->prepare("SELECT title FROM $t WHERE id = %d", $item['workshop_id']));
        return esc_html($w ?: '–');
    }
    public function column_slot_date($item) {
        global $wpdb;
        $t = $wpdb->prefix . 'bacera_workshop_slots';
        $slot = $wpdb->get_row($wpdb->prepare("SELECT slot_date, time_start, time_end FROM $t WHERE id = %d", $item['slot_id']), ARRAY_A);
        if (!$slot) return '–';
        return esc_html($slot['slot_date'] . ' | ' . $slot['time_start'] . '–' . $slot['time_end']);
    }
    public function column_status($item) {
        $map = [
            'pending'   => ['wks-badge wks-badge-amber', '⏳ Pending'],
            'confirmed' => ['wks-badge wks-badge-green', '✅ Confirmed'],
            'cancelled' => ['wks-badge wks-badge-red',   '❌ Cancelled'],
        ];
        $s = $map[$item['status']] ?? ['wks-badge wks-badge-gray', $item['status']];
        return '<span class="' . $s[0] . '">' . $s[1] . '</span>';
    }
    public function column_default($item, $col) {
        return esc_html($item[$col] ?? '–');
    }
    public function prepare_items() {
        global $wpdb;
        $table = $wpdb->prefix . 'bacera_workshop_bookings';
        $search = isset($_GET['s']) ? '%' . $wpdb->esc_like(sanitize_text_field($_GET['s'])) . '%' : '';
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
        $where_parts = [];
        if ($search) $where_parts[] = $wpdb->prepare("(customer_name LIKE %s OR phone LIKE %s OR email LIKE %s)", $search, $search, $search);
        if ($status_filter) $where_parts[] = $wpdb->prepare("status = %s", $status_filter);
        $where = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';
        $per_page = 20;
        $page = $this->get_pagenum();
        $offset = ($page - 1) * $per_page;
        $total = $wpdb->get_var("SELECT COUNT(id) FROM $table $where");
        $this->items = $wpdb->get_results("SELECT * FROM $table $where ORDER BY id DESC LIMIT $per_page OFFSET $offset", ARRAY_A);
        $this->set_pagination_args(['total_items' => $total, 'per_page' => $per_page, 'total_pages' => ceil($total / $per_page)]);
        $this->_column_headers = [$this->get_columns(), [], []];
    }
}

// ─── REVIEWS LIST ─────────────────────────────────────────────────────────────
class ReviewListTable extends \WP_List_Table {
    public function __construct() {
        parent::__construct(['singular' => 'review', 'plural' => 'reviews', 'ajax' => false]);
    }
    public function get_columns() {
        return [
            'cb'          => '<input type="checkbox" />',
            'author_name' => 'Tên',
            'rating'      => 'Sao',
            'workshop'    => 'Workshop',
            'review_text' => 'Nội dung',
            'status'      => 'Trạng thái',
            'created_at'  => 'Ngày',
        ];
    }
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="review[]" value="%s" />', $item['id']);
    }
    public function column_rating($item) {
        return str_repeat('★', $item['rating']) . str_repeat('☆', 5 - $item['rating']);
    }
    public function column_workshop($item) {
        global $wpdb;
        $t = $wpdb->prefix . 'bacera_workshops';
        $w = $wpdb->get_var($wpdb->prepare("SELECT title FROM $t WHERE id = %d", $item['workshop_id']));
        return esc_html($w ?: '–');
    }
    public function column_review_text($item) {
        return '<span title="' . esc_attr($item['review_text']) . '">' . esc_html(mb_strimwidth($item['review_text'], 0, 80, '...')) . '</span>';
    }
    public function column_status($item) {
        $map = [
            'pending'  => ['wks-badge wks-badge-amber', '⏳ Chờ duyệt'],
            'approved' => ['wks-badge wks-badge-green', '✅ Đã duyệt'],
            'rejected' => ['wks-badge wks-badge-red',   '❌ Từ chối'],
        ];
        $s = $map[$item['status']] ?? ['wks-badge wks-badge-gray', $item['status']];
        $approve_url = wp_nonce_url(add_query_arg(['page' => 'bacera-workshops', 'tab' => 'reviews', 'action' => 'approve', 'id' => $item['id']], admin_url('admin.php')), 'review_action_' . $item['id']);
        $reject_url  = wp_nonce_url(add_query_arg(['page' => 'bacera-workshops', 'tab' => 'reviews', 'action' => 'reject',  'id' => $item['id']], admin_url('admin.php')), 'review_action_' . $item['id']);
        $actions = '';
        if ($item['status'] !== 'approved') $actions .= " &nbsp;<a href='$approve_url' style='color:#15803d;font-weight:600'>✅ Duyệt</a>";
        if ($item['status'] !== 'rejected')  $actions .= " &nbsp;<a href='$reject_url' style='color:#b91c1c;font-weight:600'>❌ Từ chối</a>";
        return '<span class="' . $s[0] . '">' . $s[1] . '</span>' . $actions;
    }
    public function column_default($item, $col) {
        return esc_html($item[$col] ?? '–');
    }
    public function prepare_items() {
        global $wpdb;
        $table = $wpdb->prefix . 'bacera_workshop_reviews';
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
        $where = $status_filter ? $wpdb->prepare("WHERE status = %s", $status_filter) : '';
        $per_page = 20;
        $page = $this->get_pagenum();
        $offset = ($page - 1) * $per_page;
        $total = $wpdb->get_var("SELECT COUNT(id) FROM $table $where");
        $this->items = $wpdb->get_results("SELECT * FROM $table $where ORDER BY id DESC LIMIT $per_page OFFSET $offset", ARRAY_A);
        $this->set_pagination_args(['total_items' => $total, 'per_page' => $per_page, 'total_pages' => ceil($total / $per_page)]);
        $this->_column_headers = [$this->get_columns(), [], []];
    }
}
