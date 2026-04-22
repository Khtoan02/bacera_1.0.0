<?php
/**
 * Template Name: Workshop Detail
 * Route: /?bacera_workshop_slug={slug} — loaded by MainController::loadWorkshopDetailTemplate()
 */

/* ─── 1. Lấy dữ liệu từ DB ────────────────────────────────────────── */
global $wpdb;

if (have_posts()) : while (have_posts()) : the_post();

$workshop_id = get_the_ID();
$ts = $wpdb->prefix . 'bacera_workshop_slots';
$tr = $wpdb->prefix . 'bacera_workshop_reviews';

$upcoming_slots = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT s.*,
                COALESCE(SUM(b.num_seats), 0) AS booked_seats_real
         FROM {$ts} s
         LEFT JOIN {$wpdb->prefix}bacera_workshop_bookings b
                ON b.slot_id = s.id AND b.status NOT IN ('cancelled')
         WHERE s.workshop_id = %d AND s.slot_date >= CURDATE() AND s.status != 'cancelled'
         GROUP BY s.id
         ORDER BY s.slot_date ASC, s.time_start ASC",
        $workshop_id
    ),
    ARRAY_A
);
// Normalise: make booked_seats always the accurate real sum
foreach ($upcoming_slots as &$_s) {
    $_s['booked_seats'] = (int)$_s['booked_seats_real'];
}
unset($_s);

$reviews = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$tr} WHERE workshop_id = %d AND status = 'approved' ORDER BY created_at DESC LIMIT 10",
        $workshop_id
    ),
    ARRAY_A
);

$avg_rating = $wpdb->get_var(
    $wpdb->prepare("SELECT AVG(rating) FROM {$tr} WHERE workshop_id = %d AND status = 'approved'", $workshop_id)
);

$includes   = json_decode(get_post_meta($workshop_id, '_includes', true) ?: '[]', true) ?: [];
$highlights = json_decode(get_post_meta($workshop_id, '_highlights', true) ?: '[]', true) ?: [];
$meta_desc  = get_post_meta($workshop_id, '_meta_description', true) ?: '';

// Gallery images (thumbnail + extras from _gallery meta if present)
$thumb_url = get_post_meta($workshop_id, '_thumbnail_url', true);
if (has_post_thumbnail()) {
    $thumb_url = get_the_post_thumbnail_url($workshop_id, 'full');
}
$gallery_images_raw = json_decode(get_post_meta($workshop_id, '_gallery', true) ?: '[]', true) ?: [];
$gallery_images = [];
if ($thumb_url) $gallery_images[] = $thumb_url;
foreach ($gallery_images_raw as $g) {
    if (!empty($g['url']) && $g['url'] !== $thumb_url) $gallery_images[] = $g['url'];
}
// Fallback stock images
if (empty($gallery_images)) {
    $gallery_images = [
        'https://images.unsplash.com/photo-1610701596007-11502861dcfa?q=80&w=2070&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1565193566173-7a0cb3d16233?auto=format&fit=crop&w=2070&q=80',
        'https://images.unsplash.com/photo-1578916171728-46686eac8d58?auto=format&fit=crop&w=2070&q=80',
    ];
}

$title      = get_the_title();
$price_raw  = get_post_meta($workshop_id, '_price', true) ?: '';
// Format price for display: raw is stored as plain integer
$price_num  = (int) preg_replace('/[^0-9]/', '', $price_raw ?: '0');
$price_fmt  = $price_num > 0 ? number_format($price_num, 0, ',', '.') . 'đ' : 'Contact us';
$duration_min = intval(get_post_meta($workshop_id, '_duration', true) ?: 0);
// Convert minutes to human-readable
if ($duration_min > 0) {
    $dur_h = floor($duration_min / 60);
    $dur_m = $duration_min % 60;
    $duration_label = $dur_h > 0 ? ($dur_m > 0 ? "{$dur_h} giờ {$dur_m} phút" : "{$dur_h} giờ") : "{$duration_min} phút";
} else {
    $duration_label = get_post_meta($workshop_id, '_duration', true) ?: '—';
}
$trainer    = get_post_meta($workshop_id, '_trainer', true) ?: '—';
$tagline    = get_post_meta($workshop_id, '_tagline', true) ?: '';
$short_desc = get_post_meta($workshop_id, '_short_desc', true) ?: '';
$desc       = get_the_content();
$rating_str = $avg_rating ? number_format($avg_rating, 1) : '5.0';
$review_cnt = count($reviews);

/* ─── 2. Kiểm tra trạng thái đăng nhập ────────────────────────── */
$is_logged_in     = false;
$current_customer = null;
$cookie = $_COOKIE['bacera_customer_auth'] ?? '';
if ($cookie) {
    $decoded = base64_decode($cookie, true);
    if ($decoded && strpos($decoded, '|') !== false) {
        $cid = (int) explode('|', $decoded)[0];
        if ($cid > 0) {
            $tc = $wpdb->prefix . 'bacera_customers';
            $current_customer = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$tc} WHERE id = %d LIMIT 1", $cid),
                ARRAY_A
            );
            if ($current_customer) $is_logged_in = true;
        }
    }
}

/* ─── 3. Booked seats cho slot đầu tiên ────────────────────────── */
$first_slot       = $upcoming_slots[0] ?? null;
$first_booked_arr = [];
$first_total      = 16;
$first_slot_id    = 0;
if ($first_slot) {
    $first_slot_id = (int) $first_slot['id'];
    $first_total   = (int) $first_slot['total_seats'];
    $booked_rows   = $wpdb->get_col($wpdb->prepare(
        "SELECT seats_selected FROM {$wpdb->prefix}bacera_workshop_bookings WHERE slot_id = %d AND status != 'cancelled'",
        $first_slot_id
    ));
    foreach ($booked_rows as $b) {
        if ($b) foreach (array_map('intval', explode(',', $b)) as $n) {
            if ($n > 0) $first_booked_arr[] = $n;
        }
    }
    $first_booked_arr = array_values(array_unique($first_booked_arr));
}

/* ─── 4. Auth page URL ──────────────────────────────────────────── */
$auth_page_url = home_url('/auth/');
$auth_page_id = $wpdb->get_var(
    "SELECT p.ID FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
     WHERE p.post_type='page' AND p.post_status='publish'
       AND pm.meta_key='_wp_page_template'
       AND pm.meta_value IN ('templates/template-auth.php','template-auth.php')
     LIMIT 1"
);
if ($auth_page_id) $auth_page_url = get_permalink($auth_page_id);

/* ─── 5. Slot data for JS ───────────────────────────────────────── */
$slots_js = [];
$days_vi = ['CN','T2','T3','T4','T5','T6','T7'];
foreach ($upcoming_slots as $s) {
    $slots_js[] = [
        'id'           => (int) $s['id'],
        'total_seats'  => (int) $s['total_seats'],
        'booked_seats' => (int) $s['booked_seats'],
        'status'       => $s['status'],
        'label_date'   => date('d/m/Y', strtotime($s['slot_date'])),
        'label_day'    => $days_vi[date('w', strtotime($s['slot_date']))],
        'label_time'   => $s['time_start'] . ' – ' . $s['time_end'],
    ];
}

/* ─── 6. Customer's existing bookings ───────────────────────────── */
$my_bookings = [];
if ($is_logged_in && $current_customer) {
    $tb = $wpdb->prefix . 'bacera_workshop_bookings';
    $my_bookings = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT b.*, s.slot_date, s.time_start, s.time_end
               FROM {$tb} b
               LEFT JOIN {$wpdb->prefix}bacera_workshop_slots s ON b.slot_id = s.id
              WHERE b.workshop_id = %d
                AND (b.phone = %s OR b.email = %s)
                AND b.status != 'cancelled'
              ORDER BY b.created_at DESC LIMIT 5",
            $workshop_id,
            $current_customer['phone'] ?: '__none__',
            $current_customer['email'] ?: '__none__'
        ),
        ARRAY_A
    );
}

/* ─── 7. Payment Methods ────────────────────────────────────────── */
$payment_methods = [];
if ($is_logged_in) {
    if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}bacera_payment_methods'") === "{$wpdb->prefix}bacera_payment_methods") {
        $payment_methods = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bacera_payment_methods WHERE is_active = 1 ORDER BY sort_order ASC, id ASC", ARRAY_A);
    }
}

$redirect_url = esc_url(add_query_arg('redirect_to', urlencode($_SERVER['REQUEST_URI']), $auth_page_url));

get_header();
?>
<?php if ($meta_desc): ?>
<meta name="description" content="<?= esc_attr($meta_desc) ?>">
<?php endif; ?>

<style>
/* ════ Base reset for this page ════ */
#primary.site-main{background:#FAFAF8!important;color:#1c1917!important;padding:0!important;padding-top:80px!important}
*,*::before,*::after{box-sizing:border-box}

/* ════ Layout ════ */
.ws-outer{max-width:1280px;margin:0 auto;padding:36px 24px 80px}
@media(max-width:1023px){.ws-outer{padding-bottom:calc(88px + env(safe-area-inset-bottom,0px))}}
@media(max-width:767px){.ws-outer{padding:24px 16px}}

/* ════ Breadcrumb ════ */
.ws-bc{display:flex;align-items:center;gap:6px;font-size:13px;color:#78716c;font-weight:500;margin-bottom:28px;flex-wrap:wrap}
.ws-bc a{color:#78716c;text-decoration:none;transition:color .2s}.ws-bc a:hover{color:#1c1917}
.ws-bc-sep{color:#d6d3d1;font-size:12px}
.ws-bc-cur{color:#292524}

/* ════ Title row ════ */
.ws-title-row{display:flex;flex-direction:column;gap:8px;margin-bottom:24px}
@media(min-width:768px){.ws-title-row{flex-direction:row;align-items:flex-end;justify-content:space-between;gap:20px}}
.ws-h1{font-size:clamp(28px,5vw,48px);font-weight:500;color:#1c1917;margin:0;line-height:1.15;font-family:Georgia,serif;letter-spacing:-.02em}
.ws-rating-block{display:flex;align-items:center;gap:10px;flex-shrink:0}
.ws-stars{display:flex;gap:3px}
.ws-star-svg{width:18px;height:18px;fill:#ef4444;flex-shrink:0}
.ws-star-svg.empty{fill:#e7e5e4}
.ws-rating-text{font-size:15px;font-weight:500;color:#44403c;white-space:nowrap}

/* ════ Image Slider ════ */
.ws-slider{position:relative;width:100%;aspect-ratio:16/9;border-radius:20px;overflow:hidden;background:#e7e5e4;margin-bottom:52px;box-shadow:0 4px 24px rgba(0,0,0,.07)}
.ws-slider-track{display:flex;width:100%;height:100%}
.ws-slider-img{width:100%;height:100%;object-fit:cover;display:block;transition:opacity .6s,transform .7s;position:absolute;inset:0;opacity:0}
.ws-slider-img.active{opacity:1}
.ws-slider:hover .ws-slider-img.active{transform:scale(1.04)}
/* gradient overlay */
.ws-slider-grad{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.65) 0%,transparent 45%);pointer-events:none;z-index:1}
/* controls row inside slider */
.ws-slider-controls{position:absolute;bottom:18px;left:50%;transform:translateX(-50%);z-index:2;display:flex;align-items:center;gap:12px;width:90%;max-width:680px;justify-content:center}
.ws-slider-nav{width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.92);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s;box-shadow:0 2px 12px rgba(0,0,0,.18)}
.ws-slider-nav:hover{background:#fff;transform:scale(1.08)}
.ws-slider-nav svg{width:18px;height:18px;stroke:#1c1917;stroke-width:2;fill:none}
/* thumbnails */
.ws-thumbs{display:flex;gap:8px;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;padding:2px}
.ws-thumbs::-webkit-scrollbar{display:none}
.ws-thumb{width:72px;height:52px;border-radius:8px;overflow:hidden;cursor:pointer;flex-shrink:0;border:2.5px solid transparent;opacity:.55;transition:all .2s}
.ws-thumb.active,.ws-thumb:hover{opacity:1}
.ws-thumb.active{border-color:#ef4444;box-shadow:0 2px 8px rgba(0,0,0,.2)}
.ws-thumb img{width:100%;height:100%;object-fit:cover;display:block}

/* ════ Body grid ════ */
.ws-body{display:grid;grid-template-columns:1fr;gap:40px}
@media(min-width:1024px){.ws-body{grid-template-columns:minmax(0,1fr) 380px;gap:52px}}
@media(min-width:1200px){.ws-body{grid-template-columns:minmax(0,1fr) 420px}}
.ws-left{display:flex;flex-direction:column;gap:48px}

/* ════ Section headings ════ */
.ws-h2{font-size:28px;font-weight:600;color:#1c1917;margin:0 0 16px;letter-spacing:-.02em}
.ws-h2-sm{font-size:22px;font-weight:600;color:#1c1917;margin:0 0 20px;letter-spacing:-.01em}
.ws-body-text{font-size:17px;line-height:1.75;color:#57534e;max-width:680px}
.ws-divider{border:none;border-top:1px solid #e7e5e4;margin:0}

/* ════ Meta strip (duration / trainer) ════ */
.ws-meta-strip{display:flex;flex-wrap:wrap;gap:32px;padding:20px 0;border-top:1px solid #e7e5e4;border-bottom:1px solid #e7e5e4}
.ws-meta-item{display:flex;flex-direction:column;gap:6px}
.ws-meta-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#a8a29e}
.ws-meta-val{display:flex;align-items:center;gap:8px;font-size:16px;font-weight:500;color:#1c1917}
.ws-meta-icon{width:20px;height:20px;stroke:#57534e;fill:none;stroke-width:1.75;flex-shrink:0}

/* ════ What's in this workshop ════ */
.ws-includes-grid{display:grid;grid-template-columns:1fr;gap:0}
@media(min-width:640px){.ws-includes-grid{grid-template-columns:1fr 1fr;column-gap:32px}}
.ws-incl-item{display:flex;align-items:center;gap:14px;padding:18px 0;border-bottom:1px solid #f3f4f6;color:#292524;font-size:16px}
.ws-incl-item:last-child{border-bottom:none}
.ws-incl-col:last-child .ws-incl-item:last-child{border-bottom:none}
.ws-incl-icon{width:44px;height:44px;border-radius:12px;background:#f5f5f4;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.ws-incl-icon svg{width:22px;height:22px;stroke:#44403c;fill:none;stroke-width:1.75}

/* ════ Key Highlights ════ */
.ws-highlights{padding-top:32px;border-top:1px solid #e7e5e4}
.ws-hl-list{list-style:disc;padding-left:22px;display:flex;flex-direction:column;gap:12px;color:#57534e;font-size:17px;margin:0}
.ws-hl-list li::marker{color:#a8a29e}

/* ════ Reviews ════ */
.ws-reviews-header{display:flex;flex-direction:column;gap:12px;margin-bottom:28px}
@media(min-width:640px){.ws-reviews-header{flex-direction:row;align-items:center;justify-content:space-between}}
.ws-review-item{padding:22px 0;border-bottom:1px solid rgba(231,229,228,.7)}
.ws-review-item:last-of-type{border-bottom:none}
.ws-review-stars{display:flex;gap:3px;margin-bottom:9px}
.ws-review-star{width:16px;height:16px;fill:#ef4444}
.ws-review-star.empty{fill:#e7e5e4}
.ws-review-text{font-size:17px;color:#292524;line-height:1.7;margin-bottom:10px;opacity:.92}
.ws-review-author{display:flex;align-items:center;gap:7px;font-size:13px}
.ws-review-name{font-weight:600;color:#1c1917}
.ws-review-dot{width:4px;height:4px;border-radius:50%;background:#a8a29e;flex-shrink:0}
.ws-review-date{color:#a8a29e}
.ws-btn-more{padding:11px 24px;border-radius:10px;border:1px solid #d6d3d1;background:#fff;color:#57534e;font-size:14px;font-weight:500;cursor:pointer;font-family:inherit;transition:all .2s;margin-top:24px}
.ws-btn-more:hover{background:#f5f5f4;border-color:#a8a29e;color:#1c1917}

/* ════ Write a review form ════ */
.ws-review-form-hd{display:flex;align-items:center;gap:16px;margin-bottom:28px}
.ws-rating-pick{display:flex;gap:5px}
.ws-rating-pick-star{width:26px;height:26px;fill:#e7e5e4;cursor:pointer;transition:fill .15s}
.ws-rating-pick-star:hover,.ws-rating-pick-star.lit{fill:#ef4444}
/* floating label inputs */
.ws-fl-group{position:relative}
.ws-fl-input,.ws-fl-ta{width:100%;padding:18px 20px 6px;border:1.5px solid #e7e5e4;border-radius:14px;font-size:15px;font-family:inherit;color:#1c1917;background:#fff;outline:none;transition:border .2s,box-shadow .2s;resize:none}
.ws-fl-input:focus,.ws-fl-ta:focus{border-color:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,.1)}
.ws-fl-label{position:absolute;left:20px;top:50%;transform:translateY(-50%);color:#a8a29e;font-size:15px;pointer-events:none;transition:all .2s;background:transparent}
.ws-fl-ta-wrap .ws-fl-label{top:18px;transform:none}
.ws-fl-input:focus ~ .ws-fl-label,
.ws-fl-input:not(:placeholder-shown) ~ .ws-fl-label{top:9px;transform:none;font-size:11px;font-weight:600;color:#ef4444;letter-spacing:.04em}
.ws-fl-ta:focus ~ .ws-fl-label,
.ws-fl-ta:not(:placeholder-shown) ~ .ws-fl-label{top:6px;font-size:11px;font-weight:600;color:#ef4444;letter-spacing:.04em}
.ws-grid-2{display:grid;grid-template-columns:1fr;gap:16px}
@media(min-width:640px){.ws-grid-2{grid-template-columns:1fr 1fr}}
.ws-form-col{display:flex;flex-direction:column;gap:16px}
.ws-btn-submit{width:100%;padding:17px;background:#1c1917;color:#fff;border:none;border-radius:14px;font-size:16px;font-weight:600;cursor:pointer;font-family:inherit;transition:background .2s;box-shadow:0 2px 8px rgba(0,0,0,.12)}
.ws-btn-submit:hover{background:#000}

/* ════ RIGHT: Sticky booking card ════ */
.ws-right{position:relative}
@media(max-width:1023px){.ws-right{display:none}} /* hidden on mobile — card moves to bottom sheet */
.ws-book-card{background:#fff;border-radius:24px;border:1px solid #e7e5e4;box-shadow:0 4px 32px rgba(0,0,0,.06);padding:28px;display:flex;flex-direction:column;gap:20px}
@media(min-width:1024px){.ws-book-card{position:sticky;top:96px}}
.ws-card-price{font-size:24px;font-weight:600;color:#1c1917;letter-spacing:-.02em}
.ws-card-price span{font-size:16px;font-weight:400;color:#78716c}

/* ════ Mobile fixed booking bar ════ */
.ws-mobile-bar{
  display:none;
  position:fixed;bottom:0;left:0;right:0;z-index:190;
  background:#fff;
  border-top:1px solid #ece9e4;
  padding:12px 20px;
  padding-bottom:calc(12px + env(safe-area-inset-bottom,0px));
  box-shadow:0 -4px 24px rgba(0,0,0,.10);
  align-items:center;gap:12px;
}
@media(max-width:1023px){.ws-mobile-bar{display:flex}}
.ws-mbar-info{flex:1;min-width:0}
.ws-mbar-price{font-size:19px;font-weight:700;color:#1c1917;letter-spacing:-.02em;line-height:1.2}
.ws-mbar-price span{font-size:13px;font-weight:400;color:#78716c}
.ws-mbar-meta{font-size:12px;color:#78716c;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ws-mbar-cta{
  flex-shrink:0;
  padding:13px 22px;
  background:#ef4444;
  color:#fff;
  border:none;
  border-radius:14px;
  font-size:15px;
  font-weight:700;
  cursor:pointer;
  font-family:inherit;
  transition:background .2s, transform .15s;
  box-shadow:0 4px 16px rgba(239,68,68,.28);
  white-space:nowrap;
}
.ws-mbar-cta:hover{background:#dc2626}
.ws-mbar-cta:active{transform:scale(.97)}

/* ════ Bottom sheet overlay ════ */
.ws-overlay{
  display:none;
  position:fixed;inset:0;z-index:195;
  background:rgba(15,10,5,.45);
  opacity:0;
  transition:opacity .3s;
  -webkit-tap-highlight-color:transparent;
}
.ws-overlay.open{opacity:1}

/* ════ Bottom sheet ════ */
.ws-sheet{
  display:none;
  position:fixed;
  bottom:0;left:0;right:0;
  z-index:196;
  background:#fff;
  border-radius:22px 22px 0 0;
  max-height:92svh;
  overflow-y:auto;
  -webkit-overflow-scrolling:touch;
  transform:translateY(100%);
  transition:transform .38s cubic-bezier(.32,.72,0,1);
  padding-bottom:env(safe-area-inset-bottom,0px);
}
.ws-sheet.open{transform:translateY(0)}
.ws-sheet-bar{
  position:sticky;top:0;z-index:2;
  background:#fff;
  display:flex;align-items:center;justify-content:space-between;
  padding:14px 20px 10px;
  border-bottom:1px solid #f3f4f6;
}
.ws-sheet-handle{
  width:36px;height:4px;
  background:#d6d3d1;
  border-radius:4px;
  flex:1;
  margin:0 auto;
}
.ws-sheet-close{
  width:32px;height:32px;
  border-radius:50%;
  background:#f5f5f4;
  border:none;
  cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;
  transition:background .15s;
}
.ws-sheet-close:hover{background:#e7e5e4}
.ws-sheet-close svg{width:16px;height:16px;stroke:#57534e;stroke-width:2.5;fill:none}
.ws-sheet-inner{padding:20px}
/* When card is inside the sheet, remove card's rounded corners + border */
.ws-sheet .ws-book-card{
  border:none;
  border-radius:0;
  box-shadow:none;
  padding:0;
  position:static!important;
}

/* date/time selector */
.ws-datetime-row{display:flex;border:1.5px solid #e7e5e4;border-radius:14px;overflow:hidden}
.ws-dt-cell{flex:1;padding:14px 16px;cursor:pointer;transition:background .15s;display:flex;justify-content:space-between;align-items:center;gap:6px;border-right:1.5px solid #e7e5e4}
.ws-dt-cell:last-child{border-right:none}
.ws-dt-cell:hover{background:#faf8f5}
.ws-dt-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#a8a29e;margin-bottom:3px}
.ws-dt-value{font-size:14px;font-weight:600;color:#1c1917}
.ws-dt-chev{width:18px;height:18px;stroke:#a8a29e;fill:none;stroke-width:2;flex-shrink:0}

/* seats available divider */
.ws-seats-divider{display:flex;align-items:center;gap:10px}
.ws-seats-line{flex:1;height:1px;background:#e7e5e4}
.ws-seats-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#a8a29e;white-space:nowrap}

/* seat map */
.ws-seatmap{border:1.5px solid #e7e5e4;border-radius:14px;overflow:hidden;background:#fff}
.ws-seatmap-trainer{display:flex;align-items:center;justify-content:center;gap:7px;padding:12px;border-bottom:1px solid #f3f4f6;font-size:13px;font-weight:600;color:#78716c;background:#faf8f5}
.ws-seatmap-trainer svg{width:16px;height:16px;stroke:#a8a29e;fill:none;stroke-width:2}
.ws-seatmap-grid{display:grid;gap:6px;padding:12px}
.ws-seat{height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;cursor:pointer;transition:all .15s;border:none;font-family:inherit;position:relative;overflow:hidden}
.ws-seat.empty{background:#f5f5f4;color:#57534e}
.ws-seat.empty:hover{background:#e7e5e4;transform:scale(1.06)}
.ws-seat.selected{background:#1c1917;color:#fff;transform:scale(1.04);box-shadow:0 2px 8px rgba(0,0,0,.18)}
.ws-seat.booked{background:#fef2f2;color:#fca5a5;cursor:not-allowed}
.ws-seat.booked::after{content:'';position:absolute;width:65%;height:1.5px;background:#fca5a5;transform:rotate(45deg);border-radius:4px}
.ws-seat-legend{display:flex;justify-content:center;gap:16px;padding:10px 12px;border-top:1px solid #f3f4f6;font-size:11px;color:#78716c}
.ws-leg{display:flex;align-items:center;gap:5px}
.ws-leg-dot{width:11px;height:11px;border-radius:4px}

/* total bar */
.ws-total-bar{background:#f5f5f4;border:1px solid #e7e5e4;border-radius:14px;padding:14px 18px;display:flex;align-items:center;justify-content:space-between}
.ws-total-sub{font-size:12px;color:#78716c;margin-top:3px}
.ws-total-amount{font-size:24px;font-weight:700;color:#1c1917;letter-spacing:-.03em}

/* book button */
.ws-btn-book{width:100%;padding:16px;border-radius:14px;border:none;font-size:16px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .2s;box-shadow:0 4px 16px rgba(239,68,68,.2)}
.ws-btn-book.active{background:#ef4444;color:#fff}
.ws-btn-book.active:hover{background:#dc2626}
.ws-btn-book:not(.active){background:#e7e5e4;color:#a8a29e;cursor:not-allowed;box-shadow:none}

/* login gate */
.ws-login-gate{text-align:center;display:flex;flex-direction:column;align-items:center;gap:16px;padding:8px 0}
.ws-login-avatar{width:60px;height:60px;border-radius:50%;background:#f3f4f6;display:flex;align-items:center;justify-content:center}
.ws-login-avatar svg{width:32px;height:32px;stroke:#9ca3af;fill:none;stroke-width:1.5}
.ws-login-title{font-size:17px;font-weight:700;color:#1c1917;margin:0}
.ws-login-sub{font-size:14px;color:#78716c;margin:4px 0 0}
.ws-btn-login{display:block;width:100%;padding:15px;background:#1c1917;color:#fff!important;font-weight:700;font-size:15px;border-radius:14px;text-decoration:none!important;transition:background .2s;text-align:center}
.ws-btn-login:hover{background:#000}
.ws-login-hint{font-size:12px;color:#a8a29e}

/* no slots */
.ws-no-slot{text-align:center;padding:20px 0;display:flex;flex-direction:column;align-items:center;gap:14px}

/* slot list */
.ws-slot-btn{width:100%;display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-radius:14px;border:2px solid #e7e5e4;background:#fff;cursor:pointer;transition:all .2s;margin-bottom:8px;font-family:inherit}
.ws-slot-btn:hover:not([disabled]){border-color:#a8a29e}
.ws-slot-btn.sel{border-color:#1c1917!important;background:#faf8f5}
.ws-slot-btn[disabled]{opacity:.5;cursor:not-allowed}
.ws-slot-date{font-size:14px;font-weight:700;color:#1c1917}
.ws-slot-time{font-size:12px;color:#78716c;margin-top:2px}
.ws-slot-avail-open{font-size:12px;font-weight:700;color:#16a34a}
.ws-slot-avail-full{font-size:12px;font-weight:700;color:#dc2626}

/* steps */
.ws-step-wrap{display:none;flex-direction:column;gap:14px}
.ws-step-wrap.active{display:flex}
.ws-back-btn{display:inline-flex;align-items:center;gap:5px;color:#78716c;background:none;border:none;cursor:pointer;font-size:13px;font-weight:600;font-family:inherit;padding:0;transition:color .2s}
.ws-back-btn:hover{color:#1c1917}
.ws-step-label{font-size:13px;font-weight:600;color:#57534e}

/* summary box */
.ws-summary-box{background:#faf8f5;border:1px solid #e7e5e4;border-radius:14px;padding:16px;display:flex;flex-direction:column;gap:8px;font-size:13px}
.ws-sumrow{display:flex;justify-content:space-between;align-items:baseline;gap:8px}
.ws-sumlabel{color:#78716c;white-space:nowrap}
.ws-sumvalue{font-weight:600;color:#1c1917;text-align:right;word-break:break-word}
.ws-sumdivider{border:none;border-top:1px solid #e7e5e4;margin:2px 0}
.ws-notes-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#78716c;display:block;margin-bottom:8px}
.ws-notes-ta{width:100%;padding:12px;font-size:13px;border:1.5px solid #e7e5e4;border-radius:12px;resize:none;font-family:inherit;background:#faf8f5;color:#1c1917;transition:border .2s;box-sizing:border-box}
.ws-notes-ta:focus{border-color:#1c1917;background:#fff;outline:none}
.ws-err-box{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 14px;font-size:13px;color:#b91c1c}
.ws-hint{font-size:12px;color:#a8a29e;text-align:center}
.ws-user-bar{display:flex;align-items:center;gap:12px;padding:12px;background:#faf8f5;border-radius:12px;border:1px solid #e7e5e4}
.ws-user-avatar{width:36px;height:36px;border-radius:50%;background:#e7e5e4;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#57534e;flex-shrink:0}
.ws-user-name{font-size:13px;font-weight:700;color:#1c1917}
.ws-user-contact{font-size:12px;color:#78716c}

/* payment */
.pay-method-card{display:flex;align-items:flex-start;gap:12px;padding:13px 15px;border:2px solid #e7e5e4;border-radius:13px;cursor:pointer;transition:all .18s;width:100%;text-align:left;background:#fff;font-family:inherit;margin-bottom:8px}
.pay-method-card:hover{border-color:#a8a29e}
.pay-method-card.active{border-color:#1c1917;background:#faf8f5}
.pay-method-card .pm-radio{width:18px;height:18px;border-radius:50%;border:2.5px solid #d6d3d1;flex-shrink:0;display:flex;align-items:center;justify-content:center;margin-top:2px;transition:all .2s}
.pay-method-card.active .pm-radio{border-color:#1c1917;background:#1c1917}
.pay-method-card.active .pm-radio::after{content:'';width:6px;height:6px;background:#fff;border-radius:50%;display:block}
.pm-name{font-size:13px;font-weight:700;color:#1c1917}
.pm-desc{font-size:12px;color:#78716c;margin-top:2px}

/* promo */
.promo-wrap{display:flex;gap:8px}
.promo-input{flex:1;padding:10px 13px;border:1.5px solid #e7e5e4;border-radius:10px;font-size:13px;font-family:inherit;text-transform:uppercase;font-weight:700;color:#d95f47;background:#faf8f5;transition:border .2s}
.promo-input:focus{border-color:#1c1917;outline:none}
.promo-btn{padding:10px 14px;background:#1c1917;color:#fff;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;transition:background .2s}
.promo-btn:hover{background:#000}
.promo-result{font-size:12px;padding:8px 12px;border-radius:8px;margin-top:4px}
.promo-result.ok{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
.promo-result.err{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}

/* success */
.ws-success-wrap{text-align:center;display:flex;flex-direction:column;align-items:center;gap:16px;padding:12px 0}
.ws-success-icon{width:60px;height:60px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center}
.ws-success-icon svg{width:32px;height:32px;stroke:#16a34a;fill:none;stroke-width:2}
.ws-success-title{font-size:19px;font-weight:700;color:#1c1917;margin:0}
.ws-success-msg{font-size:14px;color:#78716c;margin:2px 0 0}
.ws-success-btns{display:flex;flex-direction:column;gap:8px;width:100%}
.ws-btn-secondary{display:block;width:100%;padding:12px;border:1.5px solid #e7e5e4;color:#57534e!important;font-weight:600;font-size:13px;border-radius:12px;background:#fff;cursor:pointer;font-family:inherit;transition:background .2s;text-align:center;text-decoration:none!important}
.ws-btn-secondary:hover{background:#faf8f5}

/* slot preview for guests */
.ws-preview-slot{display:flex;align-items:center;justify-content:space-between;padding:10px 13px;background:#faf8f5;border-radius:12px;border:1px solid #e7e5e4;font-size:13px;margin-top:6px}
.ws-preview-date{font-weight:700;color:#1c1917}
.ws-preview-time{font-size:11px;color:#78716c;margin-top:2px}
.ws-preview-avail{font-weight:700;color:#1c1917;text-align:right;font-size:12px}

/* live price bar on seat map */
.ws-price-live{background:#1c1917;color:#fff;border-radius:12px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;display:none}
.ws-price-live.show{display:flex}

/* ════════════════════════════════════════════════════
   MOBILE COMPACT OVERRIDES  (≤ 767 px)
   Shrink fonts, gaps, padding to show more per screen
════════════════════════════════════════════════════ */
@media(max-width:767px){

  /* ── Page layout ── */
  #primary.site-main{padding-top:0!important}
  .ws-outer{padding:0 0 0}  /* slider flush to top on mobile */

  /* ── Hide title row — shown in mobile bar instead ── */
  .ws-title-row{display:none!important}

  /* ── Breadcrumb — gets its own padding since outer has padding:0 ── */
  .ws-bc{padding:14px 16px 0;margin-bottom:10px;font-size:12px}

  /* ── Image slider: 4:5 ratio, full bleed on mobile ── */
  .ws-slider{aspect-ratio:4/5;border-radius:0;margin-bottom:24px;box-shadow:none}
  .ws-slider-controls{bottom:10px;gap:8px}
  .ws-slider-nav{width:30px;height:30px}
  .ws-slider-nav svg{width:14px;height:14px}
  .ws-thumb{width:52px;height:38px;border-radius:6px}

  /* ── Body grid: slider full bleed, content has 16px gutter ── */
  .ws-body{gap:0;padding:0 16px 16px 16px }  /* horizontal margins for all content below slider */
  .ws-left{gap:24px}

  /* ── Section headings ── */
  .ws-h2{font-size:19px;margin-bottom:10px}
  .ws-h2-sm{font-size:16px;margin-bottom:14px}

  /* ── Body text & overview ── */
  .ws-body-text{font-size:15px;line-height:1.6}

  /* ── Meta strip (duration/trainer) ── */
  .ws-meta-strip{padding:14px 0;gap:20px;margin-top:18px}
  .ws-meta-label{font-size:10px}
  .ws-meta-val{font-size:14px;gap:6px}
  .ws-meta-icon{width:16px;height:16px}

  /* ── Includes grid ── */
  .ws-includes-grid{grid-template-columns:1fr}
  .ws-incl-item{padding:12px 0;gap:10px;font-size:14px}
  .ws-incl-icon{width:34px;height:34px;border-radius:8px}
  .ws-incl-icon svg{width:17px;height:17px}

  /* ── Highlights ── */
  .ws-highlights{padding-top:20px}
  .ws-hl-list{font-size:15px;gap:9px}

  /* ── Reviews ── */
  .ws-reviews-header{margin-bottom:16px}
  .ws-review-item{padding:14px 0}
  .ws-review-text{font-size:15px;line-height:1.6;margin-bottom:6px}
  .ws-review-stars{margin-bottom:6px}
  .ws-review-star{width:13px;height:13px}
  .ws-review-author{font-size:12px}
  .ws-btn-more{padding:9px 18px;font-size:13px;margin-top:16px}

  /* ── Review form ── */
  .ws-review-form-hd{margin-bottom:18px;gap:10px}
  .ws-fl-input,.ws-fl-ta{padding:14px 16px 5px;font-size:14px;border-radius:12px}
  .ws-fl-label{left:16px;font-size:14px}
  .ws-fl-input:focus ~ .ws-fl-label,
  .ws-fl-input:not(:placeholder-shown) ~ .ws-fl-label{top:7px;font-size:10px}
  .ws-fl-ta:focus ~ .ws-fl-label,
  .ws-fl-ta:not(:placeholder-shown) ~ .ws-fl-label{top:5px;font-size:10px}
  .ws-btn-submit{padding:14px;font-size:15px;border-radius:12px}
  .ws-form-col{gap:12px}
  .ws-grid-2{gap:12px}
  .ws-rating-pick-star{width:22px;height:22px}

  /* ── Section divider spacing ─ tighten top-padded sections ── */
  div[style*="border-top:1px solid #e7e5e4;padding-top:36px"]{padding-top:22px!important}
  div[style*="border-top:1px solid #e7e5e4;padding-top:40px"]{padding-top:24px!important}
  div[style*="margin-bottom:48px"]{margin-bottom:0!important}

  /* ── Bottom sheet inner ── */
  .ws-sheet-inner{padding:16px}
  .ws-book-card{gap:14px}
  .ws-card-price{font-size:20px}
  .ws-slot-btn{padding:11px 13px}
  .ws-slot-date{font-size:13px}
  .ws-slot-time{font-size:11px}
  .ws-summary-box{padding:12px;gap:6px;font-size:12px}
  .ws-notes-ta{padding:10px;font-size:13px}
  .ws-btn-book{padding:13px;font-size:15px;border-radius:12px}
  .ws-seatmap-grid{gap:5px;padding:10px}
  .ws-seat{height:36px;font-size:11px;border-radius:8px}
  .ws-seat-legend{font-size:10px;gap:10px;padding:8px}

  /* ── Mobile bar with title ── */
  .ws-mobile-bar{flex-direction:row;align-items:center;gap:12px;padding:10px 16px}
  .ws-mobile-bar{padding-bottom:calc(10px + env(safe-area-inset-bottom,0px))}
  .ws-mbar-info{display:flex;flex-direction:column;gap:3px;min-width:0;flex:1}
  .ws-mbar-title{font-size:13px;font-weight:700;color:#1c1917;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.3}
  .ws-mbar-bottom{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap}
  .ws-mbar-price{font-size:15px;font-weight:700;color:#1c1917;white-space:nowrap}
  .ws-mbar-price span{font-size:12px;font-weight:400;color:#78716c}
  .ws-mbar-meta{font-size:11px;color:#a8a29e;white-space:nowrap}
  .ws-mbar-cta{padding:11px 16px;font-size:14px;border-radius:12px;flex-shrink:0}
}
</style>

<main id="primary" class="site-main">
<div class="ws-outer">

    <!-- BREADCRUMB -->
    <nav class="ws-bc">
        <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
        <span class="ws-bc-sep">/</span>
        <a href="<?php echo esc_url(home_url('/workshop/')); ?>">Workshop</a>
        <span class="ws-bc-sep">/</span>
        <span class="ws-bc-cur"><?php echo esc_html($title); ?></span>
    </nav>

    <!-- TITLE & RATING -->
    <div class="ws-title-row">
        <h1 class="ws-h1"><?php echo esc_html($title); ?></h1>
        <?php if ($review_cnt > 0 || $avg_rating): ?>
        <div class="ws-rating-block">
            <div class="ws-stars">
                <?php
                $rating_val = $avg_rating ? round($avg_rating) : 5;
                for ($i = 1; $i <= 5; $i++) {
                    $cls = $i <= $rating_val ? '' : ' empty';
                    echo '<svg class="ws-star-svg' . $cls . '" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
                }
                ?>
            </div>
            <span class="ws-rating-text"><?php echo esc_html($rating_str); ?>/5<?php if ($review_cnt > 0): ?> &mdash; <?php echo $review_cnt; ?> reviews<?php endif; ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- IMAGE SLIDER -->
    <div class="ws-slider" id="ws-slider">
        <?php foreach ($gallery_images as $idx => $img): ?>
        <img src="<?php echo esc_url($img); ?>"
             alt="<?php echo esc_attr($title . ' - ' . ($idx + 1)); ?>"
             class="ws-slider-img<?php echo $idx === 0 ? ' active' : ''; ?>"
             data-idx="<?php echo $idx; ?>">
        <?php endforeach; ?>

        <div class="ws-slider-grad"></div>

        <div class="ws-slider-controls">
            <button class="ws-slider-nav" id="ws-prev" aria-label="Ảnh trước">
                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <div class="ws-thumbs" id="ws-thumbs">
                <?php foreach ($gallery_images as $idx => $img): ?>
                <div class="ws-thumb<?php echo $idx === 0 ? ' active' : ''; ?>" data-idx="<?php echo $idx; ?>">
                    <img src="<?php echo esc_url($img); ?>" alt="">
                </div>
                <?php endforeach; ?>
            </div>
            <button class="ws-slider-nav" id="ws-next" aria-label="Ảnh tiếp">
                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>

    <!-- BODY GRID -->
    <div class="ws-body">

        <!-- ══ LEFT COLUMN ══ -->
        <div class="ws-left">

            <!-- Overview -->
            <div>
                <h2 class="ws-h2">Workshop overview</h2>
                <?php if ($tagline): ?>
                <p class="ws-body-text" style="font-size:19px;color:#292524;font-weight:500;margin-bottom:10px;"><?php echo esc_html($tagline); ?></p>
                <?php endif; ?>
                <?php if ($short_desc || $desc): ?>
                <div class="ws-body-text">
                    <?php echo $short_desc ? esc_html($short_desc) : wp_kses_post(wpautop($desc)); ?>
                </div>
                <?php else: ?>
                <p class="ws-body-text">Experience a handcraft workshop with Bacera. This class will help you discover yourself through creative art.</p>
                <?php endif; ?>

                <!-- Meta strip -->
                <div class="ws-meta-strip" style="margin-top:28px">
                    <?php if ($duration_label && $duration_label !== '—'): ?>
                    <div class="ws-meta-item">
                        <span class="ws-meta-label">Duration</span>
                        <div class="ws-meta-val">
                            <svg class="ws-meta-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/></svg>
                            <?php echo esc_html($duration_label); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($trainer && $trainer !== '—'): ?>
                    <div class="ws-meta-item">
                        <span class="ws-meta-label">Instructor</span>
                        <div class="ws-meta-val">
                            <svg class="ws-meta-icon" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <?php echo esc_html($trainer); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- What's in this workshop -->
            <?php if (!empty($includes)): ?>
            <div>
                <h2 class="ws-h2-sm">What's in this workshop</h2>
                <?php
                // Icons to cycle through
                $incl_icons = [
                    '<path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/>',
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM16 3H8v4h8V3z"/>',
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>',
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>',
                ];
                // Split into 2 columns
                $col1 = array_slice($includes, 0, ceil(count($includes)/2));
                $col2 = array_slice($includes, ceil(count($includes)/2));
                ?>
                <div class="ws-includes-grid">
                    <div class="ws-incl-col">
                        <?php foreach ($col1 as $i => $item): ?>
                        <div class="ws-incl-item">
                            <div class="ws-incl-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                                    <?php echo $incl_icons[$i % count($incl_icons)]; ?>
                                </svg>
                            </div>
                            <span><?php echo esc_html($item); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($col2)): ?>
                    <div class="ws-incl-col">
                        <?php foreach ($col2 as $i => $item): ?>
                        <div class="ws-incl-item">
                            <div class="ws-incl-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                                    <?php echo $incl_icons[($i + ceil(count($includes)/2)) % count($incl_icons)]; ?>
                                </svg>
                            </div>
                            <span><?php echo esc_html($item); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Key highlights -->
            <?php if (!empty($highlights)): ?>
            <div class="ws-highlights">
                <h2 class="ws-h2-sm">Key highlights</h2>
                <ul class="ws-hl-list">
                    <?php foreach ($highlights as $hl): ?>
                    <li><?php echo esc_html($hl); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Reviews -->
            <div style="border-top:1px solid #e7e5e4;padding-top:36px">
                <div class="ws-reviews-header">
                    <h2 class="ws-h2" style="margin-bottom:0">Reviews</h2>
                    <?php if ($review_cnt > 0): ?>
                    <div class="ws-rating-block">
                        <span class="ws-rating-text"><?php echo esc_html($rating_str); ?>/5 &mdash; <?php echo $review_cnt; ?> reviews</span>
                        <div class="ws-stars">
                            <?php for ($i = 1; $i <= 5; $i++):
                                $cls = $i <= round((float)$avg_rating) ? '' : ' empty';
                            ?><svg class="ws-star-svg<?php echo $cls; ?>" viewBox="0 0 24 24" style="width:16px;height:16px"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg><?php endfor; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $rev): ?>
                <div class="ws-review-item">
                    <div class="ws-review-stars">
                        <?php for ($i = 1; $i <= 5; $i++):
                            $cls = $i <= intval($rev['rating']) ? '' : ' empty';
                        ?><svg class="ws-review-star<?php echo $cls; ?>" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg><?php endfor; ?>
                    </div>
                    <p class="ws-review-text"><?php echo esc_html($rev['review_text']); ?></p>
                    <div class="ws-review-author">
                        <span class="ws-review-name"><?php echo esc_html($rev['author_name']); ?></span>
                        <span class="ws-review-dot"></span>
                        <span class="ws-review-date"><?php echo date('d/m/Y', strtotime($rev['created_at'])); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if ($review_cnt >= 10): ?>
                <button class="ws-btn-more">See more reviews</button>
                <?php endif; ?>
                <?php else: ?>
                <p style="color:#a8a29e;font-style:italic;font-size:15px">No reviews yet. Be the first to share your experience!</p>
                <?php endif; ?>
            </div>

            <!-- Write a review -->
            <div style="border-top:1px solid #e7e5e4;padding-top:40px;margin-bottom:48px">
                <div class="ws-review-form-hd">
                    <h2 class="ws-h2" style="margin-bottom:0">Write a review</h2>
                    <div class="ws-rating-pick" id="ws-star-picker">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <svg class="ws-rating-pick-star" data-val="<?php echo $i; ?>" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" id="ws-review-rating" value="0">
                </div>

                <form id="ws-review-form" onsubmit="return wsSubmitReview(event)" class="ws-form-col">
                    <div class="ws-grid-2">
                        <div class="ws-fl-group">
                            <input type="text" id="ws-rev-name" class="ws-fl-input" placeholder=" " required>
                            <label class="ws-fl-label" for="ws-rev-name">Full name</label>
                        </div>
                        <div class="ws-fl-group">
                            <input type="email" id="ws-rev-email" class="ws-fl-input" placeholder=" ">
                            <label class="ws-fl-label" for="ws-rev-email">Email (optional)</label>
                        </div>
                    </div>
                    <div class="ws-fl-group">
                        <input type="text" id="ws-rev-title" class="ws-fl-input" placeholder=" ">
                        <label class="ws-fl-label" for="ws-rev-title">Review title</label>
                    </div>
                    <div class="ws-fl-group ws-fl-ta-wrap">
                        <textarea id="ws-rev-body" class="ws-fl-ta" rows="5" placeholder=" " required></textarea>
                        <label class="ws-fl-label" for="ws-rev-body">Share your experience...</label>
                    </div>
                    <div id="ws-review-msg" style="display:none;font-size:13px;padding:10px 14px;border-radius:10px"></div>
                    <button type="submit" class="ws-btn-submit">Submit review</button>
                </form>
            </div>

        </div><!-- /ws-left -->

        <!-- ══ RIGHT: BOOKING CARD ══ -->
        <div class="ws-right">
            <div class="ws-book-card" id="bwks-widget">

                <?php if (empty($upcoming_slots)): ?>
                <!-- No slots -->
                <div class="ws-no-slot">
                    <div style="font-size:44px">📅</div>
                    <p style="font-weight:600;color:#44403c;font-size:15px;margin:0">Chưa có lịch học nào</p>
                    <p style="color:#78716c;font-size:14px;margin:0">Please contact us to schedule a private session.</p>
                    <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="ws-btn-login" style="margin-top:4px">Contact us</a>
                </div>

                <?php elseif (!$is_logged_in): ?>
                <!-- Login gate -->
                <h3 class="ws-card-price"><?php echo esc_html($price_fmt); ?><span>/Person</span></h3>
                <div class="ws-login-gate">
                        <div class="ws-login-avatar">
                            <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                        </div>
                        <div>
                            <p class="ws-login-title">Sign in to book a seat</p>
                            <p class="ws-login-sub">You need a Bacera account to confirm and manage your bookings.</p>
                        </div>
                        <a href="<?php echo $redirect_url; ?>" class="ws-btn-login">Sign in / Register</a>
                        <p class="ws-login-hint">Free &mdash; Takes only 30 seconds</p>
                    </div>
                    <!-- Slot preview -->
                    <div style="border-top:1px solid #f0ede8;padding-top:16px">
                        <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#a8a29e;margin:0 0 8px">Upcoming sessions</p>
                        <?php foreach (array_slice($upcoming_slots, 0, 3) as $sl):
                            $pvail = (int)$sl['total_seats'] - (int)$sl['booked_seats']; ?>
                        <div class="ws-preview-slot">
                            <div>
                                <div class="ws-preview-date"><?php echo date('d/m/Y', strtotime($sl['slot_date'])); ?></div>
                                <div class="ws-preview-time"><?php echo esc_html($sl['time_start'].' – '.$sl['time_end']); ?></div>
                            </div>
                            <div class="ws-preview-avail">
                                <?php echo $pvail; ?> seats available<br>
                                <span style="color:<?php echo $sl['status']==='full'?'#dc2626':'#16a34a'; ?>;font-weight:600"><?php echo $sl['status']==='full'?'Full':'Open'; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php else: ?>
                <!-- ══ FULL BOOKING WIDGET (logged in) ══ -->

                <!-- Price -->
                <h3 class="ws-card-price" id="bwks-header-price"><?php echo esc_html($price_fmt); ?><span>/Person</span></h3>

                <!-- User bar -->
                <div class="ws-user-bar">
                    <div class="ws-user-avatar"><?php echo mb_strtoupper(mb_substr($current_customer['name']?:'K',0,1)); ?></div>
                    <div style="flex:1;min-width:0">
                        <div class="ws-user-name"><?php echo esc_html($current_customer['name']?:'Khách'); ?></div>
                        <div class="ws-user-contact"><?php echo esc_html($current_customer['phone']?:$current_customer['email']?:''); ?></div>
                    </div>
                    <svg style="width:18px;height:18px;stroke:#16a34a;fill:none" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </div>

                <div id="bwks-booking">

                    <!-- STEP 1: Chọn lịch -->
                    <div id="bwks-step1" class="ws-step-wrap active">
                        <div class="ws-step-label">Select a date</div>
                        <?php foreach ($upcoming_slots as $sl):
                            $avail   = (int)$sl['total_seats'] - (int)$sl['booked_seats'];
                            $is_full = $sl['status']==='full' || $avail <= 0;
                            $slot_price_raw = !empty($sl['price']) ? $sl['price'] : $price_raw;
                            $slot_price_num = (int) preg_replace('/[^0-9]/', '', $slot_price_raw ?: '0');
                        ?>
                        <button type="button" class="ws-slot-btn"
                                id="slot-btn-<?php echo (int)$sl['id']; ?>"
                                <?php echo $is_full ? 'disabled' : ''; ?>
                                data-slot-id="<?php echo (int)$sl['id']; ?>"
                                data-total="<?php echo (int)$sl['total_seats']; ?>"
                                data-price="<?php echo $slot_price_num; ?>"
                                data-label="<?php echo esc_attr($days_vi[date('w',strtotime($sl['slot_date']))].', '.date('d/m/Y',strtotime($sl['slot_date'])).' — '.$sl['time_start'].' – '.$sl['time_end']); ?>"
                                onclick="bwksSelectSlot(this)">
                            <div>
                                <div class="ws-slot-date"><?php echo $days_vi[date('w',strtotime($sl['slot_date']))].', '.date('d/m/Y',strtotime($sl['slot_date'])); ?></div>
                                <div class="ws-slot-time"><?php echo esc_html($sl['time_start'].' – '.$sl['time_end']); ?></div>
                            </div>
                            <div style="text-align:right">
                                <?php if ($is_full): ?>
                                <span class="ws-slot-avail-full">Full</span>
                                <?php else: ?>
                                <span class="ws-slot-avail-open"><?php echo $avail; ?> seats available</span>
                                <?php if ($slot_price_num > 0): ?>
                                <div style="font-size:11px;color:#78716c;margin-top:2px"><?php echo number_format($slot_price_num,0,',','.'); ?>đ/seat</div>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </button>
                        <?php endforeach; ?>
                        <button id="bwks-btn-step2" type="button" disabled onclick="bwksGoStep(2)"
                                class="ws-btn-book" style="font-size:14px;padding:14px">
                            Select seat position →
                        </button>
                    </div>

                    <!-- STEP 2: Seat map -->
                    <div id="bwks-step2" class="ws-step-wrap">
                        <div style="display:flex;align-items:center;gap:8px">
                            <button type="button" class="ws-back-btn" onclick="bwksGoStep(1)">
                                <svg style="width:18px;height:18px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                Quay lại
                            </button>
                            <span class="ws-step-label" id="bwks-step2-label">Select seat position</span>
                        </div>

                        <div id="bwks-seats-loading" style="text-align:center;padding:16px;color:#a8a29e;font-size:13px;display:none">Loading seat map...</div>

                        <div id="bwks-seats-area" class="ws-seatmap">
                            <div class="ws-seatmap-trainer">
                                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Trainer position
                            </div>
                            <div id="bwks-seat-grid" class="ws-seatmap-grid"></div>
                            <div class="ws-seat-legend">
                                <div class="ws-leg"><div class="ws-leg-dot" style="background:#1c1917"></div> Selected (<span id="bwks-sel-count">0</span>)</div>
                                <div class="ws-leg"><div class="ws-leg-dot" style="background:#f5f5f4;border:1.5px solid #e7e5e4"></div> Available</div>
                                <div class="ws-leg"><div class="ws-leg-dot" style="background:#fef2f2;border:1.5px solid #fecaca"></div> Booked</div>
                            </div>
                        </div>

                        <!-- Live price bar -->
                        <div id="bwks-price-live" class="ws-price-live">
                            <span style="font-size:13px;opacity:.8">Estimated total</span>
                            <span style="font-size:20px;font-weight:700" id="bwks-price-live-val">0đ</span>
                        </div>

                        <button id="bwks-btn-step3" type="button" disabled onclick="bwksGoStep(3)"
                                class="ws-btn-book" style="font-size:14px;padding:14px">
                            Continue (<span id="bwks-seat-count">0</span> seat(s)) →
                        </button>
                    </div>

                    <!-- STEP 3: Confirm -->
                    <div id="bwks-step3" class="ws-step-wrap">
                        <div style="display:flex;align-items:center;gap:8px">
                            <button type="button" class="ws-back-btn" onclick="bwksGoStep(2)">
                                <svg style="width:18px;height:18px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                Back
                            </button>
                            <span class="ws-step-label">Confirm booking</span>
                        </div>

                        <!-- Summary -->
                        <div class="ws-summary-box">
                            <div class="ws-sumrow"><span class="ws-sumlabel">Workshop</span><span class="ws-sumvalue"><?php echo esc_html($title); ?></span></div>
                            <div class="ws-sumrow"><span class="ws-sumlabel">Date</span><span class="ws-sumvalue" id="bwks-sum-date">—</span></div>
                            <div class="ws-sumrow"><span class="ws-sumlabel">Seat position(s)</span><span class="ws-sumvalue" id="bwks-sum-seats">—</span></div>
                            <div class="ws-sumrow"><span class="ws-sumlabel">Quantity</span><span class="ws-sumvalue" id="bwks-sum-qty">—</span></div>
                            <div class="ws-sumrow"><span class="ws-sumlabel">Price/seat</span><span class="ws-sumvalue" id="bwks-sum-unit">—</span></div>
                            <div class="ws-sumrow" id="bwks-sum-discount" style="display:none"></div>
                            <hr class="ws-sumdivider">
                            <div class="ws-sumrow"><span class="ws-sumlabel">Booked by</span><span class="ws-sumvalue"><?php echo esc_html($current_customer['name']?:'Guest'); ?></span></div>
                            <div class="ws-sumrow"><span class="ws-sumlabel">Contact</span><span class="ws-sumvalue"><?php echo esc_html($current_customer['phone']?:$current_customer['email']?:'—'); ?></span></div>
                            <hr class="ws-sumdivider">
                            <div class="ws-sumrow" style="margin-top:2px">
                                <span style="font-size:14px;font-weight:700;color:#1c1917">TOTAL</span>
                                <span style="font-size:22px;font-weight:800;color:#ef4444;letter-spacing:-.5px" id="bwks-sum-total">—</span>
                            </div>
                        </div>

                        <!-- Payment methods -->
                        <?php if (!empty($payment_methods)): ?>
                        <div>
                            <label class="ws-notes-label">Payment method</label>
                            <?php foreach ($payment_methods as $idx => $pm): ?>
                            <label class="pay-method-card <?php echo $idx === 0 ? 'active' : ''; ?>">
                                <div class="pm-radio"></div>
                                <div style="flex:1">
                                    <div class="pm-name"><?php echo esc_html($pm['name']); ?></div>
                                    <div class="pm-desc"><?php echo esc_html($pm['description']); ?></div>
                                    <input type="radio" name="bwks_payment_method" value="<?php echo esc_attr($pm['code']); ?>" <?php checked($idx, 0); ?> style="display:none" onchange="bwksSelectPayment(this)">
                                </div>
                                <?php if ($pm['icon_url']): ?><img src="<?php echo esc_url($pm['icon_url']); ?>" style="width:32px;height:32px;object-fit:contain"><?php endif; ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Promo code -->
                        <div>
                            <label class="ws-notes-label">Promo code</label>
                            <div class="promo-wrap">
                                <input type="text" id="bwks-promo-input" class="promo-input" placeholder="Enter code">
                                <button type="button" class="promo-btn" onclick="bwksApplyPromo()">Apply</button>
                            </div>
                            <div id="bwks-promo-msg" class="promo-result" style="display:none"></div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="ws-notes-label">Notes (optional)</label>
                            <textarea id="bwks-notes" class="ws-notes-ta" rows="2" placeholder="Special requests, questions..."></textarea>
                        </div>

                        <div id="bwks-err" class="ws-err-box" style="display:none"></div>
                        <button id="bwks-confirm-btn" class="ws-btn-book active" type="button" onclick="bwksSubmit()">
                            Confirm booking
                        </button>
                        <p class="ws-hint">After booking, we will contact you to confirm and guide you through payment.</p>
                    </div>

                    <!-- SUCCESS -->
                    <div id="bwks-success" style="display:none" class="ws-success-wrap">
                        <div class="ws-success-icon">
                            <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        </div>
                        <div>
                            <p class="ws-success-title">Booking confirmed! 🎉</p>
                            <p class="ws-success-msg" id="bwks-success-msg">We will contact you to confirm soon.</p>
                        </div>
                        <div class="ws-success-btns">
                            <a href="<?php echo esc_url(home_url('/')); ?>" class="ws-btn-login">View other workshops</a>
                            <button type="button" onclick="bwksReset()" class="ws-btn-secondary">Book another session</button>
                        </div>
                    </div>

                </div><!-- /bwks-booking -->
                <?php endif; ?>

            </div><!-- /ws-book-card -->
        </div><!-- /ws-right -->

    </div><!-- /ws-body -->
</div><!-- /ws-outer -->

<!-- ══ MOBILE FIXED BOOKING BAR ══ -->
<div class="ws-mobile-bar" id="ws-mobile-bar">
    <div class="ws-mbar-info">
        <div class="ws-mbar-title"><?php echo esc_html($title); ?></div>
        <div class="ws-mbar-bottom">
            <div class="ws-mbar-price" id="ws-mbar-price-lbl">
                <?php echo esc_html($price_fmt); ?><span>/Person</span>
            </div>
            <div class="ws-mbar-meta" id="ws-mbar-meta-lbl">
                <?php
                if (!empty($upcoming_slots)) {
                    $first_avail = (int)$upcoming_slots[0]['total_seats'] - (int)$upcoming_slots[0]['booked_seats'];
                    echo count($upcoming_slots) . ' sessions · ' . $first_avail . ' seats available';
                } else {
                    echo 'No upcoming sessions';
                }
                ?>
            </div>
        </div>
    </div>
    <button class="ws-mbar-cta" id="ws-mbar-cta" onclick="wsOpenSheet()">
        <?php echo $is_logged_in ? 'Book a seat' : 'Book a seat'; ?>
    </button>
</div>

<!-- ══ OVERLAY ══ -->
<div class="ws-overlay" id="ws-overlay" onclick="wsCloseSheet()"></div>

<!-- ══ BOTTOM SHEET ══ -->
<div class="ws-sheet" id="ws-sheet">
    <div class="ws-sheet-bar">
        <div style="width:32px"></div><!-- spacer -->
        <div class="ws-sheet-handle"></div>
        <button class="ws-sheet-close" onclick="wsCloseSheet()" aria-label="Close">
            <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div class="ws-sheet-inner" id="ws-sheet-inner"></div>
</div>

</main>

<script>
/* ════ Mobile Bottom Sheet ════ */
(function(){
    var overlay = document.getElementById('ws-overlay');
    var sheet   = document.getElementById('ws-sheet');
    var sheetIn = document.getElementById('ws-sheet-inner');
    var card    = document.getElementById('bwks-widget');
    var wsRight = document.querySelector('.ws-right');
    var isMoved = false;

    function isMobile() { return window.innerWidth < 1024; }

    function ensureMoved() {
        if (!card || !sheetIn) return;
        if (isMobile() && !isMoved) {
            sheetIn.appendChild(card);
            isMoved = true;
        } else if (!isMobile() && isMoved) {
            if (wsRight) wsRight.appendChild(card);
            isMoved = false;
        }
    }

    window.wsOpenSheet = function() {
        if (!sheet || !overlay) return;
        ensureMoved();

        /* Make visible first (display:none → block), THEN animate */
        overlay.style.display = 'block';
        sheet.style.display   = 'block';

        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                overlay.classList.add('open');
                sheet.classList.add('open');
                document.body.style.overflow = 'hidden';
            });
        });
    };

    window.wsCloseSheet = function() {
        if (!sheet || !overlay) return;
        sheet.classList.remove('open');
        overlay.classList.remove('open');
        document.body.style.overflow = '';

        function onEnd() {
            sheet.style.display   = 'none';
            overlay.style.display = 'none';
            sheet.removeEventListener('transitionend', onEnd);
        }
        sheet.addEventListener('transitionend', onEnd);
    };

    /* Resize: move card back to desktop column */
    window.addEventListener('resize', function() {
        ensureMoved();
        if (!isMobile() && sheet.classList.contains('open')) wsCloseSheet();
    });

    /* Swipe-down to close */
    var startY = 0;
    if (sheet) {
        sheet.addEventListener('touchstart', function(e) {
            startY = e.touches[0].clientY;
        }, { passive: true });
        sheet.addEventListener('touchend', function(e) {
            if (e.changedTouches[0].clientY - startY > 72) wsCloseSheet();
        }, { passive: true });
    }

    /* Init on page load */
    if (isMobile()) ensureMoved();
})();

/* ════ Image slider + Review form + Booking widget ════ */
(function() {
    /* ── Image Slider ────────────────────────────────────────────── */
    var imgs   = document.querySelectorAll('.ws-slider-img');
    var thumbs = document.querySelectorAll('.ws-thumb');
    var cur    = 0;

    function goSlide(n) {
        imgs[cur].classList.remove('active');
        thumbs[cur] && thumbs[cur].classList.remove('active');
        cur = (n + imgs.length) % imgs.length;
        imgs[cur].classList.add('active');
        thumbs[cur] && thumbs[cur].classList.add('active');
    }

    var prevBtn = document.getElementById('ws-prev');
    var nextBtn = document.getElementById('ws-next');
    if (prevBtn) prevBtn.addEventListener('click', function() { goSlide(cur - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function() { goSlide(cur + 1); });
    thumbs.forEach(function(t) {
        t.addEventListener('click', function() { goSlide(parseInt(t.dataset.idx, 10)); });
    });

    /* auto slide every 5s */
    setInterval(function() { if (imgs.length > 1) goSlide(cur + 1); }, 5000);

    /* ── Star picker for review form ─────────────────────────────── */
    var starPicker = document.getElementById('ws-star-picker');
    var ratingInput = document.getElementById('ws-review-rating');
    if (starPicker) {
        var pickStars = starPicker.querySelectorAll('.ws-rating-pick-star');
        pickStars.forEach(function(s) {
            s.addEventListener('mouseover', function() {
                var val = parseInt(s.dataset.val, 10);
                pickStars.forEach(function(ps, i) {
                    ps.classList.toggle('lit', i < val);
                });
            });
            s.addEventListener('click', function() {
                var val = parseInt(s.dataset.val, 10);
                ratingInput.value = val;
                pickStars.forEach(function(ps, i) {
                    ps.classList.toggle('lit', i < val);
                });
            });
        });
        starPicker.addEventListener('mouseleave', function() {
            var sel = parseInt(ratingInput.value, 10);
            pickStars.forEach(function(ps, i) {
                ps.classList.toggle('lit', i < sel);
            });
        });
    }

    /* ── Review form submit ──────────────────────────────────────── */
    window.wsSubmitReview = function(e) {
        e.preventDefault();
        var name   = document.getElementById('ws-rev-name').value.trim();
        var email  = document.getElementById('ws-rev-email').value.trim();
        var body   = document.getElementById('ws-rev-body').value.trim();
        var rating = parseInt(document.getElementById('ws-review-rating').value, 10);
        var msg    = document.getElementById('ws-review-msg');

        if (!name || !body) {
            msg.style.display = 'block';
            msg.style.background = '#fef2f2'; msg.style.border = '1px solid #fecaca'; msg.style.color = '#dc2626';
            msg.textContent = 'Please fill in your name and review content.';
            return false;
        }
        if (rating < 1) {
            msg.style.display = 'block';
            msg.style.background = '#fef2f2'; msg.style.border = '1px solid #fecaca'; msg.style.color = '#dc2626';
            msg.textContent = 'Please select a star rating.';
            return false;
        }

        var fd = new FormData();
        fd.append('action', 'bacera_submit_review');
        fd.append('workshop_id', <?php echo $workshop_id; ?>);
        fd.append('author_name', name);
        fd.append('author_email', email);
        fd.append('review_text', body);
        fd.append('rating', rating);
        fd.append('_ajax_nonce', '<?php echo wp_create_nonce('bacera_review_nonce'); ?>');

        var btn = document.querySelector('#ws-review-form .ws-btn-submit');
        btn.disabled = true; btn.textContent = 'Sending...';

        fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                msg.style.display = 'block';
                if (data.success) {
                    msg.style.background='#f0fdf4';msg.style.border='1px solid #bbf7d0';msg.style.color='#16a34a';
                    msg.textContent = 'Cảm ơn bạn! Đánh giá sẽ được xét duyệt trước khi hiển thị.';
                    document.getElementById('ws-review-form').reset();
                    document.getElementById('ws-review-rating').value = 0;
                } else {
                    msg.style.background='#fef2f2';msg.style.border='1px solid #fecaca';msg.style.color='#dc2626';
                    msg.textContent = (data.data && data.data.message) || 'Có lỗi xảy ra, vui lòng thử lại.';
                }
            })
            .catch(function() {
                msg.style.display = 'block';
                msg.style.background='#fef2f2';msg.style.border='1px solid #fecaca';msg.style.color='#dc2626';
                msg.textContent = 'Unable to connect. Please try again.';
            })
            .finally(function() { btn.disabled = false; btn.textContent = 'Submit review'; });
        return false;
    };

    /* ════════════════════════════════════════════════════════════
       BOOKING WIDGET JS (unchanged logic, adapted selectors)
    ════════════════════════════════════════════════════════════ */
    var AJAX_URL    = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
    var NONCE       = '<?php echo wp_create_nonce('bacera_booking_nonce'); ?>';
    var WORKSHOP_ID = <?php echo $workshop_id; ?>;
    var REDIRECT    = '<?php echo esc_js($redirect_url); ?>';

    var state = {
        slotId: 0, slotLabel: '', slotPrice: 0, totalSeats: 0,
        bookedSeats: [], selectedSeats: [],
        promoCode: '', discountAmt: 0,
        paymentMethod: '<?php echo !empty($payment_methods) ? esc_js($payment_methods[0]['code']) : ''; ?>',
        submitting: false
    };

    function fmt(n) {
        if (!n) return 'Contact us';
        return n.toLocaleString('vi-VN') + 'đ';
    }

    function bwksUpdateTotal() {
        var total = state.slotPrice * state.selectedSeats.length;
        var disEl = document.getElementById('bwks-sum-discount');
        if (state.discountAmt > 0) {
            total = Math.max(0, total - state.discountAmt);
            if (disEl) {
                disEl.innerHTML = '<span class="ws-sumlabel">Giảm giá</span><span class="ws-sumvalue" style="color:#16a34a">-' + fmt(state.discountAmt) + '</span>';
                disEl.style.display = 'flex';
            }
        } else {
            if (disEl) { disEl.innerHTML = ''; disEl.style.display = 'none'; }
        }
        var totalEl = document.getElementById('bwks-sum-total');
        if (totalEl) totalEl.textContent = total > 0 ? fmt(total) : (total === 0 && state.selectedSeats.length > 0 ? '0đ' : 'Contact us');
    }

    window.bwksApplyPromo = function() {
        var input = document.getElementById('bwks-promo-input');
        var msgEl = document.getElementById('bwks-promo-msg');
        if (!input || !msgEl) return;
        var code = input.value.trim().toUpperCase();
        if (!code) { msgEl.style.display='none'; state.promoCode=''; state.discountAmt=0; bwksUpdateTotal(); return; }
        msgEl.style.display = 'block';
        msgEl.className = 'promo-result';
        msgEl.textContent = 'Checking...';
        var fd = new FormData();
        fd.append('action','bacera_check_promo_code');
        fd.append('_ajax_nonce',NONCE);
        fd.append('workshop_id',WORKSHOP_ID);
        fd.append('code',code);
        fd.append('amount', state.slotPrice * state.selectedSeats.length);
        fetch(AJAX_URL,{method:'POST',body:fd})
            .then(function(r){return r.json();})
            .then(function(data){
                if (data.success && data.data && data.data.valid) {
                    state.promoCode=code; state.discountAmt=parseInt(data.data.discount_amount,10)||0;
                    msgEl.className='promo-result ok'; msgEl.textContent='Đã áp dụng giảm '+fmt(state.discountAmt);
                } else {
                    state.promoCode=''; state.discountAmt=0;
                    msgEl.className='promo-result err'; msgEl.textContent=(data.data&&data.data.message)||'Mã không hợp lệ.';
                }
                bwksUpdateTotal();
            });
    };

    window.bwksSelectPayment = function(radio) {
        document.querySelectorAll('.pay-method-card').forEach(function(c){c.classList.remove('active');});
        radio.closest('.pay-method-card').classList.add('active');
        state.paymentMethod = radio.value;
    };

    window.bwksGoStep = function(step) {
        ['bwks-step1','bwks-step2','bwks-step3'].forEach(function(id){
            var el=document.getElementById(id);
            if(el) el.classList.remove('active');
        });
        var target = document.getElementById('bwks-step'+step);
        if (target) target.classList.add('active');
        if (step === 2) {
            var s2btn = document.getElementById('bwks-btn-step2');
            if (s2btn) s2btn.classList.toggle('active', true);
        }
        if (step === 3) {
            document.getElementById('bwks-sum-date').textContent  = state.slotLabel;
            document.getElementById('bwks-sum-seats').textContent = state.selectedSeats.join(', ');
            document.getElementById('bwks-sum-qty').textContent   = state.selectedSeats.length + ' seat(s)';
            document.getElementById('bwks-sum-unit').textContent  = state.slotPrice ? fmt(state.slotPrice) : 'Contact us';
            bwksUpdateTotal();
        }
    };

    window.bwksSelectSlot = function(btn) {
        document.querySelectorAll('.ws-slot-btn').forEach(function(b){b.classList.remove('sel');});
        btn.classList.add('sel');
        state.slotId    = parseInt(btn.dataset.slotId, 10);
        state.slotLabel = btn.dataset.label;
        state.slotPrice = parseInt(btn.dataset.price, 10) || 0;
        state.totalSeats= parseInt(btn.dataset.total, 10);
        state.selectedSeats = [];
        var hdr = document.getElementById('bwks-header-price');
        if (hdr && state.slotPrice > 0) hdr.innerHTML = fmt(state.slotPrice) + '<span>/Person</span>';
        var step2btn = document.getElementById('bwks-btn-step2');
        if (step2btn) { step2btn.disabled = false; step2btn.classList.add('active'); }
        bwksLoadSeats();
    };

    function bwksLoadSeats() {
        var loadEl=document.getElementById('bwks-seats-loading');
        var areaEl=document.getElementById('bwks-seats-area');
        if(loadEl) loadEl.style.display='block';
        if(areaEl) areaEl.style.display='none';
        var fd=new FormData();
        fd.append('action','bacera_get_slot_seats');
        fd.append('slot_id',state.slotId);
        fetch(AJAX_URL,{method:'POST',body:fd})
            .then(function(r){return r.json();})
            .then(function(data){
                if(data.success){
                    state.bookedSeats=data.data.booked||[];
                    state.totalSeats=data.data.total_seats||state.totalSeats;
                }
                if(loadEl) loadEl.style.display='none';
                if(areaEl) areaEl.style.display='';
                bwksRenderSeats();
            })
            .catch(function(){
                if(loadEl) loadEl.style.display='none';
                if(areaEl) areaEl.style.display='';
                bwksRenderSeats();
            });
    }

    function bwksRenderSeats() {
        var grid=document.getElementById('bwks-seat-grid');
        if(!grid) return;
        grid.innerHTML='';
        var cols=Math.ceil(Math.sqrt(state.totalSeats));
        if (cols < 4) cols = 4;
        grid.style.gridTemplateColumns='repeat('+cols+',1fr)';
        for(var i=1;i<=state.totalSeats;i++){
            (function(sn){
                var btn=document.createElement('button');
                btn.type='button';
                btn.className='ws-seat '+(state.bookedSeats.includes(sn)?'booked':state.selectedSeats.includes(sn)?'selected':'empty');
                btn.textContent=sn;
                btn.disabled=state.bookedSeats.includes(sn);
                btn.id='seat-'+sn;
                btn.addEventListener('click',function(){bwksToggleSeat(sn);});
                grid.appendChild(btn);
            })(i);
        }
        bwksUpdateSeatUI();
    }

    function bwksToggleSeat(n) {
        if(state.bookedSeats.includes(n)) return;
        var idx=state.selectedSeats.indexOf(n);
        if(idx>-1){state.selectedSeats.splice(idx,1);}else{state.selectedSeats.push(n);}
        bwksUpdateSeatUI();
    }

    function bwksUpdateSeatUI() {
        for(var i=1;i<=state.totalSeats;i++){
            var btn=document.getElementById('seat-'+i);
            if(!btn) continue;
            btn.className='ws-seat '+(state.bookedSeats.includes(i)?'booked':state.selectedSeats.includes(i)?'selected':'empty');
        }
        var count=state.selectedSeats.length;
        var countEl=document.getElementById('bwks-sel-count');
        if(countEl) countEl.textContent=count;
        var cntLabel=document.getElementById('bwks-seat-count');
        if(cntLabel) cntLabel.textContent=count;
        var btn3=document.getElementById('bwks-btn-step3');
        if(btn3){ btn3.disabled=count===0; btn3.classList.toggle('active',count>0); }
        var priceBar=document.getElementById('bwks-price-live');
        var priceVal=document.getElementById('bwks-price-live-val');
        if(priceBar&&priceVal){
            if(count>0&&state.slotPrice>0){ priceBar.classList.add('show'); priceVal.textContent=fmt(state.slotPrice*count); }
            else { priceBar.classList.remove('show'); }
        }
    }

    window.bwksSubmit = function() {
        if(state.submitting||!state.selectedSeats.length||!state.slotId) return;
        var errEl=document.getElementById('bwks-err');
        if(errEl) errEl.style.display='none';
        var btn=document.getElementById('bwks-confirm-btn');
        if(btn){btn.disabled=true;btn.textContent='Đang xử lý...';}
        state.submitting=true;
        var fd=new FormData();
        fd.append('action','bacera_book_workshop');
        fd.append('_ajax_nonce',NONCE);
        fd.append('workshop_id',WORKSHOP_ID);
        fd.append('slot_id',state.slotId);
        fd.append('seats',state.selectedSeats.join(','));
        fd.append('promo_code',state.promoCode);
        fd.append('payment',state.paymentMethod);
        fd.append('notes',document.getElementById('bwks-notes').value);
        fetch(AJAX_URL,{method:'POST',body:fd})
            .then(function(r){return r.json();})
            .then(function(data){
                state.submitting=false;
                if(btn){btn.disabled=false;btn.textContent='Confirm booking';}
                if(data.success){
                    ['bwks-step1','bwks-step2','bwks-step3'].forEach(function(id){
                        var el=document.getElementById(id);if(el)el.classList.remove('active');
                    });
                    var successEl=document.getElementById('bwks-success');
                    if(successEl) successEl.style.display='flex';
                    var msgEl=document.getElementById('bwks-success-msg');
                    if(msgEl) msgEl.textContent=(data.data&&data.data.message)||'Booking confirmed!';
                    if(data.data&&data.data.payment&&data.data.payment.instructions){
                        var pay=data.data.payment;
                        var ex=document.getElementById('bwks-pay-inst');if(ex)ex.remove();
                        var instHtml='<div id="bwks-pay-inst" style="margin-top:16px;text-align:left;background:#f9f8f6;border-radius:12px;padding:16px;border:1px solid #e7e5e4;width:100%;box-sizing:border-box;">' +
                                     '<h4 style="margin:0 0 10px;font-size:14px;font-weight:700;color:#1c1917">Cần thanh toán qua '+pay.name+'</h4>' +
                                     '<div style="font-size:13px;line-height:1.6;white-space:pre-wrap;color:#57534e">'+pay.instructions+'</div></div>';
                        if(msgEl) msgEl.insertAdjacentHTML('afterend',instHtml);
                    }
                } else {
                    var code=data.data&&data.data.code;
                    if(code==='login_required'){window.location.href=REDIRECT;return;}
                    var msg=(data.data&&data.data.message)||'Có lỗi xảy ra.';
                    if(errEl){errEl.textContent=msg;errEl.style.display='block';}
                }
            })
            .catch(function(){
                state.submitting=false;
                if(btn){btn.disabled=false;btn.textContent='Confirm booking';}
                if(errEl){errEl.textContent='Unable to connect. Please try again.';errEl.style.display='block';}
            });
    };

    window.bwksReset = function() {
        state={slotId:0,slotLabel:'',slotPrice:0,totalSeats:0,bookedSeats:[],selectedSeats:[],submitting:false};
        document.querySelectorAll('.ws-slot-btn').forEach(function(b){b.classList.remove('sel');});
        var sEl=document.getElementById('bwks-success');if(sEl)sEl.style.display='none';
        var s2=document.getElementById('bwks-btn-step2');if(s2){s2.disabled=true;s2.classList.remove('active');}
        var payInst=document.getElementById('bwks-pay-inst');if(payInst)payInst.remove();
        bwksGoStep(1);
    };
})();
</script>

<?php
endwhile; endif;
get_footer();
?>
