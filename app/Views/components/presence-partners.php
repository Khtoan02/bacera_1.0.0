<?php
/**
 * Component: Bacera's Presence (Partners)
 * Redesigned: static grid + horizontal scroll on mobile, no Swiper overflow issues.
 */

$partners_query = new WP_Query([
    'post_type'      => 'bacera_partner',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'menu_order title',
    'order'          => 'ASC',
]);
$has_partners = $partners_query->have_posts();

// Collect items
$items = [];
if ($has_partners) {
    while ($partners_query->have_posts()) {
        $partners_query->the_post();
        $items[] = [
            'type'  => 'image',
            'img'   => has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'full') : '',
            'name'  => get_the_title(),
        ];
    }
    wp_reset_postdata();
} else {
    // Fallback demo
    $items = [
        ['type' => 'image', 'img' => '', 'name' => 'MARRIOTT'],
        ['type' => 'image', 'img' => '', 'name' => 'THE JAHAN'],
        ['type' => 'image', 'img' => '', 'name' => 'ACCOR'],
        ['type' => 'image', 'img' => '', 'name' => 'HERITAGE'],
        ['type' => 'image', 'img' => '', 'name' => "L'USINE"],
    ];
}
?>

<section class="bacera-presence-section">
    <style>
    /* ── Bacera's Presence ────────────────────────────────────────── */
    .bacera-presence-section {
        position: relative;
        padding: clamp(4rem, 6vw, 7rem) 0;
        border-top: 1px solid rgba(217,95,71,.15);
        /* No background — inherits the page background seamlessly */
    }

    /* Decorative ambient blobs — self-contained, no overflow leaking */
    .bacera-presence-bg {
        position: absolute;
        inset: 0;
        overflow: hidden;
        pointer-events: none;
        z-index: 0;
    }
    .presence-orb {
        position: absolute;
        border-radius: 9999px;
        filter: blur(90px);
        mix-blend-mode: multiply;
    }
    .presence-orb-1 {
        width: 500px; height: 500px;
        top: -100px; right: -80px;
        background: rgba(217,95,71,.12);
    }
    .presence-orb-3 {
        width: 300px; height: 200px;
        top: 50%; left: 50%;
        transform: translate(-50%,-50%);
        background: rgba(217,95,71,.07);
    }

    /* ── Header ── */
    .bacera-presence-header {
        text-align: center;
        margin-bottom: clamp(2.5rem, 4vw, 4rem);
        position: relative;
        z-index: 1;
    }
    .bacera-presence-eyebrow {
        font-size: 10px;
        letter-spacing: .3em;
        text-transform: uppercase;
        color: var(--theme-primary, #8d6a54);
        margin-bottom: 1rem;
        font-weight: 500;
    }
    .bacera-presence-title {
        font-family: 'Gowun Batang', serif;
        font-size: clamp(1.5rem, 2.5vw, 2.25rem);
        font-weight: 400;
        color: #3d2f26;
        line-height: 1.3;
    }

    /* ── Grid Track ── */
    .bacera-presence-track {
        position: relative;
        z-index: 1;                  /* above blobs */
        /* !! KEY: NO overflow:hidden on this element !! */
    }

    .bacera-presence-grid {
        display: flex;
        gap: 1.25rem;
        /* Mobile: scroll horizontally */
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding: 1.5rem 1rem 2.5rem; /* bottom padding = space for hover lift shadow */
        /* Desktop: wrap into grid */
    }
    .bacera-presence-grid::-webkit-scrollbar { display: none; }

    @media (min-width: 768px) {
        .bacera-presence-grid {
            overflow-x: visible;
            flex-wrap: wrap;
            justify-content: center;
            padding: 1.5rem 0 2.5rem;
        }
    }

    /* ── Glass Card ── */
    .partner-card {
        /* Fixed width so flex-scroll works on mobile */
        flex: 0 0 clamp(160px, 40vw, 200px);
        min-height: 120px;

        /* Glassmorphism */
        background: rgba(255,255,255,.55);
        backdrop-filter: blur(18px) saturate(180%);
        -webkit-backdrop-filter: blur(18px) saturate(180%);
        border: 1px solid rgba(255,255,255,.7);
        border-radius: 1.5rem;
        box-shadow:
            0 4px 24px rgba(0,0,0,.06),
            0 1px 2px rgba(0,0,0,.04),
            inset 0 1px 0 rgba(255,255,255,.8);

        /* Layout */
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem 1.75rem;
        position: relative;

        /* Transition — transform only, so nothing clips */
        transition: transform .35s cubic-bezier(.22,.68,0,1.2),
                    box-shadow .35s ease,
                    background .2s ease;
        will-change: transform;
        cursor: default;
    }

    /* Glare overlay — inside, uses border-radius, no overflow needed */
    .partner-card::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: inherit;
        background: linear-gradient(
            135deg,
            rgba(255,255,255,.7) 0%,
            rgba(255,255,255,.1) 50%,
            rgba(255,255,255,0) 100%
        );
        pointer-events: none;
    }

    /* Bottom edge shimmer */
    .partner-card::after {
        content: '';
        position: absolute;
        bottom: 0; left: 10%; right: 10%;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,.9), transparent);
        border-radius: 9999px;
    }

    /* !! Hover — translate ONLY, no overflow issues !! */
    .partner-card:hover {
        transform: translateY(-12px);
        background: rgba(255,255,255,.75);
        box-shadow:
            0 20px 48px rgba(61,47,38,.12),
            0 8px 16px rgba(61,47,38,.08),
            inset 0 1px 0 rgba(255,255,255,.9);
    }

    .partner-card img {
        max-height: 80px;
        width: auto;
        object-fit: contain;
        transition: transform .35s ease;
        filter: grayscale(20%);
        position: relative;
        z-index: 1;
    }
    .partner-card:hover img {
        transform: scale(1.06);
        filter: grayscale(0%);
    }

    .partner-card .partner-name {
        font-family: 'Gowun Batang', serif;
        font-size: clamp(1.1rem, 2vw, 1.5rem);
        letter-spacing: .15em;
        color: #3d2f26;
        text-align: center;
        position: relative;
        z-index: 1;
        transition: transform .35s ease;
    }
    .partner-card:hover .partner-name {
        transform: scale(1.05);
    }

    @media (min-width: 768px) {
        .partner-card {
            flex: 0 0 clamp(180px, 22%, 220px);
            min-height: 140px;
        }
        .partner-card img { max-height: 96px; }
    }
    </style>

    <!-- Decorative background (self-contained overflow) -->
    <div class="bacera-presence-bg" aria-hidden="true">
        <div class="presence-orb presence-orb-1"></div>
        <div class="presence-orb presence-orb-3"></div>
    </div>

    <!-- Inner container -->
    <div class="bacera-container">

        <!-- Header -->
        <div class="bacera-presence-header">
            <p class="bacera-presence-eyebrow">Bacera's Presence</p>
            <h2 class="bacera-presence-title">
                Our creations accompany the most luxurious<br>
                and culturally rich spaces.
            </h2>
        </div>

        <!-- Partner grid / scroll track -->
        <div class="bacera-presence-track">
            <div class="bacera-presence-grid">
                <?php foreach ($items as $item): ?>
                <div class="partner-card">
                    <?php if (!empty($item['img'])): ?>
                        <img src="<?php echo esc_url($item['img']); ?>"
                             alt="<?php echo esc_attr($item['name']); ?>"
                             loading="lazy">
                    <?php else: ?>
                        <span class="partner-name"><?php echo esc_html($item['name']); ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</section>
