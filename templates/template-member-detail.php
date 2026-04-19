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
                        <a href="<?php echo esc_url($facebook); ?>" target="_blank" rel="noopener noreferrer" title="Facebook" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            
                        </a>
                        <?php endif; ?>

                        <?php /* ── INSTAGRAM ── */ if ( $instagram ): ?>
                        <a href="<?php echo esc_url($instagram); ?>" target="_blank" rel="noopener noreferrer" title="Instagram" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            
                        </a>
                        <?php endif; ?>

                        <?php /* ── X / TWITTER ── */ if ( $x_twitter ): ?>
                        <a href="<?php echo esc_url($x_twitter); ?>" target="_blank" rel="noopener noreferrer" title="X / Twitter" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            
                        </a>
                        <?php endif; ?>

                        <?php /* ── TIKTOK ── */ if ( $tiktok ): ?>
                        <a href="<?php echo esc_url($tiktok); ?>" target="_blank" rel="noopener noreferrer" title="TikTok" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            
                        </a>
                        <?php endif; ?>

                        <?php /* ── LINKEDIN ── */ if ( $linkedin ): ?>
                        <a href="<?php echo esc_url($linkedin); ?>" target="_blank" rel="noopener noreferrer" title="LinkedIn" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            
                        </a>
                        <?php endif; ?>

                        <?php /* ── YOUTUBE ── */ if ( $youtube ): ?>
                        <a href="<?php echo esc_url($youtube); ?>" target="_blank" rel="noopener noreferrer" title="YouTube" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            
                        </a>
                        <?php endif; ?>

                        <?php /* ── PINTEREST ── */ if ( ! empty($pinterest) ): ?>
                        <a href="<?php echo esc_url($pinterest); ?>" target="_blank" rel="noopener noreferrer" title="Pinterest" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            
                        </a>
                        <?php endif; ?>

                        <?php /* ── MESSENGER ── */ if ( ! empty($messenger) ): ?>
                        <a href="<?php echo esc_url($messenger); ?>" target="_blank" rel="noopener noreferrer" title="Messenger" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            
                        </a>
                        <?php endif; ?>

                        <?php /* ── TELEGRAM ── */ if ( ! empty($telegram) ): ?>
                        <a href="<?php echo esc_url($telegram); ?>" target="_blank" rel="noopener noreferrer" title="Telegram" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            
                        </a>
                        <?php endif; ?>

                        <?php /* ── WHATSAPP ── */ if ( $whatsapp ): ?>
                        <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $whatsapp)); ?>" target="_blank" rel="noopener noreferrer" title="WhatsApp: <?php echo esc_attr($whatsapp); ?>" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            
                        </a>
                        <?php endif; ?>

                        <?php /* ── ZALO ── */ if ( $zalo ):
                            $zalo_href = filter_var($zalo, FILTER_VALIDATE_URL) ? $zalo : 'https://zalo.me/' . preg_replace('/[^0-9]/', '', $zalo);
                        ?>
                        <a href="<?php echo esc_url($zalo_href); ?>" target="_blank" rel="noopener noreferrer" title="Zalo: <?php echo esc_attr($zalo); ?>" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            
                        </a>
                        <?php endif; ?>

                        <?php /* ── VIBER ── */ if ( ! empty($viber) ): ?>
                        <a href="viber://chat?number=<?php echo esc_attr(preg_replace('/[^0-9]/', '', $viber)); ?>" title="Viber: <?php echo esc_attr($viber); ?>" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            
                        </a>
                        <?php endif; ?>

                        <?php /* ── SKYPE ── */ if ( $skype ): ?>
                        <a href="skype:<?php echo esc_attr($skype); ?>?chat" title="Skype: <?php echo esc_attr($skype); ?>" class="transition-all duration-300 hover:-translate-y-1 hover:shadow-lg" style="border-radius:10px;display:inline-block;">
                            
                        </a>
                        <?php endif; ?>

                        <?php /* ── WECHAT ── */ if ( $wechat ): ?>
                        <span title="WeChat ID: <?php echo esc_attr($wechat); ?>" style="border-radius:10px;display:inline-block;cursor:help;" class="transition-all duration-300 hover:-translate-y-1">
                            
                        </span>
                        <?php endif; ?>

                        <?php /* ── LINE ── */ if ( ! empty($line_app) ): ?>
                        <span title="Line ID: <?php echo esc_attr($line_app); ?>" style="border-radius:10px;display:inline-block;cursor:help;" class="transition-all duration-300 hover:-translate-y-1">
                            
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
