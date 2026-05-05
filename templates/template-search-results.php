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
$cart_page_url         = class_exists( 'Bacera_Utils' ) ? Bacera_Utils::get_cart_page_url() : home_url( '/cart/' );
$checkout_shipping_url = class_exists( 'Bacera_Utils' ) ? Bacera_Utils::get_checkout_shipping_page_url() : ( function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : $cart_page_url );
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
.bacera-cart-overlay { position:fixed; inset:0; background:rgba(0,0,0,.28); opacity:0; pointer-events:none; transition:opacity .25s; z-index:60; }
.bacera-cart-overlay.is-open { opacity:1; pointer-events:auto; }
.bacera-cart-drawer { position:fixed; top:0; right:0; height:100vh; width:min(520px,93vw); background:#fff; box-shadow:-8px 0 28px rgba(42,31,23,.14); transform:translateX(100%); transition:transform .28s; display:flex; flex-direction:column; z-index:70; }
.bacera-cart-drawer.is-open { transform:translateX(0); }
.bacera-cart-drawer-head { padding:2rem 1.5rem 1.25rem; border-bottom:1px solid #EDE6DD; }
.bacera-cart-items { flex:1; overflow-y:auto; padding:1.5rem; }
.bacera-cart-item { display:grid; grid-template-columns:88px minmax(0,1fr); gap:.85rem; padding:1rem 0; border-bottom:1px solid #EDE6DD; }
.bacera-cart-item img { width:88px; height:88px; border-radius:10px; object-fit:cover; background:#F2EDE5; }
.bacera-cart-item-main { display:flex; align-items:flex-start; justify-content:space-between; gap:.5rem; min-width:0; }
.bacera-cart-item-text { min-width:0; flex:1; }
.bacera-cart-variant-line { margin:.3rem 0 0; font-size:.82rem; line-height:1.45; color:#6B5344; }
.bacera-cart-qty { display:inline-flex; align-items:center; border:1px solid #DDD5CB; border-radius:8px; overflow:hidden; }
.bacera-cart-qty button { width:2rem; height:2rem; border:0; background:#fff; color:#6B5344; cursor:pointer; font-size:1rem; }
.bacera-cart-qty span { min-width:2rem; text-align:center; font-variant-numeric:tabular-nums; color:#2A1F17; font-size:.875rem; font-weight:600; }
.bacera-cart-remove { display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; width:2rem; height:2rem; padding:0; border:0; border-radius:8px; background:transparent; color:#9A8478; cursor:pointer; transition:color .15s,background .15s; }
.bacera-cart-remove:hover { color:#6B5344; background:#F2EDE5; }
.bacera-cart-footer { border-top:1px solid #EDE6DD; padding:1.5rem; background:#fff; }
.bacera-cart-empty { padding:2.5rem 1rem; color:#9A8478; font-size:.9rem; text-align:center; }
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
                <?php if ( $type === 'product' ) : ?>
                    <?php
                    $post_id           = (int) ( $item['id'] ?? 0 );
                    $title             = (string) ( $item['title'] ?? '' );
                    $price_value       = isset( $item['price'] ) ? (float) $item['price'] : 0;
                    $old_price_value   = (float) get_post_meta( $post_id, '_regular_price', true );
                    $variation_id      = (string) get_post_meta( $post_id, '_pancake_id', true );
                    $pancake_product_id= (string) get_post_meta( $post_id, '_pancake_product_id', true );
                    if ( $pancake_product_id === '' ) {
                        $pancake_product_id = (string) $post_id;
                    }
                    $cart_item_uid = $variation_id !== '' ? 'var_' . $variation_id : 'prd_' . $pancake_product_id . '_' . md5( $title . '|' . $price_value );
                    get_template_part(
                        'app/Views/components/product-card',
                        null,
                        [
                            'title'       => $title,
                            'brand'       => 'Bacera',
                            'price'       => (string) ( $item['price_text'] ?? '' ),
                            'old_price'   => (string) ( $item['old_price_text'] ?? '' ),
                            'image'       => (string) ( $item['image'] ?? '' ),
                            'url'         => (string) ( $item['url'] ?? '#' ),
                            'add_to_cart' => true,
                            'cart_item'   => [
                                'id'             => $cart_item_uid,
                                'variation_id'   => $variation_id,
                                'product_id'     => $pancake_product_id,
                                'name'           => $title,
                                'brand'          => 'Bacera',
                                'variant_label'  => $title,
                                'color'          => '',
                                'size'           => '',
                                'image'          => (string) ( $item['image'] ?? '' ),
                                'price'          => $price_value,
                                'original_price' => $old_price_value,
                                'url'            => (string) ( $item['url'] ?? '#' ),
                            ],
                        ]
                    );
                    ?>
                <?php else : ?>
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
                <?php endif; ?>
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

<?php if ( $type === 'product' ) : ?>
<div id="bacera-cart-overlay" class="bacera-cart-overlay" aria-hidden="true"></div>
<aside id="bacera-cart-drawer" class="bacera-cart-drawer" aria-hidden="true" aria-label="<?php esc_attr_e('Shopping cart','bacera');?>">
	<div class="bacera-cart-drawer-head" style="display:flex;align-items:center;justify-content:space-between;">
		<h2 style="margin:0;font-family:'Cormorant Garamond',Georgia,serif;font-size:1.6rem;font-weight:500;color:#2A1F17;"><?php esc_html_e('Your Cart','bacera');?></h2>
		<button type="button" id="bacera-cart-close" style="width:34px;height:34px;border-radius:50%;border:1px solid #DDD5CB;background:transparent;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#9A8478;" aria-label="<?php esc_attr_e('Close cart','bacera');?>">×</button>
	</div>
	<div id="bacera-cart-items" class="bacera-cart-items"></div>
	<div class="bacera-cart-footer">
		<div style="display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:1rem;">
			<p style="margin:0;font-size:.8rem;color:#9A8478;"><?php esc_html_e('Total (VAT included)','bacera');?></p>
			<p id="bacera-cart-total" style="margin:0;font-family:'Cormorant Garamond',Georgia,serif;font-size:1.75rem;color:#2A1F17;">0đ</p>
		</div>
		<div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;">
			<a id="bacera-cart-go-checkout" href="<?php echo esc_url($checkout_shipping_url);?>" style="border-radius:10px;background:#4d3d32;padding:.75rem 1rem;text-align:center;font-weight:500;color:#fff;text-decoration:none;font-size:.875rem;"><?php esc_html_e('Checkout','bacera');?></a>
			<a id="bacera-cart-go-cart"     href="<?php echo esc_url($cart_page_url);?>"       style="border-radius:10px;border:1px solid #DDD5CB;padding:.75rem 1rem;text-align:center;font-weight:500;color:#6B5344;text-decoration:none;font-size:.875rem;"><?php esc_html_e('View cart','bacera');?></a>
		</div>
	</div>
</aside>
<script>
(function(){
var drawer=document.getElementById('bacera-cart-drawer'),overlay=document.getElementById('bacera-cart-overlay'),closeBtn=document.getElementById('bacera-cart-close'),listEl=document.getElementById('bacera-cart-items'),totalEl=document.getElementById('bacera-cart-total');
if(!drawer||!overlay||!closeBtn||!listEl||!totalEl)return;
var KEY='bacera_shop_cart_v1',CK='bacera_checkout_items',previewId=null;
function loadCart(){try{var p=JSON.parse(localStorage.getItem(KEY)||'[]');return Array.isArray(p)?p:[];}catch(e){return[];}}
function saveCart(c){localStorage.setItem(KEY,JSON.stringify(c));}
function toCurrency(n){return Number(n||0).toLocaleString('vi-VN')+'đ';}
function esc(v){return String(v||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');}
function total(c){return c.reduce(function(s,i){return s+Number(i.price||0)*Number(i.qty||0);},0);}
var trash='<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>';
function pv(item){var c=String(item.color||'').trim(),s=String(item.size||'').trim(),l=String(item.variant_label||'').trim();if((!c||!s)&&l){var pts=l.split('|').map(function(x){return x.trim();}).filter(Boolean);if(!c&&pts[0])c=pts[0];if(!s&&pts[1])s=pts[1];}return c&&s?c+' · '+s:c||s||l;}
function getDisp(){var c=loadCart();if(!previewId)return[];return c.filter(function(i){return String(i.id)===String(previewId);});}
function render(){
	var cart=getDisp();
	if(!cart.length){listEl.innerHTML='<p class="bacera-cart-empty"><?php echo esc_js(__('Your cart is empty.','bacera'));?></p>';totalEl.textContent='0đ';return;}
	listEl.innerHTML=cart.map(function(item){
		var sub=pv(item),op=Number(item.original_price||0),oph=op>Number(item.price||0)?'<span style="font-size:.75rem;text-decoration:line-through;color:#9A8478;">'+toCurrency(op)+'</span>':'';
		return '<article class="bacera-cart-item" data-cart-id="'+esc(item.id)+'">'
			+'<img src="'+esc(item.image||'https://placehold.co/120x120/f0ece3/8d6a54?text=Bacera')+'" alt="'+esc(item.name||'')+'" loading="lazy"/>'
			+'<div style="min-width:0;"><div class="bacera-cart-item-main"><div class="bacera-cart-item-text">'
			+'<p style="margin:0;font-size:.75rem;color:#9A8478;">'+esc(item.brand||'Bacera')+'</p>'
			+'<p style="margin:.15rem 0 0;font-size:.9rem;font-weight:600;color:#2A1F17;">'+esc(item.name||'')+'</p>'
			+(sub?'<p class="bacera-cart-variant-line">'+esc(sub)+'</p>':'')
			+'</div><button type="button" class="bacera-cart-remove" data-cart-action="remove" aria-label="<?php echo esc_js(__('Remove','bacera'));?>">'+trash+'</button></div>'
			+'<div style="margin-top:.6rem;display:flex;align-items:center;justify-content:space-between;gap:.5rem;">'
			+'<div class="bacera-cart-qty" role="group"><button type="button" data-cart-action="minus">−</button><span>'+String(Number(item.qty||1)).padStart(2,'0')+'</span><button type="button" data-cart-action="plus">+</button></div>'
			+'<div style="text-align:right;">'+oph+'<p style="margin:0;font-size:1rem;font-weight:600;color:#2A1F17;">'+toCurrency(item.price)+'</p></div>'
			+'</div></div></article>';
	}).join('');
	totalEl.textContent=toCurrency(total(cart));
}
function setOpen(open){if(!open)previewId=null;drawer.classList.toggle('is-open',open);overlay.classList.toggle('is-open',open);drawer.setAttribute('aria-hidden',open?'false':'true');overlay.setAttribute('aria-hidden',open?'false':'true');document.body.classList.toggle('overflow-hidden',open);}
function upsert(next){var c=loadCart(),i=c.findIndex(function(x){return String(x.id)===String(next.id);});if(i>=0){c[i].qty=Number(c[i].qty||1)+1;}else{next.qty=1;c.push(next);}saveCart(c);}
function handleSearchAddToCart(btn,e){
	if(e){e.preventDefault();e.stopPropagation();}
	if(!btn)return;
	var raw=btn.getAttribute('data-cart-item')||'';
	if(!raw)return;
	try{
		var p=JSON.parse(raw);
		if(!p||!p.id)return;
		upsert(p);
		previewId=String(p.id);
		render();
		setOpen(true);
	}catch(err){}
}
document.addEventListener('click',function(e){
	var btn=e.target.closest('.bacera-shop-add-cart-btn');
	if(!btn)return;
	handleSearchAddToCart(btn,e);
},true);
document.querySelectorAll('.bacera-shop-add-cart-btn').forEach(function(btn){
	btn.addEventListener('click',function(e){handleSearchAddToCart(btn,e);});
});
listEl.addEventListener('click',function(e){var btn=e.target.closest('button[data-cart-action]');if(!btn)return;var row=btn.closest('.bacera-cart-item');if(!row)return;var id=row.getAttribute('data-cart-id'),action=btn.getAttribute('data-cart-action'),c=loadCart(),i=c.findIndex(function(x){return String(x.id)===String(id);});if(i<0)return;if(action==='remove')c.splice(i,1);else if(action==='minus'){c[i].qty=Number(c[i].qty||1)-1;if(c[i].qty<=0)c.splice(i,1);}else if(action==='plus')c[i].qty=Number(c[i].qty||1)+1;saveCart(c);render();});
var co=document.getElementById('bacera-cart-go-checkout');if(co){co.addEventListener('click',function(e){var full=loadCart(),picked=previewId?full.filter(function(x){return String(x.id)===String(previewId);}):[];if(!picked.length){e.preventDefault();return;}try{sessionStorage.setItem(CK,JSON.stringify(picked));sessionStorage.setItem('bacera_checkout_force_step1','1');}catch(err){}});}
closeBtn.addEventListener('click',function(){setOpen(false);});
overlay.addEventListener('click',function(){setOpen(false);});
document.addEventListener('keydown',function(e){if(e.key==='Escape')setOpen(false);});
render();
})();
</script>
<?php endif; ?>

<?php get_footer(); ?>
