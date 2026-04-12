<?php
namespace Bacera\Controllers;

class WorkshopController
{
    public function __construct()
    {
        // We are already inside the 'init' hook (booted by MainController on init).
        // So we can just call it directly safely.
        $this->registerPostTypes();

        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('save_post', [$this, 'saveMetaBoxes']);
        add_action('admin_head', [$this, 'outputAdminStyles']);
    }

    public function outputAdminStyles()
    {
        global $post_type;
        if (!in_array($post_type, ['workshop', 'workshop_slot', 'workshop_booking']))
            return;
        ?>
        <style>
            #workshop_details .inside,
            #slot_details .inside,
            #booking_details .inside {
                padding: 0;
                margin: 0;
            }

            .bacera-admin-panel {
                background: #F8F7F3;
                border-radius: 8px;
                padding: 24px;
                font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }

            .bacera-admin-panel .bacera-row {
                display: flex;
                gap: 24px;
                margin-bottom: 20px;
                flex-wrap: wrap;
            }

            .bacera-admin-panel .bacera-col {
                flex: 1;
                min-width: 250px;
            }

            .bacera-admin-panel label {
                display: block;
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: #6b5344;
                margin-bottom: 8px;
            }

            .bacera-admin-panel input[type="text"],
            .bacera-admin-panel input[type="date"],
            .bacera-admin-panel input[type="number"],
            .bacera-admin-panel select {
                width: 100%;
                padding: 10px 14px;
                font-size: 14px;
                color: #3d2f26;
                background-color: #fff;
                border: 1px solid #ded5cd;
                border-radius: 8px;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
                transition: all 0.2s ease;
                height: 42px;
            }

            .bacera-admin-panel input:focus,
            .bacera-admin-panel select:focus {
                border-color: #d95f47;
                box-shadow: 0 0 0 3px rgba(217, 95, 71, 0.15);
                outline: none;
            }

            .bacera-admin-panel .bacera-status-badge {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 6px;
                background: #e9e1d9;
                font-size: 12px;
                font-weight: 600;
            }
        </style>
        <?php
    }

    public function registerPostTypes()
    {
        // Workshop CPT
        register_post_type('workshop', [
            'labels' => [
                'name'          => 'Workshops',
                'singular_name' => 'Workshop',
                'menu_name'     => 'Workshops',
                'all_items'     => 'Tất cả Workshop',
                'add_new_item'  => 'Thêm Workshop mới',
                'edit_item'     => 'Sửa Workshop',
            ],
            'public'        => true,
            'has_archive'   => true,
            'show_ui'       => true,
            'show_in_menu'  => false,  // Ẩn khỏi sidebar — quản lý qua Bacera > Workshop
            'supports'      => ['title', 'editor', 'thumbnail', 'excerpt', 'comments'],
            'show_in_rest'  => true,
        ]);


    }

    public function addMetaBoxes()
    {
        add_meta_box('workshop_quick_link', 'Quản lý Ca Học & Học Viên', [$this, 'renderQuickLinkBox'], 'workshop', 'normal', 'high');
        add_meta_box('workshop_details', 'Thông tin cơ bản', [$this, 'renderWorkshopMetaBox'], 'workshop', 'normal', 'high');
    }

    public function renderQuickLinkBox($post)
    {
        $hub_url = admin_url("admin.php?page=bacera-workshops&id={$post->ID}&tab=sessions");
        ?>
        <div class="bacera-admin-panel" style="background:#f0fdf4; border:1px solid #bbf7d0; text-align:center; padding:16px;">
            <h3 style="margin-top:0; color:#166534;">Khu vực Vận Hành Workshop</h3>
            <p style="color:#15803d; font-size:14px;">Để thêm Lịch học (Ca), xem danh sách Học viên đăng ký, Điểm danh... vui
                lòng truy cập Workshop Hub.</p>
            <a href="<?php echo esc_url($hub_url); ?>" class="button button-primary button-large"
                style="background:#16a34a; border-color:#15803d;">Truy cập Quản Lý Ca Học & Khách Hàng</a>
        </div>
        <?php
    }

    public function renderWorkshopMetaBox($post)
    {
        $price = get_post_meta($post->ID, '_price', true);
        $duration = get_post_meta($post->ID, '_duration', true);
        $trainer = get_post_meta($post->ID, '_trainer', true);
        $target = get_post_meta($post->ID, '_target', true);
        wp_nonce_field('workshop_save', 'workshop_nonce');
        ?>
        <div class="bacera-admin-panel">
            <div class="bacera-row">
                <div class="bacera-col">
                    <label>Giá (Liên hệ hoặc Số tiền)</label>
                    <input type="text" name="_price" value="<?php echo esc_attr($price); ?>"
                        placeholder="VD: 500,000 VND hoặc Liên hệ">
                </div>
                <div class="bacera-col">
                    <label>Khoảng thời gian (Thời lượng)</label>
                    <input type="text" name="_duration" value="<?php echo esc_attr($duration); ?>"
                        placeholder="VD: 3 giờ / buổi">
                </div>
            </div>
            <div class="bacera-row" style="margin-bottom: 0;">
                <div class="bacera-col">
                    <label>Người hướng dẫn (Trainer)</label>
                    <input type="text" name="_trainer" value="<?php echo esc_attr($trainer); ?>"
                        placeholder="VD: Nghệ nhân Bát Tràng">
                </div>
                <div class="bacera-col">
                    <label>Đối tượng tham gia</label>
                    <input type="text" name="_target" value="<?php echo esc_attr($target); ?>"
                        placeholder="VD: Khuyên dùng cho lứa tuổi 12+">
                </div>
            </div>
        </div>
        <?php
    }



    public function saveMetaBoxes($post_id)
    {
        if (!isset($_POST['workshop_nonce']) || !wp_verify_nonce($_POST['workshop_nonce'], 'workshop_save'))
            return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        if (!current_user_can('edit_post', $post_id))
            return;

        $post_type = get_post_type($post_id);

        if ($post_type === 'workshop') {
            update_post_meta($post_id, '_price', sanitize_text_field($_POST['_price'] ?? ''));
            update_post_meta($post_id, '_duration', sanitize_text_field($_POST['_duration'] ?? ''));
            update_post_meta($post_id, '_trainer', sanitize_text_field($_POST['_trainer'] ?? ''));
            update_post_meta($post_id, '_target', sanitize_text_field($_POST['_target'] ?? ''));
        }

    }
}
