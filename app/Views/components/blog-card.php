<?php
/**
 * Blog Card Component
 * @param string $image Thumbnail URL
 * @param string $category Label
 * @param string $title Post title
 * @param string $class Custom wrapper class
 */
$image = isset($args['image']) ? $args['image'] : 'https://placehold.co/296x209/eae3d1/8d6a54?text=Blog';
$category = isset($args['category']) ? $args['category'] : 'Ceramics';
$title = isset($args['title']) ? $args['title'] : 'Mealtime fun';
$class = isset($args['class']) ? $args['class'] : '';
?>
<article class="w-full inline-flex flex-col justify-start items-start gap-2.5 group cursor-pointer <?php echo esc_attr($class); ?>">
    
    <!-- Image Section Formatted strictly per Figma snippet -->
    <div class="self-stretch rounded-lg flex flex-col justify-start items-start gap-2 overflow-hidden bg-neutral-100 relative">
        <img 
            src="<?php echo esc_url($image); ?>" 
            alt="<?php echo esc_attr($title); ?>" 
            class="self-stretch w-full h-52 object-cover transition-transform duration-700 group-hover:scale-105"
        >
        <!-- Optional hover subtle gradient layer for polish -->
        <div class="absolute inset-0 bg-gray-900/10 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none mix-blend-multiply"></div>
    </div>
    
    <!-- Content Section Formatted strictly per Figma snippet -->
    <div class="self-stretch flex flex-col justify-start items-start gap-1">
        
        <!-- Category -->
        <div class="self-stretch justify-start text-stone-400 text-[16px] font-normal font-sans leading-4">
            <?php echo esc_html($category); ?>
        </div>
        
        <!-- Title -->
        <h3 class="self-stretch justify-start text-stone-800 text-[20px] font-medium font-sans leading-6 group-hover:text-stone-500 transition-colors">
            <?php echo esc_html($title); ?>
        </h3>
        
    </div>
</article>
