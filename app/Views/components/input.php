<?php
$placeholder = isset($args['placeholder']) ? $args['placeholder'] : 'Placeholder';
$type = isset($args['type']) ? $args['type'] : 'text';
$name = isset($args['name']) ? $args['name'] : '';
$id = isset($args['id']) ? $args['id'] : (empty($name) ? uniqid('input_') : $name);
$class = isset($args['class']) ? $args['class'] : '';
$state = isset($args['state']) ? $args['state'] : 'default'; // default, typing, filled, error, disabled
$value = isset($args['value']) ? $args['value'] : '';

// Base styles matching Figma
$base_classes = "w-full h-16 px-4 py-4 text-[16px] font-normal leading-4 transition-all rounded-lg outline-none font-sans";

// State overrides
switch ($state) {
    case 'error':
        $input_classes = "bg-red-50 ring-1 ring-inset ring-red-600 text-red-600 placeholder:text-red-600 focus:ring-1 focus:ring-inset focus:ring-red-600";
        break;
    case 'disabled':
        $input_classes = "bg-stone-200 ring-1 ring-inset ring-stone-200 text-stone-400 placeholder:text-stone-400 cursor-not-allowed";
        break;
    case 'typing':
        $input_classes = "bg-white ring-2 ring-inset ring-stone-700 text-stone-800 placeholder:text-stone-800";
        break;
    case 'filled':
        $input_classes = "bg-white ring-1 ring-inset ring-stone-700 text-stone-800";
        break;
    default:
        // Default interactive state
        $input_classes = "bg-white ring-1 ring-inset ring-stone-300 text-stone-800 placeholder:text-stone-700/50 hover:ring-stone-400 focus:ring-2 focus:ring-inset focus:ring-stone-700";
        break;
}
?>
<div class="relative w-full <?php echo esc_attr($class); ?>">
    <input 
        type="<?php echo esc_attr($type); ?>" 
        id="<?php echo esc_attr($id); ?>"
        name="<?php echo esc_attr($name); ?>"
        placeholder="<?php echo esc_attr($placeholder); ?>" 
        value="<?php echo esc_attr($value); ?>"
        <?php echo $state === 'disabled' ? 'disabled' : ''; ?>
        class="<?php echo esc_attr($base_classes . ' ' . $input_classes); ?>"
    >
</div>
