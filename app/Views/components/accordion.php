<?php
/**
 * Accordion Component (Using Alpine.js)
 * 
 * Usage:
 * get_template_part('app/Views/components/accordion', null, [
 *     'items' => [
 *         [
 *             'title' => 'What is your return policy?',
 *             'content' => 'You can return any item within 30 days of purchase.'
 *         ],
 *         [
 *             'title' => 'Do you ship internationally?',
 *             'content' => 'Yes, we ship worldwide.'
 *         ]
 *     ]
 * ]);
 */

$items = $args['items'] ?? [];
if ( empty( $items ) ) {
    return;
}
?>
<div class="space-y-4" x-data="{ active: null }">
    <?php foreach ( $items as $index => $item ) : ?>
    <div class="border border-accent/20 rounded-xl bg-white overflow-hidden transition-all duration-300"
         :class="active === <?php echo $index; ?> ? 'shadow-md border-terracotta/30' : 'hover:border-accent/40'">
        
        <button @click="active = active === <?php echo $index; ?> ? null : <?php echo $index; ?>"
                class="w-full flex items-center justify-between px-6 py-5 text-left focus:outline-none">
            <span class="font-serif text-lg text-textmain font-medium pr-4"
                  :class="active === <?php echo $index; ?> ? 'text-terracotta' : ''">
                <?php echo esc_html( $item['title'] ); ?>
            </span>
            <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 transition-transform duration-300"
                  :class="active === <?php echo $index; ?> ? 'bg-terracotta/10 text-terracotta rotate-180' : 'bg-bgtheme text-textmuted'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </span>
        </button>
        
        <div x-show="active === <?php echo $index; ?>" 
             x-collapse
             x-cloak
             class="px-6 pb-6 text-textmuted text-[15px] leading-relaxed">
            <div class="pt-2 border-t border-accent/10">
                <?php echo wp_kses_post( $item['content'] ); ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<style>
    [x-cloak] { display: none !important; }
</style>
