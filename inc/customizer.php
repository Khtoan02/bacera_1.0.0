<?php
/**
 * Theme Customizer Integration
 *
 * @package Bacera
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Customizer settings.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function bacera_customize_register( $wp_customize ) {
    
    // ==========================================
    // 1. COLORS
    // ==========================================
    $wp_customize->add_section( 'bacera_colors', [
        'title'    => __( 'Theme Colors', 'bacera' ),
        'priority' => 30,
    ] );

    // Primary Color
    $wp_customize->add_setting( 'bacera_primary_color', [
        'default'           => '#8d6a54',
        'sanitize_callback' => 'sanitize_hex_color',
    ] );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'bacera_primary_color', [
        'label'    => __( 'Primary/Brand Color', 'bacera' ),
        'section'  => 'bacera_colors',
        'settings' => 'bacera_primary_color',
    ] ) );

    // Accent Color
    $wp_customize->add_setting( 'bacera_accent_color', [
        'default'           => '#d95f47',
        'sanitize_callback' => 'sanitize_hex_color',
    ] );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'bacera_accent_color', [
        'label'    => __( 'Accent (Terracotta) Color', 'bacera' ),
        'section'  => 'bacera_colors',
        'settings' => 'bacera_accent_color',
    ] ) );

    // Neutral Color (Background)
    $wp_customize->add_setting( 'bacera_neutral_color', [
        'default'           => '#f8f7f3',
        'sanitize_callback' => 'sanitize_hex_color',
    ] );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'bacera_neutral_color', [
        'label'    => __( 'Neutral/Background Color', 'bacera' ),
        'section'  => 'bacera_colors',
        'settings' => 'bacera_neutral_color',
    ] ) );

    // ==========================================
    // 2. LAYOUT & CONTAINER
    // ==========================================
    $wp_customize->add_section( 'bacera_layout', [
        'title'    => __( 'Layout & Container', 'bacera' ),
        'priority' => 35,
    ] );

    $wp_customize->add_setting( 'bacera_container_width', [
        'default'           => '1232',
        'sanitize_callback' => 'absint',
    ] );
    $wp_customize->add_control( 'bacera_container_width', [
        'label'       => __( 'Container Max Width (px)', 'bacera' ),
        'description' => __( 'Default is 1232', 'bacera' ),
        'section'     => 'bacera_layout',
        'type'        => 'number',
        'input_attrs' => [
            'min'  => 960,
            'max'  => 1600,
            'step' => 10,
        ],
    ] );

}
add_action( 'customize_register', 'bacera_customize_register' );

/**
 * Output CSS custom properties in <head> based on Customizer settings.
 */
function bacera_customizer_css() {
    $primary_color = get_theme_mod( 'bacera_primary_color', '#8d6a54' );
    $accent_color  = get_theme_mod( 'bacera_accent_color', '#d95f47' );
    $neutral_color = get_theme_mod( 'bacera_neutral_color', '#f8f7f3' );
    $container_w   = get_theme_mod( 'bacera_container_width', 1232 );

    // Output variables for Tailwind via custom properties
    ?>
    <style type="text/css" id="bacera-customizer-properties">
        :root {
            --theme-primary: <?php echo esc_attr( $primary_color ); ?>;
            --theme-accent: <?php echo esc_attr( $accent_color ); ?>;
            --theme-neutral: <?php echo esc_attr( $neutral_color ); ?>;
            --theme-container: <?php echo esc_attr( $container_w ); ?>px;
        }
        .bg-texture {
            background-color: var(--theme-neutral);
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
        }
        .glass-morphism {
            background-color: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .bacera-container {
            max-width: var(--theme-container);
            margin-left: auto;
            margin-right: auto;
            padding-left: clamp(1rem, 5vw, 1.5rem);
            padding-right: clamp(1rem, 5vw, 1.5rem);
            width: 100%;
        }
        .section-pad {
            padding-top: clamp(2.5rem, 6vw, 6rem);
            padding-bottom: clamp(2.5rem, 6vw, 6rem);
        }
        .pb-safe {
            padding-bottom: env(safe-area-inset-bottom, 20px);
        }
    </style>
    <?php
}
add_action( 'wp_head', 'bacera_customizer_css', 5 );
