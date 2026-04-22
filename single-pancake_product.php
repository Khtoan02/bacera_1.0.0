<?php
/**
 * Template Name: Pancake Product Single
 * Description: Trang chi tiết sản phẩm gốm Bacera
 */

get_header();

if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();

		$product_id = get_the_ID();
		$review_tab_requested = isset( $_GET['tab'] ) && sanitize_key( wp_unslash( $_GET['tab'] ) ) === 'reviews';
		$review_redirect_url  = add_query_arg(
			[
				'tab' => 'reviews',
			],
			get_permalink( $product_id )
		);
		$reviews_list = get_comments(
			[
				'post_id' => $product_id,
				'status'  => 'approve',
				'type'    => 'comment',
				'order'   => 'DESC',
				'orderby' => 'comment_date_gmt',
				'number'  => 100,
			]
		);
		$reviews_total  = count( $reviews_list );
		$ratings_sum    = 0;
		$ratings_count  = 0;
		foreach ( $reviews_list as $review_item ) {
			$rating_value = (int) get_comment_meta( (int) $review_item->comment_ID, 'rating', true );
			if ( $rating_value >= 1 && $rating_value <= 5 ) {
				$ratings_sum += $rating_value;
				$ratings_count++;
			}
		}
		$avg_rating = $ratings_count > 0 ? round( $ratings_sum / $ratings_count, 1 ) : 0;

		$api_product = class_exists( 'Bacera_Utils' ) ? Bacera_Utils::fetch_product_detail_for_wp_post( $product_id ) : null;

		$categories_map = [];
		if ( class_exists( 'Bacera_Module_Products' ) ) {
			$cat_resp = Bacera_Module_Products::get_categories();
			if ( is_array( $cat_resp ) && ! empty( $cat_resp['data'] ) && is_array( $cat_resp['data'] ) && class_exists( 'Bacera_Utils' ) ) {
				$categories_map = Bacera_Utils::flatten_category_names( $cat_resp['data'] );
			}
		}

		/**
		 * Tên bộ sưu tập / danh mục cho thẻ "You might also like" (cùng logic ưu tiên như Shop).
		 *
		 * @param array<string,mixed> $p Item từ /products/variations.
		 */
		$bacera_pdp_related_brand = static function ( $p ) use ( $categories_map ) {
			if ( ! is_array( $p ) ) {
				return '';
			}
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
			$ids = array_values( array_unique( array_filter( $ids ) ) );
			foreach ( $ids as $id ) {
				if ( isset( $categories_map[ $id ] ) && $categories_map[ $id ] !== '' ) {
					return $categories_map[ $id ];
				}
			}
			return '';
		};

		$pancake_variation_id      = (string) get_post_meta( $product_id, '_pancake_id', true );
		$current_pancake_product_id = (string) get_post_meta( $product_id, '_pancake_product_id', true );
		if ( $current_pancake_product_id === '' && isset( $api_product['id'] ) ) {
			$current_pancake_product_id = (string) $api_product['id'];
		}

		if ( function_exists( 'get_field' ) ) {
			$acf_sale       = get_field( 'gia_khuyen_mai', $product_id );
			$acf_regular    = get_field( 'gia_goc', $product_id );
			$acf_disc       = get_field( 'phan_tram_giam_gia', $product_id );
			$acf_stock      = get_field( 'ton_kho', $product_id );
			$gallery_images = get_field( 'thu_vien_anh', $product_id );
		} else {
			$acf_sale       = get_post_meta( $product_id, 'gia_khuyen_mai', true );
			$acf_regular    = get_post_meta( $product_id, 'gia_goc', true );
			$acf_disc       = get_post_meta( $product_id, 'phan_tram_giam_gia', true );
			$acf_stock      = get_post_meta( $product_id, 'ton_kho', true );
			$gallery_raw    = get_post_meta( $product_id, 'thu_vien_anh', true );
			$gallery_images = is_string( $gallery_raw ) ? maybe_unserialize( $gallery_raw ) : $gallery_raw;
		}

		$variations = ( $api_product && ! empty( $api_product['variations'] ) && is_array( $api_product['variations'] ) ) ? $api_product['variations'] : [];

		$bacera_pdp_variation_stock = static function ( $v ) {
			if ( isset( $v['remain_quantity'] ) ) {
				return max( 0, (int) $v['remain_quantity'] );
			}
			$sum = 0;
			if ( ! empty( $v['variations_warehouses'] ) && is_array( $v['variations_warehouses'] ) ) {
				foreach ( $v['variations_warehouses'] as $wh ) {
					$sum += isset( $wh['remain_quantity'] ) ? (int) $wh['remain_quantity'] : 0;
				}
			}
			return max( 0, $sum );
		};

		$bacera_pdp_field_by_keywords = static function ( $variation, array $keywords ) {
			$fields = isset( $variation['fields'] ) && is_array( $variation['fields'] ) ? $variation['fields'] : [];
			foreach ( $fields as $f ) {
				$n = isset( $f['name'] ) ? mb_strtolower( (string) $f['name'], 'UTF-8' ) : '';
				foreach ( $keywords as $kw ) {
					if ( $n !== '' && strpos( $n, $kw ) !== false ) {
						return isset( $f['value'] ) ? trim( (string) $f['value'] ) : '';
					}
				}
			}
			return '';
		};

		$current_variation = null;
		$current_vi        = 0;
		foreach ( $variations as $vi => $v ) {
			$vid = isset( $v['id'] ) ? (string) $v['id'] : '';
			if ( $vid !== '' && $vid === $pancake_variation_id ) {
				$current_variation = $v;
				$current_vi        = (int) $vi;
				break;
			}
		}
		if ( ! $current_variation && ! empty( $variations[0] ) ) {
			$current_variation = $variations[0];
			$current_vi        = 0;
		}

		$bacera_pdp_product_name_for_proxy = $api_product['name'] ?? get_the_title();
		$bacera_pdp_variation_proxy_url    = static function ( $variation ) use ( $bacera_pdp_product_name_for_proxy ) {
			if ( ! class_exists( 'Bacera_Utils' ) || ! is_array( $variation ) || empty( $variation['id'] ) ) {
				return '';
			}
			return Bacera_Utils::get_proxy_url(
				[
					'id'      => $variation['id'],
					'product' => [
						'name' => $bacera_pdp_product_name_for_proxy,
					],
				]
			);
		};
		$bacera_pdp_post_proxy_url = static function () use ( $product_id, $bacera_pdp_product_name_for_proxy ) {
			if ( ! class_exists( 'Bacera_Utils' ) ) {
				return '';
			}
			$pid = (string) get_post_meta( $product_id, '_pancake_id', true );
			if ( $pid === '' ) {
				return '';
			}
			return Bacera_Utils::get_proxy_url(
				[
					'id'      => $pid,
					'product' => [
						'name' => $bacera_pdp_product_name_for_proxy,
					],
				]
			);
		};

		// Gom mọi ảnh (API / WP / meta), bỏ trùng URL — hỗ trợ danh sách rất dài (thumbnail cuộn ngang).
		$gallery_seen = [];
		$gallery_urls = [];
		$bacera_pdp_gallery_add = function ( $url ) use ( &$gallery_urls, &$gallery_seen ) {
			$u = esc_url_raw( (string) $url );
			if ( $u === '' ) {
				return;
			}
			$key = md5( strtolower( $u ) );
			if ( isset( $gallery_seen[ $key ] ) ) {
				return;
			}
			$gallery_seen[ $key ] = true;
			$gallery_urls[]       = $u;
		};
		if ( $api_product ) {
			foreach ( $variations as $v ) {
				if ( ! empty( $v['images'] ) && is_array( $v['images'] ) ) {
					foreach ( $v['images'] as $img ) {
						$bacera_pdp_gallery_add( $img );
					}
				}
			}
			if ( ! empty( $api_product['images'] ) && is_array( $api_product['images'] ) ) {
				foreach ( $api_product['images'] as $img ) {
					$bacera_pdp_gallery_add( $img );
				}
			}
			if ( ! empty( $api_product['image'] ) ) {
				$bacera_pdp_gallery_add( $api_product['image'] );
			}
		}
		if ( has_post_thumbnail( $product_id ) ) {
			$bacera_pdp_gallery_add( get_the_post_thumbnail_url( $product_id, 'full' ) );
		}
		if ( ! empty( $gallery_images ) && is_array( $gallery_images ) ) {
			foreach ( $gallery_images as $image ) {
				if ( is_array( $image ) && ! empty( $image['url'] ) ) {
					$bacera_pdp_gallery_add( $image['url'] );
				} elseif ( is_numeric( $image ) ) {
					$att = wp_get_attachment_image_url( (int) $image, 'full' );
					if ( $att ) {
						$bacera_pdp_gallery_add( $att );
					}
				}
			}
		}
		if ( empty( $gallery_urls ) && $api_product && class_exists( 'Bacera_Utils' ) ) {
			foreach ( $variations as $v ) {
				$proxy_u = $bacera_pdp_variation_proxy_url( $v );
				if ( $proxy_u !== '' ) {
					$bacera_pdp_gallery_add( $proxy_u );
				}
			}
			if ( empty( $gallery_urls ) ) {
				$single = $bacera_pdp_post_proxy_url();
				if ( $single !== '' ) {
					$bacera_pdp_gallery_add( $single );
				}
			}
		}
		$pancake_fallback_img = get_post_meta( $product_id, '_pancake_image_url', true );
		if ( $pancake_fallback_img ) {
			$bacera_pdp_gallery_add( $pancake_fallback_img );
		}

		$main_image_url = '';
		if ( $current_variation && ! empty( $current_variation['images'][0] ) ) {
			$main_image_url = esc_url_raw( (string) $current_variation['images'][0] );
		}
		if ( $main_image_url === '' && $current_variation ) {
			$main_image_url = $bacera_pdp_variation_proxy_url( $current_variation );
		}
		if ( $main_image_url === '' ) {
			$main_image_url = $bacera_pdp_post_proxy_url();
		}
		if ( $main_image_url === '' && ! empty( $gallery_urls[0] ) ) {
			$main_image_url = $gallery_urls[0];
		}

		$price_at_counter = $current_variation ? (float) ( $current_variation['price_at_counter'] ?? 0 ) : 0;
		$retail_price     = $current_variation ? (float) ( $current_variation['retail_price'] ?? 0 ) : 0;
		$sale_price       = $price_at_counter > 0 ? $price_at_counter : $retail_price;
		$regular_price    = ( $retail_price > $sale_price ) ? $retail_price : 0;
		$discount_pct     = 0;
		if ( $regular_price > 0 && $sale_price < $regular_price ) {
			$discount_pct = (int) round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
		}

		$stock_quantity = $current_variation ? $bacera_pdp_variation_stock( $current_variation ) : 0;

		if ( ! $api_product ) {
			$sale_price    = $acf_sale !== '' && $acf_sale !== null ? (float) $acf_sale : 0;
			$regular_price = $acf_regular !== '' && $acf_regular !== null ? (float) $acf_regular : 0;
			$discount_pct  = $acf_disc !== '' && $acf_disc !== null ? (int) $acf_disc : 0;
			$stock_quantity = $acf_stock !== '' && $acf_stock !== null ? (int) $acf_stock : 0;
		}

		$product_title = $api_product['name'] ?? get_the_title();
		$short_desc    = '';
		if ( $api_product ) {
			$short_desc = isset( $api_product['note_product'] ) ? (string) $api_product['note_product'] : '';
			if ( $short_desc === '' && ! empty( $api_product['note'] ) ) {
				$short_desc = (string) $api_product['note'];
			}
		}
		if ( $short_desc === '' ) {
			$short_desc = get_the_excerpt();
		}
		if ( $short_desc === '' && $api_product && is_array( $current_variation ) && ! empty( $current_variation['product']['description'] ) ) {
			$short_desc = wp_strip_all_tags( (string) $current_variation['product']['description'] );
		}

		$breadcrumb_collection = '';
		if ( $api_product && ! empty( $api_product['category_ids'][0] ) ) {
			$cid = (string) $api_product['category_ids'][0];
			if ( isset( $categories_map[ $cid ] ) ) {
				$breadcrumb_collection = $categories_map[ $cid ];
			}
		}
		if ( $breadcrumb_collection === '' ) {
			$breadcrumb_collection = __( 'Collection', 'bacera' );
		}

		$shop_url          = class_exists( 'Bacera_Utils' ) ? Bacera_Utils::get_shop_page_url() : home_url( '/' );
		$cart_page_url     = class_exists( 'Bacera_Utils' ) ? Bacera_Utils::get_cart_page_url() : home_url( '/cart/' );
		$checkout_page_url = class_exists( 'Bacera_Utils' ) ? Bacera_Utils::get_checkout_shipping_page_url() : $cart_page_url;

		$color_label = $current_variation ? $bacera_pdp_field_by_keywords( $current_variation, [ 'màu', 'mau', 'color' ] ) : '';
		if ( $color_label === '' ) {
			$color_label = __( 'Dark brown', 'bacera' );
		}

		$capacity_options = [];
		if ( $api_product && ! empty( $api_product['product_attributes'] ) && is_array( $api_product['product_attributes'] ) ) {
			foreach ( $api_product['product_attributes'] as $pa ) {
				$pname = isset( $pa['name'] ) ? mb_strtolower( (string) $pa['name'], 'UTF-8' ) : '';
				if ( strpos( $pname, 'dung' ) !== false || strpos( $pname, 'ml' ) !== false || strpos( $pname, 'capacity' ) !== false || strpos( $pname, 'size' ) !== false ) {
					if ( ! empty( $pa['values'] ) && is_array( $pa['values'] ) ) {
						$capacity_options = array_map( 'strval', $pa['values'] );
					}
					break;
				}
			}
		}
		if ( empty( $capacity_options ) ) {
			$cap_from_field = $current_variation ? $bacera_pdp_field_by_keywords( $current_variation, [ 'dung', 'ml', 'capacity', 'size' ] ) : '';
			if ( $cap_from_field !== '' ) {
				$capacity_options = [ $cap_from_field ];
			} else {
				$capacity_options = [ '50-100ml', '100-150ml' ];
			}
		}

		/* Trùng scale Primary trong template-design-system.php */
		$swatch_palette = [ '#3d2f26', '#4d3d32', '#6b5344', '#8d6a54', '#a9846b', '#c0a28e' ];

		$dimensions_lines = [];
		if ( $current_variation && ! empty( $current_variation['fields'] ) && is_array( $current_variation['fields'] ) ) {
			foreach ( $current_variation['fields'] as $f ) {
				if ( ! empty( $f['name'] ) && isset( $f['value'] ) ) {
					$dimensions_lines[] = [
						'label' => (string) $f['name'],
						'value' => (string) $f['value'],
					];
				}
			}
		}
		if ( empty( $dimensions_lines ) && $api_product && ! empty( $api_product['note'] ) ) {
			$dimensions_lines[] = [ 'label' => '', 'value' => trim( (string) $api_product['note'] ) ];
		}
		if ( empty( $dimensions_lines ) ) {
			if ( function_exists( 'get_field' ) ) {
				$dim_html = get_field( 'thong_so_kich_thuoc', $product_id );
			} else {
				$dim_html = get_post_meta( $product_id, 'thong_so_kich_thuoc', true );
			}
			if ( $dim_html ) {
				$dimensions_lines[] = [ 'label' => '', 'value' => wp_strip_all_tags( (string) $dim_html ) ];
			}
		}

		$pdp_js_variants              = [];
		$pancake_product_id_for_cart = (string) get_post_meta( $product_id, '_pancake_product_id', true );
		foreach ( $variations as $vi => $v ) {
			$p_at = (float) ( $v['price_at_counter'] ?? 0 );
			$r_at = (float) ( $v['retail_price'] ?? 0 );
			$pr   = $p_at > 0 ? $p_at : $r_at;
			$reg  = ( $r_at > $pr ) ? $r_at : 0;
			$dct  = 0;
			if ( $reg > 0 && $pr < $reg ) {
				$dct = (int) round( ( ( $reg - $pr ) / $reg ) * 100 );
			}
			$img0 = ! empty( $v['images'][0] ) ? esc_url_raw( (string) $v['images'][0] ) : '';
			if ( $img0 === '' ) {
				$img0 = $bacera_pdp_variation_proxy_url( $v );
			}
			if ( $img0 === '' ) {
				$img0 = $main_image_url;
			}
			$v_imgs = [];
			if ( ! empty( $v['images'] ) && is_array( $v['images'] ) ) {
				foreach ( $v['images'] as $vim ) {
					$vu = esc_url_raw( (string) $vim );
					if ( $vu !== '' ) {
						$v_imgs[] = $vu;
					}
				}
			}
			$v_cap = $bacera_pdp_field_by_keywords( $v, [ 'dung', 'ml', 'capacity', 'size' ] );
			$pdp_js_variants[] = [
				'index'            => (int) $vi,
				'variationId'      => isset( $v['id'] ) ? (string) $v['id'] : '',
				'mainImage'        => $img0,
				'images'           => $v_imgs,
				'priceFormatted'   => number_format( $pr, 0, ',', '.' ) . '₫',
				'regularFormatted' => $reg > 0 ? number_format( $reg, 0, ',', '.' ) . '₫' : '',
				'discountPercent'  => $dct,
				'stock'            => $bacera_pdp_variation_stock( $v ),
				'colorLabel'       => $bacera_pdp_field_by_keywords( $v, [ 'màu', 'mau', 'color' ] ) ?: __( '—', 'bacera' ),
				'price'            => $pr,
				'originalPrice'    => $reg,
				'productId'        => $pancake_product_id_for_cart,
				'variantLabel'     => isset( $v['name'] ) ? (string) $v['name'] : '',
				'size'             => $v_cap,
			];
		}

		?>

<main class="bacera-pdp-page" style="background:#F8F4EE;min-height:100vh;font-family:inherit;">
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&display=swap" rel="stylesheet" />
<style id="bacera-pdp-redesign-css">
:root{
	--pdp-cream:#F8F4EE; --pdp-cream2:#F2EDE5; --pdp-sand:#E8DFD3;
	--pdp-clay1:#E5D8CC; --pdp-clay3:#C0A28E; --pdp-clay5:#A9846B;
	--pdp-clay6:#8D6A54; --pdp-clay7:#6B5344; --pdp-clay8:#4d3d32;
	--pdp-accent:#C06B3A; --pdp-text:#2A1F17; --pdp-sec:#6B5344; --pdp-muted:#9A8478;
	--pdp-border:#DDD5CB; --pdp-blt:#EDE6DD; --pdp-white:#FFFFFF;
}
.pdp-container{ max-width:1440px; margin:0 auto; padding:1.5rem clamp(1.25rem,4vw,3rem) 0; }
/* breadcrumb */
.pdp-breadcrumb{ display:flex; gap:.5rem; align-items:center; list-style:none; flex-wrap:wrap; margin-bottom:2rem; }
.pdp-breadcrumb li{ font-size:.75rem; color:var(--pdp-muted); }
.pdp-breadcrumb li a{ color:var(--pdp-muted); text-decoration:none; transition:color .15s; }
.pdp-breadcrumb li a:hover{ color:var(--pdp-text); }
/* Hero grid */
.pdp-hero{ display:grid; grid-template-columns:1.05fr 0.95fr; gap:3rem; align-items:start; }
@media(max-width:900px){ .pdp-hero{grid-template-columns:1fr;} .pdp-gallery{position:static!important;} }
/* Gallery */
.pdp-gallery{ position:sticky; top:88px; display:flex; flex-direction:column; gap:.75rem; }
.pdp-main-wrap{
	width:100%; aspect-ratio:4/5; border-radius:20px; overflow:hidden;
	background:var(--pdp-cream2); position:relative; cursor:zoom-in;
}
.pdp-main-img{ width:100%; height:100%; object-fit:cover; transition:transform .5s cubic-bezier(.4,0,.2,1); }
.pdp-main-wrap:hover .pdp-main-img{ transform:scale(1.04); }
.pdp-gallery-badge-sale{
	position:absolute; left:1rem; top:1rem; z-index:5;
	background:var(--pdp-accent); color:#fff;
	font-size:.68rem; font-weight:700; padding:5px 10px; border-radius:7px; letter-spacing:.04em;
}
.pdp-gallery-nav{
	position:absolute; top:50%; transform:translateY(-50%); z-index:5;
	width:36px; height:36px; border-radius:50%;
	background:rgba(255,255,255,.88); border:1px solid var(--pdp-blt);
	cursor:pointer; display:flex; align-items:center; justify-content:center;
	color:var(--pdp-text); transition:all .2s; backdrop-filter:blur(4px);
	font-size:.9rem;
}
.pdp-gallery-nav:hover{ background:#fff; box-shadow:0 4px 12px rgba(42,31,23,.1); }
.pdp-gallery-nav.prev{ left:.75rem; }
.pdp-gallery-nav.next{ right:.75rem; }
/* Thumbs */
.pdp-thumbs{ display:flex; gap:.5rem; overflow-x:auto; padding-bottom:2px; scrollbar-width:none; scroll-snap-type:x mandatory; }
.pdp-thumbs::-webkit-scrollbar{ display:none; }
.pdp-thumb{
	flex-shrink:0; width:72px; height:75px; border-radius:10px; overflow:hidden;
	border:2px solid transparent; background:var(--pdp-cream2); cursor:pointer;
	transition:all .15s; scroll-snap-align:start;
}
.pdp-thumb:hover:not(.is-active){ border-color:var(--pdp-clay3); }
.pdp-thumb.is-active{ border-color:var(--pdp-clay7); }
.pdp-thumb img{ width:100%; height:100%; object-fit:cover; }
/* Info column */
.pdp-info{ display:flex; flex-direction:column; gap:1.1rem; }
.pdp-collection-tag{ font-size:.65rem; font-weight:700; letter-spacing:.18em; text-transform:uppercase; color:var(--pdp-clay5); }
.pdp-title{ font-family:'Cormorant Garamond',Georgia,serif; font-size:clamp(2rem,3.5vw,2.75rem); font-weight:400; line-height:1.15; color:var(--pdp-text); }
.pdp-title em{ font-style:italic; }
/* Rating */
.pdp-rating{ display:flex; align-items:center; gap:.75rem; padding-bottom:1.1rem; border-bottom:1px solid var(--pdp-blt); flex-wrap:wrap; }
.pdp-stars{ color:var(--pdp-accent); font-size:.9rem; letter-spacing:-.03em; cursor:pointer; }
.pdp-rating-score{ font-size:.85rem; font-weight:600; color:var(--pdp-text); }
.pdp-rating-count{ font-size:.8rem; color:var(--pdp-muted); cursor:pointer; text-decoration:underline; text-underline-offset:2px; }
/* Price */
.pdp-price-block{ display:flex; align-items:baseline; gap:.75rem; flex-wrap:wrap; }
.pdp-price{ font-family:'Cormorant Garamond',Georgia,serif; font-size:2rem; font-weight:500; color:var(--pdp-text); }
.pdp-price-old{ font-size:1rem; color:var(--pdp-muted); text-decoration:line-through; }
.pdp-disc-badge{ background:var(--pdp-accent); color:#fff; font-size:.68rem; font-weight:700; letter-spacing:.05em; padding:4px 9px; border-radius:6px; }
.pdp-savings{ font-size:.78rem; color:var(--pdp-accent); font-weight:500; margin-top:.1rem; }
/* Excerpt */
.pdp-excerpt{ font-size:.88rem; color:var(--pdp-sec); line-height:1.8; }
/* Swatches */
.pdp-variant-section{ display:flex; flex-direction:column; gap:.4rem; }
.pdp-variant-row{ display:flex; align-items:center; justify-content:space-between; }
.pdp-variant-label{ font-size:.78rem; font-weight:500; color:var(--pdp-sec); }
.pdp-variant-val{ font-size:.78rem; font-weight:600; color:var(--pdp-text); }
.pdp-swatches{ display:flex; gap:.5rem; flex-wrap:wrap; }
.pdp-swatch{
	width:34px; height:34px; border-radius:50%; border:2px solid transparent;
	cursor:pointer; position:relative; transition:all .2s;
	box-shadow:0 0 0 1px rgba(0,0,0,.12);
}
.pdp-swatch.is-active{ box-shadow:0 0 0 3px #fff, 0 0 0 5px var(--pdp-clay7); }
.pdp-swatch:hover:not(.is-active){ transform:scale(1.12); }
/* Size pills */
.pdp-sizes{ display:flex; gap:.4rem; flex-wrap:wrap; }
.pdp-size-pill{
	padding:.38rem .9rem; border-radius:999px; border:1.5px solid var(--pdp-border);
	background:#fff; font-size:.78rem; color:var(--pdp-sec); cursor:pointer; transition:all .15s; font-family:inherit;
}
.pdp-size-pill.is-active{ border-color:var(--pdp-clay8); background:var(--pdp-clay8); color:#fff; }
.pdp-size-pill:hover:not(.is-active){ border-color:var(--pdp-clay5); color:var(--pdp-text); }
/* Stock */
.pdp-stock{ display:flex; align-items:center; gap:.4rem; font-size:.78rem; color:var(--pdp-sec); }
.pdp-stock-dot{ width:7px; height:7px; border-radius:50%; background:#52b754; }
.pdp-stock-dot.low{ background:var(--pdp-accent); }
/* ATC row */
.pdp-atc-row{ display:grid; grid-template-columns:auto 1fr auto; gap:.6rem; align-items:stretch; }
.pdp-qty{ display:flex; align-items:center; border:1.5px solid var(--pdp-border); border-radius:12px; overflow:hidden; background:#fff; height:50px; }
.pdp-qty-btn{ width:42px; height:100%; border:none; background:transparent; font-size:1.1rem; color:var(--pdp-sec); cursor:pointer; transition:background .15s; display:flex; align-items:center; justify-content:center; }
.pdp-qty-btn:hover{ background:var(--pdp-cream2); }
.pdp-qty-val{ min-width:38px; text-align:center; font-size:.9rem; font-weight:600; color:var(--pdp-text); }
.pdp-btn-atc{
	height:50px; border-radius:12px; background:var(--pdp-clay8); color:#fff;
	font-family:inherit; font-size:.88rem; font-weight:500; border:none; cursor:pointer;
	letter-spacing:.03em; transition:background .2s,transform .1s; padding:0 1.5rem;
}
.pdp-btn-atc:hover{ background:var(--pdp-clay7); }
.pdp-btn-atc:active{ transform:scale(.98); }
.pdp-btn-wish{
	width:50px; height:50px; border-radius:12px; border:1.5px solid var(--pdp-border);
	background:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center;
	font-size:1.2rem; color:var(--pdp-clay5); transition:all .2s;
}
.pdp-btn-wish:hover{ border-color:var(--pdp-accent); color:var(--pdp-accent); }
/* Trust */
.pdp-trust{ display:flex; gap:.875rem; flex-wrap:wrap; padding:1rem 0; border-top:1px solid var(--pdp-blt); border-bottom:1px solid var(--pdp-blt); }
.pdp-trust-item{ display:flex; align-items:center; gap:.35rem; font-size:.72rem; color:var(--pdp-sec); flex:1; min-width:110px; }
/* Accordions */
.pdp-accordions{ display:flex; flex-direction:column; }
.pdp-acc{ border-bottom:1px solid var(--pdp-blt); }
.pdp-acc-btn{
	width:100%; display:flex; align-items:center; justify-content:space-between;
	padding:.85rem 0; background:none; border:none; cursor:pointer;
	font-family:inherit; font-size:.875rem; font-weight:500; color:var(--pdp-text); text-align:left;
	transition:color .15s;
}
.pdp-acc-btn:hover{ color:var(--pdp-clay6); }
.pdp-acc-chevron{ font-size:.7rem; color:var(--pdp-muted); transition:transform .25s; }
.pdp-acc.is-open .pdp-acc-chevron{ transform:rotate(180deg); }
.pdp-acc-body{ overflow:hidden; max-height:0; transition:max-height .3s ease; font-size:.83rem; color:var(--pdp-sec); line-height:1.75; }
.pdp-acc.is-open .pdp-acc-body{ max-height:400px; padding-bottom:1rem; }
.pdp-spec-table{ width:100%; border-collapse:collapse; font-size:.82rem; }
.pdp-spec-table tr{ border-bottom:1px solid var(--pdp-blt); }
.pdp-spec-table tr:last-child{ border-bottom:none; }
.pdp-spec-table td{ padding:.4rem 0; vertical-align:top; }
.pdp-spec-table td:first-child{ color:var(--pdp-muted); width:45%; padding-right:1rem; }
.pdp-spec-table td:last-child{ color:var(--pdp-text); font-weight:500; }
/* Lower tabs */
.pdp-lower{ max-width:1440px; margin:0 auto; padding:3.5rem clamp(1.25rem,4vw,3rem) 0; }
.pdp-tabs-nav{ display:flex; border-bottom:1px solid var(--pdp-blt); gap:0; margin-bottom:2.25rem; overflow-x:auto; scrollbar-width:none; }
.pdp-tabs-nav::-webkit-scrollbar{ display:none; }
.pdp-tab-btn{
	background:none; border:none; cursor:pointer; font-family:inherit;
	font-size:.875rem; font-weight:400; color:var(--pdp-muted);
	padding:.8rem 1.4rem; border-bottom:2px solid transparent; margin-bottom:-1px;
	transition:all .2s; white-space:nowrap;
}
.pdp-tab-btn.is-active{ color:var(--pdp-text); font-weight:600; border-color:var(--pdp-clay7); }
.pdp-tab-btn:hover:not(.is-active){ color:var(--pdp-sec); }
.pdp-tab-panel{ display:none; }
.pdp-tab-panel.is-active{ display:block; }
/* Detail tab */
.pdp-detail-grid{ display:grid; grid-template-columns:1.4fr .6fr; gap:2.5rem; align-items:start; }
@media(max-width:900px){ .pdp-detail-grid{ grid-template-columns:1fr; } .pdp-reviews-grid{ grid-template-columns:1fr; } }
.pdp-rich-text{ font-size:.88rem; color:var(--pdp-sec); line-height:1.85; }
.pdp-rich-text h2{ font-family:'Cormorant Garamond',Georgia,serif; font-size:1.5rem; color:var(--pdp-text); margin:1.5rem 0 .6rem; }
.pdp-rich-text h2:first-child{ margin-top:0; }
.pdp-rich-text p{ margin-bottom:.8rem; }
.pdp-rich-text ul{ padding-left:1.25rem; margin-bottom:.8rem; }
.pdp-rich-text li{ margin-bottom:.25rem; }
.pdp-craft-box{ background:var(--pdp-cream2); border-radius:var(--pdp-r-lg,20px); padding:1.5rem; border:1px solid var(--pdp-blt); display:flex; flex-direction:column; gap:.75rem; }
.pdp-craft-icon{ font-size:2.25rem; }
.pdp-craft-label{ font-size:.62rem; font-weight:700; letter-spacing:.18em; text-transform:uppercase; color:var(--pdp-clay5); }
.pdp-craft-title{ font-family:'Cormorant Garamond',Georgia,serif; font-size:1.3rem; color:var(--pdp-text); }
.pdp-craft-body{ font-size:.82rem; color:var(--pdp-sec); line-height:1.75; }
/* Reviews tab */
.pdp-reviews-grid{ display:grid; grid-template-columns:.85fr 2fr; gap:2.5rem; align-items:start; }
.pdp-rating-summary{ background:#fff; border-radius:20px; border:1px solid var(--pdp-blt); padding:1.5rem; box-shadow:0 1px 3px rgba(42,31,23,.06); text-align:center; display:flex; flex-direction:column; align-items:center; gap:.4rem; position:sticky; top:88px; }
.pdp-rating-big{ font-family:'Cormorant Garamond',Georgia,serif; font-size:3.75rem; line-height:1; color:var(--pdp-text); }
.pdp-rating-bar-row{ width:100%; display:flex; flex-direction:column; gap:.35rem; margin-top:.6rem; }
.pdp-rbar{ display:flex; align-items:center; gap:.4rem; font-size:.7rem; color:var(--pdp-muted); }
.pdp-rbar-track{ flex:1; height:5px; background:var(--pdp-sand); border-radius:999px; overflow:hidden; }
.pdp-rbar-fill{ height:100%; background:var(--pdp-accent); border-radius:999px; }
.pdp-write-review-btn{ display:flex; align-items:center; gap:.4rem; padding:.5rem 1.1rem; border-radius:8px; border:1.5px solid var(--pdp-clay7); background:transparent; color:var(--pdp-clay7); font-family:inherit; font-size:.78rem; font-weight:500; cursor:pointer; transition:all .15s; margin-top:.6rem; }
.pdp-write-review-btn:hover{ background:var(--pdp-clay7); color:#fff; }
.pdp-reviews-list{ display:flex; flex-direction:column; gap:1rem; }
.pdp-review-card{ background:#fff; border:1px solid var(--pdp-blt); border-radius:14px; padding:1.2rem 1.4rem; box-shadow:0 1px 3px rgba(42,31,23,.06); }
.pdp-review-header{ display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; margin-bottom:.5rem; }
.pdp-review-author{ display:flex; align-items:center; gap:.5rem; }
.pdp-review-avatar{ width:34px; height:34px; border-radius:50%; background:var(--pdp-sand); display:flex; align-items:center; justify-content:center; font-size:.8rem; font-weight:600; color:var(--pdp-clay6); flex-shrink:0; }
.pdp-review-name{ font-size:.85rem; font-weight:600; color:var(--pdp-text); }
.pdp-review-date{ font-size:.7rem; color:var(--pdp-muted); }
.pdp-review-stars{ color:var(--pdp-accent); font-size:.78rem; letter-spacing:-.02em; }
.pdp-review-title{ font-size:.85rem; font-weight:600; color:var(--pdp-text); margin-bottom:.25rem; }
.pdp-review-body{ font-size:.82rem; color:var(--pdp-sec); line-height:1.7; }
.pdp-review-verified{ display:inline-flex; align-items:center; gap:.25rem; font-size:.67rem; color:#52b754; margin-top:.4rem; }
.pdp-review-form{ background:#fff; border:1px solid var(--pdp-blt); border-radius:14px; padding:1.4rem; box-shadow:0 1px 3px rgba(42,31,23,.06); }
.pdp-review-form input, .pdp-review-form textarea, .pdp-review-form select{
	width:100%; border:1px solid var(--pdp-border); border-radius:9px;
	padding:.55rem .8rem; font-family:inherit; font-size:.83rem; color:var(--pdp-text);
	outline:none; transition:border-color .2s; background:#fff;
}
.pdp-review-form input:focus, .pdp-review-form textarea:focus, .pdp-review-form select:focus{ border-color:var(--pdp-clay5); }
.pdp-review-form label{ font-size:.75rem; font-weight:500; color:var(--pdp-sec); margin-bottom:.3rem; display:block; }
.pdp-form-grid{ display:flex; flex-direction:column; gap:.75rem; }
.pdp-form-submit{ width:100%; height:46px; border-radius:10px; background:var(--pdp-clay8); color:#fff; border:none; font-family:inherit; font-size:.875rem; font-weight:500; cursor:pointer; transition:background .2s; margin-top:.75rem; }
.pdp-form-submit:hover{ background:var(--pdp-clay7); }
/* Related */
.pdp-related{ max-width:1440px; margin:0 auto; padding:3.5rem clamp(1.25rem,4vw,3rem) 5rem; }
.pdp-related-hdr{ text-align:center; margin-bottom:2rem; }
.pdp-related-title{ font-family:'Cormorant Garamond',Georgia,serif; font-size:clamp(1.75rem,3vw,2.4rem); font-weight:400; color:var(--pdp-text); }
.pdp-related-sub{ font-size:.83rem; color:var(--pdp-muted); margin-top:.35rem; }
.pdp-related-grid{ display:grid; grid-template-columns:repeat(4,1fr); gap:1.1rem; }
@media(max-width:900px){ .pdp-related-grid{ grid-template-columns:repeat(2,1fr); } }
/* Cart drawer */
.bacera-cart-overlay{ position:fixed; inset:0; background:rgba(0,0,0,.28); opacity:0; pointer-events:none; transition:opacity .25s; z-index:60; }
.bacera-cart-overlay.is-open{ opacity:1; pointer-events:auto; }
.bacera-cart-drawer{ position:fixed; top:0; right:0; height:100vh; width:min(520px,93vw); background:#fff; box-shadow:-8px 0 28px rgba(42,31,23,.14); transform:translateX(100%); transition:transform .28s cubic-bezier(.4,0,.2,1); display:flex; flex-direction:column; z-index:70; }
.bacera-cart-drawer.is-open{ transform:translateX(0); }
.bacera-cart-drawer-head{ padding:2rem 1.5rem 1.25rem; border-bottom:1px solid #EDE6DD; }
.bacera-cart-items{ flex:1; overflow-y:auto; padding:1.5rem; }
.bacera-cart-item{ display:grid; grid-template-columns:88px minmax(0,1fr); gap:.85rem; padding:1rem 0; border-bottom:1px solid #EDE6DD; }
.bacera-cart-item img{ width:88px; height:88px; border-radius:10px; object-fit:cover; background:#F2EDE5; }
.bacera-cart-item-main{ display:flex; align-items:flex-start; justify-content:space-between; gap:.5rem; min-width:0; }
.bacera-cart-item-text{ min-width:0; flex:1; }
.bacera-cart-variant-line{ margin:.3rem 0 0; font-size:.82rem; line-height:1.45; color:#6B5344; }
.bacera-cart-qty{ display:inline-flex; align-items:center; border:1px solid #DDD5CB; border-radius:8px; overflow:hidden; }
.bacera-cart-qty button{ width:2rem; height:2rem; border:0; background:#fff; color:#6B5344; cursor:pointer; font-size:1rem; }
.bacera-cart-qty span{ min-width:2rem; text-align:center; font-variant-numeric:tabular-nums; color:#2A1F17; font-size:.875rem; font-weight:600; }
.bacera-cart-remove{ display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; width:2rem; height:2rem; padding:0; border:0; border-radius:8px; background:transparent; color:#9A8478; cursor:pointer; transition:color .15s,background .15s; }
.bacera-cart-remove:hover{ color:#6B5344; background:#F2EDE5; }
.bacera-cart-footer{ border-top:1px solid #EDE6DD; padding:1.5rem; background:#fff; }
.bacera-cart-empty{ padding:2.5rem 1rem; color:#9A8478; font-size:.9rem; text-align:center; }
</style>

<div class="pdp-container">

	<?php /* Breadcrumb */ ?>
	<ol class="pdp-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'bacera' ); ?>">
		<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Homepage', 'bacera' ); ?></a></li>
		<li style="color:#DDD5CB;">›</li>
		<li><a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Store', 'bacera' ); ?></a></li>
		<li style="color:#DDD5CB;">›</li>
		<li><a href="<?php echo esc_url( add_query_arg( 'filter_collection', rawurlencode( $api_product['category_ids'][0] ?? '' ), $shop_url ) ); ?>"><?php echo esc_html( $breadcrumb_collection ); ?></a></li>
		<li style="color:#DDD5CB;">›</li>
		<li style="color:var(--pdp-text,#2A1F17);font-weight:500;"><?php echo esc_html( $product_title ); ?></li>
	</ol>

	<?php /* Hero grid */ ?>
	<div class="pdp-hero">

		<?php /* Gallery column */ ?>
		<div class="pdp-gallery">
			<div class="pdp-main-wrap">
				<img id="bacera-pdp-main-img"
					src="<?php echo esc_url( $main_image_url ); ?>"
					alt="<?php echo esc_attr( $product_title ); ?>"
					class="pdp-main-img" />

				<?php if ( $discount_pct > 0 ) : ?>
				<span class="pdp-gallery-badge-sale"><?php echo esc_html( '-' . $discount_pct . '%' ); ?></span>
				<?php endif; ?>

				<?php if ( count( $gallery_urls ) > 1 ) : ?>
				<button class="pdp-gallery-nav prev" id="pdp-nav-prev" aria-label="<?php esc_attr_e( 'Previous image', 'bacera' ); ?>">‹</button>
				<button class="pdp-gallery-nav next" id="pdp-nav-next" aria-label="<?php esc_attr_e( 'Next image', 'bacera' ); ?>">›</button>
				<?php endif; ?>
			</div>

			<?php /* Thumbnails */ ?>
			<?php if ( ! empty( $gallery_urls ) ) : ?>
			<div class="pdp-thumbs" id="bacera-pdp-thumbs" role="list" aria-label="<?php esc_attr_e( 'Product images', 'bacera' ); ?>">
				<?php
				$t = 0;
				$thumb_selected_index = 0;
				if ( $main_image_url !== '' && ! empty( $gallery_urls ) ) {
					$found_thumb = array_search( $main_image_url, $gallery_urls, true );
					if ( $found_thumb !== false ) { $thumb_selected_index = (int) $found_thumb; }
				}
				foreach ( $gallery_urls as $gurl ) :
					$sel = ( (int) $t === $thumb_selected_index );
				?>
				<button type="button"
					class="pdp-thumb <?php echo $sel ? 'is-active' : ''; ?>"
					data-src="<?php echo esc_url( $gurl ); ?>"
					data-thumb-idx="<?php echo esc_attr( (string) $t ); ?>"
					role="listitem"
					aria-label="<?php echo esc_attr( sprintf( __( 'Image %d', 'bacera' ), $t + 1 ) ); ?>">
					<img src="<?php echo esc_url( $gurl ); ?>" alt="" loading="lazy" />
				</button>
				<?php $t++; endforeach; ?>
			</div>
			<?php endif; ?>
		</div>

		<?php /* Info column */ ?>
		<div class="pdp-info">
			<div class="pdp-collection-tag"><?php echo esc_html( $breadcrumb_collection ); ?> · <?php esc_html_e( 'Handcrafted in Vietnam', 'bacera' ); ?></div>

			<h1 class="pdp-title"><?php echo esc_html( $product_title ); ?></h1>

			<?php /* Rating */ ?>
			<div class="pdp-rating">
				<?php
				if ( $reviews_total === 0 ) {
					$hero_rating_rounded = 0;
					$hero_rating_label   = __( 'No reviews yet', 'bacera' );
				} elseif ( $ratings_count > 0 ) {
					$hero_rating_rounded = (int) round( $avg_rating );
					$hero_rating_label   = sprintf( __( '%1$s/5 · %2$s reviews', 'bacera' ), number_format( (float) $avg_rating, 1, '.', '' ), number_format_i18n( $reviews_total ) );
				} else {
					$hero_rating_rounded = 0;
					$hero_rating_label   = sprintf( __( '%s reviews', 'bacera' ), number_format_i18n( $reviews_total ) );
				}
				?>
				<span class="pdp-stars" aria-hidden="true">
					<?php for ( $ri = 1; $ri <= 5; $ri++ ) { echo $ri <= $hero_rating_rounded ? '★' : '☆'; } ?>
				</span>
				<?php if ( $ratings_count > 0 ) : ?>
				<span class="pdp-rating-score"><?php echo esc_html( number_format( (float) $avg_rating, 1, '.', '' ) ); ?></span>
				<?php endif; ?>
				<a class="pdp-rating-count" href="#pdp-tab-reviews" onclick="switchPdpTab('reviews');return false;">
					<?php echo esc_html( $hero_rating_label ); ?>
				</a>
			</div>

			<?php /* Price */ ?>
			<div>
				<div class="pdp-price-block">
					<span class="pdp-price" id="bacera-pdp-price-sale"><?php echo esc_html( number_format( $sale_price, 0, ',', '.' ) ); ?>₫</span>
					<?php if ( $discount_pct > 0 ) : ?>
					<span class="pdp-price-old" id="bacera-pdp-price-regular"><?php echo esc_html( number_format( $regular_price, 0, ',', '.' ) ); ?>₫</span>
					<span class="pdp-disc-badge" id="bacera-pdp-discount-badge">-<?php echo esc_html( $discount_pct ); ?>%</span>
					<?php else : ?>
					<span id="bacera-pdp-price-regular" class="pdp-price-old" style="display:none;"></span>
					<span id="bacera-pdp-discount-badge" style="display:none;"></span>
					<?php endif; ?>
				</div>
				<?php if ( $discount_pct > 0 ) : ?>
				<p class="pdp-savings"><?php echo esc_html( sprintf( __( 'You save %s₫', 'bacera' ), number_format( $regular_price - $sale_price, 0, ',', '.' ) ) ); ?></p>
				<?php endif; ?>
			</div>

			<?php /* Description */ ?>
			<?php if ( $short_desc !== '' ) : ?>
			<div class="pdp-excerpt"><?php echo wp_kses_post( wpautop( $short_desc ) ); ?></div>
			<?php endif; ?>

			<?php /* Color variant picker */ ?>
			<?php if ( ! empty( $variations ) ) : ?>
			<div class="pdp-variant-section">
				<div class="pdp-variant-row">
					<span class="pdp-variant-label"><?php esc_html_e( 'Color:', 'bacera' ); ?></span>
					<span class="pdp-variant-val" id="bacera-pdp-color-label"><?php echo esc_html( $color_label ); ?></span>
				</div>
				<div class="pdp-swatches" id="bacera-pdp-color-swatches">
					<?php
					$swatch_palette = [ '#3d2f26', '#4d3d32', '#6b5344', '#8d6a54', '#a9846b', '#c0a28e' ];
					foreach ( $variations as $vi => $_v ) :
						$col    = $swatch_palette[ (int) $vi % count( $swatch_palette ) ];
						$active = ( (int) $vi === $current_vi );
					?>
					<button type="button"
						class="pdp-swatch bacera-pdp-swatch <?php echo $active ? 'is-active' : ''; ?>"
						data-variant-index="<?php echo esc_attr( (string) (int) $vi ); ?>"
						style="background-color:<?php echo esc_attr( $col ); ?>;"
						aria-label="<?php echo esc_attr( sprintf( __( 'Color option %d', 'bacera' ), (int) $vi + 1 ) ); ?>">
					</button>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

			<?php /* Size / Capacity */ ?>
			<?php if ( ! empty( $capacity_options ) ) : ?>
			<div class="pdp-variant-section">
				<span class="pdp-variant-label"><?php esc_html_e( 'Capacity:', 'bacera' ); ?></span>
				<div class="pdp-sizes">
					<?php foreach ( $capacity_options as $opt ) : ?>
					<button type="button" class="pdp-size-pill <?php echo $opt === $capacity_options[0] ? 'is-active' : ''; ?>"
						onclick="document.querySelectorAll('.pdp-size-pill').forEach(function(b){b.classList.remove('is-active');}); this.classList.add('is-active');">
						<?php echo esc_html( $opt ); ?>
					</button>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

			<?php /* Stock */ ?>
			<div class="pdp-stock">
				<?php if ( $stock_quantity > 5 ) : ?>
				<span class="pdp-stock-dot"></span>
				<span><?php echo esc_html( sprintf( __( '%d in stock', 'bacera' ), $stock_quantity ) ); ?></span>
				<?php elseif ( $stock_quantity > 0 ) : ?>
				<span class="pdp-stock-dot low"></span>
				<span><?php echo esc_html( sprintf( __( 'Only %d left', 'bacera' ), $stock_quantity ) ); ?></span>
				<?php else : ?>
				<span class="pdp-stock-dot" style="background:#ccc;"></span>
				<span><?php esc_html_e( 'Out of stock', 'bacera' ); ?></span>
				<?php endif; ?>
			</div>

			<?php /* Add to cart row */ ?>
			<form id="bacera-pdp-add-form" action="#" method="post" onsubmit="return false;">
				<div class="pdp-atc-row">
					<button type="button" class="pdp-btn-wish" id="bacera-pdp-wish-btn" aria-label="<?php esc_attr_e( 'Wishlist', 'bacera' ); ?>">♡</button>
					<div class="pdp-qty">
						<button type="button" class="pdp-qty-btn bacera-pdp-qty-minus" aria-label="<?php esc_attr_e( 'Decrease', 'bacera' ); ?>">−</button>
						<input id="bacera-pdp-qty" type="text" readonly value="01" class="pdp-qty-val" style="border:none;background:transparent;width:38px;text-align:center;font-weight:600;color:var(--pdp-text,#2A1F17);font-size:.9rem;outline:none;cursor:default;" />
						<button type="button" class="pdp-qty-btn bacera-pdp-qty-plus" aria-label="<?php esc_attr_e( 'Increase', 'bacera' ); ?>">+</button>
					</div>
					<button type="button" id="bacera-pdp-add-cart-btn" class="pdp-btn-atc">
						<?php esc_html_e( 'Add to cart', 'bacera' ); ?>
					</button>
				</div>
			</form>

			<?php /* Trust bar */ ?>
			<div class="pdp-trust">
				<div class="pdp-trust-item">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
					<?php esc_html_e( 'Free ship over 2,000,000₫', 'bacera' ); ?>
				</div>
				<div class="pdp-trust-item">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
					<?php esc_html_e( '30-day returns', 'bacera' ); ?>
				</div>
				<div class="pdp-trust-item">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
					<?php esc_html_e( 'Handcrafted · unique piece', 'bacera' ); ?>
				</div>
			</div>

			<?php /* Accordions */ ?>
			<div class="pdp-accordions">
				<?php /* Dimensions */ ?>
				<div class="pdp-acc is-open" id="pdp-acc-dims">
					<button class="pdp-acc-btn" onclick="togglePdpAcc('pdp-acc-dims')">
						<?php esc_html_e( 'Dimensions & Specifications', 'bacera' ); ?>
						<span class="pdp-acc-chevron">▾</span>
					</button>
					<div class="pdp-acc-body">
						<?php if ( ! empty( $dimensions_lines ) ) : ?>
						<table class="pdp-spec-table">
							<?php foreach ( $dimensions_lines as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['label'] ); ?></td>
								<td><?php echo esc_html( $row['value'] ); ?></td>
							</tr>
							<?php endforeach; ?>
						</table>
						<?php else : ?>
						<p><?php esc_html_e( 'Size information will be updated soon.', 'bacera' ); ?></p>
						<?php endif; ?>
					</div>
				</div>

				<div class="pdp-acc" id="pdp-acc-ship">
					<button class="pdp-acc-btn" onclick="togglePdpAcc('pdp-acc-ship')">
						<?php esc_html_e( 'Shipping & Returns', 'bacera' ); ?>
						<span class="pdp-acc-chevron">▾</span>
					</button>
					<div class="pdp-acc-body">
						<?php esc_html_e( 'Orders are carefully packed with recycled materials. Standard delivery 3–5 days within Vietnam. Free returns within 30 days if item arrives damaged.', 'bacera' ); ?>
					</div>
				</div>

				<div class="pdp-acc" id="pdp-acc-care">
					<button class="pdp-acc-btn" onclick="togglePdpAcc('pdp-acc-care')">
						<?php esc_html_e( 'Care Instructions', 'bacera' ); ?>
						<span class="pdp-acc-chevron">▾</span>
					</button>
					<div class="pdp-acc-body">
						<?php esc_html_e( 'Hand wash with warm water and mild soap. Avoid abrasive cleaners. Dry with a soft cloth to preserve the glaze finish.', 'bacera' ); ?>
					</div>
				</div>
			</div>

		</div><?php /* .pdp-info */ ?>
	</div><?php /* .pdp-hero */ ?>

</div><?php /* .pdp-container */ ?>

<?php /* ── JS for variants, gallery, qty ── */ ?>
<?php if ( ! empty( $pdp_js_variants ) ) : ?>
<script type="application/json" id="bacera-pdp-variants-json"><?php echo wp_json_encode( $pdp_js_variants ); ?></script>
<script type="application/json" id="bacera-pdp-cart-meta-json"><?php echo wp_json_encode( [ 'brand' => $breadcrumb_collection, 'productName' => $product_title, 'permalink' => get_permalink( $product_id ) ] ); ?></script>
<script>
(function(){
	var jsonEl = document.getElementById('bacera-pdp-variants-json');
	if (!jsonEl) return;
	var variants;
	try { variants = JSON.parse(jsonEl.textContent); } catch(e) { return; }

	var galleryUrls = <?php echo wp_json_encode( array_values( $gallery_urls ) ); ?>;
	var currentThumbIdx = <?php echo (int) $thumb_selected_index; ?>;

	var mainImg   = document.getElementById('bacera-pdp-main-img');
	var priceSale = document.getElementById('bacera-pdp-price-sale');
	var priceReg  = document.getElementById('bacera-pdp-price-regular');
	var discBadge = document.getElementById('bacera-pdp-discount-badge');
	var colorLbl  = document.getElementById('bacera-pdp-color-label');
	var stockEl   = document.querySelector('.pdp-stock');

	function setMainImg(src, thumbIdx) {
		if (mainImg && src) mainImg.src = src;
		currentThumbIdx = (typeof thumbIdx !== 'undefined') ? thumbIdx : currentThumbIdx;
		document.querySelectorAll('.pdp-thumb').forEach(function(t) {
			var idx = parseInt(t.getAttribute('data-thumb-idx'), 10);
			t.classList.toggle('is-active', idx === currentThumbIdx);
		});
	}

	function applyVariant(i) {
		if (!variants[i]) return;
		var v = variants[i];
		var img = v.mainImage || (galleryUrls && galleryUrls[0]) || '';
		var tidx = galleryUrls ? galleryUrls.indexOf(img) : -1;
		setMainImg(img, tidx >= 0 ? tidx : 0);
		if (priceSale) priceSale.textContent = v.priceFormatted;
		if (priceReg) {
			if (v.regularFormatted) { priceReg.textContent = v.regularFormatted; priceReg.style.display = ''; }
			else { priceReg.textContent = ''; priceReg.style.display = 'none'; }
		}
		if (discBadge) {
			if (v.discountPercent > 0) { discBadge.textContent = '-' + v.discountPercent + '%'; discBadge.style.display = ''; }
			else { discBadge.style.display = 'none'; }
		}
		if (colorLbl) colorLbl.textContent = v.colorLabel || '—';
	}

	/* Swatches */
	document.querySelectorAll('.bacera-pdp-swatch').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var i = parseInt(this.getAttribute('data-variant-index'), 10);
			if (isNaN(i) || i < 0 || i >= variants.length) return;
			document.querySelectorAll('.bacera-pdp-swatch').forEach(function(s) {
				s.classList.toggle('is-active', parseInt(s.getAttribute('data-variant-index'),10) === i);
			});
			applyVariant(i);
		});
	});

	/* Thumbnails */
	document.querySelectorAll('.pdp-thumb').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var src = this.getAttribute('data-src');
			var idx = parseInt(this.getAttribute('data-thumb-idx'), 10);
			setMainImg(src, idx);
		});
	});

	/* Gallery nav arrows */
	var prevBtn = document.getElementById('pdp-nav-prev');
	var nextBtn = document.getElementById('pdp-nav-next');
	if (prevBtn && galleryUrls && galleryUrls.length > 1) {
		prevBtn.addEventListener('click', function() {
			var ni = (currentThumbIdx - 1 + galleryUrls.length) % galleryUrls.length;
			setMainImg(galleryUrls[ni], ni);
		});
	}
	if (nextBtn && galleryUrls && galleryUrls.length > 1) {
		nextBtn.addEventListener('click', function() {
			var ni = (currentThumbIdx + 1) % galleryUrls.length;
			setMainImg(galleryUrls[ni], ni);
		});
	}

	/* Qty */
	var qEl = document.getElementById('bacera-pdp-qty');
	function qVal() { var n = parseInt(qEl && qEl.value ? qEl.value : '1', 10); return isNaN(n) || n < 1 ? 1 : n; }
	function setQ(n) { if (qEl) qEl.value = n < 10 ? '0' + n : String(n); }
	var qmin = document.querySelector('.bacera-pdp-qty-minus');
	var qpls = document.querySelector('.bacera-pdp-qty-plus');
	if (qmin) qmin.addEventListener('click', function() { setQ(Math.max(1, qVal() - 1)); });
	if (qpls) qpls.addEventListener('click', function() { setQ(qVal() + 1); });
})();
</script>
<?php endif; ?>

<script>
function togglePdpAcc(id) {
	var el = document.getElementById(id);
	if (el) el.classList.toggle('is-open');
}
var pdpWish = false;
var wishBtn = document.getElementById('bacera-pdp-wish-btn');
if (wishBtn) { wishBtn.addEventListener('click', function() { pdpWish = !pdpWish; this.textContent = pdpWish ? '♥' : '♡'; this.style.color = pdpWish ? '#C06B3A' : ''; this.style.borderColor = pdpWish ? '#C06B3A' : ''; }); }
function switchPdpTab(name) {
	document.querySelectorAll('.pdp-tab-btn').forEach(function(b){ b.classList.toggle('is-active', b.getAttribute('data-tab') === name); });
	document.querySelectorAll('.pdp-tab-panel').forEach(function(p){ p.classList.toggle('is-active', p.getAttribute('data-tab') === name); });
}
</script>

<?php /* ─── LOWER TABS ─── */ ?>
<div class="pdp-lower">
	<div class="pdp-tabs-nav" role="tablist">
		<button class="pdp-tab-btn is-active" data-tab="details" onclick="switchPdpTab('details')"><?php esc_html_e( 'Product Details', 'bacera' ); ?></button>
		<button class="pdp-tab-btn" data-tab="reviews" onclick="switchPdpTab('reviews')"><?php echo esc_html( sprintf( __( 'Reviews (%d)', 'bacera' ), $reviews_total ) ); ?></button>
	</div>

	<?php /* Detail tab */ ?>
	<div class="pdp-tab-panel is-active" data-tab="details" id="pdp-tab-details">
		<div class="pdp-detail-grid">
			<div class="pdp-rich-text">
				<?php
				$pdp_editor_content = get_post_field( 'post_content', $product_id );
				if ( $pdp_editor_content === '' || trim( wp_strip_all_tags( $pdp_editor_content ) ) === '' ) {
					echo '<p style="color:var(--pdp-muted,#9A8478);">' . esc_html__( 'Add detailed content in the post editor (Pancake Products) in admin.', 'bacera' ) . '</p>';
				} else {
					echo apply_filters( 'the_content', $pdp_editor_content ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</div>
			<div class="pdp-craft-box">
				<div class="pdp-craft-icon">🏺</div>
				<div class="pdp-craft-label"><?php esc_html_e( 'Behind the piece', 'bacera' ); ?></div>
				<div class="pdp-craft-title"><?php esc_html_e( 'Crafted with intention, fired with patience', 'bacera' ); ?></div>
				<div class="pdp-craft-body"><?php esc_html_e( 'Each piece is wheel-thrown by hand in our Hanoi studio using local stoneware clay — a tradition connecting modern craft with centuries of Vietnamese ceramics.', 'bacera' ); ?></div>
			</div>
		</div>
	</div>

	<?php /* Reviews tab */ ?>
	<div class="pdp-tab-panel" data-tab="reviews" id="pdp-tab-reviews">
		<div class="pdp-reviews-grid">

			<?php /* Rating summary */ ?>
			<div class="pdp-rating-summary">
				<div style="color:var(--pdp-accent,#C06B3A);font-size:1.5rem;letter-spacing:-.02em;">
					<?php for ( $i = 1; $i <= 5; $i++ ) { echo $i <= (int) round( $avg_rating ) ? '★' : '☆'; } ?>
				</div>
				<div class="pdp-rating-big"><?php echo esc_html( $avg_rating > 0 ? number_format( $avg_rating, 1, '.', '' ) : '—' ); ?></div>
				<div style="font-size:.75rem;color:var(--pdp-muted,#9A8478);">
					<?php echo esc_html( sprintf( __( 'out of 5 · %d reviews', 'bacera' ), $reviews_total ) ); ?>
				</div>
				<?php if ( $reviews_total > 0 ) : ?>
				<div class="pdp-rating-bar-row">
					<?php
					$star_counts = [ 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 ];
					foreach ( $reviews_list as $rev_item ) {
						$rv = (int) get_comment_meta( (int) $rev_item->comment_ID, 'rating', true );
						if ( $rv >= 1 && $rv <= 5 ) { $star_counts[ $rv ]++; }
					}
					for ( $star = 5; $star >= 1; $star-- ) :
						$pct = $reviews_total > 0 ? round( ( $star_counts[ $star ] / $reviews_total ) * 100 ) : 0;
					?>
					<div class="pdp-rbar">
						<span><?php echo esc_html( $star ); ?>★</span>
						<div class="pdp-rbar-track"><div class="pdp-rbar-fill" style="width:<?php echo esc_attr( (string) $pct ); ?>%;"></div></div>
						<span><?php echo esc_html( $pct ); ?>%</span>
					</div>
					<?php endfor; ?>
				</div>
				<?php endif; ?>
				<button class="pdp-write-review-btn" onclick="document.getElementById('pdp-review-form').scrollIntoView({behavior:'smooth'});">
					✍ <?php esc_html_e( 'Write a review', 'bacera' ); ?>
				</button>
			</div>

			<?php /* Reviews list + form */ ?>
			<div>
				<div class="pdp-reviews-list">
					<?php if ( empty( $reviews_list ) ) : ?>
					<p style="color:var(--pdp-muted,#9A8478);font-size:.875rem;"><?php esc_html_e( 'No reviews yet. Be the first!', 'bacera' ); ?></p>
					<?php else : ?>
					<?php foreach ( array_slice( $reviews_list, 0, 6 ) as $review ) :
						$rev_rating = (int) get_comment_meta( (int) $review->comment_ID, 'rating', true );
						$rev_title  = (string) get_comment_meta( (int) $review->comment_ID, 'review_title', true );
						$initials   = mb_strtoupper( mb_substr( $review->comment_author, 0, 1, 'UTF-8' ), 'UTF-8' );
					?>
					<article class="pdp-review-card">
						<div class="pdp-review-header">
							<div class="pdp-review-author">
								<div class="pdp-review-avatar"><?php echo esc_html( $initials ); ?></div>
								<div>
									<div class="pdp-review-name"><?php echo esc_html( $review->comment_author ); ?></div>
									<div class="pdp-review-date"><?php echo esc_html( get_comment_date( 'F j, Y', $review ) ); ?></div>
								</div>
							</div>
							<?php if ( $rev_rating >= 1 ) : ?>
							<div class="pdp-review-stars">
								<?php for ( $i = 1; $i <= 5; $i++ ) { echo $i <= $rev_rating ? '★' : '☆'; } ?>
							</div>
							<?php endif; ?>
						</div>
						<?php if ( $rev_title !== '' ) : ?>
						<div class="pdp-review-title"><?php echo esc_html( $rev_title ); ?></div>
						<?php endif; ?>
						<p class="pdp-review-body"><?php echo esc_html( $review->comment_content ); ?></p>
						<div class="pdp-review-verified">✓ <?php esc_html_e( 'Verified purchase', 'bacera' ); ?></div>
					</article>
					<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<?php /* Write review form */ ?>
				<div id="pdp-review-form" class="pdp-review-form" style="margin-top:1.5rem;">
					<h3 style="font-family:'Cormorant Garamond',Georgia,serif;font-size:1.4rem;font-weight:500;color:var(--pdp-text,#2A1F17);margin:0 0 1rem;"><?php esc_html_e( 'Write a review', 'bacera' ); ?></h3>
					<form action="<?php echo esc_url( site_url( '/wp-comments-post.php' ) ); ?>" method="post" class="pdp-form-grid">
						<div>
							<label for="pdp-review-rating"><?php esc_html_e( 'Rating', 'bacera' ); ?></label>
							<select id="pdp-review-rating" name="rating" required>
								<option value=""><?php esc_html_e( 'Select rating', 'bacera' ); ?></option>
								<option value="5">5 ★★★★★</option>
								<option value="4">4 ★★★★☆</option>
								<option value="3">3 ★★★☆☆</option>
								<option value="2">2 ★★☆☆☆</option>
								<option value="1">1 ★☆☆☆☆</option>
							</select>
						</div>
						<?php $commenter = wp_get_current_commenter(); ?>
						<div><label for="pdp-author"><?php esc_html_e( 'Name', 'bacera' ); ?></label><input type="text" id="pdp-author" name="author" required value="<?php echo esc_attr( $commenter['comment_author'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Your name', 'bacera' ); ?>" /></div>
						<div><label for="pdp-email"><?php esc_html_e( 'Email', 'bacera' ); ?></label><input type="email" id="pdp-email" name="email" required value="<?php echo esc_attr( $commenter['comment_author_email'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'your@email.com', 'bacera' ); ?>" /></div>
						<div><label for="pdp-review-title"><?php esc_html_e( 'Review title', 'bacera' ); ?></label><input type="text" id="pdp-review-title" name="review_title" placeholder="<?php esc_attr_e( 'In a few words…', 'bacera' ); ?>" /></div>
						<div><label for="pdp-comment"><?php esc_html_e( 'Your review', 'bacera' ); ?></label><textarea id="pdp-comment" name="comment" required rows="5" placeholder="<?php esc_attr_e( 'Tell us what you think…', 'bacera' ); ?>"></textarea></div>
						<input type="hidden" name="comment_post_ID" value="<?php echo esc_attr( $product_id ); ?>" />
						<input type="hidden" name="comment_parent" value="0" />
						<input type="hidden" name="bacera_review_redirect" value="<?php echo esc_url( add_query_arg( 'tab', 'reviews', get_permalink( $product_id ) ) . '#pdp-tab-reviews' ); ?>" />
						<?php wp_nonce_field( 'comment_form_' . $product_id ); ?>
						<button type="submit" class="pdp-form-submit"><?php esc_html_e( 'Post review', 'bacera' ); ?></button>
					</form>
				</div>
			</div>
		</div>
	</div>
</div><?php /* .pdp-lower */ ?>

<?php /* ─── RELATED PRODUCTS ─── */ ?>
<?php
$rel_items = [];
if ( class_exists( 'Pancake_API_Client' ) && class_exists( 'Bacera_Utils' ) ) {
	$rel_api   = new Pancake_API_Client();
	$rel_resp  = $rel_api->request( '/shops/{SHOP_ID}/products/variations?' . http_build_query( [ 'page_size' => 24, 'page' => 1 ] ), 'GET' );
	if ( is_array( $rel_resp ) && ! empty( $rel_resp['success'] ) && ! empty( $rel_resp['data'] ) && is_array( $rel_resp['data'] ) ) {
		foreach ( $rel_resp['data'] as $rel_item ) {
			$vid = isset( $rel_item['id'] ) ? (string) $rel_item['id'] : '';
			$pid = isset( $rel_item['product_id'] ) ? (string) $rel_item['product_id'] : ( isset( $rel_item['product']['id'] ) ? (string) $rel_item['product']['id'] : '' );
			if ( $pancake_variation_id !== '' && $vid === $pancake_variation_id ) { continue; }
			if ( $current_pancake_product_id !== '' && $pid !== '' && $pid === $current_pancake_product_id ) { continue; }
			Bacera_Utils::upsert_external_product( $rel_item );
			$rel_items[] = $rel_item;
			if ( count( $rel_items ) >= 4 ) { break; }
		}
	}
}
?>
<div class="pdp-related">
	<div class="pdp-related-hdr">
		<h2 class="pdp-related-title"><?php esc_html_e( 'You might also like', 'bacera' ); ?></h2>
		<p class="pdp-related-sub"><?php esc_html_e( 'Freshly crafted. New stories waiting to be part of your everyday rituals.', 'bacera' ); ?></p>
	</div>
	<?php if ( ! empty( $rel_items ) ) : ?>
	<div class="pdp-related-grid">
		<?php foreach ( $rel_items as $rp ) :
			$rname  = $rp['product']['name'] ?? $rp['name'] ?? __( 'Product', 'bacera' );
			$rimage = Bacera_Utils::get_proxy_url( $rp );
			if ( $rimage === '' ) { $rimage = $rp['images'][0] ?? $rp['product']['image'] ?? 'https://placehold.co/400x533/f0ece3/8d6a54?text=Bacera'; }
			$rpat = isset( $rp['price_at_counter'] ) ? (float) $rp['price_at_counter'] : 0;
			$rrat = isset( $rp['retail_price'] ) ? (float) $rp['retail_price'] : 0;
			$rprice = $rpat > 0 ? $rpat : $rrat;
			$rorig  = $rrat > $rprice ? $rrat : 0;
			$rbrand = $bacera_pdp_related_brand( $rp );
			if ( $rbrand === '' ) { $rbrand = 'Bacera'; }
			$rurl = Bacera_Utils::get_product_permalink( $rp );
			if ( $rurl === '' ) { $rurl = '#'; }
		?>
		<?php get_template_part( 'app/Views/components/product-card', null, [
			'title'     => $rname,
			'brand'     => $rbrand,
			'price'     => number_format( $rprice, 0, ',', '.' ) . ' ₫',
			'old_price' => $rorig > 0 ? number_format( $rorig, 0, ',', '.' ) . ' ₫' : '',
			'image'     => $rimage,
			'url'       => $rurl,
		] ); ?>
		<?php endforeach; ?>
	</div>
	<?php else : ?>
	<p style="text-align:center;color:var(--pdp-muted,#9A8478);font-size:.875rem;"><?php esc_html_e( 'No suggestions available yet.', 'bacera' ); ?></p>
	<?php endif; ?>
</div>

<?php /* ─── CART DRAWER ─── */ ?>
<div id="bacera-cart-overlay" class="bacera-cart-overlay" aria-hidden="true"></div>
<aside id="bacera-cart-drawer" class="bacera-cart-drawer" aria-hidden="true" aria-label="<?php esc_attr_e( 'Shopping cart', 'bacera' ); ?>">
	<div class="bacera-cart-drawer-head" style="display:flex;align-items:center;justify-content:space-between;">
		<h2 style="margin:0;font-family:'Cormorant Garamond',Georgia,serif;font-size:1.6rem;font-weight:500;color:#2A1F17;"><?php esc_html_e( 'Your Cart', 'bacera' ); ?></h2>
		<button type="button" id="bacera-cart-close" style="width:34px;height:34px;border-radius:50%;border:1px solid #DDD5CB;background:transparent;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#9A8478;" aria-label="<?php esc_attr_e( 'Close cart', 'bacera' ); ?>">×</button>
	</div>
	<div id="bacera-cart-items" class="bacera-cart-items"></div>
	<div class="bacera-cart-footer">
		<div style="display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:1rem;">
			<p style="margin:0;font-size:.8rem;color:#9A8478;"><?php esc_html_e( 'Total (VAT included)', 'bacera' ); ?></p>
			<p id="bacera-cart-total" style="margin:0;font-family:'Cormorant Garamond',Georgia,serif;font-size:1.75rem;color:#2A1F17;">0đ</p>
		</div>
		<div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;">
			<a id="bacera-cart-go-checkout" href="<?php echo esc_url( $checkout_page_url ); ?>" style="border-radius:10px;background:#4d3d32;padding:.75rem 1rem;text-align:center;font-weight:500;color:#fff;text-decoration:none;font-size:.875rem;"><?php esc_html_e( 'Checkout', 'bacera' ); ?></a>
			<a id="bacera-cart-go-cart"     href="<?php echo esc_url( $cart_page_url ); ?>"       style="border-radius:10px;border:1px solid #DDD5CB;padding:.75rem 1rem;text-align:center;font-weight:500;color:#6B5344;text-decoration:none;font-size:.875rem;"><?php esc_html_e( 'View cart', 'bacera' ); ?></a>
		</div>
	</div>
</aside>

</main>

<script>
(function(){
var drawer=document.getElementById('bacera-cart-drawer'),overlay=document.getElementById('bacera-cart-overlay'),closeBtn=document.getElementById('bacera-cart-close'),listEl=document.getElementById('bacera-cart-items'),totalEl=document.getElementById('bacera-cart-total');
var addBtn=document.getElementById('bacera-pdp-add-cart-btn'),jsonEl=document.getElementById('bacera-pdp-variants-json'),metaEl=document.getElementById('bacera-pdp-cart-meta-json');
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
			+'<div style="min-width:0;"><div class="bacera-cart-item-main"><div class="bacera-cart-item-text">'
			+'<p style="margin:0;font-size:.75rem;color:#9A8478;">'+esc(item.brand||'Bacera')+'</p>'
			+'<p style="margin:.15rem 0 0;font-size:.9rem;font-weight:600;color:#2A1F17;">'+esc(item.name||'')+'</p>'
			+(sub?'<p class="bacera-cart-variant-line">'+esc(sub)+'</p>':'')
			+'</div><button type="button" class="bacera-cart-remove" data-cart-action="remove">'+trash+'</button></div>'
			+'<div style="margin-top:.6rem;display:flex;align-items:center;justify-content:space-between;gap:.5rem;">'
			+'<div class="bacera-cart-qty" role="group"><button type="button" data-cart-action="minus">−</button><span>'+String(Number(item.qty||1)).padStart(2,'0')+'</span><button type="button" data-cart-action="plus">+</button></div>'
			+'<div style="text-align:right;">'+oph+'<p style="margin:0;font-size:1rem;font-weight:600;color:#2A1F17;">'+toCurrency(item.price)+'</p></div>'
			+'</div></div></article>';
	}).join('');
	totalEl.textContent=toCurrency(total(cart));
}
function setOpen(open){if(!open)previewId=null;drawer.classList.toggle('is-open',open);overlay.classList.toggle('is-open',open);drawer.setAttribute('aria-hidden',open?'false':'true');overlay.setAttribute('aria-hidden',open?'false':'true');document.body.classList.toggle('overflow-hidden',open);}
function upsertQty(next,qty){var c=loadCart(),i=c.findIndex(function(x){return String(x.id)===String(next.id);});var q=Math.max(1,parseInt(qty,10)||1);if(i>=0){c[i].qty=Number(c[i].qty||1)+q;}else{next.qty=q;c.push(next);}saveCart(c);}
function getVi(){var sw=document.querySelectorAll('.bacera-pdp-swatch');for(var i=0;i<sw.length;i++){if(sw[i].classList.contains('is-active')){var ix=parseInt(sw[i].getAttribute('data-variant-index'),10);return isNaN(ix)?0:ix;}}return 0;}
function getQ(){var q=document.getElementById('bacera-pdp-qty');var n=parseInt(q&&q.value?q.value:'1',10);return isNaN(n)||n<1?1:n;}
if(addBtn&&jsonEl&&metaEl){
	addBtn.addEventListener('click',function(){
		var variants,meta;
		try{variants=JSON.parse(jsonEl.textContent);meta=JSON.parse(metaEl.textContent);}catch(e){return;}
		if(!variants||!variants.length||!meta)return;
		var vi=getVi();if(vi<0||vi>=variants.length)vi=0;
		var v=variants[vi];
		var vid=v.variationId||'';if(!vid)return;
		var item={id:'var_'+vid,variation_id:vid,product_id:v.productId||'',name:meta.productName,brand:meta.brand,variant_label:v.variantLabel||'',color:v.colorLabel||'',size:v.size||'',image:v.mainImage||'',price:Number(v.price||0),original_price:Number(v.originalPrice||0),url:meta.permalink};
		upsertQty(item,getQ());
		previewId=String(item.id);render();setOpen(true);
	});
}
listEl.addEventListener('click',function(e){var btn=e.target.closest('button[data-cart-action]');if(!btn)return;var row=btn.closest('.bacera-cart-item');if(!row)return;var id=row.getAttribute('data-cart-id'),action=btn.getAttribute('data-cart-action'),c=loadCart(),i=c.findIndex(function(x){return String(x.id)===String(id);});if(i<0)return;if(action==='remove')c.splice(i,1);else if(action==='minus'){c[i].qty=Number(c[i].qty||1)-1;if(c[i].qty<=0)c.splice(i,1);}else if(action==='plus')c[i].qty=Number(c[i].qty||1)+1;saveCart(c);render();});
var co=document.getElementById('bacera-cart-go-checkout');if(co){co.addEventListener('click',function(e){var full=loadCart(),picked=previewId?full.filter(function(x){return String(x.id)===String(previewId);}):[];if(!picked.length){e.preventDefault();return;}try{sessionStorage.setItem(CK,JSON.stringify(picked));}catch(err){}});}
closeBtn.addEventListener('click',function(){setOpen(false);});
overlay.addEventListener('click',function(){setOpen(false);});
document.addEventListener('keydown',function(e){if(e.key==='Escape')setOpen(false);});
render();
})();
</script>

<?php
	endwhile;
endif;

get_footer();
?>
