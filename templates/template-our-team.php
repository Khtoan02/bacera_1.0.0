<?php
/**
 * Template Name: Our Team
 * Description: Meet our team — Bacera
 */

get_header();

// ── If ?member_id is set (Legacy fallback) → Route to Detail Template ───────
if ( ! empty( $_GET['member_id'] ) ) {
    $template = locate_template('templates/template-member-detail.php');
    if ( $template ) {
        include $template;
        exit;
    }
}

// ── NO member_id → render full team listing below ────────────────────────────

// ── Load from DB (bacera_team_members) with static fallback ──────────────────
global $wpdb;
$team_table = $wpdb->prefix . 'bacera_team_members';
$db_exists  = $wpdb->get_var( "SHOW TABLES LIKE '{$team_table}'" ) === $team_table;

$board       = [];
$departments = [];
$member_ids  = []; // name => id map for linking

if ( $db_exists ) {
    $db_members = $wpdb->get_results(
        "SELECT * FROM {$team_table} WHERE is_active = 1 ORDER BY order_index ASC, id ASC",
        ARRAY_A
    ) ?: [];

    if ( ! empty( $db_members ) ) {
        $dept_map = [];
        foreach ( $db_members as $m ) {
            $member_ids[ $m['name'] ] = $m['id'];
            $entry = [ 'name' => $m['name'], 'role' => $m['role'], 'img' => $m['photo_url'], 'id' => $m['id'], 'seo_slug' => $m['seo_slug'] ?? '' ];
            $dept  = $m['department'] ?: '';

            if ( strpos( strtolower( $dept ), 'giám đốc' ) !== false || $dept === 'Ban Giám đốc' ) {
                $board[] = $entry;
            } else {
                $dept_map[ $dept ][] = $entry;
            }
        }
        foreach ( $dept_map as $label => $members ) {
            $departments[] = [ 'label' => $label, 'members' => $members ];
        }
    }
}

// Fallback data (static) — used when DB table empty or not yet seeded
if ( empty( $board ) ) {
    $board = [
        [ 'name' => 'Nam Nguyen', 'role' => 'CEO / Owner',    'img' => 'https://bacera.demo/wp-content/uploads/2026/04/Nam-Nguyen.png',  'id' => 0 ],
        [ 'name' => 'Anh Trinh', 'role' => 'Vice Director',  'img' => 'https://bacera.demo/wp-content/uploads/2026/04/Anh-Trinh.png',  'id' => 0 ],
        [ 'name' => 'Thao Bui',  'role' => 'Vice Director',  'img' => 'https://bacera.demo/wp-content/uploads/2026/04/Thao-Bui.png',   'id' => 0 ],
    ];
}

if ( empty( $departments ) ) {
    $departments = [
        [
            'label'   => 'Sales & Business',
            'members' => [
                [ 'name' => 'Phạm Minh Ngọc', 'role' => 'Sales',     'img' => 'https://bacera.demo/wp-content/uploads/2026/04/Minh-Ngoc.png',  'id' => 0 ],
                [ 'name' => 'Đỗ Lan Chi',       'role' => 'Sales',     'img' => 'https://bacera.demo/wp-content/uploads/2026/04/Lan-Chi.png',   'id' => 0 ],
                [ 'name' => 'Bảo Châu',         'role' => 'Sales',     'img' => 'https://bacera.demo/wp-content/uploads/2026/04/Bao-Chau.png',  'id' => 0 ],
                [ 'name' => 'Ms. Thuy',         'role' => 'Sales',     'img' => 'https://bacera.demo/wp-content/uploads/2026/04/Ms-Thuy.png',   'id' => 0 ],
            ],
        ],
        [
            'label'   => 'Marketing & Creative',
            'members' => [
                [ 'name' => 'Nguyễn Nhật Huy',  'role' => 'Marketing', 'img' => 'https://bacera.demo/wp-content/uploads/2026/04/Nhat-Huy.png',   'id' => 0 ],
                [ 'name' => 'Lê An Khuê',        'role' => 'Marketing', 'img' => 'https://bacera.demo/wp-content/uploads/2026/04/An-Khue.png',   'id' => 0 ],
                [ 'name' => 'Nguyễn Tâm Minh',  'role' => 'Marketing', 'img' => 'https://bacera.demo/wp-content/uploads/2026/04/Tam-Minh.png',  'id' => 0 ],
                [ 'name' => 'Vũ Gia An',         'role' => 'Marketing', 'img' => 'https://bacera.demo/wp-content/uploads/2026/04/Gia-An.png',    'id' => 0 ],
                [ 'name' => 'Đặng Hải Đường',   'role' => 'Marketing', 'img' => 'https://bacera.demo/wp-content/uploads/2026/04/Hai-Duong.png', 'id' => 0 ],
                [ 'name' => 'Vương Bảo Quyên',  'role' => 'Marketing', 'img' => 'https://bacera.demo/wp-content/uploads/2026/04/Bao-Quyen.png', 'id' => 0 ],
                [ 'name' => 'Lê Khánh An',       'role' => 'Marketing', 'img' => 'https://bacera.demo/wp-content/uploads/2026/04/Khanh-An.png', 'id' => 0 ],
                [ 'name' => 'Hoàng Nhật Quang', 'role' => 'Marketing', 'img' => 'https://bacera.demo/wp-content/uploads/2026/04/Nhat-Quang.png', 'id' => 0 ],
            ],
        ],
    ];
}

// ── URLs ──────────────────────────────────────────────────────────────────────
$hero_img    = get_post_meta( get_the_ID(), '_team_hero_image', true )
               ?: 'https://bacera.demo/wp-content/uploads/2026/04/all.png';
$home_url    = home_url( '/' );
$detail_base = get_permalink(); // member detail loads on same page via ?member_id=X
?>

<!-- Embed Tailwind runtime (same pattern as other templates) -->
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
    /* Noise-grain texture */
    .bg-texture {
        background-color: #F7F6F0;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.03'/%3E%3C/svg%3E");
    }

    /* Dashed divider */
    .divider-art {
        background-image: linear-gradient(to right, #D0BCA0 50%, transparent 50%);
        background-size: 10px 1px;
        background-repeat: repeat-x;
    }

    /* Member card image hover zoom */
    .member-card:hover .member-img {
        transform: scale(1.05);
    }

    /* Subtle fade-in for sections */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .fade-up { animation: fadeUp 0.7s ease both; }
</style>

<div class="font-sans antialiased bg-texture text-textmain selection:bg-accentdark selection:text-white w-full overflow-hidden">

    <!-- ═══════════════════════════════════════════════════════
         1. BREADCRUMB + TITLE
    ═══════════════════════════════════════════════════════ -->
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0 pt-14 pb-0">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-textmuted mb-4 tracking-wide">
            <a href="<?php echo esc_url($home_url); ?>"
               class="text-textmain font-medium hover:text-terracotta transition-colors">
                Homepage
            </a>
            <span class="text-accent/60">/</span>
            <span>Our Team</span>
        </nav>

        <!-- Page Title -->
        <h1 class="font-serif text-5xl lg:text-6xl font-medium text-textmain leading-tight tracking-tight mb-12">
            Meet our <span class="italic text-terracotta">team</span>
        </h1>

    </div>

    <!-- ═══════════════════════════════════════════════════════
         2. HERO IMAGE
    ═══════════════════════════════════════════════════════ -->
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0 mb-20">
        <div class="w-full overflow-hidden rounded-2xl aspect-[21/9] bg-[#e2e2e2] shadow-md">
            <img src="<?php echo esc_url($hero_img); ?>"
                 alt="Our Team at Bacera"
                 class="w-full h-full object-cover transition-transform duration-[3s] hover:scale-105">
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         3. BOARD OF DIRECTORS
    ═══════════════════════════════════════════════════════ -->
    <section class="max-w-[1232px] mx-auto px-6 lg:px-0 mb-24">

        <!-- Section label -->
        <div class="flex items-center gap-4 mb-8">
            <span class="w-8 h-[1px] bg-accentdark"></span>
            <h2 class="text-xs uppercase tracking-[0.3em] text-accentdark font-semibold">
                The Board of Directors
            </h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 lg:gap-8">
            <?php foreach ($board as $member):
                if ( !empty($member['seo_slug']) ) {
                    $m_url = home_url( '/our-team/' . $member['seo_slug'] . '/' );
                } else {
                    $m_url = $member['id'] > 0 ? add_query_arg('member_id', $member['id'], $detail_base) : '';
                }
            ?>
            <?php if ($m_url): ?><a href="<?php echo esc_url($m_url); ?>" class="member-card group"><?php else: ?><div class="member-card group"><?php endif; ?>
                <!-- Portrait -->
                <div class="overflow-hidden rounded-xl aspect-[3/4] bg-[#EBE7DF] mb-4 shadow-sm">
                    <img src="<?php echo esc_url($member['img']); ?>"
                         alt="<?php echo esc_attr($member['name']); ?>"
                         class="member-img w-full h-full object-cover transition-transform duration-700">
                </div>
                <!-- Info -->
                <h3 class="text-sm font-semibold text-textmain mb-0.5 <?php echo $m_url ? 'group-hover:text-terracotta transition-colors' : ''; ?>">
                    <?php echo esc_html($member['name']); ?>
                </h3>
                <p class="text-[11px] text-textmuted tracking-wide uppercase">
                    <?php echo esc_html($member['role']); ?>
                </p>
            <?php if ($m_url): ?></a><?php else: ?></div><?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Dashed divider -->
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0 mb-20">
        <div class="w-full h-[1px] divider-art opacity-50"></div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         4. DEPARTMENTS
    ═══════════════════════════════════════════════════════ -->
    <?php foreach ($departments as $dept): ?>
    <section class="max-w-[1232px] mx-auto px-6 lg:px-0 mb-20">

        <!-- Department label -->
        <div class="flex items-center gap-4 mb-8">
            <span class="w-8 h-[1px] bg-accent"></span>
            <h2 class="text-xs uppercase tracking-[0.3em] text-accentdark font-semibold">
                <?php echo esc_html($dept['label']); ?>
            </h2>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5 lg:gap-6 row-gap-10">
            <?php foreach ($dept['members'] as $member):
                if ( !empty($member['seo_slug']) ) {
                    $m_url = home_url( '/our-team/' . $member['seo_slug'] . '/' );
                } else {
                    $m_url = !empty($member['id']) ? add_query_arg('member_id', $member['id'], $detail_base) : '';
                }
            ?>
            <?php if ($m_url): ?><a href="<?php echo esc_url($m_url); ?>" class="member-card group"><?php else: ?><div class="member-card group"><?php endif; ?>
                <!-- Portrait -->
                <div class="overflow-hidden rounded-xl aspect-[3/4] bg-[#EBE7DF] mb-3 shadow-sm">
                    <img src="<?php echo esc_url($member['img']); ?>"
                         alt="<?php echo esc_attr($member['name']); ?>"
                         class="member-img w-full h-full object-cover transition-transform duration-700">
                </div>
                <!-- Info -->
                <h3 class="text-[13px] font-semibold text-textmain mb-0.5 leading-snug <?php echo $m_url ? 'group-hover:text-terracotta transition-colors' : ''; ?>">
                    <?php echo esc_html($member['name']); ?>
                </h3>
                <p class="text-[11px] text-textmuted tracking-wide uppercase">
                    <?php echo esc_html($member['role']); ?>
                </p>
            <?php if ($m_url): ?></a><?php else: ?></div><?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Small divider between departments (skip after last) -->
    <?php if ($dept !== end($departments)): ?>
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0 mb-20">
        <div class="w-full h-[1px] divider-art opacity-30"></div>
    </div>
    <?php endif; ?>

    <?php endforeach; ?>

    <!-- ═══════════════════════════════════════════════════════
         5. CLOSING CTA BANNER
    ═══════════════════════════════════════════════════════ -->
    <div class="max-w-[1232px] mx-auto px-6 lg:px-0 pb-20">
        <div class="bg-textmain rounded-2xl lg:rounded-3xl px-8 lg:px-16 py-14 flex flex-col lg:flex-row items-center justify-between gap-8 relative overflow-hidden">
            <!-- Decorative circle -->
            <div class="absolute -right-16 -top-16 w-48 h-48 rounded-full bg-accent/10 pointer-events-none"></div>
            <div class="absolute -left-8 -bottom-10 w-32 h-32 rounded-full bg-terracotta/10 pointer-events-none"></div>

            <div class="relative z-10 text-center lg:text-left">
                <p class="text-[10px] uppercase tracking-[0.35em] text-accent mb-3">
                    Join the family
                </p>
                <h2 class="font-serif text-3xl lg:text-4xl text-bgtheme leading-snug">
                    Shaped by hands, <span class="italic text-accent">bound by clay.</span>
                </h2>
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
