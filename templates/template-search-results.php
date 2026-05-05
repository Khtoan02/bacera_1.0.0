<?php
/**
 * Unified search "View all" page.
 * Route: /search/{product|blog|workshop}/?q=...
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$type = isset( $_GET['search_type'] ) ? sanitize_key( wp_unslash( $_GET['search_type'] ) ) : 'product';
if ( ! in_array( $type, [ 'product', 'blog', 'workshop' ], true ) ) {
    $type = 'product';
}
$q          = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) );
$page       = max( 1, (int) ( $_GET['page'] ?? 1 ) );
$per_page   = 12;
$title_map  = [
    'product'  => __( 'Products search for', 'bacera' ),
    'blog'     => __( 'Blogs search for', 'bacera' ),
    'workshop' => __( 'Workshops search for', 'bacera' ),
];
[ $items, $total, $total_pages ] = bacera_search_query_items( $type, $q, $page, $per_page );

$base_url = home_url( '/search/' . $type . '/' );
$page_url = static function ( int $target ) use ( $base_url, $q ): string {
    $args = [ 'q' => $q ];
    if ( $target > 1 ) {
        $args['page'] = $target;
    }
    return add_query_arg( $args, $base_url );
};

get_header();
?>
<style>
.bsr-wrap { max-width: 1232px; margin: 0 auto; padding: 104px 24px 56px; }
.bsr-breadcrumb { font-size: 11px; color: #a8a29e; margin-bottom: 8px; }
.bsr-breadcrumb a { color: #78716c; text-decoration: none; }
.bsr-title { font-family: 'Gowun Batang', Georgia, serif; font-size: 46px; color: #292524; margin: 0 0 14px; line-height: 1.14; font-weight: 400; }
.bsr-title em { color: #d95f47; font-style: italic; }
.bsr-grid { display: grid; gap: 12px; border-top: 1px solid #e7e5e4; padding-top: 14px; }
.bsr-grid.product, .bsr-grid.workshop { grid-template-columns: repeat(4, minmax(0, 1fr)); }
.bsr-grid.blog { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
.bsr-card { text-decoration: none; color: inherit; display: block; }
.bsr-thumb { width: 100%; aspect-ratio: 4/5; border-radius: 6px; overflow: hidden; background: #f5f5f4; margin-bottom: 6px; }
.bsr-thumb img { width: 100%; height: 100%; object-fit: cover; }
.bsr-cat { font-size: 10px; color: #a8a29e; margin-bottom: 2px; }
.bsr-name { margin: 0 0 2px; font-size: 12px; line-height: 1.35; color: #292524; }
.bsr-desc { margin: 0; font-size: 11px; color: #78716c; line-height: 1.45; }
.bsr-price { margin: 2px 0 0; font-size: 12px; color: #292524; font-weight: 500; }
.bsr-old { font-size: 11px; color: #a8a29e; text-decoration: line-through; margin-left: 6px; }
.bsr-empty { border: 1px dashed #d6d3d1; border-radius: 12px; padding: 36px 20px; text-align: center; color: #78716c; }
.bsr-blog .bsr-thumb { aspect-ratio: 16/11; border-radius: 8px; margin-bottom: 8px; }
.bsr-blog .bsr-name { font-size: 14px; margin-bottom: 3px; }
.bsr-blog .bsr-desc { font-size: 12px; }
.bsr-pagination { margin-top: 22px; display: flex; justify-content: flex-start; gap: 14px; }
.bsr-pagination a, .bsr-pagination span { display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #78716c; font-size: 12px; border: 0; min-width: auto; height: auto; border-radius: 0; }
.bsr-pagination .current { color: #292524; font-weight: 600; }
@media (max-width: 1024px) {
    .bsr-grid.product, .bsr-grid.workshop { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    .bsr-wrap { padding: 96px 16px 46px; }
    .bsr-title { font-size: 36px; }
    .bsr-grid.product, .bsr-grid.workshop, .bsr-grid.blog { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
</style>

<main class="bsr-wrap">
    <div class="bsr-breadcrumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Homepage</a> / Shop all</div>
    <h1 class="bsr-title"><?php echo esc_html( $title_map[ $type ] ); ?> <em>"<?php echo esc_html( $q ); ?>"</em></h1>
    <?php if ( empty( $items ) ) : ?>
        <div class="bsr-empty"><?php esc_html_e( 'No results found. Try another keyword.', 'bacera' ); ?></div>
    <?php else : ?>
        <div class="bsr-grid <?php echo esc_attr( $type === 'blog' ? 'blog bsr-blog' : $type ); ?>">
            <?php foreach ( $items as $item ) : ?>
                <a class="bsr-card" href="<?php echo esc_url( $item['url'] ?? '#' ); ?>">
                    <div class="bsr-thumb"><img src="<?php echo esc_url( $item['image'] ?? '' ); ?>" alt="<?php echo esc_attr( $item['title'] ?? '' ); ?>" loading="lazy"></div>
                    <?php if ( ! empty( $item['cat_name'] ) ) : ?>
                        <p class="bsr-cat"><?php echo esc_html( $item['cat_name'] ); ?></p>
                    <?php elseif ( $type !== 'workshop' ) : ?>
                        <p class="bsr-cat">Whispers of Clay</p>
                    <?php endif; ?>
                    <h3 class="bsr-name"><?php echo esc_html( $item['title'] ?? '' ); ?></h3>
                    <?php if ( ! empty( $item['excerpt'] ) ) : ?><p class="bsr-desc"><?php echo esc_html( $item['excerpt'] ); ?></p><?php endif; ?>
                    <?php if ( ! empty( $item['description'] ) ) : ?><p class="bsr-desc"><?php echo esc_html( $item['description'] ); ?></p><?php endif; ?>
                    <?php if ( ! empty( $item['price_text'] ) || ! empty( $item['price'] ) ) : ?>
                        <p class="bsr-price">
                            <?php echo esc_html( $item['price_text'] ?? $item['price'] ); ?>
                            <?php if ( ! empty( $item['old_price_text'] ) ) : ?><span class="bsr-old"><?php echo esc_html( $item['old_price_text'] ); ?></span><?php endif; ?>
                        </p>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ( $total_pages > 1 ) : ?>
        <nav class="bsr-pagination" aria-label="<?php esc_attr_e( 'Pagination', 'bacera' ); ?>">
            <?php for ( $p = 1; $p <= $total_pages; $p++ ) : ?>
                <?php if ( $p === $page ) : ?>
                    <span class="current"><?php echo (int) $p; ?></span>
                <?php else : ?>
                    <a href="<?php echo esc_url( $page_url( $p ) ); ?>"><?php echo (int) $p; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</main>

<?php get_footer(); ?>
