<?php
// Simple presentation modal structure
$title = isset($args['title']) ? $args['title'] : 'Xác nhận thông tin';
$desc = isset($args['desc']) ? $args['desc'] : 'Bạn có chắc chắn muốn thực hiện hành động này?';
$confirm = isset($args['confirm']) ? $args['confirm'] : 'Xác nhận';
$cancel = isset($args['cancel']) ? $args['cancel'] : 'Hủy bỏ';
$is_preview = isset($args['is_preview']) ? $args['is_preview'] : false;

$position_class = $is_preview ? 'absolute inset-0' : 'fixed inset-0 z-[100]';
?>
<!-- Modal Container with Alpine transition -->
<div class="<?php echo $position_class; ?>" 
     x-show="modalOpen" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     style="display: none;">
     
    <div class="absolute inset-0 bg-stone-900/60 backdrop-blur-sm shadow-2xl" @click="modalOpen = false"></div>
    
    <div class="absolute inset-0 z-10 w-full h-full overflow-y-auto pointer-events-none">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <!-- Modal panel -->
            <div @click.stop 
                 x-show="modalOpen"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 translate-y-8 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-8 scale-95"
                 class="relative transform overflow-hidden rounded-[2rem] bg-white p-6 md:p-8 text-left shadow-2xl transition-all sm:my-8 w-full sm:max-w-md border border-stone-200 pointer-events-auto">
                
                <div class="absolute top-4 right-4">
                    <button type="button" @click="modalOpen = false" class="bg-stone-50 rounded-full p-2 text-stone-400 hover:text-stone-900 hover:bg-stone-100 transition-colors focus:outline-none">
                        <span class="sr-only">Close</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                    </button>
                </div>
                
                <div>
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-accent-50 text-accent-500 shadow-inner">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="mt-6 text-center">
                        <h3 class="text-[24px] font-bold leading-tight text-stone-900 font-display" id="modal-title">
                            <?php echo esc_html($title); ?>
                        </h3>
                        <div class="mt-3">
                            <p class="text-[16px] text-stone-500 font-normal leading-relaxed">
                                <?php echo esc_html($desc); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-8 flex flex-col gap-3">
                    <?php get_template_part('app/Views/components/button', null, ['text' => $confirm, 'variant' => 'primary', 'class' => 'w-full']); ?>
                    <!-- Added @click to cancel button wrapper using alpine inline JS handling -->
                    <div @click="modalOpen = false" class="w-full">
                        <?php get_template_part('app/Views/components/button', null, ['text' => $cancel, 'variant' => 'outline', 'class' => 'w-full']); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
