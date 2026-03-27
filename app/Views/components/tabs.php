<?php
$tabs = isset($args['tabs']) ? $args['tabs'] : ['Tab-item 1', 'Tab-item 2', 'Tab-item 3', 'Tab-item 4', 'Tab-item 5'];
$active = isset($args['active']) ? $args['active'] : 0;
?>
<div class="w-full flex flex-col mt-4" x-data="{ activeTab: <?php echo esc_attr($active); ?> }">
    <!-- Tab Links Container -->
    <nav class="flex gap-4 overflow-x-auto hide-scrollbar z-10" aria-label="Tabs">
        <?php foreach($tabs as $index => $tab): ?>
            <a href="#" @click.prevent="activeTab = <?php echo $index; ?>" class="cursor-pointer inline-flex flex-col justify-center items-center gap-2 group transition-opacity font-sans pb-[2px]">
                <!-- Text -->
                <span class="text-[16px] leading-[1.2] transition-all"
                      :class="activeTab === <?php echo $index; ?> ? 'text-stone-700 font-medium' : 'text-stone-700 opacity-50 font-normal group-hover:opacity-80'">
                    <?php echo esc_html($tab); ?>
                </span>
                
                <!-- Local Underline Effect -->
                <div class="w-full h-[2px] transition-colors"
                     :class="activeTab === <?php echo $index; ?> ? 'bg-stone-700' : 'bg-transparent group-hover:bg-stone-400'"></div>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <!-- Global Divider line matching the 2px thickness (placed directly under the local underlines) -->
    <!-- Negative margin pulls it up to visually overlap the transparent/colored underlines -->
    <div class="w-full h-[2px] bg-stone-300 -mt-[4px] z-0"></div>
</div>
