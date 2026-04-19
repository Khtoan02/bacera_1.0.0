<?php
namespace Bacera\Controllers;

/**
 * Bacera Team Controller
 * ─────────────────────────────────────────────────────────────────
 * Admin page "Nhân sự" under the Bacera menu.
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
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );

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

        $sql = "CREATE TABLE {$table} (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name        VARCHAR(120) NOT NULL DEFAULT '',
            role        VARCHAR(120) NOT NULL DEFAULT '',
            seo_slug    VARCHAR(150) NOT NULL DEFAULT '',
            department  VARCHAR(120) NOT NULL DEFAULT '',
            bio         TEXT,
            photo_url   VARCHAR(500) NOT NULL DEFAULT '',
            gallery_urls TEXT,
            phone       VARCHAR(100) NOT NULL DEFAULT '',
            email       VARCHAR(120) NOT NULL DEFAULT '',
            facebook    VARCHAR(200) NOT NULL DEFAULT '',
            instagram   VARCHAR(200) NOT NULL DEFAULT '',
            tiktok      VARCHAR(200) NOT NULL DEFAULT '',
            x_twitter   VARCHAR(200) NOT NULL DEFAULT '',
            linkedin    VARCHAR(200) NOT NULL DEFAULT '',
            youtube     VARCHAR(200) NOT NULL DEFAULT '',
            pinterest   VARCHAR(200) NOT NULL DEFAULT '',
            messenger   VARCHAR(200) NOT NULL DEFAULT '',
            telegram    VARCHAR(200) NOT NULL DEFAULT '',
            whatsapp    VARCHAR(120) NOT NULL DEFAULT '',
            zalo        VARCHAR(120) NOT NULL DEFAULT '',
            viber       VARCHAR(120) NOT NULL DEFAULT '',
            skype       VARCHAR(120) NOT NULL DEFAULT '',
            line_app    VARCHAR(120) NOT NULL DEFAULT '',
            wechat      VARCHAR(120) NOT NULL DEFAULT '',
            order_index SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            is_active   TINYINT(1) NOT NULL DEFAULT 1,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset};";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /* ── Enqueue Styles ──────────────────────────────────────────── */

    public function enqueue_styles( $hook ) {
        if ( strpos( $hook, 'bacera-team' ) === false ) return;

        wp_enqueue_style( 'bricolage-font', 'https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300;12..96,400;12..96,500;12..96,600;12..96,700&display=swap', [], null );
        wp_enqueue_media();
        echo '<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>';

        $css = '
:root {
  --bg:#F8F7F3; --surface:#fff; --surface-2:#F1EEE1; --border:#EAE3D1;
  --text:#3d2f26; --text-2:#8d6a54; --text-3:#c0a28e;
  --accent:#d95f47; --accent-2:#c8513b;
  --green:#166534; --green-bg:#f0fdf4; --green-border:#bbf7d0;
  --amber:#92400e; --amber-bg:#fffbeb; --amber-border:#fde68a;
  --red:#991b1b;   --red-bg:#fef2f2;   --red-border:#fecaca;
  --blue:#1e3a5f;  --blue-bg:#eff6ff;  --blue-border:#bfdbfe;
  --r:10px; --rl:14px;
}
#wpcontent { padding-left: 0 !important; }
#wpbody-content { padding-bottom: 0; }

.tm-wrap * { box-sizing:border-box; margin:0; padding:0; }
.tm-wrap {
  font-family:"Bricolage Grotesque",system-ui,sans-serif;
  background:var(--bg); color:var(--text); font-size:14px; line-height:1.6;
  min-height:calc(100vh - 32px);
  padding:32px 36px 64px;
}

/* ── Buttons ── */
.tm-btn { height:34px; padding:0 14px; border-radius:var(--r); font-family:inherit; font-size:13px; font-weight:500; cursor:pointer; transition:all .15s; display:inline-flex; align-items:center; gap:6px; border:1px solid transparent; text-decoration:none!important; letter-spacing:-.01em; }
.tm-btn-outline { background:var(--surface); border-color:var(--border); color:var(--text)!important; }
.tm-btn-outline:hover { background:var(--surface-2)!important; border-color:var(--text-3)!important; }
.tm-btn-solid { background:var(--accent); color:#fff!important; border-color:var(--accent); }
.tm-btn-solid:hover { background:var(--accent-2)!important; border-color:var(--accent-2)!important; }
.tm-btn-danger { background:var(--red-bg); color:var(--red)!important; border-color:var(--red-border); }
.tm-btn-danger:hover { background:#fee2e2!important; }
.tm-btn-ghost { background:none; border:none; color:var(--text-2); font-family:inherit; font-size:13px; cursor:pointer; padding:4px 8px; border-radius:6px; transition:all .15s; display:inline-flex; align-items:center; gap:5px; }
.tm-btn-ghost:hover { background:var(--surface-2); color:var(--text); }
.tm-btn svg { width:13px; height:13px; flex-shrink:0; stroke:currentColor; fill:none; stroke-width:1.8; stroke-linecap:round; }
.tm-btn-sm { height:28px; padding:0 10px; font-size:12px; }

/* ── Badges ── */
.tm-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; white-space:nowrap; letter-spacing:.02em; }
.tm-badge-green { background:var(--green-bg); color:var(--green); border:1px solid var(--green-border); }
.tm-badge-gray  { background:var(--surface-2); color:var(--text-2); border:1px solid var(--border); }
.tm-badge-accent { background:#fff5f3; color:var(--accent); border:1px solid #ffd5cc; }
.tm-badge-icon { width:6px; height:6px; border-radius:50%; flex-shrink:0; }

/* ── List header ── */
.tm-list-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:28px; }
.tm-list-title { font-size:24px; font-weight:600; letter-spacing:-.5px; color:var(--text); }
.tm-list-sub { font-size:13px; color:var(--text-2); margin-top:4px; }

/* ── Metrics ── */
.tm-metrics { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:28px; }
.tm-mc { background:var(--surface); border:1px solid var(--border); border-radius:var(--rl); padding:18px 20px; }
.tm-mc:first-child { border-top:3px solid var(--accent); }
.tm-mc:nth-child(2) { border-top:3px solid var(--green); }
.tm-mc:nth-child(3) { border-top:3px solid #3b82f6; }
.tm-ml { font-size:11px; font-weight:600; color:var(--text-3); text-transform:uppercase; letter-spacing:.8px; margin-bottom:10px; display:flex; align-items:center; gap:6px; }
.tm-ml svg { width:12px; height:12px; stroke:currentColor; fill:none; stroke-width:1.8; flex-shrink:0; }
.tm-mv { font-size:28px; font-weight:600; letter-spacing:-.8px; line-height:1; color:var(--text); }
.tm-ms { font-size:12px; color:var(--text-2); margin-top:6px; }

/* ── Filter row ── */
.tm-filter-row { display:flex; gap:8px; margin-bottom:20px; align-items:center; flex-wrap:wrap; }
.tm-search-wrap { position:relative; flex:1; max-width:320px; }
.tm-search-wrap svg { position:absolute; left:10px; top:50%; transform:translateY(-50%); width:14px; height:14px; stroke:var(--text-3); fill:none; stroke-width:1.5; }
.tm-inp { width:100%; height:36px; padding:0 12px 0 34px; font-family:inherit; font-size:13px; border:1px solid var(--border); border-radius:var(--r); background:var(--surface); color:var(--text); outline:none; transition:all .15s; }
.tm-inp:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(217,95,71,.1); }
.tm-sel { height:36px; padding:0 12px; font-family:inherit; font-size:13px; border:1px solid var(--border); border-radius:var(--r); background:var(--surface); color:var(--text-2); outline:none; cursor:pointer; }
.tm-sel:focus { border-color:var(--accent); }

/* ── Section card ── */
.tm-sc { background:var(--surface); border:1px solid var(--border); border-radius:var(--rl); margin-bottom:20px; overflow:hidden; }
.tm-sh { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-bottom:1px solid var(--border); }
.tm-st { font-size:14px; font-weight:600; color:var(--text); display:flex; align-items:center; gap:8px; }
.tm-st svg { width:16px; height:16px; stroke:var(--text-2); fill:none; stroke-width:1.8; stroke-linecap:round; flex-shrink:0; }
.tm-sb { padding:0; }

/* ── Card grid ── */
.tm-card-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:16px; padding:20px; }
.tm-card {
  background:var(--surface); border:1px solid var(--border); border-radius:var(--rl);
  overflow:hidden; display:flex; flex-direction:column;
  transition:border-color .18s, box-shadow .18s, transform .18s;
  position:relative;
}
.tm-card:hover { border-color:var(--text-3); box-shadow:0 6px 24px rgba(61,47,38,.09); transform:translateY(-2px); }
.tm-card.hidden-member { opacity:.55; }

/* Card photo zone */
.tm-card-photo { position:relative; aspect-ratio:3/4; background:linear-gradient(135deg,#EBE7DF,#D9CFBE); overflow:hidden; }
.tm-card-photo img { width:100%; height:100%; object-fit:cover; object-position:top; transition:transform .6s; }
.tm-card:hover .tm-card-photo img { transform:scale(1.04); }
.tm-card-initials {
  width:100%; height:100%; display:flex; align-items:center; justify-content:center;
  font-size:40px; font-weight:700; color:var(--text-3); letter-spacing:-2px;
}
/* Status pill on photo */
.tm-card-status {
  position:absolute; top:10px; right:10px;
}
/* Drag handle on photo */
.tm-card-drag {
  position:absolute; top:10px; left:10px;
  width:28px; height:28px; border-radius:8px;
  background:rgba(255,255,255,.85); backdrop-filter:blur(4px);
  border:1px solid rgba(0,0,0,.08);
  display:flex; align-items:center; justify-content:center;
  cursor:grab; opacity:0; transition:opacity .15s;
  color:var(--text-2);
}
.tm-card-drag:active { cursor:grabbing; }
.tm-card-drag svg { width:13px; height:13px; stroke:currentColor; fill:none; stroke-width:1.8; }
.tm-card:hover .tm-card-drag { opacity:1; }

/* Card body */
.tm-card-body { padding:12px 14px; display:flex; flex-direction:column; gap:2px; flex:1; }
.tm-card-name { font-size:13px; font-weight:700; color:var(--text); line-height:1.3; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.tm-card-role { font-size:11px; color:var(--text-2); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:6px; }
.tm-card-dept { display:inline-flex; padding:2px 8px; border-radius:6px; font-size:10px; font-weight:600; background:var(--surface-2); color:var(--text-2); border:1px solid var(--border); max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

/* Card footer actions */
.tm-card-foot {
  display:flex; align-items:center; justify-content:space-between;
  padding:8px 10px; border-top:1px solid var(--border);
  background:var(--bg);
}

/* Sortable ghost */
.tm-card-ghost { opacity:.35; border-style:dashed !important; }

/* Avatar ── (still used in form sidebar) */
.tm-avatar { width:38px; height:38px; border-radius:9px; object-fit:cover; background:var(--surface-2); border:1.5px solid var(--border); flex-shrink:0; }
.tm-avatar-initials { width:38px; height:38px; border-radius:9px; background:linear-gradient(135deg,#EBE7DF,#D9CFBE); border:1.5px solid var(--border); display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:var(--text-2); flex-shrink:0; letter-spacing:-.5px; }
.tm-member-name { font-size:13px; font-weight:600; color:var(--text); }
.tm-member-dept { display:inline-block; padding:2px 8px; border-radius:6px; font-size:11px; font-weight:500; background:var(--surface-2); color:var(--text-2); border:1px solid var(--border); }

/* ── Empty state ── */
.tm-empty { text-align:center; padding:64px 24px; }
.tm-empty-icon { width:52px; height:52px; stroke:var(--text-3); fill:none; stroke-width:1; margin:0 auto 16px; opacity:.4; display:block; }
.tm-empty h3 { font-size:16px; font-weight:600; margin-bottom:6px; color:var(--text); }
.tm-empty p { font-size:13px; color:var(--text-2); margin-bottom:20px; }

/* ── Toast ── */
#tm-toast { position:fixed; bottom:28px; right:28px; z-index:99999; background:#1c1917; color:#fff; padding:13px 18px; border-radius:10px; font-size:13px; font-weight:500; box-shadow:0 8px 24px rgba(0,0,0,.2); opacity:0; transform:translateY(8px); transition:all .3s; pointer-events:none; display:flex; align-items:center; gap:8px; }
#tm-toast.show { opacity:1; transform:translateY(0); }
#tm-toast svg { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:2.5; stroke-linecap:round; flex-shrink:0; }
#tm-toast.ok svg { stroke:#4ade80; }
#tm-toast.err svg { stroke:#f87171; }

/* ── Back nav ── */
.tm-back { display:inline-flex; align-items:center; gap:5px; font-size:13px; color:var(--text-2); margin-bottom:20px; background:none; border:none; font-family:inherit; padding:0; cursor:pointer; transition:color .15s; text-decoration:none!important; }
.tm-back:hover { color:var(--accent); }
.tm-back svg { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:2; stroke-linecap:round; }

/* ── Page header ── */
.tm-page-header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:28px; }
.tm-page-title { font-size:22px; font-weight:600; letter-spacing:-.4px; color:var(--text); display:flex; align-items:center; gap:10px; }
.tm-page-title svg { width:20px; height:20px; stroke:var(--text-2); fill:none; stroke-width:1.8; stroke-linecap:round; }
.tm-page-sub { font-size:13px; color:var(--text-2); margin-top:4px; }
.tm-hdr-actions { display:flex; gap:8px; align-items:flex-start; }

/* ── Form ── */
.tm-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:18px; }
.tm-form-grid.cols1 { grid-template-columns:1fr; }
.tm-field { display:flex; flex-direction:column; gap:5px; }
.tm-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--text-2); display:flex; align-items:center; gap:5px; }
.tm-label svg { width:11px; height:11px; stroke:currentColor; fill:none; stroke-width:1.8; flex-shrink:0; }
.tm-req { color:var(--accent); }
.tm-fi, .tm-fs, .tm-ft {
  width:100%; font-family:inherit; font-size:13px; padding:9px 12px;
  border:1px solid var(--border); border-radius:var(--r);
  background:var(--surface); color:var(--text); outline:none; transition:all .15s;
}
.tm-fi, .tm-fs { height:38px; padding:0 12px; }
.tm-ft { padding:9px 12px; resize:vertical; min-height:90px; line-height:1.6; height:auto; }
.tm-fi:focus, .tm-fs:focus, .tm-ft:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(217,95,71,.1); }
.tm-hint { font-size:11px; color:var(--text-3); margin-top:3px; }
.tm-form-divider { border:0; border-top:1px solid var(--border); margin:20px 0; }
.tm-form-footer { display:flex; align-items:center; gap:12px; padding-top:4px; }
.tm-form-section { padding:24px; }
.tm-form-section + .tm-form-section { border-top:1px solid var(--border); }
.tm-section-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--text-3); margin-bottom:16px; display:flex; align-items:center; gap:6px; }
.tm-section-title svg { width:12px; height:12px; stroke:currentColor; fill:none; stroke-width:1.8; }

/* ── Photo preview ── */
.tm-photo-preview-wrap { margin-top:10px; display:none; }
.tm-photo-preview-wrap.visible { display:flex; align-items:flex-start; gap:12px; }
.tm-photo-preview { width:72px; height:72px; object-fit:cover; border-radius:10px; border:2px solid var(--border); flex-shrink:0; }
.tm-photo-clear { background:none; border:1px solid var(--border); border-radius:7px; padding:3px 8px; font-size:11px; color:var(--text-2); cursor:pointer; font-family:inherit; transition:all .15s; display:flex; align-items:center; gap:4px; }
.tm-photo-clear:hover { background:var(--red-bg); border-color:var(--red-border); color:var(--red); }
.tm-photo-clear svg { width:10px; height:10px; stroke:currentColor; fill:none; stroke-width:2; }
.tm-photo-actions { display:flex; gap:8px; align-items:flex-start; }

/* ── Notice ── */
.tm-notice-success { background:var(--green-bg); border:1px solid var(--green-border); border-radius:var(--r); padding:10px 16px; color:var(--green); font-size:13px; margin-bottom:20px; display:flex; align-items:center; gap:8px; }
.tm-notice-success svg { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:2.5; stroke-linecap:round; flex-shrink:0; }
.tm-notice-error { background:var(--red-bg); border:1px solid var(--red-border); border-radius:var(--r); padding:10px 16px; color:var(--red); font-size:13px; margin-bottom:20px; }

/* ── Form page – sticky header ── */
.tmf-header {
  display:flex; align-items:center; justify-content:space-between; gap:16px;
  padding:13px 24px; border-bottom:1px solid var(--border);
  background:rgba(248,247,243,.92); backdrop-filter:blur(10px);
  position:sticky; top:32px; z-index:20; flex-wrap:wrap;
  margin:-32px -36px 28px; /* bleed out of .tm-wrap padding */
  border-radius:0;
}
.tmf-hd-info { display:flex; align-items:center; gap:12px; }
.tmf-hd-icon {
  width:36px; height:36px; border-radius:9px;
  background:var(--surface-2); border:1px solid var(--border);
  display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.tmf-hd-icon svg { width:16px; height:16px; stroke:var(--text-2); fill:none; stroke-width:1.8; stroke-linecap:round; }
.tmf-hd-title { font-size:14px; font-weight:700; color:var(--text); }
.tmf-hd-sub   { font-size:11px; color:var(--text-3); margin-top:1px; }
.tmf-hd-acts  { display:flex; align-items:center; gap:8px; }

/* ── Form 2-col body ── */
.tmf-body { display:grid; grid-template-columns:1fr 300px; gap:24px; align-items:start; }
@media(max-width:900px) { .tmf-body { grid-template-columns:1fr; } }
.tmf-main { display:flex; flex-direction:column; gap:16px; }
.tmf-side { display:flex; flex-direction:column; gap:16px; position:sticky; top:80px; }

/* ── Form section card ── */
.tmf-section { background:var(--surface); border:1px solid var(--border); border-radius:var(--rl); overflow:hidden; }
.tmf-sec-head {
  display:flex; align-items:center; gap:8px;
  padding:12px 18px; border-bottom:1px solid var(--border);
  font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--text-2);
}
.tmf-sec-head svg { width:13px; height:13px; stroke:currentColor; fill:none; stroke-width:1.8; stroke-linecap:round; flex-shrink:0; }
.tmf-sec-body { padding:18px 20px; display:flex; flex-direction:column; gap:16px; }

/* ── Field ── */
.tmf-row2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.tmf-field { display:flex; flex-direction:column; gap:5px; }
.tmf-label { font-size:11px; font-weight:600; color:var(--text-2); display:flex; align-items:center; gap:5px; letter-spacing:.01em; }
.tmf-label svg { width:11px; height:11px; stroke:currentColor; fill:none; stroke-width:1.8; flex-shrink:0; }
.tmf-req { color:var(--accent); margin-left:1px; }
.tmf-input, .tmf-select, .tmf-textarea {
  width:100%; font-family:inherit; font-size:13px;
  border:1px solid var(--border); border-radius:var(--r);
  background:var(--surface); color:var(--text); outline:none; transition:all .15s;
}
.tmf-input, .tmf-select { height:38px; padding:0 12px; }
.tmf-textarea { padding:9px 12px; resize:vertical; min-height:100px; line-height:1.7; height:auto; }
.tmf-input:focus, .tmf-select:focus, .tmf-textarea:focus {
  border-color:var(--accent); box-shadow:0 0 0 3px rgba(217,95,71,.1);
}
.tmf-hint { font-size:11px; color:var(--text-3); line-height:1.5; }

/* ── Photo upload zone ── */
.tmf-photo-zone {
  aspect-ratio:3/4; border-radius:12px; overflow:hidden;
  background:linear-gradient(135deg,#EBE7DF,#D9CFBE);
  border:2px dashed var(--border); position:relative;
  transition:border-color .15s;
  cursor:pointer;
}
.tmf-photo-zone:hover { border-color:var(--text-3); }
.tmf-photo-zone img { width:100%; height:100%; object-fit:cover; object-position:top; display:block; }
.tmf-photo-initials {
  width:100%; height:100%; display:flex; flex-direction:column;
  align-items:center; justify-content:center; gap:10px;
  color:var(--text-3);
}
.tmf-photo-initials-text { font-size:48px; font-weight:700; letter-spacing:-3px; opacity:.5; }
.tmf-photo-initials-label { font-size:12px; font-weight:500; display:flex; align-items:center; gap:5px; }
.tmf-photo-initials-label svg { width:13px; height:13px; stroke:currentColor; fill:none; stroke-width:1.8; stroke-linecap:round; }
.tmf-photo-overlay {
  position:absolute; inset:0; background:rgba(28,25,23,.55);
  display:flex; align-items:center; justify-content:center;
  opacity:0; transition:opacity .2s; gap:8px; flex-direction:column;
}
.tmf-photo-zone:hover .tmf-photo-overlay { opacity:1; }
.tmf-photo-overlay-label { color:#fff; font-size:12px; font-weight:600; display:flex; align-items:center; gap:6px; }
.tmf-photo-overlay-label svg { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:1.8; }

/* Status toggle pills */
.tmf-status-group { display:flex; gap:8px; }
.tmf-status-pill {
  flex:1; display:flex; align-items:center; justify-content:center; gap:7px;
  padding:9px 12px; border-radius:8px; border:1.5px solid var(--border);
  background:var(--surface); cursor:pointer; transition:all .15s;
  font-size:12px; font-weight:600; color:var(--text-2);
}
.tmf-status-pill:hover { border-color:var(--text-3); }
.tmf-status-pill input { display:none; }
.tmf-status-pill.active-show { border-color:var(--green); background:var(--green-bg); color:var(--green); }
.tmf-status-pill.active-hide { border-color:var(--border); background:var(--surface-2); color:var(--text-2); }
.tmf-status-pill svg { width:12px; height:12px; stroke:currentColor; fill:none; stroke-width:2; flex-shrink:0; }

/* Preview card (sidebar) */
.tmf-preview-name { font-size:15px; font-weight:700; color:var(--text); text-align:center; margin-top:12px; line-height:1.3; }
.tmf-preview-role { font-size:12px; color:var(--accent); font-weight:500; text-align:center; margin-top:3px; }
.tmf-preview-dept { text-align:center; margin-top:8px; }

/* URL input row */
.tmf-url-row { display:flex; gap:8px; align-items:flex-start; }
.tmf-url-row .tmf-input { flex:1; }

/* ── Modal / drawer ── */
.tmm-bd {
  position:fixed; inset:0; background:rgba(28,25,23,.45);
  z-index:9990; opacity:0; pointer-events:none;
  transition:opacity .24s; backdrop-filter:blur(3px);
}
.tmm-bd.open { opacity:1; pointer-events:all; }
.tmm-dr {
  position:fixed; top:32px; right:0; bottom:0; width:640px; max-width:96vw;
  background:var(--bg); z-index:9991;
  transform:translateX(100%);
  transition:transform .28s cubic-bezier(.4,0,.2,1);
  display:flex; flex-direction:column; overflow:hidden;
  box-shadow:-12px 0 48px rgba(28,25,23,.18);
}
.tmm-dr.open { transform:none; }
.tmm-head {
  display:flex; align-items:center; gap:12px;
  padding:13px 18px; border-bottom:1px solid var(--border);
  background:var(--surface); flex-shrink:0;
}
.tmm-head-icon {
  width:34px; height:34px; border-radius:8px;
  background:var(--surface-2); border:1px solid var(--border);
  display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.tmm-head-icon svg { width:15px; height:15px; stroke:var(--text-2); fill:none; stroke-width:1.8; stroke-linecap:round; }
.tmm-head-text { flex:1; min-width:0; }
.tmm-head-title { font-size:13px; font-weight:700; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.tmm-head-sub { font-size:11px; color:var(--text-3); margin-top:1px; }
.tmm-close {
  background:none; border:1px solid var(--border); border-radius:8px;
  width:30px; height:30px; display:flex; align-items:center; justify-content:center;
  cursor:pointer; color:var(--text-2); transition:all .15s; flex-shrink:0;
}
.tmm-close:hover { background:var(--surface-2); color:var(--text); }
.tmm-close svg { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:2; stroke-linecap:round; }
.tmm-body {
  flex:1; overflow-y:auto; padding:20px; display:block;
}
.tmm-photo-col { display:flex; flex-direction:column; gap:10px; width:150px; flex-shrink:0; }
.tmm-fields-col { display:flex; flex-direction:column; gap:14px; flex:1; min-width:0; }
.tmm-pill {
  flex:1; display:flex; align-items:center; justify-content:center; gap:6px;
  padding:7px 8px; border-radius:7px; border:1.5px solid var(--border);
  background:var(--surface); cursor:pointer;
  font-size:11px; font-weight:600; color:var(--text-2); transition:all .15s;
}
.tmm-pill input { display:none; }
.tmm-pill.on-show { border-color:var(--green); background:var(--green-bg); color:var(--green); }
.tmm-pill.on-hide { border-color:var(--border); background:var(--surface-2); color:var(--text-2); }
.tmm-pill svg { width:11px; height:11px; stroke:currentColor; fill:none; stroke-width:2; flex-shrink:0; }
.tmm-pill-grp { display:flex; gap:6px; }
.tmm-foot {
  padding:13px 18px; border-top:1px solid var(--border);
  background:var(--surface); display:flex; gap:8px; align-items:center; flex-shrink:0;
}
.tmm-foot-gap { flex:1; }
.tm-card-photo, .tm-card-body { cursor:pointer; }
.tm-card-edit-overlay {
  position:absolute; inset:0; background:rgba(28,25,23,.5);
  display:flex; align-items:center; justify-content:center; gap:6px;
  color:#fff; font-size:12px; font-weight:700;
  opacity:0; transition:opacity .18s;
  border-radius:inherit;
}
.tm-card-edit-overlay svg { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:1.8; }
.tm-card-photo:hover .tm-card-edit-overlay { opacity:1; }
';
        wp_register_style( 'bacera-team-admin', false );
        wp_enqueue_style( 'bacera-team-admin' );
        wp_add_inline_style( 'bacera-team-admin', $css );
    }

    /* ── Menu ────────────────────────────────────────────────────── */

    public function add_menu() {
        add_submenu_page(
            'bacera-main',
            'Quản lý Nhân sự',
            'Nhân sự',
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

        $phone      = sanitize_text_field( $_POST['phone'] ?? '' );
        $email      = sanitize_email( $_POST['email'] ?? '' );
        $facebook   = esc_url_raw( $_POST['facebook'] ?? '' );
        $instagram  = esc_url_raw( $_POST['instagram'] ?? '' );
        $tiktok     = esc_url_raw( $_POST['tiktok'] ?? '' );
        $x_twitter  = esc_url_raw( $_POST['x_twitter'] ?? '' );
        $linkedin   = esc_url_raw( $_POST['linkedin'] ?? '' );
        $youtube    = esc_url_raw( $_POST['youtube'] ?? '' );
        $pinterest  = esc_url_raw( $_POST['pinterest'] ?? '' );
        $messenger  = esc_url_raw( $_POST['messenger'] ?? '' );
        $telegram   = esc_url_raw( $_POST['telegram'] ?? '' );
        $whatsapp   = sanitize_text_field( $_POST['whatsapp'] ?? '' );
        $zalo       = sanitize_text_field( $_POST['zalo'] ?? '' );
        $viber      = sanitize_text_field( $_POST['viber'] ?? '' );
        $skype      = sanitize_text_field( $_POST['skype'] ?? '' );
        $line_app   = sanitize_text_field( $_POST['line_app'] ?? '' );
        $wechat     = sanitize_text_field( $_POST['wechat'] ?? '' );
        $gallery_urls = sanitize_textarea_field( wp_unslash( $_POST['gallery_urls'] ?? '' ) );

        if ( empty( $name ) ) {
            wp_send_json_error( [ 'message' => 'Tên không được để trống.' ] );
        }

        $seo_slug = sanitize_title($role . '-' . $name);

        $data = compact( 'name', 'role', 'seo_slug', 'department', 'bio', 'photo_url', 'is_active', 'phone', 'email', 'facebook', 'instagram', 'tiktok', 'x_twitter', 'linkedin', 'youtube', 'pinterest', 'messenger', 'telegram', 'whatsapp', 'zalo', 'viber', 'skype', 'line_app', 'wechat', 'gallery_urls' );

        if ( $id > 0 ) {
            $wpdb->update( $table, $data, [ 'id' => $id ] );
            wp_send_json_success( [ 'message' => 'Đã cập nhật thành viên.', 'id' => $id ] );
        } else {
            $max_order = (int) $wpdb->get_var( "SELECT COALESCE(MAX(order_index),0) FROM {$table}" );
            $data['order_index'] = $max_order + 1;
            $data['created_at']  = current_time( 'mysql' );
            $wpdb->insert( $table, $data );
            wp_send_json_success( [ 'message' => 'Đã thêm thành viên mới.', 'id' => $wpdb->insert_id ] );
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
        wp_send_json_success( [ 'message' => 'Đã xóa thành viên.' ] );
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

    /* ── Render Page (router) ─────────────────────────────────────── */

    public function render_page() {
        global $wpdb;
        $table   = $wpdb->prefix . self::TABLE_SUFFIX;
        $nonce   = wp_create_nonce( 'bacera_team_nonce' );
        $members = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY order_index ASC, id ASC", ARRAY_A ) ?: [];

        $edit_id = intval( $_GET['edit'] ?? 0 );
        $editing = null;
        if ( $edit_id > 0 ) {
            $editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $edit_id ), ARRAY_A );
        }

        $is_add_form  = ! $edit_id && isset( $_GET['action'] ) && $_GET['action'] === 'add';
        $is_edit_form = $edit_id && $editing;
        $is_list      = ! $is_add_form && ! $is_edit_form;
        ?>

        <div id="tm-toast">
            <svg viewBox="0 0 14 14" id="tm-toast-icon"><polyline points="2,7 5.5,10.5 12,3"/></svg>
            <span id="tm-toast-msg"></span>
        </div>

        <div class="tm-wrap">
            <div id="tm-alert"></div>

            <?php if ( $is_add_form || $is_edit_form ): ?>
                <?php $this->render_form( $is_edit_form ? $editing : null, $nonce, $members ); ?>
            <?php else: ?>
                <?php $this->render_list( $members, $nonce ); ?>
            <?php endif; ?>
        </div>

        <?php
        $this->render_scripts( $nonce );
    }

    /* ── List View ────────────────────────────────────────────────── */

    private function render_list( array $members, string $nonce ) {
        $total      = count( $members );
        $active     = count( array_filter( $members, fn($m) => $m['is_active'] ) );
        $depts      = array_unique( array_filter( array_column( $members, 'department' ) ) );
        $dept_count = count( $depts );
        sort( $depts );

        $add_url  = admin_url( 'admin.php?page=bacera-team&action=add' );
        $team_page = get_page_by_path( 'our-team' );
        $team_url = $team_page ? get_permalink( $team_page->ID ) : home_url( '/our-team/' );
        ?>

        <!-- List header -->
        <div class="tm-list-header">
            <div>
                <div class="tm-list-title">Quản lý Nhân sự</div>
                <div class="tm-list-sub"><?php echo $total; ?> thành viên trong <?php echo $dept_count; ?> phòng ban</div>
            </div>
            <div style="display:flex;gap:8px;">
                <a href="<?php echo esc_url( $team_url ); ?>" target="_blank" class="tm-btn tm-btn-outline">
                    <svg viewBox="0 0 14 14"><path d="M8 2h4v4M12 2L6 8M5 3H2v9h9V9"/></svg>
                    Xem trang
                </a>
                <a href="<?php echo esc_url( $add_url ); ?>" class="tm-btn tm-btn-solid">
                    <svg viewBox="0 0 14 14"><path d="M7 2v10M2 7h10" stroke-linecap="round"/></svg>
                    Thêm thành viên
                </a>
            </div>
        </div>

        <!-- Metrics -->
        <div class="tm-metrics">
            <div class="tm-mc">
                <div class="tm-ml">
                    <svg viewBox="0 0 14 14"><circle cx="7" cy="5" r="2.5"/><path d="M2 12c0-3.31 2.24-5 5-5s5 1.69 5 5"/></svg>
                    Tổng thành viên
                </div>
                <div class="tm-mv"><?php echo $total; ?></div>
                <div class="tm-ms">Trong hệ thống</div>
            </div>
            <div class="tm-mc">
                <div class="tm-ml">
                    <svg viewBox="0 0 14 14"><circle cx="7" cy="7" r="5"/><polyline points="5,7 6.5,8.5 9.5,5.5"/></svg>
                    Đang hiển thị
                </div>
                <div class="tm-mv"><?php echo $active; ?></div>
                <div class="tm-ms"><?php echo $total - $active; ?> đang ẩn</div>
            </div>
            <div class="tm-mc">
                <div class="tm-ml">
                    <svg viewBox="0 0 14 14"><rect x="1" y="3" width="12" height="9" rx="1.5"/><path d="M1 6h12"/><path d="M5 3V1M9 3V1"/></svg>
                    Phòng ban
                </div>
                <div class="tm-mv"><?php echo $dept_count; ?></div>
                <div class="tm-ms">Bộ phận khác nhau</div>
            </div>
        </div>

        <!-- Filter row -->
        <div class="tm-filter-row">
            <div class="tm-search-wrap">
                <svg viewBox="0 0 16 16"><circle cx="7" cy="7" r="4"/><path d="M10.5 10.5l3 3" stroke-linecap="round"/></svg>
                <input class="tm-inp" type="text" id="tm-search" placeholder="Tìm tên, chức danh...">
            </div>
            <select class="tm-sel" id="tm-dept-filter">
                <option value="">Tất cả phòng ban</option>
                <?php foreach ( $depts as $d ): ?>
                <option value="<?php echo esc_attr( $d ); ?>"><?php echo esc_html( $d ); ?></option>
                <?php endforeach; ?>
            </select>
            <select class="tm-sel" id="tm-status-filter">
                <option value="">Tất cả trạng thái</option>
                <option value="1">Đang hiển thị</option>
                <option value="0">Đang ẩn</option>
            </select>
        </div>

        <!-- Card grid -->
        <div class="tm-sc">
            <div class="tm-sh">
                <span class="tm-st">
                    <svg viewBox="0 0 16 16"><circle cx="6" cy="5" r="3"/><path d="M1 14c0-4 10-4 10 0"/><circle cx="13" cy="5" r="2"/><path d="M15 12c0-2-1.5-3-3-3"/></svg>
                    Danh sách thành viên
                </span>
                <span style="font-size:11px;color:var(--text-3);display:flex;align-items:center;gap:5px;">
                    <svg style="width:12px;height:12px;stroke:var(--text-3);fill:none;stroke-width:1.8;" viewBox="0 0 12 12"><circle cx="4" cy="2.5" r=".9"/><circle cx="8" cy="2.5" r=".9"/><circle cx="4" cy="6" r=".9"/><circle cx="8" cy="6" r=".9"/><circle cx="4" cy="9.5" r=".9"/><circle cx="8" cy="9.5" r=".9"/></svg>
                    Kéo thẻ để sắp xếp thứ tự
                </span>
            </div>
            <div class="tm-sb">
                <?php if ( empty( $members ) ): ?>
                <div class="tm-empty">
                    <svg class="tm-empty-icon" viewBox="0 0 48 48">
                        <circle cx="24" cy="18" r="8"/>
                        <path d="M8 42c0-9 32-9 32 0"/>
                    </svg>
                    <h3>Chưa có thành viên nào</h3>
                    <p>Bắt đầu bằng cách thêm thành viên đầu tiên vào team.</p>
                    <a href="<?php echo esc_url( $add_url ); ?>" class="tm-btn tm-btn-solid">
                        <svg viewBox="0 0 14 14"><path d="M7 2v10M2 7h10" stroke-linecap="round"/></svg>
                        Thêm thành viên
                    </a>
                </div>
                <?php else: ?>
                <div class="tm-card-grid" id="tm-sortable">
                    <?php foreach ( $members as $m ):
                        $initials  = implode( '', array_map( fn($w) => mb_substr($w,0,1,'UTF-8'), array_slice( explode(' ', $m['name']), -2 ) ) );
                        $dept_safe = esc_attr( $m['department'] ?? '' );
                        $status_val = $m['is_active'] ? '1' : '0';
                        $edit_url  = esc_url( admin_url( 'admin.php?page=bacera-team&edit=' . $m['id'] ) );
                    ?>
                    <div class="tm-card <?php echo $m['is_active'] ? '' : 'hidden-member'; ?>"
                         data-id="<?php echo esc_attr( $m['id'] ); ?>"
                         data-name="<?php echo esc_attr( strtolower($m['name']) . ' ' . strtolower($m['role']) ); ?>"
                         data-dept="<?php echo $dept_safe; ?>"
                         data-status="<?php echo $status_val; ?>">

                        <!-- Drag handle (appears on hover) -->
                        <div class="tm-card-drag" title="Kéo để sắp xếp">
                            <svg viewBox="0 0 14 14"><circle cx="5" cy="3.5" r="1"/><circle cx="9" cy="3.5" r="1"/><circle cx="5" cy="7" r="1"/><circle cx="9" cy="7" r="1"/><circle cx="5" cy="10.5" r="1"/><circle cx="9" cy="10.5" r="1"/></svg>
                        </div>

                        <!-- Photo (click = open drawer) -->
                        <div class="tm-card-photo" title="Nhấp để chỉnh sửa">
                            <?php if ( $m['photo_url'] ): ?>
                            <img src="<?php echo esc_url( $m['photo_url'] ); ?>" alt="<?php echo esc_attr( $m['name'] ); ?>"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <div class="tm-card-initials" style="display:none;"><?php echo esc_html( mb_strtoupper($initials,'UTF-8') ); ?></div>
                            <?php else: ?>
                            <div class="tm-card-initials"><?php echo esc_html( mb_strtoupper($initials,'UTF-8') ); ?></div>
                            <?php endif; ?>

                            <!-- Hover edit overlay -->
                            <div class="tm-card-edit-overlay">
                                <svg viewBox="0 0 16 16"><path d="M11 2l3 3-8 8H3v-3L11 2z"/></svg>
                                Chỉnh sửa
                            </div>

                            <!-- Status badge on photo -->
                            <div class="tm-card-status">
                                <?php if ( $m['is_active'] ): ?>
                                <span class="tm-badge tm-badge-green" style="backdrop-filter:blur(4px);background:rgba(240,253,244,.9);">
                                    <span class="tm-badge-icon" style="background:var(--green)"></span>Hiển thị
                                </span>
                                <?php else: ?>
                                <span class="tm-badge tm-badge-gray" style="backdrop-filter:blur(4px);background:rgba(241,238,225,.9);">
                                    <span class="tm-badge-icon" style="background:var(--text-3)"></span>Ẩn
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Info (click = open drawer) -->
                        <div class="tm-card-body" title="Nhấp để chỉnh sửa">
                            <div class="tm-card-name"><?php echo esc_html( $m['name'] ); ?></div>
                            <div class="tm-card-role"><?php echo esc_html( $m['role'] ?: '—' ); ?></div>
                            <?php if ( $m['department'] ): ?>
                            <span class="tm-card-dept"><?php echo esc_html( $m['department'] ); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Footer: only delete (edit via drawer) -->
                        <div class="tm-card-foot">
                            <button class="tm-btn tm-btn-outline tm-btn-sm tmm-open-btn" data-id="<?php echo esc_attr( $m['id'] ); ?>">
                                <svg viewBox="0 0 14 14"><path d="M9.5 2.5l2 2-7 7H2.5V9l7-6.5z"/></svg>
                                Chỉnh sửa
                            </button>
                            <button class="tm-btn tm-btn-danger tm-btn-sm tm-delete-btn"
                                    data-id="<?php echo esc_attr( $m['id'] ); ?>"
                                    title="Xóa">
                                <svg viewBox="0 0 14 14"><polyline points="1,3 13,3"/><path d="M5,3V1h4v2"/><path d="M2,3l1,9h8l1-9"/></svg>
                                Xóa
                            </button>
                        </div>

                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php
        $departments_preset = [
            'Ban Giám đốc', 'Sales & Business', 'Marketing & Creative',
            'Craft & Production', 'Accounting', 'HR & Admin',
        ];
        ?>

        <!-- ══ EDIT DRAWER ══ -->
        <div id="tmm-bd" class="tmm-bd"></div>
        <div id="tmm-dr" class="tmm-dr" role="dialog" aria-modal="true">

            <!-- Head -->
            <div class="tmm-head">
                <div class="tmm-head-icon">
                    <svg viewBox="0 0 16 16"><path d="M11 2l3 3-8 8H3v-3L11 2z"/></svg>
                </div>
                <div class="tmm-head-text">
                    <div class="tmm-head-title" id="tmm-title">Chỉnh sửa thành viên</div>
                    <div class="tmm-head-sub" id="tmm-sub"></div>
                </div>
                <button class="tmm-close" id="tmm-close" title="Đóng (Esc)">
                    <svg viewBox="0 0 14 14"><path d="M2 2l10 10M12 2L2 12"/></svg>
                </button>
            </div>

            <!-- Body: photo col + fields col -->
            <div class="tmm-body">

                <div style="display:flex; gap:24px; margin-bottom:24px;">
                    <!-- Photo -->
                    <div class="tmm-photo-col">
                        <div class="tmf-photo-zone" id="tmm-photo-zone" onclick="document.getElementById('tmm-media-btn').click()">
                            <img id="tmm-photo-img" alt="" style="display:none;">
                            <div class="tmf-photo-initials" id="tmm-placeholder">
                                <div class="tmf-photo-initials-text" id="tmm-initials-big"></div>
                                <div class="tmf-photo-initials-label">
                                    <svg viewBox="0 0 14 14"><rect x="1" y="3" width="12" height="9" rx="1.5"/><circle cx="5" cy="7" r="1.5"/><path d="M1 11l3-3 2.5 2.5 2-2L12 11"/></svg>
                                    Đổi ảnh
                                </div>
                            </div>
                            <div class="tmf-photo-overlay">
                                <div class="tmf-photo-overlay-label">
                                    <svg viewBox="0 0 14 14"><rect x="1" y="3" width="12" height="9" rx="1.5"/><circle cx="5" cy="7" r="1.5"/><path d="M1 11l3-3 2.5 2.5 2-2L12 11"/></svg>
                                    Đổi ảnh
                                </div>
                            </div>
                        </div>
                        <div style="text-align:center;">
                            <div id="tmm-prev-name" style="font-size:12px;font-weight:700;color:var(--text);line-height:1.3;"></div>
                            <div id="tmm-prev-role" style="font-size:11px;color:var(--accent);margin-top:2px;"></div>
                        </div>
                        
                        <input type="hidden" id="tmm-photo-url">
                        <!-- Hidden media trigger -->
                        <button type="button" id="tmm-media-btn" style="display:none;"></button>
                        <button type="button" id="tmm-photo-clear" class="tm-btn tm-btn-danger tm-btn-sm" style="margin-top:4px; display:none; width:100%;" title="Xóa ảnh">Xoá ảnh hiện tại</button>
                    </div>

                    <!-- Fields -->
                    <div class="tmm-fields-col">
                        <input type="hidden" id="tmm-id">

                        <div style="display:flex; gap:14px;">
                            <div class="tmf-field" style="flex:1;">
                                <label class="tmf-label" for="tmm-name">Họ &amp; Tên <span class="tmf-req">*</span></label>
                                <input type="text" id="tmm-name" class="tmf-input" placeholder="Nguyễn Văn A">
                            </div>
                            <div class="tmf-field" style="flex:1;">
                                <label class="tmf-label" for="tmm-role">Chức danh</label>
                                <input type="text" id="tmm-role" class="tmf-input" placeholder="CEO, Designer…">
                            </div>
                        </div>

                        <div style="display:flex; gap:14px;">
                            <div class="tmf-field" style="flex:1;">
                                <label class="tmf-label" for="tmm-dept">Phòng ban</label>
                                <select id="tmm-dept" class="tmf-select">
                                    <option value="">— Chọn phòng ban —</option>
                                    <?php foreach ($departments_preset as $d): ?>
                                    <option value="<?php echo esc_attr($d); ?>"><?php echo esc_html($d); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="tmf-field" style="flex:1;">
                                <label class="tmf-label">Trạng thái (Hiển thị website)</label>
                                <div class="tmm-pill-grp">
                                    <label class="tmm-pill" id="tmm-pill-show">
                                        <input type="radio" name="tmm_active" value="1">
                                        <svg viewBox="0 0 12 12"><circle cx="6" cy="6" r="5"/><path d="M4 6l1.5 1.5L8 4"/></svg>
                                        Hiển thị
                                    </label>
                                    <label class="tmm-pill" id="tmm-pill-hide">
                                        <input type="radio" name="tmm_active" value="0">
                                        <svg viewBox="0 0 12 12"><circle cx="6" cy="6" r="5"/><path d="M4.5 7.5l3-3M7.5 7.5l-3-3"/></svg>
                                        Ẩn
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bio & Social -->
                <div style="display:flex; flex-direction:column; gap:20px;">
                    <div class="tmf-field">
                        <label class="tmf-label" for="tmm-bio">Tiểu sử (Nội dung giới thiệu)</label>
                        <textarea id="tmm-bio" class="tmf-textarea" rows="3" placeholder="Vài dòng giới thiệu…" style="min-height:60px;"></textarea>
                    </div>

                    <div class="tmf-section" style="padding:16px; background:var(--surface-2); border-radius:12px; border:1px solid var(--border);">
                        <div style="font-size:12px; font-weight:700; color:var(--text); margin-bottom:12px;">Mạng xã hội &amp; Liên hệ</div>
                        <style>
                            .dsi { width:24px; height:24px; flex-shrink:0; pointer-events:none; display:inline-block; }
                            .dsi svg,.dsi img { width:24px; height:24px; display:block; border-radius:5px; }
                            .drawer-si-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
                            .tmf-label { display:flex; align-items:center; gap:6px; font-size:11px; font-weight:600; color:var(--text-2); margin-bottom:4px; }
                        </style>
                        <div style="display:grid;grid-template-columns:1fr;gap:8px;">
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><span class="dsi"><svg width="24" height="24" viewBox="0 0 93 92" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="93" height="92" rx="15" fill="#4CAF50"/><path d="M65 57.7c-.1-1.1-.9-2.5-2.2-3.9-1.3-1.4-2.9-2.7-4.4-3.5-1.5-.8-2.9-.7-3.9.3l-2.2 2.2c-.3.3-.7.5-1 .4-.3-.1-1.5-.6-4-3.1-2.5-2.5-3-3.7-3.1-4-.1-.3.1-.7.4-1l2.2-2.2c1-1 1.1-2.4.3-3.9-.8-1.5-2.1-3.1-3.5-4.4-1.4-1.3-2.8-2.1-3.9-2.2-1.1-.1-2 .3-2.7 1l-2 2c-1.5 1.5-2.2 3.6-2 5.7.2 2.1 1.2 5.4 4.5 8.7 3.3 3.3 6.6 4.3 8.7 4.5 2.1.2 4.2-.5 5.7-2l2-2c.7-.7 1.1-1.6 1-2.7-.1-1.1-.6-2.3-1.4-3.4" fill="white"/></svg></span>Điện thoại</label>
                                    <input type="text" id="tmm-phone" class="tmf-input" placeholder="090...">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><span class="dsi"><svg width="24" height="24" viewBox="0 0 92 92" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="0.638672" y="0.5" width="90.5618" height="90.5618" rx="14.5" fill="white" stroke="#C4CFE3"/><path d="M22.0065 66.1236H30.4893V45.5227L18.3711 36.4341V62.4881C18.3711 64.4997 20.001 66.1236 22.0065 66.1236Z" fill="#4285F4"/><path d="M59.5732 66.1236H68.056C70.0676 66.1236 71.6914 64.4937 71.6914 62.4881V36.4341L59.5732 45.5227" fill="#34A853"/><path d="M59.5732 29.7693V45.5229L71.6914 36.4343V31.587C71.6914 27.0912 66.5594 24.5282 62.9663 27.2245" fill="#FBBC04"/><path d="M30.4893 45.5227V29.769L45.0311 40.6754L59.5729 29.769V45.5227L45.0311 56.429" fill="#EA4335"/><path d="M18.3711 31.587V36.4343L30.4893 45.5229V29.7693L27.0962 27.2245C23.4971 24.5282 18.3711 27.0912 18.3711 31.587Z" fill="#C5221F"/></svg></span>Email</label>
                                    <input type="email" id="tmm-email" class="tmf-input" placeholder="admin@domain.com">
                                </div>
                            </div>
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><span class="dsi"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 93 92" fill="none"><rect x="1.13867" width="91.5618" height="91.5618" rx="15" fill="#337FFF"/><path d="M57.4233 48.6403L58.7279 40.3588H50.6917V34.9759C50.6917 32.7114 51.8137 30.4987 55.4013 30.4987H59.1063V23.4465C56.9486 23.1028 54.7685 22.9168 52.5834 22.8901C45.9692 22.8901 41.651 26.8626 41.651 34.0442V40.3588H34.3193V48.6403H41.651V68.671H50.6917V48.6403H57.4233Z" fill="white"/></svg></span>Facebook</label>
                                    <input type="url" id="tmm-facebook" class="tmf-input" placeholder="https://facebook.com/...">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><span class="dsi"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 93 92" fill="none"><rect x="1.13867" width="91.5618" height="91.5618" rx="15" fill="url(#ig_adm)"/><path d="M38.3762 45.7808C38.3762 41.1786 42.1083 37.4468 46.7132 37.4468C51.3182 37.4468 55.0522 41.1786 55.0522 45.7808C55.0522 50.383 51.3182 54.1148 46.7132 54.1148C42.1083 54.1148 38.3762 50.383 38.3762 45.7808ZM33.8683 45.7808C33.8683 52.8708 39.619 58.618 46.7132 58.618C53.8075 58.618 59.5581 52.8708 59.5581 45.7808C59.5581 38.6908 53.8075 32.9436 46.7132 32.9436C39.619 32.9436 33.8683 38.6908 33.8683 45.7808ZM57.0648 32.4346C57.0648 33.6278 57.9726 34.5918 59.1499 34.8154C60.3272 35.039 61.5168 34.5918 62.1884 33.623C62.8601 32.6541 62.8601 31.3924 62.1884 30.4235C61.516 29.4546 60.3272 29.0074 59.1499 29.231C57.9726 29.4546 57.0648 30.4188 57.0648 31.612V32.4346ZM36.6072 66.1302C34.1683 66.0192 32.8427 65.6132 31.9618 65.2702C30.7939 64.8158 29.9606 64.2746 29.0845 63.4002C28.2083 62.5258 27.666 61.6938 27.2133 60.5266C26.8699 59.6466 26.4637 58.3214 26.3528 55.884C26.2316 53.2488 26.2073 52.4572 26.2073 45.781C26.2073 39.1048 26.2336 38.3154 26.3528 35.678C26.4639 33.2406 26.8731 31.918 27.2133 31.0354C27.668 29.8682 28.2095 29.0354 29.0845 28.1598C29.9594 27.2842 30.7919 26.7422 31.9618 26.2898C32.8423 25.9466 34.1683 25.5406 36.6072 25.4298C39.244 25.3086 40.036 25.2844 46.7132 25.2844C53.3904 25.2844 54.1833 25.3106 56.8223 25.4298C59.2612 25.5408 60.5846 25.9498 61.4677 26.2898C62.6356 26.7422 63.4689 27.2854 64.345 28.1598C65.2211 29.0342 65.7615 29.8682 66.2161 31.0354C66.5595 31.9154 66.9658 33.2406 67.0767 35.678C67.1979 38.3154 67.2221 39.1048 67.2221 45.781C67.2221 52.4572 67.1979 53.2466 67.0767 55.884C66.9656 58.3214 66.5573 59.6462 66.2161 60.5266C65.7615 61.6938 65.2199 62.5266 64.345 63.4002C63.4701 64.2738 62.6356 64.8158 61.4677 65.2702C60.5872 65.6134 59.2612 66.0194 56.8223 66.1302C54.1855 66.2514 53.3934 66.2756 46.7132 66.2756C40.033 66.2756 39.2432 66.2514 36.6072 66.1302ZM36.4001 20.9322C33.7371 21.0534 31.9174 21.4754 30.3282 22.0934C28.6824 22.7316 27.2892 23.5878 25.897 24.977C24.5047 26.3662 23.6502 27.7608 23.0116 29.4056C22.3933 30.9948 21.971 32.8124 21.8497 35.4738C21.7265 38.1394 21.6982 38.9916 21.6982 45.7808C21.6982 52.57 21.7265 53.4222 21.8497 56.0878C21.971 58.7494 22.3933 60.5668 23.0116 62.156C23.6502 63.7998 24.5049 65.196 25.897 66.5846C27.289 67.9732 28.6824 68.8282 30.3282 69.4682C31.9204 70.0862 33.7371 70.5082 36.4001 70.6294C39.0687 70.7506 39.92 70.7808 46.7132 70.7808C53.5065 70.7808 54.3592 70.7526 57.0264 70.6294C59.6896 70.5082 61.5081 70.0862 63.0983 69.4682C64.7431 68.8282 66.1373 67.9738 67.5295 66.5846C68.9218 65.1954 69.7745 63.7998 70.4149 62.156C71.0332 60.5668 71.4575 58.7492 71.5768 56.0878C71.698 53.4202 71.7262 52.57 71.7262 45.7808C71.7262 38.9916 71.698 38.1394 71.5768 35.4738C71.4555 32.8122 71.0332 30.9938 70.4149 29.4056C69.7745 27.7618 68.9196 26.3684 67.5295 24.977C66.1395 23.5856 64.7431 22.7316 63.1003 22.0934C61.5081 21.4754 59.6894 21.0514 57.0284 20.9322C54.3612 20.811 53.5085 20.7808 46.7152 20.7808C39.922 20.7808 39.0687 20.809 36.4001 20.9322Z" fill="white"/><defs><linearGradient id="ig_adm" x1="90.9407" y1="91.5618" x2="-0.621143" y2="0" gradientUnits="userSpaceOnUse"><stop stop-color="#FBE18A"/><stop offset="0.21" stop-color="#FCBB45"/><stop offset="0.38" stop-color="#F75274"/><stop offset="0.52" stop-color="#D53692"/><stop offset="0.74" stop-color="#8F39CE"/><stop offset="1" stop-color="#5B4FE9"/></linearGradient></defs></svg></span>Instagram</label>
                                    <input type="url" id="tmm-instagram" class="tmf-input" placeholder="https://instagram.com/...">
                                </div>
                            </div>
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><span class="dsi"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 93 92" fill="none"><rect x="0.138672" width="91.5618" height="91.5618" rx="15" fill="black"/><path d="M50.7568 42.1716L69.3704 21H64.9596L48.7974 39.383L35.8887 21H21L40.5205 48.7983L21 71H25.4111L42.4788 51.5869L56.1113 71H71L50.7557 42.1716H50.7568ZM44.7152 49.0433L42.7374 46.2752L27.0005 24.2492H33.7756L46.4755 42.0249L48.4533 44.7929L64.9617 67.8986H58.1865L44.7152 49.0443V49.0433Z" fill="white"/></svg></span>X / Twitter</label>
                                    <input type="url" id="tmm-x_twitter" class="tmf-input" placeholder="https://x.com/...">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><span class="dsi"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="24" height="24" rx="4" fill="#010101"/><path d="M16.6 7.8c-.7-.5-1.3-1.3-1.5-2.3H13v9.5c0 1-.9 1.7-1.7 1.7-1 0-1.7-.8-1.7-1.7s.8-1.7 1.7-1.7c.2 0 .3 0 .5.1V11c-.2 0-.3 0-.5 0-1.9 0-3.5 1.6-3.5 3.5S9.4 18 11.3 18s3.5-1.6 3.5-3.5V9.7c.8.5 1.7.8 2.6.8V8c-.3 0-.6-.1-.8-.2z" fill="white"/></svg></span>TikTok</label>
                                    <input type="url" id="tmm-tiktok" class="tmf-input" placeholder="https://tiktok.com/...">
                                </div>
                            </div>
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><span class="dsi"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 93 93" fill="none"><rect x="1.13867" y="1" width="91.5618" height="91.5618" rx="15" fill="#006699"/><path d="M37.1339 63.4304V40.9068H29.6473V63.4304H37.1346H37.1339ZM33.3922 37.8321C36.0023 37.8321 37.6273 36.1025 37.6273 33.9411C37.5785 31.7304 36.0023 30.0491 33.4418 30.0491C30.8795 30.0491 29.2061 31.7304 29.2061 33.9409C29.2061 36.1023 30.8305 37.8319 33.3431 37.8319H33.3916L33.3922 37.8321ZM41.2777 63.4304H48.7637V50.8535C48.7637 50.1813 48.8125 49.5072 49.0103 49.0271C49.5513 47.6815 50.7831 46.2887 52.8517 46.2887C55.5599 46.2887 56.644 48.354 56.644 51.3822V63.4304H64.1297V50.516C64.1297 43.598 60.4369 40.3787 55.5115 40.3787C51.4733 40.3787 49.6998 42.6357 48.7144 44.173H48.7643V40.9075H41.2781C41.3759 43.0205 41.2775 63.4312 41.2775 63.4312L41.2777 63.4304Z" fill="white"/></svg></span>LinkedIn</label>
                                    <input type="url" id="tmm-linkedin" class="tmf-input" placeholder="https://linkedin.com/...">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><span class="dsi"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 93 93" fill="none"><rect x="1.13867" y="1" width="91.5618" height="91.5618" rx="15" fill="#FF0000"/><path fill-rule="evenodd" clip-rule="evenodd" d="M67.5615 29.2428C69.8115 29.8504 71.58 31.6234 72.1778 33.8708C73.2654 37.9495 73.2654 46.4647 73.2654 46.4647C73.2654 46.4647 73.2654 54.98 72.1778 59.0586C71.5717 61.3144 69.8032 63.0873 67.5615 63.6866C63.4932 64.7771 47.1703 64.7771 47.1703 64.7771C47.1703 64.7771 30.8557 64.7771 26.7791 63.6866C24.5291 63.079 22.7606 61.306 22.1628 59.0586C21.0752 54.98 21.0752 46.4647 21.0752 46.4647C21.0752 46.4647 21.0752 37.9495 22.1628 33.8708C22.7689 31.615 24.5374 29.8421 26.7791 29.2428C30.8557 28.1523 47.1703 28.1523 47.1703 28.1523C47.1703 28.1523 63.4932 28.1523 67.5615 29.2428ZM55.5142 46.4647L41.9561 54.314V38.6154L55.5142 46.4647Z" fill="white"/></svg></span>YouTube</label>
                                    <input type="url" id="tmm-youtube" class="tmf-input" placeholder="https://youtube.com/...">
                                </div>
                            </div>
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><span class="dsi"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 93 92" fill="none"><rect x="1.13867" width="91.5618" height="91.5618" rx="15" fill="#FF0000"/><path d="M44.2808 23.0437C36.8492 23.893 29.4439 30.0479 29.1382 38.84C28.9461 44.2083 30.435 48.2356 35.4258 49.3664C37.5915 45.4553 34.7272 44.5927 34.2818 41.7633C32.4523 30.1686 47.346 22.2615 55.14 30.3563C60.5324 35.9615 56.9826 53.206 48.2848 51.4136C39.9537 49.7017 52.3629 35.9749 45.713 33.2796C40.3074 31.0894 37.4343 39.9798 39.9974 44.396C38.4953 51.9902 35.2599 59.1464 36.5698 68.6715C40.8183 65.5158 42.2504 59.4727 43.425 53.1702C45.5601 54.4978 46.6998 55.8789 49.4244 56.0935C59.4714 56.8891 65.0822 45.8263 63.7112 35.6218C62.4929 26.5749 53.6729 21.971 44.2808 23.0437Z" fill="white"/></svg></span>Pinterest</label>
                                    <input type="url" id="tmm-pinterest" class="tmf-input" placeholder="https://pinterest.com/...">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><span class="dsi"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 93 93" fill="none"><rect x="0.138672" y="1" width="91.5618" height="91.5618" rx="15" fill="url(#msg_adm)"/><path fill-rule="evenodd" clip-rule="evenodd" d="M46.4114 21C32.0561 21 20.9307 31.317 20.9307 45.2508C20.9307 52.5396 23.9761 58.8375 28.9338 63.1887C29.3491 63.5559 29.6003 64.0639 29.6208 64.6122L29.7592 69.059C29.8054 70.4775 31.2973 71.398 32.62 70.8296L37.6752 68.6414C38.1058 68.4553 38.5826 68.4201 39.0338 68.5408C41.3563 69.1696 43.8326 69.5016 46.4114 69.5016C60.7668 69.5016 71.8922 59.1846 71.8922 45.2508C71.8922 31.317 60.7668 21 46.4114 21ZM61.7102 39.6572L54.2249 51.3072C53.0354 53.1584 50.4822 53.6211 48.698 52.3082L42.7457 47.9269C42.1971 47.5245 41.4486 47.5295 40.9051 47.9319L32.8661 53.9179C31.7946 54.7177 30.3898 53.4551 31.1127 52.3384L38.598 40.6884C39.7875 38.8372 42.3407 38.3745 44.1248 39.6874L50.0772 44.0687C50.6258 44.4711 51.3743 44.4661 51.9177 44.0637L59.9567 38.0777C61.0283 37.2779 62.433 38.5405 61.7102 39.6572Z" fill="white"/><defs><radialGradient id="msg_adm" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(15.4753 92.5593) scale(100.718 100.715)"><stop stop-color="#0099FF"/><stop offset="0.6" stop-color="#A033FF"/><stop offset="0.9" stop-color="#FF5280"/><stop offset="1" stop-color="#FF7061"/></radialGradient></defs></svg></span>Messenger</label>
                                    <input type="url" id="tmm-messenger" class="tmf-input" placeholder="https://m.me/...">
                                </div>
                            </div>
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><span class="dsi"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 93 92" fill="none"><rect x="1.13867" width="91.5618" height="91.5618" rx="15" fill="#00D95F"/><path d="M23.5068 66.8405L26.7915 54.6381C24.1425 49.8847 23.3009 44.3378 24.4211 39.0154C25.5413 33.693 28.5482 28.952 32.89 25.6624C37.2319 22.3729 42.6173 20.7554 48.0583 21.1068C53.4992 21.4582 58.6306 23.755 62.5108 27.5756C66.3911 31.3962 68.7599 36.4844 69.1826 41.9065C69.6053 47.3286 68.0535 52.7208 64.812 57.0938C61.5705 61.4668 56.8568 64.5271 51.5357 65.7133C46.2146 66.8994 40.6432 66.1318 35.8438 63.5513L23.5068 66.8405ZM36.4386 58.985L37.2016 59.4365C40.6779 61.4918 44.7382 62.3423 48.7498 61.8555C52.7613 61.3687 56.4987 59.5719 59.3796 56.7452C62.2605 53.9185 64.123 50.2206 64.6769 46.2279C65.2308 42.2351 64.445 38.1717 62.4419 34.6709C60.4388 31.1701 57.331 28.4285 53.6027 26.8734C49.8745 25.3184 45.7352 25.0372 41.8299 26.0736C37.9247 27.11 34.4729 29.4059 32.0124 32.6035C29.5519 35.801 28.2209 39.7206 28.2269 43.7514C28.2237 47.0937 29.1503 50.3712 30.9038 53.2192L31.3823 54.0061L29.546 60.8167L36.4386 58.985Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M54.9566 46.8847C54.5093 46.5249 53.9856 46.2716 53.4254 46.1442C52.8651 46.0168 52.2831 46.0186 51.7236 46.1495C50.8831 46.4977 50.3399 47.8134 49.7968 48.4713C49.6823 48.629 49.514 48.7396 49.3235 48.7823C49.133 48.8251 48.9335 48.797 48.7623 48.7034C45.6849 47.5012 43.1055 45.2965 41.4429 42.4475C41.3011 42.2697 41.2339 42.044 41.2557 41.8178C41.2774 41.5916 41.3862 41.3827 41.5593 41.235C42.165 40.6368 42.6098 39.8959 42.8524 39.0809C42.9063 38.1818 42.6998 37.2863 42.2576 36.5011C41.9157 35.4002 41.265 34.42 40.3825 33.6762C39.9273 33.472 39.4225 33.4036 38.9292 33.4791C38.4359 33.5546 37.975 33.7709 37.6021 34.1019C36.9548 34.6589 36.4411 35.3537 36.0987 36.135C35.7562 36.9163 35.5939 37.7643 35.6236 38.6165C35.6256 39.0951 35.6864 39.5716 35.8046 40.0354C36.1049 41.1497 36.5667 42.2144 37.1754 43.1956C37.6145 43.9473 38.0937 44.6749 38.6108 45.3755C40.2914 47.6767 42.4038 49.6305 44.831 51.1284C46.049 51.8897 47.3507 52.5086 48.7105 52.973C50.1231 53.6117 51.6827 53.8568 53.2237 53.6824C54.1018 53.5499 54.9337 53.2041 55.6462 52.6755C56.3588 52.1469 56.9302 51.4518 57.3102 50.6512C57.5334 50.1675 57.6012 49.6269 57.5042 49.1033C57.2714 48.0327 55.836 47.4007 54.9566 46.8847Z" fill="white"/></svg></span>WhatsApp</label>
                                    <input type="text" id="tmm-whatsapp" class="tmf-input" placeholder="+84 9x...">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><span class="dsi"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 92 93" fill="none"><rect x="0.138672" y="1" width="91.5618" height="91.5618" rx="15" fill="#34AADF"/><path d="M25.0881 43.5652C25.0881 43.5652 43.716 35.7194 50.1765 32.9567C52.6532 31.8518 61.0518 28.3155 61.0518 28.3155C61.0518 28.3155 64.9282 26.7685 64.6052 30.5256C64.4974 32.0728 63.6361 37.4874 62.7747 43.3442C61.4825 51.6322 60.0827 60.6935 60.0827 60.6935C60.0827 60.6935 59.8674 63.2352 58.0369 63.6772C56.2065 64.1192 53.1914 62.1302 52.6532 61.6881C52.2223 61.3566 44.5774 56.3838 41.7778 53.9527C41.0241 53.2897 40.1627 51.9637 41.8854 50.4166C45.7618 46.7699 50.3919 42.2392 53.1914 39.3661C54.4836 38.04 55.7757 34.9459 50.3919 38.703C42.7469 44.1178 35.2096 49.201 35.2096 49.201C35.2096 49.201 33.4868 50.306 30.2565 49.3115C27.0261 48.317 23.2575 46.9909 23.2575 46.9909C23.2575 46.9909 20.6734 45.3334 25.0881 43.5652Z" fill="white"/></svg></span>Telegram</label>
                                    <input type="url" id="tmm-telegram" class="tmf-input" placeholder="https://t.me/...">
                                </div>
                            </div>
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><span class="dsi"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/Icon_of_Zalo.svg/250px-Icon_of_Zalo.svg.png" alt="Zalo" width="24" height="24" style="border-radius:5px;object-fit:contain;"></span>Zalo</label>
                                    <input type="text" id="tmm-zalo" class="tmf-input" placeholder="SĐT hoặc link Zalo">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><span class="dsi"></span>tiktok</label>
                                    <input type="viber" id="tmm-Viber" class="tmf-input" placeholder="text">
                                </div>
                            </div>
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><span class="dsi"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 92 92" fill="none"><rect x="0.138672" width="91.5618" height="91.5618" rx="15" fill="#00B7F0"/><path fill-rule="evenodd" clip-rule="evenodd" d="M21.1081 36.9185C21.2825 39.0304 21.9143 41.0712 22.951 42.9027C22.7897 44.0027 22.7082 45.1152 22.7082 46.2319C22.7082 52.4582 27.2879 57.9418 33.3975 59.5083C35.3155 60.0097 37.3406 60.0885 39.2947 59.7382C41.2488 59.3879 43.0821 58.6182 44.6686 57.4886C46.255 56.359 47.5516 54.8993 48.4574 53.2218C49.3633 51.5444 49.8541 49.6934 49.8926 47.8069C49.931 45.9204 49.5163 44.0522 48.6809 42.3418C47.8454 40.6313 46.6128 39.1236 45.0784 37.9348C43.5441 36.746 41.7509 35.9086 39.8385 35.4885C37.926 35.0684 35.9437 35.0763 34.0349 35.5116C32.1261 35.9469 30.3392 36.7987 28.8145 38.0028C27.2898 39.2069 26.0682 40.7302 25.2449 42.4594C24.4215 44.1887 24.0189 46.0783 24.0686 47.9843C24.1183 49.8903 24.6189 51.7568 25.5315 53.4409C24.1979 51.1996 23.4065 48.6449 23.2346 46.0108C23.0628 43.3767 23.5154 40.7386 24.5562 38.3228C25.5971 35.9069 27.1962 33.7837 29.2241 32.1164C31.252 30.4491 33.6498 29.2861 36.2213 28.7239C33.9026 27.5065 31.2953 26.9785 28.6913 27.1981C26.0872 27.4177 23.5848 28.3759 21.4629 29.9683C21.2265 32.2268 21.0547 33.9185 21.1081 36.9185ZM37.0659 43.0286C37.6308 43.9944 38.4114 44.8169 39.3468 45.4319C40.5861 46.2419 41.9036 46.9259 43.2795 47.4736C44.8179 48.1158 46.0114 48.6602 46.8601 49.1069C47.5778 49.4595 48.2292 49.9331 48.7856 50.5069C49.2067 50.9532 49.4407 51.5436 49.4397 52.1569C49.4578 52.5894 49.3725 53.02 49.1908 53.413C49.0092 53.806 48.7364 54.1501 48.3952 54.4169C47.4835 55.0297 46.3928 55.3201 45.2967 55.2419C44.6769 55.2483 44.0584 55.1846 43.453 55.0519C42.962 54.9442 42.4818 54.7925 42.0181 54.5986C41.6121 54.423 41.0576 54.1597 40.3546 53.8086C39.7892 53.5073 39.1599 53.3456 38.5192 53.3369C37.9442 53.3134 37.3821 53.5115 36.9491 53.8902C36.5161 54.2689 36.3272 54.9627 36.3401 55.4236C36.3272 55.9627 36.49 56.4915 36.8039 56.9302C37.1821 57.409 37.6711 57.7888 38.2289 58.0369C40.4105 58.9814 42.7753 59.4283 45.1516 59.3452C46.8808 59.38 48.6018 59.0998 50.2305 58.5186C51.5819 58.0393 52.7587 57.1668 53.6093 56.0136C54.4446 54.7955 54.8679 53.3425 54.8173 51.8669C54.8497 50.6618 54.5477 49.4712 53.9447 48.4269C53.3322 47.4333 52.5071 46.5875 51.5286 45.9502C50.2897 45.1519 48.9753 44.477 47.6043 43.9352C47.4577 43.8635 47.306 43.8028 47.1504 43.7536C45.7455 43.1736 44.6888 42.708 43.9803 42.3569C43.3477 42.0607 42.7748 41.6511 42.29 41.1486C41.8821 40.7208 41.6571 40.151 41.6627 39.5602C41.6431 39.1102 41.7449 38.6633 41.9574 38.266C42.1699 37.8688 42.4853 37.5358 42.8707 37.3019C43.7839 36.7581 44.8349 36.4895 45.8974 36.5286C46.7125 36.52 47.5251 36.6209 48.3134 36.8286C49.1697 37.0752 50.0077 37.3815 50.8212 37.7452C51.5102 37.9789 51.874 37.9819 52.6455 37.8383C53.1095 37.5845 53.2906 37.3919 53.7271 36.6721C53.8238 36.4059 53.8672 36.1232 53.8546 35.8402C53.8631 35.2956 53.6727 34.7665 53.319 34.3519C52.8388 33.848 52.2433 33.4683 51.5837 33.2452C50.8653 32.9817 50.1225 32.7902 49.3662 32.6736C48.3096 32.5004 47.24 32.4185 46.1694 32.4286C44.5329 32.4189 42.9071 32.6924 41.364 33.2369C39.9453 33.7153 38.6787 34.5607 37.6933 35.6869C36.745 36.8084 36.2437 38.2395 36.285 39.7069C36.2514 40.8631 36.5205 42.0081 37.0659 43.0286Z" fill="white"/></svg></span>Skype</label>
                                    <input type="text" id="tmm-skype" class="tmf-input" placeholder="Skype ID">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><span class="dsi"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 93 93" fill="none"><rect x="1.13867" y="1" width="91.5618" height="91.5618" rx="15" fill="#51C332"/><path d="M55.8615 36.5403C56.8503 36.5403 57.8161 36.6225 58.769 36.74C57.0463 29.1747 49.1004 23.4524 39.5457 23.4524C28.7309 23.4524 19.9658 30.7781 19.9658 39.8123C19.9658 45.021 22.8964 49.6421 27.4419 52.6322L24.8606 57.8086L31.8926 54.7884C33.4005 55.3254 34.9674 55.7676 36.6411 55.9734C36.4124 54.975 36.2824 53.9515 36.2824 52.901C36.2824 43.8797 45.0634 36.5403 55.8615 36.5403ZM46.0722 30.8139C47.4235 30.8139 48.5194 31.9132 48.5194 33.2682C48.5194 34.6237 47.4236 35.7222 46.0722 35.7222C44.7201 35.7222 43.6247 34.6237 43.6247 33.2682C43.6247 31.9131 44.7201 30.8139 46.0722 30.8139ZM33.0189 35.7222C31.6674 35.7222 30.5715 34.6237 30.5715 33.2682C30.5715 31.9132 31.6675 30.8139 33.0189 30.8139C34.3703 30.8139 35.4664 31.9132 35.4664 33.2682C35.4663 34.6237 34.3702 35.7222 33.0189 35.7222Z" fill="white"/><path d="M72.1779 52.9008C72.1779 45.6724 64.8709 39.8123 55.8615 39.8123C46.8517 39.8123 39.5457 45.6724 39.5457 52.9008C39.5457 60.1287 46.8517 65.9889 55.8615 65.9889C57.3432 65.9889 58.7525 65.7794 60.12 65.4821L68.9148 69.2608L65.8731 63.1654C69.6849 60.7698 72.1779 57.0859 72.1779 52.9008ZM50.9668 52.0827C49.6154 52.0827 48.5193 50.9838 48.5193 49.6281C48.5193 48.2731 49.6153 47.1746 50.9668 47.1746C52.3186 47.1746 53.4141 48.2736 53.4141 49.6281C53.4141 50.9839 52.3184 52.0827 50.9668 52.0827ZM60.7564 52.0827C59.4043 52.0827 58.3091 50.9838 58.3091 49.6281C58.3091 48.2731 59.4042 47.1746 60.7564 47.1746C62.1083 47.1746 63.2039 48.2736 63.2039 49.6281C63.2039 50.9839 62.1083 52.0827 60.7564 52.0827Z" fill="white"/></svg></span>WeChat</label>
                                    <input type="text" id="tmm-wechat" class="tmf-input" placeholder="WeChat ID">
                                </div>
                            </div>
                            <div class="tmf-field">
                                <label class="tmf-label"><span class="dsi"><svg width="24" height="24" viewBox="0 0 93 92" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="93" height="92" rx="15" fill="#06C755"/><path d="M78 40.2C78 25.5 63.2 13.5 46.3 13.5 29.4 13.5 14.6 25.5 14.6 40.2c0 13.2 11.7 24.3 27.6 26.4 1.1.2 2.5.7 2.9 1.7.4 1 .3 2.4 0 3.3l-.5 2.7c-.1 1-.9 3.8 1.5 2.7 2.4-1.1 13.3-7.8 18.2-13.4C69.1 59 78 50.2 78 40.2zM35.8 48.2H29v-14h3.1v11h3.7v3zm4.6 0h-3.1V34.2h3.1v14zm14 0h-3l-5.3-9.4v9.4H43V34.2h3l5.3 9.3v-9.3h3.1v14zm12.6-11h-6.2v2.5h6.2v3.1h-6.2v2.5h6.2v3H57.7V34.2H67v3z" fill="white"/></svg></span>Line App</label>
                                <input type="text" id="tmm-line_app" class="tmf-input" placeholder="Line ID">
                            </div>
                        </div>
                    </div>


                    <div class="tmf-section" style="padding:16px; background:var(--surface-2); border-radius:12px; border:1px solid var(--border);">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <div style="font-size:12px; font-weight:700; color:var(--text);">Thư viện ảnh Khác (Nhiều ảnh)</div>
                            <button type="button" id="tmm-gallery-btn" class="tm-btn tm-btn-solid tm-btn-sm" style="background:var(--text); color:var(--surface);">
                                <svg viewBox="0 0 14 14"><rect x="1" y="3" width="12" height="9" rx="1.5"/><circle cx="5" cy="7" r="1.5"/><path d="M1 11l3-3 2.5 2.5 2-2L12 11"/></svg>
                                Mở thư viện chọn ảnh
                            </button>
                        </div>
                        <textarea id="tmm-gallery" class="tmf-textarea" rows="2" style="display:none;"></textarea>
                        
                        <!-- Gallery Preview Bar -->
                        <div id="tmm-gallery-preview" style="display:flex; flex-wrap:wrap; gap:8px; padding-top:4px;"></div>
                    </div>
                </div>

            </div><!-- /tmm-body -->

            <!-- Footer -->
            <div class="tmm-foot">
                <button id="tmm-save" class="tm-btn tm-btn-solid">
                    <svg viewBox="0 0 14 14"><polyline points="2,7 5.5,10.5 12,3"/></svg>
                    Lưu thay đổi
                </button>
                <button id="tmm-delete" class="tm-btn tm-btn-danger">
                    <svg viewBox="0 0 14 14"><polyline points="1,3 13,3"/><path d="M5,3V1h4v2"/><path d="M2,3l1,9h8l1-9"/></svg>
                    Xóa
                </button>
                <div class="tmm-foot-gap"></div>
                <a id="tmm-full-edit" href="#" class="tm-btn tm-btn-outline tm-btn-sm" target="_blank" title="Mở trang chỉnh sửa đầy đủ">
                    <svg viewBox="0 0 14 14"><path d="M8 2h4v4M12 2L6 8M5 3H2v9h9V9"/></svg>
                    Đầy đủ
                </a>
            </div>

        </div><!-- /tmm-dr -->

        <script>
        window.tmMembers = <?php echo wp_json_encode( array_map( function($m) {
            return [
                'id'         => (int)$m['id'],
                'name'       => $m['name'],
                'role'       => $m['role'] ?? '',
                'department' => $m['department'] ?? '',
                'bio'        => $m['bio'] ?? '',
                'photo_url'  => $m['photo_url'] ?? '',
                'is_active'  => (int)$m['is_active'],
                'phone'      => $m['phone'] ?? '',
                'email'      => $m['email'] ?? '',
                'facebook'   => $m['facebook'] ?? '',
                'instagram'  => $m['instagram'] ?? '',
                'tiktok'     => $m['tiktok'] ?? '',
                'x_twitter'  => $m['x_twitter'] ?? '',
                'linkedin'   => $m['linkedin'] ?? '',
                'youtube'    => $m['youtube'] ?? '',
                'pinterest'  => $m['pinterest'] ?? '',
                'messenger'  => $m['messenger'] ?? '',
                'telegram'   => $m['telegram'] ?? '',
                'whatsapp'   => $m['whatsapp'] ?? '',
                'zalo'       => $m['zalo'] ?? '',
                'viber'      => $m['viber'] ?? '',
                'skype'      => $m['skype'] ?? '',
                'line_app'   => $m['line_app'] ?? '',
                'wechat'     => $m['wechat'] ?? '',
                'gallery_urls'=> $m['gallery_urls'] ?? ''
            ];
        }, $members ) ); ?>;
        </script>

        <?php
    }

    /* ── Form View (Add / Edit) ───────────────────────────────────── */

    private function render_form( ?array $m, string $nonce, array $all_members = [] ) {
        $is_edit  = ! is_null( $m );
        $list_url = admin_url( 'admin.php?page=bacera-team' );
        $save_label = $is_edit ? 'Lưu thay đổi' : 'Thêm thành viên';

        $departments_preset = [
            'Ban Giám đốc',
            'Sales & Business',
            'Marketing & Creative',
            'Craft & Production',
            'Accounting',
            'HR & Admin',
        ];
        $cur_dept = $m['department'] ?? '';
        ?>

        <!-- ══ STICKY HEADER ══ -->
        <div class="tmf-header">
            <div class="tmf-hd-info">
                <div class="tmf-hd-icon">
                    <?php if ($is_edit): ?>
                    <svg viewBox="0 0 16 16"><path d="M11 2l3 3-8 8H3v-3L11 2z"/></svg>
                    <?php else: ?>
                    <svg viewBox="0 0 16 16"><circle cx="8" cy="6" r="3"/><path d="M14 14c0-3.31-2.69-6-6-6S2 10.69 2 14"/><path d="M11 3v5M13.5 5.5h-5" stroke-linecap="round"/></svg>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="tmf-hd-title">
                        <?php echo $is_edit ? 'Chỉnh sửa: ' . esc_html($m['name']) : 'Thêm thành viên mới'; ?>
                    </div>
                    <div class="tmf-hd-sub">
                        <?php echo $is_edit ? 'Cập nhật thông tin thành viên' : 'Điền đầy đủ để hiển thị trên trang Our Team'; ?>
                    </div>
                </div>
            </div>
            <div class="tmf-hd-acts">
                <a href="<?php echo esc_url($list_url); ?>" class="tm-btn tm-btn-outline">
                    <svg viewBox="0 0 14 14"><path d="M9 11L5 7l4-4" stroke-linejoin="round"/></svg>
                    Danh sách
                </a>
                <?php if ($is_edit): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=bacera-team&action=add')); ?>"
                   class="tm-btn tm-btn-outline">
                    <svg viewBox="0 0 14 14"><path d="M7 2v10M2 7h10" stroke-linecap="round"/></svg>
                    Thêm mới
                </a>
                <?php endif; ?>
                <button type="submit" form="tm-member-form" id="tm-submit-btn" class="tm-btn tm-btn-solid">
                    <svg viewBox="0 0 14 14"><polyline points="2,7 5.5,10.5 12,3"/></svg>
                    <?php echo $save_label; ?>
                </button>
            </div>
        </div>

        <!-- ══ FORM ══ -->
        <form id="tm-member-form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="bacera_team_save">
            <input type="hidden" name="_nonce" value="<?php echo esc_attr($nonce); ?>">
            <input type="hidden" name="id"     value="<?php echo esc_attr($m['id'] ?? 0); ?>">

            <div class="tmf-body">

                <!-- LEFT: Main fields -->
                <div class="tmf-main">

                    <!-- Section 1: Identity -->
                    <div class="tmf-section">
                        <div class="tmf-sec-head">
                            <svg viewBox="0 0 13 13"><circle cx="6.5" cy="4" r="2.5"/><path d="M1 12c0-3 11-3 11 0"/></svg>
                            Thông tin cá nhân
                        </div>
                        <div class="tmf-sec-body">

                            <!-- Name -->
                            <div class="tmf-field">
                                <label class="tmf-label" for="tm-name">
                                    <svg viewBox="0 0 12 12"><rect x="1" y="1" width="10" height="10" rx="1.5"/><path d="M3.5 4.5h5M3.5 7.5h3"/></svg>
                                    Họ &amp; Tên <span class="tmf-req">*</span>
                                </label>
                                <input type="text" id="tm-name" name="name" class="tmf-input"
                                       value="<?php echo esc_attr($m['name'] ?? ''); ?>"
                                       placeholder="Nguyễn Văn A" required autocomplete="off">
                            </div>

                            <!-- Role + Department -->
                            <div class="tmf-row2">
                                <div class="tmf-field">
                                    <label class="tmf-label" for="tm-role">
                                        <svg viewBox="0 0 12 12"><rect x="1" y="3" width="10" height="7" rx="1.5"/><path d="M4 3V2a2 2 0 014 0v1"/></svg>
                                        Chức danh
                                    </label>
                                    <input type="text" id="tm-role" name="role" class="tmf-input"
                                           value="<?php echo esc_attr($m['role'] ?? ''); ?>"
                                           placeholder="CEO, Marketing Manager…">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label" for="tm-dept">
                                        <svg viewBox="0 0 12 12"><rect x="1" y="5" width="3" height="6" rx="1"/><rect x="4.5" y="3" width="3" height="8" rx="1"/><rect x="8" y="1" width="3" height="10" rx="1"/></svg>
                                        Phòng ban
                                    </label>
                                    <select id="tm-dept" name="department" class="tmf-select">
                                        <option value="">— Chọn phòng ban —</option>
                                        <?php foreach ($departments_preset as $d): ?>
                                        <option value="<?php echo esc_attr($d); ?>"
                                            <?php selected($cur_dept, $d); ?>>
                                            <?php echo esc_html($d); ?>
                                        </option>
                                        <?php endforeach; ?>
                                        <?php if ($cur_dept && !in_array($cur_dept, $departments_preset)): ?>
                                        <option value="<?php echo esc_attr($cur_dept); ?>" selected>
                                            <?php echo esc_html($cur_dept); ?>
                                        </option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Section 2: Bio -->
                    <div class="tmf-section">
                        <div class="tmf-sec-head">
                            <svg viewBox="0 0 13 13"><rect x="1" y="1" width="11" height="11" rx="1.5"/><path d="M3.5 4.5h6M3.5 7h6M3.5 9.5h4"/></svg>
                            Tiểu sử
                        </div>
                        <div class="tmf-sec-body">
                            <div class="tmf-field">
                                <label class="tmf-label" for="tm-bio">Nội dung giới thiệu</label>
                                <textarea id="tm-bio" name="bio" class="tmf-textarea" rows="4"
                                          placeholder="Vài dòng giới thiệu, sở trường, châm ngôn nghề nghiệp…"
                                          ><?php echo esc_textarea($m['bio'] ?? ''); ?></textarea>
                                <span class="tmf-hint">Xuống dòng = tạo nhiều đoạn văn trên trang cá nhân.</span>
                            </div>
                        </div>
                    </div>

                    <div class="tmf-section">
                        <div class="tmf-sec-head">
                            <svg viewBox="0 0 14 14"><path d="M7 13.5a6.5 6.5 0 1 0 0-13 6.5 6.5 0 0 0 0 13z"/><path d="M3.5 3.5v7h7v-7h-7z" fill="none" stroke="currentColor"/></svg>
                            Liên hệ & Mạng xã hội 
                            <span style="font-weight:400;font-size:11px;color:var(--text-3);margin-left:auto;">(Tùy chọn)</span>
                        </div>
                        <div class="tmf-sec-body">
                            <style>
                                .social-icon-label { display:flex; align-items:center; gap:8px; font-size:12px; font-weight:600; color:var(--text); margin-bottom:6px; }
                                .social-icon-wrapper { width:32px; height:32px; flex-shrink:0; display:flex; items-center; justify-content:center; border-radius:8px; background:#fff; box-shadow:0 2px 5px rgba(0,0,0,0.05); border:1px solid var(--border); }
                                .social-icon-wrapper svg { width:20px; height:20px; }
                            </style>
                            <div class="tmf-row2">
                                <div class="tmf-field">
                                    <label class="social-icon-label">
                                        <div class="social-icon-wrapper">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="#6b5344" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 .84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                        </div>
                                        Số điện thoại
                                    </label>
                                    <input type="text" name="phone" class="tmf-input" placeholder="090..." value="<?php echo esc_attr($m['phone'] ?? ''); ?>">
                                </div>
                                <div class="tmf-field">
                                    <label class="social-icon-label">
                                        <div class="social-icon-wrapper">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="#6b5344" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                        </div>
                                        Email
                                    </label>
                                    <input type="email" name="email" class="tmf-input" placeholder="admin@domain.com" value="<?php echo esc_attr($m['email'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="tmf-row2" style="margin-top:16px;">
                                <div class="tmf-field">
                                    <label class="social-icon-label">
                                        <div class="social-icon-wrapper border-none">
                                            <svg viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M46.4927 38.6403L47.7973 30.3588H39.7611V24.9759C39.7611 22.7114 40.883 20.4987 44.4706 20.4987H48.1756V13.4465C46.018 13.1028 43.8378 12.9168 41.6527 12.8901C35.0385 12.8901 30.7204 16.8626 30.7204 24.0442V30.3588H23.3887V38.6403H30.7204V58.671H39.7611V38.6403H46.4927Z" fill="#337FFF"/></svg>
                                        </div>
                                        Facebook
                                    </label>
                                    <input type="url" name="facebook" class="tmf-input" placeholder="https://facebook.com/..." value="<?php echo esc_attr($m['facebook'] ?? ''); ?>">
                                </div>
                                <div class="tmf-field">
                                    <label class="social-icon-label">
                                        <div class="social-icon-wrapper border-none">
                                            <svg viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M27.4456 35.7808C27.4456 31.1786 31.1776 27.4468 35.7826 27.4468C40.3875 27.4468 44.1216 31.1786 44.1216 35.7808C44.1216 40.383 40.3875 44.1148 35.7826 44.1148C31.1776 44.1148 27.4456 40.383 27.4456 35.7808ZM22.9377 35.7808C22.9377 42.8708 28.6883 48.618 35.7826 48.618C42.8768 48.618 48.6275 42.8708 48.6275 35.7808C48.6275 28.6908 42.8768 22.9436 35.7826 22.9436C28.6883 22.9436 22.9377 28.6908 22.9377 35.7808ZM46.1342 22.4346C46.1339 23.0279 46.3098 23.608 46.6394 24.1015C46.9691 24.595 47.4377 24.9797 47.9861 25.2069C48.5346 25.4342 49.1381 25.4939 49.7204 25.3784C50.3028 25.2628 50.8378 24.9773 51.2577 24.5579C51.6777 24.1385 51.9638 23.6041 52.0799 23.0222C52.1959 22.4403 52.1367 21.8371 51.9097 21.2888C51.6828 20.7406 51.2982 20.2719 50.8047 19.942C50.3112 19.6122 49.7309 19.436 49.1372 19.4358H49.136C48.3402 19.4361 47.5771 19.7522 47.0142 20.3144C46.4514 20.8767 46.1349 21.6392 46.1342 22.4346ZM25.6765 56.1302C23.2377 56.0192 21.9121 55.6132 21.0311 55.2702C19.8632 54.8158 19.0299 54.2746 18.1538 53.4002C17.2777 52.5258 16.7354 51.6938 16.2827 50.5266C15.9393 49.6466 15.533 48.3214 15.4222 45.884C15.3009 43.2488 15.2767 42.4572 15.2767 35.781C15.2767 29.1048 15.3029 28.3154 15.4222 25.678C15.5332 23.2406 15.9425 21.918 16.2827 21.0354C16.7374 19.8682 17.2789 19.0354 18.1538 18.1598C19.0287 17.2842 19.8612 16.7422 21.0311 16.2898C21.9117 15.9466 23.2377 15.5406 25.6765 15.4298C28.3133 15.3086 29.1054 15.2844 35.7826 15.2844C42.4598 15.2844 43.2527 15.3106 45.8916 15.4298C48.3305 15.5408 49.6539 15.9498 50.537 16.2898C51.7049 16.7422 52.5382 17.2854 53.4144 18.1598C54.2905 19.0342 54.8308 19.8682 55.2855 21.0354C55.6289 21.9154 56.0351 23.2406 56.146 25.678C56.2673 28.3154 56.2915 29.1048 56.2915 35.781C56.2915 42.4572 56.2673 43.2466 56.146 45.884C56.0349 48.3214 55.6267 49.6462 55.2855 50.5266C54.8308 51.6938 54.2893 52.5266 53.4144 53.4002C52.5394 54.2738 51.7049 54.8158 50.537 55.2702C49.6565 55.6134 48.3305 56.0194 45.8916 56.1302C43.2549 56.2514 42.4628 56.2756 35.7826 56.2756C29.1024 56.2756 28.3125 56.2514 25.6765 56.1302ZM25.4694 10.9322C22.8064 11.0534 20.9867 11.4754 19.3976 12.0934C17.7518 12.7316 16.3585 13.5878 14.9663 14.977C13.5741 16.3662 12.7195 17.7608 12.081 19.4056C11.4626 20.9948 11.0403 22.8124 10.9191 25.4738C10.7958 28.1394 10.7676 28.9916 10.7676 35.7808C10.7676 42.57 10.7958 43.4222 10.9191 46.0878C11.0403 48.7494 11.4626 50.5668 12.081 52.156C12.7195 53.7998 13.5743 55.196 14.9663 56.5846C16.3583 57.9732 17.7518 58.8282 19.3976 59.4682C20.9897 60.0862 22.8064 60.5082 25.4694 60.6294C28.138 60.7506 28.9893 60.7808 35.7826 60.7808C42.5759 60.7808 43.4286 60.7526 46.0958 60.6294C48.759 60.5082 50.5774 60.0862 52.1676 59.4682C53.8124 58.8282 55.2066 57.9738 56.5989 56.5846C57.9911 55.1954 58.8438 53.7998 59.4842 52.156C60.1026 50.5668 60.5268 48.7492 60.6461 46.0878C60.7674 43.4202 60.7956 42.57 60.7956 35.7808C60.7956 28.9916 60.7674 28.1394 60.6461 25.4738C60.5248 22.8122 60.1026 20.9938 59.4842 19.4056C58.8438 17.7618 57.9889 16.3684 56.5989 14.977C55.2088 13.5856 53.8124 12.7316 52.1696 12.0934C50.5775 11.4754 48.7588 11.0514 46.0978 10.9322C43.4306 10.811 42.5779 10.7808 35.7846 10.7808C28.9913 10.7808 28.138 10.809 25.4694 10.9322Z" fill="url(#ig_adm1)"/><defs><radialGradient id="ig_adm1" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(17.4144 61.017) scale(65.31 65.2708)"><stop offset="0.09" stop-color="#FA8F21"/><stop offset="0.78" stop-color="#D82D7E"/></radialGradient></defs></svg>
                                        </div>
                                        Instagram
                                    </label>
                                    <input type="url" name="instagram" class="tmf-input" placeholder="https://instagram.com/..." value="<?php echo esc_attr($m['instagram'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="tmf-row2" style="margin-top:16px;">
                                <div class="tmf-field">
                                    <label class="social-icon-label">
                                        <div class="social-icon-wrapper border-none">
                                            <svg viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M40.7568 32.1716L59.3704 11H54.9596L38.7974 29.383L25.8887 11H11L30.5205 38.7983L11 61H15.4111L32.4788 41.5869L46.1113 61H61L40.7557 32.1716H40.7568ZM34.7152 39.0433L32.7374 36.2752L17.0005 14.2492H23.7756L36.4755 32.0249L38.4533 34.7929L54.9617 57.8986H48.1865L34.7152 39.0443V39.0433Z" fill="black"/></svg>
                                        </div>
                                        X / Twitter
                                    </label>
                                    <input type="url" name="x_twitter" class="tmf-input" placeholder="https://x.com/..." value="<?php echo esc_attr($m['x_twitter'] ?? ''); ?>">
                                </div>
                                <div class="tmf-field">
                                    <label class="social-icon-label">
                                        <div class="social-icon-wrapper border-none">
                                            <svg viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M45.6721 29.4285C48.7387 31.6085 52.4112 32.7733 56.1737 32.7592V25.3024C55.434 25.3045 54.6963 25.2253 53.9739 25.0663V31.0068C50.203 31.0135 46.5252 29.8354 43.4599 27.6389V42.9749C43.4507 45.4914 42.7606 47.9585 41.4628 50.1146C40.165 52.2706 38.3079 54.0353 36.0885 55.2215C33.8691 56.4076 31.37 56.9711 28.8563 56.852C26.3426 56.733 23.9079 55.9359 21.8105 54.5453C23.7506 56.5082 26.2295 57.8513 28.9333 58.4044C31.6372 58.9576 34.4444 58.6959 36.9994 57.6526C39.5545 56.6093 41.7425 54.8312 43.2864 52.5436C44.8302 50.256 45.6605 47.5616 45.6721 44.8018V29.4285ZM48.3938 21.8226C46.8343 20.1323 45.8775 17.9739 45.6721 15.6832V14.7139H43.5842C43.8423 16.1699 44.4039 17.5553 45.2326 18.78C46.0612 20.0048 47.1383 21.0414 48.3938 21.8226ZM26.645 48.642C25.9213 47.6957 25.4779 46.5653 25.365 45.3793C25.2522 44.1934 25.4746 42.9996 26.0068 41.9338C26.5391 40.8681 27.3598 39.9731 28.3757 39.3508C29.3915 38.7285 30.5616 38.4039 31.7529 38.4139C32.4106 38.4137 33.0644 38.5143 33.6916 38.7121V31.0068C32.9584 30.9097 32.2189 30.8682 31.4794 30.8826V36.8728C29.9522 36.39 28.2992 36.4998 26.8492 37.1803C25.3992 37.8608 24.2585 39.0621 23.6539 40.5454C23.0494 42.0286 23.0252 43.6851 23.5864 45.1853C24.1475 46.6855 25.2527 47.9196 26.6823 48.642H26.645Z" fill="#EE1D52"/><path fill-rule="evenodd" clip-rule="evenodd" d="M43.4589 27.5892C46.5241 29.7857 50.2019 30.9638 53.9729 30.9571V25.0166C51.8243 24.5623 49.8726 23.4452 48.3927 21.8226C47.1372 21.0414 46.0601 20.0048 45.2315 18.78C44.4029 17.5553 43.8412 16.1699 43.5831 14.7139H38.09V44.8018C38.0849 46.1336 37.6629 47.4304 36.8831 48.51C36.1034 49.5897 35.0051 50.3981 33.7425 50.8217C32.4798 51.2453 31.1162 51.2629 29.8431 50.872C28.57 50.4811 27.4512 49.7012 26.6439 48.642C25.3645 47.9965 24.3399 46.9387 23.7354 45.6394C23.1309 44.3401 22.9818 42.875 23.3121 41.4805C23.6424 40.0861 24.4329 38.8435 25.556 37.9535C26.6791 37.0634 28.0693 36.5776 29.5023 36.5745C30.1599 36.5766 30.8134 36.6772 31.4411 36.8728V30.8826C28.7288 30.9477 26.0946 31.8033 23.8617 33.3444C21.6289 34.8855 19.8946 37.0451 18.8717 39.5579C17.8489 42.0708 17.5821 44.8276 18.1039 47.49C18.6258 50.1524 19.9137 52.6045 21.8095 54.5453C23.9073 55.9459 26.3458 56.7512 28.8651 56.8755C31.3845 56.9997 33.8904 56.4383 36.1158 55.2509C38.3413 54.0636 40.2031 52.2948 41.5027 50.133C42.8024 47.9712 43.4913 45.4973 43.4962 42.9749L43.4589 27.5892Z" fill="black"/><path fill-rule="evenodd" clip-rule="evenodd" d="M53.9736 25.0161V23.4129C52.0005 23.4213 50.0655 22.8696 48.3934 21.8221C49.8695 23.4493 51.8229 24.5674 53.9736 25.0161ZM43.5838 14.7134C43.5838 14.4275 43.4968 14.1292 43.4596 13.8434V12.874H35.8785V42.9744C35.872 44.6598 35.197 46.2738 34.0017 47.4621C32.8064 48.6504 31.1885 49.3159 29.503 49.3126C28.5106 49.3176 27.5311 49.0876 26.6446 48.6415C27.4519 49.7007 28.5707 50.4805 29.8438 50.8715C31.1169 51.2624 32.4805 51.2448 33.7432 50.8212C35.0058 50.3976 36.1041 49.5892 36.8838 48.5095C37.6636 47.4298 38.0856 46.1331 38.0907 44.8013V14.7134H43.5838ZM31.4418 30.8696V29.167C28.3222 28.7432 25.1511 29.3885 22.4453 30.9977C19.7394 32.6069 17.6584 35.0851 16.5413 38.0284C15.4242 40.9718 15.337 44.2067 16.2938 47.206C17.2506 50.2053 19.195 52.792 21.8102 54.5448C19.9287 52.5995 18.6545 50.1484 18.1433 47.4908C17.6321 44.8333 17.906 42.0844 18.9315 39.5799C19.957 37.0755 21.6897 34.924 23.918 33.3882C26.1463 31.8524 28.7736 30.9988 31.4791 30.9318L31.4418 30.8696Z" fill="#69C9D0"/></svg>
                                        </div>
                                        TikTok
                                    </label>
                                    <input type="url" name="tiktok" class="tmf-input" placeholder="https://tiktok.com/..." value="<?php echo esc_attr($m['tiktok'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="tmf-row2" style="margin-top:16px;">
                                <div class="tmf-field">
                                    <label class="social-icon-label">
                                        <div class="social-icon-wrapper border-none">
                                            <svg viewBox="0 0 71 72" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.5762 56.8405L15.8608 44.6381C... Z" fill="#00D95F"/><path d="M12.5762 56.8405L15.8608 44.6381C13.2118 39.8847 12.3702 34.3378 13.4904 29.0154C14.6106 23.693 17.6176 18.952 21.9594 15.6624C26.3012 12.3729 31.6867 10.7554 37.1276 11.1068C42.5685 11.4582 47.6999 13.755 51.5802 17.5756C55.4604 21.3962 57.8292 26.4844 58.2519 31.9065C58.6746 37.3286 57.1228 42.7208 53.8813 47.0938C50.6399 51.4668 45.9261 54.5271 40.605 55.7133C35.284 56.8994 29.7125 56.1318 24.9131 53.5513L12.5762 56.8405ZM25.508 48.985L26.2709 49.4365C29.7473 51.4918 33.8076 52.3423 37.8191 51.8555C41.8306 51.3687 45.5681 49.5719 48.4489 46.7452C51.3298 43.9185 53.1923 40.2206 53.7463 36.2279C54.3002 32.2351 53.5143 28.1717 51.5113 24.6709C49.5082 21.1701 46.4003 18.4285 42.6721 16.8734C38.9438 15.3184 34.8045 15.0372 30.8993 16.0736C26.994 17.11 23.5422 19.4059 21.0817 22.6035C18.6212 25.801 17.2903 29.7206 17.2963 33.7514C17.293 37.0937 18.2197 40.3712 19.9732 43.2192L20.4516 44.0061L18.6153 50.8167L25.508 48.985Z" fill="#00D95F"/><path fill-rule="evenodd" clip-rule="evenodd" d="M44.0259 36.8847C43.5787 36.5249 43.0549 36.2716 42.4947 36.1442C41.9344 36.0168 41.3524 36.0186 40.793 36.1495C39.9524 36.4977 39.4093 37.8134 38.8661 38.4713C38.7516 38.629 38.5833 38.7396 38.3928 38.7823C38.2024 38.8251 38.0028 38.797 37.8316 38.7034C34.7543 37.5012 32.1748 35.2965 30.5122 32.4475C30.3704 32.2697 30.3033 32.044 30.325 31.8178C30.3467 31.5916 30.4555 31.3827 30.6286 31.235C31.2344 30.6368 31.6791 29.8959 31.9218 29.0809C31.9756 28.1818 31.7691 27.2863 31.3269 26.5011C30.985 25.4002 30.3344 24.42 29.4518 23.6762C28.9966 23.472 28.4919 23.4036 27.9985 23.4791C27.5052 23.5546 27.0443 23.7709 26.6715 24.1019C26.0242 24.6589 25.5104 25.3537 25.168 26.135C24.8256 26.9163 24.6632 27.7643 24.6929 28.6165C24.6949 29.0951 24.7557 29.5716 24.8739 30.0354C25.1742 31.1497 25.636 32.2144 26.2447 33.1956C26.6839 33.9473 27.163 34.6749 27.6801 35.3755C29.3607 37.6767 31.4732 39.6305 33.9003 41.1284C35.1183 41.8897 36.42 42.5086 37.7799 42.973C39.1924 43.6117 40.752 43.8568 42.2931 43.6824C43.1711 43.5499 44.003 43.2041 44.7156 42.6755C45.4281 42.1469 45.9995 41.4518 46.3795 40.6512C46.6028 40.1675 46.6705 39.6269 46.5735 39.1033C46.3407 38.0327 44.9053 37.4007 44.0259 36.8847Z" fill="white"/></svg>
                                        </div>
                                        WhatsApp
                                    </label>
                                    <input type="text" name="whatsapp" class="tmf-input" placeholder="+84 9x..." value="<?php echo esc_attr($m['whatsapp'] ?? ''); ?>">
                                </div>
                                <div class="tmf-field">
                                    <label class="social-icon-label">
                                        <div class="social-icon-wrapper border-none">
                                            <svg viewBox="0 0 71 72" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M33.3501 13.0437C25.9186 13.893 18.5132 20.0479 18.2075 28.84C18.0154 34.2083 19.5044 38.2356 24.4951 39.3664C26.6608 35.4553 23.7965 34.5927 23.3511 31.7633C21.5216 20.1686 36.4153 12.2615 44.2093 20.3563C49.6018 25.9615 46.0519 43.206 37.3541 41.4136C29.0231 39.7017 41.4323 25.9749 34.7823 23.2796C29.3767 21.0894 26.5037 29.9798 29.0667 34.396C27.5647 41.9902 24.3292 49.1464 25.6391 58.6715C29.8876 55.5158 31.3198 49.4727 32.4943 43.1702C34.6295 44.4978 35.7691 45.8789 38.4937 46.0935C48.5407 46.8891 54.1515 35.8263 52.7805 25.6218C51.5623 16.5749 42.7422 11.971 33.3501 13.0437Z" fill="#FF0000"/></svg>
                                        </div>
                                        YouTube
                                    </label>
                                    <input type="url" name="youtube" class="tmf-input" placeholder="https://youtube.com/..." value="<?php echo esc_attr($m['youtube'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="tmf-row2" style="margin-top:16px;">
                                <div class="tmf-field">
                                    <label class="social-icon-label">
                                        <div class="social-icon-wrapper border-none">
                                            <svg viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.6975 11C12.6561 11 11 12.6057 11 14.5838V57.4474C11 59.4257 12.6563 61.03 14.6975 61.03H57.3325C59.3747 61.03 61.03 59.4255 61.03 57.4468V14.5838C61.03 12.6057 59.3747 11 57.3325 11H14.6975ZM26.2032 30.345V52.8686H18.7167V30.345H26.2032ZM26.6967 23.3793C26.6967 25.5407 25.0717 27.2703 22.4615 27.2703L22.4609 27.2701H22.4124C19.8998 27.2701 18.2754 25.5405 18.2754 23.3791C18.2754 21.1686 19.9489 19.4873 22.5111 19.4873C25.0717 19.4873 26.6478 21.1686 26.6967 23.3793ZM37.833 52.8686H30.3471L30.3469 52.8694C30.3469 52.8694 30.4452 32.4588 30.3475 30.3458H37.8336V33.5339C38.8288 31.9995 40.6098 29.8169 44.5808 29.8169C49.5062 29.8169 53.1991 33.0363 53.1991 39.9543V52.8686H45.7133V40.8204C45.7133 37.7922 44.6293 35.7269 41.921 35.7269C39.8524 35.7269 38.6206 37.1198 38.0796 38.4653C37.8819 38.9455 37.833 39.6195 37.833 40.2918V52.8686Z" fill="#006699"/></svg>
                                        </div>
                                        LinkedIn
                                    </label>
                                    <input type="url" name="linkedin" class="tmf-input" placeholder="https://linkedin.com/..." value="<?php echo esc_attr($m['linkedin'] ?? ''); ?>">
                                </div>
                                <div class="tmf-field">
                                    <label class="social-icon-label">
                                        <div class="social-icon-wrapper border-none">
                                            <svg viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M45.8956 26.0879C46.8845 26.0879 47.8503 26.1701 48.8032 26.2876C47.0805 18.7224 39.1346 13 29.5799 13C18.7651 13 10 20.3257 10 29.3599C10 34.5686 12.9306 39.1897 17.4761 42.1798L14.8948 47.3562L21.9268 44.336C23.4347 44.873 25.0016 45.3152 26.6753 45.521C26.4465 44.5226 26.3166 43.4991 26.3166 42.4487C26.3166 33.4273 35.0976 26.0879 45.8956 26.0879ZM36.1064 20.3615C37.4577 20.3615 38.5536 21.4608 38.5536 22.8158C38.5536 24.1713 37.4578 25.2698 36.1064 25.2698C34.7543 25.2698 33.6589 24.1713 33.6589 22.8158C33.6589 21.4607 34.7543 20.3615 36.1064 20.3615ZM23.0531 25.2698C21.7016 25.2698 20.6057 24.1713 20.6057 22.8158C20.6057 21.4608 21.7017 20.3615 23.0531 20.3615C24.4045 20.3615 25.5006 21.4608 25.5006 22.8158C25.5005 24.1713 24.4044 25.2698 23.0531 25.2698Z" fill="#51C332"/><path d="M62.2121 42.4484C62.2121 35.22 54.9051 29.3599 45.8956 29.3599C36.8858 29.3599 29.5799 35.22 29.5799 42.4484C29.5799 49.6763 36.8858 55.5365 45.8956 55.5365C47.3773 55.5365 48.7867 55.3271 50.1542 55.0297L58.9489 58.8084L55.9072 52.713C59.7191 50.3174 62.2121 46.6335 62.2121 42.4484ZM41.001 41.6303C39.6496 41.6303 38.5534 40.5314 38.5534 39.1757C38.5534 37.8207 39.6495 36.7222 41.001 36.7222C42.3528 36.7222 43.4482 37.8212 43.4482 39.1757C43.4482 40.5316 42.3526 41.6303 41.001 41.6303ZM50.7905 41.6303C49.4385 41.6303 48.3433 40.5314 48.3433 39.1757C48.3433 37.8207 49.4384 36.7222 50.7905 36.7222C52.1425 36.7222 53.238 37.8212 53.238 39.1757C53.238 40.5316 52.1425 41.6303 50.7905 41.6303Z" fill="#51C332"/></svg>
                                        </div>
                                        WeChat
                                    </label>
                                    <input type="text" name="wechat" class="tmf-input" placeholder="WeChat ID" value="<?php echo esc_attr($m['wechat'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="tmf-row2" style="margin-top:16px;">
                                <div class="tmf-field">
                                    <label class="social-icon-label">
                                        <div class="social-icon-wrapper font-bold text-[#0068FF] text-[15px]">
                                            Zalo
                                        </div>
                                        Zalo
                                    </label>
                                    <input type="text" name="zalo" class="tmf-input" placeholder="Số điện thoại hoặc link Zalo" value="<?php echo esc_attr($m['zalo'] ?? ''); ?>">
                                </div>
                                <div class="tmf-field">
                                    <label class="social-icon-label">
                                        <div class="social-icon-wrapper border-none">
                                            <svg viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M40.3522 25.55C40.3522 29.4089 38.8124 33.1097 36.0717 35.8384C33.3309 38.5671 29.6136 40.1 25.7376 40.1C21.8616 40.1 18.1443 38.5671 15.4036 35.8384C12.6628 33.1097 11.123 29.4089 11.123 25.55C11.123 21.6911 12.6628 17.9902 15.4036 15.2616C18.1443 12.5329 21.8616 11 25.7376 11C29.6136 11 33.3309 12.5329 36.0717 15.2616C38.8124 17.9902 40.3522 21.6911 40.3522 25.55Z" fill="url(#skype1)"/><path d="M60.7196 46.445C60.7196 48.3564 60.3415 50.2491 59.6068 52.015C58.8721 53.7809 57.7952 55.3854 56.4376 56.737C55.0801 58.0885 53.4684 59.1606 51.6947 59.8921C49.921 60.6235 48.0199 61 46.1 61C42.2227 61 38.5041 59.4665 35.7624 56.737C33.0207 54.0074 31.4805 50.3052 31.4805 46.445C31.4805 42.5848 33.0207 38.8827 35.7624 36.1531C38.5041 33.4235 42.2227 31.89 46.1 31.89C48.0199 31.89 49.921 32.2665 51.6947 32.9979C53.4684 33.7294 55.0801 34.8015 56.4376 36.1531C57.7952 37.5046 58.8721 39.1092 59.6068 40.8751C60.3415 42.641 60.7196 44.5336 60.7196 46.445Z" fill="url(#skype2)"/><path d="M59.0711 36.1084C59.0711 39.1347 58.4724 42.1313 57.3092 44.9273C56.1459 47.7232 54.4409 50.2637 52.2914 52.4036C50.142 54.5436 47.5903 56.241 44.7819 57.3992C41.9735 58.5573 38.9635 59.1534 35.9238 59.1534C32.884 59.1534 29.874 58.5573 27.0656 57.3992C24.2573 56.241 21.7055 54.5436 19.5561 52.4036C17.4066 50.2637 15.7016 47.7232 14.5384 44.9273C13.3751 42.1313 12.7764 39.1347 12.7764 36.1084C12.7764 33.082 13.3751 30.0854 14.5384 27.2894C15.7016 24.4935 17.4066 21.953 19.5561 19.8131C21.7055 17.6732 24.2573 15.9757 27.0656 14.8176C29.874 13.6594 32.884 13.0634 35.9238 13.0634C38.9635 13.0634 41.9735 13.6594 44.7819 14.8176C47.5903 15.9757 50.142 17.6732 52.2914 19.8131C54.4409 21.953 56.1459 24.4935 57.3092 27.2894C58.4724 30.0854 59.0711 33.082 59.0711 36.1084Z" fill="url(#skype3)"/><path fill-rule="evenodd" clip-rule="evenodd" d="M29.4151 35.3084C28.4797 34.6934 27.6991 33.8709 27.1343 32.905C26.5889 31.8846 26.3197 30.7396 26.3534 29.5834C26.3121 28.116 26.8134 26.6849 27.7616 25.5634C28.7471 24.4371 30.0136 23.5918 31.4324 23.1134C32.9754 22.5688 34.6012 22.2954 36.2377 22.305C37.3084 22.2949 38.378 22.3769 39.4346 22.55C40.1909 22.6667 40.9337 22.8582 41.6521 23.1217C42.3117 23.3447 42.9072 23.7245 43.3873 24.2284C43.7411 24.6429 43.9315 25.172 43.9229 25.7167C43.9355 25.9997 43.8922 26.2823 43.7954 26.5486C43.6987 26.8148 43.5504 27.0594 43.359 27.2684C43.1778 27.4609 42.958 27.6131 42.7139 27.7147C42.4697 27.8164 42.2068 27.8654 41.9424 27.8584C41.5786 27.8554 41.2196 27.7747 40.8895 27.6217C40.0761 27.258 39.2381 26.9517 38.3818 26.705C37.5935 26.4974 36.7809 26.3965 35.9657 26.405C34.9033 26.366 33.8522 26.6345 32.939 27.1784C32.5537 27.4122 32.2383 27.7452 32.0258 28.1425C31.8133 28.5397 31.7115 28.9867 31.731 29.4367C31.7254 30.0275 31.9505 30.5972 32.3584 31.025C32.8431 31.5276 33.416 31.9371 34.0486 32.2334C34.7572 32.5845 35.8139 33.05 37.2188 33.63C37.3744 33.6792 37.5261 33.74 37.6726 33.8117C39.0436 34.3534 40.3581 35.0283 41.597 35.8267C42.5755 36.464 43.4006 37.3098 44.013 38.3034C44.6161 39.3476 44.9181 40.5382 44.8857 41.7434C44.9363 43.2189 44.513 44.672 43.6776 45.89C42.827 47.0433 41.6503 47.9157 40.2989 48.395C38.6702 48.9763 36.9491 49.2564 35.2199 49.2217C32.8437 49.3048 30.4788 48.8578 28.2972 47.9134C27.7395 47.6653 27.2505 47.2855 26.8723 46.8067C26.5583 46.368 26.3955 45.8392 26.4085 45.3C26.3929 45.0157 26.4393 44.7313 26.5444 44.4666C26.6495 44.2018 26.8109 43.9631 27.0175 43.7667C27.4504 43.388 28.0125 43.1899 28.5875 43.2134C29.2282 43.2221 29.8576 43.3838 30.4229 43.685C31.1259 44.0361 31.6804 44.2995 32.0864 44.475C32.5501 44.6689 33.0304 44.8207 33.5214 44.9284C34.1268 45.061 34.7453 45.1248 35.3651 45.1184C36.4611 45.1965 37.5518 44.9061 38.4635 44.2934C38.8048 44.0266 39.0776 43.6825 39.2592 43.2895C39.4408 42.8964 39.5261 42.4659 39.508 42.0334C39.509 41.4201 39.275 40.8297 38.854 40.3834C38.2975 39.8095 37.6461 39.3359 36.9285 38.9834C36.0798 38.5367 34.8862 37.9923 33.3478 37.35C31.972 36.8024 30.6545 36.1184 29.4151 35.3084Z" fill="white"/><defs><linearGradient id="skype1" x1="23.4584" y1="11.1767" x2="28.0069" y2="39.9249" gradientUnits="userSpaceOnUse"><stop offset="0.012" stop-color="#00B7F0"/><stop offset="0.339" stop-color="#009DE5"/><stop offset="0.755" stop-color="#0082D9"/><stop offset="1" stop-color="#0078D4"/></linearGradient><linearGradient id="skype2" x1="33.446" y1="53.7417" x2="58.7384" y2="39.124" gradientUnits="userSpaceOnUse"><stop stop-color="#0078D4"/><stop offset="0.37" stop-color="#007AD5"/><stop offset="0.573" stop-color="#0082D9"/><stop offset="0.735" stop-color="#0090DF"/><stop offset="0.875" stop-color="#00A3E7"/><stop offset="1" stop-color="#00BCF2"/></linearGradient><linearGradient id="skype3" x1="26.8904" y1="20.4817" x2="49.9459" y2="60.4589" gradientUnits="userSpaceOnUse"><stop stop-color="#00B7F0"/><stop offset="1" stop-color="#007CC1"/></linearGradient></defs></svg>
                                        </div>
                                        Skype
                                    </label>
                                    <input type="text" name="skype" class="tmf-input" placeholder="Skype ID" value="<?php echo esc_attr($m['skype'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="tmf-row2" style="margin-top:16px;">
                                <div class="tmf-field">
                                    <label class="social-icon-label" style="opacity:0.6;">
                                        <div class="social-icon-wrapper border-none" style="background:#51C332;color:#fff;font-weight:bold;font-size:10px;">
                                            LINE
                                        </div>
                                        Line App (Tùy chọn)
                                    </label>
                                    <input type="text" name="line_app" class="tmf-input" placeholder="Line ID" value="<?php echo esc_attr($m['line_app'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2.8: Gallery -->
                    <div class="tmf-section">
                        <div class="tmf-sec-head">
                            <svg viewBox="0 0 14 14"><rect x="1" y="2" width="12" height="10" rx="1.5"/><circle cx="5" cy="6" r="1.5"/><path d="M1 10l3-3 2.5 2.5 2-2L13 10"/></svg>
                            Thư viện ảnh Khác
                        </div>
                        <div class="tmf-sec-body">
                            <div class="tmf-field">
                                <label class="tmf-label">Các ảnh khác (Nhiều ảnh)</label>
                                <textarea id="tm-gallery" name="gallery_urls" class="tmf-textarea" rows="4" style="display:none;"
                                          ><?php echo esc_textarea($m['gallery_urls'] ?? ''); ?></textarea>
                                          
                                <div id="tm-gallery-preview" style="display:flex; flex-wrap:wrap; gap:8px; padding-top:8px;"></div>
                                
                                <button type="button" id="tm-gallery-btn" class="tm-btn tm-btn-outline tm-btn-sm" style="margin-top:8px;">
                                    <svg viewBox="0 0 14 14"><rect x="1" y="3" width="12" height="9" rx="1.5"/><circle cx="5" cy="7" r="1.5"/><path d="M1 11l3-3 2.5 2.5 2-2L12 11"/></svg>
                                    Chọn từ thiết bị web
                                </button>
                            </div>
                        </div>
                    </div>


                    <!-- Section 3: Status -->
                    <div class="tmf-section">
                        <div class="tmf-sec-head">
                            <svg viewBox="0 0 13 13"><circle cx="6.5" cy="6.5" r="5.5"/><path d="M4.5 6.5l1.5 1.5 3-3"/></svg>
                            Trạng thái hiển thị
                        </div>
                        <div class="tmf-sec-body">
                            <div class="tmf-status-group" id="tm-status-group">
                                <label class="tmf-status-pill <?php echo ($m['is_active'] ?? 1) ? 'active-show' : ''; ?>" id="pill-show">
                                    <input type="radio" name="is_active" value="1" <?php checked($m['is_active'] ?? 1, 1); ?>>
                                    <svg viewBox="0 0 12 12"><circle cx="6" cy="6" r="5"/><path d="M4 6l1.5 1.5L8 4"/></svg>
                                    Hiển thị trên website
                                </label>
                                <label class="tmf-status-pill <?php echo !($m['is_active'] ?? 1) ? 'active-hide' : ''; ?>" id="pill-hide">
                                    <input type="radio" name="is_active" value="0" <?php checked($m['is_active'] ?? 1, 0); ?>>
                                    <svg viewBox="0 0 12 12"><circle cx="6" cy="6" r="5"/><path d="M4.5 7.5l3-3M7.5 7.5l-3-3"/></svg>
                                    Ẩn khỏi website
                                </label>
                            </div>
                            <span class="tmf-hint">Chỉ thành viên "Ẩn" sẽ không hiển thị trên trang Our Team.</span>
                        </div>
                    </div>

                </div><!-- /tmf-main -->

                <!-- RIGHT: Sidebar -->
                <div class="tmf-side">

                    <!-- Photo zone -->
                    <div class="tmf-section">
                        <div class="tmf-sec-head">
                            <svg viewBox="0 0 13 13"><rect x="1" y="1" width="11" height="11" rx="1.5"/><circle cx="4.5" cy="4.5" r="1.5"/><path d="M1 9.5l3-3 2.5 2.5 2-2 3.5 3.5"/></svg>
                            Ảnh đại diện
                        </div>
                        <div class="tmf-sec-body">

                            <!-- Clickable photo zone -->
                            <div class="tmf-photo-zone" id="tm-photo-zone" onclick="document.getElementById('tm-media-btn').click()">
                                <?php if (!empty($m['photo_url'])): ?>
                                <img id="tm-photo-img" src="<?php echo esc_url($m['photo_url']); ?>" alt=""
                                     onerror="this.style.display='none';document.getElementById('tm-photo-placeholder').style.display='flex'">
                                <div class="tmf-photo-initials" id="tm-photo-placeholder" style="display:none;">
                                    <div class="tmf-photo-initials-text" id="tm-photo-initials-big"></div>
                                    <div class="tmf-photo-initials-label">
                                        <svg viewBox="0 0 14 14"><rect x="1" y="3" width="12" height="9" rx="1.5"/><circle cx="5" cy="7" r="1.5"/><path d="M1 11l3-3 2.5 2.5 2-2L12 11"/></svg>
                                        Chọn ảnh
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="tmf-photo-initials" id="tm-photo-placeholder">
                                    <div class="tmf-photo-initials-text" id="tm-photo-initials-big"></div>
                                    <div class="tmf-photo-initials-label">
                                        <svg viewBox="0 0 14 14"><rect x="1" y="3" width="12" height="9" rx="1.5"/><circle cx="5" cy="7" r="1.5"/><path d="M1 11l3-3 2.5 2.5 2-2L12 11"/></svg>
                                        Nhấp để chọn
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Hover overlay -->
                                <div class="tmf-photo-overlay">
                                    <div class="tmf-photo-overlay-label">
                                        <svg viewBox="0 0 14 14"><rect x="1" y="3" width="12" height="9" rx="1.5"/><circle cx="5" cy="7" r="1.5"/><path d="M1 11l3-3 2.5 2.5 2-2L12 11"/></svg>
                                        Đổi ảnh
                                    </div>
                                </div>
                            </div>

                            <!-- Preview info below photo -->
                            <div>
                                <div class="tmf-preview-name" id="preview-name"><?php echo esc_html($m['name'] ?? 'Tên thành viên'); ?></div>
                                <div class="tmf-preview-role" id="preview-role"><?php echo esc_html($m['role'] ?? 'Chức danh'); ?></div>
                                <div class="tmf-preview-dept" id="preview-dept">
                                    <?php if (!empty($cur_dept)): ?>
                                    <span class="tm-badge tm-badge-accent"><?php echo esc_html($cur_dept); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- URL input (hidden, triggered by photo click) -->
                            <div class="tmf-url-row">
                                <input type="url" name="photo_url" id="tm-photo-input" class="tmf-input"
                                       value="<?php echo esc_attr($m['photo_url'] ?? ''); ?>"
                                       placeholder="https://…/photo.jpg">
                                <button type="button" id="tm-media-btn" class="tm-btn tm-btn-outline" title="Chọn từ Media" style="flex-shrink:0;">
                                    <svg viewBox="0 0 14 14"><rect x="1" y="3" width="12" height="9" rx="1.5"/><circle cx="5" cy="7" r="1.5"/><path d="M1 11l3-3 2.5 2.5 2-2L12 11"/></svg>
                                </button>
                                <?php if (!empty($m['photo_url'])): ?>
                                <button type="button" id="tm-photo-clear" class="tm-btn tm-btn-danger" title="Xóa ảnh" style="flex-shrink:0;">
                                    <svg viewBox="0 0 14 14"><path d="M2 2l10 10M12 2L2 12"/></svg>
                                </button>
                                <?php else: ?>
                                <button type="button" id="tm-photo-clear" class="tm-btn tm-btn-danger" title="Xóa ảnh" style="flex-shrink:0;display:none;">
                                    <svg viewBox="0 0 14 14"><path d="M2 2l10 10M12 2L2 12"/></svg>
                                </button>
                                <?php endif; ?>
                            </div>
                            <span class="tmf-hint">Tỷ lệ ảnh đẹp nhất: 3:4 (dọc). Nhất vào vùng ảnh hoặc dán URL bên trên.</span>
                        </div>
                    </div>

                    <!-- Tips -->
                    <div class="tmf-section">
                        <div class="tmf-sec-head">
                            <svg viewBox="0 0 13 13"><circle cx="6.5" cy="6.5" r="5.5"/><path d="M6.5 6v3.5M6.5 4.5v.5"/></svg>
                            Lưu ý
                        </div>
                        <div class="tmf-sec-body" style="gap:10px;">
                            <?php
                            $tips = [
                                'Thành viên "Ban Giám đốc" được hệ thống xếp lên đầu trang Our Team.',
                                'Nhấn khởi dều Enter trong ô Bio để ngăn cách đoạn văn.',
                                'Thứ tự có thể điều chỉnh bằng kéo thả tay trên trang danh sách.',
                            ];
                            foreach ($tips as $tip):
                            ?>
                            <div style="display:flex;gap:8px;align-items:flex-start;">
                                <svg style="width:12px;height:12px;stroke:var(--text-3);fill:none;stroke-width:2;stroke-linecap:round;flex-shrink:0;margin-top:2px;" viewBox="0 0 12 12"><polyline points="2,6.5 4.5,9 10,3.5"/></svg>
                                <span style="font-size:12px;color:var(--text-2);line-height:1.5;"><?php echo $tip; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div><!-- /tmf-side -->

            </div><!-- /tmf-body -->
        </form>

        <?php
    }

    /* ── JavaScript ──────────────────────────────────────────────── */

    private function render_scripts( string $nonce ) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tmConfig = {
                nonce: '<?php echo esc_js( $nonce ); ?>',
                ajaxurl: (typeof ajaxurl !== 'undefined') ? ajaxurl : '<?php echo esc_url_raw( admin_url( 'admin-ajax.php' ) ); ?>',
                listUrl: '<?php echo esc_url_raw( admin_url( 'admin.php?page=bacera-team' ) ); ?>',
                editBase: '<?php echo esc_url_raw( admin_url( 'admin.php?page=bacera-team&edit=' ) ); ?>'
            };

            /* ── Common Utils ── */
            function $(selector, parent) { return (parent || document).querySelector(selector); }
            function $$(selector, parent) { return (parent || document).querySelectorAll(selector); }
            
            function toast(msg, isOk) {
                if (typeof isOk === 'undefined') isOk = true;
                var el = document.getElementById('tm-toast');
                var ico = document.getElementById('tm-toast-icon');
                var txt = document.getElementById('tm-toast-msg');
                if (!el) return;
                txt.textContent = msg;
                el.className = isOk ? 'show ok' : 'show err';
                ico.innerHTML = isOk 
                    ? '<polyline points="2,7 5.5,10.5 12,3"/>' 
                    : '<path d="M2 2l10 10M12 2L2 12"/>';
                clearTimeout(el.to);
                el.to = setTimeout(function() { el.className = ''; }, 3200);
            }
            
            function syncGalleryPreview(ta, previewDiv) {
                if (!ta || !previewDiv) return;
                var urls = ta.value.split('\n').map(function(s) { return s.trim(); }).filter(Boolean);
                if (urls.length === 0) {
                    previewDiv.innerHTML = '<div style="font-size:11px; color:var(--text-3); font-style:italic; padding:10px 0;">Chưa có ảnh nào. Hãy nhấn Thêm ảnh.</div>'; return;
                }
                var html = '';
                for (var i = 0; i < urls.length; i++) {
                    html += '<div style="position:relative; width:64px; height:64px; border-radius:6px; border:1px solid var(--border); overflow:hidden; flex-shrink:0; cursor:pointer;" class="tmm-gal-item" data-index="'+i+'" title="Nhấn để xóa ảnh này">' +
                            '<img src="'+urls[i]+'" style="width:100%;height:100%;object-fit:cover;">' +
                            '<div style="position:absolute;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .2s;" class="tmm-gal-del"><svg style="width:20px;height:20px;stroke:#fff;stroke-width:2" viewBox="0 0 14 14"><path d="M2 2l10 10M12 2L2 12"/></svg></div>' +
                            '</div>';
                }
                previewDiv.innerHTML = html;
            }

            function apiPost(action, dataObj, callback) {
                var fd = new FormData();
                fd.append('action', action);
                fd.append('_nonce', tmConfig.nonce);
                for (var key in dataObj) { fd.append(key, dataObj[key]); }
                
                fetch(tmConfig.ajaxurl, { method: 'POST', body: fd })
                    .then(function(res) { return res.json(); })
                    .then(function(res) { callback(res.success, res.data ? res.data.message : ''); })
                    .catch(function(err) { callback(false, 'Lỗi kết nối máy chủ!'); });
            }

            /* ── Drawer UI Management ── */
            var drawerVars = {
                bd: $('#tmm-bd'),
                dr: $('#tmm-dr'),
                activeMemberId: 0
            };

            function closeDrawer() {
                if (drawerVars.bd) drawerVars.bd.classList.remove('open');
                if (drawerVars.dr) drawerVars.dr.classList.remove('open');
                document.body.style.overflow = '';
                drawerVars.activeMemberId = 0;
            }

            function setDrawerPhoto(url) {
                var img = $('#tmm-photo-img'), ph = $('#tmm-placeholder'), clr = $('#tmm-photo-clear');
                if (url) {
                    if (img) { img.src = url; img.style.display = 'block'; }
                    if (ph) ph.style.display = 'none';
                    if (clr) clr.style.display = '';
                } else {
                    if (img) { img.src = ''; img.style.display = 'none'; }
                    if (ph) ph.style.display = 'flex';
                    if (clr) clr.style.display = 'none';
                }
            }

            function openDrawer(id) {
                var m = window.tmMembers ? window.tmMembers.find(function(x) { return parseInt(x.id, 10) === parseInt(id, 10); }) : null;
                if (!m) return toast('Không tìm thấy dữ liệu nội bộ.', false);

                drawerVars.activeMemberId = m.id;
                
                // Form setup
                var idF = $('#tmm-id');     if (idF) idF.value = m.id;
                var nmF = $('#tmm-name');   if (nmF) nmF.value = m.name || '';
                var rlF = $('#tmm-role');   if (rlF) rlF.value = m.role || '';
                var biF = $('#tmm-bio');    if (biF) biF.value = m.bio || '';
                var dpF = $('#tmm-dept');   if (dpF) dpF.value = m.department || '';
                var puF = $('#tmm-photo-url'); if (puF) puF.value = m.photo_url || '';
                
                var phF = $('#tmm-phone');  if (phF) phF.value = m.phone || '';
                var emF = $('#tmm-email');  if (emF) emF.value = m.email || '';
                var fbF = $('#tmm-facebook'); if (fbF) fbF.value = m.facebook || '';
                var igF = $('#tmm-instagram'); if (igF) igF.value = m.instagram || '';
                var tkF = $('#tmm-tiktok'); if (tkF) tkF.value = m.tiktok || '';
                var xtF = $('#tmm-x_twitter'); if (xtF) xtF.value = m.x_twitter || '';
                var inF = $('#tmm-linkedin'); if (inF) inF.value = m.linkedin || '';
                var ytF = $('#tmm-youtube'); if (ytF) ytF.value = m.youtube || '';
                var ptF = $('#tmm-pinterest'); if (ptF) ptF.value = m.pinterest || '';
                var msF = $('#tmm-messenger'); if (msF) msF.value = m.messenger || '';
                var tgF = $('#tmm-telegram'); if (tgF) tgF.value = m.telegram || '';
                var waF = $('#tmm-whatsapp'); if (waF) waF.value = m.whatsapp || '';
                var zlF = $('#tmm-zalo');   if (zlF) zlF.value = m.zalo || '';
                var vbF = $('#tmm-viber');  if (vbF) vbF.value = m.viber || '';
                var skF = $('#tmm-skype');  if (skF) skF.value = m.skype || '';
                var lnF = $('#tmm-line_app'); if (lnF) lnF.value = m.line_app || '';
                var wcF = $('#tmm-wechat'); if (wcF) wcF.value = m.wechat || '';
                var glF = $('#tmm-gallery'); 
                if (glF) { 
                    glF.value = m.gallery_urls || ''; 
                    syncGalleryPreview(glF, $('#tmm-gallery-preview')); 
                }

                // Load active pills
                var isActiveStr = String(m.is_active);
                $$('[name="tmm_active"]').forEach(function(radio) {
                    radio.checked = (String(radio.value) === isActiveStr);
                    var pill = radio.closest('.tmm-pill');
                    if (pill) {
                        pill.classList.remove('on-show', 'on-hide');
                        if (radio.checked) pill.classList.add(radio.value === '1' ? 'on-show' : 'on-hide');
                    }
                });

                // Display info setup
                var tTit = $('#tmm-title'); if (tTit) tTit.textContent = m.name || 'Chỉnh sửa';
                var sTit = $('#tmm-sub');   if (sTit) sTit.textContent = m.role || m.department || '';
                
                var pNam = $('#tmm-prev-name'); if (pNam) pNam.textContent = m.name || '';
                var pRol = $('#tmm-prev-role'); if (pRol) pRol.textContent = m.role || '';
                var pIni = $('#tmm-initials-big');
                if (pIni) {
                    var pts = (m.name || '').trim().split(' ');
                    pIni.textContent = pts.length > 1 ? (pts[pts.length-2][0]+pts[pts.length-1][0]).toUpperCase() : (pts[0] ? pts[0].substring(0,2).toUpperCase() : '?');
                }
                
                var lA = $('#tmm-full-edit'); if (lA) lA.href = tmConfig.editBase + m.id;
                setDrawerPhoto(m.photo_url);

                if (drawerVars.bd) drawerVars.bd.classList.add('open');
                if (drawerVars.dr) drawerVars.dr.classList.add('open');
                document.body.style.overflow = 'hidden';
            }

            /* ── Global Click Manager ── */
            document.addEventListener('click', function(e) {
                var t = e.target;
                
                // BACKDROP CLOSE
                if (drawerVars.bd && t === drawerVars.bd) { closeDrawer(); return; }
                
                // CLOSE BUTTON
                if (t.closest('#tmm-close')) { closeDrawer(); return; }
                
                // CARD DELETE
                var delCardBtn = t.closest('.tm-delete-btn');
                if (delCardBtn) {
                    e.preventDefault();
                    if (!confirm('Xóa thành viên này?')) return;
                    var id = delCardBtn.getAttribute('data-id');
                    var card = delCardBtn.closest('.tm-card');
                    delCardBtn.disabled = true;
                    apiPost('bacera_team_delete', { id: id }, function(success, msg) {
                        if (success && card) {
                            card.style.opacity = 0;
                            card.style.transform = 'scale(0.9)';
                            setTimeout(function() { card.remove(); toast(msg); }, 300);
                        } else {
                            toast(msg || 'Lỗi', false);
                            delCardBtn.disabled = false;
                        }
                    });
                    return;
                }
                
                // DRAWER OPEN VIA CARD CLICK
                var openBtn = t.closest('.tmm-open-btn');
                var cardZone = t.closest('.tm-card-photo, .tm-card-body');
                if (openBtn || cardZone) {
                    e.preventDefault();
                    var cardInner = t.closest('.tm-card');
                    if (cardInner) {
                        var cId = parseInt(cardInner.getAttribute('data-id'), 10);
                        if (cId) openDrawer(cId);
                    }
                    return;
                }
                
                // DRAWER DELETE BTN
                var delDrBtn = t.closest('#tmm-delete');
                if (delDrBtn) {
                    e.preventDefault();
                    if (!confirm('Xóa thành viên này?')) return;
                    delDrBtn.disabled = true;
                    apiPost('bacera_team_delete', { id: drawerVars.activeMemberId }, function(ok, msg) {
                        if (ok) {
                            var dc = $('.tm-card[data-id="'+drawerVars.activeMemberId+'"]');
                            if (dc) { dc.style.opacity = 0; setTimeout(function() { dc.remove(); }, 300); }
                            toast(msg); closeDrawer();
                        } else {
                            toast(msg||'Lỗi', false); delDrBtn.disabled = false;
                        }
                    });
                    return;
                }
                
                // DRAWER SAVE BTN
                var saveBtn = t.closest('#tmm-save');
                if (saveBtn) {
                    e.preventDefault();
                    var nameF = $('#tmm-name');
                    var rname = nameF ? nameF.value.trim() : '';
                    if (!rname) { toast('Bạn cần điền tên.', false); return; }
                    
                    var newActive = '1';
                    var actRdo = $('[name="tmm_active"]:checked');
                    if (actRdo) newActive = actRdo.value;

                    var sData = {
                        id: drawerVars.activeMemberId,
                        name: rname,
                        role: ($('#tmm-role') || {}).value || '',
                        department: ($('#tmm-dept') || {}).value || '',
                        photo_url: ($('#tmm-photo-url') || {}).value || '',
                        bio: ($('#tmm-bio') || {}).value || '',
                        is_active: newActive,
                        phone: ($('#tmm-phone') || {}).value || '',
                        email: ($('#tmm-email') || {}).value || '',
                        facebook: ($('#tmm-facebook') || {}).value || '',
                        instagram: ($('#tmm-instagram') || {}).value || '',
                        tiktok: ($('#tmm-tiktok') || {}).value || '',
                        x_twitter: ($('#tmm-x_twitter') || {}).value || '',
                        linkedin: ($('#tmm-linkedin') || {}).value || '',
                        youtube: ($('#tmm-youtube') || {}).value || '',
                        pinterest: ($('#tmm-pinterest') || {}).value || '',
                        messenger: ($('#tmm-messenger') || {}).value || '',
                        telegram: ($('#tmm-telegram') || {}).value || '',
                        whatsapp: ($('#tmm-whatsapp') || {}).value || '',
                        zalo: ($('#tmm-zalo') || {}).value || '',
                        viber: ($('#tmm-viber') || {}).value || '',
                        skype: ($('#tmm-skype') || {}).value || '',
                        line_app: ($('#tmm-line_app') || {}).value || '',
                        wechat: ($('#tmm-wechat') || {}).value || '',
                        gallery_urls: ($('#tmm-gallery') || {}).value || ''
                    };

                    saveBtn.disabled = true;
                    var oldHtml = saveBtn.innerHTML;
                    saveBtn.innerHTML = 'Đang lưu…';

                    apiPost('bacera_team_save', sData, function(ok, msg) {
                        if (ok) {
                            toast(msg);
                            // Update internal obj
                            var mo = window.tmMembers.find(function(x) { return x.id == sData.id; });
                            if (mo) {
                                mo.name = sData.name; mo.role = sData.role; mo.department = sData.department;
                                mo.instagram = sData.instagram; mo.tiktok = sData.tiktok; mo.x_twitter = sData.x_twitter;
                                mo.linkedin = sData.linkedin; mo.youtube = sData.youtube;
                                mo.phone = sData.phone; mo.email = sData.email; mo.whatsapp = sData.whatsapp;
                                mo.zalo = sData.zalo; mo.skype = sData.skype; mo.line_app = sData.line_app;
                                mo.wechat = sData.wechat; mo.gallery_urls = sData.gallery_urls;
                            }
                            // Update UI DOM Card
                            var cd = $('.tm-card[data-id="'+sData.id+'"]');
                            if (cd) {
                                cd.setAttribute('data-name', (sData.name + ' ' + sData.role).toLowerCase());
                                cd.setAttribute('data-dept', sData.department);
                                cd.setAttribute('data-status', sData.is_active);
                                cd.className = parseInt(sData.is_active,10) ? 'tm-card' : 'tm-card hidden-member';
                                
                                var cname = $('.tm-card-name', cd); if (cname) cname.textContent = sData.name;
                                var crole = $('.tm-card-role', cd); if (crole) crole.textContent = sData.role || '—';
                                var cdept = $('.tm-card-dept', cd); if (cdept) cdept.textContent = sData.department;
                                var cmg = $('.tm-card-photo img', cd);
                                var cini = $('.tm-card-initials', cd);
                                if (sData.photo_url) {
                                    if (cmg) { cmg.src = sData.photo_url; cmg.style.display = 'block'; }
                                    if (cini) cini.style.display = 'none';
                                } else {
                                    if (cmg) cmg.style.display = 'none';
                                    if (cini) cini.style.display = 'flex';
                                }
                                var cst = $('.tm-card-status', cd);
                                if (cst) {
                                    cst.innerHTML = sData.is_active === '1' 
                                        ? '<span class="tm-badge tm-badge-green" style="backdrop-filter:blur(4px);background:rgba(240,253,244,.9);"><span class="tm-badge-icon" style="background:var(--green)"></span>Hiển thị</span>'
                                        : '<span class="tm-badge tm-badge-gray" style="backdrop-filter:blur(4px);background:rgba(241,238,225,.9);"><span class="tm-badge-icon" style="background:var(--text-3)"></span>Ẩn</span>';
                                }
                            }
                            closeDrawer();
                        } else {
                            toast(msg||'Máy chủ gặp lỗi.', false);
                        }
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = oldHtml;
                    });
                    return;
                }
                
                // PHOTO CLEAR BTN
                if (t.closest('#tmm-photo-clear') || t.closest('#tm-photo-clear')) {
                    e.preventDefault();
                    if (t.closest('#tmm-photo-clear')) {
                        var pu = $('#tmm-photo-url'); if (pu) pu.value = '';
                        setDrawerPhoto('');
                    } else {
                        var pup = $('#tm-photo-input'); if (pup) pup.value = '';
                        var mimg = $('#tm-photo-img'), mph = $('#tm-photo-placeholder');
                        if (mimg) mimg.style.display = 'none';
                        if (mph) mph.style.display = 'flex';
                        t.closest('#tm-photo-clear').style.display='none';
                    }
                    return;
                }
                
                // MEDIA SELECT BTN
                var mediaBtn = t.closest('#tmm-media-btn') || t.closest('#tm-media-btn');
                if (mediaBtn) {
                    e.preventDefault();
                    if (typeof wp === 'undefined' || !wp.media) return;
                    var isDrawer = !!t.closest('#tmm-media-btn');
                    var frame = wp.media({ title: 'Chọn ảnh', button: { text: 'Dùng ảnh này' }, multiple: false });
                    frame.on('select', function() {
                        var att = frame.state().get('selection').first().toJSON();
                        if (isDrawer) {
                            var dInp = $('#tmm-photo-url'); if (dInp) dInp.value = att.url;
                            setDrawerPhoto(att.url);
                        } else {
                            var pInp = $('#tm-photo-input'); if (pInp) pInp.value = att.url;
                            var pbImg = $('#tm-photo-img'), pc = $('#tm-photo-clear'), pf = $('#tm-photo-placeholder');
                            if (pbImg){ pbImg.src = att.url; pbImg.style.display = 'block'; }
                            if (pc) pc.style.display = '';
                            if (pf) pf.style.display = 'none';
                        }
                    });
                    frame.open();
                }
                
                // GALLERY MULTI-SELECT BTN
                var galBtn = t.closest('#tmm-gallery-btn') || t.closest('#tm-gallery-btn');
                if (galBtn) {
                    e.preventDefault();
                    if (typeof wp === 'undefined' || !wp.media) return;
                    var isDraw = !!t.closest('#tmm-gallery-btn');
                    var fr = wp.media({ title: 'Chọn thư viện ảnh', button: { text: 'Thêm vào bộ sưu tập' }, multiple: true });
                    fr.on('select', function() {
                        var models = fr.state().get('selection').models;
                        var urls = [];
                        for(var i=0; i<models.length; i++) { urls.push(models[i].toJSON().url); }
                        if (urls.length > 0) {
                            var ta = isDraw ? $('#tmm-gallery') : $('#tm-gallery');
                            if (ta) {
                                var cur = (ta.value || '').trim();
                                ta.value = cur ? cur + '\n' + urls.join('\n') : urls.join('\n');
                                syncGalleryPreview(ta, isDraw ? $('#tmm-gallery-preview') : $('#tm-gallery-preview'));
                            }
                        }
                    });
                    fr.open();
                    return;
                }
                
                // DELETE GALLERY ITEM
                var galItem = t.closest('.tmm-gal-item');
                if (galItem) {
                    var isDr = !!t.closest('.tmm-dr');
                    var ta = isDr ? $('#tmm-gallery') : $('#tm-gallery');
                    var pr = isDr ? $('#tmm-gallery-preview') : $('#tm-gallery-preview');
                    if (ta) {
                        var urls = ta.value.split('\n').map(function(s) { return s.trim(); }).filter(Boolean);
                        var idx = parseInt(galItem.getAttribute('data-index'));
                        if (idx >= 0 && idx < urls.length) {
                            urls.splice(idx, 1);
                            ta.value = urls.join('\n');
                            syncGalleryPreview(ta, pr);
                        }
                    }
                    return;
                }
            });

            // (Note: Input handlers would normally go here, but textarea is hidden so input events are obsolete except programmatically, handled directly in syncGalleryPreview)

            // ESC CLose
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeDrawer();
            });

            // Live Input Sync
            document.addEventListener('input', function(e) {
                var t = e.target;
                if (t.id === 'tmm-name') {
                    var a = $('#tmm-title'); if (a) a.textContent = t.value || 'Chỉnh sửa';
                    var b = $('#tmm-prev-name'); if (b) b.textContent = t.value || '';
                    var c = $('#tmm-initials-big');
                    if (c) {
                        var pts = (t.value || '').trim().split(' ');
                        c.textContent = pts.length > 1 ? (pts[pts.length-2][0]+pts[pts.length-1][0]).toUpperCase() : (t.value ? t.value.substring(0,2).toUpperCase() : '?');
                    }
                }
                if (t.id === 'tmm-role') {
                    var x = $('#tmm-prev-role'); if (x) x.textContent = t.value || '';
                }
                if (t.id === 'tmm-photo-url') {
                    setDrawerPhoto(t.value.trim());
                }
            });

            // Toggle Pills Style
            document.addEventListener('change', function(e) {
                var t = e.target;
                if (t.name === 'tmm_active' || t.name === 'is_active') {
                    var grpName = t.name;
                    $$('[name="'+grpName+'"]').forEach(function(r) {
                        var p = r.closest(grpName==='is_active'?'.tmf-status-pill':'.tmm-pill');
                        if (p) {
                            p.classList.remove(grpName==='is_active'?'active-show':'on-show', grpName==='is_active'?'active-hide':'on-hide');
                            if (r.checked) p.classList.add(r.value === '1' ? (grpName==='is_active'?'active-show':'on-show') : (grpName==='is_active'?'active-hide':'on-hide'));
                        }
                    });
                }
                // Filter Cards (list filters)
                if (t.id === 'tm-dept-filter' || t.id === 'tm-status-filter') { filterList(); }
            });

            // Fast Input Event for Filters
            var sInput = $('#tm-search');
            if (sInput) sInput.addEventListener('input', filterList);

            function filterList() {
                var q = ($('#tm-search') ? $('#tm-search').value.toLowerCase() : '');
                var d = ($('#tm-dept-filter') ? $('#tm-dept-filter').value : '');
                var s = ($('#tm-status-filter') ? $('#tm-status-filter').value : '');
                
                $$('.tm-card').forEach(function(c) {
                    var nm = (c.getAttribute('data-name') || '');
                    var dp = (c.getAttribute('data-dept') || '');
                    var st = (c.getAttribute('data-status') || '');
                    var show = (!q || nm.indexOf(q) !== -1) && (!d || dp === d) && (!s || st === s);
                    c.style.display = show ? '' : 'none';
                });
            }

            /* ── Load Sortable ── */
            var sortBox = document.getElementById('tm-sortable');
            if (sortBox && typeof window.Sortable !== 'undefined') {
                window.Sortable.create(sortBox, {
                    handle: '.tm-card-drag',
                    animation: 200,
                    ghostClass: 'tm-card-ghost',
                    onEnd: function() {
                        var items = sortBox.querySelectorAll('.tm-card');
                        var pass = {};
                        for (var i = 0; i < items.length; i++) {
                            pass['ids['+i+']'] = items[i].getAttribute('data-id');
                        }
                        apiPost('bacera_team_order', pass, function(ok, msg) { if (ok) toast('Đã lưu thứ tự mới.'); });
                    }
                });
            }
        });
        </script>
        <style>
            .tmm-gal-item:hover .tmm-gal-del { opacity: 1 !important; }
        </style>
        <?php
    }
}
