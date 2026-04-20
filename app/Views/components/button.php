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
    // Primary: terracotta -> hover shadow -> active accentdark -> disabled bg-accent text-white
    'primary' => 'bg-terracotta text-white hover:shadow-[2px_2px_10px_0px_rgba(0,0,0,0.15)] active:bg-accentdark active:shadow-none disabled:bg-accent/50 disabled:text-white disabled:shadow-none',
    
    // Secondary: textmain -> hover terracotta shadow -> active accentdark -> disabled bg-accent/50 text-white
    'secondary' => 'bg-textmain text-white hover:bg-terracotta hover:shadow-[2px_2px_10px_0px_rgba(0,0,0,0.15)] active:bg-accentdark active:shadow-none disabled:bg-accent/50 disabled:text-white disabled:shadow-none',
    
    // Outline: ring-accentdark text-accentdark -> hover bg-terracotta ring-0 text-white shadow -> active bg-accentdark -> disabled ring-accent/30 text-accent/50
    'outline' => 'ring-1 ring-inset ring-accent text-accentdark hover:bg-terracotta hover:ring-0 hover:text-white hover:shadow-[2px_2px_10px_0px_rgba(0,0,0,0.15)] active:bg-accentdark active:shadow-none disabled:ring-accent/30 disabled:text-accent/50 disabled:bg-transparent disabled:shadow-none',
    
    // Light: bgtheme text-textmain -> hover shadow -> active accent/20 -> disabled bgtheme/50 text-textmuted
    'light' => 'bg-bgtheme text-textmain hover:shadow-[2px_2px_10px_0px_rgba(0,0,0,0.1)] active:bg-accent/20 active:shadow-none disabled:bg-bgtheme/50 disabled:text-textmuted disabled:shadow-none',
    
    // Secondary-light: accent/20 text-textmain -> hover bgtheme shadow -> active accent/30 -> disabled accent/10 text-textmuted
    'secondary-light' => 'bg-accent/10 text-textmain hover:bg-bgtheme hover:shadow-[2px_2px_10px_0px_rgba(0,0,0,0.1)] active:bg-accent/30 active:shadow-none disabled:bg-accent/10 disabled:text-textmuted disabled:shadow-none',
    
    // Outline-light: ring-white text-white -> hover bg-white ring-0 text-textmain shadow -> active bg-bgtheme -> disabled ring-white/30 text-white/50
    'outline-light' => 'ring-1 ring-inset ring-white/50 text-white hover:bg-white hover:ring-0 hover:text-textmain hover:shadow-[2px_2px_10px_0px_rgba(0,0,0,0.15)] active:bg-bgtheme active:text-textmain active:shadow-none disabled:ring-0 disabled:bg-white/20 disabled:text-white/50 disabled:shadow-none',
    
    // Ghost (Dark text context)
    'ghost' => 'text-textmain hover:text-terracotta active:opacity-70 disabled:opacity-50 disabled:text-textmuted',
    
    // Ghost Light (Light text context)
    'ghost-light' => 'text-white hover:text-bgtheme active:opacity-70 disabled:opacity-50 disabled:text-white/50'
];

$final_class = $base_classes . ($variants[$variant] ?? $variants['primary']) . ' ' . $class . ($disabled ? ' cursor-not-allowed pointer-events-none' : '');
?>
<<?php echo $tag; ?> <?php echo $href_attr; ?> <?php echo $disabled_attr; ?> class="<?php echo esc_attr($final_class); ?>">
    
    <?php if (str_starts_with($variant, 'ghost')): ?>
        <span class="block transition-colors"><?php echo esc_html($text); ?></span>
        <?php if (!$disabled): ?>
            <div class="w-full h-[2px] overflow-hidden opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex">
                <?php if ($variant === 'ghost-light'): ?>
                    <div class="h-full w-full bg-neutral-200 transform origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                <?php else: ?>
                    <div class="h-full w-full bg-primary-800 transform origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        
        <?php echo esc_html($text); ?>
        <?php if (isset($args['icon'])): ?>
            <?php echo $args['icon']; ?>
        <?php endif; ?>
    <?php endif; ?>
</<?php echo $tag; ?>>
