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
        
        // Only enqueue scripts on customization page
        if (!is_admin()) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        }
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only enqueue on customization page
        if (get_query_var('wc_customize') !== '1') {
            return;
        }

        // Enqueue styles
        wp_enqueue_style(
            'wc-customizer-wizard',
            WC_PRODUCT_CUSTOMIZER_PLUGIN_URL . 'assets/css/wizard.css',
            array(),
            WC_PRODUCT_CUSTOMIZER_VERSION . '.' . time() . '.v9' // Enhanced cache busting
        );

        // Enqueue scripts
        wp_enqueue_script(
            'wc-customizer-wizard',
            WC_PRODUCT_CUSTOMIZER_PLUGIN_URL . 'assets/js/wizard.js',
            array('jquery'),
            WC_PRODUCT_CUSTOMIZER_VERSION . '.' . time() . '.v9', // Enhanced cache busting
            true
        );

        // Debug: Check if constants are defined
        error_log('WC_PRODUCT_CUSTOMIZER_PLUGIN_URL: ' . (defined('WC_PRODUCT_CUSTOMIZER_PLUGIN_URL') ? WC_PRODUCT_CUSTOMIZER_PLUGIN_URL : 'NOT DEFINED'));
        
        // Localize script (standardized nonce names)
        wp_localize_script('wc-customizer-wizard', 'wcCustomizerWizard', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'pluginUrl' => defined('WC_PRODUCT_CUSTOMIZER_PLUGIN_URL') ? WC_PRODUCT_CUSTOMIZER_PLUGIN_URL : '',
            'nonce' => wp_create_nonce('wc_customizer_nonce'),
            'uploadNonce' => wp_create_nonce('wc_customizer_upload'),
            'cartNonce' => wp_create_nonce('wc_customizer_cart'),
            'pricingNonce' => wp_create_nonce('wc_customizer_pricing'),
            'cartUrl' => wc_get_cart_url(),
            'debug' => array(
                'pluginUrl' => defined('WC_PRODUCT_CUSTOMIZER_PLUGIN_URL') ? WC_PRODUCT_CUSTOMIZER_PLUGIN_URL : 'NOT DEFINED',
                'pluginDir' => defined('WC_PRODUCT_CUSTOMIZER_PLUGIN_DIR') ? WC_PRODUCT_CUSTOMIZER_PLUGIN_DIR : 'NOT DEFINED',
                'version' => defined('WC_PRODUCT_CUSTOMIZER_VERSION') ? WC_PRODUCT_CUSTOMIZER_VERSION : 'NOT DEFINED',
                'timestamp' => time()
            ),
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
     * Render wizard content
     */
    public function render_wizard_content() {
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
                </div>
            </div>

            <!-- Step 2: Choose Application Method -->
            <div class="wizard-step" id="step-2" data-step="2">
                <div class="step-header">
                    <h3><?php esc_html_e('2. Choose application method', 'wc-product-customizer'); ?></h3>
                </div>
                
                <div class="step-content">
                    <div class="method-selection" id="method-selection">
                        <!-- Methods will be loaded dynamically -->
                    </div>
                </div>
                
                <div class="wizard-footer">
                    <div class="method-status">
                        <span id="method-selected-text"></span>
                    </div>
                </div>
            </div>

            <!-- Step 3: Choose and Add Logo or Text -->
            <div class="wizard-step" id="step-3" data-step="3">
                <div class="step-header">
                    <h3><?php esc_html_e('3. Choose and add your logo or text', 'wc-product-customizer'); ?></h3>
                </div>
                
                <div class="step-content">
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
                    <!-- DEBUG: New upload UI v8 -->
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
                            
                            <div class="setup-cost" id="setup-cost">
                                <strong>£<span id="setup-fee-amount">8.95</span></strong> 
                                <?php esc_html_e('one-time new logo setup cost', 'wc-product-customizer'); ?>
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
                        </div>
                    </div>

                    <!-- Text Input Section -->
                    <div class="text-input-section" id="text-input-section" style="display: none;">
                        <div class="text-input-wrapper">
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
                    <!-- Navigation buttons removed for single-page approach -->
                </div>
            </div>

            <!-- Final Step: Review and Add to Cart -->
            <div class="wizard-step" id="step-final" data-step="final">
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
        
        // Get product's customization configuration
        $config = $db->get_product_customization_config($product_id);
        
        if ($config) {
            // Filter zones based on category configuration
            $allowed_zone_ids = maybe_unserialize($config->available_zones);
            if (is_array($allowed_zone_ids) && !empty($allowed_zone_ids)) {
                $zones = $db->get_zones_by_ids($allowed_zone_ids);
            } else {
                // Configuration exists but has no zones - return error
                wp_send_json_error(array(
                    'message' => __('No customization zones are available for this product. Please contact the store administrator.', 'wc-product-customizer'),
                    'code' => 'no_zones_configured'
                ));
            }
        } else {
            // No configuration found - return error
            wp_send_json_error(array(
                'message' => __('This product does not support customization. Please contact the store administrator.', 'wc-product-customizer'),
                'code' => 'no_configuration'
            ));
        }
        
        // Filter zones based on product compatibility if needed
        $available_zones = array();
        foreach ($zones as $zone) {
            // Ensure all fields are properly sanitized and never null
            $zone_name = $zone->name ?? 'Unnamed Zone';
            $zone_description = $zone->description ?? '';
            $zone_group = $zone->zone_group ?? '';
            $zone_methods = $zone->methods_available ?? '';
            $zone_charge = $zone->zone_charge ?? 0;
            $zone_thumbnail = $zone->thumbnail_url ?? '';
            
            $available_zones[] = array(
                'id' => intval($zone->id),
                'name' => sanitize_text_field($zone_name),
                'group' => sanitize_text_field($zone_group),
                'methods' => array_filter(explode(',', $zone_methods)),
                'charge' => floatval($zone_charge),
                'thumbnail_url' => esc_url_raw($zone_thumbnail),
                'description' => sanitize_textarea_field($zone_description)
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
        
        $product_id = intval($_POST['product_id'] ?? 0);
        
        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product ID', 'wc-product-customizer')));
        }
        
        $db = WC_Product_Customizer_Database::get_instance();
        
        // Get product's customization configuration
        $config = $db->get_product_customization_config($product_id);
        
        if ($config) {
            // Filter types based on category configuration
            $allowed_type_ids = maybe_unserialize($config->available_types);
            if (is_array($allowed_type_ids) && !empty($allowed_type_ids)) {
                $types = $db->get_types_by_ids($allowed_type_ids);
            } else {
                // Configuration exists but has no types - return error
                wp_send_json_error(array(
                    'message' => __('No customization methods are available for this product. Please contact the store administrator.', 'wc-product-customizer'),
                    'code' => 'no_types_configured'
                ));
            }
        } else {
            // No configuration found - return error
            wp_send_json_error(array(
                'message' => __('This product does not support customization. Please contact the store administrator.', 'wc-product-customizer'),
                'code' => 'no_configuration'
            ));
        }
        
        $methods = array();
        foreach ($types as $type) {
            $methods[] = array(
                'id' => $type->id,
                'name' => $type->name,
                'slug' => $type->slug,
                'description' => $type->description,
                'icon' => $type->icon,
                'text_setup_fee' => floatval($type->text_setup_fee),
                'logo_setup_fee' => floatval($type->logo_setup_fee),
                'setup_fee' => floatval($type->text_setup_fee) // For backward compatibility
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
        
        // Determine thumbnail URL - use custom thumbnail if available, otherwise fall back to default SVG
        $thumbnail_url = '';
        if (!empty($zone['thumbnail_url'])) {
            $thumbnail_url = $zone['thumbnail_url'];
        } else {
            $thumbnail_url = WC_PRODUCT_CUSTOMIZER_PLUGIN_URL . 'assets/images/zones/' . strtolower(str_replace(' ', '-', $zone['name'] ?? 'default')) . '.svg';
        }
        ?>
        <div class="zone-card" data-zone-id="<?php echo esc_attr($zone['id']); ?>" data-zone-name="<?php echo esc_attr($zone['name'] ?? ''); ?>" title="<?php echo esc_attr($zone['description'] ?? ''); ?>">
            <div class="zone-image">
                <img src="<?php echo esc_url($thumbnail_url); ?>" 
                     alt="<?php echo esc_attr($zone['name'] ?? 'Unnamed Zone'); ?>"
                     onerror="this.src='<?php echo esc_url(WC_PRODUCT_CUSTOMIZER_PLUGIN_URL . 'assets/images/zones/' . strtolower(str_replace(' ', '-', $zone['name'] ?? 'default')) . '.svg'); ?>'">
            </div>
            <h4><?php echo esc_html($zone['name'] ?? 'Unnamed Zone'); ?></h4>
            <?php if (!empty($zone['description'])): ?>
                <p class="zone-description"><?php echo esc_html($zone['description'] ?? ''); ?></p>
            <?php endif; ?>
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
        <div class="method-card" data-method="<?php echo esc_attr($method['slug']); ?>" data-type-id="<?php echo esc_attr($method['id']); ?>">
            <div class="method-image">
                <div class="method-icon" style="font-size: 48px; text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <?php echo esc_html($method['icon']); ?>
                </div>
            </div>
            <div class="method-info">
                <h4>
                    <?php echo esc_html($method['name']); ?>
                    <span class="checkmark" style="display: none;">✓</span>
                    <span class="info-icon"><?php echo esc_html($method['icon']); ?></span>
                </h4>
                <?php if (!empty($method['description'])): ?>
                    <p class="method-description"><?php echo esc_html($method['description']); ?></p>
                <?php endif; ?>
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
