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
$member_slug = isset($_GET['member_slug']) ? sanitize_text_field($_GET['member_slug']) : '';
$member_id   = intval( $_GET['member_id'] ?? get_post_meta( get_the_ID(), '_member_id', true ) );

$member = null;
if ( $member_slug ) {
    $member = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM {$table} WHERE seo_slug = %s AND is_active = 1", $member_slug ),
        ARRAY_A
    );
} elseif ( $member_id > 0 ) {
    $member = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND is_active = 1", $member_id ),
        ARRAY_A
    );
}

// ── Fetch all active members for the "Meet the rest" section ────
$all_members = $wpdb->get_results(
    "SELECT id, seo_slug, name, role, department, photo_url FROM {$table}
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
$name     = $member['name'];
$role     = $member['role'];
$dept     = $member['department'];
$bio      = $member['bio'];
$photo    = $member['photo_url'];
$phone      = $member['phone'] ?? '';
$email      = $member['email'] ?? '';
$facebook   = $member['facebook'] ?? '';
$instagram  = $member['instagram'] ?? '';
$tiktok     = $member['tiktok'] ?? '';
$x_twitter  = $member['x_twitter'] ?? '';
$linkedin   = $member['linkedin'] ?? '';
$youtube    = $member['youtube'] ?? '';
$whatsapp   = $member['whatsapp'] ?? '';
$zalo       = $member['zalo'] ?? '';
$skype      = $member['skype'] ?? '';
$line       = $member['line_app'] ?? '';
$wechat     = $member['wechat'] ?? '';
$pinterest  = $member['pinterest'] ?? '';
$messenger  = $member['messenger'] ?? '';
$telegram   = $member['telegram'] ?? '';
$viber      = $member['viber'] ?? '';
$line_app   = $member['line_app'] ?? '';
$gallery  = $member['gallery_urls'] ?? '';
$gallery_urls = array_filter( array_map( 'trim', explode( "\n", $gallery ) ) );

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

                <!-- Contact & Social -->
                <?php if ( $phone || $email || $facebook || $instagram || $tiktok || $x_twitter || $linkedin || $youtube || $whatsapp || $zalo || $skype || $line || $wechat || $pinterest || $messenger || $telegram || $viber || $line_app ): ?>
                <div class="mb-10">
                    <h3 class="flex items-center gap-2 text-[10px] uppercase tracking-[0.2em] font-semibold text-accentdark mb-4">
                        <svg class="w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                        Liên hệ & Kết nối
                    </h3>

                    <?php if ( $phone || $email ): ?>
                    <div class="flex flex-wrap gap-x-6 gap-y-4 text-[13px] text-textmain font-medium mb-5">
                        <?php if ( $phone ): ?>
                        <a href="tel:<?php echo esc_attr( preg_replace('/[^0-9\+]/', '', $phone) ); ?>" class="flex items-center gap-2 hover:text-terracotta transition-colors group">
                            <span class="w-8 h-8 rounded-full bg-accent/10 flex items-center justify-center text-accentdark group-hover:bg-terracotta group-hover:text-white transition-colors">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 .84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </span>
                            <?php echo esc_html($phone); ?>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ( $email ): ?>
                        <a href="mailto:<?php echo esc_attr($email); ?>" class="flex items-center gap-2 hover:text-terracotta transition-colors group">
                            <span class="w-8 h-8 rounded-full bg-accent/10 flex items-center justify-center text-accentdark group-hover:bg-terracotta group-hover:text-white transition-colors">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </span>
                            <?php echo esc_html($email); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="flex items-center gap-3 flex-wrap">

                        <?php /* ── FACEBOOK ── */ if ( $facebook ): ?>
                        <a href="<?php echo esc_url($facebook); ?>" target="_blank" rel="noopener noreferrer" title="Facebook" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="40" height="40" rx="10" fill="#337FFF"/><path d="M22.8 20.7h-2v7.3h-3v-7.3h-1.4v-2.6h1.4v-1.7c0-2 .8-3.1 3.1-3.1h1.9v2.6h-1.2c-.9 0-.9.3-.9 1v1.2h2.2l-.3 2.6z" fill="white"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── INSTAGRAM ── */ if ( $instagram ): ?>
                        <a href="<?php echo esc_url($instagram); ?>" target="_blank" rel="noopener noreferrer" title="Instagram" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="40" height="40" rx="10" fill="url(#ig_fe_u)"/><circle cx="20" cy="20" r="5.5" stroke="white" stroke-width="2.5" fill="none"/><circle cx="26.5" cy="13.5" r="1.5" fill="white"/><rect x="9" y="9" width="22" height="22" rx="7" stroke="white" stroke-width="2.5" fill="none"/><defs><linearGradient id="ig_fe_u" x1="40" y1="40" x2="0" y2="0" gradientUnits="userSpaceOnUse"><stop stop-color="#FBE18A"/><stop offset="0.21" stop-color="#FCBB45"/><stop offset="0.38" stop-color="#F75274"/><stop offset="0.52" stop-color="#D53692"/><stop offset="0.74" stop-color="#8F39CE"/><stop offset="1" stop-color="#5B4FE9"/></linearGradient></defs></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── X / TWITTER ── */ if ( $x_twitter ): ?>
                        <a href="<?php echo esc_url($x_twitter); ?>" target="_blank" rel="noopener noreferrer" title="X / Twitter" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="40" height="40" rx="10" fill="#000"/><path d="M22.2 18.5 29.4 10h-1.7l-6.3 7.4L16.3 10H10l7.5 11L10 30h1.7l6.6-7.7 5.2 7.7H30L22.2 18.5zm-2.3 2.7-.8-1.1-6-8.6h2.6l4.9 7 .8 1.1 6.3 9h-2.6l-5.2-7.4z" fill="white"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── TIKTOK ── */ if ( $tiktok ): ?>
                        <a href="<?php echo esc_url($tiktok); ?>" target="_blank" rel="noopener noreferrer" title="TikTok" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="40" height="40" rx="10" fill="#010101"/><path d="M26.5 10h-3.7v13.3a3.3 3.3 0 0 1-3.3 3.4 3.3 3.3 0 0 1-3.3-3.4 3.3 3.3 0 0 1 3.3-3.3v-3.8c-3.8 0-6.9 3.2-6.9 7.1 0 4 3.1 7.1 6.9 7.1 3.8 0 6.9-3.1 6.9-7.1V16.7c1.4 1 3 1.5 4.6 1.5v-3.7C29.1 14.5 26.5 12.5 26.5 10z" fill="white"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── LINKEDIN ── */ if ( $linkedin ): ?>
                        <a href="<?php echo esc_url($linkedin); ?>" target="_blank" rel="noopener noreferrer" title="LinkedIn" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="40" height="40" rx="10" fill="#006699"/><path d="M12.5 15.5h3.5V28h-3.5zM14.25 13.75a2 2 0 1 1 0-4 2 2 0 0 1 0 4zM18.5 15.5h3.4v1.7c.5-.9 1.6-1.9 3.4-1.9 3.6 0 4.2 2.4 4.2 5.5V28h-3.5v-6.5c0-1.6-.5-2.7-2.1-2.7-1.9 0-2.6 1.4-2.6 2.9V28h-3.4V15.5z" fill="white"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── YOUTUBE ── */ if ( $youtube ): ?>
                        <a href="<?php echo esc_url($youtube); ?>" target="_blank" rel="noopener noreferrer" title="YouTube" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="40" height="40" rx="10" fill="#FF0000"/><path d="M31 14.8a2.7 2.7 0 0 0-1.9-1.9C27.4 12.5 20 12.5 20 12.5s-7.4 0-9.1.4a2.7 2.7 0 0 0-1.9 1.9C8.6 16.5 8.6 20 8.6 20s0 3.5.4 5.2a2.7 2.7 0 0 0 1.9 1.9c1.7.4 9.1.4 9.1.4s7.4 0 9.1-.4a2.7 2.7 0 0 0 1.9-1.9c.4-1.7.4-5.2.4-5.2s0-3.5-.4-5.2zM17.5 23.5v-7l6 3.5-6 3.5z" fill="white"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── PINTEREST ── */ if ( ! empty($pinterest) ): ?>
                        <a href="<?php echo esc_url($pinterest); ?>" target="_blank" rel="noopener noreferrer" title="Pinterest" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="40" height="40" rx="10" fill="#E60023"/><path d="M20 8c-6.6 0-12 5.4-12 12 0 5.1 3.1 9.4 7.6 11.2-.1-.9-.2-2.4.1-3.5l1.4-5.9s-.3-.7-.3-1.8c0-1.7 1-3 2.2-3 1 0 1.5.8 1.5 1.7 0 1-.7 2.6-1 4-.3 1.2.6 2.2 1.8 2.2 2.2 0 3.7-2.8 3.7-6.2 0-2.6-1.8-4.4-4.3-4.4-3 0-4.7 2.2-4.7 4.5 0 .9.3 1.8.8 2.4.1.2.1.3.1.3l-.3 1.2c-.1.2-.3.3-.5.2-1.7-.8-2.8-3.2-2.8-5.1 0-4.2 3-8 8.7-8 4.6 0 8.2 3.3 8.2 7.7 0 4.6-2.9 8.3-6.9 8.3-1.3 0-2.6-.7-3-1.5l-.8 3.1c-.3 1.1-1.1 2.6-1.6 3.4.6.2 1.3.3 1.9.3 6.6 0 12-5.4 12-12S26.6 8 20 8z" fill="white"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── MESSENGER ── */ if ( ! empty($messenger) ): ?>
                        <a href="<?php echo esc_url($messenger); ?>" target="_blank" rel="noopener noreferrer" title="Messenger" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="40" height="40" rx="10" fill="url(#ms_fe_u)"/><path d="M20 8C13.4 8 8 13 8 19.2c0 3.5 1.7 6.6 4.4 8.7v4.1l3.8-2.1c1 .3 2.5.4 3.8.4C26.6 30.3 32 25.4 32 19.2 32 13 26.6 8 20 8zm1.9 15.2-3.2-3.4-6.2 3.4 6.8-7.2 3.3 3.4 6.1-3.4-6.8 7.2z" fill="white"/><defs><linearGradient id="ms_fe_u" x1="0" y1="40" x2="40" y2="0" gradientUnits="userSpaceOnUse"><stop stop-color="#0099FF"/><stop offset="1" stop-color="#A033FF"/></linearGradient></defs></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── TELEGRAM ── */ if ( ! empty($telegram) ): ?>
                        <a href="<?php echo esc_url($telegram); ?>" target="_blank" rel="noopener noreferrer" title="Telegram" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="40" height="40" rx="10" fill="#229ED9"/><path d="M9.6 19.4s10.2-4.2 13.7-5.7c1.4-.6 5.9-2.5 5.9-2.5s2.1-.8 1.9 1.2c-.1.8-.5 3.7-1 6.9-.7 4.5-1.4 9.4-1.4 9.4s-.1 1.4-1.1 1.6c-1 .2-2.6-.9-2.9-1.1-.2-.2-4.3-2.8-5.7-3.9 0 0-.8-.6-.7-1.5 0 0 .1-.7 4.2-4.4 0 0 2.2-2.1-.2-.4-.2.2-3 2.2-5.3 3.8-.5.3-1.7.4-1.7.4l-5.8-1.9s-1.4-.8.1-1.9z" fill="white"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── WHATSAPP ── */ if ( $whatsapp ): ?>
                        <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $whatsapp)); ?>" target="_blank" rel="noopener noreferrer" title="WhatsApp: <?php echo esc_attr($whatsapp); ?>" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="40" height="40" rx="10" fill="#25D366"/><path d="M20 8c-6.6 0-12 5.4-12 12 0 2.1.5 4.1 1.6 5.9L8 32l6.3-1.6c1.7.9 3.7 1.4 5.7 1.4 6.6 0 12-5.4 12-12S26.6 8 20 8zm5.9 17.1c-.3.8-1.5 1.4-2.1 1.5-.5.1-1.2.1-3.8-1.1-3.2-1.5-5.4-4.3-5.6-4.5-.2-.2-1.6-2.1-1.6-4s1-3 1.4-3.4c.3-.4.7-.5 1-.5h.7c.3 0 .5 0 .7.6.3.7.9 2.3 1 2.5.1.2.1.4 0 .6-.1.2-.2.3-.3.5-.2.2-.4.4-.5.5-.2.2-.4.3-.2.7.2.4 1 1.6 2.1 2.6 1.4 1.2 2.6 1.6 3 1.8.4.2.7.1.9-.1.3-.3.6-.8.9-1.2.2-.3.5-.3.8-.2l2.5 1.2c.4.2.6.3.7.4.1.3 0 1-.3 1.6z" fill="white"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── ZALO ── */ if ( $zalo ):
                            $zalo_href = filter_var($zalo, FILTER_VALIDATE_URL) ? $zalo : 'https://zalo.me/' . preg_replace('/[^0-9]/', '', $zalo);
                        ?>
                        <a href="<?php echo esc_url($zalo_href); ?>" target="_blank" rel="noopener noreferrer" title="Zalo: <?php echo esc_attr($zalo); ?>" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="40" height="40" rx="10" fill="#0068FF"/><path d="M20 10c-5.5 0-10 4.5-10 10s4.5 10 10 10 10-4.5 10-10-4.5-10-10-10zm-4.5 6.5H21l-5.5 5.5V24h7.5v-1.5H17l5.5-5.5V15.5h-7v1.5" fill="white"/><path d="M25 15.5h1.5V23H25v-7.5zm.75-2.5a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" fill="white"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── VIBER ── */ if ( ! empty($viber) ): ?>
                        <a href="viber://chat?number=<?php echo esc_attr(preg_replace('/[^0-9]/', '', $viber)); ?>" title="Viber: <?php echo esc_attr($viber); ?>" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="40" height="40" rx="10" fill="#7360F2"/><path d="M27.5 11.5h-15C11 11.5 10 12.5 10 14v13c0 1.5 1 2.5 2.5 2.5H14l2 3.5 2-3.5h9.5c1.5 0 2.5-1 2.5-2.5V14c0-1.5-1-2.5-2.5-2.5zM21 23.5c-.3.2-.6.3-1 .2-.3-.1-.7-.3-1-.7-1-1-1.6-2.2-1.8-3.5 0-.5.1-.9.5-1.2.3-.3.7-.4 1.1-.3l.9 1.4c.1.2.1.5 0 .7l-.5.5a4.4 4.4 0 0 0 1.4 1.4l.5-.5c.2-.2.5-.2.7 0l1.4.9c.1.4 0 .8-.3 1.1-.3.2-.5.4-.9.6z" fill="white"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── SKYPE ── */ if ( $skype ): ?>
                        <a href="skype:<?php echo esc_attr($skype); ?>?chat" title="Skype: <?php echo esc_attr($skype); ?>" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="40" height="40" rx="10" fill="#00AFF0"/><path d="M30.5 21.7A10.5 10.5 0 0 0 9.6 19.3a6.3 6.3 0 0 0 8.5 8.5 10.5 10.5 0 0 0 12.4-6.1zm-7-1c1.1.3 1.9.7 2.4 1.2.5.5.7 1.1.7 1.7 0 .7-.3 1.3-.8 1.8-.5.5-1.3.7-2.2.7-.9 0-1.7-.2-2.4-.7-.5-.3-.8-.7-1-.9l1.5-1c.2.2.4.4.6.5.3.2.7.3 1.3.3.5 0 .8-.1 1-.3.2-.2.3-.4.3-.6 0-.2-.1-.4-.3-.5-.3-.2-.7-.4-1.4-.5-1-.3-1.8-.6-2.3-1.1-.5-.5-.8-1.1-.8-1.8 0-.7.3-1.3.8-1.7.5-.4 1.3-.6 2.2-.6.8 0 1.5.2 2.1.5.5.3.8.6 1 .9l-1.4 1c-.2-.2-.4-.4-.7-.5-.3-.2-.6-.2-1-.2-.4 0-.7.1-.9.2-.2.1-.3.3-.3.5s.1.4.3.5c.3.2.7.4 1.3.6z" fill="white"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── WECHAT ── */ if ( $wechat ): ?>
                        <span title="WeChat ID: <?php echo esc_attr($wechat); ?>" style="border-radius:10px;display:inline-block;cursor:help;" class="transition-all duration-300 hover:-translate-y-1">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="40" height="40" rx="10" fill="#07C160"/><path d="M15.5 9.5C10.3 9.5 6 13.2 6 17.7c0 2.5 1.3 4.7 3.5 6.2l-1 3.3 3.5-1.8c1 .3 2 .5 3 .5 2.8 0 5.3-1.1 7-2.8A6.3 6.3 0 0 1 24.5 29c1.3 0 2.5-.3 3.6-.7l3 1.5-.9-2.9A6.3 6.3 0 0 0 24.5 17c-.3 0-.6 0-.9.1C22.7 12.5 19.4 9.5 15.5 9.5zm-2 5a1.3 1.3 0 1 1 0 2.6 1.3 1.3 0 0 1 0-2.6zm6 0a1.3 1.3 0 1 1 0 2.6 1.3 1.3 0 0 1 0-2.6zm3 8.5a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm4 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" fill="white"/></svg>
                        </span>
                        <?php endif; ?>

                        <?php /* ── LINE ── */ if ( ! empty($line_app) ): ?>
                        <span title="Line ID: <?php echo esc_attr($line_app); ?>" style="border-radius:10px;display:inline-block;cursor:help;" class="transition-all duration-300 hover:-translate-y-1">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="40" height="40" rx="10" fill="#06C755"/><path d="M32 19.2C32 13.4 26.1 8.7 20 8.7c-6.1 0-12 4.7-12 10.5 0 5.2 4.6 9.5 10.8 10.3.4.1 1 .3 1.2.7.2.4.1.9 0 1.3l-.2 1.1c-.1.4-.4 1.5.6 1 1-.4 5.2-3.1 7.1-5.3C30.7 26.6 32 23.1 32 19.2zM15.5 22.3h-2.8v-5.6h1.2v4.4h1.6v1.2zm1.4 0h-1.2v-5.6h1.2v5.6zm5.6 0h-1.2l-2.1-3.8v3.8h-1.2v-5.6h1.2l2.1 3.7v-3.7h1.2v5.6zm4.4-4.4h-2.4v1h2.4v1.2h-2.4v1h2.4v1.2h-3.6v-5.6h3.6v1.2z" fill="white"/></svg>
                        </span>
                        <?php endif; ?>

                    </div>
                </div>
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

    <!-- ═══ 2.5 GALLERY ══════════════════════════════════════════ -->
    <?php if ( ! empty( $gallery_urls ) ): ?>
    <section class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-10 pb-24">
        <div class="flex flex-col items-center mb-12">
            <span class="w-12 h-[2px] bg-terracotta mb-4"></span>
            <h2 class="font-serif text-3xl md:text-4xl text-textmain mb-3 text-center">
                Personal Gallery
            </h2>
            <p class="text-sm text-textmuted text-center max-w-xl">
                Những khoảnh khắc đáng nhớ và các hoạt động nổi bật.
            </p>
        </div>

        <div class="columns-2 md:columns-3 lg:columns-4 gap-4 space-y-4">
            <?php foreach ( $gallery_urls as $idx => $gurl ): ?>
            <div class="break-inside-avoid relative group rounded-xl overflow-hidden bg-[#EBE7DF] shadow-sm cursor-zoom-in">
                <!-- Use actual aspect ratio if possible, else standard image rendering -->
                <img src="<?php echo esc_url( $gurl ); ?>" alt="Gallery Image <?php echo $idx+1; ?>" 
                     class="w-full h-auto object-cover transition-transform duration-700 group-hover:scale-[1.03]" />
                <div class="absolute inset-0 bg-textmain/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

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
                if ( !empty($c['seo_slug']) ) {
                    $c_url = home_url( '/our-team/' . $c['seo_slug'] . '/' );
                } else {
                    $c_url = add_query_arg( 'member_id', $c['id'], home_url('/our-team/') );
                }
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
