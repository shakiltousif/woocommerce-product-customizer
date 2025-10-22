<?php
/**
 * WooCommerce Product Customizer - Plugin Verification Script
 * 
 * Run this script to verify the plugin is properly installed
 */

echo "=== WooCommerce Product Customizer - Plugin Verification ===\n\n";

// Check if we're in the correct directory
$current_dir = basename(getcwd());
if ($current_dir !== 'woocommerce-product-customizer') {
    echo "❌ ERROR: Please run this script from the plugin directory\n";
    echo "Current directory: {$current_dir}\n";
    echo "Expected: woocommerce-product-customizer\n\n";
    exit(1);
}

echo "✅ Running from correct plugin directory\n\n";

// Test 1: Check main plugin file
echo "1. MAIN PLUGIN FILE\n";
echo "===================\n";
if (file_exists('woocommerce-product-customizer.php')) {
    $content = file_get_contents('woocommerce-product-customizer.php');
    if (strpos($content, 'Plugin Name:') !== false) {
        echo "✅ Main plugin file exists with proper header\n";
    } else {
        echo "⚠️  Main plugin file exists but missing header\n";
    }
} else {
    echo "❌ Main plugin file missing\n";
}

// Test 2: Check includes directory
echo "\n2. INCLUDES DIRECTORY\n";
echo "=====================\n";
$required_classes = [
    'class-database.php',
    'class-admin.php', 
    'class-cart-integration.php',
    'class-wizard.php',
    'class-pricing.php',
    'class-file-manager.php'
];

$includes_score = 0;
foreach ($required_classes as $class) {
    if (file_exists("includes/{$class}")) {
        echo "✅ {$class}\n";
        $includes_score++;
    } else {
        echo "❌ {$class} [MISSING]\n";
    }
}
echo "Includes Score: {$includes_score}/" . count($required_classes) . "\n";

// Test 3: Check assets
echo "\n3. ASSETS DIRECTORY\n";
echo "===================\n";
$asset_files = [
    'assets/css/wizard.css',
    'assets/css/admin.css',
    'assets/js/wizard.js',
    'assets/js/admin.js'
];

$assets_score = 0;
foreach ($asset_files as $asset) {
    if (file_exists($asset)) {
        $size = round(filesize($asset) / 1024, 2);
        echo "✅ {$asset} ({$size} KB)\n";
        $assets_score++;
    } else {
        echo "❌ {$asset} [MISSING]\n";
    }
}
echo "Assets Score: {$assets_score}/" . count($asset_files) . "\n";

// Test 4: Check zone images
echo "\n4. ZONE IMAGES\n";
echo "==============\n";
$zone_images = [
    'left-breast.svg',
    'right-breast.svg', 
    'centre-of-chest.svg',
    'left-sleeve.svg',
    'right-sleeve.svg',
    'big-front.svg',
    'big-back.svg',
    'nape-of-neck.svg'
];

$images_score = 0;
foreach ($zone_images as $image) {
    if (file_exists("assets/images/zones/{$image}")) {
        echo "✅ {$image}\n";
        $images_score++;
    } else {
        echo "❌ {$image} [MISSING]\n";
    }
}
echo "Zone Images Score: {$images_score}/" . count($zone_images) . "\n";

// Test 5: Check templates
echo "\n5. TEMPLATE FILES\n";
echo "=================\n";
$templates = [
    'step-1-zones.php',
    'step-2-methods.php',
    'step-3-upload.php', 
    'step-final-summary.php'
];

$templates_score = 0;
foreach ($templates as $template) {
    if (file_exists("templates/wizard/{$template}")) {
        echo "✅ {$template}\n";
        $templates_score++;
    } else {
        echo "❌ {$template} [MISSING]\n";
    }
}
echo "Templates Score: {$templates_score}/" . count($templates) . "\n";

// Test 6: Check documentation
echo "\n6. DOCUMENTATION\n";
echo "================\n";
$docs = ['README.md', 'INSTALLATION.md', 'PROGRESS.md', 'PLAN.md'];
$docs_score = 0;
foreach ($docs as $doc) {
    if (file_exists($doc)) {
        echo "✅ {$doc}\n";
        $docs_score++;
    } else {
        echo "❌ {$doc} [MISSING]\n";
    }
}
echo "Documentation Score: {$docs_score}/" . count($docs) . "\n";

// Calculate total score
$total_score = $includes_score + $assets_score + $images_score + $templates_score + $docs_score + 1; // +1 for main file
$max_score = count($required_classes) + count($asset_files) + count($zone_images) + count($templates) + count($docs) + 1;
$percentage = round(($total_score / $max_score) * 100);

echo "\n7. FINAL VERIFICATION\n";
echo "=====================\n";
echo "Total Score: {$total_score}/{$max_score} ({$percentage}%)\n\n";

if ($percentage >= 95) {
    echo "🎉 VERIFICATION RESULT: EXCELLENT\n";
    echo "✅ Plugin is ready for WordPress installation!\n\n";
    echo "NEXT STEPS:\n";
    echo "1. Go to WordPress Admin > Plugins\n";
    echo "2. Find 'WooCommerce Product Customizer'\n";
    echo "3. Click 'Activate'\n";
    echo "4. Go to Customization > Settings to configure\n";
} elseif ($percentage >= 80) {
    echo "✅ VERIFICATION RESULT: GOOD\n";
    echo "Plugin is mostly complete but may need minor fixes.\n";
} else {
    echo "⚠️  VERIFICATION RESULT: NEEDS ATTENTION\n";
    echo "Some files are missing. Please check the installation.\n";
}

echo "\nPLUGIN DIRECTORY STRUCTURE:\n";
echo "wp-content/plugins/woocommerce-product-customizer/\n";
echo "├── woocommerce-product-customizer.php (Main file)\n";
echo "├── includes/ (PHP classes)\n";
echo "├── assets/ (CSS, JS, Images)\n";
echo "├── templates/ (Wizard templates)\n";
echo "└── *.md (Documentation)\n\n";

echo "=== VERIFICATION COMPLETE ===\n";
?>
