<?php
/**
 * Workshop Card Component
 * @param string $image       Thumbnail URL
 * @param string $title       Title of workshop
 * @param string $description Short description
 * @param string $pricing     Price string e.g 'From $38.00/Person'
 * @param int    $bookedSlots Số lượng slot đã đặt (a)
 * @param int    $totalSlots  Tổng số slot (b)
 * @param string $link        URL to workshop detail
 * @param string $class       Additional wrapper classes
 */

$image       = $args['image'] ?? 'https://placehold.co/296x220';
$title       = $args['title'] ?? 'Pottery Wheel Throwing';
$description = $args['description'] ?? 'A peaceful, hands-on journey for beginners and curious minds.';
$pricing     = $args['pricing'] ?? 'From $38.00/Person';
$bookedSlots = isset($args['bookedSlots']) ? (int)$args['bookedSlots'] : 13;
$totalSlots  = isset($args['totalSlots']) ? (int)$args['totalSlots'] : 16;
$link        = $args['link'] ?? '#';
$class       = $args['class'] ?? '';

// Logic kiểm tra tình trạng chỗ
$isFull = $bookedSlots >= $totalSlots;
?>

<!-- Card Wrapper: Ultra-minimal Premium Visual -->
<a href="<?php echo esc_url($link); ?>" class="group flex flex-col bg-white rounded-[20px] border border-black/5 overflow-hidden shadow-[0_4px_20px_rgb(0,0,0,0.03)] hover:shadow-[0_12px_40px_rgb(0,0,0,0.08)] hover:-translate-y-1.5 transition-all duration-500 ease-[cubic-bezier(0.2,0.8,0.2,1)] <?php echo esc_attr($class); ?>">
    
    <!-- Image Section -->
    <div class="w-full relative overflow-hidden bg-neutral-100 aspect-[4/3]">
        <img 
            src="<?php echo esc_url($image); ?>" 
            alt="<?php echo esc_attr($title); ?>" 
            class="absolute inset-0 w-full h-full object-cover scale-[1.02] group-hover:scale-105 transition-transform duration-700 ease-[cubic-bezier(0.25,1,0.5,1)]"
            loading="lazy"
        >
        
        <!-- Premium Gradient Overlay & Action Button -->
        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 bg-gradient-to-t from-black/20 via-black/10 to-transparent backdrop-blur-[2px] transition-all duration-500 z-10">
            <div class="bg-white/95 backdrop-blur-sm px-6 py-2.5 rounded-full text-primary-900 text-sm font-semibold tracking-wide shadow-[0_8px_20px_rgb(0,0,0,0.1)] translate-y-6 group-hover:translate-y-0 transition-all duration-500 ease-out flex items-center gap-2">
                <?php echo $isFull ? 'Xem chi tiết' : 'Đăng ký ngay'; ?>
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </div>
        </div>
        
        <?php if ($isFull): ?>
        <div class="absolute top-4 right-4 bg-white/95 backdrop-blur-md text-primary-900 border border-white/20 shadow-sm text-xs font-bold uppercase tracking-wider px-3 py-1.5 rounded-full z-20">
            Kín chỗ
        </div>
        <?php endif; ?>
    </div>

    <!-- Content Section -->
    <div class="flex flex-col flex-1 p-6 z-20 relative">
        <!-- Badge / Pricing space -->
        <div class="flex items-center gap-2 mb-3">
            <span class="text-[10px] font-bold uppercase tracking-widest text-accent-500">Workshop</span>
            <span class="w-1 h-1 rounded-full bg-neutral-300"></span>
            <span class="text-[12px] font-medium text-neutral-500 tracking-wide"><?php echo esc_html($pricing); ?></span>
        </div>

        <!-- Title & Description -->
        <div class="flex flex-col gap-2.5 mb-6">
            <h3 class="text-primary-900 font-serif font-medium text-xl leading-snug group-hover:text-accent-500 transition-colors duration-300 line-clamp-2">
                <?php echo esc_html($title); ?>
            </h3>
            <p class="text-neutral-500 text-[14px] leading-relaxed line-clamp-2 font-medium">
                <?php echo esc_html($description); ?>
            </p>
        </div>

        <div class="flex-1"></div>
        
        <!-- Minimal Footer: Slots Info -->
        <div class="flex justify-between items-center w-full pt-5 border-t border-black/[0.04]">
            <div class="inline-flex items-center gap-2">
                <?php if ($isFull): ?>
                <div class="w-2 h-2 rounded-full bg-neutral-400"></div>
                <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wide">Đã hết chỗ trống</span>
                <?php else: ?>
                <div class="flex items-center gap-1.5">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-accent-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-accent-500"></span>
                    </span>
                    <span class="text-xs font-semibold text-primary-900 uppercase tracking-wide">Còn <?php echo max(0, $totalSlots - $bookedSlots); ?> chỗ</span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="text-neutral-400 group-hover:text-accent-500 transform group-hover:translate-x-1 group-hover:-translate-y-1 transition-all duration-300 relative">
                <svg class="w-5 h-5 relative z-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </div>
        </div>
    </div>
</a>