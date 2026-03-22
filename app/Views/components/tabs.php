<?php
$tabs = isset($args['tabs']) ? $args['tabs'] : ['Tổng quan Workshop', 'Kế hoạch chi tiết', 'Đánh giá (12)', 'Quy định'];
$active = isset($args['active']) ? $args['active'] : 0;
?>
<div class="border-b border-gray-200">
    <nav class="-mb-px flex space-x-10 overflow-x-auto hide-scrollbar" aria-label="Tabs">
        <?php foreach($tabs as $index => $tab): ?>
            <?php $isActive = $index === $active; ?>
            <a href="#" class="<?php echo $isActive ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-900 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-[3px] font-bold text-body-reg transition-all uppercase tracking-wide">
                <?php echo esc_html($tab); ?>
            </a>
        <?php endforeach; ?>
    </nav>
</div>
