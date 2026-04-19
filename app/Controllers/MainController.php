<?php
namespace Bacera\Controllers;

/**
 * Main Controller used for setting up hooks, assets, routing helpers to the views
 */
class MainController {
    public function __construct() {
        add_action( 'wp_head', [ $this, 'outputCustomMeta' ] );

        // AJAX: đăng nhập, OTP
        new AuthController();

        // OAuth callbacks: Google, Facebook (chạy mọi request vì cần bắt redirect)
        new SocialLoginController();

        // SMTP hook phải chạy trên mọi request (cả frontend gửi mail)
        add_action( 'phpmailer_init', [ 'Bacera\\Controllers\\ConfigController', 'static_configure_phpmailer' ] );

        // Init DB tables (payment)
        \Bacera\Database\PaymentTables::init();

        // Init DB tables (video)
        \Bacera\Database\VideoTables::init();

        // Bảng bacera_customers — tạo ngay khi theme boot (không chỉ admin_init), tránh deploy chỉ frontend / WP Pusher không vào admin.
        $this->ensureBaceraCustomerSchema();

        // Khởi tạo các Custom Post Type cho Workshop
        new WorkshopController();

        // ── Frontend Workshop Booking AJAX ──
        add_action( 'wp_ajax_nopriv_bacera_book_workshop',  [ $this, 'ajaxBookWorkshop' ] );
        add_action( 'wp_ajax_bacera_book_workshop',         [ $this, 'ajaxBookWorkshop' ] );
        add_action( 'wp_ajax_nopriv_bacera_get_slot_seats', [ $this, 'ajaxGetSlotSeats' ] );
        add_action( 'wp_ajax_bacera_get_slot_seats',        [ $this, 'ajaxGetSlotSeats' ] );

        // ── Payment & Promo AJAX (frontend) ──
        new PaymentController();

        // Admin-only
        if ( is_admin() ) {
            new AdminCustomerController();  // tạo menu cha "bacera-main" trước
            new ConfigController();         // thêm submenu "bacera-config" sau
            new AdminWorkshopController();  // Workshop DB + 4 trang quản lý
            new AdminPaymentController();   // Payment methods + promo codes
            new AdminTeamController();      // Our Team member management
            new AdminVideoController();     // Video library & categories
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

    /**
     * Đảm bảo bảng khách + cột Pancake tồn tại (dbDelta an toàn gọi lại).
     */
    public function ensureBaceraCustomerSchema() {
        if ( ! class_exists( '\\Bacera\\Database\\CustomerTable', false ) ) {
            return;
        }
        $db_version = get_option( 'bacera_customers_db_version', '0' );
        if ( version_compare( (string) $db_version, '1.3', '>=' ) ) {
            return;
        }
        \Bacera\Database\CustomerTable::createTable();
        \Bacera\Database\CustomerTable::migrate_to_1_3();
        update_option( 'bacera_customers_db_version', '1.3' );
    }

    /* ── Workshop Routing (Removed) ────────────────────────────────── */

    /**
     * Lấy danh sách ghế đã đặt cho một slot cụ thể (realtime seat map update)
     */
    public function ajaxGetSlotSeats() {
        $slot_id = intval( $_POST['slot_id'] ?? 0 );
        if ( ! $slot_id ) wp_send_json_error( 'Invalid slot' );

        global $wpdb;
        $tb = $wpdb->prefix . 'bacera_workshop_bookings';
        $ts = $wpdb->prefix . 'bacera_workshop_slots';

        $slot = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$ts} WHERE id = %d", $slot_id ), ARRAY_A );
        if ( ! $slot ) wp_send_json_error( 'Slot not found' );

        $booked_rows = $wpdb->get_col( $wpdb->prepare(
            "SELECT seats_selected FROM {$tb} WHERE slot_id = %d AND status != 'cancelled'",
            $slot_id
        ) );

        $booked_nums = [];
        foreach ( $booked_rows as $r ) {
            if ( $r ) {
                foreach ( array_map( 'intval', explode( ',', $r ) ) as $n ) {
                    if ( $n > 0 ) $booked_nums[] = $n;
                }
            }
        }

        wp_send_json_success( [
            'booked'       => array_values( array_unique( $booked_nums ) ),
            'total_seats'  => (int) $slot['total_seats'],
            'booked_seats' => (int) $slot['booked_seats'],
            'status'       => $slot['status'],
        ] );
    }

    /**
     * Xử lý đăng ký workshop từ frontend
     * Yêu cầu: đăng nhập bằng cookie bacera_customer_auth
     */
    public function ajaxBookWorkshop() {
        // ── 1. Kiểm tra auth cookie ───────────────────────────────
        $cookie = $_COOKIE['bacera_customer_auth'] ?? '';
        $customer_id = 0;
        $customer_name = '';
        $customer_phone = '';
        $customer_email = '';

        if ( $cookie ) {
            $decoded = base64_decode( $cookie, true );
            if ( $decoded && strpos( $decoded, '|' ) !== false ) {
                $customer_id = (int) explode( '|', $decoded )[0];
            }
        }

        if ( $customer_id <= 0 ) {
            wp_send_json_error( [ 'code' => 'login_required', 'message' => 'Bạn cần đăng nhập để đặt chỗ.' ] );
        }

        // Lấy thông tin khách hàng từ DB
        global $wpdb;
        $tc = $wpdb->prefix . 'bacera_customers';
        $customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tc} WHERE id = %d", $customer_id ), ARRAY_A );
        if ( ! $customer ) wp_send_json_error( [ 'code' => 'login_required', 'message' => 'Phiên đăng nhập không hợp lệ.' ] );

        $customer_name  = $customer['name']  ?: '';
        $customer_phone = $customer['phone'] ?: '';
        $customer_email = $customer['email'] ?: '';

        // ── 2. Validate input ─────────────────────────────────────
        $slot_id     = intval( $_POST['slot_id'] ?? 0 );
        $workshop_id = intval( $_POST['workshop_id'] ?? 0 );
        $seats_raw   = sanitize_text_field( $_POST['seats'] ?? '' );   // "1,2,3"
        $city        = sanitize_text_field( $_POST['city'] ?? '' );
        $notes       = sanitize_textarea_field( $_POST['notes'] ?? '' );

        if ( ! $slot_id || ! $workshop_id ) {
            wp_send_json_error( [ 'message' => 'Thiếu thông tin lịch học.' ] );
        }

        $seats_arr = array_filter( array_map( 'intval', explode( ',', $seats_raw ) ) );
        $num_seats = count( $seats_arr );
        if ( $num_seats < 1 ) {
            wp_send_json_error( [ 'message' => 'Bạn chưa chọn ghế ngồi.' ] );
        }

        // ── 3. Kiểm tra slot còn chỗ và thời gian đăng ký ──────────────
        $ts = $wpdb->prefix . 'bacera_workshop_slots';
        $tb = $wpdb->prefix . 'bacera_workshop_bookings';
        $slot = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$ts} WHERE id = %d AND workshop_id = %d", $slot_id, $workshop_id ), ARRAY_A );

        if ( ! $slot )                     wp_send_json_error( [ 'message' => 'Lịch học không tồn tại.' ] );
        if ( $slot['status'] === 'cancelled' ) wp_send_json_error( [ 'message' => 'Lịch học đã bị hủy.' ] );
        if ( $slot['status'] === 'full' )      wp_send_json_error( [ 'message' => 'Ca học này đã hết chỗ.' ] );

        $now = current_time('Y-m-d H:i:s');
        if ( !empty($slot['reg_start']) && $now < $slot['reg_start'] ) {
            wp_send_json_error( [ 'message' => 'Chưa tới thời gian mở đăng ký cho ca học này.' ] );
        }
        if ( !empty($slot['reg_end']) && $now > $slot['reg_end'] ) {
            wp_send_json_error( [ 'message' => 'Đã quá hạn đăng ký cho ca học này.' ] );
        }

        // Real-time booked count = SUM(num_seats) — NOT cached booked_seats column
        $real_booked = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(num_seats),0) FROM {$tb} WHERE slot_id = %d AND status != 'cancelled'",
            $slot_id
        ) );
        $available = (int) $slot['total_seats'] - $real_booked;
        if ( $num_seats > $available ) {
            wp_send_json_error( [ 'message' => "Chỉ còn {$available} chỗ trống. Vui lòng chọn lại." ] );
        }

        // ── 4. REMOVED: Block re-booking — allow customers to book more seats ──
        // Users can book multiple bookings to the same slot (different seats each time)

        // ── 5. Kiểm tra ghế chưa bị đặt ─────────────────────────
        $tb = $wpdb->prefix . 'bacera_workshop_bookings';
        $booked_rows = $wpdb->get_col( $wpdb->prepare(
            "SELECT seats_selected FROM {$tb} WHERE slot_id = %d AND status != 'cancelled'",
            $slot_id
        ) );
        $taken = [];
        foreach ( $booked_rows as $r ) {
            if ( $r ) foreach ( array_map( 'intval', explode( ',', $r ) ) as $n ) {
                if ( $n > 0 ) $taken[] = $n;
            }
        }
        $conflict = array_intersect( $seats_arr, $taken );
        if ( ! empty( $conflict ) ) {
            wp_send_json_error( [ 'message' => 'Ghế số ' . implode( ', ', $conflict ) . ' vừa có người đặt. Vui lòng chọn lại.' ] );
        }

        // ── 6. Payment method ─────────────────────────────────────
        $payment_method = sanitize_key( $_POST['payment'] ?? 'cod' );
        $payment_data = null;
        if ($payment_method) {
            $tm = $wpdb->prefix . 'bacera_payment_methods';
            if ($wpdb->get_var("SHOW TABLES LIKE '{$tm}'") === $tm) {
                $payment_data = $wpdb->get_row($wpdb->prepare("SELECT name, instructions FROM {$tm} WHERE code = %s", $payment_method), ARRAY_A);
            }
        }

        // ── 7. Promo code ─────────────────────────────────────────
        $promo_code     = strtoupper( sanitize_text_field( $_POST['promo_code'] ?? '' ) );
        $discount_amount = 0;
        if ( $promo_code ) {
            $tp    = $wpdb->prefix . 'bacera_promo_codes';
            $promo = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$tp} WHERE code = %s AND is_active = 1", $promo_code
            ), ARRAY_A );
            if ( $promo ) {
                // Increment used_count
                $wpdb->update( $tp, [ 'used_count' => (int)$promo['used_count'] + 1 ], [ 'id' => $promo['id'] ] );
            }
        }

        // ── 8. Lưu booking ────────────────────────────────────────
        $insert_data = [
            'slot_id'        => $slot_id,
            'workshop_id'    => $workshop_id,
            'customer_name'  => $customer_name,
            'phone'          => $customer_phone,
            'email'          => $customer_email,
            'customer_id'    => $customer_id,
            'city'           => $city,
            'seats_selected' => implode( ',', $seats_arr ),
            'num_seats'      => $num_seats,
            'status'         => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => $payment_method,
            'promo_code'     => $promo_code ?: null,
            'notes'          => $notes,
            'created_at'     => current_time( 'mysql' ),
        ];

        $inserted = $wpdb->insert( $tb, $insert_data );

        if ( ! $inserted ) {
            wp_send_json_error( [ 'message' => 'Lỗi hệ thống. Vui lòng thử lại.' ] );
        }

        // ── 9. Cập nhật booked_seats trong slot (sync cache) ─────
        $new_booked = $real_booked + $num_seats;
        $new_status = ( $new_booked >= (int) $slot['total_seats'] ) ? 'full' : 'open';
        $wpdb->update( $ts, [
            'booked_seats' => $new_booked,
            'status'       => $new_status,
        ], [ 'id' => $slot_id ] );

        wp_send_json_success( [
            'message'    => 'Đặt chỗ thành công! Ghế của bạn đã được giữ.',
            'booking_id' => $wpdb->insert_id,
            'payment'    => $payment_data
        ] );
    }
}

