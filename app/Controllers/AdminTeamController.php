<?php
namespace Bacera\Controllers;

/**
 * Bacera Team Controller
 * ─────────────────────────────────────────────────────────────────
 * Admin page "👥 Nhân sự" under the Bacera menu.
 * Stores team members in `{prefix}bacera_team_members` table.
 *
 * Fields:
 *   id, name, role, department, bio, photo_url, order_index, is_active, created_at
 */
class AdminTeamController {

    const TABLE_SUFFIX = 'bacera_team_members';

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_init', [ $this, 'create_table' ] );

        // AJAX endpoints
        add_action( 'wp_ajax_bacera_team_save',   [ $this, 'ajax_save' ] );
        add_action( 'wp_ajax_bacera_team_delete', [ $this, 'ajax_delete' ] );
        add_action( 'wp_ajax_bacera_team_order',  [ $this, 'ajax_order' ] );
    }

    /* ── Table ──────────────────────────────────────────────────── */

    public function create_table() {
        global $wpdb;
        $table   = $wpdb->prefix . self::TABLE_SUFFIX;
        $charset = $wpdb->get_charset_collate();

        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
            $sql = "CREATE TABLE {$table} (
                id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                name        VARCHAR(120) NOT NULL DEFAULT '',
                role        VARCHAR(120) NOT NULL DEFAULT '',
                department  VARCHAR(120) NOT NULL DEFAULT '',
                bio         TEXT,
                photo_url   VARCHAR(500) NOT NULL DEFAULT '',
                order_index SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                is_active   TINYINT(1) NOT NULL DEFAULT 1,
                created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$charset};";
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
        }
    }

    /* ── Menu ────────────────────────────────────────────────────── */

    public function add_menu() {
        add_submenu_page(
            'bacera-main',
            'Quản lý Nhân sự',
            '👥 Nhân sự',
            'manage_options',
            'bacera-team',
            [ $this, 'render_page' ]
        );
    }

    /* ── AJAX: Save (Add / Edit) ─────────────────────────────────── */

    public function ajax_save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Không có quyền.' ] );
        }
        check_ajax_referer( 'bacera_team_nonce', '_nonce' );

        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_SUFFIX;

        $id         = intval( $_POST['id'] ?? 0 );
        $name       = sanitize_text_field( $_POST['name'] ?? '' );
        $role       = sanitize_text_field( $_POST['role'] ?? '' );
        $department = sanitize_text_field( $_POST['department'] ?? '' );
        $bio        = sanitize_textarea_field( $_POST['bio'] ?? '' );
        $photo_url  = esc_url_raw( $_POST['photo_url'] ?? '' );
        $is_active  = intval( $_POST['is_active'] ?? 1 );

        if ( empty( $name ) ) {
            wp_send_json_error( [ 'message' => 'Tên không được để trống.' ] );
        }

        $data = compact( 'name', 'role', 'department', 'bio', 'photo_url', 'is_active' );

        if ( $id > 0 ) {
            $wpdb->update( $table, $data, [ 'id' => $id ] );
            wp_send_json_success( [ 'message' => '✅ Đã cập nhật thành viên.', 'id' => $id ] );
        } else {
            $max_order = (int) $wpdb->get_var( "SELECT COALESCE(MAX(order_index),0) FROM {$table}" );
            $data['order_index'] = $max_order + 1;
            $data['created_at']  = current_time( 'mysql' );
            $wpdb->insert( $table, $data );
            wp_send_json_success( [ 'message' => '✅ Đã thêm thành viên mới.', 'id' => $wpdb->insert_id ] );
        }
    }

    /* ── AJAX: Delete ────────────────────────────────────────────── */

    public function ajax_delete() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Không có quyền.' ] );
        }
        check_ajax_referer( 'bacera_team_nonce', '_nonce' );

        global $wpdb;
        $id = intval( $_POST['id'] ?? 0 );
        if ( $id <= 0 ) {
            wp_send_json_error( [ 'message' => 'ID không hợp lệ.' ] );
        }
        $wpdb->delete( $wpdb->prefix . self::TABLE_SUFFIX, [ 'id' => $id ] );
        wp_send_json_success( [ 'message' => '🗑️ Đã xóa thành viên.' ] );
    }

    /* ── AJAX: Reorder ───────────────────────────────────────────── */

    public function ajax_order() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }
        check_ajax_referer( 'bacera_team_nonce', '_nonce' );

        global $wpdb;
        $table  = $wpdb->prefix . self::TABLE_SUFFIX;
        $ids    = array_map( 'intval', (array)( $_POST['ids'] ?? [] ) );
        foreach ( $ids as $pos => $id ) {
            $wpdb->update( $table, [ 'order_index' => $pos ], [ 'id' => $id ] );
        }
        wp_send_json_success();
    }

    /* ── Render Page ─────────────────────────────────────────────── */

    public function render_page() {
        global $wpdb;
        $table   = $wpdb->prefix . self::TABLE_SUFFIX;
        $nonce   = wp_create_nonce( 'bacera_team_nonce' );
        $members = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY order_index ASC, id ASC", ARRAY_A ) ?: [];

        // Gather distinct departments for sidebar filter
        $departments = array_unique( array_filter( array_column( $members, 'department' ) ) );
        sort( $departments );

        // Editing?
        $edit_id     = intval( $_GET['edit'] ?? 0 );
        $editing     = null;
        if ( $edit_id > 0 ) {
            $editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $edit_id ), ARRAY_A );
        }
        ?>
        <!-- ════════════════════════════════════════════════════════
             STYLES  (reuses bcfg design tokens exactly)
        ════════════════════════════════════════════════════════ -->
        <style>
        /* ── Layout reuse from ConfigController's bcfg classes ── */
        .bcfg-page    { display:flex; gap:28px; max-width:1140px; padding:16px 0 40px; font-family:"Inter",-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; }
        .bcfg-sidebar { width:232px; flex-shrink:0; }
        .bcfg-content { flex:1; min-width:0; }

        .bcfg-brand   { background:linear-gradient(135deg,#1c1917,#3d2f26); border-radius:12px; padding:20px; margin-bottom:16px; }
        .bcfg-brand h2{ color:#fff; font-size:15px; font-weight:700; margin:0 0 4px; }
        .bcfg-brand p { color:#a8a29e; font-size:11px; margin:0; }

        .bcfg-card         { background:#fff; border:1px solid #eae3d1; border-radius:12px; overflow:hidden; margin-bottom:20px; }
        .bcfg-card-header  { background:#faf8f5; border-bottom:1px solid #eae3d1; padding:14px 22px; display:flex; align-items:center; gap:10px; }
        .bcfg-card-header h2{ margin:0; font-size:14px; font-weight:700; color:#1c1917; }
        .bcfg-card-body    { padding:22px; }

        .bcfg-grid         { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        .bcfg-grid.cols1   { grid-template-columns:1fr; }
        .bcfg-grid.cols3   { grid-template-columns:1fr 1fr 1fr; }
        .bcfg-field        { display:flex; flex-direction:column; gap:6px; }
        .bcfg-field label  { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#6b5344; }
        .bcfg-field input,
        .bcfg-field select,
        .bcfg-field textarea{
            padding:10px 14px; font-size:14px; color:#3d2f26; background:#fff;
            border:1px solid #ded5cd; border-radius:8px;
            box-shadow:0 1px 2px rgba(0,0,0,.03); transition:all .2s;
            width:100%; box-sizing:border-box;
        }
        .bcfg-field textarea { min-height:90px; resize:vertical; }
        .bcfg-field input:focus,
        .bcfg-field select:focus,
        .bcfg-field textarea:focus { border-color:#d95f47; box-shadow:0 0 0 3px rgba(217,95,71,.15); outline:none; }
        .bcfg-hint { font-size:11px; color:#9ca3af; margin-top:3px; }

        .bcfg-footer       { display:flex; align-items:center; gap:14px; padding-top:12px; flex-wrap:wrap; }
        .bcfg-btn-primary  { height:42px; padding:0 24px; background:#d95f47; color:#fff; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; transition:background .15s; }
        .bcfg-btn-primary:hover  { background:#b84d38; }
        .bcfg-btn-secondary{ height:42px; padding:0 20px; background:#f5f0e8; color:#6b5344; border:1px solid #ded5cd; border-radius:8px; font-size:14px; font-weight:500; cursor:pointer; transition:all .15s; }
        .bcfg-btn-secondary:hover{ background:#eae3d1; color:#3d2f26; }
        .bcfg-btn-danger   { height:36px; padding:0 14px; background:#fff1f0; color:#dc2626; border:1px solid #fca5a5; border-radius:7px; font-size:13px; font-weight:500; cursor:pointer; transition:all .15s; }
        .bcfg-btn-danger:hover { background:#dc2626; color:#fff; border-color:#dc2626; }

        .bcfg-saved-bar { background:#dcfce7; border:1px solid #86efac; border-radius:10px; padding:12px 18px; margin-bottom:22px; font-size:13px; color:#166534; display:flex; align-items:center; gap:8px; }
        .bcfg-err-bar   { background:#fef2f2; border:1px solid #fca5a5; border-radius:10px; padding:12px 18px; margin-bottom:22px; font-size:13px; color:#b91c1c; }

        .bcfg-divider { border:0; border-top:1px solid #f1ede1; margin:18px 0; }

        /* ── Stats sidebar ── */
        .tm-stat { background:#fff; border:1px solid #eae3d1; border-radius:10px; padding:14px 16px; margin-bottom:12px; display:flex; align-items:center; gap:12px; }
        .tm-stat-icon { width:38px; height:38px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
        .tm-stat-icon.green { background:#dcfce7; }
        .tm-stat-icon.amber { background:#fef3c7; }
        .tm-stat-icon.blue  { background:#dbeafe; }
        .tm-stat-value { font-size:22px; font-weight:700; color:#1c1917; line-height:1; }
        .tm-stat-label { font-size:11px; color:#78716c; margin-top:2px; }

        /* ── Member list table ── */
        .tm-table { width:100%; border-collapse:collapse; font-size:13px; }
        .tm-table th { background:#faf8f5; border-bottom:2px solid #eae3d1; padding:10px 14px; text-align:left; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#6b5344; white-space:nowrap; }
        .tm-table td { padding:12px 14px; border-bottom:1px solid #f5f0e8; vertical-align:middle; }
        .tm-table tr:hover td { background:#fdf8f4; }
        .tm-table tr:last-child td { border-bottom:none; }

        .tm-avatar { width:44px; height:44px; border-radius:8px; object-fit:cover; background:#EBE7DF; border:2px solid #eae3d1; flex-shrink:0; }
        .tm-name   { font-weight:600; color:#1c1917; }
        .tm-dept   { display:inline-block; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:600; background:#f5f0e8; color:#6b5344; }

        .tm-badge-active   { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; background:#dcfce7; color:#16a34a; }
        .tm-badge-inactive { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; background:#f5f5f4; color:#78716c; }

        /* ── Photo preview ── */
        #tm-photo-preview { width:80px; height:80px; object-fit:cover; border-radius:10px; border:2px solid #eae3d1; display:none; margin-top:8px; }
        #tm-photo-preview.visible { display:block; }

        /* ── Toast notification ── */
        #tm-toast { position:fixed; bottom:28px; right:28px; z-index:99999; background:#1c1917; color:#fff; padding:14px 20px; border-radius:10px; font-size:13px; font-weight:500; box-shadow:0 8px 24px rgba(0,0,0,.2); opacity:0; transform:translateY(8px); transition:all .3s; pointer-events:none; }
        #tm-toast.show { opacity:1; transform:translateY(0); }

        /* ── Drag handle ── */
        .tm-drag { cursor:grab; color:#d6cfc4; font-size:18px; padding-right:6px; }
        .tm-drag:active { cursor:grabbing; }

        /* ── Empty state ── */
        .tm-empty { text-align:center; padding:48px 20px; color:#a8a29e; }
        .tm-empty p { font-size:14px; margin:8px 0 0; }
        </style>

        <div id="tm-toast"></div>

        <div class="bcfg-page">

            <!-- ══ SIDEBAR ══════════════════════════════════════════ -->
            <aside class="bcfg-sidebar">
                <div class="bcfg-brand">
                    <h2>👥 Nhân sự</h2>
                    <p>Quản lý thành viên team</p>
                </div>

                <!-- Stats -->
                <?php
                $total   = count( $members );
                $active  = count( array_filter( $members, fn($m) => $m['is_active'] ) );
                $dept_count = count( $departments );
                ?>
                <div class="tm-stat">
                    <div class="tm-stat-icon green">👤</div>
                    <div>
                        <div class="tm-stat-value"><?php echo $total; ?></div>
                        <div class="tm-stat-label">Tổng thành viên</div>
                    </div>
                </div>
                <div class="tm-stat">
                    <div class="tm-stat-icon amber">✅</div>
                    <div>
                        <div class="tm-stat-value"><?php echo $active; ?></div>
                        <div class="tm-stat-label">Đang hoạt động</div>
                    </div>
                </div>
                <div class="tm-stat">
                    <div class="tm-stat-icon blue">🏢</div>
                    <div>
                        <div class="tm-stat-value"><?php echo $dept_count; ?></div>
                        <div class="tm-stat-label">Phòng ban</div>
                    </div>
                </div>

                <!-- Quick actions -->
                <div class="bcfg-card" style="margin-top:16px;">
                    <div class="bcfg-card-header"><h2>⚡ Thao tác nhanh</h2></div>
                    <div class="bcfg-card-body" style="padding:14px;">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bacera-team' ) ); ?>" class="bcfg-btn-primary" style="display:block;text-align:center;height:38px;line-height:38px;font-size:13px;text-decoration:none;margin-bottom:10px;">
                            ➕ Thêm thành viên
                        </a>
                        <a href="<?php echo esc_url( home_url( '/?page_id=' . get_page_by_path( 'our-team', OBJECT, 'page' )?->ID ) ); ?>" target="_blank" class="bcfg-btn-secondary" style="display:block;text-align:center;height:38px;line-height:38px;font-size:13px;text-decoration:none;">
                            🔗 Xem trang Our Team
                        </a>
                    </div>
                </div>
            </aside>

            <!-- ══ CONTENT ═════════════════════════════════════════ -->
            <div class="bcfg-content">
                <div id="tm-alert"></div>

                <?php if ( $edit_id && $editing ): ?>
                <!-- ──────────────────── EDIT FORM ──────────────────── -->
                <?php $this->render_form( $editing, $nonce ); ?>

                <?php elseif ( ! $edit_id && isset( $_GET['action'] ) && $_GET['action'] === 'add' ): ?>
                <!-- ──────────────────── ADD FORM ───────────────────── -->
                <?php $this->render_form( null, $nonce ); ?>

                <?php else: ?>
                <!-- ──────────────────── LIST ───────────────────────── -->
                <div class="bcfg-card">
                    <div class="bcfg-card-header" style="justify-content:space-between;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span style="font-size:18px;">👥</span>
                            <h2>Danh sách thành viên</h2>
                        </div>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bacera-team&action=add' ) ); ?>"
                           class="bcfg-btn-primary" style="height:36px;line-height:36px;padding:0 18px;font-size:13px;text-decoration:none;display:inline-block;">
                           ➕ Thêm mới
                        </a>
                    </div>
                    <div class="bcfg-card-body" style="padding:0;">
                        <?php if ( empty( $members ) ): ?>
                        <div class="tm-empty">
                            <div style="font-size:40px;">👤</div>
                            <p>Chưa có thành viên nào. <a href="<?php echo esc_url( admin_url('admin.php?page=bacera-team&action=add') ); ?>">Thêm ngay</a></p>
                        </div>
                        <?php else: ?>
                        <table class="tm-table" id="tm-member-table">
                            <thead>
                                <tr>
                                    <th style="width:32px;"></th>
                                    <th style="width:56px;">Ảnh</th>
                                    <th>Họ & Tên</th>
                                    <th>Chức danh</th>
                                    <th>Phòng ban</th>
                                    <th>Trạng thái</th>
                                    <th style="width:130px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="tm-sortable">
                            <?php foreach ( $members as $m ): ?>
                            <tr data-id="<?php echo esc_attr( $m['id'] ); ?>">
                                <td><span class="tm-drag" title="Kéo để sắp xếp">⠿</span></td>
                                <td>
                                    <?php if ( $m['photo_url'] ): ?>
                                    <img src="<?php echo esc_url( $m['photo_url'] ); ?>" alt="" class="tm-avatar">
                                    <?php else: ?>
                                    <div class="tm-avatar" style="background:#EBE7DF;display:flex;align-items:center;justify-content:center;color:#c0a28e;font-size:20px;">👤</div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="tm-name"><?php echo esc_html( $m['name'] ); ?></span></td>
                                <td><?php echo esc_html( $m['role'] ?: '–' ); ?></td>
                                <td>
                                    <?php if ( $m['department'] ): ?>
                                    <span class="tm-dept"><?php echo esc_html( $m['department'] ); ?></span>
                                    <?php else: echo '–'; endif; ?>
                                </td>
                                <td>
                                    <?php if ( $m['is_active'] ): ?>
                                    <span class="tm-badge-active">● Hiển thị</span>
                                    <?php else: ?>
                                    <span class="tm-badge-inactive">○ Ẩn</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=bacera-team&edit=' . $m['id'] ) ); ?>"
                                       style="color:#d95f47;font-size:13px;font-weight:600;text-decoration:none;margin-right:10px;">✏️ Sửa</a>
                                    <button class="bcfg-btn-danger tm-delete-btn" data-id="<?php echo esc_attr( $m['id'] ); ?>">🗑️</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div><!-- /bcfg-content -->
        </div><!-- /bcfg-page -->

        <script>
        (function($){
            const nonce  = '<?php echo esc_js( $nonce ); ?>';
            const ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';

            // ── Toast helper ─────────────────────────
            function toast(msg, ok = true) {
                const el = document.getElementById('tm-toast');
                el.innerHTML = (ok ? '✅ ' : '❌ ') + msg;
                el.classList.add('show');
                setTimeout(() => el.classList.remove('show'), 3200);
            }

            // ── Delete ───────────────────────────────
            document.querySelectorAll('.tm-delete-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (!confirm('Xác nhận xóa thành viên này?')) return;
                    const id = this.dataset.id;
                    fetch(ajaxurl, {
                        method: 'POST',
                        body: new URLSearchParams({ action:'bacera_team_delete', id, _nonce: nonce })
                    }).then(r => r.json()).then(data => {
                        if (data.success) {
                            this.closest('tr').style.transition = 'opacity .3s';
                            this.closest('tr').style.opacity = '0';
                            setTimeout(() => { this.closest('tr').remove(); toast(data.data.message); }, 300);
                        } else {
                            toast(data.data?.message || 'Lỗi!', false);
                        }
                    });
                });
            });

            // ── Save form ────────────────────────────
            const form = document.getElementById('tm-member-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const btn = document.getElementById('tm-submit-btn');
                    btn.disabled = true; btn.textContent = 'Đang lưu…';
                    fetch(ajaxurl, { method:'POST', body: new FormData(form) })
                        .then(r => r.json()).then(data => {
                            if (data.success) {
                                toast(data.data.message);
                                setTimeout(() => window.location = '<?php echo esc_js( admin_url("admin.php?page=bacera-team") ); ?>', 900);
                            } else {
                                toast(data.data?.message || 'Lỗi!', false);
                                btn.disabled = false; btn.textContent = '💾 Lưu thành viên';
                            }
                        });
                });
            }

            // ── Photo URL preview ────────────────────
            const photoInput = document.getElementById('tm-photo-input');
            const photoPreview = document.getElementById('tm-photo-preview');
            if (photoInput && photoPreview) {
                function updatePreview(url) {
                    if (url) { photoPreview.src = url; photoPreview.classList.add('visible'); }
                    else { photoPreview.classList.remove('visible'); }
                }
                photoInput.addEventListener('input', () => updatePreview(photoInput.value.trim()));
                updatePreview(photoInput.value.trim());

                // WP Media picker
                const mediaPicker = document.getElementById('tm-media-btn');
                if (mediaPicker) {
                    mediaPicker.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (typeof wp === 'undefined' || !wp.media) { alert('Vui lòng sử dụng URL thủ công.'); return; }
                        const frame = wp.media({ title:'Chọn ảnh', button:{ text:'Dùng ảnh này' }, multiple:false });
                        frame.on('select', () => {
                            const att = frame.state().get('selection').first().toJSON();
                            photoInput.value = att.url;
                            updatePreview(att.url);
                        });
                        frame.open();
                    });
                }
            }

            // ── Sortable (drag-and-drop reorder) ─────
            const sortable = document.getElementById('tm-sortable');
            if (sortable && typeof Sortable !== 'undefined') {
                Sortable.create(sortable, {
                    handle: '.tm-drag',
                    animation: 200,
                    onEnd: function() {
                        const ids = [...sortable.querySelectorAll('tr')].map(r => r.dataset.id);
                        const body = new URLSearchParams({ action:'bacera_team_order', _nonce: nonce });
                        ids.forEach(id => body.append('ids[]', id));
                        fetch(ajaxurl, { method:'POST', body }).then(r => r.json()).then(d => {
                            if (d.success) toast('Đã lưu thứ tự.');
                        });
                    }
                });
            }

        })(jQuery);
        </script>
        <?php
        // Enqueue WP media uploader
        wp_enqueue_media();
        // Load SortableJS from CDN (lightweight, no jQuery dependency)
        echo '<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>';
    }

    /* ── Shared Form (Add / Edit) ────────────────────────────────── */

    private function render_form( ?array $m, string $nonce ) {
        $is_edit = ! is_null( $m );
        $title   = $is_edit ? '✏️ Sửa thành viên' : '➕ Thêm thành viên mới';

        $departments_preset = [
            'Ban Giám đốc',
            'Sales & Business',
            'Marketing & Creative',
            'Craft & Production',
            'Accounting',
            'HR & Admin',
        ];
        ?>
        <div class="bcfg-card" style="margin-bottom:20px;">
            <div class="bcfg-card-header" style="justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="font-size:18px;">👤</span>
                    <h2><?php echo $title; ?></h2>
                </div>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=bacera-team' ) ); ?>"
                   class="bcfg-btn-secondary" style="height:36px;line-height:36px;padding:0 16px;font-size:13px;text-decoration:none;">
                   ← Quay lại danh sách
                </a>
            </div>

            <div class="bcfg-card-body">
                <form id="tm-member-form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action"  value="bacera_team_save">
                    <input type="hidden" name="_nonce"  value="<?php echo esc_attr( $nonce ); ?>">
                    <input type="hidden" name="id"      value="<?php echo esc_attr( $m['id'] ?? 0 ); ?>">

                    <!-- Row 1: Name + Role -->
                    <div class="bcfg-grid" style="margin-bottom:18px;">
                        <div class="bcfg-field">
                            <label>Họ & Tên <span style="color:#ef4444">*</span></label>
                            <input type="text" name="name" value="<?php echo esc_attr( $m['name'] ?? '' ); ?>" placeholder="Nguyễn Văn A" required>
                        </div>
                        <div class="bcfg-field">
                            <label>Chức danh</label>
                            <input type="text" name="role" value="<?php echo esc_attr( $m['role'] ?? '' ); ?>" placeholder="CEO / Marketing / Sales…">
                        </div>
                    </div>

                    <!-- Row 2: Department + Status -->
                    <div class="bcfg-grid" style="margin-bottom:18px;">
                        <div class="bcfg-field">
                            <label>Phòng ban</label>
                            <select name="department">
                                <option value="">— Chọn phòng ban —</option>
                                <?php foreach ( $departments_preset as $dept ): ?>
                                <option value="<?php echo esc_attr( $dept ); ?>"
                                    <?php selected( $m['department'] ?? '', $dept ); ?>>
                                    <?php echo esc_html( $dept ); ?>
                                </option>
                                <?php endforeach; ?>
                                <?php
                                // Show custom department if it doesn't match presets
                                $cur_dept = $m['department'] ?? '';
                                if ( $cur_dept && ! in_array( $cur_dept, $departments_preset ) ):
                                ?>
                                <option value="<?php echo esc_attr( $cur_dept ); ?>" selected><?php echo esc_html( $cur_dept ); ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="bcfg-field">
                            <label>Trạng thái</label>
                            <select name="is_active">
                                <option value="1" <?php selected( $m['is_active'] ?? 1, 1 ); ?>>● Hiển thị trên website</option>
                                <option value="0" <?php selected( $m['is_active'] ?? 1, 0 ); ?>>○ Ẩn</option>
                            </select>
                        </div>
                    </div>

                    <!-- Row 3: Photo URL -->
                    <div class="bcfg-field" style="margin-bottom:18px;">
                        <label>URL ảnh đại diện</label>
                        <div style="display:flex;gap:10px;align-items:flex-start;">
                            <div style="flex:1;">
                                <input type="url" name="photo_url" id="tm-photo-input"
                                       value="<?php echo esc_attr( $m['photo_url'] ?? '' ); ?>"
                                       placeholder="https://bacera.demo/wp-content/uploads/…">
                                <span class="bcfg-hint">Dán link ảnh từ thư viện media, hoặc nhấn nút bên phải để chọn.</span>
                            </div>
                            <button type="button" id="tm-media-btn" class="bcfg-btn-secondary" style="height:42px;white-space:nowrap;flex-shrink:0;">
                                🖼️ Chọn ảnh
                            </button>
                        </div>
                        <img id="tm-photo-preview" alt="Preview" onerror="this.classList.remove('visible')">
                    </div>

                    <hr class="bcfg-divider">

                    <!-- Row 4: Bio -->
                    <div class="bcfg-field" style="margin-bottom:18px;">
                        <label>Giới thiệu (Bio)</label>
                        <textarea name="bio" placeholder="Vài dòng giới thiệu về thành viên, sở thích, chuyên môn…"><?php echo esc_textarea( $m['bio'] ?? '' ); ?></textarea>
                    </div>

                    <div class="bcfg-footer">
                        <button type="submit" id="tm-submit-btn" class="bcfg-btn-primary">💾 Lưu thành viên</button>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bacera-team' ) ); ?>" class="bcfg-btn-secondary">Hủy</a>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
}
