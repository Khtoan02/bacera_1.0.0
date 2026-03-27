<?php
$text = isset($args['text']) ? $args['text'] : 'Label';
$variant = isset($args['variant']) ? $args['variant'] : 'primary'; // primary, secondary, outline, light, secondary-light, outline-light, ghost, ghost-light
$link = isset($args['link']) ? $args['link'] : null;
$class = isset($args['class']) ? $args['class'] : '';
$disabled = isset($args['disabled']) ? $args['disabled'] : false;

// Determine if we should render an <a> or a <button>
$tag = $link ? 'a' : 'button';
$href_attr = $link ? 'href="' . esc_url($link) . '"' : '';
$disabled_attr = $disabled ? 'disabled' : '';

// Base classes
$base_classes = 'inline-flex justify-center items-center gap-2 font-sans transition-all duration-300 ';

// Solid & Outline Button variants (px-5 py-4 rounded-lg)
if (!str_starts_with($variant, 'ghost')) {
    $base_classes .= 'px-5 py-4 rounded-lg text-[16px] font-medium leading-4 ';
} else {
    $base_classes .= 'flex-col items-start gap-1 group text-[16px] font-normal '; // Ghost buttons don't have px-5 py-4, they just wrap text
}

// Map out the complex Figma states per variant
$variants = [
    // bg-red-500 -> hover shadow -> active bg-orange-700 -> disabled bg-stone-200 text-stone-300
    'primary' => 'bg-red-500 text-stone-200 hover:shadow-[2px_2px_10px_0px_rgba(0,0,0,0.25)] active:bg-orange-700 active:shadow-none disabled:bg-stone-200 disabled:text-stone-300 disabled:shadow-none',
    
    // bg-stone-600 -> hover bg-red-500 shadow -> active bg-orange-700 -> disabled bg-stone-200 text-stone-300
    'secondary' => 'bg-stone-600 text-stone-50 hover:bg-red-500 hover:text-stone-200 hover:shadow-[2px_2px_10px_0px_rgba(0,0,0,0.25)] active:bg-orange-700 active:shadow-none disabled:bg-stone-200 disabled:text-stone-300 disabled:shadow-none',
    
    // outline-stone-300 text-stone-500 -> hover bg-red-500 shadow text-stone-200 -> active bg-orange-700 -> disabled outline-stone-50 text-stone-200
    'outline' => 'ring-1 ring-inset ring-stone-300 text-stone-500 hover:bg-red-500 hover:ring-0 hover:text-stone-200 hover:shadow-[2px_2px_10px_0px_rgba(0,0,0,0.25)] active:bg-orange-700 active:shadow-none disabled:ring-stone-50 disabled:text-stone-200 disabled:bg-transparent disabled:shadow-none',
    
    // bg-stone-200 text-stone-600 -> hover shadow -> active bg-stone-300 -> disabled bg-stone-200 text-stone-300
    'light' => 'bg-stone-200 text-stone-600 hover:shadow-[2px_2px_10px_0px_rgba(0,0,0,0.25)] active:bg-stone-300 active:shadow-none disabled:bg-stone-200 disabled:text-stone-300 disabled:shadow-none',
    
    // bg-stone-400 text-stone-600 -> hover bg-stone-200 shadow -> active bg-stone-300 -> disabled bg-stone-200 text-stone-300
    'secondary-light' => 'bg-stone-400 text-stone-600 hover:bg-stone-200 hover:shadow-[2px_2px_10px_0px_rgba(0,0,0,0.25)] active:bg-stone-300 active:shadow-none disabled:bg-stone-200 disabled:text-stone-300 disabled:shadow-none',
    
    // outline-stone-50 text-stone-50 -> hover bg-stone-200 shadow text-stone-600 -> active bg-stone-300 -> disabled bg-stone-200 text-stone-300
    'outline-light' => 'ring-1 ring-inset ring-stone-50 text-stone-50 hover:bg-stone-200 hover:ring-0 hover:text-stone-600 hover:shadow-[2px_2px_10px_0px_rgba(0,0,0,0.25)] active:bg-stone-300 active:text-stone-600 active:shadow-none disabled:ring-0 disabled:bg-stone-200 disabled:text-stone-300 disabled:shadow-none',
    
    // Ghost (Dark text context)
    'ghost' => 'text-stone-600 hover:text-stone-800 active:opacity-70 active:text-stone-800 disabled:opacity-70 disabled:text-stone-400',
    
    // Ghost Light (Light text context)
    'ghost-light' => 'text-stone-200 hover:text-stone-200 active:opacity-70 active:text-stone-200 disabled:opacity-70 disabled:text-stone-200'
];

$final_class = $base_classes . ($variants[$variant] ?? $variants['primary']) . ' ' . $class . ($disabled ? ' cursor-not-allowed pointer-events-none' : '');
?>
<<?php echo $tag; ?> <?php echo $href_attr; ?> <?php echo $disabled_attr; ?> class="<?php echo esc_attr($final_class); ?>">
    <!-- Ghost Wrap -->
    <?php if (str_starts_with($variant, 'ghost')): ?>
        <span class="block transition-colors"><?php echo esc_html($text); ?></span>
        <?php if (!$disabled): ?>
            <div class="w-full h-[2px] overflow-hidden opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex">
                <?php if ($variant === 'ghost-light'): ?>
                    <div class="h-full w-full bg-stone-200 transform origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                <?php else: ?>
                    <div class="h-full w-full bg-stone-800 transform origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Solid / Outline Content -->
        <?php echo esc_html($text); ?>
        <?php if (isset($args['icon'])): ?>
            <?php echo $args['icon']; ?>
        <?php endif; ?>
    <?php endif; ?>
</<?php echo $tag; ?>>
