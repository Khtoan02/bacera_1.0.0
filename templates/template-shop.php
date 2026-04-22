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

<main class="bacera-shop-page min-h-screen font-sans" style="background:#F8F4EE;">
<style id="bacera-shop-redesign-css">
:root {
	--bsh-cream:       #F8F4EE;
	--bsh-cream-mid:   #F2EDE5;
	--bsh-sand:        #E8DFD3;
	--bsh-clay-100:    #E5D8CC;
	--bsh-clay-300:    #C0A28E;
	--bsh-clay-500:    #A9846B;
	--bsh-clay-600:    #8D6A54;
	--bsh-clay-700:    #6B5344;
	--bsh-clay-800:    #4d3d32;
	--bsh-clay-900:    #3d2f26;
	--bsh-accent:      #C06B3A;
	--bsh-text:        #2A1F17;
	--bsh-text-sec:    #6B5344;
	--bsh-text-muted:  #9A8478;
	--bsh-border:      #DDD5CB;
	--bsh-border-lt:   #EDE6DD;
	--bsh-white:       #FFFFFF;
}
.bsh-wrap {
	max-width: 1440px; margin: 0 auto;
	padding: clamp(2rem,5vw,4rem) clamp(1.25rem,4vw,3rem) 5rem;
}
/* Hero */
.bsh-hero {
	background: linear-gradient(135deg,#F2EBE0 0%,#EAE0D5 40%,#DDD0C4 100%);
	border-radius: 28px;
	padding: clamp(2rem,4vw,3.5rem) clamp(1.5rem,4vw,3rem) clamp(1.75rem,3.5vw,3rem);
	margin-bottom: 2.5rem; position: relative; overflow: hidden;
	display: flex; flex-direction: column; gap: 1.75rem;
}
.bsh-hero::after {
	content:''; position:absolute; right:-80px; top:-80px;
	width:340px; height:340px; border-radius:50%;
	background:radial-gradient(circle,rgba(192,107,58,.10) 0%,transparent 70%);
	pointer-events:none;
}
.bsh-hero-label { font-size:.68rem; font-weight:700; letter-spacing:.18em; text-transform:uppercase; color:#8D6A54; }
.bsh-hero-title { font-family:'Cormorant Garamond',Georgia,serif; font-size:clamp(1.9rem,3.5vw,3rem); font-weight:400; line-height:1.15; color:#2A1F17; max-width:500px; margin-top:.5rem; }
.bsh-hero-title em { font-style:italic; color:#C06B3A; }
/* Cat pills */
.bsh-cats { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; }
.bsh-cat-label { font-size:.7rem; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:#9A8478; margin-right:.25rem; }
.bsh-cat-pill {
	display:inline-flex; align-items:center; gap:.35rem;
	padding:.4rem 1rem; border-radius:999px;
	font-family:inherit; font-size:.78rem; font-weight:500;
	border:1px solid #DDD5CB; background:#fff; color:#6B5344;
	cursor:pointer; text-decoration:none;
	transition:all .2s;
}
.bsh-cat-pill:hover { border-color:#A9846B; color:#4d3d32; }
.bsh-cat-pill.is-active { background:#4d3d32; color:#fff; border-color:#4d3d32; }
/* Layout split */
.bsh-split { display:grid; grid-template-columns:220px 1fr; gap:2rem; align-items:start; }
@media(max-width:900px){ .bsh-split{grid-template-columns:1fr;} .bsh-sidebar{display:none;} .bsh-filter-fab{display:flex!important;} }
/* Sidebar */
.bsh-sidebar { position:sticky; top:88px; background:#fff; border:1px solid #EDE6DD; border-radius:20px; padding:1.5rem 1.25rem; box-shadow:0 1px 3px rgba(42,31,23,.06); }
.bsh-sidebar-heading { font-size:.62rem; font-weight:700; letter-spacing:.18em; text-transform:uppercase; color:#9A8478; padding-bottom:.6rem; border-bottom:1px solid #EDE6DD; margin-bottom:.85rem; }
.bsh-sidebar-section { margin-bottom:1.5rem; }
.bsh-sidebar-section:last-child { margin-bottom:0; }
.bsh-filter-list { list-style:none; display:flex; flex-direction:column; gap:.2rem; }
.bsh-filter-item { display:flex; align-items:center; gap:.55rem; }
.bsh-filter-item label { font-size:.83rem; color:#6B5344; cursor:pointer; flex:1; transition:color .15s; }
.bsh-filter-item label:hover { color:#2A1F17; }
.bsh-filter-item input[type="checkbox"], .bsh-filter-item input[type="radio"] {
	width:14px; height:14px; flex-shrink:0; border:1.5px solid #C0A28E; border-radius:3px;
	appearance:none; cursor:pointer; position:relative; transition:background .15s,border-color .15s;
	margin-top:0;
}
.bsh-filter-item input[type="radio"] { border-radius:50%; }
.bsh-filter-item input[type="checkbox"]:checked, .bsh-filter-item input[type="radio"]:checked { background:#4d3d32; border-color:#4d3d32; }
.bsh-filter-item input[type="checkbox"]:checked::after { content:'✓'; position:absolute; font-size:9px; color:#fff; font-weight:700; top:50%; left:50%; transform:translate(-50%,-50%); }
.bsh-filter-item input[type="radio"]:checked::after { content:''; position:absolute; width:5px; height:5px; border-radius:50%; background:#fff; top:50%; left:50%; transform:translate(-50%,-50%); }
.bsh-all-link { display:block; border-radius:14px; padding:.45rem .6rem; font-size:.83rem; font-weight:500; text-decoration:none; transition:background .15s,color .15s; }
.bsh-all-link.is-active { background:#E5D8CC; color:#4d3d32; }
.bsh-all-link:not(.is-active) { color:#6B5344; }
.bsh-all-link:not(.is-active):hover { background:#F2EDE5; }
/* Toolbar */
.bsh-toolbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; flex-wrap:wrap; gap:.6rem; }
.bsh-product-count { font-size:.83rem; color:#9A8478; }
.bsh-product-count strong { color:#2A1F17; font-weight:600; }
.bsh-sort-select {
	font-family:inherit; font-size:.78rem; color:#2A1F17; background:#fff;
	border:1px solid #DDD5CB; border-radius:8px; padding:.4rem 2rem .4rem .7rem;
	appearance:none; cursor:pointer; outline:none;
	background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236B5344' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
	background-repeat:no-repeat; background-position:right .6rem center; transition:border-color .2s;
}
.bsh-sort-select:focus { border-color:#A9846B; }
/* Product grid */
.bsh-product-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1.1rem; }
@media(max-width:1100px){ .bsh-product-grid{grid-template-columns:repeat(2,minmax(0,1fr));} }
@media(max-width:600px){ .bsh-product-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem;} }
/* Empty */
.bsh-empty { border-radius:28px; border:1px solid #EDE6DD; background:#fff; padding:4rem 2rem; text-align:center; }
.bsh-empty-icon { font-size:2.5rem; margin-bottom:.75rem; }
.bsh-empty-title { font-family:'Cormorant Garamond',Georgia,serif; font-size:1.5rem; color:#2A1F17; margin-bottom:.35rem; }
.bsh-empty-sub { font-size:.85rem; color:#9A8478; }
/* Pagination */
.bsh-pagination { display:flex; justify-content:center; align-items:center; gap:.35rem; margin-top:2.5rem; flex-wrap:wrap; }
.bsh-page-btn { width:36px; height:36px; border-radius:8px; border:1px solid #DDD5CB; background:#fff; font-size:.83rem; color:#6B5344; cursor:pointer; display:flex; align-items:center; justify-content:center; text-decoration:none; font-family:inherit; font-weight:500; transition:all .15s; }
.bsh-page-btn:hover { border-color:#A9846B; color:#2A1F17; }
.bsh-page-btn.is-current { background:#4d3d32; color:#fff; border-color:#4d3d32; }
.bsh-page-ellipsis { font-size:.85rem; color:#9A8478; padding:0 .2rem; line-height:36px; }
/* Mobile FAB */
.bsh-filter-fab { display:none; position:fixed; bottom:1.5rem; right:1.5rem; z-index:100; background:#4d3d32; color:#fff; padding:.75rem 1.25rem; border-radius:999px; font-size:.83rem; font-weight:500; font-family:inherit; box-shadow:0 8px 24px rgba(42,31,23,.18); border:none; cursor:pointer; align-items:center; gap:.4rem; }
/* Cart overlay/drawer */
.bacera-cart-overlay { position:fixed; inset:0; background:rgba(0,0,0,.28); opacity:0; pointer-events:none; transition:opacity .25s; z-index:60; }
.bacera-cart-overlay.is-open { opacity:1; pointer-events:auto; }
.bacera-cart-drawer { position:fixed; top:0; right:0; height:100vh; width:min(520px,93vw); background:#fff; box-shadow:-8px 0 28px rgba(42,31,23,.14); transform:translateX(100%); transition:transform .28s; display:flex; flex-direction:column; z-index:70; }
.bacera-cart-drawer.is-open { transform:translateX(0); }
.bacera-cart-drawer-head { padding:2rem 1.5rem 1.25rem; border-bottom:1px solid #EDE6DD; }
.bacera-cart-items { flex:1; overflow-y:auto; padding:1.5rem; }
.bacera-cart-item { display:grid; grid-template-columns:88px minmax(0,1fr); gap:.85rem; padding:1rem 0; border-bottom:1px solid #EDE6DD; }
.bacera-cart-item img { width:88px; height:88px; border-radius:10px; object-fit:cover; background:#F2EDE5; }
.bacera-cart-item-main { display:flex; align-items:flex-start; justify-content:space-between; gap:.5rem; min-width:0; }
.bacera-cart-item-text { min-width:0; flex:1; }
.bacera-cart-variant-line { margin:.3rem 0 0; font-size:.82rem; line-height:1.45; color:#6B5344; }
.bacera-cart-qty { display:inline-flex; align-items:center; border:1px solid #DDD5CB; border-radius:8px; overflow:hidden; }
.bacera-cart-qty button { width:2rem; height:2rem; border:0; background:#fff; color:#6B5344; cursor:pointer; font-size:1rem; }
.bacera-cart-qty span { min-width:2rem; text-align:center; font-variant-numeric:tabular-nums; color:#2A1F17; font-size:.875rem; font-weight:600; }
.bacera-cart-remove { display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; width:2rem; height:2rem; padding:0; border:0; border-radius:8px; background:transparent; color:#9A8478; cursor:pointer; transition:color .15s,background .15s; }
.bacera-cart-remove:hover { color:#6B5344; background:#F2EDE5; }
.bacera-cart-footer { border-top:1px solid #EDE6DD; padding:1.5rem; background:#fff; }
.bacera-cart-empty { padding:2.5rem 1rem; color:#9A8478; font-size:.9rem; text-align:center; }
</style>

<div class="bsh-wrap">

	<?php /* ── Load Cormorant Garamond if not in header ── */ ?>
	<link rel="preconnect" href="https://fonts.googleapis.com" />
	<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet" />

	<?php /* ─── HERO ─── */ ?>
	<div class="bsh-hero">
		<div>
			<div class="bsh-hero-label"><?php esc_html_e( 'Handcrafted · Vietnamese Ceramics', 'bacera' ); ?></div>
			<h1 class="bsh-hero-title">
				<?php esc_html_e( 'Every piece holds', 'bacera' ); ?><br/>
				<em><?php esc_html_e( 'a quiet story.', 'bacera' ); ?></em>
			</h1>
		</div>
		<div class="bsh-cats">
			<span class="bsh-cat-label"><?php esc_html_e( 'Browse', 'bacera' ); ?></span>
			<?php
			$all_active   = empty( $selected_category_ids );
			$all_pill_url = remove_query_arg( [ 'cat', 'filter_collection', 'shop_page' ], $shop_base_url );
			if ( $sort !== 'price_low' ) { $all_pill_url = add_query_arg( 'sort', $sort, $all_pill_url ); }
			?>
			<a href="<?php echo esc_url( $all_pill_url ); ?>" class="bsh-cat-pill <?php echo $all_active ? 'is-active' : ''; ?>" <?php echo $all_active ? 'aria-current="page"' : ''; ?>>
				<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
				<?php esc_html_e( 'All', 'bacera' ); ?>
			</a>
			<?php foreach ( $bacera_shop_by_tiles as $tile ) :
				$tkey = $tile['key']; $tid = (string) $tile['id'];
				if ( $tkey === 'all' || $tid === '' ) { continue; }
				$t_url = bacera_shop_by_tile_url( $shop_base_url, $sort, $tid );
				$t_active = count( $selected_category_ids ) === 1 && $selected_category_ids[0] === $tid;
			?>
			<a href="<?php echo esc_url( $t_url ); ?>" class="bsh-cat-pill <?php echo $t_active ? 'is-active' : ''; ?>" <?php echo $t_active ? 'aria-current="page"' : ''; ?>>
				<?php echo bacera_shop_by_tile_icon( $tkey ); // phpcs:ignore ?>
				<?php echo esc_html( $tile['label'] ); ?>
			</a>
			<?php endforeach; ?>
		</div>
	</div>

	<?php /* ─── API Error states ─── */ ?>
	<?php if ( ! class_exists( 'Pancake_API_Client' ) || ! class_exists( 'Bacera_Utils' ) ) : ?>
		<div class="bsh-empty"><div class="bsh-empty-icon">⚙️</div><p class="bsh-empty-title"><?php esc_html_e( 'Plugin not active', 'bacera' ); ?></p><p class="bsh-empty-sub"><?php esc_html_e( 'Please enable the Bacera Pancake plugin.', 'bacera' ); ?></p></div>
	<?php elseif ( $products_response === false ) : ?>
		<div class="bsh-empty"><div class="bsh-empty-icon">🔌</div><p class="bsh-empty-title"><?php esc_html_e( 'Connection error', 'bacera' ); ?></p><p class="bsh-empty-sub"><?php esc_html_e( 'Could not reach the shop API.', 'bacera' ); ?></p></div>
	<?php elseif ( $products_api_error ) : ?>
		<div class="bsh-empty"><div class="bsh-empty-icon">⚠️</div><p class="bsh-empty-title"><?php esc_html_e( 'API error', 'bacera' ); ?></p><p class="bsh-empty-sub"><?php esc_html_e( 'Try again later.', 'bacera' ); ?></p></div>
	<?php else : ?>

	<div class="bsh-split">

		<?php /* ─── SIDEBAR ─── */ ?>
		<aside class="bsh-sidebar" aria-label="<?php esc_attr_e( 'Filters', 'bacera' ); ?>">
			<form method="get" action="<?php echo esc_url( $shop_base_url ); ?>" id="bacera-shop-sidebar-form">
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

				<div class="bsh-sidebar-section">
					<div class="bsh-sidebar-heading"><?php esc_html_e( 'Collection', 'bacera' ); ?></div>
					<a href="<?php echo esc_url( $url_shop_clear_cats ); ?>" class="bsh-all-link <?php echo empty( $selected_category_ids ) ? 'is-active' : ''; ?>">
						<?php esc_html_e( 'All products', 'bacera' ); ?>
					</a>
					<ul class="bsh-filter-list" style="margin-top:.5rem;">
						<?php foreach ( $categories_data as $cat ) :
							if ( ! is_array( $cat ) ) { continue; }
							$cid   = isset( $cat['id'] ) ? (string) $cat['id'] : ( isset( $cat['category_id'] ) ? (string) $cat['category_id'] : '' );
							$label = $cat['text'] ?? $cat['name'] ?? '';
							if ( $cid === '' ) { continue; }
							$fid = 'bsh-cat-' . preg_replace( '/[^a-zA-Z0-9_-]/', '', $cid );
						?>
						<li class="bsh-filter-item">
							<input type="checkbox" name="filter_collection[]" value="<?php echo esc_attr( $cid ); ?>" id="<?php echo esc_attr( $fid ); ?>" <?php checked( $chk_cat( $cid ) ); ?> onchange="this.form.submit()" />
							<label for="<?php echo esc_attr( $fid ); ?>"><?php echo esc_html( $label !== '' ? $label : $cid ); ?></label>
						</li>
						<?php endforeach; ?>
					</ul>
				</div>

				<div class="bsh-sidebar-section">
					<div class="bsh-sidebar-heading"><?php esc_html_e( 'Price', 'bacera' ); ?></div>
					<ul class="bsh-filter-list">
						<?php
						$price_opts = [ '' => __( 'Any price', 'bacera' ), 'p1' => __( 'Under $30', 'bacera' ), 'p2' => '$30–$60', 'p3' => '$60–$100', 'p4' => __( 'Above $100', 'bacera' ) ];
						$cur_price  = isset( $_GET['filter_price'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_price'] ) ) : '';
						foreach ( $price_opts as $val => $lab ) :
							$fid = $val === '' ? 'bsh-price-any' : 'bsh-price-' . $val;
						?>
						<li class="bsh-filter-item">
							<input type="radio" name="filter_price" value="<?php echo esc_attr( $val ); ?>" id="<?php echo esc_attr( $fid ); ?>" <?php checked( $cur_price, $val ); ?> onchange="this.form.submit()" />
							<label for="<?php echo esc_attr( $fid ); ?>"><?php echo esc_html( $lab ); ?></label>
						</li>
						<?php endforeach; ?>
					</ul>
				</div>

				<?php
				$bacera_filter_groups = [
					[ 'heading' => __( 'Glaze Finish', 'bacera' ), 'name' => 'filter_glaze',    'options' => [ 'smooth' => __( 'Smooth', 'bacera' ), 'reactive' => __( 'Reactive', 'bacera' ), 'matte' => __( 'Matte', 'bacera' ), 'glossy' => __( 'Glossy', 'bacera' ) ] ],
					[ 'heading' => __( 'Best For',    'bacera' ), 'name' => 'filter_best_for', 'options' => [ 'tea' => __( 'Tea', 'bacera' ), 'coffee' => __( 'Coffee', 'bacera' ), 'espresso' => __( 'Espresso', 'bacera' ), 'alcohol' => __( 'Alcohol', 'bacera' ), 'juice' => __( 'Juice', 'bacera' ) ] ],
					[ 'heading' => __( 'Capacity',    'bacera' ), 'name' => 'filter_capacity', 'options' => [ 'lt100' => '<100ml', '100_150' => '100–150ml', '150_250' => '150–250ml', 'gt250' => '>250ml' ] ],
					[ 'heading' => __( 'Shape',       'bacera' ), 'name' => 'filter_shape',    'options' => [ 'round' => __( 'Round', 'bacera' ), 'slim' => __( 'Slim', 'bacera' ), 'tall' => __( 'Tall', 'bacera' ), 'flared_rim' => __( 'Flared rim', 'bacera' ) ] ],
				];
				foreach ( $bacera_filter_groups as $grp ) :
					$gname = $grp['name'];
				?>
				<div class="bsh-sidebar-section">
					<div class="bsh-sidebar-heading"><?php echo esc_html( $grp['heading'] ); ?></div>
					<ul class="bsh-filter-list">
						<?php foreach ( $grp['options'] as $val => $lab ) :
							$fid = 'bsh-' . sanitize_key( $gname . '-' . $val );
						?>
						<li class="bsh-filter-item">
							<input type="checkbox" name="<?php echo esc_attr( $gname ); ?>[]" value="<?php echo esc_attr( $val ); ?>" id="<?php echo esc_attr( $fid ); ?>" <?php checked( $chk( $gname, $val ) ); ?> onchange="this.form.submit()" />
							<label for="<?php echo esc_attr( $fid ); ?>"><?php echo esc_html( is_string( $lab ) ? $lab : (string) $lab ); ?></label>
						</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php endforeach; ?>

			</form>
		</aside>

		<?php /* ─── MAIN CONTENT ─── */ ?>
		<div class="bsh-main-col">

			<div class="bsh-toolbar">
				<p class="bsh-product-count">
					<?php
					if ( ! empty( $items ) ) {
						printf( esc_html( _n( '<strong>%d</strong> product', '<strong>%d</strong> products', count( $items ), 'bacera' ) ), count( $items ) );
					} else {
						esc_html_e( 'No products found.', 'bacera' );
					}
					?>
				</p>
				<form method="get" action="<?php echo esc_url( $shop_base_url ); ?>" style="display:flex;align-items:center;gap:.6rem;">
					<input type="hidden" name="shop_page" value="1" />
					<?php
					foreach ( $selected_category_ids as $sid ) {
						echo '<input type="hidden" name="filter_collection[]" value="' . esc_attr( $sid ) . '" />';
					}
					if ( isset( $_GET['filter_price'] ) && $_GET['filter_price'] !== '' ) {
						echo '<input type="hidden" name="filter_price" value="' . esc_attr( sanitize_text_field( wp_unslash( $_GET['filter_price'] ) ) ) . '" />';
					}
					foreach ( [ 'filter_capacity', 'filter_glaze', 'filter_shape', 'filter_best_for', 'filter_size', 'filter_handle' ] as $pk ) {
						if ( empty( $_GET[ $pk ] ) ) { continue; }
						foreach ( (array) wp_unslash( $_GET[ $pk ] ) as $pv ) {
							echo '<input type="hidden" name="' . esc_attr( $pk ) . '[]" value="' . esc_attr( sanitize_text_field( $pv ) ) . '" />';
						}
					}
					?>
					<label for="bsh-sort" style="font-size:.78rem;color:#9A8478;"><?php esc_html_e( 'Sort:', 'bacera' ); ?></label>
					<select name="sort" id="bsh-sort" class="bsh-sort-select" onchange="this.form.submit()">
						<option value="price_low"  <?php selected( $sort, 'price_low' ); ?>><?php esc_html_e( 'Price: Low → High', 'bacera' ); ?></option>
						<option value="price_high" <?php selected( $sort, 'price_high' ); ?>><?php esc_html_e( 'Price: High → Low', 'bacera' ); ?></option>
						<option value="newest"     <?php selected( $sort, 'newest' ); ?>><?php esc_html_e( 'Newest', 'bacera' ); ?></option>
					</select>
				</form>
			</div>

			<?php
			// ── Client-side filter + sort (original logic preserved) ──
			if ( ! empty( $items ) ) {
				$fp     = isset( $_GET['filter_price'] )    ? sanitize_text_field( wp_unslash( $_GET['filter_price'] ) ) : '';
				$fs     = isset( $_GET['filter_size'] )     ? array_map( 'sanitize_text_field', (array) wp_unslash( $_GET['filter_size'] ) )     : [];
				$f_cap  = isset( $_GET['filter_capacity'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_GET['filter_capacity'] ) )  : [];
				$f_hand = isset( $_GET['filter_handle'] )   ? array_map( 'sanitize_text_field', (array) wp_unslash( $_GET['filter_handle'] ) )    : [];
				$f_glz  = isset( $_GET['filter_glaze'] )    ? array_map( 'sanitize_text_field', (array) wp_unslash( $_GET['filter_glaze'] ) )     : [];
				$f_shp  = isset( $_GET['filter_shape'] )    ? array_map( 'sanitize_text_field', (array) wp_unslash( $_GET['filter_shape'] ) )     : [];
				$f_best = isset( $_GET['filter_best_for'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_GET['filter_best_for'] ) )  : [];
				$fcats  = count( $selected_category_ids ) > 1 ? $selected_category_ids : [];
				$needle_maps = bacera_shop_attribute_filter_needle_maps();
				$items = array_values( array_filter( $items, static function ( $p ) use ( $fp, $fcats, $fs, $f_cap, $f_hand, $f_glz, $f_shp, $f_best, $needle_maps ) {
					$pa = isset( $p['price_at_counter'] ) ? (float) $p['price_at_counter'] : 0;
					$ra = isset( $p['retail_price'] ) ? (float) $p['retail_price'] : 0;
					$pr = $pa > 0 ? $pa : $ra;
					if ( ! bacera_shop_price_band_matches( $pr, $fp ) ) { return false; }
					$hs = bacera_shop_filter_haystack( $p );
					if ( ! empty( $fcats ) ) {
						$ids = array_map( 'strval', bacera_shop_product_category_ids( $p ) );
						if ( count( array_intersect( array_map( 'strval', $fcats ), $ids ) ) === 0 ) { return false; }
					}
					if ( ! bacera_shop_keyword_group_match( $hs, $f_cap,  $needle_maps['capacity'] ) ) { return false; }
					if ( ! bacera_shop_keyword_group_match( $hs, $f_hand, $needle_maps['handle'] ) )   { return false; }
					if ( ! bacera_shop_keyword_group_match( $hs, $f_glz,  $needle_maps['glaze'] ) )    { return false; }
					if ( ! bacera_shop_keyword_group_match( $hs, $f_shp,  $needle_maps['shape'] ) )    { return false; }
					if ( ! bacera_shop_keyword_group_match( $hs, $f_best, $needle_maps['best_for'] ) ) { return false; }
					if ( ! empty( $fs ) ) { $ok=false; foreach($fs as $sz){if(strpos($hs,strtolower((string)$sz))!==false){$ok=true;break;}} if(!$ok)return false; }
					return true;
				} ) );
			}
			if ( $sort === 'price_high' || $sort === 'price_low' ) {
				usort( $items, function ( $a, $b ) use ( $sort ) {
					$ap = ( (float)( $a['price_at_counter'] ?? 0 ) ) ?: ( (float)( $a['retail_price'] ?? 0 ) );
					$bp = ( (float)( $b['price_at_counter'] ?? 0 ) ) ?: ( (float)( $b['retail_price'] ?? 0 ) );
					return $sort === 'price_high' ? $bp <=> $ap : $ap <=> $bp;
				} );
			} elseif ( $sort === 'newest' ) {
				usort( $items, function ( $a, $b ) { return ( (int)( $b['id'] ?? 0 ) ) <=> ( (int)( $a['id'] ?? 0 ) ); } );
			}
			?>

			<?php if ( empty( $items ) ) : ?>
			<div class="bsh-empty">
				<div class="bsh-empty-icon">🔍</div>
				<p class="bsh-empty-title"><?php esc_html_e( 'No products found', 'bacera' ); ?></p>
				<p class="bsh-empty-sub"><?php esc_html_e( 'Try adjusting your filters or browse all products.', 'bacera' ); ?></p>
			</div>
			<?php else : ?>

			<div class="bsh-product-grid">
				<?php foreach ( $items as $p ) :
					$name             = $p['product']['name'] ?? $p['name'] ?? __( 'Product', 'bacera' );
					$price_at_counter = isset( $p['price_at_counter'] ) ? (float) $p['price_at_counter'] : 0;
					$retail_price     = isset( $p['retail_price'] ) ? (float) $p['retail_price'] : 0;
					$price            = $price_at_counter > 0 ? $price_at_counter : $retail_price;
					$original_price   = ( $retail_price > $price ) ? $retail_price : 0;
					$discount_percent = false;
					if ( $original_price > 0 && $price < $original_price ) {
						$discount_percent = '-' . (int) round( ( ( $original_price - $price ) / $original_price ) * 100 ) . '%';
					}
					$image_url = Bacera_Utils::get_proxy_url( $p );
					$brand_fallback_ids = count( $selected_category_ids ) === 1 ? $selected_category_ids : [];
					$brand = bacera_shop_product_category_display_name( $p, $categories_data, $brand_fallback_ids );
					if ( $brand === '' ) { $brand = __( 'Bacera', 'bacera' ); }
					$product_detail_url = Bacera_Utils::get_product_permalink( $p );
					$variation_id       = isset( $p['id'] ) ? (string) $p['id'] : '';
					$product_id         = isset( $p['product']['id'] ) ? (string) $p['product']['id'] : ( isset( $p['product_id'] ) ? (string) $p['product_id'] : '' );
					$cart_item_uid      = $variation_id !== '' ? 'var_' . $variation_id : 'prd_' . $product_id . '_' . md5( $name . '|' . $price );
					$cart_variant_label = $p['name'] ?? '';
					$cart_color         = $p['color_name'] ?? $p['color'] ?? '';
					$cart_size          = $p['size_name'] ?? $p['size'] ?? $p['capacity'] ?? '';

					get_template_part( 'app/Views/components/product-card', null, [
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
					] );
				endforeach; ?>
			</div>

			<?php /* ── Pagination ── */ ?>
			<?php if ( $total_pages > 1 ) :
				$tp = (int) $total_pages;
				$prange = [];
				if ( $tp <= 9 ) { for ($i=1;$i<=$tp;$i++){ $prange[]=$i; } }
				else {
					for($i=1;$i<=min(3,$tp);$i++){ $prange[]=$i; }
					if($current_page>4){ $prange[]='dot'; }
					if($current_page>3&&$current_page<$tp-2){ $prange[]=$current_page; }
					if($current_page<$tp-3){ $prange[]='dot'; }
					for($i=max($tp-2,4);$i<=$tp;$i++){ $prange[]=$i; }
					$prange=array_unique($prange);
				}
				$mkurl = static function($n) use($shop_base_url){
					$pa=[];
					foreach($_GET as $gk=>$gv){
						$gk=sanitize_key($gk);
						$pa[$gk]=is_array($gv)?array_map('sanitize_text_field',array_map('wp_unslash',(array)$gv)):sanitize_text_field(wp_unslash($gv));
					}
					$pa['shop_page']=$n;
					return add_query_arg($pa,$shop_base_url);
				};
			?>
			<nav class="bsh-pagination" aria-label="<?php esc_attr_e( 'Pagination', 'bacera' ); ?>">
				<?php if($current_page>1):?><a href="<?php echo esc_url($mkurl($current_page-1));?>" class="bsh-page-btn" aria-label="<?php esc_attr_e('Previous','bacera');?>">‹</a><?php endif;?>
				<?php foreach($prange as $entry):
					if($entry==='dot'){ echo '<span class="bsh-page-ellipsis">…</span>'; continue; }
					$n=(int)$entry; $ic=($n===$current_page);
				?>
				<a href="<?php echo esc_url($mkurl($n));?>" class="bsh-page-btn <?php echo $ic?'is-current':'';?>" <?php echo $ic?'aria-current="page"':'';?>><?php echo esc_html($n);?></a>
				<?php endforeach;?>
				<?php if($current_page<$total_pages):?><a href="<?php echo esc_url($mkurl($current_page+1));?>" class="bsh-page-btn" aria-label="<?php esc_attr_e('Next','bacera');?>">›</a><?php endif;?>
			</nav>
			<?php endif;?>

			<?php endif; // empty check ?>
		</div><?php // .bsh-main-col ?>
	</div><?php // .bsh-split ?>

	<?php endif; // API check ?>

	<?php get_template_part( 'app/Views/components/seo-content', null, [ 'title' => __( 'Shop', 'bacera' ) ] ); ?>

</div><?php // .bsh-wrap ?>

<button class="bsh-filter-fab" onclick="document.querySelector('.bsh-sidebar').style.display='block';" aria-label="<?php esc_attr_e('Open filters','bacera');?>">
	<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
	<?php esc_html_e('Filters','bacera');?>
</button>

<div id="bacera-cart-overlay" class="bacera-cart-overlay" aria-hidden="true"></div>
<aside id="bacera-cart-drawer" class="bacera-cart-drawer" aria-hidden="true" aria-label="<?php esc_attr_e('Shopping cart','bacera');?>">
	<div class="bacera-cart-drawer-head" style="display:flex;align-items:center;justify-content:space-between;">
		<h2 style="margin:0;font-family:'Cormorant Garamond',Georgia,serif;font-size:1.6rem;font-weight:500;color:#2A1F17;"><?php esc_html_e('Your Cart','bacera');?></h2>
		<button type="button" id="bacera-cart-close" style="width:34px;height:34px;border-radius:50%;border:1px solid #DDD5CB;background:transparent;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#9A8478;" aria-label="<?php esc_attr_e('Close cart','bacera');?>">×</button>
	</div>
	<div id="bacera-cart-items" class="bacera-cart-items"></div>
	<div class="bacera-cart-footer">
		<div style="display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:1rem;">
			<p style="margin:0;font-size:.8rem;color:#9A8478;"><?php esc_html_e('Total (VAT included)','bacera');?></p>
			<p id="bacera-cart-total" style="margin:0;font-family:'Cormorant Garamond',Georgia,serif;font-size:1.75rem;color:#2A1F17;">0đ</p>
		</div>
		<div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;">
			<a id="bacera-cart-go-checkout" href="<?php echo esc_url($checkout_shipping_url);?>" style="border-radius:10px;background:#4d3d32;padding:.75rem 1rem;text-align:center;font-weight:500;color:#fff;text-decoration:none;font-size:.875rem;"><?php esc_html_e('Checkout','bacera');?></a>
			<a id="bacera-cart-go-cart"     href="<?php echo esc_url($cart_page_url);?>"       style="border-radius:10px;border:1px solid #DDD5CB;padding:.75rem 1rem;text-align:center;font-weight:500;color:#6B5344;text-decoration:none;font-size:.875rem;"><?php esc_html_e('View cart','bacera');?></a>
		</div>
	</div>
</aside>

</main>

<script>
(function(){
var drawer=document.getElementById('bacera-cart-drawer'),overlay=document.getElementById('bacera-cart-overlay'),closeBtn=document.getElementById('bacera-cart-close'),listEl=document.getElementById('bacera-cart-items'),totalEl=document.getElementById('bacera-cart-total');
if(!drawer||!overlay||!closeBtn||!listEl||!totalEl)return;
var KEY='bacera_shop_cart_v1',CK='bacera_checkout_items',previewId=null;
function loadCart(){try{var p=JSON.parse(localStorage.getItem(KEY)||'[]');return Array.isArray(p)?p:[];}catch(e){return[];}}
function saveCart(c){localStorage.setItem(KEY,JSON.stringify(c));}
function toCurrency(n){return Number(n||0).toLocaleString('vi-VN')+'đ';}
function esc(v){return String(v||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');}
function total(c){return c.reduce(function(s,i){return s+Number(i.price||0)*Number(i.qty||0);},0);}
var trash='<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>';
function pv(item){var c=String(item.color||'').trim(),s=String(item.size||'').trim(),l=String(item.variant_label||'').trim();if((!c||!s)&&l){var pts=l.split('|').map(function(x){return x.trim();}).filter(Boolean);if(!c&&pts[0])c=pts[0];if(!s&&pts[1])s=pts[1];}return c&&s?c+' · '+s:c||s||l;}
function getDisp(){var c=loadCart();if(!previewId)return[];return c.filter(function(i){return String(i.id)===String(previewId);});}
function render(){
	var cart=getDisp();
	if(!cart.length){listEl.innerHTML='<p class="bacera-cart-empty"><?php echo esc_js(__('Your cart is empty.','bacera'));?></p>';totalEl.textContent='0đ';return;}
	listEl.innerHTML=cart.map(function(item){
		var sub=pv(item),op=Number(item.original_price||0),oph=op>Number(item.price||0)?'<span style="font-size:.75rem;text-decoration:line-through;color:#9A8478;">'+toCurrency(op)+'</span>':'';
		return '<article class="bacera-cart-item" data-cart-id="'+esc(item.id)+'">'
			+'<img src="'+esc(item.image||'https://placehold.co/120x120/f0ece3/8d6a54?text=Bacera')+'" alt="'+esc(item.name||'')+'" loading="lazy"/>'
			+'<div style="min-width:0;">'
			+'<div class="bacera-cart-item-main"><div class="bacera-cart-item-text">'
			+'<p style="margin:0;font-size:.75rem;color:#9A8478;">'+esc(item.brand||'Bacera')+'</p>'
			+'<p style="margin:.15rem 0 0;font-size:.9rem;font-weight:600;color:#2A1F17;line-height:1.3;">'+esc(item.name||'')+'</p>'
			+(sub?'<p class="bacera-cart-variant-line">'+esc(sub)+'</p>':'')
			+'</div><button type="button" class="bacera-cart-remove" data-cart-action="remove" aria-label="<?php echo esc_js(__('Remove','bacera'));?>">'+trash+'</button></div>'
			+'<div style="margin-top:.6rem;display:flex;align-items:center;justify-content:space-between;gap:.5rem;">'
			+'<div class="bacera-cart-qty" role="group"><button type="button" data-cart-action="minus">−</button><span>'+String(Number(item.qty||1)).padStart(2,'0')+'</span><button type="button" data-cart-action="plus">+</button></div>'
			+'<div style="text-align:right;">'+oph+'<p style="margin:0;font-size:1rem;font-weight:600;color:#2A1F17;">'+toCurrency(item.price)+'</p></div>'
			+'</div></div></article>';
	}).join('');
	totalEl.textContent=toCurrency(total(cart));
}
function setOpen(open){if(!open)previewId=null;drawer.classList.toggle('is-open',open);overlay.classList.toggle('is-open',open);drawer.setAttribute('aria-hidden',open?'false':'true');overlay.setAttribute('aria-hidden',open?'false':'true');document.body.classList.toggle('overflow-hidden',open);}
function upsert(next){var c=loadCart(),i=c.findIndex(function(x){return String(x.id)===String(next.id);});if(i>=0){c[i].qty=Number(c[i].qty||1)+1;}else{next.qty=1;c.push(next);}saveCart(c);}
document.addEventListener('click',function(e){var btn=e.target.closest('.bacera-shop-add-cart-btn');if(!btn)return;e.preventDefault();e.stopPropagation();var raw=btn.getAttribute('data-cart-item')||'';if(!raw)return;try{var p=JSON.parse(raw);if(!p||!p.id)return;upsert(p);previewId=String(p.id);render();setOpen(true);}catch(err){}});
listEl.addEventListener('click',function(e){var btn=e.target.closest('button[data-cart-action]');if(!btn)return;var row=btn.closest('.bacera-cart-item');if(!row)return;var id=row.getAttribute('data-cart-id'),action=btn.getAttribute('data-cart-action'),c=loadCart(),i=c.findIndex(function(x){return String(x.id)===String(id);});if(i<0)return;if(action==='remove')c.splice(i,1);else if(action==='minus'){c[i].qty=Number(c[i].qty||1)-1;if(c[i].qty<=0)c.splice(i,1);}else if(action==='plus')c[i].qty=Number(c[i].qty||1)+1;saveCart(c);render();});
var co=document.getElementById('bacera-cart-go-checkout');if(co){co.addEventListener('click',function(e){var full=loadCart(),picked=previewId?full.filter(function(x){return String(x.id)===String(previewId);}):[];if(!picked.length){e.preventDefault();return;}try{sessionStorage.setItem(CK,JSON.stringify(picked));}catch(err){}});}
closeBtn.addEventListener('click',function(){setOpen(false);});
overlay.addEventListener('click',function(){setOpen(false);});
document.addEventListener('keydown',function(e){if(e.key==='Escape')setOpen(false);});
render();
})();
</script>

<?php
get_footer();
