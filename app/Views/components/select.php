<?php
$label = isset($args['label']) ? $args['label'] : '';
$name = isset($args['name']) ? $args['name'] : uniqid('select_');
$options = isset($args['options']) ? $args['options'] : ['Mặc định', 'Giá từ thấp đến cao', 'Giá từ cao đến thấp'];
$class = isset($args['class']) ? $args['class'] : '';
?>
<div class="<?php echo esc_attr($class); ?>">
    <?php if ($label): ?>
    <label class="block text-body-small font-semibold text-gray-900 mb-2"><?php echo esc_html($label); ?></label>
    <?php endif; ?>
    <div class="relative">
        <select name="<?php echo esc_attr($name); ?>" class="w-full pl-5 pr-12 py-3.5 appearance-none rounded-2xl border border-gray-200 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 outline-none transition-all text-body-reg text-gray-900 bg-white hover:border-gray-300 font-medium">
            <option value="" disabled selected>Vui lòng chọn...</option>
            <?php foreach($options as $val => $opt): ?>
                <option value="<?php echo esc_attr(is_numeric($val) ? $opt : $val); ?>"><?php echo esc_html($opt); ?></option>
            <?php endforeach; ?>
        </select>
        <div class="absolute inset-y-0 right-0 flex items-center pr-5 pointer-events-none text-gray-400">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
    </div>
</div>
