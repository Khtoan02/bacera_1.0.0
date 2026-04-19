<?php
namespace Bacera\Controllers;

use Bacera\Database\CustomerTable;

class AdminCustomerController {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'init_database' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        add_action( 'admin_post_bacera_sync_pancake_customers', [ $this, 'process_pancake_sync' ] );
    }

    public function init_database() {
        $db_version = get_option( 'bacera_customers_db_version', '0' );
        if ( version_compare( $db_version, '1.3', '<' ) ) {
            CustomerTable::createTable();
            CustomerTable::migrate_to_1_3();
            update_option( 'bacera_customers_db_version', '1.3' );
        }
    }

    /**
     * Đồng bộ khách hàng từ Pancake POS (nguồn chính) vào bảng bacera_customers.
     */
    public function process_pancake_sync() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Không có quyền.', 'bacera' ), '', [ 'response' => 403 ] );
        }
        check_admin_referer( 'bacera_sync_pancake_customers' );

        if ( ! class_exists( '\\Bacera_Module_Customers', false ) ) {
            wp_safe_redirect(
                add_query_arg(
                    [ 'page' => 'bacera-customers', 'bacera_sync' => 'no_plugin' ],
                    admin_url( 'admin.php' )
                )
            );
            exit;
        }

        $stats = \Bacera_Module_Customers::sync_bacera_customers_full();

        $args = [
            'page'          => 'bacera-customers',
            'bacera_sync'   => '1',
            'sync_pulled'   => isset( $stats['pulled'] ) ? (int) $stats['pulled'] : 0,
            'sync_inserted' => isset( $stats['inserted'] ) ? (int) $stats['inserted'] : 0,
            'sync_updated'  => isset( $stats['updated'] ) ? (int) $stats['updated'] : 0,
            'sync_linked'   => isset( $stats['linked'] ) ? (int) $stats['linked'] : 0,
            'sync_pushed'   => isset( $stats['pushed'] ) ? (int) $stats['pushed'] : 0,
        ];

        if ( ! empty( $stats['errors'] ) ) {
            $args['bacera_sync_err'] = rawurlencode( implode( ' | ', array_slice( $stats['errors'], 0, 5 ) ) );
        }

        wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
        exit;
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
        global $wpdb;

        // — Gather stats —
        $tb_customers = $wpdb->prefix . 'bacera_customers';
        $tb_workshops = $wpdb->prefix . 'posts';
        $tb_slots     = $wpdb->prefix . 'bacera_workshop_slots';
        $tb_bookings  = $wpdb->prefix . 'bacera_workshop_bookings';
        $tb_methods   = $wpdb->prefix . 'bacera_payment_methods';
        $tb_promos    = $wpdb->prefix . 'bacera_promo_codes';

        $total_customers = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tb_customers}");
        $new_customers   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tb_customers} WHERE created_at >= '" . date('Y-m-d', strtotime('-30 days')) . "'");
        $total_workshops = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tb_workshops} WHERE post_type='workshop' AND post_status IN('publish','draft')");
        $pub_workshops   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tb_workshops} WHERE post_type='workshop' AND post_status='publish'");
        $total_slots     = $wpdb->get_var("SHOW TABLES LIKE '{$tb_slots}'") === $tb_slots
            ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tb_slots} WHERE status != 'cancelled'") : 0;
        $total_bookings  = $wpdb->get_var("SHOW TABLES LIKE '{$tb_bookings}'") === $tb_bookings
            ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tb_bookings} WHERE status != 'cancelled'") : 0;
        $active_methods  = $wpdb->get_var("SHOW TABLES LIKE '{$tb_methods}'") === $tb_methods
            ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tb_methods} WHERE is_active=1") : 0;
        $active_promos   = $wpdb->get_var("SHOW TABLES LIKE '{$tb_promos}'") === $tb_promos
            ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tb_promos} WHERE is_active=1") : 0;

        // Recent bookings
        $recent_bookings = ($wpdb->get_var("SHOW TABLES LIKE '{$tb_bookings}'") === $tb_bookings)
            ? $wpdb->get_results("SELECT b.*, s.slot_date, s.time_start, p.post_title as workshop_name
                FROM {$tb_bookings} b
                LEFT JOIN {$tb_slots} s ON b.slot_id = s.id
                LEFT JOIN {$tb_workshops} p ON b.workshop_id = p.ID
                WHERE b.status != 'cancelled'
                ORDER BY b.created_at DESC LIMIT 6", ARRAY_A)
            : [];

        // Enqueue Tailwind for this page
        wp_enqueue_style('bacera-main-css', BACERA_THEME_URI . 'assets/css/main.css', [], BACERA_THEME_VERSION);
        wp_add_inline_style('bacera-main-css', '#wpcontent { padding-left: 0; }');
        ?>
        <div class="bacera-dashboard p-6 md:p-10 min-h-screen bg-[#fafaf9]">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4 border-b border-neutral-200/60 pb-6">
                <div>
                    <h1 class="text-3xl font-extrabold text-primary-900 m-0 p-0 tracking-tight">Bacera Dashboard</h1>
                    <p class="text-primary-500 mt-1.5 text-sm">Tổng quan hệ thống — <?= date('d/m/Y, H:i') ?></p>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <a href="<?= admin_url('admin.php?page=bacera-workshops') ?>" class="inline-flex items-center px-4 py-2 bg-primary-900 text-white rounded-lg hover:bg-primary-800 font-medium transition-colors text-[13px] shadow-sm">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        Workshop Hub
                    </a>
                    <a href="<?= home_url() ?>" target="_blank" class="inline-flex items-center px-4 py-2 bg-white border border-neutral-200 text-primary-700 rounded-lg hover:bg-neutral-50 font-medium transition-colors text-[13px] shadow-sm">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        Xem Website
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-10">

                <a href="<?= admin_url('admin.php?page=bacera-customers') ?>" class="bg-white p-5 rounded-xl border border-neutral-200 shadow-sm flex flex-col gap-1 hover:shadow-md hover:border-primary-200 transition-all group">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-neutral-500 text-[11px] font-semibold tracking-wide uppercase">Khách hàng</p>
                        <div class="w-8 h-8 rounded-lg bg-primary-100 flex items-center justify-center text-primary-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                    </div>
                    <span class="text-3xl font-bold text-primary-900 tracking-tight"><?= $total_customers ?></span>
                    <?php if ($new_customers > 0): ?>
                    <span class="text-xs font-medium text-emerald-600">+<?= $new_customers ?> trong 30 ngày</span>
                    <?php endif; ?>
                </a>

                <a href="<?= admin_url('admin.php?page=bacera-workshops') ?>" class="bg-white p-5 rounded-xl border border-neutral-200 shadow-sm flex flex-col gap-1 hover:shadow-md hover:border-primary-200 transition-all group">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-neutral-500 text-[11px] font-semibold tracking-wide uppercase">Workshop</p>
                        <div class="w-8 h-8 rounded-lg bg-accent-50 flex items-center justify-center text-accent-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                    </div>
                    <span class="text-3xl font-bold text-primary-900 tracking-tight"><?= $total_workshops ?></span>
                    <span class="text-xs font-medium text-primary-400"><?= $pub_workshops ?> đang hoạt động</span>
                </a>

                <div class="bg-white p-5 rounded-xl border border-neutral-200 shadow-sm flex flex-col gap-1">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-neutral-500 text-[11px] font-semibold tracking-wide uppercase">Ca học</p>
                        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                    </div>
                    <span class="text-3xl font-bold text-primary-900 tracking-tight"><?= $total_slots ?></span>
                    <span class="text-xs font-medium text-primary-400">lịch đang khả dụng</span>
                </div>

                <div class="bg-white p-5 rounded-xl border border-neutral-200 shadow-sm flex flex-col gap-1">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-neutral-500 text-[11px] font-semibold tracking-wide uppercase">Đăng ký</p>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </div>
                    </div>
                    <span class="text-3xl font-bold text-primary-900 tracking-tight"><?= $total_bookings ?></span>
                    <span class="text-xs font-medium text-primary-400">tổng lượt tham gia</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Recent Bookings -->
                <div class="lg:col-span-2 bg-white rounded-xl border border-neutral-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-neutral-100 flex items-center justify-between">
                        <h2 class="text-[15px] font-bold text-primary-900">Đăng ký gần đây</h2>
                        <a href="<?= admin_url('admin.php?page=bacera-workshops') ?>" class="text-xs text-accent-500 hover:text-accent-600 font-semibold">Xem tất cả →</a>
                    </div>
                    <?php if (empty($recent_bookings)): ?>
                    <div class="p-10 text-center text-primary-400 text-sm">Chưa có đăng ký nào.</div>
                    <?php else: ?>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-[#faf8f5]">
                                <th class="px-5 py-3 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wide">Khách hàng</th>
                                <th class="px-5 py-3 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wide">Workshop</th>
                                <th class="px-5 py-3 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wide">Ngày học</th>
                                <th class="px-5 py-3 text-left text-[11px] font-bold text-neutral-500 uppercase tracking-wide">Thanh toán</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recent_bookings as $b):
                            $payment_color = $b['payment_status'] === 'paid'
                                ? 'bg-emerald-50 text-emerald-700'
                                : ($b['payment_status'] === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-neutral-100 text-neutral-600');
                            $payment_label = $b['payment_status'] === 'paid' ? 'Đã TT' : ($b['payment_status'] === 'pending' ? 'Chờ TT' : ucfirst($b['payment_status'] ?? '—'));
                        ?>
                        <tr class="border-t border-neutral-100 hover:bg-neutral-50/50 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="font-semibold text-primary-900 text-[13px]"><?= esc_html($b['customer_name'] ?? '—') ?></div>
                                <div class="text-[11px] text-primary-400"><?= esc_html($b['customer_phone'] ?? '') ?></div>
                            </td>
                            <td class="px-5 py-3.5 text-[13px] text-primary-700 font-medium max-w-[160px] truncate"><?= esc_html(mb_strimwidth($b['workshop_name'] ?? '—', 0, 28, '…')) ?></td>
                            <td class="px-5 py-3.5 text-[13px] text-primary-600">
                                <?= $b['slot_date'] ? date('d/m/Y', strtotime($b['slot_date'])) : '—' ?>
                                <?php if ($b['time_start']): ?><div class="text-[11px] text-primary-400"><?= $b['time_start'] ?></div><?php endif; ?>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold <?= $payment_color ?>"><?= $payment_label ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>

                <!-- Quick Links + Status -->
                <div class="flex flex-col gap-5">

                    <!-- Quick Navigation -->
                    <div class="bg-white rounded-xl border border-neutral-200 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-neutral-100">
                            <h2 class="text-[15px] font-bold text-primary-900">Điều hướng nhanh</h2>
                        </div>
                        <div class="p-3 flex flex-col gap-1">
                            <?php
                            $nav = [
                                ['url' => admin_url('admin.php?page=bacera-customers'),    'label' => 'Quản lý Khách hàng',   'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                                ['url' => admin_url('admin.php?page=bacera-workshops'),    'label' => 'Quản lý Workshop',     'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                                ['url' => admin_url('admin.php?page=bacera-payments'),     'label' => 'Thanh toán & Mã GG',   'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                                ['url' => admin_url('admin.php?page=bacera-config'),       'label' => 'Cấu hình hệ thống',    'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                            ];
                            foreach ($nav as $item): ?>
                            <a href="<?= esc_url($item['url']) ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13px] font-medium text-primary-700 hover:bg-primary-50 hover:text-primary-900 transition-colors">
                                <svg class="w-4 h-4 text-primary-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $item['icon'] ?>"/></svg>
                                <?= $item['label'] ?>
                                <svg class="w-3.5 h-3.5 ml-auto text-primary-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- System Status -->
                    <div class="bg-white rounded-xl border border-neutral-200 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-neutral-100">
                            <h2 class="text-[15px] font-bold text-primary-900">Trạng thái hệ thống</h2>
                        </div>
                        <div class="p-4 flex flex-col gap-3">
                            <?php
                            $smtp_on = get_option('bacera_smtp_enabled') && get_option('bacera_smtp_host');
                            $gg_on   = get_option('bacera_google_enabled') && get_option('bacera_google_client_id');
                            $ts_on   = (bool) get_option('bacera_turnstile_site_key');
                            $statuses = [
                                ['label' => 'SMTP Mail',        'on' => $smtp_on,       'off_text' => 'Chưa cấu hình'],
                                ['label' => 'Google Login',     'on' => $gg_on,         'off_text' => 'Chưa bật'],
                                ['label' => 'CAPTCHA',          'on' => $ts_on,         'off_text' => 'Chưa cấu hình'],
                                ['label' => 'Thanh toán',       'on' => $active_methods > 0, 'off_text' => 'Chưa có'],
                                ['label' => 'Mã khuyến mãi',   'on' => $active_promos > 0,  'off_text' => 'Không có'],
                            ];
                            foreach ($statuses as $s): ?>
                            <div class="flex items-center justify-between text-[13px]">
                                <span class="text-primary-600 font-medium"><?= $s['label'] ?></span>
                                <?php if ($s['on']): ?>
                                <span class="flex items-center gap-1 text-emerald-600 font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>Hoạt động</span>
                                <?php else: ?>
                                <span class="flex items-center gap-1 text-neutral-400 font-medium"><span class="w-1.5 h-1.5 rounded-full bg-neutral-300 inline-block"></span><?= $s['off_text'] ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>
            </div>

        </div>
        <?php
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
            'filtered_total' => $filtered_total,
            'pancake_configured' => (bool) ( get_option( 'bacera_pancake_api_key' ) && get_option( 'bacera_pancake_shop_id' ) ),
            'pancake_plugin_active' => class_exists( '\\Bacera_Module_Customers', false ),
        ]);

        get_template_part('app/Views/admin/customer-dashboard');
    }
}
