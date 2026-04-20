<?php
global $wpdb;

$bacera_customer = null;
$bacera_auth_cookie = $_COOKIE['bacera_customer_auth'] ?? '';
$bacera_cust_table  = $wpdb->prefix . 'bacera_customers';
$is_logged_in = false;

if ( $bacera_auth_cookie ) {
    $decoded = base64_decode( $bacera_auth_cookie, true );
    if ( $decoded && strpos( $decoded, '|' ) !== false ) {
        $customer_id = (int) explode( '|', $decoded )[0];
        if ( $customer_id > 0 ) {
            $is_logged_in = true;
            if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $bacera_cust_table ) ) === $bacera_cust_table ) {
                $bacera_customer = $wpdb->get_row( $wpdb->prepare("SELECT id, name, phone, email FROM `{$bacera_cust_table}` WHERE id = %d LIMIT 1", $customer_id), ARRAY_A);
            }
        }
    }
}

$cust_name   = $bacera_customer ? ( $bacera_customer['name'] ?: $bacera_customer['phone'] ?: $bacera_customer['email'] ) : '';
$avatar_url  = "https://ui-avatars.com/api/?name=" . rawurlencode($cust_name ?: 'KH') . "&background=3d2f26&color=E8C5B0&bold=true&size=200";

$bacera_cart_url     = class_exists('Bacera_Utils') ? Bacera_Utils::get_cart_page_url() : home_url('/cart/');
$bacera_shop_url     = class_exists('Bacera_Utils') ? Bacera_Utils::get_shop_page_url() : home_url('/shop/');
$bacera_workshop_url = get_post_type_archive_link('workshop') ?: home_url('/workshop/');

$current_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$is_home    = is_front_page();
$is_shop    = !$is_home && strpos($current_path, '/shop') !== false;
$is_cart    = strpos($current_path, '/cart') !== false;
$is_explore = false; // Explore is always a bottom sheet, never a page

// Active state classes helper
$act = 'text-terracotta';
$def = 'text-textmuted';
?>

<style>
#bacera-mobile-dock {
    /* handles home indicator on iOS */
    padding-bottom: calc(10px + env(safe-area-inset-bottom, 16px));
}
.dock-btn {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    gap: 4px;
    padding: 10px 4px 0;
    background: transparent;
    border: none;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    text-decoration: none;
}
.dock-btn svg {
    display: block;
    transition: transform .15s ease;
}
.dock-btn:active svg { transform: scale(.88); }
.dock-btn span {
    font-size: 10px;
    line-height: 1.2;
    font-family: inherit;
    white-space: nowrap;
}
.dock-active { color: #d95f47; }
.dock-inactive { color: #9b7e6d; }
.dock-inactive:hover { color: #7a5c48; }
</style>

<div id="bacera-mobile-dock" 
     class="lg:hidden fixed bottom-0 left-0 right-0 z-[100] bg-white/95 border-t border-neutral-200/80 shadow-[0_-2px_20px_rgba(0,0,0,.07)] backdrop-blur-md pt-1 flex">

    <!-- Home -->
    <a href="<?php echo esc_url(home_url('/')); ?>" 
       class="dock-btn <?php echo $is_home ? 'dock-active' : 'dock-inactive'; ?>"
       id="dock-home">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            <polyline stroke-linecap="round" stroke-linejoin="round" points="9 22 9 12 15 12 15 22"/>
        </svg>
        <span>Trang chủ</span>
    </a>

    <!-- Shop -->
    <a href="<?php echo esc_url($bacera_shop_url); ?>"
       class="dock-btn <?php echo $is_shop ? 'dock-active' : 'dock-inactive'; ?>"
       id="dock-shop">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 10a4 4 0 0 1-8 0"/>
        </svg>
        <span>Cửa hàng</span>
    </a>

    <!-- Explore (compass) -->
    <button onclick="openHub('mobile-explore-hub')" 
            class="dock-btn dock-inactive"
            id="dock-explore"
            aria-label="Khám phá">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"/>
            <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/>
        </svg>
        <span>Khám phá</span>
    </button>

    <!-- Cart -->
    <a href="<?php echo esc_url($bacera_cart_url); ?>"
       class="dock-btn <?php echo $is_cart ? 'dock-active' : 'dock-inactive'; ?> relative"
       id="dock-cart">
        <div class="relative">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
            </svg>
            <span class="absolute -top-1 -right-2.5 min-w-[16px] h-4 px-0.5 rounded-full bg-terracotta flex items-center justify-center text-white text-[9px] font-bold leading-none">1</span>
        </div>
        <span>Giỏ hàng</span>
    </a>

    <!-- Account -->
    <button onclick="openHub('mobile-account-hub')" 
            class="dock-btn <?php echo $is_logged_in ? 'dock-active' : 'dock-inactive'; ?>"
            id="dock-account"
            aria-label="Tài khoản">
        <div class="relative">
            <?php if ($is_logged_in): ?>
            <div class="w-6 h-6 rounded-full overflow-hidden border-[1.5px] border-terracotta">
                <img src="<?php echo esc_url($avatar_url); ?>" class="w-full h-full object-cover" alt="Avatar" loading="lazy">
            </div>
            <?php else: ?>
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <circle cx="12" cy="8" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 20c0-4 3.58-7 8-7s8 3 8 7"/>
            </svg>
            <?php endif; ?>
        </div>
        <span><?php echo $is_logged_in ? esc_html(mb_substr($cust_name, 0, 6, 'UTF-8') . (mb_strlen($cust_name, 'UTF-8') > 6 ? '…' : '')) : 'Của tôi'; ?></span>
    </button>

</div>

<?php
get_template_part('app/Views/components/account-hub');
?>
