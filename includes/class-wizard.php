<?php
/**
 * Frontend wizard class
 *
 * @package WooCommerce_Product_Customizer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wizard class
 */
class WC_Product_Customizer_Wizard {

    /**
     * Instance
     *
     * @var WC_Product_Customizer_Wizard
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return WC_Product_Customizer_Wizard
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
        // Always register AJAX handlers (needed for both admin and frontend)
        add_action('wp_ajax_wc_customizer_get_zones', array($this, 'ajax_get_zones'));
        add_action('wp_ajax_nopriv_wc_customizer_get_zones', array($this, 'ajax_get_zones'));
        add_action('wp_ajax_wc_customizer_get_methods', array($this, 'ajax_get_methods'));
        add_action('wp_ajax_nopriv_wc_customizer_get_methods', array($this, 'ajax_get_methods'));
        add_action('wp_ajax_wc_customizer_get_product_info', array($this, 'ajax_get_product_info'));
        add_action('wp_ajax_nopriv_wc_customizer_get_product_info', array($this, 'ajax_get_product_info'));
        add_action('wp_ajax_wc_customizer_save_session', array($this, 'ajax_save_session'));
        add_action('wp_ajax_nopriv_wc_customizer_save_session', array($this, 'ajax_save_session'));
        add_action('wp_ajax_wc_customizer_load_session', array($this, 'ajax_load_session'));
        add_action('wp_ajax_nopriv_wc_customizer_load_session', array($this, 'ajax_load_session'));
        add_action('wp_ajax_wc_customizer_remove_customization', array($this, 'ajax_remove_customization'));
        add_action('wp_ajax_nopriv_wc_customizer_remove_customization', array($this, 'ajax_remove_customization'));
        
        // Only enqueue scripts and render modal on frontend
        if (!is_admin()) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_action('wp_footer', array($this, 'render_wizard_modal'));
        }
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (!is_cart() && !is_checkout()) {
            return;
        }

        // Enqueue styles
        wp_enqueue_style(
            'wc-customizer-wizard',
            WC_PRODUCT_CUSTOMIZER_PLUGIN_URL . 'assets/css/wizard.css',
            array(),
            WC_PRODUCT_CUSTOMIZER_VERSION
        );

        // Enqueue scripts
        wp_enqueue_script(
            'wc-customizer-wizard',
            WC_PRODUCT_CUSTOMIZER_PLUGIN_URL . 'assets/js/wizard.js',
            array('jquery'),
            WC_PRODUCT_CUSTOMIZER_VERSION,
            true
        );

        // Localize script (standardized nonce names)
        wp_localize_script('wc-customizer-wizard', 'wcCustomizerWizard', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_customizer_nonce'),
            'uploadNonce' => wp_create_nonce('wc_customizer_upload'),
            'cartNonce' => wp_create_nonce('wc_customizer_cart'),
            'pricingNonce' => wp_create_nonce('wc_customizer_pricing'),
            'strings' => array(
                'loading' => __('Loading...', 'wc-product-customizer'),
                'error' => __('An error occurred', 'wc-product-customizer'),
                'selectZones' => __('Please select at least one position', 'wc-product-customizer'),
                'selectMethod' => __('Please select an application method', 'wc-product-customizer'),
                'uploadFile' => __('Please upload a file or select an alternative', 'wc-product-customizer'),
                'positionsSelected' => __('position(s) selected (applied to all)', 'wc-product-customizer'),
                'setupCost' => __('one-time new logo setup cost', 'wc-product-customizer'),
                'addingLogo' => __('Adding a new logo or text?', 'wc-product-customizer'),
                'emailMockup' => __("We'll email a mock up to approve before we begin!", 'wc-product-customizer'),
                'confidence' => __('Add your logo with confidence', 'wc-product-customizer'),
                'designTeam' => __('Our in-house design team based in Durham, UK 🇬🇧 will send you a proof of your logo/text for you to approve before we begin production.', 'wc-product-customizer'),
                'returningCustomer' => __('Returning Customer?', 'wc-product-customizer'),
                'logIn' => __('Log In', 'wc-product-customizer'),
                'accessLogos' => __('Log in to access your previous logos', 'wc-product-customizer'),
                'addNewLogo' => __('Add new logo', 'wc-product-customizer'),
                'backStep' => __('Back a step', 'wc-product-customizer'),
                'continue' => __('Continue', 'wc-product-customizer'),
                'selected' => __('selected', 'wc-product-customizer'),
                'embroiderySelected' => __('Embroidery selected', 'wc-product-customizer'),
                'printSelected' => __('Print selected', 'wc-product-customizer'),
                'confirmRemove' => __('Are you sure you want to remove this customization?', 'wc-product-customizer')
            ),
            'settings' => array(
                'maxFileSize' => WC_Product_Customizer_File_Manager::get_instance()->get_max_file_size(),
                'allowedTypes' => WC_Product_Customizer_File_Manager::get_instance()->get_allowed_file_types()
            )
        ));
    }

    /**
     * Render wizard modal in footer
     */
    public function render_wizard_modal() {
        if (!is_cart() && !is_checkout()) {
            return;
        }
        ?>
        <div id="wc-customizer-wizard-modal" class="wc-customizer-modal" style="display: none;">
            <div class="wc-customizer-modal-overlay"></div>
            <div class="wc-customizer-modal-content">
                <div class="wc-customizer-modal-header">
                    <button type="button" class="wc-customizer-modal-close">&times;</button>
                </div>
                <div class="wc-customizer-modal-body">
                    <?php $this->render_wizard_content(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render wizard content
     */
    private function render_wizard_content() {
        ?>
        <div class="customization-wizard">
            <!-- Wizard Header -->
            <div class="wizard-header">
                <div class="wizard-title">
                    <h2><?php esc_html_e('Add your logo', 'wc-product-customizer'); ?></h2>
                </div>
                
                <div class="customizing-product">
                    <div class="product-info-simple">
                        <div class="product-image">
                            <img src="" alt="" id="wizard-product-image">
                        </div>
                        <div class="product-name" id="wizard-product-name"></div>
                    </div>
                </div>
            </div>

            <!-- Step 1: Choose Position(s) -->
            <div class="wizard-step" id="step-1" data-step="1">
                <div class="step-header">
                    <h3><?php esc_html_e('1. Choose position(s)', 'wc-product-customizer'); ?></h3>
                    <div class="selection-counter">
                        <span id="position-count">0</span> <?php esc_html_e('position(s) selected (applied to all)', 'wc-product-customizer'); ?>
                    </div>
                </div>
                
                <div class="step-content">
                    <div class="zone-grid" id="zone-grid">
                        <!-- Zones will be loaded dynamically -->
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

            <!-- Step 2: Choose Application Method -->
            <div class="wizard-step" id="step-2" data-step="2" style="display: none;">
                <div class="step-header">
                    <h3><?php esc_html_e('2. Choose application method', 'wc-product-customizer'); ?></h3>
                </div>
                
                <div class="step-content">
                    <div class="method-selection" id="method-selection">
                        <!-- Methods will be loaded dynamically -->
                    </div>
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

            <!-- Step 3: Choose and Add Logo or Text -->
            <div class="wizard-step" id="step-3" data-step="3" style="display: none;">
                <div class="step-header">
                    <h3><?php esc_html_e('3. Choose and add your logo or text', 'wc-product-customizer'); ?></h3>
                </div>
                
                <div class="step-content">
                    <!-- Content Type Selection -->
                    <div class="content-type-selection">
                        <div class="content-type-options">
                            <label class="content-type-option">
                                <input type="radio" name="content_type" value="logo" checked>
                                <div class="option-content">
                                    <span class="option-icon">🖼️</span>
                                    <span class="option-title"><?php esc_html_e('Upload Logo', 'wc-product-customizer'); ?></span>
                                    <span class="option-description"><?php esc_html_e('Upload an image file (JPG, PNG, PDF, AI, EPS)', 'wc-product-customizer'); ?></span>
                                </div>
                            </label>
                            <label class="content-type-option">
                                <input type="radio" name="content_type" value="text">
                                <div class="option-content">
                                    <span class="option-icon">📝</span>
                                    <span class="option-title"><?php esc_html_e('Add Text', 'wc-product-customizer'); ?></span>
                                    <span class="option-description"><?php esc_html_e('Type text directly for printing/embroidery', 'wc-product-customizer'); ?></span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Logo Upload Section -->
                    <div class="logo-upload-section" id="logo-upload-section">
                        <div class="upload-area" id="upload-area">
                            <button type="button" class="add-logo-btn" id="add-logo-btn">
                                <span class="upload-icon">📤</span>
                                <?php esc_html_e('Add new logo', 'wc-product-customizer'); ?>
                            </button>
                            <input type="file" id="file-input" style="display: none;" accept=".jpg,.jpeg,.png,.pdf,.ai,.eps">
                        </div>
                        
                        <div class="setup-cost" id="setup-cost">
                            <strong>£<span id="setup-fee-amount">8.95</span></strong> 
                            <?php esc_html_e('one-time new logo setup cost', 'wc-product-customizer'); ?>
                        </div>
                        
                        <p class="setup-description">
                            <?php esc_html_e('This cost includes the full digitisation of your logo. Don\'t worry how it looks when it\'s uploaded, we will send a proof before we begin production!', 'wc-product-customizer'); ?>
                        </p>
                        
                        <div class="upload-progress" id="upload-progress" style="display: none;">
                            <div class="progress-bar">
                                <div class="progress-fill"></div>
                            </div>
                            <span class="progress-text">Uploading...</span>
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
                    </div>

                    <!-- Text Input Section -->
                    <div class="text-input-section" id="text-input-section" style="display: none;">
                        <div class="text-input-area">
                            <label for="custom-text-input" class="text-input-label">
                                <?php esc_html_e('Enter your text:', 'wc-product-customizer'); ?>
                            </label>
                            <textarea 
                                id="custom-text-input" 
                                name="custom_text" 
                                placeholder="<?php esc_attr_e('Type your text here...', 'wc-product-customizer'); ?>"
                                rows="3"
                                maxlength="100"
                            ></textarea>
                            <div class="text-input-info">
                                <span class="character-count">
                                    <span id="text-char-count">0</span>/100 <?php esc_html_e('characters', 'wc-product-customizer'); ?>
                                </span>
                                <span class="text-preview-label"><?php esc_html_e('Preview:', 'wc-product-customizer'); ?></span>
                            </div>
                            <div class="text-preview" id="text-preview">
                                <span class="preview-text"><?php esc_html_e('Your text will appear here...', 'wc-product-customizer'); ?></span>
                            </div>
                        </div>
                        
                        <div class="setup-cost" id="text-setup-cost">
                            <strong>£<span id="text-setup-fee-amount">2.95</span></strong> 
                            <?php esc_html_e('one-time text setup cost', 'wc-product-customizer'); ?>
                        </div>
                        
                        <p class="setup-description">
                            <?php esc_html_e('This cost includes font selection and text formatting for your design.', 'wc-product-customizer'); ?>
                        </p>
                    </div>

                    <div class="confidence-section">
                        <h4>🎯 <?php esc_html_e('Add your logo with confidence', 'wc-product-customizer'); ?></h4>
                        <p><?php esc_html_e('Our in-house design team based in Durham, UK 🇬🇧 will send you a proof of your logo/text for you to approve before we begin production.', 'wc-product-customizer'); ?></p>
                    </div>
                </div>
                
                <div class="wizard-footer">
                    <button type="button" class="back-btn" id="step-3-back">
                        <?php esc_html_e('Back a step', 'wc-product-customizer'); ?>
                    </button>
                    <button type="button" class="continue-btn" id="step-3-continue" disabled>
                        <?php esc_html_e('Continue', 'wc-product-customizer'); ?>
                    </button>
                </div>
            </div>

            <!-- Final Step: Review and Add to Cart -->
            <div class="wizard-step" id="step-final" data-step="final" style="display: none;">
                <div class="step-header">
                    <h3><?php esc_html_e('Review Your Customization', 'wc-product-customizer'); ?></h3>
                </div>
                
                <div class="step-content">
                    <div class="customization-summary" id="customization-summary">
                        <!-- Summary will be populated dynamically -->
                    </div>
                    
                    <div class="pricing-breakdown" id="pricing-breakdown">
                        <!-- Pricing breakdown will be populated dynamically -->
                    </div>
                </div>
                
                <div class="wizard-footer">
                    <button type="button" class="back-btn" id="final-back">
                        <?php esc_html_e('Back a step', 'wc-product-customizer'); ?>
                    </button>
                    <button type="button" class="add-to-cart-btn" id="add-to-cart-btn">
                        <?php esc_html_e('Save & Return to Cart', 'wc-product-customizer'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler to get zones for product
     */
    public function ajax_get_zones() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wc_customizer_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'wc-product-customizer')));
        }
        
        $product_id = intval($_POST['product_id'] ?? 0);
        
        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product ID', 'wc-product-customizer')));
        }
        
        $db = WC_Product_Customizer_Database::get_instance();
        $zones = $db->get_customization_zones();
        
        // Filter zones based on product compatibility if needed
        $available_zones = array();
        foreach ($zones as $zone) {
            $available_zones[] = array(
                'id' => $zone->id,
                'name' => $zone->name,
                'group' => $zone->zone_group,
                'methods' => explode(',', $zone->methods_available),
                'charge' => floatval($zone->zone_charge)
            );
        }
        
        wp_send_json_success(array('zones' => $available_zones));
    }

    /**
     * AJAX handler to get product information
     */
    public function ajax_get_product_info() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wc_customizer_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'wc-product-customizer')));
        }

        $product_id = intval($_POST['product_id'] ?? 0);

        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product ID', 'wc-product-customizer')));
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            wp_send_json_error(array('message' => __('Product not found', 'wc-product-customizer')));
        }

        $product_name = $product->get_name();
        $product_image_id = $product->get_image_id();
        $product_image_url = $product_image_id ? wp_get_attachment_image_url($product_image_id, 'thumbnail') : '';

        wp_send_json_success(array(
            'product_name' => $product_name,
            'product_image' => $product_image_url
        ));
    }

    /**
     * AJAX handler to get customization methods
     */
    public function ajax_get_methods() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wc_customizer_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'wc-product-customizer')));
        }
        
        $db = WC_Product_Customizer_Database::get_instance();
        $types = $db->get_customization_types();
        
        $methods = array();
        foreach ($types as $type) {
            $methods[] = array(
                'id' => $type->id,
                'name' => $type->name,
                'description' => $type->description,
                'setup_fee' => floatval($type->setup_fee)
            );
        }
        
        wp_send_json_success(array('methods' => $methods));
    }

    /**
     * AJAX handler to save session data
     */
    public function ajax_save_session() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wc_customizer_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'wc-product-customizer')));
        }
        
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $cart_item_key = sanitize_text_field($_POST['cart_item_key'] ?? '');
        $step_data = $_POST['step_data'] ?? array();
        
        if (empty($session_id)) {
            $session_id = wp_generate_password(16, false);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_customization_sessions';
        
        // Set expiration to 24 hours from now
        $expires_at = date('Y-m-d H:i:s', time() + 86400);
        
        $result = $wpdb->replace(
            $table_name,
            array(
                'session_id' => $session_id,
                'cart_item_key' => $cart_item_key,
                'step_data' => maybe_serialize($step_data),
                'expires_at' => $expires_at
            ),
            array('%s', '%s', '%s', '%s')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('session_id' => $session_id));
        } else {
            wp_send_json_error(array('message' => __('Failed to save session', 'wc-product-customizer')));
        }
    }

    /**
     * AJAX handler to load session data
     */
    public function ajax_load_session() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wc_customizer_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'wc-product-customizer')));
        }
        
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        
        if (empty($session_id)) {
            wp_send_json_error(array('message' => __('Invalid session ID', 'wc-product-customizer')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_customization_sessions';
        
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE session_id = %s AND expires_at > NOW()",
            $session_id
        ));
        
        if ($session) {
            $step_data = maybe_unserialize($session->step_data);
            wp_send_json_success(array(
                'step_data' => $step_data,
                'cart_item_key' => $session->cart_item_key
            ));
        } else {
            wp_send_json_error(array('message' => __('Session not found or expired', 'wc-product-customizer')));
        }
    }

    /**
     * Get wizard template
     *
     * @param string $template_name
     * @param array $args
     * @return string
     */
    public function get_template($template_name, $args = array()) {
        $template_path = WC_PRODUCT_CUSTOMIZER_PLUGIN_DIR . 'templates/wizard/' . $template_name . '.php';
        
        if (file_exists($template_path)) {
            extract($args);
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        
        return '';
    }

    /**
     * Render zone card
     *
     * @param array $zone
     * @return string
     */
    public function render_zone_card($zone) {
        ob_start();
        ?>
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
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render method card
     *
     * @param array $method
     * @return string
     */
    public function render_method_card($method) {
        ob_start();
        ?>
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
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler to remove customization from cart
     */
    public function ajax_remove_customization() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wc_customizer_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'wc-product-customizer')));
        }

        $cart_item_key = sanitize_text_field($_POST['cart_item_key'] ?? '');

        if (empty($cart_item_key)) {
            wp_send_json_error(array('message' => __('Invalid cart item key', 'wc-product-customizer')));
        }

        // Get cart instance
        $cart = WC()->cart;
        
        if (!$cart) {
            wp_send_json_error(array('message' => __('Cart not available', 'wc-product-customizer')));
        }

        // Get cart item
        $cart_item = $cart->get_cart_item($cart_item_key);
        
        if (!$cart_item) {
            wp_send_json_error(array('message' => __('Cart item not found', 'wc-product-customizer')));
        }

        // Check if item has customization
        if (!isset($cart_item['customization_data'])) {
            wp_send_json_error(array('message' => __('No customization found for this item', 'wc-product-customizer')));
        }

        // Remove customization data from cart item
        unset($cart_item['customization_data']);
        unset($cart_item['customization_fees']);
        unset($cart_item['unique_key']);

        // Update cart item
        $cart->cart_contents[$cart_item_key] = $cart_item;
        $cart->set_session();

        wp_send_json_success(array('message' => __('Customization removed successfully', 'wc-product-customizer')));
    }
}
