<?php
/**
 * Template Name: Chi tiết Nhân sự
 * Description: Member detail page - reads ?member_id=X from query string
 *              OR works as a standard page with custom meta _member_id set.
 *
 * Usage patterns:
 *   1. Direct URL: /our-team/?member_id=5
 *   2. WP Page using this template with post meta _member_id = 5
 */

get_header();

global $wpdb;
$table = $wpdb->prefix . 'bacera_team_members';

// ── Resolve member ──────────────────────────────────────────────
$member_id = intval( $_GET['member_id'] ?? get_post_meta( get_the_ID(), '_member_id', true ) );

$member = null;
if ( $member_id > 0 ) {
    $member = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND is_active = 1", $member_id ),
        ARRAY_A
    );
}

// ── Fetch all active members for the "Meet the rest" section ────
$all_members = $wpdb->get_results(
    "SELECT id, name, role, department, photo_url FROM {$table}
     WHERE is_active = 1 ORDER BY order_index ASC, id ASC",
    ARRAY_A
) ?: [];

// Companion members (same dept, excluding current)
$companions = array_filter( $all_members, function( $m ) use ( $member ) {
    return $member && $m['department'] === $member['department'] && $m['id'] != $member['id'];
} );
$companions = array_slice( array_values( $companions ), 0, 4 );

$home_url  = home_url( '/' );
$team_url  = home_url( '/our-team/' );

// ── If member not found, show graceful 404 ──────────────────────
if ( ! $member ):
?>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{colors:{bgtheme:'#f8f7f3',textmain:'#3d2f26',textmuted:'#6b5344',accent:'#c0a28e',terracotta:'#d95f47'},fontFamily:{serif:['"Gowun Batang"','serif'],sans:['"Bricolage Grotesque"','sans-serif']}}}}</script>
<div class="font-sans antialiased min-h-[60vh] flex items-center justify-center" style="background:#f8f7f3;">
    <div class="text-center px-6">
        <p class="text-8xl mb-6">👤</p>
        <h1 class="font-serif text-4xl text-[#3d2f26] mb-4">Không tìm thấy thành viên</h1>
        <p class="text-[#6b5344] mb-8">Thành viên này không tồn tại hoặc đã bị ẩn.</p>
        <a href="<?php echo esc_url( $team_url ); ?>"
           class="inline-flex items-center gap-3 bg-[#d95f47] text-white px-8 py-3 rounded-full text-sm font-semibold hover:bg-[#b84d38] transition-colors">
            ← Về trang Our Team
        </a>
    </div>
</div>
<?php get_footer(); return; endif; ?>

<!-- ═══════════════════════════════════════════════════════════════
     FOUND: Render member detail
═══════════════════════════════════════════════════════════════ -->
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
    background-color: #F7F6F0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.03'/%3E%3C/svg%3E");
}
.divider-art {
    background-image: linear-gradient(to right, #D0BCA0 50%, transparent 50%);
    background-size: 10px 1px;
    background-repeat: repeat-x;
}
</style>

<?php
$name   = $member['name'];
$role   = $member['role'];
$dept   = $member['department'];
$bio    = $member['bio'];
$photo  = $member['photo_url'];
$fallback_photo = 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=600&q=80';
?>

<div class="font-sans antialiased bg-texture text-textmain selection:bg-accentdark selection:text-white w-full overflow-hidden">

    <!-- ═══ 1. BREADCRUMB ═══════════════════════════════════════ -->
    <div class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-10 pt-12 pb-0">
        <nav class="flex items-center gap-2 text-xs text-textmuted mb-10 tracking-wide">
            <a href="<?php echo esc_url( $home_url ); ?>" class="text-textmain font-medium hover:text-terracotta transition-colors">Homepage</a>
            <span class="text-accent/60">/</span>
            <a href="<?php echo esc_url( $team_url ); ?>" class="hover:text-terracotta transition-colors">Our Team</a>
            <span class="text-accent/60">/</span>
            <span><?php echo esc_html( $name ); ?></span>
        </nav>
    </div>

    <!-- ═══ 2. HERO — Photo + Info ══════════════════════════════ -->
    <section class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-10 pb-24">
        <div class="flex flex-col lg:flex-row items-start gap-12 lg:gap-20">

            <!-- Portrait -->
            <div class="w-full lg:w-5/12 relative group">
                <!-- Decorative bg blob -->
                <div class="absolute -inset-4 bg-[#EBE7DF] rounded-tl-[80px] rounded-br-[80px] -z-10 opacity-60 transform -rotate-2 group-hover:rotate-0 transition-transform duration-700"></div>

                <div class="overflow-hidden rounded-tl-[80px] rounded-br-[80px] aspect-[3/4] shadow-2xl bg-[#EBE7DF]">
                    <img src="<?php echo esc_url( $photo ?: $fallback_photo ); ?>"
                         alt="<?php echo esc_attr( $name ); ?>"
                         class="w-full h-full object-cover object-top transition-transform duration-[2s] group-hover:scale-105"
                         onerror="this.src='<?php echo esc_js( $fallback_photo ); ?>'">
                </div>

                <!-- Dept badge -->
                <?php if ( $dept ): ?>
                <div class="absolute -bottom-4 left-8 bg-white/90 backdrop-blur-md px-5 py-2 rounded-full shadow-lg border border-white/20">
                    <span class="text-xs uppercase tracking-[0.25em] text-accentdark font-semibold">
                        <?php echo esc_html( $dept ); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Text content -->
            <div class="w-full lg:w-7/12 lg:pt-6">

                <!-- Label -->
                <div class="flex items-center gap-3 mb-6">
                    <span class="w-8 h-[1px] bg-accentdark"></span>
                    <span class="text-[10px] uppercase tracking-[0.35em] text-accentdark font-medium">
                        Team member
                    </span>
                </div>

                <!-- Name -->
                <h1 class="font-serif text-5xl lg:text-6xl text-textmain leading-tight mb-3">
                    <?php echo esc_html( $name ); ?>
                </h1>

                <!-- Role -->
                <?php if ( $role ): ?>
                <p class="text-base text-terracotta font-semibold tracking-wide mb-8">
                    <?php echo esc_html( $role ); ?>
                </p>
                <?php endif; ?>

                <!-- Divider -->
                <div class="w-full h-[1px] divider-art opacity-50 mb-8"></div>

                <!-- Bio -->
                <?php if ( $bio ): ?>
                <div class="space-y-4 text-[15px] leading-[1.9] text-textmuted font-light pl-5 border-l-2 border-accent/30 mb-10">
                    <?php
                    // Support multi-paragraphs separated by newlines
                    $paragraphs = array_filter( array_map( 'trim', explode( "\n", $bio ) ) );
                    foreach ( $paragraphs as $p ):
                    ?>
                    <p><?php echo esc_html( $p ); ?></p>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-textmuted text-sm italic mb-10 pl-5">
                    Chưa có thông tin giới thiệu.
                </p>
                <?php endif; ?>

                <!-- CTA: back to team -->
                <a href="<?php echo esc_url( $team_url ); ?>"
                   class="inline-flex items-center gap-3 group text-xs uppercase tracking-[0.2em] text-textmain hover:text-terracotta transition-colors">
                    <span class="w-10 h-10 rounded-full border border-accent flex items-center justify-center group-hover:border-terracotta transition-colors">
                        <svg class="w-4 h-4 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </span>
                    Meet the full team
                </a>

            </div>
        </div>
    </section>

    <!-- ═══ 3. SAME DEPARTMENT ══════════════════════════════════ -->
    <?php if ( ! empty( $companions ) ): ?>
    <section class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-10 pb-24">
        <div class="w-full h-[1px] divider-art opacity-40 mb-16"></div>

        <div class="flex items-center gap-4 mb-10">
            <span class="w-8 h-[1px] bg-accentdark"></span>
            <h2 class="text-xs uppercase tracking-[0.3em] text-accentdark font-semibold">
                Cùng phòng ban · <?php echo esc_html( $dept ); ?>
            </h2>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5 lg:gap-6">
            <?php foreach ( $companions as $c ):
                $c_url = add_query_arg( 'member_id', $c['id'], $team_url . 'member/' );
                // Use the our-team page URL + member_id param
                $c_url = add_query_arg( 'member_id', $c['id'], home_url('/our-team-member/') );
                // Fallback: pass as query param on current page
                $c_url = home_url( '/?page_id=' . get_the_ID() . '&member_id=' . $c['id'] );
                // Best approach: absolute URL
                $c_url = add_query_arg( 'member_id', $c['id'], get_permalink() );
            ?>
            <a href="<?php echo esc_url( $c_url ); ?>" class="group flex flex-col text-left">
                <div class="overflow-hidden rounded-xl aspect-[3/4] bg-[#EBE7DF] mb-3 shadow-sm">
                    <img src="<?php echo esc_url( $c['photo_url'] ?: $fallback_photo ); ?>"
                         alt="<?php echo esc_attr( $c['name'] ); ?>"
                         class="w-full h-full object-cover object-top transition-transform duration-700 group-hover:scale-105"
                         onerror="this.src='<?php echo esc_js( $fallback_photo ); ?>'">
                </div>
                <h3 class="text-[13px] font-semibold text-textmain mb-0.5 group-hover:text-terracotta transition-colors leading-snug">
                    <?php echo esc_html( $c['name'] ); ?>
                </h3>
                <p class="text-[11px] text-textmuted tracking-wide uppercase">
                    <?php echo esc_html( $c['role'] ?: '–' ); ?>
                </p>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ═══ 4. CTA BANNER ═══════════════════════════════════════ -->
    <div class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-10 pb-20">
        <div class="bg-textmain rounded-2xl lg:rounded-3xl px-8 lg:px-16 py-14
                    flex flex-col lg:flex-row items-center justify-between gap-8 relative overflow-hidden">
            <div class="absolute -right-16 -top-16 w-48 h-48 rounded-full bg-accent/10 pointer-events-none"></div>
            <div class="absolute -left-8 -bottom-10 w-32 h-32 rounded-full bg-terracotta/10 pointer-events-none"></div>

            <div class="relative z-10 text-center lg:text-left">
                <p class="text-[10px] uppercase tracking-[0.35em] text-accent mb-3">Join the family</p>
                <h2 class="font-serif text-3xl lg:text-4xl text-bgtheme leading-snug">
                    Shaped by hands, <span class="italic text-accent">bound by clay.</span>
                </h2>
            </div>

            <a href="<?php echo esc_url( home_url( '/workshop/' ) ); ?>"
               class="relative z-10 shrink-0 inline-flex items-center gap-3 text-xs uppercase tracking-[0.2em]
                      text-textmain bg-bgtheme hover:bg-accent hover:text-white
                      px-7 py-4 rounded-full transition-all duration-300 font-medium shadow-md">
                Explore Workshops
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>

</div><!-- /wrapper -->

<?php get_footer(); ?>
