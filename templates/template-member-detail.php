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
    <div class="bacera-container pt-12 pb-0">
        <nav class="flex items-center gap-2 text-xs text-textmuted mb-10 tracking-wide">
            <a href="<?php echo esc_url( $home_url ); ?>" class="text-textmain font-medium hover:text-terracotta transition-colors">Homepage</a>
            <span class="text-accent/60">/</span>
            <a href="<?php echo esc_url( $team_url ); ?>" class="hover:text-terracotta transition-colors">Our Team</a>
            <span class="text-accent/60">/</span>
            <span><?php echo esc_html( $name ); ?></span>
        </nav>
    </div>

    <!-- ═══ 2. HERO — Photo + Info ══════════════════════════════ -->
    <section class="bacera-container pb-24">
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
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z" fill="currentColor"/></svg>
                            </span>
                            <?php echo esc_html($phone); ?>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ( $email ): ?>
                        <a href="mailto:<?php echo esc_attr($email); ?>" class="flex items-center gap-2 hover:text-terracotta transition-colors group">
                            <span class="w-8 h-8 rounded-full bg-accent/10 flex items-center justify-center text-accentdark group-hover:bg-terracotta group-hover:text-white transition-colors">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" fill="currentColor"/></svg>
                            </span>
                            <?php echo esc_html($email); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="flex flex-wrap gap-3">

                        <?php /* ── FACEBOOK ── */ if ( $facebook ): ?>
                        <a href="<?php echo esc_url($facebook); ?>" target="_blank" rel="noopener noreferrer"
                           class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-[#337FFF]/10 hover:bg-[#337FFF] text-[#337FFF] hover:text-white transition-all duration-300 text-xs font-medium group">
                            <span style="display:inline-block;line-height:0;border-radius:50%;overflow:hidden;flex-shrink:0;"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 93 92" fill="none"><rect x="1.13867" width="91.5618" height="91.5618" rx="15" fill="#337FFF"/><path d="M57.4233 48.6403L58.7279 40.3588H50.6917V34.9759C50.6917 32.7114 51.8137 30.4987 55.4013 30.4987H59.1063V23.4465C56.9486 23.1028 54.7685 22.9168 52.5834 22.8901C45.9692 22.8901 41.651 26.8626 41.651 34.0442V40.3588H34.3193V48.6403H41.651V68.671H50.6917V48.6403H57.4233Z" fill="white"/></svg></span>
                            Facebook
                        </a>
                        <?php endif; ?>

                        <?php /* ── INSTAGRAM ── */ if ( $instagram ): ?>
                        <a href="<?php echo esc_url($instagram); ?>" target="_blank" rel="noopener noreferrer"
                           class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-pink-50 hover:bg-gradient-to-r hover:from-[#f75274] hover:to-[#8F39CE] text-[#f75274] hover:text-white transition-all duration-300 text-xs font-medium">
                            <span style="display:inline-block;line-height:0;border-radius:50%;overflow:hidden;flex-shrink:0;"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 93 92" fill="none"><rect x="1.13867" width="91.5618" height="91.5618" rx="15" fill="url(#ig_fe2)"/><path d="M38.3762 45.7808C38.3762 41.1786 42.1083 37.4468 46.7132 37.4468C51.3182 37.4468 55.0522 41.1786 55.0522 45.7808C55.0522 50.383 51.3182 54.1148 46.7132 54.1148C42.1083 54.1148 38.3762 50.383 38.3762 45.7808ZM33.8683 45.7808C33.8683 52.8708 39.619 58.618 46.7132 58.618C53.8075 58.618 59.5581 52.8708 59.5581 45.7808C59.5581 38.6908 53.8075 32.9436 46.7132 32.9436C39.619 32.9436 33.8683 38.6908 33.8683 45.7808ZM36.4001 20.9322C33.7371 21.0534 31.9174 21.4754 30.3282 22.0934C28.6824 22.7316 27.2892 23.5878 25.897 24.977C24.5047 26.3662 23.6502 27.7608 23.0116 29.4056C22.3933 30.9948 21.971 32.8124 21.8497 35.4738C21.7265 38.1394 21.6982 38.9916 21.6982 45.7808C21.6982 52.57 21.7265 53.4222 21.8497 56.0878C21.971 58.7494 22.3933 60.5668 23.0116 62.156C23.6502 63.7998 24.5049 65.196 25.897 66.5846C27.289 67.9732 28.6824 68.8282 30.3282 69.4682C31.9204 70.0862 33.7371 70.5082 36.4001 70.6294C39.0687 70.7506 39.92 70.7808 46.7132 70.7808C53.5065 70.7808 54.3592 70.7526 57.0264 70.6294C59.6896 70.5082 61.5081 70.0862 63.0983 69.4682C64.7431 68.8282 66.1373 67.9738 67.5295 66.5846C68.9218 65.1954 69.7745 63.7998 70.4149 62.156C71.0332 60.5668 71.4575 58.7492 71.5768 56.0878C71.698 53.4202 71.7262 52.57 71.7262 45.7808C71.7262 38.9916 71.698 38.1394 71.5768 35.4738C71.4555 32.8122 71.0332 30.9938 70.4149 29.4056C69.7745 27.7618 68.9196 26.3684 67.5295 24.977C66.1395 23.5856 64.7431 22.7316 63.1003 22.0934C61.5081 21.4754 59.6894 21.0514 57.0284 20.9322C54.3612 20.811 53.5085 20.7808 46.7152 20.7808C39.922 20.7808 39.0687 20.809 36.4001 20.9322Z" fill="white"/><defs><linearGradient id="ig_fe2" x1="90.9407" y1="91.5618" x2="-0.621143" y2="0" gradientUnits="userSpaceOnUse"><stop stop-color="#FBE18A"/><stop offset="0.21" stop-color="#FCBB45"/><stop offset="0.38" stop-color="#F75274"/><stop offset="0.52" stop-color="#D53692"/><stop offset="0.74" stop-color="#8F39CE"/><stop offset="1" stop-color="#5B4FE9"/></linearGradient></defs></svg></span>
                            Instagram
                        </a>
                        <?php endif; ?>

                        <?php /* ── X / TWITTER ── */ if ( $x_twitter ): ?>
                        <a href="<?php echo esc_url($x_twitter); ?>" target="_blank" rel="noopener noreferrer"
                           class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-gray-100 hover:bg-black text-gray-800 hover:text-white transition-all duration-300 text-xs font-medium">
                            <span style="display:inline-block;line-height:0;border-radius:50%;overflow:hidden;flex-shrink:0;"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 93 92" fill="none"><rect x="0.138672" width="91.5618" height="91.5618" rx="15" fill="black"/><path d="M50.7568 42.1716L69.3704 21H64.9596L48.7974 39.383L35.8887 21H21L40.5205 48.7983L21 71H25.4111L42.4788 51.5869L56.1113 71H71L50.7557 42.1716H50.7568ZM44.7152 49.0433L42.7374 46.2752L27.0005 24.2492H33.7756L46.4755 42.0249L48.4533 44.7929L64.9617 67.8986H58.1865L44.7152 49.0443V49.0433Z" fill="white"/></svg></span>
                            X / Twitter
                        </a>
                        <?php endif; ?>

                        <?php /* ── TIKTOK ── */ if ( $tiktok ): ?>
                        <a href="<?php echo esc_url($tiktok); ?>" target="_blank" rel="noopener noreferrer"
                           class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-gray-100 hover:bg-[#010101] text-gray-800 hover:text-white transition-all duration-300 text-xs font-medium">
                            <span style="display:inline-block;line-height:0;border-radius:50%;overflow:hidden;flex-shrink:0;"><svg width="28" height="28" viewBox="0 0 92 92" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="92" height="92" rx="15" fill="#010101"/><path d="M63.3 28.5c-2.8-1.9-4.8-4.9-5.5-8.4H52v34.7c0 3.5-2.8 6.3-6.3 6.3-3.5 0-6.3-2.8-6.3-6.3 0-3.5 2.8-6.3 6.3-6.3.6 0 1.2.1 1.8.2V43c-.6-.1-1.2-.1-1.8-.1-7 0-12.7 5.7-12.7 12.7 0 7 5.7 12.7 12.7 12.7 7 0 12.7-5.7 12.7-12.7V36.5c2.8 1.8 6 2.9 9.6 2.9v-6c-2.1 0-4.1-.6-4.7-4.9z" fill="white"/></svg></span>
                            TikTok
                        </a>
                        <?php endif; ?>

                        <?php /* ── LINKEDIN ── */ if ( $linkedin ): ?>
                        <a href="<?php echo esc_url($linkedin); ?>" target="_blank" rel="noopener noreferrer"
                           class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-[#006699]/10 hover:bg-[#006699] text-[#006699] hover:text-white transition-all duration-300 text-xs font-medium">
                            <span style="display:inline-block;line-height:0;border-radius:50%;overflow:hidden;flex-shrink:0;"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 93 93" fill="none"><rect x="1.13867" y="1" width="91.5618" height="91.5618" rx="15" fill="#006699"/><path d="M37.1339 63.4304V40.9068H29.6473V63.4304H37.1346H37.1339ZM33.3922 37.8321C36.0023 37.8321 37.6273 36.1025 37.6273 33.9411C37.5785 31.7304 36.0023 30.0491 33.4418 30.0491C30.8795 30.0491 29.2061 31.7304 29.2061 33.9409C29.2061 36.1023 30.8305 37.8319 33.3431 37.8319H33.3916L33.3922 37.8321ZM41.2777 63.4304H48.7637V50.8535C48.7637 50.1813 48.8125 49.5072 49.0103 49.0271C49.5513 47.6815 50.7831 46.2887 52.8517 46.2887C55.5599 46.2887 56.644 48.354 56.644 51.3822V63.4304H64.1297V50.516C64.1297 43.598 60.4369 40.3787 55.5115 40.3787C51.4733 40.3787 49.6998 42.6357 48.7144 44.173H48.7643V40.9075H41.2781C41.3759 43.0205 41.2775 63.4312 41.2775 63.4312L41.2777 63.4304Z" fill="white"/></svg></span>
                            LinkedIn
                        </a>
                        <?php endif; ?>

                        <?php /* ── YOUTUBE ── */ if ( $youtube ): ?>
                        <a href="<?php echo esc_url($youtube); ?>" target="_blank" rel="noopener noreferrer"
                           class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-red-50 hover:bg-[#FF0000] text-[#FF0000] hover:text-white transition-all duration-300 text-xs font-medium">
                            <span style="display:inline-block;line-height:0;border-radius:50%;overflow:hidden;flex-shrink:0;"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 93 93" fill="none"><rect x="1.13867" y="1" width="91.5618" height="91.5618" rx="15" fill="#FF0000"/><path fill-rule="evenodd" clip-rule="evenodd" d="M67.5615 29.2428C69.8115 29.8504 71.58 31.6234 72.1778 33.8708C73.2654 37.9495 73.2654 46.4647 73.2654 46.4647C73.2654 46.4647 73.2654 54.98 72.1778 59.0586C71.5717 61.3144 69.8032 63.0873 67.5615 63.6866C63.4932 64.7771 47.1703 64.7771 47.1703 64.7771C47.1703 64.7771 30.8557 64.7771 26.7791 63.6866C24.5291 63.079 22.7606 61.306 22.1628 59.0586C21.0752 54.98 21.0752 46.4647 21.0752 46.4647C21.0752 46.4647 21.0752 37.9495 22.1628 33.8708C22.7689 31.615 24.5374 29.8421 26.7791 29.2428C30.8557 28.1523 47.1703 28.1523 47.1703 28.1523C47.1703 28.1523 63.4932 28.1523 67.5615 29.2428ZM55.5142 46.4647L41.9561 54.314V38.6154L55.5142 46.4647Z" fill="white"/></svg></span>
                            YouTube
                        </a>
                        <?php endif; ?>

                        <?php /* ── PINTEREST ── */ if ( ! empty($pinterest) ): ?>
                        <a href="<?php echo esc_url($pinterest); ?>" target="_blank" rel="noopener noreferrer"
                           class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-red-50 hover:bg-[#E60023] text-[#E60023] hover:text-white transition-all duration-300 text-xs font-medium">
                            <span style="display:inline-block;line-height:0;border-radius:50%;overflow:hidden;flex-shrink:0;"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 93 92" fill="none"><rect x="1.13867" width="91.5618" height="91.5618" rx="15" fill="#FF0000"/><path d="M44.2808 23.0437C36.8492 23.893 29.4439 30.0479 29.1382 38.84C28.9461 44.2083 30.435 48.2356 35.4258 49.3664C37.5915 45.4553 34.7272 44.5927 34.2818 41.7633C32.4523 30.1686 47.346 22.2615 55.14 30.3563C60.5324 35.9615 56.9826 53.206 48.2848 51.4136C39.9537 49.7017 52.3629 35.9749 45.713 33.2796C40.3074 31.0894 37.4343 39.9798 39.9974 44.396C38.4953 51.9902 35.2599 59.1464 36.5698 68.6715C40.8183 65.5158 42.2504 59.4727 43.425 53.1702C45.5601 54.4978 46.6998 55.8789 49.4244 56.0935C59.4714 56.8891 65.0822 45.8263 63.7112 35.6218C62.4929 26.5749 53.6729 21.971 44.2808 23.0437Z" fill="white"/></svg></span>
                            Pinterest
                        </a>
                        <?php endif; ?>

                        <?php /* ── MESSENGER ── */ if ( ! empty($messenger) ): ?>
                        <a href="<?php echo esc_url($messenger); ?>" target="_blank" rel="noopener noreferrer"
                           class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-purple-50 hover:bg-[#0099FF] text-[#0099FF] hover:text-white transition-all duration-300 text-xs font-medium">
                            <span style="display:inline-block;line-height:0;border-radius:50%;overflow:hidden;flex-shrink:0;"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 93 93" fill="none"><rect x="0.138672" y="1" width="91.5618" height="91.5618" rx="15" fill="url(#msg_fe2)"/><path fill-rule="evenodd" clip-rule="evenodd" d="M46.4114 21C32.0561 21 20.9307 31.317 20.9307 45.2508C20.9307 52.5396 23.9761 58.8375 28.9338 63.1887C29.3491 63.5559 29.6003 64.0639 29.6208 64.6122L29.7592 69.059C29.8054 70.4775 31.2973 71.398 32.62 70.8296L37.6752 68.6414C38.1058 68.4553 38.5826 68.4201 39.0338 68.5408C41.3563 69.1696 43.8326 69.5016 46.4114 69.5016C60.7668 69.5016 71.8922 59.1846 71.8922 45.2508C71.8922 31.317 60.7668 21 46.4114 21ZM61.7102 39.6572L54.2249 51.3072C53.0354 53.1584 50.4822 53.6211 48.698 52.3082L42.7457 47.9269C42.1971 47.5245 41.4486 47.5295 40.9051 47.9319L32.8661 53.9179C31.7946 54.7177 30.3898 53.4551 31.1127 52.3384L38.598 40.6884C39.7875 38.8372 42.3407 38.3745 44.1248 39.6874L50.0772 44.0687C50.6258 44.4711 51.3743 44.4661 51.9177 44.0637L59.9567 38.0777C61.0283 37.2779 62.433 38.5405 61.7102 39.6572Z" fill="white"/><defs><radialGradient id="msg_fe2" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(15.4753 92.5593) scale(100.718 100.715)"><stop stop-color="#0099FF"/><stop offset="0.6" stop-color="#A033FF"/><stop offset="0.9" stop-color="#FF5280"/><stop offset="1" stop-color="#FF7061"/></radialGradient></defs></svg></span>
                            Messenger
                        </a>
                        <?php endif; ?>

                        <?php /* ── TELEGRAM ── */ if ( ! empty($telegram) ): ?>
                        <a href="<?php echo esc_url($telegram); ?>" target="_blank" rel="noopener noreferrer"
                           class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-sky-50 hover:bg-[#34AADF] text-[#34AADF] hover:text-white transition-all duration-300 text-xs font-medium">
                            <span style="display:inline-block;line-height:0;border-radius:50%;overflow:hidden;flex-shrink:0;"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 92 93" fill="none"><rect x="0.138672" y="1" width="91.5618" height="91.5618" rx="15" fill="#34AADF"/><path d="M25.0881 43.5652C25.0881 43.5652 43.716 35.7194 50.1765 32.9567C52.6532 31.8518 61.0518 28.3155 61.0518 28.3155C61.0518 28.3155 64.9282 26.7685 64.6052 30.5256C64.4974 32.0728 63.6361 37.4874 62.7747 43.3442C61.4825 51.6322 60.0827 60.6935 60.0827 60.6935C60.0827 60.6935 59.8674 63.2352 58.0369 63.6772C56.2065 64.1192 53.1914 62.1302 52.6532 61.6881C52.2223 61.3566 44.5774 56.3838 41.7778 53.9527C41.0241 53.2897 40.1627 51.9637 41.8854 50.4166C45.7618 46.7699 50.3919 42.2392 53.1914 39.3661C54.4836 38.04 55.7757 34.9459 50.3919 38.703C42.7469 44.1178 35.2096 49.201 35.2096 49.201C35.2096 49.201 33.4868 50.306 30.2565 49.3115C27.0261 48.317 23.2575 46.9909 23.2575 46.9909C23.2575 46.9909 20.6734 45.3334 25.0881 43.5652Z" fill="white"/></svg></span>
                            Telegram
                        </a>
                        <?php endif; ?>

                        <?php /* ── WHATSAPP ── */ if ( $whatsapp ): ?>
                        <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $whatsapp)); ?>" target="_blank" rel="noopener noreferrer"
                           class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-green-50 hover:bg-[#00D95F] text-[#00a349] hover:text-white transition-all duration-300 text-xs font-medium">
                            <span style="display:inline-block;line-height:0;border-radius:50%;overflow:hidden;flex-shrink:0;"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 93 92" fill="none"><rect x="1.13867" width="91.5618" height="91.5618" rx="15" fill="#00D95F"/><path d="M23.5068 66.8405L26.7915 54.6381C24.1425 49.8847 23.3009 44.3378 24.4211 39.0154C25.5413 33.693 28.5482 28.952 32.89 25.6624C37.2319 22.3729 42.6173 20.7554 48.0583 21.1068C53.4992 21.4582 58.6306 23.755 62.5108 27.5756C66.3911 31.3962 68.7599 36.4844 69.1826 41.9065C69.6053 47.3286 68.0535 52.7208 64.812 57.0938C61.5705 61.4668 56.8568 64.5271 51.5357 65.7133C46.2146 66.8994 40.6432 66.1318 35.8438 63.5513L23.5068 66.8405ZM36.4386 58.985L37.2016 59.4365C40.6779 61.4918 44.7382 62.3423 48.7498 61.8555C52.7613 61.3687 56.4987 59.5719 59.3796 56.7452C62.2605 53.9185 64.123 50.2206 64.6769 46.2279C65.2308 42.2351 64.445 38.1717 62.4419 34.6709C60.4388 31.1701 57.331 28.4285 53.6027 26.8734C49.8745 25.3184 45.7352 25.0372 41.8299 26.0736C37.9247 27.11 34.4729 29.4059 32.0124 32.6035C29.5519 35.801 28.2209 39.7206 28.2269 43.7514C28.2237 47.0937 29.1503 50.3712 30.9038 53.2192L31.3823 54.0061L29.546 60.8167L36.4386 58.985Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M54.9566 46.8847C54.5093 46.5249 53.9856 46.2716 53.4254 46.1442C52.8651 46.0168 52.2831 46.0186 51.7236 46.1495C50.8831 46.4977 50.3399 47.8134 49.7968 48.4713C49.6823 48.629 49.514 48.7396 49.3235 48.7823C49.133 48.8251 48.9335 48.797 48.7623 48.7034C45.6849 47.5012 43.1055 45.2965 41.4429 42.4475C41.3011 42.2697 41.2339 42.044 41.2557 41.8178C41.2774 41.5916 41.3862 41.3827 41.5593 41.235C42.165 40.6368 42.6098 39.8959 42.8524 39.0809C42.9063 38.1818 42.6998 37.2863 42.2576 36.5011C41.9157 35.4002 41.265 34.42 40.3825 33.6762C39.9273 33.472 39.4225 33.4036 38.9292 33.4791C38.4359 33.5546 37.975 33.7709 37.6021 34.1019C36.9548 34.6589 36.4411 35.3537 36.0987 36.135C35.7562 36.9163 35.5939 37.7643 35.6236 38.6165C35.6256 39.0951 35.6864 39.5716 35.8046 40.0354C36.1049 41.1497 36.5667 42.2144 37.1754 43.1956C37.6145 43.9473 38.0937 44.6749 38.6108 45.3755C40.2914 47.6767 42.4038 49.6305 44.831 51.1284C46.049 51.8897 47.3507 52.5086 48.7105 52.973C50.1231 53.6117 51.6827 53.8568 53.2237 53.6824C54.1018 53.5499 54.9337 53.2041 55.6462 52.6755C56.3588 52.1469 56.9302 51.4518 57.3102 50.6512C57.5334 50.1675 57.6012 49.6269 57.5042 49.1033C57.2714 48.0327 55.836 47.4007 54.9566 46.8847Z" fill="white"/></svg></span>
                            WhatsApp
                        </a>
                        <?php endif; ?>

                        <?php /* ── ZALO ── */ if ( $zalo ):
                            $zalo_href = filter_var($zalo, FILTER_VALIDATE_URL) ? $zalo : 'https://zalo.me/' . preg_replace('/[^0-9]/', '', $zalo);
                        ?>
                        <a href="<?php echo esc_url($zalo_href); ?>" target="_blank" rel="noopener noreferrer"
                           class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-blue-50 hover:bg-[#0068FF] text-[#0068FF] hover:text-white transition-all duration-300 text-xs font-medium">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/91/Icon_of_Zalo.svg/250px-Icon_of_Zalo.svg.png" alt="Zalo" width="28" height="28" style="border-radius:50%;object-fit:contain;flex-shrink:0;">
                            Zalo
                        </a>
                        <?php endif; ?>

                        <?php /* ── VIBER ── */ if ( ! empty($viber) ): ?>
                        <a href="viber://chat?number=<?php echo esc_attr(preg_replace('/[^0-9]/', '', $viber)); ?>"
                           class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-purple-50 hover:bg-[#754A91] text-[#754A91] hover:text-white transition-all duration-300 text-xs font-medium">
                            <span style="display:inline-block;line-height:0;border-radius:50%;overflow:hidden;flex-shrink:0;"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 92 93" fill="none"><rect x="0.138672" y="1" width="91.5618" height="91.5618" rx="15" fill="#754A91"/><path d="M35.396 64.818C35.396 63.2844 35.396 61.7508 35.396 60.2172C33.2693 59.6773 31.4973 58.7154 30.0052 57.4032C28.918 56.4241 28.0128 55.2638 27.3308 53.975C26.3695 52.1133 25.7297 50.1055 25.438 48.0347C25.0766 45.4372 24.9511 42.8127 25.063 40.193C25.5144 34.2218 26.1059 31.9501 27.0434 29.8113C28.201 27.1815 30.3012 25.0692 32.9389 23.8814C34.7357 23.0674 36.626 22.4732 38.568 22.1118C40.4725 21.7547 42.4007 21.5355 44.3374 21.456C49.574 21.5324 52.0688 21.6366 54.5411 24.0754C56.9346 22.7503 58.7092 23.2567 61.9434 25.023C63.4496 26.0466 64.6679 27.433 65.4835 29.0514C66.4423 30.9417 67.094 32.9699 67.4148 35.0611C67.8249 38.812 67.916 40.3699 67.8459 43.4858C67.7863 45.0368 67.6531 46.5427 67.4603 48.0625C67.2167 50.3435 66.5084 52.552 65.3783 54.5545C63.9074 57.0877 61.5511 59.0006 58.7537 59.9327C56.841 60.5958 54.8659 61.0677 52.8582 61.3414C48.4383 61.7474 47.3062 61.7995 45.0419 61.7682C42.7952 61.619 42.2235 61.9686 41.5053 63.5023C40.1664 65.0 38.8169 66.4851 38.2452 67.0759C37.0099 67.4376 36.2523 67.4974 35.4946 67.5573C34.748 67.3107 34.1451 66.803 33.5421 66.2952C33.0414 65.5608 33.0005 64.7468 33.4801 62.4129L35.396 59.5478V64.818Z" fill="white"/><path d="M34.5029 36.7373C34.8955 35.4257 35.4002 34.9295 36.0659 34.2982C36.7993 33.7409 37.5874 33.2675 38.7906 33.0321C39.8551 33.6353 40.8086 34.6275 42.4033 36.8657C43.2938 38.8109 43.2368 39.3037 42.873 39.9399C42.4686 40.3328 42.0377 40.698 41.5831 41.0329C41.0031 41.5003 40.9117 42.1564 41.0749 42.5839C41.9917 45.1072 42.9151 46.0884 47.1211 49.1661C47.8285 49.4885 48.6124 49.3431 49.0454 48.9441C49.5572 48.3368 49.778 48.0072 51.9406 47.4902C54.7377 48.9625 55.7787 49.8497 57.0615 52.4C56.1292 53.9406 55.6319 54.6247 54.268 55.6338C53.5376 56.0667 51.9406 55.783 49.1048 54.6528C44.0787 51.2064 39.7234 47.0911 38.05 44.6137C36.642 42.5629 35.5218 40.3322 34.7202 37.9829C34.5647 37.4104 34.4784 36.8644 34.5029 36.7373Z" fill="#754A91"/></svg></span>
                            Viber
                        </a>
                        <?php endif; ?>

                        <?php /* ── SKYPE ── */ if ( $skype ): ?>
                        <a href="skype:<?php echo esc_attr($skype); ?>?chat"
                           class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-sky-50 hover:bg-[#00B7F0] text-[#00B7F0] hover:text-white transition-all duration-300 text-xs font-medium">
                            <span style="display:inline-block;line-height:0;border-radius:50%;overflow:hidden;flex-shrink:0;"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 92 92" fill="none"><rect x="0.138672" width="91.5618" height="91.5618" rx="15" fill="#00B7F0"/><path fill-rule="evenodd" clip-rule="evenodd" d="M37.0659 43.0286C37.6308 43.9944 38.4114 44.8169 39.3468 45.4319C40.5861 46.2419 41.9036 46.9259 43.2795 47.4736C44.8179 48.1158 46.0114 48.6602 46.8601 49.1069C47.5778 49.4595 48.2292 49.9331 48.7856 50.5069C49.4397 52.1569 49.4578 52.5894 48.3952 54.4169C47.4835 55.0297 46.3928 55.3201 45.2967 55.2419C43.453 55.0519 42.0181 54.5986 40.3546 53.8086C39.1599 53.3456 38.5192 53.3369 36.9491 53.8902C36.3401 55.4236 36.49 56.4915 36.8039 56.9302C37.1821 57.409 37.6711 57.7888 38.2289 58.0369C40.4105 58.9814 42.7753 59.4283 45.1516 59.3452C48.6018 59.0998 50.2305 58.5186 53.6093 56.0136C54.8679 53.3425 54.8173 51.8669 53.9447 48.4269C52.5071 46.5875 51.5286 45.9502 47.6043 43.9352C45.7455 43.1736 43.9803 42.3569 42.29 41.1486C41.6627 39.5602 41.9574 38.266 42.8707 37.3019C43.7839 36.7581 44.8349 36.4895 45.8974 36.5286C48.3134 36.8286 50.8212 37.7452 51.874 37.9819C53.2906 37.3919 53.7271 36.6721 53.8546 35.8402C53.319 34.3519 51.5837 33.2452 49.3662 32.6736C47.24 32.4185 45.8974 36.5286 41.364 33.2369C38.6787 34.5607 36.285 39.7069 37.0659 43.0286Z" fill="white"/></svg></span>
                            Skype: <?php echo esc_html($skype); ?>
                        </a>
                        <?php endif; ?>

                        <?php /* ── WECHAT ── */ if ( $wechat ): ?>
                        <span class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-green-50 text-[#07C160] text-xs font-medium cursor-help" title="WeChat ID: <?php echo esc_attr($wechat); ?>">
                            <span style="display:inline-block;line-height:0;border-radius:50%;overflow:hidden;flex-shrink:0;"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 93 93" fill="none"><rect x="1.13867" y="1" width="91.5618" height="91.5618" rx="15" fill="#51C332"/><path d="M55.8615 36.5403C57.0463 29.1747 49.1004 23.4524 39.5457 23.4524C28.7309 23.4524 19.9658 30.7781 19.9658 39.8123C19.9658 45.021 22.8964 49.6421 27.4419 52.6322L24.8606 57.8086L31.8926 54.7884C33.4005 55.3254 34.9674 55.7676 36.6411 55.9734C36.2824 43.8797 45.0634 36.5403 55.8615 36.5403ZM46.0722 30.8139C47.4235 30.8139 48.5194 31.9132 48.5194 33.2682C48.5194 34.6237 47.4236 35.7222 46.0722 35.7222C44.7201 35.7222 43.6247 34.6237 43.6247 33.2682C43.6247 31.9131 44.7201 30.8139 46.0722 30.8139ZM33.0189 35.7222C31.6674 35.7222 30.5715 34.6237 30.5715 33.2682C30.5715 31.9132 31.6675 30.8139 33.0189 30.8139C34.3703 30.8139 35.4664 31.9132 35.4664 33.2682C35.4663 34.6237 34.3702 35.7222 33.0189 35.7222Z" fill="white"/><path d="M72.1779 52.9008C72.1779 45.6724 64.8709 39.8123 55.8615 39.8123C46.8517 39.8123 39.5457 45.6724 39.5457 52.9008C39.5457 60.1287 46.8517 65.9889 55.8615 65.9889C57.3432 65.9889 58.7525 65.7794 60.12 65.4821L68.9148 69.2608L65.8731 63.1654C69.6849 60.7698 72.1779 57.0859 72.1779 52.9008ZM50.9668 52.0827C49.6154 52.0827 48.5193 50.9838 48.5193 49.6281C48.5193 48.2731 49.6153 47.1746 50.9668 47.1746C52.3186 47.1746 53.4141 48.2736 53.4141 49.6281C53.4141 50.9839 52.3184 52.0827 50.9668 52.0827ZM60.7564 52.0827C59.4043 52.0827 58.3091 50.9838 58.3091 49.6281C58.3091 48.2731 59.4042 47.1746 60.7564 47.1746C62.1083 47.1746 63.2039 48.2736 63.2039 49.6281C63.2039 50.9839 62.1083 52.0827 60.7564 52.0827Z" fill="white"/></svg></span>
                            WeChat: <?php echo esc_html($wechat); ?>
                        </span>
                        <?php endif; ?>

                        <?php /* ── LINE ── */ if ( ! empty($line_app) ): ?>
                        <span class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-green-50 text-[#06C755] text-xs font-medium cursor-help" title="Line ID: <?php echo esc_attr($line_app); ?>">
                            <span style="display:inline-block;line-height:0;border-radius:50%;overflow:hidden;flex-shrink:0;"><svg width="28" height="28" viewBox="0 0 93 92" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="93" height="92" rx="15" fill="#06C755"/><path d="M78 40.2C78 25.5 63.2 13.5 46.3 13.5 29.4 13.5 14.6 25.5 14.6 40.2c0 13.2 11.7 24.3 27.6 26.4 1.1.2 2.5.7 2.9 1.7.4 1 .3 2.4 0 3.3l-.5 2.7c-.1 1-.9 3.8 1.5 2.7 2.4-1.1 13.3-7.8 18.2-13.4C69.1 59 78 50.2 78 40.2zM35.8 48.2H29v-14h3.1v11h3.7v3zm4.6 0h-3.1V34.2h3.1v14zm14 0h-3l-5.3-9.4v9.4H43V34.2h3l5.3 9.3v-9.3h3.1v14zm12.6-11h-6.2v2.5h6.2v3.1h-6.2v2.5h6.2v3H57.7V34.2H67v3z" fill="white"/></svg></span>
                            LINE: <?php echo esc_html($line_app); ?>
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

    <section class="bacera-container pb-6">
        <!-- Header -->
        <div class="text-center mb-2">
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
    <section class="bacera-container pb-24">
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
    <div class="bacera-container pb-20">
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
