<?php
/**
 * Breadcrumb Component
 * 
 * Usage:
 * get_template_part('app/Views/components/breadcrumb', null, [
 *     'items' => [
 *         'Homepage' => home_url('/'),
 *         'Our Team' => home_url('/our-team/'),
 *         'Current Page' => ''
 *     ]
 * ]);
 */

$items = $args['items'] ?? [];
if ( empty( $items ) ) {
    return;
}
?>
<nav class="flex items-center gap-2 text-xs text-textmuted mb-4 tracking-wide overflow-x-auto whitespace-nowrap hide-scrollbar">
    <?php
    $total = count( $items );
    $i = 0;
    foreach ( $items as $label => $url ) {
        $i++;
        $is_last = ( $i === $total );

        if ( ! $is_last && ! empty( $url ) ) {
            // Link
            echo '<a href="' . esc_url( $url ) . '" class="text-textmain font-medium hover:text-terracotta transition-colors">';
            echo esc_html( $label );
            echo '</a>';
            // Separator
            echo '<span class="text-accent/60 mx-1">/</span>';
        } else {
            // Current Page (No link)
            echo '<span class="text-textmuted truncate max-w-[200px]">' . esc_html( $label ) . '</span>';
        }
    }
    ?>
</nav>
