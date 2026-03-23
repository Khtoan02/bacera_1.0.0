<?php
// Simple presentation modal structure
$title = isset($args['title']) ? $args['title'] : 'Xác nhận thông tin';
$desc = isset($args['desc']) ? $args['desc'] : 'Bạn có chắc chắn muốn thực hiện hành động này?';
$confirm = isset($args['confirm']) ? $args['confirm'] : 'Xác nhận';
$cancel = isset($args['cancel']) ? $args['cancel'] : 'Hủy bỏ';
?>
<div class="absolute z-50 transition-opacity inset-0">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm shadow-2xl"></div>
    <div class="absolute inset-0 z-10 w-full h-full overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <!-- Modal panel -->
            <div class="relative transform overflow-hidden rounded-[2rem] bg-white p-6 md:p-8 text-left shadow-2xl transition-all sm:my-8 w-full sm:max-w-md border border-gray-100">
                <div class="absolute top-4 right-4">
                    <button type="button" class="bg-gray-50 rounded-full p-2 text-gray-400 hover:text-gray-900 hover:bg-gray-100 transition-colors">
                        <span class="sr-only">Close</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                    </button>
                </div>
                
                <div>
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100 shadow-inner">
                        <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>
                    <div class="mt-6 text-center">
                        <h3 class="text-h2 font-bold leading-6 text-gray-900" id="modal-title">
                            <?php echo esc_html($title); ?>
                        </h3>
                        <div class="mt-3">
                            <p class="text-body-reg text-gray-500 font-medium">
                                <?php echo esc_html($desc); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-8 flex flex-col sm:flex-row-reverse gap-3">
                    <?php get_template_part('app/Views/components/button', null, ['text' => $confirm, 'type' => 'primary', 'class' => 'w-full sm:w-auto shadow-xl shadow-primary-900/10']); ?>
                    <?php get_template_part('app/Views/components/button', null, ['text' => $cancel, 'type' => 'outline', 'class' => 'w-full sm:w-auto']); ?>
                </div>
            </div>
        </div>
    </div>
</div>
