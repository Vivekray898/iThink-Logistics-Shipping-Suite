<?php
/**
 * Plugin Name: iThink ShipFlow – WooCommerce Shipping for India
 * Description: Modular integration for iThink Logistics (V3 API) with Geoapify and Shipping Rates.
 * Version: 5.3
 * Author: vivekroy898
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ITHINK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// 1. Load Admin, API, and Frontend files (These don't depend on Shipping)
require_once ITHINK_PLUGIN_DIR . 'admin/class-ithink-settings.php';
require_once ITHINK_PLUGIN_DIR . 'includes/class-ithink-api.php';
require_once ITHINK_PLUGIN_DIR . 'public/class-ithink-product.php';
require_once ITHINK_PLUGIN_DIR . 'public/class-ithink-checkout.php';

// 2. Initialize Standard Features
function ithink_init_plugin() {
    new iThink_Settings();
    new iThink_API();
    new iThink_Product_Page();
    new iThink_Checkout();
}
add_action( 'plugins_loaded', 'ithink_init_plugin' );

// ==========================================
// 3. SHIPPING METHOD LOADER (The Fix)
// ==========================================

// Step A: Only include the Shipping Class when WooCommerce is ready
function ithink_include_shipping_class() {
    if ( ! class_exists( 'iThink_Shipping_Method' ) ) {
        $file_path = ITHINK_PLUGIN_DIR . 'includes/class-ithink-shipping.php';
        
        if ( file_exists( $file_path ) ) {
            require_once $file_path;
        }
    }
}
add_action( 'woocommerce_shipping_init', 'ithink_include_shipping_class' );

// Step B: Register the method with WooCommerce
function ithink_register_shipping_method( $methods ) {
    // The class name here MUST match the class name in includes/class-ithink-shipping.php
    $methods['ithink_shipping'] = 'iThink_Shipping_Method';
    return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'ithink_register_shipping_method' );