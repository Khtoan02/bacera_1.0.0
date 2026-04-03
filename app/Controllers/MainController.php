<?php
namespace Bacera\Controllers;

/**
 * Main Controller used for setting up hooks, assets, routing helpers to the views
 */
class MainController {
    public function __construct() {
        // Initialize hooks via models or specific components
        add_action('wp_head', [$this, 'outputCustomMeta']);

        // Initialize AJAX Controllers
        new AuthController();

        // Initialize Admin Custom Controllers
        if ( is_admin() ) {
            new AdminCustomerController();
            new TurnstileSettingsController();
        }
    }

    public function outputCustomMeta() {
        echo '';
    }
}
