<?php
/**
 * Template Name: Blog Single
 * Description: Trang chi tiết bài viết — Bacera
 *
 * NOTE: Template này được gọi qua single.php hoặc route trực tiếp.
 * Nếu dùng làm Page Template thì tạo page rỗng và dùng get_queried_object().
 * WordPress sẽ tự chọn template này qua single.php pattern override nếu bạn
 * đặt file là single.php — hoặc dùng qua the_post() cho page template.
 */

// Hỗ trợ cả hai cách load: là single post thực (single.php) hoặc page template
if ( is_singular('post') ) {
    // single.php flow — post đã được setup bởi WP
    global $post;
    if ( !$post ) { get_404_template(); exit; }
    $post_obj = $post;
    setup_postdata($post);
} else {
    // fallback: page template — lấy post mới nhất
    $args_s   = ['post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 1];
    $q        = new WP_Query($args_s);
    if ( !$q->have_posts() ) { get_404_template(); exit; }
    $q->the_post();
    $post_obj = get_post();
}

get_header();

$blog_url    = get_post_type_archive_link('post') ?: home_url('/blog/');
$home_url    = home_url('/');
$post_id     = get_the_ID();
$post_url    = get_permalink($post_id);
$post_thumb  = get_the_post_thumbnail_url($post_id, 'full') ?: 'https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=1400';
$post_cats   = get_the_category($post_id);
$post_tags   = get_the_tags($post_id);
$post_date   = get_the_date('d M Y', $post_id);
$post_author = get_the_author();
$post_author_meta = get_the_author_meta('description');
$post_avatar = get_avatar_url(get_the_author_meta('email'), ['size' => 80]);
$read_time   = max(1, (int) round(str_word_count(strip_tags(get_the_content())) / 200));

// ── Related posts ──────────────────────────────────────────────────────────────
$related_args = [
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => 3,
    'post__not_in'        => [$post_id],
    'orderby'             => 'rand',
    'ignore_sticky_posts' => true,
];
if ($post_cats) {
    $related_args['category__in'] = wp_list_pluck($post_cats, 'term_id');
}
$related_query = new WP_Query($related_args);
?>

<!-- Tailwind -->
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
            typography: {
                DEFAULT: { css: { color: '#3d2f26' } }
            },
        },
    },
    plugins: [],
}
</script>

<style>
/* ───────────────────────────────────────────────────────
   BLOG SINGLE — CUSTOM STYLES
─────────────────────────────────────────────────────── */
.bg-texture {
    background-color: #F7F6F0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
}
.divider-art {
    background-image: linear-gradient(to right, #D0BCA0 50%, transparent 50%);
    background-size: 10px 1px;
    background-repeat: repeat-x;
}

/* ── Article body content ── */
.article-body { color: #3d2f26; line-height: 1.9; font-size: 16px; }
.article-body h1,.article-body h2,.article-body h3,.article-body h4 {
    font-family: 'Gowun Batang', serif;
    margin-top: 2em; margin-bottom: 0.6em;
    color: #3d2f26; line-height: 1.3;
}
.article-body h2 { font-size: 1.6rem; }
.article-body h3 { font-size: 1.3rem; }
.article-body p { margin-bottom: 1.4em; }
.article-body a { color: #d95f47; text-decoration: underline; }
.article-body a:hover { color: #c0533e; }
.article-body ul,.article-body ol { padding-left: 1.5em; margin-bottom: 1.4em; }
.article-body li { margin-bottom: 0.4em; }
.article-body blockquote {
    border-left: 3px solid #c0a28e;
    padding: 1rem 1.5rem;
    margin: 2rem 0;
    font-style: italic;
    background: rgba(192,162,142,.07);
    border-radius: 0 12px 12px 0;
    color: #6b5344;
}
.article-body img { border-radius: 12px; max-width: 100%; margin: 2em 0; }
.article-body figure { margin: 2em 0; }
.article-body figcaption { text-align: center; font-size: 12px; color: #a08070; margin-top: 0.5em; }
.article-body table { width: 100%; border-collapse: collapse; margin-bottom: 1.5em; }
.article-body th,.article-body td { border: 1px solid #e8e2d9; padding: 0.6em 0.9em; }
.article-body th { background: #f2ede6; font-weight: 600; }
.article-body code { background: #f2ede6; padding: 2px 6px; border-radius: 4px; font-size: 0.9em; }
.article-body pre { background: #2c2420; color: #f0ebe4; padding: 1.2em; border-radius: 10px; overflow-x: auto; margin-bottom: 1.5em; }
.article-body pre code { background: none; color: inherit; }
.article-body hr { border: none; border-top: 1px solid #ddd6ca; margin: 2.5em 0; }

/* ── Related card hover ── */
.rel-card-img { transition: transform 0.6s ease; }
.rel-card:hover .rel-card-img { transform: scale(1.05); }

/* ── Progress bar ── */
#read-progress {
    position: fixed; top: 76px; left: 0; height: 3px;
    background: linear-gradient(90deg, #d95f47, #c0a28e);
    z-index: 100; width: 0%; transition: width .1s linear;
    box-shadow: 0 0 6px rgba(217,95,71,.4);
}

/* ── TOC ── */
.toc-link { color: #6b5344; font-size: 13px; padding: 4px 0; display: block; transition: color .15s; }
.toc-link:hover,.toc-link.active { color: #d95f47; }
.toc-link.h3 { padding-left: 14px; font-size: 12px; }

/* ── Back to top ── */
#back-top { transition: all .25s ease; opacity: 0; pointer-events: none; }
#back-top.show { opacity: 1; pointer-events: auto; }

@keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:translateY(0); } }
.fade-up { animation: fadeUp 0.5s ease both; }
</style>

<!-- Reading progress bar -->
<div id="read-progress"></div>

<div class="font-sans antialiased bg-texture text-textmain w-full overflow-hidden" style="padding-top:76px;">

<!-- ═══════════════════════════════════════════════════
     1. HERO / POST HEADER
═══════════════════════════════════════════════════ -->
<section class="relative w-full overflow-hidden mb-0">
    <!-- Thumbnail full-width with gradient overlay -->
    <div class="relative w-full aspect-[21/9] max-h-[540px] bg-textmain overflow-hidden">
        <img src="<?php echo esc_url($post_thumb); ?>"
             alt="<?php echo esc_attr(get_the_title()); ?>"
             class="absolute inset-0 w-full h-full object-cover opacity-60">
        <div class="absolute inset-0 bg-gradient-to-t from-textmain via-textmain/40 to-transparent"></div>

        <!-- Content overlay -->
        <div class="absolute inset-0 flex flex-col justify-end max-w-[800px] mx-auto px-4 sm:px-6 pb-10 lg:pb-14 w-full left-0 right-0">
            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-[11px] text-white/50 mb-5 tracking-wide">
                <a href="<?php echo esc_url($home_url); ?>" class="hover:text-white transition-colors">Homepage</a>
                <span>/</span>
                <a href="<?php echo esc_url($blog_url); ?>" class="hover:text-white transition-colors">Blog</a>
                <?php if ($post_cats): ?>
                <span>/</span>
                <a href="<?php echo esc_url(get_category_link($post_cats[0]->term_id)); ?>" class="hover:text-white transition-colors"><?php echo esc_html($post_cats[0]->name); ?></a>
                <?php endif; ?>
            </nav>

            <!-- Category badge -->
            <?php if ($post_cats): ?>
            <span class="inline-block text-[10px] uppercase tracking-[0.3em] text-accent mb-4">
                <?php echo esc_html($post_cats[0]->name); ?>
            </span>
            <?php endif; ?>

            <!-- Title -->
            <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl text-white leading-tight mb-5">
                <?php the_title(); ?>
            </h1>

            <!-- Meta row -->
            <div class="flex items-center gap-4 flex-wrap">
                <img src="<?php echo esc_url($post_avatar); ?>" alt="<?php echo esc_attr($post_author); ?>"
                     class="w-8 h-8 rounded-full object-cover border-2 border-white/30">
                <span class="text-white/80 text-[13px] font-medium"><?php echo esc_html($post_author); ?></span>
                <span class="text-white/30 text-lg leading-none">·</span>
                <span class="text-white/60 text-[12px]"><?php echo esc_html($post_date); ?></span>
                <span class="text-white/30 text-lg leading-none">·</span>
                <span class="text-white/60 text-[12px]"><?php echo $read_time; ?> min read</span>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     2. ARTICLE BODY
═══════════════════════════════════════════════════ -->
<div class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-10 py-14">
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-16" id="article-grid">

        <!-- Main content -->
        <article class="min-w-0 fade-up">

            <!-- Tags -->
            <?php if ($post_tags): ?>
            <div class="flex items-center gap-2 flex-wrap mb-8">
                <?php foreach ($post_tags as $tag): ?>
                <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>"
                   class="inline-block text-[11px] px-3 py-1 rounded-full border border-accent/30 text-textmuted hover:border-terracotta hover:text-terracotta transition-colors">
                    #<?php echo esc_html($tag->name); ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Content -->
            <div class="article-body" id="article-content">
                <?php the_content(); ?>
            </div>

            <!-- Divider -->
            <div class="w-full h-[1px] divider-art opacity-40 my-12"></div>

            <!-- Share -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <h4 class="text-[12px] uppercase tracking-[0.25em] text-accentdark font-semibold">Share this article</h4>
                <div class="flex items-center gap-3">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($post_url); ?>"
                       target="_blank" rel="noopener"
                       class="w-9 h-9 rounded-full border border-accent/30 flex items-center justify-center text-textmuted hover:border-[#337FFF] hover:text-[#337FFF] transition-colors">
                        <svg width="16" height="16" viewBox="0 0 93 92" fill="currentColor"><path d="M57.4 48.6L58.7 40.4H50.7V35C50.7 32.7 51.8 30.5 55.4 30.5H59.1V23.4C56.9 23.1 54.8 22.9 52.6 22.9C45.9 22.9 41.7 26.9 41.7 34V40.4H34.3V48.6H41.7V68.7H50.7V48.6H57.4Z"/></svg>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($post_url); ?>&text=<?php echo urlencode(get_the_title()); ?>"
                       target="_blank" rel="noopener"
                       class="w-9 h-9 rounded-full border border-accent/30 flex items-center justify-center text-textmuted hover:border-black hover:text-black transition-colors">
                        <svg width="16" height="14" viewBox="0 0 93 92" fill="currentColor"><path d="M50.8 42.2L69.4 21H65L48.8 39.4L35.9 21H21L40.5 48.8L21 71H25.4L42.5 51.6L56.1 71H71L50.8 42.2ZM44.7 49L42.7 46.3L27 24.2H33.8L46.5 42L48.5 44.8L65 67.9H58.2L44.7 49Z"/></svg>
                    </a>
                    <button onclick="navigator.clipboard.writeText('<?php echo esc_js($post_url); ?>').then(()=>{this.title='Đã copy!'})"
                            class="w-9 h-9 rounded-full border border-accent/30 flex items-center justify-center text-textmuted hover:border-accentdark hover:text-accentdark transition-colors"
                            title="Copy link">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Author bio -->
            <div class="flex items-start gap-5 mt-12 p-6 bg-white rounded-2xl border border-accent/20 shadow-sm">
                <img src="<?php echo esc_url($post_avatar); ?>" alt="<?php echo esc_attr($post_author); ?>"
                     class="w-14 h-14 rounded-full object-cover border-2 border-accent/20 shrink-0">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.2em] text-accent mb-1">Written by</p>
                    <h4 class="text-[15px] font-semibold text-textmain mb-2"><?php echo esc_html($post_author); ?></h4>
                    <?php if ($post_author_meta): ?>
                    <p class="text-[13px] text-textmuted leading-relaxed"><?php echo esc_html($post_author_meta); ?></p>
                    <?php else: ?>
                    <p class="text-[13px] text-textmuted leading-relaxed">Craftsperson &amp; storyteller at Bacera Studio.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Post navigation -->
            <div class="grid grid-cols-2 gap-4 mt-10">
                <?php
                $prev_post = get_previous_post();
                $next_post = get_next_post();
                ?>
                <?php if ($prev_post): ?>
                <a href="<?php echo esc_url(get_permalink($prev_post)); ?>"
                   class="group flex flex-col gap-1 p-5 bg-white rounded-xl border border-accent/20 hover:border-accentdark transition-colors">
                    <span class="text-[10px] uppercase tracking-wider text-accent flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Previous
                    </span>
                    <span class="text-[13px] font-medium text-textmain group-hover:text-terracotta transition-colors line-clamp-2 leading-snug">
                        <?php echo esc_html($prev_post->post_title); ?>
                    </span>
                </a>
                <?php else: ?>
                <div></div>
                <?php endif; ?>

                <?php if ($next_post): ?>
                <a href="<?php echo esc_url(get_permalink($next_post)); ?>"
                   class="group flex flex-col gap-1 p-5 bg-white rounded-xl border border-accent/20 hover:border-accentdark transition-colors text-right">
                    <span class="text-[10px] uppercase tracking-wider text-accent flex items-center gap-1 justify-end">
                        Next
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                    <span class="text-[13px] font-medium text-textmain group-hover:text-terracotta transition-colors line-clamp-2 leading-snug">
                        <?php echo esc_html($next_post->post_title); ?>
                    </span>
                </a>
                <?php endif; ?>
            </div>

            <!-- Comments -->
            <?php if (comments_open() || get_comments_number() > 0): ?>
            <div class="mt-12 pt-10 border-t border-accent/20">
                <?php comments_template(); ?>
            </div>
            <?php endif; ?>

        </article>

        <!-- ── SIDEBAR ── -->
        <aside class="hidden lg:block">
            <div class="sticky top-24 flex flex-col gap-8">

                <!-- Table of Contents -->
                <div class="bg-white rounded-2xl border border-accent/20 p-6 shadow-sm" id="toc-box">
                    <h4 class="text-[11px] uppercase tracking-[0.25em] text-accentdark font-semibold mb-4">In this article</h4>
                    <nav id="toc-nav" class="flex flex-col gap-1">
                        <!-- Filled by JS -->
                        <p class="text-[12px] text-accent/60 italic">Loading…</p>
                    </nav>
                </div>

                <!-- Categories -->
                <?php if ($post_cats): ?>
                <div class="bg-white rounded-2xl border border-accent/20 p-6 shadow-sm">
                    <h4 class="text-[11px] uppercase tracking-[0.25em] text-accentdark font-semibold mb-4">Categories</h4>
                    <div class="flex flex-col gap-2">
                        <?php foreach ($post_cats as $pcat): ?>
                        <a href="<?php echo esc_url(get_category_link($pcat->term_id)); ?>"
                           class="flex items-center justify-between text-[13px] text-textmuted hover:text-terracotta transition-colors group">
                            <span class="group-hover:underline"><?php echo esc_html($pcat->name); ?></span>
                            <span class="text-[11px] text-accent/60"><?php echo $pcat->count; ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Back to blog -->
                <a href="<?php echo esc_url($blog_url); ?>"
                   class="inline-flex items-center gap-2 text-[12px] uppercase tracking-wider font-semibold text-accentdark hover:text-terracotta transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to blog
                </a>
            </div>
        </aside>

    </div>
</div>

<!-- ═══════════════════════════════════════════════════
     3. RELATED POSTS
═══════════════════════════════════════════════════ -->
<?php if ($related_query->have_posts()): ?>
<section class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-10 pb-20">
    <div class="w-full h-[1px] divider-art opacity-40 mb-14"></div>
    <div class="flex items-center gap-4 mb-8">
        <span class="w-8 h-[1px] bg-accentdark"></span>
        <h2 class="text-xs uppercase tracking-[0.3em] text-accentdark font-semibold">You might also like</h2>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
        <?php while ($related_query->have_posts()): $related_query->the_post();
            $r_id     = get_the_ID();
            $r_url    = get_permalink();
            $r_thumb  = get_the_post_thumbnail_url($r_id, 'medium_large') ?: 'https://images.unsplash.com/photo-1530018607912-eff2daa1bac4?auto=format&fit=crop&q=80&w=600';
            $r_cats   = get_the_category($r_id);
            $r_date   = get_the_date('d M Y', $r_id);
            $r_exc    = get_the_excerpt() ?: wp_trim_words(strip_tags(get_the_content()), 18, '…');
        ?>
        <article class="rel-card group flex flex-col">
            <a href="<?php echo esc_url($r_url); ?>" class="block overflow-hidden rounded-xl aspect-[4/3] bg-[#EBE7DF] mb-4 shadow-sm">
                <img src="<?php echo esc_url($r_thumb); ?>"
                     alt="<?php echo esc_attr(get_the_title()); ?>"
                     class="rel-card-img w-full h-full object-cover">
            </a>
            <?php if ($r_cats): ?>
            <span class="text-[10px] uppercase tracking-[0.25em] text-terracotta font-semibold mb-2"><?php echo esc_html($r_cats[0]->name); ?></span>
            <?php endif; ?>
            <h3 class="text-[15px] font-semibold text-textmain leading-snug mb-2 group-hover:text-terracotta transition-colors">
                <a href="<?php echo esc_url($r_url); ?>"><?php the_title(); ?></a>
            </h3>
            <p class="text-[12px] text-textmuted leading-relaxed line-clamp-2 mb-3"><?php echo esc_html($r_exc); ?></p>
            <span class="text-[11px] text-textmuted/60 mt-auto"><?php echo esc_html($r_date); ?></span>
        </article>
        <?php endwhile; wp_reset_postdata(); ?>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════
     4. CTA FOOTER
═══════════════════════════════════════════════════ -->
<div class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-10 pb-20">
    <div class="bg-textmain rounded-2xl lg:rounded-3xl px-8 lg:px-16 py-14 flex flex-col lg:flex-row items-center justify-between gap-8 relative overflow-hidden">
        <div class="absolute -right-16 -top-16 w-48 h-48 rounded-full bg-accent/10 pointer-events-none"></div>
        <div class="absolute -left-8 -bottom-10 w-32 h-32 rounded-full bg-terracotta/10 pointer-events-none"></div>
        <div class="relative z-10 text-center lg:text-left">
            <p class="text-[10px] uppercase tracking-[0.35em] text-accent mb-3">Keep reading</p>
            <h2 class="font-serif text-3xl lg:text-4xl text-bgtheme leading-snug">
                More stories from <span class="italic text-accent">the studio.</span>
            </h2>
        </div>
        <a href="<?php echo esc_url($blog_url); ?>"
           class="relative z-10 shrink-0 inline-flex items-center gap-3 text-xs uppercase tracking-[0.2em] text-textmain bg-bgtheme hover:bg-accent hover:text-white px-7 py-4 rounded-full transition-all duration-300 font-medium shadow-md">
            All articles
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
            </svg>
        </a>
    </div>
</div>

<!-- Back to top -->
<button id="back-top"
        onclick="window.scrollTo({top:0,behavior:'smooth'})"
        class="fixed bottom-8 right-8 w-11 h-11 bg-textmain text-bgtheme rounded-full shadow-xl flex items-center justify-center hover:bg-accentdark transition-all z-50"
        aria-label="Back to top">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
    </svg>
</button>

</div><!-- /wrapper -->

<script>
(function() {
    /* ── Reading progress ── */
    const bar     = document.getElementById('read-progress');
    const content = document.getElementById('article-content');
    function updateProgress() {
        if (!content || !bar) return;
        const top    = content.getBoundingClientRect().top + window.scrollY - 76;
        const bottom = content.getBoundingClientRect().bottom + window.scrollY;
        const h      = bottom - top;
        const pct    = Math.min(100, Math.max(0, ((window.scrollY - top + window.innerHeight * 0.5) / h) * 100));
        bar.style.width = pct + '%';
    }
    window.addEventListener('scroll', updateProgress, { passive: true });

    /* ── Back to top ── */
    const backTop = document.getElementById('back-top');
    window.addEventListener('scroll', function() {
        if (backTop) backTop.classList.toggle('show', window.scrollY > 600);
    }, { passive: true });

    /* ── Table of Contents — auto-generated from h2/h3 ── */
    const tocNav  = document.getElementById('toc-nav');
    const tocBox  = document.getElementById('toc-box');
    const headings = content ? content.querySelectorAll('h2, h3') : [];

    if (tocNav && headings.length > 0) {
        tocNav.innerHTML = '';
        headings.forEach(function(h, i) {
            if (!h.id) h.id = 'heading-' + i;
            var a = document.createElement('a');
            a.href = '#' + h.id;
            a.textContent = h.textContent;
            a.className = 'toc-link ' + (h.tagName === 'H3' ? 'h3' : '');
            a.addEventListener('click', function(e) {
                e.preventDefault();
                var target = document.getElementById(h.id);
                if (target) {
                    window.scrollTo({ top: target.getBoundingClientRect().top + window.scrollY - 100, behavior: 'smooth' });
                }
            });
            tocNav.appendChild(a);
        });

        /* Highlight active heading on scroll */
        var tocLinks = tocNav.querySelectorAll('.toc-link');
        window.addEventListener('scroll', function() {
            var current = '';
            headings.forEach(function(h) {
                if (h.getBoundingClientRect().top - 130 <= 0) current = h.id;
            });
            tocLinks.forEach(function(l) {
                l.classList.toggle('active', l.getAttribute('href') === '#' + current);
            });
        }, { passive: true });
    } else if (tocBox && headings.length === 0) {
        tocBox.style.display = 'none';
    }
})();
</script>

<?php get_footer(); ?>
