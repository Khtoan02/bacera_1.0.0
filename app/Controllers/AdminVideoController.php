<?php
namespace Bacera\Controllers;

/**
 * AdminVideoController
 * ─────────────────────────────────────────────────────────────────
 * Quản lý Video: 2 submenu (Video + Danh mục Video) dưới bacera-main.
 * Hỗ trợ 2 loại: Upload từ thư viện WP | Nhúng YouTube URL.
 * Mỗi video có: tiêu đề, mô tả ngắn, danh mục, thumbnail, trạng thái.
 */
class AdminVideoController {

    const TABLE_VIDEOS = 'bacera_videos';
    const TABLE_CATS   = 'bacera_video_categories';
    const NONCE        = 'bacera_video_nonce';
    const PAGE_VIDEOS  = 'bacera-videos';
    const PAGE_CATS    = 'bacera-video-cats';

    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'add_menus' ] );
        add_action( 'admin_init',            [ $this, 'create_tables' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        // Video AJAX
        add_action( 'wp_ajax_bacera_video_save',    [ $this, 'ajax_video_save' ] );
        add_action( 'wp_ajax_bacera_video_delete',  [ $this, 'ajax_video_delete' ] );
        add_action( 'wp_ajax_bacera_video_order',   [ $this, 'ajax_video_order' ] );
        // Category AJAX
        add_action( 'wp_ajax_bacera_vcat_save',     [ $this, 'ajax_cat_save' ] );
        add_action( 'wp_ajax_bacera_vcat_delete',   [ $this, 'ajax_cat_delete' ] );
        add_action( 'wp_ajax_bacera_vcat_order',    [ $this, 'ajax_cat_order' ] );
        // YouTube info fetch
        add_action( 'wp_ajax_bacera_yt_info',       [ $this, 'ajax_yt_info' ] );
    }

    /* ── DB ─────────────────────────────────────────────────────── */

    public function create_tables(): void {
        \Bacera\Database\VideoTables::createTables();
    }

    /* ── Menu ───────────────────────────────────────────────────── */

    public function add_menus(): void {
        add_submenu_page(
            'bacera-main',
            'Quản lý Video',
            'Video',
            'manage_options',
            self::PAGE_VIDEOS,
            [ $this, 'render_videos_page' ]
        );
        add_submenu_page(
            'bacera-main',
            'Danh mục Video',
            'Danh mục Video',
            'manage_options',
            self::PAGE_CATS,
            [ $this, 'render_cats_page' ]
        );
    }

    /* ── Assets ─────────────────────────────────────────────────── */

    public function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'bacera-video' ) === false ) return;
        wp_enqueue_media();
        echo '<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>';

        // Shared CSS (same variables as team controller)
        $css = '
:root {
  --bg:#F8F7F3; --surface:#fff; --surface-2:#F1EEE1; --border:#EAE3D1;
  --text:#3d2f26; --text-2:#8d6a54; --text-3:#c0a28e;
  --accent:#d95f47; --accent-2:#c8513b;
  --green:#166534; --green-bg:#f0fdf4; --green-border:#bbf7d0;
  --amber:#92400e; --amber-bg:#fffbeb; --amber-border:#fde68a;
  --red:#991b1b;   --red-bg:#fef2f2;   --red-border:#fecaca;
  --blue:#1e3a5f;  --blue-bg:#eff6ff;  --blue-border:#bfdbfe;
  --yt:#FF0000;
  --r:10px; --rl:14px;
}
#wpcontent { padding-left:0 !important; }
#wpbody-content { padding-bottom:0; }

.vd-wrap * { box-sizing:border-box; margin:0; padding:0; }
.vd-wrap {
  font-family:"Bricolage Grotesque",system-ui,sans-serif;
  background:var(--bg); color:var(--text); font-size:14px; line-height:1.6;
  min-height:calc(100vh - 32px); padding:32px 36px 64px;
}

/* ── Buttons ── */
.vd-btn { height:34px; padding:0 14px; border-radius:var(--r); font-family:inherit; font-size:13px; font-weight:500; cursor:pointer; transition:all .15s; display:inline-flex; align-items:center; gap:6px; border:1px solid transparent; text-decoration:none!important; }
.vd-btn-outline { background:var(--surface); border-color:var(--border); color:var(--text)!important; }
.vd-btn-outline:hover { background:var(--surface-2)!important; }
.vd-btn-solid  { background:var(--accent); color:#fff!important; border-color:var(--accent); }
.vd-btn-solid:hover { background:var(--accent-2)!important; }
.vd-btn-danger { background:var(--red-bg); color:var(--red)!important; border-color:var(--red-border); }
.vd-btn-ghost  { background:none; border:none; color:var(--text-2); font-family:inherit; font-size:13px; cursor:pointer; padding:4px 8px; border-radius:6px; transition:all .15s; display:inline-flex; align-items:center; gap:5px; }
.vd-btn-ghost:hover { background:var(--surface-2); }
.vd-btn svg { width:13px; height:13px; flex-shrink:0; stroke:currentColor; fill:none; stroke-width:1.8; stroke-linecap:round; }
.vd-btn-sm { height:28px; padding:0 10px; font-size:12px; }

/* ── Badges ── */
.vd-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; }
.vd-badge-green { background:var(--green-bg); color:var(--green); border:1px solid var(--green-border); }
.vd-badge-gray  { background:var(--surface-2); color:var(--text-2); border:1px solid var(--border); }
.vd-badge-yt    { background:#fff5f5; color:var(--yt); border:1px solid #fecaca; }
.vd-badge-upload { background:var(--blue-bg); color:var(--blue); border:1px solid var(--blue-border); }
.vd-badge-dot   { width:6px; height:6px; border-radius:50%; flex-shrink:0; }

/* ── Page header ── */
.vd-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:28px; flex-wrap:wrap; gap:12px; }
.vd-title  { font-size:24px; font-weight:700; letter-spacing:-.5px; }
.vd-sub    { font-size:13px; color:var(--text-2); margin-top:4px; }

/* ── Metrics ── */
.vd-metrics { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:24px; }
.vd-mc { background:var(--surface); border:1px solid var(--border); border-radius:var(--rl); padding:16px 18px; }
.vd-mc:nth-child(1) { border-top:3px solid var(--accent); }
.vd-mc:nth-child(2) { border-top:3px solid var(--yt); }
.vd-mc:nth-child(3) { border-top:3px solid #3b82f6; }
.vd-mc:nth-child(4) { border-top:3px solid var(--green); }
.vd-ml { font-size:11px; font-weight:600; color:var(--text-3); text-transform:uppercase; letter-spacing:.8px; margin-bottom:8px; }
.vd-mv { font-size:26px; font-weight:700; letter-spacing:-.6px; }
.vd-ms { font-size:12px; color:var(--text-2); margin-top:4px; }

/* ── Filter row ── */
.vd-filter { display:flex; gap:8px; margin-bottom:18px; flex-wrap:wrap; align-items:center; }
.vd-search-wrap { position:relative; flex:1; max-width:300px; }
.vd-search-wrap svg { position:absolute; left:10px; top:50%; transform:translateY(-50%); width:14px; height:14px; stroke:var(--text-3); fill:none; stroke-width:1.5; }
.vd-inp { width:100%; height:36px; padding:0 12px 0 34px; font-family:inherit; font-size:13px; border:1px solid var(--border); border-radius:var(--r); background:var(--surface); color:var(--text); outline:none; transition:all .15s; }
.vd-inp:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(217,95,71,.1); }
.vd-sel { height:36px; padding:0 10px; font-family:inherit; font-size:13px; border:1px solid var(--border); border-radius:var(--r); background:var(--surface); color:var(--text-2); outline:none; cursor:pointer; }

/* ── Section card ── */
.vd-sc { background:var(--surface); border:1px solid var(--border); border-radius:var(--rl); overflow:hidden; }
.vd-sh { display:flex; align-items:center; justify-content:space-between; padding:12px 18px; border-bottom:1px solid var(--border); }
.vd-stitle { font-size:13px; font-weight:700; color:var(--text); display:flex; align-items:center; gap:8px; }
.vd-stitle svg { width:15px; height:15px; stroke:var(--text-2); fill:none; stroke-width:1.8; }

/* ── Video grid ── */
.vd-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px; padding:20px; }

/* ── Video card ── */
.vd-card {
  background:var(--surface); border:1px solid var(--border); border-radius:var(--rl);
  overflow:hidden; display:flex; flex-direction:column;
  transition:border-color .18s, box-shadow .18s, transform .18s;
  position:relative;
}
.vd-card:hover { border-color:var(--text-3); box-shadow:0 6px 24px rgba(61,47,38,.09); transform:translateY(-2px); }
.vd-card.hidden-video { opacity:.55; }

/* Thumbnail zone */
.vd-card-thumb {
  position:relative; aspect-ratio:16/9;
  background:linear-gradient(135deg,#2c2420,#3d3028);
  overflow:hidden; cursor:pointer;
}
.vd-card-thumb img { width:100%; height:100%; object-fit:cover; transition:transform .6s; }
.vd-card:hover .vd-card-thumb img { transform:scale(1.04); }
.vd-play-btn {
  position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
  background:rgba(28,25,23,.3); transition:background .2s;
}
.vd-card:hover .vd-play-btn { background:rgba(28,25,23,.5); }
.vd-play-circle {
  width:44px; height:44px; border-radius:50%; background:rgba(255,255,255,.9);
  display:flex; align-items:center; justify-content:center;
  transition:transform .2s; flex-shrink:0;
}
.vd-card:hover .vd-play-circle { transform:scale(1.1); }
.vd-play-circle svg { width:16px; height:16px; fill:#3d2f26; margin-left:3px; }

/* Type badge on thumb */
.vd-type-badge {
  position:absolute; top:8px; left:8px;
  padding:3px 8px; border-radius:6px; font-size:10px; font-weight:700;
  backdrop-filter:blur(6px);
}
.vd-type-yt { background:rgba(255,0,0,.85); color:#fff; }
.vd-type-up { background:rgba(30,58,95,.85); color:#fff; }

/* Status badge on thumb */
.vd-status-badge { position:absolute; top:8px; right:8px; }

/* Drag handle */
.vd-drag {
  position:absolute; bottom:8px; left:8px;
  width:26px; height:26px; border-radius:7px;
  background:rgba(255,255,255,.85); display:flex; align-items:center; justify-content:center;
  cursor:grab; opacity:0; transition:opacity .15s;
}
.vd-card:hover .vd-drag { opacity:1; }
.vd-drag:active { cursor:grabbing; }
.vd-drag svg { width:12px; height:12px; stroke:var(--text-2); fill:none; stroke-width:1.8; }

/* Card body */
.vd-card-body { padding:12px 14px 10px; flex:1; display:flex; flex-direction:column; gap:4px; }
.vd-card-cat  { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:var(--accent); }
.vd-card-title { font-size:13px; font-weight:600; color:var(--text); line-height:1.35; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.vd-card-desc  { font-size:11px; color:var(--text-2); line-height:1.5; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; margin-top:2px; }

/* Card footer */
.vd-card-foot { display:flex; gap:6px; padding:8px 10px; border-top:1px solid var(--border); background:var(--bg); }

/* ── Empty state ── */
.vd-empty { text-align:center; padding:56px 24px; }
.vd-empty-icon { width:48px; height:48px; stroke:var(--text-3); fill:none; stroke-width:1; margin:0 auto 14px; opacity:.4; display:block; }
.vd-empty h3 { font-size:16px; font-weight:600; margin-bottom:6px; }
.vd-empty p { font-size:13px; color:var(--text-2); margin-bottom:18px; }

/* ── Toast ── */
#vd-toast { position:fixed; bottom:28px; right:28px; z-index:99999; background:#1c1917; color:#fff; padding:13px 18px; border-radius:10px; font-size:13px; font-weight:500; box-shadow:0 8px 24px rgba(0,0,0,.2); opacity:0; transform:translateY(8px); transition:all .3s; pointer-events:none; display:flex; align-items:center; gap:8px; }
#vd-toast.show { opacity:1; transform:translateY(0); }
#vd-toast svg { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:2.5; stroke-linecap:round; }
#vd-toast.ok svg { stroke:#4ade80; }
#vd-toast.err svg { stroke:#f87171; }

/* ── Modal backdrop + drawer ── */
.vdm-bd { position:fixed; inset:0; background:rgba(28,25,23,.45); z-index:9990; opacity:0; pointer-events:none; transition:opacity .24s; backdrop-filter:blur(3px); }
.vdm-bd.open { opacity:1; pointer-events:all; }
.vdm-dr {
  position:fixed; top:32px; right:0; bottom:0; width:660px; max-width:96vw;
  background:var(--bg); z-index:9991;
  transform:translateX(100%); transition:transform .28s cubic-bezier(.4,0,.2,1);
  display:flex; flex-direction:column; overflow:hidden;
  box-shadow:-12px 0 48px rgba(28,25,23,.18);
}
.vdm-dr.open { transform:none; }

/* Drawer head */
.vdm-head { display:flex; align-items:center; gap:12px; padding:13px 18px; border-bottom:1px solid var(--border); background:var(--surface); flex-shrink:0; }
.vdm-head-icon { width:34px; height:34px; border-radius:8px; background:var(--surface-2); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; }
.vdm-head-icon svg { width:15px; height:15px; stroke:var(--text-2); fill:none; stroke-width:1.8; stroke-linecap:round; }
.vdm-head-text { flex:1; min-width:0; }
.vdm-head-title { font-size:13px; font-weight:700; color:var(--text); }
.vdm-head-sub   { font-size:11px; color:var(--text-3); margin-top:1px; }
.vdm-close { background:none; border:1px solid var(--border); border-radius:8px; width:30px; height:30px; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--text-2); transition:all .15s; }
.vdm-close:hover { background:var(--surface-2); }
.vdm-close svg { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:2; }

/* Drawer body */
.vdm-body { flex:1; overflow-y:auto; padding:20px; display:flex; flex-direction:column; gap:16px; }

/* Drawer footer */
.vdm-foot { padding:13px 18px; border-top:1px solid var(--border); background:var(--surface); display:flex; gap:8px; align-items:center; flex-shrink:0; }
.vdm-foot-gap { flex:1; }

/* ── Form elements (inside drawer) ── */
.vdf-section { background:var(--surface); border:1px solid var(--border); border-radius:var(--rl); overflow:hidden; }
.vdf-sec-head { display:flex; align-items:center; gap:8px; padding:11px 16px; border-bottom:1px solid var(--border); font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--text-2); }
.vdf-sec-head svg { width:12px; height:12px; stroke:currentColor; fill:none; stroke-width:1.8; }
.vdf-sec-body { padding:16px 18px; display:flex; flex-direction:column; gap:14px; }
.vdf-row2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.vdf-field { display:flex; flex-direction:column; gap:5px; }
.vdf-label { font-size:11px; font-weight:600; color:var(--text-2); display:flex; align-items:center; gap:4px; }
.vdf-req { color:var(--accent); }
.vdf-input, .vdf-select, .vdf-textarea {
  width:100%; font-family:inherit; font-size:13px;
  border:1px solid var(--border); border-radius:var(--r);
  background:var(--surface); color:var(--text); outline:none; transition:all .15s;
}
.vdf-input, .vdf-select { height:38px; padding:0 12px; }
.vdf-textarea { padding:9px 12px; resize:vertical; min-height:80px; line-height:1.7; }
.vdf-input:focus, .vdf-select:focus, .vdf-textarea:focus {
  border-color:var(--accent); box-shadow:0 0 0 3px rgba(217,95,71,.1);
}
.vdf-hint { font-size:11px; color:var(--text-3); line-height:1.5; }

/* ── Video source detected badge ── */
.vdf-src-badge {
  display:inline-flex; align-items:center; gap:6px;
  padding:4px 10px; border-radius:6px; font-size:11px; font-weight:700;
  margin-bottom:4px;
}
.vdf-src-badge.yt   { background:#fff5f5; color:var(--yt); border:1px solid #fecaca; }
.vdf-src-badge.up   { background:var(--blue-bg); color:var(--blue); border:1px solid var(--blue-border); }
.vdf-src-badge.none { background:var(--surface-2); color:var(--text-3); border:1px solid var(--border); }

/* ── Status toggle ── */
.vdf-status-group { display:flex; gap:8px; }
.vdf-status-pill {
  flex:1; display:flex; align-items:center; justify-content:center; gap:6px;
  padding:8px 10px; border-radius:8px; border:1.5px solid var(--border);
  background:var(--surface); cursor:pointer; font-size:12px; font-weight:600; color:var(--text-2);
  transition:all .15s;
}
.vdf-status-pill input { display:none; }
.vdf-status-pill.on-show { border-color:var(--green); background:var(--green-bg); color:var(--green); }
.vdf-status-pill.on-hide { border-color:var(--border); background:var(--surface-2); color:var(--text-2); }
.vdf-status-pill svg { width:12px; height:12px; stroke:currentColor; fill:none; stroke-width:2; }

/* ── Thumbnail preview ── */
.vdf-thumb-preview {
  width:100%; aspect-ratio:16/9; border-radius:10px; overflow:hidden;
  background:linear-gradient(135deg,#2c2420,#3d3028);
  border:2px dashed var(--border); position:relative; cursor:pointer;
  display:flex; align-items:center; justify-content:center;
  transition:border-color .15s;
}
.vdf-thumb-preview:hover { border-color:var(--text-3); }
.vdf-thumb-preview img { width:100%; height:100%; object-fit:cover; display:block; }
.vdf-thumb-placeholder { display:flex; flex-direction:column; align-items:center; gap:8px; color:var(--text-3); }
.vdf-thumb-placeholder svg { width:28px; height:28px; stroke:currentColor; fill:none; stroke-width:1.5; }
.vdf-thumb-placeholder span { font-size:12px; font-weight:500; }

/* ── YouTube ID preview box ── */
.vdf-yt-preview { display:none; border-radius:10px; overflow:hidden; aspect-ratio:16/9; background:#000; margin-top:6px; }
.vdf-yt-preview iframe { width:100%; height:100%; border:none; }

/* ── Loading spinner ── */
.vdf-spinner { display:none; width:18px; height:18px; border:2px solid var(--border); border-top-color:var(--accent); border-radius:50%; animation:spin .6s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }

/* ── Category table ── */
.vd-table { width:100%; border-collapse:collapse; }
.vd-table th { background:var(--surface-2); color:var(--text-2); font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; padding:10px 14px; text-align:left; border-bottom:1px solid var(--border); }
.vd-table td { padding:12px 14px; border-bottom:1px solid var(--border); font-size:13px; vertical-align:middle; }
.vd-table tr:last-child td { border-bottom:none; }
.vd-table tr:hover td { background:var(--surface-2); }
.vd-row-drag { cursor:grab; color:var(--text-3); }
.vd-row-drag:active { cursor:grabbing; }
';
        wp_register_style( 'bacera-video-admin', false );
        wp_enqueue_style( 'bacera-video-admin' );
        wp_add_inline_style( 'bacera-video-admin', $css );
    }

    /* ── AJAX: Video Save ───────────────────────────────────────── */

    public function ajax_video_save(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( [ 'message' => 'Không có quyền.' ] );
        check_ajax_referer( self::NONCE, '_nonce' );

        global $wpdb;
        $t = $wpdb->prefix . self::TABLE_VIDEOS;

        $id          = intval( $_POST['id'] ?? 0 );
        $cat_id      = intval( $_POST['category_id'] ?? 0 );
        $title       = sanitize_text_field( $_POST['title'] ?? '' );
        $description = sanitize_textarea_field( $_POST['description'] ?? '' );
        $type        = in_array( $_POST['type'] ?? '', ['upload','youtube'] ) ? $_POST['type'] : 'upload';
        $video_url   = esc_url_raw( $_POST['video_url'] ?? '' );
        $thumb_url   = esc_url_raw( $_POST['thumbnail_url'] ?? '' );
        $youtube_id  = sanitize_text_field( $_POST['youtube_id'] ?? '' );
        $duration    = sanitize_text_field( $_POST['duration'] ?? '' );
        $is_active   = intval( $_POST['is_active'] ?? 1 );

        if ( empty( $title ) ) wp_send_json_error( [ 'message' => 'Tiêu đề không được để trống.' ] );
        if ( $type === 'youtube' && empty( $youtube_id ) ) wp_send_json_error( [ 'message' => 'Vui lòng nhập YouTube URL.' ] );
        if ( $type === 'upload' && empty( $video_url ) ) wp_send_json_error( [ 'message' => 'Vui lòng chọn file video.' ] );

        // Auto-extract YT ID from URL if user pasted full URL
        if ( $type === 'youtube' && empty( $youtube_id ) && ! empty( $video_url ) ) {
            preg_match('/(?:v=|\/embed\/|youtu\.be\/)([A-Za-z0-9_-]{11})/', $video_url, $m);
            $youtube_id = $m[1] ?? '';
        }

        $data = compact( 'cat_id', 'title', 'description', 'type', 'video_url', 'thumb_url', 'youtube_id', 'duration', 'is_active' );
        $data = [ 'category_id' => $cat_id, 'title' => $title, 'description' => $description, 'type' => $type, 'video_url' => $video_url, 'thumbnail_url' => $thumb_url, 'youtube_id' => $youtube_id, 'duration' => $duration, 'is_active' => $is_active ];

        if ( $id > 0 ) {
            $wpdb->update( $t, $data, [ 'id' => $id ] );
            wp_send_json_success( [ 'message' => 'Đã cập nhật video.', 'id' => $id ] );
        } else {
            $max = (int) $wpdb->get_var( "SELECT COALESCE(MAX(order_index),0) FROM {$t}" );
            $data['order_index'] = $max + 1;
            $data['created_at']  = current_time( 'mysql' );
            $wpdb->insert( $t, $data );
            wp_send_json_success( [ 'message' => 'Đã thêm video mới.', 'id' => $wpdb->insert_id ] );
        }
    }

    public function ajax_video_delete(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        check_ajax_referer( self::NONCE, '_nonce' );
        global $wpdb;
        $id = intval( $_POST['id'] ?? 0 );
        if ( $id <= 0 ) wp_send_json_error( [ 'message' => 'ID không hợp lệ.' ] );
        $wpdb->delete( $wpdb->prefix . self::TABLE_VIDEOS, [ 'id' => $id ] );
        wp_send_json_success( [ 'message' => 'Đã xóa video.' ] );
    }

    public function ajax_video_order(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        check_ajax_referer( self::NONCE, '_nonce' );
        global $wpdb;
        $t   = $wpdb->prefix . self::TABLE_VIDEOS;
        $ids = array_map( 'intval', (array)( $_POST['ids'] ?? [] ) );
        foreach ( $ids as $pos => $vid_id ) {
            $wpdb->update( $t, [ 'order_index' => $pos ], [ 'id' => $vid_id ] );
        }
        wp_send_json_success();
    }

    /* ── AJAX: Category Save/Delete/Order ───────────────────────── */

    public function ajax_cat_save(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        check_ajax_referer( self::NONCE, '_nonce' );

        global $wpdb;
        $t    = $wpdb->prefix . self::TABLE_CATS;
        $id   = intval( $_POST['id'] ?? 0 );
        $name = sanitize_text_field( $_POST['name'] ?? '' );
        $desc = sanitize_textarea_field( $_POST['description'] ?? '' );
        $active = intval( $_POST['is_active'] ?? 1 );

        if ( empty( $name ) ) wp_send_json_error( [ 'message' => 'Tên danh mục không được để trống.' ] );

        $slug = sanitize_title( $name );
        // Ensure unique slug
        if ( $id === 0 ) {
            $base = $slug; $i = 1;
            while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE slug=%s", $slug ) ) ) {
                $slug = $base . '-' . $i++;
            }
        }

        $data = [ 'name' => $name, 'slug' => $slug, 'description' => $desc, 'is_active' => $active ];

        if ( $id > 0 ) {
            $wpdb->update( $t, $data, [ 'id' => $id ] );
            wp_send_json_success( [ 'message' => 'Đã cập nhật danh mục.', 'id' => $id ] );
        } else {
            $max = (int) $wpdb->get_var( "SELECT COALESCE(MAX(order_index),0) FROM {$t}" );
            $data['order_index'] = $max + 1;
            $wpdb->insert( $t, $data );
            wp_send_json_success( [ 'message' => 'Đã thêm danh mục.', 'id' => $wpdb->insert_id ] );
        }
    }

    public function ajax_cat_delete(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        check_ajax_referer( self::NONCE, '_nonce' );
        global $wpdb;
        $id = intval( $_POST['id'] ?? 0 );
        // Move videos to uncategorized (0)
        $wpdb->update( $wpdb->prefix . self::TABLE_VIDEOS, [ 'category_id' => 0 ], [ 'category_id' => $id ] );
        $wpdb->delete( $wpdb->prefix . self::TABLE_CATS, [ 'id' => $id ] );
        wp_send_json_success( [ 'message' => 'Đã xóa danh mục. Video đã được chuyển sang Chưa phân loại.' ] );
    }

    public function ajax_cat_order(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        check_ajax_referer( self::NONCE, '_nonce' );
        global $wpdb;
        $t   = $wpdb->prefix . self::TABLE_CATS;
        $ids = array_map( 'intval', (array)( $_POST['ids'] ?? [] ) );
        foreach ( $ids as $pos => $cid ) {
            $wpdb->update( $t, [ 'order_index' => $pos ], [ 'id' => $cid ] );
        }
        wp_send_json_success();
    }

    /* ── AJAX: Fetch YouTube metadata ───────────────────────────── */

    public function ajax_yt_info(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        check_ajax_referer( self::NONCE, '_nonce' );

        $url = sanitize_text_field( $_POST['url'] ?? '' );
        preg_match( '/(?:v=|\/embed\/|youtu\.be\/|shorts\/)([A-Za-z0-9_-]{11})/', $url, $m );
        $yt_id = $m[1] ?? '';

        if ( ! $yt_id ) wp_send_json_error( [ 'message' => 'Không tìm thấy YouTube ID từ URL này.' ] );

        // YouTube's oEmbed (no API key needed)
        $oembed_url = 'https://www.youtube.com/oembed?url=' . urlencode( "https://youtu.be/{$yt_id}" ) . '&format=json';
        $response   = wp_remote_get( $oembed_url, [ 'timeout' => 6 ] );

        $thumb = "https://img.youtube.com/vi/{$yt_id}/hqdefault.jpg";
        $title = '';

        if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
            $body  = json_decode( wp_remote_retrieve_body( $response ), true );
            $title = $body['title'] ?? '';
            $thumb = $body['thumbnail_url'] ?? $thumb;
        }

        wp_send_json_success( [
            'yt_id'     => $yt_id,
            'title'     => $title,
            'thumbnail' => $thumb,
        ] );
    }

    /* ── Render: Videos Page ─────────────────────────────────────── */

    public function render_videos_page(): void {
        global $wpdb;
        $tv   = $wpdb->prefix . self::TABLE_VIDEOS;
        $tc   = $wpdb->prefix . self::TABLE_CATS;
        $nonce = wp_create_nonce( self::NONCE );

        $videos = $wpdb->get_results(
            "SELECT v.*, c.name as cat_name FROM {$tv} v
             LEFT JOIN {$tc} c ON v.category_id = c.id
             ORDER BY v.order_index ASC, v.id ASC",
            ARRAY_A
        ) ?: [];

        $cats = $wpdb->get_results(
            "SELECT * FROM {$tc} ORDER BY order_index ASC, id ASC",
            ARRAY_A
        ) ?: [];

        // Metrics
        $total   = count( $videos );
        $active  = count( array_filter( $videos, fn($v) => $v['is_active'] ) );
        $yt_cnt  = count( array_filter( $videos, fn($v) => $v['type'] === 'youtube' ) );
        $up_cnt  = $total - $yt_cnt;
        ?>

        <div id="vd-toast">
            <svg viewBox="0 0 14 14" id="vd-toast-icon"><polyline points="2,7 5.5,10.5 12,3"/></svg>
            <span id="vd-toast-msg"></span>
        </div>

        <div class="vd-wrap">

            <!-- Header -->
            <div class="vd-header">
                <div>
                    <div class="vd-title">Quản lý Video</div>
                    <div class="vd-sub">Danh sách tất cả video — kéo để sắp xếp thứ tự hiển thị</div>
                </div>
                <div style="display:flex;gap:8px;">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_CATS ) ); ?>" class="vd-btn vd-btn-outline">
                        <svg viewBox="0 0 14 14"><rect x="1" y="1" width="12" height="5" rx="1"/><rect x="1" y="8" width="5" height="5" rx="1"/><rect x="8" y="8" width="5" height="5" rx="1"/></svg>
                        Danh mục
                    </a>
                    <button class="vd-btn vd-btn-solid" id="vd-add-btn">
                        <svg viewBox="0 0 14 14"><path d="M7 2v10M2 7h10" stroke-linecap="round"/></svg>
                        Thêm video
                    </button>
                </div>
            </div>

            <!-- Metrics -->
            <div class="vd-metrics">
                <div class="vd-mc">
                    <div class="vd-ml">Tổng Video</div>
                    <div class="vd-mv"><?php echo $total; ?></div>
                    <div class="vd-ms">Trong thư viện</div>
                </div>
                <div class="vd-mc">
                    <div class="vd-ml">YouTube</div>
                    <div class="vd-mv"><?php echo $yt_cnt; ?></div>
                    <div class="vd-ms">Video nhúng YT</div>
                </div>
                <div class="vd-mc">
                    <div class="vd-ml">Upload</div>
                    <div class="vd-mv"><?php echo $up_cnt; ?></div>
                    <div class="vd-ms">Video tự host</div>
                </div>
                <div class="vd-mc">
                    <div class="vd-ml">Đang hiển thị</div>
                    <div class="vd-mv"><?php echo $active; ?></div>
                    <div class="vd-ms"><?php echo $total - $active; ?> đang ẩn</div>
                </div>
            </div>

            <!-- Filter -->
            <div class="vd-filter">
                <div class="vd-search-wrap">
                    <svg viewBox="0 0 16 16"><circle cx="7" cy="7" r="4"/><path d="M10.5 10.5l3 3" stroke-linecap="round"/></svg>
                    <input class="vd-inp" type="text" id="vd-search" placeholder="Tìm tiêu đề video...">
                </div>
                <select class="vd-sel" id="vd-cat-filter">
                    <option value="">Tất cả danh mục</option>
                    <?php foreach ( $cats as $cat ): ?>
                    <option value="<?php echo esc_attr( $cat['id'] ); ?>"><?php echo esc_html( $cat['name'] ); ?></option>
                    <?php endforeach; ?>
                    <option value="0">Chưa phân loại</option>
                </select>
                <select class="vd-sel" id="vd-type-filter">
                    <option value="">Tất cả loại</option>
                    <option value="youtube">YouTube</option>
                    <option value="upload">Upload</option>
                </select>
                <select class="vd-sel" id="vd-status-filter">
                    <option value="">Tất cả trạng thái</option>
                    <option value="1">Hiển thị</option>
                    <option value="0">Ẩn</option>
                </select>
            </div>

            <!-- Video grid -->
            <div class="vd-sc">
                <div class="vd-sh">
                    <span class="vd-stitle">
                        <svg viewBox="0 0 16 16"><polygon points="6,4 14,8 6,12"/><line x1="2" y1="4" x2="2" y2="12"/></svg>
                        Danh sách Video
                    </span>
                    <span style="font-size:11px;color:var(--text-3);display:flex;align-items:center;gap:5px;">
                        <svg style="width:12px;height:12px;stroke:var(--text-3);fill:none;stroke-width:1.8;" viewBox="0 0 12 12"><circle cx="4" cy="2.5" r=".9"/><circle cx="8" cy="2.5" r=".9"/><circle cx="4" cy="6" r=".9"/><circle cx="8" cy="6" r=".9"/><circle cx="4" cy="9.5" r=".9"/><circle cx="8" cy="9.5" r=".9"/></svg>
                        Kéo thẻ để sắp xếp thứ tự
                    </span>
                </div>

                <?php if ( empty( $videos ) ): ?>
                <div class="vd-empty">
                    <svg class="vd-empty-icon" viewBox="0 0 48 48"><rect x="4" y="8" width="40" height="32" rx="4"/><polygon points="19,18 33,24 19,30"/></svg>
                    <h3>Chưa có video nào</h3>
                    <p>Thêm video đầu tiên vào thư viện của bạn.</p>
                    <button class="vd-btn vd-btn-solid" id="vd-add-btn-2">
                        <svg viewBox="0 0 14 14"><path d="M7 2v10M2 7h10" stroke-linecap="round"/></svg>
                        Thêm video đầu tiên
                    </button>
                </div>
                <?php else: ?>
                <div class="vd-grid" id="vd-sortable">
                    <?php foreach ( $videos as $v ):
                        $thumb = $v['thumbnail_url'] ?: ( $v['type'] === 'youtube' && $v['youtube_id']
                            ? "https://img.youtube.com/vi/{$v['youtube_id']}/hqdefault.jpg"
                            : '' );
                    ?>
                    <div class="vd-card <?php echo $v['is_active'] ? '' : 'hidden-video'; ?>"
                         data-id="<?php echo esc_attr( $v['id'] ); ?>"
                         data-cat="<?php echo esc_attr( $v['category_id'] ); ?>"
                         data-type="<?php echo esc_attr( $v['type'] ); ?>"
                         data-status="<?php echo $v['is_active'] ? '1' : '0'; ?>"
                         data-search="<?php echo esc_attr( strtolower( $v['title'] ) ); ?>">

                        <!-- Drag -->
                        <div class="vd-drag" title="Kéo để sắp xếp">
                            <svg viewBox="0 0 14 14"><circle cx="5" cy="3.5" r="1"/><circle cx="9" cy="3.5" r="1"/><circle cx="5" cy="7" r="1"/><circle cx="9" cy="7" r="1"/><circle cx="5" cy="10.5" r="1"/><circle cx="9" cy="10.5" r="1"/></svg>
                        </div>

                        <!-- Thumbnail -->
                        <div class="vd-card-thumb">
                            <?php if ( $thumb ): ?>
                            <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $v['title'] ); ?>" loading="lazy">
                            <?php else: ?>
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#6b5344;">
                                <svg width="36" height="36" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><rect x="2" y="4" width="20" height="16" rx="2"/><polygon points="10,9 17,12 10,15" fill="currentColor"/></svg>
                            </div>
                            <?php endif; ?>
                            <div class="vd-play-btn">
                                <div class="vd-play-circle">
                                    <svg viewBox="0 0 16 16"><polygon points="4,2 14,8 4,14" fill="#3d2f26"/></svg>
                                </div>
                            </div>
                            <!-- Type badge -->
                            <div class="vd-type-badge <?php echo $v['type'] === 'youtube' ? 'vd-type-yt' : 'vd-type-up'; ?>">
                                <?php echo $v['type'] === 'youtube' ? '▶ YouTube' : '⬆ Upload'; ?>
                            </div>
                            <!-- Status -->
                            <div class="vd-status-badge">
                                <?php if ( $v['is_active'] ): ?>
                                <span class="vd-badge vd-badge-green" style="backdrop-filter:blur(4px);background:rgba(240,253,244,.9);">
                                    <span class="vd-badge-dot" style="background:var(--green)"></span>Hiển thị
                                </span>
                                <?php else: ?>
                                <span class="vd-badge vd-badge-gray" style="backdrop-filter:blur(4px);background:rgba(241,238,225,.9);">
                                    <span class="vd-badge-dot" style="background:var(--text-3)"></span>Ẩn
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="vd-card-body">
                            <?php if ( $v['cat_name'] ): ?>
                            <div class="vd-card-cat"><?php echo esc_html( $v['cat_name'] ); ?></div>
                            <?php endif; ?>
                            <div class="vd-card-title"><?php echo esc_html( $v['title'] ); ?></div>
                            <?php if ( $v['description'] ): ?>
                            <div class="vd-card-desc"><?php echo esc_html( $v['description'] ); ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Actions -->
                        <div class="vd-card-foot">
                            <button class="vd-btn vd-btn-outline vd-btn-sm vd-edit-btn" data-id="<?php echo esc_attr( $v['id'] ); ?>">
                                <svg viewBox="0 0 14 14"><path d="M9.5 2.5l2 2-7 7H2.5V9l7-6.5z"/></svg>
                                Sửa
                            </button>
                            <button class="vd-btn vd-btn-danger vd-btn-sm vd-del-btn" data-id="<?php echo esc_attr( $v['id'] ); ?>">
                                <svg viewBox="0 0 14 14"><polyline points="1,3 13,3"/><path d="M5,3V1h4v2"/><path d="M2,3l1,9h8l1-9"/></svg>
                                Xóa
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /vd-wrap -->

        <!-- ══ EDIT/ADD DRAWER ══ -->
        <div id="vdm-bd" class="vdm-bd"></div>
        <div id="vdm-dr" class="vdm-dr" role="dialog" aria-modal="true">
            <div class="vdm-head">
                <div class="vdm-head-icon">
                    <svg viewBox="0 0 16 16"><polygon points="6,4 14,8 6,12"/><line x1="2" y1="4" x2="2" y2="12" stroke-linecap="round"/></svg>
                </div>
                <div class="vdm-head-text">
                    <div class="vdm-head-title" id="vdm-title">Thêm Video</div>
                    <div class="vdm-head-sub" id="vdm-sub">Điền thông tin video bên dưới</div>
                </div>
                <button class="vdm-close" id="vdm-close">
                    <svg viewBox="0 0 14 14"><path d="M2 2l10 10M12 2L2 12" stroke-linecap="round"/></svg>
                </button>
            </div>

            <div class="vdm-body">

                <!-- Smart source input -->
                <div class="vdf-section">
                    <div class="vdf-sec-head">
                        <svg viewBox="0 0 12 12"><polygon points="4,2 10,6 4,10"/></svg>
                        Nguồn Video
                    </div>
                    <div class="vdf-sec-body">

                        <!-- Detected badge -->
                        <div id="vd-src-badge" class="vdf-src-badge none">
                            <span id="vd-src-badge-icon">○</span>
                            <span id="vd-src-badge-text">Chưa có nguồn video</span>
                        </div>

                        <!-- Single smart URL input -->
                        <div class="vdf-field">
                            <label class="vdf-label">Link YouTube hoặc URL video <span class="vdf-req">*</span></label>
                            <div style="display:flex;gap:8px;align-items:center;">
                                <input class="vdf-input" type="text" id="vd-src-url"
                                       placeholder="Dán link YouTube hoặc URL video...">
                                <div class="vdf-spinner" id="vd-src-spinner"></div>
                            </div>
                            <div id="vd-src-hint" class="vdf-hint">Hỗ trợ: youtube.com/watch?v=... · youtu.be/... · shorts/... · hoặc URL file .mp4</div>
                        </div>

                        <!-- OR divider -->
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="flex:1;height:1px;background:var(--border);"></div>
                            <span style="font-size:11px;color:var(--text-3);font-weight:600;">HOẶC</span>
                            <div style="flex:1;height:1px;background:var(--border);"></div>
                        </div>

                        <!-- WP Media picker button -->
                        <button type="button" class="vd-btn vd-btn-outline" id="vd-pick-video"
                                style="width:100%;justify-content:center;height:42px;">
                            <svg viewBox="0 0 14 14"><rect x="1" y="2" width="12" height="10" rx="1"/><path d="M1 6h12"/><path d="M5 2v4M9 2v4"/></svg>
                            Chọn video từ Thư viện WordPress
                        </button>

                        <input type="hidden" id="vd-yt-id">
                        <input type="hidden" id="vd-detected-type" value="">

                    </div>
                </div>

                <!-- Thumbnail — always shown -->
                <div class="vdf-section">
                    <div class="vdf-sec-head">
                        <svg viewBox="0 0 12 12"><rect x="1" y="1" width="10" height="10" rx="1.5"/><circle cx="4" cy="4" r="1"/><path d="M1 8l3-3 2.5 2.5L9 5l3 3"/></svg>
                        Ảnh Thumbnail <span style="font-weight:400;color:var(--text-3);font-size:10px;"> — tự động lấy từ YouTube nếu không chọn</span>
                    </div>
                    <div class="vdf-sec-body">
                        <div class="vdf-thumb-preview" id="vd-thumb-zone">
                            <img id="vd-thumb-img" src="" alt="" style="display:none;">
                            <div class="vdf-thumb-placeholder" id="vd-thumb-placeholder">
                                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                                <span>Nhấp để chọn ảnh bìa</span>
                            </div>
                        </div>
                        <input type="hidden" id="vd-thumb-url">
                        <div class="vdf-hint" id="vd-thumb-hint" style="display:none;">Thumbnail đã được tự động lấy từ YouTube — nhấp vào ảnh để thay thế.</div>
                    </div>
                </div>

                <!-- Video info -->
                <div class="vdf-section">
                    <div class="vdf-sec-head">
                        <svg viewBox="0 0 12 12"><path d="M2 2h8M2 5h8M2 8h5"/></svg>
                        Thông tin Video
                    </div>
                    <div class="vdf-sec-body">
                        <div class="vdf-field">
                            <label class="vdf-label">Tiêu đề <span class="vdf-req">*</span></label>
                            <input class="vdf-input" type="text" id="vd-title" placeholder="Tiêu đề video...">
                        </div>
                        <div class="vdf-field">
                            <label class="vdf-label">Mô tả ngắn</label>
                            <textarea class="vdf-textarea" id="vd-desc" placeholder="Mô tả ngắn về nội dung video..."></textarea>
                        </div>
                        <div class="vdf-row2">
                            <div class="vdf-field">
                                <label class="vdf-label">Danh mục</label>
                                <select class="vdf-select" id="vd-cat">
                                    <option value="0">— Chưa phân loại —</option>
                                    <?php foreach ( $cats as $cat ): ?>
                                    <option value="<?php echo esc_attr( $cat['id'] ); ?>"><?php echo esc_html( $cat['name'] ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="vdf-field">
                                <label class="vdf-label">Thời lượng</label>
                                <input class="vdf-input" type="text" id="vd-duration" placeholder="vd: 12:34">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="vdf-section">
                    <div class="vdf-sec-head">
                        <svg viewBox="0 0 12 12"><circle cx="6" cy="6" r="5"/><path d="M4 6l1.5 1.5L8 4"/></svg>
                        Trạng thái hiển thị
                    </div>
                    <div class="vdf-sec-body">
                        <div class="vdf-status-group">
                            <label class="vdf-status-pill on-show" id="spill-show">
                                <input type="radio" name="vd_status" value="1" checked>
                                <svg viewBox="0 0 12 12"><circle cx="6" cy="6" r="5"/><path d="M3.5 6l2 2 3-3" stroke-linecap="round"/></svg>
                                Hiển thị
                            </label>
                            <label class="vdf-status-pill" id="spill-hide">
                                <input type="radio" name="vd_status" value="0">
                                <svg viewBox="0 0 12 12"><path d="M2 6s1.5-3 4-3 4 3 4 3-1.5 3-4 3-4-3-4-3z"/><path d="M10 2L2 10" stroke-linecap="round"/></svg>
                                Ẩn
                            </label>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="vdm-id" value="0">

            </div><!-- /body -->

            <div class="vdm-foot">
                <button class="vd-btn vd-btn-danger vd-btn-sm" id="vdm-del-btn" style="display:none;">
                    <svg viewBox="0 0 14 14"><polyline points="1,3 13,3"/><path d="M5,3V1h4v2"/><path d="M2,3l1,9h8l1-9"/></svg>
                    Xóa video
                </button>
                <span class="vdm-foot-gap"></span>
                <button class="vd-btn vd-btn-ghost" id="vdm-cancel">Hủy</button>
                <button class="vd-btn vd-btn-solid" id="vdm-save">
                    <svg viewBox="0 0 14 14"><path d="M2 8l4 4L12 3"/></svg>
                    Lưu Video
                </button>
            </div>
        </div>

        <?php $this->render_videos_script( $nonce, $cats ); ?>
        <?php
    }

    /* ── Render: Categories Page ─────────────────────────────────── */

    public function render_cats_page(): void {
        global $wpdb;
        $tc   = $wpdb->prefix . self::TABLE_CATS;
        $tv   = $wpdb->prefix . self::TABLE_VIDEOS;
        $nonce = wp_create_nonce( self::NONCE );

        $cats = $wpdb->get_results(
            "SELECT c.*, (SELECT COUNT(*) FROM {$tv} WHERE category_id = c.id) as video_count
             FROM {$tc} c ORDER BY c.order_index ASC, c.id ASC",
            ARRAY_A
        ) ?: [];
        ?>

        <div id="vd-toast">
            <svg viewBox="0 0 14 14" id="vd-toast-icon"><polyline points="2,7 5.5,10.5 12,3"/></svg>
            <span id="vd-toast-msg"></span>
        </div>

        <div class="vd-wrap">
            <div class="vd-header">
                <div>
                    <div class="vd-title">Danh mục Video</div>
                    <div class="vd-sub"><?php echo count($cats); ?> danh mục — kéo để sắp xếp thứ tự tab trên frontend</div>
                </div>
                <div style="display:flex;gap:8px;">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_VIDEOS ) ); ?>" class="vd-btn vd-btn-outline">
                        <svg viewBox="0 0 14 14"><polygon points="5,3 12,7 5,11"/><line x1="2" y1="3" x2="2" y2="11" stroke-linecap="round"/></svg>
                        Về danh sách Video
                    </a>
                    <button class="vd-btn vd-btn-solid" id="vc-add-btn">
                        <svg viewBox="0 0 14 14"><path d="M7 2v10M2 7h10" stroke-linecap="round"/></svg>
                        Thêm danh mục
                    </button>
                </div>
            </div>

            <div class="vd-sc">
                <div class="vd-sh">
                    <span class="vd-stitle">
                        <svg viewBox="0 0 16 16"><rect x="1" y="1" width="14" height="5" rx="1.5"/><rect x="1" y="9" width="6" height="6" rx="1.5"/><rect x="9" y="9" width="6" height="6" rx="1.5"/></svg>
                        Tất cả danh mục
                    </span>
                    <span style="font-size:11px;color:var(--text-3);">Kéo hàng để sắp xếp thứ tự</span>
                </div>

                <?php if ( empty( $cats ) ): ?>
                <div class="vd-empty">
                    <svg class="vd-empty-icon" viewBox="0 0 48 48"><rect x="4" y="4" width="40" height="18" rx="4"/><rect x="4" y="28" width="18" height="18" rx="4"/><rect x="26" y="28" width="18" height="18" rx="4"/></svg>
                    <h3>Chưa có danh mục nào</h3>
                    <p>Tạo danh mục để phân loại video theo chủ đề.</p>
                </div>
                <?php else: ?>
                <table class="vd-table" id="vc-table">
                    <thead>
                        <tr>
                            <th style="width:30px;"></th>
                            <th>Tên danh mục</th>
                            <th>Slug</th>
                            <th>Số video</th>
                            <th>Trạng thái</th>
                            <th style="width:130px;"></th>
                        </tr>
                    </thead>
                    <tbody id="vc-tbody">
                        <?php foreach ( $cats as $cat ): ?>
                        <tr data-id="<?php echo esc_attr( $cat['id'] ); ?>">
                            <td class="vd-row-drag" title="Kéo để sắp xếp">
                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="4" cy="3" r=".8"/><circle cx="8" cy="3" r=".8"/><circle cx="4" cy="6" r=".8"/><circle cx="8" cy="6" r=".8"/><circle cx="4" cy="9" r=".8"/><circle cx="8" cy="9" r=".8"/></svg>
                            </td>
                            <td>
                                <strong><?php echo esc_html( $cat['name'] ); ?></strong>
                                <?php if ( $cat['description'] ): ?>
                                <div style="font-size:11px;color:var(--text-3);margin-top:2px;"><?php echo esc_html( $cat['description'] ); ?></div>
                                <?php endif; ?>
                            </td>
                            <td><code style="font-size:11px;background:var(--surface-2);padding:2px 6px;border-radius:4px;"><?php echo esc_html( $cat['slug'] ); ?></code></td>
                            <td>
                                <span class="vd-badge <?php echo $cat['video_count'] > 0 ? 'vd-badge-upload' : 'vd-badge-gray'; ?>">
                                    <?php echo (int)$cat['video_count']; ?> video
                                </span>
                            </td>
                            <td>
                                <?php if ( $cat['is_active'] ): ?>
                                <span class="vd-badge vd-badge-green"><span class="vd-badge-dot" style="background:var(--green)"></span>Hiển thị</span>
                                <?php else: ?>
                                <span class="vd-badge vd-badge-gray"><span class="vd-badge-dot" style="background:var(--text-3)"></span>Ẩn</span>
                                <?php endif; ?>
                            </td>
                            <td style="display:flex;gap:6px;">
                                <button class="vd-btn vd-btn-outline vd-btn-sm vc-edit-btn"
                                        data-id="<?php echo esc_attr($cat['id']); ?>"
                                        data-name="<?php echo esc_attr($cat['name']); ?>"
                                        data-desc="<?php echo esc_attr($cat['description']); ?>"
                                        data-active="<?php echo $cat['is_active']; ?>">
                                    <svg viewBox="0 0 14 14"><path d="M9.5 2.5l2 2-7 7H2.5V9l7-6.5z"/></svg>
                                    Sửa
                                </button>
                                <button class="vd-btn vd-btn-danger vd-btn-sm vc-del-btn"
                                        data-id="<?php echo esc_attr($cat['id']); ?>"
                                        data-count="<?php echo esc_attr($cat['video_count']); ?>">
                                    <svg viewBox="0 0 14 14"><polyline points="1,3 13,3"/><path d="M5,3V1h4v2"/><path d="M2,3l1,9h8l1-9"/></svg>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Category Modal -->
        <div id="vcm-bd" class="vdm-bd"></div>
        <div id="vcm-dr" class="vdm-dr" style="width:460px;" role="dialog">
            <div class="vdm-head">
                <div class="vdm-head-icon">
                    <svg viewBox="0 0 16 16"><rect x="1" y="1" width="14" height="5" rx="1.5"/></svg>
                </div>
                <div class="vdm-head-text">
                    <div class="vdm-head-title" id="vcm-title">Thêm danh mục</div>
                    <div class="vdm-head-sub">Điền thông tin danh mục</div>
                </div>
                <button class="vdm-close" id="vcm-close">
                    <svg viewBox="0 0 14 14"><path d="M2 2l10 10M12 2L2 12" stroke-linecap="round"/></svg>
                </button>
            </div>
            <div class="vdm-body">
                <div class="vdf-section">
                    <div class="vdf-sec-body">
                        <div class="vdf-field">
                            <label class="vdf-label">Tên danh mục <span class="vdf-req">*</span></label>
                            <input class="vdf-input" type="text" id="vc-name" placeholder="vd: Pottery Wheel Throwing">
                        </div>
                        <div class="vdf-field">
                            <label class="vdf-label">Mô tả ngắn</label>
                            <textarea class="vdf-textarea" id="vc-desc" placeholder="Mô tả về danh mục này..." style="min-height:60px;"></textarea>
                        </div>
                        <div class="vdf-field">
                            <label class="vdf-label">Trạng thái</label>
                            <div class="vdf-status-group">
                                <label class="vdf-status-pill on-show" id="vcspill-show">
                                    <input type="radio" name="vc_status" value="1" checked>
                                    <svg viewBox="0 0 12 12"><circle cx="6" cy="6" r="5"/><path d="M3.5 6l2 2 3-3"/></svg>
                                    Hiển thị
                                </label>
                                <label class="vdf-status-pill" id="vcspill-hide">
                                    <input type="radio" name="vc_status" value="0">
                                    <svg viewBox="0 0 12 12"><path d="M2 6s1.5-3 4-3 4 3 4 3-1.5 3-4 3-4-3-4-3z"/><path d="M10 2L2 10"/></svg>
                                    Ẩn
                                </label>
                            </div>
                        </div>
                        <input type="hidden" id="vcm-id" value="0">
                    </div>
                </div>
            </div>
            <div class="vdm-foot">
                <span class="vdm-foot-gap"></span>
                <button class="vd-btn vd-btn-ghost" id="vcm-cancel">Hủy</button>
                <button class="vd-btn vd-btn-solid" id="vcm-save">
                    <svg viewBox="0 0 14 14"><path d="M2 8l4 4L12 3"/></svg>
                    Lưu danh mục
                </button>
            </div>
        </div>

        <?php $this->render_cats_script( $nonce ); ?>
        <?php
    }

    /* ── JS: Videos page ─────────────────────────────────────────── */

    private function render_videos_script( string $nonce, array $cats ): void {
        $cats_json = wp_json_encode( array_column( $cats, null, 'id' ) );
        ?>
<script>
(function(){
'use strict';
var AJAX='<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
var NONCE='<?php echo esc_js($nonce); ?>';

/* ── Toast ── */
function toast(msg,ok){
    var t=document.getElementById('vd-toast'),i=document.getElementById('vd-toast-icon'),m=document.getElementById('vd-toast-msg');
    t.className='show '+(ok===false?'err':'ok');
    i.innerHTML=ok===false?'<path d="M2 2l10 10M12 2L2 12" stroke-linecap="round"/>':'<polyline points="2,7 5.5,10.5 12,3"/>';
    m.textContent=msg; clearTimeout(t._t); t._t=setTimeout(function(){t.className='';},3000);
}

/* ── Drawer ── */
var bd=document.getElementById('vdm-bd'), dr=document.getElementById('vdm-dr');
function openDrawer(){ bd.classList.add('open'); dr.classList.add('open'); document.body.style.overflow='hidden'; }
function closeDrawer(){ bd.classList.remove('open'); dr.classList.remove('open'); document.body.style.overflow=''; }
bd.addEventListener('click',closeDrawer);
document.getElementById('vdm-close').addEventListener('click',closeDrawer);
document.getElementById('vdm-cancel').addEventListener('click',closeDrawer);
document.addEventListener('keydown',function(e){ if(e.key==='Escape') closeDrawer(); });

/* ── Type toggle ── */
var radiosType=document.querySelectorAll('[name="vd_type"]');
var secUpload=document.getElementById('section-upload'), secYt=document.getElementById('section-youtube');
var pillUp=document.getElementById('pill-upload'), pillYt=document.getElementById('pill-youtube');
function updateTypeUI(val){
    secUpload.style.display = val==='upload'?'':'none';
    secYt.style.display     = val==='youtube'?'':'none';
    pillUp.className='vdf-type-pill '+(val==='upload'?'sel-upload':'');
    pillYt.className='vdf-type-pill '+(val==='youtube'?'sel-youtube':'');
}
radiosType.forEach(function(r){ r.addEventListener('change',function(){ updateTypeUI(r.value); }); });

/* ── Status toggle ── */
var radiosStatus=document.querySelectorAll('[name="vd_status"]');
var spillShow=document.getElementById('spill-show'), spillHide=document.getElementById('spill-hide');
function updateStatusUI(val){
    spillShow.className='vdf-status-pill '+(val==='1'?'on-show':'');
    spillHide.className='vdf-status-pill '+(val==='0'?'on-hide':'on-hide'.replace('on-hide',''));
}
radiosStatus.forEach(function(r){ r.addEventListener('change',function(){ updateStatusUI(r.value); }); });

/* ── WP Media picker: video ── */
document.getElementById('vd-pick-video').addEventListener('click',function(){
    var frame=wp.media({ title:'Chọn file video', button:{text:'Chọn'}, library:{type:'video'}, multiple:false });
    frame.on('select',function(){
        var att=frame.state().get('selection').first().toJSON();
        document.getElementById('vd-video-url').value=att.url;
        document.getElementById('vd-video-name').textContent=att.filename||att.url;
    });
    frame.open();
});

/* ── WP Media picker: thumbnail ── */
document.getElementById('vd-thumb-zone').addEventListener('click',function(){
    var frame=wp.media({ title:'Chọn ảnh thumbnail', button:{text:'Chọn'}, library:{type:'image'}, multiple:false });
    frame.on('select',function(){
        var att=frame.state().get('selection').first().toJSON();
        setThumb(att.url);
    });
    frame.open();
});
function setThumb(url){
    var img=document.getElementById('vd-thumb-img');
    var ph=document.getElementById('vd-thumb-placeholder');
    document.getElementById('vd-thumb-url').value=url;
    if(url){ img.src=url; img.style.display='block'; ph.style.display='none'; }
    else   { img.style.display='none'; ph.style.display='flex'; }
}

/* ── YouTube fetch ── */
document.getElementById('vd-yt-fetch').addEventListener('click',function(){
    var url=document.getElementById('vd-yt-url').value.trim();
    if(!url) return;
    var spinner=document.getElementById('vd-yt-spinner');
    var errEl=document.getElementById('vd-yt-error');
    spinner.style.display='block'; errEl.style.display='none';

    var fd=new FormData();
    fd.append('action','bacera_yt_info'); fd.append('_nonce',NONCE); fd.append('url',url);
    fetch(AJAX,{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(res){
        srcSpinner.style.display='none';
        if(!res.success){ setSrcBadge('yt','YouTube ID: '+ytId); return; }
        setSrcBadge('yt','YouTube — '+(res.data.title||'ID: '+res.data.yt_id));
        var titleEl=document.getElementById('vd-title');
        if(!titleEl.value && res.data.title) titleEl.value=res.data.title;
        if(res.data.thumbnail && !document.getElementById('vd-thumb-url').value){
            setThumb(res.data.thumbnail, true);
        }
    })
    .catch(function(){ srcSpinner.style.display='none'; setSrcBadge('yt','YouTube ID: '+ytId); });
}

/* ── Reset form ── */
function resetDrawer(){
    document.getElementById('vd-src-url').value='';
    document.getElementById('vd-yt-id').value='';
    document.getElementById('vd-detected-type').value='';
    document.getElementById('vd-title').value='';
    document.getElementById('vd-desc').value='';
    document.getElementById('vd-cat').value='0';
    document.getElementById('vd-duration').value='';
    setSrcBadge('none','Chưa có nguồn video');
    setThumb('', false);
}

/* ── Open ADD ── */
function openAdd(){
    document.getElementById('vdm-title').textContent='Thêm Video mới';
    document.getElementById('vdm-sub').textContent='Nhập link YouTube hoặc chọn file từ thư viện';
    document.getElementById('vdm-id').value='0';
    resetDrawer();
    document.querySelector('[name="vd_status"][value="1"]').checked=true; updateStatusUI('1');
    document.getElementById('vdm-del-btn').style.display='none';
    openDrawer();
}
var aBtn=document.getElementById('vd-add-btn');
if(aBtn) aBtn.addEventListener('click',openAdd);
var aBtn2=document.getElementById('vd-add-btn-2');
if(aBtn2) aBtn2.addEventListener('click',openAdd);

/* ── Open EDIT ── */
document.querySelectorAll('.vd-edit-btn').forEach(function(btn){
    btn.addEventListener('click',function(){
        var id=btn.getAttribute('data-id');
        var data=window._vd_data && window._vd_data[id];
        document.getElementById('vdm-title').textContent='Chỉnh sửa Video';
        document.getElementById('vdm-sub').textContent='Cập nhật thông tin video';
        document.getElementById('vdm-id').value=id;
        document.getElementById('vdm-del-btn').style.display='';
        resetDrawer();
        if(data){
            document.getElementById('vd-title').value=data.title||'';
            document.getElementById('vd-desc').value=data.description||'';
            document.getElementById('vd-cat').value=data.category_id||'0';
            document.getElementById('vd-duration').value=data.duration||'';
            var type=data.type==='youtube'?'youtube':'upload';
            document.getElementById('vd-detected-type').value=type;
            document.getElementById('vd-src-url').value=data.video_url||( type==='youtube' && data.youtube_id ? 'https://youtu.be/'+data.youtube_id : '' );
            document.getElementById('vd-yt-id').value=data.youtube_id||'';
            if(type==='youtube') setSrcBadge('yt','YouTube — '+(data.title||'ID: '+data.youtube_id));
            else setSrcBadge('up','Upload — '+(data.video_url||'').split('/').pop());
            var thumb=data.thumbnail_url||( data.youtube_id ? 'https://img.youtube.com/vi/'+data.youtube_id+'/hqdefault.jpg' : '');
            setThumb(thumb, type==='youtube'&&!data.thumbnail_url);
            var sv=( data.is_active==='1'||data.is_active===1 ) ? '1' : '0';
            document.querySelector('[name="vd_status"][value="'+sv+'"]').checked=true; updateStatusUI(sv);
        }
        openDrawer();
    });
});

/* ── Save ── */
document.getElementById('vdm-save').addEventListener('click',function(){
    var detectedType=document.getElementById('vd-detected-type').value||'upload';
    var status=document.querySelector('[name="vd_status"]:checked').value;
    var srcUrl=document.getElementById('vd-src-url').value.trim();
    var ytId=document.getElementById('vd-yt-id').value;
    var thumbUrl=document.getElementById('vd-thumb-url').value;
    if(!srcUrl){ toast('Vui lòng nhập link YouTube hoặc chọn file video.',false); return; }
    if(!document.getElementById('vd-title').value.trim()){ toast('Tiêu đề không được để trống.',false); return; }
    var fd=new FormData();
    fd.append('action','bacera_video_save'); fd.append('_nonce',NONCE);
    fd.append('id',document.getElementById('vdm-id').value);
    fd.append('title',document.getElementById('vd-title').value.trim());
    fd.append('description',document.getElementById('vd-desc').value.trim());
    fd.append('category_id',document.getElementById('vd-cat').value);
    fd.append('duration',document.getElementById('vd-duration').value.trim());
    fd.append('type',detectedType);
    fd.append('video_url',srcUrl);
    fd.append('thumbnail_url',thumbUrl);
    fd.append('youtube_id',ytId);
    fd.append('is_active',status);
    var btn=document.getElementById('vdm-save');
    btn.disabled=true; btn.textContent='Đang lưu...';
    fetch(AJAX,{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(res){
        btn.disabled=false; btn.innerHTML='<svg viewBox="0 0 14 14" style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:1.8;"><path d="M2 8l4 4L12 3"/></svg> Lưu Video';
        if(res.success){ toast(res.data.message,true); closeDrawer(); setTimeout(function(){location.reload();},800); }
        else toast(res.data.message||'Lỗi',false);
    })
    .catch(function(){ btn.disabled=false; toast('Lỗi kết nối',false); });
});

/* ── Delete (card & drawer) ── */
document.querySelectorAll('.vd-del-btn').forEach(function(btn){
    btn.addEventListener('click',function(e){
        e.stopPropagation();
        if(!confirm('Xóa video này? Hành động không thể hoàn tác.')) return;
        var fd=new FormData();
        fd.append('action','bacera_video_delete'); fd.append('_nonce',NONCE); fd.append('id',btn.getAttribute('data-id'));
        fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){
            if(res.success){ toast(res.data.message,true); setTimeout(function(){location.reload();},800); }
            else toast(res.data.message||'Lỗi',false);
        });
    });
});
document.getElementById('vdm-del-btn').addEventListener('click',function(){
    var id=document.getElementById('vdm-id').value;
    if(!id||id==='0') return;
    if(!confirm('Xóa video này? Hành động không thể hoàn tác.')) return;
    var fd=new FormData();
    fd.append('action','bacera_video_delete'); fd.append('_nonce',NONCE); fd.append('id',id);
    fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){
        if(res.success){ toast(res.data.message,true); closeDrawer(); setTimeout(function(){location.reload();},800); }
        else toast(res.data.message||'Lỗi',false);
    });
});

/* ── Filters ── */
function applyFilters(){
    var q=document.getElementById('vd-search').value.toLowerCase();
    var catF=document.getElementById('vd-cat-filter').value;
    var typeF=document.getElementById('vd-type-filter').value;
    var stF=document.getElementById('vd-status-filter').value;
    document.querySelectorAll('.vd-card').forEach(function(c){
        var show=true;
        if(q && c.getAttribute('data-search').indexOf(q)===-1) show=false;
        if(catF && c.getAttribute('data-cat')!==catF) show=false;
        if(typeF && c.getAttribute('data-type')!==typeF) show=false;
        if(stF && c.getAttribute('data-status')!==stF) show=false;
        c.style.display=show?'':'none';
    });
}
['vd-search','vd-cat-filter','vd-type-filter','vd-status-filter'].forEach(function(id){
    var el=document.getElementById(id); if(el) el.addEventListener('input',applyFilters);
});

/* ── Sortable ── */
var sortEl=document.getElementById('vd-sortable');
if(sortEl && typeof Sortable!=='undefined'){
    Sortable.create(sortEl,{
        handle:'.vd-drag', animation:150, ghostClass:'vd-card-ghost',
        onEnd:function(){
            var ids=[].map.call(sortEl.querySelectorAll('.vd-card'),function(c){ return c.getAttribute('data-id'); });
            var fd=new FormData();
            fd.append('action','bacera_video_order'); fd.append('_nonce',NONCE);
            ids.forEach(function(id){ fd.append('ids[]',id); });
            fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){
                if(res.success) toast('Đã lưu thứ tự.',true);
            });
        }
    });
}
})();
</script>

<?php
// Embed video data for edit prefill
$tv_all = $GLOBALS['wpdb']->get_results(
    "SELECT * FROM " . $GLOBALS['wpdb']->prefix . self::TABLE_VIDEOS . " ORDER BY id ASC",
    ARRAY_A
) ?: [];
$vd_map = [];
foreach ( $tv_all as $v ) { $vd_map[$v['id']] = $v; }
echo '<script>window._vd_data=' . wp_json_encode( $vd_map ) . ';</script>';
    }

    /* ── JS: Categories page ─────────────────────────────────────── */

    private function render_cats_script( string $nonce ): void {
        ?>
<script>
(function(){
'use strict';
var AJAX='<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
var NONCE='<?php echo esc_js($nonce); ?>';

function toast(msg,ok){
    var t=document.getElementById('vd-toast'),i=document.getElementById('vd-toast-icon'),m=document.getElementById('vd-toast-msg');
    t.className='show '+(ok===false?'err':'ok');
    i.innerHTML=ok===false?'<path d="M2 2l10 10M12 2L2 12" stroke-linecap="round"/>':'<polyline points="2,7 5.5,10.5 12,3"/>';
    m.textContent=msg; clearTimeout(t._t); t._t=setTimeout(function(){t.className='';},3000);
}

/* ── Cat drawer ── */
var bd=document.getElementById('vcm-bd'), dr=document.getElementById('vcm-dr');
function openCatDrawer(){ bd.classList.add('open'); dr.classList.add('open'); document.body.style.overflow='hidden'; }
function closeCatDrawer(){ bd.classList.remove('open'); dr.classList.remove('open'); document.body.style.overflow=''; }
bd.addEventListener('click',closeCatDrawer);
document.getElementById('vcm-close').addEventListener('click',closeCatDrawer);
document.getElementById('vcm-cancel').addEventListener('click',closeCatDrawer);

/* Status pill */
document.querySelectorAll('[name="vc_status"]').forEach(function(r){
    r.addEventListener('change',function(){
        document.getElementById('vcspill-show').className='vdf-status-pill '+(r.value==='1'?'on-show':'');
        document.getElementById('vcspill-hide').className='vdf-status-pill '+(r.value==='0'?'on-hide':'');
    });
});

/* Add btn */
document.getElementById('vc-add-btn').addEventListener('click',function(){
    document.getElementById('vcm-title').textContent='Thêm danh mục';
    document.getElementById('vcm-id').value='0';
    document.getElementById('vc-name').value='';
    document.getElementById('vc-desc').value='';
    document.querySelector('[name="vc_status"][value="1"]').checked=true;
    document.getElementById('vcspill-show').className='vdf-status-pill on-show';
    document.getElementById('vcspill-hide').className='vdf-status-pill';
    openCatDrawer();
});

/* Edit btn */
document.querySelectorAll('.vc-edit-btn').forEach(function(btn){
    btn.addEventListener('click',function(){
        document.getElementById('vcm-title').textContent='Chỉnh sửa danh mục';
        document.getElementById('vcm-id').value=btn.getAttribute('data-id');
        document.getElementById('vc-name').value=btn.getAttribute('data-name');
        document.getElementById('vc-desc').value=btn.getAttribute('data-desc');
        var active=btn.getAttribute('data-active');
        document.querySelector('[name="vc_status"][value="'+active+'"]').checked=true;
        document.getElementById('vcspill-show').className='vdf-status-pill '+(active==='1'?'on-show':'');
        document.getElementById('vcspill-hide').className='vdf-status-pill '+(active==='0'?'on-hide':'');
        openCatDrawer();
    });
});

/* Save */
document.getElementById('vcm-save').addEventListener('click',function(){
    var fd=new FormData();
    fd.append('action','bacera_vcat_save'); fd.append('_nonce',NONCE);
    fd.append('id',document.getElementById('vcm-id').value);
    fd.append('name',document.getElementById('vc-name').value.trim());
    fd.append('description',document.getElementById('vc-desc').value.trim());
    fd.append('is_active',document.querySelector('[name="vc_status"]:checked').value);
    var btn=document.getElementById('vcm-save');
    btn.disabled=true; btn.textContent='Đang lưu...';
    fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){
        btn.disabled=false; btn.textContent='Lưu danh mục';
        if(res.success){ toast(res.data.message,true); closeCatDrawer(); setTimeout(function(){location.reload();},800); }
        else toast(res.data.message||'Lỗi',false);
    });
});

/* Delete */
document.querySelectorAll('.vc-del-btn').forEach(function(btn){
    btn.addEventListener('click',function(){
        var count=parseInt(btn.getAttribute('data-count')||'0',10);
        var msg=count>0
            ?('Danh mục này có '+count+' video. Các video sẽ được chuyển sang Chưa phân loại. Tiếp tục?')
            :'Xóa danh mục này?';
        if(!confirm(msg)) return;
        var fd=new FormData();
        fd.append('action','bacera_vcat_delete'); fd.append('_nonce',NONCE); fd.append('id',btn.getAttribute('data-id'));
        fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){
            if(res.success){ toast(res.data.message,true); setTimeout(function(){location.reload();},800); }
            else toast(res.data.message||'Lỗi',false);
        });
    });
});

/* Sortable rows */
var tbody=document.getElementById('vc-tbody');
if(tbody && typeof Sortable!=='undefined'){
    Sortable.create(tbody,{
        handle:'.vd-row-drag', animation:120,
        onEnd:function(){
            var ids=[].map.call(tbody.querySelectorAll('tr'),function(r){ return r.getAttribute('data-id'); });
            var fd=new FormData();
            fd.append('action','bacera_vcat_order'); fd.append('_nonce',NONCE);
            ids.forEach(function(id){ fd.append('ids[]',id); });
            fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){
                if(res.success) toast('Đã cập nhật thứ tự.',true);
            });
        }
    });
}
})();
</script>
        <?php
    }
}
