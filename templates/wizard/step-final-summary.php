<?php
/**
 * Wizard Final Step - Summary Template
 *
 * @package WooCommerce_Product_Customizer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wizard-step" id="step-final" data-step="final" style="display: none;">
    <div class="step-header">
        <h3><?php esc_html_e('Summary & Pricing', 'wc-product-customizer'); ?></h3>
    </div>
    
    <div class="summary-section">
        <div class="customization-summary" id="customization-summary">
            <!-- Summary will be populated by JavaScript -->
        </div>
        
        <div class="pricing-breakdown" id="pricing-breakdown">
            <!-- Pricing will be populated by JavaScript -->
        </div>
    </div>
    
    <div class="production-info">
        <h4>📋 <?php esc_html_e('Production Information', 'wc-product-customizer'); ?></h4>
        <div class="info-grid">
            <div class="info-item">
                <strong><?php esc_html_e('Production Time:', 'wc-product-customizer'); ?></strong>
                <span><?php esc_html_e('5-7 working days', 'wc-product-customizer'); ?></span>
            </div>
            <div class="info-item">
                <strong><?php esc_html_e('Quality Check:', 'wc-product-customizer'); ?></strong>
                <span><?php esc_html_e('Every item inspected', 'wc-product-customizer'); ?></span>
            </div>
            <div class="info-item">
                <strong><?php esc_html_e('Artwork Proof:', 'wc-product-customizer'); ?></strong>
                <span><?php esc_html_e('Sent within 24 hours', 'wc-product-customizer'); ?></span>
            </div>
            <div class="info-item">
                <strong><?php esc_html_e('Delivery:', 'wc-product-customizer'); ?></strong>
                <span><?php esc_html_e('Free UK delivery', 'wc-product-customizer'); ?></span>
            </div>
        </div>
    </div>
    
    <div class="guarantee-section">
        <h4>✅ <?php esc_html_e('Our Guarantee', 'wc-product-customizer'); ?></h4>
        <ul class="guarantee-list">
            <li><?php esc_html_e('100% satisfaction guarantee', 'wc-product-customizer'); ?></li>
            <li><?php esc_html_e('Free artwork adjustments', 'wc-product-customizer'); ?></li>
            <li><?php esc_html_e('Professional quality finish', 'wc-product-customizer'); ?></li>
            <li><?php esc_html_e('On-time delivery promise', 'wc-product-customizer'); ?></li>
        </ul>
    </div>
    
    <div class="next-steps">
        <h4>🚀 <?php esc_html_e('What Happens Next?', 'wc-product-customizer'); ?></h4>
        <div class="steps-timeline">
            <div class="timeline-step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h5><?php esc_html_e('Order Confirmation', 'wc-product-customizer'); ?></h5>
                    <p><?php esc_html_e('You\'ll receive an email confirmation with your order details', 'wc-product-customizer'); ?></p>
                </div>
            </div>
            <div class="timeline-step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h5><?php esc_html_e('Artwork Proof', 'wc-product-customizer'); ?></h5>
                    <p><?php esc_html_e('We\'ll send you a proof of your design within 24 hours', 'wc-product-customizer'); ?></p>
                </div>
            </div>
            <div class="timeline-step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h5><?php esc_html_e('Production', 'wc-product-customizer'); ?></h5>
                    <p><?php esc_html_e('Once approved, we\'ll start production (5-7 working days)', 'wc-product-customizer'); ?></p>
                </div>
            </div>
            <div class="timeline-step">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h5><?php esc_html_e('Delivery', 'wc-product-customizer'); ?></h5>
                    <p><?php esc_html_e('Your customized items will be delivered to your door', 'wc-product-customizer'); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="wizard-footer">
        <button type="button" class="back-btn" id="final-back">
            <?php esc_html_e('Back a step', 'wc-product-customizer'); ?>
        </button>
        <div class="final-actions">
            <button type="button" class="secondary-btn" id="save-for-later">
                <?php esc_html_e('Save for Later', 'wc-product-customizer'); ?>
            </button>
            <button type="button" class="primary-btn" id="add-to-cart-btn">
                <?php esc_html_e('Add to Cart', 'wc-product-customizer'); ?>
            </button>
        </div>
    </div>
</div>
