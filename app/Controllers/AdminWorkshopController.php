<?php
namespace Bacera\Controllers;

use Bacera\Database\WorkshopTables;

/* ─── Date / Time helpers for slot form ─────────────────────── */
if (!function_exists('wks_fmt_date_vn')) {
    function wks_fmt_date_vn(string $iso): string {
        if (!$iso) return 'Chọn ngày';
        $d = \DateTime::createFromFormat('Y-m-d', $iso);
        if (!$d) return $iso;
        $dow = ['Chủ nhật','Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7'];
        return $dow[(int)$d->format('w')] . ', ' . $d->format('d/m/Y');
    }
}
if (!function_exists('wks_tp')) {
    /**
     * Render a branded 24h time input (supports manual typing).
     * @param string $name   Form field name (e.g. "time_start")
     * @param string $val    Current HH:MM value
     * @param bool   $small  Compact width variant
     */
    function wks_tp(string $name, string $val = '09:00', bool $small = false): string {
        $w   = $small ? 'width:90px;' : 'width:100%;';
        $fid = 'tp_' . esc_attr($name);
        return '<input type="time" class="wks-fi wks-ti" id="' . $fid . '" name="' . esc_attr($name)
             . '" value="' . esc_attr($val) . '" style="' . $w . '" required>';
    }
}

class AdminWorkshopController {

    public function __construct() {
        add_action('admin_menu',             [$this, 'registerMenus']);
        add_action('admin_init',             [$this, 'initDatabase']);

        add_action('admin_post_save_session',            [$this, 'handleSaveSession']);
        add_action('admin_post_create_draft_workshop',   [$this, 'handleCreateDraftWorkshop']);
        add_action('admin_post_save_workshop_general',   [$this, 'handleSaveWorkshopGeneral']);
        add_action('admin_post_delete_workshop',         [$this, 'handleDeleteWorkshop']);
        add_action('admin_enqueue_scripts',  [$this, 'enqueueStyles']);

        // AJAX handlers
        add_action('wp_ajax_wks_checkin',         [$this, 'ajaxCheckin']);
        add_action('wp_ajax_wks_cancel_booking',  [$this, 'ajaxCancelBooking']);
        add_action('wp_ajax_wks_update_payment',  [$this, 'ajaxUpdatePayment']);
        add_action('wp_ajax_wks_save_overview',   [$this, 'ajaxSaveOverview']);
    }

    public function initDatabase() {
        if (get_option('bacera_workshops_db_version') !== WorkshopTables::DB_VERSION) {
            WorkshopTables::createTables();
            update_option('bacera_workshops_db_version', WorkshopTables::DB_VERSION);
        }
        global $wpdb;
        $ts = $wpdb->prefix . 'bacera_workshop_slots';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$ts}'") === $ts) {
            $wpdb->query("UPDATE {$ts} SET status = 'open' WHERE status = 'full' AND booked_seats < total_seats");
        }
    }

    public function registerMenus() {
        add_submenu_page(
            'bacera-main',
            'Quản lý Workshop',
            'Workshop',
            'manage_options',
            'bacera-workshops',
            [$this, 'pageMain']
        );
    }

    /* ════════════════════════════════════════════════════════════
       ENQUEUE STYLES
    ════════════════════════════════════════════════════════════ */
    public function enqueueStyles($hook) {
        if (strpos($hook, 'bacera-workshops') === false) return;

        wp_enqueue_style('bricolage-font', 'https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300;12..96,400;12..96,500;12..96,600;12..96,700&family=DM+Mono:wght@400;500&display=swap', [], null);
        wp_enqueue_media();
        wp_enqueue_script('bacera-admin-js', BACERA_THEME_URI . 'assets/js/admin.js', ['jquery'], BACERA_THEME_VERSION, true);

        $css = '
:root {
  /* Brand palette (matches tailwind.config.js) */
  --bg:#F8F7F3; --surface:#fff; --surface-2:#F1EEE1; --border:#EAE3D1;
  --text:#3d2f26; --text-2:#8d6a54; --text-3:#c0a28e;
  /* Accent: terracotta */
  --accent:#d95f47; --accent-2:#c8513b;
  /* Status colours */
  --green:#166534; --green-bg:#f0fdf4; --green-border:#bbf7d0;
  --amber:#92400e; --amber-bg:#fffbeb; --amber-border:#fde68a;
  --red:#991b1b;   --red-bg:#fef2f2;   --red-border:#fecaca;
  --blue:#1e3a5f;  --blue-bg:#eff6ff;  --blue-border:#bfdbfe;
  --r:10px; --rl:14px;
}
/* Reset WP admin padding so content goes edge-to-edge */
#wpcontent { padding-left: 0 !important; }
#wpbody-content { padding-bottom: 0; }
.wks-wrap *{box-sizing:border-box;margin:0;padding:0}
.wks-wrap{
  font-family:"Bricolage Grotesque",system-ui,sans-serif;
  background:var(--bg);color:var(--text);font-size:14px;line-height:1.6;
  min-height:calc(100vh - 32px);
  padding:32px 36px 64px;
  /* No max-width — fill the full admin content area */
}

/* buttons */
.wks-btn{height:34px;padding:0 14px;border-radius:var(--r);font-family:inherit;font-size:13px;font-weight:500;cursor:pointer;transition:all .15s;display:inline-flex;align-items:center;gap:6px;border:1px solid transparent;text-decoration:none!important;letter-spacing:-.01em}
.wks-btn-outline{background:var(--surface);border-color:var(--border);color:var(--text)!important}
.wks-btn-outline:hover{background:var(--surface-2)!important;border-color:var(--text-3)!important}
.wks-btn-solid{background:var(--accent);color:#fff!important;border-color:var(--accent)}
.wks-btn-solid:hover{background:var(--accent-2)!important;border-color:var(--accent-2)!important}
.wks-btn-danger{background:var(--red-bg);color:var(--red)!important;border-color:var(--red-border)}
.wks-btn-danger:hover{background:#fee2e2!important}
.wks-btn-ghost{background:none;border:none;color:var(--text-2);font-family:inherit;font-size:13px;cursor:pointer;padding:4px 8px;border-radius:6px;transition:all .15s;display:inline-flex;align-items:center;gap:5px}
.wks-btn-ghost:hover{background:var(--surface-2);color:var(--text)}
.wks-btn svg{width:13px;height:13px;flex-shrink:0}
.wks-btn-sm{height:28px;padding:0 10px;font-size:12px}

/* badge */
.wks-badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap;letter-spacing:.02em}
.wks-bg-green{background:var(--green-bg);color:var(--green);border:1px solid var(--green-border)}
.wks-bg-amber{background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber-border)}
.wks-bg-red{background:var(--red-bg);color:var(--red);border:1px solid var(--red-border)}
.wks-bg-blue{background:var(--blue-bg);color:var(--blue);border:1px solid var(--blue-border)}
.wks-bg-gray{background:var(--surface-2);color:var(--text-2);border:1px solid var(--border)}
.wks-bg-accent{background:#fff5f3;color:var(--accent);border:1px solid #ffd5cc}

/* ── BOARD ── */
.wks-list-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px}
.wks-list-title{font-size:24px;font-weight:600;letter-spacing:-.5px;color:var(--text)}
.wks-list-sub{font-size:13px;color:var(--text-2);margin-top:4px}
.wks-filter-row{display:flex;gap:8px;margin-bottom:20px;align-items:center;flex-wrap:wrap}
.wks-search-wrap{position:relative;flex:1;max-width:320px}
.wks-search-wrap svg{position:absolute;left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;stroke:var(--text-3);fill:none;stroke-width:1.5}
.wks-inp{width:100%;height:36px;padding:0 12px 0 34px;font-family:inherit;font-size:13px;border:1px solid var(--border);border-radius:var(--r);background:var(--surface);color:var(--text);outline:none;transition:all .15s}
.wks-inp:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(217,95,71,.1)}
.wks-sel{height:36px;padding:0 12px;font-family:inherit;font-size:13px;border:1px solid var(--border);border-radius:var(--r);background:var(--surface);color:var(--text-2);outline:none;cursor:pointer;transition:border-color .15s}
.wks-sel:focus{border-color:var(--accent)}
.wks-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px}
.wks-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--rl);cursor:pointer;transition:all .18s;overflow:hidden;text-decoration:none!important;display:block;color:var(--text)!important}
.wks-card:hover{border-color:var(--text-3);box-shadow:0 6px 24px rgba(61,47,38,.09);transform:translateY(-2px)}
.wks-card-img{height:140px;background:linear-gradient(135deg,var(--surface-2),var(--border));display:flex;align-items:center;justify-content:center;border-bottom:1px solid var(--border);position:relative;overflow:hidden}
.wks-card-img img{width:100%;height:100%;object-fit:cover}
.wks-card-img-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center}
.wks-card-img-placeholder svg{width:44px;height:44px;stroke:var(--text-3);fill:none;stroke-width:1;opacity:.4}
.wks-card-badge{position:absolute;top:10px;right:10px}
.wks-card-body{padding:14px 16px}
.wks-card-title{font-size:14px;font-weight:600;margin-bottom:6px;line-height:1.4;color:var(--text)}
.wks-card-meta{font-size:12px;color:var(--text-2);margin-bottom:12px;display:flex;gap:10px;flex-wrap:wrap}
.wks-card-meta span{display:flex;align-items:center;gap:4px}
.wks-card-meta svg{width:12px;height:12px;stroke:currentColor;fill:none;stroke-width:1.5;flex-shrink:0}
.wks-card-foot{display:flex;align-items:center;justify-content:space-between;padding-top:10px;border-top:1px solid var(--border)}
.wks-slot-info{font-size:12px;color:var(--text-2)}
.wks-slot-info strong{color:var(--text)}
.wks-mini-bar{height:3px;border-radius:2px;background:var(--border);margin-top:5px;width:100px;overflow:hidden}
.wks-mini-fill{height:100%;border-radius:2px}
.wks-empty{background:var(--surface);border:1px solid var(--border);border-radius:var(--rl);padding:64px 24px;text-align:center}
.wks-empty svg{width:48px;height:48px;stroke:var(--text-3);fill:none;stroke-width:1;margin-bottom:16px;opacity:.4}
.wks-empty h3{font-size:16px;font-weight:600;margin-bottom:6px;color:var(--text)}
.wks-empty p{font-size:13px;color:var(--text-2);margin-bottom:20px}

/* ── BACK / HEADER ── */
.wks-back{display:inline-flex;align-items:center;gap:5px;font-size:13px;color:var(--text-2);cursor:pointer;margin-bottom:20px;background:none;border:none;font-family:inherit;padding:0;transition:color .15s;text-decoration:none!important}
.wks-back:hover{color:var(--accent)}
.wks-back svg{width:14px;height:14px}
.wks-page-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:24px}
.wks-page-title{font-size:24px;font-weight:600;letter-spacing:-.5px;color:var(--text)}
.wks-title-row{display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap}
.wks-page-meta{font-size:13px;color:var(--text-2)}
.wks-page-meta span{margin:0 5px;color:var(--border)}
.wks-hdr-actions{display:flex;gap:8px;flex-shrink:0;align-items:flex-start}

/* ── METRICS ── */
.wks-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px}
.wks-mc{background:var(--surface);border:1px solid var(--border);border-radius:var(--rl);padding:18px 20px;transition:box-shadow .15s}
.wks-mc:first-child{border-top:3px solid var(--accent)}
.wks-ml{font-size:11px;font-weight:600;color:var(--text-3);text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px}
.wks-mv{font-size:28px;font-weight:600;letter-spacing:-.8px;line-height:1;color:var(--text)}
.wks-ms{font-size:12px;color:var(--text-2);margin-top:7px}
.wks-sbar{height:4px;border-radius:2px;background:var(--border);margin-top:10px;overflow:hidden}
.wks-sfill{height:100%;background:var(--accent);border-radius:2px}

/* ── TABS ── */
.wks-tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:24px}
.wks-tab{padding:9px 18px;font-size:13px;font-weight:500;cursor:pointer;color:var(--text-2);border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .15s;text-decoration:none!important;display:inline-flex;align-items:center;letter-spacing:-.01em}
.wks-tab:hover{color:var(--text)}
.wks-tab.active{color:var(--accent);border-bottom-color:var(--accent);font-weight:600}
.wks-tab-ct{background:var(--surface-2);border:1px solid var(--border);border-radius:20px;padding:0 6px;font-size:10px;margin-left:5px;font-weight:600}
.wks-tab-panel{display:none}
.wks-tab-panel.active{display:block}

/* ── SECTION CARD ── */
.wks-sc{background:var(--surface);border:1px solid var(--border);border-radius:var(--rl);margin-bottom:20px;overflow:hidden}
.wks-sh{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border);background:var(--surface)}
.wks-st{font-size:14px;font-weight:600;color:var(--text)}
.wks-sb{padding:20px}

/* ── BASIC INFO / FORM ── */
.wks-irow{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px}
.wks-fl{font-size:11px;font-weight:600;color:var(--text-3);text-transform:uppercase;letter-spacing:.7px;margin-bottom:4px}
.wks-fv{font-size:14px;font-weight:500;color:var(--text)}
.wks-ff{margin-bottom:14px}
.wks-ff label{font-size:11px;font-weight:600;color:var(--text-2);text-transform:uppercase;letter-spacing:.6px;margin-bottom:5px;display:block}
.wks-fi,.wks-ft,.wks-ff select{width:100%;font-family:inherit;font-size:13px;border:1px solid var(--border);border-radius:var(--r);background:var(--bg);color:var(--text);outline:none;transition:all .15s;padding:0 11px}
.wks-fi,.wks-ff select{height:36px}
.wks-ft{padding:9px 11px;resize:vertical;min-height:80px;line-height:1.6;height:auto}
.wks-fi:focus,.wks-ft:focus,.wks-ff select:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(217,95,71,.1)}
.wks-f2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.wks-f3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
.wks-fhint{font-size:11px;color:var(--text-3);margin-top:3px}

/* ── GALLERY ── */
.wks-gallery{display:flex;gap:10px;flex-wrap:wrap}
/* Gallery grid */
.wks-gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:10px;margin-top:4px}
.wks-gal-item{position:relative;border-radius:var(--r);overflow:hidden;aspect-ratio:4/3;border:1px solid var(--border);background:var(--surface-2)}
.wks-gal-item img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .2s}
.wks-gal-item:hover img{transform:scale(1.04)}
.wks-gal-remove{position:absolute;top:5px;right:5px;width:22px;height:22px;border-radius:50%;background:rgba(0,0,0,.6);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .15s;padding:0}
.wks-gal-remove svg{width:9px;height:9px;stroke:#fff}
.wks-gal-item:hover .wks-gal-remove{opacity:1}
.wks-gal-add{border:2px dashed var(--border);border-radius:var(--r);background:var(--surface-2);cursor:pointer;aspect-ratio:4/3;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;transition:all .15s;width:100%}
.wks-gal-add:hover{border-color:var(--accent);background:#fff5f3}
.wks-gal-add:hover svg rect{stroke:var(--accent)}
.wks-gal-add:hover svg path{stroke:var(--accent)}
.wks-gal-add:hover span{color:var(--accent)!important}
.wks-ith{width:90px;height:68px;border-radius:var(--r);border:1px solid var(--border);background:var(--surface-2);display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;transition:all .15s;gap:4px;text-decoration:none!important;color:var(--text-2)}
.wks-ith:hover{border-color:var(--text-3);background:var(--border)}
.wks-ith svg{width:20px;height:20px;stroke:var(--text-3);fill:none;stroke-width:1.5}
.wks-ith span{font-size:10px;color:var(--text-3)}
.wks-ith-add{border-style:dashed}

/* ── OVERVIEW EDITOR ── */
.wks-ov-section{background:var(--surface);border:1px solid var(--border);border-radius:var(--rl);margin-bottom:20px;overflow:hidden}
.wks-ov-head{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.wks-ov-head-title{font-size:14px;font-weight:600}
.wks-ov-body{padding:18px 20px}
.wks-ov-tagline{font-size:16px;font-weight:600;margin-bottom:6px;letter-spacing:-.2px}
.wks-ov-desc{font-size:14px;color:var(--text-2);line-height:1.75;margin-bottom:20px}
.wks-ov-meta{display:flex;gap:32px;margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--border)}
.wks-ov-m-label{font-size:11px;font-weight:600;color:var(--text-3);text-transform:uppercase;letter-spacing:.7px;margin-bottom:3px}
.wks-ov-m-value{font-size:14px;font-weight:500}
.wks-ov-cols{display:grid;grid-template-columns:1fr 1fr;gap:24px}
.wks-ov-col-title{font-size:11px;font-weight:600;color:var(--text-3);text-transform:uppercase;letter-spacing:.7px;margin-bottom:10px}
.wks-ov-ul{list-style:none;display:flex;flex-direction:column;gap:7px;padding:0}
.wks-ov-ul li{font-size:13px;color:var(--text-2);display:flex;align-items:flex-start;gap:8px;line-height:1.5}
.wks-ov-ul li::before{content:"";width:5px;height:5px;border-radius:50%;background:var(--text-3);flex-shrink:0;margin-top:6.5px}
.wks-ov-ul.hi li::before{background:var(--green)}
.wks-ov-edit{display:none;padding:18px 20px;border-top:1px solid var(--border)}
.wks-ov-section.editing .wks-ov-edit{display:block}
.wks-edit-acts{display:flex;gap:8px;margin-top:18px;padding-top:16px;border-top:1px solid var(--border)}
.wks-le{display:flex;flex-direction:column;gap:6px}
.wks-le-item{display:flex;align-items:center;gap:6px}
.wks-le-item input{flex:1;height:32px;font-family:inherit;font-size:13px;border:1px solid var(--border);border-radius:7px;padding:0 10px;background:var(--bg);color:var(--text);outline:none}
.wks-le-item input:focus{border-color:var(--text-3)}
.wks-ic-btn{width:28px;height:28px;border-radius:7px;border:1px solid var(--border);background:var(--surface);cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s}
.wks-ic-btn svg{width:13px;height:13px;stroke:var(--text-2);fill:none;stroke-width:1.8}
.wks-ic-btn.del:hover{background:var(--red-bg);border-color:#F7C1C1}
.wks-ic-btn.del:hover svg{stroke:var(--red)}
.wks-add-row{display:inline-flex;align-items:center;gap:5px;font-size:12px;color:var(--text-2);cursor:pointer;background:none;border:none;font-family:inherit;padding:4px 0;margin-top:2px;transition:color .15s}
.wks-add-row:hover{color:var(--text)}
.wks-add-row svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2}

/* ── SESSIONS ── */
/* ── SESSION CARDS GRID ── */
.wks-sessions-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;margin-top:4px}
.wks-slot-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--rl);overflow:hidden;display:flex;flex-direction:column;transition:all .18s;position:relative}
.wks-slot-card:hover{border-color:var(--text-3);box-shadow:0 6px 24px rgba(61,47,38,.09);transform:translateY(-2px)}
.wks-slot-card-accent{height:3px;width:100%;flex-shrink:0}
.wks-slot-card-body{padding:16px;flex:1;display:flex;flex-direction:column;gap:12px}
.wks-slot-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:8px}
.wks-slot-date-block{display:flex;flex-direction:column;gap:1px}
.wks-slot-dow{font-size:10px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.06em}
.wks-slot-date{font-size:16px;font-weight:700;color:var(--text);letter-spacing:-.02em;line-height:1.2}
.wks-slot-badges{display:flex;gap:5px;flex-wrap:wrap;align-items:center;margin-top:2px}
.wks-slot-meta-row{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-2)}
.wks-slot-meta-row svg{width:12px;height:12px;stroke:var(--text-3);fill:none;stroke-width:1.8;flex-shrink:0}
.wks-slot-meta-row strong{color:var(--text);font-weight:600}
.wks-slot-cap-wrap{display:flex;flex-direction:column;gap:5px}
.wks-slot-cap-row{display:flex;justify-content:space-between;font-size:11px;color:var(--text-2)}
.wks-slot-cap-num{font-weight:600;color:var(--text)}
.wks-slot-bar{height:5px;background:var(--border);border-radius:3px;overflow:hidden}
.wks-slot-fill{height:100%;border-radius:3px;transition:width .3s}
.wks-slot-card-foot{padding:10px 16px;border-top:1px solid var(--border);display:flex;gap:6px}
.wks-slot-card-foot .wks-btn{flex:1;justify-content:center}

/* \u2500\u2500 INLINE ROSTER \u2500\u2500 */
.wks-roster{border-top:1px solid var(--border)}
.wks-roster-toggle{width:100%;display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 16px;background:var(--bg);border:none;cursor:pointer;font-family:inherit;transition:background .12s}
.wks-roster-toggle:hover{background:var(--surface-2)}
.wks-roster-toggle-left{display:flex;align-items:center;gap:8px}
.wks-roster-count{font-size:12px;font-weight:600;color:var(--text-2)}
.wks-roster-chevron{width:14px;height:14px;stroke:var(--text-3);fill:none;stroke-width:2;stroke-linecap:round;transition:transform .18s;flex-shrink:0}
.wks-roster-chevron.open{transform:rotate(180deg)}
/* Avatar stack */
.wks-av-stack{display:flex;align-items:center}
.wks-av-mini{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;border:2px solid var(--surface);margin-left:-6px;flex-shrink:0}
.wks-av-stack .wks-av-mini:first-child{margin-left:0}
/* Roster body */
.wks-roster-body{border-top:1px solid var(--border)}
.wks-roster-row{display:flex;align-items:center;gap:10px;padding:8px 14px;border-bottom:1px solid var(--border);transition:background .1s}
.wks-roster-row:last-child{border-bottom:none}
.wks-roster-row:hover{background:var(--bg)}
.wks-roster-info{flex:1;min-width:0}
.wks-roster-name{font-size:12px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.wks-roster-phone{font-size:11px;color:var(--text-3)}
.wks-roster-badges{display:flex;gap:4px;flex-shrink:0}
/* Check-in button */
.wks-ci-btn{width:28px;height:28px;border-radius:50%;border:1.5px solid var(--border);background:none;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s;flex-shrink:0}
.wks-ci-btn svg{width:12px;height:12px;stroke:var(--text-3);stroke-linecap:round;stroke-linejoin:round;fill:none;transition:stroke .15s}
.wks-ci-btn:hover{border-color:var(--green);background:rgba(26,107,74,.07)}
.wks-ci-btn:hover svg{stroke:var(--green)}
.wks-ci-btn.checked{border-color:var(--green);background:var(--green)}
.wks-ci-btn.checked svg{stroke:#fff}


/* Header */
.wsf-header{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 22px;border-bottom:1px solid var(--border);background:rgba(250,248,243,.9);backdrop-filter:blur(8px);position:sticky;top:32px;z-index:20;flex-wrap:wrap}
.wsf-header-info{display:flex;align-items:center;gap:12px}
.wsf-header-icon{width:38px;height:38px;border-radius:10px;background:var(--surface-2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.wsf-header-icon svg{width:18px;height:18px;stroke:var(--text-2);fill:none;stroke-width:1.8;stroke-linecap:round}
.wsf-header-title{font-size:15px;font-weight:700;color:var(--text);leading-trim:both}
.wsf-header-sub{font-size:11px;color:var(--text-3);margin-top:1px}
.wsf-header-actions{display:flex;align-items:center;gap:8px}
.wsf-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:8px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;transition:all .15s;border:none;outline:none;text-decoration:none}
.wsf-btn-cancel{background:var(--bg);border:1px solid var(--border);color:var(--text-2)}
.wsf-btn-cancel:hover{background:var(--surface-2);border-color:var(--text-3);color:var(--text)}
.wsf-btn-save{background:var(--text);color:#fff;box-shadow:0 2px 8px rgba(61,47,38,.18)}
.wsf-btn-save:hover{background:#1a1410}
.wsf-btn-save svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2.2;stroke-linecap:round}
/* Body grid */
.wsf-body{display:grid;grid-template-columns:1fr 380px;gap:32px;padding:28px 24px}
.wsf-col-left{display:flex;flex-direction:column;gap:28px}
.wsf-col-right{}
.wsf-ticket-panel{background:var(--bg);border:1px solid var(--border);border-radius:14px;padding:22px;display:flex;flex-direction:column;gap:22px;height:100%}
/* Sections */
.wsf-section{display:flex;flex-direction:column;gap:16px}
.wsf-section-title{display:flex;align-items:center;gap:7px;font-size:11px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.07em;padding-bottom:10px;border-bottom:1px solid var(--border)}
.wsf-section-title svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;flex-shrink:0}
.wsf-fields{display:flex;flex-direction:column;gap:18px}
/* Fields */
.wsf-field{display:flex;flex-direction:column;gap:6px}
.wsf-label{font-size:12px;font-weight:600;color:var(--text-2);display:flex;align-items:center;gap:5px}
.wsf-label svg{stroke:var(--text-3);fill:none;stroke-width:1.8;flex-shrink:0}
.wsf-req{color:var(--accent)}
.wsf-hint{font-size:11px;color:var(--text-3);line-height:1.5;margin-top:2px}
/* Input base */
.wsf-input{width:100%;font-family:inherit;font-size:13px;padding:9px 13px;border:1px solid var(--border);border-radius:10px;background:var(--surface);color:var(--text);outline:none;transition:all .15s;color-scheme:light}
.wsf-input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(217,95,71,.1);background:#fff}
.wsf-input-lg{font-size:18px;font-weight:700;padding:10px 14px}
.wsf-input-sm{font-size:12px;padding:7px 10px}
.wsf-input-time{font-variant-numeric:tabular-nums;max-width:130px}
.wsf-input-time::-webkit-calendar-picker-indicator{opacity:.4;cursor:pointer}
/* Icon-prefixed input */
.wsf-input-icon{position:relative;display:flex;align-items:center;max-width:300px}
.wsf-input-icon .wsf-input{padding-left:36px;width:100%}
.wsf-ico{position:absolute;left:11px;width:16px;height:16px;stroke:var(--text-3);fill:none;stroke-width:1.8;stroke-linecap:round;flex-shrink:0;pointer-events:none}
/* Time range row */
.wsf-time-range{display:flex;align-items:center;gap:10px}
.wsf-arrow{width:16px;height:16px;stroke:var(--text-3);fill:none;stroke-width:1.8;stroke-linecap:round;flex-shrink:0}
/* Toggle cards */
.wsf-toggle-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;transition:border-color .15s}
.wsf-toggle-card:hover{border-color:var(--text-3)}
.wsf-toggle-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;cursor:pointer;user-select:none}
.wsf-toggle-label{font-size:13px;font-weight:600;color:var(--text)}
.wsf-toggle-desc{font-size:11px;color:var(--text-3);margin-top:2px;line-height:1.4}
.wsf-toggle-body{padding:0 16px 14px}
/* Toggle switch */
.wsf-switch{width:42px;height:24px;border-radius:12px;background:var(--border);border:none;cursor:pointer;position:relative;transition:background .2s;flex-shrink:0;padding:0}
.wsf-switch.on{background:var(--green)}
.wsf-switch-knob{position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.2);transition:left .2s}
.wsf-switch.on .wsf-switch-knob{left:21px}
/* Custom status dropdown */
.wsf-status-wrap{position:relative}
.wsf-status-btn{width:100%;display:flex;align-items:center;gap:10px;padding:10px 14px;border:1px solid var(--border);border-radius:10px;background:var(--surface);cursor:pointer;font-family:inherit;font-size:13px;font-weight:600;color:var(--text);transition:all .15s;text-align:left}
.wsf-status-btn:hover,.wsf-status-btn.open{border-color:var(--accent);box-shadow:0 0 0 3px rgba(217,95,71,.1)}
.wsf-status-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.wsf-status-dot-open{background:#10b981}
.wsf-status-dot-full{background:#ef4444}
.wsf-status-dot-cancelled{background:#a8a29e}
.wsf-status-chevron{width:16px;height:16px;stroke:var(--text-3);fill:none;stroke-width:2;stroke-linecap:round;margin-left:auto;transition:transform .15s}
.wsf-status-btn.open .wsf-status-chevron{transform:rotate(180deg)}
.wsf-status-menu{display:none;position:absolute;top:calc(100% + 6px);left:0;width:100%;background:#fff;border:1px solid var(--border);border-radius:10px;box-shadow:0 10px 32px rgba(61,47,38,.12);overflow:hidden;z-index:100}
.wsf-status-menu.open{display:block;animation:wksDpIn .12s ease}
.wsf-status-opt{display:flex;align-items:center;gap:10px;padding:10px 14px;cursor:pointer;font-size:13px;font-weight:500;color:var(--text-2);transition:background .1s}
.wsf-status-opt:hover{background:var(--bg);color:var(--text)}
/* Price field */
.wsf-price-wrap{position:relative}
.wsf-price-wrap .wsf-input{padding-right:28px}
.wsf-price-d{position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:12px;font-weight:600;color:var(--text-3);pointer-events:none}
/* Responsive */
@media(max-width:900px){.wsf-body{grid-template-columns:1fr}.wsf-input-icon{max-width:100%}.wsf-input-time{max-width:100%}.wsf-header{top:0}}
/* Roster panel inside form */
.wsf-roster-panel{background:var(--bg);border:1px solid var(--border);border-radius:14px;padding:18px;margin-top:14px;display:flex;flex-direction:column;gap:10px}
.wsf-roster-ct{background:var(--surface-2);border:1px solid var(--border);border-radius:20px;padding:1px 8px;font-size:10px;font-weight:700;color:var(--text-2);margin-left:auto}
.wsf-roster-empty{font-size:12px;color:var(--text-3);text-align:center;padding:16px 0}
.wsf-roster-list{display:flex;flex-direction:column;border:1px solid var(--border);border-radius:10px;overflow:hidden;max-height:340px;overflow-y:auto}
.wsf-roster-row{display:flex;align-items:center;gap:10px;padding:9px 12px;border-bottom:1px solid var(--border);background:var(--surface);transition:background .1s}
.wsf-roster-row:last-child{border-bottom:none}
.wsf-roster-row:hover{background:var(--bg)}
.wsf-rl-info{flex:1;min-width:0}
.wsf-rl-name{font-size:12px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.wsf-rl-meta{font-size:11px;color:var(--text-3);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.wsf-rl-badges{display:flex;gap:4px;flex-shrink:0}
.wsf-roster-all-link{display:block;text-align:center;font-size:12px;font-weight:600;color:var(--accent);text-decoration:none;padding:8px 0 2px;border-top:1px solid var(--border);transition:color .15s}
.wsf-roster-all-link:hover{color:var(--accent-2)}



/* ════════════════════════════════════
   OVERVIEW TAB — 2-col layout system
   ════════════════════════════════════ */
/* Top action bar */
.wks-ov-topbar{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;background:var(--surface);border:1px solid var(--border);border-radius:14px;margin-bottom:20px;gap:12px}
.wks-ov-topbar-left{display:flex;align-items:center;gap:12px;min-width:0}
.wks-ov-topbar-right{display:flex;align-items:center;gap:8px;flex-shrink:0}
.wks-ov-tbicon{width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#fff1ee,#ffe0d8);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.wks-ov-tbicon svg{width:18px;height:18px;color:#c53030}
.wks-ov-tbname{font-size:15px;font-weight:700;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:380px}
.wks-ov-tbmeta{font-size:12px;color:var(--text-3);margin-top:2px;display:flex;align-items:center;gap:5px}
.wks-ov-status-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
.wks-ov-status-dot.live{background:#10b981}
.wks-ov-status-dot.draft{background:#9ca3af}

/* 2-col layout */
.wks-ov-layout{display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start}
.wks-ov-main{display:flex;flex-direction:column;gap:16px}
.wks-ov-sidebar{display:flex;flex-direction:column;gap:16px;position:sticky;top:56px}

/* Card */
.wks-ov-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden}
.wks-ov-card-head{display:flex;align-items:center;gap:8px;padding:14px 18px;border-bottom:1px solid var(--border);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--text-2)}
.wks-ov-card-head svg{width:14px;height:14px;flex-shrink:0}
.wks-ov-card-sub{font-size:11px;font-weight:400;text-transform:none;letter-spacing:0;color:var(--text-3);margin-left:auto}
.wks-ov-card-ct{background:var(--surface-2);border:1px solid var(--border);border-radius:20px;padding:1px 8px;font-size:10px;font-weight:700;color:var(--text-2);margin-left:auto;text-transform:none;letter-spacing:0}
.wks-ov-card-body{padding:18px}
.wks-ov-card-body.wks-ov-compact{padding:14px 18px;display:flex;flex-direction:column;gap:12px}

/* Fields */
.wks-ov-field{display:flex;flex-direction:column;gap:4px}
.wks-ov-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--text-2);display:flex;align-items:center;gap:6px}
.wks-ov-badge-label{background:#f0fdf4;border:1px solid #86efac;color:#16a34a;font-size:9px;font-weight:600;padding:1px 6px;border-radius:20px;text-transform:none;letter-spacing:0}
.wks-ov-badge-seo{background:#eff6ff;border:1px solid #93c5fd;color:#1d4ed8;font-size:9px;font-weight:600;padding:1px 6px;border-radius:20px;text-transform:none;letter-spacing:0}
.wks-ov-input{width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:10px;background:var(--bg);color:var(--text);font-size:13px;outline:none;transition:border-color .15s,box-shadow .15s;font-family:inherit;box-sizing:border-box}
.wks-ov-input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(239,68,68,.08)}
.wks-ov-textarea{resize:vertical;min-height:72px}
.wks-ov-hint{font-size:11px;color:var(--text-3);line-height:1.4}
.wks-req{color:var(--red)}

/* Card fields stacking */
.wks-ov-card-body .wks-ov-field + .wks-ov-field{margin-top:14px}

/* Price input */
.wks-ov-price-wrap{position:relative}
.wks-ov-price-wrap .wks-ov-input{padding-right:28px}
.wks-ov-price-unit{position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:13px;color:var(--text-3);pointer-events:none;font-weight:600}
/* Unit input */
.wks-ov-unit-wrap{position:relative}
.wks-ov-unit-wrap .wks-ov-input{padding-right:40px}
.wks-ov-unit{position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:12px;color:var(--text-3);pointer-events:none}

/* 2-col small (for sidebar) */
.wks-ov-2col-sm{display:grid;grid-template-columns:1fr 1fr;gap:10px}

/* Thumbnail */
.wks-ov-thumb-wrap{border-radius:10px;overflow:hidden;border:1px solid var(--border);background:var(--surface-2);margin-bottom:10px;aspect-ratio:16/9}
.wks-ov-thumb-img{width:100%;height:100%;object-fit:cover;display:block}
.wks-ov-thumb-ph{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;height:100%;color:var(--text-3);font-size:12px}
.wks-ov-thumb-actions{display:flex;gap:8px;align-items:center}

/* List section 2col */
.wks-ov-2col-list{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.wks-ov-list-head{display:flex;align-items:center;gap:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--text-2);margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid var(--border)}
.wks-ov-list-head svg{width:12px;height:12px}

/* Status select */
.wks-ov-status-select select{cursor:pointer}

/* Responsive */
@media(max-width:1100px){.wks-ov-layout{grid-template-columns:1fr 280px}}
@media(max-width:900px){
    .wks-ov-layout{grid-template-columns:1fr}
    .wks-ov-sidebar{position:static}
    .wks-ov-2col-list{grid-template-columns:1fr}
    .wks-ov-topbar{flex-wrap:wrap}
}

/* ── Custom Date Picker ── */

.wks-dp-wrap{position:relative}
.wks-dp-trigger{width:100%;height:36px;padding:0 11px;font-family:inherit;font-size:13px;border:1px solid var(--border);border-radius:var(--r);background:var(--bg);color:var(--text);cursor:pointer;text-align:left;display:flex;align-items:center;justify-content:space-between;gap:6px;outline:none;transition:all .15s}
.wks-dp-trigger:hover,.wks-dp-trigger.open{border-color:var(--accent);box-shadow:0 0 0 3px rgba(217,95,71,.1)}
.wks-dp-trigger-txt{flex:1;color:var(--text);font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.wks-dp-trigger-txt.placeholder{color:var(--text-3)}
.wks-dp-trigger-ico{width:14px;height:14px;stroke:var(--text-3);fill:none;stroke-width:1.8;flex-shrink:0}
/* Calendar popup */
.wks-cal{display:none;position:absolute;top:calc(100% + 6px);left:0;z-index:9999;background:#FAF8F3;border:1px solid var(--border);border-radius:14px;box-shadow:0 10px 40px rgba(61,47,38,.13);width:296px;overflow:hidden}
.wks-cal.open{display:block;animation:wksDpIn .15s cubic-bezier(.16,1,.3,1)}
@keyframes wksDpIn{from{opacity:0;transform:translateY(-6px) scale(.98)}to{opacity:1;transform:none}}
.wks-cal-head{display:flex;align-items:center;justify-content:space-between;padding:16px 16px 10px}
.wks-cal-monthlbl{font-size:15px;font-weight:700;color:var(--text);letter-spacing:-.02em}
.wks-cal-navs{display:flex;gap:6px}
.wks-cal-nav{width:30px;height:30px;border-radius:50%;background:none;border:1.5px solid var(--border);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s;flex-shrink:0}
.wks-cal-nav:hover{background:var(--surface);border-color:var(--accent);color:var(--accent)}
.wks-cal-nav.active{border-color:var(--accent);color:var(--accent)}
.wks-cal-nav svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2.2;stroke-linecap:round;stroke-linejoin:round}
.wks-cal-grid{padding:0 12px 14px}
.wks-cal-dow{display:grid;grid-template-columns:repeat(7,1fr);margin-bottom:6px}
.wks-cal-dow span{text-align:center;font-size:11px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.04em;padding:4px 0}
.wks-cal-days{display:grid;grid-template-columns:repeat(7,1fr);gap:3px}
.wks-cal-day{position:relative;width:100%;aspect-ratio:1;border-radius:9px;border:none;background:var(--surface-2);font-family:inherit;font-size:13px;cursor:pointer;color:var(--text);display:flex;align-items:center;justify-content:center;transition:background .12s,color .12s;outline:none;padding:0;font-weight:500}
.wks-cal-day:hover:not(.past):not(.disabled){background:var(--border)}
.wks-cal-day.today{color:var(--accent);font-weight:700}
.wks-cal-day.today::after{content:"";position:absolute;bottom:4px;left:50%;transform:translateX(-50%);width:4px;height:4px;border-radius:50%;background:var(--accent)}
.wks-cal-day.sel{background:var(--accent)!important;color:#fff!important;font-weight:700}
.wks-cal-day.sel::after{display:none}
.wks-cal-day.past{color:var(--text-3);cursor:default;position:relative;overflow:hidden}
.wks-cal-day.past::before{content:"";position:absolute;inset:0;background:repeating-linear-gradient(-45deg,transparent,transparent 4px,rgba(192,162,142,.25) 4px,rgba(192,162,142,.25) 5px)}
.wks-cal-day.empty{background:none;cursor:default;pointer-events:none}
/* Time input */
.wks-ti{font-variant-numeric:tabular-nums;color-scheme:light;letter-spacing:.02em}
.wks-ti::-webkit-calendar-picker-indicator{opacity:.5;cursor:pointer}
/* Slot price */
.wks-price-wrap{position:relative}
.wks-price-suffix{position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:12px;font-weight:600;color:var(--text-3);pointer-events:none}

/* ── TABLE ── */
.wks-sr-row{display:flex;gap:10px;margin-bottom:14px;align-items:center;flex-wrap:wrap}
.wks-rc{font-size:12px;color:var(--text-3);white-space:nowrap;margin-left:auto}
.wks-tw{overflow-x:auto}
.wks-table{width:100%;border-collapse:collapse}
.wks-table thead th{font-size:11px;font-weight:600;color:var(--text-3);text-transform:uppercase;letter-spacing:.7px;text-align:left;padding:8px 12px;border-bottom:1px solid var(--border);white-space:nowrap;background:var(--surface)}
.wks-table tbody tr{transition:background .1s}
.wks-table tbody tr:hover{background:var(--bg)}
.wks-table tbody td{padding:10px 12px;border-bottom:1px solid var(--border);vertical-align:middle}
.wks-table tbody tr:last-child td{border-bottom:none}
.wks-mono{font-family:"DM Mono",monospace;font-size:12px;color:var(--text-2)}
.wks-av{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:600;flex-shrink:0}
.wks-nc{display:flex;align-items:center;gap:10px}
.wks-ns{font-size:12px;color:var(--text-2);margin-top:1px}
.wks-action-btn{font-size:12px;color:var(--text-2);cursor:pointer;background:none;border:none;font-family:inherit;padding:4px 8px;border-radius:6px;transition:all .15s}
.wks-action-btn:hover{background:var(--surface-2);color:var(--text)}

/* ── REVIEWS ── */
.wks-rs{display:flex;align-items:center;gap:24px;padding:18px 20px;border-bottom:1px solid var(--border)}
.wks-rb{font-size:36px;font-weight:700;letter-spacing:-1px;line-height:1;color:var(--text)}
.wks-stars{color:var(--accent);font-size:16px;letter-spacing:1px}
.wks-stars-sm{color:var(--accent);font-size:13px}
.wks-rc2{font-size:12px;color:var(--text-2);margin-top:2px}
.wks-rbars{flex:1}
.wks-rr{display:flex;align-items:center;gap:8px;margin-bottom:4px;font-size:12px;color:var(--text-2)}
.wks-rbar{flex:1;height:4px;background:var(--border);border-radius:2px;overflow:hidden}
.wks-rf{height:100%;background:var(--accent);border-radius:2px}
.wks-rn{width:20px;text-align:right;color:var(--text-2)}
.wks-ri{padding:18px 20px;border-bottom:1px solid var(--border)}
.wks-ri:last-child{border-bottom:none}
.wks-rih{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.wks-rim{flex:1}
.wks-rin{font-size:13px;font-weight:600;color:var(--text)}
.wks-rid{font-size:12px;color:var(--text-3)}
.wks-rit{font-size:13px;color:var(--text-2);line-height:1.7}
.wks-notice-success{background:var(--green-bg);border:1px solid var(--green-border);border-radius:var(--r);padding:10px 16px;color:var(--green);font-size:13px;margin-bottom:20px}
.wks-notice-error{background:var(--red-bg);border:1px solid var(--red-border);border-radius:var(--r);padding:10px 16px;color:var(--red);font-size:13px;margin-bottom:20px}
';
        wp_register_style('bacera-wks-admin', false);
        wp_enqueue_style('bacera-wks-admin');
        wp_add_inline_style('bacera-wks-admin', $css);

        // Inline JS for roster toggle + checkin
        $js = '
window.wksRosterToggle = function(id) {
    var body = document.getElementById(id + "-body");
    var ico  = document.getElementById(id + "-ico");
    if (!body) return;
    var open = body.style.display !== "none";
    body.style.display = open ? "none" : "block";
    if (ico) { open ? ico.classList.remove("open") : ico.classList.add("open"); }
};
window.wksCheckin = function(bookingId, nonce, btn) {
    if (btn.classList.contains("checked")) return; // already checked in
    btn.style.opacity = "0.5";
    btn.disabled = true;
    var fd = new FormData();
    fd.append("action", "wks_checkin");
    fd.append("booking_id", bookingId);
    fd.append("_ajax_nonce", nonce);
    fetch(wks_ajax.url, { method: "POST", body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            btn.style.opacity = "";
            btn.disabled = false;
            if (d.success) {
                btn.classList.add("checked");
                btn.title = "Đã điểm danh";
            } else {
                alert("Lỗi: " + (d.data || "Không thể điểm danh"));
            }
        });
};
';
        wp_add_inline_script('bacera-admin-js', $js);
        wp_localize_script('bacera-admin-js', 'wks_ajax', ['url' => admin_url('admin-ajax.php')]);
    }

    /* ════════════════════════════════════════════════════════════
       MAIN ROUTER
    ════════════════════════════════════════════════════════════ */
    public function pageMain() {
        if (!current_user_can('manage_options')) return;

        $action = $_GET['action'] ?? '';
        $id = intval($_GET['id'] ?? 0);

        // Handle delete session
        if ($action === 'delete_session' && isset($_GET['session_id'])) {
            $sid = intval($_GET['session_id']);
            if (check_admin_referer('delete_session_' . $sid)) {
                global $wpdb;
                $wpdb->delete($wpdb->prefix . 'bacera_workshop_slots', ['id' => $sid]);
                wp_redirect(admin_url("admin.php?page=bacera-workshops&id={$id}&tab=sessions&msg=deleted_session"));
                exit;
            }
        }

        echo '<div class="wks-wrap">';

        if (isset($_GET['saved'])) {
            echo '<div class="wks-notice-success">Đã lưu thay đổi thành công!</div>';
        }
        if (isset($_GET['msg']) && $_GET['msg'] === 'deleted_session') {
            echo '<div class="wks-notice-success">Đã xóa ca học thành công!</div>';
        }

        if ($id > 0) {
            $tab = sanitize_key($_GET['tab'] ?? 'info');
            $this->renderWorkshopHub($id, $tab);
        } else {
            $this->renderWorkshopBoard();
        }

        echo '</div>';
    }

    /* ════════════════════════════════════════════════════════════
       BOARD — Danh sách Workshop
    ════════════════════════════════════════════════════════════ */
    private function renderWorkshopBoard() {
        global $wpdb;
        $ts = $wpdb->prefix . 'bacera_workshop_slots';
        $tb = $wpdb->prefix . 'bacera_workshop_bookings';

        $workshops = get_posts([
            'post_type'      => 'workshop',
            'post_status'    => ['publish', 'draft'],
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);
        ?>
        <div class="wks-list-header">
            <div>
                <div class="wks-list-title">Danh sách Workshop</div>
                <div class="wks-list-sub">Quản lý tất cả Workshop — <?= count($workshops) ?> workshop</div>
            </div>
            <form method="POST" action="<?= esc_url(admin_url('admin-post.php')) ?>" style="display:inline">
                <?php wp_nonce_field('create_draft_workshop_nonce', 'workshop_nonce'); ?>
                <input type="hidden" name="action" value="create_draft_workshop">
                <button type="submit" class="wks-btn wks-btn-solid">
                    <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 2v10M2 7h10" stroke-linecap="round"/></svg>
                    Tạo Workshop
                </button>
            </form>
        </div>

        <div class="wks-filter-row">
            <div class="wks-search-wrap">
                <svg viewBox="0 0 16 16"><circle cx="7" cy="7" r="4"/><path d="M10.5 10.5l3 3" stroke-linecap="round"/></svg>
                <input class="wks-inp" type="text" id="wks-search" placeholder="Tìm Workshop..."/>
            </div>
            <select class="wks-sel" id="wks-status-filter">
                <option value="">Tất cả trạng thái</option>
                <option value="publish">Đang hoạt động</option>
                <option value="draft">Bản nháp</option>
            </select>
        </div>

        <?php if (empty($workshops)): ?>
        <div class="wks-empty">
            <svg viewBox="0 0 48 48"><rect x="8" y="12" width="32" height="28" rx="4"/><path d="M16 20h16M16 26h10M16 32h6"/></svg>
            <h3>Chưa có Workshop nào</h3>
            <p>Bắt đầu bằng cách tạo một workshop mới cho doanh nghiệp của bạn.</p>
            <form method="POST" action="<?= esc_url(admin_url('admin-post.php')) ?>">
                <?php wp_nonce_field('create_draft_workshop_nonce', 'workshop_nonce'); ?>
                <input type="hidden" name="action" value="create_draft_workshop">
                <button type="submit" class="wks-btn wks-btn-solid">Tạo Workshop đầu tiên</button>
            </form>
        </div>
        <?php else: ?>
        <div class="wks-grid" id="wks-grid">
            <?php foreach ($workshops as $p):
                $pid = $p->ID;
                $thumb = get_post_meta($pid, '_thumbnail_url', true) ?: get_the_post_thumbnail_url($pid) ?: '';
                $price = get_post_meta($pid, '_price', true) ?: 'Liên hệ';
                $trainer = get_post_meta($pid, '_trainer', true) ?: '';

                $total_seats  = (int)($wpdb->get_var($wpdb->prepare("SELECT SUM(total_seats) FROM $ts WHERE workshop_id=%d AND status!='cancelled'", $pid)) ?: 0);
                $booked_seats = (int)($wpdb->get_var($wpdb->prepare("SELECT SUM(booked_seats) FROM $ts WHERE workshop_id=%d AND status!='cancelled'", $pid)) ?: 0);
                $pending_pay  = (int)($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tb WHERE workshop_id=%d AND payment_status='unpaid' AND status!='cancelled'", $pid)) ?: 0);
                $next_slot    = $wpdb->get_row($wpdb->prepare("SELECT * FROM $ts WHERE workshop_id=%d AND slot_date >= CURDATE() AND status!='cancelled' ORDER BY slot_date ASC LIMIT 1", $pid), ARRAY_A);

                $pct = $total_seats > 0 ? min(100, round($booked_seats / $total_seats * 100)) : 0;
                $bar_color = $pct >= 100 ? '#9B2626' : ($pct >= 80 ? '#8A5C00' : '#1A6B4A');

                if ($p->post_status === 'publish') {
                    $badge_class = 'wks-bg-green';
                    $badge_text = 'Hoạt động';
                    if ($pct >= 100) { $badge_class = 'wks-bg-amber'; $badge_text = 'Hết chỗ'; }
                } else {
                    $badge_class = 'wks-bg-gray';
                    $badge_text = 'Bản nháp';
                }

                $hub_url = admin_url("admin.php?page=bacera-workshops&id={$pid}&tab=info");
            ?>
            <a href="<?= esc_url($hub_url) ?>" class="wks-card" data-title="<?= esc_attr(strtolower($p->post_title)) ?>" data-status="<?= esc_attr($p->post_status) ?>">
                <div class="wks-card-img">
                    <?php if ($thumb): ?>
                        <img src="<?= esc_url($thumb) ?>" alt="">
                    <?php else: ?>
                    <div class="wks-card-img-placeholder">
                        <svg viewBox="0 0 48 48"><circle cx="24" cy="24" r="16"/><path d="M16 32c0-8 16-8 16 0"/><circle cx="20" cy="22" r="2"/><circle cx="28" cy="22" r="2"/></svg>
                    </div>
                    <?php endif; ?>
                    <span class="wks-card-badge"><span class="wks-badge <?= $badge_class ?>"><?= $badge_text ?></span></span>
                </div>
                <div class="wks-card-body">
                    <div class="wks-card-title"><?= esc_html($p->post_title ?: 'Workshop Mới') ?></div>
                    <div class="wks-card-meta">
                        <?php if ($next_slot): ?>
                        <span>
                            <svg viewBox="0 0 16 16"><rect x="2" y="3" width="12" height="11" rx="1.5"/><path d="M5 1v4M11 1v4M2 8h12"/></svg>
                            <?= date('d/m/Y', strtotime($next_slot['slot_date'])) ?>
                        </span>
                        <span>
                            <svg viewBox="0 0 16 16"><circle cx="8" cy="8" r="6"/><path d="M8 5v3l2 2"/></svg>
                            <?= $next_slot['time_start'] ?> – <?= $next_slot['time_end'] ?>
                        </span>
                        <?php else: ?>
                        <span style="color:var(--text-3)">Chưa có lịch học</span>
                        <?php endif; ?>
                        <?php if ($price): ?>
                        <span>
                            <svg viewBox="0 0 16 16"><path d="M8 2v12M5 5h6a1.5 1.5 0 010 3H5.5a1.5 1.5 0 000 3H11"/></svg>
                            <?= esc_html($price) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="wks-card-foot">
                        <div class="wks-slot-info">
                            <strong><?= $booked_seats ?></strong> / <?= $total_seats ?> chỗ đã đặt
                            <div class="wks-mini-bar"><div class="wks-mini-fill" style="width:<?= $pct ?>%;background:<?= $bar_color ?>"></div></div>
                        </div>
                        <?php if ($pending_pay > 0): ?>
                        <span class="wks-badge wks-bg-amber"><?= $pending_pay ?> chờ TT</span>
                        <?php elseif ($pct >= 100): ?>
                        <span class="wks-badge wks-bg-red">Full</span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <script>
        (function(){
            const search = document.getElementById('wks-search');
            const sf     = document.getElementById('wks-status-filter');
            const cards  = document.querySelectorAll('.wks-card');
            function filter(){
                const q = search.value.toLowerCase();
                const s = sf.value;
                cards.forEach(c=>{
                    const titleMatch = c.dataset.title.includes(q);
                    const statusMatch = !s || c.dataset.status === s;
                    c.style.display = (titleMatch && statusMatch) ? '' : 'none';
                });
            }
            search.addEventListener('input', filter);
            sf.addEventListener('change', filter);
        })();
        </script>
        <?php
    }

    /* ════════════════════════════════════════════════════════════
       HUB — Chi tiết Workshop
    ════════════════════════════════════════════════════════════ */
    private function renderWorkshopHub($id, $active_tab) {
        $post = get_post($id);
        if (!$post || $post->post_type !== 'workshop') {
            echo '<div class="wks-notice-error">Không tìm thấy Workshop.</div>';
            return;
        }

        global $wpdb;
        $ts = $wpdb->prefix . 'bacera_workshop_slots';
        $tb = $wpdb->prefix . 'bacera_workshop_bookings';
        $tr = $wpdb->prefix . 'bacera_workshop_reviews';

        $total_seats    = (int)($wpdb->get_var($wpdb->prepare("SELECT SUM(total_seats) FROM $ts WHERE workshop_id=%d AND status!='cancelled'", $id)) ?: 0);
        $booked_seats   = (int)($wpdb->get_var($wpdb->prepare("SELECT SUM(booked_seats) FROM $ts WHERE workshop_id=%d AND status!='cancelled'", $id)) ?: 0);
        $paid_count     = (int)($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tb WHERE workshop_id=%d AND payment_status='paid' AND status!='cancelled'", $id)) ?: 0);
        $unpaid_count   = (int)($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tb WHERE workshop_id=%d AND payment_status='unpaid' AND status!='cancelled'", $id)) ?: 0);
        $slot_count     = (int)($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $ts WHERE workshop_id=%d AND status!='cancelled'", $id)) ?: 0);
        $booking_count  = (int)($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tb WHERE workshop_id=%d AND status!='cancelled'", $id)) ?: 0);
        $review_count   = $wpdb->get_var("SHOW TABLES LIKE '{$tr}'") === $tr
            ? (int)($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tr WHERE workshop_id=%d AND status='approved'", $id)) ?: 0) : 0;
        $avg_rating     = $wpdb->get_var("SHOW TABLES LIKE '{$tr}'") === $tr
            ? (float)($wpdb->get_var($wpdb->prepare("SELECT AVG(rating) FROM $tr WHERE workshop_id=%d AND status='approved'", $id)) ?: 0) : 0;

        $price          = get_post_meta($id, '_price', true) ?: 'Liên hệ';
        $trainer        = get_post_meta($id, '_trainer', true) ?: '—';
        $thumb          = get_post_meta($id, '_thumbnail_url', true) ?: get_the_post_thumbnail_url($id) ?: '';
        $pct            = $total_seats > 0 ? min(100, round($booked_seats / $total_seats * 100)) : 0;
        ?>

        <a href="?page=bacera-workshops" class="wks-back">
            <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 11L5 7l4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Danh sách Workshop
        </a>

        <div class="wks-page-header">
            <div>
                <div class="wks-title-row">
                    <h1 class="wks-page-title"><?= esc_html($post->post_title ?: 'Workshop Mới') ?></h1>
                    <?php
                    if ($post->post_status === 'publish'):
                        echo '<span class="wks-badge wks-bg-green">Hoạt động</span>';
                    else:
                        echo '<span class="wks-badge wks-bg-gray">Bản nháp</span>';
                    endif;
                    ?>
                </div>
                <div class="wks-page-meta">
                    ID: <strong>#<?= $id ?></strong><span>·</span>
                    Trainer: <?= esc_html($trainer) ?><span>·</span>
                    Giá: <?= esc_html($price) ?>
                </div>
            </div>
            <div class="wks-hdr-actions">
                <form method="POST" action="<?= esc_url(admin_url('admin-post.php')) ?>"
                    onsubmit="return confirm('Xóa Workshop này và toàn bộ ca học, đăng ký liên quan?')">
                    <?php wp_nonce_field('delete_workshop_nonce', 'workshop_nonce'); ?>
                    <input type="hidden" name="action" value="delete_workshop">
                    <input type="hidden" name="workshop_id" value="<?= $id ?>">
                    <button type="submit" class="wks-btn wks-btn-danger wks-btn-sm">Xóa Workshop</button>
                </form>
                <a href="<?= get_permalink($id) ?>" target="_blank" class="wks-btn wks-btn-outline wks-btn-sm">Xem trang</a>
            </div>
        </div>

        <!-- Metrics -->
        <div class="wks-metrics">
            <div class="wks-mc">
                <div class="wks-ml">Tổng slot</div>
                <div class="wks-mv"><?= $total_seats ?></div>
                <div class="wks-ms"><?= $slot_count ?> ca học</div>
            </div>
            <div class="wks-mc">
                <div class="wks-ml">Đã đặt</div>
                <div class="wks-mv" style="color:var(--green)"><?= $booked_seats ?></div>
                <div class="wks-sbar"><div class="wks-sfill" style="width:<?= $pct ?>%"></div></div>
                <div class="wks-ms"><?= $pct ?>% · còn <strong><?= max(0, $total_seats - $booked_seats) ?></strong> chỗ</div>
            </div>
            <div class="wks-mc">
                <div class="wks-ml">Đã thanh toán</div>
                <div class="wks-mv"><?= $paid_count ?></div>
                <?php if ($unpaid_count > 0): ?>
                <div class="wks-ms" style="color:var(--amber)"><?= $unpaid_count ?> chờ xác nhận</div>
                <?php else: ?>
                <div class="wks-ms">Không có chờ TT</div>
                <?php endif; ?>
            </div>
            <div class="wks-mc">
                <div class="wks-ml">Đánh giá</div>
                <div class="wks-mv" style="font-size:20px"><?= $avg_rating > 0 ? number_format($avg_rating, 1) . ' ★' : '—' ?></div>
                <div class="wks-ms"><?= $review_count ?> đánh giá</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="wks-tabs">
            <?php
            $tabs = [
                'info'     => 'Tổng quan',
                'sessions' => 'Ca học <span class="wks-tab-ct">' . $slot_count . '</span>',
                'bookings' => 'Học viên <span class="wks-tab-ct">' . $booking_count . '</span>',
                'reviews'  => 'Đánh giá <span class="wks-tab-ct">' . $review_count . '</span>',
            ];
            foreach ($tabs as $slug => $label):
                $cls = $active_tab === $slug ? 'active' : '';
            ?>
            <a href="?page=bacera-workshops&id=<?= $id ?>&tab=<?= $slug ?>" class="wks-tab <?= $cls ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>

        <?php
        if ($active_tab === 'info')     $this->renderTabInfo($post, $id);
        elseif ($active_tab === 'sessions') $this->renderTabSessions($id);
        elseif ($active_tab === 'bookings') $this->renderTabBookings($id);
        else                            $this->renderTabReviews($id);
    }

    /* ════════════════════════════════════════════════════════════
       TAB: TỔNG QUAN (Info + Overview)
    ════════════════════════════════════════════════════════════ */
    private function renderTabInfo($post, $wid) {
        $price        = get_post_meta($wid, '_price', true);
        $duration     = intval(get_post_meta($wid, '_duration', true) ?: 0);
        $trainer      = get_post_meta($wid, '_trainer', true);
        $meta_desc    = get_post_meta($wid, '_meta_description', true) ?: '';
        $thumb_url    = get_post_meta($wid, '_thumbnail_url', true) ?: get_the_post_thumbnail_url($wid) ?: '';
        $thumb_id     = get_post_meta($wid, '_thumbnail_id', true);

        // Overview fields
        $tagline    = get_post_meta($wid, '_tagline', true) ?: '';
        $short_desc = get_post_meta($wid, '_short_desc', true) ?: '';
        $includes   = json_decode(get_post_meta($wid, '_includes', true) ?: '[]', true) ?: [];
        $highlights = json_decode(get_post_meta($wid, '_highlights', true) ?: '[]', true) ?: [];

        // Format display price (raw stored as number string e.g. "350000")
        $price_display = $price ? number_format((float)preg_replace('/[^0-9.]/', '', $price), 0, '.', ',') : '';
        ?>


        <!-- ══════════════════════════════════════════════
             OVERVIEW TAB — Premium 2-column layout
        ══════════════════════════════════════════════ -->

        <!-- Sticky top action bar -->
        <div class="wks-ov-topbar">
            <div class="wks-ov-topbar-left">
                <div class="wks-ov-tbicon">
                    <svg viewBox="0 0 20 20" fill="none"><rect x="3" y="3" width="14" height="14" rx="2.5" stroke="currentColor" stroke-width="1.6"/><path d="M7 7h6M7 10h6M7 13h3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </div>
                <div>
                    <div class="wks-ov-tbname"><?= esc_html($post->post_title ?: 'Workshop chưa có tên') ?></div>
                    <div class="wks-ov-tbmeta">
                        <span class="wks-ov-status-dot <?= $post->post_status === 'publish' ? 'live' : 'draft' ?>"></span>
                        <?= $post->post_status === 'publish' ? 'Đang hoạt động' : 'Bản nháp' ?>
                        <?php if ($price): ?> · <strong><?= number_format((float)preg_replace('/[^0-9.]/','',$price),0,'.','.')?>đ</strong><?php endif; ?>
                        <?php if ($duration): ?> · <?= $duration ?> phút<?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="wks-ov-topbar-right">
                <a href="<?= get_permalink($wid) ?>" target="_blank" class="wks-btn wks-btn-outline wks-btn-sm">
                    <svg viewBox="0 0 14 14" fill="none" width="12" height="12"><path d="M6 2H2v10h10V8M8 2h4v4M12 2L6 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Xem trang
                </a>
                <button type="submit" form="wks-main-form" class="wks-btn wks-btn-solid wks-btn-sm" id="wks-save-main-btn">
                    <svg viewBox="0 0 14 14" fill="none" width="12" height="12"><path d="M1.5 7l4 4 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Lưu thay đổi
                </button>
            </div>
        </div>

        <form method="POST" action="<?= esc_url(admin_url('admin-post.php')) ?>" id="wks-main-form">
            <?php wp_nonce_field('save_workshop_general_nonce', 'workshop_nonce'); ?>
            <input type="hidden" name="action" value="save_workshop_general">
            <input type="hidden" name="workshop_id" value="<?= $wid ?>">

            <!-- 2-column main layout -->
            <div class="wks-ov-layout">

                <!-- ══ CỘT TRÁI — Nội dung chính ══ -->
                <div class="wks-ov-main">

                    <!-- Section 1: Thông tin cơ bản -->
                    <div class="wks-ov-card">
                        <div class="wks-ov-card-head">
                            <svg viewBox="0 0 16 16" fill="none"><circle cx="8" cy="6" r="2.5" stroke="currentColor" stroke-width="1.5"/><path d="M3 14c0-3 2.2-5 5-5s5 2 5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                            Thông tin cơ bản
                        </div>
                        <div class="wks-ov-card-body">

                            <!-- Tên Workshop -->
                            <div class="wks-ov-field">
                                <label class="wks-ov-label">Tên Workshop <span class="wks-req">*</span></label>
                                <input class="wks-ov-input" type="text" name="post_title"
                                    value="<?= esc_attr($post->post_title) ?>" required
                                    placeholder="VD: Workshop Vẽ Tranh Sơn Dầu">
                            </div>

                            <!-- Tagline -->
                            <div class="wks-ov-field">
                                <label class="wks-ov-label">Tagline <span class="wks-ov-badge-label">Hiển thị nổi bật</span></label>
                                <input class="wks-ov-input" type="text" name="_tagline" id="wks-e-tagline"
                                    value="<?= esc_attr($tagline) ?>"
                                    placeholder="VD: Workshop thực hành — nhỏ gọn, chuyên sâu">
                                <div class="wks-ov-hint">Câu mô tả ngắn hấp dẫn, xuất hiện ngay bên dưới tên trên trang đặt chỗ</div>
                            </div>

                            <!-- Mô tả phụ -->
                            <div class="wks-ov-field">
                                <label class="wks-ov-label">Mô tả giới thiệu <span class="wks-ov-badge-label">Sub description</span></label>
                                <textarea class="wks-ov-input wks-ov-textarea" name="_short_desc" id="wks-e-desc" rows="3"
                                    placeholder="Giới thiệu ngắn về workshop, không quá 2–3 câu..."><?= esc_textarea($short_desc) ?></textarea>
                            </div>

                            <!-- Meta Description -->
                            <div class="wks-ov-field">
                                <label class="wks-ov-label">Meta Description <span class="wks-ov-badge-seo">SEO</span></label>
                                <textarea class="wks-ov-input wks-ov-textarea" name="_meta_description" maxlength="160"
                                    placeholder="Mô tả ngắn hiển thị trên Google, tối đa 160 ký tự..." rows="2"
                                    id="wks-meta-desc"><?= esc_textarea($meta_desc) ?></textarea>
                                <div class="wks-ov-hint">
                                    <span id="wks-meta-count"><?= mb_strlen($meta_desc) ?></span>/160 ký tự
                                    <?php if (mb_strlen($meta_desc) > 155): ?><span style="color:#d97706"> — Nên giữ dưới 155</span><?php endif; ?>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Section 2: Nội dung chi tiết -->
                    <div class="wks-ov-card">
                        <div class="wks-ov-card-head">
                            <svg viewBox="0 0 16 16" fill="none"><rect x="2" y="2" width="12" height="12" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M5 6h6M5 9h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                            Nội dung chi tiết
                            <span class="wks-ov-card-sub">Nội dung hiển thị trên trang Workshop</span>
                        </div>
                        <div class="wks-ov-card-body">
                            <?php wp_editor($post->post_content, 'post_content', [
                                'textarea_name' => 'post_content',
                                'media_buttons' => false,
                                'textarea_rows' => 10,
                                'tinymce'       => ['toolbar1' => 'bold,italic,bullist,numlist,link,unlink,hr'],
                                'quicktags'     => false,
                            ]); ?>
                        </div>
                    </div>

                    <!-- Section 3: Bao gồm + Điểm nổi bật -->
                    <div class="wks-ov-card">
                        <div class="wks-ov-card-head">
                            <svg viewBox="0 0 16 16" fill="none"><path d="M2 8l4 4 8-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Quyền lợi &amp; Điểm nổi bật
                        </div>
                        <div class="wks-ov-card-body">
                            <div class="wks-ov-2col-list">

                                <!-- What's included -->
                                <div>
                                    <div class="wks-ov-list-head">
                                        <svg viewBox="0 0 14 14" fill="none"><path d="M2 7l3 3 7-7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        Học viên nhận được
                                    </div>
                                    <div class="wks-le" id="wks-le-includes">
                                        <?php foreach ($includes as $item): ?>
                                        <div class="wks-le-item">
                                            <input type="text" name="_includes[]" value="<?= esc_attr($item) ?>" placeholder="VD: Bộ dụng cụ vẽ cơ bản...">
                                            <button type="button" class="wks-ic-btn del" onclick="wksRemoveItem(this)" title="Xóa">
                                                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M2 2l10 10M12 2L2 12"/></svg>
                                            </button>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="wks-add-row" onclick="wksAddNamedItem('wks-le-includes','_includes[]','VD: Bộ dụng cụ vẽ...')">
                                        <svg viewBox="0 0 14 14"><path d="M7 2v10M2 7h10" stroke-linecap="round" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                                        Thêm mục
                                    </button>
                                </div>

                                <!-- Highlights -->
                                <div>
                                    <div class="wks-ov-list-head">
                                        <svg viewBox="0 0 14 14" fill="none"><path d="M7 1l1.8 3.6L13 5.3l-3 2.9.7 4.1L7 10.3l-3.7 2 .7-4.1-3-2.9 4.2-.7z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>
                                        Điểm nổi bật
                                    </div>
                                    <div class="wks-le" id="wks-le-highlights">
                                        <?php foreach ($highlights as $item): ?>
                                        <div class="wks-le-item">
                                            <input type="text" name="_highlights[]" value="<?= esc_attr($item) ?>" placeholder="VD: Học từ nghệ nhân thực thụ...">
                                            <button type="button" class="wks-ic-btn del" onclick="wksRemoveItem(this)" title="Xóa">
                                                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M2 2l10 10M12 2L2 12"/></svg>
                                            </button>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="wks-add-row" onclick="wksAddNamedItem('wks-le-highlights','_highlights[]','VD: Không cần kinh nghiệm...')">
                                        <svg viewBox="0 0 14 14"><path d="M7 2v10M2 7h10" stroke-linecap="round" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                                        Thêm điểm nổi bật
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>

                </div><!-- /main col -->

                <!-- ══ CỘT PHẢI — Sidebar cấu hình ══ -->
                <div class="wks-ov-sidebar">

                    <!-- Publish Settings -->
                    <div class="wks-ov-card">
                        <div class="wks-ov-card-head">
                            <svg viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="5.5" stroke="currentColor" stroke-width="1.5"/><path d="M8 5v3l2 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                            Xuất bản
                        </div>
                        <div class="wks-ov-card-body wks-ov-compact">

                            <div class="wks-ov-field">
                                <label class="wks-ov-label">Trạng thái</label>
                                <div class="wks-ov-status-select">
                                    <select class="wks-ov-input" name="post_status" id="wks-status-sel">
                                        <option value="publish" <?= selected($post->post_status, 'publish', false) ?>>🟢 Hoạt động (Public)</option>
                                        <option value="draft"   <?= selected($post->post_status, 'draft',   false) ?>>⚪ Bản nháp (Draft)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="wks-ov-field">
                                <label class="wks-ov-label">Học phí</label>
                                <div class="wks-ov-price-wrap">
                                    <input class="wks-ov-input" type="text" id="wks-price-display"
                                        value="<?= esc_attr($price_display) ?>"
                                        placeholder="350,000" inputmode="numeric">
                                    <span class="wks-ov-price-unit">đ</span>
                                </div>
                                <input type="hidden" name="_price" id="wks-price-raw" value="<?= esc_attr($price) ?>">
                            </div>

                            <div class="wks-ov-2col-sm">
                                <div class="wks-ov-field">
                                    <label class="wks-ov-label">Thời lượng</label>
                                    <div class="wks-ov-unit-wrap">
                                        <input class="wks-ov-input" type="number" name="_duration"
                                            value="<?= esc_attr($duration ?: '') ?>"
                                            min="0" step="5" placeholder="180">
                                        <span class="wks-ov-unit">phút</span>
                                    </div>
                                </div>
                                <div class="wks-ov-field">
                                    <label class="wks-ov-label">Giảng viên</label>
                                    <div class="wks-ov-unit-wrap">
                                        <input class="wks-ov-input" type="text" name="_trainer"
                                            value="<?= esc_attr($trainer) ?>"
                                            placeholder="Tên hoặc nghệ danh">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="wks-btn wks-btn-solid" style="width:100%;justify-content:center;margin-top:4px">
                                <svg viewBox="0 0 14 14" fill="none" width="13" height="13"><path d="M1.5 7l4 4 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                Lưu thay đổi
                            </button>
                        </div>
                    </div>

                    <!-- Ảnh đại diện -->
                    <div class="wks-ov-card">
                        <div class="wks-ov-card-head">
                            <svg viewBox="0 0 16 16" fill="none"><rect x="2" y="2" width="12" height="12" rx="2" stroke="currentColor" stroke-width="1.5"/><circle cx="6" cy="6" r="1.2" fill="currentColor"/><path d="M2 11l4-4 3 3 2-2 3 3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Ảnh đại diện
                        </div>
                        <div class="wks-ov-card-body">
                            <div class="wks-ov-thumb-wrap">
                                <?php if ($thumb_url): ?>
                                <img id="wks-thumb-preview" src="<?= esc_url($thumb_url) ?>" class="wks-ov-thumb-img">
                                <?php else: ?>
                                <div id="wks-thumb-preview" class="wks-ov-thumb-ph">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--text-3)" stroke-width="1.3" width="32" height="32"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                                    <span>Chưa có ảnh</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="wks-ov-thumb-actions">
                                <button type="button" id="wks-select-thumb" class="wks-btn wks-btn-outline wks-btn-sm" style="flex:1">
                                    <svg viewBox="0 0 14 14" fill="none" width="11" height="11"><rect x="2" y="2" width="10" height="10" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M5 7h4M7 5v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                    Chọn ảnh
                                </button>
                                <button type="button" id="wks-remove-thumb" class="wks-btn wks-btn-ghost wks-btn-sm" style="color:var(--red)<?= $thumb_id ? '' : ';display:none' ?>">Xóa</button>
                            </div>
                            <input type="hidden" name="_thumbnail_id"  id="wks-thumb-id"        value="<?= esc_attr($thumb_id) ?>">
                            <input type="hidden" name="_thumbnail_url" id="wks-thumb-url-input" value="<?= esc_attr($thumb_url) ?>">
                        </div>
                    </div>

                    <!-- Gallery -->
                    <?php $gallery_meta = json_decode(get_post_meta($wid, '_gallery', true) ?: '[]', true) ?: []; ?>
                    <div class="wks-ov-card">
                        <div class="wks-ov-card-head">
                            <svg viewBox="0 0 16 16" fill="none"><rect x="1" y="4" width="9" height="9" rx="1.5" stroke="currentColor" stroke-width="1.4"/><rect x="6" y="3" width="9" height="9" rx="1.5" stroke="currentColor" stroke-width="1.4" opacity=".5"/></svg>
                            Ảnh Gallery
                            <span class="wks-ov-card-ct" id="wks-gal-ct"><?= count($gallery_meta) ?></span>
                        </div>
                        <div class="wks-ov-card-body">
                            <div class="wks-gallery-grid" id="wks-gallery-grid">
                                <?php foreach ($gallery_meta as $g): ?>
                                <div class="wks-gal-item" data-id="<?= intval($g['id'] ?? 0) ?>">
                                    <img src="<?= esc_url($g['url'] ?? '') ?>" alt="">
                                    <button type="button" class="wks-gal-remove" onclick="wksGalRemove(this)" title="Xóa">
                                        <svg viewBox="0 0 12 12" fill="none"><path d="M1 1l10 10M11 1L1 11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                    </button>
                                    <input type="hidden" name="_gallery_ids[]"  value="<?= intval($g['id'] ?? 0) ?>">
                                    <input type="hidden" name="_gallery_urls[]" value="<?= esc_url($g['url'] ?? '') ?>">
                                </div>
                                <?php endforeach; ?>
                                <button type="button" class="wks-gal-add" id="wks-gal-add-btn" onclick="wksGalAdd()">
                                    <svg viewBox="0 0 20 20" fill="none" width="20" height="20"><rect x="3" y="3" width="14" height="14" rx="2" stroke="var(--text-3)" stroke-width="1.5"/><path d="M7 10h6M10 7v6" stroke="var(--text-3)" stroke-width="1.8" stroke-linecap="round"/></svg>
                                    <span>Thêm ảnh</span>
                                </button>
                            </div>
                            <div class="wks-ov-hint" style="margin-top:8px">Ảnh sẽ hiển thị trong carousel trên trang chi tiết Workshop</div>
                        </div>
                    </div>

                </div><!-- /sidebar -->

            </div><!-- /layout -->
        </form>


        <script>
        /* ── List item helpers ── */
        function wksRemoveItem(btn){ btn.closest('.wks-le-item').remove(); }
        function wksAddNamedItem(lid, fname, ph){
            const le=document.getElementById(lid);
            const div=document.createElement('div');
            div.className='wks-le-item';
            div.innerHTML='<input type="text" name="'+fname+'" placeholder="'+ph+'"><button type="button" class="wks-ic-btn del" onclick="wksRemoveItem(this)" title="Xóa"><svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M2 2l10 10M12 2L2 12"/></svg></button>';
            le.appendChild(div);
            div.querySelector('input').focus();
        }

        /* ── Price formatting ── */
        (function(){
            const disp=document.getElementById('wks-price-display');
            const raw=document.getElementById('wks-price-raw');
            if(!disp) return;
            function fmt(v){
                const n=parseFloat(v.replace(/[^0-9.]/g,''));
                if(isNaN(n)) return '';
                return n.toLocaleString('vi-VN');
            }
            disp.addEventListener('input',function(){
                const num=this.value.replace(/[^0-9]/g,'');
                if(num){ this.value=parseInt(num,10).toLocaleString('vi-VN'); raw.value=num; }
                else { raw.value=''; }
            });
            disp.addEventListener('blur',function(){
                this.value=fmt(this.value);
            });
            // Init display
            if(disp.value) disp.value=fmt(disp.value);
        })();

        /* ── Duration helper: show hours ── */
        (function(){
            const dur=document.querySelector('input[name="_duration"]');
            const hint=dur && dur.closest('.wks-ff') && dur.closest('.wks-ff').querySelector('.wks-fhint');
            if(!dur||!hint) return;
            dur.addEventListener('input',function(){
                const m=parseInt(this.value,10);
                if(m>0){
                    const h=Math.floor(m/60), r=m%60;
                    hint.textContent=h>0?(r>0?h+' giờ '+r+' phút':h+' giờ'):m+' phút';
                } else { hint.textContent='Nhập số phút, VD: 180 = 3 giờ'; }
            });
        })();

        /* ── Meta description counter ── */
        (function(){
            const ta=document.getElementById('wks-meta-desc');
            const ct=document.getElementById('wks-meta-count');
            if(!ta||!ct) return;
            ta.addEventListener('input',function(){ ct.textContent=this.value.length; });
        })();

        /* ── Thumbnail picker ── */
        (function(){
            let uploader;
            const selBtn=document.getElementById('wks-select-thumb');
            if(!selBtn) return;
            selBtn.addEventListener('click',function(){
                if(uploader){uploader.open();return;}
                uploader=wp.media({title:'Chọn ảnh đại diện',button:{text:'Dùng ảnh này'},multiple:false});
                uploader.on('select',function(){
                    const att=uploader.state().get('selection').first().toJSON();
                    const prev=document.getElementById('wks-thumb-preview');
                    if(prev.tagName==='IMG'){prev.src=att.url;}
                    else{prev.outerHTML='<img id="wks-thumb-preview" src="'+att.url+'" style="width:96px;height:72px;object-fit:cover;border-radius:var(--r);border:1px solid var(--border)">';}
                    document.getElementById('wks-thumb-id').value=att.id;
                    document.getElementById('wks-thumb-url-input').value=att.url;
                });
                uploader.open();
            });
            const removeBtn=document.getElementById('wks-remove-thumb');
            if(removeBtn) removeBtn.addEventListener('click',function(){
                const prev=document.getElementById('wks-thumb-preview');
                prev.outerHTML='<div id="wks-thumb-preview" style="width:96px;height:72px;border-radius:var(--r);border:2px dashed var(--border);background:var(--surface-2);display:flex;align-items:center;justify-content:center"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--text-3)" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg></div>';
                document.getElementById('wks-thumb-id').value='';
                document.getElementById('wks-thumb-url-input').value='';
                this.style.display='none';
            });

            /* ── Gallery multi-picker ── */
            var galUploader;
            window.wksGalAdd = function(){
                if(galUploader){galUploader.open();return;}
                galUploader = wp.media({
                    title: 'Chọn ảnh gallery',
                    button:{text:'Thêm vào Gallery'},
                    multiple: 'add'
                });
                galUploader.on('select', function(){
                    var grid = document.getElementById('wks-gallery-grid');
                    var addBtn = document.getElementById('wks-gal-add-btn');
                    galUploader.state().get('selection').each(function(att){
                        var data = att.toJSON();
                        var item = document.createElement('div');
                        item.className = 'wks-gal-item';
                        item.dataset.id = data.id;
                        item.innerHTML =
                            '<img src="'+data.url+'" alt="">'
                            +'<button type="button" class="wks-gal-remove" onclick="wksGalRemove(this)" title="Xóa">'
                            +'<svg viewBox="0 0 12 12" fill="none"><path d="M1 1l10 10M11 1L1 11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></button>'
                            +'<input type="hidden" name="_gallery_ids[]" value="'+data.id+'">'
                            +'<input type="hidden" name="_gallery_urls[]" value="'+data.url+'">';
                        grid.insertBefore(item, addBtn);
                    });
                });
                galUploader.open();
            };
            window.wksGalRemove = function(btn){
                btn.closest('.wks-gal-item').remove();
            };
        })();
        </script>
        <?php
    }

    /* ════════════════════════════════════════════════════════════
       TAB: CA HỌC (Sessions)
    ════════════════════════════════════════════════════════════ */
    private function renderTabSessions($wid) {
        global $wpdb;
        $ts = $wpdb->prefix . 'bacera_workshop_slots';
        $action = $_GET['session_action'] ?? 'list';
        $sid = intval($_GET['session_id'] ?? 0);

        if ($action === 'new' || ($action === 'edit' && $sid)) {
            $s = $sid ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $ts WHERE id=%d AND workshop_id=%d", $sid, $wid), ARRAY_A) : [];
            ?>
            <div class="wks-session-form">
                <div class="wks-session-form-title"><?= $sid ? "Cập nhật Ca học #{$sid}" : 'Thêm Ca học mới' ?></div>
                <form method="POST" action="<?= esc_url(admin_url('admin-post.php')) ?>">
                    <?php wp_nonce_field('save_session_nonce', 'session_nonce'); ?>
                    <input type="hidden" name="action" value="save_session">
                    <input type="hidden" name="workshop_id" value="<?= $wid ?>">
                    <input type="hidden" name="session_id" value="<?= $sid ?>">
<?php
                    $slot_date_val = $s['slot_date'] ?? date('Y-m-d', strtotime('+1 day'));
                    $ts_val = $s['time_start'] ?? '09:00';
                    $te_val = $s['time_end']   ?? '12:00';
                    $rs_d   = ($s && !empty($s['reg_start'])) ? date('Y-m-d', strtotime($s['reg_start'])) : '';
                    $re_d   = ($s && !empty($s['reg_end']))   ? date('Y-m-d', strtotime($s['reg_end']))   : '';
                    $rs_t   = ($s && !empty($s['reg_start'])) ? date('H:i',   strtotime($s['reg_start'])) : '08:00';
                    $re_t   = ($s && !empty($s['reg_end']))   ? date('H:i',   strtotime($s['reg_end']))   : '23:55';
                    $sp     = $s['price'] ?? '';
                    $spn    = (int) preg_replace('/[^0-9]/', '', $sp);
                    $cur_status = $s['status'] ?? 'open';

                    // Smart default flags for registration window
                    $is_immediate_open = empty($rs_d);
                    $is_close_on_start = empty($re_d);

                    // Workshop base price for placeholder
                    $ws_price = get_post_meta($wid, '_price', true);
                    $ws_price_fmt = $ws_price ? number_format((int)preg_replace('/[^0-9]/', '', $ws_price), 0, '.', '.') . 'đ' : null;
                    ?>

                    <!-- ══ FORM HEADER (sticky) ══ -->
                    <div class="wsf-header">
                        <div class="wsf-header-info">
                            <div class="wsf-header-icon">
                                <svg viewBox="0 0 20 20" fill="none"><rect x="3" y="3" width="14" height="14" rx="2"/><path d="M7 1v4M13 1v4M3 8h14" stroke-linecap="round"/></svg>
                            </div>
                            <div>
                                <div class="wsf-header-title"><?= $sid ? "Cập nhật Ca học #{$sid}" : 'Thêm Ca học mới' ?></div>
                                <div class="wsf-header-sub">Thiết lập lịch trình và cấu hình chỗ ngồi</div>
                            </div>
                        </div>
                        <div class="wsf-header-actions">
                            <a href="?page=bacera-workshops&id=<?= $wid ?>&tab=sessions" class="wsf-btn wsf-btn-cancel">Hủy</a>
                            <button type="submit" class="wsf-btn wsf-btn-save">
                                <svg viewBox="0 0 16 16" fill="none"><path d="M2.5 8.5l4 4 7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                                Lưu thay đổi
                            </button>
                        </div>
                    </div>

                    <!-- ══ 2-COLUMN BODY ══ -->
                    <div class="wsf-body">

                        <!-- CỘT TRÁI: Thời gian & Đăng ký -->
                        <div class="wsf-col-left">

                            <!-- Lịch trình ca học -->
                            <section class="wsf-section">
                                <h3 class="wsf-section-title">
                                    <svg viewBox="0 0 16 16" fill="none"><rect x="2" y="2" width="12" height="12" rx="2"/><path d="M5 0v4M11 0v4M2 7h12" stroke-linecap="round"/></svg>
                                    Lịch trình ca học
                                </h3>
                                <div class="wsf-fields">

                                    <!-- Ngày -->
                                    <div class="wsf-field">
                                        <label class="wsf-label">Ngày diễn ra <span class="wsf-req">*</span></label>
                                        <div class="wsf-input-icon">
                                            <svg class="wsf-ico" viewBox="0 0 20 20" fill="none"><rect x="3" y="3" width="14" height="14" rx="2"/><path d="M7 1v4M13 1v4M3 8h14" stroke-linecap="round"/></svg>
                                            <input type="date" class="wsf-input" name="slot_date"
                                                   value="<?= esc_attr($slot_date_val) ?>" required>
                                        </div>
                                    </div>

                                    <!-- Khung giờ -->
                                    <div class="wsf-field">
                                        <label class="wsf-label">Khung giờ <span class="wsf-req">*</span></label>
                                        <div class="wsf-time-range">
                                            <div class="wsf-input-icon">
                                                <svg class="wsf-ico" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="7"/><path d="M10 6v4l3 2" stroke-linecap="round"/></svg>
                                                <input type="time" class="wsf-input wsf-input-time" name="time_start"
                                                       value="<?= esc_attr($ts_val) ?>" required>
                                            </div>
                                            <svg class="wsf-arrow" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M10 5l3 3-3 3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            <div class="wsf-input-icon">
                                                <svg class="wsf-ico" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="7"/><path d="M10 6v4l3 2" stroke-linecap="round"/></svg>
                                                <input type="time" class="wsf-input wsf-input-time" name="time_end"
                                                       value="<?= esc_attr($te_val) ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Quy tắc đăng ký -->
                            <section class="wsf-section">
                                <h3 class="wsf-section-title">
                                    <svg viewBox="0 0 16 16" fill="none"><path d="M8 2a6 6 0 100 12A6 6 0 008 2zM8 5v3l2 2" stroke-linecap="round"/></svg>
                                    Quy tắc nhận đăng ký
                                </h3>
                                <div class="wsf-fields">

                                    <!-- Mở đăng ký -->
                                    <div class="wsf-toggle-card" id="wstc-open">
                                        <div class="wsf-toggle-head" onclick="wsfToggle('open')">
                                            <div>
                                                <div class="wsf-toggle-label">Mở đăng ký ngay lập tức</div>
                                                <div class="wsf-toggle-desc">Học viên có thể đặt chỗ ngay khi lưu ca học này</div>
                                            </div>
                                            <button type="button" id="wsf-sw-open" class="wsf-switch <?= $is_immediate_open ? 'on' : '' ?>" onclick="event.stopPropagation();wsfToggle('open')">
                                                <span class="wsf-switch-knob"></span>
                                            </button>
                                        </div>
                                        <div class="wsf-toggle-body" id="wstb-open" style="<?= $is_immediate_open ? 'display:none' : '' ?>">
                                            <div class="wsf-time-range" style="padding-top:14px;border-top:1px solid var(--border)">
                                                <input type="date" class="wsf-input wsf-input-sm" id="ws-rs-date"
                                                       value="<?= esc_attr($rs_d) ?>">
                                                <input type="time" class="wsf-input wsf-input-sm wsf-input-time" id="ws-rs-time"
                                                       value="<?= esc_attr($rs_t) ?>">
                                            </div>
                                        </div>
                                        <input type="hidden" name="reg_start" id="reg_start_combined">
                                    </div>

                                    <!-- Đóng đăng ký -->
                                    <div class="wsf-toggle-card" id="wstc-close">
                                        <div class="wsf-toggle-head" onclick="wsfToggle('close')">
                                            <div>
                                                <div class="wsf-toggle-label">Đóng khi ca học bắt đầu</div>
                                                <div class="wsf-toggle-desc">Tự động chốt danh sách khi đến giờ sự kiện</div>
                                            </div>
                                            <button type="button" id="wsf-sw-close" class="wsf-switch <?= $is_close_on_start ? 'on' : '' ?>" onclick="event.stopPropagation();wsfToggle('close')">
                                                <span class="wsf-switch-knob"></span>
                                            </button>
                                        </div>
                                        <div class="wsf-toggle-body" id="wstb-close" style="<?= $is_close_on_start ? 'display:none' : '' ?>">
                                            <div class="wsf-time-range" style="padding-top:14px;border-top:1px solid var(--border)">
                                                <input type="date" class="wsf-input wsf-input-sm" id="ws-re-date"
                                                       value="<?= esc_attr($re_d) ?>">
                                                <input type="time" class="wsf-input wsf-input-sm wsf-input-time" id="ws-re-time"
                                                       value="<?= esc_attr($re_t) ?>">
                                            </div>
                                        </div>
                                        <input type="hidden" name="reg_end" id="reg_end_combined">
                                    </div>

                                </div>
                            </section>

                        </div><!-- /left -->

                        <!-- CỘT PHẢI: Cấu hình vé -->
                        <div class="wsf-col-right">
                            <div class="wsf-ticket-panel">
                                <h3 class="wsf-section-title">
                                    <svg viewBox="0 0 16 16" fill="none"><path d="M2 10a1 1 0 000 2h.5a1.5 1.5 0 100-3H2zM14 6a1 1 0 000-2h-.5a1.5 1.5 0 100 3H14M2 6h12M2 10h12M4 2v12M12 2v12" stroke-linecap="round"/></svg>
                                    Cấu hình vé
                                </h3>

                                <!-- Trạng thái - Custom dropdown -->
                                <div class="wsf-field">
                                    <label class="wsf-label">
                                        <svg viewBox="0 0 14 14" fill="none" width="12" height="12"><circle cx="7" cy="7" r="5"/><path d="M7 4v3l2 2" stroke-linecap="round"/></svg>
                                        Trạng thái ca học
                                    </label>
                                    <div class="wsf-status-wrap" id="wsf-status-wrap">
                                        <button type="button" class="wsf-status-btn" id="wsf-status-btn" onclick="wsfStatusToggle()">
                                            <span class="wsf-status-dot wsf-status-dot-<?= $cur_status ?>"></span>
                                            <span id="wsf-status-label"><?php
                                                echo $cur_status === 'open' ? 'Đang mở nhận khách' :
                                                    ($cur_status === 'full' ? 'Đã hết chỗ (Full)' : 'Đã hủy bỏ');
                                            ?></span>
                                            <svg class="wsf-status-chevron" viewBox="0 0 16 16" fill="none"><path d="M4 6l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </button>
                                        <div class="wsf-status-menu" id="wsf-status-menu">
                                            <div class="wsf-status-opt" onclick="wsfStatusPick('open','Đang mở nhận khách')">
                                                <svg viewBox="0 0 14 14" fill="none" width="14" height="14"><circle cx="7" cy="7" r="5" fill="#10b981" opacity=".2"/><circle cx="7" cy="7" r="3" fill="#10b981"/></svg>
                                                <span>Đang mở nhận khách</span>
                                            </div>
                                            <div class="wsf-status-opt" onclick="wsfStatusPick('full','Đã hết chỗ (Full)')">
                                                <svg viewBox="0 0 14 14" fill="none" width="14" height="14"><circle cx="7" cy="7" r="5" fill="#ef4444" opacity=".2"/><circle cx="7" cy="7" r="3" fill="#ef4444"/></svg>
                                                <span>Đã hết chỗ (Full)</span>
                                            </div>
                                            <div class="wsf-status-opt" onclick="wsfStatusPick('cancelled','Đã hủy bỏ')">
                                                <svg viewBox="0 0 14 14" fill="none" width="14" height="14"><circle cx="7" cy="7" r="5" fill="#a8a29e" opacity=".2"/><circle cx="7" cy="7" r="3" fill="#a8a29e"/></svg>
                                                <span>Đã hủy bỏ</span>
                                            </div>
                                        </div>
                                        <input type="hidden" name="status" id="wsf-status-val" value="<?= esc_attr($cur_status) ?>">
                                    </div>
                                </div>

                                <!-- Số chỗ -->
                                <div class="wsf-field">
                                    <label class="wsf-label">
                                        <svg viewBox="0 0 14 14" fill="none" width="12" height="12"><circle cx="5" cy="4" r="2"/><circle cx="9" cy="4" r="2"/><path d="M1 11c0-2.2 1.8-4 4-4m4 0c2.2 0 4 1.8 4 4" stroke-linecap="round"/></svg>
                                        Giới hạn chỗ ngồi <span class="wsf-req">*</span>
                                    </label>
                                    <input type="number" class="wsf-input wsf-input-lg" name="total_seats"
                                           value="<?= intval($s['total_seats'] ?? 16) ?>" min="1" required>
                                </div>

                                <!-- Giá riêng -->
                                <div class="wsf-field">
                                    <label class="wsf-label">
                                        <svg viewBox="0 0 14 14" fill="none" width="12" height="12"><path d="M7 1v12M4 4c0-1.1.9-2 2-2h2.5a1.5 1.5 0 010 3H5.5a1.5 1.5 0 000 3H9a2 2 0 010 4H6.5A1.5 1.5 0 015 12" stroke-linecap="round"/></svg>
                                        Giá riêng cho ca này
                                    </label>
                                    <div class="wsf-price-wrap">
                                        <input type="text" class="wsf-input" id="wks-slot-price-disp"
                                               autocomplete="off"
                                               placeholder="<?= $ws_price_fmt ? "Mặc định: {$ws_price_fmt}" : 'Mặc định: giá WS' ?>"
                                               value="<?= $spn > 0 ? number_format($spn, 0, '.', '.') : '' ?>">
                                        <span class="wsf-price-d">đ</span>
                                    </div>
                                    <input type="hidden" name="price" id="wks-slot-price-raw"
                                           value="<?= esc_attr($sp) ?>">
                                    <p class="wsf-hint">Chỉ nhập nếu ca này có phụ thu hoặc giảm giá. Bỏ trống → dùng giá gốc Workshop.</p>
                                </div>

                            </div>
                        <!-- ══ DANH SÁCH HỌC VIÊN CỦA CA HỌC ══ -->
                        <?php if ($sid && !empty($s)):
                            $tb_r = $wpdb->prefix . 'bacera_workshop_bookings';
                            $tc_r = $wpdb->prefix . 'bacera_customers';
                            $slot_bks = $wpdb->get_results($wpdb->prepare("
                                SELECT b.*,
                                       COALESCE(NULLIF(c.name,''), NULLIF(b.customer_name,''), b.phone, b.email, 'Khách') AS display_name,
                                       COALESCE(NULLIF(c.phone,''), b.phone) AS display_phone
                                FROM {$tb_r} b
                                LEFT JOIN {$tc_r} c ON c.id = b.customer_id
                                WHERE b.slot_id = %d AND b.status != 'cancelled'
                                ORDER BY b.created_at ASC
                            ", $sid), ARRAY_A);
                            $av_cls = [['#E6EFF8','#1A4A7A'],['#FFF3D6','#8A5C00'],['#F0F4EC','#2E5C1A'],['#F5EEF8','#6A1A7A'],['#FDEAED','#7A1A2A']];
                        ?>
                        <div class="wsf-roster-panel">
                            <h3 class="wsf-section-title" style="margin-bottom:12px">
                                <svg viewBox="0 0 16 16" fill="none"><circle cx="6" cy="5" r="2.5"/><circle cx="10" cy="5" r="2.5"/><path d="M1 14c0-3 2.2-5 5-5m4 0c2.8 0 5 2 5 5" stroke-linecap="round"/></svg>
                                Học viên đã đăng ký
                                <span class="wsf-roster-ct"><?= count($slot_bks ?? []) ?></span>
                            </h3>

                            <?php if(empty($slot_bks)): ?>
                            <div class="wsf-roster-empty">Chưa có học viên nào đăng ký ca này.</div>
                            <?php else: ?>
                            <div class="wsf-roster-list">
                                <?php foreach($slot_bks as $i => $bk):
                                    $c = $av_cls[$i % 5];
                                    $dn   = $bk['display_name'] ?? ($bk['customer_name'] ?? '?');
                                    $init = mb_strtoupper(mb_substr($dn, 0, 1));
                                    $paid = ($bk['payment_status'] ?? '') === 'paid';
                                    $checked = !empty($bk['checked_in_at']);
                                    $ci_nonce = wp_create_nonce('wks_checkin_' . (int)($bk['id'] ?? 0));
                                ?>
                                <div class="wsf-roster-row" id="brow-<?= (int)($bk['id'] ?? 0) ?>">
                                    <span class="wks-av" style="background:<?= $c[0] ?>;color:<?= $c[1] ?>"><?= $init ?></span>
                                    <div class="wsf-rl-info">
                                        <div class="wsf-rl-name"><?= esc_html($dn) ?></div>
                                        <div class="wsf-rl-meta"><?= esc_html($bk['display_phone'] ?? $bk['phone'] ?? '') ?><?= !empty($bk['email']) ? ' · ' . esc_html($bk['email']) : '' ?></div>
                                    </div>
                                    <div class="wsf-rl-badges">
                                        <?php if ($paid): ?>
                                        <span class="wks-badge wks-bg-green" style="font-size:10px">Đã TT</span>
                                        <?php else: ?>
                                        <span class="wks-badge wks-bg-amber" style="font-size:10px">Chưa TT</span>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button"
                                            class="wks-ci-btn <?= $checked ? 'checked' : '' ?>"
                                            title="<?= $checked ? 'Điểm danh: ' . date('H:i d/m', strtotime($bk['checked_in_at'])) : 'Chưa điểm danh — click để xác nhận' ?>"
                                            onclick="wksCheckin(<?= (int)($bk['id'] ?? 0) ?>, '<?= $ci_nonce ?>', this)">
                                        <svg viewBox="0 0 16 16" fill="none">
                                            <path d="M2 8l4 4 8-8" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                                        </svg>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <a href="?page=bacera-workshops&id=<?= $wid ?>&tab=bookings&slot_id=<?= $sid ?>"
                               class="wsf-roster-all-link">
                                Quản lý chi tiết →
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        </div><!-- /right -->

                    </div><!-- /body -->
                </form>
            </div>
            <script>
            (function(){
                /* ── Toggle đăng ký ── */
                function wsfToggle(type) {
                    var sw = document.getElementById('wsf-sw-' + type);
                    var body = document.getElementById('wstb-' + type);
                    if (!sw || !body) return;
                    var isOn = sw.classList.contains('on');
                    if (isOn) {
                        sw.classList.remove('on');
                        body.style.display = 'block';
                    } else {
                        sw.classList.add('on');
                        body.style.display = 'none';
                        // Clear hidden field
                        if (type === 'open')  document.getElementById('reg_start_combined').value = '';
                        if (type === 'close') document.getElementById('reg_end_combined').value = '';
                    }
                    assembleReg();
                }
                window.wsfToggle = wsfToggle;

                /* ── Assemble reg_start / reg_end ── */
                function assembleReg() {
                    // reg_start
                    var openSw = document.getElementById('wsf-sw-open');
                    var cStart = document.getElementById('reg_start_combined');
                    if (cStart) {
                        if (openSw && openSw.classList.contains('on')) {
                            cStart.value = '';
                        } else {
                            var d = (document.getElementById('ws-rs-date') || {}).value || '';
                            var t = (document.getElementById('ws-rs-time') || {}).value || '08:00';
                            cStart.value = d ? d + 'T' + t : '';
                        }
                    }
                    // reg_end
                    var closeSw = document.getElementById('wsf-sw-close');
                    var cEnd = document.getElementById('reg_end_combined');
                    if (cEnd) {
                        if (closeSw && closeSw.classList.contains('on')) {
                            cEnd.value = '';
                        } else {
                            var d2 = (document.getElementById('ws-re-date') || {}).value || '';
                            var t2 = (document.getElementById('ws-re-time') || {}).value || '23:55';
                            cEnd.value = d2 ? d2 + 'T' + t2 : '';
                        }
                    }
                }
                ['ws-rs-date','ws-rs-time','ws-re-date','ws-re-time'].forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el) el.addEventListener('change', assembleReg);
                });
                assembleReg();

                /* ── Custom status dropdown ── */
                window.wsfStatusToggle = function() {
                    var menu = document.getElementById('wsf-status-menu');
                    var btn  = document.getElementById('wsf-status-btn');
                    if (!menu) return;
                    var open = menu.classList.contains('open');
                    menu.classList.toggle('open');
                    if (btn) btn.classList.toggle('open');
                };
                window.wsfStatusPick = function(val, label) {
                    document.getElementById('wsf-status-val').value = val;
                    var lbl = document.getElementById('wsf-status-label');
                    if (lbl) lbl.textContent = label;
                    // Update dot color
                    var dot = document.querySelector('#wsf-status-btn .wsf-status-dot');
                    if (dot) dot.className = 'wsf-status-dot wsf-status-dot-' + val;
                    // Close menu
                    document.getElementById('wsf-status-menu').classList.remove('open');
                    document.getElementById('wsf-status-btn').classList.remove('open');
                };
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('#wsf-status-wrap')) {
                        var m = document.getElementById('wsf-status-menu');
                        var b = document.getElementById('wsf-status-btn');
                        if (m) m.classList.remove('open');
                        if (b) b.classList.remove('open');
                    }
                });

                /* ── Price formatting ── */
                var pDisp = document.getElementById('wks-slot-price-disp');
                var pRaw  = document.getElementById('wks-slot-price-raw');
                if (pDisp && pRaw) {
                    pDisp.addEventListener('input', function() {
                        var n = this.value.replace(/[^0-9]/g, '');
                        pRaw.value = n;
                        this.value = n ? parseInt(n, 10).toLocaleString('vi-VN') : '';
                    });
                    pDisp.addEventListener('blur', function() {
                        var n = pRaw.value;
                        this.value = n ? parseInt(n, 10).toLocaleString('vi-VN') : '';
                    });
                }
            })();
            </script>
            <?php
            return;

        }

        $tb = $wpdb->prefix . 'bacera_workshop_bookings';
        $tc = $wpdb->prefix . 'bacera_customers';
        $sessions = $wpdb->get_results($wpdb->prepare("SELECT * FROM $ts WHERE workshop_id=%d ORDER BY slot_date ASC, time_start ASC", $wid), ARRAY_A);
        // Pre-load bookings keyed by slot_id — JOIN customers for real name
        $all_bookings = [];
        if (!empty($sessions)) {
            $slot_ids = implode(',', array_map('intval', array_column($sessions, 'id')));
            $rows = $wpdb->get_results("
                SELECT b.*,
                       COALESCE(NULLIF(c.name,''), NULLIF(b.customer_name,''), b.phone, b.email, 'Khách') AS display_name,
                       COALESCE(NULLIF(c.phone,''), b.phone) AS display_phone
                FROM {$tb} b
                LEFT JOIN {$tc} c ON c.id = b.customer_id
                WHERE b.slot_id IN ({$slot_ids}) AND b.status != 'cancelled'
                ORDER BY b.created_at ASC
            ", ARRAY_A);
            foreach ($rows as $r) { $all_bookings[(int)$r['slot_id']][] = $r; }
        }
        ?>

        <div style="margin-bottom:14px">
            <a href="?page=bacera-workshops&id=<?= $wid ?>&tab=sessions&session_action=new" class="wks-btn wks-btn-solid">
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 2v10M2 7h10" stroke-linecap="round"/></svg>
                Thêm Ca học
            </a>
        </div>

        <?php if (empty($sessions)): ?>
        <div class="wks-empty">
            <svg viewBox="0 0 48 48"><rect x="8" y="10" width="32" height="30" rx="3"/><path d="M16 6v8M32 6v8M8 22h32"/></svg>
            <h3>Chưa có ca học nào</h3>
            <p>Thêm ca học để học viên có thể đăng ký.</p>
        </div>
        <?php else: ?>
        <div class="wks-sessions-grid">
        <?php foreach ($sessions as $s):
            // Sum num_seats across bookings (1 booking can book multiple seats)
            $slot_id_key = (int)($s['id'] ?? 0);
            $slot_bks_arr = $all_bookings[$slot_id_key] ?? [];
            $booked = array_sum(array_column($slot_bks_arr, 'num_seats'));
            $total  = (int)($s['total_seats'] ?? 0);
            $avail  = max(0, $total - $booked);
            $pct    = $total > 0 ? min(100, round($booked / $total * 100)) : 0;
            $bar_color    = $pct >= 100 ? 'var(--red)' : ($pct >= 80 ? '#d97706' : 'var(--green)');
            $accent_color = $pct >= 100 ? 'var(--red)' : ($pct >= 80 ? '#d97706' : 'var(--green)');

            $status_val = $s['status'] ?? 'open';
            if ($status_val === 'open')      { $bc = 'wks-bg-green'; $bt = 'Đang mở'; }
            elseif ($status_val === 'full')  { $bc = 'wks-bg-amber'; $bt = 'Hết chỗ'; }
            else                             { $bc = 'wks-bg-gray';  $bt = 'Đã hủy'; }

            // Format date (guard against empty slot_date)
            $slot_date_str = $s['slot_date'] ?? '';
            try {
                $dt  = $slot_date_str ? new \DateTime($slot_date_str) : null;
            } catch (\Exception $e) { $dt = null; }
            $dow          = $dt ? ['Chủ nhật','Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7'][(int)$dt->format('w')] : '';
            $date_display = $dt ? $dt->format('d/m/Y') : '—';

            // Format price
            $price_raw  = preg_replace('/[^0-9]/', '', $s['price'] ?? '');
            $price_disp = $price_raw ? number_format((int)$price_raw, 0, '.', '.') . 'đ' : null;

            // Format reg window
            $reg_open  = !empty($s['reg_start']) ? (new \DateTime($s['reg_start']))->format('d/m H:i') : null;
            $reg_close = !empty($s['reg_end'])   ? (new \DateTime($s['reg_end']))->format('d/m H:i')   : null;

            $s_id     = (int)($s['id'] ?? 0);
            $edit_url = admin_url("admin.php?page=bacera-workshops&id={$wid}&tab=sessions&session_action=edit&session_id={$s_id}");
            $del_url  = wp_nonce_url(admin_url("admin.php?page=bacera-workshops&id={$wid}&tab=sessions&action=delete_session&session_id={$s_id}"), 'delete_session_' . $s_id);
        ?>
        <div class="wks-slot-card">
            <!-- Accent bar -->
            <div class="wks-slot-card-accent" style="background:<?= $accent_color ?>"></div>

            <div class="wks-slot-card-body">
                <!-- Head: date + status badge -->
                <div class="wks-slot-card-head">
                    <div class="wks-slot-date-block">
                        <span class="wks-slot-dow"><?= $dow ?></span>
                        <span class="wks-slot-date"><?= $date_display ?></span>
                    </div>
                    <div class="wks-slot-badges">
                        <span class="wks-badge <?= $bc ?>"><?= $bt ?></span>
                        <?php if ($price_disp): ?>
                        <span class="wks-badge wks-bg-blue"><?= $price_disp ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Time row -->
                <div class="wks-slot-meta-row">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3" stroke-linecap="round"/></svg>
                    <span><?= esc_html($s['time_start'] ?? '') ?> – <?= esc_html($s['time_end'] ?? '') ?></span>
                </div>

                <!-- Reg window (if set) -->
                <?php if ($reg_open || $reg_close): ?>
                <div class="wks-slot-meta-row">
                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18" stroke-linecap="round"/></svg>
                    <span>ĐK: <strong><?= $reg_open ?? 'Ngay' ?></strong> – <strong><?= $reg_close ?? 'Hết lịch' ?></strong></span>
                </div>
                <?php endif; ?>

                <!-- Capacity bar -->
                <div class="wks-slot-cap-wrap">
                    <div class="wks-slot-cap-row">
                        <span>Chỗ đặt</span>
                        <span class="wks-slot-cap-num" style="<?= $pct >= 100 ? 'color:var(--red)' : '' ?>">
                            <?= $booked ?>/<?= $total ?>
                            <span style="font-weight:400;color:var(--text-3);font-size:10px">(còn <?= $avail ?>)</span>
                        </span>
                    </div>
                    <div class="wks-slot-bar">
                        <div class="wks-slot-fill" style="width:<?= $pct ?>%;background:<?= $bar_color ?>"></div>
                    </div>
                </div>
            </div>

            <?php
            $slot_bookings = $slot_bks_arr; // already computed above with num_seats
            $roster_id = 'roster-' . $s_id;
            $booked_seats_display = $booked; // SUM(num_seats)
            $booking_count = count($slot_bookings);
            ?>

            <!-- ── Học viên inline ── -->
            <?php if (!empty($slot_bookings)): ?>
            <div class="wks-roster" id="<?= $roster_id ?>">
                <button type="button" class="wks-roster-toggle" onclick="wksRosterToggle('<?= $roster_id ?>')">
                    <span class="wks-roster-toggle-left">
                        <!-- Avatar stack -->
                        <span class="wks-av-stack">
                        <?php foreach (array_slice($slot_bookings, 0, 4) as $i => $bk):
                            $colors = [['#E6EFF8','#1A4A7A'],['#FFF3D6','#8A5C00'],['#F0F4EC','#2E5C1A'],['#F5EEF8','#6A1A7A']];
                            $c = $colors[$i % 4];
                            $init = mb_strtoupper(mb_substr($bk['display_name'] ?? $bk['customer_name'] ?? '?', 0, 1));
                        ?>
                        <span class="wks-av-mini" style="background:<?= $c[0] ?>;color:<?= $c[1] ?>;z-index:<?= 10 - $i ?>"><?= $init ?></span>
                        <?php endforeach; ?>
                        </span>
                        <span class="wks-roster-count">
                            <?= $booked_seats_display ?> ghế
                            <?php if ($booking_count !== $booked_seats_display): ?>
                            <span style="font-weight:400;color:var(--text-3)">(<?= $booking_count ?> đơn)</span>
                            <?php endif; ?>
                        </span>
                    </span>
                    <svg class="wks-roster-chevron" id="<?= $roster_id ?>-ico" viewBox="0 0 16 16" fill="none">
                        <path d="M4 6l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                <div class="wks-roster-body" id="<?= $roster_id ?>-body" style="display:none">
                    <?php $av_colors = [['#E6EFF8','#1A4A7A'],['#FFF3D6','#8A5C00'],['#F0F4EC','#2E5C1A'],['#F5EEF8','#6A1A7A'],['#FDEAED','#7A1A2A']];
                    foreach ($slot_bookings as $i => $bk):
                        $c = $av_colors[$i % 5];
                        $dn_c   = $bk['display_name'] ?? ($bk['customer_name'] ?? '?');
                        $init = mb_strtoupper(mb_substr($dn_c, 0, 1));
                        $paid = ($bk['payment_status'] ?? '') === 'paid';
                        $checked = !empty($bk['checked_in_at']);
                        $ci_nonce = wp_create_nonce('wks_checkin_' . (int)($bk['id'] ?? 0));
                    ?>
                    <div class="wks-roster-row" id="brow-<?= (int)($bk['id'] ?? 0) ?>">
                        <span class="wks-av" style="background:<?= $c[0] ?>;color:<?= $c[1] ?>"><?= $init ?></span>
                        <div class="wks-roster-info">
                            <div class="wks-roster-name"><?= esc_html($dn_c) ?></div>
                            <div class="wks-roster-phone"><?= esc_html($bk['display_phone'] ?? $bk['phone'] ?? '') ?></div>
                        </div>
                        <div class="wks-roster-badges">
                            <?php if ($paid): ?>
                            <span class="wks-badge wks-bg-green" style="font-size:10px">Đã TT</span>
                            <?php else: ?>
                            <span class="wks-badge wks-bg-amber" style="font-size:10px">Chưa TT</span>
                            <?php endif; ?>
                        </div>
                        <button type="button"
                                class="wks-ci-btn <?= $checked ? 'checked' : '' ?>"
                                title="<?= $checked ? 'Điểm danh: ' . date('H:i d/m', strtotime($bk['checked_in_at'])) : 'Chưa điểm danh' ?>"
                                onclick="wksCheckin(<?= (int)($bk['id'] ?? 0) ?>, '<?= $ci_nonce ?>', this)">
                            <svg viewBox="0 0 16 16" fill="none">
                                <path d="M2 8l4 4 8-8" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                            </svg>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Footer actions -->
            <div class="wks-slot-card-foot">
                <a href="<?= esc_url($edit_url) ?>" class="wks-btn wks-btn-outline wks-btn-sm">Sửa</a>
                <a href="?page=bacera-workshops&id=<?= $wid ?>&tab=bookings&slot_id=<?= $s_id ?>"                   class="wks-btn wks-btn-outline wks-btn-sm">Tất cả HV</a>
                <a href="<?= esc_url($del_url) ?>" class="wks-btn wks-btn-danger wks-btn-sm"
                   onclick="return confirm('Xóa ca học này?')">Xóa</a>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php
    }

    /* ════════════════════════════════════════════════════════════
       TAB: HỌC VIÊN (Bookings)
    ════════════════════════════════════════════════════════════ */
    private function renderTabBookings($wid) {
        global $wpdb;
        $ts = $wpdb->prefix . 'bacera_workshop_slots';
        $tb = $wpdb->prefix . 'bacera_workshop_bookings';

        $slot_filter = intval($_GET['slot_id'] ?? 0);
        $payment_filter = sanitize_key($_GET['pf'] ?? '');

        $where = $wpdb->prepare("b.workshop_id = %d AND b.status != 'cancelled'", $wid);
        if ($slot_filter) $where .= $wpdb->prepare(" AND b.slot_id = %d", $slot_filter);
        if ($payment_filter === 'paid')   $where .= " AND b.payment_status = 'paid'";
        if ($payment_filter === 'unpaid') $where .= " AND b.payment_status = 'unpaid'";

        $bookings = $wpdb->get_results("
            SELECT b.*, s.slot_date, s.time_start
            FROM {$tb} b
            LEFT JOIN {$ts} s ON b.slot_id = s.id
            WHERE {$where}
            ORDER BY b.created_at DESC
        ", ARRAY_A);

        $slots = $wpdb->get_results($wpdb->prepare("SELECT * FROM $ts WHERE workshop_id=%d AND status!='cancelled' ORDER BY slot_date ASC", $wid), ARRAY_A);
        $total = count($bookings);

        // Generate avatar color
        $av_colors = [
            ['bg' => '#E6EFF8', 'color' => '#1A4A7A'],
            ['bg' => '#FFF3D6', 'color' => '#8A5C00'],
            ['bg' => '#FDEAEA', 'color' => '#9B2626'],
            ['bg' => '#E6F4EE', 'color' => '#1A6B4A'],
            ['bg' => '#F0EEE8', 'color' => '#6B6860'],
        ];
        ?>
        <div class="wks-sc">
            <div class="wks-sh">
                <div class="wks-st">Danh sách học viên đăng ký</div>
                <a href="?page=bacera-workshops&id=<?= $wid ?>&tab=bookings" class="wks-btn wks-btn-outline wks-btn-sm">Tất cả ca</a>
            </div>
            <div class="wks-sb" style="padding-bottom:0">
                <div class="wks-sr-row">
                    <select class="wks-sel" onchange="location.href=this.value">
                        <option value="?page=bacera-workshops&id=<?= $wid ?>&tab=bookings">Tất cả ca học</option>
                        <?php foreach ($slots as $sl): ?>
                        <option value="?page=bacera-workshops&id=<?= $wid ?>&tab=bookings&slot_id=<?= $sl['id'] ?>"
                            <?= selected($slot_filter, $sl['id'], false) ?>>
                            <?= date('d/m/Y', strtotime($sl['slot_date'])) ?> (<?= $sl['time_start'] ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <select class="wks-sel" onchange="location.href=this.value">
                        <option value="?page=bacera-workshops&id=<?= $wid ?>&tab=bookings<?= $slot_filter ? "&slot_id={$slot_filter}" : '' ?>">Tất cả TT</option>
                        <option value="?page=bacera-workshops&id=<?= $wid ?>&tab=bookings&pf=paid<?= $slot_filter ? "&slot_id={$slot_filter}" : '' ?>" <?= selected($payment_filter, 'paid', false) ?>>Đã thanh toán</option>
                        <option value="?page=bacera-workshops&id=<?= $wid ?>&tab=bookings&pf=unpaid<?= $slot_filter ? "&slot_id={$slot_filter}" : '' ?>" <?= selected($payment_filter, 'unpaid', false) ?>>Chưa thanh toán</option>
                    </select>
                    <span class="wks-rc"><?= $total ?> kết quả</span>
                </div>
            </div>
            <div class="wks-tw">
                <table class="wks-table">
                    <thead>
                        <tr>
                            <th>Khách hàng</th>
                            <th>Mã booking</th>
                            <th>Ca học</th>
                            <th>Số chỗ</th>
                            <th>Thanh toán</th>
                            <th>Check-in</th>
                            <th>Đặt lúc</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($bookings)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-3)">Chưa có đăng ký nào.</td></tr>
                    <?php else: foreach ($bookings as $i => $b):
                        $av = $av_colors[$i % count($av_colors)];
                        $initials = strtoupper(mb_substr($b['customer_name'], 0, 2));
                        if ($b['payment_status'] === 'paid') { $pc = 'wks-bg-green'; $pt = 'Đã TT'; }
                        elseif ($b['payment_status'] === 'deposited') { $pc = 'wks-bg-blue'; $pt = 'Cọc'; }
                        else { $pc = 'wks-bg-amber'; $pt = 'Chưa TT'; }
                        $nonce_chk = wp_create_nonce('wks_chk_' . $b['id']);
                        $nonce_pay = wp_create_nonce('wks_upd_' . $b['id']);
                        $nonce_can = wp_create_nonce('wks_cancel_' . $b['id']);
                    ?>
                    <tr>
                        <td>
                            <div class="wks-nc">
                                <div class="wks-av" style="background:<?= $av['bg'] ?>;color:<?= $av['color'] ?>"><?= esc_html($initials) ?></div>
                                <div>
                                    <div style="font-weight:500;font-size:13px"><?= esc_html($b['customer_name']) ?></div>
                                    <div class="wks-ns"><?= esc_html($b['phone'] ?? '') ?><?= !empty($b['email']) ? ' · ' . esc_html($b['email']) : '' ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="wks-mono">#BK-<?= str_pad($b['id'], 4, '0', STR_PAD_LEFT) ?></span></td>
                        <td style="font-size:12px;color:var(--text-2)"><?= $b['slot_date'] ? date('d/m/Y', strtotime($b['slot_date'])) : '—' ?><?php if ($b['time_start']): ?><br><?= $b['time_start'] ?><?php endif; ?></td>
                        <td style="font-weight:600"><?= intval($b['num_seats'] ?? 1) ?></td>
                        <td>
                            <select class="wks-sel" style="height:28px;font-size:12px" onchange="wksUpdatePayment(<?= $b['id'] ?>, this.value, '<?= $nonce_pay ?>')">
                                <option value="unpaid" <?= selected($b['payment_status'], 'unpaid', false) ?>>Chưa TT</option>
                                <option value="deposited" <?= selected($b['payment_status'], 'deposited', false) ?>>Đã cọc</option>
                                <option value="paid" <?= selected($b['payment_status'], 'paid', false) ?>>Đã TT</option>
                            </select>
                        </td>
                        <td>
                            <?php if ($b['checked_in']): ?>
                            <span class="wks-badge wks-bg-green">Check-in</span>
                            <?php else: ?>
                            <button class="wks-btn wks-btn-outline wks-btn-sm" onclick="wksCheckin(<?= $b['id'] ?>, '<?= $nonce_chk ?>', this)">Check-in</button>
                            <?php endif; ?>
                        </td>
                        <td><span class="wks-mono"><?= date('d/m H:i', strtotime($b['created_at'])) ?></span></td>
                        <td>
                            <button class="wks-action-btn" onclick="wksCancelBooking(<?= $b['id'] ?>, '<?= $nonce_can ?>',this)" title="Hủy đặt chỗ">Hủy</button>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
        function wksCheckin(id, nonce, btn){
            if(!confirm('Xác nhận check-in học viên này?')) return;
            btn.textContent='...'; btn.disabled=true;
            fetch(ajaxurl,{method:'POST',body:new URLSearchParams({action:'wks_checkin',booking_id:id,_ajax_nonce:nonce})})
            .then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else { btn.textContent='Check-in'; btn.disabled=false; } });
        }
        function wksUpdatePayment(id, status, nonce){
            fetch(ajaxurl,{method:'POST',body:new URLSearchParams({action:'wks_update_payment',booking_id:id,status,_ajax_nonce:nonce})});
        }
        function wksCancelBooking(id, nonce, btn){
            if(!confirm('Hủy đặt chỗ này?')) return;
            btn.textContent='...'; btn.disabled=true;
            fetch(ajaxurl,{method:'POST',body:new URLSearchParams({action:'wks_cancel_booking',booking_id:id,_ajax_nonce:nonce})})
            .then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else { btn.textContent='Hủy'; btn.disabled=false; } });
        }
        </script>
        <?php
    }

    /* ════════════════════════════════════════════════════════════
       TAB: ĐÁNH GIÁ (Reviews)
    ════════════════════════════════════════════════════════════ */
    private function renderTabReviews($wid) {
        global $wpdb;
        $tr = $wpdb->prefix . 'bacera_workshop_reviews';
        $has_table = $wpdb->get_var("SHOW TABLES LIKE '{$tr}'") === $tr;

        if (!$has_table) {
            echo '<div class="wks-empty" style="padding:40px 24px"><p style="color:var(--text-3)">Hệ thống đánh giá chưa được khởi tạo.</p></div>';
            return;
        }

        $reviews = $wpdb->get_results($wpdb->prepare("SELECT * FROM $tr WHERE workshop_id=%d ORDER BY created_at DESC", $wid), ARRAY_A);
        $avg = $wpdb->get_var($wpdb->prepare("SELECT AVG(rating) FROM $tr WHERE workshop_id=%d AND status='approved'", $wid));
        $total = count($reviews);

        $av_colors = [['bg'=>'#E6EFF8','color'=>'#1A4A7A'],['bg'=>'#FFF3D6','color'=>'#8A5C00'],['bg'=>'#FDEAEA','color'=>'#9B2626'],['bg'=>'#E6F4EE','color'=>'#1A6B4A']];

        $rating_counts = array_fill(1, 5, 0);
        foreach ($reviews as $r) {
            if (isset($rating_counts[$r['rating']])) $rating_counts[$r['rating']]++;
        }
        ?>
        <div class="wks-sc">
            <?php if ($total > 0): ?>
            <div class="wks-rs">
                <div style="text-align:center;padding-right:20px;border-right:1px solid var(--border)">
                    <div class="wks-rb"><?= $avg > 0 ? number_format($avg, 1) : '—' ?></div>
                    <div class="wks-stars">★★★★★</div>
                    <div class="wks-rc2"><?= $total ?> đánh giá</div>
                </div>
                <div class="wks-rbars">
                    <?php for ($star = 5; $star >= 1; $star--):
                        $count = $rating_counts[$star];
                        $pct = $total > 0 ? round($count / $total * 100) : 0;
                    ?>
                    <div class="wks-rr">
                        <span><?= $star ?>★</span>
                        <div class="wks-rbar"><div class="wks-rf" style="width:<?= $pct ?>%"></div></div>
                        <span class="wks-rn"><?= $count ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <?php foreach ($reviews as $i => $r):
                $av = $av_colors[$i % count($av_colors)];
                $initials = strtoupper(mb_substr($r['author_name'], 0, 2));
                $stars = str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']);
                if ($r['status'] === 'pending') $status_badge = '<span class="wks-badge wks-bg-amber" style="margin-left:8px">Chờ duyệt</span>';
                elseif ($r['status'] === 'rejected') $status_badge = '<span class="wks-badge wks-bg-red" style="margin-left:8px">Từ chối</span>';
                else $status_badge = '';
            ?>
            <div class="wks-ri">
                <div class="wks-rih">
                    <div class="wks-av" style="background:<?= $av['bg'] ?>;color:<?= $av['color'] ?>;width:34px;height:34px"><?= esc_html($initials) ?></div>
                    <div class="wks-rim">
                        <div class="wks-rin"><?= esc_html($r['author_name']) ?><?= $status_badge ?></div>
                        <div class="wks-rid"><?= date('d/m/Y', strtotime($r['created_at'])) ?></div>
                    </div>
                    <div class="wks-stars-sm"><?= $stars ?></div>
                </div>
                <div class="wks-rit"><?= esc_html($r['review_text']) ?></div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div style="padding:60px 24px;text-align:center;color:var(--text-3);font-size:13px">Chưa có đánh giá nào cho Workshop này.</div>
            <?php endif; ?>
        </div>
        <?php
    }

    /* ════════════════════════════════════════════════════════════
       AJAX: Save Overview
    ════════════════════════════════════════════════════════════ */
    public function ajaxSaveOverview() {
        if (!current_user_can('manage_options')) wp_send_json_error('No permission');
        if (!check_ajax_referer('wks_save_overview', '_ajax_nonce', false)) wp_send_json_error('Nonce error');

        $wid = intval($_POST['workshop_id'] ?? 0);
        if (!$wid) wp_send_json_error('No workshop ID');

        update_post_meta($wid, '_tagline',    sanitize_text_field($_POST['tagline'] ?? ''));
        update_post_meta($wid, '_short_desc', sanitize_textarea_field($_POST['short_desc'] ?? ''));

        $includes   = json_decode(stripslashes($_POST['includes'] ?? '[]'), true) ?: [];
        $highlights = json_decode(stripslashes($_POST['highlights'] ?? '[]'), true) ?: [];
        update_post_meta($wid, '_includes',   wp_json_encode(array_map('sanitize_text_field', $includes)));
        update_post_meta($wid, '_highlights', wp_json_encode(array_map('sanitize_text_field', $highlights)));

        wp_send_json_success();
    }

    /* ════════════════════════════════════════════════════════════
       FORM HANDLERS
    ════════════════════════════════════════════════════════════ */
    public function handleCreateDraftWorkshop() {
        if (!current_user_can('manage_options') || !check_admin_referer('create_draft_workshop_nonce', 'workshop_nonce')) wp_die('Unauthorized');
        $id = wp_insert_post(['post_type' => 'workshop', 'post_title' => 'Workshop Mới', 'post_status' => 'draft']);
        wp_redirect(admin_url("admin.php?page=bacera-workshops&id={$id}&tab=info"));
        exit;
    }

    public function handleSaveWorkshopGeneral() {
        if (!current_user_can('manage_options') || !check_admin_referer('save_workshop_general_nonce', 'workshop_nonce')) wp_die('Unauthorized');
        $wid = intval($_POST['workshop_id'] ?? 0);
        if (!$wid) wp_die('No workshop ID');

        wp_update_post([
            'ID'          => $wid,
            'post_title'  => sanitize_text_field($_POST['post_title'] ?? ''),
            'post_content'=> wp_kses_post($_POST['post_content'] ?? ''),
            'post_status' => in_array($_POST['post_status'] ?? '', ['publish', 'draft']) ? $_POST['post_status'] : 'draft',
        ]);

        // Price: store raw number (strip all non-numeric)
        $raw_price = preg_replace('/[^0-9]/', '', $_POST['_price'] ?? '');
        update_post_meta($wid, '_price', $raw_price);

        // Duration: store as integer minutes
        update_post_meta($wid, '_duration', absint($_POST['_duration'] ?? 0));

        update_post_meta($wid, '_trainer', sanitize_text_field($_POST['_trainer'] ?? ''));
        update_post_meta($wid, '_meta_description', sanitize_textarea_field($_POST['_meta_description'] ?? ''));

        // Includes list (array from named inputs)
        $includes_raw = $_POST['_includes'] ?? [];
        if (is_array($includes_raw)) {
            $includes_clean = array_values(array_filter(array_map('sanitize_text_field', $includes_raw)));
            update_post_meta($wid, '_includes', wp_json_encode($includes_clean));
        }

        // Highlights list (array from named inputs)
        $highlights_raw = $_POST['_highlights'] ?? [];
        if (is_array($highlights_raw)) {
            $highlights_clean = array_values(array_filter(array_map('sanitize_text_field', $highlights_raw)));
            update_post_meta($wid, '_highlights', wp_json_encode($highlights_clean));
        }

        $thumb_id  = intval($_POST['_thumbnail_id'] ?? 0);
        $thumb_url = esc_url_raw($_POST['_thumbnail_url'] ?? '');
        if ($thumb_id > 0) {
            set_post_thumbnail($wid, $thumb_id);
            update_post_meta($wid, '_thumbnail_id', $thumb_id);
            update_post_meta($wid, '_thumbnail_url', $thumb_url);
        } else {
            delete_post_thumbnail($wid);
            delete_post_meta($wid, '_thumbnail_id');
            delete_post_meta($wid, '_thumbnail_url');
        }

        // Gallery images
        $gallery_ids  = array_map('intval',      (array)($_POST['_gallery_ids']  ?? []));
        $gallery_urls = array_map('esc_url_raw', (array)($_POST['_gallery_urls'] ?? []));
        $gallery = [];
        foreach ($gallery_ids as $i => $gid) {
            $gurl = $gallery_urls[$i] ?? '';
            if ($gid > 0 && $gurl) $gallery[] = ['id' => $gid, 'url' => $gurl];
        }
        update_post_meta($wid, '_gallery', wp_json_encode($gallery));

        wp_redirect(admin_url("admin.php?page=bacera-workshops&id={$wid}&tab=info&saved=1"));
        exit;
    }

    public function handleDeleteWorkshop() {
        if (!current_user_can('manage_options') || !check_admin_referer('delete_workshop_nonce', 'workshop_nonce')) wp_die('Unauthorized');
        $wid = intval($_POST['workshop_id'] ?? 0);
        if (!$wid) wp_die('No workshop ID');
        global $wpdb;
        $ts = $wpdb->prefix . 'bacera_workshop_slots';
        $tb = $wpdb->prefix . 'bacera_workshop_bookings';
        $slots = $wpdb->get_col($wpdb->prepare("SELECT id FROM $ts WHERE workshop_id=%d", $wid));
        if (!empty($slots)) {
            $sl = implode(',', array_map('intval', $slots));
            $wpdb->query("DELETE FROM $tb WHERE slot_id IN ($sl)");
            $wpdb->query("DELETE FROM $ts WHERE workshop_id = $wid");
        }
        wp_delete_post($wid, true);
        wp_redirect(admin_url('admin.php?page=bacera-workshops'));
        exit;
    }

    public function handleSaveSession() {
        if (!current_user_can('manage_options') || !check_admin_referer('save_session_nonce', 'session_nonce')) wp_die('Unauthorized');
        global $wpdb;
        $id  = intval($_POST['session_id'] ?? 0);
        $wid = intval($_POST['workshop_id'] ?? 0);
        if (!$wid) wp_die('No workshop ID');

        $data = [
            'workshop_id' => $wid,
            'slot_date'   => sanitize_text_field($_POST['slot_date'] ?? ''),
            'time_start'  => sanitize_text_field($_POST['time_start'] ?? ''),
            'time_end'    => sanitize_text_field($_POST['time_end'] ?? ''),
            'price'       => sanitize_text_field($_POST['price'] ?? ''),
            'reg_start'   => !empty($_POST['reg_start']) ? date('Y-m-d H:i:s', strtotime($_POST['reg_start'])) : null,
            'reg_end'     => !empty($_POST['reg_end']) ? date('Y-m-d H:i:s', strtotime($_POST['reg_end'])) : null,
            'total_seats' => intval($_POST['total_seats'] ?? 0),
            'status'      => sanitize_key($_POST['status'] ?? 'open'),
        ];

        $id > 0
            ? $wpdb->update($wpdb->prefix . 'bacera_workshop_slots', $data, ['id' => $id])
            : $wpdb->insert($wpdb->prefix . 'bacera_workshop_slots', $data);

        wp_redirect(admin_url("admin.php?page=bacera-workshops&id={$wid}&tab=sessions&saved=1"));
        exit;
    }

    /* ════════════════════════════════════════════════════════════
       AJAX HANDLERS
    ════════════════════════════════════════════════════════════ */
    public function ajaxCheckin() {
        if (!current_user_can('manage_options')) wp_send_json_error();
        $id = intval($_POST['booking_id']);
        if (!check_ajax_referer('wks_chk_' . $id, '_ajax_nonce', false)) wp_send_json_error('Nonce');
        global $wpdb;
        $res = $wpdb->update($wpdb->prefix . 'bacera_workshop_bookings',
            ['checked_in' => 1, 'checked_in_at' => current_time('mysql')], ['id' => $id]);
        $res !== false ? wp_send_json_success() : wp_send_json_error();
    }

    public function ajaxCancelBooking() {
        if (!current_user_can('manage_options')) wp_send_json_error();
        $id = intval($_POST['booking_id']);
        if (!check_ajax_referer('wks_cancel_' . $id, '_ajax_nonce', false)) wp_send_json_error('Nonce');
        global $wpdb;
        $tb = $wpdb->prefix . 'bacera_workshop_bookings';
        $ts = $wpdb->prefix . 'bacera_workshop_slots';
        $b = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tb WHERE id=%d", $id), ARRAY_A);
        if ($b && $b['status'] !== 'cancelled') {
            $wpdb->update($tb, ['status' => 'cancelled'], ['id' => $id]);
            $slot = $wpdb->get_row($wpdb->prepare("SELECT * FROM $ts WHERE id=%d", $b['slot_id']), ARRAY_A);
            if ($slot) {
                $new_booked = max(0, (int)$slot['booked_seats'] - (int)($b['num_seats'] ?? 1));
                $new_status = ($new_booked < (int)$slot['total_seats'] && $slot['status'] === 'full') ? 'open' : $slot['status'];
                $wpdb->update($ts, ['booked_seats' => $new_booked, 'status' => $new_status], ['id' => $slot['id']]);
            }
            wp_send_json_success();
        }
        wp_send_json_error();
    }

    public function ajaxUpdatePayment() {
        if (!current_user_can('manage_options')) wp_send_json_error();
        $id = intval($_POST['booking_id']);
        if (!check_ajax_referer('wks_upd_' . $id, '_ajax_nonce', false)) wp_send_json_error('Nonce');
        global $wpdb;
        $wpdb->update($wpdb->prefix . 'bacera_workshop_bookings',
            ['payment_status' => sanitize_key($_POST['status'])], ['id' => $id]);
        wp_send_json_success();
    }
}
