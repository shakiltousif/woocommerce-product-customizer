<?php
/**
 * Wizard Step 3 - File Upload Template
 *
 * @package WooCommerce_Product_Customizer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$max_file_size = $args['max_file_size'] ?? 8388608; // 8MB default
$allowed_types = $args['allowed_types'] ?? array('jpg', 'jpeg', 'png', 'pdf', 'ai', 'eps');
?>

<div class="wizard-step" id="step-3" data-step="3" style="display: none;">
    <div class="step-header">
        <h3><?php esc_html_e('3. Choose and add logo', 'wc-product-customizer'); ?></h3>
        <div class="setup-fee-info">
            <?php esc_html_e('One-time setup fee:', 'wc-product-customizer'); ?> 
            <strong>£<span id="setup-fee-amount">8.95</span></strong>
        </div>
    </div>
    
    <div class="upload-section">
        <div class="upload-area" id="upload-area">
            <div class="upload-icon">📁</div>
            <h4><?php esc_html_e('Upload your logo', 'wc-product-customizer'); ?></h4>
            <p><?php esc_html_e('Drag and drop your file here or click to browse', 'wc-product-customizer'); ?></p>
            <button type="button" class="upload-btn" id="add-logo-btn">
                <?php esc_html_e('Choose File', 'wc-product-customizer'); ?>
            </button>
            <input type="file" id="file-input" accept=".jpg,.jpeg,.png,.pdf,.ai,.eps" style="display: none;">
        </div>
        
        <div class="uploaded-file" id="uploaded-file" style="display: none;">
            <div class="file-preview">
                <div class="file-icon">📄</div>
                <div class="file-info">
                    <div class="file-name"></div>
                    <div class="file-status">✓ <?php esc_html_e('Ready for production', 'wc-product-customizer'); ?></div>
                </div>
                <button type="button" class="remove-file-btn">✕</button>
            </div>
        </div>
        
        <div class="upload-progress" id="upload-progress" style="display: none;">
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <div class="progress-text"><?php esc_html_e('Uploading...', 'wc-product-customizer'); ?></div>
        </div>
    </div>
    
    <div class="file-requirements">
        <h4><?php esc_html_e('File Requirements', 'wc-product-customizer'); ?></h4>
        <div class="requirements-grid">
            <div class="requirement-item">
                <strong><?php esc_html_e('File Types:', 'wc-product-customizer'); ?></strong>
                <span><?php echo esc_html(strtoupper(implode(', ', $allowed_types))); ?></span>
            </div>
            <div class="requirement-item">
                <strong><?php esc_html_e('Max Size:', 'wc-product-customizer'); ?></strong>
                <span><?php echo esc_html(size_format($max_file_size)); ?></span>
            </div>
            <div class="requirement-item">
                <strong><?php esc_html_e('Resolution:', 'wc-product-customizer'); ?></strong>
                <span><?php esc_html_e('300 DPI minimum', 'wc-product-customizer'); ?></span>
            </div>
            <div class="requirement-item">
                <strong><?php esc_html_e('Colors:', 'wc-product-customizer'); ?></strong>
                <span><?php esc_html_e('RGB or CMYK', 'wc-product-customizer'); ?></span>
            </div>
        </div>
    </div>
    
    <div class="logo-tips">
        <h4>💡 <?php esc_html_e('Logo Tips for Best Results', 'wc-product-customizer'); ?></h4>
        <div class="tips-grid">
            <div class="tip-item">
                <h5><?php esc_html_e('Vector Files', 'wc-product-customizer'); ?></h5>
                <p><?php esc_html_e('AI or EPS files give the sharpest results at any size', 'wc-product-customizer'); ?></p>
            </div>
            <div class="tip-item">
                <h5><?php esc_html_e('High Resolution', 'wc-product-customizer'); ?></h5>
                <p><?php esc_html_e('Use 300 DPI or higher for crisp, professional results', 'wc-product-customizer'); ?></p>
            </div>
            <div class="tip-item">
                <h5><?php esc_html_e('Simple Colors', 'wc-product-customizer'); ?></h5>
                <p><?php esc_html_e('Fewer colors often look better, especially for embroidery', 'wc-product-customizer'); ?></p>
            </div>
            <div class="tip-item">
                <h5><?php esc_html_e('Clear Background', 'wc-product-customizer'); ?></h5>
                <p><?php esc_html_e('Transparent or white backgrounds work best', 'wc-product-customizer'); ?></p>
            </div>
        </div>
    </div>
    
    <div class="returning-customer-section">
        <h4><?php esc_html_e('Returning Customer?', 'wc-product-customizer'); ?></h4>
        <p><?php esc_html_e('If you\'ve uploaded this logo before, you can reuse it without paying the setup fee again.', 'wc-product-customizer'); ?></p>
        <button type="button" class="secondary-btn" id="browse-previous-logos">
            <?php esc_html_e('Browse Previous Logos', 'wc-product-customizer'); ?>
        </button>
    </div>
    
    <div class="wizard-footer">
        <button type="button" class="back-btn" id="step-3-back">
            <?php esc_html_e('Back a step', 'wc-product-customizer'); ?>
        </button>
        <div class="upload-status">
            <span id="upload-status-text"><?php esc_html_e('Please upload a file', 'wc-product-customizer'); ?></span>
        </div>
        <button type="button" class="continue-btn" id="step-3-continue" disabled>
            <?php esc_html_e('Continue', 'wc-product-customizer'); ?>
        </button>
    </div>
</div>
