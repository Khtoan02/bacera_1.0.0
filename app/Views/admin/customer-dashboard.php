<?php
$data = get_query_var('bacera_dashboard_data');
extract($data);
$pancake_configured      = isset( $pancake_configured ) ? $pancake_configured : false;
$pancake_plugin_active   = isset( $pancake_plugin_active ) ? $pancake_plugin_active : false;
$sync_disabled           = ! $pancake_plugin_active || ! $pancake_configured;
?>

<div class="bacera-dashboard wrap m-0 p-6 bg-stone-50 min-h-screen">

    <?php if ( isset( $_GET['bacera_sync'] ) && $_GET['bacera_sync'] === '1' ) : ?>
        <div class="notice notice-success is-dismissible mb-6">
            <p>
                <strong>Đồng bộ Pancake hoàn tất.</strong>
                Đã xử lý: <?php echo isset( $_GET['sync_pulled'] ) ? (int) $_GET['sync_pulled'] : 0; ?> bản ghi từ POS;
                thêm <?php echo isset( $_GET['sync_inserted'] ) ? (int) $_GET['sync_inserted'] : 0; ?>,
                cập nhật <?php echo isset( $_GET['sync_updated'] ) ? (int) $_GET['sync_updated'] : 0 ?>;
                liên kết <?php echo isset( $_GET['sync_linked'] ) ? (int) $_GET['sync_linked'] : 0 ?>;
                tạo mới trên POS <?php echo isset( $_GET['sync_pushed'] ) ? (int) $_GET['sync_pushed'] : 0 ?>.
            </p>
            <?php if ( ! empty( $_GET['bacera_sync_err'] ) ) : ?>
                <p class="text-amber-800"><?php echo esc_html( urldecode( (string) $_GET['bacera_sync_err'] ) ); ?></p>
            <?php endif; ?>
        </div>
    <?php elseif ( isset( $_GET['bacera_sync'] ) && $_GET['bacera_sync'] === 'no_plugin' ) : ?>
        <div class="notice notice-error is-dismissible mb-6">
            <p>Cần kích hoạt plugin <strong>Bacera Pancake POS Integration</strong> để đồng bộ.</p>
        </div>
    <?php endif; ?>
    
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-stone-900 m-0 p-0">Quản lý Khách hàng</h1>
            <p class="text-stone-500 mt-2">Theo dõi và quản lý dữ liệu người dùng Bacera của bạn. Đồng bộ từ Pancake POS lấy dữ liệu chuẩn từ cửa hàng.</p>
        </div>
        <div class="flex gap-4 flex-wrap items-center">
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="inline">
                <?php wp_nonce_field( 'bacera_sync_pancake_customers' ); ?>
                <input type="hidden" name="action" value="bacera_sync_pancake_customers" />
                <button type="submit" class="inline-flex items-center px-4 py-2.5 rounded-lg text-sm font-semibold border border-stone-300 bg-white text-stone-800 hover:bg-stone-100 disabled:opacity-50 disabled:cursor-not-allowed" <?php disabled( $sync_disabled ); ?> title="<?php echo $sync_disabled ? esc_attr( 'Cấu hình API Key & Shop ID trong Settings Pancake và bật plugin.' ) : ''; ?>">
                    <svg class="w-4 h-4 mr-2 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Đồng bộ từ Pancake
                </button>
            </form>
            <?php 
            get_template_part('app/Views/components/button', null, [
                'text' => 'Tải lại dữ liệu',
                'variant' => 'light',
                'link' => admin_url('admin.php?page=bacera-customers')
            ]); 
            ?>
            <?php 
            get_template_part('app/Views/components/button', null, [
                'text' => 'Thêm khách hàng',
                'variant' => 'primary',
                'icon' => '<svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>'
            ]); 
            ?>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl border border-stone-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-stone-500 text-sm font-medium">Tổng Khách Hàng</p>
                <p class="text-3xl font-bold text-stone-800 mt-2"><?php echo esc_html($total_customers); ?></p>
            </div>
            <div class="w-12 h-12 bg-indigo-50 rounded-full flex items-center justify-center text-indigo-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl border border-stone-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-stone-500 text-sm font-medium">Đã Thiết Lập Mật Khẩu</p>
                <p class="text-3xl font-bold text-stone-800 mt-2"><?php echo esc_html($total_active_pass); ?></p>
            </div>
            <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center text-green-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl border border-stone-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-stone-500 text-sm font-medium">Đăng Ký Mới (30 ngày)</p>
                <p class="text-3xl font-bold text-stone-800 mt-2"><?php echo esc_html($recent_users); ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center text-blue-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
    </div>

    <div class="bg-white p-4 rounded-t-xl border border-stone-200 border-b-0 flex items-center justify-between gap-4">
        <form method="get" action="" class="flex-1 flex items-center gap-4">
            <input type="hidden" name="page" value="bacera-customers">
            <div class="w-full max-w-sm relative">
                <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Tìm tên, SĐT, Email..." class="w-full h-12 pl-10 pr-4 py-2 bg-white ring-1 ring-inset ring-stone-300 text-stone-800 rounded-lg outline-none font-sans focus:ring-2 focus:ring-stone-700">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-stone-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
            <?php 
            get_template_part('app/Views/components/button', null, [
                'text' => 'Tìm kiếm',
                'variant' => 'secondary',
                'class' => '!py-3'
            ]); 
            ?>
        </form>
    </div>

    <div class="bg-white rounded-b-xl border border-stone-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table>
                <thead>
                    <tr>
                        <th class="w-12 text-center text-stone-500 font-medium text-sm">ID</th>
                        <th class="text-stone-500 font-medium text-sm">Tên Khách Hàng</th>
                        <th class="text-stone-500 font-medium text-sm">Số Điện Thoại</th>
                        <th class="text-stone-500 font-medium text-sm">Email</th>
                        <th class="text-stone-500 font-medium text-sm">Trạng Thái MK</th>
                        <th class="text-stone-500 font-medium text-sm">Pancake ID</th>
                        <th class="text-stone-500 font-medium text-sm">Ngày Tạo</th>
                        <th class="text-stone-500 font-medium text-sm">Cập Nhật LK</th>
                        <th class="text-right text-stone-500 font-medium text-sm">Thao Tác</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-12 text-stone-500">
                            Không tìm thấy dữ liệu phù hợp.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $c): ?>
                        <tr>
                            <td class="text-center text-stone-500">#<?php echo esc_html($c['id']); ?></td>
                            <td class="font-medium text-stone-900"><?php echo esc_html($c['name']); ?></td>
                            <td class="text-stone-600"><?php echo esc_html($c['phone']); ?></td>
                            <td class="text-stone-600"><?php echo esc_html($c['email']); ?></td>
                            <td class="text-stone-500 font-mono text-xs"><?php echo ! empty( $c['pancake_customer_id'] ) ? esc_html( $c['pancake_customer_id'] ) : '—'; ?></td>
                            <td>
                                <?php if ($c['has_password']): ?>
                                    <?php get_template_part('app/Views/components/badge', null, ['text' => 'Đã Thiết Lập', 'type' => 'success']); ?>
                                <?php else: ?>
                                    <?php get_template_part('app/Views/components/badge', null, ['text' => 'Chưa Có', 'type' => 'neutral']); ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-stone-500"><?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?></td>
                            <td class="text-stone-500"><?php echo date('d/m/Y H:i', strtotime($c['password_updated_at'])); ?></td>
                            <td class="text-right">
                                <a href="#" class="inline-flex items-center text-stone-400 hover:text-stone-800 transition-colors">
                                    Chi tiết
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="px-6 py-4 border-t border-stone-200 flex items-center justify-between bg-stone-50">
            <span class="text-sm text-stone-500">
                Hiển thị trang <strong><?php echo esc_html($paged); ?></strong> / <strong><?php echo esc_html($total_pages); ?></strong>
            </span>
            <div class="flex gap-2">
                <?php if ($paged > 1): ?>
                    <a href="<?php echo admin_url('admin.php?page=bacera-customers&paged=' . ($paged - 1) . ($search ? '&s=' . esc_attr($search) : '')); ?>" class="px-4 py-2 bg-white border border-stone-300 rounded text-stone-600 text-sm hover:bg-stone-100">Trước</a>
                <?php endif; ?>
                
                <?php if ($paged < $total_pages): ?>
                    <a href="<?php echo admin_url('admin.php?page=bacera-customers&paged=' . ($paged + 1) . ($search ? '&s=' . esc_attr($search) : '')); ?>" class="px-4 py-2 bg-white border border-stone-300 rounded text-stone-600 text-sm hover:bg-stone-100">Sau</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
