<?php
namespace Bacera\Controllers;

class AdminSyncController {

    private array $pages = [
        'Trang chủ'             => 'templates/template-home-page.php',
        'Về chúng tôi'          => 'templates/template-about.php',
        'Quy trình'             => 'templates/template-our-process.php',
        'Cửa hàng'              => 'templates/template-shop.php',
        'Workshop'              => 'templates/template-workshop.php',
        'Sổ tay gốm'            => 'templates/template-blog.php',
        'Video'                 => 'templates/template-our-video.php',
        'Phát triển bền vững'    => 'templates/template-sustainability.php',
        'Đội ngũ'               => 'templates/template-our-team.php',
        'Đăng nhập'             => 'templates/template-auth.php',
        'Giỏ hàng'              => 'templates/template-cart.php',
        'Thanh toán'            => 'templates/template-checkout.php',
        'Tài khoản'             => 'templates/templates-my-account.php',
    ];

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'wp_ajax_bacera_sync_pages_menu', [ $this, 'ajax_sync_pages_menu' ] );
    }

    public function add_menu() {
        add_submenu_page(
            'bacera-main',
            'Đồng bộ Website',
            'Đồng bộ Website',
            'manage_options',
            'bacera-sync',
            [ $this, 'render_page' ]
        );
    }

    public function render_page() {
        ?>
        <style>
        .bsync-wrap { max-width: 800px; padding: 24px 0; font-family: "Inter", sans-serif; }
        .bsync-card { background: #fff; border: 1px solid #eae3d1; border-radius: 12px; padding: 24px; box-shadow: 0 4px 12px rgba(61,47,38,0.05); }
        .bsync-btn { background: #d95f47; color: #fff; border: none; border-radius: 8px; padding: 12px 24px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; display: inline-flex; items-center; gap: 8px; }
        .bsync-btn:hover { background: #b84833; }
        .bsync-log { margin-top: 20px; background: #fdfaf6; border: 1px solid #eae3d1; border-radius: 8px; padding: 16px; min-height: 150px; font-family: monospace; font-size: 13px; color: #57534e; display: none; }
        .bsync-log p { margin: 0 0 6px 0; }
        .bsync-success { color: #16a34a; font-weight: 600; }
        .bsync-warn { color: #d97706; }
        .bsync-error { color: #dc2626; font-weight: 600; }
        </style>

        <div class="bsync-wrap">
            <h2>🔄 Tự động Kích hoạt & Đồng bộ</h2>
            <div class="bsync-card">
                <p style="color:#6b5344; font-size:15px; margin-top:0; line-height:1.6;">
                    Khi upload Theme lên Host mới, nhấn nút bên dưới để tự động tạo toàn bộ các Trang (Pages), map đúng file giao diện (Template), cài đặt Trang chủ, và tạo sẵn Menu đồng bộ lên Header.
                </p>
                
                <button type="button" class="bsync-btn" id="bsync-btn">
                    <svg class="w-5 h-5" style="width:20px; height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Tiến hành Đồng bộ Theme
                </button>

                <div class="bsync-log" id="bsync-log"></div>
            </div>
        </div>

        <script>
        document.getElementById('bsync-btn').addEventListener('click', function() {
            var btn = this;
            var log = document.getElementById('bsync-log');
            btn.disabled = true;
            btn.innerHTML = 'Đang đồng bộ...';
            log.style.display = 'block';
            log.innerHTML = '<p>Đang bắt đầu tạo trang và menu...</p>';

            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'bacera_sync_pages_menu',
                    nonce: '<?php echo wp_create_nonce("bacera_sync_nonce"); ?>'
                })
            })
            .then(r => r.json())
            .then(data => {
                if(data.success && data.data.logs) {
                    var html = '';
                    data.data.logs.forEach(function(l) {
                        html += '<p>➔ ' + l + '</p>';
                    });
                    html += '<p class="bsync-success" style="margin-top:12px;">✅ Hoàn tất! Vui lòng tải lại trang nếu cần.</p>';
                    log.innerHTML = html;
                } else {
                    log.innerHTML += '<p class="bsync-error">❌ Lỗi: ' + (data.data || 'Unknown error') + '</p>';
                }
            })
            .catch(e => {
                log.innerHTML += '<p class="bsync-error">❌ Lỗi kết nối máy chủ.</p>';
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = 'Tiến hành Đồng bộ Theme';
            });
        });
        </script>
        <?php
    }

    public function ajax_sync_pages_menu() {
        check_ajax_referer( 'bacera_sync_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Bạn không có quyền.' );
        }

        $logs = [];
        $menu_items_for_nav = [];

        // 1. Tạo các trang
        foreach ( $this->pages as $title => $template ) {
            // Check if page with template already exists
            $args = [
                'post_type'  => 'page',
                'meta_key'   => '_wp_page_template',
                'meta_value' => $template,
                'numberposts'=> 1
            ];
            $existing = get_posts( $args );

            if ( $existing ) {
                $page_id = $existing[0]->ID;
                $logs[] = "<span class='bsync-warn'>Trang '{$title}' đã tồn tại (ID: $page_id).</span>";
            } else {
                // Determine page content
                $content = '';
                if ($template === 'templates/template-home-page.php' || $template === 'templates/template-shop.php' || $template === 'templates/template-about.php') {
                    $content = '<!-- Demo Content -->';
                }

                $page_id = wp_insert_post( [
                    'post_title'   => $title,
                    'post_content' => $content,
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_name'    => sanitize_title( $title ),
                ] );

                if ( ! is_wp_error( $page_id ) ) {
                    update_post_meta( $page_id, '_wp_page_template', $template );
                    $logs[] = "<span class='bsync-success'>Tạo mới trang '{$title}' thành công.</span>";
                } else {
                    $logs[] = "<span class='bsync-error'>Lỗi khi tạo trang '{$title}'.</span>";
                    continue;
                }
            }

            // Dùng để đưa vào menu (chỉ đưa các trang chính)
            if ( ! in_array( $template, [ 'templates/template-auth.php', 'templates/template-checkout.php', 'templates/template-cart.php', 'templates/templates-my-account.php' ] ) ) {
                $menu_items_for_nav[$title] = $page_id;
            }

            // Thiết lập trang chủ
            if ( $title === 'Trang chủ' ) {
                update_option( 'show_on_front', 'page' );
                update_option( 'page_on_front', $page_id );
                $logs[] = "<span class='bsync-success'>Đã gán '{$title}' làm Front Page.</span>";
            }
        }

        // 2. Tạo Menu
        $menu_name = 'Main Menu Bacera';
        $menu_location = 'menu-1';

        $menu_exists = wp_get_nav_menu_object( $menu_name );
        if ( ! $menu_exists ) {
            $menu_id = wp_create_nav_menu( $menu_name );
            $logs[] = "<span class='bsync-success'>Đã tạo Menu mới '{$menu_name}'.</span>";

            if ( ! is_wp_error( $menu_id ) ) {
                // Add items to menu
                foreach ( $menu_items_for_nav as $m_title => $m_page_id ) {
                    wp_update_nav_menu_item( $menu_id, 0, [
                        'menu-item-title'     => $m_title,
                        'menu-item-object-id' => $m_page_id,
                        'menu-item-object'    => 'page',
                        'menu-item-status'    => 'publish',
                        'menu-item-type'      => 'post_type',
                    ] );
                }
                $logs[] = "Đã thêm các trang vào Menu.";

                // Set menu location
                $locations = get_theme_mod( 'nav_menu_locations' );
                $locations[$menu_location] = $menu_id;
                set_theme_mod( 'nav_menu_locations', $locations );
                $logs[] = "<span class='bsync-success'>Đã gán Menu vào vị trí Header.</span>";
            }
        } else {
            $logs[] = "<span class='bsync-warn'>Menu '{$menu_name}' đã tồn tại, bỏ qua tạo menu.</span>";
        }

        // Flush permalinks
        flush_rewrite_rules( true );
        $logs[] = "Đã flush rewrite rules (Permalinks).";

        wp_send_json_success( [ 'logs' => $logs ] );
    }
}
