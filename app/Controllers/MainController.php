<?php
namespace Bacera\Controllers;

/**
 * Main Controller used for setting up hooks, assets, routing helpers to the views
 */
class MainController {
    public function __construct() {
        // Initialize hooks via models or specific components
        add_action('wp_head', [$this, 'outputCustomMeta']);
    }

    public function outputCustomMeta() {
        echo '<!-- Bacera Theme Framework Powered -->';
    }
}
