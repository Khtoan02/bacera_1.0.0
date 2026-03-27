<?php
/**
 * Textarea Component
 */
$placeholder = isset($args['placeholder']) ? $args['placeholder'] : 'Placeholder';
$name = isset($args['name']) ? $args['name'] : '';
$id = isset($args['id']) ? $args['id'] : (empty($name) ? uniqid('textarea_') : $name);
$class = isset($args['class']) ? $args['class'] : '';
$state = isset($args['state']) ? $args['state'] : 'default'; // default, typing, filled, error, disabled
$value = isset($args['value']) ? $args['value'] : '';

// Base styles matching Figma
$base_classes = "w-full h-36 px-4 py-4 text-[16px] font-normal leading-relaxed transition-all rounded-lg outline-none font-sans resize-y min-h-[144px]";

// State overrides
switch ($state) {
    case 'error':
        $textarea_classes = "bg-red-50 ring-1 ring-inset ring-red-600 text-red-600 placeholder:text-red-600 focus:ring-1 focus:ring-inset focus:ring-red-600";
        break;
    case 'disabled':
        $textarea_classes = "bg-stone-200 ring-1 ring-inset ring-stone-200 text-stone-400 placeholder:text-stone-400 cursor-not-allowed opacity-50";
        break;
    case 'typing':
        $textarea_classes = "bg-white ring-2 ring-inset ring-stone-700 text-stone-800 placeholder:text-stone-800";
        break;
    case 'filled':
        $textarea_classes = "bg-white ring-1 ring-inset ring-stone-700 text-stone-800";
        break;
    default:
        // Default interactive state
        $textarea_classes = "bg-white ring-1 ring-inset ring-stone-300 text-stone-800 placeholder:text-stone-800/50 hover:ring-stone-400 focus:ring-2 focus:ring-inset focus:ring-stone-700";
        break;
}
?>
<div class="relative w-full <?php echo esc_attr($class); ?>">
    <textarea 
        id="<?php echo esc_attr($id); ?>"
        name="<?php echo esc_attr($name); ?>"
        placeholder="<?php echo esc_attr($placeholder); ?>" 
        <?php echo ($state === 'disabled') ? 'disabled' : ''; ?>
        class="<?php echo esc_attr($base_classes . ' ' . $textarea_classes); ?>"
    ><?php echo esc_textarea($value); ?></textarea>
    
    <!-- Custom Resize Handle Decoration (Figma Detail) -->
    <div class="pointer-events-none absolute bottom-3 right-3 w-3 h-3 border-r-2 border-b-2 <?php echo ($state === 'error') ? 'border-red-600 opacity-50' : 'border-stone-800 opacity-20'; ?>"></div>
</div>
