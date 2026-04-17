<?php
/**
 * Product Card Component
 *
 * @param string $image URL ảnh sản phẩm
 * @param string $brand Tên bộ sưu tập / category
 * @param string $name Tên sản phẩm
 * @param string $price Giá hiện tại
 * @param string $originalPrice Giá gốc (gạch ngang)
 * @param string $discount Ví dụ -30%
 * @param string $class Classes bổ sung
 * @param bool   $plp      Bật style PLP (typography, tỉ lệ 4:5, badge theo spec)
 * @param bool   $soldOut  Hiển thị lớp phủ hết hàng
 * @param string $url      Permalink trang chi tiết; nếu có thì cả thẻ bọc ngoài là thẻ <a>.
 */
// Alias giống template-plugin-design.php (title / old_price).
if ( isset( $args['title'] ) && ! isset( $args['name'] ) ) {
	$args['name'] = $args['title'];
}
if ( isset( $args['old_price'] ) && ! isset( $args['originalPrice'] ) ) {
	$args['originalPrice'] = $args['old_price'];
}

$image         = isset( $args['image'] ) ? $args['image'] : 'https://placehold.co/400x533/f0ece3/8d6a54?text=Product';
$brand         = isset( $args['brand'] ) ? $args['brand'] : 'Bacera';
$name          = isset( $args['name'] ) ? $args['name'] : 'Whisper Cup';
$price         = isset( $args['price'] ) ? $args['price'] : '0 VND';
$originalPrice = isset( $args['originalPrice'] ) ? $args['originalPrice'] : '';
$discount      = isset( $args['discount'] ) ? $args['discount'] : '';
$class         = isset( $args['class'] ) ? $args['class'] : '';
$plp           = ! empty( $args['plp'] );
$soldOut       = ! empty( $args['soldOut'] );
$product_url   = isset( $args['url'] ) ? $args['url'] : '';
$add_to_cart   = ! empty( $args['add_to_cart'] );
$cart_item     = isset( $args['cart_item'] ) && is_array( $args['cart_item'] ) ? $args['cart_item'] : [];
$cart_item_json = $add_to_cart ? wp_json_encode( $cart_item ) : '';

$img_wrap_class = $plp
	? 'self-stretch relative rounded-lg flex flex-col justify-start items-start overflow-hidden bg-neutral-100 aspect-[4/5]'
	: 'self-stretch relative rounded-lg flex flex-col justify-start items-start overflow-hidden bg-neutral-100 aspect-[3/4]';

$badge_wrap = $plp
	? 'p-1.5 bg-white/90 text-[#4A3C31] shadow-sm rounded-md inline-flex justify-center items-center gap-2 backdrop-blur-[2px]'
	: 'p-1 opacity-80 bg-primary-800 rounded inline-flex justify-center items-center gap-2';

$badge_text = $plp
	? 'justify-start text-[11px] font-semibold leading-4 tracking-tight'
	: 'justify-start text-stone-200 text-xs font-medium leading-4 tracking-tight';

$brand_class = $plp
	? 'uppercase tracking-[0.12em] text-[10px] sm:text-[11px] text-[#4A3C31]/65 font-medium leading-4'
	: 'opacity-90 justify-start text-primary-700 text-xs font-normal leading-4';

$name_class = $plp
	? 'self-stretch font-serif font-semibold text-[#4A3C31] text-base sm:text-[17px] leading-snug group-hover:text-accent-600 transition-colors'
	: 'self-stretch justify-start text-primary-700 text-base font-medium leading-5 group-hover:text-accent-500 transition-colors';

$price_class = $plp
	? 'justify-start text-[#4A3C31] text-base font-medium leading-5 tabular-nums'
	: 'justify-start text-primary-700 text-base font-medium leading-4';

$old_class = $plp
	? 'text-[#4A3C31]/35 text-sm font-normal line-through leading-5 tabular-nums'
	: 'opacity-30 justify-start text-primary-800 text-xs font-normal line-through leading-4';

$wrap_class = 'w-full flex flex-col justify-start items-start gap-2 group cursor-pointer ' . $class;
if ( $product_url ) {
	$wrap_class .= ' no-underline';
}
?>
<?php if ( $product_url ) : ?>
<a href="<?php echo esc_url( $product_url ); ?>" class="<?php echo esc_attr( trim( $wrap_class ) ); ?>">
<?php else : ?>
<div class="<?php echo esc_attr( trim( $wrap_class ) ); ?>">
<?php endif; ?>

	<div class="<?php echo esc_attr( $img_wrap_class ); ?>">

		<img src="<?php echo esc_url( $image ); ?>"
			class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 <?php echo $soldOut ? 'opacity-[0.55]' : ''; ?>"
			alt="<?php echo esc_attr( $name ); ?>"
			loading="lazy" />

		<div class="absolute inset-0 flex flex-col justify-start items-center gap-2 pointer-events-none">
			<div class="w-full flex-1 bg-gradient-to-b from-black/0 to-black/15"></div>
		</div>

		<div class="w-full h-16 absolute bottom-0 left-0 overflow-hidden z-10">
			<?php if ( $add_to_cart ) : ?>
			<button
				type="button"
				class="bacera-shop-add-cart-btn w-[calc(100%-24px)] p-4 absolute left-[12px] top-[60px] opacity-0 group-hover:top-0 group-hover:opacity-100 bg-accent-500 rounded-lg inline-flex justify-center items-center gap-2 transition-all duration-300"
				data-cart-item="<?php echo esc_attr( $cart_item_json ); ?>"
			>
				<span class="text-stone-100 text-base font-medium leading-5"><?php esc_html_e( 'Add to cart', 'bacera' ); ?></span>
			</button>
			<?php elseif ( $product_url ) : ?>
			<span class="w-[calc(100%-24px)] p-4 absolute left-[12px] top-[60px] opacity-0 group-hover:top-0 group-hover:opacity-100 bg-accent-500 rounded-lg inline-flex justify-center items-center gap-2 transition-all duration-300 pointer-events-none">
				<span class="text-stone-100 text-base font-medium leading-5"><?php esc_html_e( 'Add to cart', 'bacera' ); ?></span>
			</span>
			<?php else : ?>
			<button type="button" class="w-[calc(100%-24px)] p-4 absolute left-[12px] top-[60px] opacity-0 group-hover:top-0 group-hover:opacity-100 bg-accent-500 rounded-lg inline-flex justify-center items-center gap-2 transition-all duration-300">
				<span class="text-stone-100 text-base font-medium leading-5"><?php esc_html_e( 'Add to cart', 'bacera' ); ?></span>
			</button>
			<?php endif; ?>
		</div>

		<?php if ( $discount ) : ?>
		<div class="w-full p-3 absolute left-0 top-0 flex flex-col justify-start items-start gap-2 z-10">
			<div class="<?php echo esc_attr( $badge_wrap ); ?>">
				<div class="<?php echo esc_attr( $badge_text ); ?>">
					<?php echo esc_html( $discount ); ?>
				</div>
			</div>
		</div>
		<?php endif; ?>

		<?php if ( $soldOut ) : ?>
		<div class="absolute inset-0 z-[5] flex items-center justify-center bg-[#F9F7F2]/55 pointer-events-none">
			<span class="px-3 py-1.5 rounded-md bg-[#4A3C31]/90 text-white text-xs font-semibold uppercase tracking-widest"><?php esc_html_e( 'Sold out', 'bacera' ); ?></span>
		</div>
		<?php endif; ?>
	</div>

	<div class="self-stretch flex justify-start items-start mt-0.5">
		<div class="w-full flex flex-col justify-start items-start gap-1 min-w-0">
			<div class="self-stretch flex flex-col justify-start items-start gap-0.5 min-w-0">
				<div class="<?php echo esc_attr( $brand_class ); ?>"><?php echo esc_html( $brand ); ?></div>
				<h3 class="<?php echo esc_attr( $name_class ); ?>">
					<?php echo esc_html( $name ); ?>
				</h3>
			</div>
			<div class="self-stretch flex flex-wrap items-baseline gap-x-2 gap-y-0.5 mt-0.5">
				<div class="<?php echo esc_attr( $price_class ); ?>"><?php echo esc_html( $price ); ?></div>
				<?php if ( $originalPrice ) : ?>
				<div class="<?php echo esc_attr( $old_class ); ?>"><?php echo esc_html( $originalPrice ); ?></div>
				<?php endif; ?>
			</div>
		</div>
	</div>
<?php if ( $product_url ) : ?>
</a>
<?php else : ?>
</div>
<?php endif; ?>
