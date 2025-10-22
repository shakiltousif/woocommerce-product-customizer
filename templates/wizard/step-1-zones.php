<?php
/**
 * Wizard Step 1 - Zone Selection Template
 *
 * @package WooCommerce_Product_Customizer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$zones = $args['zones'] ?? array();
?>

<div class="wizard-step" id="step-1" data-step="1">
    <div class="step-header">
        <h3><?php esc_html_e('1. Choose position(s)', 'wc-product-customizer'); ?></h3>
        <div class="selection-counter">
            <span id="position-count">0</span> <?php esc_html_e('position(s) selected (applied to all)', 'wc-product-customizer'); ?>
        </div>
    </div>
    
    <div class="zone-grid" id="zone-grid">
        <?php foreach ($zones as $zone): ?>
            <div class="zone-card" data-zone-id="<?php echo esc_attr($zone['id']); ?>" data-zone-name="<?php echo esc_attr($zone['name']); ?>">
                <div class="zone-image">
                    <img src="<?php echo esc_url(WC_PRODUCT_CUSTOMIZER_PLUGIN_URL . 'assets/images/zones/' . strtolower(str_replace(' ', '-', $zone['name'])) . '.svg'); ?>" 
                         alt="<?php echo esc_attr($zone['name']); ?>">
                </div>
                <h4><?php echo esc_html($zone['name']); ?></h4>
                <div class="zone-availability">
                    <?php if (in_array('print', $zone['methods'])): ?>
                        <span class="method-available print">🔥 <?php esc_html_e('Print available', 'wc-product-customizer'); ?></span>
                    <?php endif; ?>
                    <?php if (in_array('embroidery', $zone['methods'])): ?>
                        <span class="method-available embroidery">🧵 <?php esc_html_e('Embroidery available', 'wc-product-customizer'); ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($zone['charge']) && $zone['charge'] > 0): ?>
                    <div class="zone-charge">
                        <small>+£<?php echo esc_html(number_format($zone['charge'], 2)); ?> per item</small>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="zone-info">
        <div class="info-card">
            <h4>💡 <?php esc_html_e('Position Selection Tips', 'wc-product-customizer'); ?></h4>
            <ul>
                <li><?php esc_html_e('Select multiple positions for maximum impact', 'wc-product-customizer'); ?></li>
                <li><?php esc_html_e('Left breast is most popular for corporate branding', 'wc-product-customizer'); ?></li>
                <li><?php esc_html_e('Big front/back positions are ideal for large designs', 'wc-product-customizer'); ?></li>
                <li><?php esc_html_e('Sleeve positions work great for team numbers or small logos', 'wc-product-customizer'); ?></li>
            </ul>
        </div>
    </div>
    
    <div class="wizard-footer">
        <div class="selection-status">
            <span class="selected-count">0 <?php esc_html_e('selected', 'wc-product-customizer'); ?></span>
        </div>
        <button type="button" class="continue-btn" id="step-1-continue" disabled>
            <?php esc_html_e('Continue', 'wc-product-customizer'); ?>
        </button>
    </div>
</div>
