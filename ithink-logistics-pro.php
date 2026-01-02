<?php
/**
 * Plugin Name: iThink Logistics Pro (Modular)
 * Description: Modular integration for iThink Logistics (V3 API) with Geoapify.
 * Version: 5.1
 * Author: Vivekroy88
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define Constants for easy access to paths
define( 'ITHINK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// 1. Load Admin Settings
require_once ITHINK_PLUGIN_DIR . 'admin/class-ithink-settings.php';

// 2. Load API Handler (Backend Logic)
require_once ITHINK_PLUGIN_DIR . 'includes/class-ithink-api.php';

// 3. Load Frontend Features
require_once ITHINK_PLUGIN_DIR . 'public/class-ithink-product.php';
require_once ITHINK_PLUGIN_DIR . 'public/class-ithink-checkout.php';

// Initialize Classes
function ithink_init_plugin() {
    new iThink_Settings();
    new iThink_API();
    new iThink_Product_Page();
    new iThink_Checkout();
}
add_action( 'plugins_loaded', 'ithink_init_plugin' );