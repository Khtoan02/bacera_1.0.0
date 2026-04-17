<?php
/**
 * Template Name: Shop
 * Description: Vertical split ~20% sidebar (categories + filters) / ~80% main; lưới 3 cột. API giống template-plugin-design.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_page = isset( $_GET['shop_page'] ) ? max( 1, (int) $_GET['shop_page'] ) : 1;
$page_size    = 9;
$sort         = isset( $_GET['sort'] ) ? sanitize_text_field( wp_unslash( $_GET['sort'] ) ) : 'price_low';

// Danh mục chọn: filter_collection[] = category id (API). Hỗ trợ ?cat= (cũ) nếu chưa có filter_collection.
$raw_filter_cats = isset( $_GET['filter_collection'] ) ? (array) wp_unslash( $_GET['filter_collection'] ) : [];
$selected_category_ids = array_values(
	array_unique(
		array_filter(
			array_map( 'sanitize_text_field', $raw_filter_cats )
		)
	)
);
$legacy_cat = isset( $_GET['cat'] ) ? sanitize_text_field( wp_unslash( $_GET['cat'] ) ) : '';
if ( empty( $selected_category_ids ) && $legacy_cat !== '' ) {
	$selected_category_ids = [ $legacy_cat ];
}
// API Pancake: chỉ gửi category_id khi chọn đúng một danh mục; nhiều danh mục → lọc phía client.
$active_cat = count( $selected_category_ids ) === 1 ? $selected_category_ids[0] : '';

$categories_response = null;
$categories_data     = [];
$products_response   = null;

if ( class_exists( 'Pancake_API_Client' ) && class_exists( 'Bacera_Utils' ) ) {
	$api = new Pancake_API_Client();

	$categories_response = $api->request( '/shops/{SHOP_ID}/categories', 'GET' );
	if ( is_array( $categories_response ) && ! empty( $categories_response['success'] ) && ! empty( $categories_response['data'] ) ) {
		$categories_data = $categories_response['data'];
	}

	$query_args = [
		'page_size' => $page_size,
		'page'      => $current_page,
	];
	if ( $active_cat !== '' ) {
		$query_args['category_id'] = $active_cat;
	}
	$endpoint          = '/shops/{SHOP_ID}/products/variations?' . http_build_query( $query_args );
	$products_response = $api->request( $endpoint, 'GET' );

	if ( is_array( $products_response ) && ! empty( $products_response['success'] ) && ! empty( $products_response['data'] ) ) {
		foreach ( $products_response['data'] as $item ) {
			Bacera_Utils::upsert_external_product( $item );
		}
	}
}

$items = [];
if ( is_array( $products_response ) && ! empty( $products_response['data'] ) && is_array( $products_response['data'] ) ) {
	$items = $products_response['data'];
}

$products_api_error = is_array( $products_response ) && array_key_exists( 'success', $products_response ) && $products_response['success'] === false;

$total_pages = 1;
if ( is_array( $products_response ) ) {
	if ( isset( $products_response['total_pages'] ) ) {
		$total_pages = max( 1, (int) $products_response['total_pages'] );
	} elseif ( isset( $products_response['total'] ) ) {
		$total_pages = max( 1, (int) ceil( (int) $products_response['total'] / $page_size ) );
	} elseif ( isset( $products_response['meta']['total_pages'] ) ) {
		$total_pages = max( 1, (int) $products_response['meta']['total_pages'] );
	} elseif ( count( $items ) < $page_size && $current_page <= 1 ) {
		$total_pages = 1;
	} else {
		$total_pages = max( $current_page, $current_page + ( count( $items ) >= $page_size ? 1 : 0 ) );
	}
}

$shop_base_url = get_permalink( get_queried_object_id() );
if ( ! $shop_base_url ) {
	$shop_base_url = home_url( '/' );
}
$cart_page_url           = class_exists( 'Bacera_Utils' ) ? Bacera_Utils::get_cart_page_url() : ( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ) );
$checkout_shipping_url   = class_exists( 'Bacera_Utils' ) ? Bacera_Utils::get_checkout_shipping_page_url() : ( function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : $cart_page_url );

$url_shop_clear_cats = remove_query_arg( [ 'cat', 'filter_collection', 'shop_page' ], $shop_base_url );
if ( $sort !== 'price_low' ) {
	$url_shop_clear_cats = add_query_arg( 'sort', $sort, $url_shop_clear_cats );
}

/**
 * Giữ tham số GET cho form sidebar (trừ các key filter do checkbox gửi).
 */
function bacera_shop_sidebar_hidden_inputs( $exclude = [] ) {
	$exclude = array_merge(
		$exclude,
		[
			'filter_collection',
			'filter_price',
			'filter_size',
			'filter_capacity',
			'filter_handle',
			'filter_glaze',
			'filter_shape',
			'filter_best_for',
			'shop_page',
			'cat',
		]
	);
	foreach ( $_GET as $k => $v ) {
		if ( in_array( $k, $exclude, true ) ) {
			continue;
		}
		if ( is_array( $v ) ) {
			foreach ( $v as $one ) {
				echo '<input type="hidden" name="' . esc_attr( $k ) . '[]" value="' . esc_attr( sanitize_text_field( wp_unslash( $one ) ) ) . '" />';
			}
		} else {
			if ( $v === '' || $v === false ) {
				continue;
			}
			echo '<input type="hidden" name="' . esc_attr( sanitize_key( $k ) ) . '" value="' . esc_attr( sanitize_text_field( wp_unslash( $v ) ) ) . '" />';
		}
	}
}

/**
 * Các category id gắn với một variation/product từ API (để khớp filter_collection).
 *
 * @param array<string,mixed> $p Item từ /products/variations.
 * @return string[]
 */
function bacera_shop_product_category_ids( $p ) {
	if ( ! is_array( $p ) ) {
		return [];
	}
	$ids = [];
	if ( ! empty( $p['product']['category']['id'] ) ) {
		$ids[] = (string) $p['product']['category']['id'];
	}
	if ( ! empty( $p['product']['category_id'] ) ) {
		$ids[] = (string) $p['product']['category_id'];
	}
	if ( ! empty( $p['category'] ) && is_array( $p['category'] ) && isset( $p['category']['id'] ) ) {
		$ids[] = (string) $p['category']['id'];
	}
	if ( ! empty( $p['categories'] ) && is_array( $p['categories'] ) ) {
		foreach ( $p['categories'] as $c ) {
			if ( is_array( $c ) && isset( $c['id'] ) ) {
				$ids[] = (string) $c['id'];
			}
		}
	}
	if ( ! empty( $p['product']['categories'] ) && is_array( $p['product']['categories'] ) ) {
		foreach ( $p['product']['categories'] as $c ) {
			if ( is_array( $c ) && isset( $c['id'] ) ) {
				$ids[] = (string) $c['id'];
			}
		}
	}
	if ( ! empty( $p['category_id'] ) ) {
		$ids[] = (string) $p['category_id'];
	}
	return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * Tên hiển thị danh mục cho thẻ sản phẩm: ưu tiên name từ payload, không có thì tra theo id trong $categories_data (API /categories).
 * $fallback_category_ids: khi payload không có category (thường gặp khi API đã lọc theo 1 danh mục), dùng id đang chọn trên Shop (một id) để hiển thị nhãn.
 *
 * @param array<string,mixed>     $p Item variation.
 * @param array<int,array<mixed>> $categories_data Danh sách category từ API.
 * @param string[]                $fallback_category_ids Id danh mục đang lọc trên PLP (khuyến nghị 1 id).
 */
function bacera_shop_product_category_display_name( $p, $categories_data, $fallback_category_ids = [] ) {
	if ( is_array( $p ) ) {
		if ( ! empty( $p['product']['category']['name'] ) ) {
			return (string) $p['product']['category']['name'];
		}
		if ( ! empty( $p['product']['category'] ) && is_string( $p['product']['category'] ) ) {
			return trim( $p['product']['category'] );
		}
		if ( ! empty( $p['categories'][0]['name'] ) ) {
			return (string) $p['categories'][0]['name'];
		}
		if ( ! empty( $p['product']['categories'][0]['name'] ) ) {
			return (string) $p['product']['categories'][0]['name'];
		}
		if ( ! empty( $p['category']['name'] ) && is_array( $p['category'] ) ) {
			return (string) $p['category']['name'];
		}
		if ( ! empty( $p['categories'] ) && is_array( $p['categories'] ) ) {
			foreach ( $p['categories'] as $c ) {
				if ( is_array( $c ) && ! empty( $c['name'] ) ) {
					return (string) $c['name'];
				}
			}
		}
		if ( ! empty( $p['product']['categories'] ) && is_array( $p['product']['categories'] ) ) {
			foreach ( $p['product']['categories'] as $c ) {
				if ( is_array( $c ) && ! empty( $c['name'] ) ) {
					return (string) $c['name'];
				}
			}
		}
	}
	$ids = bacera_shop_product_category_ids( $p );
	foreach ( $ids as $id ) {
		foreach ( $categories_data as $cat ) {
			if ( ! is_array( $cat ) ) {
				continue;
			}
			$cid = isset( $cat['id'] ) ? (string) $cat['id'] : ( isset( $cat['category_id'] ) ? (string) $cat['category_id'] : '' );
			if ( $cid === '' || (string) $id !== $cid ) {
				continue;
			}
			$label = $cat['text'] ?? $cat['name'] ?? '';
			if ( $label !== '' ) {
				return (string) $label;
			}
		}
	}
	foreach ( $fallback_category_ids as $fid ) {
		$fid = (string) $fid;
		if ( $fid === '' ) {
			continue;
		}
		foreach ( $categories_data as $cat ) {
			if ( ! is_array( $cat ) ) {
				continue;
			}
			$cid = isset( $cat['id'] ) ? (string) $cat['id'] : ( isset( $cat['category_id'] ) ? (string) $cat['category_id'] : '' );
			if ( $cid !== '' && $fid === $cid ) {
				$label = $cat['text'] ?? $cat['name'] ?? '';
				if ( $label !== '' ) {
					return (string) $label;
				}
			}
		}
	}
	return '';
}

/**
 * Chuỗi tìm kiếm thường (tên + mô tả) để lọc theo từ khóa.
 *
 * @param array<string,mixed> $p Variation.
 */
function bacera_shop_filter_haystack( $p ) {
	if ( ! is_array( $p ) ) {
		return '';
	}
	$parts = [
		$p['product']['name'] ?? '',
		$p['name'] ?? '',
		$p['product']['description'] ?? '',
		$p['product']['note'] ?? '',
	];
	return strtolower( implode( ' ', array_filter( array_map( 'strval', $parts ) ) ) );
}

/**
 * Map slug filter → các chuỗi có thể xuất hiện trong tên/mô tả (lọc OR trong cùng nhóm).
 *
 * @return array<string, array<string, string[]>>
 */
function bacera_shop_attribute_filter_needle_maps() {
	return [
		'capacity' => [
			'lt100'   => [ '<100', '< 100', '100ml', 'under 100', 'dưới 100' ],
			'100_150' => [ '100-150', '100–150', '100 150' ],
			'150_250' => [ '150-250', '150–250', '150 250', '200ml' ],
			'gt250'   => [ '>250', '> 250', '250ml', 'over 250', '300ml' ],
		],
		'handle'   => [
			'with_handle'    => [ 'with handle', 'có quai', 'co quai' ],
			'without_handle' => [ 'without handle', 'no handle', 'không quai', 'khong quai' ],
			'round_loop'     => [ 'round loop' ],
			'hollow_handle'  => [ 'hollow handle' ],
		],
		'glaze'    => [
			'smooth'   => [ 'smooth' ],
			'reactive' => [ 'reactive' ],
			'matte'    => [ 'matte' ],
			'glossy'   => [ 'glossy' ],
		],
		'shape'    => [
			'round'       => [ 'round' ],
			'slim'        => [ 'slim' ],
			'tall'        => [ 'tall' ],
			'flared_rim'  => [ 'flared', 'flared rim' ],
		],
		'best_for' => [
			'tea'       => [ 'tea', 'trà', 'tra' ],
			'coffee'    => [ 'coffee', 'cà phê', 'ca phe' ],
			'espresso'  => [ 'espresso' ],
			'alcohol'   => [ 'alcohol', 'wine', 'rượu', 'ruou' ],
			'juice'     => [ 'juice', 'nước ép', 'nuoc ep' ],
		],
	];
}

/**
 * @param string[] $selected Slug đã chọn.
 * @param array<string, string[]> $slug_needles
 */
function bacera_shop_keyword_group_match( $haystack, array $selected, array $slug_needles ) {
	if ( empty( $selected ) ) {
		return true;
	}
	foreach ( $selected as $slug ) {
		if ( empty( $slug_needles[ $slug ] ) ) {
			continue;
		}
		foreach ( $slug_needles[ $slug ] as $needle ) {
			$needle = strtolower( (string) $needle );
			if ( $needle !== '' && strpos( $haystack, $needle ) !== false ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Khoảng giá: nhãn kiểu USD trên UI, lọc theo VND (tỷ giá gần đúng ~25k/$).
 *
 * @param float  $pr Giá hiệu dụng (VND).
 * @param string $fp filter_price: '', p1–p4, hoặc legacy under|mid|over.
 */
function bacera_shop_price_band_matches( $pr, $fp ) {
	if ( $fp === '' ) {
		return true;
	}
	if ( $pr <= 0 ) {
		return false;
	}
	switch ( $fp ) {
		case 'under':
		case 'p1':
			return $pr < 750000;
		case 'mid':
		case 'p2':
			return $pr >= 750000 && $pr <= 1500000;
		case 'p3':
			return $pr > 1500000 && $pr <= 2500000;
		case 'p4':
			return $pr > 2500000;
		case 'over':
			return $pr > 1500000;
		default:
			return true;
	}
}

/**
 * Tìm category id đầu tiên khớp một trong các từ khóa trong tên/text (API).
 *
 * @param array<int, array<string,mixed>> $categories_data
 * @param string[]                        $keywords
 */
function bacera_shop_find_category_id_by_keywords( array $categories_data, array $keywords ) {
	foreach ( $categories_data as $cat ) {
		if ( ! is_array( $cat ) ) {
			continue;
		}
		$label = strtolower( (string) ( $cat['text'] ?? $cat['name'] ?? '' ) );
		if ( $label === '' ) {
			continue;
		}
		foreach ( $keywords as $kw ) {
			$kw = strtolower( trim( (string) $kw ) );
			if ( $kw !== '' && strpos( $label, $kw ) !== false ) {
				$cid = isset( $cat['id'] ) ? (string) $cat['id'] : ( isset( $cat['category_id'] ) ? (string) $cat['category_id'] : '' );
				if ( $cid !== '' ) {
					return $cid;
				}
			}
		}
	}
	return '';
}

/**
 * URL tile "Shop by" — một danh mục (filter_collection) hoặc xóa lọc (Shop all).
 */
function bacera_shop_by_tile_url( $shop_base_url, $sort, $category_id ) {
	$u = remove_query_arg( [ 'cat', 'filter_collection', 'shop_page' ], $shop_base_url );
	if ( $category_id !== '' ) {
		$u = add_query_arg( 'filter_collection', $category_id, $u );
	}
	if ( $sort !== 'price_low' ) {
		$u = add_query_arg( 'sort', $sort, $u );
	}
	return $u;
}

/**
 * Icon line-art cho từng ô Shop by (stroke, màu currentColor).
 */
function bacera_shop_by_tile_icon( $key ) {
	$a = 'fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"';
	switch ( $key ) {
		case 'all':
			return '<svg class="w-9 h-9" viewBox="0 0 24 24" aria-hidden="true"><path ' . $a . ' d="M4 5h6v6H4V5zm10 0h6v6h-6V5zM4 13h6v6H4v-6zm10 0h6v6h-6v-6z"/></svg>';
		case 'teapots':
			return '<svg class="w-9 h-9" viewBox="0 0 24 24" aria-hidden="true"><path ' . $a . ' d="M5 10c0-3 2.5-5 6-5s6 2 6 5v8H5v-8z"/><path ' . $a . ' d="M17 10h2a2 2 0 012 2v1a2 2 0 01-2 2h-2M9 18v2M15 18v2"/></svg>';
		case 'drinkware':
			return '<svg class="w-9 h-9" viewBox="0 0 24 24" aria-hidden="true"><path ' . $a . ' d="M8 4h8l-1 14a2 2 0 01-2 2h-2a2 2 0 01-2-2L8 4z"/><path ' . $a . ' d="M8 8h8"/></svg>';
		case 'bowls':
			return '<svg class="w-9 h-9" viewBox="0 0 24 24" aria-hidden="true"><ellipse cx="12" cy="14" rx="8" ry="4" ' . $a . '/><path ' . $a . ' d="M4 14c0-4 3.5-7 8-7s8 3 8 7"/></svg>';
		case 'kitchen':
			return '<svg class="w-9 h-9" viewBox="0 0 24 24" aria-hidden="true"><path ' . $a . ' d="M6 10h12v8a2 2 0 01-2 2H8a2 2 0 01-2-2v-8z"/><path ' . $a . ' d="M9 10V8a3 3 0 016 0v2M6 14h12"/></svg>';
		case 'plates':
			return '<svg class="w-9 h-9" viewBox="0 0 24 24" aria-hidden="true"><ellipse cx="12" cy="12" rx="8" ry="3" ' . $a . '/><ellipse cx="12" cy="9" rx="6" ry="2.5" ' . $a . '/><ellipse cx="12" cy="6" rx="4" ry="2" ' . $a . '/></svg>';
		case 'vases':
			return '<svg class="w-9 h-9" viewBox="0 0 24 24" aria-hidden="true"><path ' . $a . ' d="M10 3h4l1 4c2 1.5 3 4 3 7v6H6v-6c0-3 1-5.5 3-7l1-4z"/><path ' . $a . ' d="M9 21h6"/></svg>';
		case 'storage':
			return '<svg class="w-9 h-9" viewBox="0 0 24 24" aria-hidden="true"><path ' . $a . ' d="M8 4h8v4H8V4z"/><path ' . $a . ' d="M7 8h10v12a1 1 0 01-1 1H8a1 1 0 01-1-1V8z"/><path ' . $a . ' d="M10 12h4"/></svg>';
		default:
			return '<svg class="w-9 h-9" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8" ' . $a . '/></svg>';
	}
}

$bacera_shop_by_defs = [
	[ 'key' => 'all', 'label' => __( 'Shop all', 'bacera' ), 'keywords' => null ],
	[ 'key' => 'teapots', 'label' => __( 'Teapots', 'bacera' ), 'keywords' => [ 'teapot', 'tea pot', 'tea' ] ],
	[ 'key' => 'drinkware', 'label' => __( 'Drinkware', 'bacera' ), 'keywords' => [ 'drinkware', 'drink', 'cup', 'mug', 'glass' ] ],
	[ 'key' => 'bowls', 'label' => __( 'Bowls', 'bacera' ), 'keywords' => [ 'bowl' ] ],
	[ 'key' => 'kitchen', 'label' => __( 'Kitchen', 'bacera' ), 'keywords' => [ 'kitchen', 'cookware', 'pot' ] ],
	[ 'key' => 'plates', 'label' => __( 'Plates', 'bacera' ), 'keywords' => [ 'plate' ] ],
	[ 'key' => 'vases', 'label' => __( 'Vases & Decor', 'bacera' ), 'keywords' => [ 'vase', 'decor' ] ],
	[ 'key' => 'storage', 'label' => __( 'Storage', 'bacera' ), 'keywords' => [ 'storage', 'jar', 'canister' ] ],
];
$bacera_shop_by_tiles = [];
foreach ( $bacera_shop_by_defs as $def ) {
	$tid = '';
	if ( is_array( $def['keywords'] ) ) {
		$tid = bacera_shop_find_category_id_by_keywords( $categories_data, $def['keywords'] );
	}
	$bacera_shop_by_tiles[] = array_merge( $def, [ 'id' => $tid ] );
}

get_header();
?>

<main class="bacera-shop-page min-h-screen bg-gray-50/50 font-sans selection:bg-primary-500/10 selection:text-primary-800">
	<?php /* Layout 20/80: inline để không bị ghi đè / cache cũ của main.css; md+ = 2 cột (tránh chỉ &lt;1024px vẫn 1 cột). */ ?>
	<style id="bacera-shop-plp-layout">
		/* Lề trang Shop: CSS thuần (Tailwind px-* có thể không có trong main.css đã build). */
		main.bacera-shop-page {
			box-sizing: border-box;
			width: 100%;
			max-width: 100%;
			padding-top: 3.5rem;
			padding-bottom: 3.5rem;
			padding-left: max(1.25rem, env(safe-area-inset-left, 0px));
			padding-right: max(1.25rem, env(safe-area-inset-right, 0px));
		}
		@media (min-width: 640px) {
			main.bacera-shop-page {
				padding-left: max(1.75rem, env(safe-area-inset-left, 0px)) !important;
				padding-right: max(1.75rem, env(safe-area-inset-right, 0px)) !important;
			}
		}
		@media (min-width: 1024px) {
			main.bacera-shop-page {
				padding-top: 5rem !important;
				padding-bottom: 5rem !important;
				padding-left: max(3rem, min(10vw, 11rem)) !important;
				padding-right: max(3rem, min(10vw, 11rem)) !important;
			}
		}
		@media (min-width: 1280px) {
			main.bacera-shop-page {
				padding-left: max(4rem, min(11vw, 14rem)) !important;
				padding-right: max(4rem, min(11vw, 14rem)) !important;
			}
		}
		@media (min-width: 1536px) {
			main.bacera-shop-page {
				padding-left: max(5rem, min(12vw, 18rem)) !important;
				padding-right: max(5rem, min(12vw, 18rem)) !important;
			}
		}
		@media (min-width: 768px) {
			main .bacera-shop-split {
				display: grid !important;
				grid-template-columns: minmax(0, 1fr) minmax(0, 4fr) !important;
				align-items: start;
				gap: 2rem;
			}
			main .bacera-shop-split > aside,
			main .bacera-shop-split > .bacera-shop-main {
				min-width: 0;
			}
		}
		@media (min-width: 1024px) {
			main .bacera-shop-split { gap: 2.5rem; }
		}
		@media (min-width: 1280px) {
			main .bacera-shop-split { gap: 3rem; }
		}
		.bacera-shop-by-grid {
			justify-items: center;
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}
		.bacera-shop-by-tile {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			gap: 0.35rem;
			width: 100%;
			max-width: 5rem;
			min-height: 5rem;
			padding: 0.45rem 0.25rem 0.5rem;
			box-sizing: border-box;
			border-radius: 0.5rem;
			border: 1px solid rgb(231 229 228 / 0.9);
			background: rgb(255 255 255 / 0.65);
			color: rgb(41 37 36);
			font-size: 0.8125rem;
			font-weight: 500;
			line-height: 1.2;
			text-align: center;
			text-decoration: none;
			transition: background-color 0.15s ease, border-color 0.15s ease;
		}
		.bacera-shop-by-tile:hover:not(.bacera-shop-by-tile--disabled) {
			background: rgb(245 245 244);
		}
		.bacera-shop-by-tile.is-active {
			background: rgb(245 240 232);
			border-color: rgb(214 211 209);
			box-shadow: inset 0 0 0 1px rgb(231 229 228);
		}
		.bacera-shop-by-tile--disabled {
			opacity: 0.45;
			cursor: not-allowed;
		}
		@media (min-width: 640px) {
			.bacera-shop-by-grid {
				grid-template-columns: repeat(4, minmax(0, 1fr));
			}
		}
		@media (min-width: 1024px) {
			.bacera-shop-by-grid {
				grid-template-columns: repeat(8, minmax(0, 1fr));
			}
		}
		.bacera-cart-overlay {
			position: fixed;
			inset: 0;
			background: rgb(0 0 0 / 0.28);
			opacity: 0;
			pointer-events: none;
			transition: opacity 0.25s ease;
			z-index: 60;
		}
		.bacera-cart-overlay.is-open {
			opacity: 1;
			pointer-events: auto;
		}
		.bacera-cart-drawer {
			position: fixed;
			top: 0;
			right: 0;
			height: 100vh;
			width: min(560px, 92vw);
			background: #fff;
			box-shadow: -10px 0 28px rgb(41 37 36 / 0.18);
			transform: translateX(100%);
			transition: transform 0.28s ease;
			display: flex;
			flex-direction: column;
			z-index: 70;
		}
		.bacera-cart-drawer.is-open {
			transform: translateX(0);
		}
		.bacera-cart-drawer-head {
			padding: 2.5rem 1.25rem 1.25rem;
			border-bottom: 1px solid rgb(231 229 228);
		}
		.bacera-cart-items {
			flex: 1;
			overflow-y: auto;
			padding: 1.75rem 1.25rem 2.5rem;
		}
		.bacera-cart-item {
			display: grid;
			grid-template-columns: 96px minmax(0, 1fr);
			gap: 0.9rem;
			padding: 1.25rem 0;
			border-bottom: 1px solid rgb(231 229 228);
		}
		.bacera-cart-item img {
			width: 96px;
			height: 96px;
			border-radius: 0.65rem;
			object-fit: cover;
			background: rgb(245 245 244);
		}
		.bacera-cart-item-main {
			display: flex;
			align-items: flex-start;
			justify-content: space-between;
			gap: 0.5rem;
			min-width: 0;
		}
		.bacera-cart-item-text {
			min-width: 0;
			flex: 1;
		}
		.bacera-cart-variant-line {
			margin: 0.375rem 0 0;
			font-size: 0.9375rem;
			line-height: 1.45;
			color: rgb(87 83 78);
		}
		.bacera-cart-qty {
			display: inline-flex;
			align-items: center;
			border: 1px solid rgb(214 211 209);
			border-radius: 0.5rem;
			overflow: hidden;
		}
		.bacera-cart-qty button {
			width: 2rem;
			height: 2rem;
			border: 0;
			background: #fff;
			color: rgb(87 83 78);
			cursor: pointer;
		}
		.bacera-cart-qty span {
			min-width: 2rem;
			text-align: center;
			font-variant-numeric: tabular-nums;
			color: rgb(41 37 36);
		}
		.bacera-cart-remove {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
			width: 2.25rem;
			height: 2.25rem;
			padding: 0;
			border: 0;
			border-radius: 0.5rem;
			background: transparent;
			color: rgb(168 162 158);
			cursor: pointer;
			transition: color 0.15s ease, background-color 0.15s ease;
		}
		.bacera-cart-remove:hover {
			color: rgb(87 83 78);
			background: rgb(245 245 244);
		}
		.bacera-cart-remove svg {
			display: block;
		}
		.bacera-cart-footer {
			border-top: 1px solid rgb(231 229 228);
			padding: 1.75rem 1.25rem 2.5rem;
			background: #fff;
		}
		.bacera-cart-empty {
			padding: 2.5rem 1.25rem;
			color: rgb(120 113 108);
			font-size: 0.95rem;
		}
	</style>
	<div class="max-w-[90rem] mx-auto px-0">

		<header class="mb-10 md:mb-12 rounded-2xl border border-stone-200/80 bg-stone-50 px-5 py-8 md:px-8 md:py-10">
			<nav class="text-xs md:text-sm text-stone-500 mb-4 font-sans" aria-label="<?php esc_attr_e( 'Breadcrumb', 'bacera' ); ?>">
				<ol class="flex flex-wrap items-center gap-x-2 gap-y-1 list-none m-0 p-0">
					<li><a class="hover:text-primary-700 transition-colors" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Homepage', 'bacera' ); ?></a></li>
					<li class="text-stone-300 select-none" aria-hidden="true">/</li>
					<li><a class="hover:text-primary-700 transition-colors" href="<?php echo esc_url( $url_shop_clear_cats ); ?>"><?php esc_html_e( 'Shop all', 'bacera' ); ?></a></li>
				</ol>
			</nav>
			<h1 class="font-serif text-3xl md:text-4xl font-semibold text-stone-800 tracking-tight mb-6 md:mb-8"><?php esc_html_e( 'Shop by', 'bacera' ); ?></h1>
			<div class="bacera-shop-by-grid grid gap-3 md:gap-4">
				<?php foreach ( $bacera_shop_by_tiles as $tile ) : ?>
					<?php
					$key = $tile['key'];
					$tid = (string) $tile['id'];
					$is_all = ( $key === 'all' );
					$href   = bacera_shop_by_tile_url( $shop_base_url, $sort, $is_all ? '' : $tid );
					$active = $is_all
						? empty( $selected_category_ids )
						: ( $tid !== '' && count( $selected_category_ids ) === 1 && (string) $selected_category_ids[0] === $tid );
					$tile_classes = 'bacera-shop-by-tile font-sans' . ( $active ? ' is-active' : '' );
					if ( ! $is_all && $tid === '' ) {
						$tile_classes .= ' bacera-shop-by-tile--disabled';
					}
					?>
					<?php if ( $is_all || $tid !== '' ) : ?>
						<a class="<?php echo esc_attr( $tile_classes ); ?>" href="<?php echo esc_url( $href ); ?>" <?php echo $active ? 'aria-current="page"' : ''; ?>>
							<span class="text-stone-800 [&_svg]:stroke-stone-800"><?php echo bacera_shop_by_tile_icon( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span><?php echo esc_html( $tile['label'] ); ?></span>
						</a>
					<?php else : ?>
						<span class="<?php echo esc_attr( $tile_classes ); ?>" role="presentation" title="<?php esc_attr_e( 'Category not available in store', 'bacera' ); ?>">
							<span class="text-stone-800 [&_svg]:stroke-stone-800"><?php echo bacera_shop_by_tile_icon( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span><?php echo esc_html( $tile['label'] ); ?></span>
						</span>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</header>

		<?php if ( ! class_exists( 'Pancake_API_Client' ) || ! class_exists( 'Bacera_Utils' ) ) : ?>
			<div class="rounded-[2rem] border border-gray-100 bg-white p-8 text-center text-gray-700 shadow-sm">
				<?php esc_html_e( 'Pancake integration is not active. Please enable the Bacera Pancake plugin.', 'bacera' ); ?>
			</div>
		<?php elseif ( $products_response === false ) : ?>
			<div class="rounded-[2rem] border border-gray-100 bg-white p-8 text-center text-gray-700 shadow-sm">
				<?php esc_html_e( 'Could not reach the shop API. Check API key and Shop ID in settings.', 'bacera' ); ?>
			</div>
		<?php elseif ( $products_api_error ) : ?>
			<div class="rounded-[2rem] border border-gray-100 bg-white p-8 text-center text-gray-700 shadow-sm">
				<?php esc_html_e( 'The shop API returned an error. Try again later.', 'bacera' ); ?>
			</div>
		<?php else : ?>

		<?php
		// Vertical split: 20% sidebar / 80% main — CSS chính trong <style id="bacera-shop-plp-layout"> (+ .bacera-shop-split trong theme CSS).
		?>
		<div class="bacera-shop-split grid grid-cols-1">
			<aside class="min-w-0 w-full md:max-w-none border border-gray-100 rounded-2xl bg-white p-5 md:p-6 shadow-sm md:sticky md:top-24 space-y-8" aria-label="<?php esc_attr_e( 'Filters and categories', 'bacera' ); ?>">
				<form method="get" action="<?php echo esc_url( $shop_base_url ); ?>" id="bacera-shop-sidebar-form" class="space-y-8">
					<input type="hidden" name="shop_page" value="1" />
					<?php bacera_shop_sidebar_hidden_inputs(); ?>

					<?php
					$chk = static function ( $name, $value ) {
						$cur = isset( $_GET[ $name ] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_GET[ $name ] ) ) : [];
						return in_array( (string) $value, array_map( 'strval', $cur ), true );
					};
					$chk_cat = static function ( $cid ) use ( $selected_category_ids ) {
						return in_array( (string) $cid, array_map( 'strval', $selected_category_ids ), true );
					};
					?>
					<div>
						<h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest border-b border-gray-100 pb-2 mb-3"><?php esc_html_e( 'Collection', 'bacera' ); ?></h2>
						<ul class="space-y-2 list-none m-0 p-0">
							<li>
								<a href="<?php echo esc_url( $url_shop_clear_cats ); ?>" class="block rounded-lg px-2 py-2 text-sm font-medium transition-colors <?php echo empty( $selected_category_ids ) ? 'bg-primary-100 text-primary-900' : 'text-gray-700 hover:bg-gray-50'; ?>">
									<?php esc_html_e( 'All products', 'bacera' ); ?>
								</a>
							</li>
							<?php foreach ( $categories_data as $cat ) : ?>
								<?php
								if ( ! is_array( $cat ) ) {
									continue;
								}
								$cid   = isset( $cat['id'] ) ? (string) $cat['id'] : ( isset( $cat['category_id'] ) ? (string) $cat['category_id'] : '' );
								$label = $cat['text'] ?? $cat['name'] ?? '';
								if ( $cid === '' ) {
									continue;
								}
								$fid = 'filter-cat-' . preg_replace( '/[^a-zA-Z0-9_-]/', '', $cid );
								?>
								<li class="flex items-start gap-2">
									<input class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500" type="checkbox" name="filter_collection[]" value="<?php echo esc_attr( $cid ); ?>" id="<?php echo esc_attr( $fid ); ?>" <?php checked( $chk_cat( $cid ) ); ?> onchange="this.form.submit()" />
									<label class="text-sm text-gray-700 cursor-pointer leading-snug" for="<?php echo esc_attr( $fid ); ?>"><?php echo esc_html( $label !== '' ? $label : $cid ); ?></label>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>

					<?php
					$bacera_filter_groups = [
						[
							'heading' => __( 'Capacity', 'bacera' ),
							'name'    => 'filter_capacity',
							'options' => [
								'lt100'   => '<100ml',
								'100_150' => '100–150ml',
								'150_250' => '150–250ml',
								'gt250'   => '>250ml',
							],
						],
						[
							'heading' => __( 'Handle Type', 'bacera' ),
							'name'    => 'filter_handle',
							'options' => [
								'with_handle'    => __( 'With handle', 'bacera' ),
								'without_handle' => __( 'Without handle', 'bacera' ),
								'round_loop'     => __( 'Round loop', 'bacera' ),
								'hollow_handle'  => __( 'Hollow handle', 'bacera' ),
							],
						],
						[
							'heading' => __( 'Glaze Finish', 'bacera' ),
							'name'    => 'filter_glaze',
							'options' => [
								'smooth'   => __( 'Smooth', 'bacera' ),
								'reactive' => __( 'Reactive', 'bacera' ),
								'matte'    => __( 'Matte', 'bacera' ),
								'glossy'   => __( 'Glossy', 'bacera' ),
							],
						],
						[
							'heading' => __( 'Shape', 'bacera' ),
							'name'    => 'filter_shape',
							'options' => [
								'round'      => __( 'Round', 'bacera' ),
								'slim'       => __( 'Slim', 'bacera' ),
								'tall'       => __( 'Tall', 'bacera' ),
								'flared_rim' => __( 'Flared rim', 'bacera' ),
							],
						],
						[
							'heading' => __( 'Best For', 'bacera' ),
							'name'    => 'filter_best_for',
							'options' => [
								'tea'      => __( 'Tea', 'bacera' ),
								'coffee'   => __( 'Coffee', 'bacera' ),
								'espresso' => __( 'Espresso', 'bacera' ),
								'alcohol'  => __( 'Alcohol', 'bacera' ),
								'juice'    => __( 'Juice', 'bacera' ),
							],
						],
					];
					foreach ( $bacera_filter_groups as $grp ) :
						$gname = $grp['name'];
						?>
					<div>
						<h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest border-b border-gray-100 pb-2 mb-3"><?php echo esc_html( $grp['heading'] ); ?></h2>
						<ul class="space-y-2 list-none m-0 p-0">
							<?php foreach ( $grp['options'] as $val => $lab ) : ?>
								<?php
								$fid = 'bacera-' . sanitize_key( $gname . '-' . $val );
								?>
								<li class="flex items-start gap-2">
									<input class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500" type="checkbox" name="<?php echo esc_attr( $gname ); ?>[]" value="<?php echo esc_attr( $val ); ?>" id="<?php echo esc_attr( $fid ); ?>" <?php checked( $chk( $gname, $val ) ); ?> onchange="this.form.submit()" />
									<label class="text-sm text-gray-700 cursor-pointer leading-snug" for="<?php echo esc_attr( $fid ); ?>"><?php echo esc_html( is_string( $lab ) ? $lab : (string) $lab ); ?></label>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php endforeach; ?>

					<div>
						<h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest border-b border-gray-100 pb-2 mb-3"><?php esc_html_e( 'Price', 'bacera' ); ?></h2>
						<ul class="space-y-2 list-none m-0 p-0">
							<?php
							$price_opts = [
								''   => __( 'Any', 'bacera' ),
								'p1' => __( 'Under $30', 'bacera' ),
								'p2' => __( '$30–$60', 'bacera' ),
								'p3' => __( '$60–$100', 'bacera' ),
								'p4' => __( 'Above $100', 'bacera' ),
							];
							foreach ( $price_opts as $val => $lab ) :
								$fid = $val === '' ? 'filter-price-any' : 'filter-price-' . $val;
								?>
								<li class="flex items-center gap-2">
									<input class="border-gray-300 text-primary-600 focus:ring-primary-500" type="radio" name="filter_price" value="<?php echo esc_attr( $val ); ?>" id="<?php echo esc_attr( $fid ); ?>" <?php checked( isset( $_GET['filter_price'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_price'] ) ) : '', $val ); ?> onchange="this.form.submit()" />
									<label class="text-sm text-gray-700 cursor-pointer" for="<?php echo esc_attr( $fid ); ?>"><?php echo esc_html( $lab ); ?></label>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>

					<div>
						<h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest border-b border-gray-100 pb-2 mb-3"><?php esc_html_e( 'Size', 'bacera' ); ?></h2>
						<ul class="space-y-2 list-none m-0 p-0">
							<?php foreach ( [ 'small' => __( 'Small', 'bacera' ), 'medium' => __( 'Medium', 'bacera' ), 'large' => __( 'Large', 'bacera' ) ] as $val => $lab ) : ?>
								<li class="flex items-start gap-2">
									<input class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500" type="checkbox" name="filter_size[]" value="<?php echo esc_attr( $val ); ?>" id="filter-size-<?php echo esc_attr( $val ); ?>" <?php checked( $chk( 'filter_size', $val ) ); ?> onchange="this.form.submit()" />
									<label class="text-sm text-gray-700 cursor-pointer" for="filter-size-<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $lab ); ?></label>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				</form>
			</aside>

			<div class="bacera-shop-main min-w-0 w-full flex flex-col gap-8">
				<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
					<p class="text-body-small text-gray-600 m-0">
						<?php
						if ( ! empty( $items ) ) {
							printf(
								/* translators: %d: product count */
								esc_html( _n( '%d product', '%d products', count( $items ), 'bacera' ) ),
								(int) count( $items )
							);
						} else {
							esc_html_e( 'No products in this view.', 'bacera' );
						}
						?>
					</p>
					<form method="get" class="flex items-center gap-2 shrink-0" action="<?php echo esc_url( $shop_base_url ); ?>">
						<input type="hidden" name="shop_page" value="1" />
						<?php
						foreach ( $selected_category_ids as $sid ) {
							echo '<input type="hidden" name="filter_collection[]" value="' . esc_attr( $sid ) . '" />';
						}
						if ( isset( $_GET['filter_price'] ) && $_GET['filter_price'] !== '' ) {
							echo '<input type="hidden" name="filter_price" value="' . esc_attr( sanitize_text_field( wp_unslash( $_GET['filter_price'] ) ) ) . '" />';
						}
						$bacera_sort_array_keys = [ 'filter_size', 'filter_capacity', 'filter_handle', 'filter_glaze', 'filter_shape', 'filter_best_for' ];
						foreach ( $bacera_sort_array_keys as $pk ) {
							if ( empty( $_GET[ $pk ] ) ) {
								continue;
							}
							foreach ( (array) wp_unslash( $_GET[ $pk ] ) as $pv ) {
								echo '<input type="hidden" name="' . esc_attr( $pk ) . '[]" value="' . esc_attr( sanitize_text_field( $pv ) ) . '" />';
							}
						}
						?>
						<label for="bacera-shop-sort" class="text-body-small text-gray-700 whitespace-nowrap"><?php esc_html_e( 'Sort by:', 'bacera' ); ?></label>
						<select name="sort" id="bacera-shop-sort" onchange="this.form.submit()" class="text-body-small rounded-lg border border-gray-200 bg-white text-gray-900 py-2 pl-3 pr-8 focus:ring-2 focus:ring-primary-400 focus:border-primary-400">
							<option value="price_low" <?php selected( $sort, 'price_low' ); ?>><?php esc_html_e( 'Price low – high', 'bacera' ); ?></option>
							<option value="price_high" <?php selected( $sort, 'price_high' ); ?>><?php esc_html_e( 'Price high – low', 'bacera' ); ?></option>
							<option value="newest" <?php selected( $sort, 'newest' ); ?>><?php esc_html_e( 'Newest', 'bacera' ); ?></option>
						</select>
					</form>
				</div>

				<?php
				// Lọc cục bộ: giá (VND), danh mục (multi), size + bộ filter thuộc tính (từ khóa trong tên/mô tả).
				if ( ! empty( $items ) ) {
					$fp = isset( $_GET['filter_price'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_price'] ) ) : '';
					$fs = isset( $_GET['filter_size'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_GET['filter_size'] ) ) : [];
					$f_cap  = isset( $_GET['filter_capacity'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_GET['filter_capacity'] ) ) : [];
					$f_hand = isset( $_GET['filter_handle'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_GET['filter_handle'] ) ) : [];
					$f_glz  = isset( $_GET['filter_glaze'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_GET['filter_glaze'] ) ) : [];
					$f_shp  = isset( $_GET['filter_shape'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_GET['filter_shape'] ) ) : [];
					$f_best = isset( $_GET['filter_best_for'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_GET['filter_best_for'] ) ) : [];
					$fcats  = count( $selected_category_ids ) > 1 ? $selected_category_ids : [];
					$needle_maps = bacera_shop_attribute_filter_needle_maps();

					$items = array_values(
						array_filter(
							$items,
							static function ( $p ) use ( $fp, $fcats, $fs, $f_cap, $f_hand, $f_glz, $f_shp, $f_best, $needle_maps ) {
								$pa = isset( $p['price_at_counter'] ) ? (float) $p['price_at_counter'] : ( isset( $p['variations'][0]['price_at_counter'] ) ? (float) $p['variations'][0]['price_at_counter'] : 0 );
								$ra = isset( $p['retail_price'] ) ? (float) $p['retail_price'] : ( isset( $p['variations'][0]['retail_price'] ) ? (float) $p['variations'][0]['retail_price'] : 0 );
								$pr = $pa > 0 ? $pa : $ra;
								if ( ! bacera_shop_price_band_matches( $pr, $fp ) ) {
									return false;
								}

								$haystack = bacera_shop_filter_haystack( $p );

								if ( ! empty( $fcats ) ) {
									$p_cat_ids = bacera_shop_product_category_ids( $p );
									$want       = array_map( 'strval', $fcats );
									$have       = array_map( 'strval', $p_cat_ids );
									if ( count( array_intersect( $want, $have ) ) === 0 ) {
										return false;
									}
								}

								if ( ! bacera_shop_keyword_group_match( $haystack, $f_cap, $needle_maps['capacity'] ) ) {
									return false;
								}
								if ( ! bacera_shop_keyword_group_match( $haystack, $f_hand, $needle_maps['handle'] ) ) {
									return false;
								}
								if ( ! bacera_shop_keyword_group_match( $haystack, $f_glz, $needle_maps['glaze'] ) ) {
									return false;
								}
								if ( ! bacera_shop_keyword_group_match( $haystack, $f_shp, $needle_maps['shape'] ) ) {
									return false;
								}
								if ( ! bacera_shop_keyword_group_match( $haystack, $f_best, $needle_maps['best_for'] ) ) {
									return false;
								}

								if ( ! empty( $fs ) ) {
									$ok = false;
									foreach ( $fs as $sz ) {
										if ( strpos( $haystack, strtolower( (string) $sz ) ) !== false ) {
											$ok = true;
											break;
										}
									}
									if ( ! $ok ) {
										return false;
									}
								}

								return true;
							}
						)
					);
				}

				if ( $sort === 'price_high' || $sort === 'price_low' ) {
					usort(
						$items,
						function ( $a, $b ) use ( $sort ) {
							$pa = isset( $a['price_at_counter'] ) ? (float) $a['price_at_counter'] : ( isset( $a['variations'][0]['price_at_counter'] ) ? (float) $a['variations'][0]['price_at_counter'] : 0 );
							$pb = isset( $b['price_at_counter'] ) ? (float) $b['price_at_counter'] : ( isset( $b['variations'][0]['price_at_counter'] ) ? (float) $b['variations'][0]['price_at_counter'] : 0 );
							$ra = isset( $a['retail_price'] ) ? (float) $a['retail_price'] : ( isset( $a['variations'][0]['retail_price'] ) ? (float) $a['variations'][0]['retail_price'] : 0 );
							$rb = isset( $b['retail_price'] ) ? (float) $b['retail_price'] : ( isset( $b['variations'][0]['retail_price'] ) ? (float) $b['variations'][0]['retail_price'] : 0 );
							$a_price = $pa > 0 ? $pa : $ra;
							$b_price = $pb > 0 ? $pb : $rb;
							return $sort === 'price_high' ? $b_price <=> $a_price : $a_price <=> $b_price;
						}
					);
				} elseif ( $sort === 'newest' ) {
					usort(
						$items,
						function ( $a, $b ) {
							$ia = isset( $a['id'] ) ? (int) $a['id'] : 0;
							$ib = isset( $b['id'] ) ? (int) $b['id'] : 0;
							return $ib <=> $ia;
						}
					);
				}
				?>

				<?php if ( empty( $items ) ) : ?>
					<div class="rounded-[2rem] border border-gray-100 bg-white p-12 text-center shadow-sm">
						<p class="text-h2 font-bold text-gray-900 m-0 mb-2"><?php esc_html_e( 'No products', 'bacera' ); ?></p>
						<p class="text-body-small text-gray-500 m-0"><?php esc_html_e( 'Try adjusting filters or category.', 'bacera' ); ?></p>
					</div>
				<?php else : ?>
					<section class="bg-white rounded-[2rem] p-6 md:p-10 shadow-sm border border-gray-100">
						<?php /* Đúng 3 cột trên desktop: grid-cols-3 từ breakpoint lg. */ ?>
						<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-10 lg:gap-x-8 lg:gap-y-10 w-full">
							<?php
							foreach ( $items as $p ) :
								$name = $p['product']['name'] ?? $p['name'] ?? __( 'Product', 'bacera' );

								$image_url = Bacera_Utils::get_proxy_url( $p );

								$price_at_counter = isset( $p['price_at_counter'] ) ? (float) $p['price_at_counter'] : ( isset( $p['variations'][0]['price_at_counter'] ) ? (float) $p['variations'][0]['price_at_counter'] : 0 );
								$retail_price     = isset( $p['retail_price'] ) ? (float) $p['retail_price'] : ( isset( $p['variations'][0]['retail_price'] ) ? (float) $p['variations'][0]['retail_price'] : 0 );

								$price          = $price_at_counter > 0 ? $price_at_counter : $retail_price;
								$original_price = ( $retail_price > $price ) ? $retail_price : 0;

								$discount_percent = false;
								if ( $original_price > 0 && $price < $original_price ) {
									$discount_percent = '-' . (string) (int) round( ( ( $original_price - $price ) / $original_price ) * 100 ) . '%';
								}

								$brand_fallback_ids = count( $selected_category_ids ) === 1 ? $selected_category_ids : [];
								$brand              = bacera_shop_product_category_display_name( $p, $categories_data, $brand_fallback_ids );
								if ( $brand === '' ) {
									$brand = __( 'Bacera', 'bacera' );
								}

								$product_detail_url = class_exists( 'Bacera_Utils' ) ? Bacera_Utils::get_product_permalink( $p ) : '';
								$variation_id       = isset( $p['id'] ) ? (string) $p['id'] : '';
								$product_id         = isset( $p['product']['id'] ) ? (string) $p['product']['id'] : ( isset( $p['product_id'] ) ? (string) $p['product_id'] : '' );
								$cart_item_uid      = $variation_id !== '' ? 'var_' . $variation_id : 'prd_' . $product_id . '_' . md5( $name . '|' . $price );
								$cart_variant_label = $p['name'] ?? '';
								$cart_color         = $p['color_name'] ?? $p['color'] ?? '';
								$cart_size          = $p['size_name'] ?? $p['size'] ?? $p['capacity'] ?? '';

								get_template_part(
									'app/Views/components/product-card',
									null,
									[
										'title'       => $name,
										'brand'       => $brand,
										'price'       => number_format( $price, 0, ',', '.' ) . ' ₫',
										'old_price'   => $original_price > 0 ? number_format( $original_price, 0, ',', '.' ) . ' ₫' : '',
										'image'       => $image_url,
										'discount'    => $discount_percent,
										'url'         => $product_detail_url,
										'add_to_cart' => true,
										'cart_item'   => [
											'id'             => $cart_item_uid,
											'variation_id'   => $variation_id,
											'product_id'     => $product_id,
											'name'           => $name,
											'brand'          => $brand,
											'variant_label'  => is_string( $cart_variant_label ) ? $cart_variant_label : '',
											'color'          => is_string( $cart_color ) ? $cart_color : '',
											'size'           => is_string( $cart_size ) ? $cart_size : '',
											'image'          => $image_url,
											'price'          => (float) $price,
											'original_price' => (float) $original_price,
											'url'            => $product_detail_url,
										],
									]
								);
							endforeach;
							?>
						</div>
					</section>

					<?php if ( $total_pages > 1 ) : ?>
						<nav class="flex flex-wrap items-center gap-2 md:gap-3" aria-label="<?php esc_attr_e( 'Pagination', 'bacera' ); ?>">
							<?php
							$tp        = (int) $total_pages;
							$nav_pages = [];
							if ( $tp <= 9 ) {
								for ( $i = 1; $i <= $tp; $i++ ) {
									$nav_pages[] = $i;
								}
							} else {
								for ( $i = 1; $i <= 5; $i++ ) {
									$nav_pages[] = $i;
								}
								$nav_pages[] = 'ellipsis';
								$nav_pages[] = $tp;
							}
							foreach ( $nav_pages as $entry ) :
								if ( $entry === 'ellipsis' ) :
									?>
									<span class="text-gray-400 px-1 select-none">…</span>
									<?php
									continue;
								endif;
								$n = (int) $entry;
								$pargs = [];
								foreach ( $_GET as $gk => $gv ) {
									$gk = sanitize_key( $gk );
									if ( is_array( $gv ) ) {
										$pargs[ $gk ] = array_map(
											static function ( $one ) {
												return sanitize_text_field( wp_unslash( $one ) );
											},
											$gv
										);
									} else {
										$pargs[ $gk ] = sanitize_text_field( wp_unslash( $gv ) );
									}
								}
								$pargs['shop_page'] = $n;
								$purl               = add_query_arg( $pargs, $shop_base_url );
								$is_current = ( $n === $current_page );
								?>
								<a href="<?php echo esc_url( $purl ); ?>" class="text-[14px] font-medium tabular-nums px-1.5 py-1 rounded min-w-[2rem] text-center <?php echo $is_current ? 'text-primary-800 underline decoration-primary-400 underline-offset-4' : 'text-primary-600 hover:text-primary-900'; ?>">
									<?php echo esc_html( str_pad( (string) $n, 2, '0', STR_PAD_LEFT ) ); ?>
								</a>
							<?php endforeach; ?>
						</nav>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>

		<?php endif; ?>
	</div>
	<div id="bacera-cart-overlay" class="bacera-cart-overlay" aria-hidden="true"></div>
	<aside id="bacera-cart-drawer" class="bacera-cart-drawer" aria-hidden="true" aria-label="<?php esc_attr_e( 'Shopping cart', 'bacera' ); ?>">
		<div class="bacera-cart-drawer-head flex items-center justify-between">
			<h2 class="m-0 font-serif font-bold text-[2rem] leading-none text-stone-800"><?php esc_html_e( 'Giỏ hàng', 'bacera' ); ?></h2>
			<button type="button" id="bacera-cart-close" class="h-9 w-9 rounded-full border border-stone-200 text-xl leading-none text-stone-600 hover:bg-stone-50" aria-label="<?php esc_attr_e( 'Close cart', 'bacera' ); ?>">×</button>
		</div>
		<div id="bacera-cart-items" class="bacera-cart-items"></div>
		<div class="bacera-cart-footer">
			<div class="flex items-end justify-between gap-4 mb-4">
				<div>
					<p class="m-0 text-[1.75rem] leading-none font-serif font-bold text-stone-800"><?php esc_html_e( 'Giỏ hàng', 'bacera' ); ?></p>
					<p class="m-0 mt-1 text-base text-stone-600"><?php esc_html_e( 'Total (VAT included)', 'bacera' ); ?></p>
				</div>
				<p id="bacera-cart-total" class="m-0 text-[2rem] leading-none tabular-nums text-stone-800">0đ</p>
			</div>
			<div class="grid grid-cols-2 gap-2">
				<a id="bacera-cart-go-checkout" href="<?php echo esc_url( $checkout_shipping_url ); ?>" class="rounded-xl bg-accent-500 px-4 py-3 text-center font-medium text-white no-underline hover:bg-accent-600"><?php esc_html_e( 'Đến thanh toán', 'bacera' ); ?></a>
				<a id="bacera-cart-go-cart" href="<?php echo esc_url( $cart_page_url ); ?>" class="rounded-xl border border-stone-300 px-4 py-3 text-center font-medium text-stone-700 no-underline hover:bg-stone-50"><?php esc_html_e( 'Xem giỏ hàng', 'bacera' ); ?></a>
			</div>
		</div>
	</aside>
</main>

<script>
(function () {
	var drawer = document.getElementById('bacera-cart-drawer');
	var overlay = document.getElementById('bacera-cart-overlay');
	var closeBtn = document.getElementById('bacera-cart-close');
	var listEl = document.getElementById('bacera-cart-items');
	var totalEl = document.getElementById('bacera-cart-total');
	if (!drawer || !overlay || !closeBtn || !listEl || !totalEl) return;

	var STORAGE_KEY = 'bacera_shop_cart_v1';
	var CHECKOUT_ITEMS_KEY = 'bacera_checkout_items';
	/** Chỉ hiển thị trong panel dòng sản phẩm vừa thêm (theo id trong giỏ). */
	var drawerPreviewItemId = null;

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

	function toCurrency(numberValue) {
		var amount = Number(numberValue || 0);
		return amount.toLocaleString('vi-VN') + 'đ';
	}

	function escapeHtml(value) {
		return String(value || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function computeTotal(cart) {
		return cart.reduce(function (sum, item) {
			return sum + (Number(item.price || 0) * Number(item.qty || 0));
		}, 0);
	}

	var trashIconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>';

	function parseVariantDetails(item) {
		var color = String(item.color || '').trim();
		var size = String(item.size || '').trim();
		var label = String(item.variant_label || '').trim();
		if ((!color || !size) && label) {
			var parts = label.split('|').map(function (part) { return part.trim(); }).filter(Boolean);
			if (!color && parts[0]) color = parts[0];
			if (!size && parts[1]) size = parts[1];
		}
		return { color: color, size: size };
	}

	/** Một dòng dưới tên: "màu | kích thước" giống mockup. */
	function formatColorSizeSubtitle(meta, item) {
		var c = meta.color;
		var s = meta.size;
		if (c && s) return c + ' | ' + s;
		if (c) return c;
		if (s) return s;
		var label = String(item.variant_label || '').trim();
		return label;
	}

	function getDrawerDisplayCart() {
		var cart = loadCart();
		if (!drawerPreviewItemId) {
			return [];
		}
		return cart.filter(function (item) {
			return String(item.id) === String(drawerPreviewItemId);
		});
	}

	function renderCart() {
		var cart = getDrawerDisplayCart();
		if (!cart.length) {
			listEl.innerHTML = '<p class="bacera-cart-empty"><?php echo esc_js( __( 'Giỏ hàng của bạn đang trống.', 'bacera' ) ); ?></p>';
			totalEl.textContent = '0đ';
			return;
		}

		listEl.innerHTML = cart.map(function (item) {
			var title = escapeHtml(item.name || '<?php echo esc_js( __( 'Product', 'bacera' ) ); ?>');
			var brand = escapeHtml(item.brand || '<?php echo esc_js( __( 'Bacera', 'bacera' ) ); ?>');
			var variantMeta = parseVariantDetails(item);
			var variantSubtitle = formatColorSizeSubtitle(variantMeta, item);
			var variantLineHtml = variantSubtitle
				? '<p class="bacera-cart-variant-line">' + escapeHtml(variantSubtitle) + '</p>'
				: '';
			var image = escapeHtml(item.image || 'https://placehold.co/120x120/f0ece3/8d6a54?text=Product');
			var originalPrice = Number(item.original_price || 0);
			var oldPriceHtml = originalPrice > Number(item.price || 0)
				? '<span class="text-sm line-through text-stone-400 tabular-nums">' + toCurrency(originalPrice) + '</span>'
				: '';
			return ''
				+ '<article class="bacera-cart-item" data-cart-id="' + escapeHtml(item.id) + '">'
				+ '  <img src="' + image + '" alt="' + title + '" loading="lazy" />'
				+ '  <div class="min-w-0">'
				+ '    <div class="bacera-cart-item-main">'
				+ '      <div class="bacera-cart-item-text">'
				+ '        <p class="m-0 text-sm text-stone-500">' + brand + '</p>'
				+ '        <p class="m-0 mt-1 text-xl leading-snug font-medium text-stone-800">' + title + '</p>'
				+ variantLineHtml
				+ '      </div>'
				+ '      <button type="button" class="bacera-cart-remove" data-cart-action="remove" aria-label="<?php echo esc_js( __( 'Remove item', 'bacera' ) ); ?>">' + trashIconSvg + '</button>'
				+ '    </div>'
				+ '    <div class="mt-3 flex items-center justify-between gap-3">'
				+ '      <div class="bacera-cart-qty" role="group" aria-label="<?php echo esc_attr( __( 'Quantity', 'bacera' ) ); ?>">'
				+ '        <button type="button" data-cart-action="minus">−</button>'
				+ '        <span>' + String(Number(item.qty || 1)).padStart(2, '0') + '</span>'
				+ '        <button type="button" data-cart-action="plus">+</button>'
				+ '      </div>'
				+ '      <div class="text-right">'
				+ oldPriceHtml
				+ '        <p class="m-0 text-[1.75rem] leading-none tabular-nums text-stone-800">' + toCurrency(item.price) + '</p>'
				+ '      </div>'
				+ '    </div>'
				+ '  </div>'
				+ '</article>';
		}).join('');

		totalEl.textContent = toCurrency(computeTotal(cart));
	}

	function setDrawerOpen(opened) {
		if (!opened) {
			drawerPreviewItemId = null;
		}
		drawer.classList.toggle('is-open', opened);
		overlay.classList.toggle('is-open', opened);
		drawer.setAttribute('aria-hidden', opened ? 'false' : 'true');
		overlay.setAttribute('aria-hidden', opened ? 'false' : 'true');
		document.body.classList.toggle('overflow-hidden', opened);
	}

	function upsertItem(nextItem) {
		var cart = loadCart();
		var index = cart.findIndex(function (item) {
			return String(item.id) === String(nextItem.id);
		});
		if (index >= 0) {
			cart[index].qty = Number(cart[index].qty || 1) + 1;
		} else {
			nextItem.qty = 1;
			cart.push(nextItem);
		}
		saveCart(cart);
	}

	document.addEventListener('click', function (event) {
		var addBtn = event.target.closest('.bacera-shop-add-cart-btn');
		if (!addBtn) return;
		event.preventDefault();
		event.stopPropagation();
		var payloadRaw = addBtn.getAttribute('data-cart-item') || '';
		if (!payloadRaw) return;
		try {
			var payload = JSON.parse(payloadRaw);
			if (!payload || !payload.id) return;
			upsertItem(payload);
			drawerPreviewItemId = String(payload.id);
			renderCart();
			setDrawerOpen(true);
		} catch (e) {
			return;
		}
	});

	listEl.addEventListener('click', function (event) {
		var actionBtn = event.target.closest('button[data-cart-action]');
		if (!actionBtn) return;
		var row = actionBtn.closest('.bacera-cart-item');
		if (!row) return;
		var itemId = row.getAttribute('data-cart-id');
		if (!itemId) return;
		var action = actionBtn.getAttribute('data-cart-action');
		var cart = loadCart();
		var idx = cart.findIndex(function (item) {
			return String(item.id) === String(itemId);
		});
		if (idx < 0) return;
		if (action === 'remove') {
			cart.splice(idx, 1);
		} else if (action === 'minus') {
			cart[idx].qty = Number(cart[idx].qty || 1) - 1;
			if (cart[idx].qty <= 0) {
				cart.splice(idx, 1);
			}
		} else if (action === 'plus') {
			cart[idx].qty = Number(cart[idx].qty || 1) + 1;
		}
		saveCart(cart);
		renderCart();
	});

	var checkoutLink = document.getElementById('bacera-cart-go-checkout');
	if (checkoutLink) {
		checkoutLink.addEventListener('click', function (e) {
			var full = loadCart();
			var picked = drawerPreviewItemId
				? full.filter(function (item) { return String(item.id) === String(drawerPreviewItemId); })
				: [];
			if (!picked.length) {
				e.preventDefault();
				return;
			}
			try {
				sessionStorage.setItem(CHECKOUT_ITEMS_KEY, JSON.stringify(picked));
			} catch (err) {}
		});
	}

	closeBtn.addEventListener('click', function () { setDrawerOpen(false); });
	overlay.addEventListener('click', function () { setDrawerOpen(false); });
	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') setDrawerOpen(false);
	});

	renderCart();
})();
</script>

<?php
get_footer();
