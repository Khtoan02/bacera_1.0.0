<?php
$title = isset($args['title']) ? $args['title'] : 'Thông báo hệ thống!';
$message = isset($args['message']) ? $args['message'] : 'Đã thêm sản phẩm thành công vào giỏ hàng của bạn.';
$type = isset($args['type']) ? $args['type'] : 'success'; // success, warning, error, info

$styles = [
    'success' => ['bg' => 'bg-green-50', 'text' => 'text-green-900', 'desc' => 'text-green-700', 'border' => 'border-green-200', 'icon' => 'text-green-500', 'svg' => '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />'],
    'error'   => ['bg' => 'bg-red-50', 'text' => 'text-red-900', 'desc' => 'text-red-700', 'border' => 'border-red-200', 'icon' => 'text-red-500', 'svg' => '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />'],
    'warning' => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-900', 'desc' => 'text-yellow-700', 'border' => 'border-yellow-200', 'icon' => 'text-yellow-500', 'svg' => '<path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />']
];
$style = $styles[$type] ?? $styles['success'];
?>
<div class="rounded-2xl p-5 <?php echo esc_attr($style['bg']); ?> border <?php echo esc_attr($style['border']); ?> shadow-sm">
    <div class="flex items-start">
        <div class="shrink-0 pt-0.5">
            <svg class="h-6 w-6 <?php echo esc_attr($style['icon']); ?>" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <?php echo $style['svg']; ?>
            </svg>
        </div>
        <div class="ml-4 flex-1">
            <h3 class="text-body-reg font-bold <?php echo esc_attr($style['text']); ?>"><?php echo esc_html($title); ?></h3>
            <div class="mt-1 text-sm <?php echo esc_attr($style['desc']); ?> font-medium">
                <p><?php echo esc_html($message); ?></p>
            </div>
        </div>
        <div class="ml-4 shrink-0 flex">
            <button type="button" class="inline-flex rounded-md <?php echo esc_attr($style['bg']); ?> p-1.5 <?php echo esc_attr($style['desc']); ?> hover:<?php echo esc_attr($style['text']); ?> focus:outline-none transition-colors">
                <span class="sr-only">Dismiss</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                </svg>
            </button>
        </div>
    </div>
</div>
