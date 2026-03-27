<?php
$label = isset($args['label']) ? $args['label'] : 'Select Option';
$value = isset($args['value']) ? $args['value'] : 'Select...';
$options = isset($args['options']) ? $args['options'] : ['Item 1', 'Item 2', 'Item 3', 'Item 4'];
$name = isset($args['name']) ? $args['name'] : uniqid('select_');
$class = isset($args['class']) ? $args['class'] : '';
$state = isset($args['state']) ? $args['state'] : 'default'; // default, focus, filled
$selected_index = isset($args['selected_index']) ? $args['selected_index'] : 1; // Simulated selected

// Base container matching Figma Select Box
$base_classes = "w-full h-16 px-4 py-6 rounded-lg flex justify-between items-center transition-all bg-white font-sans cursor-pointer focus:outline-none";

// State overrides
switch ($state) {
    case 'focus':
        $box_classes = "ring-2 ring-inset ring-stone-700";
        break;
    case 'filled':
        $box_classes = "ring-1 ring-inset ring-stone-700";
        break;
    default:
        $box_classes = "ring-1 ring-inset ring-stone-300 hover:ring-stone-400";
        break;
}
?>
<!-- Alpine Component context -->
<div class="relative w-full <?php echo esc_attr($class); ?>" 
     x-data="{ open: false, selectedIndex: <?php echo esc_js($selected_index); ?>, selectedText: '<?php echo esc_js($value); ?>' }"
     @click.outside="open = false">
    
    <!-- Simulated native select hidden for form submission -->
    <select name="<?php echo esc_attr($name); ?>" class="hidden" x-model="selectedText">
        <?php foreach($options as $opt): ?>
            <option value="<?php echo esc_attr($opt); ?>"><?php echo esc_html($opt); ?></option>
        <?php endforeach; ?>
    </select>
    
    <!-- Visual Select Trigger -->
    <div @click="open = !open" 
         class="<?php echo esc_attr($base_classes . ' ' . $box_classes); ?>"
         :class="open ? 'ring-2 ring-inset ring-stone-700' : ''">
        <div class="flex-1 flex flex-col justify-center items-start gap-0.5 pointer-events-none">
            <span class="opacity-70 text-stone-800 text-[12px] font-normal leading-none tracking-tight"><?php echo esc_html($label); ?></span>
            <span class="text-stone-800 text-[16px] font-medium leading-[1.2] mt-0.5" x-text="selectedText"></span>
        </div>
        <div class="w-6 h-6 flex items-center justify-center text-stone-600 transition-transform duration-300 transform" :class="open ? 'rotate-180' : ''">
            <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </div>
    </div>

    <!-- Dropdown Options List -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         style="display: none;"
         class="absolute top-[72px] left-0 w-full py-1 bg-stone-50 rounded-lg shadow-[0px_0px_16px_0px_rgba(0,0,0,0.08)] flex flex-col z-[60]">
        <?php foreach($options as $index => $opt): ?>
        <div class="w-full px-2 py-1">
            <button type="button" 
                    @click.stop="selectedIndex = <?php echo $index; ?>; selectedText = '<?php echo esc_js($opt); ?>'; open = false;"
                    class="w-full px-3 py-2.5 rounded-md flex justify-between items-center transition-colors"
                    :class="selectedIndex === <?php echo $index; ?> ? 'bg-stone-200' : 'bg-transparent hover:bg-stone-200'">
                <span class="flex-1 text-left opacity-80 text-stone-700 text-[16px] font-normal leading-none">
                    <?php echo esc_html($opt); ?>
                </span>
                <!-- Checkmark indicating selection via Alpine conditionally -->
                <template x-if="selectedIndex === <?php echo $index; ?>">
                    <svg class="w-4 h-4 text-stone-800 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </template>
            </button>
        </div>
        <?php endforeach; ?>
    </div>
</div>
