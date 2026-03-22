<?php
/**
 * Main index file
 */

get_header();
?>

<main id="primary" class="site-main">
    <?php
    if ( have_posts() ) :
        while ( have_posts() ) :
            the_post();
            get_template_part( 'app/Views/content' );
        endwhile;
    else :
        get_template_part( 'app/Views/content', 'none' );
    endif;
    ?>
</main>

<?php
get_footer();
