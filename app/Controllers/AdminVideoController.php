<?php
namespace Bacera\Controllers;

/**
 * AdminVideoController — Premium UI/UX Edition
 * Quản lý Video: Upload từ thư viện WP hoặc nhúng YouTube.
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

        add_action( 'wp_ajax_bacera_video_save',   [ $this, 'ajax_video_save' ] );
        add_action( 'wp_ajax_bacera_video_delete', [ $this, 'ajax_video_delete' ] );
        add_action( 'wp_ajax_bacera_video_order',  [ $this, 'ajax_video_order' ] );
        add_action( 'wp_ajax_bacera_vcat_save',    [ $this, 'ajax_cat_save' ] );
        add_action( 'wp_ajax_bacera_vcat_delete',  [ $this, 'ajax_cat_delete' ] );
        add_action( 'wp_ajax_bacera_vcat_order',   [ $this, 'ajax_cat_order' ] );
        add_action( 'wp_ajax_bacera_yt_info',      [ $this, 'ajax_yt_info' ] );
    }

    public function create_tables(): void {
        \Bacera\Database\VideoTables::createTables();
    }

    public function add_menus(): void {
        add_submenu_page( 'bacera-main', 'Quản lý Video', 'Video', 'manage_options', self::PAGE_VIDEOS, [ $this, 'render_videos_page' ] );
        add_submenu_page( 'bacera-main', 'Danh mục Video', 'Danh mục Video', 'manage_options', self::PAGE_CATS, [ $this, 'render_cats_page' ] );
    }

    /* ── Assets ───────────────────────────────────────────────────── */

    public function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'bacera-video' ) === false ) return;
        wp_enqueue_media();
        echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300;400;500;600;700&display=swap" rel="stylesheet">';
        echo '<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>';
        echo '<style>' . $this->get_css() . '</style>';
    }

    private function get_css(): string { return '
/* ══ Reset & Variables ═══════════════════════════════════════════ */
:root {
  --bg:#F6F5EF; --surface:#ffffff; --surface-2:#F1EDE2; --surface-3:#EAE3D0;
  --border:#E3DAC8; --border-2:#D5C9B3;
  --text:#3d2f26; --text-2:#7a5c47; --text-3:#b09070; --text-4:#d4bfa8;
  --accent:#d95f47; --accent-h:#c8513b; --accent-light:#FDF1EE; --accent-border:#f3c4b9;
  --yt:#FF0000; --yt-bg:#FFF5F5; --yt-border:#fecaca;
  --up-color:#2563eb; --up-bg:#EFF6FF; --up-border:#bfdbfe;
  --green:#16a34a; --green-bg:#f0fdf4; --green-border:#bbf7d0;
  --amber:#d97706; --amber-bg:#fffbeb; --amber-border:#fde68a;
  --red:#dc2626; --red-bg:#fef2f2; --red-border:#fecaca;
  --shadow-sm:0 1px 3px rgba(61,47,38,.06); --shadow-md:0 4px 16px rgba(61,47,38,.1); --shadow-lg:0 12px 40px rgba(61,47,38,.15);
  --r:8px; --rl:12px; --rxl:16px;
  --transition:.18s cubic-bezier(.4,0,.2,1);
}
#wpcontent { padding-left:0 !important; }
#wpbody-content { padding-bottom:0; }
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }

/* ══ Base ══════════════════════════════════════════════════════════ */
.vd-app {
  font-family:"Bricolage Grotesque", system-ui, sans-serif;
  font-size:14px; line-height:1.6; color:var(--text);
  background:var(--bg); min-height:calc(100vh - 32px);
}

/* ══ Topbar ════════════════════════════════════════════════════════ */
.vd-topbar {
  background:var(--surface); border-bottom:1px solid var(--border);
  padding:0 32px; height:60px;
  display:flex; align-items:center; justify-content:space-between; gap:16px;
  position:sticky; top:32px; z-index:100; box-shadow:var(--shadow-sm);
}
.vd-topbar-left { display:flex; align-items:center; gap:16px; }
.vd-topbar-logo {
  width:36px; height:36px; border-radius:10px;
  background:linear-gradient(135deg,var(--accent),#e8845c);
  display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.vd-topbar-logo svg { width:18px; height:18px; fill:#fff; }
.vd-topbar-title { font-size:16px; font-weight:700; color:var(--text); letter-spacing:-.3px; }
.vd-topbar-sub { font-size:12px; color:var(--text-3); margin-top:1px; }
.vd-topbar-nav { display:flex; gap:4px; }
.vd-nav-link {
  height:32px; padding:0 14px; display:flex; align-items:center; gap:6px;
  font-size:12px; font-weight:600; border-radius:var(--r); text-decoration:none!important;
  color:var(--text-2)!important; transition:all var(--transition); border:1px solid transparent;
}
.vd-nav-link:hover { background:var(--surface-2); color:var(--text)!important; }
.vd-nav-link.active { background:var(--accent-light); color:var(--accent)!important; border-color:var(--accent-border); }
.vd-nav-link svg { width:13px; height:13px; stroke:currentColor; fill:none; stroke-width:1.8; stroke-linecap:round; }

/* ══ Buttons ═══════════════════════════════════════════════════════ */
.vd-btn { height:36px; padding:0 16px; display:inline-flex; align-items:center; gap:7px; border-radius:var(--r); font-family:inherit; font-size:13px; font-weight:600; cursor:pointer; transition:all var(--transition); border:1px solid transparent; text-decoration:none!important; white-space:nowrap; flex-shrink:0; }
.vd-btn svg { width:13px; height:13px; stroke:currentColor; fill:none; stroke-width:1.8; stroke-linecap:round; flex-shrink:0; }
.vd-btn-primary { background:var(--accent); color:#fff!important; box-shadow:0 1px 3px rgba(217,95,71,.3); }
.vd-btn-primary:hover { background:var(--accent-h)!important; box-shadow:0 2px 8px rgba(217,95,71,.4); transform:translateY(-1px); }
.vd-btn-secondary { background:var(--surface); color:var(--text)!important; border-color:var(--border); box-shadow:var(--shadow-sm); }
.vd-btn-secondary:hover { background:var(--surface-2)!important; border-color:var(--border-2); }
.vd-btn-ghost { background:transparent; color:var(--text-2)!important; border:none; height:32px; padding:0 10px; font-size:12px; border-radius:var(--r); }
.vd-btn-ghost:hover { background:var(--surface-2); color:var(--text)!important; }
.vd-btn-danger { background:var(--red-bg); color:var(--red)!important; border-color:var(--red-border); }
.vd-btn-danger:hover { background:#fee2e2!important; }
.vd-btn-sm { height:30px; padding:0 12px; font-size:12px; }
.vd-btn-xs { height:26px; padding:0 9px; font-size:11px; border-radius:6px; }
.vd-btn-icon { width:32px; height:32px; padding:0; justify-content:center; }
.vd-btn-icon svg { width:14px; height:14px; }

/* ══ Stats strip ═══════════════════════════════════════════════════ */
.vd-stats { display:flex; gap:0; padding:24px 32px 0; }
.vd-stat {
  flex:1; background:var(--surface); border:1px solid var(--border);
  padding:18px 20px; position:relative; overflow:hidden;
  transition:box-shadow var(--transition);
}
.vd-stat:first-child { border-radius:var(--rl) 0 0 var(--rl); }
.vd-stat:last-child  { border-radius:0 var(--rl) var(--rl) 0; }
.vd-stat + .vd-stat  { border-left:none; }
.vd-stat:hover { box-shadow:var(--shadow-md); z-index:1; }
.vd-stat-accent { position:absolute; left:0; top:0; bottom:0; width:3px; }
.vd-stat-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:var(--text-3); margin-bottom:8px; }
.vd-stat-value { font-size:28px; font-weight:700; letter-spacing:-.8px; color:var(--text); line-height:1; }
.vd-stat-sub   { font-size:11px; color:var(--text-3); margin-top:5px; }
.vd-stat-icon  { position:absolute; right:16px; top:50%; transform:translateY(-50%); opacity:.06; }
.vd-stat-icon svg { width:48px; height:48px; }

/* ══ Toolbar ═══════════════════════════════════════════════════════ */
.vd-toolbar {
  display:flex; align-items:center; gap:10px; padding:20px 32px 16px; flex-wrap:wrap;
}
.vd-search-wrap { position:relative; flex:1; min-width:200px; max-width:320px; }
.vd-search-wrap svg { position:absolute; left:11px; top:50%; transform:translateY(-50%); width:14px; height:14px; stroke:var(--text-3); fill:none; stroke-width:1.5; pointer-events:none; }
.vd-search-input {
  width:100%; height:36px; padding:0 12px 0 36px; font-family:inherit; font-size:13px;
  border:1px solid var(--border); border-radius:var(--r); background:var(--surface);
  color:var(--text); outline:none; transition:all var(--transition);
}
.vd-search-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(217,95,71,.1); }
.vd-search-input::placeholder { color:var(--text-4); }
.vd-filter-group { display:flex; gap:6px; align-items:center; }
.vd-select {
  height:36px; padding:0 30px 0 11px; font-family:inherit; font-size:12px; font-weight:500;
  border:1px solid var(--border); border-radius:var(--r); background:var(--surface);
  color:var(--text-2); outline:none; cursor:pointer; appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2712%27 height=%2712%27 viewBox=%270 0 12 12%27%3E%3Cpath d=%27M3 4.5l3 3 3-3%27 stroke=%27%23b09070%27 stroke-width=%271.5%27 fill=%27none%27 stroke-linecap=%27round%27/%3E%3C/svg%3E");
  background-repeat:no-repeat; background-position:right 9px center;
  transition:all var(--transition);
}
.vd-select:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(217,95,71,.1); }
.vd-toolbar-gap { flex:1; }
.vd-view-toggle { display:flex; gap:2px; background:var(--surface); border:1px solid var(--border); border-radius:var(--r); padding:2px; }
.vd-view-btn { width:30px; height:28px; display:flex; align-items:center; justify-content:center; border-radius:6px; cursor:pointer; border:none; background:transparent; color:var(--text-3); transition:all var(--transition); }
.vd-view-btn:hover { color:var(--text-2); }
.vd-view-btn.active { background:var(--surface-2); color:var(--text); }
.vd-view-btn svg { width:13px; height:13px; stroke:currentColor; fill:none; stroke-width:1.8; }
.vd-count-badge { font-size:11px; font-weight:600; color:var(--text-3); background:var(--surface-2); padding:3px 9px; border-radius:20px; border:1px solid var(--border); }

/* ══ Video Grid ════════════════════════════════════════════════════ */
.vd-body { padding:0 32px 48px; }
.vd-grid {
  display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:16px;
}
.vd-grid.list-view { grid-template-columns:1fr; gap:8px; }

/* ══ Video Card ════════════════════════════════════════════════════ */
.vd-card {
  background:var(--surface); border:1px solid var(--border); border-radius:var(--rl);
  overflow:hidden; display:flex; flex-direction:column; position:relative;
  transition:border-color var(--transition), box-shadow var(--transition), transform var(--transition);
  cursor:pointer;
}
.vd-card:hover { border-color:var(--border-2); box-shadow:var(--shadow-md); transform:translateY(-2px); }
.vd-card.is-hidden { opacity:.5; }
.vd-card.is-selected { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-light),var(--shadow-md); }

/* Thumb */
.vd-card-thumb {
  position:relative; aspect-ratio:16/9; background:linear-gradient(135deg,#2a1f1a,#3d2d24);
  overflow:hidden;
}
.vd-card-thumb img { width:100%; height:100%; object-fit:cover; display:block; transition:transform .5s ease; }
.vd-card:hover .vd-card-thumb img { transform:scale(1.05); }
.vd-thumb-skeleton { position:absolute; inset:0; background:linear-gradient(90deg,#2a1f1a 25%,#3d2d24 50%,#2a1f1a 75%); background-size:200% 100%; animation:shimmer 1.4s infinite; }
@keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

/* Play overlay */
.vd-play-overlay {
  position:absolute; inset:0;
  background:linear-gradient(to top, rgba(0,0,0,.55) 0%, rgba(0,0,0,.1) 60%);
  display:flex; align-items:center; justify-content:center;
  transition:background var(--transition); opacity:0;
}
.vd-card:hover .vd-play-overlay { opacity:1; }
.vd-play-circle {
  width:46px; height:46px; border-radius:50%;
  background:rgba(255,255,255,.92); backdrop-filter:blur(4px);
  display:flex; align-items:center; justify-content:center;
  transition:transform var(--transition); box-shadow:0 4px 16px rgba(0,0,0,.3);
}
.vd-card:hover .vd-play-circle { transform:scale(1.1); }
.vd-play-circle svg { width:14px; height:14px; fill:#3d2f26; margin-left:3px; }

/* Badges on thumb */
.vd-thumb-badges { position:absolute; top:8px; left:8px; right:8px; display:flex; justify-content:space-between; align-items:flex-start; gap:6px; }
.vd-type-badge {
  font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.08em;
  padding:3px 7px; border-radius:5px; backdrop-filter:blur(8px);
  display:flex; align-items:center; gap:4px;
}
.vd-type-badge svg { width:9px; height:9px; fill:currentColor; stroke:none; }
.vd-badge-yt { background:rgba(220,38,38,.85); color:#fff; }
.vd-badge-up { background:rgba(37,99,235,.8); color:#fff; }
.vd-status-dot { width:8px; height:8px; border-radius:50%; border:1.5px solid rgba(255,255,255,.7); }
.vd-status-dot.on { background:#4ade80; }
.vd-status-dot.off { background:#94a3b8; }

/* Drag handle */
.vd-drag-handle {
  position:absolute; top:8px; right:8px; width:24px; height:24px;
  background:rgba(255,255,255,.8); backdrop-filter:blur(4px);
  border-radius:5px; display:flex; align-items:center; justify-content:center;
  cursor:grab; opacity:0; transition:opacity var(--transition);
}
.vd-card:hover .vd-drag-handle { opacity:1; }
.vd-drag-handle:active { cursor:grabbing; }
.vd-drag-handle svg { width:11px; height:11px; stroke:var(--text-2); fill:none; stroke-width:2; }

/* Card body */
.vd-card-body { padding:13px 14px 10px; flex:1; display:flex; flex-direction:column; gap:3px; }
.vd-card-cat { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.12em; color:var(--accent); }
.vd-card-title { font-size:13px; font-weight:600; color:var(--text); line-height:1.35; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.vd-card-desc { font-size:11px; color:var(--text-3); line-height:1.5; display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden; margin-top:2px; }

/* Card footer */
.vd-card-foot {
  padding:8px 12px; border-top:1px solid var(--surface-2);
  display:flex; align-items:center; gap:6px;
}
.vd-card-actions { display:flex; gap:5px; }

/* ── List view card ── */
.vd-grid.list-view .vd-card { flex-direction:row; align-items:center; gap:0; }
.vd-grid.list-view .vd-card-thumb { width:120px; flex-shrink:0; aspect-ratio:16/9; }
.vd-grid.list-view .vd-card-body { flex-direction:row; align-items:center; gap:16px; padding:12px 16px; }
.vd-grid.list-view .vd-card-title { flex:1; -webkit-line-clamp:1; }
.vd-grid.list-view .vd-card-desc,
.vd-grid.list-view .vd-card-cat { display:none; }
.vd-grid.list-view .vd-card-foot { border-top:none; border-left:1px solid var(--surface-2); padding:8px 12px; flex-direction:column; gap:4px; }
.vd-grid.list-view .vd-drag-handle { opacity:.5; position:static; background:var(--surface-2); }
.vd-grid.list-view .vd-card:hover .vd-drag-handle { opacity:1; }
.vd-grid.list-view .vd-play-overlay { opacity:.4; }

/* ══ Empty state ════════════════════════════════════════════════════ */
.vd-empty {
  grid-column:1/-1; text-align:center; padding:80px 32px;
  display:flex; flex-direction:column; align-items:center; gap:12px;
}
.vd-empty-art {
  width:96px; height:96px; border-radius:50%;
  background:linear-gradient(135deg,var(--surface-2),var(--surface-3));
  border:2px dashed var(--border-2);
  display:flex; align-items:center; justify-content:center; margin-bottom:4px;
}
.vd-empty-art svg { width:36px; height:36px; stroke:var(--text-3); fill:none; stroke-width:1.2; opacity:.6; }
.vd-empty-title { font-size:18px; font-weight:700; color:var(--text); }
.vd-empty-sub { font-size:13px; color:var(--text-3); max-width:280px; line-height:1.6; }

/* ══ Toast ══════════════════════════════════════════════════════════ */
#vd-toast {
  position:fixed; bottom:28px; right:28px; z-index:99999;
  display:flex; align-items:center; gap:10px;
  background:#1c1917; color:#fff;
  padding:12px 18px; border-radius:12px; font-size:13px; font-weight:500;
  box-shadow:var(--shadow-lg); opacity:0; transform:translateY(10px) scale(.97);
  transition:all .3s cubic-bezier(.34,1.56,.64,1); pointer-events:none;
}
#vd-toast.show { opacity:1; transform:translateY(0) scale(1); }
#vd-toast-icon { width:16px; height:16px; flex-shrink:0; border-radius:50%; display:flex; align-items:center; justify-content:center; }
#vd-toast.ok #vd-toast-icon  { background:#22c55e; }
#vd-toast.err #vd-toast-icon { background:#ef4444; }
#vd-toast-icon svg { width:10px; height:10px; stroke:#fff; fill:none; stroke-width:2.5; stroke-linecap:round; }

/* ══ Drawer ══════════════════════════════════════════════════════════ */
.vd-backdrop {
  position:fixed; inset:0; background:rgba(28,25,23,.5); z-index:9990;
  opacity:0; pointer-events:none; transition:opacity .25s; backdrop-filter:blur(4px);
}
.vd-backdrop.open { opacity:1; pointer-events:all; }
.vd-drawer {
  position:fixed; top:0; right:0; bottom:0; width:680px; max-width:96vw;
  background:var(--bg); z-index:9991; display:flex; flex-direction:column;
  transform:translateX(100%); transition:transform .3s cubic-bezier(.4,0,.2,1);
  box-shadow:-20px 0 60px rgba(28,25,23,.2);
}
.vd-drawer.open { transform:none; }

/* Drawer header */
.vdw-head {
  padding:0 24px;
  height:64px; display:flex; align-items:center; gap:14px; flex-shrink:0;
  background:var(--surface); border-bottom:1px solid var(--border);
}
.vdw-head-icon {
  width:38px; height:38px; border-radius:10px; flex-shrink:0;
  background:linear-gradient(135deg,var(--accent),#e8845c);
  display:flex; align-items:center; justify-content:center;
}
.vdw-head-icon svg { width:16px; height:16px; fill:#fff; }
.vdw-head-info { flex:1; min-width:0; }
.vdw-title { font-size:15px; font-weight:700; color:var(--text); letter-spacing:-.2px; }
.vdw-sub { font-size:11px; color:var(--text-3); margin-top:1px; }
.vdw-close {
  width:34px; height:34px; border:1px solid var(--border); background:transparent;
  border-radius:8px; display:flex; align-items:center; justify-content:center;
  cursor:pointer; color:var(--text-3); transition:all var(--transition);
}
.vdw-close:hover { background:var(--surface-2); color:var(--text); border-color:var(--border-2); }
.vdw-close svg { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:2; stroke-linecap:round; }

/* Drawer body */
.vdw-body { flex:1; overflow-y:auto; padding:20px 24px; display:flex; flex-direction:column; gap:14px; }
.vdw-body::-webkit-scrollbar { width:4px; }
.vdw-body::-webkit-scrollbar-track { background:transparent; }
.vdw-body::-webkit-scrollbar-thumb { background:var(--border); border-radius:4px; }

/* Drawer footer */
.vdw-foot {
  padding:14px 24px; border-top:1px solid var(--border);
  background:var(--surface); display:flex; align-items:center; gap:10px; flex-shrink:0;
}
.vdw-foot-gap { flex:1; }

/* ══ Form sections ═══════════════════════════════════════════════════ */
.vdf-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--rl); overflow:hidden; }
.vdf-head {
  display:flex; align-items:center; gap:9px; padding:11px 16px;
  border-bottom:1px solid var(--border); background:var(--surface-2);
  font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:var(--text-2);
}
.vdf-head svg { width:12px; height:12px; stroke:currentColor; fill:none; stroke-width:1.8; stroke-linecap:round; }
.vdf-body { padding:16px; display:flex; flex-direction:column; gap:12px; }
.vdf-2col { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.vdf-3col { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; }

.vdf-field { display:flex; flex-direction:column; gap:5px; }
.vdf-label { font-size:11px; font-weight:600; color:var(--text-2); display:flex; align-items:center; gap:4px; }
.vdf-req { color:var(--accent); }
.vdf-hint { font-size:11px; color:var(--text-3); line-height:1.5; }
.vdf-input, .vdf-select, .vdf-textarea {
  width:100%; font-family:inherit; font-size:13px;
  border:1px solid var(--border); border-radius:var(--r);
  background:var(--surface); color:var(--text); outline:none;
  transition:all var(--transition);
}
.vdf-input, .vdf-select { height:38px; padding:0 12px; }
.vdf-textarea { padding:9px 12px; resize:vertical; min-height:76px; line-height:1.7; }
.vdf-input:focus, .vdf-select:focus, .vdf-textarea:focus {
  border-color:var(--accent); box-shadow:0 0 0 3px rgba(217,95,71,.1);
}
.vdf-input-group { display:flex; gap:8px; align-items:center; }
.vdf-input-group .vdf-input { flex:1; }

/* ══ Source input + badge ════════════════════════════════════════════ */
.vdf-src-badge {
  display:inline-flex; align-items:center; gap:7px;
  padding:6px 12px; border-radius:var(--r); font-size:12px; font-weight:600;
  border:1px solid; transition:all var(--transition);
}
.vdf-src-badge.none { background:var(--surface-2); color:var(--text-3); border-color:var(--border); }
.vdf-src-badge.yt   { background:var(--yt-bg); color:#dc2626; border-color:var(--yt-border); }
.vdf-src-badge.up   { background:var(--up-bg); color:var(--up-color); border-color:var(--up-border); }
.vdf-src-badge-dot { width:7px; height:7px; border-radius:50%; background:currentColor; opacity:.8; animation:pulse-dot 1.4s infinite; }
@keyframes pulse-dot { 0%,100%{opacity:.5;transform:scale(1)} 50%{opacity:1;transform:scale(1.3)} }
.vdf-src-badge.none .vdf-src-badge-dot { animation:none; opacity:.3; }

/* ══ Thumbnail preview ═══════════════════════════════════════════════ */
.vdf-thumb-zone {
  width:100%; aspect-ratio:16/9; border-radius:var(--rl); overflow:hidden;
  border:2px dashed var(--border); position:relative; cursor:pointer;
  background:linear-gradient(135deg,#2a1f1a,#3d2d24);
  display:flex; align-items:center; justify-content:center;
  transition:border-color var(--transition);
}
.vdf-thumb-zone:hover { border-color:var(--accent); }
.vdf-thumb-zone img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:none; transition:transform .4s; }
.vdf-thumb-zone:hover img { transform:scale(1.03); }
.vdf-thumb-zone .vdf-thumb-ph {
  display:flex; flex-direction:column; align-items:center; gap:6px;
  color:var(--text-4); text-align:center; pointer-events:none;
}
.vdf-thumb-zone .vdf-thumb-ph svg { width:24px; height:24px; stroke:currentColor; fill:none; stroke-width:1.3; opacity:.6; }
.vdf-thumb-zone .vdf-thumb-ph span { font-size:11px; font-weight:500; }
.vdf-thumb-overlay {
  position:absolute; inset:0; background:rgba(0,0,0,.45);
  display:none; align-items:center; justify-content:center;
  color:#fff; font-size:12px; font-weight:600; gap:6px;
  border-radius:calc(var(--rl) - 2px);
}
.vdf-thumb-zone:hover .vdf-thumb-overlay { display:flex; }
.vdf-thumb-overlay svg { width:14px; height:14px; stroke:#fff; fill:none; stroke-width:1.8; }
.vdf-thumb-hint { font-size:10px; color:var(--text-3); text-align:center; padding:4px 0 0; }

/* ══ Status toggle ═══════════════════════════════════════════════════ */
.vdf-toggle-group { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.vdf-toggle {
  padding:10px 14px; border-radius:var(--r); border:1.5px solid var(--border);
  cursor:pointer; display:flex; align-items:center; gap:9px;
  font-size:12px; font-weight:600; color:var(--text-2); background:var(--surface);
  transition:all var(--transition); user-select:none;
}
.vdf-toggle input { display:none; }
.vdf-toggle-icon { width:28px; height:28px; border-radius:7px; background:var(--surface-2); display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:all var(--transition); }
.vdf-toggle-icon svg { width:13px; height:13px; stroke:var(--text-3); fill:none; stroke-width:1.8; stroke-linecap:round; }
.vdf-toggle:hover { border-color:var(--border-2); background:var(--surface-2); }
.vdf-toggle.active-show { border-color:var(--green); background:var(--green-bg); color:var(--green); }
.vdf-toggle.active-show .vdf-toggle-icon { background:rgba(22,163,74,.12); }
.vdf-toggle.active-show .vdf-toggle-icon svg { stroke:var(--green); }
.vdf-toggle.active-hide { border-color:var(--border-2); background:var(--surface-2); color:var(--text-2); }

/* ══ Spinner ═════════════════════════════════════════════════════════ */
.vdf-spinner { width:16px; height:16px; border:2px solid var(--border); border-top-color:var(--accent); border-radius:50%; animation:spin .6s linear infinite; display:none; flex-shrink:0; }
@keyframes spin { to { transform:rotate(360deg); } }

/* ══ OR divider ══════════════════════════════════════════════════════ */
.vdf-or { display:flex; align-items:center; gap:10px; }
.vdf-or-line { flex:1; height:1px; background:var(--border); }
.vdf-or-text { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:var(--text-4); }

/* ══ 2-col drawer layout ════════════════════════════════════════════ */
.vdw-2col { display:flex; gap:14px; align-items:flex-start; }
.vdw-col-left  { width:220px; flex-shrink:0; display:flex; flex-direction:column; gap:10px; }
.vdw-col-right { flex:1; min-width:0; display:flex; flex-direction:column; gap:10px; }

/* Compact thumb zone */
.vdf-thumb-zone-sm {
  width:100%; aspect-ratio:16/9; border-radius:10px; overflow:hidden;
  border:2px dashed var(--border); position:relative; cursor:pointer;
  background:linear-gradient(135deg,#2a1f1a,#3d2d24);
  display:flex; align-items:center; justify-content:center;
  transition:border-color var(--transition);
}
.vdf-thumb-zone-sm:hover { border-color:var(--accent); }
.vdf-thumb-zone-sm img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:none; transition:transform .4s; }
.vdf-thumb-zone-sm:hover img { transform:scale(1.04); }
.vdf-thumb-zone-sm .vdf-thumb-ph { display:flex; flex-direction:column; align-items:center; gap:5px; color:var(--text-4); pointer-events:none; }
.vdf-thumb-zone-sm .vdf-thumb-ph svg { width:20px; height:20px; stroke:currentColor; fill:none; stroke-width:1.3; opacity:.6; }
.vdf-thumb-zone-sm .vdf-thumb-ph span { font-size:10px; font-weight:500; text-align:center; }
.vdf-thumb-zone-sm .vdf-thumb-overlay {
  position:absolute; inset:0; background:rgba(0,0,0,.5);
  display:none; align-items:center; justify-content:center;
  font-size:11px; font-weight:600; color:#fff; gap:5px; border-radius:8px;
}
.vdf-thumb-zone-sm:hover .vdf-thumb-overlay { display:flex; }
.vdf-thumb-zone-sm .vdf-thumb-overlay svg { width:12px; height:12px; stroke:#fff; fill:none; stroke-width:1.8; }

/* YT mini preview */
.vdf-yt-preview {
  width:100%; aspect-ratio:16/9; border-radius:10px; overflow:hidden;
  border:1.5px solid var(--green-border); display:none; position:relative; background:#000;
}
.vdf-yt-preview iframe { width:100%; height:100%; border:none; display:block; }
.vdf-yt-preview-badge {
  position:absolute; bottom:0; left:0; right:0;
  background:linear-gradient(to top,rgba(0,0,0,.8),transparent);
  padding:10px 10px 7px; font-size:10px; font-weight:600; color:#fff;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis; pointer-events:none;
}

/* Source chip (replaces old vdf-src-badge in drawer) */
.vdf-src-chip {
  display:flex; align-items:center; gap:7px;
  padding:6px 12px; border-radius:20px; font-size:11px; font-weight:600;
  border:1px solid; width:100%; transition:all var(--transition);
}
.vdf-src-chip.none { background:var(--surface-2); color:var(--text-4); border-color:var(--border); }
.vdf-src-chip.yt   { background:var(--yt-bg); color:#dc2626; border-color:var(--yt-border); }
.vdf-src-chip.up   { background:var(--up-bg); color:var(--up-color); border-color:var(--up-border); }
.vdf-src-chip-dot  { width:6px; height:6px; border-radius:50%; background:currentColor; flex-shrink:0; }
.vdf-src-chip.yt .vdf-src-chip-dot,.vdf-src-chip.up .vdf-src-chip-dot { animation:pulse-dot 1.5s infinite; }
.vdf-src-chip.none .vdf-src-chip-dot { animation:none; opacity:.3; }

/* ══ Category table ══════════════════════════════════════════════════ */
.vd-table-wrap { overflow:hidden; }
.vd-table { width:100%; border-collapse:collapse; }
.vd-table thead tr { background:var(--surface-2); }
.vd-table th { padding:10px 16px; text-align:left; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--text-3); border-bottom:1px solid var(--border); white-space:nowrap; }
.vd-table td { padding:13px 16px; border-bottom:1px solid var(--border); vertical-align:middle; font-size:13px; color:var(--text); }
.vd-table tbody tr { transition:background var(--transition); }
.vd-table tbody tr:hover td { background:var(--surface-2); }
.vd-table tbody tr:last-child td { border-bottom:none; }
.vd-row-drag { cursor:grab; color:var(--text-4); width:28px; text-align:center; }
.vd-row-drag:active { cursor:grabbing; }

/* ══ Badges ══════════════════════════════════════════════════════════ */
.vd-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; border:1px solid; }
.vd-badge-green { background:var(--green-bg); color:var(--green); border-color:var(--green-border); }
.vd-badge-gray  { background:var(--surface-2); color:var(--text-3); border-color:var(--border); }
.vd-badge-red   { background:var(--red-bg); color:var(--red); border-color:var(--red-border); }
.vd-badge-yt    { background:var(--yt-bg); color:var(--yt); border-color:var(--yt-border); }
.vd-badge-up    { background:var(--up-bg); color:var(--up-color); border-color:var(--up-border); }
.vd-badge-dot   { width:6px; height:6px; border-radius:50%; background:currentColor; }

/* ══ Section card wrapper ════════════════════════════════════════════ */
.vd-section { background:var(--surface); border:1px solid var(--border); border-radius:var(--rl); overflow:hidden; }
.vd-section-head { display:flex; align-items:center; justify-content:space-between; padding:13px 20px; border-bottom:1px solid var(--border); }
.vd-section-title { font-size:13px; font-weight:700; color:var(--text); display:flex; align-items:center; gap:8px; }
.vd-section-title svg { width:14px; height:14px; stroke:var(--text-3); fill:none; stroke-width:1.8; stroke-linecap:round; }

'; }

    /* ── AJAX ─────────────────────────────────────────────────────── */

    public function ajax_video_save(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( [ 'message' => 'Không có quyền.' ] );
        check_ajax_referer( self::NONCE, '_nonce' );
        global $wpdb;
        $t = $wpdb->prefix . self::TABLE_VIDEOS;

        $id          = intval( $_POST['id'] ?? 0 );
        $title       = sanitize_text_field( $_POST['title'] ?? '' );
        $description = sanitize_textarea_field( $_POST['description'] ?? '' );
        $type        = in_array( $_POST['type'] ?? '', ['upload','youtube'] ) ? $_POST['type'] : 'upload';
        $video_url   = esc_url_raw( $_POST['video_url'] ?? '' );
        $thumb_url   = esc_url_raw( $_POST['thumbnail_url'] ?? '' );
        $youtube_id  = sanitize_text_field( $_POST['youtube_id'] ?? '' );
        $duration    = sanitize_text_field( $_POST['duration'] ?? '' );
        $cat_id      = intval( $_POST['category_id'] ?? 0 );
        $is_active   = intval( $_POST['is_active'] ?? 1 );

        if ( empty( $title ) ) wp_send_json_error( [ 'message' => 'Tiêu đề không được để trống.' ] );
        if ( empty( $video_url ) ) wp_send_json_error( [ 'message' => 'Vui lòng nhập link hoặc chọn file video.' ] );

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
        foreach ( $ids as $pos => $vid ) { $wpdb->update( $t, [ 'order_index' => $pos ], [ 'id' => $vid ] ); }
        wp_send_json_success();
    }

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
        if ( $id === 0 ) {
            $base = $slug; $i = 1;
            while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE slug=%s", $slug ) ) ) { $slug = $base . '-' . $i++; }
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
        $wpdb->update( $wpdb->prefix . self::TABLE_VIDEOS, [ 'category_id' => 0 ], [ 'category_id' => $id ] );
        $wpdb->delete( $wpdb->prefix . self::TABLE_CATS, [ 'id' => $id ] );
        wp_send_json_success( [ 'message' => 'Đã xóa danh mục.' ] );
    }

    public function ajax_cat_order(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        check_ajax_referer( self::NONCE, '_nonce' );
        global $wpdb;
        $t   = $wpdb->prefix . self::TABLE_CATS;
        $ids = array_map( 'intval', (array)( $_POST['ids'] ?? [] ) );
        foreach ( $ids as $pos => $cid ) { $wpdb->update( $t, [ 'order_index' => $pos ], [ 'id' => $cid ] ); }
        wp_send_json_success();
    }

    public function ajax_yt_info(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        check_ajax_referer( self::NONCE, '_nonce' );
        $url = sanitize_text_field( $_POST['url'] ?? '' );
        preg_match( '/(?:v=|\/embed\/|youtu\.be\/|shorts\/)([A-Za-z0-9_-]{11})/', $url, $m );
        $yt_id = $m[1] ?? '';
        if ( ! $yt_id ) wp_send_json_error( [ 'message' => 'Không tìm thấy YouTube ID.' ] );
        $oembed = wp_remote_get( 'https://www.youtube.com/oembed?url=' . urlencode( "https://youtu.be/{$yt_id}" ) . '&format=json', [ 'timeout' => 6 ] );
        $thumb = "https://img.youtube.com/vi/{$yt_id}/hqdefault.jpg";
        $title = '';
        if ( ! is_wp_error( $oembed ) && wp_remote_retrieve_response_code( $oembed ) === 200 ) {
            $body  = json_decode( wp_remote_retrieve_body( $oembed ), true );
            $title = $body['title'] ?? '';
            $thumb = $body['thumbnail_url'] ?? $thumb;
        }
        wp_send_json_success( [ 'yt_id' => $yt_id, 'title' => $title, 'thumbnail' => $thumb ] );
    }

    /* ── Render: Videos Page ─────────────────────────────────────── */

    public function render_videos_page(): void {
        global $wpdb;
        $tv    = $wpdb->prefix . self::TABLE_VIDEOS;
        $tc    = $wpdb->prefix . self::TABLE_CATS;
        $nonce = wp_create_nonce( self::NONCE );
        $videos = $wpdb->get_results( "SELECT v.*, c.name as cat_name FROM {$tv} v LEFT JOIN {$tc} c ON v.category_id=c.id ORDER BY v.order_index ASC, v.id ASC", ARRAY_A ) ?: [];
        $cats   = $wpdb->get_results( "SELECT * FROM {$tc} ORDER BY order_index ASC, id ASC", ARRAY_A ) ?: [];
        $total  = count($videos);
        $active = count( array_filter( $videos, fn($v) => $v['is_active'] ) );
        $yt_cnt = count( array_filter( $videos, fn($v) => $v['type']==='youtube' ) );
        $up_cnt = $total - $yt_cnt;
        ?>
<div id="vd-toast"><div id="vd-toast-icon"><svg viewBox="0 0 10 10"><polyline points="1.5,5 4,7.5 8.5,2"/></svg></div><span id="vd-toast-msg"></span></div>

<div class="vd-app">

  <!-- Topbar -->
  <div class="vd-topbar">
    <div class="vd-topbar-left">
      <div class="vd-topbar-logo">
        <svg viewBox="0 0 20 20"><polygon points="5,3 17,10 5,17"/></svg>
      </div>
      <div>
        <div class="vd-topbar-title">Video Library</div>
        <div class="vd-topbar-sub">Bacera · <?php echo $total; ?> videos</div>
      </div>
      <nav class="vd-topbar-nav">
        <a href="<?php echo esc_url(admin_url('admin.php?page='.self::PAGE_VIDEOS)); ?>" class="vd-nav-link active">
          <svg viewBox="0 0 14 14"><polygon points="4,2 12,7 4,12"/><line x1="2" y1="2" x2="2" y2="12"/></svg>Videos
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page='.self::PAGE_CATS)); ?>" class="vd-nav-link">
          <svg viewBox="0 0 14 14"><rect x="1" y="1" width="12" height="5" rx="1"/><rect x="1" y="8" width="5" height="5" rx="1"/><rect x="8" y="8" width="5" height="5" rx="1"/></svg>Danh mục
        </a>
      </nav>
    </div>
    <button class="vd-btn vd-btn-primary" id="vd-add-btn">
      <svg viewBox="0 0 14 14"><path d="M7 2v10M2 7h10" stroke-linecap="round"/></svg>Thêm Video
    </button>
  </div>

  <!-- Stats -->
  <div class="vd-stats">
    <div class="vd-stat">
      <div class="vd-stat-accent" style="background:var(--accent)"></div>
      <div class="vd-stat-label">Tổng Video</div>
      <div class="vd-stat-value"><?php echo $total; ?></div>
      <div class="vd-stat-sub"><?php echo $active; ?> đang hiển thị</div>
      <div class="vd-stat-icon"><svg viewBox="0 0 48 48" fill="currentColor" style="color:var(--accent)"><polygon points="10,6 42,24 10,42"/></svg></div>
    </div>
    <div class="vd-stat">
      <div class="vd-stat-accent" style="background:#FF0000"></div>
      <div class="vd-stat-label">YouTube</div>
      <div class="vd-stat-value"><?php echo $yt_cnt; ?></div>
      <div class="vd-stat-sub">Videos nhúng YT</div>
    </div>
    <div class="vd-stat">
      <div class="vd-stat-accent" style="background:var(--up-color)"></div>
      <div class="vd-stat-label">Upload</div>
      <div class="vd-stat-value"><?php echo $up_cnt; ?></div>
      <div class="vd-stat-sub">Self-hosted videos</div>
    </div>
    <div class="vd-stat">
      <div class="vd-stat-accent" style="background:var(--green)"></div>
      <div class="vd-stat-label">Danh mục</div>
      <div class="vd-stat-value"><?php echo count($cats); ?></div>
      <div class="vd-stat-sub"><?php echo $total - $active; ?> đang ẩn</div>
    </div>
  </div>

  <!-- Toolbar -->
  <div class="vd-toolbar">
    <div class="vd-search-wrap">
      <svg viewBox="0 0 16 16"><circle cx="7" cy="7" r="4"/><path d="M10.5 10.5l3 3" stroke-linecap="round"/></svg>
      <input class="vd-search-input" type="text" id="vd-search" placeholder="Tìm tiêu đề video…" autocomplete="off">
    </div>
    <div class="vd-filter-group">
      <select class="vd-select" id="vd-cat-filter">
        <option value="">Tất cả danh mục</option>
        <?php foreach ($cats as $c): ?>
        <option value="<?php echo esc_attr($c['id']); ?>"><?php echo esc_html($c['name']); ?></option>
        <?php endforeach; ?>
        <option value="0">Chưa phân loại</option>
      </select>
      <select class="vd-select" id="vd-type-filter">
        <option value="">Tất cả loại</option>
        <option value="youtube">YouTube</option>
        <option value="upload">Upload</option>
      </select>
      <select class="vd-select" id="vd-status-filter">
        <option value="">Tất cả trạng thái</option>
        <option value="1">Hiển thị</option>
        <option value="0">Đang ẩn</option>
      </select>
    </div>
    <div class="vd-toolbar-gap"></div>
    <span class="vd-count-badge" id="vd-count-badge"><?php echo $total; ?> videos</span>
    <div class="vd-view-toggle">
      <button class="vd-view-btn active" id="vd-view-grid" title="Grid view">
        <svg viewBox="0 0 14 14"><rect x="1" y="1" width="5" height="5" rx="1"/><rect x="8" y="1" width="5" height="5" rx="1"/><rect x="1" y="8" width="5" height="5" rx="1"/><rect x="8" y="8" width="5" height="5" rx="1"/></svg>
      </button>
      <button class="vd-view-btn" id="vd-view-list" title="List view">
        <svg viewBox="0 0 14 14"><line x1="1" y1="3" x2="13" y2="3"/><line x1="1" y1="7" x2="13" y2="7"/><line x1="1" y1="11" x2="13" y2="11"/></svg>
      </button>
    </div>
  </div>

  <!-- Grid -->
  <div class="vd-body">
    <div class="vd-grid" id="vd-grid">
      <?php if ( empty($videos) ): ?>
      <div class="vd-empty">
        <div class="vd-empty-art">
          <svg viewBox="0 0 48 48"><rect x="4" y="8" width="40" height="32" rx="4"/><polygon points="19,18 33,24 19,30"/></svg>
        </div>
        <div class="vd-empty-title">Chưa có video nào</div>
        <div class="vd-empty-sub">Thêm video đầu tiên vào thư viện để bắt đầu.</div>
        <button class="vd-btn vd-btn-primary" id="vd-add-btn-2" style="margin-top:4px;">
          <svg viewBox="0 0 14 14"><path d="M7 2v10M2 7h10" stroke-linecap="round"/></svg>Thêm video đầu tiên
        </button>
      </div>
      <?php else: foreach ($videos as $idx => $v):
        $thumb = $v['thumbnail_url'] ?: ( $v['type']==='youtube' && $v['youtube_id'] ? "https://img.youtube.com/vi/{$v['youtube_id']}/hqdefault.jpg" : '' );
        $is_yt = $v['type'] === 'youtube';
      ?>
      <div class="vd-card <?php echo $v['is_active'] ? '' : 'is-hidden'; ?>"
           data-id="<?php echo esc_attr($v['id']); ?>"
           data-cat="<?php echo esc_attr($v['category_id']??0); ?>"
           data-type="<?php echo esc_attr($v['type']); ?>"
           data-status="<?php echo $v['is_active']?'1':'0'; ?>"
           data-search="<?php echo esc_attr(strtolower($v['title'])); ?>">

        <div class="vd-drag-handle">
          <svg viewBox="0 0 12 12"><circle cx="4" cy="3" r=".9"/><circle cx="8" cy="3" r=".9"/><circle cx="4" cy="6.5" r=".9"/><circle cx="8" cy="6.5" r=".9"/><circle cx="4" cy="10" r=".9"/><circle cx="8" cy="10" r=".9"/></svg>
        </div>

        <div class="vd-card-thumb">
          <?php if ($thumb): ?>
          <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($v['title']); ?>" loading="lazy">
          <?php else: ?>
          <div class="vd-thumb-skeleton" style="position:relative;">
            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;">
              <svg width="36" height="36" viewBox="0 0 24 24" style="opacity:.2;fill:#fff;"><polygon points="8,5 21,12 8,19"/></svg>
            </div>
          </div>
          <?php endif; ?>
          <div class="vd-play-overlay">
            <div class="vd-play-circle"><svg viewBox="0 0 14 14"><polygon points="3.5,1.5 12.5,7 3.5,12.5" fill="#3d2f26"/></svg></div>
          </div>
          <div class="vd-thumb-badges">
            <span class="vd-type-badge <?php echo $is_yt ? 'vd-badge-yt' : 'vd-badge-up'; ?>">
              <?php if ($is_yt): ?>
              <svg viewBox="0 0 16 16"><path d="M14.5 5s-.2-1.2-.7-1.7c-.7-.7-1.5-.7-1.8-.7C10 2.5 8 2.5 8 2.5s-2 0-4 .1c-.4 0-1.2 0-1.8.7C1.7 3.8 1.5 5 1.5 5S1.3 6.4 1.3 7.8v1.3c0 1.4.2 2.7.2 2.7s.2 1.2.7 1.7c.7.7 1.6.7 2 .8C5.5 14 8 14 8 14s2 0 4-.2c.4 0 1.2-.1 1.8-.7.5-.5.7-1.7.7-1.7s.2-1.4.2-2.7V7.8C14.7 6.4 14.5 5 14.5 5zM6.5 10V6l4 2-4 2z"/></svg>
              YT<?php else: ?>↑ File<?php endif; ?>
            </span>
            <div class="vd-status-dot <?php echo $v['is_active'] ? 'on' : 'off'; ?>" title="<?php echo $v['is_active'] ? 'Hiển thị' : 'Đang ẩn'; ?>"></div>
          </div>
        </div>

        <div class="vd-card-body">
          <?php if ($v['cat_name']): ?><div class="vd-card-cat"><?php echo esc_html($v['cat_name']); ?></div><?php endif; ?>
          <div class="vd-card-title"><?php echo esc_html($v['title']); ?></div>
          <?php if ($v['description']): ?><div class="vd-card-desc"><?php echo esc_html($v['description']); ?></div><?php endif; ?>
        </div>

        <div class="vd-card-foot">
          <button class="vd-btn vd-btn-secondary vd-btn-xs vd-edit-btn" data-id="<?php echo esc_attr($v['id']); ?>">
            <svg viewBox="0 0 14 14"><path d="M9.5 2.5l2 2-7 7H2.5V9l7-6.5z" stroke-linecap="round"/></svg>Sửa
          </button>
          <button class="vd-btn vd-btn-danger vd-btn-xs vd-del-btn" data-id="<?php echo esc_attr($v['id']); ?>">
            <svg viewBox="0 0 14 14"><polyline points="1,3 13,3"/><path d="M5,3V1h4v2"/><path d="M2,3l1,9h8l1-9"/></svg>Xóa
          </button>
          <?php if ($v['is_active']): ?>
          <span class="vd-badge vd-badge-green" style="margin-left:auto;font-size:10px;padding:2px 7px;"><span class="vd-badge-dot"></span>Active</span>
          <?php else: ?>
          <span class="vd-badge vd-badge-gray" style="margin-left:auto;font-size:10px;padding:2px 7px;"><span class="vd-badge-dot"></span>Hidden</span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

</div><!-- /vd-app -->

<!-- ══ DRAWER ══ -->
<div id="vd-backdrop" class="vd-backdrop"></div>
<div id="vd-drawer" class="vd-drawer" role="dialog" aria-modal="true" aria-label="Video form">

  <div class="vdw-head">
    <div class="vdw-head-icon"><svg viewBox="0 0 20 20"><polygon points="5,3 17,10 5,17"/></svg></div>
    <div class="vdw-head-info">
      <div class="vdw-title" id="vdw-title">Thêm Video mới</div>
      <div class="vdw-sub" id="vdw-sub">Nhập link YouTube hoặc chọn file từ thư viện</div>
    </div>
    <button class="vdw-close" id="vdw-close"><svg viewBox="0 0 16 16"><path d="M2 2l12 12M14 2L2 14" stroke-linecap="round"/></svg></button>
  </div>

  <div class="vdw-body">

    <div class="vdw-2col">

      <!-- LEFT: Source + Thumbnail -->
      <div class="vdw-col-left">

        <!-- Source chip -->
        <div id="vd-src-badge" class="vdf-src-chip none">
          <span class="vdf-src-chip-dot"></span>
          <span id="vd-src-badge-text" style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">Chưa có nguồn</span>
          <div class="vdf-spinner" id="vd-src-spinner" style="margin-left:auto;"></div>
        </div>

        <!-- Thumbnail zone -->
        <div class="vdf-thumb-zone-sm" id="vd-thumb-zone">
          <img id="vd-thumb-img" alt="">
          <div class="vdf-thumb-ph">
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            <span>Thumbnail</span>
          </div>
          <div class="vdf-thumb-overlay">
            <svg viewBox="0 0 14 14"><path d="M9.5 2.5l2 2-7 7H2.5V9l7-6.5z" stroke-linecap="round"/></svg>Thay ảnh
          </div>
        </div>
        <div class="vdf-thumb-hint" id="vd-thumb-hint" style="font-size:10px;color:var(--text-4);text-align:center;display:none;">Tự lấy từ YouTube — nhấp để đổi</div>
        <input type="hidden" id="vd-thumb-url">

        <!-- YouTube mini preview -->
        <div class="vdf-yt-preview" id="vd-yt-preview">
          <div id="vd-yt-preview-inner"></div>
          <div class="vdf-yt-preview-badge" id="vd-yt-preview-title"></div>
        </div>

        <!-- Danh mục + Thời lượng -->
        <div class="vdf-card">
          <div class="vdf-head">
            <svg viewBox="0 0 12 12"><rect x="1" y="1" width="10" height="5" rx="1"/><rect x="1" y="8" width="4" height="4" rx="1"/><rect x="7" y="8" width="4" height="4" rx="1"/></svg>Phân loại
          </div>
          <div class="vdf-body">
            <div class="vdf-field">
              <label class="vdf-label">Danh mục</label>
              <select class="vdf-select" id="vd-cat">
                <option value="0">— Chưa phân loại —</option>
                <?php foreach ($cats as $c): ?>
                <option value="<?php echo esc_attr($c['id']); ?>"><?php echo esc_html($c['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="vdf-field">
              <label class="vdf-label">Thời lượng</label>
              <input class="vdf-input" type="text" id="vd-duration" placeholder="12:34">
            </div>
          </div>
        </div>

        <!-- Visibility -->
        <div class="vdf-card">
          <div class="vdf-head">
            <svg viewBox="0 0 12 12"><circle cx="6" cy="6" r="5"/><path d="M4 6l1.5 1.5L8 4"/></svg>Trạng thái
          </div>
          <div class="vdf-body">
            <div style="display:flex;flex-direction:column;gap:6px;">
              <label class="vdf-toggle active-show" id="vtog-show" style="padding:8px 10px;">
                <input type="radio" name="vd_status" value="1" checked>
                <div class="vdf-toggle-icon" style="width:24px;height:24px;border-radius:6px;"><svg viewBox="0 0 14 14"><circle cx="7" cy="7" r="5"/><path d="M4.5 7l2 2 3-3" stroke-linecap="round"/></svg></div>
                Hiển thị
              </label>
              <label class="vdf-toggle" id="vtog-hide" style="padding:8px 10px;">
                <input type="radio" name="vd_status" value="0">
                <div class="vdf-toggle-icon" style="width:24px;height:24px;border-radius:6px;"><svg viewBox="0 0 14 14"><path d="M2 7s2-4 5-4 5 4 5 4-2 4-5 4-5-4-5-4z"/><path d="M12 2L2 12" stroke-linecap="round"/></svg></div>
                Ẩn
              </label>
            </div>
          </div>
        </div>

      </div><!-- /col-left -->

      <!-- RIGHT: Info + Source URL -->
      <div class="vdw-col-right">

        <!-- URL input -->
        <div class="vdf-card">
          <div class="vdf-head">
            <svg viewBox="0 0 12 12"><polygon points="4,2 10,6 4,10"/></svg>Nguồn Video <span class="vdf-req" style="font-size:10px;">*</span>
          </div>
          <div class="vdf-body">
            <div class="vdf-field">
              <label class="vdf-label">Link YouTube hoặc URL file video</label>
              <div class="vdf-input-group">
                <input class="vdf-input" type="text" id="vd-src-url"
                       placeholder="Dán link YouTube hoặc URL .mp4, .webm…" autocomplete="off">
              </div>
              <div class="vdf-hint" style="margin-top:4px;">youtube.com/watch?v=… · youtu.be/… · shorts/… · URL file .mp4</div>
            </div>
            <div class="vdf-or"><div class="vdf-or-line"></div><div class="vdf-or-text">Hoặc chọn file</div><div class="vdf-or-line"></div></div>
            <button type="button" class="vd-btn vd-btn-secondary" id="vd-pick-video"
                    style="width:100%;justify-content:center;">
              <svg viewBox="0 0 14 14"><rect x="1" y="2" width="12" height="10" rx="1.2"/><path d="M1 6h12M5 2v4M9 2v4"/></svg>
              Thư viện WordPress
            </button>
            <input type="hidden" id="vd-yt-id">
            <input type="hidden" id="vd-detected-type" value="">
          </div>
        </div>

        <!-- Title + Desc -->
        <div class="vdf-card">
          <div class="vdf-head">
            <svg viewBox="0 0 12 12"><path d="M2 3h8M2 6h8M2 9h5"/></svg>Thông tin Video
          </div>
          <div class="vdf-body">
            <div class="vdf-field">
              <label class="vdf-label">Tiêu đề <span class="vdf-req">†</span></label>
              <input class="vdf-input" type="text" id="vd-title" placeholder="Tiêu đề video…">
            </div>
            <div class="vdf-field">
              <label class="vdf-label">Mô tả ngắn</label>
              <textarea class="vdf-textarea" id="vd-desc"
                        placeholder="Mô tả ngắn về nội dung video…"
                        style="min-height:90px;"></textarea>
            </div>
          </div>
        </div>

      </div><!-- /col-right -->
    </div><!-- /2col -->

    <input type="hidden" id="vdw-id" value="0">
  </div><!-- /body -->

  <div class="vdw-foot">
    <button class="vd-btn vd-btn-danger vd-btn-sm" id="vdw-del-btn" style="display:none;">
      <svg viewBox="0 0 14 14"><polyline points="1,3 13,3"/><path d="M5,3V1h4v2"/><path d="M2,3l1,9h8l1-9"/></svg>Xóa video
    </button>
    <span class="vdw-foot-gap"></span>
    <button class="vd-btn vd-btn-ghost" id="vdw-cancel">Hủy</button>
    <button class="vd-btn vd-btn-primary" id="vdw-save">
      <svg viewBox="0 0 14 14"><path d="M2 8l4 4L12 3" stroke-linecap="round"/></svg>Lưu Video
    </button>
  </div>
</div><!-- /drawer -->

<?php $this->render_videos_script( $nonce, $cats ); ?>
<?php
    }

    /* ── Render: Categories Page ─────────────────────────────────── */

    public function render_cats_page(): void {
        global $wpdb;
        $tv    = $wpdb->prefix . self::TABLE_VIDEOS;
        $tc    = $wpdb->prefix . self::TABLE_CATS;
        $nonce = wp_create_nonce( self::NONCE );
        $cats  = $wpdb->get_results( "SELECT c.*,(SELECT COUNT(*) FROM {$tv} WHERE category_id=c.id) as video_count FROM {$tc} c ORDER BY c.order_index ASC, c.id ASC", ARRAY_A ) ?: [];
        ?>
<div id="vd-toast"><div id="vd-toast-icon"><svg viewBox="0 0 10 10"><polyline points="1.5,5 4,7.5 8.5,2"/></svg></div><span id="vd-toast-msg"></span></div>

<div class="vd-app">

  <!-- Topbar -->
  <div class="vd-topbar">
    <div class="vd-topbar-left">
      <div class="vd-topbar-logo">
        <svg viewBox="0 0 20 20"><rect x="2" y="2" width="16" height="6" rx="2"/><rect x="2" y="11" width="7" height="7" rx="2"/><rect x="11" y="11" width="7" height="7" rx="2"/></svg>
      </div>
      <div>
        <div class="vd-topbar-title">Danh mục Video</div>
        <div class="vd-topbar-sub">Bacera · <?php echo count($cats); ?> danh mục</div>
      </div>
      <nav class="vd-topbar-nav">
        <a href="<?php echo esc_url(admin_url('admin.php?page='.self::PAGE_VIDEOS)); ?>" class="vd-nav-link">
          <svg viewBox="0 0 14 14"><polygon points="4,2 12,7 4,12"/><line x1="2" y1="2" x2="2" y2="12"/></svg>Videos
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page='.self::PAGE_CATS)); ?>" class="vd-nav-link active">
          <svg viewBox="0 0 14 14"><rect x="1" y="1" width="12" height="5" rx="1"/><rect x="1" y="8" width="5" height="5" rx="1"/><rect x="8" y="8" width="5" height="5" rx="1"/></svg>Danh mục
        </a>
      </nav>
    </div>
    <button class="vd-btn vd-btn-primary" id="vc-add-btn">
      <svg viewBox="0 0 14 14"><path d="M7 2v10M2 7h10" stroke-linecap="round"/></svg>Thêm danh mục
    </button>
  </div>

  <!-- Content -->
  <div style="padding:28px 32px 48px;">
    <div class="vd-section">
      <div class="vd-section-head">
        <span class="vd-section-title">
          <svg viewBox="0 0 14 14"><rect x="1" y="1" width="12" height="5" rx="1"/><rect x="1" y="8" width="5" height="5" rx="1"/><rect x="8" y="8" width="5" height="5" rx="1"/></svg>
          Tất cả danh mục
        </span>
        <span style="font-size:11px;color:var(--text-3);">Kéo hàng để sắp xếp thứ tự hiển thị trên website</span>
      </div>
      <?php if (empty($cats)): ?>
      <div class="vd-empty">
        <div class="vd-empty-art"><svg viewBox="0 0 48 48"><rect x="4" y="4" width="40" height="16" rx="4"/><rect x="4" y="28" width="18" height="18" rx="4"/><rect x="26" y="28" width="18" height="18" rx="4"/></svg></div>
        <div class="vd-empty-title">Chưa có danh mục nào</div>
        <div class="vd-empty-sub">Tạo danh mục để phân loại video theo chủ đề.</div>
      </div>
      <?php else: ?>
      <div class="vd-table-wrap">
        <table class="vd-table" id="vc-table">
          <thead>
            <tr>
              <th style="width:36px;"></th>
              <th>Tên danh mục</th>
              <th>Slug</th>
              <th>Số video</th>
              <th>Trạng thái</th>
              <th style="width:120px;"></th>
            </tr>
          </thead>
          <tbody id="vc-tbody">
            <?php foreach ($cats as $cat): ?>
            <tr data-id="<?php echo esc_attr($cat['id']); ?>">
              <td class="vd-row-drag" title="Kéo để sắp xếp">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="4" cy="2.5" r=".8"/><circle cx="8" cy="2.5" r=".8"/><circle cx="4" cy="6" r=".8"/><circle cx="8" cy="6" r=".8"/><circle cx="4" cy="9.5" r=".8"/><circle cx="8" cy="9.5" r=".8"/></svg>
              </td>
              <td>
                <strong style="font-size:13px;"><?php echo esc_html($cat['name']); ?></strong>
                <?php if ($cat['description']): ?><div style="font-size:11px;color:var(--text-3);margin-top:2px;"><?php echo esc_html($cat['description']); ?></div><?php endif; ?>
              </td>
              <td><code style="font-size:11px;background:var(--surface-2);padding:2px 7px;border-radius:5px;border:1px solid var(--border);"><?php echo esc_html($cat['slug']); ?></code></td>
              <td>
                <span class="vd-badge <?php echo $cat['video_count']>0?'vd-badge-up':'vd-badge-gray'; ?>">
                  <?php echo (int)$cat['video_count']; ?> video
                </span>
              </td>
              <td>
                <?php if ($cat['is_active']): ?>
                <span class="vd-badge vd-badge-green"><span class="vd-badge-dot"></span>Hiển thị</span>
                <?php else: ?>
                <span class="vd-badge vd-badge-gray"><span class="vd-badge-dot"></span>Ẩn</span>
                <?php endif; ?>
              </td>
              <td style="display:flex;gap:6px;align-items:center;">
                <button class="vd-btn vd-btn-secondary vd-btn-xs vc-edit-btn"
                        data-id="<?php echo esc_attr($cat['id']); ?>"
                        data-name="<?php echo esc_attr($cat['name']); ?>"
                        data-desc="<?php echo esc_attr($cat['description']); ?>"
                        data-active="<?php echo $cat['is_active']; ?>">
                  <svg viewBox="0 0 14 14"><path d="M9.5 2.5l2 2-7 7H2.5V9l7-6.5z" stroke-linecap="round"/></svg>Sửa
                </button>
                <button class="vd-btn vd-btn-danger vd-btn-xs vc-del-btn"
                        data-id="<?php echo esc_attr($cat['id']); ?>"
                        data-count="<?php echo esc_attr($cat['video_count']); ?>">
                  <svg viewBox="0 0 14 14"><polyline points="1,3 13,3"/><path d="M5,3V1h4v2"/><path d="M2,3l1,9h8l1-9"/></svg>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div><!-- /vd-app -->

<!-- Category Drawer -->
<div id="vcbd" class="vd-backdrop"></div>
<div id="vcdr" class="vd-drawer" style="width:440px;" role="dialog">
  <div class="vdw-head">
    <div class="vdw-head-icon" style="background:linear-gradient(135deg,#7c3aed,#a78bfa);">
      <svg viewBox="0 0 20 20" style="fill:#fff;"><rect x="2" y="2" width="16" height="6" rx="2"/><rect x="2" y="11" width="7" height="7" rx="2"/><rect x="11" y="11" width="7" height="7" rx="2"/></svg>
    </div>
    <div class="vdw-head-info">
      <div class="vdw-title" id="vcdr-title">Thêm danh mục</div>
      <div class="vdw-sub">Điền thông tin danh mục bên dưới</div>
    </div>
    <button class="vdw-close" id="vcdr-close"><svg viewBox="0 0 16 16"><path d="M2 2l12 12M14 2L2 14" stroke-linecap="round"/></svg></button>
  </div>
  <div class="vdw-body">
    <div class="vdf-card">
      <div class="vdf-body">
        <div class="vdf-field">
          <label class="vdf-label">Tên danh mục <span class="vdf-req">*</span></label>
          <input class="vdf-input" type="text" id="vc-name" placeholder="vd: Pottery Wheel Throwing">
        </div>
        <div class="vdf-field">
          <label class="vdf-label">Mô tả ngắn</label>
          <textarea class="vdf-textarea" id="vc-desc" placeholder="Mô tả về danh mục này…" style="min-height:64px;"></textarea>
        </div>
        <div class="vdf-field">
          <label class="vdf-label">Trạng thái</label>
          <div class="vdf-toggle-group">
            <label class="vdf-toggle active-show" id="vcpill-show">
              <input type="radio" name="vc_status" value="1" checked>
              <div class="vdf-toggle-icon"><svg viewBox="0 0 14 14"><circle cx="7" cy="7" r="5"/><path d="M4.5 7l2 2 3-3" stroke-linecap="round"/></svg></div>
              Hiển thị
            </label>
            <label class="vdf-toggle" id="vcpill-hide">
              <input type="radio" name="vc_status" value="0">
              <div class="vdf-toggle-icon"><svg viewBox="0 0 14 14"><path d="M2 7s2-4 5-4 5 4 5 4-2 4-5 4-5-4-5-4z"/><path d="M12 2L2 12" stroke-linecap="round"/></svg></div>
              Ẩn
            </label>
          </div>
        </div>
        <input type="hidden" id="vc-id" value="0">
      </div>
    </div>
  </div>
  <div class="vdw-foot">
    <span class="vdw-foot-gap"></span>
    <button class="vd-btn vd-btn-ghost" id="vcdr-cancel">Hủy</button>
    <button class="vd-btn vd-btn-primary" id="vcdr-save">
      <svg viewBox="0 0 14 14"><path d="M2 8l4 4L12 3" stroke-linecap="round"/></svg>Lưu danh mục
    </button>
  </div>
</div>

<?php $this->render_cats_script( $nonce ); ?>
<?php
    }

    /* ── JS: Videos page ─────────────────────────────────────────── */

    private function render_videos_script( string $nonce, array $cats ): void {
        global $wpdb;
        $vd_map = [];
        foreach ( $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . self::TABLE_VIDEOS, ARRAY_A ) ?: [] as $v ) {
            $vd_map[ $v['id'] ] = $v;
        }
        ?>
<script>
window._vd_data = <?php echo wp_json_encode( $vd_map ); ?>;
(function(){
'use strict';
var AJAX  = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
var NONCE = '<?php echo esc_js($nonce); ?>';

/* ─ Toast ─ */
function toast(msg,ok){
  var t=document.getElementById('vd-toast'),m=document.getElementById('vd-toast-msg');
  var icon=document.getElementById('vd-toast-icon');
  t.className='show '+(ok===false?'err':'ok');
  icon.innerHTML=ok===false?'<svg viewBox="0 0 10 10"><path d="M2 2l6 6M8 2l-6 6" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>':'<svg viewBox="0 0 10 10"><polyline points="1.5,5 4,7.5 8.5,2" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round"/></svg>';
  m.textContent=msg; clearTimeout(t._t); t._t=setTimeout(function(){t.className='';},3200);
}

/* ─ Drawer ─ */
var bd=document.getElementById('vd-backdrop'), dr=document.getElementById('vd-drawer');
function openDr(){  bd.classList.add('open'); dr.classList.add('open'); document.body.style.overflow='hidden'; }
function closeDr(){ bd.classList.remove('open'); dr.classList.remove('open'); document.body.style.overflow=''; }
bd.addEventListener('click',closeDr);
document.getElementById('vdw-close').addEventListener('click',closeDr);
document.getElementById('vdw-cancel').addEventListener('click',closeDr);
document.addEventListener('keydown',function(e){ if(e.key==='Escape') closeDr(); });

/* ─ Status toggle ─ */
var radSt=document.querySelectorAll('[name="vd_status"]');
function updateStatus(val){
  document.getElementById('vtog-show').className='vdf-toggle '+(val==='1'?'active-show':'');
  document.getElementById('vtog-hide').className='vdf-toggle '+(val==='0'?'active-hide':'');
}
radSt.forEach(function(r){ r.addEventListener('change',function(){ updateStatus(r.value); }); });

/* ─ Helpers ─ */
function extractYtId(url){
  var m=url.match(/(?:v=|\/embed\/|youtu\.be\/|shorts\/|\/v\/)([A-Za-z0-9_-]{11})/);
  return m?m[1]:'';
}

/* ─ Source chip ─ */
function setSrcBadge(type,label){
  var el=document.getElementById('vd-src-badge');
  el.className='vdf-src-chip '+type;
  document.getElementById('vd-src-badge-text').textContent=label;
}

/* ─ YouTube mini preview ─ */
function showYtPreview(ytId,title){
  var box=document.getElementById('vd-yt-preview');
  var inner=document.getElementById('vd-yt-preview-inner');
  var badge=document.getElementById('vd-yt-preview-title');
  box.style.display='block';
  inner.innerHTML='<iframe src="https://www.youtube.com/embed/'+ytId+'?rel=0&modestbranding=1" allowfullscreen loading="lazy" style="width:100%;height:100%;border:none;"></iframe>';
  badge.textContent=title||'';
}
function hideYtPreview(){
  var box=document.getElementById('vd-yt-preview');
  var inner=document.getElementById('vd-yt-preview-inner');
  box.style.display='none'; inner.innerHTML='';
}

/* ─ Thumbnail ─ */
function setThumb(url,auto){
  var img=document.getElementById('vd-thumb-img');
  var ph=document.querySelector('.vdf-thumb-zone-sm .vdf-thumb-ph');
  var hint=document.getElementById('vd-thumb-hint');
  document.getElementById('vd-thumb-url').value=url||'';
  if(url){ img.src=url; img.style.display='block'; if(ph) ph.style.display='none'; hint.style.display=auto?'block':'none'; }
  else   { img.style.display='none'; if(ph) ph.style.display='flex'; hint.style.display='none'; }
}

/* ─ Thumb zone click ─ */
document.getElementById('vd-thumb-zone').addEventListener('click',function(){
  var frame=wp.media({title:'Chọn ảnh thumbnail',button:{text:'Dùng ảnh này'},library:{type:'image'},multiple:false});
  frame.on('select',function(){
    var att=frame.state().get('selection').first().toJSON();
    setThumb(att.url,false);
  });
  frame.open();
});

/* ─ WP Media picker: video ─ */
document.getElementById('vd-pick-video').addEventListener('click',function(){
  var frame=wp.media({title:'Chọn file video',button:{text:'Chọn video này'},library:{type:'video'},multiple:false});
  frame.on('select',function(){
    var att=frame.state().get('selection').first().toJSON();
    document.getElementById('vd-src-url').value=att.url;
    document.getElementById('vd-yt-id').value='';
    document.getElementById('vd-detected-type').value='upload';
    setSrcBadge('up','Upload — '+(att.filename||att.url.split('/').pop()));
    if(att.image&&att.image.src) setThumb(att.image.src,false);
  });
  frame.open();
});

/* ─ Smart URL detect ─ */
var srcInp=document.getElementById('vd-src-url');
var srcSpin=document.getElementById('vd-src-spinner');
var _ytT=null;
srcInp.addEventListener('input',function(){
  var url=this.value.trim(); clearTimeout(_ytT);
  if(!url){ setSrcBadge('none','Chưa có nguồn video'); document.getElementById('vd-yt-id').value=''; document.getElementById('vd-detected-type').value=''; return; }
  var ytId=extractYtId(url);
  if(ytId){
    document.getElementById('vd-yt-id').value=ytId;
    document.getElementById('vd-detected-type').value='youtube';
    setSrcBadge('yt','YouTube — đang nhận diện…');
    _ytT=setTimeout(function(){ fetchYtInfo(url,ytId); },650);
  } else {
    document.getElementById('vd-yt-id').value='';
    document.getElementById('vd-detected-type').value='upload';
    setSrcBadge('up','Upload — '+url.split('/').pop().split('?')[0]);
  }
});

function fetchYtInfo(url,ytId){
  srcSpin.style.display='block';
  var fd=new FormData(); fd.append('action','bacera_yt_info'); fd.append('_nonce',NONCE); fd.append('url',url);
  fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){
    srcSpin.style.display='none';
    if(!res.success){ setSrcBadge('yt','ID: '+ytId); showYtPreview(ytId,''); return; }
    var title=res.data.title||'ID: '+res.data.yt_id;
    setSrcBadge('yt','YouTube — '+title);
    if(!document.getElementById('vd-title').value&&res.data.title) document.getElementById('vd-title').value=res.data.title;
    if(res.data.thumbnail&&!document.getElementById('vd-thumb-url').value) setThumb(res.data.thumbnail,true);
    showYtPreview(ytId, title);
  }).catch(function(){ srcSpin.style.display='none'; setSrcBadge('yt','ID: '+ytId); showYtPreview(ytId,''); });
}

/* ─ Reset ─ */
function resetDr(){
  document.getElementById('vd-src-url').value='';
  document.getElementById('vd-yt-id').value='';
  document.getElementById('vd-detected-type').value='';
  document.getElementById('vd-title').value='';
  document.getElementById('vd-desc').value='';
  document.getElementById('vd-cat').value='0';
  document.getElementById('vd-duration').value='';
  setSrcBadge('none','Chưa có nguồn');
  setThumb('',false);
  hideYtPreview();
}

/* ─ Open ADD ─ */
function openAdd(){
  document.getElementById('vdw-title').textContent='Thêm Video mới';
  document.getElementById('vdw-sub').textContent='Nhập link YouTube hoặc chọn file từ thư viện';
  document.getElementById('vdw-id').value='0';
  document.getElementById('vdw-del-btn').style.display='none';
  resetDr();
  document.querySelector('[name="vd_status"][value="1"]').checked=true; updateStatus('1');
  openDr();
}
var ab=document.getElementById('vd-add-btn'); if(ab) ab.addEventListener('click',openAdd);
var ab2=document.getElementById('vd-add-btn-2'); if(ab2) ab2.addEventListener('click',openAdd);

/* ─ Open EDIT ─ */
document.querySelectorAll('.vd-edit-btn').forEach(function(btn){
  btn.addEventListener('click',function(e){
    e.stopPropagation();
    var id=btn.getAttribute('data-id');
    var data=window._vd_data&&window._vd_data[id];
    document.getElementById('vdw-title').textContent='Chỉnh sửa Video';
    document.getElementById('vdw-sub').textContent='ID #'+id;
    document.getElementById('vdw-id').value=id;
    document.getElementById('vdw-del-btn').style.display='';
    resetDr();
    if(data){
      var type=data.type==='youtube'?'youtube':'upload';
      document.getElementById('vd-detected-type').value=type;
      document.getElementById('vd-src-url').value=data.video_url||(type==='youtube'&&data.youtube_id?'https://youtu.be/'+data.youtube_id:'');
      document.getElementById('vd-yt-id').value=data.youtube_id||'';
      if(type==='youtube'){
        setSrcBadge('yt','YouTube — '+(data.title||'ID: '+data.youtube_id));
        if(data.youtube_id) showYtPreview(data.youtube_id, data.title||'');
      } else {
        setSrcBadge('up','Upload — '+(data.video_url||'').split('/').pop());
        hideYtPreview();
      }
      document.getElementById('vd-title').value=data.title||'';
      document.getElementById('vd-desc').value=data.description||'';
      document.getElementById('vd-cat').value=data.category_id||'0';
      document.getElementById('vd-duration').value=data.duration||'';
      var thumb=data.thumbnail_url||(data.youtube_id?'https://img.youtube.com/vi/'+data.youtube_id+'/hqdefault.jpg':'');
      setThumb(thumb,type==='youtube'&&!data.thumbnail_url);
      var sv=(data.is_active==='1'||data.is_active===1)?'1':'0';
      document.querySelector('[name="vd_status"][value="'+sv+'"]').checked=true; updateStatus(sv);
    }
    openDr();
  });
});

/* ─ Save ─ */
document.getElementById('vdw-save').addEventListener('click',function(){
  var type=document.getElementById('vd-detected-type').value||'upload';
  var status=document.querySelector('[name="vd_status"]:checked').value;
  var srcUrl=document.getElementById('vd-src-url').value.trim();
  var ytId=document.getElementById('vd-yt-id').value;
  var thumb=document.getElementById('vd-thumb-url').value;
  if(!srcUrl){ toast('Vui lòng nhập link hoặc chọn file video.',false); return; }
  if(!document.getElementById('vd-title').value.trim()){ toast('Tiêu đề không được để trống.',false); return; }
  var fd=new FormData();
  fd.append('action','bacera_video_save'); fd.append('_nonce',NONCE);
  fd.append('id',document.getElementById('vdw-id').value);
  fd.append('title',document.getElementById('vd-title').value.trim());
  fd.append('description',document.getElementById('vd-desc').value.trim());
  fd.append('category_id',document.getElementById('vd-cat').value);
  fd.append('duration',document.getElementById('vd-duration').value.trim());
  fd.append('type',type); fd.append('video_url',srcUrl);
  fd.append('thumbnail_url',thumb); fd.append('youtube_id',ytId); fd.append('is_active',status);
  var btn=this; btn.disabled=true; btn.textContent='Đang lưu…';
  fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){
    btn.disabled=false;
    btn.innerHTML='<svg viewBox="0 0 14 14" style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;"><path d="M2 8l4 4L12 3"/></svg> Lưu Video';
    if(res.success){ toast(res.data.message,true); closeDr(); setTimeout(function(){location.reload();},700); }
    else toast(res.data.message||'Lỗi không xác định.',false);
  }).catch(function(){ btn.disabled=false; toast('Lỗi kết nối.',false); });
});

/* ─ Delete (card) ─ */
document.querySelectorAll('.vd-del-btn').forEach(function(btn){
  btn.addEventListener('click',function(e){
    e.stopPropagation();
    if(!confirm('Xóa video này? Hành động không thể hoàn tác.')) return;
    var fd=new FormData(); fd.append('action','bacera_video_delete'); fd.append('_nonce',NONCE); fd.append('id',btn.getAttribute('data-id'));
    fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){
      if(res.success){ toast(res.data.message,true); setTimeout(function(){location.reload();},700); }
      else toast(res.data.message||'Lỗi',false);
    });
  });
});

/* ─ Delete (drawer) ─ */
document.getElementById('vdw-del-btn').addEventListener('click',function(){
  var id=document.getElementById('vdw-id').value;
  if(!id||id==='0') return;
  if(!confirm('Xóa video này? Hành động không thể hoàn tác.')) return;
  var fd=new FormData(); fd.append('action','bacera_video_delete'); fd.append('_nonce',NONCE); fd.append('id',id);
  fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){
    if(res.success){ toast(res.data.message,true); closeDr(); setTimeout(function(){location.reload();},700); }
    else toast(res.data.message||'Lỗi',false);
  });
});

/* ─ Filters + Live search highlight ─ */
function applyFilters(){
  var q=document.getElementById('vd-search').value.toLowerCase().trim();
  var catF=document.getElementById('vd-cat-filter').value;
  var typeF=document.getElementById('vd-type-filter').value;
  var stF=document.getElementById('vd-status-filter').value;
  var shown=0;
  document.querySelectorAll('.vd-card').forEach(function(c){
    var ok=true;
    if(q&&c.getAttribute('data-search').indexOf(q)===-1) ok=false;
    if(catF&&c.getAttribute('data-cat')!==catF) ok=false;
    if(typeF&&c.getAttribute('data-type')!==typeF) ok=false;
    if(stF&&c.getAttribute('data-status')!==stF) ok=false;
    c.style.display=ok?'':'none';
    if(ok) shown++;
  });
  var badge=document.getElementById('vd-count-badge');
  if(badge) badge.textContent=shown+' video'+(shown!==1?'s':'');
}
['vd-search','vd-cat-filter','vd-type-filter','vd-status-filter'].forEach(function(id){
  var el=document.getElementById(id); if(el) el.addEventListener('input',applyFilters);
});

/* ─ View toggle ─ */
var grid=document.getElementById('vd-grid');
document.getElementById('vd-view-grid').addEventListener('click',function(){
  grid.classList.remove('list-view'); this.classList.add('active');
  document.getElementById('vd-view-list').classList.remove('active');
});
document.getElementById('vd-view-list').addEventListener('click',function(){
  grid.classList.add('list-view'); this.classList.add('active');
  document.getElementById('vd-view-grid').classList.remove('active');
});

/* ─ Sortable ─ */
var sortEl=document.getElementById('vd-grid');
if(sortEl&&typeof Sortable!=='undefined'){
  Sortable.create(sortEl,{
    handle:'.vd-drag-handle', animation:180, ghostClass:'is-selected',
    onEnd:function(){
      var ids=[].map.call(sortEl.querySelectorAll('.vd-card'),function(c){return c.getAttribute('data-id');});
      var fd=new FormData(); fd.append('action','bacera_video_order'); fd.append('_nonce',NONCE);
      ids.forEach(function(id){fd.append('ids[]',id);});
      fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){
        if(res.success) toast('Đã lưu thứ tự.',true);
      });
    }
  });
}
})();
</script>
<?php
    }

    /* ── JS: Categories page ─────────────────────────────────────── */

    private function render_cats_script( string $nonce ): void {
        ?>
<script>
(function(){
'use strict';
var AJAX  = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
var NONCE = '<?php echo esc_js($nonce); ?>';

function toast(msg,ok){
  var t=document.getElementById('vd-toast'),m=document.getElementById('vd-toast-msg');
  var icon=document.getElementById('vd-toast-icon');
  t.className='show '+(ok===false?'err':'ok');
  icon.innerHTML=ok===false?'<svg viewBox="0 0 10 10"><path d="M2 2l6 6M8 2l-6 6" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>':'<svg viewBox="0 0 10 10"><polyline points="1.5,5 4,7.5 8.5,2" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round"/></svg>';
  m.textContent=msg; clearTimeout(t._t); t._t=setTimeout(function(){t.className='';},3200);
}

var bd=document.getElementById('vcbd'), dr=document.getElementById('vcdr');
function openDr(){ bd.classList.add('open'); dr.classList.add('open'); document.body.style.overflow='hidden'; }
function closeDr(){ bd.classList.remove('open'); dr.classList.remove('open'); document.body.style.overflow=''; }
bd.addEventListener('click',closeDr);
document.getElementById('vcdr-close').addEventListener('click',closeDr);
document.getElementById('vcdr-cancel').addEventListener('click',closeDr);
document.addEventListener('keydown',function(e){ if(e.key==='Escape') closeDr(); });

/* Status toggle */
var radVC=document.querySelectorAll('[name="vc_status"]');
function updateVcStatus(val){
  document.getElementById('vcpill-show').className='vdf-toggle '+(val==='1'?'active-show':'');
  document.getElementById('vcpill-hide').className='vdf-toggle '+(val==='0'?'active-hide':'');
}
radVC.forEach(function(r){ r.addEventListener('change',function(){ updateVcStatus(r.value); }); });

/* Open ADD */
function openAdd(){
  document.getElementById('vcdr-title').textContent='Thêm danh mục';
  document.getElementById('vc-id').value='0';
  document.getElementById('vc-name').value='';
  document.getElementById('vc-desc').value='';
  document.querySelector('[name="vc_status"][value="1"]').checked=true; updateVcStatus('1');
  openDr(); setTimeout(function(){ document.getElementById('vc-name').focus(); },300);
}
document.getElementById('vc-add-btn').addEventListener('click',openAdd);

/* Open EDIT */
document.querySelectorAll('.vc-edit-btn').forEach(function(btn){
  btn.addEventListener('click',function(){
    document.getElementById('vcdr-title').textContent='Sửa danh mục';
    document.getElementById('vc-id').value=btn.getAttribute('data-id');
    document.getElementById('vc-name').value=btn.getAttribute('data-name')||'';
    document.getElementById('vc-desc').value=btn.getAttribute('data-desc')||'';
    var av=btn.getAttribute('data-active')||'1';
    document.querySelector('[name="vc_status"][value="'+av+'"]').checked=true; updateVcStatus(av);
    openDr();
  });
});

/* Save */
document.getElementById('vcdr-save').addEventListener('click',function(){
  var name=document.getElementById('vc-name').value.trim();
  if(!name){ toast('Tên danh mục không được để trống.',false); return; }
  var status=document.querySelector('[name="vc_status"]:checked').value;
  var fd=new FormData();
  fd.append('action','bacera_vcat_save'); fd.append('_nonce',NONCE);
  fd.append('id',document.getElementById('vc-id').value);
  fd.append('name',name); fd.append('description',document.getElementById('vc-desc').value.trim());
  fd.append('is_active',status);
  var btn=this; btn.disabled=true; btn.textContent='Đang lưu…';
  fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){
    btn.disabled=false;
    btn.innerHTML='<svg viewBox="0 0 14 14" style="width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;"><path d="M2 8l4 4L12 3"/></svg> Lưu danh mục';
    if(res.success){ toast(res.data.message,true); closeDr(); setTimeout(function(){location.reload();},700); }
    else toast(res.data.message||'Lỗi',false);
  }).catch(function(){ btn.disabled=false; toast('Lỗi kết nối.',false); });
});

/* Delete */
document.querySelectorAll('.vc-del-btn').forEach(function(btn){
  btn.addEventListener('click',function(){
    var count=parseInt(btn.getAttribute('data-count')||'0',10);
    var msg='Xóa danh mục này?'+(count>0?' '+count+' video sẽ được chuyển sang Chưa phân loại.':'')+' Hành động không thể hoàn tác.';
    if(!confirm(msg)) return;
    var fd=new FormData(); fd.append('action','bacera_vcat_delete'); fd.append('_nonce',NONCE); fd.append('id',btn.getAttribute('data-id'));
    fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){
      if(res.success){ toast(res.data.message,true); setTimeout(function(){location.reload();},700); }
      else toast(res.data.message||'Lỗi',false);
    });
  });
});

/* Sortable */
var tbody=document.getElementById('vc-tbody');
if(tbody&&typeof Sortable!=='undefined'){
  Sortable.create(tbody,{
    handle:'.vd-row-drag', animation:150,
    onEnd:function(){
      var ids=[].map.call(tbody.querySelectorAll('tr'),function(r){return r.getAttribute('data-id');});
      var fd=new FormData(); fd.append('action','bacera_vcat_order'); fd.append('_nonce',NONCE);
      ids.forEach(function(id){fd.append('ids[]',id);});
      fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(res){
        if(res.success) toast('Đã lưu thứ tự.',true);
      });
    }
  });
}
})();
</script>
<?php
    }
}
