<?php
/**
 * Footer
 */
?>
    </main>

    <footer id="colophon" class="site-footer bg-textmain text-bgtheme font-sans pb-20 lg:pb-0">
        <div class="bacera-container pt-16 pb-12">
            
            <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-12 gap-10 lg:gap-12 mb-16">
                
                <div class="col-span-1 md:col-span-2 lg:col-span-3">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="inline-block hover:opacity-80 transition-opacity mb-6">

                        <div class="w-32 h-14 relative overflow-hidden flex items-center">
                            <?php
                            $ft_dark_id  = (int)get_option('bacera_logo_dark_id', 0);
                            $ft_logo_src = $ft_dark_id
                                ? wp_get_attachment_image_url($ft_dark_id, 'full')
                                : bacera_get_brand_logo_url();
                            $ft_style    = $ft_dark_id ? '' : 'style="filter:brightness(0)invert(1)"';
                            ?>
                            <img src="<?php echo esc_url($ft_logo_src); ?>" <?php echo $ft_style; ?>
                                 class="w-full h-full object-contain" alt="<?php echo esc_attr(get_bloginfo('name')); ?> Footer Logo">
                        </div>
                    </a>
                </div>

                <div class="col-span-1 md:col-span-2 lg:col-span-3 flex flex-col gap-2.5">
                    <h3 class="text-xs font-medium leading-4 tracking-tight text-white/50 mb-1 uppercase">Sitemap</h3>
                    <a href="<?php echo esc_url(home_url('/shop/')); ?>" class="text-[15px] font-normal leading-5 text-white/75 hover:text-white hover:translate-x-1 transition-all no-underline">Shop</a>
                    <a href="<?php echo esc_url(home_url('/our-team/')); ?>" class="text-[15px] font-normal leading-5 text-white/75 hover:text-white hover:translate-x-1 transition-all no-underline">Our maker</a>
                    <a href="<?php echo esc_url(home_url('/blog/')); ?>" class="text-[15px] font-normal leading-5 text-white/75 hover:text-white hover:translate-x-1 transition-all no-underline">Journal</a>
                    <a href="<?php echo esc_url(home_url('/sustainability/')); ?>" class="text-[15px] font-normal leading-5 text-white/75 hover:text-white hover:translate-x-1 transition-all no-underline">Our sustainability</a>
                    <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="text-[15px] font-normal leading-5 text-white/75 hover:text-white hover:translate-x-1 transition-all no-underline">Contact us</a>
                </div>

                <div class="col-span-1 md:col-span-2 lg:col-span-3 flex flex-col gap-2.5">
                    <h3 class="text-xs font-medium leading-4 tracking-tight text-white/50 mb-1 uppercase">Workshop</h3>
                    <?php 
                    $footer_workshops = get_posts(['post_type'=>'workshop','post_status'=>'publish','posts_per_page'=>4,'orderby'=>'date','order'=>'DESC']);
                    if ($footer_workshops): foreach($footer_workshops as $fw): ?>
                    <a href="<?php echo esc_url(get_permalink($fw)); ?>" class="text-[15px] font-normal leading-5 text-white/75 hover:text-white hover:translate-x-1 transition-all no-underline"><?php echo esc_html($fw->post_title); ?></a>
                    <?php endforeach; else: ?>
                    <a href="<?php echo esc_url(get_post_type_archive_link('workshop') ?: home_url('/workshop/')); ?>" class="text-[15px] font-normal text-white/75 hover:text-white transition-all no-underline">Xem tất cả workshop</a>
                    <?php endif; ?>
                </div>

                <div class="col-span-1 md:col-span-2 lg:col-span-3 flex flex-col gap-6">
                    <div class="flex flex-col gap-1.5">
                        <h3 class="text-xs font-medium leading-4 tracking-tight text-white/50 uppercase">Address</h3>
                        <p class="text-[15px] font-normal leading-snug text-white/75 notranslate">No. 22, Alley 29, Lane 218,<br>Lac Long Quan Street, Buoi Ward,<br>Tay Ho District, Hanoi</p>
                    </div>
                    
                    <div class="flex flex-col gap-1.5">
                        <h3 class="text-xs font-medium leading-4 tracking-tight text-white/50 uppercase">Tel (+84)</h3>
                        <p class="text-[15px] font-normal leading-5 text-white/75 notranslate">0912 262 119<br>0912 262 119</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <h3 class="text-xs font-medium leading-4 tracking-tight text-white/50 uppercase">Email</h3>
                        <a href="mailto:sales@bacera.vn" class="text-[15px] font-normal leading-4 text-white/75 hover:text-white transition-colors notranslate no-underline">sales@bacera.vn</a>
                    </div>
                    
                    <div class="flex flex-col gap-2">
                        <h3 class="text-xs font-medium leading-4 tracking-tight text-white/50 uppercase">Follow us</h3>
                        <div class="flex items-center gap-2.5">
                            <a href="#" aria-label="Facebook" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                            </a>
                            <a href="#" aria-label="Instagram" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            </a>
                            <a href="#" aria-label="TikTok" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.27 6.27 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.09 8.09 0 004.74 1.52V6.76a4.85 4.85 0 01-.97-.07z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="h-px bg-neutral-200 opacity-20 w-full mb-8"></div>

            <div class="flex flex-col md:flex-row justify-between items-center gap-6 pb-4">
                <div class="text-[13px] text-white/40">
                    <span class="notranslate">Copyright &copy; <?php echo date('Y'); ?> Bacera Pottery class</span>
                </div>
                
                <div class="flex flex-wrap items-center justify-center md:justify-end gap-6">

                    <div class="flex items-center gap-2 cursor-pointer group">
                        <span class="text-[13px] text-white/40 group-hover:text-white/70 transition-colors notranslate">Vietnam (VND ₫)</span>
                        <svg class="w-3 h-3 text-white/30 group-hover:text-white/60 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </div>

                    <div class="flex gap-1.5 opacity-90 grayscale hover:grayscale-0 transition-all duration-300">
                        <div class="w-8 h-5 rounded overflow-hidden flex bg-white justify-center items-center shadow-sm">

                            <div class="text-[8px] font-bold text-blue-900 italic">VISA</div>
                        </div>
                        <div class="w-8 h-5 rounded overflow-hidden flex bg-white justify-center items-center shadow-sm">

                            <div class="flex">
                                <div class="w-2.5 h-2.5 rounded-full bg-red-600 mix-blend-multiply opacity-90 -mr-1"></div>
                                <div class="w-2.5 h-2.5 rounded-full bg-amber-500 mix-blend-multiply opacity-90"></div>
                            </div>
                        </div>
                        <div class="w-8 h-5 rounded overflow-hidden flex bg-white justify-center items-center shadow-sm">

                            <div class="text-[8px] font-bold text-sky-700 italic">Pay</div>
                        </div>
                        <div class="w-8 h-5 rounded bg-black flex justify-center items-center shadow-sm">

                            <svg class="w-3 h-3 text-white" viewBox="0 0 384 512" fill="currentColor"><path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"/></svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</div>

<?php 
// Include the mobile app-like dock
get_template_part('app/Views/components/mobile-dock'); 
?>

<?php wp_footer(); ?>
</body>
</html>
