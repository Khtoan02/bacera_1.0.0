<?php
$current = isset($args['current']) ? $args['current'] : 3;
$total = isset($args['total']) ? $args['total'] : 8;
?>
<nav class="flex items-center justify-between border-t border-gray-200 px-4 sm:px-0 mt-12 py-6">
    <div class="-mt-px flex w-0 flex-1">
        <a href="#" class="inline-flex items-center border-t-2 border-transparent pr-1 pt-4 text-body-small font-bold text-gray-500 hover:text-gray-900 transition-colors uppercase tracking-wider group">
            <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:-translate-x-1 group-hover:text-gray-900 transition-all" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M15 10a.75.75 0 01-.75.75H7.612l2.158 1.96a.75.75 0 11-1.04 1.08l-3.5-3.25a.75.75 0 010-1.08l3.5-3.25a.75.75 0 111.04 1.08L7.612 9.25h6.638A.75.75 0 0115 10z" clip-rule="evenodd" /></svg>
            Trang trước
        </a>
    </div>
    <div class="hidden md:-mt-px md:flex">
        <?php for($i=1; $i<=$total; $i++): ?>
            <?php if($i == 1 || $i == $total || abs($i - $current) <= 1): ?>
                <a href="#" class="<?php echo $i === $current ? 'border-primary-600 text-primary-600 bg-primary-50 rounded-lg' : 'border-transparent text-gray-500 hover:text-gray-900 hover:bg-gray-50 rounded-lg'; ?> inline-flex items-center h-10 w-10 justify-center border-2 border-transparent text-body-reg font-bold transition-all mx-1 mt-3"><?php echo $i; ?></a>
            <?php elseif($i == 2 || $i == $total - 1): ?>
                <span class="inline-flex items-center px-4 pt-4 text-body-small font-medium text-gray-500">...</span>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <div class="-mt-px flex w-0 flex-1 justify-end">
        <a href="#" class="inline-flex items-center border-t-2 border-transparent pl-1 pt-4 text-body-small font-bold text-gray-500 hover:text-gray-900 transition-colors uppercase tracking-wider group">
            Trang sau
            <svg class="ml-3 h-5 w-5 text-gray-400 group-hover:translate-x-1 group-hover:text-gray-900 transition-all" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5 10a.75.75 0 01.75-.75h6.638L10.23 7.29a.75.75 0 111.04-1.08l3.5 3.25a.75.75 0 010 1.08l-3.5 3.25a.75.75 0 11-1.04-1.08l2.158-1.96H5.75A.75.75 0 015 10z" clip-rule="evenodd" /></svg>
        </a>
    </div>
</nav>
