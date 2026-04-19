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
                            .dsi { width:24px; height:24px; border-radius:5px; display:flex; align-items:center; justify-content:center; flex-shrink:0; pointer-events:none; overflow:hidden; }
                            .dsi svg { width:24px; height:24px; display:block; }
                            .dsi-text { font-size:9px; font-weight:900; color:#fff; letter-spacing:-.3px; }
                            .drawer-si-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
                        </style>
                        <div style="display:grid;grid-template-columns:1fr;gap:8px;">
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="dsi" style="background:#3d2f26">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                    </div>Điện thoại</label>
                                    <input type="text" id="tmm-phone" class="tmf-input" placeholder="090...">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="dsi" style="background:#fff;border:1px solid #C4CFE3">
                                        <svg viewBox="0 0 92 92" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="4" y="4" width="84" height="84" rx="12" fill="white"/><path d="M11 55.1236H19.4828V34.5227L7.36475 25.4341V51.4881C7.36475 53.4997 8.9947 55.1236 11 55.1236Z" fill="#4285F4"/><path d="M48.5664 55.1236H57.0492C59.0608 55.1236 60.6846 53.4937 60.6846 51.4881V25.4341L48.5664 34.5227" fill="#34A853"/><path d="M48.5664 18.7693V34.5229L60.6846 25.4343V20.587C60.6846 16.0912 55.5526 13.5282 51.9595 16.2245" fill="#FBBC04"/><path d="M19.4893 34.5227V18.769L34.0311 29.6754L48.5729 18.769V34.5227L34.0311 45.429" fill="#EA4335"/><path d="M7.36475 20.587V25.4343L19.4829 34.5229V18.7693L16.0898 16.2245C12.4907 13.5282 7.36475 16.0912 7.36475 20.587Z" fill="#C5221F"/></svg>
                                    </div>Email</label>
                                    <input type="email" id="tmm-email" class="tmf-input" placeholder="admin@domain.com">
                                </div>
                            </div>
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="dsi">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 93 92" fill="none"><rect x="1" width="91.5618" height="91.5618" rx="15" fill="#337FFF"/><path d="M57.4233 48.6403L58.7279 40.3588H50.6917V34.9759C50.6917 32.7114 51.8137 30.4987 55.4013 30.4987H59.1063V23.4465C56.9486 23.1028 54.7685 22.9168 52.5834 22.8901C45.9692 22.8901 41.651 26.8626 41.651 34.0442V40.3588H34.3193V48.6403H41.651V68.671H50.6917V48.6403H57.4233Z" fill="white"/></svg>
                                    </div>Facebook</label>
                                    <input type="url" id="tmm-facebook" class="tmf-input" placeholder="https://facebook.com/...">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="dsi">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 93 92" fill="none"><rect x="1" width="91.5618" height="91.5618" rx="15" fill="url(#ig_d1)"/><path d="M34 46c0-6.6 5.4-12 12-12s12 5.4 12 12-5.4 12-12 12-12-5.4-12-12zm-5 0c0 9.4 7.6 17 17 17s17-7.6 17-17-7.6-17-17-17-17 7.6-17 17zm32-17.8c0 2.2 1.8 4 4 4s4-1.8 4-4-1.8-4-4-4-4 1.8-4 4zM22 64.4c-2.4-.1-3.8-.5-4.6-.8-1.2-.5-2-.9-2.9-1.8-.9-.9-1.4-1.7-1.8-2.9-.3-.9-.7-2.2-.8-4.6-.1-2.6-.2-3.4-.2-10.3s.1-7.7.2-10.3c.1-2.4.5-3.7.8-4.6.4-1.2.9-2 1.8-2.9.9-.9 1.7-1.4 2.9-1.8.9-.3 2.2-.7 4.6-.8 2.6-.1 3.4-.1 10.1-.1s7.5.1 10.1.1c2.4.1 3.7.5 4.6.8 1.2.4 2 .9 2.9 1.8.9.9 1.4 1.7 1.8 2.9.3.9.7 2.2.8 4.6.1 2.6.1 3.4.1 10.3s-.1 7.7-.1 10.3c-.1 2.4-.5 3.7-.8 4.6-.4 1.2-.9 2-1.8 2.9-.9.9-1.7 1.4-2.9 1.8-.9.3-2.2.7-4.6.8-2.6.1-3.4.1-10.1.1s-7.5.1-10.1-.1zm-.2-49.5c-2.7.1-4.5.5-6.1 1.1-1.6.6-3 1.5-4.4 2.9s-2.2 2.8-2.9 4.4c-.6 1.6-1 3.4-1.1 6.1-.1 2.7-.1 3.5-.1 10.1s.1 7.4.1 10.1c.1 2.7.5 4.5 1.1 6.1.6 1.6 1.5 3 2.9 4.4 1.4 1.4 2.8 2.2 4.4 2.9 1.6.6 3.4 1 6.1 1.1 2.7.1 3.5.1 10.2.1s7.5-.1 10.2-.1c2.7-.1 4.5-.5 6.1-1.1 1.6-.6 3-1.5 4.4-2.9 1.4-1.4 2.2-2.8 2.9-4.4.6-1.6 1-3.4 1.1-6.1.1-2.7.1-3.5.1-10.1s-.1-7.4-.1-10.1c-.1-2.7-.5-4.5-1.1-6.1-.6-1.6-1.5-3-2.9-4.4-1.4-1.4-2.8-2.2-4.4-2.9-1.6-.6-3.4-1-6.1-1.1-2.7-.1-3.5-.1-10.2-.1s-7.5 0-10.2.1z" fill="white"/><defs><linearGradient id="ig_d1" x1="91" y1="92" x2="-0.6" y2="0" gradientUnits="userSpaceOnUse"><stop stop-color="#FBE18A"/><stop offset="0.21" stop-color="#FCBB45"/><stop offset="0.38" stop-color="#F75274"/><stop offset="0.52" stop-color="#D53692"/><stop offset="0.74" stop-color="#8F39CE"/><stop offset="1" stop-color="#5B4FE9"/></linearGradient></defs></svg>
                                    </div>Instagram</label>
                                    <input type="url" id="tmm-instagram" class="tmf-input" placeholder="https://instagram.com/...">
                                </div>
                            </div>
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="dsi">
                                        <svg viewBox="0 0 93 92" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="0" width="92" height="92" rx="15" fill="black"/><path d="M50.7568 42.1716L69.3704 21H64.9596L48.7974 39.383L35.8887 21H21L40.5205 48.7983L21 71H25.4111L42.4788 51.5869L56.1113 71H71L50.7557 42.1716H50.7568ZM44.7152 49.0433L42.7374 46.2752L27.0005 24.2492H33.7756L46.4755 42.0249L48.4533 44.7929L64.9617 67.8986H58.1865L44.7152 49.0443V49.0433Z" fill="white"/></svg>
                                    </div>X / Twitter</label>
                                    <input type="url" id="tmm-x_twitter" class="tmf-input" placeholder="https://x.com/...">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="dsi">
                                        <svg viewBox="0 0 92 92" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="92" height="92" rx="15" fill="black"/><path fill-rule="evenodd" clip-rule="evenodd" d="M55.5 29.4C58.6 31.6 62.3 32.8 66.1 32.8V25.3c-.7 0-1.5-.1-2.2-.3v6c-3.8.1-7.5-1.1-10.5-3.3v15.3c-.1 2.5-.8 5-2.1 7.2-1.3 2.1-3.1 3.9-5.3 5.1-2.2 1.2-4.7 1.8-7.2 1.7-2.5-.1-4.9-.9-7-2.3 1.9 2 4.4 3.3 7.1 3.8 2.7.6 5.5.3 8.1-.7 2.5-1 4.7-2.8 6.3-5 1.5-2.3 2.3-4.9 2.3-7.7V29.4zm3.7-7.6c-1.5-1.7-2.5-3.9-2.7-6.1v-1h-2.1c.3 1.5.8 2.9 1.6 4.1 1 1.3 2.1 2.3 3.2 3zm-22.8 27.2c-.7-1-.9-2.1-.8-3.4.1-1.2.5-2.4 1.1-3.4.6-1.1 1.5-2 2.5-2.6 1-.6 2.2-.9 3.4-.9.7 0 1.3.1 2 .3v-7.7c-.7-.1-1.5-.1-2.2 0v6c-1.5-.5-3.2-.4-4.6.3-1.5.7-2.6 1.9-3.2 3.4-.6 1.5-.6 3.1 0 4.6.5 1.5 1.6 2.8 3 3.4z" fill="#EE1D52"/><path fill-rule="evenodd" clip-rule="evenodd" d="M53.3 37.6c3.1 2.2 6.8 3.4 10.5 3.3v-5c-2.1-.5-4.1-1.6-5.6-3.2C57 31.8 56 30.7 55.2 29.5c-.8-1.2-1.4-2.6-1.6-4.1H48.1v30c-.1 1.3-.5 2.6-1.3 3.7-.8 1.1-1.9 1.9-3.1 2.3-1.3.4-2.7.4-4-.1-1.3-.4-2.4-1.2-3.2-2.2-1.3-.7-2.3-1.7-2.9-3-.6-1.3-.8-2.8-.4-4.2.3-1.4 1.1-2.6 2.2-3.5 1.1-.9 2.5-1.4 4-1.4.7 0 1.3.1 2 .3V37c-2.7.1-5.4 1-7.6 2.5-2.2 1.5-4 3.7-4.9 6.2-1 2.5-1.3 5.3-.7 7.9.6 2.7 1.9 5.1 3.8 7 2.1 1.4 4.5 2.2 7 2.3 2.5.1 5-.5 7.3-1.7 2.2-1.2 4.1-3 5.4-5.1 1.3-2.2 2-4.7 2-7.2L53.3 37.6z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M63.8 35v-1.6c-2 0-3.9-.5-5.6-1.6 1.5 1.6 3.5 2.7 5.6 3.2zM53.5 24.7V23.8c-.8-.1-1.5-.2-2.3-.2V14h-7.5v30c-.1 1.7-.7 3.3-1.9 4.5-1.2 1.2-2.8 1.9-4.5 1.9-.99 0-1.97-.23-2.85-.65.8 1.1 2 1.9 3.2 2.2 1.3.4 2.7.4 4-.1 1.3-.4 2.4-1.2 3.1-2.3.8-1.1 1.2-2.4 1.3-3.7V24.7h5.5zM41.4 40.9V39.2c-3.1-.4-6.3.3-9 1.9-2.7 1.6-4.8 4-5.9 7-.9 2.9-1 6.2 0 9.2 1 3 2.9 5.6 5.5 7.3-1.9-2-3.1-4.4-3.6-7.1-.5-2.7-.2-5.4.8-7.9 1-2.5 2.7-4.6 4.9-6.1 2.3-1.5 4.9-2.4 7.3-2.6z" fill="#69C9D0"/></svg>
                                    </div>TikTok</label>
                                    <input type="url" id="tmm-tiktok" class="tmf-input" placeholder="https://tiktok.com/...">
                                </div>
                            </div>
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="dsi">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 93 93" fill="none"><rect x="1" y="1" width="91.5618" height="91.5618" rx="15" fill="#006699"/><path d="M37.1339 63.4304V40.9068H29.6473V63.4304H37.1346H37.1339ZM33.3922 37.8321C36.0023 37.8321 37.6273 36.1025 37.6273 33.9411C37.5785 31.7304 36.0023 30.0491 33.4418 30.0491C30.8795 30.0491 29.2061 31.7304 29.2061 33.9409C29.2061 36.1023 30.8305 37.8319 33.3431 37.8319H33.3916L33.3922 37.8321ZM41.2777 63.4304H48.7637V50.8535C48.7637 50.1813 48.8125 49.5072 49.0103 49.0271C49.5513 47.6815 50.7831 46.2887 52.8517 46.2887C55.5599 46.2887 56.644 48.354 56.644 51.3822V63.4304H64.1297V50.516C64.1297 43.598 60.4369 40.3787 55.5115 40.3787C51.4733 40.3787 49.6998 42.6357 48.7144 44.173H48.7643V40.9075H41.2781C41.3759 43.0205 41.2775 63.4312 41.2775 63.4312L41.2777 63.4304Z" fill="white"/></svg>
                                    </div>LinkedIn</label>
                                    <input type="url" id="tmm-linkedin" class="tmf-input" placeholder="https://linkedin.com/...">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="dsi">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 93 93" fill="none"><rect x="1" y="1" width="91.5618" height="91.5618" rx="15" fill="#FF0000"/><path fill-rule="evenodd" clip-rule="evenodd" d="M67.5615 29.2428C69.8115 29.8504 71.58 31.6234 72.1778 33.8708C73.2654 37.9495 73.2654 46.4647 73.2654 46.4647C73.2654 46.4647 73.2654 54.98 72.1778 59.0586C71.5717 61.3144 69.8032 63.0873 67.5615 63.6866C63.4932 64.7771 47.1703 64.7771 47.1703 64.7771C47.1703 64.7771 30.8557 64.7771 26.7791 63.6866C24.5291 63.079 22.7606 61.306 22.1628 59.0586C21.0752 54.98 21.0752 46.4647 21.0752 46.4647C21.0752 46.4647 21.0752 37.9495 22.1628 33.8708C22.7689 31.615 24.5374 29.8421 26.7791 29.2428C30.8557 28.1523 47.1703 28.1523 47.1703 28.1523C47.1703 28.1523 63.4932 28.1523 67.5615 29.2428ZM55.5142 46.4647L41.9561 54.314V38.6154L55.5142 46.4647Z" fill="white"/></svg>
                                    </div>YouTube</label>
                                    <input type="url" id="tmm-youtube" class="tmf-input" placeholder="https://youtube.com/...">
                                </div>
                            </div>
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="dsi">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 93 92" fill="none"><rect x="1" width="91.5618" height="91.5618" rx="15" fill="#337FFF"/><path fill-rule="evenodd" clip-rule="evenodd" d="M46.4 21C32 21 21 31.3 21 45.3c0 7.3 3 13.6 8 17.9l.1 4.5 5.1-2.2c2.3.6 4.8.9 7.4.9C56 66.4 72 56 72 45.3 72 31.3 60.7 21 46.4 21zm15 18.7L53.2 51.4c-1.2 1.9-3.8 2.3-5.6 1L41.1 48c-.6-.4-1.3-.4-1.8 0l-8 6-1.1-1.4 7.5-11.7c1.2-1.9 3.7-2.3 5.5-1L49.2 44c.5.4 1.3.4 1.8 0l8-6 1.4 1.7z" fill="white"/></svg>
                                    </div>Messenger</label>
                                    <input type="url" id="tmm-messenger" class="tmf-input" placeholder="https://m.me/...">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="dsi">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 93 93" fill="none"><rect x="1" y="1" width="91.5618" height="91.5618" rx="15" fill="url(#ms_d)"/><path fill-rule="evenodd" clip-rule="evenodd" d="M46.4 21C32 21 21 31.3 21 45.3c0 7.3 3 13.6 8 17.9l.1 4.5 5.1-2.2c2.3.6 4.8.9 7.4.9C56 66.4 72 56 72 45.3 72 31.3 60.7 21 46.4 21zm15 18.7L53.2 51.4c-1.2 1.9-3.8 2.3-5.6 1L41.1 48c-.6-.4-1.3-.4-1.8 0l-8 6-1.1-1.4 7.5-11.7c1.2-1.9 3.7-2.3 5.5-1L49.2 44c.5.4 1.3.4 1.8 0l8-6 1.4 1.7z" fill="white"/><defs><radialGradient id="ms_d" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(15 93) scale(101)"><stop stop-color="#0099FF"/><stop offset="0.6" stop-color="#A033FF"/><stop offset="0.9" stop-color="#FF5280"/><stop offset="1" stop-color="#FF7061"/></radialGradient></defs></svg>
                                    </div>Pinterest</label>
                                    <input type="url" id="tmm-pinterest" class="tmf-input" placeholder="https://pinterest.com/...">
                                </div>
                            </div>
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="dsi">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 93 92" fill="none"><rect x="1" width="91.5618" height="91.5618" rx="15" fill="#00D95F"/><path d="M23.5 66.8l3.3-12.2c-2.6-4.7-3.5-10.3-2.4-15.6 1.1-5.3 4.1-10 8.5-13.3 4.3-3.3 9.7-4.9 15.2-4.6 5.4.4 10.5 2.7 14.4 6.5s6.2 8.8 6.6 14.3c.4 5.4-1.1 10.8-4.4 15.2-3.2 4.4-7.9 7.4-13.2 8.6-5.3 1.2-10.9.4-15.7-2.2L23.5 66.8zm12.9-7.9l.8.5c3.5 2 7.6 2.9 11.6 2.4 4-.5 7.7-2.3 10.6-5.1 2.9-2.8 4.7-6.5 5.3-10.4.5-4-.3-8-2.3-11.5-2-3.5-5.1-6.3-8.8-7.8-3.7-1.6-7.9-1.8-11.8-.8-3.9 1-7.3 3.3-9.8 6.5-2.5 3.2-3.8 7.1-3.8 11.1 0 3.3.9 6.6 2.7 9.5l.5.8-1.8 6.8 6.8-1.8z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M55 46.9c-.5-.3-1-.6-1.6-.7-.6-.1-1.2-.1-1.7 0-.8.3-1.4 1.6-1.9 2.3-.1.2-.3.3-.5.3-.2.1-.4 0-.6-.1-3.1-1.2-5.6-3.4-7.3-6.3-.1-.2-.2-.4-.2-.7 0-.2.1-.4.3-.6.6-.6 1-.3 1.3-2.1.1-.9-.1-1.8-.5-2.6-.3-1.1-1-2.1-1.9-2.8-.5-.2-1-.3-1.5-.2-.5.1-1 .3-1.3.6-.6.6-1.1 1.3-1.5 2.1-.4.8-.5 1.6-.5 2.5 0 .5.1 1 .2 1.5.3 1.1.8 2.2 1.4 3.2.4.7.9 1.4 1.4 2.1 1.7 2.3 3.8 4.2 6.3 5.7 1.2.8 2.5 1.4 3.8 1.9 1.4.6 3 .9 4.5.7.9-.1 1.7-.5 2.4-1 .7-.5 1.3-1.2 1.7-2 .2-.5.3-1 .2-1.5-.2-1.1-1.7-1.7-2.6-2.3z" fill="white"/></svg>
                                    </div>WhatsApp</label>
                                    <input type="text" id="tmm-whatsapp" class="tmf-input" placeholder="+84 9x...">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="dsi" style="background:#34AADF">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 93 93" fill="none"><path d="M25 53.6C25 53.6 43.7 45.7 50.2 43c2.5-1.1 10.9-4.6 10.9-4.6s3.9-1.5 3.6 2.2c-.1 1.5-1 6.9-1.8 12.8-1.3 8.3-2.7 17.4-2.7 17.4S59.9 73.2 58 73.7c-1.8.4-4.8-1.6-5.4-2l-7.8-5.6c-2.8-2.4-1.9-3.8.4-5.9 3.9-3.6 8.5-8.1 11.3-11l-17.2 11.3-6.6-2.2-7.7-2.7s-2.6-1.7 1.8-3.4z" fill="white"/></svg>
                                    </div>Telegram</label>
                                    <input type="url" id="tmm-telegram" class="tmf-input" placeholder="https://t.me/...">
                                </div>
                            </div>
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="dsi" style="background:#0068FF;font-size:8px;font-weight:900;color:#fff;display:flex;align-items:center;justify-content:center;">Zalo</div>Zalo</label>
                                    <input type="text" id="tmm-zalo" class="tmf-input" placeholder="SĐT hoặc link Zalo">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="dsi">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 92 93" fill="none"><rect y="1" width="91.5618" height="91.5618" rx="15" fill="#754A91"/><path d="M24.4 64.8v-4.3c-2-.5-3.7-1.5-5.2-2.8-1.1-1-2-2.1-2.7-3.4-.9-1.9-1.5-3.9-1.8-5.9-.4-2.6-.5-5.2-.4-7.8.1-1.2.1-2.4.3-3.6.2-2.3.8-4.5 1.7-6.6 1.2-2.6 3.3-4.7 5.9-5.9 1.8-.8 3.7-1.4 5.6-1.8 1.9-.4 3.8-.6 5.8-.7 1.7-.1 3.5-.1 5.2.05 2.5.1 5 .5 7.4 1.2 1.8.5 3.5 1.3 5 2.3 1.5 1 2.7 2.4 3.5 3.9.9 1.9 1.5 3.1 1.8 5.2.2 1.2.3 2.5.4 3.7.1 1.6.1 3.2.05 4.8-.05 1.6-.2 3.1-.4 4.7-.3 2.3-1 4.4-2.1 6.4-1.5 2.5-3.8 4.4-6.5 5.3-1.9.7-3.9 1.1-5.9 1.4-1.5.2-3 .4-4.5.4-1.1 0-2.2 0-3.3-.05-.6 0-1.3-.06-1.9-.1-.1 0-.2.04-.3.09-.1.05-.1.1-.1.16-1.3 1.5-2.6 3.05-3.9 4.5-.2.3-.5.55-.8.74-.2.1-.4.2-.6.2-.9-.1-1.6-.6-1.9-1.4-.1-.4-.2-.9-.2-1.3V64.8zm1.6 3l.2-.15 2.9-3.2c1.1-1.2 2.2-2.4 3.3-3.6.05-.1.1-.15.2-.18.1-.04.2-.04.3-.03.4.04.8.05 1.2.05 1.3 0 2.6 0 3.9-.09 1.2-.1 2.4-.25 3.6-.45 1.3-.2 2.6-.5 3.8-.87 2.8-.75 4.9-2.35 6.3-4.9.8-1.6 1.3-3.3 1.5-5.1.2-1.7.3-3.4.4-5.1.05-2.1-.1-4.2-.4-6.3-.3-1.5-.8-2.9-1.5-4.3-.8-1.7-1.9-3.1-3.6-4.1-2.6-1.4-5.5-2.1-8.4-2.4-.8-.1-1.6-.15-2.4-.18-1.7-.08-3.4-.07-5.1 0-1.2.09-2.4.25-3.6.48-1.8.33-3.5.92-5.1 1.75-.9.47-1.7 1.1-2.5 1.86-1.1 1.3-1.9 2.85-2.4 4.5-.6 2-.9 4-.93 6.1-.1 2.1-.08 4.1.1 6.2.1 1.2.3 2.4.6 3.6.45 1.8 1.3 3.4 2.5 4.8 1.3 1.5 3 2.6 4.9 3.1.25.07.4.15.4.42-.02 1-.01 2 0 3.1l-.02 5.1z" fill="white"/><path d="M23.5 36.7c-.1-.8.3-1.3.8-1.8.7-.6 1.4-1.1 2.2-1.6.4-.2.8-.3 1.2-.26.4.07.8.28 1.1.58.9 1 1.8 2.05 2.5 3.2.4.5.7 1.1.9 1.7.07.24.08.5.02.74-.07.24-.2.46-.37.64-.4.39-.84.73-1.3 1.03-.2.18-.4.42-.48.68-.08.27-.07.55.01.82.3 1.3.97 2.5 1.9 3.5 1.1 1.35 2.6 2.4 4.3 3 .4.2.8.3 1.2.25.24-.07.45-.22.6-.43.25-.3.52-.59.74-.88.29-.41.73-.7 1.23-.8s1 .05 1.45.34c1.2.68 2.3 1.47 3.3 2.35.25.21.5.42.75.63.29.22.5.53.6.88.1.36.1.74-.03 1.09-.2.56-.5 1.08-.9 1.54-.5.68-1.1 1.25-1.8 1.67-.35.2-.74.31-1.14.34-.4.03-.8-.04-1.17-.2-2.8-1.1-5.4-2.7-7.8-4.6-2.3-1.9-4.3-4.1-5.9-6.6-1.4-2-2.5-4.3-3.3-6.6-.08-.28-.15-.57-.2-.86-.02-.12-.02-.25-.01-.37z" fill="white"/></svg>
                                    </div>Viber</label>
                                    <input type="text" id="tmm-viber" class="tmf-input" placeholder="+84 9x...">
                                </div>
                            </div>
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="dsi">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 92 92" fill="none"><rect width="92" height="92" rx="15" fill="#00B7F0"/><text x="10" y="62" font-family="Arial" font-weight="900" font-size="36" fill="white">S</text></svg>
                                    </div>Skype</label>
                                    <input type="text" id="tmm-skype" class="tmf-input" placeholder="Skype ID">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="dsi">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 93 93" fill="none"><rect x="1" y="1" width="91.5618" height="91.5618" rx="15" fill="#51C332"/><path d="M55.9 36.5c1 0 1.9.1 2.9.2-1.7-7.6-9.6-13.3-19.2-13.3-10.8 0-19.6 7.3-19.6 16.4 0 5.2 2.9 9.8 7.5 12.8l-2.6 5.2 7-3 1.7.5c1.6.3 3.1.5 4.7.5v-3c0-9 8.8-16.3 19.6-16.3zm-9.8-5.7c1.4 0 2.5 1.1 2.5 2.5s-1.1 2.5-2.5 2.5-2.5-1.1-2.5-2.5 1.1-2.5 2.5-2.5zm-13 4.9c-1.4 0-2.5-1.1-2.5-2.5s1.1-2.5 2.5-2.5 2.5 1.1 2.5 2.5-1.1 2.5-2.5 2.5z" fill="white"/><path d="M72.2 53c0-7.2-7.3-13.1-16.3-13.1-9 0-16.3 5.9-16.3 13.1s7.3 13.1 16.3 13.1c1.5 0 2.9-.2 4.3-.5l8.8 3.8-3-6.1c3.8-2.4 6.2-6.1 6.2-10.3zm-21.2-.8c-1.4 0-2.5-1.1-2.5-2.5s1.1-2.5 2.5-2.5 2.5 1.1 2.5 2.5-1.1 2.5-2.5 2.5zm9.8 0c-1.4 0-2.5-1.1-2.5-2.5s1.1-2.5 2.5-2.5 2.5 1.1 2.5 2.5-1.1 2.5-2.5 2.5z" fill="white"/></svg>
                                    </div>WeChat</label>
                                    <input type="text" id="tmm-wechat" class="tmf-input" placeholder="WeChat ID">
                                </div>
                            </div>
                            <div class="tmf-field">
                                <label class="tmf-label"><div class="dsi" style="background:#06C755">
                                    <span class="dsi-text">LINE</span>
                                </div>Line App</label>
                                <input type="text" id="tmm-line_app" class="tmf-input" placeholder="Line ID">
                            </div>
                        </div>
                    </div>


                                    <input type="email" id="tmm-email" class="tmf-input" placeholder="admin@domain.com">
                                </div>
                            </div>
                            <!-- Facebook & Instagram -->
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="drawer-social-icon" style="border:none;"><svg viewBox="0 0 72 72" fill="none"><path d="M46.4927 38.6403L47.7973 30.3588H39.7611V24.9759C39.7611 22.7114 40.883 20.4987 44.4706 20.4987H48.1756V13.4465C46.018 13.1028 43.8378 12.9168 41.6527 12.8901C35.0385 12.8901 30.7204 16.8626 30.7204 24.0442V30.3588H23.3887V38.6403H30.7204V58.671H39.7611V38.6403H46.4927Z" fill="#337FFF"/></svg></div>Facebook</label>
                                    <input type="url" id="tmm-facebook" class="tmf-input" placeholder="https://facebook.com/...">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="drawer-social-icon" style="border:none;"><svg viewBox="0 0 72 72" fill="none"><path d="M27.4456 35.7808C27.4456 31.1786 31.1776 27.4468 35.7826 27.4468C40.3875 27.4468 44.1216 31.1786 44.1216 35.7808C44.1216 40.383 40.3875 44.1148 35.7826 44.1148C31.1776 44.1148 27.4456 40.383 27.4456 35.7808ZM22.9377 35.7808C22.9377 42.8708 28.6883 48.618 35.7826 48.618C42.8768 48.618 48.6275 42.8708 48.6275 35.7808C48.6275 28.6908 42.8768 22.9436 35.7826 22.9436C28.6883 22.9436 22.9377 28.6908 22.9377 35.7808ZM46.1342 22.4346C46.1339 23.0279 46.3098 23.608 46.6394 24.1015C46.9691 24.595 47.4377 24.9797 47.9861 25.2069C48.5346 25.4342 49.1381 25.4939 49.7204 25.3784C50.3028 25.2628 50.8378 24.9773 51.2577 24.5579C51.6777 24.1385 51.9638 23.6041 52.0799 23.0222C52.1959 22.4403 52.1367 21.8371 51.9097 21.2888C51.6828 20.7406 51.2982 20.2719 50.8047 19.942C50.3112 19.6122 49.7309 19.436 49.1372 19.4358H49.136C48.3402 19.4361 47.5771 19.7522 47.0142 20.3144C46.4514 20.8767 46.1349 21.6392 46.1342 22.4346ZM25.6765 56.1302C23.2377 56.0192 21.9121 55.6132 21.0311 55.2702C19.8632 54.8158 19.0299 54.2746 18.1538 53.4002C17.2777 52.5258 16.7354 51.6938 16.2827 50.5266C15.9393 49.6466 15.533 48.3214 15.4222 45.884C15.3009 43.2488 15.2767 42.4572 15.2767 35.781C15.2767 29.1048 15.3029 28.3154 15.4222 25.678C15.5332 23.2406 15.9425 21.918 16.2827 21.0354C16.7374 19.8682 17.2789 19.0354 18.1538 18.1598C19.0287 17.2842 19.8612 16.7422 21.0311 16.2898C21.9117 15.9466 23.2377 15.5406 25.6765 15.4298C28.3133 15.3086 29.1054 15.2844 35.7826 15.2844C42.4598 15.2844 43.2527 15.3106 45.8916 15.4298C48.3305 15.5408 49.6539 15.9498 50.537 16.2898C51.7049 16.7422 52.5382 17.2854 53.4144 18.1598C54.2905 19.0342 54.8308 19.8682 55.2855 21.0354C55.6289 21.9154 56.0351 23.2406 56.146 25.678C56.2673 28.3154 56.2915 29.1048 56.2915 35.781C56.2915 42.4572 56.2673 43.2466 56.146 45.884C56.0349 48.3214 55.6267 49.6462 55.2855 50.5266C54.8308 51.6938 54.2893 52.5266 53.4144 53.4002C52.5394 54.2738 51.7049 54.8158 50.537 55.2702C49.6565 55.6134 48.3305 56.0194 45.8916 56.1302C43.2549 56.2514 42.4628 56.2756 35.7826 56.2756C29.1024 56.2756 28.3125 56.2514 25.6765 56.1302ZM25.4694 10.9322C22.8064 11.0534 20.9867 11.4754 19.3976 12.0934C17.7518 12.7316 16.3585 13.5878 14.9663 14.977C13.5741 16.3662 12.7195 17.7608 12.081 19.4056C11.4626 20.9948 11.0403 22.8124 10.9191 25.4738C10.7958 28.1394 10.7676 28.9916 10.7676 35.7808C10.7676 42.57 10.7958 43.4222 10.9191 46.0878C11.0403 48.7494 11.4626 50.5668 12.081 52.156C12.7195 53.7998 13.5743 55.196 14.9663 56.5846C16.3583 57.9732 17.7518 58.8282 19.3976 59.4682C20.9897 60.0862 22.8064 60.5082 25.4694 60.6294C28.138 60.7506 28.9893 60.7808 35.7826 60.7808C42.5759 60.7808 43.4286 60.7526 46.0958 60.6294C48.759 60.5082 50.5774 60.0862 52.1676 59.4682C53.8124 58.8282 55.2066 57.9738 56.5989 56.5846C57.9911 55.1954 58.8438 53.7998 56.5989 52.156C60.1026 50.5668 60.5268 48.7492 60.6461 46.0878C60.7674 43.4202 60.7956 42.57 60.7956 35.7808C60.7956 28.9916 60.7674 28.1394 60.6461 25.4738C60.5248 22.8122 60.1026 20.9938 59.4842 19.4056C58.8438 17.7618 57.9889 16.3684 56.5989 14.977C55.2088 13.5856 53.8124 12.7316 52.1696 12.0934C50.5775 11.4754 48.7588 11.0514 46.0978 10.9322C43.4306 10.811 42.5779 10.7808 35.7846 10.7808C28.9913 10.7808 28.138 10.809 25.4694 10.9322Z" fill="url(#ig_dr)"/><defs><radialGradient id="ig_dr" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(17.4144 61.017) scale(65.31 65.2708)"><stop offset="0.09" stop-color="#FA8F21"/><stop offset="0.78" stop-color="#D82D7E"/></radialGradient></defs></svg></div>Instagram</label>
                                    <input type="url" id="tmm-instagram" class="tmf-input" placeholder="https://instagram.com/...">
                                </div>
                            </div>
                            <!-- TikTok & X/Twitter -->
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="drawer-social-icon" style="border:none;"><svg viewBox="0 0 72 72" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M45.6721 29.4285C48.7387 31.6085 52.4112 32.7733 56.1737 32.7592V25.3024C55.434 25.3045 54.6963 25.2253 53.9739 25.0663V31.0068C50.203 31.0135 46.5252 29.8354 43.4599 27.6389V42.9749C43.4507 45.4914 42.7606 47.9585 41.4628 50.1146C40.165 52.2706 38.3079 54.0353 36.0885 55.2215C33.8691 56.4076 31.37 56.9711 28.8563 56.852C26.3426 56.733 23.9079 55.9359 21.8105 54.5453C23.7506 56.5082 26.2295 57.8513 28.9333 58.4044C31.6372 58.9576 34.4444 58.6959 36.9994 57.6526C39.5545 56.6093 41.7425 54.8312 43.2864 52.5436C44.8302 50.256 45.6605 47.5616 45.6721 44.8018V29.4285ZM48.3938 21.8226C46.8343 20.1323 45.8775 17.9739 45.6721 15.6832V14.7139H43.5842C43.8423 16.1699 44.4039 17.5553 45.2326 18.78C46.0612 20.0048 47.1383 21.0414 48.3938 21.8226ZM26.645 48.642C25.9213 47.6957 25.4779 46.5653 25.365 45.3793C25.2522 44.1934 25.4746 42.9996 26.0068 41.9338C26.5391 40.8681 27.3598 39.9731 28.3757 39.3508C29.3915 38.7285 30.5616 38.4039 31.7529 38.4139C32.4106 38.4137 33.0644 38.5143 33.6916 38.7121V31.0068C32.9584 30.9097 32.2189 30.8682 31.4794 30.8826V36.8728C29.9522 36.39 28.2992 36.4998 26.8492 37.1803C25.3992 37.8608 24.2585 39.0621 23.6539 40.5454C23.0494 42.0286 23.0252 43.6851 23.5864 45.1853C24.1475 46.6855 25.2527 47.9196 26.6823 48.642H26.645Z" fill="#EE1D52"/><path fill-rule="evenodd" clip-rule="evenodd" d="M43.4589 27.5892C46.5241 29.7857 50.2019 30.9638 53.9729 30.9571V25.0166C51.8243 24.5623 49.8726 23.4452 48.3927 21.8226C47.1372 21.0414 46.0601 20.0048 45.2315 18.78C44.4029 17.5553 43.8412 16.1699 43.5831 14.7139H38.09V44.8018C38.0849 46.1336 37.6629 47.4304 36.8831 48.51C36.1034 49.5897 35.0051 50.3981 33.7425 50.8217C32.4798 51.2453 31.1162 51.2629 29.8431 50.872C28.57 50.4811 27.4512 49.7012 26.6439 48.642C25.3645 47.9965 24.3399 46.9387 23.7354 45.6394C23.1309 44.3401 22.9818 42.875 23.3121 41.4805C23.6424 40.0861 24.4329 38.8435 25.556 37.9535C26.6791 37.0634 28.0693 36.5776 29.5023 36.5745C30.1599 36.5766 30.8134 36.6772 31.4411 36.8728V30.8826C28.7288 30.9477 26.0946 31.8033 23.8617 33.3444C21.6289 34.8855 19.8946 37.0451 18.8717 39.5579C17.8489 42.0708 17.5821 44.8276 18.1039 47.49C18.6258 50.1524 19.9137 52.6045 21.8095 54.5453C23.9073 55.9459 26.3458 56.7512 28.8651 56.8755C31.3845 56.9997 33.8904 56.4383 36.1158 55.2509C38.3413 54.0636 40.2031 52.2948 41.5027 50.133C42.8024 47.9712 43.4913 45.4973 43.4962 42.9749L43.4589 27.5892Z" fill="black"/><path fill-rule="evenodd" clip-rule="evenodd" d="M53.9736 25.0161V23.4129C52.0005 23.4213 50.0655 22.8696 48.3934 21.8221C49.8695 23.4493 51.8229 24.5674 53.9736 25.0161ZM43.5838 14.7134C43.5838 14.4275 43.4968 14.1292 43.4596 13.8434V12.874H35.8785V42.9744C35.872 44.6598 35.197 46.2738 34.0017 47.4621C32.8064 48.6504 31.1885 49.3159 29.503 49.3126C28.5106 49.3176 27.5311 49.0876 26.6446 48.6415C27.4519 49.7007 28.5707 50.4805 29.8438 50.8715C31.1169 51.2624 32.4805 51.2448 33.7432 50.8212C35.0058 50.3976 36.1041 49.5892 36.8838 48.5095C37.6636 47.4298 38.0856 46.1331 38.0907 44.8013V14.7134H43.5838ZM31.4418 30.8696V29.167C28.3222 28.7432 25.1511 29.3885 22.4453 30.9977C19.7394 32.6069 17.6584 35.0851 16.5413 38.0284C15.4242 40.9718 15.337 44.2067 16.2938 47.206C17.2506 50.2053 19.195 52.792 21.8102 54.5448C19.9287 52.5995 18.6545 50.1484 18.1433 47.4908C17.6321 44.8333 17.906 42.0844 18.9315 39.5799C19.957 37.0755 21.6897 34.924 23.918 33.3882C26.1463 31.8524 28.7736 30.9988 31.4791 30.9318L31.4418 30.8696Z" fill="#69C9D0"/></svg></div>TikTok</label>
                                    <input type="url" id="tmm-tiktok" class="tmf-input" placeholder="https://tiktok.com/...">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="drawer-social-icon" style="border:none;"><svg viewBox="0 0 72 72" fill="none"><path d="M40.7568 32.1716L59.3704 11H54.9596L38.7974 29.383L25.8887 11H11L30.5205 38.7983L11 61H15.4111L32.4788 41.5869L46.1113 61H61L40.7557 32.1716H40.7568ZM34.7152 39.0433L32.7374 36.2752L17.0005 14.2492H23.7756L36.4755 32.0249L38.4533 34.7929L54.9617 57.8986H48.1865L34.7152 39.0443V39.0433Z" fill="black"/></svg></div>X / Twitter</label>
                                    <input type="url" id="tmm-x_twitter" class="tmf-input" placeholder="https://x.com/...">
                                </div>
                            </div>
                            <!-- LinkedIn & YouTube -->
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="drawer-social-icon" style="border:none;"><svg viewBox="0 0 72 72" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.6975 11C12.6561 11 11 12.6057 11 14.5838V57.4474C11 59.4257 12.6563 61.03 14.6975 61.03H57.3325C59.3747 61.03 61.03 59.4255 61.03 57.4468V14.5838C61.03 12.6057 59.3747 11 57.3325 11H14.6975ZM26.2032 30.345V52.8686H18.7167V30.345H26.2032ZM26.6967 23.3793C26.6967 25.5407 25.0717 27.2703 22.4615 27.2703L22.4609 27.2701H22.4124C19.8998 27.2701 18.2754 25.5405 18.2754 23.3791C18.2754 21.1686 19.9489 19.4873 22.5111 19.4873C25.0717 19.4873 26.6478 21.1686 26.6967 23.3793ZM37.833 52.8686H30.3471L30.3469 52.8694C30.3469 52.8694 30.4452 32.4588 30.3475 30.3458H37.8336V33.5339C38.8288 31.9995 40.6098 29.8169 44.5808 29.8169C49.5062 29.8169 53.1991 33.0363 53.1991 39.9543V52.8686H45.7133V40.8204C45.7133 37.7922 44.6293 35.7269 41.921 35.7269C39.8524 35.7269 38.6206 37.1198 38.0796 38.4653C37.8819 38.9455 37.833 39.6195 37.833 40.2918V52.8686Z" fill="#006699"/></svg></div>LinkedIn</label>
                                    <input type="url" id="tmm-linkedin" class="tmf-input" placeholder="https://linkedin.com/...">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="drawer-social-icon" style="border:none;"><svg viewBox="0 0 71 72" fill="none"><path d="M33.3501 13.0437C25.9186 13.893 18.5132 20.0479 18.2075 28.84C18.0154 34.2083 19.5044 38.2356 24.4951 39.3664C26.6608 35.4553 23.7965 34.5927 23.3511 31.7633C21.5216 20.1686 36.4153 12.2615 44.2093 20.3563C49.6018 25.9615 46.0519 43.206 37.3541 41.4136C29.0231 39.7017 41.4323 25.9749 34.7823 23.2796C29.3767 21.0894 26.5037 29.9798 29.0667 34.396C27.5647 41.9902 24.3292 49.1464 25.6391 58.6715C29.8876 55.5158 31.3198 49.4727 32.4943 43.1702C34.6295 44.4978 35.7691 45.8789 38.4937 46.0935C48.5407 46.8891 54.1515 35.8263 52.7805 25.6218C51.5623 16.5749 42.7422 11.971 33.3501 13.0437Z" fill="#FF0000"/></svg></div>YouTube</label>
                                    <input type="url" id="tmm-youtube" class="tmf-input" placeholder="https://youtube.com/...">
                                </div>
                            </div>
                            <!-- WhatsApp & Zalo -->
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="drawer-social-icon" style="border:none;"><svg viewBox="0 0 71 72" fill="none"><path d="M12.5762 56.8405L15.8608 44.6381C13.2118 39.8847 12.3702 34.3378 13.4904 29.0154C14.6106 23.693 17.6176 18.952 21.9594 15.6624C26.3012 12.3729 31.6867 10.7554 37.1276 11.1068C42.5685 11.4582 47.6999 13.755 51.5802 17.5756C55.4604 21.3962 57.8292 26.4844 58.2519 31.9065C58.6746 37.3286 57.1228 42.7208 53.8813 47.0938C50.6399 51.4668 45.9261 54.5271 40.605 55.7133C35.284 56.8994 29.7125 56.1318 24.9131 53.5513L12.5762 56.8405ZM25.508 48.985L26.2709 49.4365C29.7473 51.4918 33.8076 52.3423 37.8191 51.8555C41.8306 51.3687 45.5681 49.5719 48.4489 46.7452C51.3298 43.9185 53.1923 40.2206 53.7463 36.2279C54.3002 32.2351 53.5143 28.1717 51.5113 24.6709C49.5082 21.1701 46.4003 18.4285 42.6721 16.8734C38.9438 15.3184 34.8045 15.0372 30.8993 16.0736C26.994 17.11 23.5422 19.4059 21.0817 22.6035C18.6212 25.801 17.2903 29.7206 17.2963 33.7514C17.293 37.0937 18.2197 40.3712 19.9732 43.2192L20.4516 44.0061L18.6153 50.8167L25.508 48.985Z" fill="#00D95F"/><path fill-rule="evenodd" clip-rule="evenodd" d="M44.0259 36.8847C43.5787 36.5249 43.0549 36.2716 42.4947 36.1442C41.9344 36.0168 41.3524 36.0186 40.793 36.1495C39.9524 36.4977 39.4093 37.8134 38.8661 38.4713C38.7516 38.629 38.5833 38.7396 38.3928 38.7823C38.2024 38.8251 38.0028 38.797 37.8316 38.7034C34.7543 37.5012 32.1748 35.2965 30.5122 32.4475C30.3704 32.2697 30.3033 32.044 30.325 31.8178C30.3467 31.5916 30.4555 31.3827 30.6286 31.235C31.2344 30.6368 31.6791 29.8959 31.9218 29.0809C31.9756 28.1818 31.7691 27.2863 31.3269 26.5011C30.985 25.4002 30.3344 24.42 29.4518 23.6762C28.9966 23.472 28.4919 23.4036 27.9985 23.4791C27.5052 23.5546 27.0443 23.7709 26.6715 24.1019C26.0242 24.6589 25.5104 25.3537 25.168 26.135C24.8256 26.9163 24.6632 27.7643 24.6929 28.6165C24.6949 29.0951 24.7557 29.5716 24.8739 30.0354C25.1742 31.1497 25.636 32.2144 26.2447 33.1956C26.6839 33.9473 27.163 34.6749 27.6801 35.3755C29.3607 37.6767 31.4732 39.6305 33.9003 41.1284C35.1183 41.8897 36.42 42.5086 37.7799 42.973C39.1924 43.6117 40.752 43.8568 42.2931 43.6824C43.1711 43.5499 44.003 43.2041 44.7156 42.6755C45.4281 42.1469 45.9995 41.4518 46.3795 40.6512C46.6028 40.1675 46.6705 39.6269 46.5735 39.1033C46.3407 38.0327 44.9053 37.4007 44.0259 36.8847Z" fill="white"/></svg></div>WhatsApp</label>
                                    <input type="text" id="tmm-whatsapp" class="tmf-input" placeholder="+84 9x...">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="drawer-social-icon" style="color:#0068FF;">Zalo</div>Zalo</label>
                                    <input type="text" id="tmm-zalo" class="tmf-input" placeholder="SĐT hoặc link Zalo">
                                </div>
                            </div>
                            <!-- Skype & WeChat -->
                            <div class="drawer-si-grid">
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="drawer-social-icon" style="border:none;"><svg viewBox="0 0 72 72" fill="none"><path d="M40.3522 25.55C40.3522 29.4089 38.8124 33.1097 36.0717 35.8384C33.3309 38.5671 29.6136 40.1 25.7376 40.1C21.8616 40.1 18.1443 38.5671 15.4036 35.8384C12.6628 33.1097 11.123 29.4089 11.123 25.55C11.123 21.6911 12.6628 17.9902 15.4036 15.2616C18.1443 12.5329 21.8616 11 25.7376 11C29.6136 11 33.3309 12.5329 36.0717 15.2616C38.8124 17.9902 40.3522 21.6911 40.3522 25.55Z" fill="url(#sk1dr)"/><path d="M60.7196 46.445C60.7196 50.3052 59.181 53.9903 56.4376 56.737C53.6941 59.4836 49.9783 61.03 46.1 61.03C42.2217 61.03 38.5059 59.4836 35.7624 56.737C33.019 53.9903 31.4805 50.3052 31.4805 46.445C31.4805 42.5848 33.019 38.8997 35.7624 36.153C38.5059 33.4064 42.2217 31.86 46.1 31.86C49.9783 31.86 53.6941 33.4064 56.4376 36.153C59.181 38.8997 60.7196 42.5848 60.7196 46.445Z" fill="url(#sk2dr)"/><path d="M59.0711 36.1084C59.0711 50.029 47.8575 61.03 33.9238 61.03C19.9901 61.03 9 50.029 9 36.1084C9 22.1878 19.9901 11 33.9238 11C47.8575 11 59.0711 22.1878 59.0711 36.1084Z" fill="url(#sk3dr)"/><path fill-rule="evenodd" clip-rule="evenodd" d="M29.4151 35.3084C28.4797 34.6934 27.6991 33.8709 27.1343 32.905C26.5889 31.8846 26.3197 30.7396 26.3534 29.5834C26.3121 28.116 26.8134 26.6849 27.7616 25.5634C28.7471 24.4371 30.0136 23.5918 31.4324 23.1134C32.9754 22.5688 34.6012 22.2954 36.2377 22.305C37.3084 22.2949 38.378 22.3769 39.4346 22.55C40.1909 22.6667 40.9337 22.8582 41.6521 23.1217C42.3117 23.3447 42.9072 23.7245 43.3873 24.2284C43.7411 24.6429 43.9315 25.172 43.9229 25.7167C43.9229 26.9218 43.0001 27.8584 41.9424 27.8584C41.5786 27.8554 41.2196 27.7747 40.8895 27.6217C40.0761 27.258 39.2381 26.9517 38.3818 26.705C37.5935 26.4974 36.7809 26.3965 35.9657 26.405C34.9033 26.366 33.8522 26.6345 32.939 27.1784C32.2147 27.6162 31.731 28.4682 31.731 29.4367C31.731 30.6418 32.5054 31.3334 34.0486 32.2334C34.7572 32.5845 35.8139 33.05 37.2188 33.63C37.3744 33.6792 37.5261 33.74 37.6726 33.8117C39.0436 34.3534 40.3581 35.0283 41.597 35.8267C42.5755 36.464 43.4006 37.3098 44.013 38.3034C44.6161 39.3476 44.9181 40.5382 44.8857 41.7434C44.9363 43.2189 44.513 44.672 43.6776 45.89C42.827 47.0433 41.6503 47.9157 40.2989 48.395C38.6702 48.9763 36.9491 49.2564 35.2199 49.2217C32.8437 49.3048 30.4788 48.8578 28.2972 47.9134C27.7395 47.6653 27.2505 47.2855 26.8723 46.8067C26.5583 46.368 26.3955 45.8392 26.4085 45.3C26.3929 45.0157 26.4393 44.7313 26.5444 44.4666C26.6495 44.2018 26.8109 43.9631 27.0175 43.7667C27.4504 43.388 28.0125 43.1899 28.5875 43.2134C29.2282 43.2221 29.8576 43.3838 30.4229 43.685C31.1259 44.0361 31.6804 44.2995 32.0864 44.475C32.5501 44.6689 33.0304 44.8207 33.5214 44.9284C34.1268 45.061 34.7453 45.1248 35.3651 45.1184C36.4611 45.1965 37.5518 44.9061 38.4635 44.2934C39.2363 43.7195 39.508 42.9317 39.508 42.0334C39.509 41.4201 39.275 40.8297 38.854 40.3834C38.2975 39.8095 37.6461 39.3359 36.9285 38.9834C36.0798 38.5367 34.8862 37.9923 33.3478 37.35C31.972 36.8024 30.6545 36.1184 29.4151 35.3084Z" fill="white"/><defs><linearGradient id="sk1dr" x1="23.4584" y1="11.1767" x2="28.0069" y2="39.9249" gradientUnits="userSpaceOnUse"><stop offset="0.012" stop-color="#00B7F0"/><stop offset="1" stop-color="#0078D4"/></linearGradient><linearGradient id="sk2dr" x1="33.446" y1="53.7417" x2="58.7384" y2="39.124" gradientUnits="userSpaceOnUse"><stop stop-color="#0078D4"/><stop offset="1" stop-color="#00BCF2"/></linearGradient><linearGradient id="sk3dr" x1="26.8904" y1="20.4817" x2="49.9459" y2="60.4589" gradientUnits="userSpaceOnUse"><stop stop-color="#00B7F0"/><stop offset="1" stop-color="#007CC1"/></linearGradient></defs></svg></div>Skype</label>
                                    <input type="text" id="tmm-skype" class="tmf-input" placeholder="Skype ID">
                                </div>
                                <div class="tmf-field">
                                    <label class="tmf-label"><div class="drawer-social-icon" style="border:none;"><svg viewBox="0 0 72 72" fill="none"><path d="M45.8956 26.0879C46.8845 26.0879 47.8503 26.1701 48.8032 26.2876C47.0805 18.7224 39.1346 13 29.5799 13C18.7651 13 10 20.3257 10 29.3599C10 34.5686 12.9306 39.1897 17.4761 42.1798L14.8948 47.3562L21.9268 44.336C23.4347 44.873 25.0016 45.3152 26.6753 45.521C26.4465 44.5226 26.3166 43.4991 26.3166 42.4487C26.3166 33.4273 35.0976 26.0879 45.8956 26.0879ZM36.1064 20.3615C37.4577 20.3615 38.5536 21.4608 38.5536 22.8158C38.5536 24.1713 37.4578 25.2698 36.1064 25.2698C34.7543 25.2698 33.6589 24.1713 33.6589 22.8158C33.6589 21.4607 34.7543 20.3615 36.1064 20.3615ZM23.0531 25.2698C21.7016 25.2698 20.6057 24.1713 20.6057 22.8158C20.6057 21.4608 21.7017 20.3615 23.0531 20.3615C24.4045 20.3615 25.5006 21.4608 25.5006 22.8158C25.5005 24.1713 24.4044 25.2698 23.0531 25.2698Z" fill="#51C332"/><path d="M62.2121 42.4484C62.2121 35.22 54.9051 29.3599 45.8956 29.3599C36.8858 29.3599 29.5799 35.22 29.5799 42.4484C29.5799 49.6763 36.8858 55.5365 45.8956 55.5365C47.3773 55.5365 48.7867 55.3271 50.1542 55.0297L58.9489 58.8084L55.9072 52.713C59.7191 50.3174 62.2121 46.6335 62.2121 42.4484ZM41.001 41.6303C39.6496 41.6303 38.5534 40.5314 38.5534 39.1757C38.5534 37.8207 39.6495 36.7222 41.001 41.6303C42.3528 36.7222 43.4482 37.8212 43.4482 39.1757C43.4482 40.5316 42.3526 41.6303 41.001 41.6303ZM50.7905 41.6303C49.4385 41.6303 48.3433 40.5314 48.3433 39.1757C48.3433 37.8207 49.4384 36.7222 50.7905 36.7222C52.1425 36.7222 53.238 37.8212 53.238 39.1757C53.238 40.5316 52.1425 41.6303 50.7905 41.6303Z" fill="#51C332"/></svg></div>WeChat</label>
                                    <input type="text" id="tmm-wechat" class="tmf-input" placeholder="WeChat ID">
                                </div>
                            </div>
                            <!-- Line App -->
                            <div class="tmf-field">
                                <label class="tmf-label"><div class="drawer-social-icon" style="background:#06C755;color:#fff;">LINE</div>Line App</label>
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
                                            <svg viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M27.4456 35.7808C27.4456 31.1786 31.1776 27.4468 35.7826 27.4468C40.3875 27.4468 44.1216 31.1786 44.1216 35.7808C44.1216 40.383 40.3875 44.1148 35.7826 44.1148C31.1776 44.1148 27.4456 40.383 27.4456 35.7808ZM22.9377 35.7808C22.9377 42.8708 28.6883 48.618 35.7826 48.618C42.8768 48.618 48.6275 42.8708 48.6275 35.7808C48.6275 28.6908 42.8768 22.9436 35.7826 22.9436C28.6883 22.9436 22.9377 28.6908 22.9377 35.7808ZM46.1342 22.4346C46.1339 23.0279 46.3098 23.608 46.6394 24.1015C46.9691 24.595 47.4377 24.9797 47.9861 25.2069C48.5346 25.4342 49.1381 25.4939 49.7204 25.3784C50.3028 25.2628 50.8378 24.9773 51.2577 24.5579C51.6777 24.1385 51.9638 23.6041 52.0799 23.0222C52.1959 22.4403 52.1367 21.8371 51.9097 21.2888C51.6828 20.7406 51.2982 20.2719 50.8047 19.942C50.3112 19.6122 49.7309 19.436 49.1372 19.4358H49.136C48.3402 19.4361 47.5771 19.7522 47.0142 20.3144C46.4514 20.8767 46.1349 21.6392 46.1342 22.4346ZM25.6765 56.1302C23.2377 56.0192 21.9121 55.6132 21.0311 55.2702C19.8632 54.8158 19.0299 54.2746 18.1538 53.4002C17.2777 52.5258 16.7354 51.6938 16.2827 50.5266C15.9393 49.6466 15.533 48.3214 15.4222 45.884C15.3009 43.2488 15.2767 42.4572 15.2767 35.781C15.2767 29.1048 15.3029 28.3154 15.4222 25.678C15.5332 23.2406 15.9425 21.918 16.2827 21.0354C16.7374 19.8682 17.2789 19.0354 18.1538 18.1598C19.0287 17.2842 19.8612 16.7422 21.0311 16.2898C21.9117 15.9466 23.2377 15.5406 25.6765 15.4298C28.3133 15.3086 29.1054 15.2844 35.7826 15.2844C42.4598 15.2844 43.2527 15.3106 45.8916 15.4298C48.3305 15.5408 49.6539 15.9498 50.537 16.2898C51.7049 16.7422 52.5382 17.2854 53.4144 18.1598C54.2905 19.0342 54.8308 19.8682 55.2855 21.0354C55.6289 21.9154 56.0351 23.2406 56.146 25.678C56.2673 28.3154 56.2915 29.1048 56.2915 35.781C56.2915 42.4572 56.2673 43.2466 56.146 45.884C56.0349 48.3214 55.6267 49.6462 55.2855 50.5266C54.8308 51.6938 54.2893 52.5266 53.4144 53.4002C52.5394 54.2738 51.7049 54.8158 50.537 55.2702C49.6565 55.6134 48.3305 56.0194 45.8916 56.1302C43.2549 56.2514 42.4628 56.2756 35.7826 56.2756C29.1024 56.2756 28.3125 56.2514 25.6765 56.1302ZM25.4694 10.9322C22.8064 11.0534 20.9867 11.4754 19.3976 12.0934C17.7518 12.7316 16.3585 13.5878 14.9663 14.977C13.5741 16.3662 12.7195 17.7608 12.081 19.4056C11.4626 20.9948 11.0403 22.8124 10.9191 25.4738C10.7958 28.1394 10.7676 28.9916 10.7676 35.7808C10.7676 42.57 10.7958 43.4222 10.9191 46.0878C11.0403 48.7494 11.4626 50.5668 12.081 52.156C12.7195 53.7998 13.5743 55.196 14.9663 56.5846C16.3583 57.9732 17.7518 58.8282 19.3976 59.4682C20.9897 60.0862 22.8064 60.5082 25.4694 60.6294C28.138 60.7506 28.9893 60.7808 35.7826 60.7808C42.5759 60.7808 43.4286 60.7526 46.0958 60.6294C48.759 60.5082 50.5774 60.0862 52.1676 59.4682C53.8124 58.8282 55.2066 57.9738 56.5989 56.5846C57.9911 55.1954 58.8438 53.7998 59.4842 52.156C60.1026 50.5668 60.5268 48.7492 60.6461 46.0878C60.7674 43.4202 60.7956 42.57 60.7956 35.7808C60.7956 28.9916 60.7674 28.1394 60.6461 25.4738C60.5248 22.8122 60.1026 20.9938 59.4842 19.4056C58.8438 17.7618 57.9889 16.3684 56.5989 14.977C55.2088 13.5856 53.8124 12.7316 52.1696 12.0934C50.5775 11.4754 48.7588 11.0514 46.0978 10.9322C43.4306 10.811 42.5779 10.7808 35.7846 10.7808C28.9913 10.7808 28.138 10.809 25.4694 10.9322Z" fill="url(#ig1)"/><defs><radialGradient id="ig1" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(17.4144 61.017) scale(65.31 65.2708)"><stop offset="0.09" stop-color="#FA8F21"/><stop offset="0.78" stop-color="#D82D7E"/></radialGradient></defs></svg>
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
