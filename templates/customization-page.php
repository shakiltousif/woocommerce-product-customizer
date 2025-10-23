<?php
/**
 * Customization Page Template
 *
 * This template is used to display the full-page customization wizard.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$cart_key = isset($_GET['cart_key']) ? sanitize_text_field($_GET['cart_key']) : '';
$return_url = isset($_GET['return_url']) ? esc_url_raw(urldecode($_GET['return_url'])) : wc_get_cart_url();

$product = wc_get_product($product_id);
$cart_item = WC()->cart->get_cart_item($cart_key);

if (!$product || !$product->exists() || ($cart_key && !$cart_item)) {
    wc_print_notice(__('Invalid product or cart item for customization.', 'wc-product-customizer'), 'error');
    return;
}

$wizard = WC_Product_Customizer_Wizard::get_instance();
?>

<div id="wc-customizer-page-wrapper" class="wc-customizer-page-wrapper">
    <header class="customization-page-header">
        <div class="container">
            <a href="<?php echo esc_url($return_url); ?>" class="back-to-cart">
                &larr; <?php esc_html_e('Back to Cart', 'wc-product-customizer'); ?>
            </a>
            <h1><?php esc_html_e('Customize Your Item', 'wc-product-customizer'); ?></h1>
        </div>
    </header>

    <main class="customization-page-content">
        <div class="container">
            <div class="product-preview">
                <div class="product-image">
                    <?php echo $product->get_image('woocommerce_thumbnail'); ?>
                </div>
                <div class="product-details">
                    <h2><?php echo esc_html($product->get_name()); ?></h2>
                    <p class="product-price"><?php echo $product->get_price_html(); ?></p>
                    <p class="product-description"><?php echo esc_html($product->get_short_description() ? $product->get_short_description() : wp_trim_words($product->get_description(), 30)); ?></p>
                </div>
            </div>

            <?php $wizard->render_wizard_content(); ?>
        </div>
    </main>

    <footer class="customization-page-footer">
        <div class="container">
            <button type="button" class="button alt cancel-customization"><?php esc_html_e('Cancel', 'wc-product-customizer'); ?></button>
        </div>
    </footer>
</div>