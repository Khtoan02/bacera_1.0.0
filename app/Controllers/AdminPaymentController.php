<?php
namespace Bacera\Controllers;

use Bacera\Database\PaymentTables;

/**
 * AdminPaymentController — WP Admin pages to manage:
 *  - Payment Methods (CRUD + toggle active)
 *  - Promo Codes (CRUD + toggle active)
 */
class AdminPaymentController {

    public function __construct() {
        add_action('admin_menu',              [$this, 'registerMenus']);
        add_action('admin_init',              [$this, 'initDatabase']);
        add_action('admin_enqueue_scripts',   [$this, 'enqueueStyles']);
        add_action('admin_post_bacera_save_payment_method', [$this, 'handleSaveMethod']);
        add_action('admin_post_bacera_delete_payment_method', [$this, 'handleDeleteMethod']);
        add_action('admin_post_bacera_save_promo', [$this, 'handleSavePromo']);
        add_action('admin_post_bacera_delete_promo', [$this, 'handleDeletePromo']);
        add_action('wp_ajax_bacera_toggle_payment_method', [$this, 'ajaxToggleMethod']);
        add_action('wp_ajax_bacera_toggle_promo', [$this, 'ajaxTogglePromo']);
    }

    public function enqueueStyles($hook) {
        if (strpos($hook, 'bacera-payments') === false) return;
        wp_enqueue_style('bacera-main-css', BACERA_THEME_URI . 'assets/css/main.css', [], BACERA_THEME_VERSION);
        wp_enqueue_script('bacera-main-js', BACERA_THEME_URI . 'assets/js/main.js', ['jquery'], BACERA_THEME_VERSION, true);
    }

    public function initDatabase() {
        $installed = get_option('bacera_payment_db_version');
        if ($installed !== PaymentTables::DB_VERSION) {
            PaymentTables::createTables();
            update_option('bacera_payment_db_version', PaymentTables::DB_VERSION);
        }
    }

    public function registerMenus() {
        add_submenu_page(
            'bacera-main',
            'Thanh toán & Mã giảm giá',
            'Thanh toán',
            'manage_options',
            'bacera-payments',
            [$this, 'pagePayments']
        );
    }

    /* ══════════════════════════════════════════════════════════════
     *  PAYMENT METHODS PAGE
     * ══════════════════════════════════════════════════════════════ */
    public function pagePayments() {
        global $wpdb;
        $tm = $wpdb->prefix . 'bacera_payment_methods';
        $tp = $wpdb->prefix . 'bacera_promo_codes';

        $methods = $wpdb->get_results("SELECT * FROM {$tm} ORDER BY sort_order ASC, id ASC", ARRAY_A);
        $promos  = $wpdb->get_results("SELECT * FROM {$tp} ORDER BY created_at DESC LIMIT 100", ARRAY_A);

        $edit_method_id = intval($_GET['edit_method'] ?? 0);
        $edit_method    = $edit_method_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tm} WHERE id=%d", $edit_method_id), ARRAY_A) : null;

        $edit_promo_id = intval($_GET['edit_promo'] ?? 0);
        $edit_promo    = $edit_promo_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tp} WHERE id=%d", $edit_promo_id), ARRAY_A) : null;

        $tab = sanitize_key($_GET['tab'] ?? 'methods');
        ?>
        <div class="wks-wrap" style="font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;padding:24px 24px 60px;background:#faf8f5;margin:-10px -20px 0;min-height:100vh;box-sizing:border-box;">
        <style>
        .wks-wrap *{box-sizing:border-box}
        .wks-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none!important;border:none;transition:all .15s;line-height:1.2;font-family:'Inter',sans-serif}
        .wks-btn-primary{background:#d95f47;color:#fff!important}.wks-btn-primary:hover{background:#b84d38!important;color:#fff!important}
        .wks-btn-secondary{background:#fff;color:#57534e!important;border:1px solid #ded5cd;box-shadow:0 1px 2px rgba(0,0,0,.04)}.wks-btn-secondary:hover{background:#f9f8f6!important;color:#1c1917!important}
        .wks-btn-danger{background:#fee2e2;color:#b91c1c!important;border:1px solid #fecaca}.wks-btn-danger:hover{background:#fecaca!important}
        .wks-btn-sm{padding:6px 12px;font-size:12px;border-radius:6px}
        .pay-card{background:#fff;border:1px solid #eae3d1;border-radius:14px;padding:24px;margin-bottom:16px}
        .pay-field{margin-bottom:16px}
        .pay-field label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#78716c;margin-bottom:6px}
        .pay-field input,.pay-field textarea,.pay-field select{width:100%;padding:10px 12px;border:1px solid #e7e5e4;border-radius:8px;font-size:14px;color:#1c1917;font-family:inherit;background:#fff}
        .pay-field input:focus,.pay-field textarea:focus,.pay-field select:focus{border-color:#d95f47;outline:none;box-shadow:0 0 0 3px rgba(217,95,71,.12)}
        .pay-field textarea{resize:vertical;min-height:80px}
        .pay-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .pay-method-row{display:flex;align-items:center;gap:12px;padding:14px 16px;background:#fff;border:1px solid #eae3d1;border-radius:12px;margin-bottom:8px}
        .pay-method-icon{width:40px;height:40px;border-radius:10px;background:#f5f5f4;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
        .pay-method-info{flex:1;min-width:0}
        .pay-method-name{font-weight:700;color:#1c1917;font-size:14px}
        .pay-method-code{font-size:12px;color:#78716c;margin-top:2px}
        .pay-toggle{position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0}
        .pay-toggle input{opacity:0;width:0;height:0}
        .pay-slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#d6d3d1;border-radius:24px;transition:.3s}
        .pay-slider:before{position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:white;border-radius:50%;transition:.3s}
        .pay-toggle input:checked+.pay-slider{background:#16a34a}
        .pay-toggle input:checked+.pay-slider:before{transform:translateX(20px)}
        .pay-tabs{display:flex;gap:4px;background:#f0ede8;border-radius:10px;padding:4px;margin-bottom:24px;width:fit-content}
        .pay-tab{padding:8px 20px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;color:#78716c;text-decoration:none!important;transition:all .2s}
        .pay-tab.active{background:#fff;color:#1c1917;box-shadow:0 1px 4px rgba(0,0,0,.08)}
        .notice-success{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;color:#15803d;font-size:13px;margin-bottom:16px}
        .promo-badge-active{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700}
        .promo-badge-inactive{background:#f5f5f4;color:#78716c;border:1px solid #e7e5e4;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700}
        </style>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
            <h1 style="margin:0;font-size:22px;font-weight:800;color:#1c1917;">💳 Thanh toán & Khuyến mãi</h1>
        </div>

        <?php if (!empty($_GET['saved'])): ?>
        <div class="notice-success">✅ Đã lưu thành công!</div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="pay-tabs">
            <a href="?page=bacera-payments&tab=methods" class="pay-tab <?php echo $tab==='methods'?'active':''; ?>">
                💳 Phương thức thanh toán
            </a>
            <a href="?page=bacera-payments&tab=promos" class="pay-tab <?php echo $tab==='promos'?'active':''; ?>">
                🎟️ Mã khuyến mãi
            </a>
        </div>

        <?php if ($tab === 'methods'): ?>
        <!-- ════ PAYMENT METHODS TAB ════ -->
        <div style="display:grid;grid-template-columns:1fr 420px;gap:24px;align-items:flex-start;">
            <!-- Left: list -->
            <div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                    <h2 style="margin:0;font-size:16px;font-weight:700;color:#1c1917;">Phương thức thanh toán</h2>
                    <a href="?page=bacera-payments&tab=methods&edit_method=new" class="wks-btn wks-btn-primary wks-btn-sm">+ Thêm mới</a>
                </div>
                <?php if (empty($methods)): ?>
                    <div style="text-align:center;padding:40px;color:#78716c;">Chưa có phương thức nào.</div>
                <?php else: foreach ($methods as $m): ?>
                <div class="pay-method-row">
                    <div class="pay-method-icon">
                        <?php echo $m['icon_url'] ? '<img src="'.esc_url($m['icon_url']).'" style="width:32px;height:32px;object-fit:contain;">' : '💳'; ?>
                    </div>
                    <div class="pay-method-info">
                        <div class="pay-method-name"><?php echo esc_html($m['name']); ?></div>
                        <div class="pay-method-code"><?php echo esc_html($m['code']); ?> — <?php echo esc_html(mb_substr($m['description'] ?: '—', 0, 60)); ?></div>
                    </div>
                    <label class="pay-toggle" title="Bật/tắt">
                        <input type="checkbox" <?php checked($m['is_active'],1); ?>
                               onchange="baceraToggleMethod(<?php echo $m['id']; ?>, this.checked)">
                        <span class="pay-slider"></span>
                    </label>
                    <a href="?page=bacera-payments&tab=methods&edit_method=<?php echo $m['id']; ?>" class="wks-btn wks-btn-secondary wks-btn-sm">Sửa</a>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;" onsubmit="return confirm('Xóa phương thức này?')">
                        <?php wp_nonce_field('bacera_delete_payment_method'); ?>
                        <input type="hidden" name="action" value="bacera_delete_payment_method">
                        <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                        <button type="submit" class="wks-btn wks-btn-danger wks-btn-sm">Xóa</button>
                    </form>
                </div>
                <?php endforeach; endif; ?>
            </div>

            <!-- Right: form -->
            <div class="pay-card">
                <?php if ($edit_method_id === 'new' || $edit_method): ?>
                <h3 style="margin:0 0 20px;font-size:15px;font-weight:700;"><?php echo $edit_method ? 'Chỉnh sửa phương thức' : 'Thêm phương thức mới'; ?></h3>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('bacera_save_payment_method'); ?>
                    <input type="hidden" name="action" value="bacera_save_payment_method">
                    <input type="hidden" name="id" value="<?php echo $edit_method['id'] ?? 0; ?>">
                    <div class="pay-field">
                        <label>Tên phương thức *</label>
                        <input type="text" name="name" value="<?php echo esc_attr($edit_method['name'] ?? ''); ?>" required placeholder="VD: Chuyển khoản ngân hàng">
                    </div>
                    <div class="pay-grid">
                        <div class="pay-field">
                            <label>Mã code (slug) *</label>
                            <input type="text" name="code" value="<?php echo esc_attr($edit_method['code'] ?? ''); ?>" required placeholder="bank_transfer">
                        </div>
                        <div class="pay-field">
                            <label>Thứ tự hiển thị</label>
                            <input type="number" name="sort_order" value="<?php echo esc_attr($edit_method['sort_order'] ?? 0); ?>" min="0">
                        </div>
                    </div>
                    <div class="pay-field">
                        <label>Mô tả ngắn</label>
                        <input type="text" name="description" value="<?php echo esc_attr($edit_method['description'] ?? ''); ?>" placeholder="Mô tả hiển thị cho khách">
                    </div>
                    <div class="pay-field">
                        <label>Hướng dẫn thanh toán (markdown hoặc text)</label>
                        <textarea name="instructions" rows="6" placeholder="Thông tin tài khoản, QR code URL..."><?php echo esc_textarea($edit_method['instructions'] ?? ''); ?></textarea>
                    </div>
                    <div class="pay-field">
                        <label>URL Icon/Logo</label>
                        <input type="text" name="icon_url" value="<?php echo esc_attr($edit_method['icon_url'] ?? ''); ?>" placeholder="https://...">
                    </div>
                    <div class="pay-field">
                        <label>Cấu hình thanh toán tự động (JSON - API keys, Secret...)</label>
                        <textarea name="settings" rows="4" placeholder='{"partner_code": "", "access_key": "", "secret_key": ""}' style="font-family:monospace; font-size:13px; color:#d95f47;"><?php echo esc_textarea($edit_method['settings'] ?? ''); ?></textarea>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="submit" class="wks-btn wks-btn-primary">💾 Lưu</button>
                        <a href="?page=bacera-payments&tab=methods" class="wks-btn wks-btn-secondary">Hủy</a>
                    </div>
                </form>
                <?php else: ?>
                <div style="text-align:center;padding:40px 20px;color:#78716c;">
                    <div style="font-size:48px;margin-bottom:12px;">💳</div>
                    <p style="margin:0;font-size:14px;">Chọn một phương thức để chỉnh sửa<br>hoặc nhấn <strong>+ Thêm mới</strong></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php else: ?>
        <!-- ════ PROMO CODES TAB ════ -->
        <div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:flex-start;">
            <!-- Left: list -->
            <div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                    <h2 style="margin:0;font-size:16px;font-weight:700;color:#1c1917;">Mã khuyến mãi</h2>
                    <a href="?page=bacera-payments&tab=promos&edit_promo=new" class="wks-btn wks-btn-primary wks-btn-sm">+ Thêm mã mới</a>
                </div>
                <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="background:#f5f5f4;text-align:left;">
                            <th style="padding:10px 12px;font-weight:700;color:#57534e;border-bottom:1px solid #e7e5e4;">Mã</th>
                            <th style="padding:10px 12px;font-weight:700;color:#57534e;border-bottom:1px solid #e7e5e4;">Giảm giá</th>
                            <th style="padding:10px 12px;font-weight:700;color:#57534e;border-bottom:1px solid #e7e5e4;">Lượt dùng</th>
                            <th style="padding:10px 12px;font-weight:700;color:#57534e;border-bottom:1px solid #e7e5e4;">Hạn dùng</th>
                            <th style="padding:10px 12px;font-weight:700;color:#57534e;border-bottom:1px solid #e7e5e4;">Trạng thái</th>
                            <th style="padding:10px 12px;border-bottom:1px solid #e7e5e4;"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($promos)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:40px;color:#78716c;">Chưa có mã khuyến mãi nào.</td></tr>
                    <?php else: foreach ($promos as $pr): ?>
                        <tr style="border-bottom:1px solid #f0ede8;">
                            <td style="padding:12px;">
                                <span style="font-family:monospace;font-weight:700;font-size:14px;color:#d95f47;"><?php echo esc_html($pr['code']); ?></span>
                                <?php if ($pr['description']): ?><br><small style="color:#78716c;"><?php echo esc_html(mb_substr($pr['description'], 0, 40)); ?></small><?php endif; ?>
                            </td>
                            <td style="padding:12px;">
                                <?php if ($pr['discount_type'] === 'percent'): ?>
                                    <strong><?php echo (float)$pr['discount_value']; ?>%</strong>
                                    <?php if ($pr['max_discount'] > 0): ?><br><small>tối đa <?php echo number_format($pr['max_discount'],0,',','.'); ?>đ</small><?php endif; ?>
                                <?php else: ?>
                                    <strong><?php echo number_format($pr['discount_value'],0,',','.'); ?>đ</strong>
                                <?php endif; ?>
                            </td>
                            <td style="padding:12px;">
                                <?php echo $pr['used_count']; ?><?php if ($pr['max_uses'] > 0): ?>/<?php echo $pr['max_uses']; ?><?php endif; ?>
                            </td>
                            <td style="padding:12px;">
                                <?php echo $pr['valid_to'] ? date('d/m/Y', strtotime($pr['valid_to'])) : '♾️'; ?>
                            </td>
                            <td style="padding:12px;">
                                <label class="pay-toggle">
                                    <input type="checkbox" <?php checked($pr['is_active'],1); ?>
                                           onchange="baceraTogglePromo(<?php echo $pr['id']; ?>, this.checked)">
                                    <span class="pay-slider"></span>
                                </label>
                            </td>
                            <td style="padding:12px;">
                                <a href="?page=bacera-payments&tab=promos&edit_promo=<?php echo $pr['id']; ?>" class="wks-btn wks-btn-secondary wks-btn-sm">Sửa</a>
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;" onsubmit="return confirm('Xóa mã này?')">
                                    <?php wp_nonce_field('bacera_delete_promo'); ?>
                                    <input type="hidden" name="action" value="bacera_delete_promo">
                                    <input type="hidden" name="id" value="<?php echo $pr['id']; ?>">
                                    <button type="submit" class="wks-btn wks-btn-danger wks-btn-sm">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Right: promo form -->
            <div class="pay-card">
                <?php
                $ep = $edit_promo;
                $is_new_promo = (isset($_GET['edit_promo']) && $_GET['edit_promo'] === 'new');
                // Get workshops for scope selector
                global $wpdb;
                $workshops = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}bacera_workshops ORDER BY title ASC", ARRAY_A);
                if ($is_new_promo || $ep):
                ?>
                <h3 style="margin:0 0 20px;font-size:15px;font-weight:700;"><?php echo $ep ? 'Chỉnh sửa mã' : 'Tạo mã mới'; ?></h3>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('bacera_save_promo'); ?>
                    <input type="hidden" name="action" value="bacera_save_promo">
                    <input type="hidden" name="id" value="<?php echo $ep['id'] ?? 0; ?>">

                    <div class="pay-field">
                        <label>Mã code (IN HOA) *</label>
                        <input type="text" name="code" value="<?php echo esc_attr($ep['code'] ?? ''); ?>" required placeholder="BACERA10"
                               style="text-transform:uppercase;font-family:monospace;font-size:16px;font-weight:700;color:#d95f47;">
                    </div>
                    <div class="pay-field">
                        <label>Mô tả</label>
                        <input type="text" name="description" value="<?php echo esc_attr($ep['description'] ?? ''); ?>" placeholder="Giảm 10% nhân dịp khai trương">
                    </div>
                    <div class="pay-grid">
                        <div class="pay-field">
                            <label>Loại giảm giá</label>
                            <select name="discount_type" onchange="updateDiscountLabel(this.value)">
                                <option value="percent" <?php selected($ep['discount_type']??'percent','percent'); ?>>Phần trăm (%)</option>
                                <option value="fixed"   <?php selected($ep['discount_type']??'percent','fixed'); ?>>Cố định (đ)</option>
                            </select>
                        </div>
                        <div class="pay-field">
                            <label id="dc-label">Giá trị giảm *</label>
                            <input type="number" name="discount_value" step="0.01" min="0" value="<?php echo $ep['discount_value']??''; ?>" required placeholder="10">
                        </div>
                    </div>
                    <div class="pay-grid">
                        <div class="pay-field">
                            <label>Đơn hàng tối thiểu (đ)</label>
                            <input type="number" name="min_amount" value="<?php echo intval($ep['min_amount']??0); ?>" min="0" placeholder="0 = không giới hạn">
                        </div>
                        <div class="pay-field">
                            <label>Giảm tối đa (đ)</label>
                            <input type="number" name="max_discount" value="<?php echo intval($ep['max_discount']??0); ?>" min="0" placeholder="0 = không giới hạn">
                        </div>
                    </div>
                    <div class="pay-grid">
                        <div class="pay-field">
                            <label>Số lượt dùng tối đa</label>
                            <input type="number" name="max_uses" value="<?php echo intval($ep['max_uses']??0); ?>" min="0" placeholder="0 = không giới hạn">
                        </div>
                        <div class="pay-field">
                            <label>Áp dụng cho Workshop</label>
                            <select name="workshop_id">
                                <option value="">Tất cả workshop</option>
                                <?php foreach ($workshops as $w): ?>
                                <option value="<?php echo $w['id']; ?>" <?php selected($ep['workshop_id']??0,$w['id']); ?>><?php echo esc_html($w['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="pay-grid">
                        <div class="pay-field">
                            <label>Hiệu lực từ</label>
                            <input type="datetime-local" name="valid_from" value="<?php echo $ep && $ep['valid_from'] ? date('Y-m-d\TH:i', strtotime($ep['valid_from'])) : ''; ?>">
                        </div>
                        <div class="pay-field">
                            <label>Hết hạn vào</label>
                            <input type="datetime-local" name="valid_to" value="<?php echo $ep && $ep['valid_to'] ? date('Y-m-d\TH:i', strtotime($ep['valid_to'])) : ''; ?>">
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="submit" class="wks-btn wks-btn-primary">💾 Lưu mã</button>
                        <a href="?page=bacera-payments&tab=promos" class="wks-btn wks-btn-secondary">Hủy</a>
                    </div>
                </form>
                <?php else: ?>
                <div style="text-align:center;padding:40px 20px;color:#78716c;">
                    <div style="font-size:48px;margin-bottom:12px;">🎟️</div>
                    <p style="margin:0;font-size:14px;">Chọn mã để chỉnh sửa<br>hoặc nhấn <strong>+ Thêm mã mới</strong></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        </div>

        <script>
        function baceraToggleMethod(id, active) {
            fetch(ajaxurl, {
                method: 'POST',
                body: new URLSearchParams({action:'bacera_toggle_payment_method', id:id, active:active?1:0, _ajax_nonce:'<?php echo wp_create_nonce('bacera_toggle_payment'); ?>'}),
                headers:{'Content-Type':'application/x-www-form-urlencoded'}
            });
        }
        function baceraTogglePromo(id, active) {
            fetch(ajaxurl, {
                method: 'POST',
                body: new URLSearchParams({action:'bacera_toggle_promo', id:id, active:active?1:0, _ajax_nonce:'<?php echo wp_create_nonce('bacera_toggle_promo'); ?>'}),
                headers:{'Content-Type':'application/x-www-form-urlencoded'}
            });
        }
        function updateDiscountLabel(type) {
            document.getElementById('dc-label').textContent = type === 'percent' ? 'Giá trị giảm (%)' : 'Số tiền giảm (đ)';
        }
        </script>
        <?php
    }

    /* ── SAVE PAYMENT METHOD ─────────────────────── */
    public function handleSaveMethod() {
        check_admin_referer('bacera_save_payment_method');
        if (!current_user_can('manage_options')) wp_die('No permission.');

        global $wpdb;
        $tm = $wpdb->prefix . 'bacera_payment_methods';
        $id = intval($_POST['id'] ?? 0);

        $data = [
            'name'         => sanitize_text_field($_POST['name'] ?? ''),
            'code'         => sanitize_key($_POST['code'] ?? ''),
            'description'  => sanitize_text_field($_POST['description'] ?? ''),
            'instructions' => sanitize_textarea_field($_POST['instructions'] ?? ''),
            'icon_url'     => esc_url_raw($_POST['icon_url'] ?? ''),
            'settings'     => stripslashes($_POST['settings'] ?? ''),
            'sort_order'   => intval($_POST['sort_order'] ?? 0),
        ];

        if ($id) {
            $wpdb->update($tm, $data, ['id' => $id]);
        } else {
            $data['is_active'] = 1;
            $wpdb->insert($tm, $data);
        }

        wp_redirect(admin_url('admin.php?page=bacera-payments&tab=methods&saved=1'));
        exit;
    }

    public function handleDeleteMethod() {
        check_admin_referer('bacera_delete_payment_method');
        if (!current_user_can('manage_options')) wp_die('No permission.');

        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'bacera_payment_methods', ['id' => intval($_POST['id'])]);

        wp_redirect(admin_url('admin.php?page=bacera-payments&tab=methods'));
        exit;
    }

    public function ajaxToggleMethod() {
        check_ajax_referer('bacera_toggle_payment');
        if (!current_user_can('manage_options')) wp_send_json_error();

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'bacera_payment_methods',
            ['is_active' => intval($_POST['active'])],
            ['id' => intval($_POST['id'])]
        );
        wp_send_json_success();
    }

    /* ── SAVE PROMO CODE ─────────────────────────── */
    public function handleSavePromo() {
        check_admin_referer('bacera_save_promo');
        if (!current_user_can('manage_options')) wp_die('No permission.');

        global $wpdb;
        $tp = $wpdb->prefix . 'bacera_promo_codes';
        $id = intval($_POST['id'] ?? 0);

        $vf = sanitize_text_field($_POST['valid_from'] ?? '');
        $vt = sanitize_text_field($_POST['valid_to']   ?? '');

        $data = [
            'code'           => strtoupper(sanitize_text_field($_POST['code'] ?? '')),
            'description'    => sanitize_text_field($_POST['description'] ?? ''),
            'discount_type'  => sanitize_key($_POST['discount_type'] ?? 'percent'),
            'discount_value' => (float)($_POST['discount_value'] ?? 0),
            'min_amount'     => (float)($_POST['min_amount'] ?? 0),
            'max_discount'   => (float)($_POST['max_discount'] ?? 0),
            'max_uses'       => intval($_POST['max_uses'] ?? 0),
            'workshop_id'    => intval($_POST['workshop_id'] ?? 0) ?: null,
            'valid_from'     => $vf ? date('Y-m-d H:i:s', strtotime($vf)) : null,
            'valid_to'       => $vt ? date('Y-m-d H:i:s', strtotime($vt)) : null,
        ];

        if ($id) {
            $wpdb->update($tp, $data, ['id' => $id]);
        } else {
            $data['is_active']  = 1;
            $data['used_count'] = 0;
            $wpdb->insert($tp, $data);
        }

        wp_redirect(admin_url('admin.php?page=bacera-payments&tab=promos&saved=1'));
        exit;
    }

    public function handleDeletePromo() {
        check_admin_referer('bacera_delete_promo');
        if (!current_user_can('manage_options')) wp_die('No permission.');

        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'bacera_promo_codes', ['id' => intval($_POST['id'])]);

        wp_redirect(admin_url('admin.php?page=bacera-payments&tab=promos'));
        exit;
    }

    public function ajaxTogglePromo() {
        check_ajax_referer('bacera_toggle_promo');
        if (!current_user_can('manage_options')) wp_send_json_error();

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'bacera_promo_codes',
            ['is_active' => intval($_POST['active'])],
            ['id' => intval($_POST['id'])]
        );
        wp_send_json_success();
    }
}
?>
