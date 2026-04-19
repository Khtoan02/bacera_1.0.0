<?php
/**
 * Template Name: Our Process
 * Description: From clay to ceramic — Bacera's craft journey
 */

get_header();

$home_url = home_url('/');
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
        },
    },
}
</script>

<style>
.bg-texture {
    background-color:#F7F6F0;
    background-image:url("data:image/svg+xml,%3Csvg viewBox=%270 0 200 200%27 xmlns=%27http://www.w3.org/2000/svg%27%3E%3Cfilter id=%27n%27%3E%3CfeTurbulence type=%27fractalNoise%27 baseFrequency=%270.9%27 numOctaves=%274%27 stitchTiles=%27stitch%27/%3E%3C/filter%3E%3Crect width=%27100%25%27 height=%27100%25%27 filter=%27url(%23n)%27 opacity=%270.03%27/%3E%3C/svg%3E");
}
.divider-art {
    background-image:linear-gradient(to right, #D0BCA0 50%, transparent 50%);
    background-size:10px 1px; background-repeat:repeat-x;
}
@keyframes fadeUp {
    from { opacity:0; transform:translateY(24px); }
    to   { opacity:1; transform:translateY(0); }
}
.fade-up { animation:fadeUp .65s ease both; }

/* Process step cards */
.step-card { position:relative; }
.step-num {
    font-family:'Gowun Batang',serif;
    font-size:80px; line-height:1; font-weight:700;
    color:#3d2f26; opacity:.06; position:absolute;
    top:-20px; right:0; pointer-events:none; user-select:none;
}

/* Connector line between steps */
.step-connector {
    position:absolute; top:36px; left:calc(100% + 0px);
    width:100%; height:1px;
    background:linear-gradient(to right, #D0BCA0, transparent);
    z-index:0;
}

/* Image hover */
.step-img-wrap {
    position:relative; overflow:hidden; border-radius:20px;
    background:#2c2420;
}
.step-img-wrap img {
    width:100%; display:block; transition:transform .6s ease;
}
.step-card:hover .step-img-wrap img { transform:scale(1.04); }

/* Phase label chip */
.phase-chip {
    display:inline-flex; align-items:center; gap:6px;
    padding:4px 12px; border-radius:20px;
    font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.12em;
    border:1px solid; margin-bottom:12px;
}

/* Tools list */
.tool-item {
    display:flex; align-items:center; gap:8px;
    padding:7px 0; border-bottom:1px solid #EAE3D1;
    font-size:13px; color:#6b5344;
}
.tool-item:last-child { border-bottom:none; }
.tool-dot { width:6px; height:6px; border-radius:50%; background:#d95f47; flex-shrink:0; }

/* Big step hero: alternating layout */
.big-step { display:grid; gap:0; }
.big-step.odd  { grid-template-columns:1fr 1fr; }
.big-step.even { grid-template-columns:1fr 1fr; }
@media(max-width:1024px) {
    .big-step.odd,.big-step.even { grid-template-columns:1fr; }
}

/* Progress bar at top */
.proc-progress {
    position:sticky; top:76px; z-index:40;
    background:#fff; border-bottom:1px solid #EAE3D1;
}
.proc-step-pill {
    display:flex; flex-direction:column; align-items:center; gap:3px;
    padding:12px 16px; min-width:90px;
    font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.08em;
    color:#a8907c; cursor:default; transition:color .2s;
    border-bottom:2px solid transparent; margin-bottom:-1px;
}
.proc-step-pill.active { color:#d95f47; border-bottom-color:#d95f47; }
.proc-step-pill svg { width:16px; height:16px; stroke:currentColor; fill:none; stroke-width:1.5; margin-bottom:2px; }
</style>

<div class="font-sans antialiased bg-texture text-textmain w-full overflow-hidden" style="padding-top:76px;">

    <!-- ── HERO ── -->
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0 pt-14 pb-16">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-textmuted mb-5 tracking-wide">
            <a href="<?php echo esc_url($home_url); ?>" class="text-textmain font-medium hover:text-terracotta transition-colors">Homepage</a>
            <span class="text-accent/60">/</span>
            <a href="<?php echo esc_url(get_permalink(get_page_by_path('about-us'))); ?>" class="hover:text-terracotta transition-colors">About us</a>
            <span class="text-accent/60">/</span>
            <span>Our Process</span>
        </nav>

        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            <div class="fade-up">
                <p class="text-xs uppercase tracking-[.35em] text-terracotta mb-4 font-medium">From clay to ceramic</p>
                <h1 class="font-serif text-5xl lg:text-[60px] font-medium text-textmain leading-[1.05] tracking-tight mb-6">
                    Eight steps.<br>One <span class="italic text-terracotta">timeless</span> craft.
                </h1>
                <p class="text-textmuted text-[16px] leading-relaxed max-w-lg mb-8">
                    Each Bacera piece passes through many hands and many hours before it reaches yours. Here we open every door of our studio — from raw earth to finished form.
                </p>
                <!-- Step navigation pills -->
                <div class="flex flex-wrap gap-2">
                    <?php
                    $step_labels = ['Clay', 'Wedging', 'Throwing', 'Trimming', 'Bisque', 'Glazing', 'Firing', 'Finishing'];
                    foreach ($step_labels as $i => $sl): ?>
                    <a href="#step-<?php echo $i+1; ?>"
                       class="text-[11px] font-semibold px-3 py-1.5 rounded-full border border-[#DDD8CC] text-textmuted hover:border-terracotta hover:text-terracotta transition-all">
                        <?php echo ($i+1) . '. ' . $sl; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right: collage of 3 images -->
            <div class="fade-up relative grid grid-cols-2 gap-3" style="animation-delay:.12s">
                <div class="rounded-2xl overflow-hidden aspect-[3/4] col-span-1 row-span-2">
                    <img src="https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=500"
                         alt="Throwing clay on wheel" class="w-full h-full object-cover">
                </div>
                <div class="rounded-2xl overflow-hidden aspect-square">
                    <img src="https://images.unsplash.com/photo-1560707854-fb9a46c26736?auto=format&fit=crop&q=80&w=400"
                         alt="Natural clay" class="w-full h-full object-cover">
                </div>
                <div class="rounded-2xl overflow-hidden aspect-square">
                    <img src="https://images.unsplash.com/photo-1495121605193-b116b5b9c5e8?auto=format&fit=crop&q=80&w=400"
                         alt="Finished ceramics" class="w-full h-full object-cover">
                </div>
                <!-- Overlay badge -->
                <div class="absolute top-4 left-4 bg-white/95 backdrop-blur-sm rounded-xl px-4 py-3 shadow-lg">
                    <div class="text-[10px] uppercase tracking-[.12em] text-textmuted mb-0.5">Avg. time per piece</div>
                    <div class="font-serif text-2xl text-textmain font-bold">3–7 <span class="text-base font-sans text-terracotta">weeks</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── PROCESS STEPS ── -->
    <?php
    $steps = [
        [
            'num'    => 1,
            'phase'  => 'Preparation',
            'phase_color' => '#5C6B3A',
            'phase_bg'    => '#EEF2E6',
            'title'  => 'Clay Selection',
            'sub'    => 'The earth speaks first.',
            'desc'   => 'We source stoneware and porcelain clays from trusted Vietnamese quarries. Each batch is tested for plasticity, shrinkage, and mineral content before it enters our studio. The right clay is the foundation of everything.',
            'detail' => 'Clay is never just dirt. Different clay bodies behave differently in the kiln, respond differently to the wheel, and accept glazes in their own way. We select each type for its intended use.',
            'tools'  => ['Vietnamese red stoneware', 'White porcelain', 'Grog (crushed fired clay)', 'Moisture testing'],
            'img'    => 'https://images.unsplash.com/photo-1523995462485-3d171b5c8fa9?auto=format&fit=crop&q=80&w=800',
            'duration' => '1–2 days',
        ],
        [
            'num'    => 2,
            'phase'  => 'Preparation',
            'phase_color' => '#5C6B3A',
            'phase_bg'    => '#EEF2E6',
            'title'  => 'Wedging',
            'sub'    => 'Remove every air bubble.',
            'desc'   => 'Before any clay touches the wheel, it must be wedged — a process of rhythmically pressing and folding the clay to remove air pockets. Air bubbles left inside can cause cracks or even explosions in the kiln.',
            'detail' => 'Wedging is meditative and physical. A seasoned potter can feel when the clay is ready — it takes on a silky, uniform resistance. We typically wedge 5–10 minutes per piece of clay.',
            'tools'  => ['Wire cutter', 'Canvas work surface', 'Hands (always)', 'Water bowl'],
            'img'    => 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?auto=format&fit=crop&q=80&w=800',
            'duration' => '10–20 min',
        ],
        [
            'num'    => 3,
            'phase'  => 'Forming',
            'phase_color' => '#8d6a54',
            'phase_bg'    => '#F5EDE6',
            'title'  => 'Throwing on the Wheel',
            'sub'    => 'Where form is born.',
            'desc'   => 'The pottery wheel is the heartbeat of our studio. Clay is centred, opened, and pulled up into form using water and careful pressure. Every motion counts — too much and the wall collapses, too little and the form won\'t rise.',
            'detail' => 'Throwing is the most skill-intensive step. Our artisans have years of muscle memory. What looks effortless is the result of thousands of hours. Each piece is thrown one at a time, by one person.',
            'tools'  => ['Electric kick wheel', 'Throwing ribs', 'Sponge', 'Water, and patience'],
            'img'    => 'https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=800',
            'duration' => '15–60 min',
        ],
        [
            'num'    => 4,
            'phase'  => 'Forming',
            'phase_color' => '#8d6a54',
            'phase_bg'    => '#F5EDE6',
            'title'  => 'Trimming & Refining',
            'sub'    => 'The quiet discipline of subtraction.',
            'desc'   => 'Once leather-hard (partially dry), each piece is trimmed on the wheel to refine its profile, thin the walls evenly, and carve the foot ring at its base. This defines how the piece sits and feels in the hand.',
            'detail' => 'Trimming requires the same attention as throwing, but in reverse — you\'re removing clay rather than building. The foot ring is a signature: in ceramics, you can often identify the maker\'s hand from the foot alone.',
            'tools'  => ['Loop trimming tools', 'Turning tool', 'Calipers', 'Needle tool'],
            'img'    => 'https://images.unsplash.com/photo-1552423314-cf29ab68ad73?auto=format&fit=crop&q=80&w=800',
            'duration' => '20–40 min',
        ],
        [
            'num'    => 5,
            'phase'  => 'First Firing',
            'phase_color' => '#d95f47',
            'phase_bg'    => '#FDF0EC',
            'title'  => 'Bisque Firing',
            'sub'    => 'Clay becomes ceramic.',
            'desc'   => 'Bone-dry pieces are loaded into the kiln and fired to around 1000°C. This first firing — called bisque — transforms raw clay into porous ceramic. The piece is now permanent but still unglazed and fragile.',
            'detail' => 'The kiln rises slowly: too fast and the remaining moisture creates steam that cracks the piece. Bisque firings take 8–12 hours in the kiln, then cool slowly overnight before the door is opened.',
            'tools'  => ['Electric kiln', 'Kiln shelves & stilts', 'Pyrometer', 'Kiln wash'],
            'img'    => 'https://images.unsplash.com/photo-1590512668977-a6e2ddf5e1ed?auto=format&fit=crop&q=80&w=800',
            'duration' => '12–16 hours',
        ],
        [
            'num'    => 6,
            'phase'  => 'Surface',
            'phase_color' => '#7554A8',
            'phase_bg'    => '#F0EBF8',
            'title'  => 'Glazing',
            'sub'    => 'The piece finds its voice.',
            'desc'   => 'Bisqueware is dipped, poured, or brushed with our house-made glazes — all natural mineral recipes developed in-studio. Glaze is applied with intention: thickness determines colour depth, flow and texture.',
            'detail' => 'Glazing is chemistry and intuition combined. Our glazes are made from feldspar, wood ash, silica, and natural colourants. We maintain a library of over 40 unique glaze recipes developed over years of studio testing.',
            'tools'  => ['Dipping tongs', 'Glaze brush', 'Latex resist', 'Wax resist for foot'],
            'img'    => 'https://images.unsplash.com/photo-1612198188060-c7c2a3b66eae?auto=format&fit=crop&q=80&w=800',
            'duration' => '30–90 min',
        ],
        [
            'num'    => 7,
            'phase'  => 'Glaze Firing',
            'phase_color' => '#d95f47',
            'phase_bg'    => '#FDF0EC',
            'title'  => 'Glaze Firing',
            'sub'    => 'Fire does the final work.',
            'desc'   => 'Glazed pieces are fired a second time, now to 1220–1280°C — high-fire stoneware temperatures. The glaze melts, flows, and fuses permanently to the clay body. Each piece emerges unique from the heat.',
            'detail' => 'This is the moment of mystery. We load the kiln with care but accept that the fire has its own will. Some glazes shift colour under heat. Surfaces that appeared uniform can develop texture, pools, or movement.',
            'tools'  => ['Electric kiln', 'Kiln furniture', 'Temperature controller', 'Patient waiting'],
            'img'    => 'https://images.unsplash.com/photo-1570779367011-1d7498d1af88?auto=format&fit=crop&q=80&w=800',
            'duration' => '18–24 hours',
        ],
        [
            'num'    => 8,
            'phase'  => 'Completion',
            'phase_color' => '#3d2f26',
            'phase_bg'    => '#F2EDE6',
            'title'  => 'Finishing & Release',
            'sub'    => 'Ready to be lived with.',
            'desc'   => 'Each cooled piece is unloaded from the kiln, inspected, and its foot ground smooth. We test food safety, water tightness, and structural soundness. Only pieces that meet our standards are released.',
            'detail' => 'We reject more than most people expect. A crack that appeared in the drying, a glaze crawl, a foot that\'s slightly off — these send a piece to the seconds shelf or back to clay. The ones that make it are right.',
            'tools'  => ['Diamond grinding disc', 'Quality inspection', 'Food-safe sealant test', 'Felt protectors'],
            'img'    => 'https://images.unsplash.com/photo-1495121605193-b116b5b9c5e8?auto=format&fit=crop&q=80&w=800',
            'duration' => '1–2 hours',
        ],
    ];

    foreach ($steps as $i => $step):
        $odd = $i % 2 === 0;
        $anchor = 'step-' . $step['num'];
    ?>
    <!-- Step <?php echo $step['num']; ?> -->
    <div id="<?php echo $anchor; ?>"
         class="<?php echo ($i % 2 === 1) ? 'bg-[#F3F5EE] border-y border-[#DDD8CC]' : ''; ?>">
        <div class="max-w-[1232px] mx-auto px-6 lg:px-0 py-16 lg:py-20">
            <div class="grid lg:grid-cols-2 gap-10 lg:gap-20 items-center <?php echo $odd ? '' : 'lg:[direction:rtl]'; ?>">

                <!-- Image -->
                <div class="step-img-wrap <?php echo $odd ? '' : 'lg:[direction:ltr]'; ?> fade-up">
                    <img src="<?php echo esc_url($step['img']); ?>"
                         alt="<?php echo esc_attr($step['title']); ?>"
                         loading="lazy"
                         class="aspect-[4/3] object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/25 to-transparent pointer-events-none rounded-2xl"></div>
                    <!-- Step number badge -->
                    <div class="absolute top-4 left-4 w-10 h-10 rounded-full bg-white/95 backdrop-blur flex items-center justify-center">
                        <span class="font-serif text-[15px] font-bold text-textmain"><?php echo str_pad($step['num'], 2, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <!-- Duration badge -->
                    <div class="absolute bottom-4 right-4 bg-black/60 backdrop-blur-sm text-white text-[11px] font-semibold px-3 py-1.5 rounded-full">
                        ⏱ <?php echo esc_html($step['duration']); ?>
                    </div>
                </div>

                <!-- Text -->
                <div class="<?php echo $odd ? '' : 'lg:[direction:ltr]'; ?> fade-up" style="animation-delay:.1s">
                    <!-- Phase chip -->
                    <div class="phase-chip" style="background:<?php echo $step['phase_bg']; ?>;color:<?php echo $step['phase_color']; ?>;border-color:<?php echo $step['phase_color']; ?>30;">
                        <span><?php echo $step['phase']; ?></span>
                    </div>

                    <h2 class="font-serif text-4xl lg:text-5xl font-medium text-textmain mb-2 leading-tight">
                        <?php echo $step['title']; ?>
                    </h2>
                    <p class="text-textmuted italic text-[16px] mb-5 font-serif"><?php echo $step['sub']; ?></p>
                    <p class="text-[15px] text-textmuted leading-relaxed mb-5"><?php echo $step['desc']; ?></p>
                    <p class="text-[13px] text-textmuted/80 leading-relaxed mb-7 border-l-2 border-terracotta/30 pl-4"><?php echo $step['detail']; ?></p>

                    <!-- Tools -->
                    <div>
                        <div class="text-[10px] uppercase tracking-[.18em] font-bold text-textmuted mb-2">Tools & Materials</div>
                        <div class="rounded-xl border border-[#DDD8CC] bg-[#FAF8F4] overflow-hidden">
                            <?php foreach ($step['tools'] as $tool): ?>
                            <div class="tool-item px-4">
                                <span class="tool-dot"></span>
                                <?php echo esc_html($tool); ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Nav to next step -->
                    <?php if ($i < count($steps) - 1): ?>
                    <a href="#step-<?php echo $step['num'] + 1; ?>"
                       class="mt-6 inline-flex items-center gap-2 text-[12px] font-semibold uppercase tracking-[.15em] text-terracotta hover:text-textmain transition-colors">
                        Next: <?php echo $steps[$i+1]['title']; ?>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- ── DIVIDER ── -->
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0 py-12">
        <div class="w-full h-[1px] divider-art opacity-40"></div>
    </div>

    <!-- ── CTA ── -->
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0 pb-20">
        <div class="bg-textmain rounded-2xl lg:rounded-3xl px-8 lg:px-16 py-14 flex flex-col lg:flex-row items-center justify-between gap-8 relative overflow-hidden">
            <div class="absolute -right-16 -top-16 w-48 h-48 rounded-full bg-accent/10"></div>
            <div class="absolute -left-8 -bottom-10 w-32 h-32 rounded-full bg-terracotta/10"></div>
            <div class="relative z-10 text-center lg:text-left">
                <p class="text-[10px] uppercase tracking-[.35em] text-accent mb-3">Live the process</p>
                <h2 class="font-serif text-3xl lg:text-4xl text-bgtheme leading-snug">
                    Now try it<br><span class="italic text-accent">with your own hands.</span>
                </h2>
                <p class="text-accent/60 text-[13px] mt-3">Join one of our workshops and experience the craft first-hand.</p>
            </div>
            <a href="<?php echo esc_url($home_url . 'workshop/'); ?>"
               class="relative z-10 shrink-0 inline-flex items-center gap-3 text-xs uppercase tracking-[.2em] text-textmain bg-bgtheme hover:bg-accent hover:text-white px-8 py-4 rounded-full transition-all duration-300 font-semibold shadow-md">
                Explore Workshops
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>

</div><!-- /wrapper -->

<script>
// Smooth scroll for step anchors
document.querySelectorAll('a[href^="#step-"]').forEach(function(a) {
    a.addEventListener('click', function(e) {
        var target = document.querySelector(this.getAttribute('href'));
        if (!target) return;
        e.preventDefault();
        var offset = 76;
        var top = target.getBoundingClientRect().top + window.scrollY - offset - 20;
        window.scrollTo({ top: top, behavior: 'smooth' });
    });
});

// Fade-up on scroll (IntersectionObserver)
if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
            if (e.isIntersecting) { e.target.style.animationPlayState = 'running'; io.unobserve(e.target); }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -60px 0px' });
    document.querySelectorAll('.fade-up').forEach(function(el) {
        el.style.animationPlayState = 'paused';
        io.observe(el);
    });
}
</script>

<?php get_footer(); ?>
