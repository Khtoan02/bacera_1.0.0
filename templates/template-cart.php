<?php
/**
 * Template Name: Cart
 * Description: Trang giỏ hàng — đồng bộ với mini-cart Shop (localStorage bacera_shop_cart_v1).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$shop_url              = class_exists( 'Bacera_Utils' ) ? Bacera_Utils::get_shop_page_url() : home_url( '/' );
$checkout_url          = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : $shop_url;
$checkout_shipping_url = class_exists( 'Bacera_Utils' ) ? Bacera_Utils::get_checkout_shipping_page_url() : $checkout_url;
$checkout_shipping_url = add_query_arg( 'from_cart', '1', $checkout_shipping_url );
$bacera_customer_scope = 'guest';
$bacera_auth_cookie    = $_COOKIE['bacera_customer_auth'] ?? '';
if ( $bacera_auth_cookie ) {
	$decoded = base64_decode( rawurldecode( $bacera_auth_cookie ) );
	if ( $decoded && strpos( $decoded, '|' ) !== false ) {
		$customer_id = (int) explode( '|', $decoded )[0];
		if ( $customer_id > 0 ) {
			$bacera_customer_scope = 'customer_' . $customer_id;
		}
	}
}

$related_items   = [];
$categories_data = [];
if ( class_exists( 'Pancake_API_Client' ) && class_exists( 'Bacera_Utils' ) ) {
	$api = new Pancake_API_Client();
	$categories_response = $api->request( '/shops/{SHOP_ID}/categories', 'GET' );
	if ( is_array( $categories_response ) && ! empty( $categories_response['success'] ) && ! empty( $categories_response['data'] ) ) {
		$categories_data = $categories_response['data'];
	}
	$rel_endpoint = '/shops/{SHOP_ID}/products/variations?' . http_build_query(
		[
			'page_size' => 12,
			'page'      => 1,
		]
	);
	$rel_resp = $api->request( $rel_endpoint, 'GET' );
	if ( is_array( $rel_resp ) && ! empty( $rel_resp['success'] ) && ! empty( $rel_resp['data'] ) && is_array( $rel_resp['data'] ) ) {
		foreach ( $rel_resp['data'] as $item ) {
			Bacera_Utils::upsert_external_product( $item );
			$related_items[] = $item;
			if ( count( $related_items ) >= 8 ) {
				break;
			}
		}
	}
}

/**
 * Nhãn collection ngắn cho card gợi ý.
 *
 * @param array<string,mixed> $p
 * @param array<int,mixed>    $categories_data
 */
function bacera_cart_related_brand( $p, $categories_data ) {
	if ( ! empty( $p['product']['category']['name'] ) ) {
		return (string) $p['product']['category']['name'];
	}
	if ( ! empty( $p['categories'][0]['name'] ) ) {
		return (string) $p['categories'][0]['name'];
	}
	if ( ! empty( $categories_data ) && is_array( $categories_data ) ) {
		$cid = $p['product']['category_id'] ?? $p['category_id'] ?? '';
		if ( $cid !== '' ) {
			foreach ( $categories_data as $cat ) {
				if ( ! is_array( $cat ) ) {
					continue;
				}
				$id = isset( $cat['id'] ) ? (string) $cat['id'] : '';
				if ( $id !== '' && $id === (string) $cid ) {
					return (string) ( $cat['text'] ?? $cat['name'] ?? '' );
				}
			}
		}
	}
	return __( 'Bacera', 'bacera' );
}

get_header();
?>

<main id="bacera-cart-page" class="min-h-screen bg-[#F9F7F2] font-sans text-stone-800 selection:bg-primary-500/10">
	<style>
		#bacera-cart-page .bacera-cart-wrap {
			box-sizing: border-box;
			width: 100%;
			max-width: 90rem;
			margin-left: auto;
			margin-right: auto;
			padding: 5rem 1.25rem 4rem;
			padding-left: max(1.25rem, env(safe-area-inset-left, 0px));
			padding-right: max(1.25rem, env(safe-area-inset-right, 0px));
		}
		@media (min-width: 1024px) {
			#bacera-cart-page .bacera-cart-wrap {
				padding: 5.5rem max(3rem, min(10vw, 11rem)) 5rem;
			}
		}
		#bacera-cart-page .bacera-cart-row {
			display: grid;
			gap: 1rem 1.5rem;
			align-items: start;
			padding: 1.5rem 0;
			border-bottom: 1px solid rgb(231 229 228);
		}
		#bacera-cart-page .bacera-cart-select-col {
			display: flex;
			align-items: center;
			justify-content: center;
		}
		#bacera-cart-page .bacera-cart-select-col input[type="checkbox"] {
			width: 1.25rem;
			height: 1.25rem;
			border-radius: 0.25rem;
			border: 1px solid rgb(168 162 158);
			accent-color: rgb(217 91 71);
			cursor: pointer;
		}
		@media (max-width: 767px) {
			#bacera-cart-page .bacera-cart-row {
				display: grid;
				grid-template-columns: 1fr auto;
				grid-template-rows: auto auto;
				align-items: start;
			}
			#bacera-cart-page .bacera-cart-row .bacera-cart-product-cell {
				grid-column: 1;
				grid-row: 1;
			}
			#bacera-cart-page .bacera-cart-row .bacera-cart-select-col {
				grid-column: 2;
				grid-row: 1;
				padding-top: 0.125rem;
			}
			#bacera-cart-page .bacera-cart-row .bacera-cart-qty-wrap {
				grid-column: 1;
				grid-row: 2;
			}
			#bacera-cart-page .bacera-cart-row .bacera-cart-total-col {
				grid-column: 2;
				grid-row: 2;
				align-self: center;
			}
		}
		@media (min-width: 768px) {
			#bacera-cart-page .bacera-cart-row {
				grid-template-columns: minmax(0, 1fr) 9rem 10rem 2.75rem;
				align-items: center;
			}
		}
		#bacera-cart-page .bacera-cart-product-cell {
			display: grid;
			grid-template-columns: 6rem minmax(0, 1fr);
			gap: 1rem;
		}
		#bacera-cart-page .bacera-cart-qty {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			border: 1px solid rgb(214 211 209);
			border-radius: 0.5rem;
			overflow: hidden;
			background: #fff;
		}
		#bacera-cart-page .bacera-cart-qty button {
			width: 2.25rem;
			height: 2.5rem;
			border: 0;
			background: #fff;
			color: rgb(87 83 78);
			cursor: pointer;
			font-size: 1.125rem;
			line-height: 1;
		}
		#bacera-cart-page .bacera-cart-qty span {
			min-width: 2rem;
			text-align: center;
			font-variant-numeric: tabular-nums;
			font-size: 0.9375rem;
		}
		#bacera-cart-page .bacera-cart-fbt-track {
			display: flex;
			gap: 1rem;
			overflow-x: auto;
			scroll-snap-type: x mandatory;
			padding-bottom: 0.5rem;
			scrollbar-width: thin;
		}
		#bacera-cart-page .bacera-cart-fbt-track > * {
			flex: 0 0 min(220px, 75vw);
			scroll-snap-align: start;
		}
	</style>

	<div class="bacera-cart-wrap">
		<nav class="text-xs md:text-sm text-stone-500 mb-6 font-sans" aria-label="<?php esc_attr_e( 'Breadcrumb', 'bacera' ); ?>">
			<ol class="flex flex-wrap items-center gap-x-2 gap-y-1 list-none m-0 p-0">
				<li><a class="hover:text-primary-700 transition-colors" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Homepage', 'bacera' ); ?></a></li>
				<li class="text-stone-300 select-none" aria-hidden="true">/</li>
				<li class="text-stone-700 font-medium"><?php esc_html_e( 'Cart', 'bacera' ); ?></li>
			</ol>
		</nav>

		<h1 class="font-serif text-3xl md:text-4xl font-semibold text-stone-800 tracking-tight mb-10 md:mb-12"><?php esc_html_e( 'Your cart', 'bacera' ); ?></h1>

		<div id="bacera-cart-empty" class="rounded-2xl border border-stone-200/80 bg-white p-10 md:p-14 text-center shadow-sm">
			<p class="m-0 text-lg text-stone-600 mb-6"><?php esc_html_e( 'Your cart is empty.', 'bacera' ); ?></p>
			<a href="<?php echo esc_url( $shop_url ); ?>" class="inline-flex items-center justify-center rounded-xl bg-accent-500 px-8 py-3.5 font-medium text-white no-underline hover:bg-accent-600 transition-colors"><?php esc_html_e( 'Continue shopping', 'bacera' ); ?></a>
		</div>

		<div id="bacera-cart-main" class="hidden grid grid-cols-1 lg:grid-cols-3 gap-10 lg:gap-12 lg:items-start">
			<div class="lg:col-span-2 min-w-0">
				<div class="hidden md:grid md:grid-cols-[1fr_9rem_10rem_2.75rem] gap-4 pb-3 border-b border-stone-300/80 text-xs font-semibold uppercase tracking-widest text-stone-500">
					<span><?php esc_html_e( 'Products', 'bacera' ); ?></span>
					<span class="text-center"><?php esc_html_e( 'Quantity', 'bacera' ); ?></span>
					<span class="text-right"><?php esc_html_e( 'Total', 'bacera' ); ?></span>
					<span class="text-center"><?php esc_html_e( 'Select', 'bacera' ); ?></span>
				</div>
				<div id="bacera-cart-lines"></div>
				<div id="bacera-cart-paid-wrap" class="hidden mt-10 pt-10 border-t border-stone-200/90">
					<h2 class="m-0 mb-6 font-serif text-xl md:text-2xl font-semibold text-stone-800 tracking-tight"><?php esc_html_e( 'Completed orders', 'bacera' ); ?></h2>
					<p class="m-0 mb-6 text-sm text-stone-500"><?php esc_html_e( 'Orders you have just completed on the checkout page.', 'bacera' ); ?></p>
					<div id="bacera-cart-paid-orders" class="space-y-6"></div>
				</div>
				<div class="pt-8">
					<a href="<?php echo esc_url( $shop_url ); ?>" class="text-base font-medium text-primary-700 underline underline-offset-4 decoration-primary-400 hover:text-primary-900"><?php esc_html_e( 'Continue shopping', 'bacera' ); ?></a>
				</div>
			</div>

			<aside class="lg:col-span-1 min-w-0">
				<div class="rounded-2xl border border-stone-200/90 bg-white p-6 md:p-8 shadow-sm sticky top-28">
					<label for="bacera-cart-order-notes" class="sr-only"><?php esc_html_e( 'Order notes', 'bacera' ); ?></label>
					<textarea id="bacera-cart-order-notes" rows="4" placeholder="<?php esc_attr_e( 'Order notes', 'bacera' ); ?>" class="w-full rounded-xl border border-stone-200 bg-stone-50/50 px-4 py-3 text-stone-800 placeholder:text-stone-400 focus:border-primary-400 focus:ring-2 focus:ring-primary-200 focus:outline-none resize-y mb-6"></textarea>

					<dl class="space-y-3 text-sm text-stone-700 m-0">
						<div class="flex justify-between gap-4">
							<dt class="m-0" id="bacera-cart-subtotal-label"><?php esc_html_e( 'Subtotal', 'bacera' ); ?> (0):</dt>
							<dd class="m-0 tabular-nums font-medium" id="bacera-cart-subtotal-val">0đ</dd>
						</div>
						<div class="flex justify-between gap-4">
							<dt class="m-0"><?php esc_html_e( 'VAT', 'bacera' ); ?></dt>
							<dd class="m-0 tabular-nums">0đ</dd>
						</div>
						<div class="flex justify-between gap-4">
							<dt class="m-0"><?php esc_html_e( 'Shipping', 'bacera' ); ?></dt>
							<dd class="m-0"><?php esc_html_e( 'Free', 'bacera' ); ?></dd>
						</div>
					</dl>

					<hr class="my-6 border-stone-200" />

					<div class="flex justify-between items-end gap-4 mb-2">
						<div>
							<p class="m-0 font-serif text-xl font-bold text-stone-800"><?php esc_html_e( 'Cart', 'bacera' ); ?></p>
							<p class="m-0 mt-1 text-sm text-stone-600"><?php esc_html_e( 'Total (VAT included)', 'bacera' ); ?></p>
						</div>
						<p class="m-0 text-2xl md:text-3xl font-semibold tabular-nums text-stone-800" id="bacera-cart-grand-total">0đ</p>
					</div>

					<a id="bacera-cart-checkout-btn" href="<?php echo esc_url( $checkout_shipping_url ); ?>" class="mt-6 flex w-full items-center justify-center rounded-xl bg-accent-500 px-6 py-4 text-base font-semibold text-white no-underline shadow-sm hover:bg-accent-600 transition-colors"><?php esc_html_e( 'Proceed to checkout', 'bacera' ); ?></a>
				</div>
			</aside>
		</div>

		<?php if ( ! empty( $related_items ) ) : ?>
		<section class="mt-16 md:mt-24 pt-12 border-t border-stone-200/80" aria-labelledby="bacera-cart-fbt-heading">
			<div class="flex items-center justify-between gap-4 mb-8">
				<h2 id="bacera-cart-fbt-heading" class="m-0 font-serif text-2xl md:text-3xl font-semibold text-stone-800"><?php esc_html_e( 'Frequently bought together', 'bacera' ); ?></h2>
				<div class="flex gap-2 shrink-0">
					<button type="button" id="bacera-fbt-prev" class="h-10 w-10 rounded-full border border-stone-200 bg-white text-stone-600 hover:bg-stone-50 shadow-sm" aria-label="<?php esc_attr_e( 'Previous', 'bacera' ); ?>">‹</button>
					<button type="button" id="bacera-fbt-next" class="h-10 w-10 rounded-full border border-stone-200 bg-white text-stone-600 hover:bg-stone-50 shadow-sm" aria-label="<?php esc_attr_e( 'Next', 'bacera' ); ?>">›</button>
				</div>
			</div>
			<div id="bacera-fbt-scroll" class="bacera-cart-fbt-track">
				<?php
				foreach ( $related_items as $p ) :
					$name = $p['product']['name'] ?? $p['name'] ?? __( 'Product', 'bacera' );
					$image_url = Bacera_Utils::get_proxy_url( $p );
					$price_at_counter = isset( $p['price_at_counter'] ) ? (float) $p['price_at_counter'] : ( isset( $p['variations'][0]['price_at_counter'] ) ? (float) $p['variations'][0]['price_at_counter'] : 0 );
					$retail_price     = isset( $p['retail_price'] ) ? (float) $p['retail_price'] : ( isset( $p['variations'][0]['retail_price'] ) ? (float) $p['variations'][0]['retail_price'] : 0 );
					$price            = $price_at_counter > 0 ? $price_at_counter : $retail_price;
					$original_price   = ( $retail_price > $price ) ? $retail_price : 0;
					$discount_percent = false;
					if ( $original_price > 0 && $price < $original_price ) {
						$discount_percent = '-' . (string) (int) round( ( ( $original_price - $price ) / $original_price ) * 100 ) . '%';
					}
					$brand              = bacera_cart_related_brand( $p, $categories_data );
					$product_detail_url = Bacera_Utils::get_product_permalink( $p );
					if ( $product_detail_url === '' ) {
						$product_detail_url = '#';
					}
					?>
				<?php get_template_part( 'app/Views/components/product-card', null, [
					'title'     => $name,
					'brand'     => $brand,
					'price'     => number_format( $price, 0, ',', '.' ) . ' ₫',
					'old_price' => $original_price > 0 ? number_format( $original_price, 0, ',', '.' ) . ' ₫' : '',
					'image'     => $image_url,
					'discount'  => $discount_percent,
					'url'       => $product_detail_url,
				] ); ?>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endif; ?>
	</div>
</main>

<script>
(function () {
	var STORAGE_KEY = 'bacera_shop_cart_v1';
	var NOTES_KEY = 'bacera_cart_order_notes_v1';
	var SELECTED_KEY = 'bacera_cart_checkout_selected_v1';
	var LAST_IDS_KEY = 'bacera_cart_id_snapshot_v1';
	var CHECKOUT_ITEMS_KEY = 'bacera_checkout_items';
	var CUSTOMER_SCOPE = <?php echo wp_json_encode( $bacera_customer_scope ); ?>;
	var PAID_ORDERS_KEY = 'bacera_cart_paid_orders_v1_' + CUSTOMER_SCOPE;
	var ariaIncludeCheckout = <?php echo wp_json_encode( __( 'Include in checkout', 'bacera' ) ); ?>;
	var txtCartEmptyButPaid = <?php echo wp_json_encode( __( 'Your cart is empty. You can view your completed orders below.', 'bacera' ) ); ?>;
	var txtOrderRef = <?php echo wp_json_encode( __( 'Order reference', 'bacera' ) ); ?>;
	var txtOrderTotal = <?php echo wp_json_encode( __( 'Order total', 'bacera' ) ); ?>;

	var emptyEl = document.getElementById('bacera-cart-empty');
	var mainEl = document.getElementById('bacera-cart-main');
	var linesEl = document.getElementById('bacera-cart-lines');
	var paidWrapEl = document.getElementById('bacera-cart-paid-wrap');
	var paidOrdersEl = document.getElementById('bacera-cart-paid-orders');
	var subtotalLabel = document.getElementById('bacera-cart-subtotal-label');
	var subtotalVal = document.getElementById('bacera-cart-subtotal-val');
	var grandTotalEl = document.getElementById('bacera-cart-grand-total');
	var notesEl = document.getElementById('bacera-cart-order-notes');
	var fbtScroll = document.getElementById('bacera-fbt-scroll');
	var fbtPrev = document.getElementById('bacera-fbt-prev');
	var fbtNext = document.getElementById('bacera-fbt-next');
	var checkoutBtn = document.getElementById('bacera-cart-checkout-btn');

	if (!emptyEl || !mainEl || !linesEl || !grandTotalEl) return;

	function loadPaidOrders() {
		try {
			var parsed = JSON.parse(localStorage.getItem(PAID_ORDERS_KEY) || '[]');
			return Array.isArray(parsed) ? parsed : [];
		} catch (e) {
			return [];
		}
	}

	function renderPaidOrders() {
		if (!paidWrapEl || !paidOrdersEl) return;
		var list = loadPaidOrders();
		if (!list.length) {
			paidWrapEl.classList.add('hidden');
			paidOrdersEl.innerHTML = '';
			return;
		}
		paidWrapEl.classList.remove('hidden');
		paidOrdersEl.innerHTML = list.map(function (order) {
			var items = order.items && Array.isArray(order.items) ? order.items : [];
			var lines = items.map(function (item) {
				var title = escapeHtml(item.name || '');
				var brand = escapeHtml(item.brand || '<?php echo esc_js( __( 'Bacera', 'bacera' ) ); ?>');
				var meta = parseVariantDetails(item);
				var attr = formatAttrLine(meta, item);
				var attrHtml = attr ? '<p class="m-0 mt-1 text-sm text-stone-500">' + escapeHtml(attr) + '</p>' : '';
				var img = escapeHtml(item.image || 'https://placehold.co/240x300/f0ece3/8d6a54?text=Product');
				var lt = lineTotal(item);
				return ''
					+ '<div class="flex gap-4 py-3 border-b border-stone-100 last:border-0">'
					+ '  <div class="h-20 w-16 shrink-0 overflow-hidden rounded-lg bg-stone-100">'
					+ '    <img src="' + img + '" alt="" class="h-full w-full object-cover" loading="lazy" />'
					+ '  </div>'
					+ '  <div class="min-w-0 flex-1">'
					+ '    <p class="m-0 text-[10px] uppercase tracking-wider text-stone-500">' + brand + '</p>'
					+ '    <p class="m-0 mt-0.5 font-medium text-stone-900 leading-snug">' + title + '</p>'
					+ attrHtml
					+ '    <p class="m-0 mt-2 text-sm text-stone-600"><?php echo esc_js( __( 'Qty', 'bacera' ) ); ?>: ' + String(Number(item.qty || 1)) + '</p>'
					+ '  </div>'
					+ '  <div class="shrink-0 text-right">'
					+ '    <p class="m-0 text-sm font-semibold tabular-nums text-stone-900">' + toCurrency(lt) + '</p>'
					+ '  </div>'
					+ '</div>';
			}).join('');
			var total = typeof order.order_total === 'number' ? order.order_total : items.reduce(function (s, it) { return s + lineTotal(it); }, 0);
			return ''
				+ '<section class="rounded-2xl border border-stone-200/90 bg-stone-50/40 p-5 md:p-6 shadow-sm">'
				+ '  <div class="flex flex-wrap items-baseline justify-between gap-2 gap-y-1">'
				+ '    <p class="m-0 text-sm text-stone-500">' + escapeHtml(order.placed_at_display || '') + '</p>'
				+ '    <p class="m-0 text-sm font-medium text-stone-800"><span class="text-stone-500 font-normal">' + txtOrderRef + ':</span> ' + escapeHtml(String(order.pancake_order_id || '')) + '</p>'
				+ '  </div>'
				+ '  <div class="mt-4">' + lines + '</div>'
				+ '  <div class="mt-4 flex justify-end border-t border-stone-200/80 pt-4">'
				+ '    <p class="m-0 text-base font-semibold text-stone-900">' + txtOrderTotal + ': <span class="tabular-nums">' + toCurrency(total) + '</span></p>'
				+ '  </div>'
				+ '</section>';
		}).join('');
	}

	function loadCart() {
		try {
			var parsed = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
			return Array.isArray(parsed) ? parsed : [];
		} catch (e) {
			return [];
		}
	}

	function saveCart(cart) {
		localStorage.setItem(STORAGE_KEY, JSON.stringify(cart));
	}

	function loadSelected() {
		try {
			var parsed = JSON.parse(localStorage.getItem(SELECTED_KEY) || '[]');
			return Array.isArray(parsed) ? parsed.map(String) : [];
		} catch (e) {
			return [];
		}
	}

	function saveSelected(ids) {
		try {
			localStorage.setItem(SELECTED_KEY, JSON.stringify(ids));
		} catch (e) {}
	}

	/** Giữ tick theo từng dòng; sản phẩm mới thêm vào giỏ được tick mặc định, không bật lại dòng đã bỏ chọn. */
	function syncSelectedWithCart(cart) {
		var ids = cart.map(function (x) { return String(x.id); });
		var prev = [];
		try {
			prev = JSON.parse(localStorage.getItem(LAST_IDS_KEY) || '[]');
			if (!Array.isArray(prev)) prev = [];
			prev = prev.map(String);
		} catch (e) {
			prev = [];
		}
		var sel = loadSelected().filter(function (id) { return ids.indexOf(id) >= 0; });
		ids.forEach(function (id) {
			if (prev.indexOf(id) < 0 && sel.indexOf(id) < 0) {
				sel.push(id);
			}
		});
		saveSelected(sel);
		try {
			localStorage.setItem(LAST_IDS_KEY, JSON.stringify(ids));
		} catch (e) {}
		return sel;
	}

	function isSelected(id, selectedArr) {
		return selectedArr.indexOf(String(id)) >= 0;
	}

	function toCurrency(n) {
		return Number(n || 0).toLocaleString('vi-VN') + 'đ';
	}

	function escapeHtml(s) {
		return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
	}

	function parseVariantDetails(item) {
		var color = String(item.color || '').trim();
		var size = String(item.size || '').trim();
		var label = String(item.variant_label || '').trim();
		if ((!color || !size) && label) {
			var parts = label.split('|').map(function (p) { return p.trim(); }).filter(Boolean);
			if (!color && parts[0]) color = parts[0];
			if (!size && parts[1]) size = parts[1];
		}
		return { color: color, size: size };
	}

	function formatAttrLine(meta, item) {
		var c = meta.color;
		var s = meta.size;
		if (c && s) return c + ' ' + s;
		if (c) return c;
		if (s) return s;
		var label = String(item.variant_label || '').trim();
		return label;
	}

	function lineTotal(item) {
		return Number(item.price || 0) * Number(item.qty || 1);
	}

	function render() {
		var cart = loadCart();
		var paidList = loadPaidOrders();

		if (!cart.length) {
			try {
				localStorage.removeItem(LAST_IDS_KEY);
				saveSelected([]);
			} catch (e) {}
		}

		if (!cart.length && !paidList.length) {
			emptyEl.classList.remove('hidden');
			mainEl.classList.add('hidden');
			return;
		}

		emptyEl.classList.add('hidden');
		mainEl.classList.remove('hidden');

		if (!cart.length) {
			linesEl.innerHTML = '<p class="m-0 py-8 text-center text-stone-600 leading-relaxed">' + txtCartEmptyButPaid + '</p>';
			if (subtotalLabel) {
				subtotalLabel.textContent = '<?php echo esc_js( __( 'Subtotal', 'bacera' ) ); ?> (0):';
			}
			if (subtotalVal) subtotalVal.textContent = toCurrency(0);
			grandTotalEl.textContent = toCurrency(0);
			if (checkoutBtn) {
				checkoutBtn.setAttribute('aria-disabled', 'true');
				checkoutBtn.classList.add('pointer-events-none', 'opacity-45');
			}
			renderPaidOrders();
			return;
		}

		var selectedArr = syncSelectedWithCart(cart);
		var picked = cart.filter(function (item) {
			return isSelected(item.id, selectedArr);
		});
		var count = picked.reduce(function (a, i) { return a + Number(i.qty || 1); }, 0);
		var subtotal = picked.reduce(function (sum, item) { return sum + lineTotal(item); }, 0);

		if (subtotalLabel) {
			subtotalLabel.textContent = '<?php echo esc_js( __( 'Subtotal', 'bacera' ) ); ?> (' + count + '):';
		}
		if (subtotalVal) subtotalVal.textContent = toCurrency(subtotal);
		grandTotalEl.textContent = toCurrency(subtotal);

		if (checkoutBtn) {
			var none = picked.length === 0;
			checkoutBtn.setAttribute('aria-disabled', none ? 'true' : 'false');
			checkoutBtn.classList.toggle('pointer-events-none', none);
			checkoutBtn.classList.toggle('opacity-45', none);
		}

		linesEl.innerHTML = cart.map(function (item) {
			var title = escapeHtml(item.name || '');
			var brand = escapeHtml(item.brand || '<?php echo esc_js( __( 'Bacera', 'bacera' ) ); ?>');
			var meta = parseVariantDetails(item);
			var attr = formatAttrLine(meta, item);
			var attrHtml = attr ? '<p class="m-0 mt-1 text-sm text-stone-600 leading-snug">' + escapeHtml(attr) + '</p>' : '';
			var img = escapeHtml(item.image || 'https://placehold.co/240x300/f0ece3/8d6a54?text=Product');
			var orig = Number(item.original_price || 0);
			var price = Number(item.price || 0);
			var lt = lineTotal(item);
			var oldHtml = orig > price ? '<span class="block text-sm text-stone-400 line-through tabular-nums">' + toCurrency(orig * Number(item.qty || 1)) + '</span>' : '';
			var disc = '';
			if (orig > price && orig > 0) {
				var pct = Math.round((orig - price) / orig * 100);
				disc = '<span class="absolute left-1.5 top-1.5 rounded bg-stone-800/85 px-1.5 py-0.5 text-[10px] font-semibold text-white z-10">-' + pct + '%</span>';
			}
			var unitHint = Number(item.qty || 1) > 1
				? '<p class="m-0 mt-1 text-xs text-stone-500 tabular-nums">' + toCurrency(price) + ' × ' + String(item.qty) + '</p>'
				: '';
			var chk = isSelected(item.id, selectedArr) ? ' checked' : '';
			return ''
				+ '<div class="bacera-cart-row" data-cart-id="' + escapeHtml(item.id) + '">'
				+ '  <div class="bacera-cart-product-cell">'
				+ '    <div class="relative aspect-[4/5] w-full overflow-hidden rounded-xl bg-stone-100 shrink-0">'
				+ disc
				+ '      <img src="' + img + '" alt="" class="h-full w-full object-cover" loading="lazy" />'
				+ '    </div>'
				+ '    <div class="min-w-0">'
				+ '      <p class="m-0 text-xs uppercase tracking-wider text-stone-500">' + brand + '</p>'
				+ '      <p class="m-0 mt-1 font-medium text-stone-900 leading-snug">' + title + '</p>'
				+ attrHtml
				+ '    </div>'
				+ '  </div>'
				+ '  <div class="bacera-cart-qty-wrap flex justify-start md:justify-center">'
				+ '    <div class="bacera-cart-qty" role="group">'
				+ '      <button type="button" data-cart-action="minus" aria-label="<?php echo esc_js( __( 'Decrease quantity', 'bacera' ) ); ?>">−</button>'
				+ '      <span>' + String(Number(item.qty || 1)).padStart(2, '0') + '</span>'
				+ '      <button type="button" data-cart-action="plus" aria-label="<?php echo esc_js( __( 'Increase quantity', 'bacera' ) ); ?>">+</button>'
				+ '    </div>'
				+ '  </div>'
				+ '  <div class="bacera-cart-total-col text-left md:text-right">'
				+ oldHtml
				+ '    <span class="text-lg font-semibold tabular-nums text-stone-900">' + toCurrency(lt) + '</span>'
				+ unitHint
				+ '  </div>'
				+ '  <div class="bacera-cart-select-col">'
				+ '    <input type="checkbox" class="bacera-cart-line-cb" data-cart-id="' + escapeHtml(item.id) + '"' + chk + ' aria-label=' + JSON.stringify(ariaIncludeCheckout) + ' />'
				+ '  </div>'
				+ '</div>';
		}).join('');
		renderPaidOrders();
	}

	linesEl.addEventListener('click', function (ev) {
		var btn = ev.target.closest('button[data-cart-action]');
		if (!btn) return;
		var row = btn.closest('.bacera-cart-row');
		if (!row) return;
		var id = row.getAttribute('data-cart-id');
		var action = btn.getAttribute('data-cart-action');
		var cart = loadCart();
		var idx = cart.findIndex(function (x) { return String(x.id) === String(id); });
		if (idx < 0) return;
		if (action === 'minus') {
			cart[idx].qty = Number(cart[idx].qty || 1) - 1;
			if (cart[idx].qty <= 0) cart.splice(idx, 1);
		} else if (action === 'plus') {
			cart[idx].qty = Number(cart[idx].qty || 1) + 1;
		}
		saveCart(cart);
		render();
	});

	linesEl.addEventListener('change', function (ev) {
		var t = ev.target;
		if (!t || !t.classList || !t.classList.contains('bacera-cart-line-cb')) return;
		var id = String(t.getAttribute('data-cart-id'));
		var sel = loadSelected();
		var ix = sel.indexOf(id);
		if (t.checked) {
			if (ix < 0) sel.push(id);
		} else if (ix >= 0) {
			sel.splice(ix, 1);
		}
		saveSelected(sel);
		render();
	});

	if (checkoutBtn) {
		checkoutBtn.addEventListener('click', function (e) {
			var cart = loadCart();
			var sel = loadSelected();
			var picked = cart.filter(function (item) {
				return sel.indexOf(String(item.id)) >= 0;
			});
			if (!picked.length) {
				e.preventDefault();
				e.stopPropagation();
				return;
			}
			try {
				sessionStorage.setItem(CHECKOUT_ITEMS_KEY, JSON.stringify(picked));
				sessionStorage.setItem('bacera_checkout_notes', notesEl ? notesEl.value : '');
				sessionStorage.setItem('bacera_checkout_force_step1', '1');
			} catch (err) {}
		});
	}

	if (notesEl) {
		try {
			notesEl.value = localStorage.getItem(NOTES_KEY) || '';
		} catch (e) {}
		notesEl.addEventListener('input', function () {
			try { localStorage.setItem(NOTES_KEY, notesEl.value); } catch (e) {}
		});
	}

	function scrollFbt(dir) {
		if (!fbtScroll) return;
		var w = fbtScroll.clientWidth * 0.85;
		fbtScroll.scrollBy({ left: dir * w, behavior: 'smooth' });
	}
	if (fbtPrev) fbtPrev.addEventListener('click', function () { scrollFbt(-1); });
	if (fbtNext) fbtNext.addEventListener('click', function () { scrollFbt(1); });

	render();
	window.addEventListener('storage', function (e) {
		if (e.key === STORAGE_KEY || e.key === SELECTED_KEY || e.key === PAID_ORDERS_KEY) render();
	});
})();
</script>

<?php
get_footer();
