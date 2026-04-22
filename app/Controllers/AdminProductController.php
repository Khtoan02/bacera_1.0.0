<?php
namespace Bacera\Controllers;

/**
 * AdminProductController — Hybrid product management.
 * Pancake POS: name, price, SKU, short desc, photo.
 * Bacera DB   : long content, gallery, reviews, category images.
 */
class AdminProductController {

    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'register_menu' ] );
        add_action( 'admin_menu',            [ $this, 'hide_plugin_menu' ], 999 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_bprod_sync_products',   [ $this, 'ajax_sync_products' ] );
        add_action( 'wp_ajax_bprod_sync_categories', [ $this, 'ajax_sync_categories' ] );
        add_action( 'wp_ajax_bprod_debug_api',       [ $this, 'ajax_debug_api' ] );
        add_action( 'wp_ajax_bprod_add_images',      [ $this, 'ajax_add_images' ] );
        add_action( 'wp_ajax_bprod_cat_set_image',    [ $this, 'ajax_cat_set_image' ] );
        add_action( 'admin_post_bprod_save_meta',    [ $this, 'post_save_meta' ] );
        add_action( 'admin_post_bprod_add_image',    [ $this, 'post_add_image' ] );
        add_action( 'admin_post_bprod_delete_image', [ $this, 'post_delete_image' ] );
        add_action( 'admin_post_bprod_save_category',[ $this, 'post_save_category' ] );
        add_action( 'admin_post_bprod_review_action',[ $this, 'post_review_action' ] );
    }

    public function hide_plugin_menu(): void { remove_menu_page( 'bacera-pancake-settings' ); }

    public function register_menu(): void {
        add_submenu_page( 'bacera-main', 'Sản phẩm', 'Sản phẩm', 'manage_options', 'bacera-products', [ $this, 'dispatch' ] );
    }

    public function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'bacera-products' ) === false ) return;
        wp_enqueue_media();
        wp_enqueue_script( 'jquery' );
        // Google Fonts
        wp_enqueue_style( 'bprod-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap', [], null );
    }

    /* ── Router ─────────────────────────────────────────────────────────── */

    public function dispatch(): void {
        $action = sanitize_key( $_GET['action'] ?? '' );
        $tab    = sanitize_key( $_GET['tab']    ?? 'products' );
        match ( true ) {
            $action === 'edit_product'  => $this->page_edit_product( sanitize_text_field( $_GET['pid'] ?? '' ) ),
            $action === 'edit_category' => $this->page_edit_category( sanitize_text_field( $_GET['cid'] ?? '' ) ),
            $tab    === 'categories'    => $this->page_categories(),
            $tab    === 'reviews'       => $this->page_reviews(),
            default                     => $this->page_products(),
        };
    }

    /* ═══════════════════════════════════════════════════
       API HELPERS
    ═══════════════════════════════════════════════════ */

    private function api_ready(): bool {
        return class_exists( 'Pancake_API_Client' )
            && ! empty( get_option( 'bacera_pancake_api_key' ) )
            && ! empty( get_option( 'bacera_pancake_shop_id' ) );
    }

    private function api_raw( string $endpoint, array $params = [] ) {
        if ( ! $this->api_ready() ) return false;
        $api = new \Pancake_API_Client();
        if ( $params ) $endpoint .= ( strpos( $endpoint, '?' ) !== false ? '&' : '?' ) . http_build_query( $params );
        return $api->request( $endpoint, 'GET' );
    }

    private function normalize_response( $resp ): array {
        if ( ! is_array( $resp ) ) return [];
        if ( isset( $resp[0] ) ) return $resp;
        $keys = [ 'data', 'product_categories', 'categories', 'products', 'variations', 'items', 'records', 'result' ];
        foreach ( $keys as $k ) {
            if ( isset( $resp[$k] ) && is_array( $resp[$k] ) && !empty( $resp[$k] ) ) return $resp[$k];
        }
        return [];
    }

    private function api_get( string $endpoint, array $params = [] ): array {
        return $this->normalize_response( $this->api_raw( $endpoint, $params ) );
    }

    private function fetch_products( array $params = [] ): array {
        $raw = $this->api_get( '/shops/{SHOP_ID}/products', $params );
        return array_map( [ $this, 'normalize_product' ], $raw );
    }

    private function fetch_product( string $id ): array {
        $resp = $this->api_raw( '/shops/{SHOP_ID}/products/' . urlencode( $id ) );
        if ( ! is_array( $resp ) ) return [];
        if ( isset( $resp['data'] ) && is_array( $resp['data'] ) ) return $this->normalize_product( $resp['data'] );
        if ( isset( $resp['id'] ) ) return $this->normalize_product( $resp );
        $list = $this->normalize_response( $resp );
        return !empty( $list ) ? $this->normalize_product( (array) $list[0] ) : [];
    }

    private function fetch_categories( bool $force = false ): array {
        $cached = $force ? false : get_transient( 'bprod_categories' );
        if ( false !== $cached && is_array( $cached ) ) return $cached;
        if ( ! $this->api_ready() ) return [];
        $raw  = $this->normalize_response( $this->api_raw( '/shops/{SHOP_ID}/categories' ) );
        $cats = array_map( [ $this, 'normalize_category' ], $raw );
        set_transient( 'bprod_categories', $cats, 10 * MINUTE_IN_SECONDS );
        return $cats;
    }

    private function normalize_category( array $c ): array {
        return [
            'id'    => (string)( $c['id'] ?? '' ),
            'name'  => (string)( $c['text'] ?? $c['name'] ?? $c['title'] ?? '' ),
            'slug'  => sanitize_title( $c['text'] ?? $c['name'] ?? (string)($c['id']??'') ),
            'nodes' => (array)( $c['nodes'] ?? [] ),
            '_raw'  => $c,
        ];
    }

    private function normalize_product( array $p ): array {
        $thumb = '';
        if ( !empty($p['image']) && is_string($p['image']) ) {
            $thumb = $p['image'];
        } elseif ( !empty($p['image_path']) ) {
            $thumb = $p['image_path'];
        } elseif ( !empty($p['variations']) ) {
            foreach ( $p['variations'] as $v ) {
                $imgs = $v['images'] ?? [];
                if ( is_array($imgs) && !empty($imgs[0]) ) {
                    $thumb = is_string($imgs[0]) ? $imgs[0] : ($imgs[0]['url'] ?? $imgs[0]['path'] ?? '');
                    break;
                }
            }
        }
        $cat_ids = [];
        if ( !empty($p['categories']) && is_array($p['categories']) ) {
            $cat_ids = array_map( fn($c) => (string)($c['id']??''), $p['categories'] );
        } elseif ( !empty($p['category_ids']) ) {
            $cat_ids = array_map('strval', (array)$p['category_ids']);
        }
        return $p + [ '_thumb' => $thumb, '_cat_ids' => $cat_ids ];
    }

    /* ═══════════════════════════════════════════════════
       DB HELPERS
    ═══════════════════════════════════════════════════ */

    private function db_get_product_meta( string $pid ): array {
        global $wpdb;
        return (array)( $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bacera_product_meta WHERE pancake_product_id=%s", $pid
        ), ARRAY_A ) ?? [] );
    }

    private function db_get_product_images( string $pid ): array {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bacera_product_images WHERE pancake_product_id=%s ORDER BY is_primary DESC, sort_order ASC, id ASC", $pid
        ), ARRAY_A ) ?: [];
    }

    private function db_get_category_meta( string $cid ): array {
        global $wpdb;
        return (array)( $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bacera_category_meta WHERE pancake_category_id=%s", $cid
        ), ARRAY_A ) ?? [] );
    }

    private function db_upsert( string $table, array $data, string $pk_col, string $pk_val ): void {
        global $wpdb;
        $t      = $wpdb->prefix . $table;
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $t WHERE $pk_col = %s", $pk_val ) );
        $data['updated_at'] = current_time('mysql');
        if ( $exists ) {
            $wpdb->update( $t, $data, [ $pk_col => $pk_val ] );
        } else {
            $data[$pk_col]       = $pk_val;
            $data['created_at']  = current_time('mysql');
            $wpdb->insert( $t, $data );
        }
    }

    /* ═══════════════════════════════════════════════════
       PAGE: PRODUCTS LIST
    ═══════════════════════════════════════════════════ */

    private function page_products(): void {
        global $wpdb;
        $api_ok  = $this->api_ready();
        $search  = sanitize_text_field( $_GET['s']      ?? '' );
        $cat_id  = sanitize_text_field( $_GET['cat_id'] ?? '' );
        $paged   = max(1, intval( $_GET['paged'] ?? 1 ));
        $per_pg  = 20;

        $params   = [ 'page' => $paged, 'per_page' => $per_pg ];
        if ( $search ) $params['name']        = $search;
        if ( $cat_id ) $params['category_id'] = $cat_id;

        $products   = $api_ok ? $this->fetch_products( $params ) : [];
        $categories = $this->fetch_categories();

        // Local metadata lookup: which product IDs have content in our DB?
        $local_ids   = [];
        $total_local = 0;
        if ( !empty($products) ) {
            $pids = array_filter( array_map( fn($p) => (string)($p['id'] ?? ''), $products ) );
            if ( $pids ) {
                $ph        = implode( ',', array_fill( 0, count($pids), '%s' ) );
                $ids_found = $wpdb->get_col( $wpdb->prepare(
                    "SELECT pancake_product_id FROM {$wpdb->prefix}bacera_product_meta WHERE pancake_product_id IN ($ph)", ...$pids
                ) );
                $local_ids = array_values( $ids_found ?: [] );
            }
            $total_local = (int)$wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}bacera_product_meta"
            );
        }

        // Category id -> name map for grid/table view
        $cat_map = [];
        foreach ( $categories as $c ) {
            $cat_map[ (string)$c['id'] ] = $c['name'];
        }

        $this->ui_shell( 'products' );
        ?>

        <!-- ── STATS ROW ── -->
        <div class="b-stats">
            <div class="b-stat b-stat-anim" style="--delay:0ms">
                <div class="b-stat-icon" style="background:linear-gradient(135deg,#c47c3a,#d97706);"
                     role="img" aria-label="Sản phẩm">🏺</div>
                <div>
                    <div class="b-stat-val"><?php echo count($products) ?: '–'; ?></div>
                    <div class="b-stat-lbl">Trang này</div>
                </div>
            </div>
            <div class="b-stat b-stat-anim" style="--delay:60ms">
                <div class="b-stat-icon" style="background:linear-gradient(135deg,#7c5c3a,#a37042);">🏷️</div>
                <div>
                    <div class="b-stat-val"><?php echo count($categories); ?></div>
                    <div class="b-stat-lbl">Danh mục</div>
                </div>
            </div>
            <div class="b-stat b-stat-anim" style="--delay:120ms">
                <div class="b-stat-icon" style="background:linear-gradient(135deg,#059669,#10b981);">✍️</div>
                <div>
                    <div class="b-stat-val" style="color:var(--b-green);"><?php echo $total_local; ?></div>
                    <div class="b-stat-lbl">Có nội dung</div>
                </div>
            </div>
            <div class="b-stat b-stat-anim" style="--delay:180ms">
                <div class="b-stat-icon" style="background:<?php echo $api_ok?'linear-gradient(135deg,#059669,#10b981)':'linear-gradient(135deg,#dc2626,#ef4444)';?>;"><?php echo $api_ok?'🔗':'⚠️';?></div>
                <div>
                    <div class="b-stat-val" style="font-size:14px;color:<?php echo $api_ok?'var(--b-green)':'var(--b-red)';?>;margin-top:4px;letter-spacing:-.2px;"><?php echo $api_ok?'Đã kết nối':'Chưa kết nối';?></div>
                    <div class="b-stat-lbl">Pancake API</div>
                </div>
            </div>
        </div>

        <!-- ── TOOLBAR ── -->
        <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" id="b-products-filter">
            <input type="hidden" name="page" value="bacera-products">
            <div class="b-toolbar">
                <div class="b-toolbar-left">
                    <div class="b-search-box">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        <input type="text" name="s" id="b-prod-search" value="<?php echo esc_attr($search); ?>" placeholder="Tìm theo tên sản phẩm..." class="b-search-input" autocomplete="off">
                        <?php if ($search): ?><button type="button" class="b-search-clear" onclick="document.getElementById('b-prod-search').value='';this.closest('form').submit();">✕</button><?php endif; ?>
                    </div>
                    <div class="b-select-wrap">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M8 12h8M12 18h0"/></svg>
                        <select name="cat_id" class="b-select" onchange="this.form.submit()">
                            <option value="">Tất cả danh mục</option>
                            <?php foreach($categories as $c): ?>
                            <option value="<?php echo esc_attr($c['id']); ?>" <?php selected($cat_id,$c['id']); ?>><?php echo esc_html($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="b-toolbar-right">
                    <?php if ($search || $cat_id): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=bacera-products')); ?>" class="b-btn b-btn-ghost b-btn-sm">
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg> Bỏ lọc
                    </a>
                    <?php endif; ?>
                    <button type="submit" class="b-btn b-btn-outline b-btn-sm">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg> Tìm kiếm
                    </button>
                    <div class="b-view-toggle">
                        <button type="button" class="b-view-btn is-active" id="b-view-table" title="Dạng bảng">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/></svg>
                        </button>
                        <button type="button" class="b-view-btn" id="b-view-grid" title="Dạng lưới">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M4 4h6v6H4V4zm10 0h6v6h-6V4zm0 10h6v6h-6v-6zM4 14h6v6H4v-6z"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <?php if ( !$api_ok ): ?>
        <div class="b-notice b-notice-warn">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m10.29 3.86-8.16 14.14a1 1 0 0 0 .87 1.5H21a1 1 0 0 0 .87-1.5L13.71 3.86a1 1 0 0 0-1.73 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <span>Chưa cấu hình Pancake API. <a href="<?php echo admin_url('admin.php?page=bacera-config'); ?>">Cấu hình ngay →</a></span>
        </div>
        <?php elseif ( empty($products) ): ?>
        <div class="b-empty-state">
            <div class="b-empty-icon">🔍</div>
            <div class="b-empty-title">Không tìm thấy sản phẩm</div>
            <div class="b-empty-sub">Thử thay đổi bộ lọc hoặc nhấn <strong>Sync Sản phẩm</strong> để tải về từ Pancake.</div>
        </div>
        <?php else: ?>

        <!-- ── TABLE VIEW ── -->
        <div class="b-table-card" id="b-prod-table">
            <div class="b-table-header">
                <div class="b-table-title">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h5M16 12h5"/></svg>
                    Danh sách sản phẩm
                    <span class="b-count-badge"><?php echo count($products); ?> sản phẩm</span>
                </div>
                <div class="b-table-meta">Trang <?php echo $paged; ?><?php echo ($search||$cat_id)?' · Đang lọc':''; ?></div>
            </div>
            <table class="b-table">
                <thead>
                    <tr>
                        <th class="b-th-thumb"></th>
                        <th>Sản phẩm</th>
                        <th class="b-th-sku">SKU</th>
                        <th class="b-th-price">Giá bán</th>
                        <th class="b-th-cat">Danh mục</th>
                        <th class="b-th-content">Nội dung</th>
                        <th class="b-th-status">Trạng thái</th>
                        <th class="b-th-action">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $products as $idx => $p ):
                    $pid       = (string)($p['id']??'');
                    $name      = (string)($p['name']??'');
                    $sku       = (string)($p['custom_id']??$p['sku']??'');
                    $img       = (string)($p['_thumb']??'');
                    $pub       = (bool)($p['is_published']??false);
                    $note      = (string)($p['note_product']??$p['note']??'');
                    $has_local = in_array($pid, $local_ids, true);
                    $var_count = count($p['variations']??[]);
                    $edit_url  = admin_url('admin.php?page=bacera-products&action=edit_product&pid='.urlencode($pid));

                    $prices = [];
                    if (!empty($p['variations']) && is_array($p['variations'])) {
                        $prices = array_filter(array_map(fn($v)=>is_numeric($v['retail_price']??null)?(float)$v['retail_price']:null, $p['variations']));
                    }
                    $price_html = '<span class="b-price-dash">—</span>';
                    if ($prices) {
                        $mn = min($prices); $mx = max($prices);
                        $price_html = '<span class="b-price">'.number_format($mn,0,',','.').'₫</span>';
                        if ($mn !== $mx) $price_html .= '<span class="b-price-range"> – '.number_format($mx,0,',','.').'₫</span>';
                    }

                    $cat_html = '';
                    foreach ($p['_cat_ids']??[] as $ci) {
                        $cn = $cat_map[(string)$ci]??'';
                        if ($cn) $cat_html .= '<span class="b-cat-badge">'.esc_html($cn).'</span>';
                    }
                ?>
                <tr class="b-tr" style="--row-delay:<?php echo $idx * 30; ?>ms">
                    <td class="b-td b-td-thumb">
                        <a href="<?php echo esc_url($edit_url); ?>" class="b-thumb-link">
                            <?php if ($img): ?>
                            <div class="b-thumb" style="background-image:url('<?php echo esc_url($img); ?>')"></div>
                            <?php else: ?>
                            <div class="b-thumb b-thumb-empty">🏺</div>
                            <?php endif; ?>
                        </a>
                    </td>
                    <td class="b-td b-td-name">
                        <a href="<?php echo esc_url($edit_url); ?>" class="b-product-name">
                            <?php echo $name !== '' ? esc_html($name) : '<em class="b-no-name">Chưa đặt tên</em>'; ?>
                        </a>
                        <?php if ($note): ?>
                        <div class="b-product-note"><?php echo esc_html(mb_strimwidth(strip_tags($note),0,80,'…')); ?></div>
                        <?php endif; ?>
                        <?php if ($var_count > 1): ?>
                        <div class="b-var-count"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="6" height="6" rx="1"/><rect x="10" y="3" width="6" height="6" rx="1"/><rect x="2" y="13" width="6" height="6" rx="1"/></svg> <?php echo $var_count; ?> biến thể</div>
                        <?php endif; ?>
                    </td>
                    <td class="b-td b-td-sku"><code class="b-sku"><?php echo $sku ? esc_html($sku) : '—'; ?></code></td>
                    <td class="b-td b-td-price"><?php echo $price_html; ?></td>
                    <td class="b-td b-td-cats"><?php echo $cat_html ?: '<span class="b-no-cat">—</span>'; ?></td>
                    <td class="b-td b-td-content">
                        <?php if ($has_local): ?>
                        <span class="b-badge b-badge-success">
                            <svg width="9" height="9" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Có nội dung
                        </span>
                        <?php else: ?>
                        <a href="<?php echo esc_url($edit_url); ?>" class="b-badge b-badge-warn">
                            <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                            Thêm mới
                        </a>
                        <?php endif; ?>
                    </td>
                    <td class="b-td b-td-status">
                        <?php if ($pub): ?>
                        <span class="b-status b-status-live"><span class="b-pulse"></span>Đang bán</span>
                        <?php else: ?>
                        <span class="b-status b-status-draft">Nháp</span>
                        <?php endif; ?>
                    </td>
                    <td class="b-td b-td-action">
                        <a href="<?php echo esc_url($edit_url); ?>" class="b-action-btn" title="Chỉnh sửa sản phẩm">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            <span>Sửa</span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- ── CARD GRID VIEW (hidden by default) ── -->
        <div class="b-prod-grid" id="b-prod-grid" style="display:none;">
            <?php foreach ( $products as $idx => $p ):
                $pid      = (string)($p['id']??'');
                $name     = (string)($p['name']??'');
                $img      = (string)($p['_thumb']??'');
                $pub      = (bool)($p['is_published']??false);
                $has_local= in_array($pid, $local_ids, true);
                $edit_url = admin_url('admin.php?page=bacera-products&action=edit_product&pid='.urlencode($pid));
                $prices   = [];
                if (!empty($p['variations']) && is_array($p['variations'])) {
                    $prices = array_filter(array_map(fn($v)=>is_numeric($v['retail_price']??null)?(float)$v['retail_price']:null, $p['variations']));
                }
            ?>
            <div class="b-prod-card" style="--card-delay:<?php echo $idx * 40; ?>ms">
                <a href="<?php echo esc_url($edit_url); ?>" class="b-prod-card-img-wrap">
                    <?php if ($img): ?>
                    <div class="b-prod-card-img" style="background-image:url('<?php echo esc_url($img); ?>')"></div>
                    <?php else: ?>
                    <div class="b-prod-card-img b-prod-card-img-empty">🏺</div>
                    <?php endif; ?>
                    <div class="b-prod-card-overlay">
                        <span class="b-overlay-edit">Chỉnh sửa →</span>
                    </div>
                    <?php if ($pub): ?>
                    <div class="b-prod-card-badge b-prod-card-badge-live">Đang bán</div>
                    <?php else: ?>
                    <div class="b-prod-card-badge b-prod-card-badge-draft">Nháp</div>
                    <?php endif; ?>
                    <?php if ($has_local): ?>
                    <div class="b-prod-card-content-dot" title="Có nội dung">✓</div>
                    <?php endif; ?>
                </a>
                <div class="b-prod-card-body">
                    <a href="<?php echo esc_url($edit_url); ?>" class="b-prod-card-name"><?php echo $name ?: '<em>Chưa đặt tên</em>'; ?></a>
                    <?php if ($prices): ?>
                    <div class="b-prod-card-price"><?php echo number_format(min($prices),0,',','.').'₫'; ?><?php if(count(array_unique($prices))>1) echo '<span class="b-prod-card-price-range"> – '.number_format(max($prices),0,',','.').'₫</span>'; ?></div>
                    <?php endif; ?>
                    <?php $cat_names = array_filter(array_map(fn($ci)=>$cat_map[(string)$ci]??'', $p['_cat_ids']??[])); ?>
                    <?php if ($cat_names): ?>
                    <div class="b-prod-card-cats"><?php foreach(array_slice($cat_names,0,2) as $cn) echo '<span class="b-cat-badge-sm">'.esc_html($cn).'</span>'; ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ── PAGINATION ── -->
        <?php if ($paged > 1 || count($products) === $per_pg): ?>
        <div class="b-pagination">
            <?php if ($paged > 1): ?>
            <a href="<?php echo esc_url(add_query_arg(['paged'=>$paged-1]+$_GET, admin_url('admin.php'))); ?>" class="b-page-btn" title="Trang trước">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <?php endif; ?>
            <span class="b-page-info">Trang <strong><?php echo $paged; ?></strong></span>
            <?php if (count($products) === $per_pg): ?>
            <a href="<?php echo esc_url(add_query_arg(['paged'=>$paged+1]+$_GET, admin_url('admin.php'))); ?>" class="b-page-btn" title="Trang sau">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
            </a>
            <?php endif; ?>
        </div>
        <?php endif;
        endif;
        ?>
        <script>
        (function(){
            var TABLE = document.getElementById('b-prod-table');
            var GRID  = document.getElementById('b-prod-grid');
            var btnT  = document.getElementById('b-view-table');
            var btnG  = document.getElementById('b-view-grid');
            if (!TABLE || !GRID || !btnT || !btnG) return;

            function setView(v) {
                if (v === 'grid') {
                    TABLE.style.display = 'none';
                    GRID.style.display  = '';
                    btnT.classList.remove('is-active');
                    btnG.classList.add('is-active');
                } else {
                    GRID.style.display  = 'none';
                    TABLE.style.display = '';
                    btnG.classList.remove('is-active');
                    btnT.classList.add('is-active');
                }
                try { localStorage.setItem('bacera_prod_view', v); } catch(e){}
            }

            // Restore preference
            try {
                var pref = localStorage.getItem('bacera_prod_view');
                if (pref === 'grid') setView('grid');
            } catch(e){}

            btnT.addEventListener('click', function(){ setView('table'); });
            btnG.addEventListener('click', function(){ setView('grid'); });
        })();
        </script>
        <?php
        $this->ui_close();
    }

    /* ═══════════════════════════════════════════════════
       PAGE: CATEGORIES
    ═══════════════════════════════════════════════════ */

    private function page_categories(): void {
        global $wpdb;
        $categories = $this->fetch_categories();

        $meta_all = [];
        if ($categories) {
            $cids = array_filter(array_map(fn($c)=>$c['id'], $categories));
            if ($cids) {
                $ph   = implode(',', array_fill(0,count($cids),'%s'));
                $rows = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}bacera_category_meta WHERE pancake_category_id IN ($ph)", ...$cids
                ), ARRAY_A);
                foreach ($rows as $r) $meta_all[$r['pancake_category_id']] = $r;
            }
        }

        $with_img    = count(array_filter($categories, fn($c)=>!empty($meta_all[$c['id']]['image_url'])));
        $without_img = count($categories) - $with_img;

        $this->ui_shell('categories');
        ?>

        <!-- ── STATS ── -->
        <div class="b-stats">
            <div class="b-stat b-stat-anim" style="--delay:0ms">
                <div class="b-stat-icon" style="background:linear-gradient(135deg,#c47c3a,#d97706);">🏷️</div>
                <div><div class="b-stat-val"><?php echo count($categories); ?></div><div class="b-stat-lbl">Tổng danh mục</div></div>
            </div>
            <div class="b-stat b-stat-anim" style="--delay:60ms">
                <div class="b-stat-icon" style="background:linear-gradient(135deg,#059669,#10b981);">🖼️</div>
                <div><div class="b-stat-val" style="color:var(--b-green);"><?php echo $with_img; ?></div><div class="b-stat-lbl">Đã có ảnh</div></div>
            </div>
            <div class="b-stat b-stat-anim" style="--delay:120ms">
                <div class="b-stat-icon" style="background:linear-gradient(135deg,#dc2626,#f87171);">📷</div>
                <div><div class="b-stat-val" style="color:var(--b-red);"><?php echo $without_img; ?></div><div class="b-stat-lbl">Chưa có ảnh</div></div>
            </div>
        </div>

        <div class="b-info-callout">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>Pancake POS không lưu hình ảnh danh mục. Thiết lập <strong>ảnh đại diện</strong>, <strong>banner</strong> và <strong>mô tả</strong> tại đây để hiển thị trên website.</span>
        </div>

        <?php if (empty($categories)): ?>
        <?php if ($this->api_ready()): ?>
        <div class="b-notice b-notice-warn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m10.29 3.86-8.16 14.14a1 1 0 0 0 .87 1.5H21a1 1 0 0 0 .87-1.5L13.71 3.86a1 1 0 0 0-1.73 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <span>Không lấy được danh mục. Nhấn <strong>Sync Danh mục</strong> để thử lại.</span>
        </div>
        <?php endif; ?>
        <div class="b-empty-state">
            <div class="b-empty-icon">🏷️</div>
            <div class="b-empty-title">Chưa có danh mục</div>
            <div class="b-empty-sub">Nhấn <strong>Sync Danh mục</strong> bên trên để tải về từ Pancake POS.</div>
        </div>
        <?php else: ?>

        <!-- ── CATEGORY LIST ── -->
        <div class="b-cat-list">
            <div class="b-cat-list-header">
                <div class="b-cat-list-title">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="4" height="18" rx="1"/><path d="M9 7h12M9 12h12M9 17h7"/></svg>
                    Danh sách danh mục
                    <span class="b-count-badge"><?php echo count($categories); ?></span>
                </div>
                <div class="b-cat-list-legend">
                    <span class="b-legend-item"><span class="b-legend-dot" style="background:var(--b-green);"></span> Có ảnh</span>
                    <span class="b-legend-item"><span class="b-legend-dot" style="background:#fbbf24;"></span> Chưa có ảnh</span>
                </div>
            </div>

            <?php foreach ($categories as $idx => $c):
                $cid    = $c['id'];
                $cname  = $c['name'];
                $m      = $meta_all[$cid] ?? [];
                $img    = $m['image_url'] ?? '';
                $banner = $m['banner_url'] ?? '';
                $desc   = $m['description'] ?? '';
                $feat   = !empty($m['is_featured']);
                $active = !isset($m['is_active']) || !empty($m['is_active']);
                $slug   = $m['slug'] ?? sanitize_title($cname);
                $name_override = $m['name_override'] ?? '';
                $edit   = admin_url('admin.php?page=bacera-products&action=edit_category&cid='.urlencode($cid));
            ?>
            <div class="b-cat-row <?php echo !$active ? 'b-cat-row-inactive' : ''; ?>" style="--row-delay:<?php echo $idx * 40; ?>ms">
                <!-- Thumbnail: clickable inline image picker -->
                <div class="b-cat-row-thumb">
                    <div class="ci-thumb-wrap" data-cid="<?php echo esc_attr($cid); ?>"
                         data-nonce="<?php echo esc_attr(wp_create_nonce('bprod_cat_image_'.$cid)); ?>"
                         title="Nhấn để đổi ảnh đại diện">
                        <?php if ($img): ?>
                        <div class="b-cat-thumb ci-thumb" id="ci-thumb-<?php echo esc_attr($cid); ?>"
                             style="background-image:url('<?php echo esc_url($img); ?>')"></div>
                        <?php else: ?>
                        <div class="b-cat-thumb b-cat-thumb-empty ci-thumb" id="ci-thumb-<?php echo esc_attr($cid); ?>">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M3 7h4l2-3h6l2 3h4a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1z"/><circle cx="12" cy="13" r="3"/></svg>
                        </div>
                        <?php endif; ?>
                        <div class="ci-thumb-hint">
                            <svg width="12" height="12" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 0 2-2l1-3h4l1 3h10l1-3h4l1 3a2 2 0 0 0 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        </div>
                        <div class="ci-thumb-saving" id="ci-saving-<?php echo esc_attr($cid); ?>" style="display:none;">
                            <div class="ci-spinner"></div>
                        </div>
                    </div>
                </div>

                <!-- Info -->
                <div class="b-cat-row-info">
                    <div class="b-cat-row-top">
                        <div class="b-cat-row-name">
                            <?php echo esc_html($name_override ?: $cname); ?>
                            <?php if ($name_override && $name_override !== $cname): ?>
                            <span class="b-cat-row-orig">(Pancake: <?php echo esc_html($cname); ?>)</span>
                            <?php endif; ?>
                        </div>
                        <div class="b-cat-row-badges">
                            <?php if ($feat): ?><span class="b-badge b-badge-featured">★ Nổi bật</span><?php endif; ?>
                            <?php if (!$active): ?><span class="b-badge b-badge-inactive">Ẩn</span><?php endif; ?>
                            <?php if ($img): ?><span class="b-badge b-badge-success"><svg width="8" height="8" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Ảnh</span><?php else: ?><span class="b-badge b-badge-warn">Thiếu ảnh</span><?php endif; ?>
                            <?php if ($banner): ?><span class="b-badge b-badge-info">Banner ✓</span><?php endif; ?>
                        </div>
                    </div>
                    <div class="b-cat-row-meta">
                        <span class="b-cat-row-meta-item">
                            <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                            ID: <code>#<?php echo esc_html($cid); ?></code>
                        </span>
                        <?php if ($slug): ?>
                        <span class="b-cat-row-meta-item">
                            <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                            /<?php echo esc_html($slug); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($desc): ?>
                        <span class="b-cat-row-desc"><?php echo esc_html(mb_strimwidth($desc,0,80,'…')); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Banner preview strip -->
                <?php if ($banner): ?>
                <div class="b-cat-row-banner" style="background-image:url('<?php echo esc_url($banner); ?>')"
                     title="Banner: <?php echo esc_attr($cname); ?>"></div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="b-cat-row-actions">
                    <a href="<?php echo esc_url($edit); ?>" class="b-action-btn b-action-btn-primary">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Chỉnh sửa
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=bacera-products&cat_id='.urlencode($cid))); ?>" class="b-action-btn" title="Xem sản phẩm trong danh mục">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                        Sản phẩm
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Inline image picker styles -->
        <style>
        .ci-thumb-wrap{
            position:relative; cursor:pointer; display:inline-block;
            border-radius:10px; overflow:hidden;
        }
        .ci-thumb-wrap:hover .b-cat-thumb{ filter:brightness(.7); }
        .ci-thumb-wrap:hover .ci-thumb-hint{ opacity:1; }
        .ci-thumb-hint{
            position:absolute; inset:0;
            display:flex; align-items:center; justify-content:center;
            background:rgba(30,15,5,.45);
            border-radius:10px; opacity:0; transition:opacity .18s;
            pointer-events:none;
        }
        .ci-thumb-saving{
            position:absolute; inset:0;
            background:rgba(30,15,5,.55);
            display:flex; align-items:center; justify-content:center;
            border-radius:10px;
        }
        .ci-spinner{
            width:20px; height:20px; border-radius:50%;
            border:2.5px solid rgba(255,255,255,.3);
            border-top-color:#fff;
            animation:spin .7s linear infinite;
        }
        /* Success flash */
        .ci-thumb.ci-saved{ animation:ciFlash .5s ease; }
        @keyframes ciFlash{
            0%,100%{box-shadow:none;}
            50%{box-shadow:0 0 0 4px rgba(5,150,105,.5);}
        }
        </style>

        <!-- Inline image picker JS -->
        <script>
        jQuery(function($){
            var frames = {};
            var aj = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';

            $(document).on('click', '.ci-thumb-wrap', function(){
                var $wrap = $(this);
                var cid   = $wrap.data('cid');
                var nonce = $wrap.data('nonce');
                if (!cid) return;

                if (frames[cid]) { frames[cid].open(); return; }

                frames[cid] = wp.media({
                    title  : 'Chọn ảnh đại diện — ' + $wrap.closest('.b-cat-row').find('.b-cat-row-name').text().trim(),
                    button : { text: 'Đặt làm ảnh đại diện' },
                    multiple: false
                });

                frames[cid].on('select', function(){
                    var att  = frames[cid].state().get('selection').first().toJSON();
                    var url  = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
                    var full = att.url;
                    var $saving = $('#ci-saving-' + cid);
                    var $thumb  = $('#ci-thumb-'  + cid);

                    $saving.show();

                    $.ajax({
                        url: aj, method: 'POST',
                        data: { action:'bprod_cat_set_image', cid:cid, nonce:nonce, url:full, attid:att.id },
                        success: function(r){
                            $saving.hide();
                            if (r.success) {
                                // Update thumbnail
                                $thumb
                                    .css('background-image', 'url(' + full + ')')
                                    .removeClass('b-cat-thumb-empty')
                                    .find('svg').remove();
                                $thumb.addClass('ci-saved');
                                setTimeout(function(){ $thumb.removeClass('ci-saved'); }, 600);
                                // Update badge
                                var $badges = $wrap.closest('.b-cat-row').find('.b-cat-row-badges');
                                $badges.find('.b-badge-warn').replaceWith(
                                    '<span class="b-badge b-badge-success"><svg width="8" height="8" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Ảnh</span>'
                                );
                            } else {
                                alert('Lỗi: ' + (r.data || 'Không xác định'));
                            }
                        },
                        error: function(){ $saving.hide(); alert('Lỗi kết nối.'); }
                    });
                });

                frames[cid].open();
            });
        });
        </script>
        <?php
        $this->ui_close();
    }

    /* ═══════════════════════════════════════════════════
       PAGE: REVIEWS
    ═══════════════════════════════════════════════════ */

    private function page_reviews(): void {
        global $wpdb;
        $t       = $wpdb->prefix.'bacera_product_reviews';
        $status  = sanitize_key($_GET['rstatus']??'');
        $paged   = max(1, intval($_GET['paged']??1));
        $per     = 20;
        $where   = $status ? $wpdb->prepare("WHERE status=%s",$status) : '';
        $total   = (int)$wpdb->get_var("SELECT COUNT(id) FROM $t $where");
        $reviews = $wpdb->get_results("SELECT * FROM $t $where ORDER BY id DESC LIMIT $per OFFSET ".($paged-1)*$per, ARRAY_A)?:[];
        $counts  = [''=> (int)$wpdb->get_var("SELECT COUNT(id) FROM $t"),
            'pending'  => (int)$wpdb->get_var("SELECT COUNT(id) FROM $t WHERE status='pending'"),
            'approved' => (int)$wpdb->get_var("SELECT COUNT(id) FROM $t WHERE status='approved'"),
            'rejected' => (int)$wpdb->get_var("SELECT COUNT(id) FROM $t WHERE status='rejected'"),
        ];
        $this->ui_shell('reviews');
        ?>
        <div style="display:flex;gap:8px;margin-bottom:22px;flex-wrap:wrap;">
            <?php foreach([''=> 'Tất cả ('.$counts[''].')','pending'=>'⏳ Chờ ('.$counts['pending'].')','approved'=>'✅ Duyệt ('.$counts['approved'].')','rejected'=>'❌ Từ chối ('.$counts['rejected'].')'] as $sv=>$sl): ?>
            <a href="<?php echo esc_url(add_query_arg(['page'=>'bacera-products','tab'=>'reviews','rstatus'=>$sv],admin_url('admin.php'))); ?>"
               class="b-btn b-btn-sm <?php echo $status===$sv?'b-btn-primary':'b-btn-outline';?>"><?php echo $sl;?></a>
            <?php endforeach; ?>
        </div>
        <?php if (empty($reviews)): ?>
        <div class="b-empty-state"><div class="b-empty-icon">💬</div><div class="b-empty-title">Chưa có đánh giá nào</div></div>
        <?php else: ?>
        <div class="b-table-card">
            <table class="b-table">
                <thead><tr>
                    <th>Khách hàng</th><th style="width:120px;">SP ID</th><th style="width:100px;">Sao</th><th>Nội dung</th><th style="width:120px;">Trạng thái</th><th style="width:120px;text-align:center;">Thao tác</th>
                </tr></thead>
                <tbody>
                <?php foreach ($reviews as $r):
                    $au  = wp_nonce_url(add_query_arg(['action'=>'bprod_review_action','review_id'=>$r['id'],'rv_action'=>'approve'],admin_url('admin-post.php')),'rv_'.$r['id']);
                    $rj  = wp_nonce_url(add_query_arg(['action'=>'bprod_review_action','review_id'=>$r['id'],'rv_action'=>'reject'],admin_url('admin-post.php')),'rv_'.$r['id']);
                    $del = wp_nonce_url(add_query_arg(['action'=>'bprod_review_action','review_id'=>$r['id'],'rv_action'=>'delete'],admin_url('admin-post.php')),'rv_'.$r['id']);
                ?>
                <tr class="b-tr">
                    <td class="b-td"><strong><?php echo esc_html($r['customer_name']); ?></strong><br><small style="color:var(--b-light);"><?php echo esc_html($r['customer_email']); ?><br><?php echo esc_html(date('d/m/Y',strtotime($r['created_at']))); ?></small></td>
                    <td class="b-td"><span class="b-sku">#<?php echo esc_html(mb_substr($r['pancake_product_id'],0,8)); ?>…</span></td>
                    <td class="b-td"><span style="color:#f59e0b;font-size:16px;letter-spacing:1px;"><?php echo str_repeat('★',(int)$r['rating']); ?></span><span style="color:#e5e7eb;font-size:16px;"><?php echo str_repeat('★',5-(int)$r['rating']); ?></span></td>
                    <td class="b-td" style="font-size:13px;max-width:220px;"><?php echo esc_html(mb_strimwidth($r['review_text'],0,90,'…')); ?></td>
                    <td class="b-td"><?php
                        $bs=['pending'=>'b-pill-amber','approved'=>'b-pill-green','rejected'=>'b-pill-red'];
                        $ls=['pending'=>'⏳ Chờ duyệt','approved'=>'✅ Đã duyệt','rejected'=>'❌ Từ chối'];
                        echo '<span class="b-pill '.($bs[$r['status']]??'b-pill-grey').'">'.($ls[$r['status']]??$r['status']).'</span>';
                    ?></td>
                    <td class="b-td" style="text-align:center;">
                        <div style="display:flex;gap:4px;justify-content:center;">
                        <?php if($r['status']!=='approved'): ?><a href="<?php echo esc_url($au); ?>" class="b-icon-btn" style="background:#ecfdf5;color:#065f46;" title="Duyệt">✅</a><?php endif; ?>
                        <?php if($r['status']!=='rejected'): ?><a href="<?php echo esc_url($rj); ?>" class="b-icon-btn" style="background:#fef2f2;color:#dc2626;" title="Từ chối">❌</a><?php endif; ?>
                        <a href="<?php echo esc_url($del); ?>" class="b-icon-btn" style="background:#f3f4f6;color:#6b7280;" onclick="return confirm('Xóa đánh giá?')" title="Xóa">🗑</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif;
        $this->ui_close();
    }

    /* ═══════════════════════════════════════════════════
       SUBPAGE: EDIT PRODUCT
    ═══════════════════════════════════════════════════ */

    private function page_edit_product( string $pid ): void {
        if (!$pid) wp_die('Thiếu ID sản phẩm.');
        $pancake = $this->fetch_product($pid);
        $meta    = $this->db_get_product_meta($pid);
        $images  = $this->db_get_product_images($pid);
        $pname   = $pancake['name'] ?? 'Sản phẩm #'.mb_substr($pid,0,8);
        $saved   = !empty($_GET['saved']);

        // Prep price data
        $p_prices = [];
        if (!empty($pancake['variations'])) {
            $p_prices = array_filter(array_map(
                fn($v) => is_numeric($v['retail_price']??null) ? (float)$v['retail_price'] : null,
                $pancake['variations']
            ));
        }
        $p_price_str = $p_prices
            ? number_format(min($p_prices),0,',','.').'₫'.(count(array_unique($p_prices))>1?' – '.number_format(max($p_prices),0,',','.').'₫':'')
            : '';

        $this->ui_shell('products', ['label'=>$pname, 'back'=>admin_url('admin.php?page=bacera-products')]);
        ?>

        <?php if ($saved): ?>
        <div class="b-toast b-toast-show" id="b-save-toast">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            Đã lưu thành công!
        </div>
        <script>setTimeout(function(){document.getElementById('b-save-toast')?.classList.remove('b-toast-show');},3500);</script>
        <?php endif; ?>

        <!-- ─── STICKY QUICK-ACTION BAR ─── -->
        <div class="ep-topbar">
            <div class="ep-topbar-left">
                <a href="<?php echo esc_url(admin_url('admin.php?page=bacera-products')); ?>" class="ep-back-btn">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
                    Danh sách
                </a>
                <div class="ep-topbar-sep"></div>
                <div class="ep-topbar-name"><?php echo esc_html($pname); ?></div>
                <?php if ($p_price_str): ?>
                <div class="ep-topbar-price"><?php echo $p_price_str; ?></div>
                <?php endif; ?>
                <span class="ep-topbar-status <?php echo ($pancake['is_published']??false)?'is-live':'is-draft'; ?>">
                    <?php echo ($pancake['is_published']??false) ? '<span class="ep-pulse"></span>Đang bán' : '○ Nháp'; ?>
                </span>
            </div>
            <div class="ep-topbar-right">
                <button form="b-meta-form" type="submit" class="b-btn b-btn-primary b-btn-sm" id="ep-save-btn">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                    Lưu thay đổi
                </button>
            </div>
        </div>

        <div class="b-edit-layout ep-layout">

            <!-- ═══ MAIN COLUMN ═══ -->
            <div class="b-edit-main">

                <!-- ── Product Identity Card ── -->
                <div class="ep-identity">
                    <div class="ep-identity-thumb">
                        <?php if (!empty($pancake['_thumb'])): ?>
                        <img src="<?php echo esc_url($pancake['_thumb']); ?>" alt="" class="ep-identity-img">
                        <div class="ep-identity-glow" style="background-image:url('<?php echo esc_url($pancake['_thumb']); ?>')"></div>
                        <?php else: ?>
                        <div class="ep-identity-placeholder">🏺</div>
                        <?php endif; ?>
                    </div>
                    <div class="ep-identity-info">
                        <h1 class="ep-identity-name"><?php echo esc_html($pname); ?></h1>
                        <div class="ep-identity-meta">
                            <?php if (!empty($pancake['custom_id'])): ?>
                            <span class="ep-meta-chip ep-meta-chip-sku">
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                                <?php echo esc_html($pancake['custom_id']); ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($p_price_str): ?>
                            <span class="ep-meta-chip ep-meta-chip-price">
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                <?php echo $p_price_str; ?>
                            </span>
                            <?php endif; ?>
                            <span class="ep-meta-chip <?php echo ($pancake['is_published']??false)?'ep-meta-chip-live':'ep-meta-chip-draft'; ?>">
                                <?php echo ($pancake['is_published']??false) ? '● Đang bán' : '○ Nháp'; ?>
                            </span>
                            <?php $var_count = count($pancake['variations']??[]); if ($var_count): ?>
                            <span class="ep-meta-chip ep-meta-chip-var">
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="5" height="5" rx="1"/><rect x="10" y="3" width="5" height="5" rx="1"/><rect x="2" y="12" width="5" height="5" rx="1"/></svg>
                                <?php echo $var_count; ?> biến thể
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="ep-identity-source">Pancake POS</div>
                </div>

                <!-- ── Tabbed Form ── -->
                <div class="ep-tabs-nav" id="ep-tabs-nav">
                    <button type="button" class="ep-tab-btn is-active" data-tab="content">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        Nội dung
                    </button>
                    <button type="button" class="ep-tab-btn" data-tab="seo">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        SEO
                    </button>
                    <button type="button" class="ep-tab-btn" data-tab="gallery">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        Gallery
                        <span class="ep-tab-count" id="ep-tab-gal-count"><?php echo count($images); ?></span>
                    </button>
                    <button type="button" class="ep-tab-btn" data-tab="settings">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
                        Cài đặt
                    </button>
                </div>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="b-meta-form">
                    <input type="hidden" name="action" value="bprod_save_meta">
                    <input type="hidden" name="pid" value="<?php echo esc_attr($pid); ?>">
                    <?php wp_nonce_field('bprod_save_meta_'.$pid); ?>

                    <!-- TAB: CONTENT -->
                    <div class="ep-tab-panel is-active" id="ep-tab-content">

                        <div class="b-section">
                            <div class="b-section-hd">
                                <span class="b-section-icon">✍️</span>
                                <span>Câu chuyện sản phẩm</span>
                                <span class="b-section-badge" style="background:var(--b-amber-bg);color:var(--b-amber);border-color:var(--b-amber-border);">Hiển thị trên website</span>
                            </div>
                            <div class="b-section-body">
                                <div class="b-field">
                                    <div class="ep-field-hd">
                                        <label>Câu chuyện ngắn</label>
                                        <span class="ep-char-counter" id="ep-cnt-story" data-target="ep-story" data-max="280">0 / 280</span>
                                    </div>
                                    <textarea name="story" id="ep-story" rows="4" class="b-textarea ep-textarea-rich"
                                              placeholder="Kể câu chuyện về nguồn gốc, cảm hứng, người thợ thủ công..."><?php echo esc_textarea($meta['story']??''); ?></textarea>
                                    <div class="ep-field-hint">Hiển thị trong product card và trang chi tiết sản phẩm</div>
                                </div>
                            </div>
                        </div>

                        <div class="b-section">
                            <div class="b-section-hd">
                                <span class="b-section-icon">📐</span>
                                <span>Thông số kỹ thuật</span>
                            </div>
                            <div class="b-section-body">
                                <div class="ep-spec-grid">
                                    <div class="b-field">
                                        <label>Chất liệu / Vật liệu</label>
                                        <input type="text" name="materials" class="b-input" value="<?php echo esc_attr($meta['materials']??''); ?>" placeholder="Đất sét cao lanh, men nâu tự nhiên...">
                                    </div>
                                    <div class="b-field">
                                        <label>Kích thước</label>
                                        <input type="text" name="dimensions" class="b-input" value="<?php echo esc_attr($meta['dimensions']??''); ?>" placeholder="Cao 18cm, Đ.kính 12cm">
                                    </div>
                                    <div class="b-field ep-spec-span2">
                                        <label>Hướng dẫn bảo quản</label>
                                        <input type="text" name="care_instructions" class="b-input" value="<?php echo esc_attr($meta['care_instructions']??''); ?>" placeholder="Rửa tay, tránh ngâm nước, lò vi sóng được...">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="b-section">
                            <div class="b-section-hd">
                                <span class="b-section-icon">📖</span>
                                <span>Bài viết chi tiết</span>
                                <span class="b-section-badge">HTML</span>
                                <button type="button" class="ep-code-toggle" id="ep-preview-toggle">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Xem trước
                                </button>
                            </div>
                            <div class="b-section-body" style="padding:0;">
                                <textarea name="long_description" id="ep-long-desc" rows="18" class="b-textarea b-textarea-code ep-code-editor"
                                          placeholder="<p>Nội dung HTML đầy đủ...</p>"><?php echo esc_textarea($meta['long_description']??''); ?></textarea>
                                <div class="ep-html-preview" id="ep-html-preview" style="display:none;"><div class="ep-preview-content" id="ep-preview-content"></div></div>
                                <div class="ep-code-footer">
                                    <span class="ep-char-count-live" id="ep-long-desc-count">0 ký tự</span>
                                    <button type="button" class="ep-link-btn" onclick="document.getElementById('ep-long-desc').focus()">Chỉnh sửa HTML</button>
                                </div>
                            </div>
                        </div>

                    </div><!-- /ep-tab-content -->

                    <!-- TAB: SEO -->
                    <div class="ep-tab-panel" id="ep-tab-seo">

                        <div class="ep-seo-preview-card">
                            <div class="ep-seo-preview-label">Xem trước kết quả Google</div>
                            <div class="ep-seo-preview-url">bacera.vn › shop › <span id="ep-seo-prev-slug">san-pham</span></div>
                            <div class="ep-seo-preview-title" id="ep-seo-prev-title"><?php echo esc_html($pname); ?></div>
                            <div class="ep-seo-preview-desc" id="ep-seo-prev-desc">Thêm meta description để hiển thị tại đây...</div>
                        </div>

                        <div class="b-section">
                            <div class="b-section-hd"><span class="b-section-icon">🔤</span><span>Tiêu đề &amp; URL</span></div>
                            <div class="b-section-body">
                                <div class="b-field">
                                    <div class="ep-field-hd">
                                        <label>Tiêu đề SEO</label>
                                        <span class="ep-char-counter" id="ep-cnt-seo-title" data-target="ep-seo-title" data-max="60">0 / 60</span>
                                    </div>
                                    <input type="text" name="seo_title" id="ep-seo-title" class="b-input" value="<?php echo esc_attr($meta['seo_title']??''); ?>" placeholder="<?php echo esc_attr($pname); ?>" maxlength="70">
                                    <div class="ep-field-hint">Để trống = dùng tên Pancake POS. Tốt nhất: 50–60 ký tự.</div>
                                </div>
                                <div class="b-field">
                                    <div class="ep-field-hd"><label>Slug URL</label></div>
                                    <div class="ep-slug-wrap">
                                        <span class="ep-slug-prefix">shop/</span>
                                        <input type="text" name="slug_override" id="ep-slug" class="b-input ep-slug-input" value="<?php echo esc_attr($meta['slug_override']??''); ?>" placeholder="<?php echo esc_attr(sanitize_title($pname)); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="b-section">
                            <div class="b-section-hd"><span class="b-section-icon">📄</span><span>Meta Description</span></div>
                            <div class="b-section-body">
                                <div class="b-field">
                                    <div class="ep-field-hd">
                                        <label>Mô tả tìm kiếm</label>
                                        <span class="ep-char-counter" id="ep-cnt-seo-desc" data-target="ep-seo-desc" data-max="160">0 / 160</span>
                                    </div>
                                    <textarea name="seo_description" id="ep-seo-desc" rows="3" class="b-textarea" maxlength="170" placeholder="Mô tả ngắn gọn, thu hút — hiển thị trên Google khi người dùng tìm kiếm..."><?php echo esc_textarea($meta['seo_description']??''); ?></textarea>
                                    <div class="ep-field-hint">Tốt nhất: 120–160 ký tự. Không điền = Google tự chọn.</div>
                                </div>
                            </div>
                        </div>

                    </div><!-- /ep-tab-seo -->

                    <!-- TAB: GALLERY -->
                    <div class="ep-tab-panel" id="ep-tab-gallery">
                        <div class="b-section">
                            <div class="b-section-hd">
                                <span class="b-section-icon">🖼️</span>
                                <span>Thư viện hình ảnh</span>
                                <span class="b-section-badge" id="b-gal-count"><?php echo count($images); ?> ảnh</span>
                                <button type="button" id="b-gal-media" class="b-btn b-btn-outline b-btn-sm" style="margin-left:auto;">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                    Thêm ảnh
                                </button>
                            </div>
                            <div class="b-section-body">
                                <?php if (empty($images)): ?>
                                <div class="ep-drop-zone" id="ep-drop-zone">
                                    <div class="ep-drop-icon">🖼️</div>
                                    <div class="ep-drop-title">Chưa có ảnh nào</div>
                                    <div class="ep-drop-sub">Nhấn <strong>Thêm ảnh</strong> để chọn từ thư viện WordPress</div>
                                    <button type="button" class="b-btn b-btn-outline ep-drop-btn" id="ep-drop-trigger">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                        Chọn từ thư viện WP
                                    </button>
                                </div>
                                <?php endif; ?>

                                <div class="ep-gallery-grid" id="b-gal-grid" <?php echo empty($images)?'style="display:none;"':''; ?>>
                                    <?php foreach ($images as $idx => $img):
                                        $del = wp_nonce_url(add_query_arg(['action'=>'bprod_delete_image','img_id'=>$img['id'],'pid'=>$pid],admin_url('admin-post.php')),'bprod_del_img_'.$img['id']);
                                    ?>
                                    <div class="ep-gal-item <?php echo $img['is_primary']?'is-primary':''; ?>" style="--idx:<?php echo $idx; ?>">
                                        <div class="ep-gal-img-wrap"><img src="<?php echo esc_url($img['image_url']); ?>" alt="<?php echo esc_attr($img['alt_text']); ?>" loading="lazy"></div>
                                        <div class="ep-gal-overlay">
                                            <?php if ($img['is_primary']): ?>
                                            <span class="ep-gal-primary-badge">
                                                <svg width="9" height="9" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                Ảnh chính
                                            </span>
                                            <?php endif; ?>
                                            <a href="<?php echo esc_url($del); ?>" class="ep-gal-del" onclick="return confirm('Xóa ảnh này?')" title="Xóa ảnh">
                                                <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                            </a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <div id="b-gal-progress" style="display:none;" class="ep-upload-progress">
                                    <div class="ep-upload-spinner"><div class="ep-spinner-ring"></div></div>
                                    <div>
                                        <div id="b-gal-prog-text" class="ep-upload-text">Đang lưu...</div>
                                        <div class="ep-upload-bar-wrap"><div class="ep-upload-bar" id="ep-upload-bar"></div></div>
                                    </div>
                                </div>

                                <details class="b-url-fallback" style="margin-top:16px;">
                                    <summary>Thêm ảnh theo URL</summary>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="padding:14px;">
                                        <input type="hidden" name="action" value="bprod_add_image">
                                        <input type="hidden" name="pid" value="<?php echo esc_attr($pid); ?>">
                                        <?php wp_nonce_field('bprod_add_image_'.$pid); ?>
                                        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                                            <input type="text" name="img_url" class="b-input" placeholder="https://..." style="flex:1;min-width:200px;">
                                            <input type="text" name="img_alt" class="b-input" placeholder="Alt text mô tả" style="width:160px;">
                                            <button type="submit" class="b-btn b-btn-success b-btn-sm">Thêm</button>
                                        </div>
                                    </form>
                                </details>
                            </div>
                        </div>
                    </div><!-- /ep-tab-gallery -->

                    <!-- TAB: SETTINGS -->
                    <div class="ep-tab-panel" id="ep-tab-settings">
                        <div class="b-section b-section-flat">
                            <div class="b-section-hd"><span class="b-section-icon">⭐</span><span>Hiển thị &amp; Ưu tiên</span></div>
                            <div class="b-toggle-group">
                                <label class="b-toggle-row">
                                    <input type="checkbox" name="is_featured" value="1" <?php checked($meta['is_featured']??0,1); ?> class="b-toggle-cb">
                                    <div class="b-toggle-track"><div class="b-toggle-thumb"></div></div>
                                    <div>
                                        <div class="b-toggle-label">⭐ Sản phẩm nổi bật</div>
                                        <div class="b-toggle-sub">Hiển thị ưu tiên trên trang chủ và các section highlight</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div><!-- /ep-tab-settings -->

                    <div class="ep-form-actions">
                        <button type="submit" class="b-btn b-btn-primary b-btn-lg">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                            Lưu thay đổi
                        </button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=bacera-products')); ?>" class="b-btn b-btn-ghost">← Quay lại danh sách</a>
                    </div>

                </form>

            </div><!-- .b-edit-main -->

            <!-- ═══ STICKY SIDEBAR ═══ -->
            <div class="b-edit-sidebar">
                <div class="b-sidebar-block b-sidebar-sticky">
                    <div class="ep-sb-header">
                        <div class="ep-sb-logo">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                            Pancake POS
                        </div>
                        <span class="b-readonly-badge">Chỉ đọc</span>
                    </div>

                    <?php if (empty($pancake)): ?>
                    <div class="ep-sb-error">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <p>Không tải được dữ liệu từ Pancake API</p>
                    </div>
                    <?php else: ?>

                    <?php if (!empty($pancake['_thumb'])): ?>
                    <div class="ep-sb-thumb">
                        <img src="<?php echo esc_url($pancake['_thumb']); ?>" alt="" loading="lazy">
                        <div class="ep-sb-thumb-overlay"></div>
                    </div>
                    <?php endif; ?>

                    <div class="b-sidebar-body ep-sb-body">
                        <?php $this->sb_row('ID', '<code class="b-sku">'.esc_html(mb_substr($pid,0,10)).'…</code>'); ?>
                        <?php $this->sb_row('Tên Pancake', '<strong style="word-break:break-word;">'.esc_html($pname).'</strong>'); ?>
                        <?php if (!empty($pancake['custom_id'])): $this->sb_row('SKU', '<span class="b-sku">'.esc_html($pancake['custom_id']).'</span>'); endif; ?>
                        <?php if ($p_price_str): $this->sb_row('Giá', '<strong class="b-info-price">'.$p_price_str.'</strong>'); endif; ?>
                        <?php $this->sb_row('Trạng thái', ($pancake['is_published']??false)
                            ? '<span class="b-badge b-badge-success">Đang bán</span>'
                            : '<span class="b-badge b-badge-inactive">Nháp</span>'); ?>
                        <?php if (!empty($pancake['note_product'])): ?>
                        <?php $this->sb_row('Mô tả POS', '<span style="font-size:11px;color:var(--b-mid);line-height:1.5;display:block;">'.esc_html(mb_strimwidth($pancake['note_product'],0,100,'…')).'</span>'); ?>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($pancake['variations'])): ?>
                    <div class="ep-variations">
                        <div class="ep-variations-hd">
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="5" height="5" rx="1"/><rect x="10" y="3" width="5" height="5" rx="1"/><rect x="2" y="12" width="5" height="5" rx="1"/></svg>
                            <?php echo count($pancake['variations']); ?> biến thể
                        </div>
                        <div class="ep-var-list">
                            <?php foreach (array_slice($pancake['variations'],0,6) as $v):
                                $vname  = $v['name'] ?? ($v['value'] ?? 'Biến thể');
                                $vprice = is_numeric($v['retail_price']??null) ? number_format((float)$v['retail_price'],0,',','.') : '—';
                                $vstock = $v['quantity'] ?? null;
                            ?>
                            <div class="ep-var-row">
                                <div class="ep-var-name"><?php echo esc_html($vname); ?></div>
                                <div class="ep-var-meta">
                                    <span class="ep-var-price"><?php echo $vprice; ?>₫</span>
                                    <?php if ($vstock !== null): ?>
                                    <span class="ep-var-stock <?php echo intval($vstock)===0?'is-out':''; ?>"><?php echo intval($vstock)>0?intval($vstock).' kho':'Hết'; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if (count($pancake['variations']) > 6): ?>
                            <div class="ep-var-more">+<?php echo count($pancake['variations'])-6; ?> biến thể khác</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="ep-sb-footer">
                        <button form="b-meta-form" type="submit" class="b-btn b-btn-primary b-btn-sm b-btn-block">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                            Lưu thay đổi
                        </button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=bacera-products')); ?>" class="b-btn b-btn-ghost b-btn-sm b-btn-block" style="margin-top:6px;justify-content:center;">← Quay lại</a>
                    </div>

                    <?php endif; ?>
                </div>
            </div>
        </div><!-- .b-edit-layout -->

        <style>
        /* ── EP: Edit Product Inline Styles ── */
        .ep-topbar{position:sticky;top:32px;z-index:200;display:flex;align-items:center;justify-content:space-between;background:rgba(253,250,245,.96);backdrop-filter:blur(12px);border:1.5px solid var(--b-border);border-radius:var(--b-r-lg);padding:10px 16px;margin-bottom:20px;box-shadow:0 2px 12px rgba(61,47,38,.08);}
        .ep-topbar-left{display:flex;align-items:center;gap:10px;min-width:0;flex:1;overflow:hidden;}
        .ep-topbar-right{flex-shrink:0;margin-left:16px;}
        .ep-back-btn{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:var(--b-mid);text-decoration:none;white-space:nowrap;padding:5px 10px;border-radius:6px;background:var(--b-warm);border:1px solid var(--b-border);transition:all .15s;}
        .ep-back-btn:hover{color:var(--b-brown);}
        .ep-topbar-sep{width:1px;height:18px;background:var(--b-border);flex-shrink:0;}
        .ep-topbar-name{font-size:13px;font-weight:700;color:var(--b-brown);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:260px;}
        .ep-topbar-price{font-size:12px;font-weight:700;color:var(--b-accent);white-space:nowrap;}
        .ep-topbar-status{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;white-space:nowrap;}
        .ep-topbar-status.is-live{background:var(--b-green-bg);color:#065f46;border:1px solid var(--b-green-border);}
        .ep-topbar-status.is-draft{background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb;}
        .ep-pulse{width:7px;height:7px;border-radius:50%;background:var(--b-green);animation:pulse 2s ease-in-out infinite;flex-shrink:0;}
        /* Identity */
        .ep-identity{display:flex;align-items:flex-start;gap:18px;background:linear-gradient(135deg,var(--b-brown) 0%,#6b3e26 100%);border-radius:var(--b-r-lg);padding:22px 24px;margin-bottom:0;box-shadow:var(--b-shadow-md);position:relative;overflow:hidden;}
        .ep-identity::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 70% 80% at 90% 50%,rgba(255,255,255,.05),transparent);pointer-events:none;}
        .ep-identity-thumb{position:relative;flex-shrink:0;width:80px;height:80px;}
        .ep-identity-img{width:80px;height:80px;border-radius:12px;object-fit:cover;position:relative;z-index:1;border:2px solid rgba(255,255,255,.25);}
        .ep-identity-glow{position:absolute;inset:-6px;border-radius:16px;background-size:cover;background-position:center;filter:blur(10px);opacity:.4;z-index:0;}
        .ep-identity-placeholder{width:80px;height:80px;border-radius:12px;background:rgba(255,255,255,.1);border:2px solid rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:36px;}
        .ep-identity-info{flex:1;min-width:0;}
        .ep-identity-name{font-size:18px;font-weight:800;color:#fff;line-height:1.3;margin:0 0 10px;}
        .ep-identity-meta{display:flex;flex-wrap:wrap;gap:6px;}
        .ep-meta-chip{display:inline-flex;align-items:center;gap:4px;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:600;}
        .ep-meta-chip-sku{background:rgba(255,255,255,.12);color:rgba(255,255,255,.8);border:1px solid rgba(255,255,255,.15);}
        .ep-meta-chip-price{background:rgba(217,119,6,.3);color:#fcd34d;border:1px solid rgba(217,119,6,.4);}
        .ep-meta-chip-live{background:rgba(5,150,105,.25);color:#34d399;border:1px solid rgba(52,211,153,.3);}
        .ep-meta-chip-draft{background:rgba(255,255,255,.1);color:rgba(255,255,255,.6);border:1px solid rgba(255,255,255,.2);}
        .ep-meta-chip-var{background:rgba(255,255,255,.1);color:rgba(255,255,255,.7);border:1px solid rgba(255,255,255,.15);}
        .ep-identity-source{position:absolute;top:14px;right:16px;font-size:9px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;background:rgba(255,255,255,.1);color:rgba(255,255,255,.6);padding:3px 9px;border-radius:20px;border:1px solid rgba(255,255,255,.12);}
        /* Tabs */
        .ep-layout{grid-template-columns:1fr 290px;}
        @media(max-width:960px){.ep-layout{grid-template-columns:1fr;}}
        .ep-tabs-nav{display:flex;gap:2px;margin-bottom:0;background:var(--b-warm);padding:4px;border-radius:0;border:1.5px solid var(--b-border);border-top:none;border-bottom:none;}
        .ep-tab-btn{display:flex;align-items:center;gap:6px;padding:8px 16px;font-size:13px;font-weight:600;color:var(--b-light);background:transparent;border:none;border-radius:var(--b-r-sm);cursor:pointer;transition:all .18s;white-space:nowrap;font-family:inherit;}
        .ep-tab-btn:hover{color:var(--b-mid);background:rgba(255,255,255,.5);}
        .ep-tab-btn.is-active{background:#fff;color:var(--b-brown);box-shadow:0 1px 4px rgba(61,47,38,.1);}
        .ep-tab-count{background:var(--b-accent-light);color:var(--b-accent);border:1px solid rgba(196,124,58,.2);border-radius:20px;padding:1px 7px;font-size:10px;font-weight:700;}
        .ep-tab-btn.is-active .ep-tab-count{background:var(--b-accent);color:#fff;border-color:var(--b-accent);}
        .ep-tab-panel{display:none;}
        .ep-tab-panel.is-active{display:block;}
        /* Fields */
        .ep-field-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:7px;}
        .ep-field-hd label{margin:0;}
        .ep-char-counter{font-size:11px;font-weight:600;color:var(--b-light);padding:2px 8px;background:var(--b-warm);border-radius:20px;border:1px solid var(--b-border);white-space:nowrap;transition:color .2s,background .2s;}
        .ep-char-counter.warn{color:var(--b-amber);background:var(--b-amber-bg);border-color:var(--b-amber-border);}
        .ep-char-counter.over{color:var(--b-red);background:var(--b-red-bg);border-color:var(--b-red-border);}
        .ep-field-hint{font-size:11px;color:var(--b-light);margin-top:5px;line-height:1.5;}
        .ep-textarea-rich{min-height:100px;resize:vertical;}
        .ep-code-editor{display:block;width:100%;min-height:320px;border-radius:0;border-left:none;border-right:none;border-bottom:none;font-family:'SF Mono','Fira Code',monospace;font-size:12px;line-height:1.7;resize:vertical;}
        .ep-code-footer{display:flex;align-items:center;justify-content:space-between;padding:8px 16px;background:var(--b-warm);border-top:1px solid var(--b-border);}
        .ep-char-count-live{font-size:11px;color:var(--b-light);font-weight:500;}
        .ep-link-btn{font-size:11px;color:var(--b-mid);background:none;border:none;cursor:pointer;text-decoration:underline;}
        .ep-code-toggle{margin-left:auto;display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:var(--b-mid);background:var(--b-warm);border:1px solid var(--b-border);border-radius:5px;padding:3px 9px;cursor:pointer;transition:all .15s;font-family:inherit;}
        .ep-code-toggle:hover{color:var(--b-brown);border-color:var(--b-accent);}
        .ep-html-preview{padding:20px 24px;background:#fff;min-height:200px;border-top:1px solid var(--b-border);font-size:14px;line-height:1.7;color:var(--b-brown);}
        .ep-spec-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 14px;}
        .ep-spec-span2{grid-column:1 / -1;}
        /* SEO Preview */
        .ep-seo-preview-card{background:#fff;border:1.5px solid var(--b-border);border-radius:var(--b-r-lg);padding:18px 20px;margin-bottom:18px;box-shadow:var(--b-shadow);}
        .ep-seo-preview-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--b-light);margin-bottom:10px;}
        .ep-seo-preview-url{font-size:12px;color:#006621;margin-bottom:4px;}
        .ep-seo-preview-title{font-size:16px;font-weight:600;color:#1a0dab;line-height:1.3;margin-bottom:4px;}
        .ep-seo-preview-desc{font-size:13px;color:#545454;line-height:1.5;}
        .ep-slug-wrap{display:flex;align-items:center;border:1.5px solid var(--b-border);border-radius:var(--b-r-sm);overflow:hidden;background:#fff;transition:border-color .15s,box-shadow .15s;}
        .ep-slug-wrap:focus-within{border-color:var(--b-accent);box-shadow:0 0 0 3px rgba(196,124,58,.12);}
        .ep-slug-prefix{padding:9px 12px;background:var(--b-warm);color:var(--b-mid);font-size:12px;font-weight:600;border-right:1px solid var(--b-border);white-space:nowrap;flex-shrink:0;}
        .ep-slug-input{border:none;box-shadow:none;border-radius:0;padding-left:10px;}
        .ep-slug-input:focus{box-shadow:none;}
        /* Gallery */
        .ep-drop-zone{border:2px dashed var(--b-border);border-radius:var(--b-r-lg);padding:48px 24px;text-align:center;background:linear-gradient(135deg,var(--b-cream),var(--b-warm));transition:all .2s;margin-bottom:12px;}
        .ep-drop-zone:hover{border-color:var(--b-accent);background:var(--b-accent-light);}
        .ep-drop-icon{font-size:48px;margin-bottom:12px;line-height:1;}
        .ep-drop-title{font-size:15px;font-weight:700;color:var(--b-mid);margin-bottom:6px;}
        .ep-drop-sub{font-size:13px;color:var(--b-light);margin-bottom:16px;line-height:1.5;}
        .ep-gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px;margin-bottom:14px;}
        .ep-gal-item{position:relative;border-radius:var(--b-r-sm);overflow:hidden;aspect-ratio:1;border:2px solid transparent;transition:all .2s;animation:epGalIn .3s ease both;animation-delay:calc(var(--idx,0) * 40ms);}
        @keyframes epGalIn{from{opacity:0;transform:scale(.88);}to{opacity:1;transform:scale(1);}}
        .ep-gal-item:hover{border-color:var(--b-accent);box-shadow:0 4px 12px rgba(196,124,58,.2);}
        .ep-gal-item.is-primary{border-color:var(--b-accent);box-shadow:0 0 0 3px rgba(196,124,58,.2);}
        .ep-gal-img-wrap{position:absolute;inset:0;}
        .ep-gal-img-wrap img{width:100%;height:100%;object-fit:cover;}
        .ep-gal-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.65) 0%,transparent 50%);opacity:0;transition:opacity .2s;display:flex;align-items:flex-end;justify-content:space-between;padding:6px;}
        .ep-gal-item:hover .ep-gal-overlay{opacity:1;}
        .ep-gal-primary-badge{display:inline-flex;align-items:center;gap:3px;background:var(--b-accent);color:#fff;font-size:9px;font-weight:700;padding:2px 6px;border-radius:4px;}
        .ep-gal-del{width:26px;height:26px;border-radius:50%;background:rgba(220,38,38,.9);color:#fff;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:transform .15s;flex-shrink:0;}
        .ep-gal-del:hover{transform:scale(1.1);}
        /* Upload progress */
        .ep-upload-progress{display:flex;align-items:center;gap:12px;padding:12px 16px;background:var(--b-blue-bg);border:1.5px solid var(--b-blue-border);border-radius:var(--b-r-sm);margin-bottom:12px;}
        .ep-spinner-ring{width:22px;height:22px;border-radius:50%;border:3px solid var(--b-blue-border);border-top-color:var(--b-blue);animation:spin .7s linear infinite;}
        .ep-upload-text{font-size:13px;font-weight:600;color:#1e40af;margin-bottom:4px;}
        .ep-upload-bar-wrap{height:4px;background:var(--b-blue-border);border-radius:99px;width:200px;overflow:hidden;}
        .ep-upload-bar{height:100%;background:var(--b-blue);border-radius:99px;width:0%;transition:width .5s ease;}
        /* Form actions */
        .ep-form-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:18px 0;margin-top:4px;border-top:1.5px solid var(--b-border);}
        /* Sidebar */
        .ep-sb-header{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--b-border);background:linear-gradient(to right,var(--b-warm),var(--b-cream));}
        .ep-sb-logo{display:flex;align-items:center;gap:7px;font-size:13px;font-weight:700;color:var(--b-brown);}
        .ep-sb-body{padding:12px 16px;}
        .ep-sb-footer{padding:12px 16px;border-top:1px solid var(--b-border);background:var(--b-warm);}
        .ep-sb-thumb{position:relative;overflow:hidden;}
        .ep-sb-thumb img{width:100%;height:160px;object-fit:cover;display:block;}
        .ep-sb-thumb-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(61,47,38,.4),transparent 60%);}
        .ep-sb-error{padding:24px 16px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:8px;}
        .ep-sb-error svg{opacity:.3;}
        .ep-sb-error p{font-size:13px;color:var(--b-light);margin:0;}
        /* Variations */
        .ep-variations{border-top:1px solid var(--b-border);}
        .ep-variations-hd{display:flex;align-items:center;gap:6px;padding:9px 16px;font-size:11px;font-weight:700;color:var(--b-mid);text-transform:uppercase;letter-spacing:.5px;background:var(--b-warm);border-bottom:1px solid var(--b-border);}
        .ep-var-row{display:flex;align-items:center;justify-content:space-between;padding:7px 14px;border-bottom:1px solid var(--b-warm2);transition:background .12s;}
        .ep-var-row:last-child{border-bottom:none;}
        .ep-var-row:hover{background:var(--b-cream);}
        .ep-var-name{font-size:12px;color:var(--b-brown);font-weight:500;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-right:8px;}
        .ep-var-meta{display:flex;align-items:center;gap:5px;flex-shrink:0;}
        .ep-var-price{font-size:11px;font-weight:700;color:var(--b-accent);}
        .ep-var-stock{font-size:10px;font-weight:600;background:var(--b-green-bg);color:#065f46;padding:1px 6px;border-radius:20px;border:1px solid var(--b-green-border);}
        .ep-var-stock.is-out{background:var(--b-red-bg);color:var(--b-red);border-color:var(--b-red-border);}
        .ep-var-more{padding:7px 16px;font-size:11px;color:var(--b-light);font-style:italic;text-align:center;background:var(--b-warm);}
        </style>

        <script>
        jQuery(function($){
            /* Tab switching */
            var $btns   = $('.ep-tab-btn');
            var $panels = $('.ep-tab-panel');
            $btns.on('click', function(){
                var tab = $(this).data('tab');
                $btns.removeClass('is-active');
                $panels.removeClass('is-active');
                $(this).addClass('is-active');
                $('#ep-tab-' + tab).addClass('is-active');
                try{ localStorage.setItem('bacera_ep_tab', tab); }catch(e){}
            });
            try{
                var lt = localStorage.getItem('bacera_ep_tab');
                if(lt && $('[data-tab="'+lt+'"]').length) $('[data-tab="'+lt+'"]').trigger('click');
            }catch(e){}

            /* Character counters */
            function setupCounter(targetId, max){
                var $counter = $('[data-target="' + targetId + '"]');
                var $field   = $('#' + targetId);
                if(!$field.length || !$counter.length) return;
                function update(){
                    var len = $field.val().length;
                    $counter.text(len + ' / ' + max);
                    $counter.toggleClass('warn', len > max * .85 && len <= max);
                    $counter.toggleClass('over', len > max);
                }
                $field.on('input', update);
                update();
            }
            setupCounter('ep-story', 280);
            setupCounter('ep-seo-title', 60);
            setupCounter('ep-seo-desc', 160);

            /* Long description char count */
            var $ld = $('#ep-long-desc'), $ldc = $('#ep-long-desc-count');
            $ld.on('input', function(){
                $ldc.text($(this).val().length.toLocaleString() + ' ký tự');
            }).trigger('input');

            /* HTML preview toggle */
            $('#ep-preview-toggle').on('click', function(){
                var $code    = $('#ep-long-desc');
                var $preview = $('#ep-html-preview');
                var $content = $('#ep-preview-content');
                if($preview.is(':visible')){
                    $preview.hide(); $code.show();
                    $(this).html('<svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Xem trước');
                }else{
                    $content.html($code.val() || '<em style="color:#aaa">Chưa có nội dung HTML</em>');
                    $code.hide(); $preview.show();
                    $(this).html('<svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><line x1="1" y1="1" x2="23" y2="23"/></svg> Chỉnh sửa');
                }
            });

            /* SEO live preview */
            var defaultTitle  = <?php echo json_encode($pname); ?>;
            var defaultSlug   = <?php echo json_encode(sanitize_title($pname)); ?>;
            function updateSeo(){
                $('#ep-seo-prev-title').text($('#ep-seo-title').val() || defaultTitle);
                $('#ep-seo-prev-slug').text($('#ep-slug').val() || defaultSlug);
                $('#ep-seo-prev-desc').text($('#ep-seo-desc').val() || 'Thêm meta description để hiển thị tại đây...');
            }
            $('#ep-seo-title,#ep-slug,#ep-seo-desc').on('input', updateSeo);
            updateSeo();

            /* WP Media gallery */
            var frame;
            var pid   = '<?php echo esc_js($pid); ?>';
            var aj    = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
            var nonce = '<?php echo esc_js(wp_create_nonce('bprod_add_images_'.$pid)); ?>';

            function openMedia(){
                if(frame){ frame.open(); return; }
                frame = wp.media({ title:'Chọn ảnh cho sản phẩm', button:{text:'Thêm ảnh đã chọn'}, multiple:true });
                frame.on('select', function(){
                    var selected = frame.state().get('selection').toJSON();
                    if(!selected.length) return;
                    var $prog = $('#b-gal-progress');
                    $prog.css('display','flex');
                    $('#b-gal-prog-text').text('Đang lưu ' + selected.length + ' ảnh...');
                    $('#ep-upload-bar').css('width','20%');
                    $('#b-gal-media,#ep-drop-trigger').prop('disabled', true);

                    $.ajax({
                        url:aj, method:'POST',
                        data:{ action:'bprod_add_images', nonce:nonce, pid:pid,
                               images:selected.map(function(a){ return {id:a.id,url:a.url,alt:a.alt||a.title||''}; }) },
                        success:function(r){
                            $('#ep-upload-bar').css('width','80%');
                            if(r.success){
                                $('#b-gal-prog-text').text('Đã thêm ' + (r.data.added||0) + ' ảnh!');
                                $('#ep-upload-bar').css('width','100%');
                                $('#ep-drop-zone').hide();
                                var $grid = $('#b-gal-grid');
                                $grid.show();
                                var curIdx = $grid.find('.ep-gal-item').length;
                                (r.data.items||[]).forEach(function(img, i){
                                    var $item = $('<div class="ep-gal-item">').css('--idx', curIdx+i).html(
                                        '<div class="ep-gal-img-wrap"><img src="'+img.url+'" alt="'+img.alt+'" loading="lazy"></div>'+
                                        '<div class="ep-gal-overlay"><a href="'+img.del_url+'" class="ep-gal-del" onclick="return confirm(\'Xóa ảnh?\')"><svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg></a></div>'
                                    );
                                    $grid.append($item);
                                });
                                var cnt = $grid.find('.ep-gal-item').length;
                                $('#b-gal-count').text(cnt + ' ảnh');
                                $('#ep-tab-gal-count').text(cnt);
                                setTimeout(function(){
                                    $prog.hide();
                                    $('#ep-upload-bar').css('width','0%');
                                    $('#b-gal-media,#ep-drop-trigger').prop('disabled', false);
                                }, 2000);
                            }else{
                                $('#b-gal-prog-text').text('Lỗi: '+(r.data||'Không xác định'));
                                $('#b-gal-media,#ep-drop-trigger').prop('disabled', false);
                            }
                        },
                        error:function(){
                            $('#b-gal-prog-text').text('Lỗi kết nối.');
                            $('#b-gal-media,#ep-drop-trigger').prop('disabled', false);
                        }
                    });
                });
                frame.open();
            }
            $('#b-gal-media,#ep-drop-trigger').on('click', openMedia);

            /* Toggle switches */
            $('.b-toggle-cb').each(function(){
                var $cb=$(this), $track=$cb.siblings('.b-toggle-track');
                function sync(){ $track.toggleClass('b-toggle-on', $cb.is(':checked')); }
                sync(); $cb.on('change', sync);
            });

            /* Save button feedback */
            $('#b-meta-form').on('submit', function(){
                $('#ep-save-btn').html('<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="animation:spin .7s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Đang lưu...')
                               .prop('disabled', true);
            });
        });
        </script>
        <?php
        $this->ui_close();
    }


    /* ═══════════════════════════════════════════════════
       SUBPAGE: EDIT CATEGORY
    ═══════════════════════════════════════════════════ */

    private function page_edit_category( string $cid ): void {
        if (!$cid) wp_die('Thiếu ID danh mục.');
        $categories  = $this->fetch_categories();
        $pancake_cat = [];
        foreach ($categories as $c) { if ($c['id']===$cid) { $pancake_cat=$c; break; } }

        $meta       = $this->db_get_category_meta($cid);
        $cname      = $pancake_cat['name'] ?? 'Danh mục #'.$cid;
        $saved      = !empty($_GET['saved']);
        $img_url    = $meta['image_url']  ?? '';
        $banner_url = $meta['banner_url'] ?? '';

        $this->ui_shell('categories', [
            'label' => $cname,
            'back'  => admin_url('admin.php?page=bacera-products&tab=categories'),
        ]);
        ?>

        <?php if ($saved): ?>
        <div class="b-toast b-toast-show" id="b-save-cat-toast">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            Đã lưu thành công!
        </div>
        <script>setTimeout(function(){var t=document.getElementById('b-save-cat-toast');if(t)t.classList.remove('b-toast-show');},3500);</script>
        <?php endif; ?>

        <div class="ecat-wrap">

            <!-- ── Image picker: Avatar ── -->
            <div class="b-section">
                <div class="b-section-hd">
                    <span class="b-section-icon">🖼️</span>
                    <span>Ảnh đại diện danh mục</span>
                    <span class="b-section-badge ecat-badge-required">Bắt buộc</span>
                </div>
                <div class="b-section-body">
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="b-cat-form">
                        <input type="hidden" name="action" value="bprod_save_category">
                        <input type="hidden" name="cid" value="<?php echo esc_attr($cid); ?>">
                        <?php wp_nonce_field('bprod_save_cat_'.$cid); ?>
                        <!-- hidden fields to keep other meta unchanged -->
                        <input type="hidden" name="name_override"   value="<?php echo esc_attr($meta['name_override']??''); ?>">
                        <input type="hidden" name="slug"            value="<?php echo esc_attr($meta['slug']??''); ?>">
                        <input type="hidden" name="description"     value="<?php echo esc_attr($meta['description']??''); ?>">
                        <input type="hidden" name="sort_order"      value="<?php echo esc_attr($meta['sort_order']??0); ?>">
                        <input type="hidden" name="is_featured"     value="<?php echo ($meta['is_featured']??0)?1:0; ?>">
                        <input type="hidden" name="is_active"       value="<?php echo ($meta['is_active']??1)?1:0; ?>">
                        <input type="hidden" name="banner_url"      id="ecat-banner-hidden" value="<?php echo esc_attr($banner_url); ?>">

                        <!-- Big image picker area -->
                        <div class="ecat-picker-layout">

                            <!-- Current image preview (big clickable) -->
                            <div class="ecat-img-stage" id="ecat-img-stage">
                                <?php if ($img_url): ?>
                                <img src="<?php echo esc_url($img_url); ?>" alt="" class="ecat-img-current" id="ecat-img-current">
                                <div class="ecat-img-change-hint">
                                    <svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 0 2-2l1-3h4l1 3h10l1-3h4l1 3a2 2 0 0 0 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                    <span>Đổi ảnh</span>
                                </div>
                                <?php else: ?>
                                <div class="ecat-img-empty" id="ecat-img-empty">
                                    <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                    <p>Chưa có ảnh đại diện</p>
                                    <p class="ecat-empty-sub">Nhấn để chọn ảnh từ thư viện WordPress</p>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Controls -->
                            <div class="ecat-controls">
                                <input type="hidden" name="attachment_id" id="ecat-att-id" value="<?php echo esc_attr($meta['attachment_id']??0); ?>">
                                <input type="text" name="image_url" id="ecat-img-url" class="b-input"
                                       value="<?php echo esc_attr($img_url); ?>"
                                       placeholder="Dán URL ảnh hoặc chọn từ thư viện...">

                                <button type="button" id="ecat-media-btn" class="b-btn b-btn-primary">
                                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                    Chọn ảnh từ thư viện WordPress
                                </button>

                                <?php if ($img_url): ?>
                                <button type="button" id="ecat-clear-btn" class="b-btn b-btn-ghost">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                    Xóa ảnh hiện tại
                                </button>
                                <?php else: ?>
                                <button type="button" id="ecat-clear-btn" class="b-btn b-btn-ghost" style="display:none;">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                    Xóa ảnh hiện tại
                                </button>
                                <?php endif; ?>

                                <!-- Banner (secondary) -->
                                <div class="ecat-divider"></div>
                                <label class="ecat-label-sm">Banner trang danh mục (tuỳ chọn)</label>
                                <div style="display:flex;gap:8px;">
                                    <input type="text" id="ecat-banner-url-input" class="b-input" style="flex:1;"
                                           value="<?php echo esc_attr($banner_url); ?>"
                                           placeholder="URL banner 1920×400px...">
                                    <button type="button" id="ecat-banner-media-btn" class="b-btn b-btn-outline b-btn-sm" style="flex-shrink:0;">
                                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                        Chọn
                                    </button>
                                </div>

                                <div class="ecat-actions">
                                    <button type="submit" class="b-btn b-btn-primary b-btn-lg" id="ecat-save-btn">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                                        Lưu ảnh &amp; cài đặt
                                    </button>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=bacera-products&tab=categories')); ?>"
                                       class="b-btn b-btn-ghost">← Quay lại</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Pancake info card (compact) -->
            <div class="b-section b-section-flat" style="margin-top:12px;">
                <div class="ecat-pancake-row">
                    <div class="ecat-pancake-id">
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                        Pancake POS
                    </div>
                    <span class="ecat-pancake-name"><?php echo esc_html($cname); ?></span>
                    <code class="b-sku">#<?php echo esc_html($cid); ?></code>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=bacera-products&cat_id='.urlencode($cid))); ?>"
                       class="b-btn b-btn-outline b-btn-sm" style="margin-left:auto;">
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                        Xem sản phẩm
                    </a>
                </div>
            </div>

        </div><!-- .ecat-wrap -->

        <style>
        .ecat-wrap{max-width:740px;}
        .ecat-badge-required{background:var(--b-red-bg);color:var(--b-red);border-color:var(--b-red-border);font-weight:700;}

        /* Picker layout: image stage + controls side by side */
        .ecat-picker-layout{display:grid;grid-template-columns:280px 1fr;gap:20px;align-items:start;}
        @media(max-width:680px){.ecat-picker-layout{grid-template-columns:1fr;}}

        /* Image stage — big clickable preview */
        .ecat-img-stage{
            position:relative; border-radius:var(--b-r-lg);
            overflow:hidden; cursor:pointer;
            border:2px solid var(--b-border);
            background:var(--b-warm); aspect-ratio:1;
            transition:border-color .18s, box-shadow .18s;
        }
        .ecat-img-stage:hover{border-color:var(--b-accent); box-shadow:0 0 0 4px rgba(196,124,58,.12);}
        .ecat-img-current{width:100%;height:100%;object-fit:cover;display:block;}
        .ecat-img-change-hint{
            position:absolute;inset:0;
            background:rgba(30,15,5,.52);
            display:flex;flex-direction:column;align-items:center;justify-content:center;
            gap:8px;color:#fff;font-size:14px;font-weight:700;
            opacity:0;transition:opacity .18s;
        }
        .ecat-img-stage:hover .ecat-img-change-hint{opacity:1;}
        .ecat-img-empty{
            display:flex;flex-direction:column;align-items:center;justify-content:center;
            height:100%;gap:12px;color:var(--b-lighter);padding:24px;text-align:center;
        }
        .ecat-img-empty svg{opacity:.4;}
        .ecat-img-empty p{font-size:13px;font-weight:600;color:var(--b-mid);margin:0;}
        .ecat-empty-sub{font-size:12px!important;color:var(--b-light)!important;font-weight:400!important;}

        /* Controls column */
        .ecat-controls{display:flex;flex-direction:column;gap:10px;}
        .ecat-divider{height:1px;background:var(--b-border);margin:6px 0;}
        .ecat-label-sm{font-size:12px;font-weight:600;color:var(--b-mid);}
        .ecat-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;padding-top:12px;border-top:1px solid var(--b-border);}

        /* Pancake info row */
        .ecat-pancake-row{
            display:flex;align-items:center;gap:10px;flex-wrap:wrap;
            padding:10px 4px;
        }
        .ecat-pancake-id{
            display:flex;align-items:center;gap:5px;
            font-size:11px;font-weight:700;color:var(--b-mid);white-space:nowrap;
        }
        .ecat-pancake-name{font-size:13px;font-weight:700;color:var(--b-brown);}
        </style>

        <script>
        jQuery(function($){
            var fImg, fBanner;

            /* Click stage = open media */
            $('#ecat-img-stage, #ecat-media-btn').on('click', openImgMedia);

            function openImgMedia(){
                if (fImg) { fImg.open(); return; }
                fImg = wp.media({ title:'Chọn ảnh đại diện danh mục', button:{text:'Dùng ảnh này'}, multiple:false });
                fImg.on('select', function(){
                    var att = fImg.state().get('selection').first().toJSON();
                    setImage(att.id, att.url);
                });
                fImg.open();
            }

            function setImage(attId, url){
                $('#ecat-att-id').val(attId);
                $('#ecat-img-url').val(url);
                // Update stage
                var $stage = $('#ecat-img-stage');
                $stage.find('.ecat-img-empty').hide();
                if ($stage.find('.ecat-img-current').length) {
                    $stage.find('.ecat-img-current').attr('src', url);
                } else {
                    $('<img class="ecat-img-current" id="ecat-img-current">').attr('src', url).prependTo($stage);
                    $('<div class="ecat-img-change-hint"><svg width="20" height="20" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 0 2-2l1-3h4l1 3h10l1-3h4l1 3a2 2 0 0 0 2 2z"/><circle cx="12" cy="13" r="4"/></svg><span>Đổi ảnh</span></div>').appendTo($stage);
                }
                $('#ecat-clear-btn').show();
            }

            /* Manual URL input */
            $('#ecat-img-url').on('input', function(){
                var url = $(this).val().trim();
                if (url) {
                    setImage(0, url);
                } else {
                    clearImage();
                }
            });

            /* Clear */
            $('#ecat-clear-btn').on('click', clearImage);
            function clearImage(){
                $('#ecat-att-id').val(0);
                $('#ecat-img-url').val('');
                var $stage = $('#ecat-img-stage');
                $stage.find('.ecat-img-current').remove();
                $stage.find('.ecat-img-change-hint').remove();
                if (!$stage.find('.ecat-img-empty').length) {
                    $stage.append('<div class="ecat-img-empty" id="ecat-img-empty"><svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg><p>Chưa có ảnh đại diện</p><p class="ecat-empty-sub">Nhấn để chọn ảnh từ thư viện WordPress</p></div>');
                } else {
                    $stage.find('.ecat-img-empty').show();
                }
                $('#ecat-clear-btn').hide();
            }

            /* Banner media */
            $('#ecat-banner-media-btn').on('click', function(){
                if (fBanner) { fBanner.open(); return; }
                fBanner = wp.media({ title:'Chọn banner danh mục', button:{text:'Dùng banner này'}, multiple:false });
                fBanner.on('select', function(){
                    var att = fBanner.state().get('selection').first().toJSON();
                    $('#ecat-banner-url-input').val(att.url);
                    $('#ecat-banner-hidden').val(att.url);
                });
                fBanner.open();
            });
            $('#ecat-banner-url-input').on('input', function(){
                $('#ecat-banner-hidden').val($(this).val());
            });

            /* Save feedback */
            $('#b-cat-form').on('submit', function(){
                $('#ecat-save-btn').html('<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="animation:spin .7s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Đang lưu...').prop('disabled', true);
            });
        });
        </script>
        <?php
        $this->ui_close();
    }

    /* ═══════════════════════════════════════════════════
       SHARED UI SHELL
    ═══════════════════════════════════════════════════ */

    private function ui_shell( string $active_tab, array $crumb = [] ): void {
        $nonce = wp_create_nonce('bprod_nonce');
        $tabs  = [
            'products'   => ['🏺', 'Sản phẩm',  admin_url('admin.php?page=bacera-products&tab=products')],
            'categories' => ['🏷️', 'Danh mục',  admin_url('admin.php?page=bacera-products&tab=categories')],
            'reviews'    => ['💬', 'Đánh giá',  admin_url('admin.php?page=bacera-products&tab=reviews')],
        ];
        ?>
<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>
<style>
/* ═══════════════════════════════════════
   BACERA ADMIN — DESIGN SYSTEM v2
═══════════════════════════════════════ */
/* ── Tokens ── */
:root{
    --b-cream:#fdfaf5; --b-warm:#f5ede0; --b-warm2:#f0e2cc;
    --b-border:#e8d8c0; --b-border2:#d5c4a8;
    --b-brown:#3d2f26; --b-brown2:#5a3e2e;
    --b-mid:#7c5c3a; --b-light:#b5a090; --b-lighter:#d4c4b0;
    --b-accent:#c47c3a; --b-accent2:#d97706; --b-accent-light:#fef3e2;
    --b-green:#059669; --b-green-bg:#ecfdf5; --b-green-border:#a7f3d0;
    --b-red:#dc2626; --b-red-bg:#fef2f2; --b-red-border:#fecaca;
    --b-amber:#d97706; --b-amber-bg:#fff7ed; --b-amber-border:#fed7aa;
    --b-blue:#2563eb; --b-blue-bg:#eff6ff; --b-blue-border:#bfdbfe;
    --b-shadow: 0 1px 3px rgba(61,47,38,.08), 0 4px 16px rgba(61,47,38,.04);
    --b-shadow-md: 0 4px 12px rgba(61,47,38,.12), 0 16px 40px rgba(61,47,38,.08);
    --b-shadow-lg: 0 8px 24px rgba(61,47,38,.15), 0 24px 64px rgba(61,47,38,.12);
    --b-r:10px; --b-r-sm:7px; --b-r-lg:14px; --b-r-xl:20px;
}
/* ── Reset ── */
*{box-sizing:border-box; margin:0;}
/* ── Wrap ── */
.b-wrap{
    background:var(--b-cream); min-height:100vh;
    padding:24px 28px 100px;
    font-family:'Inter',system-ui,-apple-system,sans-serif;
    color:var(--b-brown); font-size:14px; line-height:1.5;
    background-image: radial-gradient(ellipse 90% 60% at 50% -10%, rgba(196,124,58,.07), transparent),
                      radial-gradient(ellipse 60% 40% at 90% 80%, rgba(196,124,58,.04), transparent);
}
/* ── Header ── */
.b-header{
    display:flex; align-items:flex-end; justify-content:space-between;
    flex-wrap:wrap; gap:16px; margin-bottom:24px;
    padding-bottom:20px; border-bottom:2px solid var(--b-border);
}
.b-h1{
    font-size:24px; font-weight:800; color:var(--b-brown);
    margin:0; letter-spacing:-.5px; line-height:1.2;
    display:flex; align-items:center; gap:10px;
}
.b-h1-sub{
    font-size:12px; color:var(--b-light); margin-top:5px;
    display:flex; align-items:center; gap:8px;
}
.b-h1-dot{width:3px;height:3px;border-radius:50%;background:var(--b-border);display:inline-block;}
.b-header-actions{display:flex; gap:8px; flex-wrap:wrap; align-items:center;}
/* ── Breadcrumb ── */
.b-crumb{
    display:flex; align-items:center; gap:6px;
    font-size:12px; margin-bottom:20px; color:var(--b-light);
    background:var(--b-warm); border:1px solid var(--b-border);
    padding:8px 14px; border-radius:var(--b-r-sm);
    box-shadow:var(--b-shadow);
}
.b-crumb a{color:var(--b-mid); text-decoration:none; font-weight:500;}
.b-crumb a:hover{color:var(--b-accent);}
.b-crumb-sep{color:var(--b-border); font-size:14px;}
.b-crumb-current{color:var(--b-brown); font-weight:700;}
/* ── Tabs ── */
.b-tabs{
    display:flex; gap:2px; margin-bottom:24px;
    background:var(--b-warm); padding:4px; border-radius:var(--b-r);
    border:1.5px solid var(--b-border); box-shadow:var(--b-shadow);
}
.b-tab{
    display:flex; align-items:center; gap:6px;
    padding:8px 18px; font-size:13px; font-weight:600;
    color:var(--b-light); text-decoration:none;
    border-radius:var(--b-r-sm); transition:all .2s; white-space:nowrap;
}
.b-tab:hover{color:var(--b-mid); background:rgba(255,255,255,.6);}
.b-tab.is-active{
    background:#fff; color:var(--b-brown);
    box-shadow: 0 1px 4px rgba(61,47,38,.12), 0 2px 8px rgba(61,47,38,.06);
}
/* ── Buttons ── */
.b-btn{
    display:inline-flex; align-items:center; gap:7px;
    padding:9px 18px; border-radius:var(--b-r-sm);
    font-size:13px; font-weight:600; cursor:pointer;
    border:none; text-decoration:none;
    transition:all .18s cubic-bezier(.4,0,.2,1);
    line-height:1.4; white-space:nowrap; font-family:inherit;
    position:relative; overflow:hidden;
}
.b-btn-primary{
    background:linear-gradient(135deg,var(--b-brown2),var(--b-brown));
    color:#fff; box-shadow:0 2px 8px rgba(61,47,38,.28);
}
.b-btn-primary:hover{
    background:linear-gradient(135deg,var(--b-brown),var(--b-brown2));
    color:#fff; transform:translateY(-1px);
    box-shadow:0 6px 16px rgba(61,47,38,.32);
}
.b-btn-primary:active{transform:translateY(0);}
.b-btn-success{
    background:linear-gradient(135deg,#065f46,var(--b-green));
    color:#fff; box-shadow:0 2px 8px rgba(5,150,105,.28);
}
.b-btn-success:hover{transform:translateY(-1px); color:#fff; box-shadow:0 5px 14px rgba(5,150,105,.32);}
.b-btn-outline{
    background:#fff; color:var(--b-mid);
    border:1.5px solid var(--b-border); box-shadow:var(--b-shadow);
}
.b-btn-outline:hover{background:var(--b-warm); color:var(--b-brown); border-color:var(--b-accent);}
.b-btn-ghost{background:transparent; color:var(--b-light); border:none;}
.b-btn-ghost:hover{color:var(--b-mid); background:rgba(0,0,0,.04);}
.b-btn-sm{padding:6px 12px; font-size:12px;}
.b-btn-lg{padding:12px 26px; font-size:14px; border-radius:var(--b-r); font-weight:700;}
.b-btn-block{width:100%; justify-content:center;}
/* ── Stats ── */
.b-stats{display:flex; gap:14px; flex-wrap:wrap; margin-bottom:24px;}
.b-stat{
    background:#fff; border:1.5px solid var(--b-border); border-radius:var(--b-r-lg);
    padding:16px 20px; flex:1; min-width:150px;
    display:flex; align-items:center; gap:16px;
    box-shadow:var(--b-shadow); transition:all .22s;
}
.b-stat:hover{transform:translateY(-3px); box-shadow:var(--b-shadow-md); border-color:var(--b-accent);}
.b-stat-anim{opacity:0; transform:translateY(12px); animation:statIn .4s ease forwards; animation-delay:var(--delay,0ms);}
@keyframes statIn{to{opacity:1; transform:none;}}
.b-stat-icon{
    width:46px; height:46px; border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    font-size:22px; flex-shrink:0;
    box-shadow: inset 0 1px 2px rgba(255,255,255,.3);
}
.b-stat-val{
    font-size:28px; font-weight:800; color:var(--b-brown);
    letter-spacing:-.6px; line-height:1;
}
.b-stat-lbl{
    font-size:11px; font-weight:600; color:var(--b-light);
    text-transform:uppercase; letter-spacing:.6px; margin-top:4px;
}
/* ── Toolbar ── */
.b-toolbar{
    display:flex; gap:10px; flex-wrap:wrap; align-items:center;
    justify-content:space-between; margin-bottom:20px;
}
.b-toolbar-left{display:flex; gap:10px; flex-wrap:wrap; align-items:center;}
.b-toolbar-right{display:flex; gap:8px; align-items:center;}
.b-search-box{
    display:flex; align-items:center; gap:8px;
    background:#fff; border:1.5px solid var(--b-border);
    border-radius:var(--b-r-sm); padding:0 12px;
    transition:border-color .15s, box-shadow .15s; box-shadow:var(--b-shadow);
    position:relative;
}
.b-search-box:focus-within{border-color:var(--b-accent); box-shadow:0 0 0 3px rgba(196,124,58,.1);}
.b-search-box svg{color:var(--b-light); flex-shrink:0;}
.b-search-input{
    border:none; outline:none; padding:9px 4px;
    font-size:13px; color:var(--b-brown); background:transparent;
    min-width:200px; font-family:inherit;
}
.b-search-clear{
    border:none; background:none; cursor:pointer;
    color:var(--b-light); font-size:14px; padding:4px; line-height:1;
    transition:color .15s;
}
.b-search-clear:hover{color:var(--b-red);}
.b-select-wrap{
    position:relative; display:flex; align-items:center;
    background:#fff; border:1.5px solid var(--b-border);
    border-radius:var(--b-r-sm); padding:0 10px;
    box-shadow:var(--b-shadow);
}
.b-select-wrap svg{color:var(--b-light); flex-shrink:0;}
.b-select{
    border:none; background:transparent; padding:9px 4px;
    font-size:13px; color:var(--b-brown); cursor:pointer;
    outline:none; font-family:inherit; appearance:none;
    padding-right:20px;
}
.b-view-toggle{
    display:flex; gap:2px; background:var(--b-warm);
    border:1.5px solid var(--b-border); border-radius:var(--b-r-sm);
    padding:3px; box-shadow:var(--b-shadow);
}
.b-view-btn{
    width:30px; height:30px; border:none; background:transparent;
    cursor:pointer; border-radius:5px;
    display:flex; align-items:center; justify-content:center;
    color:var(--b-light); transition:all .15s;
}
.b-view-btn:hover{background:rgba(255,255,255,.7); color:var(--b-mid);}
.b-view-btn.is-active{background:#fff; color:var(--b-brown); box-shadow:0 1px 3px rgba(0,0,0,.08);}
/* ── Notices ── */
.b-notice{
    display:flex; align-items:center; gap:10px;
    border-radius:var(--b-r-sm); padding:12px 16px;
    font-size:13px; font-weight:500; margin-bottom:16px;
    border:1.5px solid;
}
.b-notice-warn{background:var(--b-amber-bg); border-color:var(--b-amber-border); color:#92400e;}
.b-notice a{color:inherit; font-weight:700;}
.b-info-callout{
    display:flex; align-items:flex-start; gap:10px;
    background:var(--b-blue-bg); border:1.5px solid var(--b-blue-border);
    border-radius:var(--b-r-sm); padding:12px 16px;
    font-size:13px; color:#1e40af; margin-bottom:20px;
    line-height:1.5;
}
.b-info-callout svg{flex-shrink:0; margin-top:1px;}
/* ── Table ── */
.b-table-card{
    background:#fff; border:1.5px solid var(--b-border);
    border-radius:var(--b-r-lg); overflow:hidden; box-shadow:var(--b-shadow);
}
.b-table-header{
    display:flex; align-items:center; justify-content:space-between;
    padding:14px 18px; border-bottom:1.5px solid var(--b-border);
    background:linear-gradient(to right, var(--b-warm), var(--b-cream));
}
.b-table-title{
    display:flex; align-items:center; gap:10px;
    font-size:13px; font-weight:700; color:var(--b-mid);
}
.b-count-badge{
    background:var(--b-accent-light); color:var(--b-accent);
    border:1px solid rgba(196,124,58,.2); border-radius:20px;
    padding:2px 10px; font-size:11px; font-weight:700;
}
.b-table-meta{font-size:12px; color:var(--b-light);}
.b-table{width:100%; border-collapse:collapse; font-size:13px;}
.b-table thead tr{background:var(--b-warm);}
.b-table th{
    padding:10px 14px; text-align:left;
    font-size:10px; font-weight:700; text-transform:uppercase;
    letter-spacing:.7px; color:var(--b-mid); white-space:nowrap;
}
.b-th-thumb{width:80px;}
.b-th-sku{width:120px;}
.b-th-price{width:150px;}
.b-th-cat{width:180px;}
.b-th-content{width:130px;}
.b-th-status{width:120px;}
.b-th-action{width:90px; text-align:right;}
.b-td{
    padding:12px 14px; vertical-align:middle;
    border-top:1px solid var(--b-warm2);
}
.b-tr{transition:background .12s; animation:rowIn .3s ease forwards; opacity:0;}
.b-tr:nth-child(1){animation-delay:30ms;}
.b-tr:nth-child(2){animation-delay:60ms;}
.b-tr:nth-child(3){animation-delay:90ms;}
.b-tr:nth-child(4){animation-delay:120ms;}
.b-tr:nth-child(n+5){animation-delay:calc(var(--row-delay,0ms));}
@keyframes rowIn{from{opacity:0;transform:translateY(4px);}to{opacity:1;transform:none;}}
.b-tr:hover .b-td{background:#fefcf8;}
.b-td-thumb{width:80px;}
.b-td-name{max-width:240px;}
.b-td-sku{}
.b-td-price{}
.b-td-cats{max-width:180px;}
.b-td-content{}
.b-td-status{}
.b-td-action{text-align:right;}
/* Thumbnails */
.b-thumb-link{display:block; border-radius:var(--b-r-sm); overflow:hidden;}
.b-thumb{
    width:60px; height:60px; border-radius:var(--b-r-sm);
    background-size:cover; background-position:center;
    border:1.5px solid var(--b-border); transition:transform .2s;
}
.b-thumb-link:hover .b-thumb{transform:scale(1.06);}
.b-thumb-empty{
    display:flex; align-items:center; justify-content:center;
    font-size:24px; background:var(--b-warm);
}
/* Product info */
.b-product-name{
    font-weight:700; color:var(--b-brown); text-decoration:none;
    transition:color .12s; display:block; line-height:1.3;
}
.b-product-name:hover{color:var(--b-accent);}
.b-product-note{
    font-size:11px; color:var(--b-light); margin-top:4px;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;
    overflow:hidden; line-height:1.4;
}
.b-var-count{
    display:inline-flex; align-items:center; gap:4px;
    font-size:11px; color:var(--b-light); margin-top:4px;
}
.b-no-name{font-style:italic; color:var(--b-lighter);}
.b-no-cat{color:var(--b-lighter);}
/* SKU */
.b-sku{
    background:var(--b-warm); padding:3px 8px; border-radius:5px;
    font-size:11px; color:var(--b-mid);
    font-family:'SF Mono','Fira Code',monospace; font-style:normal;
    border:1px solid var(--b-border);
}
/* Price */
.b-price{font-weight:700; color:var(--b-brown); font-size:13px;}
.b-price-range{font-size:11px; color:var(--b-light);}
.b-price-dash{color:var(--b-lighter);}
/* Status indicators */
.b-status{
    display:inline-flex; align-items:center; gap:6px;
    border-radius:20px; padding:4px 10px;
    font-size:11px; font-weight:700; white-space:nowrap;
}
.b-status-live{
    background:var(--b-green-bg); color:#065f46;
    border:1px solid var(--b-green-border);
}
.b-status-draft{
    background:#f3f4f6; color:#6b7280;
    border:1px solid #e5e7eb;
}
.b-pulse{
    width:7px; height:7px; border-radius:50%;
    background:var(--b-green); flex-shrink:0;
    animation:pulse 2s ease-in-out infinite;
}
@keyframes pulse{
    0%,100%{opacity:1; transform:scale(1);}
    50%{opacity:.5; transform:scale(.8);}
}
/* Badges */
.b-badge{
    display:inline-flex; align-items:center; gap:4px;
    border-radius:20px; padding:3px 9px;
    font-size:11px; font-weight:700; white-space:nowrap;
    text-decoration:none; transition:all .15s;
}
.b-badge-success{background:var(--b-green-bg); color:#065f46; border:1px solid var(--b-green-border);}
.b-badge-warn{background:var(--b-amber-bg); color:#c2410c; border:1px solid var(--b-amber-border);}
.b-badge-info{background:var(--b-blue-bg); color:#1d4ed8; border:1px solid var(--b-blue-border);}
.b-badge-inactive{background:#f3f4f6; color:#6b7280; border:1px solid #e5e7eb;}
.b-badge-featured{
    background:linear-gradient(135deg,var(--b-accent-light),#fde9cc);
    color:var(--b-accent); border:1px solid rgba(196,124,58,.25);
}
/* Action button */
.b-action-btn{
    display:inline-flex; align-items:center; gap:6px;
    padding:6px 12px; border-radius:var(--b-r-sm);
    font-size:12px; font-weight:600;
    background:var(--b-warm); color:var(--b-mid);
    border:1.5px solid var(--b-border); text-decoration:none;
    transition:all .15s; white-space:nowrap;
}
.b-action-btn:hover{
    background:var(--b-brown); color:#fff;
    border-color:var(--b-brown); transform:translateY(-1px);
    box-shadow:0 3px 8px rgba(61,47,38,.18);
}
.b-action-btn-primary{
    background:var(--b-brown); color:#fff; border-color:var(--b-brown);
}
.b-action-btn-primary:hover{
    background:var(--b-brown2); border-color:var(--b-brown2);
}
/* Category badges / pills */
.b-cat-badge{
    display:inline-block;
    background:linear-gradient(135deg,#fdf3e7,#fde9cc);
    border:1px solid #e8d5b4; color:#8b5e2f;
    border-radius:20px; padding:2px 9px;
    font-size:11px; font-weight:600; margin:2px 2px;
}
.b-cat-badge-sm{
    display:inline-block; background:var(--b-warm);
    border:1px solid var(--b-border); color:var(--b-mid);
    border-radius:20px; padding:1px 8px;
    font-size:10px; font-weight:600; margin:1px;
}
/* Pagination */
.b-pagination{
    display:flex; align-items:center; gap:10px;
    justify-content:center; margin-top:22px;
}
.b-page-btn{
    display:flex; align-items:center; justify-content:center;
    width:38px; height:38px; border-radius:var(--b-r-sm);
    background:#fff; border:1.5px solid var(--b-border);
    color:var(--b-mid); text-decoration:none; transition:all .15s;
    box-shadow:var(--b-shadow);
}
.b-page-btn:hover{background:var(--b-brown); color:#fff; border-color:var(--b-brown); box-shadow:var(--b-shadow-md);}
.b-page-info{font-size:13px; font-weight:500; color:var(--b-mid); padding:0 4px;}
/* Empty state */
.b-empty-state{
    text-align:center; padding:80px 20px;
    background:#fff; border-radius:var(--b-r-lg);
    border:2px dashed var(--b-border);
}
.b-empty-icon{font-size:56px; margin-bottom:16px;}
.b-empty-title{font-size:17px; font-weight:700; color:var(--b-mid); margin-bottom:8px;}
.b-empty-sub{font-size:13px; color:var(--b-light); line-height:1.6;}
/* ═══════════════════════
   PRODUCT CARD GRID
═══════════════════════ */
.b-prod-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(210px, 1fr));
    gap:18px;
}
.b-prod-card{
    background:#fff; border:1.5px solid var(--b-border);
    border-radius:var(--b-r-lg); overflow:hidden;
    box-shadow:var(--b-shadow); transition:all .22s;
    animation:cardIn .35s ease forwards; opacity:0;
    animation-delay:var(--card-delay, 0ms);
}
@keyframes cardIn{from{opacity:0;transform:scale(.96);}to{opacity:1;transform:scale(1);}}
.b-prod-card:hover{transform:translateY(-4px) scale(1.01); box-shadow:var(--b-shadow-md); border-color:var(--b-accent);}
.b-prod-card-img-wrap{display:block; position:relative; overflow:hidden;}
.b-prod-card-img{
    height:180px; background-size:cover; background-position:center;
    transition:transform .4s ease;
}
.b-prod-card:hover .b-prod-card-img{transform:scale(1.05);}
.b-prod-card-img-empty{
    height:180px; display:flex; align-items:center; justify-content:center;
    font-size:48px; background:linear-gradient(135deg,var(--b-warm),var(--b-warm2));
}
.b-prod-card-overlay{
    position:absolute; inset:0;
    background:linear-gradient(to top, rgba(61,47,38,.7) 0%, transparent 60%);
    opacity:0; transition:opacity .25s;
    display:flex; align-items:flex-end; padding:14px;
}
.b-prod-card:hover .b-prod-card-overlay{opacity:1;}
.b-overlay-edit{
    color:#fff; font-size:12px; font-weight:700;
    letter-spacing:.5px; text-transform:uppercase;
}
.b-prod-card-badge{
    position:absolute; top:10px; left:10px;
    border-radius:20px; padding:3px 9px;
    font-size:10px; font-weight:700; letter-spacing:.3px;
}
.b-prod-card-badge-live{background:rgba(5,150,105,.9); color:#fff;}
.b-prod-card-badge-draft{background:rgba(107,114,128,.85); color:#fff;}
.b-prod-card-content-dot{
    position:absolute; top:10px; right:10px;
    width:22px; height:22px; border-radius:50%;
    background:var(--b-brown); color:#fff;
    font-size:11px; font-weight:700;
    display:flex; align-items:center; justify-content:center;
    box-shadow:0 1px 4px rgba(0,0,0,.25);
}
.b-prod-card-body{padding:12px 14px;}
.b-prod-card-name{
    font-size:13px; font-weight:700; color:var(--b-brown);
    text-decoration:none; display:block; line-height:1.35;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    transition:color .12s;
}
.b-prod-card-name:hover{color:var(--b-accent);}
.b-prod-card-price{font-size:13px; font-weight:700; color:var(--b-accent); margin-top:4px;}
.b-prod-card-price-range{font-weight:400; color:var(--b-light); font-size:11px;}
.b-prod-card-cats{margin-top:8px; display:flex; flex-wrap:wrap; gap:3px;}
/* ═══════════════════════
   CATEGORY LIST
═══════════════════════ */
.b-cat-list{
    background:#fff; border:1.5px solid var(--b-border);
    border-radius:var(--b-r-lg); overflow:hidden; box-shadow:var(--b-shadow);
}
.b-cat-list-header{
    display:flex; align-items:center; justify-content:space-between;
    padding:14px 20px; border-bottom:1.5px solid var(--b-border);
    background:linear-gradient(to right, var(--b-warm), var(--b-cream));
}
.b-cat-list-title{
    display:flex; align-items:center; gap:10px;
    font-size:13px; font-weight:700; color:var(--b-mid);
}
.b-cat-list-legend{display:flex; gap:12px; font-size:11px; color:var(--b-light);}
.b-legend-item{display:flex; align-items:center; gap:5px;}
.b-legend-dot{width:8px; height:8px; border-radius:50%;}
.b-cat-row{
    display:flex; align-items:center; gap:16px;
    padding:14px 20px; border-bottom:1px solid var(--b-warm2);
    transition:background .15s;
    animation:rowIn .3s ease forwards; opacity:0;
    animation-delay:var(--row-delay, 0ms);
}
.b-cat-row:last-child{border-bottom:none;}
.b-cat-row:hover{background:var(--b-cream);}
.b-cat-row-inactive{opacity:.6;}
/* Cat thumbnail */
.b-cat-row-thumb{flex-shrink:0;}
.b-cat-thumb{
    width:64px; height:64px; border-radius:10px;
    background-size:cover; background-position:center;
    border:1.5px solid var(--b-border); transition:transform .2s;
}
.b-cat-row:hover .b-cat-thumb{transform:scale(1.05);}
.b-cat-thumb-empty{
    display:flex; align-items:center; justify-content:center;
    background:linear-gradient(135deg,var(--b-warm),var(--b-warm2));
    color:var(--b-light);
}
/* Cat info */
.b-cat-row-info{flex:1; min-width:0;}
.b-cat-row-top{
    display:flex; align-items:center; gap:10px; flex-wrap:wrap;
    margin-bottom:6px;
}
.b-cat-row-name{
    font-size:14px; font-weight:700; color:var(--b-brown);
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.b-cat-row-orig{font-size:11px; color:var(--b-light); font-weight:400;}
.b-cat-row-badges{display:flex; gap:5px; flex-wrap:wrap;}
.b-cat-row-meta{
    display:flex; align-items:center; gap:14px; flex-wrap:wrap;
    font-size:11px; color:var(--b-light);
}
.b-cat-row-meta-item{
    display:flex; align-items:center; gap:4px;
}
.b-cat-row-meta-item code{font-size:10px; background:var(--b-warm); padding:1px 5px; border-radius:3px; color:var(--b-mid);}
.b-cat-row-desc{font-style:italic; max-width:280px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;}
/* Banner preview strip */
.b-cat-row-banner{
    width:100px; height:56px; border-radius:8px; flex-shrink:0;
    background-size:cover; background-position:center;
    border:1.5px solid var(--b-border);
    box-shadow:var(--b-shadow);
}
/* Cat row actions */
.b-cat-row-actions{display:flex; gap:8px; flex-shrink:0;}
/* ═══════════════════════
   EDIT LAYOUT
═══════════════════════ */
.b-edit-layout{display:grid; grid-template-columns:1fr 300px; gap:24px; align-items:start;}
@media(max-width:960px){.b-edit-layout{grid-template-columns:1fr;}}
/* Product hero */
.b-product-hero{
    position:relative; border-radius:var(--b-r-lg); overflow:hidden;
    margin-bottom:20px; min-height:110px;
    background:linear-gradient(135deg,var(--b-brown),var(--b-brown2));
    box-shadow:var(--b-shadow-md);
}
.b-product-hero-bg{
    position:absolute; inset:0; background-size:cover; background-position:center;
    filter:blur(4px) brightness(.4); transform:scale(1.05);
}
.b-product-hero-overlay{position:absolute; inset:0; background:rgba(61,47,38,.5);}
.b-product-hero-content{
    position:relative; z-index:1;
    display:flex; align-items:center; gap:16px; padding:18px 22px;
}
.b-product-hero-img{
    width:68px; height:68px; border-radius:var(--b-r-sm);
    object-fit:cover; border:2px solid rgba(255,255,255,.25); flex-shrink:0;
}
.b-product-hero-placeholder{
    width:68px; height:68px; border-radius:var(--b-r-sm);
    background:rgba(255,255,255,.1); border:2px solid rgba(255,255,255,.15);
    display:flex; align-items:center; justify-content:center;
    font-size:28px; flex-shrink:0;
}
.b-product-hero-info{flex:1; min-width:0;}
.b-product-hero-name{font-size:17px; font-weight:800; color:#fff; line-height:1.3;}
.b-product-hero-meta{
    display:flex; align-items:center; gap:10px;
    margin-top:8px; flex-wrap:wrap;
}
.b-hero-sku{
    background:rgba(255,255,255,.15); padding:2px 8px;
    border-radius:4px; font-size:11px; color:rgba(255,255,255,.8);
    font-family:'SF Mono',monospace; border:1px solid rgba(255,255,255,.2);
}
.b-hero-price{font-weight:700; color:var(--b-accent2); font-size:14px;}
.b-hero-pill{
    border-radius:20px; padding:3px 10px; font-size:11px; font-weight:700;
}
.b-hero-pill-live{background:rgba(5,150,105,.25); color:#34d399; border:1px solid rgba(52,211,153,.3);}
.b-hero-pill-draft{background:rgba(255,255,255,.1); color:rgba(255,255,255,.6); border:1px solid rgba(255,255,255,.2);}
.b-product-hero-badge{
    font-size:10px; font-weight:700; letter-spacing:.7px; text-transform:uppercase;
    background:rgba(255,255,255,.12); color:rgba(255,255,255,.7);
    padding:4px 10px; border-radius:20px; border:1px solid rgba(255,255,255,.15);
    flex-shrink:0;
}
/* Sections */
.b-section{
    background:#fff; border:1.5px solid var(--b-border);
    border-radius:var(--b-r-lg); overflow:hidden;
    margin-bottom:18px; box-shadow:var(--b-shadow);
}
.b-section-hd{
    display:flex; align-items:center; gap:9px; padding:13px 20px;
    font-size:14px; font-weight:700; color:var(--b-brown);
    border-bottom:1px solid var(--b-border);
    background:linear-gradient(to right, var(--b-warm), transparent);
}
.b-section-icon{font-size:16px; line-height:1;}
.b-section-badge{
    margin-left:auto; background:var(--b-warm); color:var(--b-mid);
    border:1px solid var(--b-border); border-radius:20px;
    padding:2px 9px; font-size:11px; font-weight:600;
}
.b-section-body{padding:18px 20px;}
.b-section-flat .b-toggle-row{padding:16px 20px;}
/* Form fields */
.b-field{margin-bottom:16px;}
.b-field:last-child{margin-bottom:0;}
.b-field label{
    display:block; font-size:11px; font-weight:700;
    color:var(--b-mid); text-transform:uppercase;
    letter-spacing:.6px; margin-bottom:7px;
}
.b-hint{
    font-size:11px; font-weight:400; color:var(--b-light);
    text-transform:none; letter-spacing:0; margin-left:4px;
}
.b-field-row{display:flex; gap:14px; flex-wrap:wrap;}
.b-field-row .b-field{flex:1; min-width:150px;}
.b-input{
    width:100%; padding:9px 12px;
    border:1.5px solid var(--b-border); border-radius:var(--b-r-sm);
    font-size:13px; color:var(--b-brown); outline:none;
    transition:border-color .15s, box-shadow .15s;
    background:#fff; font-family:inherit;
}
.b-input:focus{border-color:var(--b-accent); box-shadow:0 0 0 3px rgba(196,124,58,.12);}
.b-textarea{
    width:100%; padding:10px 12px;
    border:1.5px solid var(--b-border); border-radius:var(--b-r-sm);
    font-size:13px; color:var(--b-brown); outline:none;
    transition:border-color .15s, box-shadow .15s;
    resize:vertical; font-family:inherit; background:#fff; line-height:1.6;
}
.b-textarea:focus{border-color:var(--b-accent); box-shadow:0 0 0 3px rgba(196,124,58,.12);}
.b-textarea-code{font-family:'SF Mono','Fira Code',Monaco,monospace; font-size:12px;}
.b-form-footer{
    display:flex; align-items:center; gap:12px;
    padding:16px 20px; background:var(--b-warm);
    border-top:1px solid var(--b-border); flex-wrap:wrap;
}
/* Toggle */
.b-toggle-row{display:flex; align-items:center; gap:12px; cursor:pointer;}
.b-toggle-group{display:flex; gap:0; flex-direction:column;}
.b-toggle-group .b-toggle-row{
    padding:14px 20px; border-bottom:1px solid var(--b-border);
    transition:background .15s;
}
.b-toggle-group .b-toggle-row:last-child{border-bottom:none;}
.b-toggle-group .b-toggle-row:hover{background:var(--b-cream);}
.b-toggle-track{
    width:42px; height:24px; border-radius:12px;
    background:#e5e7eb; border:2px solid #e5e7eb;
    transition:all .2s; position:relative; flex-shrink:0;
}
.b-toggle-track.b-toggle-on{background:var(--b-accent); border-color:var(--b-accent);}
.b-toggle-thumb{
    width:18px; height:18px; border-radius:50%; background:#fff;
    position:absolute; top:1px; left:1px;
    transition:transform .2s cubic-bezier(.34,1.56,.64,1);
    box-shadow:0 1px 4px rgba(0,0,0,.2);
}
.b-toggle-on .b-toggle-thumb{transform:translateX(18px);}
.b-toggle-cb{display:none;}
.b-toggle-label{font-size:13px; font-weight:600; color:var(--b-brown);}
.b-toggle-sub{font-size:12px; color:var(--b-light); margin-top:2px;}
/* Gallery */
.b-gallery-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(90px, 1fr));
    gap:10px; margin-bottom:14px;
}
.b-gal-item{
    position:relative; border-radius:var(--b-r-sm); overflow:hidden;
    border:1.5px solid var(--b-border); aspect-ratio:1;
    transition:all .18s;
}
.b-gal-item:hover{border-color:var(--b-accent); box-shadow:var(--b-shadow);}
.b-gal-item img{width:100%; height:100%; object-fit:cover;}
.b-gal-primary{
    position:absolute; bottom:5px; left:5px;
    background:linear-gradient(135deg,var(--b-accent),#d97706);
    color:#fff; font-size:10px; font-weight:700;
    padding:2px 7px; border-radius:4px;
}
.b-gal-del{
    position:absolute; top:5px; right:5px;
    width:22px; height:22px; background:var(--b-red); color:#fff;
    border-radius:50%; display:flex; align-items:center; justify-content:center;
    text-decoration:none; opacity:0; transition:opacity .15s;
    box-shadow:0 1px 4px rgba(0,0,0,.25);
}
.b-gal-item:hover .b-gal-del{opacity:1;}
.b-gal-empty{
    text-align:center; padding:32px 16px;
    border:2px dashed var(--b-border); border-radius:var(--b-r-sm);
    margin-bottom:16px;
}
.b-gal-empty svg{display:block; margin:0 auto 8px;}
.b-gal-empty p{font-size:12px; color:var(--b-light); margin:0;}
.b-gal-toolbar{display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:12px;}
/* Sidebar */
.b-edit-sidebar{}
.b-sidebar-block{
    background:#fff; border:1.5px solid var(--b-border);
    border-radius:var(--b-r-lg); overflow:hidden;
    margin-bottom:18px; box-shadow:var(--b-shadow);
}
.b-sidebar-sticky{position:sticky; top:32px;}
.b-sidebar-hd{
    display:flex; align-items:center; gap:8px; padding:12px 16px;
    font-size:13px; font-weight:700; color:var(--b-brown);
    border-bottom:1px solid var(--b-border); background:var(--b-warm);
}
.b-sidebar-hd-icon{font-size:16px;}
.b-readonly-badge{
    margin-left:auto; font-size:10px; font-weight:600;
    color:var(--b-light); text-transform:uppercase; letter-spacing:.5px;
    background:var(--b-warm); border:1px solid var(--b-border);
    padding:2px 7px; border-radius:20px;
}
.b-sidebar-hero{overflow:hidden; border-bottom:1px solid var(--b-border);}
.b-sidebar-hero img{width:100%; height:165px; object-fit:cover; display:block;}
.b-sidebar-body{padding:14px 16px;}
.b-sidebar-footer{
    padding:12px 16px; border-top:1px solid var(--b-border);
    background:var(--b-warm);
}
.b-sidebar-empty{font-size:13px; color:var(--b-light); padding:4px 0;}
.b-sb-row{
    padding:7px 0; border-bottom:1px solid var(--b-warm2);
    display:grid; grid-template-columns:84px 1fr;
    gap:8px; align-items:start;
}
.b-sb-row:last-child{border-bottom:none;}
.b-sb-lbl{font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--b-light); padding-top:2px;}
.b-sb-val{font-size:13px;}
.b-info-price{font-weight:700; color:var(--b-accent); font-size:14px;}
/* Image picker */
.b-img-picker{display:flex; gap:14px; align-items:flex-start; flex-wrap:wrap;}
.b-img-picker-preview{
    width:100px; height:100px; border-radius:var(--b-r-sm);
    background-size:cover; background-position:center;
    border:1.5px solid var(--b-border); flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    background-color:var(--b-warm); transition:border-color .15s;
}
.b-img-picker-preview:hover{border-color:var(--b-accent);}
.b-img-picker-preview-wide{
    width:100%; height:100px;
    border-radius:var(--b-r-sm);
}
.b-img-picker-placeholder{
    display:flex; flex-direction:column; align-items:center;
    gap:4px; color:var(--b-lighter); font-size:10px; text-align:center;
}
.b-img-picker-controls{
    flex:1; min-width:180px;
    display:flex; flex-direction:column; gap:8px;
}
/* Cat live preview */
.b-cat-live-preview{
    border-radius:var(--b-r-lg); overflow:hidden;
    margin-bottom:20px; box-shadow:var(--b-shadow-md);
}
.b-cat-live-banner{
    min-height:160px; position:relative;
    background:linear-gradient(135deg,var(--b-brown),#7a5030);
    background-size:cover; background-position:center;
    transition:background-image .3s;
}
.b-cat-live-banner-overlay{
    position:absolute; inset:0;
    background:linear-gradient(to top,rgba(0,0,0,.6) 0%,transparent 60%);
}
.b-cat-live-banner-content{
    position:relative; z-index:1;
    display:flex; align-items:flex-end; gap:14px;
    padding:20px;
}
.b-cat-live-avatar-wrap{flex-shrink:0;}
.b-cat-live-avatar{
    width:64px; height:64px; border-radius:50%;
    background-size:cover; background-position:center;
    background-color:rgba(255,255,255,.15);
    border:3px solid rgba(255,255,255,.4);
    display:flex; align-items:center; justify-content:center;
    overflow:hidden; transition:background-image .3s;
}
.b-cat-live-info{min-width:0;}
.b-cat-live-name{
    font-size:20px; font-weight:800; color:#fff;
    text-shadow:0 2px 4px rgba(0,0,0,.3); line-height:1.2;
}
.b-cat-live-meta{
    display:flex; align-items:center; gap:10px;
    margin-top:6px; flex-wrap:wrap;
}
.b-cat-live-id, .b-cat-live-slug{
    font-size:11px; color:rgba(255,255,255,.7);
    background:rgba(0,0,0,.2); padding:2px 8px; border-radius:20px;
}
.b-cat-live-label{
    background:linear-gradient(to right,var(--b-warm),var(--b-cream));
    padding:8px 16px; font-size:11px; font-weight:700;
    color:var(--b-light); letter-spacing:.5px; text-transform:uppercase;
    border-top:1px solid var(--b-border);
}
/* Toast */
.b-toast{
    position:fixed; top:40px; right:28px;
    background:linear-gradient(135deg,#065f46,var(--b-green));
    color:#fff; padding:12px 22px; border-radius:var(--b-r);
    font-size:13px; font-weight:600;
    display:flex; align-items:center; gap:10px;
    box-shadow:0 6px 24px rgba(5,150,105,.4);
    z-index:99999; transform:translateY(-20px);
    opacity:0; transition:all .35s cubic-bezier(.34,1.56,.64,1);
}
.b-toast-show{transform:translateY(0); opacity:1;}
/* Sync modal */
.b-modal-bg{
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.55); backdrop-filter:blur(6px);
    z-index:100000; align-items:center; justify-content:center;
}
.b-modal-bg.open{display:flex;}
.b-modal{
    background:#fff; border-radius:var(--b-r-xl);
    padding:30px; max-width:500px; width:94%;
    box-shadow:0 32px 80px rgba(0,0,0,.2);
    animation:modalIn .3s cubic-bezier(.34,1.56,.64,1);
}
@keyframes modalIn{from{opacity:0;transform:scale(.92);}to{opacity:1;transform:scale(1);}}
.b-modal-title{
    font-size:19px; font-weight:800; color:var(--b-brown);
    margin:0 0 6px; display:flex; align-items:center; gap:10px;
}
.b-modal-sub{font-size:13px; color:var(--b-light); margin:0 0 18px;}
.b-progress{
    background:var(--b-warm); border-radius:99px;
    height:8px; overflow:hidden; margin-bottom:16px;
}
.b-progress-fill{
    height:100%;
    background:linear-gradient(90deg,var(--b-accent),#d97706);
    border-radius:99px; transition:width .5s ease;
    position:relative; overflow:hidden;
}
.b-progress-fill::after{
    content:''; position:absolute; inset:0;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,.3),transparent);
    animation:shimmer 1.5s infinite;
}
@keyframes shimmer{from{transform:translateX(-100%);}to{transform:translateX(200%);}}
.b-log{
    max-height:200px; overflow-y:auto;
    background:#fafaf9; border:1px solid var(--b-border);
    border-radius:var(--b-r-sm); padding:12px;
    font-family:'SF Mono','Fira Code',monospace;
    font-size:12px; color:#57534e; line-height:1.7;
}
.b-log-line{margin:0; padding-left:14px; position:relative;}
.b-log-line::before{content:'›'; position:absolute; left:0; color:var(--b-accent); font-weight:700;}
.b-log-ok{color:#065f46;} .b-log-err{color:var(--b-red); font-weight:600;} .b-log-warn{color:var(--b-amber);}
/* Spinner */
@keyframes spin{to{transform:rotate(360deg);}}
.b-spinner{
    display:inline-block; width:14px; height:14px;
    border:2px solid var(--b-border); border-top-color:var(--b-accent);
    border-radius:50%; animation:spin .7s linear infinite;
}
/* URL fallback */
.b-url-fallback{
    margin-top:12px; border:1px solid var(--b-border);
    border-radius:var(--b-r-sm); overflow:hidden;
}
.b-url-fallback summary{
    padding:9px 14px; font-size:12px; font-weight:600;
    color:var(--b-mid); cursor:pointer; background:var(--b-warm);
    user-select:none; list-style:none;
}
.b-url-fallback summary::after{content:' ↓'; font-size:10px;}
.b-url-fallback[open] summary::after{content:' ↑';}
.b-url-fallback>form{padding:12px 14px; background:#fff;}
</style>

<!-- Sync Modal -->
<div class="b-modal-bg" id="b-modal">
    <div class="b-modal">
        <div class="b-modal-title"><span id="b-modal-emoji">🔄</span><span id="b-modal-title">Đang đồng bộ...</span></div>
        <div class="b-modal-sub" id="b-modal-sub">Vui lòng chờ trong khi hệ thống xử lý.</div>
        <div class="b-progress"><div class="b-progress-fill" id="b-progress-fill" style="width:0%"></div></div>
        <div class="b-log" id="b-modal-log"></div>
        <div style="display:flex;justify-content:flex-end;margin-top:18px;gap:8px;">
            <button class="b-btn b-btn-outline" id="b-modal-close" style="display:none;">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                Đóng & làm mới
            </button>
        </div>
    </div>
</div>

<div class="b-wrap">
    <!-- Header -->
    <div class="b-header">
        <div class="b-header-left">
            <h1 class="b-h1">🏺 Quản lý Sản phẩm</h1>
            <div class="b-h1-sub">
                <span>Pancake POS + Bacera Local</span>
                <span style="color:var(--b-border);">·</span>
                <span><?php echo date('d/m/Y H:i'); ?></span>
            </div>
        </div>
        <div class="b-header-actions">
            <button class="b-btn b-btn-success b-btn-sm" id="btn-sync-cats">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                Sync Danh mục
            </button>
            <button class="b-btn b-btn-primary b-btn-sm" id="btn-sync-prods">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                Sync Sản phẩm
            </button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=bacera-config')); ?>" class="b-btn b-btn-outline b-btn-sm">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                API
            </a>
        </div>
    </div>

    <?php if (!empty($crumb)): ?>
    <div class="b-crumb">
        <a href="<?php echo esc_url($crumb['back']); ?>">← Quay lại</a>
        <span class="b-crumb-sep">/</span>
        <span class="b-crumb-current"><?php echo esc_html($crumb['label']); ?></span>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="b-tabs">
        <?php foreach($tabs as $slug => [$ico, $label, $url]): ?>
        <a class="b-tab <?php echo $active_tab===$slug?'is-active':''; ?>" href="<?php echo esc_url($url); ?>">
            <?php echo $ico; ?> <?php echo $label; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <script>
    jQuery(function($){
        var aj='<?php echo esc_js(admin_url('admin-ajax.php')); ?>',nonce='<?php echo esc_js($nonce); ?>';
        function openModal(emoji,title,sub){
            $('#b-modal-emoji').text(emoji);$('#b-modal-title').text(title);$('#b-modal-sub').text(sub||'Vui lòng chờ...');
            $('#b-modal-log').empty();$('#b-modal-close').hide();
            $('#b-progress-fill').css('width','0%');
            $('#b-modal').addClass('open');
        }
        function setProgress(p){$('#b-progress-fill').css('width',p+'%');}
        function log(msg,cls){$('<p class="b-log-line">').text(msg).addClass(cls?'b-log-'+cls:'').appendTo('#b-modal-log');$('#b-modal-log').scrollTop(9e9);}
        $('#b-modal-close').on('click',function(){$('#b-modal').removeClass('open');location.reload();});
        $('#b-modal').on('click',function(e){if($(e.target).is('#b-modal'))$(this).removeClass('open');});
        $('#btn-sync-prods').on('click',function(){
            openModal('🔄','Sync sản phẩm từ Pancake POS','Đang kết nối và tải dữ liệu...');
            setProgress(10);log('Đang gọi Pancake API...');
            $.post(aj,{action:'bprod_sync_products',nonce:nonce},function(r){
                setProgress(100);
                if(r.success){log('✅ Hoàn tất! '+(r.data.synced||0)+' sản phẩm đã đồng bộ.','ok');(r.data.errors||[]).forEach(function(e){log('⚠ '+e,'warn');});}
                else log('❌ '+(r.data?.message||r.data||'Lỗi không xác định.'),'err');
                $('#b-modal-sub').text('Hoàn tất.');$('#b-modal-close').fadeIn(200);
            }).fail(function(){log('❌ Lỗi kết nối.','err');$('#b-modal-close').fadeIn(200);});
        });
        $('#btn-sync-cats').on('click',function(){
            openModal('🔄','Sync danh mục từ Pancake POS','Đang tải danh mục...');
            setProgress(20);log('Đang gọi Pancake API...');
            $.post(aj,{action:'bprod_sync_categories',nonce:nonce},function(r){
                setProgress(100);
                if(r.success) log('✅ Đã cache '+(r.data.count||0)+' danh mục.','ok');
                else log('❌ '+(r.data?.message||JSON.stringify(r.data)),'err');
                $('#b-modal-sub').text('Hoàn tất.');$('#b-modal-close').fadeIn(200);
            }).fail(function(){log('❌ Lỗi kết nối.','err');$('#b-modal-close').fadeIn(200);});
        });
        // Animate stats on load
        setTimeout(function(){$('.b-stat').each(function(i){var $s=$(this);setTimeout(function(){$s.css({opacity:1,transform:'none'});},i*80);});},100);
    });
    </script>
        <?php
    }

    private function ui_close(): void { echo '</div><!-- .b-wrap -->'; }

    private function sb_row( string $label, string $val_html ): void {
        echo '<div class="b-sb-row"><div class="b-sb-lbl">'.esc_html($label).'</div><div class="b-sb-val">'.$val_html.'</div></div>';
    }

    /* ═══════════════════════════════════════════════════
       AJAX
    ═══════════════════════════════════════════════════ */

    public function ajax_sync_products(): void {
        check_ajax_referer('bprod_nonce','nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Không có quyền.');
        if (!$this->api_ready()) wp_send_json_error('Chưa cấu hình API.');
        $synced=0;$errors=[];$page=1;
        do {
            $products = $this->fetch_products(['page'=>$page,'per_page'=>50]);
            if (empty($products)) break;
            foreach ($products as $pd) {
                if (empty($pd['id'])) continue;
                try { if (class_exists('\\Bacera_Module_Products',false)) { \Bacera_Module_Products::sync_pancake_product_to_wp($pd); } $synced++; }
                catch (\Throwable $e) { $errors[] = 'SP#'.mb_substr((string)($pd['id']??''),0,8).': '.$e->getMessage(); }
            }
            $page++;
        } while (count($products)===50 && $page<=20);
        wp_send_json_success(['synced'=>$synced,'errors'=>$errors]);
    }

    public function ajax_sync_categories(): void {
        check_ajax_referer('bprod_nonce','nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Không có quyền.');
        $cats = $this->fetch_categories(true);
        if (empty($cats)) {
            $raw = $this->api_raw('/shops/{SHOP_ID}/categories');
            wp_send_json_error(['message'=>'Không lấy được danh mục.','raw_keys'=>is_array($raw)?array_keys($raw):gettype($raw)]);
        }
        wp_send_json_success(['count'=>count($cats)]);
    }

    public function ajax_debug_api(): void {
        if (!current_user_can('manage_options')) wp_send_json_error('Không có quyền.');
        check_ajax_referer('bprod_nonce','nonce');
        $endpoint = sanitize_text_field($_POST['endpoint']??'/shops/{SHOP_ID}/categories');
        $raw = $this->api_raw($endpoint);
        wp_send_json_success(['endpoint'=>$endpoint,'type'=>gettype($raw),'keys'=>is_array($raw)?array_keys($raw):null,'preview'=>is_array($raw)?array_slice($raw,0,2,true):$raw,'normalized'=>$this->normalize_response($raw)]);
    }

    /** AJAX: Insert nhiều ảnh cùng lúc từ WP Media Library */
    public function ajax_add_images(): void {
        if ( ! current_user_can('manage_options') ) wp_send_json_error('Không có quyền.');
        $pid = sanitize_text_field( $_POST['pid'] ?? '' );
        if ( ! $pid ) wp_send_json_error('Thiếu ID sản phẩm.');
        check_ajax_referer( 'bprod_add_images_' . $pid, 'nonce' );

        $images = $_POST['images'] ?? [];
        if ( ! is_array($images) || empty($images) ) wp_send_json_error('Không có ảnh nào.');

        global $wpdb;
        $table   = $wpdb->prefix . 'bacera_product_images';
        $added   = 0;
        $items   = [];

        foreach ( $images as $img ) {
            $url = sanitize_url( $img['url'] ?? '' );
            $alt = sanitize_text_field( $img['alt'] ?? '' );
            $att_id = intval( $img['id'] ?? 0 );
            if ( ! $url ) continue;

            $wpdb->insert( $table, [
                'pancake_product_id' => $pid,
                'attachment_id'      => $att_id,
                'image_url'          => $url,
                'alt_text'           => $alt,
                'is_primary'         => 0,
                'sort_order'         => 0,
                'created_at'         => current_time('mysql'),
            ]);
            $new_id = (int)$wpdb->insert_id;
            $added++;

            // Build delete URL for this image
            $del_url = wp_nonce_url(
                add_query_arg([
                    'action'  => 'bprod_delete_image',
                    'img_id'  => $new_id,
                    'pid'     => $pid,
                ], admin_url('admin-post.php')),
                'bprod_del_img_' . $new_id
            );

            $items[] = [ 'url' => $url, 'alt' => $alt, 'del_url' => $del_url ];
        }

        wp_send_json_success([ 'added' => $added, 'items' => $items ]);
    }

    /* ═══════════════════════════════════════════════════
       FORM POSTS
    ═══════════════════════════════════════════════════ */

    public function post_save_meta(): void {
        if (!current_user_can('manage_options')) wp_die('Không có quyền.');
        $pid = sanitize_text_field($_POST['pid']??'');
        if (!$pid) wp_die('Thiếu ID.');
        check_admin_referer('bprod_save_meta_'.$pid);
        $this->db_upsert('bacera_product_meta',[
            'long_description'  => wp_kses_post($_POST['long_description']??''),
            'story'             => sanitize_textarea_field($_POST['story']??''),
            'materials'         => sanitize_text_field($_POST['materials']??''),
            'dimensions'        => sanitize_text_field($_POST['dimensions']??''),
            'care_instructions' => sanitize_textarea_field($_POST['care_instructions']??''),
            'seo_title'         => sanitize_text_field($_POST['seo_title']??''),
            'seo_description'   => sanitize_textarea_field($_POST['seo_description']??''),
            'slug_override'     => sanitize_title($_POST['slug_override']??''),
            'is_featured'       => !empty($_POST['is_featured'])?1:0,
        ],'pancake_product_id',$pid);
        wp_safe_redirect(add_query_arg(['page'=>'bacera-products','action'=>'edit_product','pid'=>$pid,'saved'=>1],admin_url('admin.php')));exit;
    }

    public function post_add_image(): void {
        if (!current_user_can('manage_options')) wp_die('Không có quyền.');
        $pid = sanitize_text_field($_POST['pid']??'');
        if (!$pid) wp_die('Thiếu ID.');
        check_admin_referer('bprod_add_image_'.$pid);
        $img_url = sanitize_url($_POST['img_url']??'');
        if (!$img_url) { wp_safe_redirect(add_query_arg(['page'=>'bacera-products','action'=>'edit_product','pid'=>$pid],admin_url('admin.php')));exit; }
        global $wpdb;
        $is_primary = !empty($_POST['img_primary'])?1:0;
        if ($is_primary) $wpdb->update($wpdb->prefix.'bacera_product_images',['is_primary'=>0],['pancake_product_id'=>$pid]);
        $wpdb->insert($wpdb->prefix.'bacera_product_images',[
            'pancake_product_id'=>$pid,'attachment_id'=>intval($_POST['attach_id']??0),
            'image_url'=>$img_url,'alt_text'=>sanitize_text_field($_POST['img_alt']??''),
            'is_primary'=>$is_primary,'sort_order'=>0,'created_at'=>current_time('mysql'),
        ]);
        wp_safe_redirect(add_query_arg(['page'=>'bacera-products','action'=>'edit_product','pid'=>$pid,'saved'=>1],admin_url('admin.php')).'#gallery');exit;
    }

    public function post_delete_image(): void {
        if (!current_user_can('manage_options')) wp_die('Không có quyền.');
        $img_id = intval($_GET['img_id']??0);$pid = sanitize_text_field($_GET['pid']??'');
        if (!$img_id||!$pid) wp_die('Thiếu tham số.');
        check_admin_referer('bprod_del_img_'.$img_id);
        global $wpdb;$wpdb->delete($wpdb->prefix.'bacera_product_images',['id'=>$img_id,'pancake_product_id'=>$pid]);
        wp_safe_redirect(add_query_arg(['page'=>'bacera-products','action'=>'edit_product','pid'=>$pid],admin_url('admin.php')).'#gallery');exit;
    }

    public function ajax_cat_set_image(): void {
        if ( !current_user_can('manage_options') ) wp_send_json_error('Không có quyền.');
        $cid  = sanitize_text_field( $_POST['cid']  ?? '' );
        $url  = sanitize_url(         $_POST['url']  ?? '' );
        $attid= intval(               $_POST['attid']?? 0  );
        if ( !$cid || !check_ajax_referer( 'bprod_cat_image_' . $cid, 'nonce', false ) ) {
            wp_send_json_error('Nonce không hợp lệ.');
        }
        global $wpdb;
        $t    = $wpdb->prefix . 'bacera_category_meta';
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $t WHERE pancake_category_id=%s", $cid ) );
        $data = [ 'image_url' => $url, 'attachment_id' => $attid, 'updated_at' => current_time('mysql') ];
        if ( $exists ) {
            $wpdb->update( $t, $data, ['pancake_category_id' => $cid] );
        } else {
            $data['pancake_category_id'] = $cid;
            $data['created_at']          = current_time('mysql');
            $data['is_active']           = 1;
            $wpdb->insert( $t, $data );
        }
        wp_send_json_success([ 'url' => $url, 'cid' => $cid ]);
    }

    public function post_save_category(): void {
        if (!current_user_can('manage_options')) wp_die('Không có quyền.');
        $cid = sanitize_text_field($_POST['cid']??'');
        if (!$cid) wp_die('Thiếu ID.');
        check_admin_referer('bprod_save_cat_'.$cid);
        $this->db_upsert('bacera_category_meta',[
            'name_override'=>sanitize_text_field($_POST['name_override']??''),
            'slug'=>sanitize_title($_POST['slug']??''),
            'description'=>sanitize_textarea_field($_POST['description']??''),
            'attachment_id'=>intval($_POST['attachment_id']??0),
            'image_url'=>sanitize_url($_POST['image_url']??''),
            'banner_url'=>sanitize_url($_POST['banner_url']??''),
            'sort_order'=>intval($_POST['sort_order']??0),
            'is_featured'=>!empty($_POST['is_featured'])?1:0,
            'is_active'=>!empty($_POST['is_active'])?1:0,
        ],'pancake_category_id',$cid);
        wp_safe_redirect(add_query_arg(['page'=>'bacera-products','action'=>'edit_category','cid'=>$cid,'saved'=>1],admin_url('admin.php')));exit;
    }

    public function post_review_action(): void {
        if (!current_user_can('manage_options')) wp_die('Không có quyền.');
        $rid = intval($_GET['review_id']??0);$ra = sanitize_key($_GET['rv_action']??'');
        if (!$rid||!$ra) wp_die('Thiếu tham số.');
        check_admin_referer('rv_'.$rid);
        global $wpdb;$t=$wpdb->prefix.'bacera_product_reviews';
        if ($ra==='delete') $wpdb->delete($t,['id'=>$rid]);
        elseif (in_array($ra,['approve','reject'],true)) $wpdb->update($t,['status'=>$ra==='approve'?'approved':'rejected','updated_at'=>current_time('mysql')],['id'=>$rid]);
        wp_safe_redirect(add_query_arg(['page'=>'bacera-products','tab'=>'reviews'],admin_url('admin.php')));exit;
    }
}
