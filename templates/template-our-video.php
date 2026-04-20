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

// ── Load categories (active only) ─────────────────────────────────────────────
$cats = $wpdb->get_results(
    "SELECT * FROM {$tc} WHERE is_active = 1 ORDER BY order_index ASC, id ASC",
    ARRAY_A
) ?: [];

// ── Load all active videos with category names ─────────────────────────────────
$videos = $wpdb->get_results(
    "SELECT v.*, c.name as cat_name, c.slug as cat_slug
     FROM {$tv} v
     LEFT JOIN {$tc} c ON v.category_id = c.id
     WHERE v.is_active = 1
     ORDER BY v.order_index ASC, v.id ASC",
    ARRAY_A
) ?: [];

// Active tab from URL
$active_cat = sanitize_key( $_GET['cat'] ?? '' );
if ( ! $active_cat && ! empty( $cats ) ) {
    $active_cat = $cats[0]['slug'];
}
?>

<style>
.divider-art {
    background-image: linear-gradient(to right, #D0BCA0 50%, transparent 50%);
    background-size: 10px 1px; background-repeat: repeat-x;
}

/* ── Category tabs ── */
.vt-tabs { display:flex; align-items:center; gap:0; border-bottom: 1px solid #EAE3D1; flex-wrap:wrap; }
.vt-tab {
    padding: 10px 20px; font-size:13px; font-weight:600;
    color:#6b5344; cursor:pointer; border:none; background:none;
    border-bottom: 2px solid transparent; margin-bottom:-1px;
    transition: all .2s; white-space:nowrap; font-family:inherit;
    display:flex; align-items:center; gap:6px;
}
.vt-tab:hover { color:#3d2f26; }
.vt-tab.active { color:#d95f47; border-bottom-color:#d95f47; }
.vt-tab-count {
    font-size:10px; font-weight:700;
    background:#f2ede6; color:#8d6a54;
    padding:2px 7px; border-radius:20px;
    transition:all .2s;
}
.vt-tab.active .vt-tab-count { background:#fff0ed; color:#d95f47; }

/* ── Video card ── */
.vt-card { display:flex; flex-direction:column; cursor:pointer; }
.vt-card-thumb {
    position:relative; aspect-ratio:16/9; border-radius:14px;
    overflow:hidden; background:#2c2420; margin-bottom:14px;
}
.vt-card-thumb img {
    width:100%; height:100%; object-fit:cover;
    transition: transform .7s ease;
}
.vt-card:hover .vt-card-thumb img { transform:scale(1.06); }

/* Play button overlay */
.vt-play-overlay {
    position:absolute; inset:0;
    background:rgba(28,25,23,.25);
    display:flex; align-items:center; justify-content:center;
    transition: background .25s;
}
.vt-card:hover .vt-play-overlay { background:rgba(28,25,23,.5); }
.vt-play-btn {
    width:52px; height:52px; border-radius:50%;
    background:rgba(255,255,255,.92); backdrop-filter:blur(4px);
    display:flex; align-items:center; justify-content:center;
    transition:transform .25s;
}
.vt-card:hover .vt-play-btn { transform:scale(1.12); }
.vt-play-btn svg { width:18px; height:18px; fill:#3d2f26; margin-left:4px; }

/* Duration badge */
.vt-duration {
    position:absolute; bottom:8px; right:10px;
    background:rgba(28,25,23,.75); backdrop-filter:blur(4px);
    color:#fff; font-size:10px; font-weight:700;
    padding:3px 7px; border-radius:6px; letter-spacing:.03em;
}

/* Category tag */
.vt-card-cat {
    font-size:10px; font-weight:700; text-transform:uppercase;
    letter-spacing:.15em; color:#d95f47; margin-bottom:4px;
}
.vt-card-title {
    font-size:14px; font-weight:600; color:#3d2f26;
    line-height:1.4; margin-bottom:4px;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
}
.vt-card-desc {
    font-size:12px; color:#6b5344; line-height:1.6;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
}

/* ── Fade animations ── */
@keyframes fadeUp { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }
.vt-card { animation:fadeUp .5s ease both; }

/* ── Lightbox ── */
#vt-lb {
    position:fixed; inset:0; z-index:9999;
    background:rgba(15,12,10,.88); backdrop-filter:blur(12px);
    display:none; align-items:center; justify-content:center;
    padding:24px;
}
#vt-lb.open { display:flex; }
.vt-lb-inner {
    position:relative; width:100%; max-width:900px;
    display:flex; flex-direction:column; gap:16px;
}
.vt-lb-close {
    position:absolute; top:-48px; right:0;
    background:rgba(255,255,255,.12); border:none; border-radius:50%;
    width:38px; height:38px; display:flex; align-items:center; justify-content:center;
    cursor:pointer; color:#fff; transition:background .15s;
}
.vt-lb-close:hover { background:rgba(255,255,255,.25); }
.vt-lb-close svg { width:16px; height:16px; stroke:currentColor; fill:none; stroke-width:2; }
.vt-lb-video {
    width:100%; aspect-ratio:16/9; border-radius:16px; overflow:hidden; background:#000;
}
.vt-lb-video video,
.vt-lb-video iframe { width:100%; height:100%; display:block; border:none; }
.vt-lb-info { text-align:center; color:#fff; }
.vt-lb-title { font-family:'Gowun Batang',serif; font-size:20px; font-weight:400; line-height:1.4; margin-bottom:6px; }
.vt-lb-desc  { font-size:13px; color:rgba(255,255,255,.6); line-height:1.6; max-width:600px; margin:0 auto; }

/* Nav arrows */
.vt-lb-nav {
    position:absolute; top:50%; transform:translateY(-50%);
    background:rgba(255,255,255,.12); border:none; border-radius:50%;
    width:44px; height:44px; display:flex; align-items:center; justify-content:center;
    cursor:pointer; color:#fff; transition:background .15s; z-index:10;
}
.vt-lb-nav:hover { background:rgba(255,255,255,.25); }
.vt-lb-nav svg { width:18px; height:18px; stroke:currentColor; fill:none; stroke-width:2; }
#vt-lb-prev { left:-60px; }
#vt-lb-next { right:-60px; }

/* No videos state */
.vt-empty { text-align:center; padding:80px 24px; }
.vt-empty svg { width:56px; height:56px; stroke:#c0a28e; fill:none; stroke-width:1; margin:0 auto 16px; display:block; opacity:.5; }

/* Page count pill */
.vt-count-pill {
    display:inline-flex; align-items:center; gap:6px;
    padding:4px 12px; border-radius:20px;
    background:#f2ede6; color:#8d6a54; font-size:12px; font-weight:600;
}

@media(max-width:640px) {
    #vt-lb-prev { left:-4px; }
    #vt-lb-next { right:-4px; }
    .vt-lb-nav { width:36px; height:36px; }
}
</style>

<div class="font-sans antialiased bg-texture text-textmain w-full overflow-hidden" style="padding-top:76px;">

    <!-- ── HEADER ── -->
    <div class="bacera-container pt-14 pb-8">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-textmuted mb-4 tracking-wide">
            <a href="<?php echo esc_url($home_url); ?>" class="text-textmain font-medium hover:text-terracotta transition-colors">Homepage</a>
            <span class="text-accent/60">/</span>
            <span>Our video</span>
        </nav>

        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
            <h1 class="font-serif text-5xl lg:text-6xl font-medium text-textmain leading-tight tracking-tight">
                Our <span class="italic text-terracotta">video</span>
            </h1>
            <p class="text-textmuted text-[15px] leading-relaxed max-w-sm">
                Crafted moments from the studio — techniques, stories, and ceramic journeys.
            </p>
        </div>

    </div>

    <!-- ── CATEGORY TABS ── -->
    <?php if ( ! empty( $cats ) ): ?>
    <div class="bacera-container mb-10">
        <div class="vt-tabs" id="vt-tabs" role="tablist">
            <?php
            // "All" tab
            $total_active = count( $videos );
            $show_all_tab = count( $cats ) > 1;
            if ( $show_all_tab ):
            ?>
            <button class="vt-tab <?php echo !$active_cat || $active_cat === 'all' ? 'active' : ''; ?>"
                    id="tab-all" data-slug="all" role="tab">
                About us
                <span class="vt-tab-count"><?php echo $total_active; ?></span>
            </button>
            <?php endif; ?>

            <?php foreach ( $cats as $cat ):
                $cat_vids = count( array_filter( $videos, fn($v) => $v['category_id'] == $cat['id'] ) );
            ?>
            <button class="vt-tab <?php echo $active_cat === $cat['slug'] ? 'active' : ''; ?>"
                    data-slug="<?php echo esc_attr( $cat['slug'] ); ?>"
                    data-catid="<?php echo esc_attr( $cat['id'] ); ?>"
                    role="tab">
                <?php echo esc_html( $cat['name'] ); ?>
                <span class="vt-tab-count"><?php echo $cat_vids; ?></span>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── VIDEO GRID ── -->
    <div class="bacera-container pb-20">

        <!-- Count + view info -->
        <div class="flex items-center justify-between mb-8">
            <span class="vt-count-pill" id="vt-count-label">
                <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><polygon points="6,4 14,8 6,12"/><line x1="2" y1="4" x2="2" y2="12"/></svg>
                <span id="vt-count-num"><?php echo count($videos); ?></span> videos
            </span>
        </div>

        <?php if ( empty( $videos ) ): ?>
        <!-- Empty state -->
        <div class="vt-empty">
            <svg viewBox="0 0 48 48"><rect x="4" y="8" width="40" height="32" rx="4"/><polygon points="19,18 33,24 19,30" fill="currentColor" opacity=".4"/></svg>
            <h3 class="font-serif text-2xl text-textmain mb-2">No videos yet</h3>
            <p class="text-textmuted text-[14px]">Check back soon — our video library is coming.</p>
        </div>
        <?php else: ?>

        <!-- Grid 2 columns (matches mockup) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 lg:gap-8" id="vt-grid">
            <?php foreach ( $videos as $idx => $v ):
                $thumb = $v['thumbnail_url'] ?: ( $v['type'] === 'youtube' && $v['youtube_id']
                    ? "https://img.youtube.com/vi/{$v['youtube_id']}/hqdefault.jpg"
                    : 'https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=800' );
                $delay = ($idx % 4) * 0.07;
            ?>
            <div class="vt-card"
                 style="animation-delay:<?php echo $delay; ?>s"
                 data-idx="<?php echo esc_attr( $idx ); ?>"
                 data-catid="<?php echo esc_attr( $v['category_id'] ?? 0 ); ?>"
                 data-type="<?php echo esc_attr( $v['type'] ); ?>"
                 data-videoid="<?php echo esc_attr( $v['id'] ); ?>">

                <div class="vt-card-thumb">
                    <img src="<?php echo esc_url( $thumb ); ?>"
                         alt="<?php echo esc_attr( $v['title'] ); ?>"
                         loading="lazy">

                    <!-- Play overlay -->
                    <div class="vt-play-overlay">
                        <?php if ( $idx === 0 ): ?>
                        <!-- First card: "Play video" label style -->
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="vt-play-btn">
                                <svg viewBox="0 0 16 16"><polygon points="4,2 14,8 4,14"/></svg>
                            </div>
                            <span style="color:#fff;font-size:13px;font-weight:600;letter-spacing:.05em;">Play video</span>
                        </div>
                        <?php else: ?>
                        <div class="vt-play-btn">
                            <svg viewBox="0 0 16 16"><polygon points="4,2 14,8 4,14"/></svg>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Duration -->
                    <?php if ( $v['duration'] ): ?>
                    <span class="vt-duration"><?php echo esc_html( $v['duration'] ); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Info -->
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

    </div>

    <!-- ── DIVIDER ── -->
    <div class="bacera-container mb-16">
        <div class="w-full h-[1px] divider-art opacity-40"></div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         SEO CONTENT BLOCK
    ═══════════════════════════════════════════════════════ -->
    <?php get_template_part('app/Views/components/seo-content', null, ['title' => 'Our Video']); ?>

    <!-- ── CTA ── -->
    <div class="bacera-container pb-20">
        <div class="bg-textmain rounded-2xl lg:rounded-3xl px-8 lg:px-16 py-14 flex flex-col lg:flex-row items-center justify-between gap-8 relative overflow-hidden">
            <div class="absolute -right-16 -top-16 w-48 h-48 rounded-full bg-accent/10"></div>
            <div class="absolute -left-8 -bottom-10 w-32 h-32 rounded-full bg-terracotta/10"></div>
            <div class="relative z-10 text-center lg:text-left">
                <p class="text-[10px] uppercase tracking-[0.35em] text-accent mb-3">Experience it live</p>
                <h2 class="font-serif text-3xl lg:text-4xl text-bgtheme leading-snug">
                    Join us in <span class="italic text-accent">the studio.</span>
                </h2>
                <p class="text-accent/60 text-[13px] mt-3">Book a workshop and learn the craft hands-on.</p>
            </div>
            <a href="<?php echo esc_url(home_url('/workshop/')); ?>"
               class="relative z-10 shrink-0 inline-flex items-center gap-3 text-xs uppercase tracking-[0.2em] text-textmain bg-bgtheme hover:bg-accent hover:text-white px-7 py-4 rounded-full transition-all duration-300 font-medium shadow-md">
                Explore Workshops
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>

</div><!-- /wrapper -->

<!-- ── LIGHTBOX ── -->
<div id="vt-lb" role="dialog" aria-modal="true" aria-label="Video player">
    <div class="vt-lb-inner">
        <button class="vt-lb-close" id="vt-lb-close" aria-label="Đóng">
            <svg viewBox="0 0 16 16"><path d="M2 2l12 12M14 2L2 14" stroke-linecap="round"/></svg>
        </button>
        <button class="vt-lb-nav" id="vt-lb-prev" aria-label="Video trước">
            <svg viewBox="0 0 20 20"><path d="M13 5L8 10l5 5" stroke-linecap="round"/></svg>
        </button>
        <button class="vt-lb-nav" id="vt-lb-next" aria-label="Video tiếp">
            <svg viewBox="0 0 20 20"><path d="M7 5l5 5-5 5" stroke-linecap="round"/></svg>
        </button>

        <div class="vt-lb-video" id="vt-lb-video"></div>
        <div class="vt-lb-info">
            <div class="vt-lb-title" id="vt-lb-title"></div>
            <div class="vt-lb-desc" id="vt-lb-desc"></div>
        </div>
    </div>
</div>

<!-- Pass PHP video data to JS -->
<script>
window._vtVideos = <?php
    $js_videos = array_values( array_map( function( $v ) {
        $thumb = $v['thumbnail_url'] ?: ( $v['type'] === 'youtube' && $v['youtube_id']
            ? "https://img.youtube.com/vi/{$v['youtube_id']}/hqdefault.jpg"
            : '' );
        return [
            'id'          => (int) $v['id'],
            'type'        => $v['type'],
            'title'       => $v['title'],
            'description' => $v['description'],
            'video_url'   => $v['video_url'],
            'youtube_id'  => $v['youtube_id'],
            'thumbnail'   => $thumb,
            'category_id' => (int) $v['category_id'],
            'cat_slug'    => $v['cat_slug'] ?? '',
        ];
    }, $videos ) );
    echo wp_json_encode( $js_videos );
?>;
</script>

<script>
(function(){
'use strict';

var videos = window._vtVideos || [];
var lb     = document.getElementById('vt-lb');
var lbVideo= document.getElementById('vt-lb-video');
var lbTitle= document.getElementById('vt-lb-title');
var lbDesc = document.getElementById('vt-lb-desc');
var lbPrev = document.getElementById('vt-lb-prev');
var lbNext = document.getElementById('vt-lb-next');
var countNum = document.getElementById('vt-count-num');

var currentIdx = 0;
var filteredVideos = videos.slice(); // copy

/* ── Category tab filtering ── */
var tabs = document.querySelectorAll('.vt-tab');
var cards = document.querySelectorAll('.vt-card');

tabs.forEach(function(tab) {
    tab.addEventListener('click', function() {
        tabs.forEach(function(t) { t.classList.remove('active'); });
        tab.classList.add('active');

        var slug = tab.getAttribute('data-slug');
        var catId = parseInt(tab.getAttribute('data-catid') || '0', 10);
        filteredVideos = [];

        var visible = 0;
        cards.forEach(function(card, i) {
            var cardCatId = parseInt(card.getAttribute('data-catid'), 10);
            var show = slug === 'all' || slug === '' || cardCatId === catId;
            card.style.display = show ? '' : 'none';
            if (show) {
                card.style.animationDelay = (visible % 4 * 0.07) + 's';
                filteredVideos.push(videos[i]);
                visible++;
            }
        });
        if (countNum) countNum.textContent = visible;

        // Update URL without reload
        var url = new URL(window.location);
        if (slug && slug !== 'all') url.searchParams.set('cat', slug);
        else url.searchParams.delete('cat');
        window.history.pushState({}, '', url);
    });
});

/* ── Build filtered list from initial active tab ── */
(function initFilter(){
    var activeTab = document.querySelector('.vt-tab.active');
    if (!activeTab) return;
    var slug  = activeTab.getAttribute('data-slug') || '';
    var catId = parseInt(activeTab.getAttribute('data-catid') || '0', 10);
    filteredVideos = [];
    var visible = 0;
    cards.forEach(function(card, i){
        var cardCatId = parseInt(card.getAttribute('data-catid') || '0', 10);
        var show = slug === 'all' || !slug || cardCatId === catId;
        card.style.display = show ? '' : 'none';
        if (show) { filteredVideos.push(videos[i]); visible++; }
    });
    if (countNum) countNum.textContent = visible;
})();

/* ── Open lightbox ── */
function openLightbox(vidxInFiltered) {
    currentIdx = vidxInFiltered;
    var v = filteredVideos[currentIdx];
    if (!v) return;

    lbTitle.textContent = v.title || '';
    lbDesc.textContent  = v.description || '';
    lbVideo.innerHTML   = '';

    if (v.type === 'youtube' && v.youtube_id) {
        var iframe = document.createElement('iframe');
        iframe.src = 'https://www.youtube.com/embed/' + v.youtube_id + '?autoplay=1&rel=0';
        iframe.allow = 'autoplay; fullscreen; picture-in-picture';
        iframe.allowFullscreen = true;
        lbVideo.appendChild(iframe);
    } else if (v.video_url) {
        var vid = document.createElement('video');
        vid.src = v.video_url;
        vid.controls = true;
        vid.autoplay  = true;
        vid.style.cssText = 'width:100%;height:100%;object-fit:contain;';
        lbVideo.appendChild(vid);
    }

    lbPrev.style.display = filteredVideos.length > 1 ? '' : 'none';
    lbNext.style.display = filteredVideos.length > 1 ? '' : 'none';

    lb.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    lb.classList.remove('open');
    lbVideo.innerHTML = '';
    document.body.style.overflow = '';
}

/* ── Card click ── */
cards.forEach(function(card) {
    card.addEventListener('click', function() {
        var idx = parseInt(card.getAttribute('data-idx'), 10);
        // Find position in filteredVideos
        var v = videos[idx];
        var fi = filteredVideos.indexOf(v);
        if (fi >= 0) openLightbox(fi);
        else openLightbox(0); // fallback
    });
});

/* ── Lightbox controls ── */
document.getElementById('vt-lb-close').addEventListener('click', closeLightbox);
lb.addEventListener('click', function(e){ if (e.target === lb) closeLightbox(); });

lbPrev.addEventListener('click', function(e){
    e.stopPropagation();
    currentIdx = (currentIdx - 1 + filteredVideos.length) % filteredVideos.length;
    openLightbox(currentIdx);
});
lbNext.addEventListener('click', function(e){
    e.stopPropagation();
    currentIdx = (currentIdx + 1) % filteredVideos.length;
    openLightbox(currentIdx);
});

document.addEventListener('keydown', function(e){
    if (!lb.classList.contains('open')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft')  { currentIdx=(currentIdx-1+filteredVideos.length)%filteredVideos.length; openLightbox(currentIdx); }
    if (e.key === 'ArrowRight') { currentIdx=(currentIdx+1)%filteredVideos.length; openLightbox(currentIdx); }
});

})();
</script>

<?php get_footer(); ?>
