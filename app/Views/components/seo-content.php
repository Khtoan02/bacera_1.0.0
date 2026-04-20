<?php
$page_content = get_the_content();
if ( empty( trim( strip_tags( (string) $page_content ) ) ) ) {
    return;
}

$title = $args['title'] ?? get_the_title();
$uid = uniqid('seo_');
?>

<style>
    .team-seo-content { font-size: .875rem; }   /* ~14px — nhỏ hơn body 1 chút */
    .team-seo-content h2 { font-size: 1.2rem;  font-weight: 600; margin: 1.75rem 0 .5rem; color: #3d2f26; }
    .team-seo-content h3 { font-size: 1rem;    font-weight: 600; margin: 1.35rem 0 .45rem; color: #3d2f26; }
    .team-seo-content p  { margin-bottom: 1rem; line-height: 1.85; color: #6b5748; }
    .team-seo-content ul, .team-seo-content ol { margin: .65rem 0 1.1rem 1.25rem; color: #6b5748; line-height: 1.8; }
    .team-seo-content li { margin-bottom: .3rem; }
    .team-seo-content a  { color: #d95f47; text-decoration: underline; text-underline-offset: 3px; }
    .team-seo-content strong { color: #3d2f26; }
    /* Images inside the content: rounded + shadow */
    .team-seo-content img {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(61,47,38,.12);
        max-width: 100%;
        height: auto;
        display: block;
        margin: 1rem 0;
    }

    /* Collapse wrapper */
    .seo-collapse-wrap {
        position: relative;
    }

    /* Fade-out gradient at bottom when collapsed */
    .seo-fade {
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 80px;
        background: linear-gradient(to bottom, rgba(248,247,243,0) 0%, #f8f7f3 100%);
        pointer-events: none;
        transition: opacity .4s ease;
    }
</style>

<section class="bacera-container mb-20">
    <div class="w-full h-[1px] divider-art opacity-40 mb-16"></div>

    <!-- Label -->
    <div class="flex items-center gap-4 mb-6">
        <span class="w-8 h-[1px] bg-accentdark"></span>
        <p class="text-xs uppercase tracking-[0.3em] text-accentdark font-semibold">
            <?php echo esc_html( $title ); ?>
        </p>
    </div>

    <!-- Prose content: collapsed by default, expand on click -->
    <div class="seo-collapse-wrap" id="wrap_<?php echo $uid; ?>">
        <div class="team-seo-content seo-body" id="body_<?php echo $uid; ?>">
            <?php echo apply_filters( 'the_content', $page_content ); ?>
        </div>
        <!-- Fade-out gradient overlay (hidden when expanded) -->
        <div class="seo-fade" id="fade_<?php echo $uid; ?>" aria-hidden="true"></div>
    </div>

    <!-- Toggle button -->
    <button id="btn_<?php echo $uid; ?>"
            class="mt-5 inline-flex items-center gap-2 text-[11px] uppercase tracking-[0.25em] font-semibold text-accentdark hover:text-terracotta transition-colors"
            aria-expanded="false">
        <span id="label_<?php echo $uid; ?>">Xem chi tiết</span>
        <svg id="icon_<?php echo $uid; ?>" class="w-4 h-4 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
</section>

<script>
(function(){
    var wrap  = document.getElementById('wrap_<?php echo $uid; ?>');
    var body  = document.getElementById('body_<?php echo $uid; ?>');
    var fade  = document.getElementById('fade_<?php echo $uid; ?>');
    var btn   = document.getElementById('btn_<?php echo $uid; ?>');
    var label = document.getElementById('label_<?php echo $uid; ?>');
    var icon  = document.getElementById('icon_<?php echo $uid; ?>');
    if (!wrap || !btn) return;

    var collapsed = true;
    var LINE_HEIGHT = 1.85;     // matches .team-seo-content p
    var PREVIEW_LINES = 5;
    var EM = parseFloat(getComputedStyle(body).fontSize) || 14;
    var previewH = Math.round(EM * LINE_HEIGHT * PREVIEW_LINES) + 'px';

    /* init collapsed */
    body.style.maxHeight  = previewH;
    body.style.overflow   = 'hidden';
    body.style.transition = 'max-height .55s cubic-bezier(.4,0,.2,1)';

    btn.addEventListener('click', function(){
        collapsed = !collapsed;
        if (!collapsed) {
            body.style.maxHeight = body.scrollHeight + 'px';
            fade.style.opacity   = '0';
            fade.style.pointerEvents = 'none';
            label.textContent    = 'Thu gọn';
            icon.style.transform = 'rotate(180deg)';
            btn.setAttribute('aria-expanded', 'true');
        } else {
            body.style.maxHeight = previewH;
            fade.style.opacity   = '1';
            fade.style.pointerEvents = '';
            label.textContent    = 'Xem chi tiết';
            icon.style.transform = 'rotate(0deg)';
            btn.setAttribute('aria-expanded', 'false');
        }
    });
})();
</script>
