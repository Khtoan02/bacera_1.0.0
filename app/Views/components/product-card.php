<?php
/**
 * Product Card Component — Bacera redesign
 *
 * @param string        $image         URL ảnh sản phẩm
 * @param string        $brand         Tên bộ sưu tập / category
 * @param string        $name          Tên sản phẩm (alias: title)
 * @param string        $price         Giá hiện tại (formatted)
 * @param string        $originalPrice Giá gốc (alias: old_price)
 * @param string|false  $discount      Ví dụ '-18%'
 * @param string        $class         Classes bổ sung
 * @param bool          $plp           Style PLP
 * @param bool          $soldOut       Sold-out overlay
 * @param string        $url           Permalink chi tiết
 * @param bool          $add_to_cart   Hiện nút Add to cart
 * @param array         $cart_item     Dữ liệu giỏ hàng
 * @param int           $rating        Sao trung bình (0–5)
 * @param int           $review_count  Số lượt đánh giá
 */

// Aliases.
if ( isset( $args['title'] ) && ! isset( $args['name'] ) ) {
	$args['name'] = $args['title'];
}
if ( isset( $args['old_price'] ) && ! isset( $args['originalPrice'] ) ) {
	$args['originalPrice'] = $args['old_price'];
}

$image         = isset( $args['image'] ) ? $args['image'] : 'https://placehold.co/400x533/f0ece3/8d6a54?text=Bacera';
$brand         = isset( $args['brand'] ) ? $args['brand'] : 'Bacera';
$name          = isset( $args['name'] ) ? $args['name'] : 'Product';
$price         = isset( $args['price'] ) ? $args['price'] : '';
$originalPrice = isset( $args['originalPrice'] ) ? $args['originalPrice'] : '';
$discount      = isset( $args['discount'] ) ? $args['discount'] : false;
$extra_class   = isset( $args['class'] ) ? $args['class'] : '';
$soldOut       = ! empty( $args['soldOut'] );
$product_url   = isset( $args['url'] ) ? $args['url'] : '';
$add_to_cart   = ! empty( $args['add_to_cart'] );
$cart_item     = isset( $args['cart_item'] ) && is_array( $args['cart_item'] ) ? $args['cart_item'] : [];
$cart_item_json = $add_to_cart ? wp_json_encode( $cart_item ) : '';
$rating        = isset( $args['rating'] ) ? (int) $args['rating'] : 0;
$review_count  = isset( $args['review_count'] ) ? (int) $args['review_count'] : 0;

$tag = $product_url ? 'a' : 'div';
$tag_attrs = $product_url
	? 'href="' . esc_url( $product_url ) . '" class="bpc-card ' . esc_attr( $extra_class ) . '"'
	: 'class="bpc-card ' . esc_attr( $extra_class ) . '"';
?>
<style>
.bpc-card {
	display: flex; flex-direction: column;
	background: #fff;
	border-radius: 14px;
	overflow: visible;
	text-decoration: none; color: inherit;
	cursor: pointer;
	transition: transform 0.3s cubic-bezier(0.4,0,0.2,1), box-shadow 0.3s cubic-bezier(0.4,0,0.2,1);
	box-shadow: 0 1px 3px rgba(42,31,23,0.07), 0 1px 2px rgba(42,31,23,0.04);
	position: relative;
}
.bpc-card:hover {
	transform: translateY(-4px);
	box-shadow: 0 8px 24px rgba(42,31,23,0.10), 0 3px 8px rgba(42,31,23,0.06);
}
.bpc-img-wrap {
	position: relative; overflow: hidden;
	border-radius: 14px 14px 0 0;
	aspect-ratio: 4/5;
	background: #F2EDE5;
}
.bpc-img {
	width: 100%; height: 100%; object-fit: cover;
	transition: transform 0.55s cubic-bezier(0.4,0,0.2,1);
}
.bpc-card:hover .bpc-img { transform: scale(1.06); }
.bpc-overlay-gradient {
	position: absolute; inset: 0;
	background: linear-gradient(to bottom, transparent 55%, rgba(42,31,23,0.12) 100%);
	pointer-events: none;
}
/* Badges */
.bpc-badge {
	position: absolute; left: 10px; top: 10px; z-index: 3;
	font-size: 0.65rem; font-weight: 700; letter-spacing: 0.06em;
	padding: 4px 9px; border-radius: 6px;
	background: #fff; color: #2A1F17;
	box-shadow: 0 1px 4px rgba(42,31,23,0.12);
}
.bpc-badge-sale { background: #C06B3A; color: #fff; }
/* Quick-add CTA */
.bpc-quick-add {
	position: absolute; bottom: 0; left: 0; right: 0; z-index: 4;
	padding: 0.65rem 1rem; border: none;
	background: #4d3d32; color: #fff;
	font-family: inherit; font-size: 0.8rem; font-weight: 500; letter-spacing: 0.03em;
	cursor: pointer; text-align: center;
	opacity: 0; transform: translateY(6px);
	transition: opacity 0.22s ease, transform 0.22s ease, background 0.15s;
	width: 100%;
}
.bpc-card:hover .bpc-quick-add { opacity: 1; transform: translateY(0); }
.bpc-quick-add:hover { background: #6b5344; }
/* Sold-out overlay */
.bpc-soldout-overlay {
	position: absolute; inset: 0; z-index: 5;
	background: rgba(248,244,238,0.55);
	display: flex; align-items: center; justify-content: center;
	pointer-events: none;
}
.bpc-soldout-label {
	background: rgba(42,31,23,0.85); color: #fff;
	font-size: 0.65rem; font-weight: 700; letter-spacing: 0.12em;
	text-transform: uppercase; padding: 6px 14px; border-radius: 6px;
}
/* Body */
.bpc-body {
	padding: 0.85rem 1rem 1rem;
	border-top: 1px solid #EDE6DD;
	display: flex; flex-direction: column; gap: 0.2rem;
}
.bpc-collection {
	font-size: 0.63rem; font-weight: 700; letter-spacing: 0.16em;
	text-transform: uppercase; color: #A9846B;
}
.bpc-name {
	font-family: 'Cormorant Garamond', Georgia, serif;
	font-size: 1.05rem; font-weight: 400; line-height: 1.3;
	color: #2A1F17;
	transition: color 0.15s;
	display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.bpc-card:hover .bpc-name { color: #C06B3A; }
.bpc-pricing { display: flex; align-items: baseline; gap: 0.45rem; margin-top: 0.25rem; }
.bpc-price { font-size: 0.875rem; font-weight: 600; color: #2A1F17; }
.bpc-old-price { font-size: 0.775rem; color: #9A8478; text-decoration: line-through; }
.bpc-rating { display: flex; align-items: center; gap: 0.2rem; margin-top: 0.25rem; }
.bpc-stars { font-size: 0.65rem; color: #C06B3A; letter-spacing: -0.04em; }
.bpc-review-count { font-size: 0.68rem; color: #9A8478; }
</style>

<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo $tag_attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?>>

	<div class="bpc-img-wrap">
		<img
			src="<?php echo esc_url( $image ); ?>"
			alt="<?php echo esc_attr( $name ); ?>"
			class="bpc-img"
			loading="lazy"
		/>
		<div class="bpc-overlay-gradient"></div>

		<?php if ( $discount ) : ?>
		<span class="bpc-badge bpc-badge-sale"><?php echo esc_html( $discount ); ?></span>
		<?php endif; ?>

		<?php if ( $soldOut ) : ?>
		<div class="bpc-soldout-overlay">
			<span class="bpc-soldout-label"><?php esc_html_e( 'Sold out', 'bacera' ); ?></span>
		</div>
		<?php else : ?>

			<?php if ( $add_to_cart ) : ?>
			<button
				type="button"
				class="bpc-quick-add bacera-shop-add-cart-btn"
				data-cart-item="<?php echo esc_attr( $cart_item_json ); ?>"
				aria-label="<?php echo esc_attr( sprintf( __( 'Add %s to cart', 'bacera' ), $name ) ); ?>"
				onclick="event.preventDefault(); event.stopPropagation();"
			><?php esc_html_e( 'Add to cart', 'bacera' ); ?></button>
			<?php elseif ( $product_url ) : ?>
			<span class="bpc-quick-add" aria-hidden="true"><?php esc_html_e( 'View product', 'bacera' ); ?></span>
			<?php endif; ?>

		<?php endif; ?>
	</div>

	<div class="bpc-body">
		<?php if ( $brand !== '' ) : ?>
		<div class="bpc-collection"><?php echo esc_html( $brand ); ?></div>
		<?php endif; ?>
		<h3 class="bpc-name"><?php echo esc_html( $name ); ?></h3>
		<?php if ( $price !== '' ) : ?>
		<div class="bpc-pricing">
			<span class="bpc-price"><?php echo esc_html( $price ); ?></span>
			<?php if ( $originalPrice !== '' ) : ?>
			<span class="bpc-old-price"><?php echo esc_html( $originalPrice ); ?></span>
			<?php endif; ?>
		</div>
		<?php endif; ?>
		<?php if ( $rating > 0 && $review_count > 0 ) : ?>
		<div class="bpc-rating" aria-label="<?php echo esc_attr( sprintf( __( '%s/5 from %d reviews', 'bacera' ), number_format( $rating, 1 ), $review_count ) ); ?>">
			<span class="bpc-stars" aria-hidden="true">
				<?php for ( $si = 1; $si <= 5; $si++ ) { echo $si <= $rating ? '★' : '☆'; } ?>
			</span>
			<span class="bpc-review-count">(<?php echo esc_html( number_format_i18n( $review_count ) ); ?>)</span>
		</div>
		<?php endif; ?>
	</div>

</<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
