<?php
/**
 * Template Name: Pancake API Tester
 * Description: Trang công cụ nội bộ để test kết nối và các endpoints của Pancake POS API (Hiển thị UI trực quan).
 */

// BẢO MẬT: Chỉ cho phép Admin truy cập trang này
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
            case 'test_connection':
                $api_response = $api->request( '/shops/{SHOP_ID}/warehouses', 'GET' );
                break;

            case 'get_categories':
                $api_response = $api->request( '/shops/{SHOP_ID}/categories', 'GET' );
                break;

            case 'get_products':
                // Lấy 6 sản phẩm/biến thể mới nhất để hiển thị cho đẹp đội hình 3 cột
                $api_response = $api->request( '/shops/{SHOP_ID}/products/variations?page_size=6', 'GET' );
                break;

            case 'create_mock_customer':
                $mock_data = [
                    'name' => 'Bacera Test User ' . wp_rand( 1000, 9999 ),
                    'phone_number' => '09' . wp_rand( 10000000, 99999999 ),
                    'email' => 'test' . wp_rand( 100, 999 ) . '@bacera.vn'
                ];
                $api_response = $api->request( '/shops/{SHOP_ID}/customers', 'POST', $mock_data );
                break;

            default:
                $api_response = ['error' => 'Action không hợp lệ.'];
                break;
        }
    } else {
        $api_response = ['error' => 'Class Pancake_API_Client không tồn tại. Plugin tích hợp chưa được kích hoạt!'];
    }
}
?>

<main class="min-h-screen bg-gray-50/50 py-20 font-sans selection:bg-primary-500/10 selection:text-primary-800">
    <div class="max-w-[85rem] mx-auto px-4 md:px-6 lg:px-8">
        
        <header class="mb-20 text-center relative z-10 rounded-[3rem] bg-white p-12 md:p-20 shadow-sm border border-gray-100 overflow-hidden">
            <div class="absolute inset-0 -z-10 flex items-center justify-center pointer-events-none opacity-30">
                <div class="w-[40rem] h-[40rem] bg-gradient-to-tr from-primary-200 via-transparent to-accent-100 rounded-full blur-[100px]"></div>
            </div>
            
            <h1 class="text-h1 tracking-tight text-gray-900 sm:text-5xl md:text-7xl mb-6 leading-none">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-accent-500 font-bold">Pancake POS</span><br/>API Dashboard
            </h1>
            <p class="text-body-reg md:text-[20px] text-gray-500 max-w-3xl mx-auto font-medium">
                Trang công cụ nội bộ dành cho Developer. Render trực tiếp dữ liệu thật từ API vào hệ thống UI Component của Bacera.
            </p>
        </header>

        <div class="space-y-16">

            <?php if ( $api_response !== null ) : ?>
                <div class="flex items-center gap-6 py-4">
                    <h2 class="text-4xl font-black text-gray-900 tracking-tight shrink-0">1. Response Result</h2>
                    <div class="h-1 flex-1 bg-gray-200 rounded-full"></div>
                </div>

                <section class="bg-white rounded-[2rem] p-8 md:p-12 shadow-sm border border-gray-100 relative overflow-hidden">
                    
                    <div class="mb-8 flex justify-between items-center border-b border-gray-100 pb-6">
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full <?php echo (isset($api_response['success']) && $api_response['success'] === true) ? 'bg-green-500' : 'bg-accent-500'; ?>"></span>
                            <div>
                                <h3 class="text-h2 font-bold text-gray-900 border-none mb-0">Visual Data: <?php echo esc_html( $action_tested ); ?></h3>
                            </div>
                        </div>
                        <span class="px-4 py-1.5 rounded-full text-[12px] font-bold uppercase tracking-widest <?php echo (isset($api_response['success']) && $api_response['success'] === true) ? 'bg-green-50 text-green-600' : 'bg-accent-50 text-accent-600'; ?>">
                            <?php echo (isset($api_response['success']) && $api_response['success'] === true) ? 'SUCCESS 200' : 'ERROR / FAILED'; ?>
                        </span>
                    </div>

                    <?php 
                    // NẾU LÀ TEST SẢN PHẨM -> HIỂN THỊ UI PRODUCT CARD
                    if ( $action_tested === 'get_products' && isset( $api_response['data'] ) && is_array( $api_response['data'] ) ) : 
                    ?>
                        <div class="max-w-5xl mx-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-[32px]">
                            <?php 
                            foreach ( $api_response['data'] as $p ) : 
                                $name = $p['product']['name'] ?? 'Sản phẩm chưa có tên';
                                $price_at_counter = isset($p['price_at_counter']) ? (float)$p['price_at_counter'] : 0;
                                $retail_price     = isset($p['retail_price']) ? (float)$p['retail_price'] : 0;
                                $price = $price_at_counter > 0 ? $price_at_counter : $retail_price;
                                $original_price = ($retail_price > $price) ? $retail_price : 0; 

                                // Xử lý ảnh
                                $image_url = '';
                                if (!empty($p['images']) && is_array($p['images'])) {
                                    $image_url = $p['images'][0];
                                } elseif (!empty($p['product']['image'])) {
                                    $image_url = $p['product']['image'];
                                }

                                // Tính % giảm giá
                                $discount_percent = false;
                                if ($original_price > 0 && $price < $original_price) {
                                    $discount_percent = '-' . round((($original_price - $price) / $original_price) * 100) . '%';
                                }

                                // Truyền arguments vào component
                                $card_args = [
                                    'title'    => $name,
                                    'price'    => number_format($price, 0, ',', '.') . ' ₫',
                                    'old_price'=> $original_price > 0 ? number_format($original_price, 0, ',', '.') . ' ₫' : '',
                                    'image'    => $image_url,
                                    'discount' => $discount_percent
                                ];

                                // Render Product Card
                                get_template_part('app/Views/components/product-card', null, $card_args);
                            endforeach; 
                            ?>
                        </div>

                    <?php 
                    // NẾU LÀ TEST DANH MỤC -> HIỂN THỊ DẠNG BADGES/TAGS
                    elseif ( $action_tested === 'get_categories' && isset( $api_response['data'] ) && is_array( $api_response['data'] ) ) : 
                    ?>
                        <div class="flex flex-wrap gap-4 items-center">
                            <?php foreach ( $api_response['data'] as $cat ) : ?>
                                <?php get_template_part('app/Views/components/badge', null, ['text' => esc_html( $cat['text'] ?? $cat['name'] ), 'type' => 'primary']); ?>
                                <?php 
                                // Nếu có danh mục con
                                if ( !empty($cat['nodes']) ) {
                                    foreach ( $cat['nodes'] as $subcat ) {
                                        get_template_part('app/Views/components/badge', null, ['text' => esc_html( $subcat['text'] ?? $subcat['name'] ), 'type' => 'neutral']);
                                    }
                                }
                                ?>
                            <?php endforeach; ?>
                        </div>

                    <?php 
                    // CÁC TRƯỜNG HỢP CÒN LẠI HOẶC BỊ LỖI -> HIỂN THỊ RAW JSON
                    else : 
                    ?>
                        <div class="bg-gray-900 rounded-xl p-6 overflow-x-auto max-h-[400px] overflow-y-auto custom-scrollbar">
                            <pre class="text-gray-300 text-[14px] font-mono leading-relaxed m-0"><?php echo esc_html( print_r( $api_response, true ) ); ?></pre>
                        </div>
                    <?php endif; ?>

                </section>
            <?php endif; ?>

            <div class="flex items-center gap-6 py-4 <?php echo ($api_response === null) ? 'mt-8' : 'mt-24'; ?>">
                <h2 class="text-4xl font-black text-gray-900 tracking-tight shrink-0"><?php echo ($api_response === null) ? '1' : '2'; ?>. Endpoints Testing</h2>
                <div class="h-1 flex-1 bg-gray-200 rounded-full"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-stretch">
                
                <section class="bg-white rounded-[2rem] p-8 md:p-10 shadow-sm border border-gray-100 flex flex-col h-full justify-between hover:shadow-md transition-shadow">
                    <div>
                        <div class="flex items-center gap-3 mb-6">
                            <span class="w-3 h-3 rounded-full bg-primary-500"></span>
                            <h3 class="text-h3 font-bold text-gray-900 border-none mb-0">Kiểm tra kết nối</h3>
                        </div>
                        <p class="text-body-reg text-gray-500 mb-4">Xác minh API Key. Dữ liệu trả về sẽ hiển thị dưới dạng JSON thuần.</p>
                    </div>
                    <form method="POST">
                        <?php wp_nonce_field( 'test_api_action', 'pancake_test_nonce' ); ?>
                        <input type="hidden" name="api_action" value="test_connection">
                        <button type="submit" class="w-full mt-8 px-6 py-4 bg-primary-900 hover:bg-primary-800 text-white font-medium rounded-xl transition-colors">Test Warehouses</button>
                    </form>
                </section>

                <section class="bg-white rounded-[2rem] p-8 md:p-10 shadow-sm border border-gray-100 flex flex-col h-full justify-between hover:shadow-md ring-2 ring-primary-100 transition-shadow">
                    <div>
                        <div class="flex items-center gap-3 mb-6">
                            <span class="w-3 h-3 rounded-full bg-accent-500 animate-pulse"></span>
                            <h3 class="text-h3 font-bold text-gray-900 border-none mb-0">Danh sách Sản phẩm</h3>
                        </div>
                        <p class="text-body-reg text-gray-500 mb-4">Lấy dữ liệu thật từ <code class="bg-gray-100 px-2 py-1 rounded">/products</code> và render trực tiếp thành <strong>Product Card UI</strong>.</p>
                    </div>
                    <form method="POST">
                        <?php wp_nonce_field( 'test_api_action', 'pancake_test_nonce' ); ?>
                        <input type="hidden" name="api_action" value="get_products">
                        <button type="submit" class="w-full mt-8 px-6 py-4 bg-accent-500 text-white hover:bg-accent-600 font-medium rounded-xl transition-colors">Test & Render UI</button>
                    </form>
                </section>

                <section class="bg-white rounded-[2rem] p-8 md:p-10 shadow-sm border border-gray-100 flex flex-col h-full justify-between hover:shadow-md transition-shadow">
                    <div>
                        <div class="flex items-center gap-3 mb-6">
                            <span class="w-3 h-3 rounded-full bg-neutral-300"></span>
                            <h3 class="text-h3 font-bold text-gray-900 border-none mb-0">Cấu trúc Danh mục</h3>
                        </div>
                        <p class="text-body-reg text-gray-500 mb-4">Lấy Category Tree và render ra màn hình dưới dạng <strong>Badge/Tag UI</strong>.</p>
                    </div>
                    <form method="POST">
                        <?php wp_nonce_field( 'test_api_action', 'pancake_test_nonce' ); ?>
                        <input type="hidden" name="api_action" value="get_categories">
                        <button type="submit" class="w-full mt-8 px-6 py-4 border-2 border-gray-200 text-gray-700 hover:border-gray-300 hover:bg-gray-50 font-medium rounded-xl transition-colors">Test Categories UI</button>
                    </form>
                </section>

                <section class="bg-white rounded-[2rem] p-8 md:p-10 shadow-sm border border-gray-100 flex flex-col h-full justify-between hover:shadow-md transition-shadow">
                    <div>
                        <div class="flex items-center gap-3 mb-6">
                            <span class="w-3 h-3 rounded-full bg-primary-300"></span>
                            <h3 class="text-h3 font-bold text-gray-900 border-none mb-0">Tạo Mock Customer</h3>
                        </div>
                        <p class="text-body-reg text-gray-500 mb-4">Bắn dữ liệu rác lên <code class="bg-gray-100 px-2 py-1 rounded">/customers</code> để test quyền ghi. Kết quả trả về là JSON.</p>
                    </div>
                    <form method="POST" onsubmit="return confirm('Hành động này sẽ tạo 1 khách hàng rác trên hệ thống Pancake POS của bạn. Tiếp tục?');">
                        <?php wp_nonce_field( 'test_api_action', 'pancake_test_nonce' ); ?>
                        <input type="hidden" name="api_action" value="create_mock_customer">
                        <button type="submit" class="w-full mt-8 px-6 py-4 border-2 border-primary-900 text-primary-900 hover:bg-primary-50 font-medium rounded-xl transition-colors">Test POST Customer</button>
                    </form>
                </section>

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