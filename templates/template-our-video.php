<?php
/**
 * Template Name: Our Video
 * Description: Trang gallery video — tab danh mục, lightbox in-page, hỗ trợ Upload & YouTube
 */

get_header();

global $wpdb;
$tv  = $wpdb->prefix . 'bacera_videos';
$tc  = $wpdb->prefix . 'bacera_video_categories';

$home_url = home_url('/');

$cats = $wpdb->get_results(
    "SELECT * FROM {$tc} WHERE is_active = 1 ORDER BY order_index ASC, id ASC",
    ARRAY_A
) ?: [];

$videos = $wpdb->get_results(
    "SELECT v.*, c.name as cat_name, c.slug as cat_slug
     FROM {$tv} v
     LEFT JOIN {$tc} c ON v.category_id = c.id
     WHERE v.is_active = 1
     ORDER BY v.order_index ASC, v.id ASC",
    ARRAY_A
) ?: [];

$active_cat = sanitize_key( $_GET['cat'] ?? '' );
if ( ! $active_cat && ! empty( $cats ) ) {
    $active_cat = 'all';
}
?>

<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
    theme: {
        extend: {
            colors: {
                bgtheme:    '#f8f7f3',
                textmain:   '#3d2f26',
                textmuted:  '#6b5344',
                accent:     '#c0a28e',
                accentdark: '#8d6a54',
                terracotta: '#d95f47',
            },
            fontFamily: {
                serif: ['"Gowun Batang"', 'serif'],
                sans:  ['"Bricolage Grotesque"', 'sans-serif'],
            },
        },
    },
}
</script>

<style>
/* ── Base ── */
.vt-page { background:#F7F6F0; font-family:"Bricolage Grotesque",system-ui,sans-serif; }

/* ── Category pill tabs ── */
.vt-tab-wrap {
    display:flex; gap:6px; flex-wrap:wrap; align-items:center;
}
.vt-pill {
    position:relative; padding:7px 16px; border-radius:100px;
    font-size:12px; font-weight:600; cursor:pointer;
    border:1.5px solid #E2D8C8; background:transparent;
    color:#6b5344; transition:all .22s; white-space:nowrap;
    display:inline-flex; align-items:center; gap:5px; font-family:inherit;
}
.vt-pill:hover { border-color:#c0a28e; color:#3d2f26; background:#f0ebe3; }
.vt-pill.active {
    background:#3d2f26; border-color:#3d2f26; color:#fff;
}
.vt-pill-count {
    font-size:10px; font-weight:700; opacity:.65;
    background:rgba(255,255,255,.2); padding:1px 6px; border-radius:20px;
}
.vt-pill:not(.active) .vt-pill-count { background:rgba(61,47,38,.08); opacity:1; color:#6b5344; }

/* ── Featured card ── */
.vt-featured {
    position:relative; border-radius:20px; overflow:hidden;
    aspect-ratio:16/9; cursor:pointer;
    display:block; background:#1c1917;
}
.vt-featured img {
    width:100%; height:100%; object-fit:cover;
    transition:transform .8s cubic-bezier(.25,.46,.45,.94);
}
.vt-featured:hover img { transform:scale(1.04); }
.vt-featured-overlay {
    position:absolute; inset:0;
    background:linear-gradient(to top, rgba(12,9,7,.82) 0%, rgba(12,9,7,.2) 50%, transparent 100%);
}
.vt-featured-play {
    position:absolute; bottom:28px; left:28px; right:28px;
    display:flex; align-items:flex-end; gap:16px;
}
.vt-play-circle {
    flex-shrink:0; width:56px; height:56px; border-radius:50%;
    background:rgba(255,255,255,.95); backdrop-filter:blur(6px);
    display:flex; align-items:center; justify-content:center;
    transition:transform .25s, background .2s;
    box-shadow:0 4px 20px rgba(0,0,0,.3);
}
.vt-featured:hover .vt-play-circle { transform:scale(1.1); background:#fff; }
.vt-play-circle svg { width:20px; height:20px; fill:#3d2f26; margin-left:4px; }
.vt-featured-info { flex:1; min-width:0; }
.vt-featured-cat {
    font-size:10px; font-weight:700; text-transform:uppercase;
    letter-spacing:.18em; color:rgba(255,255,255,.55); margin-bottom:6px;
}
.vt-featured-title {
    font-family:"Gowun Batang",serif; font-size:22px; line-height:1.35;
    color:#fff; overflow:hidden; display:-webkit-box;
    -webkit-line-clamp:2; -webkit-box-orient:vertical;
}
.vt-featured-duration {
    position:absolute; top:16px; right:16px;
    background:rgba(0,0,0,.55); backdrop-filter:blur(8px);
    color:#fff; font-size:10px; font-weight:700;
    padding:4px 9px; border-radius:8px; letter-spacing:.04em;
}
.vt-featured-yt-badge {
    position:absolute; top:16px; left:16px;
    background:rgba(255,0,0,.85); color:#fff;
    font-size:9px; font-weight:800; letter-spacing:.08em;
    padding:3px 8px; border-radius:6px; text-transform:uppercase;
}

/* ── Video grid card ── */
.vt-card {
    display:flex; flex-direction:column; cursor:pointer;
    transition:transform .28s;
}
.vt-card:hover { transform:translateY(-3px); }

.vt-card-thumb {
    position:relative; aspect-ratio:16/9; border-radius:14px;
    overflow:hidden; background:#1c1917; margin-bottom:12px;
}
.vt-card-thumb img {
    width:100%; height:100%; object-fit:cover;
    transition:transform .7s cubic-bezier(.25,.46,.45,.94);
    display:block;
}
.vt-card:hover .vt-card-thumb img { transform:scale(1.07); }

.vt-card-overlay {
    position:absolute; inset:0;
    background:linear-gradient(to top, rgba(12,9,7,.6) 0%, transparent 55%);
    transition:background .3s;
}
.vt-card:hover .vt-card-overlay { background:linear-gradient(to top, rgba(12,9,7,.72) 0%, rgba(12,9,7,.15) 50%, transparent 100%); }

.vt-mini-play {
    position:absolute; top:50%; left:50%; transform:translate(-50%,-50%) scale(.85);
    width:44px; height:44px; border-radius:50%;
    background:rgba(255,255,255,.88); backdrop-filter:blur(4px);
    display:flex; align-items:center; justify-content:center;
    transition:transform .25s, opacity .25s;
    opacity:0;
}
.vt-card:hover .vt-mini-play { opacity:1; transform:translate(-50%,-50%) scale(1); }
.vt-mini-play svg { width:15px; height:15px; fill:#3d2f26; margin-left:3px; }

.vt-card-duration {
    position:absolute; bottom:8px; right:10px;
    background:rgba(0,0,0,.6); backdrop-filter:blur(6px);
    color:#fff; font-size:9px; font-weight:700;
    padding:3px 7px; border-radius:6px; letter-spacing:.04em;
}
.vt-card-yt-dot {
    position:absolute; top:8px; left:8px;
    width:6px; height:6px; border-radius:50%; background:#FF0000;
    box-shadow:0 0 0 2px rgba(255,255,255,.25);
}

.vt-card-cat {
    font-size:9px; font-weight:700; text-transform:uppercase;
    letter-spacing:.18em; color:#d95f47; margin-bottom:3px;
}
.vt-card-title {
    font-size:13px; font-weight:600; color:#3d2f26;
    line-height:1.45; overflow:hidden;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;
}
.vt-card-desc {
    font-size:11px; color:#6b5344; line-height:1.65; margin-top:4px;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
}

/* ── Card fade in ── */
@keyframes vtFadeUp {
    from { opacity:0; transform:translateY(14px); }
    to   { opacity:1; transform:translateY(0); }
}
.vt-card-anim { animation:vtFadeUp .45s ease both; }
.vt-featured-anim { animation:vtFadeUp .55s ease both; }

/* ── Tabs filter transition ── */
.vt-card, .vt-featured {
    transition:opacity .2s, transform .28s;
}
.vt-card.vt-hide, .vt-featured.vt-hide {
    opacity:0; pointer-events:none;
    animation:none; transform:translateY(6px);
}

/* ── Empty state ── */
.vt-empty { text-align:center; padding:80px 20px; }
.vt-empty svg { width:52px; height:52px; margin:0 auto 14px; display:block; opacity:.3; }

/* ── Lightbox ── */
@keyframes vtLbIn  { from{opacity:0;} to{opacity:1;} }
@keyframes vtLbOut { from{opacity:1;} to{opacity:0;} }
@keyframes vtPanelIn  { from{opacity:0;transform:scale(.97) translateY(12px);} to{opacity:1;transform:none;} }

#vt-lb {
    position:fixed; inset:0; z-index:9999;
    background:rgba(8,6,4,.93); backdrop-filter:blur(14px) saturate(.6);
    display:none; align-items:center; justify-content:center;
    padding:16px;
    animation:vtLbIn .22s ease;
}
#vt-lb.open { display:flex; }

.vt-lb-panel {
    position:relative; width:100%; max-width:1020px;
    display:grid; grid-template-columns:1fr;
    gap:0; border-radius:20px; overflow:hidden;
    box-shadow:0 32px 80px rgba(0,0,0,.5);
    animation:vtPanelIn .3s ease;
    background:#1a1512;
}
@media(min-width:900px){
    .vt-lb-panel { grid-template-columns:1fr 320px; }
}

/* Video area */
.vt-lb-video-wrap {
    position:relative; aspect-ratio:16/9;
    background:#000;
}
@media(min-width:900px){
    .vt-lb-video-wrap { aspect-ratio:unset; }
}
.vt-lb-video-wrap video,
.vt-lb-video-wrap iframe {
    width:100%; height:100%; display:block; border:none;
}

/* Sidebar */
.vt-lb-sidebar {
    padding:28px 24px;
    display:flex; flex-direction:column; gap:16px;
    border-left:1px solid rgba(255,255,255,.07);
    overflow-y:auto;
}
.vt-lb-sidebar-cat {
    font-size:9px; font-weight:800; text-transform:uppercase;
    letter-spacing:.2em; color:#d95f47;
}
.vt-lb-sidebar-title {
    font-family:"Gowun Batang",serif; font-size:18px; line-height:1.4; color:#fff; font-weight:400;
}
.vt-lb-sidebar-desc {
    font-size:12px; color:rgba(255,255,255,.5); line-height:1.7; flex:1;
}
.vt-lb-sidebar-divider { height:1px; background:rgba(255,255,255,.08); }

/* Dots */
.vt-lb-dots {
    display:flex; gap:5px; flex-wrap:wrap;
}
.vt-lb-dot {
    width:5px; height:5px; border-radius:50%;
    background:rgba(255,255,255,.2); cursor:pointer;
    transition:background .15s, transform .15s;
    flex-shrink:0;
}
.vt-lb-dot.active { background:#d95f47; transform:scale(1.4); }
.vt-lb-dot:hover:not(.active) { background:rgba(255,255,255,.5); }

/* Close & nav */
.vt-lb-close {
    position:absolute; top:12px; right:12px; z-index:10;
    background:rgba(255,255,255,.1); border:none; border-radius:50%;
    width:36px; height:36px; display:flex; align-items:center; justify-content:center;
    cursor:pointer; color:#fff; transition:background .15s;
}
.vt-lb-close:hover { background:rgba(255,255,255,.22); }
.vt-lb-close svg { width:14px; height:14px; stroke:currentColor; fill:none; stroke-width:2.2; stroke-linecap:round; }

.vt-lb-nav {
    position:absolute; top:50%; transform:translateY(-50%); z-index:10;
    background:rgba(255,255,255,.1); border:none; border-radius:50%;
    width:40px; height:40px; display:flex; align-items:center; justify-content:center;
    cursor:pointer; color:#fff; transition:background .15s;
}
.vt-lb-nav:hover { background:rgba(255,255,255,.22); }
.vt-lb-nav svg { width:16px; height:16px; stroke:currentColor; fill:none; stroke-width:2; stroke-linecap:round; }
#vt-lb-prev { left:10px; }
#vt-lb-next { right:10px; }

/* Progress bar */
.vt-lb-progress {
    position:absolute; bottom:0; left:0; right:0; height:2px;
    background:rgba(255,255,255,.1);
}
.vt-lb-progress-bar {
    height:100%; background:#d95f47;
    transition:width .35s ease;
}

/* Counter */
.vt-lb-counter {
    font-size:10px; font-weight:700; color:rgba(255,255,255,.35);
    letter-spacing:.1em;
}

@media(max-width:640px) {
    .vt-lb-sidebar { padding:18px; }
    .vt-lb-sidebar-title { font-size:15px; }
    #vt-lb-prev { left:6px; }
    #vt-lb-next { right:6px; }
}
</style>

<div class="vt-page text-textmain w-full overflow-hidden" style="padding-top:76px;">

    <!-- ── HEADER ── -->
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0 pt-14 pb-10">
        <nav class="flex items-center gap-2 text-xs text-textmuted mb-5 tracking-wide">
            <a href="<?php echo esc_url($home_url); ?>" class="text-textmain font-medium hover:text-terracotta transition-colors">Homepage</a>
            <span class="text-accent/50">/</span>
            <span>Our video</span>
        </nav>

        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
            <h1 class="font-serif text-5xl lg:text-6xl font-medium text-textmain leading-tight tracking-tight">
                Our <span class="italic text-terracotta">video</span>
            </h1>
            <p class="text-textmuted text-[14px] leading-relaxed max-w-xs lg:text-right">
                Crafted moments from the studio — techniques, stories, and ceramic journeys.
            </p>
        </div>
    </div>

    <!-- ── PILL TABS ── -->
    <?php
    $total_active = count( $videos );
    $show_all = count( $cats ) > 1;
    ?>
    <?php if ( ! empty( $cats ) ): ?>
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0 mb-10">
        <div class="vt-tab-wrap" id="vt-tabs" role="tablist">
            <?php if ( $show_all ): ?>
            <button class="vt-pill <?php echo $active_cat === 'all' || !$active_cat ? 'active' : ''; ?>"
                    data-slug="all" role="tab">
                All
                <span class="vt-pill-count"><?php echo $total_active; ?></span>
            </button>
            <?php endif; ?>

            <?php foreach ( $cats as $cat ):
                $cnt = count( array_filter( $videos, fn($v) => $v['category_id'] == $cat['id'] ) );
            ?>
            <button class="vt-pill <?php echo $active_cat === $cat['slug'] ? 'active' : ''; ?>"
                    data-slug="<?php echo esc_attr( $cat['slug'] ); ?>"
                    data-catid="<?php echo esc_attr( $cat['id'] ); ?>"
                    role="tab">
                <?php echo esc_html( $cat['name'] ); ?>
                <span class="vt-pill-count"><?php echo $cnt; ?></span>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── VIDEO CONTENT ── -->
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0 pb-20">

        <!-- Count line -->
        <div class="flex items-center justify-between mb-7">
            <div class="flex items-center gap-2 text-[11px] text-textmuted font-semibold tracking-wide">
                <svg width="11" height="11" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5,3 14,8 5,13"/><line x1="2" y1="3" x2="2" y2="13" stroke-linecap="round"/></svg>
                <span id="vt-count-num"><?php echo count($videos); ?></span> videos
            </div>
        </div>

        <?php if ( empty( $videos ) ): ?>
        <div class="vt-empty">
            <svg viewBox="0 0 48 48" fill="none" stroke="#c0a28e" stroke-width="1">
                <rect x="4" y="8" width="40" height="32" rx="4"/>
                <polygon points="19,18 33,24 19,30" fill="currentColor" opacity=".4"/>
            </svg>
            <h3 class="font-serif text-2xl text-textmain mb-2">No videos yet</h3>
            <p class="text-textmuted text-sm">Check back soon — our video library is coming.</p>
        </div>

        <?php else: ?>

        <!-- Featured card (always first visible) -->
        <?php
        $featured = $videos[0] ?? null;
        if ( $featured ):
            $fthumb = $featured['thumbnail_url'] ?: ( $featured['type'] === 'youtube' && $featured['youtube_id']
                ? "https://img.youtube.com/vi/{$featured['youtube_id']}/hqdefault.jpg"
                : 'https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=1400' );
        ?>
        <div class="vt-featured vt-featured-anim mb-8"
             id="vt-featured"
             data-idx="0"
             data-catid="<?php echo esc_attr( $featured['category_id'] ?? 0 ); ?>">
            <img src="<?php echo esc_url( $fthumb ); ?>" alt="<?php echo esc_attr( $featured['title'] ); ?>">
            <div class="vt-featured-overlay"></div>

            <?php if ( $featured['type'] === 'youtube' ): ?>
            <div class="vt-featured-yt-badge">YouTube</div>
            <?php endif; ?>

            <?php if ( $featured['duration'] ): ?>
            <div class="vt-featured-duration"><?php echo esc_html( $featured['duration'] ); ?></div>
            <?php endif; ?>

            <div class="vt-featured-play">
                <div class="vt-play-circle">
                    <svg viewBox="0 0 16 16"><polygon points="4,2 14,8 4,14"/></svg>
                </div>
                <div class="vt-featured-info">
                    <?php if ( $featured['cat_name'] ): ?>
                    <div class="vt-featured-cat"><?php echo esc_html( $featured['cat_name'] ); ?></div>
                    <?php endif; ?>
                    <div class="vt-featured-title"><?php echo esc_html( $featured['title'] ); ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Grid: remaining videos (3 col) -->
        <?php if ( count($videos) > 1 ): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 lg:gap-6" id="vt-grid">
            <?php foreach ( $videos as $idx => $v ):
                if ( $idx === 0 ) continue; // featured already shown
                $thumb = $v['thumbnail_url'] ?: ( $v['type'] === 'youtube' && $v['youtube_id']
                    ? "https://img.youtube.com/vi/{$v['youtube_id']}/hqdefault.jpg"
                    : 'https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=800' );
                $delay = (($idx-1) % 6) * 0.06;
            ?>
            <div class="vt-card vt-card-anim"
                 style="animation-delay:<?php echo $delay; ?>s"
                 data-idx="<?php echo esc_attr( $idx ); ?>"
                 data-catid="<?php echo esc_attr( $v['category_id'] ?? 0 ); ?>">

                <div class="vt-card-thumb">
                    <img src="<?php echo esc_url( $thumb ); ?>"
                         alt="<?php echo esc_attr( $v['title'] ); ?>"
                         loading="lazy">
                    <div class="vt-card-overlay"></div>
                    <div class="vt-mini-play">
                        <svg viewBox="0 0 16 16"><polygon points="4,2 14,8 4,14"/></svg>
                    </div>
                    <?php if ( $v['type'] === 'youtube' ): ?>
                    <div class="vt-card-yt-dot"></div>
                    <?php endif; ?>
                    <?php if ( $v['duration'] ): ?>
                    <div class="vt-card-duration"><?php echo esc_html( $v['duration'] ); ?></div>
                    <?php endif; ?>
                </div>

                <?php if ( $v['cat_name'] ): ?>
                <div class="vt-card-cat"><?php echo esc_html( $v['cat_name'] ); ?></div>
                <?php endif; ?>
                <div class="vt-card-title"><?php echo esc_html( $v['title'] ); ?></div>
                <?php if ( $v['description'] ): ?>
                <div class="vt-card-desc"><?php echo esc_html( $v['description'] ); ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

    </div>

    <!-- ── CTA BANNER ── -->
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0 pb-20">
        <div class="relative overflow-hidden rounded-2xl lg:rounded-3xl bg-textmain px-8 lg:px-16 py-14 flex flex-col lg:flex-row items-center justify-between gap-8">
            <!-- Decorative circles -->
            <div class="absolute -right-12 -top-12 w-48 h-48 rounded-full bg-accent/10 pointer-events-none"></div>
            <div class="absolute -left-6 -bottom-8 w-32 h-32 rounded-full bg-terracotta/10 pointer-events-none"></div>
            <!-- Subtle grain -->
            <div class="absolute inset-0 opacity-[.03] pointer-events-none" style="background-image:url('data:image/svg+xml,<svg viewBox=\"0 0 200 200\" xmlns=\"http://www.w3.org/2000/svg\"><filter id=\"n\"><feTurbulence type=\"fractalNoise\" baseFrequency=\"0.9\" numOctaves=\"4\" stitchTiles=\"stitch\"/></filter><rect width=\"100%\" height=\"100%\" filter=\"url(%23n)\"/></svg>');"></div>

            <div class="relative z-10 text-center lg:text-left">
                <p class="text-[9px] uppercase tracking-[0.4em] text-accent/80 mb-3 font-semibold">Experience it live</p>
                <h2 class="font-serif text-3xl lg:text-4xl text-bgtheme leading-snug">
                    Join us in <span class="italic text-accent">the studio.</span>
                </h2>
                <p class="text-accent/50 text-[13px] mt-3 max-w-xs">Book a workshop and learn the craft hands-on.</p>
            </div>

            <a href="<?php echo esc_url(home_url('/workshop/')); ?>"
               class="relative z-10 shrink-0 inline-flex items-center gap-3 text-[11px] uppercase tracking-[0.2em] font-semibold text-textmain bg-bgtheme hover:bg-accent hover:text-white px-8 py-4 rounded-full transition-all duration-300 shadow-lg">
                Explore Workshops
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>

</div><!-- /vt-page -->

<!-- ── LIGHTBOX ── -->
<div id="vt-lb" role="dialog" aria-modal="true" aria-label="Video player">
    <div class="vt-lb-panel">

        <!-- Video -->
        <div class="vt-lb-video-wrap" id="vt-lb-video">
            <!-- Content injected by JS -->
            <button class="vt-lb-close" id="vt-lb-close" aria-label="Đóng">
                <svg viewBox="0 0 16 16"><path d="M2 2l12 12M14 2L2 14"/></svg>
            </button>
            <button class="vt-lb-nav" id="vt-lb-prev" aria-label="Video trước" style="display:none;">
                <svg viewBox="0 0 20 20"><path d="M13 5L8 10l5 5" stroke-linecap="round"/></svg>
            </button>
            <button class="vt-lb-nav" id="vt-lb-next" aria-label="Video tiếp" style="display:none;">
                <svg viewBox="0 0 20 20"><path d="M7 5l5 5-5 5" stroke-linecap="round"/></svg>
            </button>
            <!-- Progress bar -->
            <div class="vt-lb-progress">
                <div class="vt-lb-progress-bar" id="vt-lb-bar" style="width:0%"></div>
            </div>
        </div>

        <!-- Sidebar info -->
        <div class="vt-lb-sidebar">
            <div>
                <div class="vt-lb-sidebar-cat" id="vt-lb-cat"></div>
                <div class="vt-lb-sidebar-title" id="vt-lb-title"></div>
            </div>
            <div class="vt-lb-sidebar-divider"></div>
            <div class="vt-lb-sidebar-desc" id="vt-lb-desc"></div>
            <div class="vt-lb-sidebar-divider"></div>
            <!-- Dots + counter -->
            <div>
                <div class="vt-lb-dots" id="vt-lb-dots"></div>
                <div class="vt-lb-counter mt-3" id="vt-lb-counter"></div>
            </div>
        </div>

    </div>
</div>

<!-- PHP data to JS -->
<script>
window._vtVideos = <?php
    $js_videos = array_values( array_map( function( $v ) {
        $thumb = $v['thumbnail_url'] ?: ( $v['type'] === 'youtube' && $v['youtube_id']
            ? "https://img.youtube.com/vi/{$v['youtube_id']}/hqdefault.jpg" : '' );
        return [
            'id'          => (int)$v['id'],
            'type'        => $v['type'],
            'title'       => $v['title'],
            'description' => $v['description'],
            'video_url'   => $v['video_url'],
            'youtube_id'  => $v['youtube_id'],
            'thumbnail'   => $thumb,
            'category_id' => (int)$v['category_id'],
            'cat_name'    => $v['cat_name'] ?? '',
            'cat_slug'    => $v['cat_slug'] ?? '',
            'duration'    => $v['duration'] ?? '',
        ];
    }, $videos ) );
    echo wp_json_encode( $js_videos );
?>;
</script>

<script>
(function(){
'use strict';

var allVideos = window._vtVideos || [];
var lb        = document.getElementById('vt-lb');
var lbVideo   = document.getElementById('vt-lb-video');
var lbTitle   = document.getElementById('vt-lb-title');
var lbDesc    = document.getElementById('vt-lb-desc');
var lbCat     = document.getElementById('vt-lb-cat');
var lbDots    = document.getElementById('vt-lb-dots');
var lbCounter = document.getElementById('vt-lb-counter');
var lbBar     = document.getElementById('vt-lb-bar');
var lbPrev    = document.getElementById('vt-lb-prev');
var lbNext    = document.getElementById('vt-lb-next');
var countNum  = document.getElementById('vt-count-num');

var currentIdx     = 0;
var filteredVideos = allVideos.slice();

/* ── render dots ── */
function renderDots(){
    if(!lbDots) return;
    lbDots.innerHTML='';
    var max = Math.min(filteredVideos.length, 12);
    for(var i=0;i<max;i++){
        var d=document.createElement('button');
        d.className='vt-lb-dot'+(i===currentIdx?' active':'');
        d.setAttribute('aria-label','Video '+(i+1));
        (function(idx){ d.addEventListener('click',function(){ openLightbox(idx); }); })(i);
        lbDots.appendChild(d);
    }
    if(filteredVideos.length>12){
        var more=document.createElement('span');
        more.style.cssText='font-size:10px;color:rgba(255,255,255,.3);align-self:center;margin-left:4px;';
        more.textContent='+';
        lbDots.appendChild(more);
    }
}

/* ── open lightbox ── */
function openLightbox(idx){
    currentIdx = idx;
    var v = filteredVideos[currentIdx];
    if(!v) return;

    /* remove old media */
    var oldMedia = lbVideo.querySelector('video, iframe');
    if(oldMedia) oldMedia.remove();

    /* fill sidebar */
    lbCat.textContent   = v.cat_name || '';
    lbTitle.textContent = v.title || '';
    lbDesc.textContent  = v.description || '';

    /* progress bar */
    var pct = filteredVideos.length > 1 ? (currentIdx / (filteredVideos.length-1)) * 100 : 100;
    if(lbBar) lbBar.style.width = pct + '%';

    /* counter */
    if(lbCounter) lbCounter.textContent = (currentIdx+1)+' / '+filteredVideos.length;

    /* dots */
    renderDots();
    var dots = lbDots ? lbDots.querySelectorAll('.vt-lb-dot') : [];
    dots.forEach(function(d,i){ d.classList.toggle('active', i===currentIdx); });

    /* media */
    if(v.type==='youtube' && v.youtube_id){
        var iframe=document.createElement('iframe');
        iframe.src='https://www.youtube.com/embed/'+v.youtube_id+'?autoplay=1&rel=0&modestbranding=1';
        iframe.allow='autoplay; fullscreen; picture-in-picture';
        iframe.allowFullscreen=true;
        iframe.style.cssText='width:100%;height:100%;border:none;display:block;';
        lbVideo.insertBefore(iframe, lbVideo.firstChild);
    } else if(v.video_url){
        var vid=document.createElement('video');
        vid.src=v.video_url; vid.controls=true; vid.autoplay=true;
        vid.style.cssText='width:100%;height:100%;object-fit:contain;display:block;';
        lbVideo.insertBefore(vid, lbVideo.firstChild);
    }

    /* nav */
    lbPrev.style.display = filteredVideos.length > 1 ? '' : 'none';
    lbNext.style.display = filteredVideos.length > 1 ? '' : 'none';

    lb.classList.add('open');
    document.body.style.overflow='hidden';
}

function closeLightbox(){
    var oldMedia = lbVideo.querySelector('video, iframe');
    if(oldMedia) oldMedia.remove();
    lb.classList.remove('open');
    document.body.style.overflow='';
}

/* ── Tab filtering ── */
var tabs    = document.querySelectorAll('.vt-pill');
var cards   = document.querySelectorAll('.vt-card');
var featured= document.getElementById('vt-featured');

function filterBySlug(slug, catId){
    filteredVideos = [];
    var visible = 0;

    /* featured */
    if(featured){
        var fCatId = parseInt(featured.getAttribute('data-catid')||'0',10);
        var fShow  = slug==='all' || catId===0 || fCatId===catId;
        if(fShow){
            featured.classList.remove('vt-hide');
            filteredVideos.push(allVideos[0]);
            visible++;
        } else {
            featured.classList.add('vt-hide');
        }
    }

    /* grid cards */
    cards.forEach(function(card,i){
        var realIdx = parseInt(card.getAttribute('data-idx'),10);
        var cCatId  = parseInt(card.getAttribute('data-catid')||'0',10);
        var show    = slug==='all' || catId===0 || cCatId===catId;
        if(show){
            card.classList.remove('vt-hide');
            /* re-trigger animation */
            card.style.animationDelay = (visible % 6 * 0.06)+'s';
            card.classList.remove('vt-card-anim');
            void card.offsetWidth; // reflow
            card.classList.add('vt-card-anim');
            filteredVideos.push(allVideos[realIdx]);
            visible++;
        } else {
            card.classList.add('vt-hide');
        }
    });

    if(countNum) countNum.textContent = visible;
}

tabs.forEach(function(tab){
    tab.addEventListener('click',function(){
        tabs.forEach(function(t){ t.classList.remove('active'); });
        tab.classList.add('active');
        var slug  = tab.getAttribute('data-slug')||'all';
        var catId = parseInt(tab.getAttribute('data-catid')||'0',10);
        filterBySlug(slug, catId);
        var url=new URL(window.location);
        if(slug && slug!=='all') url.searchParams.set('cat',slug);
        else url.searchParams.delete('cat');
        window.history.pushState({},'',url);
    });
});

/* init filter */
(function(){
    var at=document.querySelector('.vt-pill.active');
    if(!at) return;
    var slug=at.getAttribute('data-slug')||'all';
    var catId=parseInt(at.getAttribute('data-catid')||'0',10);
    filterBySlug(slug, catId);
})();

/* ── Card click ── */
if(featured){
    featured.addEventListener('click',function(){
        var fi = filteredVideos.indexOf(allVideos[0]);
        if(fi>=0) openLightbox(fi);
    });
}
cards.forEach(function(card){
    card.addEventListener('click',function(){
        var realIdx=parseInt(card.getAttribute('data-idx'),10);
        var v=allVideos[realIdx];
        var fi=filteredVideos.indexOf(v);
        if(fi>=0) openLightbox(fi);
        else openLightbox(0);
    });
});

/* ── Lightbox controls ── */
document.getElementById('vt-lb-close').addEventListener('click', closeLightbox);
lb.addEventListener('click',function(e){
    if(e.target===lb) closeLightbox();
});
lbPrev.addEventListener('click',function(e){
    e.stopPropagation();
    currentIdx=(currentIdx-1+filteredVideos.length)%filteredVideos.length;
    openLightbox(currentIdx);
});
lbNext.addEventListener('click',function(e){
    e.stopPropagation();
    currentIdx=(currentIdx+1)%filteredVideos.length;
    openLightbox(currentIdx);
});
document.addEventListener('keydown',function(e){
    if(!lb.classList.contains('open')) return;
    if(e.key==='Escape') closeLightbox();
    if(e.key==='ArrowLeft'){ currentIdx=(currentIdx-1+filteredVideos.length)%filteredVideos.length; openLightbox(currentIdx); }
    if(e.key==='ArrowRight'){ currentIdx=(currentIdx+1)%filteredVideos.length; openLightbox(currentIdx); }
});

})();
</script>

<?php get_footer(); ?>
