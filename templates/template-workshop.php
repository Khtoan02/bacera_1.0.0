<?php
/**
 * Template Name: Workshop
 * Description: Workshop listing page for Bacera
 */
get_header();

/* ─── Lấy dữ liệu Workshop từ WordPress CPT + Custom Slots Table ── */
global $wpdb;
$ts = $wpdb->prefix . 'bacera_workshop_slots';

// Lấy tất cả Workshop CPT đã publish
$wp_posts = get_posts([
    'post_type'      => 'workshop',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'ASC',
]);

// Tổng hợp số slot từ bảng custom (dùng Post ID làm workshop_id)
$workshops = [];
foreach ($wp_posts as $post) {
    $pid = $post->ID;

    // Tổng hợp slot sắp tới (open/full, chưa bị cancelled, từ hôm nay trở đi)
    $slot_agg = $wpdb->get_row($wpdb->prepare(
        "SELECT 
            COALESCE(SUM(booked_seats), 0) AS total_booked,
            COALESCE(SUM(total_seats),  0) AS total_capacity,
            COUNT(id)                       AS upcoming_slots
         FROM {$ts}
         WHERE workshop_id = %d
           AND slot_date >= CURDATE()
           AND status != 'cancelled'",
        $pid
    ), ARRAY_A);

    $thumbnail = get_post_meta($pid, '_thumbnail_url', true) 
                 ?: get_the_post_thumbnail_url($pid, 'large') 
                 ?: 'https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=800';

    // Link tới trang single-workshop.php (CPT permalink)
    $detail_link = get_permalink($pid);

    $workshops[] = [
        'id'          => $pid,
        'title'       => $post->post_title,
        'description' => wp_trim_words(get_post_meta($pid, '_description', true) ?: $post->post_excerpt ?: strip_tags($post->post_content), 20, '…'),
        'pricing'     => get_post_meta($pid, '_price', true) ?: 'Liên hệ',
        'image'       => $thumbnail,
        'tag'         => 'workshop',
        'link'        => $detail_link,
        'badge'       => '',
        'bookedSlots' => (int)($slot_agg['total_booked'] ?? 0),
        'totalSlots'  => (int)($slot_agg['total_capacity'] ?? 0),
    ];
}

// Nếu chưa có workshop nào trong DB, hiện fallback data demo
$use_fallback = empty($workshops);
if ($use_fallback) {
    $workshops = [
        [
            'id'          => 0,
            'title'       => 'Pottery Wheel Throwing',
            'description' => 'A peaceful, hands-on journey for beginners and curious minds. Feel the clay respond as the wheel spins.',
            'pricing'     => 'From 380,000đ / Person',
            'image'       => 'https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=800',
            'tag'         => 'workshop',
            'link'        => '#',
            'badge'       => 'Most Popular',
            'bookedSlots' => 13,
            'totalSlots'  => 16,
        ],
        [
            'id'          => 0,
            'title'       => 'Hand-building Pottery',
            'description' => 'Shape, carve, and coil your own ceramic piece entirely by hand. No wheel required — just your imagination.',
            'pricing'     => 'From 380,000đ / Person',
            'image'       => 'https://images.unsplash.com/photo-1610701596087-0b1a039735d9?auto=format&fit=crop&q=80&w=800',
            'tag'         => 'workshop',
            'link'        => '#',
            'badge'       => '',
            'bookedSlots' => 5,
            'totalSlots'  => 16,
        ],
        [
            'id'          => 0,
            'title'       => 'Hanoi School Class',
            'description' => 'A curated studio visit for students — explore clay techniques, tool use, and the story of Vietnamese ceramics.',
            'pricing'     => 'Liên hệ',
            'image'       => 'https://images.unsplash.com/photo-1506806732259-39c2d0268443?auto=format&fit=crop&q=80&w=800',
            'tag'         => 'workshop',
            'link'        => '#',
            'badge'       => '',
            'bookedSlots' => 0,
            'totalSlots'  => 30,
        ],
        [
            'id'          => 0,
            'title'       => 'Team-building Pottery',
            'description' => 'A one-of-a-kind bonding session for your team — slow down, laugh together, and leave with something made.',
            'pricing'     => 'Liên hệ',
            'image'       => 'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?auto=format&fit=crop&q=80&w=800',
            'tag'         => 'workshop',
            'link'        => '#',
            'badge'       => '',
            'bookedSlots' => 0,
            'totalSlots'  => 0,
        ],
    ];
}
?>

<main id="primary" class="site-main overflow-x-hidden">


<!-- ══════════════════════════════════════════════════════════════════
     §1  HERO — Full-bleed cinematic with strong visual weight
══════════════════════════════════════════════════════════════════════ -->
<section id="workshop-hero"
         class="relative w-full flex items-center overflow-hidden bg-primary-900" style="min-height:100vh; padding-top:8rem; padding-bottom:6rem;">

    <!-- BG photography -->
    <div class="absolute inset-0">
        <img src="https://images.unsplash.com/photo-1565193566173-7a0e46e4d7a8?auto=format&fit=crop&q=80&w=1800"
             alt="" aria-hidden="true"
             class="w-full h-full object-cover object-center opacity-50">
        <!-- Multi-layer gradient for text legibility -->
        <div class="absolute inset-0" style="background: linear-gradient(to top, rgb(61,47,38) 0%, rgba(61,47,38,0.65) 50%, rgba(61,47,38,0.2) 100%);"></div>
        <div class="absolute inset-0" style="background: linear-gradient(to right, rgba(61,47,38,0.8) 0%, rgba(61,47,38,0.3) 60%, transparent 100%);"></div>
    </div>

    <!-- Floating stat pills — desktop only -->
    <div class="absolute hidden lg:flex flex-col gap-3 z-10 right-8" style="top:50%; transform:translateY(-50%)">
        <?php
        $stats = [['6+', 'Years of craft'], ['500+', 'Pieces made'], ['1,200+', 'Happy makers']];
        foreach ($stats as $s):
        ?>
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/10 backdrop-blur-md border border-white/20
                    hover:bg-white/20 transition-colors duration-300">
            <span class="text-stone-200 text-xl font-serif font-normal leading-none"><?php echo $s[0]; ?></span>
            <span class="text-primary-300 text-xs font-sans tracking-wide"><?php echo $s[1]; ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Content -->
    <div class="relative z-10 w-full bacera-container">
        <div class="max-w-xl flex flex-col gap-6 items-start">

            <!-- Eyebrow -->
            <div class="flex items-center gap-3">
                <span class="inline-block w-6 h-px bg-accent-400"></span>
                <span class="text-accent-400 text-xs font-sans font-medium tracking-[0.2em] uppercase">
                    Bacera Workshops · Hà Nội
                </span>
            </div>

            <!-- H1 -->
            <h1 class="text-stone-200 font-serif font-normal tracking-tight" style="font-size:clamp(36px,5vw,68px); line-height:1.1">
                Shape Your<br>Story with Clay
            </h1>

            <!-- Sub -->
            <p class="text-primary-200 text-base md:text-lg font-sans font-normal leading-relaxed opacity-90 max-w-lg">
                Step into a space where time slows, hands listen, and clay breathes. Every session is a story waiting to be shaped.
            </p>

            <!-- CTAs -->
            <div class="flex flex-wrap items-center gap-3 pt-2">
                <a href="#workshop-grid"
                   class="inline-flex items-center gap-2.5 px-6 py-4 bg-accent-500 hover:bg-accent-600 text-white text-[15px] font-medium font-sans rounded-xl transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg shadow-accent-500/30">
                    Browse Workshops
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" transform="rotate(-90 12 12)"/>
                    </svg>
                </a>
                <a href="#workshop-faq"
                   class="inline-flex items-center gap-2 px-6 py-4 text-stone-200 text-[15px] font-medium font-sans rounded-xl border border-white/30 hover:border-white/60 hover:bg-white/10 transition-all duration-300">
                    Learn more
                </a>
            </div>
        </div>
    </div>

    <!-- Scroll indicator -->
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-1.5 text-primary-300 opacity-60 hover:opacity-100 transition-opacity" aria-hidden="true">
        <span class="text-[10px] font-sans tracking-widest uppercase">Scroll</span>
        <div class="animate-bounce">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </div>
</section>


<!-- ══════════════════════════════════════════════════════════════════
     §2  WORKSHOP CARDS — Light section for maximum card contrast
══════════════════════════════════════════════════════════════════════ -->
<section id="workshop-grid" class="section-pad bg-neutral-100">
    <div class="bacera-container">

        <!-- Section header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-12">
            <div class="flex flex-col gap-3 max-w-lg">
                <span class="text-accent-500 text-xs font-sans font-medium tracking-[0.2em] uppercase flex items-center gap-2">
                    <span class="w-4 h-px bg-accent-500"></span>Our Sessions
                </span>
                <h2 class="text-stone-800 text-3xl md:text-4xl font-serif font-normal leading-snug">
                    Choose Your Workshop
                </h2>
                <p class="text-stone-500 text-body-reg font-sans leading-relaxed">
                    Four unique ways to get your hands in clay — from solo beginner sessions to bespoke group events.
                </p>
            </div>

            <!-- Filter pills -->
            <div class="flex gap-2 flex-wrap shrink-0">
                <?php
                if ($use_fallback) {
                    // Fallback: bộ lọc demo theo tag cứng
                    $filters = [
                        ['key' => 'all',      'label' => 'All Sessions'],
                        ['key' => 'beginner', 'label' => 'Beginner'],
                        ['key' => 'group',    'label' => 'Group'],
                        ['key' => 'school',   'label' => 'School'],
                    ];
                } else {
                    // Từ DB: chỉ show "Tất cả" (category chưa có trong schema)
                    $filters = [
                        ['key' => 'all',      'label' => 'Tất cả'],
                        ['key' => 'workshop', 'label' => 'Workshop'],
                    ];
                }
                foreach ($filters as $i => $f):
                ?>
                <button id="filter-<?php echo esc_attr($f['key']); ?>"
                        class="ws-filter-btn shrink-0 px-4 py-2 rounded-full text-sm font-medium font-sans border transition-all duration-200
                               <?php echo $i === 0
                                   ? 'bg-stone-800 text-white border-stone-800'
                                   : 'bg-white text-stone-500 border-stone-200 hover:border-stone-400 hover:text-stone-700'; ?>"
                        data-filter="<?php echo esc_attr($f['key']); ?>">
                    <?php echo esc_html($f['label']); ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Cards grid — 4 col desktop / 2 col tablet / 1 col mobile -->
        <div id="ws-cards-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <?php foreach ($workshops as $ws): ?>
            <div class="ws-card-wrap relative" data-tag="<?php echo esc_attr($ws['tag']); ?>">
                <?php
                // Truyền bookedSlots + totalSlots cho component
                get_template_part('app/Views/components/workshop-card', null, [
                    'title'       => $ws['title'],
                    'description' => $ws['description'],
                    'pricing'     => $ws['pricing'],
                    'image'       => $ws['image'],
                    'link'        => $ws['link'],
                    'bookedSlots' => $ws['bookedSlots'],
                    'totalSlots'  => $ws['totalSlots'],
                ]);
                ?>
                <?php if (!empty($ws['badge'])): ?>
                <div class="absolute top-3 left-3 z-30 pointer-events-none">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-accent-500 text-white shadow-sm">
                        <?php echo esc_html($ws['badge']); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Empty state (hidden by default) -->
        <div id="ws-empty" class="hidden py-16 text-center">
            <p class="text-stone-500 font-sans">No workshops match this filter — <button class="ws-filter-btn text-accent-500 underline underline-offset-4" data-filter="all">show all</button>.</p>
        </div>

    </div>
</section>


<!-- ══════════════════════════════════════════════════════════════════
     §3  VALUE Props — Split layout with editorial photography
══════════════════════════════════════════════════════════════════════ -->
<section id="workshop-why" class="py-24 md:py-32 bg-stone-50 border-t border-stone-200/50">
    <div class="bacera-container">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 lg:gap-24 items-center">

            <!-- Image side -->
            <div class="relative group">
                <div class="relative rounded-3xl overflow-hidden aspect-[4/3] shadow-2xl">
                    <img src="https://images.unsplash.com/photo-1578749556568-bc2c40e68b61?auto=format&fit=crop&q=80&w=900"
                         alt="Pottery class at Bacera"
                         class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-t from-primary-900/50 to-transparent opacity-60"></div>
                </div>
                <!-- Floating card -->
                <div class="absolute -bottom-8 -right-4 md:-right-10 bg-white/95 backdrop-blur-md rounded-2xl p-5 shadow-[0_20px_40px_-5px_rgba(0,0,0,0.15)] border border-white/50 flex items-center gap-4 max-w-[240px] transition-transform duration-700 ease-out group-hover:-translate-y-3 z-10">
                    <div class="w-14 h-14 rounded-2xl bg-primary-50 border border-primary-100/50 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-stone-900 text-base font-semibold font-sans">1,200+ makers</p>
                        <p class="text-stone-500 text-sm font-sans mt-0.5">have shaped their story</p>
                    </div>
                </div>
            </div>

            <!-- Text side -->
            <div class="flex flex-col gap-12 pt-12 lg:pt-0">
                <div class="flex flex-col gap-5">
                    <span class="text-accent-500 text-sm font-sans font-semibold tracking-[0.2em] uppercase flex items-center gap-3">
                        <span class="w-8 h-[2px] bg-accent-500 rounded-full"></span>Why Bacera
                    </span>
                    <h2 class="text-stone-900 text-4xl md:text-5xl lg:text-[56px] font-serif font-normal leading-[1.1] tracking-tight">
                        A workshop that actually slows you down
                    </h2>
                    <p class="text-stone-500 text-lg md:text-xl font-sans leading-relaxed max-w-lg mt-2">
                        We don't rush. We don't teach shortcuts. Every session is a space to disconnect from the noise and reconnect with your hands.
                    </p>
                </div>

                <div class="flex flex-col gap-8">
                    <?php
                    $perks = [
                        [
                            'title' => 'Small groups — maximum 8',
                            'desc'  => 'We cap every session so the instructor can give you real, personal attention.',
                            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>',
                        ],
                        [
                            'title' => 'Guided by real artisans',
                            'desc'  => 'Our instructors have 10+ years of studio practice and a genuine love for teaching.',
                            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"/>',
                        ],
                        [
                            'title' => 'Take your piece home',
                            'desc'  => 'Your creation is kiln-fired and ready for pickup in 2–3 weeks. A real piece, not a memory.',
                            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>',
                        ],
                    ];
                    foreach ($perks as $p):
                    ?>
                    <div class="flex items-start gap-5 group/perk">
                        <div class="shrink-0 w-12 h-12 rounded-2xl bg-white border border-stone-200 shadow-sm flex items-center justify-center mt-0.5 transition-colors duration-300 group-hover/perk:bg-primary-50 group-hover/perk:border-primary-200">
                            <svg class="w-6 h-6 text-primary-600 transition-transform duration-300 group-hover/perk:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <?php echo $p['icon']; ?>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-stone-900 text-lg font-semibold font-sans leading-snug mb-1.5"><?php echo esc_html($p['title']); ?></h3>
                            <p class="text-stone-500 text-base font-sans leading-relaxed"><?php echo esc_html($p['desc']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ══════════════════════════════════════════════════════════════════
     §4  HOW IT WORKS — Numbered timeline on primary-900
══════════════════════════════════════════════════════════════════════ -->
<section id="workshop-how" class="section-pad bg-primary-900">
    <div class="bacera-container">

        <div class="text-center mb-16 flex flex-col items-center gap-3">
            <span class="text-accent-400 text-xs font-sans font-medium tracking-[0.2em] uppercase flex items-center gap-2">
                <span class="w-4 h-px bg-accent-400"></span>The Process<span class="w-4 h-px bg-accent-400"></span>
            </span>
            <h2 class="text-stone-200 text-3xl font-serif font-normal">How It Works</h2>
            <p class="text-primary-400 text-base font-sans opacity-80 max-w-sm">Three simple steps. A lifetime of memory.</p>
        </div>

        <div class="relative grid grid-cols-1 md:grid-cols-3 gap-0">

            <!-- Connector line -->
            <div class="hidden md:block absolute h-px bg-primary-700" style="top:2.5rem; left:calc(16.67% + 2rem); right:calc(16.67% + 2rem); opacity:0.5;" aria-hidden="true"></div>

            <?php
            $steps = [
                [
                    'num'   => '01',
                    'title' => 'Choose a Workshop',
                    'desc'  => 'Browse our four sessions — Wheel Throwing, Hand-building, School Class, or Team Event. Pick what calls to you.',
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>',
                ],
                [
                    'num'   => '02',
                    'title' => 'Book Your Date',
                    'desc'  => 'Select a date and time that suits you. We\'ll send a confirmation with everything you need to know before arriving.',
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>',
                ],
                [
                    'num'   => '03',
                    'title' => 'Shape & Take Home',
                    'desc'  => 'Arrive, get your hands in clay, and leave with your piece kiln-queued — ready for pickup in 2–3 weeks.',
                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/>',
                ],
            ];
            foreach ($steps as $idx => $step):
            ?>
            <div class="relative flex flex-col items-center text-center gap-4 px-6 lg:px-10
                        <?php echo $idx < count($steps) - 1 ? 'pb-10 md:pb-0 border-b md:border-b-0 border-primary-800' : ''; ?>
                        pt-10 md:pt-0">
                <!-- Number bubble -->
                <div class="relative z-10 w-20 h-20 rounded-full border border-primary-700 bg-primary-800
                            flex flex-col items-center justify-center gap-0.5 group-hover:border-accent-500 transition-colors">
                    <svg class="w-6 h-6 text-accent-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <?php echo $step['icon']; ?>
                    </svg>
                    <span class="text-primary-500 text-[10px] font-mono tracking-widest"><?php echo $step['num']; ?></span>
                </div>
                <div class="flex flex-col gap-2 max-w-[220px] mx-auto">
                    <h3 class="text-stone-200 text-[17px] font-medium font-sans leading-snug">
                        <?php echo esc_html($step['title']); ?>
                    </h3>
                    <p class="text-primary-400 text-sm font-sans leading-relaxed opacity-80">
                        <?php echo esc_html($step['desc']); ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ══════════════════════════════════════════════════════════════════
     §5  SOCIAL PROOF — Testimonials with large quote treatment
══════════════════════════════════════════════════════════════════════ -->
<section id="workshop-reviews" class="section-pad bg-neutral-200 border-t border-stone-200">
    <div class="bacera-container">

        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-14">
            <div class="flex flex-col gap-3">
                <span class="text-accent-500 text-xs font-sans font-medium tracking-[0.2em] uppercase flex items-center gap-2">
                    <span class="w-4 h-px bg-accent-500"></span>Maker Stories
                </span>
                <h2 class="text-stone-800 text-3xl md:text-4xl font-serif font-normal">
                    What they made — and felt
                </h2>
            </div>
            <!-- Rating summary -->
            <div class="flex items-center gap-3 shrink-0">
                <div class="flex gap-0.5">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                    <svg class="w-5 h-5 text-accent-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    <?php endfor; ?>
                </div>
                <span class="text-stone-700 text-sm font-semibold font-sans">5.0</span>
                <span class="text-stone-400 text-sm font-sans">· 120+ reviews</span>
            </div>
        </div>

        <!-- Featured review — large format -->
        <div class="mb-8 p-8 md:p-12 rounded-2xl bg-white border border-stone-200 relative overflow-hidden">
            <!-- Decorative quote mark -->
            <div class="absolute top-6 right-8 text-[120px] leading-none font-serif text-stone-100 select-none pointer-events-none" aria-hidden="true">"</div>
            <div class="grid grid-cols-1 md:grid-cols-[1fr_2fr] gap-8 items-center relative z-10">
                <div class="flex flex-col gap-4 items-start">
                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=300"
                         alt="James & Laura"
                         class="w-20 h-20 rounded-full object-cover ring-4 ring-stone-100">
                    <div>
                        <p class="text-stone-800 font-semibold font-sans text-[15px]">James &amp; Laura</p>
                        <p class="text-accent-500 text-xs font-sans tracking-wide mt-0.5">Pottery Wheel Throwing</p>
                    </div>
                    <div class="flex gap-0.5">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                        <svg class="w-4 h-4 text-accent-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <?php endfor; ?>
                    </div>
                </div>
                <blockquote class="text-stone-700 text-lg md:text-xl font-serif font-normal leading-relaxed italic">
                    "My girlfriend and I signed up for fun. We didn't expect the wheel to make us so quiet, so present. She shaped a bowl. I shaped something odd — but it felt like we made something real together."
                </blockquote>
            </div>
        </div>

        <!-- 2 secondary reviews -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <?php
            $secondary_reviews = [
                [
                    'text' => "I've been collecting ceramics for years, but Bacera's pieces carry something different — a warmth you feel the moment you hold them.",
                    'name' => 'Linh Nguyen',
                    'role' => 'Hand-building Class',
                    'img'  => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&q=80&w=200',
                ],
                [
                    'text' => "We booked a team-building workshop and it was unlike anything we'd tried before. Our team came away quieter, closer, and with beautiful pieces made themselves.",
                    'name' => 'Minh Tuan — Marketing Team',
                    'role' => 'Team-building Session',
                    'img'  => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=200',
                ],
            ];
            foreach ($secondary_reviews as $r):
            ?>
            <div class="flex flex-col gap-5 p-6 md:p-7 rounded-2xl bg-white border border-stone-200">
                <div class="flex gap-0.5">
                    <?php for ($s = 0; $s < 5; $s++): ?>
                    <svg class="w-4 h-4 text-accent-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    <?php endfor; ?>
                </div>
                <p class="text-stone-600 text-[15px] font-sans leading-relaxed italic flex-1">
                    "<?php echo esc_html($r['text']); ?>"
                </p>
                <div class="flex items-center gap-3 pt-4 border-t border-stone-100">
                    <img src="<?php echo esc_url($r['img']); ?>" alt="<?php echo esc_attr($r['name']); ?>"
                         class="w-10 h-10 rounded-full object-cover">
                    <div>
                        <p class="text-stone-800 text-sm font-semibold font-sans"><?php echo esc_html($r['name']); ?></p>
                        <p class="text-accent-500 text-xs font-sans mt-0.5"><?php echo esc_html($r['role']); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ══════════════════════════════════════════════════════════════════
     §6  CTA BANNER — Full-bleed with subtle texture
══════════════════════════════════════════════════════════════════════ -->
<section id="workshop-cta" class="relative py-28 md:py-40 overflow-hidden bg-primary-900 group">

    <!-- Background photo  -->
    <div class="absolute inset-0 overflow-hidden">
        <img src="https://images.unsplash.com/photo-1590400516641-52481c67d302?auto=format&fit=crop&q=80&w=1920"
             alt="" aria-hidden="true"
             class="w-full h-full object-cover opacity-30 transition-transform duration-[20s] ease-linear group-hover:scale-110">
        <div class="absolute inset-0 bg-primary-900/40 mix-blend-multiply"></div>
        <div class="absolute inset-0" style="background: linear-gradient(to right, rgb(45,36,30) 0%, rgba(45,36,30,0.85) 40%, rgba(45,36,30,0.4) 100%);"></div>
    </div>

    <div class="relative z-10 bacera-container">
        <div class="max-w-2xl flex flex-col gap-8 items-start">
            <span class="text-accent-400 text-sm font-sans font-semibold tracking-[0.2em] uppercase flex items-center gap-3">
                <span class="w-8 h-[2px] bg-accent-400 rounded-full"></span>Ready to begin
            </span>
            <h2 class="text-white text-5xl md:text-6xl lg:text-7xl font-serif font-normal leading-[1.1] tracking-tight text-shadow-sm">
                Ready to shape something beautiful?
            </h2>
            <p class="text-primary-200 text-lg md:text-xl font-sans leading-relaxed opacity-95 max-w-lg">
                Seats fill quickly — especially weekend sessions. Reserve your spot today. No experience needed. Just your hands.
            </p>
            <div class="flex flex-wrap gap-4 pt-4">
                <a href="#workshop-grid"
                   class="inline-flex items-center gap-2.5 px-8 py-4 bg-accent-500 hover:bg-accent-600 text-white text-base font-medium font-sans rounded-xl transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_12px_24px_-8px_rgba(239,68,68,0.5)]">
                    Explore Workshops
                </a>
                <a href="#"
                   class="inline-flex items-center gap-2 px-8 py-4 text-stone-100 text-base font-medium font-sans rounded-xl border border-primary-400/50 hover:border-primary-300 hover:bg-white/10 backdrop-blur-sm transition-all duration-300">
                    Contact us
                </a>
            </div>
        </div>
    </div>
</section>


<!-- ══════════════════════════════════════════════════════════════════
     §7  FAQ — Clean accordion on white
══════════════════════════════════════════════════════════════════════ -->
<section id="workshop-faq" class="section-pad bg-white border-t border-stone-100"
         x-data="{ opened: 0 }">
    <div class="bacera-container">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">

            <!-- Sticky info col -->
            <div class="flex flex-col gap-5" style="position:sticky; top:7rem;">
                <span class="text-accent-500 text-xs font-sans font-medium tracking-[0.2em] uppercase flex items-center gap-2">
                    <span class="w-4 h-px bg-accent-500"></span>FAQ
                </span>
                <h2 class="text-stone-800 text-3xl md:text-4xl font-serif font-normal leading-snug">
                    Questions answered
                </h2>
                <p class="text-stone-500 text-base font-sans leading-relaxed">
                    Can't find what you're looking for? Drop us a message and we'll get back to you within one working day.
                </p>
                <a href="#"
                   class="self-start inline-flex items-center gap-2 px-5 py-3 bg-stone-800 text-white text-sm font-medium font-sans rounded-xl transition-colors duration-200 hover:bg-stone-700">
                    Ask a question
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>

            <!-- Accordion col -->
            <div class="flex flex-col" style="border-top: 1px solid #f5f5f4;">
                <?php
                $faqs = [
                    ['q' => 'Do I need any prior pottery experience?',
                     'a' => 'Not at all. All sessions are designed for complete beginners. Our instructors start from the very basics and progress at a comfortable pace.'],
                    ['q' => 'What should I wear?',
                     'a' => 'Comfortable clothes you don\'t mind getting clay on. Clay washes out easily, and we provide aprons — but it\'s pottery, so expect a little mess. It\'s part of the fun.'],
                    ['q' => 'How long does a session typically last?',
                     'a' => 'Most single sessions run 2–3 hours. School visits and Team-building sessions are custom — contact us for bespoke scheduling.'],
                    ['q' => 'When will I receive my finished ceramic piece?',
                     'a' => 'After the session, your piece is dried and kiln-fired over 2–3 weeks. We\'ll email you when it\'s ready to collect or arrange delivery.'],
                    ['q' => 'Can I book a private or group session?',
                     'a' => 'Absolutely. We offer private bookings for special occasions, corporate team events, and school visits. Use the "Contact us" button on any card, or message us directly.'],
                    ['q' => 'Is there a minimum age for participants?',
                     'a' => 'We welcome participants aged 12 and above for our public sessions. For younger children, our Hanoi School Class format applies — please reach out to discuss.'],
                ];
                foreach ($faqs as $i => $faq):
                ?>
                <div>
                    <button class="w-full flex items-center justify-between gap-6 py-5 text-left group"
                            @click="opened = opened === <?php echo $i; ?> ? null : <?php echo $i; ?>">
                        <span class="text-stone-800 text-[15px] md:text-base font-medium font-sans leading-snug
                                     group-hover:text-accent-500 transition-colors duration-200"
                              :class="opened === <?php echo $i; ?> ? 'text-accent-500' : ''">
                            <?php echo esc_html($faq['q']); ?>
                        </span>
                        <span class="shrink-0 w-7 h-7 rounded-full border border-stone-200 flex items-center justify-center
                                     transition-all duration-300"
                              :class="opened === <?php echo $i; ?> ? 'bg-accent-500 border-accent-500 rotate-180' : 'bg-stone-50 group-hover:border-stone-400'">
                            <svg class="w-3.5 h-3.5 transition-colors"
                                 :class="opened === <?php echo $i; ?> ? 'text-white' : 'text-stone-500'"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </span>
                    </button>
                    <div x-show="opened === <?php echo $i; ?>"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         style="display:none"
                         class="pb-6">
                        <p class="text-stone-500 text-sm md:text-base font-sans leading-relaxed">
                            <?php echo esc_html($faq['a']); ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>



<!-- ═══════════════════════════════════════════════════════
     SEO CONTENT BLOCK
═══════════════════════════════════════════════════════ -->
<?php get_template_part('app/Views/components/seo-content', null, ['title' => 'Workshop']); ?>

</main>

<?php
get_template_part('app/Views/components/modal', null, [
    'title'      => 'Ready to start your workshop?',
    'desc'       => 'Once confirmed, you\'ll receive an email with everything you need — including what to bring and where to find us.',
    'confirm'    => 'Confirm Booking',
    'cancel'     => 'Not yet',
    'is_preview' => false,
]);
?>

<script>
/* ─── Workshop Filter Logic ─────────────────────────────────────── */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var btns  = document.querySelectorAll('.ws-filter-btn');
        var wraps = document.querySelectorAll('.ws-card-wrap');
        var empty = document.getElementById('ws-empty');

        function setActive(btn) {
            btns.forEach(function (b) {
                b.classList.remove('bg-stone-800', 'text-white', 'border-stone-800');
                b.classList.add('bg-white', 'text-stone-500', 'border-stone-200');
            });
            btn.classList.add('bg-stone-800', 'text-white', 'border-stone-800');
            btn.classList.remove('bg-white', 'text-stone-500', 'border-stone-200');
        }

        function filterCards(filter) {
            var visible = 0;
            wraps.forEach(function (w) {
                var match = filter === 'all' || w.dataset.tag === filter;
                if (match) {
                    w.style.display = '';
                    requestAnimationFrame(function () {
                        w.style.opacity = '1';
                        w.style.transform = 'translateY(0)';
                    });
                    visible++;
                } else {
                    w.style.opacity = '0';
                    w.style.transform = 'translateY(8px)';
                    setTimeout(function () { w.style.display = 'none'; }, 220);
                }
                w.style.transition = 'opacity .22s ease, transform .22s ease';
            });
            empty && (empty.style.display = visible === 0 ? '' : 'none');
        }

        btns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setActive(btn);
                filterCards(btn.dataset.filter);
            });
        });
    });
}());
</script>

<?php get_footer(); ?>
