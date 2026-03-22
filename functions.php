<?php
/**
 * Bacera functions and definitions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define Constants
define( 'BACERA_THEME_DIR', trailingslashit( get_template_directory() ) );
define( 'BACERA_THEME_URI', trailingslashit( get_template_directory_uri() ) );
define( 'BACERA_THEME_VERSION', '1.0.0' );

// Simple Autoloader for MVC
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'Bacera\\';

    // Base directory for the namespace prefix
    $base_dir = BACERA_THEME_DIR . 'app/';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

use Bacera\Controllers\MainController;

function bacera_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ) );
}
add_action( 'after_setup_theme', 'bacera_setup' );

function bacera_enqueue_scripts() {
    wp_enqueue_style( 'bacera-style', get_stylesheet_uri(), array(), BACERA_THEME_VERSION );
    wp_enqueue_style( 'bacera-main-css', BACERA_THEME_URI . 'assets/css/main.css', array(), BACERA_THEME_VERSION );
    wp_enqueue_script( 'bacera-main-js', BACERA_THEME_URI . 'assets/js/main.js', array('jquery'), BACERA_THEME_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'bacera_enqueue_scripts' );

// Boot the main controller
add_action('init', function() {
    new MainController();
});
