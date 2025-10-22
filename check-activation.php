<?php
/**
 * Quick check to see if the plugin is activated
 */

// Navigate to WordPress root
$wp_root = dirname(dirname(dirname(__DIR__)));
require_once($wp_root . '/wp-config.php');
require_once($wp_root . '/wp-load.php');

echo "=== Plugin Activation Check ===\n\n";

// Check if WooCommerce is active
if (class_exists('WooCommerce')) {
    echo "✅ WooCommerce is active\n";
} else {
    echo "❌ WooCommerce is NOT active\n";
}

// Check if our plugin is active
$active_plugins = get_option('active_plugins');
$plugin_path = 'woocommerce-product-customizer/woocommerce-product-customizer.php';

if (in_array($plugin_path, $active_plugins)) {
    echo "✅ WooCommerce Product Customizer is ACTIVE\n";
} else {
    echo "❌ WooCommerce Product Customizer is NOT ACTIVE\n";
    echo "\nTO ACTIVATE:\n";
    echo "1. Go to WordPress Admin: " . get_admin_url() . "plugins.php\n";
    echo "2. Find 'WooCommerce Product Customizer'\n";
    echo "3. Click 'Activate'\n";
}

// Check if customization tables exist
global $wpdb;
$table_name = $wpdb->prefix . 'wc_customization_zones';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

if ($table_exists) {
    echo "✅ Database tables exist\n";
} else {
    echo "❌ Database tables do NOT exist\n";
    echo "   (Tables will be created when plugin is activated)\n";
}

echo "\n=== Check Complete ===\n";
?>
