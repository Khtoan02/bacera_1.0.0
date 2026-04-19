<?php
/**
 * single.php — WordPress single post template override.
 * Loads our custom Blog Single template for all 'post' types.
 */

// For CPT overrides, you can add conditions here if needed
if ( is_singular('post') ) {
    include locate_template('templates/template-blog-single.php');
    exit;
}

// Default fallback (shouldn't normally reach here)
get_header();
if ( have_posts() ) {
    while ( have_posts() ) {
        the_post();
        the_content();
    }
}
get_footer();
