<?php
/**
 * Product Card Component
 * @param string $image URL ảnh sản phẩm
 * @param string $brand Thương hiệu
 * @param string $name Tên sản phẩm
 * @param string $price Giá khuyến mãi
 * @param string $originalPrice Giá gốc
 * @param string $discount Phần trăm giảm giá
 * @param string $class Thêm Class
 */
$image = isset($args['image']) ? $args['image'] : 'https://placehold.co/400x533/f0ece3/8d6a54?text=Product';
$brand = isset($args['brand']) ? $args['brand'] : 'Whispers of Clay';
$name = isset($args['name']) ? $args['name'] : 'Whisper Cup';
$price = isset($args['price']) ? $args['price'] : '$28';
$originalPrice = isset($args['originalPrice']) ? $args['originalPrice'] : '$40';
$discount = isset($args['discount']) ? $args['discount'] : '-30%';
$class = isset($args['class']) ? $args['class'] : '';
?>
<div class="w-full inline-flex flex-col justify-start items-start gap-2 group cursor-pointer <?php echo esc_attr($class); ?>">
    
    <!-- Image block -->
    <div class="self-stretch relative rounded-lg flex flex-col justify-start items-start overflow-hidden bg-neutral-100 aspect-[3/4]">
        
        <!-- Image itself -->
        <img src="<?php echo esc_url($image); ?>" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" alt="<?php echo esc_attr($name); ?>"/>

        <!-- Gradient overlay: bg-gradient-to-b from-black/0 to-black/20 -->
        <div class="absolute inset-0 flex flex-col justify-start items-center gap-2 pointer-events-none">
            <div class="w-full flex-1 bg-gradient-to-b from-black/0 to-black/20"></div>
        </div>
        
        <!-- Add to cart Hover slide-up (Matching State 2 vs State 1 in Figma wrapper) -->
        <div class="w-full h-16 absolute bottom-0 left-0 overflow-hidden z-10">
            <button class="w-[calc(100%-24px)] p-4 absolute left-[12px] top-[60px] opacity-0 group-hover:top-0 group-hover:opacity-100 bg-accent-500 rounded-lg inline-flex justify-center items-center gap-2 transition-all duration-300">
                <div class="justify-start text-stone-200 text-base font-medium leading-5">Add to cart</div>
            </button>
        </div>

        <!-- Discount Badge -->
        <?php if ($discount): ?>
        <div class="w-full p-3 absolute left-0 top-0 flex flex-col justify-start items-start gap-2 z-10">
            <div class="p-1 opacity-70 bg-stone-800 rounded inline-flex justify-center items-center gap-2">
                <div class="justify-start text-stone-200 text-xs font-medium leading-4 tracking-tight">
                    <?php echo esc_html($discount); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Info Block -->
    <div class="self-stretch inline-flex justify-start items-start mt-0.5">
        <div class="w-full inline-flex flex-col justify-start items-start gap-1">
            <div class="self-stretch flex flex-col justify-start items-start">
                <div class="opacity-90 justify-start text-stone-700 text-xs font-normal leading-4"><?php echo esc_html($brand); ?></div>
                <h3 class="self-stretch justify-start text-stone-700 text-base font-medium leading-5 group-hover:text-accent-500 transition-colors">
                    <?php echo esc_html($name); ?>
                </h3>
            </div>
            <div class="self-stretch inline-flex justify-start items-center gap-1 mt-0.5">
                <div class="justify-start text-stone-700 text-base font-medium leading-4"><?php echo esc_html($price); ?></div>
                <?php if ($originalPrice): ?>
                <div class="opacity-30 justify-start text-stone-800 text-xs font-normal line-through leading-4"><?php echo esc_html($originalPrice); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>
