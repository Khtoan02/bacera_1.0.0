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
                        
                        Liên hệ & Kết nối
                    </h3>

                    <?php if ( $phone || $email ): ?>
                    <div class="flex flex-wrap gap-x-6 gap-y-4 text-[13px] text-textmain font-medium mb-5">
                        <?php if ( $phone ): ?>
                        <a href="tel:<?php echo esc_attr( preg_replace('/[^0-9\+]/', '', $phone) ); ?>" class="flex items-center gap-2 hover:text-terracotta transition-colors group">
                            <span class="w-8 h-8 rounded-full bg-accent/10 flex items-center justify-center text-accentdark group-hover:bg-terracotta group-hover:text-white transition-colors">
                                
                            </span>
                            <?php echo esc_html($phone); ?>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ( $email ): ?>
                        <a href="mailto:<?php echo esc_attr($email); ?>" class="flex items-center gap-2 hover:text-terracotta transition-colors group">
                            <span class="w-8 h-8 rounded-full bg-accent/10 flex items-center justify-center text-accentdark group-hover:bg-terracotta group-hover:text-white transition-colors">
                                
                            </span>
                            <?php echo esc_html($email); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="flex items-center gap-3 flex-wrap">

                        <?php /* ── FACEBOOK ── */ if ( $facebook ): ?>
                        <a href="<?php echo esc_url($facebook); ?>" target="_blank" rel="noopener noreferrer" title="Facebook" class="transition-all duration-500 hover:-translate-y-2" style="display:inline-block;line-height:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 93 92" fill="none"><rect x="1.13867" width="91.5618" height="91.5618" rx="15" fill="#337FFF"/><path d="M57.4233 48.6403L58.7279 40.3588H50.6917V34.9759C50.6917 32.7114 51.8137 30.4987 55.4013 30.4987H59.1063V23.4465C56.9486 23.1028 54.7685 22.9168 52.5834 22.8901C45.9692 22.8901 41.651 26.8626 41.651 34.0442V40.3588H34.3193V48.6403H41.651V68.671H50.6917V48.6403H57.4233Z" fill="white"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── INSTAGRAM ── */ if ( $instagram ): ?>
                        <a href="<?php echo esc_url($instagram); ?>" target="_blank" rel="noopener noreferrer" title="Instagram" class="transition-all duration-500 hover:-translate-y-2" style="display:inline-block;line-height:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 93 92" fill="none"><rect x="1.13867" width="91.5618" height="91.5618" rx="15" fill="url(#ig_fe)"/><path d="M38.3762 45.7808C38.3762 41.1786 42.1083 37.4468 46.7132 37.4468C51.3182 37.4468 55.0522 41.1786 55.0522 45.7808C55.0522 50.383 51.3182 54.1148 46.7132 54.1148C42.1083 54.1148 38.3762 50.383 38.3762 45.7808ZM33.8683 45.7808C33.8683 52.8708 39.619 58.618 46.7132 58.618C53.8075 58.618 59.5581 52.8708 59.5581 45.7808C59.5581 38.6908 53.8075 32.9436 46.7132 32.9436C39.619 32.9436 33.8683 38.6908 33.8683 45.7808ZM57.0648 32.4346C57.0646 33.0279 57.2404 33.608 57.5701 34.1015C57.8997 34.595 58.3684 34.9797 58.9168 35.2069C59.4652 35.4342 60.0688 35.4939 60.6511 35.3784C61.2334 35.2628 61.7684 34.9773 62.1884 34.5579C62.6084 34.1385 62.8945 33.6041 63.0105 33.0222C63.1266 32.4403 63.0674 31.8371 62.8404 31.2888C62.6134 30.7406 62.2289 30.2719 61.7354 29.942C61.2418 29.6122 60.6615 29.436 60.0679 29.4358H60.0667C59.2708 29.4361 58.5077 29.7522 57.9449 30.3144C57.3821 30.8767 57.0655 31.6392 57.0648 32.4346ZM36.6072 66.1302C34.1683 66.0192 32.8427 65.6132 31.9618 65.2702C30.7939 64.8158 29.9606 64.2746 29.0845 63.4002C28.2083 62.5258 27.666 61.6938 27.2133 60.5266C26.8699 59.6466 26.4637 58.3214 26.3528 55.884C26.2316 53.2488 26.2073 52.4572 26.2073 45.781C26.2073 39.1048 26.2336 38.3154 26.3528 35.678C26.4639 33.2406 26.8731 31.918 27.2133 31.0354C27.668 29.8682 28.2095 29.0354 29.0845 28.1598C29.9594 27.2842 30.7919 26.7422 31.9618 26.2898C32.8423 25.9466 34.1683 25.5406 36.6072 25.4298C39.244 25.3086 40.036 25.2844 46.7132 25.2844C53.3904 25.2844 54.1833 25.3106 56.8223 25.4298C59.2612 25.5408 60.5846 25.9498 61.4677 26.2898C62.6356 26.7422 63.4689 27.2854 64.345 28.1598C65.2211 29.0342 65.7615 29.8682 66.2161 31.0354C66.5595 31.9154 66.9658 33.2406 67.0767 35.678C67.1979 38.3154 67.2221 39.1048 67.2221 45.781C67.2221 52.4572 67.1979 53.2466 67.0767 55.884C66.9656 58.3214 66.5573 59.6462 66.2161 60.5266C65.7615 61.6938 65.2199 62.5266 64.345 63.4002C63.4701 64.2738 62.6356 64.8158 61.4677 65.2702C60.5872 65.6134 59.2612 66.0194 56.8223 66.1302C54.1855 66.2514 53.3934 66.2756 46.7132 66.2756C40.033 66.2756 39.2432 66.2514 36.6072 66.1302ZM36.4001 20.9322C33.7371 21.0534 31.9174 21.4754 30.3282 22.0934C28.6824 22.7316 27.2892 23.5878 25.897 24.977C24.5047 26.3662 23.6502 27.7608 23.0116 29.4056C22.3933 30.9948 21.971 32.8124 21.8497 35.4738C21.7265 38.1394 21.6982 38.9916 21.6982 45.7808C21.6982 52.57 21.7265 53.4222 21.8497 56.0878C21.971 58.7494 22.3933 60.5668 23.0116 62.156C23.6502 63.7998 24.5049 65.196 25.897 66.5846C27.289 67.9732 28.6824 68.8282 30.3282 69.4682C31.9204 70.0862 33.7371 70.5082 36.4001 70.6294C39.0687 70.7506 39.92 70.7808 46.7132 70.7808C53.5065 70.7808 54.3592 70.7526 57.0264 70.6294C59.6896 70.5082 61.5081 70.0862 63.0983 69.4682C64.7431 68.8282 66.1373 67.9738 67.5295 66.5846C68.9218 65.1954 69.7745 63.7998 70.4149 62.156C71.0332 60.5668 71.4575 58.7492 71.5768 56.0878C71.698 53.4202 71.7262 52.57 71.7262 45.7808C71.7262 38.9916 71.698 38.1394 71.5768 35.4738C71.4555 32.8122 71.0332 30.9938 70.4149 29.4056C69.7745 27.7618 68.9196 26.3684 67.5295 24.977C66.1395 23.5856 64.7431 22.7316 63.1003 22.0934C61.5081 21.4754 59.6894 21.0514 57.0284 20.9322C54.3612 20.811 53.5085 20.7808 46.7152 20.7808C39.922 20.7808 39.0687 20.809 36.4001 20.9322Z" fill="white"/><defs><linearGradient id="ig_fe" x1="90.9407" y1="91.5618" x2="-0.621143" y2="0" gradientUnits="userSpaceOnUse"><stop stop-color="#FBE18A"/><stop offset="0.21" stop-color="#FCBB45"/><stop offset="0.38" stop-color="#F75274"/><stop offset="0.52" stop-color="#D53692"/><stop offset="0.74" stop-color="#8F39CE"/><stop offset="1" stop-color="#5B4FE9"/></linearGradient></defs></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── X / TWITTER ── */ if ( $x_twitter ): ?>
                        <a href="<?php echo esc_url($x_twitter); ?>" target="_blank" rel="noopener noreferrer" title="X / Twitter" class="transition-all duration-500 hover:-translate-y-2" style="display:inline-block;line-height:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 93 92" fill="none"><rect x="0.138672" width="91.5618" height="91.5618" rx="15" fill="black"/><path d="M50.7568 42.1716L69.3704 21H64.9596L48.7974 39.383L35.8887 21H21L40.5205 48.7983L21 71H25.4111L42.4788 51.5869L56.1113 71H71L50.7557 42.1716H50.7568ZM44.7152 49.0433L42.7374 46.2752L27.0005 24.2492H33.7756L46.4755 42.0249L48.4533 44.7929L64.9617 67.8986H58.1865L44.7152 49.0443V49.0433Z" fill="white"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── TIKTOK ── */ if ( $tiktok ): ?>
                        <a href="<?php echo esc_url($tiktok); ?>" target="_blank" rel="noopener noreferrer" title="TikTok" class="transition-all duration-500 hover:-translate-y-2" style="display:inline-block;line-height:0;">
                            <svg width="40" height="40" viewBox="0 0 92 92" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="0.138672" width="91.5618" height="91.5618" rx="15" fill="black"/><path fill-rule="evenodd" clip-rule="evenodd" d="M55.6721 39.4285C58.7387 41.6085 62.4112 42.7733 66.1737 42.7592V35.3024C65.434 35.3045 64.6963 35.2253 63.9739 35.0663V41.0068C60.203 41.0135 56.5252 39.8354 53.4599 37.6389V52.9749C53.4507 55.4914 52.7606 57.9585 51.4628 60.1146C50.165 62.2706 48.3079 64.0353 46.0885 65.2215C43.8691 66.4076 41.37 66.9711 38.8563 66.852C36.3426 66.733 33.9079 65.9359 31.8105 64.5453C33.7506 66.5082 36.2295 67.8513 38.9333 68.4044C41.6372 68.9576 44.4444 68.6959 46.9994 67.6526C49.5545 66.6093 51.7425 64.8312 53.2864 62.5436C54.8302 60.256 55.6605 57.5616 55.6721 54.8018V39.4285ZM58.3938 31.8226C56.8343 30.1323 55.8775 27.9739 55.6721 25.6832V24.7139H53.5842C53.8423 26.1699 54.4039 27.5553 55.2326 28.78C56.0612 30.0048 57.1383 31.0414 58.3938 31.8226ZM36.645 58.642C35.9213 57.6957 35.4779 56.5653 35.365 55.3793C35.2522 54.1934 35.4746 52.9996 36.0068 51.9338C36.5391 50.8681 37.3598 49.9731 38.3757 49.3508C39.3915 48.7285 40.5616 48.4039 41.7529 48.4139C42.4106 48.4137 43.0644 48.5143 43.6916 48.7121V41.0068C42.9584 40.9097 42.2189 40.8682 41.4794 40.8826V46.8728C39.9522 46.39 38.2992 46.4998 36.8492 47.1803C35.3992 47.8608 34.2585 49.0621 33.6539 50.5454C33.0494 52.0286 33.0252 53.6851 33.5864 55.1853C34.1475 56.6855 35.2527 57.9196 36.6823 58.642H36.645Z" fill="#EE1D52"/><path fill-rule="evenodd" clip-rule="evenodd" d="M53.4589 37.5892C56.5241 39.7857 60.2019 40.9638 63.9729 40.9571V35.0166C61.8243 34.5623 59.8726 33.4452 58.3927 31.8226C57.1372 31.0414 56.0601 30.0048 55.2315 28.78C54.4029 27.5553 53.8412 26.1699 53.5831 24.7139H48.09V54.8018C48.0849 56.1336 47.6629 57.4304 46.8831 58.51C46.1034 59.5897 45.0051 60.3981 43.7425 60.8217C42.4798 61.2453 41.1162 61.2629 39.8431 60.872C38.57 60.4811 37.4512 59.7012 36.6439 58.642C35.3645 57.9965 34.3399 56.9387 33.7354 55.6394C33.1309 54.3401 32.9818 52.875 33.3121 51.4805C33.6424 50.0861 34.4329 48.8435 35.556 47.9535C36.6791 47.0634 38.0693 46.5776 39.5023 46.5745C40.1599 46.5766 40.8134 46.6772 41.4411 46.8728V40.8826C38.7288 40.9477 36.0946 41.8033 33.8617 43.3444C31.6289 44.8855 29.8946 47.0451 28.8717 49.5579C27.8489 52.0708 27.5821 54.8276 28.1039 57.49C28.6258 60.1524 29.9137 62.6045 31.8095 64.5453C33.9073 65.9459 36.3458 66.7512 38.8651 66.8755C41.3845 66.9997 43.8904 66.4383 46.1158 65.2509C48.3413 64.0636 50.2031 62.2948 51.5027 60.133C52.8024 57.9712 53.4913 55.4973 53.4962 52.9749L53.4589 37.5892Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M63.9736 35.0161V33.4129C62.0005 33.4213 60.0655 32.8696 58.3934 31.8221C59.8695 33.4493 61.8229 34.5674 63.9736 35.0161ZM53.5838 24.7134C53.5838 24.4275 53.4968 24.1292 53.4596 23.8434V22.874H45.8785V52.9744C45.872 54.6598 45.197 56.2738 44.0017 57.4621C42.8064 58.6504 41.1885 59.3159 39.503 59.3126C38.5106 59.3176 37.5311 59.0876 36.6446 58.6415C37.4519 59.7007 38.5707 60.4805 39.8438 60.8715C41.1169 61.2624 42.4805 61.2448 43.7432 60.8212C45.0058 60.3976 46.1041 59.5892 46.8838 58.5095C47.6636 57.4298 48.0856 56.1331 48.0907 54.8013V24.7134H53.5838ZM41.4418 40.8696V39.167C38.3222 38.7432 35.1511 39.3885 32.4453 40.9977C29.7394 42.6069 27.6584 45.0851 26.5413 48.0284C25.4242 50.9718 25.337 54.2067 26.2938 57.206C27.2506 60.2053 29.195 62.792 31.8102 64.5448C29.9287 62.5995 28.6545 60.1484 28.1433 57.4908C27.6321 54.8333 27.906 52.0844 28.9315 49.5799C29.957 47.0755 31.6897 44.924 33.918 43.3882C36.1463 41.8524 38.7736 40.9988 41.4791 40.9318L41.4418 40.8696Z" fill="#69C9D0"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── LINKEDIN ── */ if ( $linkedin ): ?>
                        <a href="<?php echo esc_url($linkedin); ?>" target="_blank" rel="noopener noreferrer" title="LinkedIn" class="transition-all duration-500 hover:-translate-y-2" style="display:inline-block;line-height:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 93 93" fill="none"><rect x="1.13867" y="1" width="91.5618" height="91.5618" rx="15" fill="#006699"/><path d="M37.1339 63.4304V40.9068H29.6473V63.4304H37.1346H37.1339ZM33.3922 37.8321C36.0023 37.8321 37.6273 36.1025 37.6273 33.9411C37.5785 31.7304 36.0023 30.0491 33.4418 30.0491C30.8795 30.0491 29.2061 31.7304 29.2061 33.9409C29.2061 36.1023 30.8305 37.8319 33.3431 37.8319H33.3916L33.3922 37.8321ZM41.2777 63.4304H48.7637V50.8535C48.7637 50.1813 48.8125 49.5072 49.0103 49.0271C49.5513 47.6815 50.7831 46.2887 52.8517 46.2887C55.5599 46.2887 56.644 48.354 56.644 51.3822V63.4304H64.1297V50.516C64.1297 43.598 60.4369 40.3787 55.5115 40.3787C51.4733 40.3787 49.6998 42.6357 48.7144 44.173H48.7643V40.9075H41.2781C41.3759 43.0205 41.2775 63.4312 41.2775 63.4312L41.2777 63.4304Z" fill="white"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── YOUTUBE ── */ if ( $youtube ): ?>
                        <a href="<?php echo esc_url($youtube); ?>" target="_blank" rel="noopener noreferrer" title="YouTube" class="transition-all duration-500 hover:-translate-y-2" style="display:inline-block;line-height:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 93 93" fill="none"><rect x="1.13867" y="1" width="91.5618" height="91.5618" rx="15" fill="#FF0000"/><path fill-rule="evenodd" clip-rule="evenodd" d="M67.5615 29.2428C69.8115 29.8504 71.58 31.6234 72.1778 33.8708C73.2654 37.9495 73.2654 46.4647 73.2654 46.4647C73.2654 46.4647 73.2654 54.98 72.1778 59.0586C71.5717 61.3144 69.8032 63.0873 67.5615 63.6866C63.4932 64.7771 47.1703 64.7771 47.1703 64.7771C47.1703 64.7771 30.8557 64.7771 26.7791 63.6866C24.5291 63.079 22.7606 61.306 22.1628 59.0586C21.0752 54.98 21.0752 46.4647 21.0752 46.4647C21.0752 46.4647 21.0752 37.9495 22.1628 33.8708C22.7689 31.615 24.5374 29.8421 26.7791 29.2428C30.8557 28.1523 47.1703 28.1523 47.1703 28.1523C47.1703 28.1523 63.4932 28.1523 67.5615 29.2428ZM55.5142 46.4647L41.9561 54.314V38.6154L55.5142 46.4647Z" fill="white"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── PINTEREST ── */ if ( ! empty($pinterest) ): ?>
                        <a href="<?php echo esc_url($pinterest); ?>" target="_blank" rel="noopener noreferrer" title="Pinterest" class="transition-all duration-500 hover:-translate-y-2" style="display:inline-block;line-height:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 93 92" fill="none"><rect x="1.13867" width="91.5618" height="91.5618" rx="15" fill="#FF0000"/><path d="M44.2808 23.0437C36.8492 23.893 29.4439 30.0479 29.1382 38.84C28.9461 44.2083 30.435 48.2356 35.4258 49.3664C37.5915 45.4553 34.7272 44.5927 34.2818 41.7633C32.4523 30.1686 47.346 22.2615 55.14 30.3563C60.5324 35.9615 56.9826 53.206 48.2848 51.4136C39.9537 49.7017 52.3629 35.9749 45.713 33.2796C40.3074 31.0894 37.4343 39.9798 39.9974 44.396C38.4953 51.9902 35.2599 59.1464 36.5698 68.6715C40.8183 65.5158 42.2504 59.4727 43.425 53.1702C45.5601 54.4978 46.6998 55.8789 49.4244 56.0935C59.4714 56.8891 65.0822 45.8263 63.7112 35.6218C62.4929 26.5749 53.6729 21.971 44.2808 23.0437Z" fill="white"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── MESSENGER ── */ if ( ! empty($messenger) ): ?>
                        <a href="<?php echo esc_url($messenger); ?>" target="_blank" rel="noopener noreferrer" title="Messenger" class="transition-all duration-500 hover:-translate-y-2" style="display:inline-block;line-height:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 93 93" fill="none"><rect x="0.138672" y="1" width="91.5618" height="91.5618" rx="15" fill="url(#msg_fe)"/><path fill-rule="evenodd" clip-rule="evenodd" d="M46.4114 21C32.0561 21 20.9307 31.317 20.9307 45.2508C20.9307 52.5396 23.9761 58.8375 28.9338 63.1887C29.3491 63.5559 29.6003 64.0639 29.6208 64.6122L29.7592 69.059C29.8054 70.4775 31.2973 71.398 32.62 70.8296L37.6752 68.6414C38.1058 68.4553 38.5826 68.4201 39.0338 68.5408C41.3563 69.1696 43.8326 69.5016 46.4114 69.5016C60.7668 69.5016 71.8922 59.1846 71.8922 45.2508C71.8922 31.317 60.7668 21 46.4114 21ZM61.7102 39.6572L54.2249 51.3072C53.0354 53.1584 50.4822 53.6211 48.698 52.3082L42.7457 47.9269C42.1971 47.5245 41.4486 47.5295 40.9051 47.9319L32.8661 53.9179C31.7946 54.7177 30.3898 53.4551 31.1127 52.3384L38.598 40.6884C39.7875 38.8372 42.3407 38.3745 44.1248 39.6874L50.0772 44.0687C50.6258 44.4711 51.3743 44.4661 51.9177 44.0637L59.9567 38.0777C61.0283 37.2779 62.433 38.5405 61.7102 39.6572Z" fill="white"/><defs><radialGradient id="msg_fe" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(15.4753 92.5593) scale(100.718 100.715)"><stop stop-color="#0099FF"/><stop offset="0.6" stop-color="#A033FF"/><stop offset="0.9" stop-color="#FF5280"/><stop offset="1" stop-color="#FF7061"/></radialGradient></defs></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── TELEGRAM ── */ if ( ! empty($telegram) ): ?>
                        <a href="<?php echo esc_url($telegram); ?>" target="_blank" rel="noopener noreferrer" title="Telegram" class="transition-all duration-500 hover:-translate-y-2" style="display:inline-block;line-height:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 92 93" fill="none"><rect x="0.138672" y="1" width="91.5618" height="91.5618" rx="15" fill="#34AADF"/><path d="M25.0881 43.5652C25.0881 43.5652 43.716 35.7194 50.1765 32.9567C52.6532 31.8518 61.0518 28.3155 61.0518 28.3155C61.0518 28.3155 64.9282 26.7685 64.6052 30.5256C64.4974 32.0728 63.6361 37.4874 62.7747 43.3442C61.4825 51.6322 60.0827 60.6935 60.0827 60.6935C60.0827 60.6935 59.8674 63.2352 58.0369 63.6772C56.2065 64.1192 53.1914 62.1302 52.6532 61.6881C52.2223 61.3566 44.5774 56.3838 41.7778 53.9527C41.0241 53.2897 40.1627 51.9637 41.8854 50.4166C45.7618 46.7699 50.3919 42.2392 53.1914 39.3661C54.4836 38.04 55.7757 34.9459 50.3919 38.703C42.7469 44.1178 35.2096 49.201 35.2096 49.201C35.2096 49.201 33.4868 50.306 30.2565 49.3115C27.0261 48.317 23.2575 46.9909 23.2575 46.9909C23.2575 46.9909 20.6734 45.3334 25.0881 43.5652Z" fill="white"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── WHATSAPP ── */ if ( $whatsapp ): ?>
                        <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $whatsapp)); ?>" target="_blank" rel="noopener noreferrer" title="WhatsApp" class="transition-all duration-500 hover:-translate-y-2" style="display:inline-block;line-height:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 93 92" fill="none"><rect x="1.13867" width="91.5618" height="91.5618" rx="15" fill="#00D95F"/><path d="M23.5068 66.8405L26.7915 54.6381C24.1425 49.8847 23.3009 44.3378 24.4211 39.0154C25.5413 33.693 28.5482 28.952 32.89 25.6624C37.2319 22.3729 42.6173 20.7554 48.0583 21.1068C53.4992 21.4582 58.6306 23.755 62.5108 27.5756C66.3911 31.3962 68.7599 36.4844 69.1826 41.9065C69.6053 47.3286 68.0535 52.7208 64.812 57.0938C61.5705 61.4668 56.8568 64.5271 51.5357 65.7133C46.2146 66.8994 40.6432 66.1318 35.8438 63.5513L23.5068 66.8405ZM36.4386 58.985L37.2016 59.4365C40.6779 61.4918 44.7382 62.3423 48.7498 61.8555C52.7613 61.3687 56.4987 59.5719 59.3796 56.7452C62.2605 53.9185 64.123 50.2206 64.6769 46.2279C65.2308 42.2351 64.445 38.1717 62.4419 34.6709C60.4388 31.1701 57.331 28.4285 53.6027 26.8734C49.8745 25.3184 45.7352 25.0372 41.8299 26.0736C37.9247 27.11 34.4729 29.4059 32.0124 32.6035C29.5519 35.801 28.2209 39.7206 28.2269 43.7514C28.2237 47.0937 29.1503 50.3712 30.9038 53.2192L31.3823 54.0061L29.546 60.8167L36.4386 58.985Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M54.9566 46.8847C54.5093 46.5249 53.9856 46.2716 53.4254 46.1442C52.8651 46.0168 52.2831 46.0186 51.7236 46.1495C50.8831 46.4977 50.3399 47.8134 49.7968 48.4713C49.6823 48.629 49.514 48.7396 49.3235 48.7823C49.133 48.8251 48.9335 48.797 48.7623 48.7034C45.6849 47.5012 43.1055 45.2965 41.4429 42.4475C41.3011 42.2697 41.2339 42.044 41.2557 41.8178C41.2774 41.5916 41.3862 41.3827 41.5593 41.235C42.165 40.6368 42.6098 39.8959 42.8524 39.0809C42.9063 38.1818 42.6998 37.2863 42.2576 36.5011C41.9157 35.4002 41.265 34.42 40.3825 33.6762C39.9273 33.472 39.4225 33.4036 38.9292 33.4791C38.4359 33.5546 37.975 33.7709 37.6021 34.1019C36.9548 34.6589 36.4411 35.3537 36.0987 36.135C35.7562 36.9163 35.5939 37.7643 35.6236 38.6165C35.6256 39.0951 35.6864 39.5716 35.8046 40.0354C36.1049 41.1497 36.5667 42.2144 37.1754 43.1956C37.6145 43.9473 38.0937 44.6749 38.6108 45.3755C40.2914 47.6767 42.4038 49.6305 44.831 51.1284C46.049 51.8897 47.3507 52.5086 48.7105 52.973C50.1231 53.6117 51.6827 53.8568 53.2237 53.6824C54.1018 53.5499 54.9337 53.2041 55.6462 52.6755C56.3588 52.1469 56.9302 51.4518 57.3102 50.6512C57.5334 50.1675 57.6012 49.6269 57.5042 49.1033C57.2714 48.0327 55.836 47.4007 54.9566 46.8847Z" fill="white"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── ZALO ── */ if ( $zalo ):
                            $zalo_href = filter_var($zalo, FILTER_VALIDATE_URL) ? $zalo : 'https://zalo.me/' . preg_replace('/[^0-9]/', '', $zalo);
                        ?>
                        <a href="<?php echo esc_url($zalo_href); ?>" target="_blank" rel="noopener noreferrer" title="Zalo" class="transition-all duration-500 hover:-translate-y-2" style="display:inline-block;line-height:0;">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/Icon_of_Zalo.svg/250px-Icon_of_Zalo.svg.png" alt="Zalo" width="40" height="40" style="border-radius:10px;object-fit:contain;">
                        </a>
                        <?php endif; ?>

                        <?php /* ── VIBER ── */ if ( ! empty($viber) ): ?>
                        <a href="viber://chat?number=<?php echo esc_attr(preg_replace('/[^0-9]/', '', $viber)); ?>" title="Viber" class="transition-all duration-500 hover:-translate-y-2" style="display:inline-block;line-height:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 92 93" fill="none"><rect x="0.138672" y="1" width="91.5618" height="91.5618" rx="15" fill="#754A91"/><path d="M35.396 64.818C35.396 64.0512 35.396 63.2844 35.396 62.5176C35.4014 62.4862 35.4003 62.4541 35.3927 62.4232C35.3851 62.3923 35.3712 62.3632 35.3518 62.3379C35.3324 62.3125 35.308 62.2913 35.28 62.2757C35.252 62.26 35.2211 62.2503 35.1892 62.2469C33.2693 61.707 31.4973 60.7451 30.0052 59.4329C28.918 58.4538 28.0128 57.2935 27.3308 56.0047C26.3695 54.143 25.7297 52.1352 25.438 50.0644C25.0766 47.4669 24.9511 44.8424 25.063 42.2227C25.0875 41.0013 25.1506 39.7938 25.2908 38.5724C25.5144 36.2515 26.1059 33.9798 27.0434 31.841C28.201 29.2112 30.3012 27.0989 32.9389 25.9111C34.7357 25.0971 36.626 24.5029 38.568 24.1415C40.4725 23.7844 42.4007 23.5652 44.3374 23.4857C46.0831 23.4246 47.8308 23.4501 49.574 23.5621C52.0688 23.6663 54.5411 24.0754 56.9346 24.78C58.7092 25.2864 60.3974 26.0524 61.9434 27.0527C63.4496 28.0763 64.6679 29.4627 65.4835 31.0811C66.4423 32.9714 67.094 34.9996 67.4148 37.0908C67.6231 38.3324 67.76 39.5847 67.8249 40.8417C67.916 42.3996 67.9055 43.9645 67.8459 45.5155C67.7863 47.0665 67.6531 48.5724 67.4603 50.0922C67.2167 52.3732 66.5084 54.5817 65.3783 56.5842C63.9074 59.1174 61.5511 61.0303 58.7537 61.9624C56.841 62.6255 54.8659 63.0974 52.8582 63.3711C51.3896 63.5724 49.9175 63.7181 48.4383 63.7771C47.3062 63.8292 46.174 63.8153 45.0419 63.7979C44.4005 63.7979 43.7626 63.7355 43.1246 63.6799C43.062 63.6683 42.9973 63.6752 42.9386 63.6999C42.88 63.7246 42.8301 63.766 42.7952 63.8187C41.5053 65.3524 40.1664 66.8444 38.8169 68.3295C38.5711 68.6004 38.2865 68.8342 37.9722 69.0234C37.7659 69.1574 37.5306 69.2415 37.2854 69.2689C37.0402 69.2963 36.7919 69.2663 36.5606 69.1811C36.3293 69.096 36.1215 68.9583 35.9538 68.779C35.7862 68.5997 35.6636 68.3839 35.5957 68.1491C35.4622 67.7317 35.3959 67.2961 35.3995 66.8583C35.396 66.1713 35.396 65.4947 35.396 64.818ZM36.9557 67.7292L37.1204 67.5765L40.0472 64.36C41.1735 63.1225 42.2986 61.8837 43.4226 60.6439C43.4778 60.5682 43.5523 60.5085 43.6386 60.4707C43.7248 60.4329 43.8196 60.4184 43.9133 60.4287C44.2953 60.4669 44.6774 60.4808 45.0629 60.4843C46.3949 60.4843 47.7268 60.4843 49.0587 60.3906C50.268 60.2969 51.4737 60.1512 52.6724 59.9638C53.9631 59.7414 55.2408 59.4506 56.5 59.0929C59.304 58.3469 61.4737 56.7507 62.8371 54.1657C63.6421 52.5764 64.147 50.8549 64.3268 49.0859C64.5399 47.4187 64.6674 45.7417 64.7089 44.0617C64.771 41.9511 64.6254 39.8396 64.2742 37.757C64.0307 36.2482 63.5925 34.7765 62.9703 33.3781C62.1782 31.6432 61.0566 30.2067 59.3286 29.2838C56.6787 27.8681 53.8116 27.2366 50.8568 26.9139C50.0191 26.8237 49.1814 26.7751 48.3402 26.7439C46.6177 26.656 44.8916 26.6652 43.1702 26.7716C41.9322 26.8626 40.7009 27.0271 39.4829 27.2643C37.6916 27.5876 35.9587 28.1723 34.3409 28.9992C33.403 29.4684 32.5521 30.0913 31.8243 30.8417C30.6447 32.1468 29.7795 33.6998 29.2936 35.3837C28.7173 37.3448 28.4026 39.3722 28.3578 41.4142C28.245 43.4562 28.2708 45.5033 28.4349 47.5419C28.537 48.7551 28.7422 49.9575 29.0483 51.1366C29.5037 52.974 30.3871 54.6804 31.628 56.1192C32.9676 57.5872 34.6991 58.6505 36.6227 59.1866C36.8646 59.2594 36.9732 59.3427 36.9732 59.6133C36.9522 60.6126 36.9732 61.6119 36.9732 62.6112L36.9557 67.7292Z" fill="white"/><path d="M34.5029 36.7373C34.4784 35.9774 34.8955 35.4257 35.4002 34.9295C36.0659 34.2982 36.7993 33.7409 37.5874 33.2675C37.9467 33.0481 38.374 32.9645 38.7906 33.0321C39.2073 33.0996 39.5853 33.3138 39.8551 33.6353C40.8086 34.6275 41.6621 35.7094 42.4033 36.8657C42.7704 37.3858 43.0476 37.9627 43.2235 38.5728C43.2938 38.8109 43.2984 39.0633 43.2368 39.3037C43.1751 39.5442 43.0495 39.7638 42.873 39.9399C42.4686 40.3328 42.0377 40.698 41.5831 41.0329C41.3541 41.2136 41.1838 41.457 41.0934 41.7328C41.0031 42.0085 40.9966 42.3045 41.0749 42.5839C41.3556 43.8958 41.9917 45.1072 42.9151 46.0884C44.037 47.4443 45.4826 48.5021 47.1211 49.1661C47.5238 49.3711 47.9851 49.4335 48.4285 49.3431C48.6714 49.2775 48.8872 49.1379 49.0454 48.9441C49.2908 48.6352 49.5572 48.3368 49.778 48.0072C50.0644 47.596 50.5017 47.3119 50.9967 47.2153C51.4917 47.1187 52.0052 47.2174 52.4278 47.4902C53.6159 48.1726 54.7377 48.9625 55.7787 49.8497C56.024 50.0579 56.2799 50.2591 56.5252 50.4708C56.8156 50.69 57.0252 50.9974 57.1221 51.3458C57.219 51.6943 57.1977 52.0646 57.0615 52.4C56.845 52.9639 56.5293 53.4855 56.1292 53.9406C55.6319 54.6247 54.9985 55.201 54.268 55.6338C53.9188 55.8369 53.5263 55.9561 53.1222 55.982C52.718 56.0079 52.3134 55.9398 51.9406 55.783C49.1048 54.6528 46.455 53.1103 44.0787 51.2064C41.7545 49.3123 39.7234 47.0911 38.05 44.6137C36.642 42.5629 35.5218 40.3322 34.7202 37.9829C34.6348 37.6988 34.5647 37.4104 34.5099 37.119C34.4943 36.9923 34.492 36.8644 34.5029 36.7373Z" fill="white"/><path d="M46.6041 33.0453C48.9768 33.0887 51.2588 33.9551 53.0523 35.4934C54.8458 37.0317 56.037 39.1444 56.4183 41.463C56.5718 42.3061 56.6539 43.1603 56.6637 44.0168C56.6637 44.2181 56.6111 44.3638 56.3938 44.3846C56.1765 44.4054 56.1204 44.2215 56.1204 44.0376C56.153 42.3051 55.7937 40.5872 55.0689 39.0099C54.4585 37.7138 53.5543 36.5746 52.4266 35.6809C51.2989 34.7872 49.9781 34.1631 48.567 33.8572C47.8209 33.6862 47.058 33.5978 46.2922 33.5935C46.2176 33.5948 46.1432 33.5855 46.0713 33.5658C46.0101 33.5487 45.958 33.5088 45.926 33.4544C45.894 33.4 45.8845 33.3355 45.8996 33.2743C45.9001 33.2453 45.9063 33.2167 45.9181 33.1901C45.9298 33.1635 45.9468 33.1395 45.968 33.1195C45.9892 33.0995 46.0142 33.0838 46.0416 33.0735C46.069 33.0631 46.0981 33.0583 46.1274 33.0592C46.2852 33.0384 46.4464 33.0453 46.6041 33.0453Z" fill="white"/><path d="M53.3703 42.4553C53.3585 41.4913 53.1187 40.5435 52.6701 39.6879C52.2215 38.8322 51.5766 38.0925 50.787 37.5282C49.8656 36.8874 48.7944 36.4898 47.6745 36.3727C47.4853 36.3485 47.296 36.338 47.1032 36.3172C46.9104 36.2964 46.7948 36.2166 46.8123 36.0223C46.8298 35.828 46.9525 35.7586 47.1628 35.7725C50.2683 35.9598 52.9111 37.6982 53.7243 40.9355C53.873 41.5598 53.9471 42.1991 53.9451 42.8405C53.9451 42.8994 53.9451 42.9584 53.9451 43.0139C53.9451 43.1874 53.833 43.3019 53.6612 43.2985C53.6231 43.2972 53.5857 43.2883 53.5512 43.2722C53.5167 43.2561 53.4859 43.2333 53.4607 43.205C53.4354 43.1768 53.4162 43.1437 53.4043 43.1079C53.3924 43.072 53.388 43.0342 53.3913 42.9966C53.3633 42.8335 53.3703 42.667 53.3703 42.4553Z" fill="white"/><path d="M47.9293 38.5898C49.6328 38.7043 50.821 39.5301 51.147 41.324C51.1865 41.5538 51.2146 41.7854 51.2311 42.018C51.2311 42.2053 51.189 42.3649 50.9822 42.3649C50.7754 42.3649 50.7053 42.2262 50.6983 42.018C50.6964 41.3303 50.4824 40.6597 50.0849 40.0957C49.6672 39.5849 49.0676 39.2521 48.4095 39.1658C48.2624 39.1385 48.1137 39.12 47.9644 39.1103C47.719 39.1103 47.5823 38.9888 47.6139 38.8015C47.6454 38.6141 47.7926 38.5794 47.9293 38.5898Z" fill="white"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── SKYPE ── */ if ( $skype ): ?>
                        <a href="skype:<?php echo esc_attr($skype); ?>?chat" title="Skype" class="transition-all duration-500 hover:-translate-y-2" style="display:inline-block;line-height:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 92 92" fill="none"><rect x="0.138672" width="91.5618" height="91.5618" rx="15" fill="#00B7F0"/><path fill-rule="evenodd" clip-rule="evenodd" d="M21.1081 36.9185C21.2825 39.0304 21.9143 41.0712 22.951 42.9027C22.7897 44.0027 22.7082 45.1152 22.7082 46.2319C22.7082 49.2582 23.3069 52.2549 24.4702 55.0508C25.6334 57.8468 27.3385 60.3872 29.4879 62.5271C31.6373 64.6671 34.1891 66.3646 36.9975 67.5227C39.8058 68.6808 42.8158 69.2769 45.8556 69.2769C46.7797 69.2769 47.7011 69.2218 48.615 69.1125C49.0204 69.3507 49.4381 69.5697 49.8667 69.7685C50.3247 69.9811 50.7948 70.1704 51.2749 70.3352L51.2815 70.3337C52.7961 70.8519 54.3999 71.1235 56.0319 71.1235C57.9518 71.1235 59.8528 70.7471 61.6266 70.0156C63.4003 69.2841 65.0119 68.212 66.3695 66.8605C67.727 65.5089 68.8039 63.9044 69.5386 62.1385C70.2733 60.3726 70.6515 58.4799 70.6515 56.5685C70.6515 56.1608 70.6343 55.7539 70.6001 55.349C70.4306 53.2486 69.8087 51.2165 68.7847 49.3893C68.9297 48.3453 69.003 47.2904 69.003 46.2319C69.003 43.2056 68.4042 40.2089 67.241 37.4129C66.0777 34.617 64.3727 32.0765 62.2233 29.9366C60.0738 27.7967 57.5221 26.0992 54.7137 24.9411C51.9053 23.7829 48.8953 23.1869 45.8556 23.1869C45.0301 23.1869 44.2068 23.2308 43.3891 23.3181C42.9962 23.0741 42.5911 22.8484 42.1748 22.6419L42.1735 22.6421C41.7305 22.4224 41.2754 22.2247 40.81 22.0502L40.8045 22.0513C40.531 21.9491 40.2543 21.855 39.9748 21.7693C39.1664 21.5204 38.3345 21.3411 37.4879 21.2352L37.4849 21.2362C36.8858 21.1615 36.2795 21.1235 35.6693 21.1235C31.7932 21.1235 28.076 22.6565 25.3352 25.3851C22.5944 28.1138 21.0547 31.8146 21.0547 35.6735C21.0547 36.0906 21.0725 36.5062 21.1081 36.9185ZM37.0659 43.0286C37.6308 43.9944 38.4114 44.8169 39.3468 45.4319C40.5861 46.2419 41.9036 46.9259 43.2795 47.4736C44.8179 48.1158 46.0114 48.6602 46.8601 49.1069C47.5778 49.4595 48.2292 49.9331 48.7856 50.5069C49.2067 50.9532 49.4407 51.5436 49.4397 52.1569C49.4578 52.5894 49.3725 53.02 49.1908 53.413C49.0092 53.806 48.7364 54.1501 48.3952 54.4169C47.4835 55.0297 46.3928 55.3201 45.2967 55.2419C44.6769 55.2483 44.0584 55.1846 43.453 55.0519C42.962 54.9442 42.4818 54.7925 42.0181 54.5986C41.6121 54.423 41.0576 54.1597 40.3546 53.8086C39.7892 53.5073 39.1599 53.3456 38.5192 53.3369C37.9442 53.3134 37.3821 53.5115 36.9491 53.8902C36.7426 54.0866 36.5812 54.3254 36.476 54.5901C36.3709 54.8548 36.3245 55.1392 36.3401 55.4236C36.3272 55.9627 36.49 56.4915 36.8039 56.9302C37.1821 57.409 37.6711 57.7888 38.2289 58.0369C40.4105 58.9814 42.7753 59.4283 45.1516 59.3452C46.8808 59.38 48.6018 59.0998 50.2305 58.5186C51.5819 58.0393 52.7587 57.1668 53.6093 56.0136C54.4446 54.7955 54.8679 53.3425 54.8173 51.8669C54.8497 50.6618 54.5477 49.4712 53.9447 48.4269C53.3322 47.4333 52.5071 46.5875 51.5286 45.9502C50.2897 45.1519 48.9753 44.477 47.6043 43.9352C47.4577 43.8635 47.306 43.8028 47.1504 43.7536C45.7455 43.1736 44.6888 42.708 43.9803 42.3569C43.3477 42.0607 42.7748 41.6511 42.29 41.1486C41.8821 40.7208 41.6571 40.151 41.6627 39.5602C41.6431 39.1102 41.7449 38.6633 41.9574 38.266C42.1699 37.8688 42.4853 37.5358 42.8707 37.3019C43.7839 36.7581 44.8349 36.4895 45.8974 36.5286C46.7125 36.52 47.5251 36.6209 48.3134 36.8286C49.1697 37.0752 50.0077 37.3815 50.8212 37.7452C51.1512 37.8982 51.5102 37.9789 51.874 37.9819C52.1384 37.9889 52.4014 37.94 52.6455 37.8383C52.8896 37.7366 53.1095 37.5845 53.2906 37.3919C53.482 37.1829 53.6303 36.9383 53.7271 36.6721C53.8238 36.4059 53.8672 36.1232 53.8546 35.8402C53.8631 35.2956 53.6727 34.7665 53.319 34.3519C52.8388 33.848 52.2433 33.4683 51.5837 33.2452C50.8653 32.9817 50.1225 32.7902 49.3662 32.6736C48.3096 32.5004 47.24 32.4185 46.1694 32.4286C44.5329 32.4189 42.9071 32.6924 41.364 33.2369C39.9453 33.7153 38.6787 34.5607 37.6933 35.6869C36.745 36.8084 36.2437 38.2395 36.285 39.7069C36.2514 40.8631 36.5205 42.0081 37.0659 43.0286Z" fill="white"/></svg>
                        </a>
                        <?php endif; ?>

                        <?php /* ── WECHAT ── */ if ( $wechat ): ?>
                        <span title="WeChat ID: <?php echo esc_attr($wechat); ?>" class="cursor-help transition-all duration-500 hover:-translate-y-2" style="display:inline-block;line-height:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 93 93" fill="none"><rect x="1.13867" y="1" width="91.5618" height="91.5618" rx="15" fill="#51C332"/><path d="M55.8615 36.5403C56.8503 36.5403 57.8161 36.6225 58.769 36.74C57.0463 29.1747 49.1004 23.4524 39.5457 23.4524C28.7309 23.4524 19.9658 30.7781 19.9658 39.8123C19.9658 45.021 22.8964 49.6421 27.4419 52.6322L24.8606 57.8086L31.8926 54.7884C33.4005 55.3254 34.9674 55.7676 36.6411 55.9734C36.4124 54.975 36.2824 53.9515 36.2824 52.901C36.2824 43.8797 45.0634 36.5403 55.8615 36.5403ZM46.0722 30.8139C47.4235 30.8139 48.5194 31.9132 48.5194 33.2682C48.5194 34.6237 47.4236 35.7222 46.0722 35.7222C44.7201 35.7222 43.6247 34.6237 43.6247 33.2682C43.6247 31.9131 44.7201 30.8139 46.0722 30.8139ZM33.0189 35.7222C31.6674 35.7222 30.5715 34.6237 30.5715 33.2682C30.5715 31.9132 31.6675 30.8139 33.0189 30.8139C34.3703 30.8139 35.4664 31.9132 35.4664 33.2682C35.4663 34.6237 34.3702 35.7222 33.0189 35.7222Z" fill="white"/><path d="M72.1779 52.9008C72.1779 45.6724 64.8709 39.8123 55.8615 39.8123C46.8517 39.8123 39.5457 45.6724 39.5457 52.9008C39.5457 60.1287 46.8517 65.9889 55.8615 65.9889C57.3432 65.9889 58.7525 65.7794 60.12 65.4821L68.9148 69.2608L65.8731 63.1654C69.6849 60.7698 72.1779 57.0859 72.1779 52.9008ZM50.9668 52.0827C49.6154 52.0827 48.5193 50.9838 48.5193 49.6281C48.5193 48.2731 49.6153 47.1746 50.9668 47.1746C52.3186 47.1746 53.4141 48.2736 53.4141 49.6281C53.4141 50.9839 52.3184 52.0827 50.9668 52.0827ZM60.7564 52.0827C59.4043 52.0827 58.3091 50.9838 58.3091 49.6281C58.3091 48.2731 59.4042 47.1746 60.7564 47.1746C62.1083 47.1746 63.2039 48.2736 63.2039 49.6281C63.2039 50.9839 62.1083 52.0827 60.7564 52.0827Z" fill="white"/></svg>
                        </span>
                        <?php endif; ?>

                        <?php /* ── LINE ── */ if ( ! empty($line_app) ): ?>
                        <span title="Line ID: <?php echo esc_attr($line_app); ?>" class="cursor-help transition-all duration-500 hover:-translate-y-2" style="display:inline-block;line-height:0;">
                            <svg width="40" height="40" viewBox="0 0 93 92" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="93" height="92" rx="15" fill="#06C755"/><path d="M78 40.2C78 25.5 63.2 13.5 46.3 13.5 29.4 13.5 14.6 25.5 14.6 40.2c0 13.2 11.7 24.3 27.6 26.4 1.1.2 2.5.7 2.9 1.7.4 1 .3 2.4 0 3.3l-.5 2.7c-.1 1-.9 3.8 1.5 2.7 2.4-1.1 13.3-7.8 18.2-13.4C69.1 59 78 50.2 78 40.2zM35.8 48.2H29v-14h3.1v11h3.7v3zm4.6 0h-3.1V34.2h3.1v14zm14 0h-3l-5.3-9.4v9.4H43V34.2h3l5.3 9.3v-9.3h3.1v14zm12.6-11h-6.2v2.5h6.2v3.1h-6.2v2.5h6.2v3H57.7V34.2H67v3z" fill="white"/></svg>
                        </span>
                        <?php endif; ?>

                    </div>
                </div>
                <?php endif; ?>

                <!-- CTA: back to team -->
                <a href="<?php echo esc_url( $team_url ); ?>"
                   class="inline-flex items-center gap-3 group text-xs uppercase tracking-[0.2em] text-textmain hover:text-terracotta transition-colors">
                    <span class="w-10 h-10 rounded-full border border-accent flex items-center justify-center group-hover:border-terracotta transition-colors">
                        
                    </span>
                    Meet the full team
                </a>

            </div>
        </div>
    </section>

    <!-- ═══ 2.5 GALLERY (Swiper Coverflow) ════════════════════════ -->
    <?php if ( ! empty( $gallery_urls ) ): ?>

    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <style>
        /* ── Gallery Swiper ─────────────────────────────── */
        .member-gallery-wrap {
            width: 100%;
            padding: 60px 0;
            overflow: hidden;
            background: transparent;
        }
        .member-swiper {
            width: 100%;
            padding-top: 20px;
            padding-bottom: 60px;
        }
        .member-swiper .swiper-slide {
            background-size: cover;
            width: 300px;
            height: 400px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            flex-shrink: 0;
        }
        @media (min-width: 768px) {
            .member-swiper .swiper-slide { width: 320px; height: 420px; }
        }
        .member-swiper .swiper-slide img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.35s ease;
        }
        .member-swiper .swiper-slide img:hover { transform: scale(1.03); }
        .member-swiper .swiper-slide-active {
            box-shadow: 0 25px 60px -10px rgba(0,0,0,0.35);
        }
        .member-swiper .swiper-pagination-bullet {
            background: #c8b8a0;
            opacity: 1;
            width: 7px; height: 7px;
            margin: 0 5px !important;
            transition: transform .2s;
        }
        .member-swiper .swiper-pagination-bullet-active {
            background: #8B5A3C;
            transform: scale(1.3);
        }

        /* ── Lightbox ───────────────────────────────────── */
        #glbOverlay {
            display: none; position: fixed; inset: 0; z-index: 9999;
            background: rgba(0,0,0,0.92);
            align-items: center; justify-content: center;
            opacity: 0; transition: opacity .3s;
        }
        #glbOverlay.open { display: flex; }
        #glbOverlay.visible { opacity: 1; }
        #glbOverlay img {
            max-height: 90vh; max-width: 92vw;
            border-radius: 10px; object-fit: contain;
            box-shadow: 0 30px 80px rgba(0,0,0,0.6);
            transform: scale(.94); transition: transform .3s;
        }
        #glbOverlay.visible img { transform: scale(1); }
        #glbClose {
            position: absolute; top: 18px; right: 22px; z-index: 10000;
            background: none; border: none; cursor: pointer;
            color: #fff; line-height: 1; font-size: 32px; opacity: .8;
        }
        #glbClose:hover { opacity: 1; }
    </style>

    <section class="max-w-[1400px] mx-auto pb-6">
        <!-- Header -->
        <div class="text-center mb-2 px-4">
            <p class="text-[10px] uppercase tracking-[.25em] text-textmuted mb-2">Gallery pictures</p>
            <h2 class="font-serif text-3xl md:text-4xl text-textmain">
                Photo of <em><?php echo esc_html( $member['name'] ?? 'Member' ); ?></em>
            </h2>
        </div>

        <!-- Swiper -->
        <div class="swiper member-swiper">
            <div class="swiper-wrapper">
                <?php foreach ( $gallery_urls as $idx => $gurl ): ?>
                <div class="swiper-slide">
                    <img src="<?php echo esc_url( $gurl ); ?>"
                         alt="<?php echo esc_attr( ($member['name'] ?? 'Gallery') . ' - ảnh ' . ($idx + 1) ); ?>"
                         loading="lazy" />
                </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </section>

    <!-- Lightbox overlay -->
    <div id="glbOverlay">
        <button id="glbClose" aria-label="Đóng">&#x2715;</button>
        <img id="glbImg" src="" alt="Ảnh phóng to" />
    </div>

    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
    (function(){
        /* ── Swiper init ── */
        var memberSwiper = new Swiper('.member-swiper', {
            effect: 'coverflow',
            grabCursor: true,
            centeredSlides: true,
            slidesPerView: 'auto',
            initialSlide: Math.min(2, <?php echo max(0, count($gallery_urls) - 1); ?>),
            coverflowEffect: {
                rotate: 0,
                stretch: 80,
                depth: 220,
                modifier: 1,
                slideShadows: true,
            },
            pagination: { el: '.member-swiper .swiper-pagination', clickable: true },
        });

        /* ── Lightbox ── */
        var overlay = document.getElementById('glbOverlay');
        var glbImg  = document.getElementById('glbImg');
        var glbClose = document.getElementById('glbClose');

        function openLightbox(src, alt) {
            glbImg.src = src;
            glbImg.alt = alt || '';
            overlay.classList.add('open');
            requestAnimationFrame(function(){
                requestAnimationFrame(function(){ overlay.classList.add('visible'); });
            });
            document.body.style.overflow = 'hidden';
        }
        function closeLightbox() {
            overlay.classList.remove('visible');
            setTimeout(function(){
                overlay.classList.remove('open');
                glbImg.src = '';
                document.body.style.overflow = '';
            }, 300);
        }

        document.querySelectorAll('.member-swiper .swiper-slide img').forEach(function(img){
            img.addEventListener('click', function(){
                openLightbox(this.src, this.alt);
            });
        });
        glbClose.addEventListener('click', closeLightbox);
        overlay.addEventListener('click', function(e){ if(e.target === overlay) closeLightbox(); });
        document.addEventListener('keydown', function(e){
            if(e.key === 'Escape') closeLightbox();
        });
    })();
    </script>

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
                
            </a>
        </div>
    </div>

</div><!-- /wrapper -->

<?php get_footer(); ?>
