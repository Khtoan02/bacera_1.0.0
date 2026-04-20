<?php
/**
 * Template Name: Blog
 * Description: Trang danh sách bài viết — Bacera
 */

get_header();

// ── Query params ──────────────────────────────────────────────────────────────
$current_cat  = isset($_GET['cat'])  ? sanitize_key($_GET['cat']) : '';
$current_page = max(1, get_query_var('paged') ?: (int)($_GET['paged'] ?? 1));
$posts_per    = 9;

// ── Categories ────────────────────────────────────────────────────────────────
$all_cats = get_categories(['hide_empty' => true, 'orderby' => 'name']);

// ── Query ─────────────────────────────────────────────────────────────────────
$args = [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => $posts_per,
    'paged'          => $current_page,
    'orderby'        => 'date',
    'order'          => 'DESC',
];
if ($current_cat) {
    $cat_obj = get_category_by_slug($current_cat);
    if ($cat_obj) $args['cat'] = $cat_obj->term_id;
}
$query      = new WP_Query($args);
$total_pages = $query->max_num_pages;

$home_url = home_url('/');
$blog_url = get_permalink();
$blog_cat_base = home_url('/blog/category/');
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
.blog-card-img { transition: transform 0.7s ease; }
.blog-card:hover .blog-card-img { transform: scale(1.06); }
.cat-pill { transition: all .2s ease; }
.cat-pill.active { background: #3d2f26; color: #f8f7f3; }
.cat-pill:not(.active):hover { background: #ede8e1; }
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
.fade-up { animation: fadeUp 0.6s ease both; }
.hero-post-img { transition: transform 0.9s ease; }
.hero-post:hover .hero-post-img { transform: scale(1.04); }
</style>

<div class="font-sans antialiased bg-texture text-textmain w-full overflow-hidden" style="padding-top:76px;">

    <div class="bacera-container pt-14 pb-0">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-textmuted mb-4 tracking-wide">
            <a href="<?php echo esc_url($home_url); ?>" class="text-textmain font-medium hover:text-terracotta transition-colors">Homepage</a>
            <span class="text-accent/60">/</span>
            <span>Blog</span>
            <?php if ($current_cat && isset($cat_obj) && $cat_obj): ?>
            <span class="text-accent/60">/</span>
            <span><?php echo esc_html($cat_obj->name); ?></span>
            <?php endif; ?>
        </nav>

        <!-- Title -->
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 mb-10">
            <h1 class="font-serif text-5xl lg:text-6xl font-medium text-textmain leading-tight tracking-tight">
                Our <span class="italic text-terracotta">journal</span>
            </h1>
            <p class="text-textmuted text-[15px] leading-relaxed max-w-md">
                Stories, techniques, and inspirations from the world of ceramic craft.
            </p>
        </div>

    </div>

    <?php if (!$current_cat && $current_page === 1 && $query->have_posts()):
        // ── Hero Post (first post) ────────────────────────────────────────────
        $query->the_post();
        $hero_id      = get_the_ID();
        $hero_url     = get_permalink();
        $hero_thumb   = get_the_post_thumbnail_url($hero_id, 'large') ?: 'https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=1200';
        $hero_cats    = get_the_category();
        $hero_excerpt = get_the_excerpt() ?: wp_trim_words(strip_tags(get_the_content()), 28, '…');
        $hero_date    = get_the_date('d M Y');
        $hero_author  = get_the_author();
    ?>

    <!-- ═══ HERO POST ═══ -->
    <section class="bacera-container mb-16">
        <a href="<?php echo esc_url($hero_url); ?>" class="hero-post group relative grid lg:grid-cols-[1fr_480px] gap-0 rounded-2xl overflow-hidden bg-textmain shadow-2xl min-h-[420px]">
            <!-- Image -->
            <div class="overflow-hidden relative order-2 lg:order-1 min-h-[260px] lg:min-h-0">
                <img src="<?php echo esc_url($hero_thumb); ?>"
                     alt="<?php echo esc_attr(get_the_title()); ?>"
                     class="hero-post-img absolute inset-0 w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-r from-textmain/60 via-transparent to-transparent lg:hidden"></div>
            </div>
            <!-- Content -->
            <div class="order-1 lg:order-2 flex flex-col justify-between p-8 lg:p-12 bg-textmain text-bgtheme">
                <div>
                    <?php if ($hero_cats): ?>
                    <span class="inline-block text-[10px] uppercase tracking-[0.3em] text-accent mb-4">
                        <?php echo esc_html($hero_cats[0]->name); ?>
                    </span>
                    <?php endif; ?>
                    <h2 class="font-serif text-2xl lg:text-3xl text-bgtheme leading-snug mb-4 group-hover:text-accent transition-colors duration-300">
                        <?php the_title(); ?>
                    </h2>
                    <p class="text-accent/80 text-[14px] leading-relaxed line-clamp-3">
                        <?php echo esc_html($hero_excerpt); ?>
                    </p>
                </div>
                <div class="flex items-center justify-between mt-8 pt-6 border-t border-white/10">
                    <div>
                        <p class="text-bgtheme/60 text-[11px] uppercase tracking-wider"><?php echo esc_html($hero_author); ?></p>
                        <p class="text-bgtheme/40 text-[11px] mt-0.5"><?php echo esc_html($hero_date); ?></p>
                    </div>
                    <span class="inline-flex items-center gap-2 text-[12px] font-medium text-accent uppercase tracking-wider">
                        Read more
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </span>
                </div>
            </div>
        </a>
    </section>

    <?php wp_reset_postdata(); $query = new WP_Query($args); endif; ?>

    <!-- ═══ CATEGORY FILTER ═══ -->
    <?php if ($all_cats): ?>
    <div class="bacera-container mb-12">
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?php echo esc_url($blog_url); ?>"
               class="cat-pill inline-flex items-center px-4 py-2 rounded-full border border-accent/30 text-[12px] font-medium tracking-wide text-textmuted <?php echo !$current_cat ? 'active' : ''; ?>">
                All posts
            </a>
            <?php foreach ($all_cats as $cat): ?>
            <a href="<?php echo esc_url($blog_cat_base . $cat->slug . '/'); ?>"
               class="cat-pill inline-flex items-center gap-1.5 px-4 py-2 rounded-full border border-accent/30 text-[12px] font-medium tracking-wide text-textmuted <?php echo $current_cat === $cat->slug ? 'active' : ''; ?>">
                <?php echo esc_html($cat->name); ?>
                <span class="text-accent/60 text-[10px]"><?php echo $cat->count; ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ POST GRID ═══ -->
    <section class="bacera-container mb-20">

        <?php if ($query->have_posts()): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
            <?php
            $card_idx = 0;
            while ($query->have_posts()):
                $query->the_post();
                // Skip hero post on page 1 without filter
                if (!$current_cat && $current_page === 1 && $card_idx === 0) {
                    $card_idx++; continue;
                }
                $p_id      = get_the_ID();
                $p_url     = get_permalink();
                $p_thumb   = get_the_post_thumbnail_url($p_id, 'medium_large') ?: 'https://images.unsplash.com/photo-1530018607912-eff2daa1bac4?auto=format&fit=crop&q=80&w=600';
                $p_cats    = get_the_category();
                $p_excerpt = get_the_excerpt() ?: wp_trim_words(strip_tags(get_the_content()), 18, '…');
                $p_date    = get_the_date('d M Y');
                $p_author  = get_the_author();
                $delay     = ($card_idx % 3) * 0.1;
            ?>
            <article class="blog-card group flex flex-col fade-up" style="animation-delay:<?php echo $delay; ?>s">
                <!-- Thumbnail -->
                <a href="<?php echo esc_url($p_url); ?>" class="block overflow-hidden rounded-xl aspect-[4/3] bg-[#EBE7DF] mb-5 shadow-sm">
                    <img src="<?php echo esc_url($p_thumb); ?>"
                         alt="<?php echo esc_attr(get_the_title()); ?>"
                         class="blog-card-img w-full h-full object-cover">
                </a>
                <!-- Meta -->
                <div class="flex items-center gap-3 mb-3">
                    <?php if ($p_cats): ?>
                    <a href="<?php echo esc_url($blog_cat_base . $p_cats[0]->slug . '/'); ?>"
                       class="text-[10px] uppercase tracking-[0.25em] text-terracotta font-semibold hover:underline">
                        <?php echo esc_html($p_cats[0]->name); ?>
                    </a>
                    <span class="w-1 h-1 rounded-full bg-accent/40"></span>
                    <?php endif; ?>
                    <span class="text-[11px] text-textmuted"><?php echo esc_html($p_date); ?></span>
                </div>
                <!-- Title -->
                <h2 class="text-[16px] font-semibold text-textmain leading-snug mb-2 group-hover:text-terracotta transition-colors">
                    <a href="<?php echo esc_url($p_url); ?>"><?php the_title(); ?></a>
                </h2>
                <!-- Excerpt -->
                <p class="text-[13px] text-textmuted leading-relaxed line-clamp-3 mb-4 flex-1">
                    <?php echo esc_html($p_excerpt); ?>
                </p>
                <!-- Footer -->
                <div class="flex items-center justify-between pt-4 border-t border-accent/20">
                    <span class="text-[11px] text-textmuted/70"><?php echo esc_html($p_author); ?></span>
                    <a href="<?php echo esc_url($p_url); ?>"
                       class="inline-flex items-center gap-1.5 text-[11px] uppercase tracking-wider font-semibold text-accentdark hover:text-terracotta transition-colors">
                        Read
                        <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                </div>
            </article>
            <?php $card_idx++; endwhile; wp_reset_postdata(); ?>
        </div>

        <?php else: ?>
        <!-- Empty state -->
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <div class="w-16 h-16 rounded-full bg-accent/10 flex items-center justify-center mb-4">
                <svg class="w-7 h-7 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <h3 class="font-serif text-2xl text-textmain mb-2">No posts yet</h3>
            <p class="text-textmuted text-[14px]">Check back soon — our journal is coming.</p>
            <a href="<?php echo esc_url($blog_url); ?>" class="mt-6 text-[12px] uppercase tracking-wider text-accentdark hover:text-terracotta font-semibold transition-colors">View all posts →</a>
        </div>
        <?php endif; ?>

        <!-- ── PAGINATION ── -->
        <?php if ($total_pages > 1): ?>
        <nav class="flex items-center justify-center gap-2 mt-16">
            <?php for ($p = 1; $p <= $total_pages; $p++): 
                $p_link = add_query_arg(['paged' => $p], $blog_url);
                if ($current_cat) $p_link = $blog_cat_base . $current_cat . '/?paged=' . $p;
                $is_cur = $p === $current_page;
            ?>
            <a href="<?php echo esc_url($p_link); ?>"
               class="w-10 h-10 flex items-center justify-center rounded-full text-[13px] font-medium transition-all <?php echo $is_cur ? 'bg-textmain text-bgtheme' : 'border border-accent/30 text-textmuted hover:border-accentdark hover:text-textmain'; ?>">
                <?php echo $p; ?>
            </a>
            <?php endfor; ?>
        </nav>
        <?php endif; ?>

    </section>

    <!-- ═══ DIVIDER ═══ -->
    <div class="bacera-container mb-16">
        <div class="w-full h-[1px] divider-art opacity-40"></div>
    </div>

    <!-- ═══ NEWSLETTER CTA ═══ -->
    <div class="bacera-container pb-20">
        <div class="bg-textmain rounded-2xl lg:rounded-3xl px-8 lg:px-16 py-14 flex flex-col lg:flex-row items-center justify-between gap-8 relative overflow-hidden">
            <div class="absolute -right-16 -top-16 w-48 h-48 rounded-full bg-accent/10 pointer-events-none"></div>
            <div class="absolute -left-8 -bottom-10 w-32 h-32 rounded-full bg-terracotta/10 pointer-events-none"></div>
            <div class="relative z-10 text-center lg:text-left">
                <p class="text-[10px] uppercase tracking-[0.35em] text-accent mb-3">Stay inspired</p>
                <h2 class="font-serif text-3xl lg:text-4xl text-bgtheme leading-snug">
                    Stories from <span class="italic text-accent">the studio.</span>
                </h2>
                <p class="text-accent/60 text-[13px] mt-3">Craft insights, new collections, and workshop updates — direct to your inbox.</p>
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

<?php get_footer(); ?>
