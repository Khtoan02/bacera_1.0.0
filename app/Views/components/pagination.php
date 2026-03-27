<?php
$current = isset($args['current']) ? $args['current'] : 1;
$total = isset($args['total']) ? $args['total'] : 5;
?>
<nav class="flex items-center justify-center gap-2 w-full font-sans" aria-label="Pagination">
    <!-- Prev Button -->
    <a href="#" class="inline-flex items-center justify-center h-10 px-4 rounded-lg border border-stone-200 text-[14px] font-medium text-stone-600 hover:bg-stone-50 hover:text-stone-900 transition-all group <?php echo $current <= 1 ? 'opacity-50 pointer-events-none' : ''; ?>">
        <svg class="mr-2 h-4 w-4 text-stone-400 group-hover:-translate-x-0.5 group-hover:text-stone-900 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
        <span class="hidden sm:inline">Trang trước</span>
    </a>

    <!-- Page Numbers -->
    <div class="hidden sm:flex items-center gap-1">
        <?php for($i=1; $i<=$total; $i++): ?>
            <?php if($i == 1 || $i == $total || abs($i - $current) <= 1): ?>
                <a href="#" class="inline-flex items-center justify-center h-10 min-w-[40px] px-2 rounded-lg text-[15px] font-medium transition-all <?php echo $i === $current ? 'bg-stone-800 text-white shadow-md' : 'text-stone-600 hover:bg-stone-100 hover:text-stone-900'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php elseif($i == 2 || $i == $total - 1): ?>
                <span class="inline-flex items-center justify-center h-10 min-w-[32px] text-stone-400">...</span>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    
    <!-- Mobile simplistic indicator -->
    <div class="sm:hidden flex items-center justify-center px-4 font-medium text-stone-600 text-[14px]">
        <?php echo $current; ?> / <?php echo $total; ?>
    </div>

    <!-- Next Button -->
    <a href="#" class="inline-flex items-center justify-center h-10 px-4 rounded-lg border border-stone-200 text-[14px] font-medium text-stone-600 hover:bg-stone-50 hover:text-stone-900 transition-all group <?php echo $current >= $total ? 'opacity-50 pointer-events-none' : ''; ?>">
        <span class="hidden sm:inline">Trang sau</span>
        <svg class="ml-2 h-4 w-4 text-stone-400 group-hover:translate-x-0.5 group-hover:text-stone-900 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
    </a>
</nav>
