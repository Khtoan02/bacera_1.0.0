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

		$pancake_variation_id = (string) get_post_meta( $product_id, '_pancake_id', true );

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

		$gallery_urls = [];
		if ( $api_product && class_exists( 'Bacera_Utils' ) ) {
			foreach ( $variations as $v ) {
				$proxy_u = $bacera_pdp_variation_proxy_url( $v );
				if ( $proxy_u !== '' && ! in_array( $proxy_u, $gallery_urls, true ) ) {
					$gallery_urls[] = esc_url_raw( $proxy_u );
				}
			}
			if ( empty( $gallery_urls ) ) {
				$single = $bacera_pdp_post_proxy_url();
				if ( $single !== '' ) {
					$gallery_urls[] = esc_url_raw( $single );
				}
			}
		}
		if ( empty( $gallery_urls ) && $api_product ) {
			foreach ( $variations as $v ) {
				if ( empty( $v['images'] ) || ! is_array( $v['images'] ) ) {
					continue;
				}
				foreach ( $v['images'] as $img ) {
					$u = esc_url_raw( (string) $img );
					if ( $u !== '' && ! in_array( $u, $gallery_urls, true ) ) {
						$gallery_urls[] = $u;
					}
				}
			}
			if ( empty( $gallery_urls ) && ! empty( $api_product['image'] ) ) {
				$gallery_urls[] = esc_url_raw( (string) $api_product['image'] );
			}
		}
		if ( empty( $gallery_urls ) && has_post_thumbnail() ) {
			$gallery_urls[] = get_the_post_thumbnail_url( $product_id, 'full' );
		}
		if ( empty( $gallery_urls ) && ! empty( $gallery_images ) && is_array( $gallery_images ) ) {
			foreach ( $gallery_images as $image ) {
				if ( is_array( $image ) && ! empty( $image['url'] ) ) {
					$gallery_urls[] = esc_url_raw( $image['url'] );
				} elseif ( is_numeric( $image ) ) {
					$u = wp_get_attachment_image_url( (int) $image, 'full' );
					if ( $u ) {
						$gallery_urls[] = esc_url_raw( $u );
					}
				}
			}
		}
		if ( empty( $gallery_urls ) ) {
			$fallback_img = get_post_meta( $product_id, '_pancake_image_url', true );
			if ( $fallback_img ) {
				$gallery_urls[] = esc_url_raw( $fallback_img );
			}
		}

		$main_image_url = '';
		if ( $current_variation ) {
			$main_image_url = $bacera_pdp_variation_proxy_url( $current_variation );
		}
		if ( $main_image_url === '' ) {
			$main_image_url = $bacera_pdp_post_proxy_url();
		}
		if ( $main_image_url === '' && $current_variation && ! empty( $current_variation['images'][0] ) ) {
			$main_image_url = esc_url_raw( (string) $current_variation['images'][0] );
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

		$shop_url = class_exists( 'Bacera_Utils' ) ? Bacera_Utils::get_shop_page_url() : home_url( '/' );

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

		$pdp_js_variants = [];
		foreach ( $variations as $vi => $v ) {
			$p_at = (float) ( $v['price_at_counter'] ?? 0 );
			$r_at = (float) ( $v['retail_price'] ?? 0 );
			$pr   = $p_at > 0 ? $p_at : $r_at;
			$reg  = ( $r_at > $pr ) ? $r_at : 0;
			$dct  = 0;
			if ( $reg > 0 && $pr < $reg ) {
				$dct = (int) round( ( ( $reg - $pr ) / $reg ) * 100 );
			}
			$img0 = $bacera_pdp_variation_proxy_url( $v );
			if ( $img0 === '' ) {
				$img0 = ! empty( $v['images'][0] ) ? esc_url_raw( (string) $v['images'][0] ) : $main_image_url;
			}
			$pdp_js_variants[] = [
				'index'            => (int) $vi,
				'variationId'      => isset( $v['id'] ) ? (string) $v['id'] : '',
				'mainImage'        => $img0,
				'priceFormatted'   => number_format( $pr, 0, ',', '.' ) . '₫',
				'regularFormatted' => $reg > 0 ? number_format( $reg, 0, ',', '.' ) . '₫' : '',
				'discountPercent'  => $dct,
				'stock'            => $bacera_pdp_variation_stock( $v ),
				'colorLabel'       => $bacera_pdp_field_by_keywords( $v, [ 'màu', 'mau', 'color' ] ) ?: __( '—', 'bacera' ),
			];
		}

		?>

<main class="bacera-product-detail bacera-pdp-main bg-neutral-100 font-sans text-primary-900 selection:bg-primary-500/10 selection:text-primary-800">
	<style>
		/* Tách nội dung khỏi header — không phụ thuộc class Tailwind có được build hay không */
		.bacera-pdp-main > .bacera-container {
			padding-top: 2.5rem;
			padding-bottom: 2rem;
		}
		@media (min-width: 768px) {
			.bacera-pdp-main > .bacera-container {
				padding-top: 5rem;
				padding-bottom: 3rem;
			}
		}
		@media (min-width: 1024px) {
			.bacera-pdp-main > .bacera-container {
				padding-top: 6.5rem;
				padding-bottom: 3.5rem;
			}
		}
		/* Khối gallery: ảnh chính + thumbnail — bám mép trái cột (trùng với lề nội dung bên dưới) */
		.bacera-pdp-main .bacera-pdp-gallery-block {
			width: 100%;
			max-width: min(100%, 44rem);
			margin-left: 0;
			margin-right: auto;
		}
		@media (min-width: 640px) {
			.bacera-pdp-main .bacera-pdp-gallery-block {
				max-width: min(100%, 42rem);
			}
		}
		@media (min-width: 768px) {
			.bacera-pdp-main .bacera-pdp-gallery-block {
				max-width: min(100%, 34rem);
			}
		}
		@media (min-width: 1024px) {
			.bacera-pdp-main .bacera-pdp-gallery-block {
				max-width: min(100%, 32rem);
			}
		}
		.bacera-pdp-main .bacera-pdp-gallery-block .main-image {
			width: 100%;
			max-width: none;
		}
		/* Ảnh phụ (thumbnail) — lớn hơn, xếp trái */
		.bacera-pdp-main .bacera-pdp-thumb {
			width: 6.25rem;
			height: 6.25rem;
			flex-shrink: 0;
		}
		@media (min-width: 768px) {
			.bacera-pdp-main .bacera-pdp-thumb {
				width: 7.5rem;
				height: 7.5rem;
			}
		}
		@media (min-width: 1024px) {
			.bacera-pdp-main .bacera-pdp-thumb {
				width: 8.25rem;
				height: 8.25rem;
			}
		}
		/* Nội dung phía dưới hero: căn trái thống nhất */
		.bacera-pdp-main .bacera-product-story,
		.bacera-pdp-main .bacera-related-products,
		.bacera-pdp-main .product-accordions {
			text-align: left;
		}
		.bacera-pdp-main .bacera-product-story p,
		.bacera-pdp-main .bacera-related-products .info {
			text-align: left;
		}
		/* Khoảng cách giữa khối hero (ảnh phụ / nội dung mua) và tab «Chi tiết sản phẩm» */
		.bacera-pdp-main section.bacera-product-story {
			margin-top: 2.25rem;
		}
		@media (min-width: 768px) {
			.bacera-pdp-main section.bacera-product-story {
				margin-top: 3.25rem;
			}
		}
		@media (min-width: 1024px) {
			.bacera-pdp-main section.bacera-product-story {
				margin-top: 4rem;
			}
		}
	</style>
	<div class="bacera-container mx-auto max-w-7xl px-4 text-left text-primary-900">

		<section class="bacera-product-hero grid grid-cols-1 items-start gap-10 md:grid-cols-2 md:gap-12 lg:gap-16">
			<div class="product-gallery flex min-w-0 w-full flex-col items-stretch">
				<div class="bacera-pdp-gallery-block">
					<div class="main-image mb-4 w-full overflow-hidden rounded-lg bg-neutral-100 aspect-[4/5]">
						<img id="bacera-pdp-main-img" src="<?php echo esc_url( $main_image_url ); ?>" alt="<?php echo esc_attr( $product_title ); ?>" class="h-full w-full object-cover" />
					</div>
					<div id="bacera-pdp-thumbs" class="thumbnail-list flex w-full flex-wrap justify-start gap-3 md:gap-4">
					<?php
					$t = 0;
					foreach ( $gallery_urls as $gurl ) :
						$sel = ( $t === 0 );
						?>
					<button type="button" data-src="<?php echo esc_url( $gurl ); ?>" class="bacera-pdp-thumb group relative overflow-hidden rounded border-2 transition <?php echo $sel ? 'border-primary-800' : 'border-transparent hover:border-primary-400'; ?>">
						<img src="<?php echo esc_url( $gurl ); ?>" alt="" class="h-full w-full object-cover" loading="lazy" />
					</button>
						<?php
						++$t;
					endforeach;
					if ( $t === 0 ) :
						?>
					<span class="text-sm text-primary-500"><?php esc_html_e( 'No images', 'bacera' ); ?></span>
					<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="product-info flex min-w-0 w-full flex-col items-stretch justify-start self-start text-left">
				<nav class="bacera-breadcrumbs mb-3 text-sm text-primary-600" aria-label="<?php esc_attr_e( 'Breadcrumb', 'bacera' ); ?>">
					<a class="hover:text-primary-800" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Homepage', 'bacera' ); ?></a>
					<span class="mx-2">/</span>
					<a class="hover:text-primary-800" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Store', 'bacera' ); ?></a>
					<span class="mx-2">/</span>
					<span class="text-primary-700"><?php echo esc_html( $breadcrumb_collection ); ?></span>
					<span class="mx-2">/</span>
					<span class="text-primary-900"><?php echo esc_html( $product_title ); ?></span>
				</nav>
				<h1 class="mb-3 font-serif text-3xl font-normal tracking-tight text-primary-900 md:text-4xl"><?php echo esc_html( $product_title ); ?></h1>

				<?php
				if ( $reviews_total === 0 ) {
					$hero_rating_rounded = 0;
					$hero_rating_label   = __( 'Chưa có đánh giá', 'bacera' );
				} elseif ( $ratings_count > 0 ) {
					$hero_rating_rounded = (int) round( $avg_rating );
					$hero_rating_label   = sprintf(
						/* translators: 1: average rating (e.g. 4,9), 2: review count */
						__( '%1$s/5 - %2$s lượt đánh giá', 'bacera' ),
						number_format( (float) $avg_rating, 1, ',', '' ),
						number_format_i18n( (int) $reviews_total )
					);
				} else {
					$hero_rating_rounded = 0;
					$hero_rating_label   = sprintf(
						/* translators: %s: review count */
						__( '%s lượt đánh giá (chưa có điểm sao)', 'bacera' ),
						number_format_i18n( (int) $reviews_total )
					);
				}
				?>
				<div
					class="product-rating mb-5 flex flex-wrap items-center gap-2 text-sm"
					aria-label="<?php echo esc_attr( $hero_rating_label ); ?>"
				>
					<span class="inline-flex items-center gap-px" aria-hidden="true">
						<?php
						for ( $ri = 1; $ri <= 5; $ri++ ) {
							$star_class = $ri <= $hero_rating_rounded ? 'text-accent-500' : 'text-primary-300';
							echo '<span class="' . esc_attr( $star_class ) . '">★</span>';
						}
						?>
					</span>
					<span class="text-primary-600"><?php echo esc_html( $hero_rating_label ); ?></span>
				</div>

				<div class="product-excerpt mb-6 text-left text-base leading-relaxed text-primary-800 [&_p]:text-left [&_p]:text-primary-800">
					<?php echo wp_kses_post( wpautop( $short_desc ) ); ?>
				</div>

				<div class="product-price mb-6 flex flex-wrap items-center gap-3">
					<span id="bacera-pdp-price-sale" class="price-sale text-2xl font-semibold text-primary-900 tabular-nums"><?php echo esc_html( number_format( $sale_price, 0, ',', '.' ) ); ?>₫</span>
					<?php if ( $discount_pct > 0 ) : ?>
					<span id="bacera-pdp-discount-badge" class="discount-badge rounded bg-primary-900 px-2.5 py-1 text-xs font-medium text-white"><?php echo esc_html( '-' . $discount_pct . '%' ); ?></span>
					<span id="bacera-pdp-price-regular" class="price-regular text-sm text-primary-400 line-through tabular-nums"><?php echo esc_html( number_format( $regular_price, 0, ',', '.' ) ); ?>₫</span>
					<?php else : ?>
					<span id="bacera-pdp-discount-badge" class="hidden"></span>
					<span id="bacera-pdp-price-regular" class="price-regular hidden text-sm text-primary-400 line-through tabular-nums"></span>
					<?php endif; ?>
				</div>

				<form class="product-add-to-cart-form" action="#" method="post">
					<div class="variation-color mb-5">
						<label class="mb-2 block text-sm text-primary-700">
							<?php esc_html_e( 'Màu sắc', 'bacera' ); ?>:
							<strong id="bacera-pdp-color-label"><?php echo esc_html( $color_label ); ?></strong>
						</label>
						<div id="bacera-pdp-color-swatches" class="flex flex-wrap gap-2">
							<?php if ( ! empty( $variations ) ) : ?>
								<?php foreach ( $variations as $vi => $_v ) : ?>
									<?php
									$col    = $swatch_palette[ (int) $vi % count( $swatch_palette ) ];
									$active = ( (int) $vi === $current_vi );
									?>
							<button type="button" data-variant-index="<?php echo esc_attr( (string) (int) $vi ); ?>" class="bacera-pdp-swatch h-9 w-9 rounded-full border-2 transition <?php echo $active ? 'border-primary-800' : 'border-transparent ring-1 ring-primary-200'; ?>" style="background-color: <?php echo esc_attr( $col ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: swatch index */ __( 'Color option %d', 'bacera' ), (int) $vi + 1 ) ); ?>"></button>
								<?php endforeach; ?>
							<?php else : ?>
								<?php
								for ( $si = 0; $si < 3; $si++ ) {
									$col = $swatch_palette[ $si % count( $swatch_palette ) ];
									echo '<span class="h-9 w-9 rounded-full ring-1 ring-primary-200" style="background-color:' . esc_attr( $col ) . '"></span>';
								}
								?>
							<?php endif; ?>
						</div>
					</div>

					<div class="variation-size mb-6">
						<label class="mb-2 block text-sm text-primary-700" for="bacera-pdp-capacity"><?php esc_html_e( 'Dung tích', 'bacera' ); ?></label>
						<select id="bacera-pdp-capacity" name="capacity" class="w-full rounded-md border border-primary-200 bg-white/90 p-2.5 text-primary-900">
							<?php foreach ( $capacity_options as $opt ) : ?>
							<option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<p class="stock-status mb-5 text-sm text-primary-700">
						<?php esc_html_e( 'Còn', 'bacera' ); ?>
						<span id="bacera-pdp-stock"><?php echo esc_html( (string) (int) $stock_quantity ); ?></span>
						<?php esc_html_e( ' sản phẩm', 'bacera' ); ?>
					</p>

					<div class="product-actions mb-8 flex flex-col gap-4 sm:flex-row sm:items-stretch sm:justify-start">
						<button type="button" class="btn-wishlist flex h-12 w-12 shrink-0 items-center justify-center rounded-md border border-primary-200 bg-white text-primary-800 hover:bg-primary-100/60" aria-label="<?php esc_attr_e( 'Wishlist', 'bacera' ); ?>">♡</button>
						<div class="qty-input flex h-12 items-center justify-center rounded-md border border-primary-200 bg-white">
							<button type="button" class="bacera-pdp-qty-minus px-3 py-2 text-primary-700 hover:bg-primary-100/80" aria-label="<?php esc_attr_e( 'Decrease quantity', 'bacera' ); ?>">−</button>
							<input id="bacera-pdp-qty" type="text" readonly value="<?php echo esc_attr( str_pad( (string) 1, 2, '0', STR_PAD_LEFT ) ); ?>" class="w-12 border-0 bg-transparent text-center text-sm tabular-nums text-primary-900 focus:ring-0" />
							<button type="button" class="bacera-pdp-qty-plus px-3 py-2 text-primary-700 hover:bg-primary-100/80" aria-label="<?php esc_attr_e( 'Increase quantity', 'bacera' ); ?>">+</button>
						</div>
						<button type="submit" class="btn-add-cart flex-1 rounded-md bg-accent-500 px-6 py-3 font-medium text-white transition-colors hover:bg-accent-600">
							<?php esc_html_e( 'Thêm vào giỏ hàng', 'bacera' ); ?>
						</button>
					</div>
				</form>

				<div class="product-accordions w-full border-t border-primary-100 text-left">
					<details class="group border-b border-primary-100 py-4" open>
						<summary class="flex cursor-pointer list-none items-center justify-between font-medium text-primary-900 [&::-webkit-details-marker]:hidden">
							<?php esc_html_e( 'Kích thước', 'bacera' ); ?>
							<span class="text-primary-500 transition group-open:rotate-180">▾</span>
						</summary>
						<div class="mt-3 text-sm leading-relaxed text-primary-700">
							<?php if ( ! empty( $dimensions_lines ) ) : ?>
							<ul class="list-outside list-disc space-y-1 pl-5 text-left">
								<?php foreach ( $dimensions_lines as $row ) : ?>
								<li>
									<?php if ( $row['label'] !== '' ) : ?>
									<strong class="font-medium text-primary-900"><?php echo esc_html( $row['label'] ); ?>:</strong>
									<?php endif; ?>
									<?php echo esc_html( $row['value'] ); ?>
								</li>
								<?php endforeach; ?>
							</ul>
							<?php else : ?>
							<p class="m-0"><?php esc_html_e( 'Thông tin kích thước đang cập nhật.', 'bacera' ); ?></p>
							<?php endif; ?>
						</div>
					</details>
					<details class="group border-b border-primary-100 py-4">
						<summary class="flex cursor-pointer list-none items-center justify-between font-medium text-primary-900 [&::-webkit-details-marker]:hidden">
							<?php esc_html_e( 'Giao hàng & Đổi trả', 'bacera' ); ?>
							<span class="text-primary-500 transition group-open:rotate-180">▾</span>
						</summary>
						<div class="mt-3 text-sm text-primary-700">
							<?php esc_html_e( 'Chính sách giao hàng và đổi trả theo quy định của Bacera. Liên hệ hỗ trợ nếu bạn cần hỗ trợ thêm.', 'bacera' ); ?>
						</div>
					</details>
				</div>

			</div>
		</section>

		<?php if ( ! empty( $pdp_js_variants ) ) : ?>
		<script type="application/json" id="bacera-pdp-variants-json"><?php echo wp_json_encode( $pdp_js_variants ); ?></script>
		<script>
		(function () {
			var jsonEl = document.getElementById('bacera-pdp-variants-json');
			if (!jsonEl) return;
			var variants;
			try { variants = JSON.parse(jsonEl.textContent); } catch (e) { return; }
			var mainImg = document.getElementById('bacera-pdp-main-img');
			var priceSale = document.getElementById('bacera-pdp-price-sale');
			var priceReg = document.getElementById('bacera-pdp-price-regular');
			var discBadge = document.getElementById('bacera-pdp-discount-badge');
			var colorLabel = document.getElementById('bacera-pdp-color-label');
			var stockEl = document.getElementById('bacera-pdp-stock');
			var swatches = document.querySelectorAll('.bacera-pdp-swatch');
			function applyVariant(i) {
				if (!variants[i] || !mainImg) return;
				var v = variants[i];
				mainImg.src = v.mainImage;
				if (priceSale) priceSale.textContent = v.priceFormatted;
				if (priceReg) {
					if (v.regularFormatted) { priceReg.textContent = v.regularFormatted; priceReg.classList.remove('hidden'); }
					else { priceReg.textContent = ''; priceReg.classList.add('hidden'); }
				}
				if (discBadge) {
					if (v.discountPercent > 0) { discBadge.textContent = '-' + v.discountPercent + '%'; discBadge.classList.remove('hidden'); }
					else { discBadge.classList.add('hidden'); }
				}
				if (colorLabel) colorLabel.textContent = v.colorLabel || '—';
				if (stockEl) stockEl.textContent = String(v.stock);
			}
			swatches.forEach(function (btn) {
				btn.addEventListener('click', function () {
					var i = parseInt(btn.getAttribute('data-variant-index'), 10);
					if (isNaN(i) || i < 0 || i >= variants.length) return;
					swatches.forEach(function (s) {
						var si = parseInt(s.getAttribute('data-variant-index'), 10);
						var on = si === i;
						s.classList.toggle('border-primary-800', on);
						s.classList.toggle('border-transparent', !on);
					});
					applyVariant(i);
				});
			});
			var thumbs = document.querySelectorAll('.bacera-pdp-thumb');
			thumbs.forEach(function (t) {
				t.addEventListener('click', function () {
					var src = t.getAttribute('data-src');
					if (src && mainImg) mainImg.src = src;
					thumbs.forEach(function (x) {
						x.classList.toggle('border-primary-800', x === t);
						x.classList.toggle('border-transparent', x !== t);
					});
				});
			});
			var q = document.getElementById('bacera-pdp-qty');
			var qmin = document.querySelector('.bacera-pdp-qty-minus');
			var qplus = document.querySelector('.bacera-pdp-qty-plus');
			function qVal() { var n = parseInt((q && q.value) ? q.value : '1', 10); return isNaN(n) ? 1 : n; }
			function setQ(n) { if (q) q.value = n < 10 ? ('0' + n) : String(n); }
			if (qmin) qmin.addEventListener('click', function () { var n = Math.max(1, qVal() - 1); setQ(n); });
			if (qplus) qplus.addEventListener('click', function () { var n = qVal() + 1; setQ(n); });
		})();
		</script>
		<?php endif; ?>

        <section class="bacera-product-story w-full text-left text-primary-900">
            <div class="tabs-nav mb-8 flex justify-start gap-8 border-b border-primary-100">
                <button
					type="button"
					class="bacera-story-tab border-b-2 pb-2 font-medium <?php echo $review_tab_requested ? 'border-transparent text-primary-600 hover:text-primary-900' : 'border-primary-900 text-primary-900'; ?>"
					data-target="detail"
					aria-selected="<?php echo $review_tab_requested ? 'false' : 'true'; ?>">
					<?php esc_html_e( 'Chi tiết sản phẩm', 'bacera' ); ?>
				</button>
                <button
					type="button"
					class="bacera-story-tab border-b-2 pb-2 font-medium <?php echo $review_tab_requested ? 'border-primary-900 text-primary-900' : 'border-transparent text-primary-600 hover:text-primary-900'; ?>"
					data-target="reviews"
					aria-selected="<?php echo $review_tab_requested ? 'true' : 'false'; ?>">
					<?php esc_html_e( 'Đánh giá sản phẩm', 'bacera' ); ?>
				</button>
            </div>

			<div class="tabs-content w-full max-w-none text-left text-primary-800">
				<div id="bacera-pdp-tab-detail" class="<?php echo $review_tab_requested ? 'hidden' : ''; ?>">
					<div class="bacera-pdp-entry-content entry-content rounded-xl border border-primary-100 bg-white p-6 shadow-sm md:p-8 [&_img]:h-auto [&_img]:max-w-full [&_img]:rounded-lg [&_a]:text-accent-600 [&_a]:underline [&_a]:underline-offset-2 hover:[&_a]:text-accent-500 [&_h2]:mb-4 [&_h2]:mt-8 [&_h2]:font-serif [&_h2]:text-2xl [&_h2]:text-primary-900 [&_h2]:first:mt-0 [&_h3]:mb-3 [&_h3]:mt-6 [&_h3]:font-serif [&_h3]:text-xl [&_h3]:text-primary-900 [&_p]:mb-4 [&_p]:leading-relaxed [&_ul]:mb-4 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:mb-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_li]:my-1 [&_blockquote]:border-l-4 [&_blockquote]:border-primary-200 [&_blockquote]:pl-4 [&_blockquote]:italic">
						<?php
						$pdp_editor_content = get_post_field( 'post_content', $product_id );
						if ( $pdp_editor_content === '' || trim( wp_strip_all_tags( $pdp_editor_content ) ) === '' ) {
							echo '<p class="m-0 text-primary-600">' . esc_html__( 'Thêm nội dung chi tiết trong trình soạn thảo bài viết (Pancake Products) trong quản trị.', 'bacera' ) . '</p>';
						} else {
							echo apply_filters( 'the_content', $pdp_editor_content );
						}
						?>
					</div>
				</div>

				<?php
				$commenter = wp_get_current_commenter();
				?>
				<div id="bacera-pdp-tab-reviews" class="<?php echo $review_tab_requested ? '' : 'hidden'; ?>">
					<div class="grid grid-cols-1 gap-4 lg:grid-cols-5 lg:gap-6">
						<div class="rounded-xl border border-primary-100 bg-white p-6 shadow-sm lg:col-span-3">
							<h3 class="mb-2 text-3xl font-medium text-primary-900"><?php esc_html_e( 'Đánh giá sản phẩm', 'bacera' ); ?></h3>
							<div class="mb-6 flex items-center gap-2 text-sm text-primary-700">
								<span><?php echo esc_html( $avg_rating > 0 ? number_format( $avg_rating, 1 ) : '0.0' ); ?>/5</span>
								<span>•</span>
								<span><?php echo esc_html( (string) $reviews_total ); ?> <?php esc_html_e( 'Reviews', 'bacera' ); ?></span>
								<span class="ml-1 text-[#D46A50]">
									<?php
									$avg_star = (int) round( $avg_rating );
									for ( $i = 1; $i <= 5; $i++ ) {
										echo $i <= $avg_star ? '★' : '☆';
									}
									?>
								</span>
							</div>

							<div class="space-y-4">
								<?php if ( empty( $reviews_list ) ) : ?>
									<p class="m-0 text-primary-600"><?php esc_html_e( 'Chưa có đánh giá nào. Hãy là người đầu tiên đánh giá sản phẩm này.', 'bacera' ); ?></p>
								<?php else : ?>
									<?php foreach ( array_slice( $reviews_list, 0, 6 ) as $review ) : ?>
										<?php
										$review_rating = (int) get_comment_meta( (int) $review->comment_ID, 'rating', true );
										$review_title  = (string) get_comment_meta( (int) $review->comment_ID, 'review_title', true );
										?>
										<article class="border-b border-primary-100 pb-4 last:border-b-0">
											<div class="mb-1 text-sm text-[#D46A50]">
												<?php
												for ( $i = 1; $i <= 5; $i++ ) {
													echo $i <= $review_rating ? '★' : '☆';
												}
												?>
											</div>
											<?php if ( $review_title !== '' ) : ?>
												<h4 class="mb-1 text-base font-medium text-primary-900"><?php echo esc_html( $review_title ); ?></h4>
											<?php endif; ?>
											<p class="m-0 text-base leading-relaxed text-primary-800"><?php echo esc_html( $review->comment_content ); ?></p>
											<p class="mt-1 text-sm text-primary-600">
												<?php echo esc_html( $review->comment_author ); ?>
												<span>•</span>
												<?php echo esc_html( get_comment_date( 'F j, Y', $review ) ); ?>
											</p>
										</article>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</div>

						<div class="rounded-xl border border-primary-100 bg-white p-6 shadow-sm lg:col-span-2">
							<h3 class="mb-4 text-3xl font-medium text-primary-900"><?php esc_html_e( 'Viết đánh giá', 'bacera' ); ?></h3>
							<form action="<?php echo esc_url( site_url( '/wp-comments-post.php' ) ); ?>" method="post" class="flex flex-col">
								<div class="flex flex-col gap-5">
									<div>
										<label for="bacera-review-rating" class="mb-2 block text-sm font-medium text-primary-700"><?php esc_html_e( 'Rating', 'bacera' ); ?></label>
										<select id="bacera-review-rating" name="rating" required class="w-full rounded-md border border-primary-200 px-3 py-2.5 text-primary-900 focus:border-accent-500 focus:outline-none focus:ring-1 focus:ring-accent-500">
											<option value=""><?php esc_html_e( 'Select rating', 'bacera' ); ?></option>
											<option value="5">5 ★</option>
											<option value="4">4 ★</option>
											<option value="3">3 ★</option>
											<option value="2">2 ★</option>
											<option value="1">1 ★</option>
										</select>
									</div>
									<input type="text" name="author" required value="<?php echo esc_attr( $commenter['comment_author'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Name', 'bacera' ); ?>" class="w-full rounded-md border border-primary-200 px-3 py-2.5 text-primary-900 focus:border-accent-500 focus:outline-none focus:ring-1 focus:ring-accent-500" />
									<input type="email" name="email" required value="<?php echo esc_attr( $commenter['comment_author_email'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Email', 'bacera' ); ?>" class="w-full rounded-md border border-primary-200 px-3 py-2.5 text-primary-900 focus:border-accent-500 focus:outline-none focus:ring-1 focus:ring-accent-500" />
									<input type="text" name="review_title" placeholder="<?php esc_attr_e( 'Title', 'bacera' ); ?>" class="w-full rounded-md border border-primary-200 px-3 py-2.5 text-primary-900 focus:border-accent-500 focus:outline-none focus:ring-1 focus:ring-accent-500" />
									<textarea name="comment" required rows="5" placeholder="<?php esc_attr_e( 'Write your review', 'bacera' ); ?>" class="min-h-[140px] w-full rounded-md border border-primary-200 px-3 py-3 leading-relaxed text-primary-900 focus:border-accent-500 focus:outline-none focus:ring-1 focus:ring-accent-500"></textarea>
								</div>
								<div class="mt-6">
									<input type="hidden" name="comment_post_ID" value="<?php echo esc_attr( $product_id ); ?>" />
									<input type="hidden" name="comment_parent" value="0" />
									<input type="hidden" name="bacera_review_redirect" value="<?php echo esc_url( $review_redirect_url . '#bacera-pdp-tab-reviews' ); ?>" />
									<?php wp_nonce_field( 'comment_form_' . $product_id ); ?>
									<button type="submit" class="mt-5 w-full rounded-md bg-accent-500 px-4 py-3 text-base font-medium text-white transition-colors hover:bg-accent-600">
										<?php esc_html_e( 'Post a review', 'bacera' ); ?>
									</button>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
			<script>
			(function () {
				var tabs = document.querySelectorAll('.bacera-story-tab');
				var detail = document.getElementById('bacera-pdp-tab-detail');
				var reviews = document.getElementById('bacera-pdp-tab-reviews');
				if (!tabs.length || !detail || !reviews) return;
				function activate(which) {
					var showReviews = which === 'reviews';
					detail.classList.toggle('hidden', showReviews);
					reviews.classList.toggle('hidden', !showReviews);
					tabs.forEach(function (tab) {
						var active = tab.getAttribute('data-target') === which;
						tab.setAttribute('aria-selected', active ? 'true' : 'false');
						tab.classList.toggle('border-primary-900', active);
						tab.classList.toggle('text-primary-900', active);
						tab.classList.toggle('border-transparent', !active);
						tab.classList.toggle('text-primary-600', !active);
					});
				}
				tabs.forEach(function (tab) {
					tab.addEventListener('click', function () {
						activate(tab.getAttribute('data-target'));
					});
				});
			})();
			</script>
        </section>

		<?php
		$current_pancake_product_id = (string) get_post_meta( $product_id, '_pancake_product_id', true );
		$related_items              = [];
		if ( class_exists( 'Pancake_API_Client' ) && class_exists( 'Bacera_Utils' ) ) {
			$api          = new Pancake_API_Client();
			$rel_endpoint = '/shops/{SHOP_ID}/products/variations?' . http_build_query(
				[
					'page_size' => 24,
					'page'      => 1,
				]
			);
			$rel_resp = $api->request( $rel_endpoint, 'GET' );
			if ( is_array( $rel_resp ) && ! empty( $rel_resp['success'] ) && ! empty( $rel_resp['data'] ) && is_array( $rel_resp['data'] ) ) {
				foreach ( $rel_resp['data'] as $item ) {
					$vid = isset( $item['id'] ) ? (string) $item['id'] : '';
					$pid = isset( $item['product_id'] ) ? (string) $item['product_id'] : '';
					if ( $pid === '' && ! empty( $item['product']['id'] ) ) {
						$pid = (string) $item['product']['id'];
					}
					if ( $pancake_variation_id !== '' && $vid === $pancake_variation_id ) {
						continue;
					}
					if ( $current_pancake_product_id !== '' && $pid !== '' && $pid === $current_pancake_product_id ) {
						continue;
					}
					Bacera_Utils::upsert_external_product( $item );
					$related_items[] = $item;
					if ( count( $related_items ) >= 4 ) {
						break;
					}
				}
			}
		}
		?>
        <section class="bacera-related-products mt-24 mb-12 w-full rounded-[2rem] border border-primary-100 bg-[#F9F7F2] px-4 py-10 text-primary-900 md:px-10">
            <div class="mx-auto mb-10 max-w-3xl text-center">
                <h2 class="mb-2 font-serif text-3xl text-primary-900"><?php esc_html_e( 'You might also like', 'bacera' ); ?></h2>
                <p class="m-0 text-sm leading-relaxed text-primary-600"><?php esc_html_e( 'Freshly crafted. New stories waiting to be part of your everyday rituals.', 'bacera' ); ?></p>
            </div>

			<?php if ( empty( $related_items ) ) : ?>
				<p class="m-0 text-center text-sm text-primary-600"><?php esc_html_e( 'Hiện chưa có sản phẩm gợi ý.', 'bacera' ); ?></p>
			<?php else : ?>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4 md:gap-6">
					<?php
					foreach ( $related_items as $p ) :
						$name = $p['product']['name'] ?? $p['name'] ?? __( 'Product', 'bacera' );

						// Ưu tiên proxy như Shop, fallback qua nhiều key ảnh từ payload.
						$image_url = Bacera_Utils::get_proxy_url( $p );
						if ( $image_url === '' ) {
							$image_url = $p['images'][0]
								?? $p['product']['images'][0]
								?? $p['image']
								?? $p['image_url']
								?? $p['product']['image']
								?? $p['product']['image_url']
								?? '';
						}
						if ( $image_url === '' ) {
							$image_url = 'https://placehold.co/400x533/f0ece3/8d6a54?text=Product';
						}

						$price_at_counter = isset( $p['price_at_counter'] ) ? (float) $p['price_at_counter'] : ( isset( $p['variations'][0]['price_at_counter'] ) ? (float) $p['variations'][0]['price_at_counter'] : 0 );
						$retail_price     = isset( $p['retail_price'] ) ? (float) $p['retail_price'] : ( isset( $p['variations'][0]['retail_price'] ) ? (float) $p['variations'][0]['retail_price'] : 0 );

						$price            = $price_at_counter > 0 ? $price_at_counter : $retail_price;
						$original_price   = ( $retail_price > $price ) ? $retail_price : 0;
						$discount_percent = false;
						if ( $original_price > 0 && $price < $original_price ) {
							$discount_percent = '-' . (string) (int) round( ( ( $original_price - $price ) / $original_price ) * 100 ) . '%';
						}

						$brand = $bacera_pdp_related_brand( $p );
						if ( $brand === '' ) {
							$brand = __( 'Bacera', 'bacera' );
						}

						$product_detail_url = Bacera_Utils::get_product_permalink( $p );
						if ( $product_detail_url === '' ) {
							$product_detail_url = '#';
						}

						?>
						<div class="w-full">
							<a href="<?php echo esc_url( $product_detail_url ); ?>" class="group block w-full no-underline">
								<div class="relative mx-auto mb-3 max-w-[93%] overflow-hidden rounded-lg bg-neutral-100">
									<?php if ( $discount_percent ) : ?>
										<span class="absolute left-2 top-2 z-10 rounded bg-white/90 px-2 py-1 text-[11px] font-semibold text-primary-900 shadow-sm"><?php echo esc_html( $discount_percent ); ?></span>
									<?php endif; ?>
									<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy" class="h-56 w-full object-cover transition-transform duration-500 group-hover:scale-105" />
								</div>
								<p class="mb-1 text-[10px] font-medium uppercase tracking-[0.12em] text-primary-700/70"><?php echo esc_html( $brand ); ?></p>
								<h3 class="mb-1 font-serif text-[22px] leading-tight text-primary-900"><?php echo esc_html( $name ); ?></h3>
								<div class="flex flex-wrap items-baseline gap-2">
									<span class="text-base font-medium text-primary-900"><?php echo esc_html( number_format( $price, 0, ',', '.' ) . ' ₫' ); ?></span>
									<?php if ( $original_price > 0 ) : ?>
										<span class="text-sm text-primary-700/35 line-through"><?php echo esc_html( number_format( $original_price, 0, ',', '.' ) . ' ₫' ); ?></span>
									<?php endif; ?>
								</div>
							</a>
						</div>
						<?php
					endforeach;
					?>
				</div>
            </div>
			<?php endif; ?>
        </section>

    </div>
</main>

<?php 
    endwhile; 
endif; 

get_footer(); 
?>