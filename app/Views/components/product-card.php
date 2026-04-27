<?php
/**
 * Product Card Component — Bacera redesign v2
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
	? 'href="' . esc_url( $product_url ) . '" class="bpc2-card ' . esc_attr( $extra_class ) . '"'
	: 'class="bpc2-card ' . esc_attr( $extra_class ) . '"';
?>
<style>
/* ─── Product Card v2 ─── */
.bpc2-card {
	display: flex;
	flex-direction: column;
	position: relative;
	text-decoration: none;
	color: inherit;
	cursor: pointer;
	background: transparent;
	border-radius: 20px;
	overflow: hidden;
	transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
	outline: none;
}
.bpc2-card:hover {
	transform: translateY(-6px);
}
.bpc2-card:focus-visible {
	box-shadow: 0 0 0 3px rgba(192, 107, 58, 0.45);
}

/* ── Image zone ── */
.bpc2-img-wrap {
	position: relative;
	overflow: hidden;
	border-radius: 20px;
	aspect-ratio: 3/4;
	background: #EDE6DD;
	flex-shrink: 0;
}
.bpc2-img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	display: block;
	transform-origin: center center;
	transition: transform 0.7s cubic-bezier(0.4, 0, 0.2, 1);
	will-change: transform;
}
.bpc2-card:hover .bpc2-img {
	transform: scale(1.08);
}

/* Gradient overlay — always subtle, stronger on hover */
.bpc2-gradient {
	position: absolute;
	inset: 0;
	background: linear-gradient(
		to bottom,
		rgba(42, 31, 23, 0) 40%,
		rgba(42, 31, 23, 0.55) 100%
	);
	opacity: 0.6;
	transition: opacity 0.4s ease;
	pointer-events: none;
	z-index: 1;
}
.bpc2-card:hover .bpc2-gradient {
	opacity: 1;
}

/* ── Badges ── */
.bpc2-badges {
	position: absolute;
	top: 12px;
	left: 12px;
	z-index: 4;
	display: flex;
	flex-direction: column;
	gap: 5px;
}
.bpc2-badge {
	display: inline-block;
	font-size: 0.6rem;
	font-weight: 800;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	padding: 4px 10px;
	border-radius: 20px;
	backdrop-filter: blur(8px);
	-webkit-backdrop-filter: blur(8px);
	background: rgba(255, 255, 255, 0.92);
	color: #2A1F17;
	box-shadow: 0 2px 8px rgba(42, 31, 23, 0.14);
	line-height: 1.4;
}
.bpc2-badge-sale {
	background: #C06B3A;
	color: #fff;
}
.bpc2-badge-soldout {
	background: rgba(42, 31, 23, 0.80);
	color: #F8F4EE;
}

/* ── Wishlist (top-right) ── */
.bpc2-wishlist {
	position: absolute;
	top: 12px;
	right: 12px;
	z-index: 5;
	width: 34px;
	height: 34px;
	border-radius: 50%;
	border: none;
	background: rgba(255, 255, 255, 0.82);
	backdrop-filter: blur(8px);
	-webkit-backdrop-filter: blur(8px);
	display: flex;
	align-items: center;
	justify-content: center;
	cursor: pointer;
	box-shadow: 0 2px 8px rgba(42, 31, 23, 0.14);
	opacity: 0;
	transform: scale(0.8) translateY(-4px);
	transition: opacity 0.25s ease, transform 0.25s cubic-bezier(0.34,1.56,0.64,1), background 0.2s;
}
.bpc2-card:hover .bpc2-wishlist {
	opacity: 1;
	transform: scale(1) translateY(0);
}
.bpc2-wishlist:hover {
	background: #fff;
}
.bpc2-wishlist svg {
	width: 15px;
	height: 15px;
	color: #6B5344;
	transition: color 0.2s;
}
.bpc2-wishlist:hover svg {
	color: #C06B3A;
}
.bpc2-wishlist.is-active svg {
	fill: #C06B3A;
	color: #C06B3A;
}

/* ── CTA — slides up from bottom of image ── */
.bpc2-cta-wrap {
	position: absolute;
	bottom: 0;
	left: 0;
	right: 0;
	z-index: 5;
	padding: 12px;
	transform: translateY(100%);
	transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.bpc2-card:hover .bpc2-cta-wrap {
	transform: translateY(0);
}
.bpc2-cta-btn {
	width: 100%;
	border: none;
	border-radius: 12px;
	padding: 0.7rem 1rem;
	font-family: inherit;
	font-size: 0.8rem;
	font-weight: 600;
	letter-spacing: 0.04em;
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 0.45rem;
	backdrop-filter: blur(12px);
	-webkit-backdrop-filter: blur(12px);
	background: rgba(255, 255, 255, 0.92);
	color: #2A1F17;
	box-shadow: 0 4px 16px rgba(42, 31, 23, 0.18);
	transition: background 0.2s ease, color 0.2s ease, transform 0.15s;
}
.bpc2-cta-btn:hover {
	background: #2A1F17;
	color: #F8F4EE;
}
.bpc2-cta-btn:active {
	transform: scale(0.97);
}
.bpc2-cta-btn svg {
	flex-shrink: 0;
}

/* ── Body ── */
.bpc2-body {
	padding: 0.9rem 0.25rem 0.25rem;
	display: flex;
	flex-direction: column;
	gap: 0;
}
.bpc2-meta-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 0.25rem;
}
.bpc2-brand {
	font-size: 0.6rem;
	font-weight: 700;
	letter-spacing: 0.16em;
	text-transform: uppercase;
	color: #A9846B;
	line-height: 1;
}
.bpc2-rating-inline {
	display: flex;
	align-items: center;
	gap: 0.2rem;
}
.bpc2-stars-s {
	font-size: 0.6rem;
	color: #C06B3A;
	letter-spacing: -0.05em;
	line-height: 1;
}
.bpc2-review-s {
	font-size: 0.6rem;
	color: #9A8478;
	line-height: 1;
}
.bpc2-name {
	font-family: 'Bricolage Grotesque', 'Cormorant Garamond', Georgia, serif;
	font-size: 0.95rem;
	font-weight: 500;
	line-height: 1.35;
	color: #2A1F17;
	transition: color 0.2s;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	overflow: hidden;
	margin-bottom: 0.4rem;
}
.bpc2-card:hover .bpc2-name {
	color: #6B5344;
}
.bpc2-pricing {
	display: flex;
	align-items: baseline;
	gap: 0.4rem;
}
.bpc2-price {
	font-size: 0.9rem;
	font-weight: 700;
	color: #2A1F17;
	letter-spacing: -0.01em;
}
.bpc2-old-price {
	font-size: 0.75rem;
	color: #B09A8E;
	text-decoration: line-through;
}
/* Sold-out dimming on image */
.bpc2-soldout-dim {
	position: absolute;
	inset: 0;
	background: rgba(248, 244, 238, 0.50);
	z-index: 2;
	pointer-events: none;
}
/* View product link style */
.bpc2-cta-btn-view {
	background: rgba(42, 31, 23, 0.75);
	color: #F8F4EE;
}
.bpc2-cta-btn-view:hover {
	background: #2A1F17;
}
</style>

<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo $tag_attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?>>

	<div class="bpc2-img-wrap">
		<img
			src="<?php echo esc_url( $image ); ?>"
			alt="<?php echo esc_attr( $name ); ?>"
			class="bpc2-img"
			loading="lazy"
		/>

		<?php if ( $soldOut ) : ?>
		<div class="bpc2-soldout-dim" aria-hidden="true"></div>
		<?php endif; ?>

		<div class="bpc2-gradient" aria-hidden="true"></div>

		<?php /* Badges */ ?>
		<div class="bpc2-badges">
			<?php if ( $soldOut ) : ?>
			<span class="bpc2-badge bpc2-badge-soldout"><?php esc_html_e( 'Sold out', 'bacera' ); ?></span>
			<?php elseif ( $discount ) : ?>
			<span class="bpc2-badge bpc2-badge-sale"><?php echo esc_html( $discount ); ?></span>
			<?php endif; ?>
		</div>

		<?php /* Wishlist button */ ?>
		<button
			type="button"
			class="bpc2-wishlist"
			aria-label="<?php echo esc_attr( sprintf( __( 'Add %s to wishlist', 'bacera' ), $name ) ); ?>"
			onclick="event.preventDefault(); event.stopPropagation(); this.classList.toggle('is-active');"
		>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
			</svg>
		</button>

		<?php /* CTA slides up from bottom */ ?>
		<?php if ( ! $soldOut ) : ?>
		<div class="bpc2-cta-wrap">
			<?php if ( $add_to_cart ) : ?>
			<button
				type="button"
				class="bpc2-cta-btn bacera-shop-add-cart-btn"
				data-cart-item="<?php echo esc_attr( $cart_item_json ); ?>"
				aria-label="<?php echo esc_attr( sprintf( __( 'Add %s to cart', 'bacera' ), $name ) ); ?>"
				onclick="event.preventDefault(); event.stopPropagation();"
			>
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
				<?php esc_html_e( 'Add to cart', 'bacera' ); ?>
			</button>
			<?php elseif ( $product_url ) : ?>
			<span class="bpc2-cta-btn bpc2-cta-btn-view" aria-hidden="true">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
				<?php esc_html_e( 'View product', 'bacera' ); ?>
			</span>
			<?php endif; ?>
		</div>
		<?php endif; ?>
	</div>

	<div class="bpc2-body">
		<div class="bpc2-meta-row">
			<?php if ( $brand !== '' ) : ?>
			<span class="bpc2-brand"><?php echo esc_html( $brand ); ?></span>
			<?php endif; ?>
			<?php if ( $rating > 0 && $review_count > 0 ) : ?>
			<div class="bpc2-rating-inline" aria-label="<?php echo esc_attr( sprintf( __( '%s/5 from %d reviews', 'bacera' ), number_format( $rating, 1 ), $review_count ) ); ?>">
				<span class="bpc2-stars-s" aria-hidden="true"><?php for ( $si = 1; $si <= 5; $si++ ) { echo $si <= $rating ? '★' : '☆'; } ?></span>
				<span class="bpc2-review-s">(<?php echo esc_html( number_format_i18n( $review_count ) ); ?>)</span>
			</div>
			<?php endif; ?>
		</div>

		<h3 class="bpc2-name"><?php echo esc_html( $name ); ?></h3>

		<?php if ( $price !== '' ) : ?>
		<div class="bpc2-pricing">
			<span class="bpc2-price"><?php echo esc_html( $price ); ?></span>
			<?php if ( $originalPrice !== '' ) : ?>
			<span class="bpc2-old-price"><?php echo esc_html( $originalPrice ); ?></span>
			<?php endif; ?>
		</div>
		<?php endif; ?>
	</div>

</<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
