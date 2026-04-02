<?php
/**
 * Template Name: Shop - Product Listing
 * Description: Shop page kết nối Pancake POS - hiển thị sản phẩm từ /products/variations
 */

get_header(); ?>

<main class="shop-page bg-[#f8f4f0] py-10">
    <div class="container mx-auto px-6 max-w-7xl">

        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="<?php echo home_url(); ?>" class="hover:text-gray-700">Homepage</a>
            <span>/</span>
            <span class="font-medium text-gray-900">Shop all</span>
        </div>

        <h1 class="text-4xl font-semibold mb-8">Shop by</h1>

        <!-- Shop by Tabs -->
        <div class="flex gap-3 overflow-x-auto pb-6 scrollbar-hide">
            <?php
            $categories = [
                ['name' => 'Shop all',      'icon' => '🏺', 'active' => true],
                ['name' => 'Teapots',      'icon' => '🍵'],
                ['name' => 'Drinkware',    'icon' => '☕'],
                ['name' => 'Bowls',        'icon' => '🥣'],
                ['name' => 'Kitchen',      'icon' => '🍳'],
                ['name' => 'Plates',       'icon' => '🍽️'],
                ['name' => 'Vases & Decor','icon' => '🏺'],
                ['name' => 'Storage',      'icon' => '📦'],
            ];
            foreach ($categories as $cat) :
                $active = !empty($cat['active']) ? 'bg-[#f5f0e8] shadow-sm' : 'hover:bg-gray-100';
            ?>
                <a href="#" class="flex-shrink-0 flex flex-col items-center gap-2 w-24 py-3 px-4 rounded-2xl border border-gray-200 <?php echo $active; ?>">
                    <div class="text-3xl"><?php echo $cat['icon']; ?></div>
                    <span class="text-xs font-medium text-center"><?php echo $cat['name']; ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">

            <!-- Sidebar Filter -->
            <div class="w-full lg:w-72 bg-white rounded-3xl p-6 shadow-sm h-fit">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="font-semibold text-lg">Filter</h2>
                    <button onclick="resetFilters()" class="text-sm text-gray-500 hover:text-red-600">Reset</button>
                </div>

                <form id="shop-filter" method="GET" class="space-y-8">
                    <!-- Collection -->
                    <div>
                        <h3 class="font-medium mb-3">Collection</h3>
                        <div class="space-y-2 text-sm">
                            <label class="flex items-center gap-2"><input type="checkbox" name="collection[]" value="whispers-of-clay" checked> Whispers of Clay</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="collection[]" value="morning-rituals"> Morning Rituals</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="collection[]" value="seasons-of-earth"> Seasons of Earth</label>
                        </div>
                    </div>

                    <!-- Capacity -->
                    <div>
                        <h3 class="font-medium mb-3">Capacity</h3>
                        <div class="space-y-2 text-sm">
                            <label><input type="checkbox" name="capacity[]" value="lt100"> &lt;100ml</label>
                            <label><input type="checkbox" name="capacity[]" value="100-150"> 100–150ml</label>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Product Area -->
            <div class="flex-1">
                <?php
                $api = new Pancake_API();
                $params = [
                    'page_size'   => 24,
                    'page_number' => 1
                ];

                $response = $api->get_products($params);

                // Lấy danh sách variation (response['data'])
                $products = isset($response['data']) && is_array($response['data']) 
                            ? $response['data'] 
                            : [];
                ?>

                <!-- Sort & Result count -->
                <div class="flex justify-between items-center mb-6">
                    <p class="text-gray-600">
                        Showing <span class="font-semibold"><?php echo count($products); ?></span> 
                        of <span class="font-semibold"><?php echo $response['total_entries'] ?? 0; ?></span> products
                    </p>
                    <div class="flex items-center gap-2">
                        <span class="text-sm">Sort by:</span>
                        <select id="sort-by" onchange="applySort()" class="border border-gray-200 rounded-2xl px-4 py-2 text-sm focus:outline-none">
                            <option value="price-low">Price low - high</option>
                            <option value="price-high">Price high - low</option>
                            <option value="name-az">Name A-Z</option>
                        </select>
                    </div>
                </div>

                <!-- Product Grid -->
                <div id="product-grid" class="grid grid-cols-2 md:grid-cols-3 gap-6">
                    <?php if (!empty($products)) : ?>
                        <?php foreach ($products as $p) : 
                            // Map đúng field theo response Pancake
                            $name       = $p['product']['name'] ?? 'No name';
                            $price      = $p['retail_price'] ?? $p['price_at_counter'] ?? 0;
                            $image_url  = '';

                            // Ưu tiên lấy ảnh từ mảng images
                            if (!empty($p['images']) && is_array($p['images'])) {
                                $image_url = $p['images'][0];
                            }
                            // Fallback về ảnh của product
                            if (empty($image_url) && !empty($p['product']['image'])) {
                                $image_url = $p['product']['image'];
                            }

                            $price_display = number_format($price, 0, ',', '.') . ' ₫';
                        ?>
                            <div class="group bg-white rounded-3xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-300">
                                <div class="relative">
                                    <img src="<?php echo esc_url($image_url ?: 'https://placehold.co/600x600?text=No+Image'); ?>" 
                                         alt="<?php echo esc_attr($name); ?>"
                                         class="w-full aspect-square object-cover">
                                </div>
                                <div class="p-4">
                                    <p class="text-xs text-gray-500">Whispers of Clay</p>
                                    <h3 class="font-medium mt-1 line-clamp-2"><?php echo esc_html($name); ?></h3>
                                    <div class="mt-3 text-xl font-semibold text-gray-900">
                                        <?php echo $price_display; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p class="col-span-full text-center py-20 text-gray-500">
                            Hiện chưa có sản phẩm nào. Vui lòng kiểm tra lại trong Pancake POS.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function resetFilters() {
    document.getElementById('shop-filter').reset();
    location.reload();
}
function applySort() {
    // Có thể nâng cấp AJAX sau
    location.reload();
}
</script>

<?php get_footer(); ?>