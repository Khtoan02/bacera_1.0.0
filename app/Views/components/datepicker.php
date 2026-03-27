<?php
/**
 * Workshop Booking Date & Time Picker Component
 */
$class = isset($args['class']) ? $args['class'] : '';
?>
<div class="relative inline-flex flex-col w-full max-w-[384px] group/picker <?php echo esc_attr($class); ?>">
    
    <!-- Input Trigger Bar -->
    <div class="w-full flex items-center cursor-pointer focus:outline-none">
        <!-- Date Side -->
        <div class="flex-1 h-16 px-4 py-2 bg-white rounded-l-lg border-y border-l border-stone-300 group-hover/picker:border-stone-800 flex justify-between items-center transition-colors shadow-sm">
            <div class="flex flex-col justify-center items-start gap-1">
                <span class="opacity-70 text-stone-800 text-[11px] font-medium tracking-wide">SELECT DATE</span>
                <span class="text-stone-800 text-[15px] font-bold leading-none">02/05/2025</span>
            </div>
            <svg class="w-5 h-5 text-stone-600 transition-transform group-hover/picker:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
        
        <!-- Divider -->
        <div class="w-px h-16 bg-stone-300 group-hover/picker:bg-stone-800 transition-colors"></div>
        
        <!-- Time Side -->
        <div class="flex-1 h-16 px-4 py-2 bg-white rounded-r-lg border-y border-r border-stone-300 group-hover/picker:border-stone-800 flex justify-between items-center transition-colors shadow-sm">
            <div class="flex flex-col justify-center items-start gap-1">
                <span class="opacity-70 text-stone-800 text-[11px] font-medium tracking-wide">SELECT TIME</span>
                <div class="flex items-center gap-1">
                    <span class="text-stone-800 text-[15px] font-bold leading-none">Day</span>
                    <span class="text-stone-600 text-[12px] font-medium leading-none">(9:00 - 11:00)</span>
                </div>
            </div>
            <svg class="w-5 h-5 text-stone-600 transition-transform group-hover/picker:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
    </div>

    <!-- Dropdown Panel (Calendar + Time Slots) -->
    <!-- Displayed when hovering the picker group -->
    <div class="absolute top-[72px] left-0 md:left-[-145px] w-[300px] sm:w-[384px] md:w-[675px] bg-stone-50 rounded-lg shadow-[1px_1px_30px_0px_rgba(0,0,0,0.15)] border border-stone-200 opacity-0 invisible group-hover/picker:opacity-100 group-hover/picker:visible transition-all duration-300 z-50 flex flex-col md:flex-row transform -translate-y-2 group-hover/picker:translate-y-0">
        
        <!-- Calendar Section (w-96 in desktop = 384px) -->
        <div class="w-full md:w-[384px] p-5 shrink-0">
            <!-- Calendar Header -->
            <div class="flex justify-between items-center mb-4">
                <span class="text-stone-800 text-[16px] font-medium">Tháng 5 2025</span>
                <div class="flex gap-2">
                    <button class="w-7 h-7 rounded-full bg-stone-200 flex items-center justify-center hover:bg-stone-300 transition-colors text-stone-400 cursor-not-allowed">
                        <svg class="w-4 h-4 ml-[-2px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <button class="w-7 h-7 rounded-full bg-stone-200 flex items-center justify-center hover:bg-stone-300 transition-colors text-stone-800">
                        <svg class="w-4 h-4 ml-[2px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
            </div>
            <div class="h-px w-full bg-stone-200 mb-4"></div>
            
            <!-- Calendar Grid -->
            <div class="grid grid-cols-7 gap-2 text-center text-[13px] text-stone-800 opacity-70 mb-2">
                <div>T2</div><div>T3</div><div>T4</div><div>T5</div><div>T6</div><div class="font-[600] opacity-100 text-stone-900">T7</div><div>CN</div>
            </div>
            
            <div class="grid grid-cols-7 gap-y-2 gap-x-2 text-center">
                <!-- Disabled Past days -->
                <?php for($i=1; $i<=4; $i++): ?>
                    <div class="w-9 h-10 md:w-11 md:h-11 flex items-center justify-center rounded-lg bg-stone-200/60 opacity-50 relative mx-auto cursor-not-allowed">
                        <span class="text-stone-400 text-[13px] font-medium z-10"><?php echo $i; ?></span>
                        <div class="absolute w-[60%] h-px bg-stone-400 -rotate-45 transform origin-center"></div>
                    </div>
                <?php endfor; ?>
                <!-- Selected day -->
                <div class="w-9 h-10 md:w-11 md:h-11 flex items-center justify-center rounded-lg bg-stone-500 mx-auto cursor-pointer shadow-sm hover:bg-stone-600 transition-colors">
                    <span class="text-stone-50 text-[13px] font-medium">5</span>
                </div>
                <!-- Available days -->
                <?php for($i=6; $i<=31; $i++): ?>
                    <div class="w-9 h-10 md:w-11 md:h-11 flex items-center justify-center rounded-lg bg-stone-200 hover:bg-stone-300 mx-auto cursor-pointer transition-colors text-stone-600">
                        <span class="text-[13px] font-medium"><?php echo $i; ?></span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <!-- Divider -->
        <div class="hidden md:block w-px bg-stone-200 self-stretch my-2"></div>
        
        <!-- Time Slots Section (Flex remaining width) -->
        <div class="flex-1 w-full p-5 bg-stone-50/80 rounded-b-lg md:rounded-r-lg relative">
            <h4 class="text-stone-800 text-[16px] font-medium mb-4 py-1">Chọn ca workshop</h4>
            <div class="h-px w-full bg-stone-200 mb-4"></div>
            
            <div class="flex flex-col gap-3">
                <!-- Slot Day (Selected) bg-stone-500 from design -->
                <button class="w-full px-4 py-3.5 bg-stone-500 hover:bg-stone-600 rounded-lg flex items-center gap-3 transition-colors text-left group shadow-sm focus:outline-none">
                    <svg class="w-6 h-6 text-stone-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-stone-50 text-[16px] font-medium leading-none">Day</span>
                        <span class="text-stone-50 text-[13px] opacity-80 font-normal leading-none">(9:00 - 11:00)</span>
                    </div>
                </button>
                
                <!-- Slot Night (Available) bg-stone-200 from design -->
                <button class="w-full px-4 py-3.5 bg-stone-200 hover:bg-stone-300 rounded-lg flex items-center gap-3 transition-colors text-left group shadow-sm focus:outline-none">
                    <svg class="w-6 h-6 text-stone-800" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-stone-800 text-[16px] font-medium leading-none">Night</span>
                        <span class="text-stone-700 text-[13px] opacity-80 font-normal leading-none">(19:00 - 21:00)</span>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>
