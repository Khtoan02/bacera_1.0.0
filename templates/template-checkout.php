<?php
/**
 * Template Name: Checkout — Bacera (3 bước)
 * Description: Thông tin đặt hàng → Phương thức giao hàng & thanh toán → Xác nhận thanh toán (một trang).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$home_url = home_url( '/' );
$cart_url = class_exists( 'Bacera_Utils' ) ? Bacera_Utils::get_cart_page_url() : home_url( '/cart/' );
$shop_url = class_exists( 'Bacera_Utils' ) ? Bacera_Utils::get_shop_page_url() : home_url( '/' );
$bacera_rest_orders_url = rest_url( 'bacera-pancake/v1/orders' );
$bacera_pancake_order_nonce = wp_create_nonce( 'bacera_pancake_create_order' );
// Bắt buộc khi trình duyệt gửi cookie đăng nhập: core REST kiểm tra X-WP-Nonce (wp_rest) trước permission_callback.
$bacera_wp_rest_nonce = wp_create_nonce( 'wp_rest' );

$auth_url = home_url( '/auth/' );
$auth_pages = get_pages(
	[
		'meta_key'   => '_wp_page_template',
		'meta_value' => 'templates/template-auth.php',
		'number'     => 1,
	]
);
if ( ! empty( $auth_pages[0] ) ) {
	$auth_url = get_permalink( $auth_pages[0]->ID );
}

$bacera_vn_areas_base = apply_filters( 'bacera_checkout_vn_areas_api_base', 'https://api.mysupership.vn/v1/partner/areas' );
$bacera_site_name      = get_bloginfo( 'name' );

get_header();
?>

<main id="bacera-checkout" class="min-h-screen bg-[#F9F7F2] font-sans text-stone-800">
	<style>
		#bacera-checkout .bacera-co-wrap {
			box-sizing: border-box;
			width: 100%;
			max-width: 92rem;
			margin-left: auto;
			margin-right: auto;
			padding: 4.5rem 1.25rem 4rem;
			padding-left: max(1.25rem, env(safe-area-inset-left, 0px));
			padding-right: max(1.25rem, env(safe-area-inset-right, 0px));
		}
		@media (min-width: 1024px) {
			#bacera-checkout .bacera-co-wrap {
				padding: 5rem max(2rem, min(8vw, 10rem)) 4.5rem;
			}
		}
		/* Bước 3: ẩn cột giỏ hàng — một cột full width (xl) */
		@media (min-width: 1280px) {
			#bacera-checkout #bacera-chk-layout.bacera-chk-layout--step3 {
				grid-template-columns: minmax(0, 1fr);
			}
		}
		#bacera-checkout .bacera-co-input {
			width: 100%;
			border-radius: 0.75rem;
			border: 1px solid rgb(214 211 209);
			background: #fff;
			padding: 0.75rem 1rem;
			font-size: 0.9375rem;
			color: rgb(41 37 36);
		}
		#bacera-checkout .bacera-co-input:focus {
			outline: none;
			border-color: rgb(217 91 71);
			box-shadow: 0 0 0 3px rgb(217 91 71 / 0.15);
		}
		#bacera-checkout .bacera-co-line-item {
			display: grid;
			grid-template-columns: 5.5rem minmax(0, 1fr);
			gap: 0.875rem;
			padding: 1rem 0;
			border-bottom: 1px solid rgb(231 229 228);
		}
		#bacera-checkout .bacera-co-qty {
			display: inline-flex;
			align-items: center;
			border: 1px solid rgb(214 211 209);
			border-radius: 0.5rem;
			background: #fff;
			font-size: 0.8125rem;
		}
		#bacera-checkout .bacera-co-choice {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 1rem;
			border-radius: 0.75rem;
			border: 1px solid rgb(231 229 228);
			background: #fff;
			padding: 1rem 1.125rem;
			cursor: pointer;
			transition: border-color 0.15s, box-shadow 0.15s;
		}
		#bacera-checkout .bacera-co-choice:has(input:checked) {
			border-color: rgb(217 91 71);
			box-shadow: 0 0 0 3px rgb(217 91 71 / 0.12);
		}
		#bacera-checkout .bacera-co-choice input {
			width: 1.125rem;
			height: 1.125rem;
			accent-color: rgb(217 91 71);
			flex-shrink: 0;
		}
		/* Phương thức giao hàng (tiêu chuẩn / nhanh / siêu tốc) */
		#bacera-checkout .bacera-chk-ship-methods {
			display: flex;
			flex-direction: column;
			gap: 1rem;
		}
		@media (min-width: 640px) {
			#bacera-checkout .bacera-chk-ship-methods {
				gap: 1.125rem;
			}
		}
		#bacera-checkout .bacera-chk-ship-methods .bacera-co-choice {
			padding: 1.125rem 1.25rem;
			align-items: center;
			gap: 1rem 1.25rem;
		}
		@media (min-width: 640px) {
			#bacera-checkout .bacera-chk-ship-methods .bacera-co-choice {
				padding: 1.25rem 1.375rem;
			}
		}
		#bacera-checkout .bacera-chk-ship-methods .bacera-co-choice .bacera-chk-ship-label {
			line-height: 1.55;
		}
		/* Phương thức thanh toán: dãn cách giữa các dòng & padding trong ô */
		#bacera-checkout .bacera-chk-pay-methods {
			display: flex;
			flex-direction: column;
			gap: 1rem;
		}
		@media (min-width: 640px) {
			#bacera-checkout .bacera-chk-pay-methods {
				gap: 1.125rem;
			}
		}
		#bacera-checkout .bacera-chk-pay-methods .bacera-co-choice {
			padding: 1.125rem 1.25rem;
			align-items: center;
			gap: 1rem 1.25rem;
		}
		@media (min-width: 640px) {
			#bacera-checkout .bacera-chk-pay-methods .bacera-co-choice {
				padding: 1.25rem 1.375rem;
			}
		}
		#bacera-checkout .bacera-chk-pay-methods .bacera-co-choice .bacera-chk-pay-label {
			line-height: 1.55;
		}
		/* Bước 3 — phiếu & màn cảm ơn: căn giữa, đủ rộng để đọc dễ */
		#bacera-checkout .bacera-chk-bill-wrap {
			width: 100%;
			max-width: 44rem;
			margin-left: auto;
			margin-right: auto;
		}
		#bacera-checkout .bacera-chk-success {
			width: 100%;
			max-width: 44rem;
			margin-left: auto;
			margin-right: auto;
		}
		#bacera-checkout .bacera-chk-success__crumb {
			font-size: 0.8125rem;
			color: rgb(120 113 108);
		}
		#bacera-checkout .bacera-chk-success__crumb a {
			color: inherit;
			text-decoration: none;
		}
		#bacera-checkout .bacera-chk-success__crumb a:hover {
			text-decoration: underline;
		}
		#bacera-checkout .bacera-chk-success__title {
			margin: 2rem 0 0;
			padding-top: 0.875rem;
			text-align: center;
			font-family: ui-serif, Georgia, Cambria, 'Times New Roman', Times, serif;
			font-size: clamp(1.75rem, 4vw, 2.25rem);
			font-weight: 600;
			letter-spacing: -0.02em;
			color: rgb(28 25 23);
		}
		#bacera-checkout .bacera-chk-success__track-row {
			display: flex;
			justify-content: center;
			width: 100%;
			margin-top: 2rem;
		}
		#bacera-checkout .bacera-chk-success__rule {
			height: 0;
			border: none;
			border-top: 1px solid rgb(231 229 228);
			margin: 1.5rem 0;
		}
		#bacera-checkout .bacera-chk-success__row-status {
			display: flex;
			align-items: center;
			justify-content: flex-start;
			gap: 0.75rem;
			flex-wrap: wrap;
		}
		#bacera-checkout .bacera-chk-success__tick {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 1.75rem;
			height: 1.75rem;
			border-radius: 9999px;
			background: rgb(34 197 94);
			color: #fff;
			flex-shrink: 0;
		}
		#bacera-checkout .bacera-chk-success__meta {
			margin: 1rem 0 0;
			padding: 0;
			list-style: none;
			font-size: 0.875rem;
			color: rgb(120 113 108);
			line-height: 1.65;
		}
		#bacera-checkout .bacera-chk-success__meta li {
			display: flex;
			align-items: flex-start;
			gap: 0.35rem;
			margin-bottom: 0.35rem;
		}
		#bacera-checkout .bacera-chk-success__copy {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			padding: 0.2rem;
			margin-left: 0.25rem;
			border: none;
			background: transparent;
			color: rgb(120 113 108);
			cursor: pointer;
			border-radius: 0.25rem;
		}
		#bacera-checkout .bacera-chk-success__copy:hover {
			color: rgb(68 64 60);
			background: rgb(245 245 244);
		}
		#bacera-checkout .bacera-chk-success__ship h2 {
			margin: 0 0 0.25rem;
			font-size: 1.0625rem;
			font-weight: 700;
			color: rgb(28 25 23);
		}
		#bacera-checkout .bacera-chk-success__ship-sub {
			margin: 0 0 1.25rem;
			font-size: 0.8125rem;
			color: rgb(120 113 108);
		}
		#bacera-checkout .bacera-chk-success__subhd {
			margin: 0 0 0.5rem;
			font-size: 0.875rem;
			font-weight: 700;
			color: rgb(28 25 23);
		}
		#bacera-checkout .bacera-chk-success__track {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: auto;
			min-width: 14rem;
			max-width: 20rem;
			margin: 0;
			padding: 0.9rem 1.5rem;
			border-radius: 0.75rem;
			background: #e4644b;
			color: #fff;
			font-weight: 600;
			font-size: 1rem;
			text-decoration: none;
			box-shadow: 0 1px 2px rgb(28 25 23 / 0.08);
		}
		#bacera-checkout .bacera-chk-success__track:hover {
			filter: brightness(0.95);
		}
		/* Bước 3 — phiếu xác nhận / bill */
		#bacera-checkout .bacera-chk-bill {
			position: relative;
			border-radius: 1rem;
			background: linear-gradient(165deg, rgb(255 255 255) 0%, rgb(252 251 249) 100%);
			box-shadow:
				0 1px 2px rgb(28 25 23 / 0.06),
				0 12px 40px -12px rgb(28 25 23 / 0.12),
				inset 0 1px 0 rgb(255 255 255);
			border: 1px solid rgb(231 229 228);
			overflow: hidden;
		}
		#bacera-checkout .bacera-chk-bill__edge {
			height: 10px;
			background: repeating-linear-gradient(
				90deg,
				rgb(214 211 209) 0px,
				rgb(214 211 209) 6px,
				transparent 6px,
				transparent 12px
			);
			opacity: 0.55;
		}
		#bacera-checkout .bacera-chk-bill__inner {
			padding: 1.5rem 1.25rem 1.75rem;
		}
		@media (min-width: 640px) {
			#bacera-checkout .bacera-chk-bill__inner {
				padding: 1.75rem 1.75rem 2rem;
			}
		}
		#bacera-checkout .bacera-chk-bill__head {
			text-align: center;
			padding-bottom: 1.25rem;
			border-bottom: 1px dashed rgb(214 211 209);
		}
		#bacera-checkout .bacera-chk-bill__brand {
			margin: 0 0 0.35rem;
			font-size: 0.6875rem;
			font-weight: 700;
			letter-spacing: 0.2em;
			text-transform: uppercase;
			color: rgb(120 113 108);
		}
		#bacera-checkout .bacera-chk-bill__title {
			margin: 0;
			font-family: ui-serif, Georgia, Cambria, 'Times New Roman', Times, serif;
			font-size: 1.375rem;
			font-weight: 600;
			letter-spacing: -0.02em;
			color: rgb(28 25 23);
		}
		#bacera-checkout .bacera-chk-bill__subtitle {
			margin: 0.5rem 0 0;
			font-size: 0.8125rem;
			line-height: 1.45;
			color: rgb(120 113 108);
		}
		#bacera-checkout .bacera-chk-bill__meta {
			display: grid;
			gap: 0.625rem;
			padding: 1rem 0;
			font-size: 0.8125rem;
		}
		#bacera-checkout .bacera-chk-bill__meta > div {
			display: flex;
			justify-content: space-between;
			align-items: baseline;
			gap: 1rem;
		}
		#bacera-checkout .bacera-chk-bill__meta-k {
			color: rgb(120 113 108);
			flex-shrink: 0;
		}
		#bacera-checkout .bacera-chk-bill__meta-v {
			text-align: right;
			color: rgb(41 37 36);
			font-weight: 500;
			word-break: break-all;
		}
		#bacera-checkout .bacera-chk-bill__rule {
			height: 0;
			border: none;
			border-top: 1px dashed rgb(214 211 209);
			margin: 0;
		}
		#bacera-checkout .bacera-chk-bill__section {
			padding: 1rem 0;
		}
		#bacera-checkout .bacera-chk-bill__section + .bacera-chk-bill__section {
			border-top: 1px dashed rgb(214 211 209);
		}
		#bacera-checkout .bacera-chk-bill__sec-title {
			margin: 0 0 0.75rem;
			font-size: 0.6875rem;
			font-weight: 700;
			letter-spacing: 0.12em;
			text-transform: uppercase;
			color: rgb(87 83 78);
		}
		#bacera-checkout .bacera-chk-bill__row {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			gap: 1rem;
			font-size: 0.875rem;
			line-height: 1.5;
			padding: 0.35rem 0;
		}
		#bacera-checkout .bacera-chk-bill__row-k {
			flex: 0 0 42%;
			max-width: 11rem;
			color: rgb(120 113 108);
		}
		#bacera-checkout .bacera-chk-bill__row-v {
			flex: 1;
			text-align: right;
			color: rgb(28 25 23);
			font-weight: 500;
		}
		#bacera-checkout .bacera-chk-bill__row-v--multiline {
			white-space: pre-wrap;
			word-break: break-word;
		}
		#bacera-checkout .bacera-chk-bill__amounts {
			padding-top: 0.25rem;
		}
		#bacera-checkout .bacera-chk-bill__amount-row {
			display: flex;
			justify-content: space-between;
			align-items: center;
			font-size: 0.875rem;
			padding: 0.4rem 0;
			color: rgb(87 83 78);
		}
		#bacera-checkout .bacera-chk-bill__amount-row dd {
			margin: 0;
			font-weight: 500;
			font-variant-numeric: tabular-nums;
			color: rgb(41 37 36);
		}
		#bacera-checkout .bacera-chk-bill__amount-total {
			display: flex;
			justify-content: space-between;
			align-items: flex-end;
			gap: 1rem;
			margin-top: 0.75rem;
			padding: 1rem 1.125rem;
			border-radius: 0.75rem;
			background: linear-gradient(135deg, rgb(254 252 251) 0%, rgb(245 240 232) 100%);
			border: 1px solid rgb(231 229 228 / 0.9);
		}
		#bacera-checkout .bacera-chk-bill__amount-total-label {
			margin: 0;
			font-size: 0.8125rem;
			font-weight: 600;
			color: rgb(68 64 60);
		}
		#bacera-checkout .bacera-chk-bill__amount-total-label span {
			display: block;
			font-size: 0.6875rem;
			font-weight: 500;
			color: rgb(120 113 108);
			margin-top: 0.2rem;
		}
		#bacera-checkout .bacera-chk-bill__amount-total-val {
			margin: 0;
			font-size: 1.5rem;
			font-weight: 700;
			letter-spacing: -0.03em;
			font-variant-numeric: tabular-nums;
			color: rgb(194 65 12);
		}
		#bacera-checkout .bacera-chk-bill__foot {
			margin-top: 1rem;
			padding-top: 1rem;
			border-top: 1px dashed rgb(214 211 209);
			text-align: center;
			font-size: 0.6875rem;
			line-height: 1.5;
			color: rgb(168 162 158);
		}
	</style>

	<div class="bacera-co-wrap">
		<nav class="text-xs md:text-sm text-stone-500 mb-6" aria-label="<?php esc_attr_e( 'Breadcrumb', 'bacera' ); ?>">
			<ol class="flex flex-wrap items-center gap-x-2 gap-y-1 list-none m-0 p-0">
				<li><a class="hover:text-primary-700 transition-colors" href="<?php echo esc_url( $home_url ); ?>"><?php esc_html_e( 'Homepage', 'bacera' ); ?></a></li>
				<li class="text-stone-300 select-none" aria-hidden="true">/</li>
				<li><a class="hover:text-primary-700 transition-colors" href="<?php echo esc_url( $cart_url ); ?>"><?php esc_html_e( 'Cart', 'bacera' ); ?></a></li>
				<li class="text-stone-300 select-none" aria-hidden="true">/</li>
				<li class="text-stone-800 font-medium"><?php esc_html_e( 'Thanh toán', 'bacera' ); ?></li>
			</ol>
		</nav>

		<div id="bacera-chk-empty" class="hidden rounded-2xl border border-stone-200 bg-white p-10 text-center shadow-sm">
			<p class="m-0 text-stone-600 mb-6"><?php esc_html_e( 'Không có sản phẩm để thanh toán. Vui lòng chọn sản phẩm trong giỏ hàng.', 'bacera' ); ?></p>
			<a href="<?php echo esc_url( $cart_url ); ?>" class="inline-flex rounded-xl bg-accent-500 px-6 py-3 font-medium text-white no-underline hover:bg-accent-600"><?php esc_html_e( 'Quay lại giỏ hàng', 'bacera' ); ?></a>
		</div>

		<div id="bacera-chk-layout" class="hidden grid grid-cols-1 gap-10 xl:grid-cols-[minmax(0,1fr)_min(26rem,100%)] xl:gap-14 xl:items-start">
			<div class="min-w-0">
				<h1 class="m-0 font-serif text-3xl md:text-4xl font-semibold text-stone-800 tracking-tight mb-8"><?php esc_html_e( 'Thanh toán', 'bacera' ); ?></h1>

				<p id="bacera-chk-progress" class="m-0 mb-10 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-stone-600" aria-live="polite">
					<span data-step-indicator="1" class="font-semibold text-stone-900"><?php esc_html_e( 'Thông tin đặt hàng', 'bacera' ); ?></span>
					<span class="text-stone-300" aria-hidden="true">›</span>
					<span data-step-indicator="2" class="text-stone-600"><?php esc_html_e( 'Phương thức thanh toán', 'bacera' ); ?></span>
					<span class="text-stone-300" aria-hidden="true">›</span>
					<span data-step-indicator="3" class="text-stone-600"><?php esc_html_e( 'Thanh toán', 'bacera' ); ?></span>
				</p>

				<!-- Bước 1 -->
				<div id="bacera-chk-step-1" class="space-y-10">
					<form id="bacera-chk-form-1" class="space-y-10" onsubmit="return false;">
						<div>
							<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
								<h2 class="m-0 text-lg font-semibold text-stone-900"><?php esc_html_e( 'Thông tin liên lạc', 'bacera' ); ?></h2>
								<p class="m-0 text-sm text-stone-600">
									<?php esc_html_e( 'Bạn đã có tài khoản?', 'bacera' ); ?>
									<a href="<?php echo esc_url( $auth_url ); ?>" class="font-medium text-accent-600 hover:text-accent-700"><?php esc_html_e( 'đăng nhập', 'bacera' ); ?></a>
								</p>
							</div>
							<div class="grid gap-4 sm:grid-cols-2">
								<div class="sm:col-span-2">
									<label class="mb-1.5 block text-sm text-stone-700" for="bacera-co-name"><?php esc_html_e( 'Họ và tên', 'bacera' ); ?></label>
									<input class="bacera-co-input" type="text" id="bacera-co-name" name="billing_name" autocomplete="name" />
								</div>
								<div>
									<label class="mb-1.5 block text-sm text-stone-700" for="bacera-co-phone"><?php esc_html_e( 'Số điện thoại', 'bacera' ); ?> <span class="text-red-600">*</span></label>
									<input class="bacera-co-input" type="tel" id="bacera-co-phone" name="billing_phone" required autocomplete="tel" />
								</div>
								<div>
									<label class="mb-1.5 block text-sm text-stone-700" for="bacera-co-email"><?php esc_html_e( 'Địa chỉ Email', 'bacera' ); ?></label>
									<input class="bacera-co-input" type="email" id="bacera-co-email" name="billing_email" autocomplete="email" />
								</div>
								<div class="sm:col-span-2 flex items-start gap-3">
									<input type="checkbox" id="bacera-co-news" name="newsletter" class="mt-1 rounded border-stone-300 text-accent-600 focus:ring-accent-500" />
									<label for="bacera-co-news" class="text-sm leading-snug text-stone-600"><?php esc_html_e( 'Nhận thông báo Email khi có khuyến mãi hoặc sản phẩm mới', 'bacera' ); ?></label>
								</div>
							</div>
						</div>

						<div>
							<h2 class="m-0 mb-4 text-lg font-semibold text-stone-900"><?php esc_html_e( 'Địa chỉ', 'bacera' ); ?></h2>
							<div class="grid gap-4">
								<div>
									<label class="mb-1.5 block text-sm text-stone-700" for="bacera-co-province"><?php esc_html_e( 'Tỉnh/ Thành phố', 'bacera' ); ?> <span class="text-red-600">*</span></label>
									<select class="bacera-co-input" id="bacera-co-province" name="billing_state" required disabled>
										<option value=""><?php esc_html_e( 'Đang tải…', 'bacera' ); ?></option>
									</select>
									<p id="bacera-co-addr-err" class="hidden mt-1.5 text-sm text-red-600 m-0" role="alert"></p>
								</div>
								<div class="grid gap-4 sm:grid-cols-2">
									<div>
										<label class="mb-1.5 block text-sm text-stone-700" for="bacera-co-district"><?php esc_html_e( 'Quận/Huyện', 'bacera' ); ?> <span class="text-red-600">*</span></label>
										<select class="bacera-co-input" id="bacera-co-district" name="billing_district" required disabled>
											<option value=""><?php esc_html_e( 'Chọn tỉnh/thành trước', 'bacera' ); ?></option>
										</select>
									</div>
									<div>
										<label class="mb-1.5 block text-sm text-stone-700" for="bacera-co-ward"><?php esc_html_e( 'Phường/Xã', 'bacera' ); ?> <span class="text-red-600">*</span></label>
										<select class="bacera-co-input" id="bacera-co-ward" name="billing_ward" required disabled>
											<option value=""><?php esc_html_e( 'Chọn quận/huyện trước', 'bacera' ); ?></option>
										</select>
									</div>
								</div>
								<div>
									<label class="mb-1.5 block text-sm text-stone-700" for="bacera-co-address2"><?php esc_html_e( 'Số nhà/ngõ', 'bacera' ); ?> <span class="text-red-600">*</span></label>
									<input class="bacera-co-input" type="text" id="bacera-co-address2" name="billing_address_2" required autocomplete="address-line2" />
								</div>
								<input type="hidden" id="bacera-co-full-address" name="billing_address_1" value="" />
							</div>
						</div>

						<div class="flex flex-col-reverse gap-4 sm:flex-row sm:items-center sm:justify-between pt-2">
							<a href="<?php echo esc_url( $cart_url ); ?>" class="inline-flex items-center gap-1 text-base font-medium text-stone-700 hover:text-stone-900 no-underline">
								<span aria-hidden="true">‹</span> <?php esc_html_e( 'Quay lại giỏ hàng', 'bacera' ); ?>
							</a>
							<button type="button" id="bacera-chk-next-1" class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl bg-accent-500 px-8 py-3.5 text-base font-semibold text-white shadow-sm hover:bg-accent-600 transition-colors border-0 cursor-pointer">
								<?php esc_html_e( 'Tiếp tục', 'bacera' ); ?>
							</button>
						</div>
					</form>
				</div>

				<!-- Bước 2 -->
				<div id="bacera-chk-step-2" class="hidden space-y-8">
					<div>
						<h2 class="m-0 mb-5 text-lg font-semibold text-stone-900"><?php esc_html_e( 'Giao hàng', 'bacera' ); ?></h2>
						<div class="bacera-chk-ship-methods" role="radiogroup" aria-label="<?php esc_attr_e( 'Phương thức giao hàng', 'bacera' ); ?>">
							<label class="bacera-co-choice">
								<span class="flex min-w-0 flex-1 items-center gap-4">
									<input type="radio" name="bacera_ship_method" value="standard" data-fee="0" checked />
									<span class="bacera-chk-ship-label text-sm text-stone-800"><?php esc_html_e( 'Tiêu chuẩn (2-5 ngày làm việc)', 'bacera' ); ?></span>
								</span>
								<span class="shrink-0 text-sm font-medium tabular-nums text-stone-900"><?php esc_html_e( 'Miễn phí', 'bacera' ); ?></span>
							</label>
							<label class="bacera-co-choice">
								<span class="flex min-w-0 flex-1 items-center gap-4">
									<input type="radio" name="bacera_ship_method" value="fast" data-fee="15000" />
									<span class="bacera-chk-ship-label text-sm text-stone-800"><?php esc_html_e( 'Nhanh (1-2 ngày làm việc)', 'bacera' ); ?></span>
								</span>
								<span class="shrink-0 text-sm font-medium tabular-nums text-stone-900">15.000đ</span>
							</label>
							<label class="bacera-co-choice">
								<span class="flex min-w-0 flex-1 items-center gap-4">
									<input type="radio" name="bacera_ship_method" value="express" data-fee="40000" />
									<span class="bacera-chk-ship-label text-sm text-stone-800"><?php esc_html_e( 'Siêu tốc (Trong ngày)', 'bacera' ); ?></span>
								</span>
								<span class="shrink-0 text-sm font-medium tabular-nums text-stone-900">40.000đ</span>
							</label>
						</div>
					</div>

					<div class="pt-1">
						<h2 class="m-0 mb-5 text-lg font-semibold text-stone-900"><?php esc_html_e( 'Phương thức thanh toán', 'bacera' ); ?></h2>
						<div class="bacera-chk-pay-methods" role="radiogroup" aria-label="<?php esc_attr_e( 'Phương thức thanh toán', 'bacera' ); ?>">
							<label class="bacera-co-choice">
								<span class="flex min-w-0 flex-1 items-center gap-4">
									<input type="radio" name="bacera_payment_method" value="cod" checked />
									<span class="bacera-chk-pay-label text-sm text-stone-800"><?php esc_html_e( 'Thanh toán trực tiếp khi giao hàng (COD)', 'bacera' ); ?></span>
								</span>
								<span class="flex h-10 w-10 shrink-0 items-center justify-center text-stone-500" aria-hidden="true">
									<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12 6v12M9 9h4.5a2.5 2.5 0 010 5H9M9 6h6M9 18h6"/></svg>
								</span>
							</label>
							<label class="bacera-co-choice">
								<span class="flex min-w-0 flex-1 items-center gap-4">
									<input type="radio" name="bacera_payment_method" value="atm" />
									<span class="bacera-chk-pay-label text-sm text-stone-800"><?php esc_html_e( 'Thanh toán bằng thẻ quốc tế và nội địa (ATM)', 'bacera' ); ?></span>
								</span>
								<span class="flex h-10 w-10 shrink-0 items-center justify-center text-stone-500" aria-hidden="true">
									<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
								</span>
							</label>
							<label class="bacera-co-choice">
								<span class="flex min-w-0 flex-1 items-center gap-4">
									<input type="radio" name="bacera_payment_method" value="transfer" />
									<span class="bacera-chk-pay-label text-sm text-stone-800"><?php esc_html_e( 'Chuyển khoản (Momo/ VNPay/ Ngân hàng…)', 'bacera' ); ?></span>
								</span>
								<span class="flex h-10 w-10 shrink-0 items-center justify-center text-stone-500" aria-hidden="true">
									<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M4 5h16v14H4z"/><path d="M4 9h16M8 13h4M8 17h8"/></svg>
								</span>
							</label>
							<label class="bacera-co-choice">
								<span class="flex min-w-0 flex-1 items-center gap-4">
									<input type="radio" name="bacera_payment_method" value="paypal" />
									<span class="bacera-chk-pay-label text-sm font-medium text-[#003087]"><?php esc_html_e( 'Thông qua Paypal', 'bacera' ); ?></span>
								</span>
								<span class="flex h-10 w-10 shrink-0 items-center justify-center" aria-hidden="true">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="#003087" d="M7.076 21.337H2.47a.641.641 0 01-.602-.866L6.26.863A.772.772 0 017.01.24h6.122a10.29 10.29 0 014.02.77 8.259 8.259 0 012.744 2.12 6.786 6.786 0 011.47 3.294 9.02 9.02 0 01-.191 4.395 7.6 7.6 0 01-1.4 2.6 8.274 8.274 0 01-2.4 1.92 10.707 10.707 0 01-3.12.96 14.937 14.937 0 01-3.48.39h-2.4a.77.77 0 00-.76.63l-.93 4.04z"/><path fill="#009cde" d="M10.56 7.08h6.48c.12 0 .22.09.24.21l.48 2.88a.24.24 0 01-.24.27h-3.84a.48.48 0 00-.48.42l-.36 2.16a.24.24 0 00.24.27h3.36a.48.48 0 01.45.6l-.48 2.4a.48.48 0 01-.48.36h-4.2a.72.72 0 00-.7.57l-1.2 5.76a.36.36 0 00.35.42h2.88a.6.6 0 00.59-.48l.3-1.44a.6.6 0 01.59-.48h1.92a1.2 1.2 0 001.17-.96l1.44-7.2a1.2 1.2 0 00-1.17-1.44H14.4a.48.48 0 01-.48-.42l-.36-2.16a.24.24 0 01.24-.27z"/></svg>
								</span>
							</label>
						</div>
					</div>

					<div class="flex flex-col-reverse gap-4 sm:flex-row sm:items-center sm:justify-between pt-2">
						<button type="button" id="bacera-chk-back-2" class="inline-flex items-center gap-1 text-base font-medium text-stone-700 hover:text-stone-900 bg-transparent border-0 cursor-pointer p-0 font-sans">
							<span aria-hidden="true">‹</span> <?php esc_html_e( 'Quay lại', 'bacera' ); ?>
						</button>
						<button type="button" id="bacera-chk-next-2" class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl bg-accent-500 px-8 py-3.5 text-base font-semibold text-white shadow-sm hover:bg-accent-600 transition-colors border-0 cursor-pointer">
							<?php esc_html_e( 'Tiếp tục', 'bacera' ); ?>
						</button>
					</div>
				</div>

				<!-- Bước 3 — phiếu xác nhận -->
				<div id="bacera-chk-step-3" class="hidden space-y-8">
					<p id="bacera-chk-pay-err" class="hidden m-0 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert"></p>
					<div id="bacera-chk-success-wrap" class="bacera-chk-success hidden space-y-0">
						<nav class="bacera-chk-success__crumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'bacera' ); ?>">
							<a id="bacera-chk-succ-crumb-home" href="<?php echo esc_url( $home_url ); ?>"><?php esc_html_e( 'Trang chủ', 'bacera' ); ?></a>
							<span aria-hidden="true"> / </span>
							<a id="bacera-chk-succ-crumb-cart" href="<?php echo esc_url( $cart_url ); ?>"><?php esc_html_e( 'Giỏ hàng', 'bacera' ); ?></a>
							<span aria-hidden="true"> / </span>
							<span><?php esc_html_e( 'Giao hàng', 'bacera' ); ?></span>
						</nav>
						<h1 class="bacera-chk-success__title"><?php esc_html_e( 'Cảm ơn bạn đã mua hàng!', 'bacera' ); ?></h1>
						<hr class="bacera-chk-success__rule" />
						<div class="bacera-chk-success__row-status">
							<p class="m-0 text-base font-semibold text-stone-900"><?php esc_html_e( 'Đơn hàng của bạn đã được đặt thành công.', 'bacera' ); ?></p>
							<span class="bacera-chk-success__tick" aria-hidden="true">
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
							</span>
						</div>
						<p class="m-0 mt-3 text-sm text-stone-500"><?php esc_html_e( 'Vui lòng kiểm tra email để nhận xác nhận đơn hàng.', 'bacera' ); ?></p>
						<ul class="bacera-chk-success__meta">
							<li>
								<span><?php esc_html_e( 'Mã đơn hàng:', 'bacera' ); ?></span>
								<strong id="bacera-chk-succ-order-id" class="text-stone-800 font-medium"></strong>
								<button type="button" id="bacera-chk-succ-copy" class="bacera-chk-success__copy" title="<?php esc_attr_e( 'Sao chép mã đơn', 'bacera' ); ?>" aria-label="<?php esc_attr_e( 'Sao chép mã đơn', 'bacera' ); ?>">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
								</button>
							</li>
							<li><span><?php esc_html_e( 'Ngày đặt hàng:', 'bacera' ); ?></span> <span id="bacera-chk-succ-placed"></span></li>
							<li id="bacera-chk-succ-email-line"></li>
						</ul>
						<hr class="bacera-chk-success__rule" />
						<section class="bacera-chk-success__ship">
							<h2><?php esc_html_e( 'Giao hàng', 'bacera' ); ?></h2>
							<p class="bacera-chk-success__ship-sub"><?php esc_html_e( 'Địa chỉ giao hàng & thời gian vận chuyển', 'bacera' ); ?></p>
							<p class="bacera-chk-success__subhd"><?php esc_html_e( 'Thông tin nhận hàng', 'bacera' ); ?></p>
							<div class="space-y-1 text-sm text-stone-700 leading-relaxed">
								<p class="m-0 font-medium text-stone-900" id="bacera-chk-succ-name"></p>
								<p class="m-0" id="bacera-chk-succ-address"></p>
								<p class="m-0" id="bacera-chk-succ-phone"></p>
							</div>
							<div class="mt-6 grid gap-6 sm:grid-cols-2">
								<div>
									<p class="bacera-chk-success__subhd"><?php esc_html_e( 'Thanh toán', 'bacera' ); ?></p>
									<p class="m-0 text-sm text-stone-600" id="bacera-chk-succ-pay"></p>
								</div>
								<div>
									<p class="bacera-chk-success__subhd"><?php esc_html_e( 'Ghi chú', 'bacera' ); ?></p>
									<p class="m-0 text-sm text-stone-600" id="bacera-chk-succ-note"></p>
								</div>
							</div>
							<div class="mt-6">
								<p class="bacera-chk-success__subhd"><?php esc_html_e( 'Giao hàng', 'bacera' ); ?></p>
								<p class="m-0 text-sm text-stone-700" id="bacera-chk-succ-ship-line"></p>
								<p class="m-0 mt-1 text-sm text-stone-600" id="bacera-chk-succ-ship-est"></p>
							</div>
						</section>
						<div class="bacera-chk-success__track-row">
							<a id="bacera-chk-succ-track" class="bacera-chk-success__track" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Theo dõi đơn hàng', 'bacera' ); ?></a>
						</div>
					</div>
					<div id="bacera-chk-bill-wrap" class="bacera-chk-bill-wrap">
					<div class="bacera-chk-bill">
						<div class="bacera-chk-bill__edge" aria-hidden="true"></div>
						<div class="bacera-chk-bill__inner">
							<header class="bacera-chk-bill__head">
								<p class="bacera-chk-bill__brand"><?php echo esc_html( $bacera_site_name ); ?></p>
								<h2 class="bacera-chk-bill__title"><?php esc_html_e( 'Phiếu xác nhận đơn hàng', 'bacera' ); ?></h2>
								<p class="bacera-chk-bill__subtitle"><?php esc_html_e( 'Kiểm tra kỹ thông tin trước khi hoàn tất thanh toán.', 'bacera' ); ?></p>
							</header>
							<div class="bacera-chk-bill__meta">
								<div>
									<span class="bacera-chk-bill__meta-k"><?php esc_html_e( 'Mã tham chiếu', 'bacera' ); ?></span>
									<span id="bacera-chk-bill-ref" class="bacera-chk-bill__meta-v"></span>
								</div>
								<div>
									<span class="bacera-chk-bill__meta-k"><?php esc_html_e( 'Thời gian', 'bacera' ); ?></span>
									<span id="bacera-chk-bill-time" class="bacera-chk-bill__meta-v"></span>
								</div>
							</div>
							<hr class="bacera-chk-bill__rule" />
							<section class="bacera-chk-bill__section">
								<h3 class="bacera-chk-bill__sec-title"><?php esc_html_e( 'Thông tin nhận hàng', 'bacera' ); ?></h3>
								<div class="bacera-chk-bill__row">
									<span class="bacera-chk-bill__row-k"><?php esc_html_e( 'Họ và tên', 'bacera' ); ?></span>
									<span id="bacera-chk-rev-name" class="bacera-chk-bill__row-v"></span>
								</div>
								<div class="bacera-chk-bill__row">
									<span class="bacera-chk-bill__row-k"><?php esc_html_e( 'Điện thoại', 'bacera' ); ?></span>
									<span id="bacera-chk-rev-phone" class="bacera-chk-bill__row-v"></span>
								</div>
								<div class="bacera-chk-bill__row">
									<span class="bacera-chk-bill__row-k"><?php esc_html_e( 'Email', 'bacera' ); ?></span>
									<span id="bacera-chk-rev-email" class="bacera-chk-bill__row-v"></span>
								</div>
								<div class="bacera-chk-bill__row">
									<span class="bacera-chk-bill__row-k"><?php esc_html_e( 'Địa chỉ giao hàng', 'bacera' ); ?></span>
									<span id="bacera-chk-rev-address" class="bacera-chk-bill__row-v bacera-chk-bill__row-v--multiline"></span>
								</div>
							</section>
							<section class="bacera-chk-bill__section">
								<h3 class="bacera-chk-bill__sec-title"><?php esc_html_e( 'Vận chuyển & thanh toán', 'bacera' ); ?></h3>
								<div class="bacera-chk-bill__row">
									<span class="bacera-chk-bill__row-k"><?php esc_html_e( 'Hình thức giao', 'bacera' ); ?></span>
									<span id="bacera-chk-rev-ship" class="bacera-chk-bill__row-v"></span>
								</div>
								<div class="bacera-chk-bill__row">
									<span class="bacera-chk-bill__row-k"><?php esc_html_e( 'Phương thức thanh toán', 'bacera' ); ?></span>
									<span id="bacera-chk-rev-pay" class="bacera-chk-bill__row-v"></span>
								</div>
							</section>
							<section class="bacera-chk-bill__section bacera-chk-bill__amounts">
								<h3 class="bacera-chk-bill__sec-title"><?php esc_html_e( 'Số tiền', 'bacera' ); ?></h3>
								<dl class="m-0 p-0">
									<div class="bacera-chk-bill__amount-row">
										<dt class="m-0"><?php esc_html_e( 'Tạm tính', 'bacera' ); ?></dt>
										<dd id="bacera-chk-bill-subtotal">0đ</dd>
									</div>
									<div class="bacera-chk-bill__amount-row">
										<dt class="m-0"><?php esc_html_e( 'Phí vận chuyển', 'bacera' ); ?></dt>
										<dd id="bacera-chk-bill-ship">0đ</dd>
									</div>
									<div class="bacera-chk-bill__amount-row">
										<dt class="m-0"><?php esc_html_e( 'Thuế VAT', 'bacera' ); ?></dt>
										<dd id="bacera-chk-bill-vat">0đ</dd>
									</div>
								</dl>
								<div class="bacera-chk-bill__amount-total">
									<p class="bacera-chk-bill__amount-total-label">
										<?php esc_html_e( 'Tổng thanh toán', 'bacera' ); ?>
										<span><?php esc_html_e( 'Đã gồm VAT (nếu có)', 'bacera' ); ?></span>
									</p>
									<p id="bacera-chk-bill-grand" class="bacera-chk-bill__amount-total-val">0đ</p>
								</div>
							</section>
							<footer class="bacera-chk-bill__foot">
								<?php esc_html_e( 'Phiếu xác nhận đặt hàng — không thay thế hóa đơn tài chính.', 'bacera' ); ?>
							</footer>
						</div>
					</div>
					</div>

					<div id="bacera-chk-step-3-actions" class="bacera-chk-bill-wrap flex w-full flex-col-reverse gap-4 pt-2">
						<button type="button" id="bacera-chk-back-3" class="inline-flex items-center gap-1 self-start text-base font-medium text-stone-700 hover:text-stone-900 bg-transparent border-0 cursor-pointer p-0 font-sans">
							<span aria-hidden="true">‹</span> <?php esc_html_e( 'Quay lại', 'bacera' ); ?>
						</button>
						<button type="button" id="bacera-chk-pay-btn" class="inline-flex w-full items-center justify-center rounded-xl bg-accent-500 px-8 py-3.5 text-base font-semibold text-white shadow-sm hover:bg-accent-600 transition-colors border-0 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed disabled:pointer-events-none">
							<?php esc_html_e( 'Thanh toán', 'bacera' ); ?>
						</button>
					</div>
				</div>
			</div>

			<aside id="bacera-chk-sidebar" class="min-w-0 xl:sticky xl:top-28">
				<div class="rounded-2xl border border-stone-200/90 bg-white p-5 md:p-6 shadow-md">
					<div class="mb-4 flex items-start justify-between gap-3">
						<h2 class="m-0 font-sans text-xl font-bold text-stone-900"><?php esc_html_e( 'Giỏ hàng', 'bacera' ); ?></h2>
						<a href="<?php echo esc_url( $cart_url ); ?>" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-stone-200 text-xl leading-none text-stone-500 hover:bg-stone-50 no-underline" aria-label="<?php esc_attr_e( 'Close and return to cart', 'bacera' ); ?>">×</a>
					</div>
					<div id="bacera-chk-lines"></div>
					<dl class="mt-4 space-y-2.5 text-sm text-stone-700 m-0 border-t border-stone-100 pt-4">
						<div class="flex justify-between gap-3">
							<dt class="m-0" id="bacera-chk-subtotal-label"><?php esc_html_e( 'Subtotal', 'bacera' ); ?> (0):</dt>
							<dd class="m-0 tabular-nums font-medium" id="bacera-chk-subtotal-val">0đ</dd>
						</div>
						<div class="flex justify-between gap-3">
							<dt class="m-0"><?php esc_html_e( 'Thuế VAT', 'bacera' ); ?></dt>
							<dd class="m-0 tabular-nums">0đ</dd>
						</div>
						<div class="flex justify-between gap-3">
							<dt class="m-0"><?php esc_html_e( 'Phí ship', 'bacera' ); ?></dt>
							<dd class="m-0 tabular-nums font-medium" id="bacera-chk-ship-val"><?php esc_html_e( 'Miễn phí', 'bacera' ); ?></dd>
						</div>
					</dl>
					<hr class="my-5 border-stone-200" />
					<div class="flex justify-between items-end gap-3">
						<div>
							<p class="m-0 font-sans text-lg font-bold text-stone-900"><?php esc_html_e( 'Tổng thanh toán', 'bacera' ); ?></p>
							<p class="m-0 mt-0.5 text-xs text-stone-500"><?php esc_html_e( 'Total (VAT included)', 'bacera' ); ?></p>
						</div>
						<p class="m-0 text-2xl font-semibold tabular-nums text-stone-900" id="bacera-chk-grand-total">0đ</p>
					</div>
					<label for="bacera-chk-sidebar-note" class="mt-6 mb-1.5 block text-sm font-medium text-stone-700"><?php esc_html_e( 'Ghi chú đơn hàng', 'bacera' ); ?></label>
					<textarea id="bacera-chk-sidebar-note" rows="3" placeholder="<?php esc_attr_e( 'Giao vào giờ hành chính', 'bacera' ); ?>" class="w-full rounded-xl border border-stone-200 bg-stone-50/60 px-3 py-2.5 text-sm text-stone-800 placeholder:text-stone-400 focus:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-200 resize-y"></textarea>
				</div>
			</aside>
		</div>
	</div>
</main>

<script>
(function () {
	var VN_API = <?php echo wp_json_encode( $bacera_vn_areas_base ); ?>;
	var STORAGE_ADDR = 'bacera_checkout_address';
	var STORAGE_CONTACT = 'bacera_checkout_contact';
	var STORAGE_STEP = 'bacera_checkout_step';
	var STORAGE_ITEMS = 'bacera_checkout_items';
	var STORAGE_NOTES = 'bacera_checkout_notes';
	var STORAGE_SHIP = 'bacera_checkout_shipping';
	var STORAGE_PAY = 'bacera_checkout_payment';
	var STORAGE_LAST_ORDER = 'bacera_last_order_success';
	var LOCAL_CART_KEY = 'bacera_shop_cart_v1';
	var PAID_ORDERS_KEY = 'bacera_cart_paid_orders_v1';

	var REST_ORDERS = <?php echo wp_json_encode( $bacera_rest_orders_url ); ?>;
	var REST_NONCE = <?php echo wp_json_encode( $bacera_pancake_order_nonce ); ?>;
	var WP_REST_NONCE = <?php echo wp_json_encode( $bacera_wp_rest_nonce ); ?>;
	var SHOP_URL = <?php echo wp_json_encode( $shop_url ); ?>;

	var txtChooseProvince = <?php echo wp_json_encode( __( 'Chọn tỉnh/thành phố', 'bacera' ) ); ?>;
	var txtChooseDistrict = <?php echo wp_json_encode( __( 'Chọn quận/huyện', 'bacera' ) ); ?>;
	var txtChooseWard = <?php echo wp_json_encode( __( 'Chọn phường/xã', 'bacera' ) ); ?>;
	var txtLoading = <?php echo wp_json_encode( __( 'Đang tải…', 'bacera' ) ); ?>;
	var txtPickProvinceFirst = <?php echo wp_json_encode( __( 'Chọn tỉnh/thành trước', 'bacera' ) ); ?>;
	var txtPickDistrictFirst = <?php echo wp_json_encode( __( 'Chọn quận/huyện trước', 'bacera' ) ); ?>;
	var txtPayProcessing = <?php echo wp_json_encode( __( 'Đang xử lý…', 'bacera' ) ); ?>;
	var txtPayDefault = <?php echo wp_json_encode( __( 'Thanh toán', 'bacera' ) ); ?>;
	var txtPayErrItems = <?php echo wp_json_encode( __( 'Không thể gửi đơn: thiếu mã biến thể Pancake trên một hoặc nhiều sản phẩm. Vui lòng thêm lại từ cửa hàng.', 'bacera' ) ); ?>;
	var txtPayErrNetwork = <?php echo wp_json_encode( __( 'Không kết nối được máy chủ. Vui lòng thử lại.', 'bacera' ) ); ?>;
	var txtEmailSentPrefix = <?php echo wp_json_encode( __( 'Chúng tôi đã gửi chi tiết đơn hàng tới:', 'bacera' ) ); ?>;
	var txtEstShip = <?php echo wp_json_encode( __( 'Dự kiến giao:', 'bacera' ) ); ?>;

	var currentStep = 1;
	var selProvince = document.getElementById('bacera-co-province');
	var selDistrict = document.getElementById('bacera-co-district');
	var selWard = document.getElementById('bacera-co-ward');
	var inpStreet = document.getElementById('bacera-co-address2');
	var inpFull = document.getElementById('bacera-co-full-address');
	var addrErr = document.getElementById('bacera-co-addr-err');

	var emptyEl = document.getElementById('bacera-chk-empty');
	var layoutEl = document.getElementById('bacera-chk-layout');
	var linesEl = document.getElementById('bacera-chk-lines');
	var subtotalLabel = document.getElementById('bacera-chk-subtotal-label');
	var subtotalVal = document.getElementById('bacera-chk-subtotal-val');
	var shipValEl = document.getElementById('bacera-chk-ship-val');
	var grandTotalEl = document.getElementById('bacera-chk-grand-total');
	var noteEl = document.getElementById('bacera-chk-sidebar-note');
	var payErrEl = document.getElementById('bacera-chk-pay-err');

	function hidePayErr() {
		if (!payErrEl) return;
		payErrEl.classList.add('hidden');
		payErrEl.textContent = '';
	}

	function showPayErr(msg) {
		if (!payErrEl) return;
		payErrEl.textContent = msg || txtPayErrNetwork;
		payErrEl.classList.remove('hidden');
	}

	function estimateShipDate(shipId) {
		var add = 4;
		if (shipId === 'fast') add = 2;
		else if (shipId === 'express') add = 1;
		else if (shipId === 'standard') add = 4;
		var d = new Date();
		d.setDate(d.getDate() + add);
		return d;
	}

	function formatViOrderWhen(d) {
		try {
			return d.toLocaleString('vi-VN', {
				dateStyle: 'long',
				timeStyle: 'short',
				timeZone: 'Asia/Ho_Chi_Minh'
			}) + ' (GMT+7)';
		} catch (e) {
			return d.toLocaleString('vi-VN');
		}
	}

	function formatViEstDelivery(d) {
		try {
			return d.toLocaleDateString('vi-VN', {
				weekday: 'long',
				day: 'numeric',
				month: 'short',
				year: 'numeric',
				timeZone: 'Asia/Ho_Chi_Minh'
			});
		} catch (e) {
			return d.toLocaleDateString('vi-VN');
		}
	}

	/** Xóa dòng vừa thanh toán khỏi giỏ localStorage và lưu lịch sử «Đã thanh toán» (đồng bộ trang Cart). */
	function baceraSyncCartAfterPayment(payload, lineItems, orderTotal) {
		if (!lineItems || !lineItems.length) {
			return;
		}
		try {
			var raw = localStorage.getItem(LOCAL_CART_KEY);
			var cart = raw ? JSON.parse(raw) : [];
			if (!Array.isArray(cart)) {
				cart = [];
			}
			var removeIds = lineItems.map(function (it) {
				return String(it.id);
			});
			var next = cart.filter(function (row) {
				return removeIds.indexOf(String(row.id)) < 0;
			});
			localStorage.setItem(LOCAL_CART_KEY, JSON.stringify(next));
		} catch (e1) {}
		try {
			var snap = lineItems.map(function (it) {
				return {
					id: it.id,
					name: it.name,
					brand: it.brand,
					image: it.image,
					qty: Number(it.qty || 1),
					price: Number(it.price || 0),
					variation_id: it.variation_id
				};
			});
			var histRaw = localStorage.getItem(PAID_ORDERS_KEY);
			var hist = histRaw ? JSON.parse(histRaw) : [];
			if (!Array.isArray(hist)) {
				hist = [];
			}
			hist.unshift({
				pancake_order_id: payload.pancake_order_id,
				placed_at_display: payload.placed_at_display,
				placed_at_iso: payload.placed_at_iso,
				order_total: typeof orderTotal === 'number' ? orderTotal : 0,
				items: snap
			});
			if (hist.length > 25) {
				hist = hist.slice(0, 25);
			}
			localStorage.setItem(PAID_ORDERS_KEY, JSON.stringify(hist));
		} catch (e2) {}
	}

	function buildPancakePayload() {
		var name = document.getElementById('bacera-co-name');
		var phone = document.getElementById('bacera-co-phone');
		var email = document.getElementById('bacera-co-email');
		var full = document.getElementById('bacera-co-full-address');
		var items = loadItems();
		var lines = [];
		for (var i = 0; i < items.length; i++) {
			var it = items[i];
			var vid = String(it.variation_id || '').trim();
			var qty = Number(it.qty || 1);
			if (!vid || qty <= 0) continue;
			lines.push({
				variation_id: vid,
				quantity: qty,
				price: Number(it.price || 0),
				name: String(it.name || '').trim()
			});
		}
		var subtotal = items.reduce(function (s, item) { return s + lineTotal(item); }, 0);
		var fee = getShipFee();
		var grand = subtotal + fee;
		var shipEl = document.querySelector('input[name="bacera_ship_method"]:checked');
		var payEl = document.querySelector('input[name="bacera_payment_method"]:checked');
		var shipLabel = shipEl && shipEl.closest('label') ? shipEl.closest('label').innerText.replace(/\s+/g, ' ').trim() : '';
		var payLabel = payEl && payEl.closest('label') ? payEl.closest('label').innerText.replace(/\s+/g, ' ').trim() : '';
		var noteParts = [];
		if (noteEl && noteEl.value.trim()) noteParts.push(noteEl.value.trim());
		noteParts.push('Giao hàng: ' + shipLabel);
		noteParts.push('Thanh toán: ' + payLabel);
		return {
			payload: {
				bill_full_name: name ? name.value.trim() : '',
				bill_phone_number: phone ? phone.value.trim() : '',
				bill_email: email ? email.value.trim() : '',
				shipping_address: { full_address: full ? full.value.trim() : '' },
				note: noteParts.join('\n'),
				items: lines,
				total_amount: grand,
				discount: 0
			},
			meta: {
				ship_label: shipLabel,
				pay_label: payLabel,
				ship_id: shipEl ? shipEl.value : '',
				ship_fee: fee,
				grand: grand,
				note_customer: noteEl ? noteEl.value.trim() : ''
			}
		};
	}

	function showInlineThankYou(data) {
		var wrap = document.getElementById('bacera-chk-success-wrap');
		var bill = document.getElementById('bacera-chk-bill-wrap');
		var act = document.getElementById('bacera-chk-step-3-actions');
		var aside = document.getElementById('bacera-chk-sidebar');
		var oid = document.getElementById('bacera-chk-succ-order-id');
		var placed = document.getElementById('bacera-chk-succ-placed');
		var eml = document.getElementById('bacera-chk-succ-email-line');
		var nm = document.getElementById('bacera-chk-succ-name');
		var addr = document.getElementById('bacera-chk-succ-address');
		var ph = document.getElementById('bacera-chk-succ-phone');
		var pay = document.getElementById('bacera-chk-succ-pay');
		var nt = document.getElementById('bacera-chk-succ-note');
		var sl = document.getElementById('bacera-chk-succ-ship-line');
		var se = document.getElementById('bacera-chk-succ-ship-est');
		if (oid) oid.textContent = data.pancake_order_id || '';
		if (placed) placed.textContent = data.placed_at_display || '';
		if (eml) eml.textContent = txtEmailSentPrefix + ' ' + (data.email || '');
		if (nm) nm.textContent = data.name || '—';
		if (addr) addr.textContent = data.address || '—';
		if (ph) ph.textContent = data.phone || '—';
		if (pay) pay.textContent = data.payment_label || '—';
		if (nt) nt.textContent = (data.note && String(data.note).trim()) ? data.note : '—';
		if (sl) sl.textContent = (data.shipping_label || '') + (data.free_shipping_line ? ' — ' + data.free_shipping_line : '');
		if (se) se.textContent = txtEstShip + ' ' + (data.estimated_delivery || '');
		var copyBtn = document.getElementById('bacera-chk-succ-copy');
		if (copyBtn) {
			copyBtn.onclick = function () {
				var id = data.pancake_order_id || '';
				if (id && navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(id).catch(function () {});
				}
			};
		}
		var track = document.getElementById('bacera-chk-succ-track');
		if (track) track.href = SHOP_URL;
		if (wrap) wrap.classList.remove('hidden');
		if (bill) bill.classList.add('hidden');
		if (act) act.classList.add('hidden');
		if (aside) aside.classList.add('hidden');
		hidePayErr();
	}

	function fetchAreas(path) {
		return fetch(VN_API + path, { credentials: 'omit' })
			.then(function (r) {
				if (!r.ok) throw new Error('http');
				return r.json();
			})
			.then(function (j) {
				if (j && j.status === 'Success' && Array.isArray(j.results)) return j.results;
				throw new Error('data');
			});
	}

	function formatFullAddress() {
		function optLabel(sel) {
			if (!sel || !sel.value) return '';
			var o = sel.options[sel.selectedIndex];
			return o ? String(o.textContent || '').trim() : '';
		}
		var parts = [];
		var st = inpStreet ? inpStreet.value.trim() : '';
		if (st) parts.push(st);
		var w = optLabel(selWard);
		if (w) parts.push(w);
		var d = optLabel(selDistrict);
		if (d) parts.push(d);
		var p = optLabel(selProvince);
		if (p) parts.push(p);
		return parts.join(', ');
	}

	function persistAddress() {
		var obj = {
			province_code: selProvince ? selProvince.value : '',
			district_code: selDistrict ? selDistrict.value : '',
			ward_code: selWard ? selWard.value : '',
			street: inpStreet ? inpStreet.value.trim() : ''
		};
		try { sessionStorage.setItem(STORAGE_ADDR, JSON.stringify(obj)); } catch (e) {}
		if (inpFull) inpFull.value = formatFullAddress();
	}

	function loadDistricts(provinceCode) {
		if (!selDistrict) return Promise.resolve();
		if (!provinceCode) {
			selDistrict.innerHTML = '<option value="">' + txtPickProvinceFirst + '</option>';
			selDistrict.disabled = true;
			if (selWard) {
				selWard.innerHTML = '<option value="">' + txtPickDistrictFirst + '</option>';
				selWard.disabled = true;
			}
			return Promise.resolve();
		}
		selDistrict.disabled = true;
		selDistrict.innerHTML = '<option value="">' + txtLoading + '</option>';
		if (selWard) {
			selWard.innerHTML = '<option value="">' + txtPickDistrictFirst + '</option>';
			selWard.disabled = true;
		}
		return fetchAreas('/district?province=' + encodeURIComponent(provinceCode)).then(function (list) {
			selDistrict.innerHTML = '<option value="">' + txtChooseDistrict + '</option>';
			list.forEach(function (row) {
				var opt = document.createElement('option');
				opt.value = row.code;
				opt.textContent = row.name;
				selDistrict.appendChild(opt);
			});
			selDistrict.disabled = false;
		});
	}

	function loadCommunes(districtCode) {
		if (!selWard) return Promise.resolve();
		if (!districtCode) {
			selWard.innerHTML = '<option value="">' + txtPickDistrictFirst + '</option>';
			selWard.disabled = true;
			return Promise.resolve();
		}
		selWard.disabled = true;
		selWard.innerHTML = '<option value="">' + txtLoading + '</option>';
		return fetchAreas('/commune?district=' + encodeURIComponent(districtCode)).then(function (list) {
			selWard.innerHTML = '<option value="">' + txtChooseWard + '</option>';
			list.forEach(function (row) {
				var opt = document.createElement('option');
				opt.value = row.code;
				opt.textContent = row.name;
				selWard.appendChild(opt);
			});
			selWard.disabled = false;
		});
	}

	function restoreAddressChain() {
		var saved = null;
		try { saved = JSON.parse(sessionStorage.getItem(STORAGE_ADDR) || 'null'); } catch (e) { saved = null; }
		if (!saved || !saved.province_code || !selProvince) {
			persistAddress();
			return Promise.resolve();
		}
		selProvince.value = saved.province_code;
		return loadDistricts(saved.province_code).then(function () {
			if (saved.district_code && selDistrict) selDistrict.value = saved.district_code;
			return loadCommunes(saved.district_code || '');
		}).then(function () {
			if (saved.ward_code && selWard) selWard.value = saved.ward_code;
			if (saved.street && inpStreet) inpStreet.value = saved.street;
			persistAddress();
		});
	}

	function initVnAddress() {
		if (!selProvince) return;
		selProvince.innerHTML = '<option value="">' + txtLoading + '</option>';
		fetchAreas('/province')
			.then(function (list) {
				selProvince.innerHTML = '<option value="">' + txtChooseProvince + '</option>';
				list.forEach(function (row) {
					var opt = document.createElement('option');
					opt.value = row.code;
					opt.textContent = row.name;
					selProvince.appendChild(opt);
				});
				selProvince.disabled = false;
				if (addrErr) { addrErr.classList.add('hidden'); addrErr.textContent = ''; }
				return restoreAddressChain();
			})
			.catch(function () {
				selProvince.innerHTML = '<option value="">' + txtChooseProvince + '</option>';
				selProvince.disabled = false;
				if (addrErr) {
					addrErr.textContent = '<?php echo esc_js( __( 'Không tải được danh sách địa chỉ. Vui lòng thử lại sau.', 'bacera' ) ); ?>';
					addrErr.classList.remove('hidden');
				}
			});

		selProvince.addEventListener('change', function () {
			loadDistricts(selProvince.value).then(function () { return loadCommunes(''); }).then(persistAddress);
		});
		if (selDistrict) selDistrict.addEventListener('change', function () {
			loadCommunes(selDistrict.value).then(persistAddress);
		});
		if (selWard) selWard.addEventListener('change', persistAddress);
		if (inpStreet) inpStreet.addEventListener('input', persistAddress);
	}

	function loadItems() {
		try {
			var raw = sessionStorage.getItem(STORAGE_ITEMS);
			if (!raw) return [];
			var parsed = JSON.parse(raw);
			return Array.isArray(parsed) ? parsed : [];
		} catch (e) { return []; }
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
		var c = meta.color, s = meta.size;
		if (c && s) return c + ' | ' + s;
		if (c) return c;
		if (s) return s;
		return String(item.variant_label || '').trim();
	}

	function lineTotal(item) {
		return Number(item.price || 0) * Number(item.qty || 1);
	}

	function getShipFee() {
		var checked = document.querySelector('input[name="bacera_ship_method"]:checked');
		if (!checked) return 0;
		return Number(checked.getAttribute('data-fee') || 0);
	}

	function persistShipPay() {
		var shipEl = document.querySelector('input[name="bacera_ship_method"]:checked');
		var payEl = document.querySelector('input[name="bacera_payment_method"]:checked');
		try {
			if (shipEl) {
				sessionStorage.setItem(STORAGE_SHIP, JSON.stringify({
					id: shipEl.value,
					fee: Number(shipEl.getAttribute('data-fee') || 0),
					label: shipEl.closest('label') ? shipEl.closest('label').innerText.replace(/\s+/g, ' ').trim() : ''
				}));
			}
			if (payEl) {
				sessionStorage.setItem(STORAGE_PAY, JSON.stringify({
					id: payEl.value,
					label: payEl.closest('label') ? payEl.closest('label').innerText.replace(/\s+/g, ' ').trim() : ''
				}));
			}
		} catch (e) {}
	}

	function restoreShipPay() {
		try {
			var s = JSON.parse(sessionStorage.getItem(STORAGE_SHIP) || 'null');
			if (s && s.id) {
				var ri = document.querySelector('input[name="bacera_ship_method"][value="' + s.id + '"]');
				if (ri) ri.checked = true;
			}
			var p = JSON.parse(sessionStorage.getItem(STORAGE_PAY) || 'null');
			if (p && p.id) {
				var rj = document.querySelector('input[name="bacera_payment_method"][value="' + p.id + '"]');
				if (rj) rj.checked = true;
			}
		} catch (e) {}
	}

	function persistContact() {
		var name = document.getElementById('bacera-co-name');
		var phone = document.getElementById('bacera-co-phone');
		var email = document.getElementById('bacera-co-email');
		try {
			sessionStorage.setItem(STORAGE_CONTACT, JSON.stringify({
				name: name ? name.value.trim() : '',
				phone: phone ? phone.value.trim() : '',
				email: email ? email.value.trim() : ''
			}));
		} catch (e) {}
	}

	function restoreContact() {
		try {
			var c = JSON.parse(sessionStorage.getItem(STORAGE_CONTACT) || 'null');
			if (!c) return;
			var name = document.getElementById('bacera-co-name');
			var phone = document.getElementById('bacera-co-phone');
			var email = document.getElementById('bacera-co-email');
			if (name && c.name) name.value = c.name;
			if (phone && c.phone) phone.value = c.phone;
			if (email && c.email) email.value = c.email;
		} catch (e) {}
	}

	function renderLinesAndTotals() {
		var items = loadItems();
		if (!emptyEl || !layoutEl || !linesEl || !grandTotalEl) return;

		if (!items.length) {
			emptyEl.classList.remove('hidden');
			layoutEl.classList.add('hidden');
			return;
		}
		emptyEl.classList.add('hidden');
		layoutEl.classList.remove('hidden');

		var count = items.reduce(function (a, i) { return a + Number(i.qty || 1); }, 0);
		var subtotal = items.reduce(function (sum, item) { return sum + lineTotal(item); }, 0);
		var fee = (currentStep >= 2) ? getShipFee() : 0;
		var grand = subtotal + fee;

		if (subtotalLabel) subtotalLabel.textContent = '<?php echo esc_js( __( 'Subtotal', 'bacera' ) ); ?> (' + count + '):';
		if (subtotalVal) subtotalVal.textContent = toCurrency(subtotal);
		if (shipValEl) {
			if (currentStep < 2) {
				shipValEl.textContent = '—';
			} else {
				shipValEl.textContent = fee <= 0 ? '<?php echo esc_js( __( 'Miễn phí', 'bacera' ) ); ?>' : toCurrency(fee);
			}
		}
		grandTotalEl.textContent = toCurrency(grand);

		linesEl.innerHTML = items.map(function (item) {
			var title = escapeHtml(item.name || '');
			var brand = escapeHtml(item.brand || '<?php echo esc_js( __( 'Bacera', 'bacera' ) ); ?>');
			var meta = parseVariantDetails(item);
			var attr = formatAttrLine(meta, item);
			var attrHtml = attr ? '<p class="m-0 mt-1 text-sm text-stone-600">' + escapeHtml(attr) + '</p>' : '';
			var img = escapeHtml(item.image || 'https://placehold.co/120x150/f0ece3/8d6a54?text=Product');
			var orig = Number(item.original_price || 0);
			var price = Number(item.price || 0);
			var oldHtml = orig > price ? '<p class="m-0 text-xs text-stone-400 line-through tabular-nums">' + toCurrency(orig) + '</p>' : '';
			var disc = '';
			if (orig > price && orig > 0) {
				var pct = Math.round((orig - price) / orig * 100);
				disc = '<span class="absolute left-1 top-1 rounded bg-stone-800/90 px-1.5 py-0.5 text-[10px] font-semibold text-white z-10">-' + pct + '%</span>';
			}
			return ''
				+ '<div class="bacera-co-line-item">'
				+ '  <div class="relative aspect-square w-full overflow-hidden rounded-lg bg-stone-100">'
				+ disc
				+ '    <img src="' + img + '" alt="" class="h-full w-full object-cover" loading="lazy" />'
				+ '  </div>'
				+ '  <div class="min-w-0 flex flex-col">'
				+ '    <p class="m-0 text-[10px] font-medium uppercase tracking-wider text-stone-500">' + brand + '</p>'
				+ '    <p class="m-0 mt-0.5 text-sm font-medium leading-snug text-stone-900">' + title + '</p>'
				+ attrHtml
				+ '    <div class="mt-auto flex flex-wrap items-end justify-between gap-2 pt-3">'
				+ '      <div class="bacera-co-qty text-stone-600">'
				+ '        <span class="px-2 py-1.5 text-stone-400">—</span>'
				+ '        <span class="px-1 tabular-nums">' + String(Number(item.qty || 1)).padStart(2, '0') + '</span>'
				+ '        <span class="px-2 py-1.5 text-stone-400">+</span>'
				+ '      </div>'
				+ '      <div class="text-right">'
				+ oldHtml
				+ '        <p class="m-0 text-base font-semibold tabular-nums text-stone-900">' + toCurrency(price) + '</p>'
				+ '      </div>'
				+ '    </div>'
				+ '  </div>'
				+ '</div>';
		}).join('');
	}

	function setStepIndicators(n) {
		document.querySelectorAll('[data-step-indicator]').forEach(function (span) {
			var sn = parseInt(span.getAttribute('data-step-indicator'), 10);
			var on = sn === n;
			span.classList.toggle('font-semibold', on);
			span.classList.toggle('text-stone-900', on);
			span.classList.toggle('text-stone-600', !on);
		});
	}

	function fillReview() {
		var name = document.getElementById('bacera-co-name');
		var phone = document.getElementById('bacera-co-phone');
		var email = document.getElementById('bacera-co-email');
		var full = document.getElementById('bacera-co-full-address');
		var refEl = document.getElementById('bacera-chk-bill-ref');
		var timeEl = document.getElementById('bacera-chk-bill-time');
		var elN = document.getElementById('bacera-chk-rev-name');
		var elPh = document.getElementById('bacera-chk-rev-phone');
		var elE = document.getElementById('bacera-chk-rev-email');
		var elA = document.getElementById('bacera-chk-rev-address');
		var elS = document.getElementById('bacera-chk-rev-ship');
		var elP = document.getElementById('bacera-chk-rev-pay');
		var shipEl = document.querySelector('input[name="bacera_ship_method"]:checked');
		var payEl = document.querySelector('input[name="bacera_payment_method"]:checked');
		if (refEl) {
			refEl.textContent = 'DH-' + String(Date.now()).slice(-10);
		}
		if (timeEl) {
			timeEl.textContent = new Date().toLocaleString('vi-VN', { dateStyle: 'medium', timeStyle: 'short' });
		}
		if (elN) elN.textContent = name && name.value.trim() ? name.value.trim() : '—';
		if (elPh) elPh.textContent = phone && phone.value.trim() ? phone.value.trim() : '—';
		if (elE) elE.textContent = email && email.value.trim() ? email.value.trim() : '—';
		if (elA) elA.textContent = full && full.value ? full.value : '—';
		if (elS) elS.textContent = shipEl && shipEl.closest('label') ? shipEl.closest('label').innerText.replace(/\s+/g, ' ').trim() : '—';
		if (elP) elP.textContent = payEl && payEl.closest('label') ? payEl.closest('label').innerText.replace(/\s+/g, ' ').trim() : '—';

		var items = loadItems();
		var subtotal = items.reduce(function (sum, item) { return sum + lineTotal(item); }, 0);
		var fee = getShipFee();
		var grand = subtotal + fee;
		var subBill = document.getElementById('bacera-chk-bill-subtotal');
		var shipBill = document.getElementById('bacera-chk-bill-ship');
		var vatBill = document.getElementById('bacera-chk-bill-vat');
		var grandBill = document.getElementById('bacera-chk-bill-grand');
		if (subBill) subBill.textContent = toCurrency(subtotal);
		if (shipBill) shipBill.textContent = fee <= 0 ? '<?php echo esc_js( __( 'Miễn phí', 'bacera' ) ); ?>' : toCurrency(fee);
		if (vatBill) vatBill.textContent = toCurrency(0);
		if (grandBill) grandBill.textContent = toCurrency(grand);
	}

	function setStep(n) {
		currentStep = n;
		var s1 = document.getElementById('bacera-chk-step-1');
		var s2 = document.getElementById('bacera-chk-step-2');
		var s3 = document.getElementById('bacera-chk-step-3');
		var layoutChk = document.getElementById('bacera-chk-layout');
		var sidebar = document.getElementById('bacera-chk-sidebar');
		if (s1) s1.classList.toggle('hidden', n !== 1);
		if (s2) s2.classList.toggle('hidden', n !== 2);
		if (s3) s3.classList.toggle('hidden', n !== 3);
		if (layoutChk) layoutChk.classList.toggle('bacera-chk-layout--step3', n === 3);
		if (sidebar) sidebar.classList.toggle('hidden', n === 3);
		setStepIndicators(n);
		try { sessionStorage.setItem(STORAGE_STEP, String(n)); } catch (e) {}
		if (n === 3) fillReview();
		renderLinesAndTotals();
	}

	function validateStep1() {
		var form = document.getElementById('bacera-chk-form-1');
		if (form && typeof form.reportValidity === 'function' && !form.reportValidity()) return false;
		persistAddress();
		persistContact();
		return true;
	}

	initVnAddress();
	restoreContact();
	restoreShipPay();

	document.querySelectorAll('input[name="bacera_ship_method"]').forEach(function (el) {
		el.addEventListener('change', function () { persistShipPay(); renderLinesAndTotals(); });
	});
	document.querySelectorAll('input[name="bacera_payment_method"]').forEach(function (el) {
		el.addEventListener('change', persistShipPay);
	});

	var next1 = document.getElementById('bacera-chk-next-1');
	if (next1) next1.addEventListener('click', function () {
		if (!validateStep1()) return;
		setStep(2);
	});

	var back2 = document.getElementById('bacera-chk-back-2');
	if (back2) back2.addEventListener('click', function () { setStep(1); });

	var next2 = document.getElementById('bacera-chk-next-2');
	if (next2) next2.addEventListener('click', function () {
		persistShipPay();
		setStep(3);
	});

	var back3 = document.getElementById('bacera-chk-back-3');
	if (back3) back3.addEventListener('click', function () { setStep(2); });

	var payBtn = document.getElementById('bacera-chk-pay-btn');
	if (payBtn) {
		payBtn.addEventListener('click', function () {
			hidePayErr();
			persistShipPay();
			persistContact();
			persistAddress();
			try { sessionStorage.setItem(STORAGE_NOTES, noteEl ? noteEl.value : ''); } catch (e) {}

			var built = buildPancakePayload();
			if (!built.payload.items.length) {
				showPayErr(txtPayErrItems);
				return;
			}

			var shipEl = document.querySelector('input[name="bacera_ship_method"]:checked');
			var shipId = shipEl ? shipEl.value : '';
			var est = estimateShipDate(shipId);
			var placed = new Date();
			var name = document.getElementById('bacera-co-name');
			var phone = document.getElementById('bacera-co-phone');
			var email = document.getElementById('bacera-co-email');
			var full = document.getElementById('bacera-co-full-address');

			payBtn.disabled = true;
			payBtn.textContent = txtPayProcessing;

			fetch(REST_ORDERS, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': WP_REST_NONCE,
					'X-Bacera-Nonce': REST_NONCE
				},
				body: JSON.stringify(Object.assign({}, built.payload, { bacera_nonce: REST_NONCE }))
			})
				.then(function (r) {
					return r.text().then(function (t) {
						var j = {};
						if (t) {
							try {
								j = JSON.parse(t);
							} catch (e) {
								j = { message: t };
							}
						}
						return { ok: r.ok, status: r.status, body: j };
					});
				})
				.then(function (res) {
					if (res.ok && res.body && res.body.success && res.body.pancake_order_id) {
						var fee = built.meta.ship_fee;
						var successPayload = {
							pancake_order_id: String(res.body.pancake_order_id),
							placed_at_iso: placed.toISOString(),
							placed_at_display: formatViOrderWhen(placed),
							email: email ? email.value.trim() : '',
							name: name ? name.value.trim() : '',
							phone: phone ? phone.value.trim() : '',
							address: full ? full.value.trim() : '',
							payment_label: built.meta.pay_label,
							note: built.meta.note_customer,
							shipping_label: built.meta.ship_label,
							ship_fee: fee,
							free_shipping_line: fee <= 0 ? '<?php echo esc_js( __( 'Miễn phí vận chuyển', 'bacera' ) ); ?>' : '<?php echo esc_js( __( 'Phí vận chuyển', 'bacera' ) ); ?>: ' + toCurrency(fee),
							estimated_delivery: formatViEstDelivery(est),
							order_total: built.meta.grand
						};
						var paidLines = loadItems();
						baceraSyncCartAfterPayment(successPayload, paidLines, built.meta.grand);
						try {
							sessionStorage.setItem(STORAGE_LAST_ORDER, JSON.stringify(successPayload));
							sessionStorage.removeItem(STORAGE_ITEMS);
							sessionStorage.removeItem(STORAGE_STEP);
							sessionStorage.removeItem(STORAGE_NOTES);
						} catch (e2) {}
						showInlineThankYou(successPayload);
						payBtn.disabled = false;
						payBtn.textContent = txtPayDefault;
						payBtn.classList.add('hidden');
						return;
					}
					var msg = (res.body && res.body.message) ? String(res.body.message) : txtPayErrNetwork;
					showPayErr(msg);
					payBtn.disabled = false;
					payBtn.textContent = txtPayDefault;
				})
				.catch(function () {
					showPayErr(txtPayErrNetwork);
					payBtn.disabled = false;
					payBtn.textContent = txtPayDefault;
				});
		});
	}

	if (noteEl) {
		noteEl.addEventListener('input', function () {
			try { sessionStorage.setItem(STORAGE_NOTES, noteEl.value); } catch (e) {}
		});
		try { noteEl.value = sessionStorage.getItem(STORAGE_NOTES) || ''; } catch (e) {}
	}

	try {
		var rs = parseInt(sessionStorage.getItem(STORAGE_STEP) || '1', 10);
		if (rs >= 1 && rs <= 3) {
			if (rs > 1 && loadItems().length) setStep(rs);
			else renderLinesAndTotals();
		} else {
			renderLinesAndTotals();
		}
	} catch (e) {
		renderLinesAndTotals();
	}

	if (!loadItems().length) {
		setStep(1);
	}
})();
</script>

<?php
get_footer();
