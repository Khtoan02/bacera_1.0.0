<?php
/**
 * Workshop Card Component
 * @param string $image Thumnail URL
 * @param string $title Title of workshop
 * @param string $description Short description
 * @param string $pricing Price string e.g 'From $38.00/Person'
 * @param string $slotsInfo String slots info e.g 'Day:4/16 slot - Noon: 2/16slot'
 * @param string $class Wrapper class
 */
$image = isset($args['image']) ? $args['image'] : 'https://placehold.co/296x220/eae3d1/8d6a54?text=Workshop';
$title = isset($args['title']) ? $args['title'] : 'Pottery Wheel Throwing';
$description = isset($args['description']) ? $args['description'] : 'A peaceful, hands-on journey for beginners and curious minds.';
$pricing = isset($args['pricing']) ? $args['pricing'] : 'From $38.00/Person';
$slotsInfo = isset($args['slotsInfo']) ? $args['slotsInfo'] : 'Day:4/16 slot - Noon: 2/16slot';
$class = isset($args['class']) ? $args['class'] : '';
?>
<div class="w-full inline-flex flex-col justify-center items-start group cursor-pointer hover:shadow-[8px_4px_24px_0px_rgba(0,0,0,0.20)] hover:-translate-y-1 transition-all duration-300 rounded-lg <?php echo esc_attr($class); ?>">
    
    <!-- Image block Formatted strictly per Figma snippet -->
    <div class="self-stretch h-56 relative rounded-tl-lg rounded-tr-lg overflow-hidden flex flex-col justify-start items-center bg-neutral-100 w-full">
        <!-- Image itself -->
        <img 
            className="self-stretch flex-1"
            src="<?php echo esc_url($image); ?>" 
            alt="<?php echo esc_attr($title); ?>" 
            class="self-stretch flex-1 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
        >

        <!-- Add to cart Hover slide-up (Matching State 2 vs State 1 in Figma wrapper) -->
        <div class="w-full h-16 absolute bottom-0 left-0 overflow-hidden z-10 flex items-end">
            <button class="w-[calc(100%-24px)] p-4 absolute left-[12px] top-[60px] opacity-0 group-hover:top-0 group-hover:opacity-100 bg-accent-500 rounded-lg inline-flex justify-center items-center gap-2 transition-all duration-300">
                <div class="justify-start text-stone-200 text-base font-medium leading-5">Add to cart</div>
            </button>
        </div>
        
        <!-- Gradient Overlay underneath the button -->
        <div class="absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-gray-900/30 to-transparent pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    </div>

    <!-- Content Block Formatted strictly per Figma snippet -->
    <div class="self-stretch flex flex-col justify-start items-start gap-2 w-full">
        <div class="self-stretch p-4 bg-stone-200 rounded-b-lg md:rounded-lg outline outline-1 outline-offset-[-1px] outline-stone-300 flex flex-col justify-between items-start gap-6 h-[192px] group-hover:bg-stone-50 transition-colors w-full">
            
            <div class="self-stretch flex-1 flex flex-col justify-start items-start gap-4">
                <div class="self-stretch flex flex-col justify-start items-start gap-3">
                    <div class="self-stretch flex flex-col justify-start items-start gap-1">
                        <h3 class="self-stretch justify-start text-stone-700 text-base font-medium leading-5 group-hover:text-accent-500 transition-colors">
                            <?php echo esc_html($title); ?>
                        </h3>
                        <p class="self-stretch opacity-80 justify-start text-stone-700 text-base font-normal leading-4 line-clamp-2">
                            <?php echo esc_html($description); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="self-stretch inline-flex justify-start items-center gap-2">
                <div class="flex-1 inline-flex flex-col justify-center items-start">
                    <div class="self-stretch justify-start text-stone-700 text-base font-medium leading-4">
                        <?php echo esc_html($pricing); ?>
                    </div>
                    <div class="inline-flex justify-start items-start gap-2 mt-1">
                        <div class="rounded flex justify-center items-center gap-1">
                            <div class="justify-start text-stone-600 text-[11px] md:text-xs font-normal leading-4">
                                <?php echo esc_html($slotsInfo); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Icon mapped properly to SVG arrow -->
                <div class="w-10 h-10 md:w-12 md:h-12 relative inline-flex flex-col justify-center items-center rounded-full transition-colors text-stone-600 group-hover:text-accent-500">
                    <svg class="w-5 h-5 ml-2 md:w-6 md:h-6 fill-none" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
            </div>

        </div>
    </div>
</div>
