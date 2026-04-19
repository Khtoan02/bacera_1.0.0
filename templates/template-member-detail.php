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
                <?php if ( $phone || $email || $facebook || $instagram || $tiktok || $x_twitter || $linkedin || $youtube || $whatsapp || $zalo || $skype || $line || $wechat ): ?>
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
                        <?php if ( $facebook ): ?>
                        <a href="<?php echo esc_url($facebook); ?>" target="_blank" rel="noopener noreferrer" title="Facebook" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white shadow-md shadow-gray-200 group transition-all duration-300 hover:shadow-lg">
                            <svg class="transition-all duration-300 group-hover:scale-110" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 72 72" fill="none">
                                <path d="M46.4927 38.6403L47.7973 30.3588H39.7611V24.9759C39.7611 22.7114 40.883 20.4987 44.4706 20.4987H48.1756V13.4465C46.018 13.1028 43.8378 12.9168 41.6527 12.8901C35.0385 12.8901 30.7204 16.8626 30.7204 24.0442V30.3588H23.3887V38.6403H30.7204V58.671H39.7611V38.6403H46.4927Z" fill="#337FFF"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ( $instagram ): ?>
                        <a href="<?php echo esc_url($instagram); ?>" target="_blank" rel="noopener noreferrer" title="Instagram" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white shadow-md shadow-gray-200 group transition-all duration-300 hover:shadow-lg">
                            <svg class="transition-all duration-300 group-hover:scale-110" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 72 72" fill="none">
                                <path d="M27.4456 35.7808C27.4456 31.1786 31.1776 27.4468 35.7826 27.4468C40.3875 27.4468 44.1216 31.1786 44.1216 35.7808C44.1216 40.383 40.3875 44.1148 35.7826 44.1148C31.1776 44.1148 27.4456 40.383 27.4456 35.7808ZM22.9377 35.7808C22.9377 42.8708 28.6883 48.618 35.7826 48.618C42.8768 48.618 48.6275 42.8708 48.6275 35.7808C48.6275 28.6908 42.8768 22.9436 35.7826 22.9436C28.6883 22.9436 22.9377 28.6908 22.9377 35.7808ZM46.1342 22.4346C46.1339 23.0279 46.3098 23.608 46.6394 24.1015C46.9691 24.595 47.4377 24.9797 47.9861 25.2069C48.5346 25.4342 49.1381 25.4939 49.7204 25.3784C50.3028 25.2628 50.8378 24.9773 51.2577 24.5579C51.6777 24.1385 51.9638 23.6041 52.0799 23.0222C52.1959 22.4403 52.1367 21.8371 51.9097 21.2888C51.6828 20.7406 51.2982 20.2719 50.8047 19.942C50.3112 19.6122 49.7309 19.436 49.1372 19.4358H49.136C48.3402 19.4361 47.5771 19.7522 47.0142 20.3144C46.4514 20.8767 46.1349 21.6392 46.1342 22.4346ZM25.6765 56.1302C23.2377 56.0192 21.9121 55.6132 21.0311 55.2702C19.8632 54.8158 19.0299 54.2746 18.1538 53.4002C17.2777 52.5258 16.7354 51.6938 16.2827 50.5266C15.9393 49.6466 15.533 48.3214 15.4222 45.884C15.3009 43.2488 15.2767 42.4572 15.2767 35.781C15.2767 29.1048 15.3029 28.3154 15.4222 25.678C15.5332 23.2406 15.9425 21.918 16.2827 21.0354C16.7374 19.8682 17.2789 19.0354 18.1538 18.1598C19.0287 17.2842 19.8612 16.7422 21.0311 16.2898C21.9117 15.9466 23.2377 15.5406 25.6765 15.4298C28.3133 15.3086 29.1054 15.2844 35.7826 15.2844C42.4598 15.2844 43.2527 15.3106 45.8916 15.4298C48.3305 15.5408 49.6539 15.9498 50.537 16.2898C51.7049 16.7422 52.5382 17.2854 53.4144 18.1598C54.2905 19.0342 54.8308 19.8682 55.2855 21.0354C55.6289 21.9154 56.0351 23.2406 56.146 25.678C56.2673 28.3154 56.2915 29.1048 56.2915 35.781C56.2915 42.4572 56.2673 43.2466 56.146 45.884C56.0349 48.3214 55.6267 49.6462 55.2855 50.5266C54.8308 51.6938 54.2893 52.5266 53.4144 53.4002C52.5394 54.2738 51.7049 54.8158 50.537 55.2702C49.6565 55.6134 48.3305 56.0194 45.8916 56.1302C43.2549 56.2514 42.4628 56.2756 35.7826 56.2756C29.1024 56.2756 28.3125 56.2514 25.6765 56.1302ZM25.4694 10.9322C22.8064 11.0534 20.9867 11.4754 19.3976 12.0934C17.7518 12.7316 16.3585 13.5878 14.9663 14.977C13.5741 16.3662 12.7195 17.7608 12.081 19.4056C11.4626 20.9948 11.0403 22.8124 10.9191 25.4738C10.7958 28.1394 10.7676 28.9916 10.7676 35.7808C10.7676 42.57 10.7958 43.4222 10.9191 46.0878C11.0403 48.7494 11.4626 50.5668 12.081 52.156C12.7195 53.7998 13.5743 55.196 14.9663 56.5846C16.3583 57.9732 17.7518 58.8282 19.3976 59.4682C20.9897 60.0862 22.8064 60.5082 25.4694 60.6294C28.138 60.7506 28.9893 60.7808 35.7826 60.7808C42.5759 60.7808 43.4286 60.7526 46.0958 60.6294C48.759 60.5082 50.5774 60.0862 52.1676 59.4682C53.8124 58.8282 55.2066 57.9738 56.5989 56.5846C57.9911 55.1954 58.8438 53.7998 59.4842 52.156C60.1026 50.5668 60.5268 48.7492 60.6461 46.0878C60.7674 43.4202 60.7956 42.57 60.7956 35.7808C60.7956 28.9916 60.7674 28.1394 60.6461 25.4738C60.5248 22.8122 60.1026 20.9938 59.4842 19.4056C58.8438 17.7618 57.9889 16.3684 56.5989 14.977C55.2088 13.5856 53.8124 12.7316 52.1696 12.0934C50.5775 11.4754 48.7588 11.0514 46.0978 10.9322C43.4306 10.811 42.5779 10.7808 35.7846 10.7808C28.9913 10.7808 28.138 10.809 25.4694 10.9322Z" fill="url(#igFRONT)"/><defs><radialGradient id="igFRONT" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(17.4144 61.017) scale(65.31 65.2708)"><stop offset="0.09" stop-color="#FA8F21"/><stop offset="0.78" stop-color="#D82D7E"/></radialGradient></defs></svg>
                        </a>
                        <?php endif; ?>

                        <?php if ( $tiktok ): ?>
                        <a href="<?php echo esc_url($tiktok); ?>" target="_blank" rel="noopener noreferrer" title="TikTok" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white shadow-md shadow-gray-200 group transition-all duration-300 hover:shadow-lg">
                            <svg class="transition-all duration-300 group-hover:scale-110" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 72 72" fill="none">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M45.6721 29.4285C48.7387 31.6085 52.4112 32.7733 56.1737 32.7592V25.3024C55.434 25.3045 54.6963 25.2253 53.9739 25.0663V31.0068C50.203 31.0135 46.5252 29.8354 43.4599 27.6389V42.9749C43.4507 45.4914 42.7606 47.9585 41.4628 50.1146C40.165 52.2706 38.3079 54.0353 36.0885 55.2215C33.8691 56.4076 31.37 56.9711 28.8563 56.852C26.3426 56.733 23.9079 55.9359 21.8105 54.5453C23.7506 56.5082 26.2295 57.8513 28.9333 58.4044C31.6372 58.9576 34.4444 58.6959 36.9994 57.6526C39.5545 56.6093 41.7425 54.8312 43.2864 52.5436C44.8302 50.256 45.6605 47.5616 45.6721 44.8018V29.4285ZM48.3938 21.8226C46.8343 20.1323 45.8775 17.9739 45.6721 15.6832V14.7139H43.5842C43.8423 16.1699 44.4039 17.5553 45.2326 18.78C46.0612 20.0048 47.1383 21.0414 48.3938 21.8226ZM26.645 48.642C25.9213 47.6957 25.4779 46.5653 25.365 45.3793C25.2522 44.1934 25.4746 42.9996 26.0068 41.9338C26.5391 40.8681 27.3598 39.9731 28.3757 39.3508C29.3915 38.7285 30.5616 38.4039 31.7529 38.4139C32.4106 38.4137 33.0644 38.5143 33.6916 38.7121V31.0068C32.9584 30.9097 32.2189 30.8682 31.4794 30.8826V36.8728C29.9522 36.39 28.2992 36.4998 26.8492 37.1803C25.3992 37.8608 24.2585 39.0621 23.6539 40.5454C23.0494 42.0286 23.0252 43.6851 23.5864 45.1853C24.1475 46.6855 25.2527 47.9196 26.6823 48.642H26.645Z" fill="#EE1D52"/><path fill-rule="evenodd" clip-rule="evenodd" d="M43.4589 27.5892C46.5241 29.7857 50.2019 30.9638 53.9729 30.9571V25.0166C51.8243 24.5623 49.8726 23.4452 48.3927 21.8226C47.1372 21.0414 46.0601 20.0048 45.2315 18.78C44.4029 17.5553 43.8412 16.1699 43.5831 14.7139H38.09V44.8018C38.0849 46.1336 37.6629 47.4304 36.8831 48.51C36.1034 49.5897 35.0051 50.3981 33.7425 50.8217C32.4798 51.2453 31.1162 51.2629 29.8431 50.872C28.57 50.4811 27.4512 49.7012 26.6439 48.642C25.3645 47.9965 24.3399 46.9387 23.7354 45.6394C23.1309 44.3401 22.9818 42.875 23.3121 41.4805C23.6424 40.0861 24.4329 38.8435 25.556 37.9535C26.6791 37.0634 28.0693 36.5776 29.5023 36.5745C30.1599 36.5766 30.8134 36.6772 31.4411 36.8728V30.8826C28.7288 30.9477 26.0946 31.8033 23.8617 33.3444C21.6289 34.8855 19.8946 37.0451 18.8717 39.5579C17.8489 42.0708 17.5821 44.8276 18.1039 47.49C18.6258 50.1524 19.9137 52.6045 21.8095 54.5453C23.9073 55.9459 26.3458 56.7512 28.8651 56.8755C31.3845 56.9997 33.8904 56.4383 36.1158 55.2509C38.3413 54.0636 40.2031 52.2948 41.5027 50.133C42.8024 47.9712 43.4913 45.4973 43.4962 42.9749L43.4589 27.5892Z" fill="black"/><path fill-rule="evenodd" clip-rule="evenodd" d="M53.9736 25.0161V23.4129C52.0005 23.4213 50.0655 22.8696 48.3934 21.8221C49.8695 23.4493 51.8229 24.5674 53.9736 25.0161ZM43.5838 14.7134C43.5838 14.4275 43.4968 14.1292 43.4596 13.8434V12.874H35.8785V42.9744C35.872 44.6598 35.197 46.2738 34.0017 47.4621C32.8064 48.6504 31.1885 49.3159 29.503 49.3126C28.5106 49.3176 27.5311 49.0876 26.6446 48.6415C27.4519 49.7007 28.5707 50.4805 29.8438 50.8715C31.1169 51.2624 32.4805 51.2448 33.7432 50.8212C35.0058 50.3976 36.1041 49.5892 36.8838 48.5095C37.6636 47.4298 38.0856 46.1331 38.0907 44.8013V14.7134H43.5838ZM31.4418 30.8696V29.167C28.3222 28.7432 25.1511 29.3885 22.4453 30.9977C19.7394 32.6069 17.6584 35.0851 16.5413 38.0284C15.4242 40.9718 15.337 44.2067 16.2938 47.206C17.2506 50.2053 19.195 52.792 21.8102 54.5448C19.9287 52.5995 18.6545 50.1484 18.1433 47.4908C17.6321 44.8333 17.906 42.0844 18.9315 39.5799C19.957 37.0755 21.6897 34.924 23.918 33.3882C26.1463 31.8524 28.7736 30.9988 31.4791 30.9318L31.4418 30.8696Z" fill="#69C9D0"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php if ( $x_twitter ): ?>
                        <a href="<?php echo esc_url($x_twitter); ?>" target="_blank" rel="noopener noreferrer" title="X / Twitter" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white shadow-md shadow-gray-200 group transition-all duration-300 hover:shadow-lg">
                            <svg class="transition-all duration-300 group-hover:scale-110" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 72 72" fill="none">
                                <path d="M40.7568 32.1716L59.3704 11H54.9596L38.7974 29.383L25.8887 11H11L30.5205 38.7983L11 61H15.4111L32.4788 41.5869L46.1113 61H61L40.7557 32.1716H40.7568ZM34.7152 39.0433L32.7374 36.2752L17.0005 14.2492H23.7756L36.4755 32.0249L38.4533 34.7929L54.9617 57.8986H48.1865L34.7152 39.0443V39.0433Z" fill="black"/>
                            </svg>
                        </a>
                        <?php endif; ?>

                        <?php if ( $linkedin ): ?>
                        <a href="<?php echo esc_url($linkedin); ?>" target="_blank" rel="noopener noreferrer" title="LinkedIn" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white shadow-md shadow-gray-200 group transition-all duration-300 hover:shadow-lg">
                            <svg class="transition-all duration-300 group-hover:scale-110" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 72 72" fill="none">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M14.6975 11C12.6561 11 11 12.6057 11 14.5838V57.4474C11 59.4257 12.6563 61.03 14.6975 61.03H57.3325C59.3747 61.03 61.03 59.4255 61.03 57.4468V14.5838C61.03 12.6057 59.3747 11 57.3325 11H14.6975ZM26.2032 30.345V52.8686H18.7167V30.345H26.2032ZM26.6967 23.3793C26.6967 25.5407 25.0717 27.2703 22.4615 27.2703L22.4609 27.2701H22.4124C19.8998 27.2701 18.2754 25.5405 18.2754 23.3791C18.2754 21.1686 19.9489 19.4873 22.5111 19.4873C25.0717 19.4873 26.6478 21.1686 26.6967 23.3793ZM37.833 52.8686H30.3471L30.3469 52.8694C30.3469 52.8694 30.4452 32.4588 30.3475 30.3458H37.8336V33.5339C38.8288 31.9995 40.6098 29.8169 44.5808 29.8169C49.5062 29.8169 53.1991 33.0363 53.1991 39.9543V52.8686H45.7133V40.8204C45.7133 37.7922 44.6293 35.7269 41.921 35.7269C39.8524 35.7269 38.6206 37.1198 38.0796 38.4653C37.8819 38.9455 37.833 39.6195 37.833 40.2918V52.8686Z" fill="#006699"/>
                            </svg>
                        </a>
                        <?php endif; ?>

                        <?php if ( $youtube ): ?>
                        <a href="<?php echo esc_url($youtube); ?>" target="_blank" rel="noopener noreferrer" title="YouTube" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white shadow-md shadow-gray-200 group transition-all duration-300 hover:shadow-lg">
                            <svg class="transition-all duration-300 group-hover:scale-110" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 71 72" fill="none">
                                <path d="M33.3501 13.0437C25.9186 13.893 18.5132 20.0479 18.2075 28.84C18.0154 34.2083 19.5044 38.2356 24.4951 39.3664C26.6608 35.4553 23.7965 34.5927 23.3511 31.7633C21.5216 20.1686 36.4153 12.2615 44.2093 20.3563C49.6018 25.9615 46.0519 43.206 37.3541 41.4136C29.0231 39.7017 41.4323 25.9749 34.7823 23.2796C29.3767 21.0894 26.5037 29.9798 29.0667 34.396C27.5647 41.9902 24.3292 49.1464 25.6391 58.6715C29.8876 55.5158 31.3198 49.4727 32.4943 43.1702C34.6295 44.4978 35.7691 45.8789 38.4937 46.0935C48.5407 46.8891 54.1515 35.8263 52.7805 25.6218C51.5623 16.5749 42.7422 11.971 33.3501 13.0437Z" fill="#FF0000"/>
                            </svg>
                        </a>
                        <?php endif; ?>

                        <?php if ( $whatsapp ): ?>
                        <a href="https://wa.me/<?php echo esc_attr( preg_replace('/[^0-9]/', '', $whatsapp) ); ?>" target="_blank" rel="noopener noreferrer" title="WhatsApp: <?php echo esc_attr($whatsapp); ?>" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white shadow-md shadow-gray-200 group transition-all duration-300 hover:shadow-lg">
                            <svg class="transition-all duration-300 group-hover:scale-110" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 71 72" fill="none">
                                <path d="M12.5762 56.8405L15.8608 44.6381C13.2118 39.8847 12.3702 34.3378 13.4904 29.0154C14.6106 23.693 17.6176 18.952 21.9594 15.6624C26.3012 12.3729 31.6867 10.7554 37.1276 11.1068C42.5685 11.4582 47.6999 13.755 51.5802 17.5756C55.4604 21.3962 57.8292 26.4844 58.2519 31.9065C58.6746 37.3286 57.1228 42.7208 53.8813 47.0938C50.6399 51.4668 45.9261 54.5271 40.605 55.7133C35.284 56.8994 29.7125 56.1318 24.9131 53.5513L12.5762 56.8405ZM25.508 48.985L26.2709 49.4365C29.7473 51.4918 33.8076 52.3423 37.8191 51.8555C41.8306 51.3687 45.5681 49.5719 48.4489 46.7452C51.3298 43.9185 53.1923 40.2206 53.7463 36.2279C54.3002 32.2351 53.5143 28.1717 51.5113 24.6709C49.5082 21.1701 46.4003 18.4285 42.6721 16.8734C38.9438 15.3184 34.8045 15.0372 30.8993 16.0736C26.994 17.11 23.5422 19.4059 21.0817 22.6035C18.6212 25.801 17.2903 29.7206 17.2963 33.7514C17.293 37.0937 18.2197 40.3712 19.9732 43.2192L20.4516 44.0061L18.6153 50.8167L25.508 48.985Z" fill="#00D95F"/>
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M44.0259 36.8847C43.5787 36.5249 43.0549 36.2716 42.4947 36.1442C41.9344 36.0168 41.3524 36.0186 40.793 36.1495C39.9524 36.4977 39.4093 37.8134 38.8661 38.4713C38.7516 38.629 38.5833 38.7396 38.3928 38.7823C38.2024 38.8251 38.0028 38.797 37.8316 38.7034C34.7543 37.5012 32.1748 35.2965 30.5122 32.4475C30.3704 32.2697 30.3033 32.044 30.325 31.8178C30.3467 31.5916 30.4555 31.3827 30.6286 31.235C31.2344 30.6368 31.6791 29.8959 31.9218 29.0809C31.9756 28.1818 31.7691 27.2863 31.3269 26.5011C30.985 25.4002 30.3344 24.42 29.4518 23.6762C28.9966 23.472 28.4919 23.4036 27.9985 23.4791C27.5052 23.5546 27.0443 23.7709 26.6715 24.1019C26.0242 24.6589 25.5104 25.3537 25.168 26.135C24.8256 26.9163 24.6632 27.7643 24.6929 28.6165C24.6949 29.0951 24.7557 29.5716 24.8739 30.0354C25.1742 31.1497 25.636 32.2144 26.2447 33.1956C26.6839 33.9473 27.163 34.6749 27.6801 35.3755C29.3607 37.6767 31.4732 39.6305 33.9003 41.1284C35.1183 41.8897 36.42 42.5086 37.7799 42.973C39.1924 43.6117 40.752 43.8568 42.2931 43.6824C43.1711 43.5499 44.003 43.2041 44.7156 42.6755C45.4281 42.1469 45.9995 41.4518 46.3795 40.6512C46.6028 40.1675 46.6705 39.6269 46.5735 39.1033C46.3407 38.0327 44.9053 37.4007 44.0259 36.8847Z" fill="white"/>
                            </svg>
                        </a>
                        <?php endif; ?>

                        <?php if ( $zalo ): ?>
                        <div title="Zalo: <?php echo esc_attr($zalo); ?>" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white shadow-md shadow-gray-200 group transition-all duration-300 hover:shadow-lg cursor-pointer">
                            <span class="transition-all duration-300 group-hover:scale-110 font-bold text-[#0068FF] text-[18px]">Zalo</span>
                        </div>
                        <?php endif; ?>

                        <?php if ( $skype ): ?>
                        <div title="Skype: <?php echo esc_attr($skype); ?>" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white shadow-md shadow-gray-200 group transition-all duration-300 hover:shadow-lg cursor-pointer">
                            <svg class="transition-all duration-300 group-hover:scale-110" xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 72 72" fill="none">
                                <path d="M40.3522 25.55C40.3522 29.4089 38.8124 33.1097 36.0717 35.8384C33.3309 38.5671 29.6136 40.1 25.7376 40.1C21.8616 40.1 18.1443 38.5671 15.4036 35.8384C12.6628 33.1097 11.123 29.4089 11.123 25.55C11.123 21.6911 12.6628 17.9902 15.4036 15.2616C18.1443 12.5329 21.8616 11 25.7376 11C29.6136 11 33.3309 12.5329 36.0717 15.2616C38.8124 17.9902 40.3522 21.6911 40.3522 25.55Z" fill="url(#skype1FE)"/><path d="M60.7196 46.445C60.7196 48.3564 60.3415 50.2491 59.6068 52.015C58.8721 53.7809 57.7952 55.3854 56.4376 56.737C55.0801 58.0885 53.4684 59.1606 51.6947 59.8921C49.921 60.6235 48.0199 61 46.1 61C42.2227 61 38.5041 59.4665 35.7624 56.737C33.0207 54.0074 31.4805 50.3052 31.4805 46.445C31.4805 42.5848 33.0207 38.8827 35.7624 36.1531C38.5041 33.4235 42.2227 31.89 46.1 31.89C48.0199 31.89 49.921 32.2665 51.6947 32.9979C53.4684 33.7294 55.0801 34.8015 56.4376 36.1531C57.7952 37.5046 58.8721 39.1092 59.6068 40.8751C60.3415 42.641 60.7196 44.5336 60.7196 46.445Z" fill="url(#skype2FE)"/><path d="M59.0711 36.1084C59.0711 39.1347 58.4724 42.1313 57.3092 44.9273C56.1459 47.7232 54.4409 50.2637 52.2914 52.4036C50.142 54.5436 47.5903 56.241 44.7819 57.3992C41.9735 58.5573 38.9635 59.1534 35.9238 59.1534C32.884 59.1534 29.874 58.5573 27.0656 57.3992C24.2573 56.241 21.7055 54.5436 19.5561 52.4036C17.4066 50.2637 15.7016 47.7232 14.5384 44.9273C13.3751 42.1313 12.7764 39.1347 12.7764 36.1084C12.7764 33.082 13.3751 30.0854 14.5384 27.2894C15.7016 24.4935 17.4066 21.953 19.5561 19.8131C21.7055 17.6732 24.2573 15.9757 27.0656 14.8176C29.874 13.6594 32.884 13.0634 35.9238 13.0634C38.9635 13.0634 41.9735 13.6594 44.7819 14.8176C47.5903 15.9757 50.142 17.6732 52.2914 19.8131C54.4409 21.953 56.1459 24.4935 57.3092 27.2894C58.4724 30.0854 59.0711 33.082 59.0711 36.1084Z" fill="url(#skype3FE)"/><path fill-rule="evenodd" clip-rule="evenodd" d="M29.4151 35.3084C28.4797 34.6934 27.6991 33.8709 27.1343 32.905C26.5889 31.8846 26.3197 30.7396 26.3534 29.5834C26.3121 28.116 26.8134 26.6849 27.7616 25.5634C28.7471 24.4371 30.0136 23.5918 31.4324 23.1134C32.9754 22.5688 34.6012 22.2954 36.2377 22.305C37.3084 22.2949 38.378 22.3769 39.4346 22.55C40.1909 22.6667 40.9337 22.8582 41.6521 23.1217C42.3117 23.3447 42.9072 23.7245 43.3873 24.2284C43.7411 24.6429 43.9315 25.172 43.9229 25.7167C43.9355 25.9997 43.8922 26.2823 43.7954 26.5486C43.6987 26.8148 43.5504 27.0594 43.359 27.2684C43.1778 27.4609 42.958 27.6131 42.7139 27.7147C42.4697 27.8164 42.2068 27.8654 41.9424 27.8584C41.5786 27.8554 41.2196 27.7747 40.8895 27.6217C40.0761 27.258 39.2381 26.9517 38.3818 26.705C37.5935 26.4974 36.7809 26.3965 35.9657 26.405C34.9033 26.366 33.8522 26.6345 32.939 27.1784C32.5537 27.4122 32.2383 27.7452 32.0258 28.1425C31.8133 28.5397 31.7115 28.9867 31.731 29.4367C31.7254 30.0275 31.9505 30.5972 32.3584 31.025C32.8431 31.5276 33.416 31.9371 34.0486 32.2334C34.7572 32.5845 35.8139 33.05 37.2188 33.63C37.3744 33.6792 37.5261 33.74 37.6726 33.8117C39.0436 34.3534 40.3581 35.0283 41.597 35.8267C42.5755 36.464 43.4006 37.3098 44.013 38.3034C44.6161 39.3476 44.9181 40.5382 44.8857 41.7434C44.9363 43.2189 44.513 44.672 43.6776 45.89C42.827 47.0433 41.6503 47.9157 40.2989 48.395C38.6702 48.9763 36.9491 49.2564 35.2199 49.2217C32.8437 49.3048 30.4788 48.8578 28.2972 47.9134C27.7395 47.6653 27.2505 47.2855 26.8723 46.8067C26.5583 46.368 26.3955 45.8392 26.4085 45.3C26.3929 45.0157 26.4393 44.7313 26.5444 44.4666C26.6495 44.2018 26.8109 43.9631 27.0175 43.7667C27.4504 43.388 28.0125 43.1899 28.5875 43.2134C29.2282 43.2221 29.8576 43.3838 30.4229 43.685C31.1259 44.0361 31.6804 44.2995 32.0864 44.475C32.5501 44.6689 33.0304 44.8207 33.5214 44.9284C34.1268 45.061 34.7453 45.1248 35.3651 45.1184C36.4611 45.1965 37.5518 44.9061 38.4635 44.2934C38.8048 44.0266 39.0776 43.6825 39.2592 43.2895C39.4408 42.8964 39.5261 42.4659 39.508 42.0334C39.509 41.4201 39.275 40.8297 38.854 40.3834C38.2975 39.8095 37.6461 39.3359 36.9285 38.9834C36.0798 38.5367 34.8862 37.9923 33.3478 37.35C31.972 36.8024 30.6545 36.1184 29.4151 35.3084Z" fill="white"/>
                                <defs><linearGradient id="skype1FE" x1="23.4584" y1="11.1767" x2="28.0069" y2="39.9249" gradientUnits="userSpaceOnUse"><stop offset="0.012" stop-color="#00B7F0"/><stop offset="0.339" stop-color="#009DE5"/><stop offset="0.755" stop-color="#0082D9"/><stop offset="1" stop-color="#0078D4"/></linearGradient><linearGradient id="skype2FE" x1="33.446" y1="53.7417" x2="58.7384" y2="39.124" gradientUnits="userSpaceOnUse"><stop stop-color="#0078D4"/><stop offset="0.37" stop-color="#007AD5"/><stop offset="0.573" stop-color="#0082D9"/><stop offset="0.735" stop-color="#0090DF"/><stop offset="0.875" stop-color="#00A3E7"/><stop offset="1" stop-color="#00BCF2"/></linearGradient><linearGradient id="skype3FE" x1="26.8904" y1="20.4817" x2="49.9459" y2="60.4589" gradientUnits="userSpaceOnUse"><stop stop-color="#00B7F0"/><stop offset="1" stop-color="#007CC1"/></linearGradient></defs>
                            </svg>
                        </div>
                        <?php endif; ?>

                        <?php if ( $wechat ): ?>
                        <div title="WeChat: <?php echo esc_attr($wechat); ?>" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white shadow-md shadow-gray-200 group transition-all duration-300 hover:shadow-lg cursor-pointer">
                            <svg class="transition-all duration-300 group-hover:scale-110" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 72 72" fill="none">
                                <path d="M45.8956 26.0879C46.8845 26.0879 47.8503 26.1701 48.8032 26.2876C47.0805 18.7224 39.1346 13 29.5799 13C18.7651 13 10 20.3257 10 29.3599C10 34.5686 12.9306 39.1897 17.4761 42.1798L14.8948 47.3562L21.9268 44.336C23.4347 44.873 25.0016 45.3152 26.6753 45.521C26.4465 44.5226 26.3166 43.4991 26.3166 42.4487C26.3166 33.4273 35.0976 26.0879 45.8956 26.0879ZM36.1064 20.3615C37.4577 20.3615 38.5536 21.4608 38.5536 22.8158C38.5536 24.1713 37.4578 25.2698 36.1064 25.2698C34.7543 25.2698 33.6589 24.1713 33.6589 22.8158C33.6589 21.4607 34.7543 20.3615 36.1064 20.3615ZM23.0531 25.2698C21.7016 25.2698 20.6057 24.1713 20.6057 22.8158C20.6057 21.4608 21.7017 20.3615 23.0531 20.3615C24.4045 20.3615 25.5006 21.4608 25.5006 22.8158C25.5005 24.1713 24.4044 25.2698 23.0531 25.2698Z" fill="#51C332"/><path d="M62.2121 42.4484C62.2121 35.22 54.9051 29.3599 45.8956 29.3599C36.8858 29.3599 29.5799 35.22 29.5799 42.4484C29.5799 49.6763 36.8858 55.5365 45.8956 55.5365C47.3773 55.5365 48.7867 55.3271 50.1542 55.0297L58.9489 58.8084L55.9072 52.713C59.7191 50.3174 62.2121 46.6335 62.2121 42.4484ZM41.001 41.6303C39.6496 41.6303 38.5534 40.5314 38.5534 39.1757C38.5534 37.8207 39.6495 36.7222 41.001 36.7222C42.3528 36.7222 43.4482 37.8212 43.4482 39.1757C43.4482 40.5316 42.3526 41.6303 41.001 41.6303ZM50.7905 41.6303C49.4385 41.6303 48.3433 40.5314 48.3433 39.1757C48.3433 37.8207 49.4384 36.7222 50.7905 36.7222C52.1425 36.7222 53.238 37.8212 53.238 39.1757C53.238 40.5316 52.1425 41.6303 50.7905 41.6303Z" fill="#51C332"/>
                            </svg>
                        </div>
                        <?php endif; ?>

                        <?php if ( $line ): ?>
                        <div title="Line: <?php echo esc_attr($line); ?>" class="w-10 h-10 flex items-center justify-center rounded-lg bg-[#51C332] text-white shadow-md shadow-gray-200 group transition-all duration-300 hover:shadow-lg cursor-pointer">
                            <span class="transition-all duration-300 group-hover:scale-110 font-bold text-[11px]">LINE</span>
                        </div>
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
