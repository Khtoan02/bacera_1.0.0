<?php
$label = isset($args['label']) ? $args['label'] : 'Chọn ngày tham gia';
$name = isset($args['name']) ? $args['name'] : uniqid('date_');
$class = isset($args['class']) ? $args['class'] : '';
?>
<div class="<?php echo esc_attr($class); ?>">
    <?php if ($label): ?>
    <label class="block text-body-small font-semibold text-gray-900 mb-2"><?php echo esc_html($label); ?></label>
    <?php endif; ?>
    <div class="relative">
        <input type="date" name="<?php echo esc_attr($name); ?>" class="w-full pl-12 pr-5 py-3.5 appearance-none rounded-2xl border border-gray-200 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 outline-none transition-all text-body-reg text-gray-900 bg-white hover:border-gray-300 font-medium [&::-webkit-calendar-picker-indicator]:opacity-0 [&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:inset-0 [&::-webkit-calendar-picker-indicator]:w-full [&::-webkit-calendar-picker-indicator]:h-full [&::-webkit-calendar-picker-indicator]:cursor-pointer">
        <div class="absolute inset-y-0 left-0 flex items-center pl-5 pointer-events-none text-primary-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
    </div>
</div>
