<?php
$label = isset($args['label']) ? $args['label'] : '';
$placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
$type = isset($args['type']) ? $args['type'] : 'text';
$name = isset($args['name']) ? $args['name'] : '';
$id = isset($args['id']) ? $args['id'] : (empty($name) ? uniqid('input_') : $name);
$class = isset($args['class']) ? $args['class'] : '';
?>
<div class="<?php echo esc_attr($class); ?>">
    <?php if ($label): ?>
    <label for="<?php echo esc_attr($id); ?>" class="block text-body-small font-semibold text-gray-900 mb-2">
        <?php echo esc_html($label); ?>
    </label>
    <?php endif; ?>
    <input 
        type="<?php echo esc_attr($type); ?>" 
        id="<?php echo esc_attr($id); ?>"
        name="<?php echo esc_attr($name); ?>"
        placeholder="<?php echo esc_attr($placeholder); ?>" 
        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all text-body-reg placeholder:text-gray-400 text-gray-900 bg-white hover:border-gray-300"
    >
</div>
