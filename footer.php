<?php
/**
 * Footer
 */
?>
    <footer id="colophon" class="site-footer">
        <div class="site-info">
            <a href="<?php echo esc_url( __( 'https://wordpress.org/', 'bacera' ) ); ?>">
                <?php
                /* translators: %s: CMS name, i.e. WordPress. */
                printf( esc_html__( 'Proudly powered by %s', 'bacera' ), 'WordPress' );
                ?>
            </a>
            <span class="sep"> | </span>
                <?php
                /* translators: 1: Theme name, 2: Theme author. */
                printf( esc_html__( 'Theme: %1$s by %2$s.', 'bacera' ), 'bacera', '<a href="#">Bacera Studio</a>' );
                ?>
        </div>
    </footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
