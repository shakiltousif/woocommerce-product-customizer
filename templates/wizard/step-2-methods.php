<?php
/**
 * Wizard Step 2 - Method Selection Template
 *
 * @package WooCommerce_Product_Customizer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$methods = $args['methods'] ?? array();
?>

<div class="wizard-step" id="step-2" data-step="2" style="display: none;">
    <div class="step-header">
        <h3><?php esc_html_e('2. Choose application method', 'wc-product-customizer'); ?></h3>
    </div>
    
    <div class="method-selection" id="method-selection">
        <?php foreach ($methods as $method): ?>
            <div class="method-card" data-method="<?php echo esc_attr($method['name']); ?>">
                <div class="method-image">
                    <img src="<?php echo esc_url(WC_PRODUCT_CUSTOMIZER_PLUGIN_URL . 'assets/images/methods/' . $method['name'] . '-sample.jpg'); ?>" 
                         alt="<?php echo esc_attr($method['name']); ?> Sample">
                </div>
                <div class="method-info">
                    <h4>
                        <?php echo esc_html(ucfirst($method['name'])); ?>
                        <span class="checkmark" style="display: none;">✓</span>
                        <?php if ($method['name'] === 'print'): ?>
                            <span class="info-icon">🔥</span>
                        <?php endif; ?>
                    </h4>
                    <?php if ($method['name'] === 'embroidery'): ?>
                        <p class="method-subtitle"><?php esc_html_e('(Stitching)', 'wc-product-customizer'); ?></p>
                    <?php endif; ?>
                    <p class="method-description"><?php echo esc_html($method['description']); ?></p>
                    
                    <?php if ($method['name'] === 'embroidery'): ?>
                        <div class="method-features">
                            <h5><?php esc_html_e('Perfect for:', 'wc-product-customizer'); ?></h5>
                            <ul>
                                <li><?php esc_html_e('Corporate uniforms', 'wc-product-customizer'); ?></li>
                                <li><?php esc_html_e('Premium finish', 'wc-product-customizer'); ?></li>
                                <li><?php esc_html_e('Long-lasting durability', 'wc-product-customizer'); ?></li>
                                <li><?php esc_html_e('Professional appearance', 'wc-product-customizer'); ?></li>
                            </ul>
                        </div>
                    <?php elseif ($method['name'] === 'print'): ?>
                        <div class="method-features">
                            <h5><?php esc_html_e('Perfect for:', 'wc-product-customizer'); ?></h5>
                            <ul>
                                <li><?php esc_html_e('Vibrant colors', 'wc-product-customizer'); ?></li>
                                <li><?php esc_html_e('Complex designs', 'wc-product-customizer'); ?></li>
                                <li><?php esc_html_e('Photo reproduction', 'wc-product-customizer'); ?></li>
                                <li><?php esc_html_e('Cost-effective option', 'wc-product-customizer'); ?></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="method-comparison">
        <h4><?php esc_html_e('Quick Comparison', 'wc-product-customizer'); ?></h4>
        <table class="comparison-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Feature', 'wc-product-customizer'); ?></th>
                    <th><?php esc_html_e('Embroidery', 'wc-product-customizer'); ?></th>
                    <th><?php esc_html_e('Print', 'wc-product-customizer'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php esc_html_e('Durability', 'wc-product-customizer'); ?></td>
                    <td>⭐⭐⭐⭐⭐</td>
                    <td>⭐⭐⭐⭐</td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Color Options', 'wc-product-customizer'); ?></td>
                    <td>⭐⭐⭐</td>
                    <td>⭐⭐⭐⭐⭐</td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Detail Level', 'wc-product-customizer'); ?></td>
                    <td>⭐⭐⭐</td>
                    <td>⭐⭐⭐⭐⭐</td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Professional Look', 'wc-product-customizer'); ?></td>
                    <td>⭐⭐⭐⭐⭐</td>
                    <td>⭐⭐⭐⭐</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="wizard-footer">
        <button type="button" class="back-btn" id="step-2-back">
            <?php esc_html_e('Back a step', 'wc-product-customizer'); ?>
        </button>
        <div class="method-status">
            <span id="method-selected-text"></span>
        </div>
        <button type="button" class="continue-btn" id="step-2-continue" disabled>
            <?php esc_html_e('Continue', 'wc-product-customizer'); ?>
        </button>
    </div>
</div>
