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
    class="flex flex-col items-start justify-end gap-2 p-8 relative rounded-lg overflow-hidden w-full group cursor-pointer hover:-translate-y-1 transition-transform duration-300 shadow-sm hover:shadow-xl <?php echo esc_attr($ratio_class); ?> <?php echo esc_attr($class); ?>"
>
    <!-- Image -->
    <img 
        src="<?php echo esc_url($image); ?>" 
        alt="<?php echo esc_attr($title); ?>" 
        class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 -z-20"
    >

    <!-- Overlay Gradient (Exact match to JSX) -->
    <div class="absolute inset-0 bg-[linear-gradient(253deg,rgba(0,0,0,0)_0%,rgba(0,0,0,0.3)_100%)] pointer-events-none -z-10 group-hover:opacity-90 transition-opacity duration-300"></div>

    <!-- Content -->
    <div class="inline-flex flex-col items-start gap-2 relative flex-[0_0_auto] z-10">
        <h2 class="relative self-stretch mt-[-1.00px] text-white text-[24px] md:text-[32px] font-bold leading-tight tracking-tight drop-shadow-sm group-hover:text-gray-100 transition-colors">
            <?php echo esc_html($title); ?>
        </h2>

        <?php if ($description): ?>
        <p class="relative w-full max-w-[316px] opacity-75 text-white text-[15px] font-medium leading-relaxed drop-shadow-sm">
            <?php echo esc_html($description); ?>
        </p>
        <?php endif; ?>
    </div>
</<?php echo $tag; ?>>
