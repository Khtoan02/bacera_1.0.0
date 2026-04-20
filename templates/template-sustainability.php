<?php
/**
 * Template Name: Sustainability
 * Description: Earth-conscious craft — Bacera's environmental commitment
 */

get_header();

$home_url = home_url('/');
?>


<style>
.divider-art {
    background-image: linear-gradient(to right, #D0BCA0 50%, transparent 50%);
    background-size: 10px 1px; background-repeat: repeat-x;
}
/* Animate in */
@keyframes fadeUp {
    from { opacity:0; transform:translateY(24px); }
    to   { opacity:1; transform:translateY(0); }
}
.fade-up { animation: fadeUp .65s ease both; }

/* Pillar cards */
.pillar-card {
    position:relative; overflow:hidden;
    border:1px solid #DDD8CC; border-radius:20px;
    background:#fff; padding:28px;
    transition: box-shadow .25s, transform .25s;
}
.pillar-card:hover { box-shadow:0 10px 40px rgba(61,47,38,.1); transform:translateY(-3px); }
.pillar-icon {
    width:52px; height:52px; border-radius:14px;
    display:flex; align-items:center; justify-content:center;
    margin-bottom:16px; flex-shrink:0;
}
.pillar-number {
    position:absolute; top:20px; right:24px;
    font-family:'Gowun Batang',serif;
    font-size:72px; line-height:1; font-weight:700;
    color:#3d2f26; opacity:.04; pointer-events:none; user-select:none;
}

/* Stat counter */
.stat-item { text-align:center; }
.stat-number {
    font-family:'Gowun Batang',serif;
    font-size:52px; line-height:1; font-weight:700;
    color:#3d2f26; letter-spacing:-2px;
}
.stat-unit { font-size:24px; color:#d95f47; }
.stat-label { font-size:13px; color:#6b5344; line-height:1.5; margin-top:8px; max-width:120px; margin-left:auto; margin-right:auto; }

/* Timeline */
.timeline { position:relative; padding-left:32px; }
.timeline::before {
    content:''; position:absolute; left:11px; top:8px; bottom:0;
    width:1px; background:linear-gradient(to bottom, #D0BCA0, transparent);
}
.tl-item { position:relative; margin-bottom:28px; }
.tl-dot {
    position:absolute; left:-32px; top:4px;
    width:22px; height:22px; border-radius:50%;
    background:#fff; border:2px solid #D0BCA0;
    display:flex; align-items:center; justify-content:center;
}
.tl-dot.active { border-color:#d95f47; background:#d95f47; }
.tl-dot.active svg { stroke:#fff; }

/* Commitment strip */
.commit-strip {
    background:linear-gradient(135deg, #3d2f26 0%, #1f1813 100%);
    border-radius:28px; overflow:hidden; position:relative;
}
.commit-strip::before {
    content:''; position:absolute; inset:0;
    background-image:radial-gradient(circle at 80% 50%, rgba(217,95,71,.3) 0%, transparent 60%);
}
</style>

<div class="font-sans antialiased bg-texture text-textmain w-full overflow-hidden" style="padding-top:76px;">

    <!-- ── HERO ── -->
    <div class="bacera-container pt-14 pb-16">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-textmuted mb-5 tracking-wide">
            <a href="<?php echo esc_url($home_url); ?>" class="text-textmain font-medium hover:text-terracotta transition-colors">Homepage</a>
            <span class="text-accent/60">/</span>
            <a href="<?php echo esc_url(get_permalink(get_page_by_path('about-us'))); ?>" class="hover:text-terracotta transition-colors">About us</a>
            <span class="text-accent/60">/</span>
            <span>Sustainability</span>
        </nav>

        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            <div class="fade-up">
                <p class="text-xs uppercase tracking-[0.35em] text-terracotta mb-4 font-medium">Earth-conscious craft</p>
                <h1 class="font-serif text-5xl lg:text-[64px] font-medium text-textmain leading-[1.05] tracking-tight mb-6">
                    Clay rooted in<br><span class="italic text-terracotta">respect</span> for earth.
                </h1>
                <p class="text-textmuted text-[16px] leading-relaxed max-w-lg mb-8">
                    At Bacera, sustainability isn't a label — it's woven into every decision. From how we source our clay to how we fire our kilns, we believe beautiful craft and environmental responsibility belong together.
                </p>
                <div class="flex items-center gap-4 flex-wrap">
                    <a href="#our-commitments"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-terracotta hover:bg-[#b84833] text-white text-[13px] font-medium rounded-full transition-all">
                        Our commitments
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-3 3-3-3m3 3V3M5 21h14a2 2 0 002-2v-5"/>
                        </svg>
                    </a>
                    <a href="<?php echo esc_url($home_url . 'workshop/'); ?>"
                       class="inline-flex items-center gap-2 px-6 py-3 border border-[#DDD8CC] text-textmain hover:border-terracotta hover:text-terracotta text-[13px] font-medium rounded-full transition-all">
                        Join a workshop
                    </a>
                </div>
            </div>

            <!-- Hero visual: leaf / earth motif -->
            <div class="fade-up relative" style="animation-delay:.15s">
                <div class="relative rounded-[28px] overflow-hidden aspect-[4/3] bg-textmain">
                    <img src="https://images.unsplash.com/photo-1530731141654-5993c3016c77?auto=format&fit=crop&q=80&w=900"
                         alt="Sustainable ceramics studio"
                         class="w-full h-full object-cover opacity-75 mix-blend-luminosity">
                    <div class="absolute inset-0 bg-gradient-to-t from-textmain/80 via-transparent to-transparent"></div>
                    <!-- Floating stat chip -->
                    <div class="absolute bottom-6 left-6 bg-white/95 backdrop-blur-sm rounded-2xl px-5 py-4 shadow-xl">
                        <div class="text-[10px] uppercase tracking-[.15em] text-terracotta font-semibold mb-1">Carbon offset</div>
                        <div class="font-serif text-3xl text-textmain font-bold">80<span class="text-terracotta text-lg">%</span></div>
                        <div class="text-[11px] text-textmuted mt-0.5">reduction since 2021</div>
                    </div>
                </div>
                <!-- Decorative ring -->
                <div class="absolute -top-6 -right-6 w-32 h-32 rounded-full border-2 border-dashed border-terracotta/20 -z-10"></div>
            </div>
        </div>
    </div>

    <!-- ── STATS ── -->
    <div class="bg-[#F3F5EE] border-y border-[#DDD8CC]">
        <div class="bacera-container py-14">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
                <?php
                $stats = [
                    ['num' => '80',  'unit' => '%', 'label' => 'Less CO₂ vs industry average'],
                    ['num' => '60',  'unit' => '%', 'label' => 'Reclaimed clay in production'],
                    ['num' => '100', 'unit' => '%', 'label' => 'Natural mineral glazes used'],
                    ['num' => '0',   'unit' => '',  'label' => 'Synthetic additives. Ever.'],
                ];
                foreach ($stats as $i => $s): ?>
                <div class="stat-item fade-up" style="animation-delay:<?php echo $i * .08; ?>s">
                    <div class="stat-number"><?php echo $s['num']; ?><span class="stat-unit"><?php echo $s['unit']; ?></span></div>
                    <div class="stat-label"><?php echo $s['label']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── PILLARS ── -->
    <div id="our-commitments" class="bacera-container section-pad">
        <div class="text-center mb-14">
            <p class="text-xs uppercase tracking-[.35em] text-terracotta font-semibold mb-3">Our commitments</p>
            <h2 class="font-serif text-4xl lg:text-5xl font-medium text-textmain leading-tight">
                Four pillars of<br><span class="italic text-terracotta">responsible craft</span>
            </h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <?php
            $pillars = [
                [
                    'icon_path' => 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3',
                    'icon_bg'   => '#FAF5EF',
                    'icon_color'=> '#8d6a54',
                    'title'     => 'Responsible Sourcing',
                    'desc'      => 'All clay is sourced from certified Vietnamese suppliers who practice responsible land use. We only buy from sources that prioritise soil regeneration.',
                ],
                [
                    'icon_path' => 'M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z',
                    'icon_bg'   => '#FDEEE9',
                    'icon_color'=> '#d95f47',
                    'title'     => 'Low-emission Firing',
                    'desc'      => 'Our kilns use recycled heat systems and are scheduled during off-peak energy hours. Wood ash from our fires goes back into our glaze recipes.',
                ],
                [
                    'icon_path' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                    'icon_bg'   => '#FAF5EF',
                    'icon_color'=> '#8d6a54',
                    'title'     => 'Zero Waste Studio',
                    'desc'      => 'Clay scraps are reclaimed and recycled back into production. Broken pieces are ground and reused as grog — a natural aggregate in new clay bodies.',
                ],
                [
                    'icon_path' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064',
                    'icon_bg'   => '#FDEEE9',
                    'icon_color'=> '#d95f47',
                    'title'     => 'Natural Glazes Only',
                    'desc'      => 'Every glaze in our studio is made from natural minerals: feldspar, wood ash, rice husk, and local stone. No synthetic colourants. No toxic heavy metals.',
                ],
            ];
            foreach ($pillars as $i => $p): ?>
            <div class="pillar-card fade-up" style="animation-delay:<?php echo $i * .1; ?>s">
                <div class="pillar-number"><?php echo str_pad($i+1, 2, '0', STR_PAD_LEFT); ?></div>
                <div class="pillar-icon" style="background:<?php echo $p['icon_bg']; ?>">
                    <svg class="w-6 h-6" fill="none" stroke="<?php echo $p['icon_color']; ?>" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $p['icon_path']; ?>"/>
                    </svg>
                </div>
                <h3 class="text-[15px] font-bold text-textmain mb-3"><?php echo $p['title']; ?></h3>
                <p class="text-[13px] text-textmuted leading-relaxed"><?php echo $p['desc']; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── TWO-COL: Story + Timeline ── -->
    <div class="bg-[#F3F5EE] border-y border-[#DDD8CC]">
        <div class="bacera-container section-pad grid lg:grid-cols-2 gap-16 items-start">

            <!-- Left: Image -->
            <div class="relative fade-up">
                <div class="rounded-[24px] overflow-hidden aspect-[3/4] lg:aspect-[4/5] bg-[#d0c8b8]">
                    <img src="https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=700"
                         alt="Natural clay sourcing" class="w-full h-full object-cover mix-blend-multiply opacity-90">
                </div>
                <!-- Pull quote card -->
                <div class="absolute -bottom-6 -right-4 lg:right-[-40px] bg-white rounded-2xl shadow-xl px-6 py-5 max-w-[240px]">
                    <p class="font-serif text-[15px] text-textmain leading-snug mb-3 italic">
                        "We owe the earth more than we take."
                    </p>
                    <p class="text-[11px] text-textmuted uppercase tracking-[.12em] font-semibold">— Bacera Studio</p>
                </div>
            </div>

            <!-- Right: Goals Timeline -->
            <div class="fade-up lg:pt-8" style="animation-delay:.1s">
                <p class="text-xs uppercase tracking-[.35em] text-terracotta font-semibold mb-3">Our roadmap</p>
                <h2 class="font-serif text-4xl font-medium text-textmain mb-3">Sustainability<br><span class="italic text-terracotta">goals & progress</span></h2>
                <p class="text-[14px] text-textmuted leading-relaxed mb-10">We hold ourselves to milestones — and we share the progress honestly, year by year.</p>

                <div class="timeline">
                    <?php
                    $goals = [
                        ['year' => '2021', 'done' => true,  'title' => 'Eliminated synthetic stains',         'desc' => 'Replaced all commercial colourants with natural iron, cobalt mineral & ash glazes.'],
                        ['year' => '2022', 'done' => true,  'title' => 'Clay reclamation system installed',   'desc' => '60% of production clay is now recycled scrap — dried, re-wedged, ready to throw.'],
                        ['year' => '2023', 'done' => true,  'title' => '80% lower emissions',                 'desc' => 'New kiln insulation and off-peak firing schedules cut carbon output by 80%.'],
                        ['year' => '2024', 'done' => false, 'title' => 'Partner with local clay quarries',    'desc' => 'Direct relationships with Vietnamese suppliers committed to soil regeneration.'],
                        ['year' => '2025', 'done' => false, 'title' => 'Carbon-neutral studio certification', 'desc' => 'Pursuing formal certification from an independent environmental body.'],
                    ];
                    foreach ($goals as $g): ?>
                    <div class="tl-item">
                        <div class="tl-dot <?php echo $g['done'] ? 'active' : ''; ?>">
                            <?php if ($g['done']): ?>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 12 12" stroke-width="2.5">
                                <polyline points="2,6 5,9 10,3"/>
                            </svg>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-3 mb-1">
                            <span class="text-[10px] font-bold uppercase tracking-[.15em] <?php echo $g['done'] ? 'text-terracotta' : 'text-textmuted'; ?>"><?php echo $g['year']; ?></span>
                            <?php if (!$g['done']): ?><span class="text-[9px] bg-[#f2ede6] text-textmuted px-2 py-0.5 rounded-full font-semibold uppercase tracking-wider">Planned</span><?php endif; ?>
                        </div>
                        <h4 class="text-[14px] font-semibold text-textmain mb-1"><?php echo $g['title']; ?></h4>
                        <p class="text-[12px] text-textmuted leading-relaxed"><?php echo $g['desc']; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Materials section ── -->
    <div class="bacera-container section-pad">
        <div class="text-center mb-14">
            <p class="text-xs uppercase tracking-[.35em] text-terracotta font-semibold mb-3">What we use</p>
            <h2 class="font-serif text-4xl lg:text-5xl font-medium text-textmain">Our natural<br><span class="italic text-terracotta">material palette</span></h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            $materials = [
                ['name' => 'Red Stoneware Clay',    'origin' => 'Bình Dương, Vietnam',  'note' => 'Iron-rich, local, low-shrinkage',         'color' => '#9B5C3A'],
                ['name' => 'White Porcelain Clay',  'origin' => 'Hải Dương, Vietnam',   'note' => 'Fine grain, minimal processing',          'color' => '#E8E0D4'],
                ['name' => 'Wood Ash Glaze',         'origin' => 'Studio-made',          'note' => 'From kiln firings — closed loop',         'color' => '#7C8A6E'],
                ['name' => 'Feldspar',               'origin' => 'Northern highlands',   'note' => 'Natural flux, no synthetic substitutes',  'color' => '#BDAAA0'],
                ['name' => 'Iron Oxide',             'origin' => 'Natural mineral ore',  'note' => 'For earthy reds, browns, and blacks',     'color' => '#6B3D2E'],
                ['name' => 'Rice Husk Ash',          'origin' => 'Mekong Delta',         'note' => 'High-silica, matte finish natural glaze', 'color' => '#D4C8A8'],
            ];
            foreach ($materials as $i => $m): ?>
            <div class="bg-white border border-[#DDD8CC] rounded-2xl p-5 flex items-start gap-4 hover:shadow-md transition-shadow fade-up" style="animation-delay:<?php echo $i * .07; ?>s">
                <div class="w-10 h-10 rounded-xl shrink-0 border border-[#DDD8CC]"
                     style="background:<?php echo $m['color']; ?>"></div>
                <div>
                    <div class="text-[14px] font-semibold text-textmain mb-0.5"><?php echo $m['name']; ?></div>
                    <div class="text-[11px] text-terracotta font-medium mb-1"><?php echo $m['origin']; ?></div>
                    <div class="text-[12px] text-textmuted"><?php echo $m['note']; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         SEO CONTENT BLOCK
    ═══════════════════════════════════════════════════════ -->
    <?php get_template_part('app/Views/components/seo-content', null, ['title' => get_the_title()]); ?>

    <!-- ── CTA strip ── -->
    <div class="bacera-container pb-20">
        <div class="commit-strip px-8 lg:px-16 py-14 flex flex-col lg:flex-row items-center justify-between gap-8 relative">
            <div class="relative z-10 text-center lg:text-left">
                <p class="text-[10px] uppercase tracking-[.35em] text-accent mb-3 font-semibold">Crafted with care</p>
                <h2 class="font-serif text-3xl lg:text-4xl text-white leading-snug">
                    Every piece holds<br><span class="italic text-accent">the earth's memory.</span>
                </h2>
                <p class="text-white/60 text-[13px] mt-3">Come see our process in person — at a Bacera workshop.</p>
            </div>
            <a href="<?php echo esc_url($home_url . 'workshop/'); ?>"
               class="relative z-10 shrink-0 inline-flex items-center gap-3 text-xs uppercase tracking-[.2em] text-textmain bg-white hover:bg-bgtheme hover:text-textmain px-8 py-4 rounded-full transition-all duration-300 font-semibold shadow-lg">
                Explore Workshops
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>

</div><!-- /wrapper -->

<?php get_footer(); ?>
