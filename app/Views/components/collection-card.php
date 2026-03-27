<?php
$image = isset($args['image']) ? $args['image'] : 'https://placehold.co/800x800/eae3d1/8d6a54?text=Whispers+of+Clay';
$title = isset($args['title']) ? $args['title'] : 'Whispers of Clay';
$description = isset($args['description']) ? $args['description'] : 'Soft forms. Gentle hues. Pieces shaped by quiet hands and slow mornings.';
$ratio = isset($args['ratio']) ? $args['ratio'] : '1/1';
$class = isset($args['class']) ? $args['class'] : '';
$href = isset($args['href']) ? $args['href'] : null;

$ratio_map = [
    '16/9' => 'aspect-[16/9]',
    '4/3' => 'aspect-[4/3]',
    '1/1' => 'aspect-square',
    '5/4' => 'aspect-[5/4]',
    '3/4' => 'aspect-[3/4]',
    '9/16' => 'aspect-[9/16]',
    'auto' => 'aspect-auto',
];
$ratio_class = $ratio_map[$ratio] ?? 'aspect-square';

$tag = $href ? 'a' : 'div';
$href_attr = $href ? 'href="' . esc_url($href) . '"' : '';
?>
<<?php echo $tag; ?> <?php echo $href_attr; ?> 
    class="flex flex-col items-start justify-end gap-2 p-8 relative rounded-lg overflow-hidden w-full group cursor-pointer hover:-translate-y-1 transition-transform duration-300 shadow-sm hover:shadow-xl bg-stone-100 <?php echo esc_attr($ratio_class); ?> <?php echo esc_attr($class); ?>"
>
    <!-- Background Image -->
    <!-- Removed negative z-index to fix stacking context bug. Using normal DOM flow z-0 -> z-10 -->
    <img 
        src="<?php echo esc_url($image); ?>" 
        alt="<?php echo esc_attr($title); ?>" 
        class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 z-0"
    >

    <!-- Overlay Gradient (Matches newest JSX: bg-gradient-to-bl from-black/0 to-black/30) -->
    <div class="absolute inset-0 bg-gradient-to-bl from-black/0 to-black/30 pointer-events-none z-10 transition-opacity duration-300 group-hover:from-black/10 group-hover:to-black/40"></div>

    <!-- Content Block -->
    <div class="flex flex-col justify-start items-start gap-2 relative z-20 w-full hover:pointer-events-auto">
        <h2 class="self-stretch justify-start text-stone-200 text-2xl md:text-[32px] font-serif font-normal leading-8 drop-shadow-sm group-hover:text-white transition-colors">
            <?php echo esc_html($title); ?>
        </h2>

        <?php if ($description): ?>
        <p class="w-full max-w-[320px] opacity-75 justify-start text-stone-200 text-[16px] font-sans font-normal leading-4 drop-shadow-sm">
            <?php echo esc_html($description); ?>
        </p>
        <?php endif; ?>
    </div>
</<?php echo $tag; ?>>
