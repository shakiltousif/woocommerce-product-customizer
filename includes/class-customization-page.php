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
class WC_Product_Customizer_Page
{

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
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        add_action('init', array($this, 'register_customization_page'));
        add_action('template_redirect', array($this, 'handle_customization_page_request'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_page_scripts'));
    }

    /**
     * Register customization page
     */
    public function register_customization_page()
    {
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
    public function handle_customization_page_request()
    {
        if (get_query_var('wc_customize') === '1') {
            $this->render_customization_page();
            exit;
        }
    }

    /**
     * Enqueue page scripts
     */
    public function enqueue_page_scripts()
    {
        if (get_query_var('wc_customize') === '1') {
            // Enqueue wizard styles
            wp_enqueue_style(
                'wc-customizer-wizard',
                WC_PRODUCT_CUSTOMIZER_PLUGIN_URL . 'assets/css/wizard.css?v=' . time() . '.v52',
                array(),
                WC_PRODUCT_CUSTOMIZER_VERSION . '.' . time() . '.v52' // Enhanced cache busting
            );

            // Enqueue wizard scripts
            wp_enqueue_script(
                'wc-customizer-wizard',
                WC_PRODUCT_CUSTOMIZER_PLUGIN_URL . 'assets/js/wizard.js?v=' . time() . '.v52',
                array('jquery'),
                WC_PRODUCT_CUSTOMIZER_VERSION . '.' . time() . '.v52', // Enhanced cache busting
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
    public function add_body_class($classes)
    {
        $classes[] = 'wc-customization-page';
        return $classes;
    }

    /**
     * Render customization page
     */
    public function render_customization_page()
    {
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
    private function render_wizard_content($existing_customization = null)
    {
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
                            <!-- Position tabs will be loaded via JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Steps 2 and 3 are now handled per-position in the tab system -->

                <!-- Final Step: Review and Save -->
                <div class="wizard-step final-step" data-step="4">
                    <div class="step-content">
                        <h4><?php esc_html_e('Review Your Customization', 'wc-product-customizer'); ?></h4>
                        <div class="customization-summary" id="customization-summary">
                            <!-- Summary will be populated by JavaScript -->
                        </div>
                        <div class="step-actions">
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
    public static function get_customization_url($product_id, $cart_key, $return_url = null)
    {
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
