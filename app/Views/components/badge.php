<?php
$text = isset($args['text']) ? $args['text'] : 'Badge';
$type = isset($args['type']) ? $args['type'] : 'primary'; // primary, accent, neutral, success
$class = isset($args['class']) ? $args['class'] : '';

$base_classes = 'inline-flex items-center px-2.5 py-1 rounded-full text-[11px] sm:text-xs font-bold tracking-wide uppercase ';

$type_classes = [
    'primary' => 'bg-primary-100 text-primary-900',
    'accent'  => 'bg-accent-100 text-accent-600',
    'neutral' => 'bg-neutral-200 text-gray-800',
    'success' => 'bg-green-100 text-green-800'
];

$final_class = $base_classes . ($type_classes[$type] ?? $type_classes['primary']) . ' ' . $class;
?>
<span class="<?php echo esc_attr($final_class); ?>">
    <?php echo esc_html($text); ?>
</span>
