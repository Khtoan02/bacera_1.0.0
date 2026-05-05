<?php
/**
 * Template Name: Blog — Danh mục
 * Description: Trang danh mục bài viết — chạy qua route /blog/category/{slug}/
 *              Hỗ trợ AJAX in-page pagination, không reload trang.
 */

// ── Resolve category from query var or GET ────────────────────────────────────
$cat_slug = get_query_var('bacera_blog_cat_slug')
    ?: sanitize_key($_GET['cat_slug'] ?? '');

$cat_obj  = $cat_slug ? get_category_by_slug($cat_slug) : null;

// ── AJAX handler (wp_ajax_ / wp_ajax_nopriv_) hoạt động riêng,
//    nhưng ta cũng hỗ trợ request không-AJAX từ URL thẳng.
// ────────────────────────────────────────────────────────────────────────────

get_header();

$home_url = home_url('/');
$blog_url = home_url('/blog/');

// ── All categories for sidebar / filter ───────────────────────────────────────
$all_cats = get_categories(['hide_empty' => true, 'orderby' => 'name']);

// ── Category page base URL helper ─────────────────────────────────────────────
function bacera_cat_url(string $slug): string {
    return home_url('/blog/category/' . $slug . '/');
}
?>



<style>
.bg-texture {
    background-color: #F7F6F0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
}
.divider-art {
    background-image: linear-gradient(to right, #D0BCA0 50%, transparent 50%);
    background-size: 10px 1px;
    background-repeat: repeat-x;
}

/* Blog card */
.blog-card-img { transition: transform 0.7s ease; }
.blog-card:hover .blog-card-img { transform: scale(1.06); }

/* Category pills */
.cat-pill { transition: all .2s ease; cursor: pointer; }
.cat-pill.active { background: #3d2f26; color: #f8f7f3; border-color: #3d2f26; }
.cat-pill:not(.active):hover { background: #ede8e1; }

/* Loading skeleton shimmer */
@keyframes shimmer {
    0%   { background-position: -600px 0; }
    100% { background-position: 600px 0; }
}
.skeleton {
    background: linear-gradient(90deg, #e8e0d8 25%, #f0ebe5 50%, #e8e0d8 75%);
    background-size: 600px 100%;
    animation: shimmer 1.4s infinite linear;
    border-radius: 10px;
}

/* Fade in cards */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
}
.fade-up { animation: fadeUp 0.5s ease both; }

/* Sidebar category links */
.sidebar-cat { transition: all .18s ease; }
.sidebar-cat.active,
.sidebar-cat:hover { color: #d95f47; }
.sidebar-cat.active { font-weight: 600; }

/* Pagination dots */
.page-dot { transition: all .2s; }
.page-dot.active { background: #3d2f26; color: #f8f7f3; }
.page-dot:not(.active):hover { border-color: #3d2f26; color: #3d2f26; }
</style>

<?php
// ── Javascript data passthrough ────────────────────────────────────────────────
$ajax_url   = admin_url('admin-ajax.php');
$nonce      = wp_create_nonce('bacera_blog_cat_nonce');
$init_slug  = esc_js($cat_slug);
$init_name  = $cat_obj ? esc_js($cat_obj->name) : 'All posts';
$init_count = $cat_obj ? (int)$cat_obj->count : wp_count_posts('post')->publish;
$init_query = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) );
$init_page  = max( 1, (int) ( $_GET['page'] ?? 1 ) );
?>

<div class="font-sans antialiased bg-texture text-textmain w-full overflow-hidden" style="padding-top:76px;">

    <div class="bacera-container pt-14 pb-0">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-textmuted mb-4 tracking-wide" id="bc-nav">
            <a href="<?php echo esc_url($home_url); ?>" class="text-textmain font-medium hover:text-terracotta transition-colors">Homepage</a>
            <span class="text-accent/60">/</span>
            <a href="<?php echo esc_url($blog_url); ?>" class="hover:text-terracotta transition-colors">Blog</a>
            <span class="text-accent/60" id="bc-sep"><?php echo $cat_obj ? '/' : ''; ?></span>
            <span id="bc-cat"><?php echo $cat_obj ? esc_html($cat_obj->name) : ''; ?></span>
        </nav>

        <!-- Page title -->
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 mb-10">
            <div>
                <h1 class="font-serif text-5xl lg:text-6xl font-medium text-textmain leading-tight tracking-tight" id="page-title">
                    <?php if ($cat_obj): ?>
                        <?php echo esc_html($cat_obj->name); ?>
                    <?php else: ?>
                        Our <span class="italic text-terracotta">journal</span>
                    <?php endif; ?>
                </h1>
                <p class="text-textmuted text-[13px] mt-2" id="page-count">
                    <?php echo $init_count; ?> articles
                </p>
            </div>
            <form id="blog-search-form" class="w-full lg:w-[360px]">
                <div class="flex items-center h-[46px] rounded-xl border border-accent/30 bg-white px-3">
                    <input id="blog-search-input" type="search" name="q" value="<?php echo esc_attr( $init_query ); ?>" placeholder="Search blog posts..." class="flex-1 border-0 outline-none text-[14px] bg-transparent text-textmain" />
                    <button type="submit" class="w-8 h-8 rounded-lg text-textmuted hover:text-terracotta transition-colors" aria-label="Search blog">
                        <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                </div>
            </form>
        </div>

    </div>

    <!-- ═══ MAIN LAYOUT: Sidebar + Grid ═══ -->
    <div class="bacera-container pb-20">
        <div class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-10 lg:gap-14">

            <!-- ── SIDEBAR ── -->
            <aside class="lg:sticky lg:top-24 lg:self-start">

                <!-- All posts link -->
                <div class="mb-6">
                    <p class="text-[10px] uppercase tracking-[0.25em] text-accentdark font-semibold mb-3">Browse by topic</p>
                    <button
                        data-slug=""
                        data-name="All posts"
                        class="cat-pill sidebar-cat w-full text-left flex items-center justify-between px-4 py-2.5 rounded-xl border border-accent/30 text-[13px] font-medium text-textmuted <?php echo !$cat_obj ? 'active' : ''; ?>"
                    >
                        <span>All posts</span>
                        <span class="text-accent/60 text-[11px]"><?php echo (int)wp_count_posts('post')->publish; ?></span>
                    </button>
                </div>

                <!-- Categories list -->
                <div class="flex flex-col gap-1.5">
                    <?php foreach ($all_cats as $cat): ?>
                    <button
                        data-slug="<?php echo esc_attr($cat->slug); ?>"
                        data-name="<?php echo esc_attr($cat->name); ?>"
                        data-count="<?php echo (int)$cat->count; ?>"
                        class="cat-pill sidebar-cat text-left flex items-center justify-between px-4 py-2.5 rounded-xl border border-accent/30 text-[13px] font-medium text-textmuted <?php echo $cat_slug === $cat->slug ? 'active' : ''; ?>"
                    >
                        <span><?php echo esc_html($cat->name); ?></span>
                        <span class="text-accent/60 text-[11px]"><?php echo (int)$cat->count; ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>

                <!-- Divider -->
                <div class="w-full h-[1px] divider-art opacity-40 my-6"></div>

                <!-- Back to blog -->
                <a href="<?php echo esc_url($blog_url); ?>"
                   class="inline-flex items-center gap-2 text-[12px] uppercase tracking-wider font-semibold text-accentdark hover:text-terracotta transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to blog
                </a>
            </aside>

            <!-- ── POST GRID ── -->
            <section>

                <!-- Grid container — JS fills this -->
                <div id="posts-grid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 lg:gap-8 min-h-[400px]">
                    <!-- Skeleton placeholders on first load -->
                    <?php for ($s = 0; $s < 6; $s++): ?>
                    <div class="flex flex-col gap-4">
                        <div class="skeleton aspect-[4/3] rounded-xl"></div>
                        <div class="skeleton h-3 w-1/3 rounded-full"></div>
                        <div class="skeleton h-5 w-5/6 rounded-full"></div>
                        <div class="skeleton h-4 w-full rounded-full"></div>
                        <div class="skeleton h-4 w-4/5 rounded-full"></div>
                    </div>
                    <?php endfor; ?>
                </div>

                <!-- Empty state -->
                <div id="empty-state" class="hidden flex-col items-center justify-center py-24 text-center">
                    <div class="w-16 h-16 rounded-full bg-accent/10 flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <h3 class="font-serif text-2xl text-textmain mb-2">No posts yet</h3>
                    <p class="text-textmuted text-[14px]">Check back soon — our journal is coming.</p>
                </div>

                <!-- Pagination -->
                <nav id="pagination" class="hidden items-center justify-center gap-2 mt-12 flex-wrap"></nav>

            </section>

        </div>
    </div>

    <!-- ═══ CTA BANNER ═══ -->
    <div class="bacera-container pb-20">
        <div class="divider-art opacity-40 h-[1px] mb-12"></div>
        <div class="bg-textmain rounded-2xl lg:rounded-3xl px-8 lg:px-16 py-14 flex flex-col lg:flex-row items-center justify-between gap-8 relative overflow-hidden">
            <div class="absolute -right-16 -top-16 w-48 h-48 rounded-full bg-accent/10 pointer-events-none"></div>
            <div class="absolute -left-8 -bottom-10 w-32 h-32 rounded-full bg-terracotta/10 pointer-events-none"></div>
            <div class="relative z-10 text-center lg:text-left">
                <p class="text-[10px] uppercase tracking-[0.35em] text-accent mb-3">Stay inspired</p>
                <h2 class="font-serif text-3xl lg:text-4xl text-bgtheme leading-snug">
                    Stories from <span class="italic text-accent">the studio.</span>
                </h2>
                <p class="text-accent/60 text-[13px] mt-3">Craft insights, new collections, and workshop updates.</p>
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

<script>
(function() {
'use strict';

var AJAX_URL   = '<?php echo esc_js($ajax_url); ?>';
var NONCE      = '<?php echo esc_js($nonce); ?>';
var BLOG_BASE  = '<?php echo esc_js(home_url('/blog/')); ?>';
var CAT_BASE   = '<?php echo esc_js(home_url('/blog/category/')); ?>';
var PER_PAGE   = 9;

var state = {
    slug: '<?php echo $init_slug; ?>',
    name: '<?php echo $init_name; ?>',
    count: <?php echo $init_count; ?>,
    query: '<?php echo esc_js( $init_query ); ?>',
    page: <?php echo (int) $init_page; ?>,
    loading: false
};

var grid = document.getElementById('posts-grid');
var emptyEl = document.getElementById('empty-state');
var paginationEl = document.getElementById('pagination');
var pageTitle = document.getElementById('page-title');
var pageCount = document.getElementById('page-count');
var bcSep = document.getElementById('bc-sep');
var bcCat = document.getElementById('bc-cat');
var searchForm = document.getElementById('blog-search-form');
var searchInput = document.getElementById('blog-search-input');

function escHtml(str) {
    var d = document.createElement('div');
    d.textContent = String(str || '');
    return d.innerHTML;
}
function basePath() {
    return state.slug ? (CAT_BASE + state.slug + '/') : BLOG_BASE;
}
function buildUrl(targetPage) {
    var params = new URLSearchParams();
    if (state.query) params.set('q', state.query);
    if (targetPage > 1) params.set('page', String(targetPage));
    var qs = params.toString();
    return basePath() + (qs ? '?' + qs : '');
}
function updateMeta() {
    if (state.slug) {
        pageTitle.innerHTML = escHtml(state.name);
        bcSep.textContent = '/';
        bcCat.textContent = state.name;
    } else {
        pageTitle.innerHTML = 'Our <span class="italic text-terracotta">journal</span>';
        bcSep.textContent = '';
        bcCat.textContent = '';
    }
    pageCount.textContent = state.count + ' articles';
    document.title = (state.slug ? state.name + ' — ' : '') + (state.query ? '"' + state.query + '" — ' : '') + 'Blog — Bacera';
}
function syncActivePill() {
    document.querySelectorAll('.cat-pill').forEach(function(b) {
        b.classList.toggle('active', (b.getAttribute('data-slug') || '') === state.slug);
    });
}
function showSkeleton(count) {
    var html = '';
    for (var i = 0; i < (count || 6); i++) {
        html += '<div class="flex flex-col gap-4"><div class="skeleton aspect-[4/3] rounded-xl"></div><div class="skeleton h-3 w-1/3 rounded-full"></div><div class="skeleton h-5 w-5/6 rounded-full"></div><div class="skeleton h-4 w-full rounded-full"></div><div class="skeleton h-4 w-4/5 rounded-full"></div></div>';
    }
    grid.innerHTML = html;
    grid.classList.remove('hidden');
    emptyEl.classList.add('hidden');
    emptyEl.classList.remove('flex');
    paginationEl.classList.add('hidden');
    paginationEl.classList.remove('flex');
}
function renderPosts(posts) {
    if (!posts || !posts.length) {
        grid.innerHTML = '';
        grid.classList.add('hidden');
        emptyEl.classList.remove('hidden');
        emptyEl.classList.add('flex');
        return;
    }
    grid.classList.remove('hidden');
    emptyEl.classList.add('hidden');
    emptyEl.classList.remove('flex');

    var html = '';
    posts.forEach(function(p, i) {
        var delay = (i % 3) * 0.08;
        html += '<article class="blog-card group flex flex-col fade-up" style="animation-delay:' + delay + 's">' +
            '<a href="' + p.url + '" class="block overflow-hidden rounded-xl aspect-[4/3] bg-[#EBE7DF] mb-5 shadow-sm">' +
                '<img src="' + p.thumb + '" alt="' + escHtml(p.title) + '" class="blog-card-img w-full h-full object-cover" loading="lazy">' +
            '</a>' +
            '<div class="flex items-center gap-3 mb-3">' +
                (p.cat_name ? '<a href="' + escHtml(CAT_BASE + p.cat_slug + '/') + '" class="text-[10px] uppercase tracking-[0.25em] text-terracotta font-semibold hover:underline">' + escHtml(p.cat_name) + '</a><span class="w-1 h-1 rounded-full bg-accent/40"></span>' : '') +
                '<span class="text-[11px] text-textmuted">' + escHtml(p.date) + '</span>' +
            '</div>' +
            '<h2 class="text-[16px] font-semibold text-textmain leading-snug mb-2 group-hover:text-terracotta transition-colors"><a href="' + p.url + '">' + p.title + '</a></h2>' +
            '<p class="text-[13px] text-textmuted leading-relaxed line-clamp-3 mb-4 flex-1">' + escHtml(p.excerpt) + '</p>' +
            '<div class="flex items-center justify-between pt-4 border-t border-accent/20"><span class="text-[11px] text-textmuted/70">' + escHtml(p.author) + '</span>' +
            '<a href="' + p.url + '" class="inline-flex items-center gap-1.5 text-[11px] uppercase tracking-wider font-semibold text-accentdark hover:text-terracotta transition-colors">Read <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg></a></div>' +
            '</article>';
    });
    grid.innerHTML = html;
}
function renderPagination(total, current) {
    if (total <= 1) {
        paginationEl.classList.add('hidden');
        paginationEl.classList.remove('flex');
        return;
    }
    paginationEl.classList.remove('hidden');
    paginationEl.classList.add('flex');
    var html = '';
    for (var p = 1; p <= total; p++) {
        var cls = p === current ? 'active' : 'text-textmuted';
        html += '<button class="page-dot w-10 h-10 flex items-center justify-center rounded-full border border-accent/30 text-[13px] font-medium ' + cls + '" data-page="' + p + '">' + p + '</button>';
    }
    paginationEl.innerHTML = html;
    paginationEl.querySelectorAll('.page-dot').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var nextPage = parseInt(btn.getAttribute('data-page') || '1', 10);
            if (nextPage === state.page) return;
            state.page = nextPage;
            window.history.pushState({ slug: state.slug, page: state.page, query: state.query }, '', buildUrl(state.page));
            loadPosts();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
}
function loadPosts() {
    if (state.loading) return;
    state.loading = true;
    showSkeleton(PER_PAGE);
    var fd = new FormData();
    fd.append('action', 'bacera_get_blog_posts');
    fd.append('nonce', NONCE);
    fd.append('cat_slug', state.slug);
    fd.append('q', state.query || '');
    fd.append('page', state.page);
    fd.append('per_page', PER_PAGE);
    fetch(AJAX_URL, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            state.loading = false;
            if (!res || !res.success || !res.data) { renderPosts([]); return; }
            state.count = res.data.total || 0;
            updateMeta();
            renderPosts(res.data.posts || []);
            renderPagination(res.data.total_pages || 0, state.page);
        })
        .catch(function() {
            state.loading = false;
            renderPosts([]);
        });
}

document.querySelectorAll('.cat-pill').forEach(function(btn) {
    btn.addEventListener('click', function() {
        state.slug = btn.getAttribute('data-slug') || '';
        state.name = btn.getAttribute('data-name') || 'All posts';
        state.page = 1;
        syncActivePill();
        window.history.pushState({ slug: state.slug, page: 1, query: state.query }, '', buildUrl(1));
        loadPosts();
    });
});

if (searchForm) {
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        state.query = (searchInput && searchInput.value ? searchInput.value : '').trim();
        state.page = 1;
        window.history.pushState({ slug: state.slug, page: 1, query: state.query }, '', buildUrl(1));
        loadPosts();
    });
}

window.addEventListener('popstate', function(e) {
    if (!e.state) return;
    state.slug = e.state.slug || '';
    state.page = e.state.page || 1;
    state.query = e.state.query || '';
    if (searchInput) searchInput.value = state.query;
    syncActivePill();
    loadPosts();
});

syncActivePill();
updateMeta();
loadPosts();

})();
</script>

<?php get_footer(); ?>
