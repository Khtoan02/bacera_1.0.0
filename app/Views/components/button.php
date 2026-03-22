<?php
$text = isset($args['text']) ? $args['text'] : 'Button';
$type = isset($args['type']) ? $args['type'] : 'primary'; // primary, accent, outline, ghost
$link = isset($args['link']) ? $args['link'] : '#';
$class = isset($args['class']) ? $args['class'] : '';

$base_classes = 'px-6 py-3 rounded-xl text-nav-link hover:-translate-y-0.5 transition-all active:scale-95 duration-200 inline-flex items-center justify-center font-medium gap-2 ';

$type_classes = [
    'primary' => 'bg-primary text-white hover:bg-primary/90 hover:shadow-lg hover:shadow-primary/30',
    'accent'  => 'bg-accent text-white hover:bg-accent/90 hover:shadow-lg hover:shadow-accent/30',
    'outline' => 'border-2 border-gray-200 text-gray-700 hover:border-gray-900 hover:text-gray-900 focus:ring-2 focus:ring-offset-2 focus:ring-gray-900',
    'ghost'   => 'text-primary hover:bg-primary-100 text-primary-800'
];

$final_class = $base_classes . ($type_classes[$type] ?? $type_classes['primary']) . ' ' . $class;
?>
<a href="<?php echo esc_url($link); ?>" class="<?php echo esc_attr($final_class); ?>">
    <?php echo esc_html($text); ?>
    <?php if (isset($args['icon'])): ?>
        <?php echo $args['icon']; ?>
    <?php endif; ?>
</a>
