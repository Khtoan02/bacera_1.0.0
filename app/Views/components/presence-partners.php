<?php
/**
 * Component: Bacera's Presence (Partners)
 */

$partners_query = new WP_Query([
    'post_type'      => 'bacera_partner',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'menu_order title',
    'order'          => 'ASC'
]);

$has_partners = $partners_query->have_posts();
?>
<div class="bacera-partners-wrapper relative w-full overflow-hidden rounded-[2rem] pb-8 pt-12 md:py-16">
    <!-- Ambient Ceramic Orbs - Bounded tightly to the wrapper -->
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-[10%] right-[-5%] w-[300px] h-[300px] md:w-[400px] md:h-[400px] bg-accent/30 blur-[80px] md:blur-[100px] rounded-full mix-blend-multiply"></div>
        <div class="absolute bottom-[-10%] left-[-5%] w-[400px] h-[400px] md:w-[500px] md:h-[500px] bg-terracotta/15 blur-[100px] md:blur-[120px] rounded-full mix-blend-multiply"></div>
    </div>

    <!-- Text Header -->
    <div class="relative z-10 text-center w-full px-4 mb-10 md:mb-16">
        <p class="text-[10px] md:text-sm uppercase tracking-[0.3em] text-accentdark mb-4 md:mb-6 font-medium">Bacera's Presence</p>
        <h2 class="font-serif text-2xl md:text-3xl lg:text-4xl text-textmain font-light leading-snug">
            Our creations accompany the most luxurious <br class="hidden sm:block">and culturally rich spaces.
        </h2>
    </div>
    
    <!-- Swiper Slider with Glass Cards -->
    <div class="relative z-10 swiper partner-swiper w-full !overflow-visible px-2 sm:px-4 py-8">
        <div class="swiper-wrapper items-stretch">
            <?php if ( $has_partners ) : ?>
                <?php 
                while ( $partners_query->have_posts() ) : $partners_query->the_post(); ?>
                    <div class="swiper-slide h-auto px-2 md:px-3">
                        <div class="h-full flex justify-center items-center py-6 px-6 backdrop-blur-xl bg-white/40 border border-white/60 shadow-[0_8px_30px_-5px_rgba(0,0,0,0.05)] rounded-[1.5rem] hover:-translate-y-3 lg:hover:-translate-y-4 hover:bg-white/70 hover:shadow-[0_20px_40px_-5px_rgba(0,0,0,0.12)] transition-all duration-500 relative overflow-hidden group">
                            <!-- Inner Glare -->
                            <div class="absolute inset-0 bg-gradient-to-br from-white/80 via-transparent to-white/10 pointer-events-none opacity-50"></div>
                            
                            <div class="relative z-10 w-full flex justify-center">
                            <?php
                            if ( has_post_thumbnail() ) {
                                $img_url = get_the_post_thumbnail_url( get_the_ID(), 'full' );
                                $alt = get_post_meta( get_post_thumbnail_id(), '_wp_attachment_image_alt', true ) ?: get_the_title();
                                echo '<img src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $alt ) . '" title="' . esc_attr( get_the_title() ) . '" class="h-16 md:h-20 lg:h-24 w-auto object-contain transition-transform duration-500 group-hover:scale-105">';
                            } else {
                                echo '<span class="font-serif text-2xl md:text-3xl lg:text-4xl tracking-[0.2em] text-textmain text-center transition-transform duration-500 inline-block group-hover:scale-105">' . esc_html( get_the_title() ) . '</span>';
                            }
                            ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile;
                wp_reset_postdata();
                ?>
            <?php else : ?>
                <!-- Fallback Demo Data -->
                <?php 
                $demos = [
                    '<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e0/Marriott_Logo.svg/1024px-Marriott_Logo.svg.png" alt="Marriott" class="h-16 md:h-20 lg:h-24 w-auto object-contain transition-transform duration-500 group-hover:scale-105">',
                    '<span class="font-serif text-2xl md:text-3xl lg:text-4xl tracking-[0.2em] text-textmain text-center transition-transform duration-500 inline-block group-hover:scale-105">HERITAGE</span>',
                    '<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/d/d4/Accor_logo.svg/1024px-Accor_logo.svg.png" alt="Accor" class="h-16 md:h-20 lg:h-24 w-auto object-contain transition-transform duration-500 group-hover:scale-105">',
                    '<span class="font-serif text-2xl md:text-3xl lg:text-4xl tracking-[0.2em] text-textmain text-center transition-transform duration-500 inline-block group-hover:scale-105">THE ARTIS</span>',
                    '<span class="font-serif text-2xl md:text-3xl lg:text-4xl tracking-[0.2em] text-textmain text-center transition-transform duration-500 inline-block group-hover:scale-105">L\'USINE</span>'
                ];
                foreach ($demos as $demo): ?>
                    <div class="swiper-slide h-auto px-2 md:px-3">
                        <div class="h-full flex justify-center items-center py-6 px-6 backdrop-blur-xl bg-white/40 border border-white/60 shadow-[0_8px_30px_-5px_rgba(0,0,0,0.05)] rounded-[1.5rem] hover:-translate-y-3 lg:hover:-translate-y-4 hover:bg-white/70 hover:shadow-[0_20px_40px_-5px_rgba(0,0,0,0.12)] transition-all duration-500 relative overflow-hidden group">
                            <!-- Inner Glare -->
                            <div class="absolute inset-0 bg-gradient-to-br from-white/80 via-transparent to-white/10 pointer-events-none opacity-50"></div>
                            <div class="relative z-10 w-full flex justify-center">
                                <?php echo $demo; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Swiper !== 'undefined') {
        new Swiper('.partner-swiper', {
            slidesPerView: 2,
            spaceBetween: 30,
            loop: true,
            grabCursor: true,
            autoplay: {
                delay: 2000,
                disableOnInteraction: false,
            },
            breakpoints: {
                640: { slidesPerView: 3, spaceBetween: 40 },
                1024: { slidesPerView: 4, spaceBetween: 60 }
            }
        });
    }
});
</script>
