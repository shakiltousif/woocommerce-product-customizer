<?php
/**
 * Customization page class
 *
 * @package WooCommerce_Product_Customizer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Customization Page class
 */
class WC_Product_Customizer_Page {

    /**
     * Instance
     *
     * @var WC_Product_Customizer_Page
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return WC_Product_Customizer_Page
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'register_customization_page'));
        add_action('template_redirect', array($this, 'handle_customization_page_request'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_page_scripts'));
    }

    /**
     * Register customization page
     */
    public function register_customization_page() {
        add_rewrite_rule(
            '^customize-product/?$',
            'index.php?wc_customize=1',
            'top'
        );
        
        add_rewrite_tag('%wc_customize%', '([^&]+)');
    }

    /**
     * Handle customization page request
     */
    public function handle_customization_page_request() {
        if (get_query_var('wc_customize') === '1') {
            $this->render_customization_page();
            exit;
        }
    }

    /**
     * Enqueue page scripts
     */
    public function enqueue_page_scripts() {
        if (get_query_var('wc_customize') === '1') {
            // Enqueue wizard styles
        wp_enqueue_style(
            'wc-customizer-wizard',
            WC_PRODUCT_CUSTOMIZER_PLUGIN_URL . 'assets/css/wizard.css',
            array(),
            WC_PRODUCT_CUSTOMIZER_VERSION . '.' . time() . '.v17' // Enhanced cache busting
        );

            // Enqueue wizard scripts
            wp_enqueue_script(
                'wc-customizer-wizard',
                WC_PRODUCT_CUSTOMIZER_PLUGIN_URL . 'assets/js/wizard.js',
                array('jquery'),
                WC_PRODUCT_CUSTOMIZER_VERSION . '.' . time() . '.v17', // Enhanced cache busting
                true
            );

            // Localize script
            // Get file upload settings
            $file_manager = WC_Product_Customizer_File_Manager::get_instance();
            $settings = get_option('wc_product_customizer_settings', array());
            
            wp_localize_script('wc-customizer-wizard', 'wcCustomizerWizard', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'pluginUrl' => WC_PRODUCT_CUSTOMIZER_PLUGIN_URL,
                'nonce' => wp_create_nonce('wc_customizer_nonce'),
                'uploadNonce' => wp_create_nonce('wc_customizer_upload'),
                'cartNonce' => wp_create_nonce('wc_customizer_cart'),
                'pricingNonce' => wp_create_nonce('wc_customizer_pricing'),
                'cartUrl' => wc_get_cart_url(),
                'settings' => array(
                    'maxFileSize' => isset($settings['file_upload']['max_size']) ? intval($settings['file_upload']['max_size']) : 8388608, // 8MB default
                    'allowedTypes' => isset($settings['file_upload']['allowed_types']) ? explode(',', $settings['file_upload']['allowed_types']) : array('jpg', 'jpeg', 'png', 'pdf', 'ai', 'eps'),
                    'uploadUrl' => $file_manager->get_upload_url()
                ),
                'strings' => array(
                    'loading' => __('Loading...', 'wc-product-customizer'),
                    'error' => __('An error occurred', 'wc-product-customizer'),
                    'selectZones' => __('Please select at least one position', 'wc-product-customizer'),
                    'selectMethod' => __('Please select an application method', 'wc-product-customizer'),
                    'uploadFile' => __('Please upload a file or select an alternative', 'wc-product-customizer'),
                    'positionsSelected' => __('position(s) selected (applied to all)', 'wc-product-customizer'),
                    'methodSelected' => __('method selected', 'wc-product-customizer'),
                    'fileUploaded' => __('file uploaded', 'wc-product-customizer'),
                    'textEntered' => __('text entered', 'wc-product-customizer'),
                    'customizationAdded' => __('Customization added successfully', 'wc-product-customizer'),
                    'customizationUpdated' => __('Customization updated successfully', 'wc-product-customizer'),
                    'customizationRemoved' => __('Customization removed successfully', 'wc-product-customizer'),
                    'confirmRemove' => __('Are you sure you want to remove this customization?', 'wc-product-customizer'),
                    'confirmCancel' => __('Are you sure you want to cancel? Any unsaved changes will be lost.', 'wc-product-customizer'),
                )
            ));

            // Add body class
            add_filter('body_class', array($this, 'add_body_class'));
        }
    }

    /**
     * Add body class for customization page
     */
    public function add_body_class($classes) {
        $classes[] = 'wc-customization-page';
        return $classes;
    }

    /**
     * Render customization page
     */
    public function render_customization_page() {
        // Get URL parameters
        $product_id = intval($_GET['product_id'] ?? 0);
        $cart_key = sanitize_text_field($_GET['cart_key'] ?? '');
        $return_url = esc_url_raw($_GET['return_url'] ?? wc_get_cart_url());

        // Validate parameters
        if (!$product_id || !$cart_key) {
            wp_die(__('Invalid customization request. Please try again.', 'wc-product-customizer'));
        }

        // Check if product exists and supports customization
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_die(__('Product not found.', 'wc-product-customizer'));
        }

        // Check if customization is enabled for this product
        $cart_integration = WC_Product_Customizer_Cart_Integration::get_instance();
        $reflection = new ReflectionClass($cart_integration);
        $method = $reflection->getMethod('is_customization_enabled');
        $method->setAccessible(true);
        
        if (!$method->invoke($cart_integration, $product_id)) {
            wp_die(__('This product does not support customization.', 'wc-product-customizer'));
        }

        // Get existing customization data if editing
        $existing_customization = null;
        if (WC()->cart) {
            $cart_items = WC()->cart->get_cart();
            if (isset($cart_items[$cart_key]['customization_data'])) {
                $existing_customization = $cart_items[$cart_key]['customization_data'];
            }
        }

        // Start output buffering
        ob_start();
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php esc_html_e('Customize Product', 'wc-product-customizer'); ?> - <?php bloginfo('name'); ?></title>
            <?php wp_head(); ?>
        </head>
        <body <?php body_class('wc-customization-page'); ?>>
            <div class="wc-customization-page">
                <div class="customization-page-header">
                    <div class="container">
                        <a href="<?php echo esc_url($return_url); ?>" class="back-to-cart">
                            ← <?php esc_html_e('Back to Cart', 'wc-product-customizer'); ?>
                        </a>
                        <h1><?php esc_html_e('Customize Your Product', 'wc-product-customizer'); ?></h1>
                    </div>
                </div>
                
                <div class="customization-page-content">
                    <div class="container">
                        <div class="product-preview">
                            <div class="product-image">
                                <?php echo $product->get_image('woocommerce_single'); ?>
                            </div>
                            <div class="product-details">
                                <h2><?php echo esc_html($product->get_name()); ?></h2>
                                <div class="product-price">
                                    <?php echo $product->get_price_html(); ?>
                                </div>
                                <?php if ($product->get_short_description()): ?>
                                    <div class="product-description">
                                        <?php echo wp_kses_post($product->get_short_description()); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="customization-wizard" 
                             data-product-id="<?php echo esc_attr($product_id); ?>"
                             data-cart-key="<?php echo esc_attr($cart_key); ?>"
                             data-return-url="<?php echo esc_attr($return_url); ?>">
                            <?php $this->render_wizard_content($existing_customization); ?>
                        </div>
                    </div>
                </div>
                
                <div class="customization-page-footer">
                    <div class="container">
                        <button type="button" class="button button-secondary cancel-customization">
                            <?php esc_html_e('Cancel', 'wc-product-customizer'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
        echo ob_get_clean();
    }

    /**
     * Render wizard content
     */
    private function render_wizard_content($existing_customization = null) {
        ?>
        <div class="wizard-container">
            <div class="wizard-header">
                <h3><?php esc_html_e('Customize Your Item', 'wc-product-customizer'); ?></h3>
                <div class="wizard-progress">
                    <div class="progress-step active" data-step="1">
                        <span class="step-number">1</span>
                        <span class="step-label"><?php esc_html_e('Position', 'wc-product-customizer'); ?></span>
                    </div>
                    <div class="progress-step" data-step="2">
                        <span class="step-number">2</span>
                        <span class="step-label"><?php esc_html_e('Method', 'wc-product-customizer'); ?></span>
                    </div>
                    <div class="progress-step" data-step="3">
                        <span class="step-number">3</span>
                        <span class="step-label"><?php esc_html_e('Content', 'wc-product-customizer'); ?></span>
                    </div>
                </div>
            </div>

            <div class="wizard-body">
                <!-- Step 1: Position Selection -->
                <div class="wizard-step step-1 active" data-step="1">
                    <div class="step-content">
                        <h4><?php esc_html_e('Select Customization Position', 'wc-product-customizer'); ?></h4>
                        <p class="step-description"><?php esc_html_e('Choose where you want to place your customization on the product.', 'wc-product-customizer'); ?></p>
                        <div id="zone-grid" class="zone-grid">
                            <!-- Zones will be loaded via AJAX -->
                        </div>
                        <div class="step-actions">
                            <button type="button" id="step-1-continue" class="button button-primary" disabled>
                                <?php esc_html_e('Continue', 'wc-product-customizer'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Method Selection -->
                <div class="wizard-step step-2" data-step="2">
                    <div class="step-content">
                        <h4><?php esc_html_e('Select Application Method', 'wc-product-customizer'); ?></h4>
                        <p class="step-description"><?php esc_html_e('Choose how you want to apply your customization.', 'wc-product-customizer'); ?></p>
                        <div id="method-selection" class="method-selection">
                            <!-- Methods will be loaded via AJAX -->
                        </div>
                        <div class="step-actions">
                            <button type="button" id="step-2-back" class="button button-secondary">
                                <?php esc_html_e('Back', 'wc-product-customizer'); ?>
                            </button>
                            <button type="button" id="step-2-continue" class="button button-primary" disabled>
                                <?php esc_html_e('Continue', 'wc-product-customizer'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Content Upload/Input -->
                <div class="wizard-step step-3" data-step="3">
                    <div class="step-content">
                        <h4><?php esc_html_e('Add Your Content', 'wc-product-customizer'); ?></h4>
                        <p class="step-description"><?php esc_html_e('Upload your logo or enter custom text.', 'wc-product-customizer'); ?></p>
                        
                        <!-- Content Type Selection -->
                        <div class="content-type-selection">
                            <div class="content-type-grid">
                                <div class="content-type-card" data-content-type="logo">
                                    <div class="content-type-icon">🖼️</div>
                                    <div class="content-type-title"><?php esc_html_e('Upload Logo', 'wc-product-customizer'); ?></div>
                                    <div class="content-type-description"><?php esc_html_e('Upload an image file (JPG, PNG, PDF, AI, EPS)', 'wc-product-customizer'); ?></div>
                                    <div class="checkmark" style="display: none;">✓</div>
                                </div>
                                <div class="content-type-card" data-content-type="text">
                                    <div class="content-type-icon">📝</div>
                                    <div class="content-type-title"><?php esc_html_e('Add Text', 'wc-product-customizer'); ?></div>
                                    <div class="content-type-description"><?php esc_html_e('Type text directly for printing/embroidery', 'wc-product-customizer'); ?></div>
                                    <div class="checkmark" style="display: none;">✓</div>
                                </div>
                            </div>
                            <!-- Hidden radio inputs for form submission -->
                            <input type="radio" name="content_type" value="logo" id="content_type_logo" checked style="display: none;">
                            <input type="radio" name="content_type" value="text" id="content_type_text" style="display: none;">
                        </div>

                        <!-- Logo Upload Section -->
                        <!-- DEBUG: New upload UI v10 - Matching reference design -->
                        <div class="logo-upload-section" id="logo-upload-section">
                            <div class="upload-container">
                                <div class="upload-header">
                                    <div class="upload-title">
                                        <span class="checkmark-icon">✓</span>
                                        <h4><?php esc_html_e('Upload your own logo', 'wc-product-customizer'); ?></h4>
                                    </div>
                                </div>
                                
                                <div class="upload-area" id="upload-area">
                                    <button type="button" class="choose-file-btn" id="add-logo-btn">
                                        <span class="upload-icon">↗</span>
                                        <?php esc_html_e('Choose file', 'wc-product-customizer'); ?>
                                    </button>
                                    <input type="file" id="file-input" style="display: none;" accept=".jpg,.jpeg,.png,.pdf,.ai,.eps">
                                    
                                    <p class="drag-drop-text">
                                        <?php esc_html_e('Drag \'n\' drop some files here, or click to select files', 'wc-product-customizer'); ?>
                                    </p>
                                    
                                    <p class="file-specs">
                                        <?php esc_html_e('JPG, PNG, EPS, AI, PDF Max size: 8MB', 'wc-product-customizer'); ?>
                                    </p>
                                    
                                    <p class="reassurance-message">
                                        <?php esc_html_e('Don\'t worry how it looks, we will make it look great and send a proof before we add to your products!', 'wc-product-customizer'); ?>
                                    </p>
                                </div>
                                
                                <div class="upload-progress" id="upload-progress" style="display: none;">
                                    <div class="progress-bar">
                                        <div class="progress-fill"></div>
                                    </div>
                                    <span class="progress-text"><?php esc_html_e('Uploading...', 'wc-product-customizer'); ?></span>
                                </div>
                                
                                <div class="uploaded-file" id="uploaded-file" style="display: none;">
                                    <div class="file-preview">
                                        <img id="uploaded-image-preview" src="" alt="Uploaded logo" style="display: none;">
                                        <div class="file-info">
                                            <span class="file-name"></span>
                                            <button type="button" class="remove-file-btn">×</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Alternatively section -->
                                <div class="alternative-section">
                                    <div class="alternative-divider">
                                        <span class="divider-text"><?php esc_html_e('alternatively...', 'wc-product-customizer'); ?></span>
                                    </div>
                                    
                                    <div class="alternative-options">
                                        <label class="alternative-option">
                                            <input type="radio" name="logo_alternative" value="contact_later">
                                            <span class="radio-custom"></span>
                                            <span class="option-text">
                                                <?php esc_html_e('Don\'t have your logo to hand? Don\'t worry, select here and we will contact after you place your order.', 'wc-product-customizer'); ?>
                                            </span>
                                        </label>
                                        
                                        <label class="alternative-option">
                                            <input type="radio" name="logo_alternative" value="already_have">
                                            <span class="radio-custom"></span>
                                            <span class="option-text">
                                                <?php esc_html_e('You already have my logo, it\'s just not in my account (no setup fee will be charged)', 'wc-product-customizer'); ?>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Notes section -->
                                <div class="notes-section">
                                    <label for="logo-notes" class="notes-label">
                                        <?php esc_html_e('Notes', 'wc-product-customizer'); ?>
                                    </label>
                                    <textarea 
                                        id="logo-notes" 
                                        name="logo_notes" 
                                        placeholder="<?php esc_attr_e('Please let us know if you have any special requirements', 'wc-product-customizer'); ?>"
                                        rows="3"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Text Input Section -->
                        <div class="text-input-section" id="text-input-section" style="display: none;">
                            <div class="text-config-container">
                                <div class="text-config-header">
                                    <h4><?php esc_html_e('Configure your text logo', 'wc-product-customizer'); ?></h4>
                                </div>
                                
                                <div class="text-config-form">
                                    <!-- Text Input Fields -->
                                    <div class="text-input-fields">
                                        <div class="text-input-group">
                                            <label for="text-line-1" class="text-input-label required">
                                                <?php esc_html_e('Text Line 1', 'wc-product-customizer'); ?>*
                                            </label>
                                            <input 
                                                type="text" 
                                                id="text-line-1" 
                                                name="text_line_1" 
                                                placeholder="<?php esc_attr_e('e.g Workwear Express', 'wc-product-customizer'); ?>"
                                                maxlength="50"
                                                required
                                            >
                                        </div>
                                        
                                        <div class="text-input-group">
                                            <label for="text-line-2" class="text-input-label">
                                                <?php esc_html_e('Text Line 2 (Optional)', 'wc-product-customizer'); ?>
                                            </label>
                                            <input 
                                                type="text" 
                                                id="text-line-2" 
                                                name="text_line_2" 
                                                placeholder=""
                                                maxlength="50"
                                            >
                                        </div>
                                        
                                        <div class="text-input-group">
                                            <label for="text-line-3" class="text-input-label">
                                                <?php esc_html_e('Text Line 3 (Optional)', 'wc-product-customizer'); ?>
                                            </label>
                                            <input 
                                                type="text" 
                                                id="text-line-3" 
                                                name="text_line_3" 
                                                placeholder=""
                                                maxlength="50"
                                            >
                                        </div>
                                    </div>
                                    
                                    <!-- Font and Color Selection -->
                                    <div class="text-options">
                                        <div class="option-group">
                                            <label for="text-font" class="option-label">
                                                <?php esc_html_e('Font', 'wc-product-customizer'); ?>
                                            </label>
                                            <select id="text-font" name="text_font" class="text-select">
                                                <option value="arial" selected><?php esc_html_e('Arial', 'wc-product-customizer'); ?></option>
                                                <option value="helvetica"><?php esc_html_e('Helvetica', 'wc-product-customizer'); ?></option>
                                                <option value="times"><?php esc_html_e('Times New Roman', 'wc-product-customizer'); ?></option>
                                                <option value="courier"><?php esc_html_e('Courier', 'wc-product-customizer'); ?></option>
                                                <option value="verdana"><?php esc_html_e('Verdana', 'wc-product-customizer'); ?></option>
                                                <option value="georgia"><?php esc_html_e('Georgia', 'wc-product-customizer'); ?></option>
                                            </select>
                                        </div>
                                        
                                        <div class="option-group">
                                            <label class="option-label">
                                                <?php esc_html_e('Colour', 'wc-product-customizer'); ?>
                                            </label>
                                            <div class="color-options">
                                                <label class="color-option">
                                                    <input type="radio" name="text_color" value="white" checked>
                                                    <span class="color-radio-custom white"></span>
                                                    <span class="color-label"><?php esc_html_e('White', 'wc-product-customizer'); ?></span>
                                                </label>
                                                <label class="color-option">
                                                    <input type="radio" name="text_color" value="black">
                                                    <span class="color-radio-custom black"></span>
                                                    <span class="color-label"><?php esc_html_e('Black', 'wc-product-customizer'); ?></span>
                                                </label>
                                                <label class="color-option">
                                                    <input type="radio" name="text_color" value="red">
                                                    <span class="color-radio-custom red"></span>
                                                    <span class="color-label"><?php esc_html_e('Red', 'wc-product-customizer'); ?></span>
                                                </label>
                                                <label class="color-option">
                                                    <input type="radio" name="text_color" value="blue">
                                                    <span class="color-radio-custom blue"></span>
                                                    <span class="color-label"><?php esc_html_e('Blue', 'wc-product-customizer'); ?></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Text Preview Section -->
                                    <div class="text-preview-section">
                                        <label class="preview-label">
                                            <?php esc_html_e('Text Preview', 'wc-product-customizer'); ?>
                                        </label>
                                        <div class="text-preview-area" id="text-preview-area">
                                            <div class="preview-content" id="preview-content">
                                                <span class="preview-text"><?php esc_html_e('Your text will appear here...', 'wc-product-customizer'); ?></span>
                                            </div>
                                            <button type="button" class="preview-btn" id="preview-btn">
                                                <?php esc_html_e('Preview', 'wc-product-customizer'); ?>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Notes Section -->
                                    <div class="text-notes-section">
                                        <label for="text-notes" class="notes-label">
                                            <?php esc_html_e('Notes', 'wc-product-customizer'); ?>
                                        </label>
                                        <textarea 
                                            id="text-notes" 
                                            name="text_notes" 
                                            placeholder="<?php esc_attr_e('Please let us know if you have any special requirements', 'wc-product-customizer'); ?>"
                                            rows="3"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Legacy upload area for fallback -->
                        <div class="content-upload-area" id="content-upload-area" style="display: none;">
                            <div class="upload-zone" id="upload-zone">
                                <div class="upload-icon">📁</div>
                                <p><?php esc_html_e('Click to upload or drag and drop your logo', 'wc-product-customizer'); ?></p>
                                <button type="button" id="add-logo-btn-legacy" class="button button-primary">
                                    <?php esc_html_e('Choose File', 'wc-product-customizer'); ?>
                                </button>
                                <input type="file" id="file-input-legacy" accept="image/*" style="display: none;">
                            </div>
                        </div>

                        <!-- Legacy text area for fallback -->
                        <div class="content-text-area" id="content-text-area" style="display: none;">
                            <textarea id="custom-text-input-legacy" placeholder="<?php esc_attr_e('Enter your custom text...', 'wc-product-customizer'); ?>" rows="4"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Final Step: Review and Save -->
                <div class="wizard-step final-step" data-step="4">
                    <div class="step-content">
                        <h4><?php esc_html_e('Review Your Customization', 'wc-product-customizer'); ?></h4>
                        <div class="customization-summary" id="customization-summary">
                            <!-- Summary will be populated by JavaScript -->
                        </div>
                        <div class="step-actions">
                            <button type="button" id="final-back" class="button button-secondary">
                                <?php esc_html_e('Back', 'wc-product-customizer'); ?>
                            </button>
                            <button type="button" id="add-to-cart-btn" class="button button-primary">
                                <?php esc_html_e('Save Customization', 'wc-product-customizer'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get customization URL
     */
    public static function get_customization_url($product_id, $cart_key, $return_url = null) {
        if (!$return_url) {
            $return_url = wc_get_cart_url();
        }
        
        return add_query_arg(array(
            'wc_customize' => '1',
            'product_id' => $product_id,
            'cart_key' => $cart_key,
            'return_url' => urlencode($return_url)
        ), home_url('/'));
    }
}
