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
                                    <label class="tmf-label"><span class="dsi"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 92 93" fill="none"><rect x="0.138672" y="1" width="91.5618" height="91.5618" rx="15" fill="#754A91"/><path d="M35.396 62.5C33.27 61.96 31.5 60.99 30.01 59.68c-1.09-.98-1.99-2.14-2.67-3.43-.96-1.86-1.6-3.87-1.89-5.94-.36-2.6-.49-5.22-.38-7.84.03-1.22.09-2.43.23-3.65.22-2.32.82-4.59 1.75-6.73 1.16-2.63 3.26-4.74 5.9-5.93 1.8-.81 3.69-1.41 5.63-1.77 1.91-.36 3.83-.58 5.77-.66 1.75-.06 3.5-.04 5.24.07 2.5.1 4.97.52 7.36 1.22 1.77.51 3.46 1.27 5.01 2.27 1.51 1.02 2.72 2.41 3.54 4.03.96 1.89 1.61 3.92 1.93 5.01.21 1.24.35 2.49.41 3.75.09 1.56.08 3.12.02 4.67-.06 1.55-.19 3.06-.39 4.58-.24 2.28-.95 4.49-2.08 6.49-1.47 2.53-3.83 4.44-6.62 5.38-1.91.66-3.89 1.13-5.9 1.41-1.47.2-2.94.35-4.42.41-1.13.05-2.26.04-3.39.02-.64 0-1.28-.06-1.92-.12-.13-.01-.27.01-.39.05-.58.23-1.1.65-1.47 1.22-1.29 1.53-2.63 3.02-3.98 4.51-.25.27-.53.51-.85.69-.41.27-.91.28-1.35.06-.44-.22-.78-.63-.93-1.12-.14-.42-.2-.85-.2-1.29V62.5z" fill="white"/><path d="M34.5 37.5c.3-.76.8-1.31 1.3-1.8.67-.63 1.4-1.19 2.19-1.66.74-.47 1.62-.39 2.12.28.95.99 1.81 2.07 2.55 3.22.37.52.64 1.1.82 1.71.14.48.03.97-.23 1.37-.4.39-.83.76-1.28 1.09-.53.38-.62 1.03-.44 1.46.28 1.31.92 2.52 1.84 3.5 1.12 1.36 2.56 2.41 4.2 3.08.7.3 1.47.16 1.9-.24.25-.31.52-.61.74-.93.49-.71 1.32-.81 2.16-.54 1.68.68 2.8 1.47 3.84 2.36.66.54 1.14 1.22 1.3 1.5.1.35.07.72-.07 1.05-.22.56-.53 1.08-.93 1.54-.5.68-1.13 1.26-1.86 1.69-.73.43-1.55.38-2.32.15-2.84-1.13-5.49-2.67-7.86-4.57-2.32-1.89-4.35-4.11-6.02-6.59-1.41-2.05-2.53-4.28-3.33-6.63-.16-.58-.28-1.17-.33-1.76z" fill="white"/></svg></span>Viber</label>
                                    <input type="text" id="tmm-viber" class="tmf-input" placeholder="+84 9x...">
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
        $departments_preset = ['Ban Giám đốc','Sales & Business','Marketing & Creative','Craft & Production','Accounting','HR & Admin'];
        $cur_dept  = $m['department'] ?? '';
        $has_photo = !empty($m['photo_url']);
        ?>

        <style>
        /* ═══════════════════════════════════════
           EXPANDED FORM — REDESIGNED UI 2025
        ════════════════════════════════════════ */
        .ef-wrap { display:flex; flex-direction:column; gap:0; min-height:100vh; background:#f6f6f4; }

        /* ── Sticky top bar ── */
        .ef-topbar {
            position:sticky; top:32px; z-index:100;
            display:flex; align-items:center; gap:12px;
            padding:14px 24px;
            background:rgba(255,255,255,.92);
            backdrop-filter:blur(12px);
            border-bottom:1px solid #e8e5df;
            box-shadow:0 1px 3px rgba(0,0,0,.06);
        }
        .ef-topbar-back { display:flex; align-items:center; gap:6px; font-size:12px; color:#8a8075; text-decoration:none; padding:5px 10px; border-radius:7px; border:1px solid #e8e5df; background:#fff; transition:all .15s; font-weight:500; }
        .ef-topbar-back:hover { background:#f6f3ee; color:#3a3228; border-color:#c8bfb0; }
        .ef-topbar-back svg { width:12px; height:12px; stroke:currentColor; fill:none; stroke-width:2; }
        .ef-topbar-divider { width:1px; height:20px; background:#e8e5df; }
        .ef-topbar-info { flex:1; min-width:0; }
        .ef-topbar-title { font-size:14px; font-weight:700; color:#1a1714; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .ef-topbar-sub { font-size:11px; color:#8a8075; margin-top:1px; }
        .ef-topbar-acts { display:flex; align-items:center; gap:8px; }
        .ef-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:8px; font-size:12px; font-weight:600; border:none; cursor:pointer; font-family:inherit; text-decoration:none; transition:all .15s; line-height:1; }
        .ef-btn svg { width:12px; height:12px; stroke:currentColor; fill:none; stroke-width:2; flex-shrink:0; }
        .ef-btn-ghost { background:transparent; border:1px solid #e8e5df; color:#5a5450; }
        .ef-btn-ghost:hover { background:#f6f3ee; border-color:#c8bfb0; color:#1a1714; }
        .ef-btn-primary { background:#1a1714; color:#fff; border:1px solid transparent; box-shadow:0 1px 3px rgba(0,0,0,.2); }
        .ef-btn-primary:hover { background:#2e2a26; transform:translateY(-1px); box-shadow:0 3px 8px rgba(0,0,0,.2); }
        .ef-btn-primary:disabled { opacity:.5; transform:none; cursor:not-allowed; }
        .ef-btn-danger { background:#fff5f5; border:1px solid #fecaca; color:#dc2626; }
        .ef-btn-danger:hover { background:#fef2f2; border-color:#fca5a5; }
        .ef-badge-active { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
        .ef-badge-active.show { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
        .ef-badge-active.hide { background:#fafafa; color:#9ca3af; border:1px solid #e5e7eb; }

        /* ── 2-Column layout ── */
        .ef-body { display:grid; grid-template-columns:1fr 340px; gap:20px; padding:20px 24px 40px; max-width:1200px; }
        @media(max-width:900px) { .ef-body { grid-template-columns:1fr; } }

        /* ── Cards (sections) ── */
        .ef-card { background:#fff; border-radius:14px; border:1px solid #e8e5df; overflow:hidden; margin-bottom:16px; }
        .ef-card:last-child { margin-bottom:0; }
        .ef-card-head { display:flex; align-items:center; gap:10px; padding:14px 18px; border-bottom:1px solid #f0ede8; }
        .ef-card-head svg { width:15px; height:15px; stroke:currentColor; fill:none; stroke-width:1.8; stroke-linecap:round; flex-shrink:0; color:#7c6f62; }
        .ef-card-head-title { font-size:12px; font-weight:700; color:#1a1714; letter-spacing:.3px; text-transform:uppercase; }
        .ef-card-head-sub { font-size:11px; color:#a09080; margin-left:auto; }
        .ef-card-body { padding:18px; }

        /* ── Fields ── */
        .ef-field { margin-bottom:14px; }
        .ef-field:last-child { margin-bottom:0; }
        .ef-field-label { display:flex; align-items:center; gap:8px; font-size:11.5px; font-weight:600; color:#4a4440; margin-bottom:7px; }
        .ef-field-ic { width:24px; height:24px; flex-shrink:0; display:flex; align-items:center; justify-content:center; }
        .ef-field-ic svg, .ef-field-ic img { width:24px; height:24px; display:block; border-radius:6px; }
        .ef-field-req { color:#e53e3e; margin-left:2px; }
        .ef-input, .ef-select, .ef-textarea { width:100%; padding:8px 11px; border:1.5px solid #e8e5df; border-radius:8px; font-size:13px; font-family:inherit; color:#1a1714; background:#fff; transition:border .15s, box-shadow .15s; outline:none; box-sizing:border-box; }
        .ef-input:focus, .ef-select:focus, .ef-textarea:focus { border-color:#8b7d6b; box-shadow:0 0 0 3px rgba(139,125,107,.12); }
        .ef-input::placeholder { color:#b0a898; }
        .ef-textarea { resize:vertical; min-height:90px; line-height:1.6; }
        .ef-select { cursor:pointer; appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12' fill='none'%3E%3Cpath d='M2 4l4 4 4-4' stroke='%238a8075' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; padding-right:28px; }
        .ef-hint { display:block; font-size:11px; color:#a09080; margin-top:5px; }
        .ef-row2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        @media(max-width:600px) { .ef-row2 { grid-template-columns:1fr; } }

        /* ── Social grid ── */
        .ef-social-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        @media(max-width:600px) { .ef-social-grid { grid-template-columns:1fr; } }

        /* ── Photo zone ── */
        .ef-photo-zone { position:relative; width:100%; aspect-ratio:3/4; background:#f6f3ee; border-radius:12px; overflow:hidden; cursor:pointer; border:2px dashed #d5cfc8; transition:border .2s; display:flex; align-items:center; justify-content:center; }
        .ef-photo-zone:hover { border-color:#8b7d6b; }
        .ef-photo-zone img { width:100%; height:100%; object-fit:cover; display:block; }
        .ef-photo-ph { display:flex; flex-direction:column; align-items:center; gap:8px; color:#a09080; }
        .ef-photo-ph svg { width:32px; height:32px; stroke:currentColor; fill:none; stroke-width:1.2; }
        .ef-photo-ph span { font-size:12px; font-weight:500; }
        .ef-photo-overlay { position:absolute; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity .2s; }
        .ef-photo-zone:hover .ef-photo-overlay { opacity:1; }
        .ef-photo-overlay span { color:#fff; font-size:12px; font-weight:600; display:flex; align-items:center; gap:6px; }
        .ef-photo-overlay svg { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:2; }
        .ef-url-row { display:flex; align-items:center; gap:8px; margin-top:10px; }
        .ef-url-row .ef-input { flex:1; font-size:12px; }

        /* ── Status pills ── */
        .ef-status-row { display:flex; gap:8px; }
        .ef-status-pill { display:flex; align-items:center; gap:7px; padding:9px 14px; border-radius:10px; border:1.5px solid #e8e5df; cursor:pointer; font-size:12px; font-weight:600; color:#6b6460; transition:all .15s; flex:1; justify-content:center; }
        .ef-status-pill input { display:none; }
        .ef-status-pill svg { width:13px; height:13px; stroke:currentColor; fill:none; stroke-width:2; }
        .ef-status-pill:hover { border-color:#c8bfb0; background:#faf8f5; }
        .ef-status-pill.is-show { border-color:#86efac; background:#f0fdf4; color:#16a34a; }
        .ef-status-pill.is-hide  { border-color:#e8e5df; background:#f9f9f9; color:#9ca3af; }

        /* ── Gallery ── */
        .ef-gallery-grid { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:10px; }
        .ef-gal-item { position:relative; width:68px; height:68px; border-radius:8px; overflow:hidden; border:1.5px solid #e8e5df; cursor:pointer; flex-shrink:0; }
        .ef-gal-item img { width:100%; height:100%; object-fit:cover; display:block; }
        .ef-gal-del { position:absolute; inset:0; background:rgba(0,0,0,.55); display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity .15s; }
        .ef-gal-item:hover .ef-gal-del { opacity:1; }
        .ef-gal-del svg { width:18px; height:18px; stroke:#fff; stroke-width:2.5; fill:none; }

        /* ── Tab bar (social) ── */
        .ef-tabs { display:flex; gap:4px; border-bottom:1.5px solid #e8e5df; margin-bottom:16px; }
        .ef-tab { padding:7px 12px; font-size:11.5px; font-weight:600; color:#8a8075; border-bottom:2px solid transparent; cursor:pointer; transition:all .15s; margin-bottom:-2px; border-radius:6px 6px 0 0; white-space:nowrap; }
        .ef-tab:hover { color:#3a3228; background:#f6f3ee; }
        .ef-tab.active { color:#1a1714; border-bottom-color:#1a1714; background:transparent; }
        .ef-tab-pane { display:none; }
        .ef-tab-pane.active { display:block; }

        @keyframes ef-spin { to { transform:rotate(360deg); } }
        </style>

        <div class="ef-wrap">

        <!-- ── TOP BAR ── -->
        <div class="ef-topbar">
            <a href="<?php echo esc_url($list_url); ?>" class="ef-topbar-back">
                <svg viewBox="0 0 12 12"><path d="M8 2L4 6l4 4"/></svg>
                Danh sách
            </a>
            <div class="ef-topbar-divider"></div>
            <div class="ef-topbar-info">
                <div class="ef-topbar-title">
                    <?php echo $is_edit ? 'Chỉnh sửa: <strong>' . esc_html($m['name']) . '</strong>' : 'Thêm thành viên mới'; ?>
                </div>
                <div class="ef-topbar-sub">
                    <?php if ($is_edit): ?>
                    ID #<?php echo esc_html($m['id']); ?> &middot; Tạo: <?php echo esc_html(substr($m['created_at'] ?? '—', 0, 10)); ?>
                    <?php else: ?>
                    Điền đầy đủ thông tin để thành viên hiển thị đúng
                    <?php endif; ?>
                </div>
            </div>
            <div class="ef-topbar-acts">
                <?php if ($is_edit): ?>
                <span class="ef-badge-active <?php echo ($m['is_active'] ?? 1) ? 'show' : 'hide'; ?>">
                    <svg viewBox="0 0 10 10" width="8" height="8" fill="currentColor"><circle cx="5" cy="5" r="4"/></svg>
                    <?php echo ($m['is_active'] ?? 1) ? 'Hiển thị' : 'Đã ẩn'; ?>
                </span>
                <a href="<?php echo esc_url(admin_url('admin.php?page=bacera-team&action=add')); ?>" class="ef-btn ef-btn-ghost">
                    <svg viewBox="0 0 14 14"><path d="M7 2v10M2 7h10" stroke-linecap="round"/></svg>
                    Thêm mới
                </a>
                <?php endif; ?>
                <button type="submit" form="ef-member-form" id="tm-submit-btn" class="ef-btn ef-btn-primary">
                    <svg viewBox="0 0 14 14"><polyline points="2,7 5.5,10.5 12,3"/></svg>
                    <?php echo $save_label; ?>
                </button>
            </div>
        </div>

        <!-- ── FORM ── -->
        <form id="ef-member-form" method="post">
            <input type="hidden" name="action" value="bacera_team_save">
            <input type="hidden" name="_nonce" value="<?php echo esc_attr($nonce); ?>">
            <input type="hidden" name="id"     value="<?php echo esc_attr($m['id'] ?? 0); ?>">

            <div class="ef-body">

                <!-- ══ LEFT COLUMN ══ -->
                <div class="ef-left">

                    <!-- Card: Thông tin cá nhân -->
                    <div class="ef-card">
                        <div class="ef-card-head">
                            <svg viewBox="0 0 14 14"><circle cx="7" cy="5" r="3"/><path d="M1 13c0-3.31 2.69-6 6-6s6 2.69 6 6"/></svg>
                            <span class="ef-card-head-title">Thông tin cá nhân</span>
                        </div>
                        <div class="ef-card-body">
                            <div class="ef-field">
                                <div class="ef-field-label">
                                    Họ &amp; Tên <span class="ef-field-req">*</span>
                                </div>
                                <input type="text" id="tm-name" name="name" class="ef-input"
                                       value="<?php echo esc_attr($m['name'] ?? ''); ?>"
                                       placeholder="Nguyễn Văn A" required autocomplete="off">
                            </div>
                            <div class="ef-row2">
                                <div class="ef-field">
                                    <div class="ef-field-label">Chức danh</div>
                                    <input type="text" id="tm-role" name="role" class="ef-input"
                                           value="<?php echo esc_attr($m['role'] ?? ''); ?>"
                                           placeholder="CEO, Manager…">
                                </div>
                                <div class="ef-field">
                                    <div class="ef-field-label">Phòng ban</div>
                                    <select id="tm-dept" name="department" class="ef-select">
                                        <option value="">— Chọn phòng ban —</option>
                                        <?php foreach ($departments_preset as $d): ?>
                                        <option value="<?php echo esc_attr($d); ?>" <?php selected($cur_dept, $d); ?>><?php echo esc_html($d); ?></option>
                                        <?php endforeach; ?>
                                        <?php if ($cur_dept && !in_array($cur_dept, $departments_preset)): ?>
                                        <option value="<?php echo esc_attr($cur_dept); ?>" selected><?php echo esc_html($cur_dept); ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card: Tiểu sử -->
                    <div class="ef-card">
                        <div class="ef-card-head">
                            <svg viewBox="0 0 14 14"><rect x="1" y="1" width="12" height="12" rx="2"/><path d="M3.5 4.5h7M3.5 7h7M3.5 9.5h4.5"/></svg>
                            <span class="ef-card-head-title">Tiểu sử</span>
                        </div>
                        <div class="ef-card-body">
                            <div class="ef-field">
                                <textarea id="tm-bio" name="bio" class="ef-textarea" rows="4"
                                          placeholder="Vài dòng giới thiệu, sở trường, châm ngôn…"><?php echo esc_textarea($m['bio'] ?? ''); ?></textarea>
                                <span class="ef-hint">Xuống dòng = tạo nhiều đoạn văn trên trang cá nhân.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card: Liên hệ & Mạng xã hội (Tabbed) -->
                    <div class="ef-card">
                        <div class="ef-card-head">
                            <svg viewBox="0 0 14 14"><circle cx="7" cy="7" r="6"/><path d="M7 4v3l2 2"/></svg>
                            <span class="ef-card-head-title">Liên hệ &amp; Mạng xã hội</span>
                            <span class="ef-card-head-sub">Tùy chọn</span>
                        </div>
                        <div class="ef-card-body">

                            <!-- Tab nav -->
                            <div class="ef-tabs" id="ef-social-tabs">
                                <div class="ef-tab active" data-tab="ef-t-contact">📞 Liên hệ</div>
                                <div class="ef-tab" data-tab="ef-t-social1">🌐 Social 1</div>
                                <div class="ef-tab" data-tab="ef-t-social2">💬 Messaging</div>
                            </div>

                            <!-- Tab: Liên hệ -->
                            <div class="ef-tab-pane active" id="ef-t-contact">
                                <div class="ef-social-grid">

                    <div class="ef-field">
                        <div class="ef-field-label">
                            <span class="ef-field-ic"><svg width="32" height="32" viewBox="0 0 93 92" fill="none"><rect width="93" height="92" rx="12" fill="#4CAF50"/><path d="M62 57.7c-.1-1.1-.9-2.5-2.2-3.9-1.3-1.4-2.9-2.7-4.4-3.5-1.5-.8-2.9-.7-3.9.3l-2.2 2.2c-.3.3-.7.5-1 .4-.3-.1-1.5-.6-4-3.1-2.5-2.5-3-3.7-3.1-4-.1-.3.1-.7.4-1l2.2-2.2c1-1 1.1-2.4.3-3.9-.8-1.5-2.1-3.1-3.5-4.4-1.4-1.3-2.8-2.1-3.9-2.2-1.1-.1-2 .3-2.7 1l-2 2c-1.5 1.5-2.2 3.6-2 5.7.2 2.1 1.2 5.4 4.5 8.7 3.3 3.3 6.6 4.3 8.7 4.5 2.1.2 4.2-.5 5.7-2l2-2c.7-.7 1.1-1.6 1-2.7z" fill="white"/></svg></span>
                            <span>Điện thoại</span>
                        </div>
                        <input type="text" name="phone" class="ef-input" placeholder="090 xxx xxxx" value="<?php echo esc_attr($m['phone'] ?? ''); ?>">
                    </div>
                    <div class="ef-field">
                        <div class="ef-field-label">
                            <span class="ef-field-ic"><svg width="32" height="32" viewBox="0 0 92 92" fill="none"><rect x="0.638672" y="0.5" width="90.5618" height="90.5618" rx="11.5" fill="white" stroke="#C4CFE3"/><path d="M22.0065 66.1236H30.4893V45.5227L18.3711 36.4341V62.4881C18.3711 64.4997 20.001 66.1236 22.0065 66.1236Z" fill="#4285F4"/><path d="M59.5732 66.1236H68.056C70.0676 66.1236 71.6914 64.4937 71.6914 62.4881V36.4341L59.5732 45.5227" fill="#34A853"/><path d="M59.5732 29.7693V45.5229L71.6914 36.4343V31.587C71.6914 27.0912 66.5594 24.5282 62.9663 27.2245" fill="#FBBC04"/><path d="M30.4893 45.5227V29.769L45.0311 40.6754L59.5729 29.769V45.5227L45.0311 56.429" fill="#EA4335"/><path d="M18.3711 31.587V36.4343L30.4893 45.5229V29.7693L27.0962 27.2245C23.4971 24.5282 18.3711 27.0912 18.3711 31.587Z" fill="#C5221F"/></svg></span>
                            <span>Email</span>
                        </div>
                        <input type="email" name="email" class="ef-input" placeholder="admin@domain.com" value="<?php echo esc_attr($m['email'] ?? ''); ?>">
                    </div>
                                </div>
                            </div>

                            <!-- Tab: Social 1 (Facebook, IG, X, TikTok, LinkedIn, YouTube, Pinterest) -->
                            <div class="ef-tab-pane" id="ef-t-social1">
                                <div class="ef-social-grid">
                    <div class="ef-field">
                        <div class="ef-field-label">
                            <span class="ef-field-ic"><svg width="32" height="32" viewBox="0 0 93 92" fill="none"><rect x="1.13867" width="91.5618" height="91.5618" rx="12" fill="#337FFF"/><path d="M57.4233 48.6403L58.7279 40.3588H50.6917V34.9759C50.6917 32.7114 51.8137 30.4987 55.4013 30.4987H59.1063V23.4465C56.9486 23.1028 54.7685 22.9168 52.5834 22.8901C45.9692 22.8901 41.651 26.8626 41.651 34.0442V40.3588H34.3193V48.6403H41.651V68.671H50.6917V48.6403H57.4233Z" fill="white"/></svg></span>
                            <span>Facebook</span>
                        </div>
                        <input type="url" name="facebook" class="ef-input" placeholder="https://facebook.com/..." value="<?php echo esc_attr($m['facebook'] ?? ''); ?>">
                    </div>
                    <div class="ef-field">
                        <div class="ef-field-label">
                            <span class="ef-field-ic"><svg width="32" height="32" viewBox="0 0 93 92" fill="none"><rect x="1.13867" width="91.5618" height="91.5618" rx="12" fill="url(#ig_ef)"/><path d="M38.3762 45.7808C38.3762 41.1786 42.1083 37.4468 46.7132 37.4468C51.3182 37.4468 55.0522 41.1786 55.0522 45.7808C55.0522 50.383 51.3182 54.1148 46.7132 54.1148C42.1083 54.1148 38.3762 50.383 38.3762 45.7808ZM33.8683 45.7808C33.8683 52.8708 39.619 58.618 46.7132 58.618C53.8075 58.618 59.5581 52.8708 59.5581 45.7808C59.5581 38.6908 53.8075 32.9436 46.7132 32.9436C39.619 32.9436 33.8683 38.6908 33.8683 45.7808ZM36.4001 20.9322C33.7371 21.0534 31.9174 21.4754 30.3282 22.0934C28.6824 22.7316 27.2892 23.5878 25.897 24.977C24.5047 26.3662 23.6502 27.7608 23.0116 29.4056C22.3933 30.9948 21.971 32.8124 21.8497 35.4738C21.7265 38.1394 21.6982 38.9916 21.6982 45.7808C21.6982 52.57 21.7265 53.4222 21.8497 56.0878C21.971 58.7494 22.3933 60.5668 23.0116 62.156C23.6502 63.7998 24.5049 65.196 25.897 66.5846C27.289 67.9732 28.6824 68.8282 30.3282 69.4682C31.9204 70.0862 33.7371 70.5082 36.4001 70.6294C39.0687 70.7506 39.92 70.7808 46.7132 70.7808C53.5065 70.7808 54.3592 70.7526 57.0264 70.6294C59.6896 70.5082 61.5081 70.0862 63.0983 69.4682C64.7431 68.8282 66.1373 67.9738 67.5295 66.5846C68.9218 65.1954 69.7745 63.7998 70.4149 62.156C71.0332 60.5668 71.4575 58.7492 71.5768 56.0878C71.698 53.4202 71.7262 52.57 71.7262 45.7808C71.7262 38.9916 71.698 38.1394 71.5768 35.4738C71.4555 32.8122 71.0332 30.9938 70.4149 29.4056C69.7745 27.7618 68.9196 26.3684 67.5295 24.977C66.1395 23.5856 64.7431 22.7316 63.1003 22.0934C61.5081 21.4754 59.6894 21.0514 57.0284 20.9322C54.3612 20.811 53.5085 20.7808 46.7152 20.7808C39.922 20.7808 39.0687 20.809 36.4001 20.9322Z" fill="white"/><defs><linearGradient id="ig_ef" x1="90.9407" y1="91.5618" x2="-0.621143" y2="0" gradientUnits="userSpaceOnUse"><stop stop-color="#FBE18A"/><stop offset="0.21" stop-color="#FCBB45"/><stop offset="0.38" stop-color="#F75274"/><stop offset="0.52" stop-color="#D53692"/><stop offset="0.74" stop-color="#8F39CE"/><stop offset="1" stop-color="#5B4FE9"/></linearGradient></defs></svg></span>
                            <span>Instagram</span>
                        </div>
                        <input type="url" name="instagram" class="ef-input" placeholder="https://instagram.com/..." value="<?php echo esc_attr($m['instagram'] ?? ''); ?>">
                    </div>
                    <div class="ef-field">
                        <div class="ef-field-label">
                            <span class="ef-field-ic"><svg width="32" height="32" viewBox="0 0 93 92" fill="none"><rect x="0.138672" width="91.5618" height="91.5618" rx="12" fill="black"/><path d="M50.7568 42.1716L69.3704 21H64.9596L48.7974 39.383L35.8887 21H21L40.5205 48.7983L21 71H25.4111L42.4788 51.5869L56.1113 71H71L50.7557 42.1716H50.7568ZM44.7152 49.0433L42.7374 46.2752L27.0005 24.2492H33.7756L46.4755 42.0249L48.4533 44.7929L64.9617 67.8986H58.1865L44.7152 49.0443V49.0433Z" fill="white"/></svg></span>
                            <span>X / Twitter</span>
                        </div>
                        <input type="url" name="x_twitter" class="ef-input" placeholder="https://x.com/..." value="<?php echo esc_attr($m['x_twitter'] ?? ''); ?>">
                    </div>
                    <div class="ef-field">
                        <div class="ef-field-label">
                            <span class="ef-field-ic"><svg width="32" height="32" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="5" fill="#010101"/><path d="M16.6 7.8c-.7-.5-1.3-1.3-1.5-2.3H13v9.5c0 1-.9 1.7-1.7 1.7-1 0-1.7-.8-1.7-1.7s.8-1.7 1.7-1.7c.2 0 .3 0 .5.1V11c-.2 0-.3 0-.5 0-1.9 0-3.5 1.6-3.5 3.5S9.4 18 11.3 18s3.5-1.6 3.5-3.5V9.7c.8.5 1.7.8 2.6.8V8c-.3 0-.6-.1-.8-.2z" fill="white"/></svg></span>
                            <span>TikTok</span>
                        </div>
                        <input type="url" name="tiktok" class="ef-input" placeholder="https://tiktok.com/@..." value="<?php echo esc_attr($m['tiktok'] ?? ''); ?>">
                    </div>
                    <div class="ef-field">
                        <div class="ef-field-label">
                            <span class="ef-field-ic"><svg width="32" height="32" viewBox="0 0 93 93" fill="none"><rect x="1.13867" y="1" width="91.5618" height="91.5618" rx="12" fill="#006699"/><path d="M37.1339 63.4304V40.9068H29.6473V63.4304H37.1346H37.1339ZM33.3922 37.8321C36.0023 37.8321 37.6273 36.1025 37.6273 33.9411C37.5785 31.7304 36.0023 30.0491 33.4418 30.0491C30.8795 30.0491 29.2061 31.7304 29.2061 33.9409C29.2061 36.1023 30.8305 37.8319 33.3431 37.8319H33.3916L33.3922 37.8321ZM41.2777 63.4304H48.7637V50.8535C48.7637 50.1813 48.8125 49.5072 49.0103 49.0271C49.5513 47.6815 50.7831 46.2887 52.8517 46.2887C55.5599 46.2887 56.644 48.354 56.644 51.3822V63.4304H64.1297V50.516C64.1297 43.598 60.4369 40.3787 55.5115 40.3787C51.4733 40.3787 49.6998 42.6357 48.7144 44.173H48.7643V40.9075H41.2781C41.3759 43.0205 41.2775 63.4312 41.2775 63.4312L41.2777 63.4304Z" fill="white"/></svg></span>
                            <span>LinkedIn</span>
                        </div>
                        <input type="url" name="linkedin" class="ef-input" placeholder="https://linkedin.com/in/..." value="<?php echo esc_attr($m['linkedin'] ?? ''); ?>">
                    </div>
                    <div class="ef-field">
                        <div class="ef-field-label">
                            <span class="ef-field-ic"><svg width="32" height="32" viewBox="0 0 93 93" fill="none"><rect x="1.13867" y="1" width="91.5618" height="91.5618" rx="12" fill="#FF0000"/><path fill-rule="evenodd" clip-rule="evenodd" d="M67.5615 29.2428C69.8115 29.8504 71.58 31.6234 72.1778 33.8708C73.2654 37.9495 73.2654 46.4647 73.2654 46.4647C73.2654 46.4647 73.2654 54.98 72.1778 59.0586C71.5717 61.3144 69.8032 63.0873 67.5615 63.6866C63.4932 64.7771 47.1703 64.7771 47.1703 64.7771C47.1703 64.7771 30.8557 64.7771 26.7791 63.6866C24.5291 63.079 22.7606 61.306 22.1628 59.0586C21.0752 54.98 21.0752 46.4647 21.0752 46.4647C21.0752 46.4647 21.0752 37.9495 22.1628 33.8708C22.7689 31.615 24.5374 29.8421 26.7791 29.2428C30.8557 28.1523 47.1703 28.1523 47.1703 28.1523C47.1703 28.1523 63.4932 28.1523 67.5615 29.2428ZM55.5142 46.4647L41.9561 54.314V38.6154L55.5142 46.4647Z" fill="white"/></svg></span>
                            <span>YouTube</span>
                        </div>
                        <input type="url" name="youtube" class="ef-input" placeholder="https://youtube.com/..." value="<?php echo esc_attr($m['youtube'] ?? ''); ?>">
                    </div>
                    <div class="ef-field">
                        <div class="ef-field-label">
                            <span class="ef-field-ic"><svg width="32" height="32" viewBox="0 0 93 92" fill="none"><rect x="1.13867" width="91.5618" height="91.5618" rx="12" fill="#E60023"/><path d="M44.2808 23.0437C36.8492 23.893 29.4439 30.0479 29.1382 38.84C28.9461 44.2083 30.435 48.2356 35.4258 49.3664C37.5915 45.4553 34.7272 44.5927 34.2818 41.7633C32.4523 30.1686 47.346 22.2615 55.14 30.3563C60.5324 35.9615 56.9826 53.206 48.2848 51.4136C39.9537 49.7017 52.3629 35.9749 45.713 33.2796C40.3074 31.0894 37.4343 39.9798 39.9974 44.396C38.4953 51.9902 35.2599 59.1464 36.5698 68.6715C40.8183 65.5158 42.2504 59.4727 43.425 53.1702C45.5601 54.4978 46.6998 55.8789 49.4244 56.0935C59.4714 56.8891 65.0822 45.8263 63.7112 35.6218C62.4929 26.5749 53.6729 21.971 44.2808 23.0437Z" fill="white"/></svg></span>
                            <span>Pinterest</span>
                        </div>
                        <input type="url" name="pinterest" class="ef-input" placeholder="https://pinterest.com/..." value="<?php echo esc_attr($m['pinterest'] ?? ''); ?>">
                    </div>
                                </div>
                            </div>

                            <!-- Tab: Messaging (WA, TG, Messenger, Zalo, Viber, Skype, WeChat, LINE) -->
                            <div class="ef-tab-pane" id="ef-t-social2">
                                <div class="ef-social-grid">
                    <div class="ef-field">
                        <div class="ef-field-label">
                            <span class="ef-field-ic"><svg width="32" height="32" viewBox="0 0 93 92" fill="none"><rect x="1.13867" width="91.5618" height="91.5618" rx="12" fill="#00D95F"/><path d="M23.5068 66.8405L26.7915 54.6381C24.1425 49.8847 23.3009 44.3378 24.4211 39.0154C25.5413 33.693 28.5482 28.952 32.89 25.6624C37.2319 22.3729 42.6173 20.7554 48.0583 21.1068C53.4992 21.4582 58.6306 23.755 62.5108 27.5756C66.3911 31.3962 68.7599 36.4844 69.1826 41.9065C69.6053 47.3286 68.0535 52.7208 64.812 57.0938C61.5705 61.4668 56.8568 64.5271 51.5357 65.7133C46.2146 66.8994 40.6432 66.1318 35.8438 63.5513L23.5068 66.8405Z" fill="white"/></svg></span>
                            <span>WhatsApp</span>
                        </div>
                        <input type="text" name="whatsapp" class="ef-input" placeholder="+84 9x..." value="<?php echo esc_attr($m['whatsapp'] ?? ''); ?>">
                    </div>
                    <div class="ef-field">
                        <div class="ef-field-label">
                            <span class="ef-field-ic"><svg width="32" height="32" viewBox="0 0 92 93" fill="none"><rect x="0.138672" y="1" width="91.5618" height="91.5618" rx="12" fill="#34AADF"/><path d="M25.0881 43.5652C25.0881 43.5652 43.716 35.7194 50.1765 32.9567C52.6532 31.8518 61.0518 28.3155 61.0518 28.3155C61.0518 28.3155 64.9282 26.7685 64.6052 30.5256C64.4974 32.0728 63.6361 37.4874 62.7747 43.3442C61.4825 51.6322 60.0827 60.6935 60.0827 60.6935C60.0827 60.6935 59.8674 63.2352 58.0369 63.6772C56.2065 64.1192 53.1914 62.1302 52.6532 61.6881C52.2223 61.3566 44.5774 56.3838 41.7778 53.9527C41.0241 53.2897 40.1627 51.9637 41.8854 50.4166C45.7618 46.7699 50.3919 42.2392 53.1914 39.3661C54.4836 38.04 55.7757 34.9459 50.3919 38.703C42.7469 44.1178 35.2096 49.201 35.2096 49.201C35.2096 49.201 33.4868 50.306 30.2565 49.3115C27.0261 48.317 23.2575 46.9909 23.2575 46.9909C23.2575 46.9909 20.6734 45.3334 25.0881 43.5652Z" fill="white"/></svg></span>
                            <span>Telegram</span>
                        </div>
                        <input type="url" name="telegram" class="ef-input" placeholder="https://t.me/..." value="<?php echo esc_attr($m['telegram'] ?? ''); ?>">
                    </div>
                    <div class="ef-field">
                        <div class="ef-field-label">
                            <span class="ef-field-ic"><svg width="32" height="32" viewBox="0 0 93 93" fill="none"><rect x="0.138672" y="1" width="91.5618" height="91.5618" rx="12" fill="url(#msg_ef)"/><path fill-rule="evenodd" clip-rule="evenodd" d="M46.4114 21C32.0561 21 20.9307 31.317 20.9307 45.2508C20.9307 52.5396 23.9761 58.8375 28.9338 63.1887C29.3491 63.5559 29.6003 64.0639 29.6208 64.6122L29.7592 69.059C29.8054 70.4775 31.2973 71.398 32.62 70.8296L37.6752 68.6414C38.1058 68.4553 38.5826 68.4201 39.0338 68.5408C41.3563 69.1696 43.8326 69.5016 46.4114 69.5016C60.7668 69.5016 71.8922 59.1846 71.8922 45.2508C71.8922 31.317 60.7668 21 46.4114 21ZM61.7102 39.6572L54.2249 51.3072C53.0354 53.1584 50.4822 53.6211 48.698 52.3082L42.7457 47.9269C42.1971 47.5245 41.4486 47.5295 40.9051 47.9319L32.8661 53.9179C31.7946 54.7177 30.3898 53.4551 31.1127 52.3384L38.598 40.6884C39.7875 38.8372 42.3407 38.3745 44.1248 39.6874L50.0772 44.0687C50.6258 44.4711 51.3743 44.4661 51.9177 44.0637L59.9567 38.0777C61.0283 37.2779 62.433 38.5405 61.7102 39.6572Z" fill="white"/><defs><radialGradient id="msg_ef" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(15.4753 92.5593) scale(100.718 100.715)"><stop stop-color="#0099FF"/><stop offset="0.6" stop-color="#A033FF"/><stop offset="0.9" stop-color="#FF5280"/><stop offset="1" stop-color="#FF7061"/></radialGradient></defs></svg></span>
                            <span>Messenger</span>
                        </div>
                        <input type="url" name="messenger" class="ef-input" placeholder="https://m.me/..." value="<?php echo esc_attr($m['messenger'] ?? ''); ?>">
                    </div>
                                    <div class="ef-field">
                                        <div class="ef-field-label">
                                            <span class="ef-field-ic"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/Icon_of_Zalo.svg/250px-Icon_of_Zalo.svg.png" alt="Zalo" width="32" height="32" style="border-radius:8px;object-fit:contain;"></span>
                                            <span>Zalo</span>
                                        </div>
                                        <input type="text" name="zalo" class="ef-input" placeholder="SĐT hoặc link Zalo" value="<?php echo esc_attr($m['zalo'] ?? ''); ?>">
                                    </div>
                    <div class="ef-field">
                        <div class="ef-field-label">
                            <span class="ef-field-ic"><svg width="32" height="32" viewBox="0 0 92 93" fill="none"><rect x="0.138672" y="1" width="91.5618" height="91.5618" rx="12" fill="#754A91"/><path d="M35.396 62.5C33.27 61.96 31.5 60.99 30.01 59.68c-1.09-.98-1.99-2.14-2.67-3.43-.96-1.86-1.6-3.87-1.89-5.94-.36-2.6-.49-5.22-.38-7.84.03-1.22.09-2.43.23-3.65.22-2.32.82-4.59 1.75-6.73 1.16-2.63 3.26-4.74 5.9-5.93 1.8-.81 3.69-1.41 5.63-1.77 1.91-.36 3.83-.58 5.77-.66 1.75-.06 3.5-.04 5.24.07 2.5.1 4.97.52 7.36 1.22 1.77.51 3.46 1.27 5.01 2.27 1.51 1.02 2.72 2.41 3.54 4.03.96 1.89 1.61 3.92 1.93 5.01.21 1.24.35 2.49.41 3.75.09 1.56.08 3.12.02 4.67-.06 1.55-.19 3.06-.39 4.58-.24 2.28-.95 4.49-2.08 6.49-1.47 2.53-3.83 4.44-6.62 5.38-1.91.66-3.89 1.13-5.9 1.41-1.47.2-2.94.35-4.42.41-1.13.05-2.26.04-3.39.02-.64 0-1.28-.06-1.92-.12-.13-.01-.27.01-.39.05-.58.23-1.1.65-1.47 1.22-1.29 1.53-2.63 3.02-3.98 4.51-.41.27-.91.28-1.35.06-.44-.22-.78-.63-.93-1.12-.14-.42-.2-.85-.2-1.29V62.5z" fill="white"/></svg></span>
                            <span>Viber</span>
                        </div>
                        <input type="text" name="viber" class="ef-input" placeholder="+84 9x..." value="<?php echo esc_attr($m['viber'] ?? ''); ?>">
                    </div>
                    <div class="ef-field">
                        <div class="ef-field-label">
                            <span class="ef-field-ic"><svg width="32" height="32" viewBox="0 0 92 92" fill="none"><rect x="0.138672" width="91.5618" height="91.5618" rx="12" fill="#00B7F0"/><path d="M57 35.3c-.6-.5-1.4-.8-2.2-.8-1.7 0-2.5.8-3.8 1.4-1 .4-2.1.6-3.4.6-4.4 0-7.9-3.4-7.9-7.6 0-1.3.3-2.5.9-3.5.7-1.3 1.1-2 1.1-3.1 0-.7-.2-1.4-.7-1.9-.5-.5-1.2-.8-2-.8-4.8 0-8.7 3.3-8.7 7.4 0 1.2.3 2.4.9 3.5.3.6.5 1.2.5 1.9 0 5.3-4.3 9.5-9.7 9.5-1.3 0-2.6-.3-3.7-.8-1.2-.6-2-.9-3.2-.9-3.8 0-6.8 3-6.8 6.7 0 5.2 5.8 9 13.4 9 2.5 0 4.9-.5 6.9-1.5 1.4-.7 2.9-1 4.5-1 4.9 0 8.8 3.7 8.8 8.2 0 1.3-.4 2.6-1 3.7-.8 1.3-1.2 2.6-1.2 3.8 0 3.8 3.2 6.9 7.2 6.9 5.8 0 10.5-4.5 10.5-10v-.5c.7-1.2 1-2.5 1-3.8 0-1.9-.7-3.7-2-5.1" fill="white"/></svg></span>
                            <span>Skype</span>
                        </div>
                        <input type="text" name="skype" class="ef-input" placeholder="Skype ID" value="<?php echo esc_attr($m['skype'] ?? ''); ?>">
                    </div>
                    <div class="ef-field">
                        <div class="ef-field-label">
                            <span class="ef-field-ic"><svg width="32" height="32" viewBox="0 0 93 93" fill="none"><rect x="1.13867" y="1" width="91.5618" height="91.5618" rx="12" fill="#51C332"/><path d="M55.8615 36.5403C57.0463 29.1747 49.1004 23.4524 39.5457 23.4524C28.7309 23.4524 19.9658 30.7781 19.9658 39.8123C19.9658 45.021 22.8964 49.6421 27.4419 52.6322L24.8606 57.8086L31.8926 54.7884C33.4005 55.3254 34.9674 55.7676 36.6411 55.9734C36.2824 43.8797 45.0634 36.5403 55.8615 36.5403ZM46.0722 30.8139C47.4235 30.8139 48.5194 31.9132 48.5194 33.2682C48.5194 34.6237 47.4236 35.7222 46.0722 35.7222C44.7201 35.7222 43.6247 34.6237 43.6247 33.2682C43.6247 31.9131 44.7201 30.8139 46.0722 30.8139ZM33.0189 35.7222C31.6674 35.7222 30.5715 34.6237 30.5715 33.2682C30.5715 31.9132 31.6675 30.8139 33.0189 30.8139C34.3703 30.8139 35.4664 31.9132 35.4664 33.2682C35.4663 34.6237 34.3702 35.7222 33.0189 35.7222Z" fill="white"/><path d="M72.1779 52.9008C72.1779 45.6724 64.8709 39.8123 55.8615 39.8123C46.8517 39.8123 39.5457 45.6724 39.5457 52.9008C39.5457 60.1287 46.8517 65.9889 55.8615 65.9889C57.3432 65.9889 58.7525 65.7794 60.12 65.4821L68.9148 69.2608L65.8731 63.1654C69.6849 60.7698 72.1779 57.0859 72.1779 52.9008ZM50.9668 52.0827C49.6154 52.0827 48.5193 50.9838 48.5193 49.6281C48.5193 48.2731 49.6153 47.1746 50.9668 47.1746C52.3186 47.1746 53.4141 48.2736 53.4141 49.6281C53.4141 50.9839 52.3184 52.0827 50.9668 52.0827ZM60.7564 52.0827C59.4043 52.0827 58.3091 50.9838 58.3091 49.6281C58.3091 48.2731 59.4042 47.1746 60.7564 47.1746C62.1083 47.1746 63.2039 48.2736 63.2039 49.6281C63.2039 50.9839 62.1083 52.0827 60.7564 52.0827Z" fill="white"/></svg></span>
                            <span>WeChat</span>
                        </div>
                        <input type="text" name="wechat" class="ef-input" placeholder="WeChat ID" value="<?php echo esc_attr($m['wechat'] ?? ''); ?>">
                    </div>
                    <div class="ef-field">
                        <div class="ef-field-label">
                            <span class="ef-field-ic"><svg width="32" height="32" viewBox="0 0 93 92" fill="none"><rect width="93" height="92" rx="12" fill="#06C755"/><path d="M78 40.2C78 25.5 63.2 13.5 46.3 13.5 29.4 13.5 14.6 25.5 14.6 40.2c0 13.2 11.7 24.3 27.6 26.4 1.1.2 2.5.7 2.9 1.7.4 1 .3 2.4 0 3.3l-.5 2.7c-.1 1-.9 3.8 1.5 2.7 2.4-1.1 13.3-7.8 18.2-13.4C69.1 59 78 50.2 78 40.2zM35.8 48.2H29v-14h3.1v11h3.7v3zm4.6 0h-3.1V34.2h3.1v14zm14 0h-3l-5.3-9.4v9.4H43V34.2h3l5.3 9.3v-9.3h3.1v14zm12.6-11h-6.2v2.5h6.2v3.1h-6.2v2.5h6.2v3H57.7V34.2H67v3z" fill="white"/></svg></span>
                            <span>LINE App</span>
                        </div>
                        <input type="text" name="line_app" class="ef-input" placeholder="Line ID" value="<?php echo esc_attr($m['line_app'] ?? ''); ?>">
                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Card: Thư viện ảnh -->
                    <div class="ef-card">
                        <div class="ef-card-head">
                            <svg viewBox="0 0 14 14"><rect x="1" y="2" width="12" height="10" rx="1.5"/><circle cx="5" cy="6" r="1.5"/><path d="M1 10l3-3 2.5 2.5 2-2L13 10"/></svg>
                            <span class="ef-card-head-title">Thư viện ảnh</span>
                            <span class="ef-card-head-sub">Nhiều ảnh</span>
                        </div>
                        <div class="ef-card-body">
                            <div id="tm-gallery-preview" class="ef-gallery-grid"></div>
                            <textarea id="tm-gallery" name="gallery_urls" class="ef-textarea" rows="3"
                                      style="display:none;"><?php echo esc_textarea($m['gallery_urls'] ?? ''); ?></textarea>
                            <button type="button" id="tm-gallery-btn" class="ef-btn ef-btn-ghost" style="font-size:12px;">
                                <svg viewBox="0 0 14 14"><rect x="1" y="3" width="12" height="9" rx="1.5"/><circle cx="5" cy="7" r="1.5"/><path d="M1 11l3-3 2.5 2.5 2-2L12 11"/></svg>
                                Thêm ảnh vào thư viện
                            </button>
                            <span class="ef-hint" style="margin-top:8px;">Ảnh sẽ hiển thị trong gallery 3D trên trang cá nhân.</span>
                        </div>
                    </div>

                </div><!-- /ef-left -->

                <!-- ══ RIGHT SIDEBAR ══ -->
                <div class="ef-right">

                    <!-- Card: Ảnh đại diện -->
                    <div class="ef-card" style="margin-bottom:16px;">
                        <div class="ef-card-head">
                            <svg viewBox="0 0 14 14"><rect x="1" y="1" width="12" height="12" rx="2"/><circle cx="5" cy="5" r="1.5"/><path d="M1 10l3-3 2.5 2.5 2-2 3.5 3.5"/></svg>
                            <span class="ef-card-head-title">Ảnh đại diện</span>
                        </div>
                        <div class="ef-card-body">

                            <div class="ef-photo-zone" id="tm-photo-zone" onclick="document.getElementById('tm-media-btn').click()">
                                <?php $has_photo = !empty($m['photo_url']); ?>
                                <img id="tm-photo-img" src="<?php echo esc_url($m['photo_url'] ?? ''); ?>" alt=""
                                     style="display:<?php echo $has_photo ? 'block' : 'none'; ?>; position:absolute; inset:0; width:100%; height:100%; object-fit:cover;"
                                     onerror="this.style.display='none';document.getElementById('tm-photo-placeholder').style.display='flex'">
                                <div class="ef-photo-ph" id="tm-photo-placeholder"
                                     style="display:<?php echo $has_photo ? 'none' : 'flex'; ?>;">
                                    <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M3 17l4-4 3 3 3-3 5 5"/></svg>
                                    <span><?php echo $has_photo ? 'Đổi ảnh' : 'Nhấp để chọn ảnh'; ?></span>
                                </div>
                                <div class="ef-photo-overlay">
                                    <span>
                                        <svg viewBox="0 0 14 14"><rect x="1" y="3" width="12" height="9" rx="1.5"/><circle cx="5" cy="7" r="1.5"/><path d="M1 11l3-3 2.5 2.5 2-2L12 11"/></svg>
                                        Đổi ảnh
                                    </span>
                                </div>
                            </div>

                            <div class="ef-url-row">
                                <input type="url" name="photo_url" id="tm-photo-input" class="ef-input"
                                       value="<?php echo esc_attr($m['photo_url'] ?? ''); ?>"
                                       placeholder="Dán URL ảnh…">
                                <button type="button" id="tm-media-btn" class="ef-btn ef-btn-ghost" title="Chọn từ Media Library" style="flex-shrink:0;padding:7px 10px;">
                                    <svg viewBox="0 0 14 14"><rect x="1" y="3" width="12" height="9" rx="1.5"/><circle cx="5" cy="7" r="1.5"/><path d="M1 11l3-3 2.5 2.5 2-2L12 11"/></svg>
                                </button>
                                <button type="button" id="tm-photo-clear" class="ef-btn ef-btn-danger" title="Xóa ảnh" style="flex-shrink:0;padding:7px 10px;<?php echo $has_photo ? '' : 'display:none;'; ?>">
                                    <svg viewBox="0 0 14 14"><path d="M2 2l10 10M12 2L2 12"/></svg>
                                </button>
                            </div>
                            <span class="ef-hint">Tỷ lệ tốt nhất: 3:4 (dọc). Nhấn vào ô ảnh hoặc dán URL.</span>

                        </div>
                    </div>

                    <!-- Card: Preview -->
                    <div class="ef-card" style="margin-bottom:16px;">
                        <div class="ef-card-head">
                            <svg viewBox="0 0 14 14"><path d="M1 7s2.5-4 6-4 6 4 6 4-2.5 4-6 4-6-4-6-4z"/><circle cx="7" cy="7" r="1.5"/></svg>
                            <span class="ef-card-head-title">Xem trước</span>
                        </div>
                        <div class="ef-card-body" style="text-align:center;">
                            <div id="preview-name" style="font-size:14px;font-weight:700;color:#1a1714;"><?php echo esc_html($m['name'] ?? 'Tên thành viên'); ?></div>
                            <div id="preview-role" style="font-size:12px;color:#8a8075;margin-top:3px;"><?php echo esc_html($m['role'] ?? 'Chức danh'); ?></div>
                            <?php if (!empty($cur_dept)): ?>
                            <div style="margin-top:8px;"><span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:#f6f3ee;color:#6b5344;border:1px solid #e8e0d5;"><?php echo esc_html($cur_dept); ?></span></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Card: Trạng thái -->
                    <div class="ef-card">
                        <div class="ef-card-head">
                            <svg viewBox="0 0 14 14"><circle cx="7" cy="7" r="6"/><path d="M5 7l1.5 1.5L9 5"/></svg>
                            <span class="ef-card-head-title">Trạng thái hiển thị</span>
                        </div>
                        <div class="ef-card-body">
                            <div class="ef-status-row" id="tm-status-group">
                                <label class="ef-status-pill <?php echo ($m['is_active'] ?? 1) ? 'is-show' : ''; ?>" id="pill-show">
                                    <input type="radio" name="is_active" value="1" <?php checked($m['is_active'] ?? 1, 1); ?>>
                                    <svg viewBox="0 0 12 12"><circle cx="6" cy="6" r="5"/><path d="M4 6l1.5 1.5L8 4"/></svg>
                                    Hiển thị
                                </label>
                                <label class="ef-status-pill <?php echo !($m['is_active'] ?? 1) ? 'is-hide' : ''; ?>" id="pill-hide">
                                    <input type="radio" name="is_active" value="0" <?php checked($m['is_active'] ?? 1, 0); ?>>
                                    <svg viewBox="0 0 12 12"><circle cx="6" cy="6" r="5"/><path d="M4.5 7.5l3-3M7.5 7.5l-3-3"/></svg>
                                    Ẩn
                                </label>
                            </div>
                            <span class="ef-hint" style="margin-top:10px;">Thành viên "Ẩn" sẽ không xuất hiện trên trang Our Team.</span>
                        </div>
                    </div>

                </div><!-- /ef-right -->

            </div><!-- /ef-body -->
        </form>

        <!-- Tab script -->
        <script>
        (function(){
            var tabs = document.querySelectorAll('.ef-tab');
            tabs.forEach(function(tab){
                tab.addEventListener('click', function(){
                    var target = this.getAttribute('data-tab');
                    document.querySelectorAll('.ef-tab').forEach(function(t){ t.classList.remove('active'); });
                    document.querySelectorAll('.ef-tab-pane').forEach(function(p){ p.classList.remove('active'); });
                    this.classList.add('active');
                    var pane = document.getElementById(target);
                    if(pane) pane.classList.add('active');
                });
            });

            // Live preview name/role sync
            var nm = document.getElementById('tm-name'), rl = document.getElementById('tm-role');
            var pn = document.getElementById('preview-name'), pr = document.getElementById('preview-role');
            if(nm && pn) nm.addEventListener('input', function(){ pn.textContent = this.value || 'Tên thành viên'; });
            if(rl && pr) rl.addEventListener('input', function(){ pr.textContent = this.value || 'Chức danh'; });

            // Status pill sync
            document.querySelectorAll('input[name="is_active"]').forEach(function(r){
                r.addEventListener('change', function(){
                    document.getElementById('pill-show').classList.toggle('is-show', this.value==='1');
                    document.getElementById('pill-hide').classList.toggle('is-hide', this.value==='0');
                });
            });
        })();
        </script>

        </div><!-- /ef-wrap -->
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
                var isExpanded = previewDiv.id === 'tm-gallery-preview';
                var itemClass  = isExpanded ? 'ef-gal-item tmm-gal-item' : 'tmm-gal-item';
                var delClass   = isExpanded ? 'ef-gal-del tmm-gal-del' : 'tmm-gal-del';
                if (urls.length === 0) {
                    previewDiv.innerHTML = '<div style="font-size:11px;color:#a09080;font-style:italic;padding:8px 0;">Chưa có ảnh nào. Nhấn "Thêm ảnh" để bắt đầu.</div>'; return;
                }
                var html = '';
                for (var i = 0; i < urls.length; i++) {
                    html += '<div class="'+itemClass+'" data-index="'+i+'" title="Nhấn để xóa ảnh này">' +
                            '<img src="'+urls[i]+'" style="width:100%;height:100%;object-fit:cover;">' +
                            '<div class="'+delClass+'"><svg style="width:18px;height:18px;stroke:#fff;stroke-width:2.5;fill:none" viewBox="0 0 14 14"><path d="M2 2l10 10M12 2L2 12"/></svg></div>' +
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

            function setExpandedPhoto(url) {
                var img = $('#tm-photo-img'), ph = $('#tm-photo-placeholder'), clr = $('#tm-photo-clear');
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

            // Live preview khi người dùng paste URL thủ công vào input
            var pUrlInp = document.getElementById('tm-photo-input');
            if (pUrlInp) {
                pUrlInp.addEventListener('input', function() {
                    setExpandedPhoto(this.value.trim());
                });
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
                            setExpandedPhoto(att.url);
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

            /* ── Expanded Form Submit (Add / Edit page) ── */
            var expandedForm = document.getElementById('ef-member-form');
            if (expandedForm) {
                expandedForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    var submitBtn = document.getElementById('tm-submit-btn');
                    var oldHtml   = submitBtn ? submitBtn.innerHTML : '';
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<svg viewBox="0 0 14 14" style="width:12px;height:12px;stroke:currentColor;stroke-width:2;fill:none;animation:tm-spin 1s linear infinite"><circle cx="7" cy="7" r="5" stroke-opacity=".3"/><path d="M7 2a5 5 0 0 1 5 5"/></svg> Đang lưu…';
                    }

                    var fd = new FormData(expandedForm);
                    fd.set('action', 'bacera_team_save');
                    fd.set('_nonce', tmConfig.nonce);

                    // Sync gallery textarea (hidden) – ensure latest value included
                    var glTa = document.getElementById('tm-gallery');
                    if (glTa) fd.set('gallery_urls', glTa.value);

                    fetch(tmConfig.ajaxurl, { method: 'POST', body: fd })
                        .then(function(r) { return r.json(); })
                        .then(function(res) {
                            if (res.success) {
                                toast(res.data ? res.data.message : 'Đã lưu!');
                                // If adding new member → redirect to edit page of new ID
                                var newId = res.data && res.data.id ? res.data.id : 0;
                                var curId = parseInt((fd.get('id') || '0'), 10);
                                setTimeout(function() {
                                    if (!curId && newId) {
                                        window.location.href = tmConfig.editBase + newId;
                                    } else {
                                        // Reload to refresh form values
                                        window.location.reload();
                                    }
                                }, 800);
                            } else {
                                toast((res.data ? res.data.message : null) || 'Lưu thất bại!', false);
                                if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = oldHtml; }
                            }
                        })
                        .catch(function() {
                            toast('Lỗi kết nối máy chủ!', false);
                            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = oldHtml; }
                        });
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
