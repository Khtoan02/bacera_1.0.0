<?php
/**
 * Template Name: Pancake API Tester
 * Description: Trang công cụ nội bộ để test kết nối và toàn bộ endpoints của Pancake POS API.
 */

// BẢO MẬT: Chỉ cho phép Admin truy cập
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Bạn không có quyền truy cập trang này.', 'Truy cập bị từ chối', array( 'response' => 403 ) );
}

get_header();

$api_response = null;
$action_tested = '';

// Xử lý khi người dùng bấm nút Test
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['pancake_test_nonce'] ) && wp_verify_nonce( $_POST['pancake_test_nonce'], 'test_api_action' ) ) {
    
    if ( class_exists( 'Pancake_API_Client' ) ) {
        $api = new Pancake_API_Client();
        $action_tested = sanitize_text_field( $_POST['api_action'] );

        switch ( $action_tested ) {
            // ================= SETUP & CATEGORIES =================
            case 'test_connection':
                $api_response = $api->request( '/shops/{SHOP_ID}/warehouses', 'GET' ); 
                break;

            case 'get_categories':
                $api_response = $api->request( '/shops/{SHOP_ID}/categories', 'GET' ); 
                break;

            // ================= PRODUCTS & INVENTORY =================
            case 'get_products':
                $api_response = $api->request( '/shops/{SHOP_ID}/products/variations?page_size=6', 'GET' ); 
                
                // ĐỒNG BỘ: Lưu dữ liệu vào WordPress Database để fix lỗi link ảnh gốc
                if ( isset( $api_response['success'] ) && $api_response['success'] && ! empty( $api_response['data'] ) ) {
                    foreach ( $api_response['data'] as $item ) {
                        Bacera_Utils::upsert_external_product( $item );
                    }
                }
                break;

            case 'create_mock_product':
                $mock_product = [
                    'product' => [
                        'name' => 'Gốm Bacera Test ' . wp_rand( 100, 999 ),
                        'weight' => 1,
                        'is_published' => false,
                        'variations' => [
                            [
                                'retail_price' => 550000,
                                'price_at_counter' => 450000,
                                'barcode' => 'TEST-' . wp_rand( 1000, 9999 )
                            ]
                        ]
                    ]
                ];
                $api_response = $api->request( '/shops/{SHOP_ID}/products', 'POST', $mock_product ); 

                // ĐỒNG BỘ: Lưu sản phẩm vừa tạo vào WordPress Database ngay lập tức
                if ( isset( $api_response['success'] ) && $api_response['success'] && ! empty( $api_response['data'] ) ) {
                    Bacera_Utils::upsert_external_product( $api_response['data'] );
                }
                break;

            case 'update_inventory':
                $mock_inventory = [
                    'is_actual_remain_quantity' => true,
                    'variations_warehouses' => [] 
                ];
                $api_response = $api->request( '/shops/{SHOP_ID}/variations/update_quantity', 'POST', $mock_inventory ); 
                break;

            // ================= ORDERS =================
            case 'get_orders':
                $api_response = $api->request( '/shops/{SHOP_ID}/orders?page_size=6', 'GET' ); 
                break;

            case 'create_mock_order':
                $mock_order = [
                    'bill_full_name' => 'Đơn hàng Test Bacera',
                    'bill_phone_number' => '09' . wp_rand( 10000000, 99999999 ),
                    'total_amount' => 350000,
                    'note' => 'Đơn hàng test từ API Dashboard. Vui lòng huỷ.'
                ];
                $api_response = $api->request( '/shops/{SHOP_ID}/orders', 'POST', $mock_order ); 
                break;

            // ================= CUSTOMERS =================
            case 'get_customers':
                $api_response = $api->request( '/shops/{SHOP_ID}/customers?page_size=6', 'GET' );
                break;

            case 'create_mock_customer':
                $mock_customer = [
                    'name' => 'Bacera Test User ' . wp_rand( 100, 999 ),
                    'phone_number' => '09' . wp_rand( 10000000, 99999999 ),
                    'email' => 'test' . wp_rand( 100, 999 ) . '@bacera.vn'
                ];
                $api_response = $api->request( '/shops/{SHOP_ID}/customers', 'POST', $mock_customer ); 
                break;

            default:
                $api_response = ['error' => 'Action không hợp lệ.'];
                break;
        }
    } else {
        $api_response = ['error' => 'Class Pancake_API_Client không tồn tại!'];
    }
}
?>

<main class="min-h-screen bg-gray-50/50 section-pad font-sans selection:bg-primary-500/10 selection:text-primary-800">
    <div class="max-w-[85rem] mx-auto px-4 md:px-6 lg:px-8">
        
        <header class="mb-20 text-center relative z-10 rounded-[3rem] bg-white p-12 md:p-20 shadow-sm border border-gray-100 overflow-hidden">
            <div class="absolute inset-0 -z-10 flex items-center justify-center pointer-events-none opacity-30">
                <div class="w-[40rem] h-[40rem] bg-gradient-to-tr from-primary-200 via-transparent to-accent-100 rounded-full blur-[100px]"></div>
            </div>
            
            <h1 class="text-h1 tracking-tight text-gray-900 sm:text-5xl md:text-7xl mb-6 leading-none">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-accent-500 font-bold">Pancake POS</span><br/>API Dashboard
            </h1>
            <p class="text-body-reg md:text-[20px] text-gray-500 max-w-3xl mx-auto font-medium">
                Công cụ test toàn diện. Dữ liệu trả về sẽ được minh hoạ bằng hệ thống UI Components.
            </p>
        </header>

        <div class="space-y-16">

            <?php if ( $api_response !== null ) : ?>
                <div class="flex items-center gap-6 py-4">
                    <h2 class="text-4xl font-black text-gray-900 tracking-tight shrink-0">Response Result</h2>
                    <div class="h-1 flex-1 bg-gray-200 rounded-full"></div>
                </div>

                <section class="bg-white rounded-[2rem] p-8 md:p-12 shadow-sm border border-gray-100 relative overflow-hidden">
                    <div class="mb-8 flex justify-between items-center border-b border-gray-100 pb-6">
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full <?php echo (isset($api_response['success']) && $api_response['success'] === true) ? 'bg-green-500' : 'bg-accent-500'; ?>"></span>
                            <div>
                                <h3 class="text-h2 font-bold text-gray-900 border-none mb-0">Action: <?php echo esc_html( $action_tested ); ?></h3>
                            </div>
                        </div>
                        <span class="px-4 py-1.5 rounded-full text-[12px] font-bold uppercase tracking-widest <?php echo (isset($api_response['success']) && $api_response['success'] === true) ? 'bg-green-50 text-green-600' : 'bg-accent-50 text-accent-600'; ?>">
                            <?php echo (isset($api_response['success']) && $api_response['success'] === true) ? 'SUCCESS 200' : 'RESPONSE'; ?>
                        </span>
                    </div>

                    <?php 
                    // Chuẩn hóa mảng dữ liệu để lặp (POST thường trả về 1 item, GET trả về mảng)
                    $res_data = $api_response['data'] ?? [];
                    $items_to_render = (isset($res_data['id']) && !isset($res_data[0])) ? [$res_data] : $res_data;
                    
                    if ( !empty($items_to_render) && is_array($items_to_render) ) : 

                        // 1. MINH HOẠ: SẢN PHẨM (PRODUCT CARDS)
                        if ( in_array($action_tested, ['get_products', 'create_mock_product']) ) : ?>
                            <div class="max-w-5xl mx-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-[32px]">
                                <?php foreach ( $items_to_render as $p ) : 
                                    $name = $p['product']['name'] ?? $p['name'] ?? 'Sản phẩm chưa có tên';
                                    
                                    // LOGIC MỚI: Không dùng link gốc, chuyển sang dùng Proxy URL của Bacera
                                    $image_url = Bacera_Utils::get_proxy_url($p); 

                                    $price_at_counter = isset($p['price_at_counter']) ? (float)$p['price_at_counter'] : (isset($p['variations'][0]['price_at_counter']) ? (float)$p['variations'][0]['price_at_counter'] : 0);
                                    $retail_price     = isset($p['retail_price']) ? (float)$p['retail_price'] : (isset($p['variations'][0]['retail_price']) ? (float)$p['variations'][0]['retail_price'] : 0);
                                    
                                    $price = $price_at_counter > 0 ? $price_at_counter : $retail_price;
                                    $original_price = ($retail_price > $price) ? $retail_price : 0; 

                                    $discount_percent = false;
                                    if ($original_price > 0 && $price < $original_price) {
                                        $discount_percent = '-' . round((($original_price - $price) / $original_price) * 100) . '%';
                                    }

                                    get_template_part('app/Views/components/product-card', null, [
                                        'title'    => $name,
                                        'price'    => number_format($price, 0, ',', '.') . ' ₫',
                                        'old_price'=> $original_price > 0 ? number_format($original_price, 0, ',', '.') . ' ₫' : '',
                                        'image'    => $image_url, // Bây giờ là link: bacera.vn/pancake-img/...
                                        'discount' => $discount_percent
                                    ]);
                                endforeach; ?>
                            </div>

                        <?php 
                        // 2. MINH HOẠ: ĐƠN HÀNG (ORDER CARDS)
                        elseif ( in_array($action_tested, ['get_orders', 'create_mock_order']) ) : ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ( $items_to_render as $ord ) : ?>
                                    <div class="bg-white border border-stone-200 p-6 rounded-[1.5rem] shadow-sm hover:shadow-md transition duration-300">
                                        <div class="flex justify-between items-start mb-4 border-b border-stone-100 pb-4">
                                            <div>
                                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">Đơn hàng</p>
                                                <h4 class="font-bold text-gray-900">#<?php echo esc_html($ord['display_id'] ?? $ord['id'] ?? 'N/A'); ?></h4>
                                            </div>
                                            <span class="px-2 py-1 bg-green-50 text-green-600 text-[11px] font-bold rounded uppercase tracking-wider">Đã ghi nhận</span>
                                        </div>
                                        <p class="text-[14px] text-gray-600 mb-1"><strong class="text-gray-900 font-medium">Khách:</strong> <?php echo esc_html($ord['bill_full_name'] ?? 'N/A'); ?></p>
                                        <p class="text-[14px] text-gray-600 mb-1"><strong class="text-gray-900 font-medium">SĐT:</strong> <?php echo esc_html($ord['bill_phone_number'] ?? 'N/A'); ?></p>
                                        <div class="mt-5 pt-4 border-t border-dashed border-stone-200 flex justify-between items-center">
                                            <span class="text-[12px] text-gray-400">Tổng tiền</span>
                                            <span class="text-[18px] font-black text-primary-600"><?php echo number_format($ord['total_amount'] ?? 0, 0, ',', '.'); ?> ₫</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        <?php 
                        // 3. MINH HOẠ: KHÁCH HÀNG (CUSTOMER CARDS)
                        elseif ( in_array($action_tested, ['get_customers', 'create_mock_customer']) ) : ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ( $items_to_render as $cus ) : 
                                    $first_letter = mb_strtoupper(mb_substr($cus['name'] ?? 'U', 0, 1));
                                ?>
                                    <div class="bg-stone-50/50 border border-stone-200 p-5 rounded-2xl flex items-center gap-4 hover:bg-white hover:shadow-md transition">
                                        <div class="w-14 h-14 shrink-0 rounded-full bg-gradient-to-br from-accent-400 to-accent-600 text-white flex items-center justify-center font-bold text-xl shadow-inner">
                                            <?php echo esc_html($first_letter); ?>
                                        </div>
                                        <div class="overflow-hidden">
                                            <h4 class="font-bold text-gray-900 truncate"><?php echo esc_html($cus['name'] ?? 'Chưa cập nhật'); ?></h4>
                                            <p class="text-[13px] text-gray-600 mt-0.5 truncate flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                                <?php echo esc_html($cus['phone_number'] ?? 'N/A'); ?>
                                            </p>
                                            <p class="text-[12px] text-gray-400 mt-0.5 truncate flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                                <?php echo esc_html($cus['email'] ?? 'N/A'); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        <?php 
                        // 4. MINH HOẠ: KHO HÀNG (WAREHOUSE CARDS)
                        elseif ( $action_tested === 'test_connection' ) : ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <?php foreach ( $items_to_render as $wh ) : ?>
                                    <div class="bg-white border-2 border-primary-100 p-6 rounded-[1.5rem] flex flex-col gap-3 relative overflow-hidden hover:border-primary-300 transition">
                                        <div class="absolute -right-4 -top-4 text-primary-50 opacity-50">
                                            <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2zM5 5v14h14V5H5zm2 2h10v2H7V7zm0 4h10v2H7v-2zm0 4h7v2H7v-2z"></path></svg>
                                        </div>
                                        <div class="relative z-10 flex items-center gap-3">
                                            <span class="w-10 h-10 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-bold">WH</span>
                                            <div>
                                                <p class="text-[11px] font-bold text-primary-400 uppercase tracking-widest">Chi nhánh / Kho</p>
                                                <h4 class="font-bold text-gray-900 text-lg"><?php echo esc_html($wh['name'] ?? 'Unnamed Warehouse'); ?></h4>
                                            </div>
                                        </div>
                                        <div class="relative z-10 bg-gray-50 p-3 rounded-lg mt-2 flex items-center justify-between">
                                            <span class="text-[12px] text-gray-500">Warehouse ID:</span>
                                            <code class="text-[13px] font-mono text-gray-800 bg-gray-200 px-2 py-0.5 rounded"><?php echo esc_html($wh['id'] ?? 'N/A'); ?></code>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        <?php 
                        // 5. MINH HOẠ: DANH MỤC (CATEGORY BADGES)
                        elseif ( $action_tested === 'get_categories' ) : ?>
                            <div class="flex flex-wrap gap-4 items-center">
                                <?php foreach ( $items_to_render as $cat ) : ?>
                                    <?php get_template_part('app/Views/components/badge', null, ['text' => esc_html( $cat['text'] ?? $cat['name'] ), 'type' => 'primary']); ?>
                                <?php endforeach; ?>
                            </div>

                        <?php else: ?>
                            <div class="bg-gray-900 rounded-xl p-6 overflow-x-auto max-h-[400px] overflow-y-auto custom-scrollbar">
                                <pre class="text-gray-300 text-[13px] font-mono leading-relaxed m-0"><?php echo esc_html( print_r( $api_response, true ) ); ?></pre>
                            </div>
                        <?php endif; 

                    // Nếu API trả về mảng rỗng hoặc lỗi -> Hiển thị Raw JSON
                    else: ?>
                        <div class="bg-gray-900 rounded-xl p-6 overflow-x-auto max-h-[400px] overflow-y-auto custom-scrollbar">
                            <pre class="text-gray-300 text-[13px] font-mono leading-relaxed m-0"><?php echo esc_html( print_r( $api_response, true ) ); ?></pre>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <div class="flex items-center gap-6 py-4 <?php echo ($api_response === null) ? 'mt-8' : 'mt-24'; ?>">
                <h2 class="text-4xl font-black text-gray-900 tracking-tight shrink-0">Endpoints Testing</h2>
                <div class="h-1 flex-1 bg-gray-200 rounded-full"></div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="flex flex-col gap-6">
                    <h3 class="text-[14px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200 pb-3">1. Sản phẩm & Kho hàng</h3>
                    
                    <form method="POST" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between h-full hover:shadow-md transition">
                        <?php wp_nonce_field( 'test_api_action', 'pancake_test_nonce' ); ?>
                        <input type="hidden" name="api_action" value="test_connection">
                        <div>
                            <span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-[10px] font-bold rounded mb-2">GET</span>
                            <h4 class="font-bold text-gray-900 mb-1">Kho hàng</h4>
                            <p class="text-[13px] text-gray-500 mb-4">Hiển thị thẻ thông tin các kho hàng thực tế.</p>
                        </div>
                        <button type="submit" class="w-full py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-medium rounded-lg transition">Test Kho Hàng</button>
                    </form>

                    <form method="POST" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between h-full ring-2 ring-primary-100 hover:shadow-md transition">
                        <?php wp_nonce_field( 'test_api_action', 'pancake_test_nonce' ); ?>
                        <input type="hidden" name="api_action" value="get_products">
                        <div>
                            <span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-[10px] font-bold rounded mb-2">GET</span>
                            <h4 class="font-bold text-gray-900 mb-1">Danh sách Sản phẩm</h4>
                            <p class="text-[13px] text-gray-500 mb-4">Hiển thị UI danh sách thẻ gốm.</p>
                        </div>
                        <button type="submit" class="w-full py-2.5 bg-primary-900 hover:bg-primary-800 text-white text-sm font-medium rounded-lg transition">Lấy</button>
                    </form>

                    <form method="POST" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between h-full hover:shadow-md transition" onsubmit="return confirm('Hành động này sẽ tạo 1 sản phẩm nháp trên POS. Tiếp tục?');">
                        <?php wp_nonce_field( 'test_api_action', 'pancake_test_nonce' ); ?>
                        <input type="hidden" name="api_action" value="create_mock_product">
                        <div>
                            <span class="inline-block px-2 py-1 bg-green-100 text-green-700 text-[10px] font-bold rounded mb-2">POST</span>
                            <h4 class="font-bold text-gray-900 mb-1">Tạo SP Mockup</h4>
                            <p class="text-[13px] text-gray-500 mb-4">Đẩy 1 sản phẩm rác và xem thẻ UI trả về.</p>
                        </div>
                        <button type="submit" class="w-full py-2.5 border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium rounded-lg transition">Test Tạo Sản phẩm</button>
                    </form>
                </div>

                <div class="flex flex-col gap-6">
                    <h3 class="text-[14px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200 pb-3">2. Đơn Hàng</h3>
                    
                    <form method="POST" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between h-full hover:shadow-md transition">
                        <?php wp_nonce_field( 'test_api_action', 'pancake_test_nonce' ); ?>
                        <input type="hidden" name="api_action" value="get_orders">
                        <div>
                            <span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-[10px] font-bold rounded mb-2">GET</span>
                            <h4 class="font-bold text-gray-900 mb-1">Lịch sử Đơn hàng</h4>
                            <p class="text-[13px] text-gray-500 mb-4">Lấy 6 đơn mới nhất hiển thị dưới dạng UI Card.</p>
                        </div>
                        <button type="submit" class="w-full py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-medium rounded-lg transition">Test Lấy Đơn hàng</button>
                    </form>

                    <form method="POST" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between h-full hover:shadow-md transition" onsubmit="return confirm('Hành động này sẽ tạo 1 ĐƠN HÀNG RÁC trên POS. Tiếp tục?');">
                        <?php wp_nonce_field( 'test_api_action', 'pancake_test_nonce' ); ?>
                        <input type="hidden" name="api_action" value="create_mock_order">
                        <div>
                            <span class="inline-block px-2 py-1 bg-green-100 text-green-700 text-[10px] font-bold rounded mb-2">POST</span>
                            <h4 class="font-bold text-gray-900 mb-1">Tạo Đơn Hàng Ảo</h4>
                            <p class="text-[13px] text-gray-500 mb-4">Mô phỏng Checkout và hiển thị UI đơn vừa tạo.</p>
                        </div>
                        <button type="submit" class="w-full py-2.5 bg-accent-500 hover:bg-accent-600 text-white text-sm font-medium rounded-lg transition">Test Tạo Đơn hàng</button>
                    </form>
                </div>

                <div class="flex flex-col gap-6">
                    <h3 class="text-[14px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-200 pb-3">3. Khách Hàng </h3>
                    
                    <form method="POST" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between h-full hover:shadow-md transition">
                        <?php wp_nonce_field( 'test_api_action', 'pancake_test_nonce' ); ?>
                        <input type="hidden" name="api_action" value="get_customers">
                        <div>
                            <span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-[10px] font-bold rounded mb-2">GET</span>
                            <h4 class="font-bold text-gray-900 mb-1">Tra cứu Khách hàng</h4>
                            <p class="text-[13px] text-gray-500 mb-4">Hiển thị thẻ User (Avatar, SĐT, Email).</p>
                        </div>
                        <button type="submit" class="w-full py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-medium rounded-lg transition">Test Lấy Khách Hàng</button>
                    </form>

                    <form method="POST" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between h-full hover:shadow-md transition" onsubmit="return confirm('Hành động này sẽ tạo 1 DATA RÁC vào CRM của POS. Tiếp tục?');">
                        <?php wp_nonce_field( 'test_api_action', 'pancake_test_nonce' ); ?>
                        <input type="hidden" name="api_action" value="create_mock_customer">
                        <div>
                            <span class="inline-block px-2 py-1 bg-green-100 text-green-700 text-[10px] font-bold rounded mb-2">POST</span>
                            <h4 class="font-bold text-gray-900 mb-1">Tạo Customer Ảo</h4>
                            <p class="text-[13px] text-gray-500 mb-4">Mô phỏng quy trình Đăng ký tài khoản.</p>
                        </div>
                        <button type="submit" class="w-full py-2.5 border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium rounded-lg transition">Test Tạo Customer</button>
                    </form>
                    
                    <form method="POST" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between h-full hover:shadow-md transition">
                        <?php wp_nonce_field( 'test_api_action', 'pancake_test_nonce' ); ?>
                        <input type="hidden" name="api_action" value="get_categories">
                        <div>
                            <span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-[10px] font-bold rounded mb-2">GET</span>
                            <h4 class="font-bold text-gray-900 mb-1">Cấu trúc Danh mục</h4>
                            <p class="text-[13px] text-gray-500 mb-4">Kiểm tra Category Tree.</p>
                        </div>
                        <button type="submit" class="w-full py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-medium rounded-lg transition">Test Categories</button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</main>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #374151; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #4b5563; }
</style>

<?php get_footer(); ?>