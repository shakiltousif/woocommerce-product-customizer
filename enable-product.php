<?php
/**
 * Enable customization for products
 */

// Navigate to WordPress root
$wp_root = dirname(dirname(dirname(__DIR__)));
require_once($wp_root . '/wp-config.php');
require_once($wp_root . '/wp-load.php');

echo "=== Enable Product Customization ===\n\n";

// Find the Active-T-Shirt product
$products = get_posts(array(
    'post_type' => 'product',
    'post_status' => 'publish',
    'numberposts' => -1,
    'meta_query' => array(
        array(
            'key' => '_stock_status',
            'value' => 'instock'
        )
    )
));

echo "Found " . count($products) . " products:\n\n";

foreach ($products as $product) {
    $product_id = $product->ID;
    $product_name = $product->post_title;
    
    // Enable customization for this product
    update_post_meta($product_id, '_customization_enabled', 'yes');
    
    echo "✅ Enabled customization for: {$product_name} (ID: {$product_id})\n";
}

echo "\n=== All products now have customization enabled ===\n";
echo "Refresh your cart page to see the 'Add logo to this item' button!\n";
?>
