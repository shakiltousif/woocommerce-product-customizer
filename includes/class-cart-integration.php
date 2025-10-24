<?php

/**
 * Cart integration class
 *
 * @package WooCommerce_Product_Customizer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cart Integration class
 */
class WC_Product_Customizer_Cart_Integration
{

    /**
     * Instance
     *
     * @var WC_Product_Customizer_Cart_Integration
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return WC_Product_Customizer_Cart_Integration
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
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // Add fallback JavaScript injection for theme compatibility
        add_action('wp_footer', array($this, 'add_cart_fallback_script'));

        // Cart hooks - Multiple hooks for theme compatibility
        add_action('woocommerce_after_cart_item_name', array($this, 'add_customization_button'), 10, 2);
        add_action('woocommerce_cart_item_meta_end', array($this, 'add_customization_button'), 10, 2);
        add_action('woocommerce_after_cart_item_quantity', array($this, 'add_customization_button'), 10, 2);
        add_action('woocommerce_cart_item_after_quantity', array($this, 'add_customization_button'), 10, 2);
        
        // Additional hooks for better theme compatibility
        add_action('woocommerce_cart_item_actions', array($this, 'add_customization_button'), 10, 2);
        add_action('woocommerce_after_cart_item_thumbnail', array($this, 'add_customization_button'), 10, 2);
        
        // Data hooks
        add_filter('woocommerce_add_cart_item_data', array($this, 'save_customization_to_cart'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_customization_in_cart'), 10, 2);
        add_action('woocommerce_cart_calculate_fees', array($this, 'add_customization_fees'));
        add_filter('woocommerce_cart_item_price', array($this, 'modify_cart_item_price'), 10, 3);
        
        // DISABLED: Template modification hooks that cause raw HTML issues
        add_filter('woocommerce_cart_item_name', array($this, 'modify_cart_item_name'), 10, 3);
        // add_action('woocommerce_cart_item_after_thumbnail', array($this, 'add_customization_button'), 10, 2);
        
        // DISABLED: More aggressive hooks that cause raw HTML issues
        // add_filter('woocommerce_cart_item_thumbnail', array($this, 'modify_cart_item_thumbnail'), 10, 3);
        // add_filter('woocommerce_cart_item_subtotal', array($this, 'modify_cart_item_subtotal'), 10, 3);
        
        // DISABLED: Mini-cart and widget hooks that cause raw HTML issues
        // add_filter('woocommerce_widget_cart_item_quantity', array($this, 'modify_widget_cart_item'), 10, 3);
        // add_filter('woocommerce_mini_cart_item_quantity', array($this, 'modify_widget_cart_item'), 10, 3);
        
        // DISABLED: Additional hooks that cause raw HTML issues
        // add_filter('woocommerce_cart_item_removed_title', array($this, 'modify_cart_item_name'), 10, 3);
        // add_filter('woocommerce_cart_item_removed_short_title', array($this, 'modify_cart_item_name'), 10, 3);
        
        // DISABLED: Global filter that causes raw HTML issues
        // add_filter('woocommerce_cart_item_name', array($this, 'modify_cart_item_name_global'), 5, 3);
        
        // DISABLED: Global text filter that causes raw HTML issues
        // add_filter('the_content', array($this, 'fix_escaped_html'), 999);
        // add_filter('woocommerce_cart_item_name', array($this, 'fix_escaped_html'), 999);
        
        // DISABLED: Direct output buffer modification that causes raw HTML issues
        // add_action('woocommerce_before_cart_table', array($this, 'start_cart_output_buffer'));
        // add_action('woocommerce_after_cart_table', array($this, 'end_cart_output_buffer'));

        // Checkout hooks
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_customization_to_order'), 10, 4);
        add_action('woocommerce_order_item_meta_end', array($this, 'display_customization_in_order'), 10, 3);
        add_action('woocommerce_order_item_name', array($this, 'display_customization_in_order_item'), 10, 3);
        
        // AJAX handlers
        add_action('wp_ajax_wc_customizer_add_to_cart', array($this, 'ajax_add_customization_to_cart'));
        add_action('wp_ajax_nopriv_wc_customizer_add_to_cart', array($this, 'ajax_add_customization_to_cart'));
        add_action('wp_ajax_wc_customizer_edit_customization', array($this, 'ajax_edit_customization'));
        add_action('wp_ajax_nopriv_wc_customizer_edit_customization', array($this, 'ajax_edit_customization'));
        
        // Email hooks
        add_action('woocommerce_order_item_meta_start', array($this, 'display_customization_in_emails'), 10, 3);

        // Migration hook
        add_action('woocommerce_cart_loaded_from_session', array($this, 'migrate_cart_file_paths'));

        // Admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Wizard modal is handled by the wizard class
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets()
    {
        // Load on cart, checkout, and order pages
        if (is_cart() || is_checkout() || is_wc_endpoint_url('order-received') || is_wc_endpoint_url('view-order') || is_account_page()) {
            // Enqueue CSS
            wp_enqueue_style(
                'wc-customizer-wizard',
                WC_PRODUCT_CUSTOMIZER_PLUGIN_URL . 'assets/css/wizard.css',
                array(),
                WC_PRODUCT_CUSTOMIZER_VERSION
            );

            // Enqueue JavaScript
            wp_enqueue_script(
                'wc-customizer-wizard',
                WC_PRODUCT_CUSTOMIZER_PLUGIN_URL . 'assets/js/wizard.js',
                array('jquery'),
                WC_PRODUCT_CUSTOMIZER_VERSION,
                true
            );

            // Localize script with AJAX data (standardized nonces)
            wp_localize_script('wc-customizer-wizard', 'wcCustomizerWizard', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wc_customizer_nonce'),
                'cartNonce' => wp_create_nonce('wc_customizer_cart'),
                'uploadNonce' => wp_create_nonce('wc_customizer_upload'),
                'pricingNonce' => wp_create_nonce('wc_customizer_pricing'),
                'pluginUrl' => WC_PRODUCT_CUSTOMIZER_PLUGIN_URL,
                'strings' => array(
                    'selectZones' => __('Please select at least one position', 'wc-product-customizer'),
                    'selectMethod' => __('Please select an application method', 'wc-product-customizer'),
                    'uploadFile' => __('Please upload a file', 'wc-product-customizer'),
                    'selected' => __('selected', 'wc-product-customizer'),
                    'error' => __('An error occurred. Please try again.', 'wc-product-customizer'),
                ),
                'settings' => array(
                    'maxFileSize' => 8388608, // 8MB
                    'allowedTypes' => array('jpg', 'jpeg', 'png', 'pdf', 'ai', 'eps')
                )
            ));
        }
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook)
    {
        // Only load on order edit pages
        if ($hook === 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) === 'shop_order') {
            wp_enqueue_style(
                'wc-customizer-wizard',
                WC_PRODUCT_CUSTOMIZER_PLUGIN_URL . 'assets/css/wizard.css',
                array(),
                WC_PRODUCT_CUSTOMIZER_VERSION
            );
        }
    }

    /**
     * Add customization button to cart items
     *
     * @param array $cart_item
     * @param string $cart_item_key
     */
    public function add_customization_button($cart_item, $cart_item_key)
    {
        $product_id = $cart_item['product_id'];
        
        // Check if product supports customization
        if (!$this->is_customization_enabled($product_id)) {
            return;
        }

        // Prevent duplicate buttons - check if already rendered for this cart item
        static $rendered_items = array();
        if (in_array($cart_item_key, $rendered_items)) {
            return;
        }
        $rendered_items[] = $cart_item_key;

        // Generate customization page URL
        $customize_url = $this->get_customization_url($product_id, $cart_item_key);

        echo '<div class="customization-actions" data-cart-key="' . esc_attr($cart_item_key) . '" data-product-id="' . esc_attr($product_id) . '">';
        
        // If already customized, show edit button and summary
        if (isset($cart_item['customization_data'])) {
            echo '<div class="customization-details">';
            echo '<div class="customization-details-header">';
            echo '<h3 class="customization-details-title">Customization Details</h3>';
            echo '</div>';
            
            $data = $cart_item['customization_data'];

            // Check if it's multiple positions (new format) or single customization (legacy)
            if (is_array($data) && isset($data[0]['zone_id'])) {
                // Multiple positions format
                foreach ($data as $index => $position) {
                    echo '<div class="position-customization">';
                    echo '<div class="customization-type">' . esc_html($position['zone_name'] . ' - ' . ucfirst($position['content_type']) . ' ' . ucfirst($position['method'])) . '</div>';

                    // Show logo preview if it's a logo customization
                    if ($position['content_type'] === 'logo' && !empty($position['file_path'])) {
                        echo '<div class="customization-logo-preview">';
                        echo '<strong>' . __('Logo Preview:', 'wc-product-customizer') . '</strong><br>';

                        // Get the file URL
                        $upload_dir = wp_upload_dir();
                        $file_url = $upload_dir['baseurl'] . '/customization-files/' . $position['file_path'];

                        // Check if it's an image file
                        $extension = strtolower(pathinfo($position['file_path'], PATHINFO_EXTENSION));
                        $image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp');

                        if (in_array($extension, $image_extensions)) {
                            echo '<img src="' . esc_url($file_url) . '" alt="' . esc_attr__('Customization Logo', 'wc-product-customizer') . '" style="max-width: 100px; max-height: 100px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">';
                        } else {
                            echo '<div style="background: #f0f0f0; padding: 10px; margin-top: 5px; border-radius: 4px; text-align: center; font-size: 12px;">';
                            echo esc_html(strtoupper($extension)) . ' ' . __('File', 'wc-product-customizer');
                            echo '</div>';
                        }
                        echo '</div>';
                    }

                    echo '</div>';
                }
            } else {
                // Legacy single customization format
            $method = ucfirst($data['method'] ?? '');
            $content_type = ucfirst($data['content_type'] ?? '');
            
            echo '<div class="customization-type">' . esc_html($content_type . ' ' . $method) . '</div>';
            
            if (!empty($data['zones'])) {
                echo '<div class="customization-zones">' . esc_html(implode(', ', $data['zones'] ?? array())) . '</div>';
            }

                // Show logo preview if it's a logo customization
                if (isset($data['content_type']) && $data['content_type'] === 'logo' && !empty($data['file_path'])) {
                    echo '<div class="customization-logo-preview">';
                    echo '<strong>' . __('Logo Preview:', 'wc-product-customizer') . '</strong><br>';

                    // Get the file URL
                    $upload_dir = wp_upload_dir();
                    $file_url = $upload_dir['baseurl'] . '/customization-files/' . $data['file_path'];

                    // Check if it's an image file
                    $extension = strtolower(pathinfo($data['file_path'], PATHINFO_EXTENSION));
                    $image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp');

                    if (in_array($extension, $image_extensions)) {
                        echo '<img src="' . esc_url($file_url) . '" alt="' . esc_attr__('Customization Logo', 'wc-product-customizer') . '" style="max-width: 100px; max-height: 100px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">';
                    } else {
                        echo '<div style="background: #f0f0f0; padding: 10px; margin-top: 5px; border-radius: 4px; text-align: center; font-size: 12px;">';
                        echo esc_html(strtoupper($extension)) . ' ' . __('File', 'wc-product-customizer');
                        echo '</div>';
                    }
                    echo '</div>';
            }
            
            if (!empty($data['total_cost'])) {
                echo '<div class="customization-cost">£' . esc_html(number_format($data['total_cost'], 2)) . '</div>';
                }
            }
            
            echo '</div>';
            echo '<div class="customization-actions">';
            echo '<a href="' . esc_url($this->get_customization_url($product_id, $cart_item_key, null, true)) . '" class="wc-customizer-edit-link">';
            echo esc_html__('Edit Customization', 'wc-product-customizer');
            echo '</a>';
            echo '<button type="button" class="wc-customizer-remove-link" data-cart-key="' . esc_attr($cart_item_key) . '">';
            echo esc_html__('Remove Customization', 'wc-product-customizer');
            echo '</button>';
            echo '</div>';
            echo '</div>';
            
            // Add another logo button
            echo '<div class="customization-actions">';
            echo '<a href="' . esc_url($customize_url) . '" class="wc-customizer-add-link">';
            echo esc_html__('Add Another Customization', 'wc-product-customizer');
            echo '</a>';
            echo '</div>';
        } else {
            // Show add customization button
            echo '<div class="customization-actions">';
            echo '<a href="' . esc_url($customize_url) . '" class="wc-customizer-add-link">';
            echo esc_html__('Add Customization', 'wc-product-customizer');
            echo '</a>';
            echo '</div>';
        }
        
        echo '</div>';
    }

    /**
     * Check if customization is enabled for product
     *
     * @param int $product_id
     * @return bool
     */
    private function is_customization_enabled($product_id)
    {
        // Check if customization is enabled
        $enabled = get_post_meta($product_id, '_customization_enabled', true);
        if ($enabled !== 'yes' && $enabled !== '1' && $enabled != 1) {
            return false;
        }

        // Check if product has a valid category configuration
        $db = WC_Product_Customizer_Database::get_instance();
        $config = $db->get_product_customization_config($product_id);

        if (!$config || !$config->enabled) {
            return false;
        }

        // Check if config has at least one zone and one type
        $zones = maybe_unserialize($config->available_zones);
        $types = maybe_unserialize($config->available_types);

        if (empty($zones) || !is_array($zones) || empty($types) || !is_array($types)) {
            return false;
        }

        return true;
    }

    /**
     * Get customization URL
     *
     * @param int $product_id
     * @param string $cart_key
     * @param string $return_url
     * @param bool $is_edit
     * @return string
     */
    private function get_customization_url($product_id, $cart_key, $return_url = null, $is_edit = false)
    {
        if (!$return_url) {
            $return_url = wc_get_cart_url();
        }

        $args = array(
            'wc_customize' => '1',
            'product_id' => $product_id,
            'cart_key' => $cart_key,
            'return_url' => urlencode($return_url)
        );

        if ($is_edit) {
            $args['edit'] = '1';
        }

        return add_query_arg($args, home_url('/'));
    }

    /**
     * Save customization data to cart
     *
     * @param array $cart_item_data
     * @param int $product_id
     * @param int $variation_id
     * @return array
     */
    public function save_customization_to_cart($cart_item_data, $product_id, $variation_id)
    {
        if (isset($_POST['customization_data'])) {
            $customization_data = $this->sanitize_customization_data($_POST['customization_data']);
            
            if (!empty($customization_data)) {
                $cart_item_data['customization_data'] = $customization_data;
                
                // Calculate fees
                $pricing = WC_Product_Customizer_Pricing::get_instance();
                $fees = $pricing->calculate_customization_fees($customization_data, 1); // Quantity will be updated later
                $cart_item_data['customization_fees'] = $fees;
                
                // Make cart item unique
                $cart_item_data['unique_key'] = md5(microtime() . rand());
            }
        }
        
        return $cart_item_data;
    }

    /**
     * Sanitize customization data
     *
     * @param array $data
     * @return array
     */
    private function sanitize_customization_data($data)
    {
        $sanitized = array();
        
        if (isset($data['zones']) && is_array($data['zones'])) {
            $sanitized['zones'] = array_map('sanitize_text_field', $data['zones']);
        }
        
        if (isset($data['method'])) {
            $sanitized['method'] = sanitize_text_field($data['method']);
        }
        
        if (isset($data['content_type'])) {
            $sanitized['content_type'] = sanitize_text_field($data['content_type']);
        }
        
        if (isset($data['file_path'])) {
            // Store just the filename for URL generation
            $file_path = sanitize_text_field($data['file_path']);
            $sanitized['file_path'] = basename($file_path);
        }
        
        // Legacy text content (for backward compatibility)
        if (isset($data['text_content'])) {
            $sanitized['text_content'] = sanitize_textarea_field($data['text_content']);
        }

        // New text configuration fields
        if (isset($data['text_line_1'])) {
            $sanitized['text_line_1'] = sanitize_text_field($data['text_line_1']);
        }

        if (isset($data['text_line_2'])) {
            $sanitized['text_line_2'] = sanitize_text_field($data['text_line_2']);
        }

        if (isset($data['text_line_3'])) {
            $sanitized['text_line_3'] = sanitize_text_field($data['text_line_3']);
        }

        if (isset($data['text_font'])) {
            $sanitized['text_font'] = sanitize_text_field($data['text_font']);
        }

        if (isset($data['text_color'])) {
            $sanitized['text_color'] = sanitize_text_field($data['text_color']);
        }

        if (isset($data['text_notes'])) {
            $sanitized['text_notes'] = sanitize_textarea_field($data['text_notes']);
        }

        // Logo alternative options
        if (isset($data['logo_alternative'])) {
            $sanitized['logo_alternative'] = sanitize_text_field($data['logo_alternative']);
        }

        if (isset($data['logo_notes'])) {
            $sanitized['logo_notes'] = sanitize_textarea_field($data['logo_notes']);
        }
        
        if (isset($data['setup_fee'])) {
            $sanitized['setup_fee'] = floatval($data['setup_fee']);
        }
        
        if (isset($data['application_fee'])) {
            $sanitized['application_fee'] = floatval($data['application_fee']);
        }
        
        if (isset($data['total_cost'])) {
            $sanitized['total_cost'] = floatval($data['total_cost']);
        }
        
        return $sanitized;
    }

    /**
     * Display customization data in cart
     *
     * @param array $item_data
     * @param array $cart_item
     * @return array
     */
    public function display_customization_in_cart($item_data, $cart_item)
    {
        if (isset($cart_item['customization_data'])) {
            $data = $cart_item['customization_data'];
            
            $item_data[] = array(
                'key' => __('Customization', 'wc-product-customizer'),
                'value' => $this->format_customization_display($data),
                'display' => ''
            );
            
            // Add logo preview if file exists
            if (!empty($data['file_path']) && is_string($data['file_path'])) {
                // Handle migration from old path to new path
                $file_path = $this->migrate_file_path($data['file_path']);
                
                if (!empty($file_path) && file_exists($file_path)) {
                    $file_url = $this->get_file_url_from_path($file_path);
                    if (!empty($file_url)) {
                        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                        $image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp');
                        
                        if (in_array($file_extension, $image_extensions)) {
                            $item_data[] = array(
                                'key' => __('Logo Preview', 'wc-product-customizer'),
                                'value' => '<div class="cart-logo-preview"><img src="' . esc_url($file_url) . '" alt="Uploaded logo" class="cart-logo-image"></div>',
                'display' => ''
            );
                        }
                    }
                }
            }
        }
        
        return $item_data;
    }

    /**
     * Migrate existing cart items to use new file paths
     */
    public function migrate_cart_file_paths()
    {
        $cart = WC()->cart->get_cart();
        $updated = false;
        
        foreach ($cart as $cart_item_key => $cart_item) {
            if (isset($cart_item['customization_data']['file_path'])) {
                $old_path = $cart_item['customization_data']['file_path'];
                $new_path = $this->migrate_file_path($old_path);
                
                if ($new_path !== $old_path) {
                    $cart[$cart_item_key]['customization_data']['file_path'] = $new_path;
                    $updated = true;
                }
            }
        }
        
        if ($updated) {
            WC()->cart->set_cart_contents($cart);
            WC()->cart->calculate_totals();
        }
    }

    /**
     * Migrate file path from old location to new location
     *
     * @param string $file_path
     * @return string
     */
    private function migrate_file_path($file_path)
    {
        // Validate input
        if (empty($file_path) || !is_string($file_path)) {
            return '';
        }
        
        // If file is in old location, check if it exists in new location
        if (strpos($file_path, '/wp-content/customization-files/') !== false) {
            $filename = basename($file_path);
            $new_path = WP_CONTENT_DIR . '/uploads/customization-files/' . $filename;
            
            // If file exists in new location, return new path
            if (file_exists($new_path)) {
                return $new_path;
            }
        }
        
        // Return original path if no migration needed or file doesn't exist in new location
        return $file_path;
    }

    /**
     * Convert file path to URL
     *
     * @param string $file_path
     * @return string
     */
    private function get_file_url_from_path($file_path)
    {
        // Validate input
        if (empty($file_path) || !is_string($file_path)) {
            return '';
        }
        
        $upload_dir = wp_upload_dir();
        
        // Normalize slashes and clean up double slashes
        $normalized_file_path = str_replace('\\', '/', $file_path);
        $normalized_file_path = preg_replace('#/+#', '/', $normalized_file_path); // Remove multiple slashes
        
        $normalized_basedir = str_replace('\\', '/', $upload_dir['basedir']);
        $normalized_basedir = preg_replace('#/+#', '/', $normalized_basedir); // Remove multiple slashes
        
        // Use a more robust approach to extract the relative path
        if (strpos($normalized_file_path, $normalized_basedir) === 0) {
            // File is within uploads directory, extract relative path
            $relative_path = substr($normalized_file_path, strlen($normalized_basedir));
            $file_url = $upload_dir['baseurl'] . $relative_path;
        } else {
            // Fallback: try direct replacement
            $file_url = str_replace($normalized_basedir, $upload_dir['baseurl'], $normalized_file_path);
        }
        
        return $file_url;
    }

    /**
     * Format customization data for display
     *
     * @param array $data
     * @return string
     */
    private function format_customization_display($data)
    {
        $display = '';
        
        if (!empty($data['method']) && isset($data['content_type']) && !empty($data['content_type'])) {
            $display .= ucfirst($data['content_type']) . ' ' . ucfirst($data['method']);
        }
        
        if (!empty($data['zones'])) {
            $display .= ' - ' . implode(', ', $data['zones']);
        }
        
        // Display text content based on content type
        if (isset($data['content_type']) && $data['content_type'] === 'text') {
            // New text configuration
            $text_lines = array();
            if (!empty($data['text_line_1'])) {
                $text_lines[] = $data['text_line_1'];
            }
            if (!empty($data['text_line_2'])) {
                $text_lines[] = $data['text_line_2'];
            }
            if (!empty($data['text_line_3'])) {
                $text_lines[] = $data['text_line_3'];
            }

            if (!empty($text_lines)) {
                $display .= ' - "' . implode(' ', $text_lines) . '"';

                // Add font and color info
                $text_details = array();
                if (!empty($data['text_font'])) {
                    $text_details[] = ucfirst($data['text_font']) . ' font';
                }
                if (!empty($data['text_color'])) {
                    $text_details[] = ucfirst($data['text_color']) . ' color';
                }

                if (!empty($text_details)) {
                    $display .= ' (' . implode(', ', $text_details) . ')';
                }
            }

            // Add text notes if available
            if (!empty($data['text_notes'])) {
                $display .= ' - Notes: ' . esc_html($data['text_notes']);
            }

            // Legacy text content (for backward compatibility)
            if (empty($text_lines) && !empty($data['text_content'])) {
                $display .= ' - "' . $data['text_content'] . '"';
            }
        } else {
            // Logo content
        if (!empty($data['file_path'])) {
            $display .= ' - ' . __('Logo uploaded', 'wc-product-customizer');
            } elseif (!empty($data['logo_alternative'])) {
                if (isset($data['logo_alternative']) && $data['logo_alternative'] === 'contact_later') {
                    $display .= ' - ' . __('Logo to be provided later', 'wc-product-customizer');
                } elseif (isset($data['logo_alternative']) && $data['logo_alternative'] === 'already_have') {
                    $display .= ' - ' . __('Logo already on file', 'wc-product-customizer');
                }
            }

            // Add logo notes if available
            if (!empty($data['logo_notes'])) {
                $display .= ' - Notes: ' . esc_html($data['logo_notes']);
            }
        }

        return $display;
    }

    /**
     * Format position customization display
     *
     * @param array $position
     * @return string
     */
    private function format_position_display($position)
    {
        $display = '';

        if (!empty($position['method']) && !empty($position['content_type'])) {
            $display .= ucfirst($position['content_type']) . ' ' . ucfirst($position['method']);
        }

        // Display text content based on content type
        if ($position['content_type'] === 'text') {
            // New text configuration
            $text_lines = array();
            if (!empty($position['text_line_1'])) {
                $text_lines[] = $position['text_line_1'];
            }
            if (!empty($position['text_line_2'])) {
                $text_lines[] = $position['text_line_2'];
            }
            if (!empty($position['text_line_3'])) {
                $text_lines[] = $position['text_line_3'];
            }

            if (!empty($text_lines)) {
                $display .= ' - "' . implode(' ', $text_lines) . '"';

                // Add font and color info
                $text_details = array();
                if (!empty($position['text_font'])) {
                    $text_details[] = ucfirst($position['text_font']) . ' font';
                }
                if (!empty($position['text_color'])) {
                    $text_details[] = ucfirst($position['text_color']) . ' color';
                }

                if (!empty($text_details)) {
                    $display .= ' (' . implode(', ', $text_details) . ')';
                }
            }

            // Add text notes if available
            if (!empty($position['text_notes'])) {
                $display .= ' - Notes: ' . esc_html($position['text_notes']);
            }

            // Legacy text content (for backward compatibility)
            if (empty($text_lines) && !empty($position['text_content'])) {
                $display .= ' - "' . $position['text_content'] . '"';
            }
        } else {
            // Logo content
            if (!empty($position['file_path'])) {
                $display .= ' - ' . __('Logo uploaded', 'wc-product-customizer');
            } elseif (!empty($position['logo_alternative'])) {
                if ($position['logo_alternative'] === 'contact_later') {
                    $display .= ' - ' . __('Logo to be provided later', 'wc-product-customizer');
                } elseif ($position['logo_alternative'] === 'already_have') {
                    $display .= ' - ' . __('Logo already on file', 'wc-product-customizer');
                }
            }

            // Add logo notes if available
            if (!empty($position['logo_notes'])) {
                $display .= ' - Notes: ' . esc_html($position['logo_notes']);
            }
        }
        
        return $display;
    }

    /**
     * Add customization fees to cart
     */
    public function add_customization_fees()
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['customization_data'])) {
                $data = $cart_item['customization_data'];
                $quantity = $cart_item['quantity'];
                
                // Recalculate fees based on current quantity
                $pricing = WC_Product_Customizer_Pricing::get_instance();
                $fees = $pricing->calculate_customization_fees($data, $quantity);
                
                // Add setup fee (one-time)
                if (!empty($fees['setup_fee'])) {
                    WC()->cart->add_fee(
                        __('One Time Setup Fees', 'wc-product-customizer'),
                        $fees['setup_fee']
                    );
                }
                
                // Add application fee (per item)
                if (!empty($fees['application_fee'])) {
                    $total_application_fee = $fees['application_fee'] * $quantity;
                    WC()->cart->add_fee(
                        sprintf(__('Costs To Add Logo (%dx)', 'wc-product-customizer'), $quantity),
                        $total_application_fee
                    );
                }
            }
        }
    }

    /**
     * Modify cart item price display
     *
     * @param string $price
     * @param array $cart_item
     * @param string $cart_item_key
     * @return string
     */
    public function modify_cart_item_price($price, $cart_item, $cart_item_key)
    {
        // Keep original price, fees are added separately
        return $price;
    }

    /**
     * Save customization data to order
     *
     * @param WC_Order_Item_Product $item
     * @param string $cart_item_key
     * @param array $values
     * @param WC_Order $order
     */
    public function save_customization_to_order($item, $cart_item_key, $values, $order)
    {
        if (isset($values['customization_data'])) {
            $data = $values['customization_data'];
            
            // Check if it's multiple positions (new format) or single customization (legacy)
            if (is_array($data) && isset($data[0]['zone_id'])) {
                // Multiple positions format
                $this->save_multiple_position_customization($item, $cart_item_key, $values, $order, $data);
            } else {
                // Legacy single customization format
                $this->save_single_customization($item, $cart_item_key, $values, $order, $data);
            }
        }
    }

    private function save_multiple_position_customization($item, $cart_item_key, $values, $order, $position_customizations)
    {
        // Calculate pricing
        $pricing = $this->calculate_multiple_position_pricing($position_customizations, $values['quantity']);

        // Save group data
        $db = WC_Product_Customizer_Database::get_instance();
        $group_id = $db->save_customization_order_group(array(
            'order_id' => $order->get_id(),
            'cart_item_key' => $cart_item_key,
            'product_id' => $values['product_id'],
            'group_setup_fee' => $pricing['setup_fee'],
            'total_application_fee' => $pricing['application_fee'],
            'total_cost' => $pricing['total']
        ));

        // Save each position separately
        foreach ($position_customizations as $position) {
            // Save as order item meta
            $item->add_meta_data('_customization_data', $position);
            $item->add_meta_data('_customization_display', $this->format_position_display($position));

            // Save to custom table for reporting
            $db->save_customization_order(array(
                'order_id' => $order->get_id(),
                'cart_item_key' => $cart_item_key,
                'product_id' => $values['product_id'],
                'zone_id' => $position['zone_id'],
                'position_name' => $position['zone_name'],
                'method' => $position['method'],
                'content_type' => $position['content_type'],
                'file_path' => $position['file_path'],
                'text_content' => $position['text_content'],
                'text_line_1' => $position['text_line_1'],
                'text_line_2' => $position['text_line_2'],
                'text_line_3' => $position['text_line_3'],
                'text_font' => $position['text_font'],
                'text_color' => $position['text_color'],
                'text_notes' => $position['text_notes'],
                'logo_alternative' => $position['logo_alternative'],
                'logo_notes' => $position['logo_notes'],
                'application_fee' => $position['application_fee']
            ));
        }
    }

    private function save_single_customization($item, $cart_item_key, $values, $order, $data)
    {
        // Legacy single customization processing
            $item->add_meta_data('_customization_data', $data);
            $item->add_meta_data('_customization_display', $this->format_customization_display($data));
            
            // Save to custom table for reporting
            $db = WC_Product_Customizer_Database::get_instance();
            $db->save_customization_order(array(
                'order_id' => $order->get_id(),
                'cart_item_key' => $cart_item_key,
                'product_id' => $values['product_id'],
            'zone_id' => 0, // Legacy format doesn't have zone_id
            'position_name' => implode(', ', $data['zones'] ?? array()),
                'method' => $data['method'] ?? '',
                'content_type' => $data['content_type'] ?? '',
                'file_path' => $data['file_path'] ?? '',
                'text_content' => $data['text_content'] ?? '',
            'text_line_1' => $data['text_line_1'] ?? '',
            'text_line_2' => $data['text_line_2'] ?? '',
            'text_line_3' => $data['text_line_3'] ?? '',
            'text_font' => $data['text_font'] ?? '',
            'text_color' => $data['text_color'] ?? '',
            'text_notes' => $data['text_notes'] ?? '',
            'logo_alternative' => $data['logo_alternative'] ?? '',
            'logo_notes' => $data['logo_notes'] ?? '',
            'application_fee' => $data['application_fee'] ?? 0
        ));
    }

    /**
     * Display customization in order details
     *
     * @param int $item_id
     * @param WC_Order_Item $item
     * @param WC_Order $order
     */
    public function display_customization_in_order($item_id, $item, $order)
    {
        $customization_data = $item->get_meta('_customization_data');
        
        if ($customization_data) {
            echo '<div class="customization-order-details" style="margin: 10px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #007cba;">';
            echo '<strong style="color: #007cba; font-size: 14px;">' . esc_html__('Customization Details:', 'wc-product-customizer') . '</strong><br>';
            echo '<div style="margin: 8px 0; line-height: 1.5;">';
            echo '<strong>' . esc_html__('Positions:', 'wc-product-customizer') . '</strong> ' . esc_html(implode(', ', $customization_data['zones'] ?? array())) . '<br>';
            echo '<strong>' . esc_html__('Method:', 'wc-product-customizer') . '</strong> ' . esc_html(ucfirst($customization_data['method'] ?? '')) . '<br>';
            echo '<strong>' . esc_html__('Content Type:', 'wc-product-customizer') . '</strong> ' . esc_html(ucfirst($customization_data['content_type'] ?? '')) . '<br>';
            echo '</div>';
            
            // Add logo preview if file exists
            if (!empty($customization_data['file_path']) && is_string($customization_data['file_path'])) {
                // Handle migration from old path to new path
                $file_path = $this->migrate_file_path($customization_data['file_path']);
                
                if (!empty($file_path) && file_exists($file_path)) {
                    $file_url = $this->get_file_url_from_path($file_path);
                    if (!empty($file_url)) {
                        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                        $image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp');
                        
                        if (in_array($file_extension, $image_extensions)) {
                            echo '<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6;">';
                            echo '<strong style="color: #007cba;">' . esc_html__('Logo Preview:', 'wc-product-customizer') . '</strong><br>';
                            echo '<div class="order-logo-preview" style="margin-top: 10px; text-align: center;">';
                            echo '<img src="' . esc_url($file_url) . '" alt="Uploaded logo" class="order-logo-image" style="max-width: 200px; max-height: 200px; border: 3px solid #007cba; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.15); background: white; padding: 5px;">';
                            echo '</div>';
                            echo '</div>';
                        }
                    }
                }
            }
            
            echo '</div>';
        }
    }

    /**
     * Display customization in order item name area
     *
     * @param string $item_name
     * @param WC_Order_Item_Product $item
     * @param bool $is_visible
     */
    public function display_customization_in_order_item($item_name, $item, $is_visible)
    {
        $customization_data = $item->get_meta('_customization_data');
        
        if ($customization_data && $is_visible) {
            echo '<div class="customization-order-item-details" style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #007cba;">';
            echo '<strong style="color: #007cba;">' . esc_html__('Customization Details:', 'wc-product-customizer') . '</strong><br>';
            echo '<div style="margin: 5px 0;">';
            echo '<strong>' . esc_html__('Positions:', 'wc-product-customizer') . '</strong> ' . esc_html(implode(', ', $customization_data['zones'] ?? array())) . '<br>';
            echo '<strong>' . esc_html__('Method:', 'wc-product-customizer') . '</strong> ' . esc_html(ucfirst($customization_data['method'] ?? '')) . '<br>';
            echo '<strong>' . esc_html__('Content Type:', 'wc-product-customizer') . '</strong> ' . esc_html(ucfirst($customization_data['content_type'] ?? '')) . '<br>';
            echo '</div>';
            
            // Add logo preview if file exists
            if (!empty($customization_data['file_path']) && is_string($customization_data['file_path'])) {
                $file_path = $this->migrate_file_path($customization_data['file_path']);
                
                if (!empty($file_path) && file_exists($file_path)) {
                    $file_url = $this->get_file_url_from_path($file_path);
                    if (!empty($file_url)) {
                        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                        $image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp');
                        
                        if (in_array($file_extension, $image_extensions)) {
                            echo '<div style="margin-top: 10px;">';
                            echo '<strong>' . esc_html__('Logo Preview:', 'wc-product-customizer') . '</strong><br>';
                            echo '<div class="order-logo-preview" style="margin-top: 5px;">';
                            echo '<img src="' . esc_url($file_url) . '" alt="Uploaded logo" class="order-logo-image" style="max-width: 150px; max-height: 150px; border: 2px solid #007cba; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
                            echo '</div>';
                            echo '</div>';
                        }
                    }
                }
            }
            echo '</div>';
        }
    }

    /**
     * Display customization in emails
     *
     * @param int $item_id
     * @param WC_Order_Item $item
     * @param WC_Order $order
     */
    public function display_customization_in_emails($item_id, $item, $order)
    {
        $customization_display = $item->get_meta('_customization_display');
        
        if ($customization_display) {
            echo '<div style="margin: 5px 0; font-size: 12px; color: #666;">';
            echo '<strong>' . esc_html__('Customization:', 'wc-product-customizer') . '</strong> ';
            echo esc_html($customization_display);
            echo '</div>';
        }
    }

    /**
     * AJAX handler for adding customization to cart
     */
    public function ajax_add_customization_to_cart()
    {
        check_ajax_referer('wc_customizer_cart', 'nonce');
        
        $cart_item_key = sanitize_text_field($_POST['cart_item_key']);

        // Check if we have position customizations (new format) or single customization (legacy)
        if (isset($_POST['position_customizations']) && is_array($_POST['position_customizations'])) {
            $position_customizations = $this->sanitize_position_customizations($_POST['position_customizations']);
            $this->process_multiple_position_customizations($cart_item_key, $position_customizations);
        } else {
            // Legacy single customization format
        $customization_data = $this->sanitize_customization_data($_POST['customization_data']);
            $this->process_single_customization($cart_item_key, $customization_data);
        }

        wp_send_json_success(array(
            'message' => __('Customization added successfully', 'wc-product-customizer'),
            'cart_hash' => WC()->cart->get_cart_hash()
        ));
    }

    private function sanitize_position_customizations($customizations)
    {
        $sanitized = array();

        foreach ($customizations as $customization) {
            $sanitized[] = array(
                'zone_id' => intval($customization['zone_id']),
                'zone_name' => sanitize_text_field($customization['zone_name']),
                'method' => sanitize_text_field($customization['method']),
                'content_type' => sanitize_text_field($customization['content_type']),
                'file_path' => isset($customization['file_path']) ? basename(sanitize_text_field($customization['file_path'])) : '',
                'text_content' => isset($customization['text_content']) ? sanitize_textarea_field($customization['text_content']) : '',
                'text_line_1' => isset($customization['text_line_1']) ? sanitize_text_field($customization['text_line_1']) : '',
                'text_line_2' => isset($customization['text_line_2']) ? sanitize_text_field($customization['text_line_2']) : '',
                'text_line_3' => isset($customization['text_line_3']) ? sanitize_text_field($customization['text_line_3']) : '',
                'text_font' => isset($customization['text_font']) ? sanitize_text_field($customization['text_font']) : '',
                'text_color' => isset($customization['text_color']) ? sanitize_text_field($customization['text_color']) : '',
                'text_notes' => isset($customization['text_notes']) ? sanitize_textarea_field($customization['text_notes']) : '',
                'logo_alternative' => isset($customization['logo_alternative']) ? sanitize_text_field($customization['logo_alternative']) : '',
                'logo_notes' => isset($customization['logo_notes']) ? sanitize_textarea_field($customization['logo_notes']) : '',
                'application_fee' => isset($customization['application_fee']) ? floatval($customization['application_fee']) : 0
            );
        }

        return $sanitized;
    }

    private function process_multiple_position_customizations($cart_item_key, $position_customizations)
    {
        // Update cart item with all position customizations
        $cart = WC()->cart->get_cart();
        
        if (isset($cart[$cart_item_key])) {
            $cart_item = $cart[$cart_item_key];

            // Store all position customizations in cart item meta
            $cart_item['customization_data'] = $position_customizations;

            // Calculate pricing
            $pricing = $this->calculate_multiple_position_pricing($position_customizations, $cart_item['quantity']);

            // Update cart item
            WC()->cart->cart_contents[$cart_item_key] = $cart_item;

            // Add fees
            $this->add_position_customization_fees($pricing);

            // Save session
            WC()->cart->set_session();
        }
    }

    private function process_single_customization($cart_item_key, $customization_data)
    {
        // Legacy single customization processing
        $cart = WC()->cart->get_cart();

        if (isset($cart[$cart_item_key])) {
            $cart_item = $cart[$cart_item_key];
            $cart_item['customization_data'] = $customization_data;

            // Calculate pricing
            $pricing_engine = WC_Product_Customizer_Pricing::get_instance();
            $pricing = $pricing_engine->calculate_customization_fees($customization_data, $cart_item['quantity']);

            // Update cart item
            WC()->cart->cart_contents[$cart_item_key] = $cart_item;

            // Add fees
            $this->add_position_customization_fees($pricing);

            // Save session
            WC()->cart->set_session();
        }
    }

    private function calculate_multiple_position_pricing($position_customizations, $quantity)
    {
        $pricing_engine = WC_Product_Customizer_Pricing::get_instance();

        // Get setup fee from first position (all positions share one setup fee)
        $first_position = $position_customizations[0];
        $setup_fee = $pricing_engine->get_setup_fee($first_position['method'], $first_position['content_type']);

        // Calculate total application fee (sum of all positions)
        $total_application_fee = 0;
        foreach ($position_customizations as $position) {
            $app_fee = $pricing_engine->get_application_cost($position['method'], $quantity);
            $total_application_fee += $app_fee;
        }

        $total_cost = $setup_fee + $total_application_fee;

        return array(
            'setup_fee' => $setup_fee,
            'application_fee' => $total_application_fee,
            'total' => $total_cost
        );
    }

    private function add_position_customization_fees($pricing)
    {
        if ($pricing['setup_fee'] > 0) {
            WC()->cart->add_fee(
                __('Customization Setup Fee', 'wc-product-customizer'),
                $pricing['setup_fee']
            );
        }

        if ($pricing['application_fee'] > 0) {
            WC()->cart->add_fee(
                __('Customization Application Fee', 'wc-product-customizer'),
                $pricing['application_fee']
            );
        }
    }

    /**
     * AJAX handler for editing customization
     */
    public function ajax_edit_customization()
    {
        check_ajax_referer('wc_customizer_cart', 'nonce');
        
        $cart_item_key = sanitize_text_field($_POST['cart_item_key']);
        
        // Get current customization data
        $cart = WC()->cart->get_cart();
        
        if (isset($cart[$cart_item_key]['customization_data'])) {
            wp_send_json_success(array(
                'customization_data' => $cart[$cart_item_key]['customization_data']
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('No customization data found', 'wc-product-customizer')
            ));
        }
    }

    /**
     * Modify cart item name to include customization button
     *
     * @param string $product_name
     * @param array $cart_item
     * @param string $cart_item_key
     * @return string
     */
    public function modify_cart_item_name($product_name, $cart_item, $cart_item_key)
    {
        $product_id = $cart_item['product_id'];

        
        // Check if product supports customization
        if (!$this->is_customization_enabled($product_id)) {
            return $product_name;
        }
        
        // Prevent duplicate buttons
        static $modified_items = array();
        if (in_array($cart_item_key, $modified_items)) {
            return $product_name;
        }
        $modified_items[] = $cart_item_key;
        
        // Use output buffering to properly render HTML
        ob_start();
        ?>
        <div class="customization-actions" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" data-product-id="<?php echo esc_attr($product_id); ?>" style="margin-top: 10px; padding: 10px 0; border-top: 1px solid #f0f0f0;">
            <?php if (isset($cart_item['customization_data'])): ?>
                <div style="margin-bottom: 8px; font-size: 12px; color: #666; font-weight: 500;">Customization Details:</div>
                <?php
                $data = $cart_item['customization_data'];

                // Check if it's multiple positions (new format) or single customization (legacy)
                if (is_array($data) && isset($data[0]['zone_id'])) {
                    // Multiple positions format
                    foreach ($data as $index => $position) {
                        echo '<div class="position-customization" style="margin-bottom: 8px; padding: 8px; background: #f8f9fa; border-radius: 4px;">';
                        echo '<div class="customization-type" style="font-weight: 600; color: #333;">' . esc_html($position['zone_name'] . ' - ' . ucfirst($position['content_type']) . ' ' . ucfirst($position['method'])) . '</div>';

                        // Show logo preview if it's a logo customization
                        if (isset($position['content_type']) && $position['content_type'] === 'logo' && !empty($position['file_path'])) {
                            echo '<div class="customization-logo-preview" style="margin-top: 5px;">';
                            echo '<strong style="font-size: 11px;">' . __('Logo Preview:', 'wc-product-customizer') . '</strong><br>';

                            // Get the file URL
                            $upload_dir = wp_upload_dir();
                            $file_url = $upload_dir['baseurl'] . '/customization-files/' . $position['file_path'];

                            // Check if it's an image file
                            $extension = strtolower(pathinfo($position['file_path'], PATHINFO_EXTENSION));
                            $image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp');

                            if (in_array($extension, $image_extensions)) {
                                echo '<img src="' . esc_url($file_url) . '" alt="' . esc_attr__('Customization Logo', 'wc-product-customizer') . '" style="max-width: 60px; max-height: 60px; margin-top: 3px; border: 1px solid #ddd; border-radius: 3px;">';
                            } else {
                                echo '<div style="background: #f0f0f0; padding: 5px; margin-top: 3px; border-radius: 3px; text-align: center; font-size: 10px;">';
                                echo esc_html(strtoupper($extension)) . ' ' . __('File', 'wc-product-customizer');
                                echo '</div>';
                            }
                            echo '</div>';

                            // Add logo notes if available
                            if (!empty($position['logo_notes'])) {
                                echo '<div class="customization-notes" style="margin-top: 3px; font-size: 10px; color: #888; font-style: italic;">';
                                echo 'Notes: ' . esc_html($position['logo_notes']);
                                echo '</div>';
                            }
                        } elseif (isset($position['content_type']) && $position['content_type'] === 'logo' && !empty($position['logo_alternative'])) {
                            // Show logo alternative when no file is uploaded
                            echo '<div class="customization-logo-alternative" style="margin-top: 5px; font-size: 11px; color: #666;">';
                            echo '<strong>' . __('Logo Option:', 'wc-product-customizer') . '</strong> ';
                            
                            if ($position['logo_alternative'] === 'contact_later') {
                                echo __('Logo to be provided later', 'wc-product-customizer');
                            } elseif ($position['logo_alternative'] === 'already_have') {
                                echo __('Logo already on file', 'wc-product-customizer');
                            } else {
                                echo esc_html(ucfirst(str_replace('_', ' ', $position['logo_alternative'])));
                            }
                            echo '</div>';
                            
                            // Add logo notes if available
                            if (!empty($position['logo_notes'])) {
                                echo '<div class="customization-notes" style="margin-top: 3px; font-size: 10px; color: #888; font-style: italic;">';
                                echo 'Notes: ' . esc_html($position['logo_notes']);
                                echo '</div>';
                            }
                        } elseif (isset($position['content_type']) && $position['content_type'] === 'text') {
                            // Show text content
                            $text_lines = array();
                            if (!empty($position['text_line_1'])) {
                                $text_lines[] = $position['text_line_1'];
                            }
                            if (!empty($position['text_line_2'])) {
                                $text_lines[] = $position['text_line_2'];
                            }
                            if (!empty($position['text_line_3'])) {
                                $text_lines[] = $position['text_line_3'];
                            }

                            if (!empty($text_lines)) {
                                echo '<div class="customization-text" style="margin-top: 5px; font-size: 11px; color: #666;">';
                                echo '<strong>' . __('Text:', 'wc-product-customizer') . '</strong><br>';
                                
                                // Show individual text lines
                                $line_number = 1;
                                foreach ($text_lines as $line) {
                                    if (!empty($line)) {
                                        echo '<div style="margin-left: 10px; margin-top: 2px;">';
                                        echo '<strong>Text Line ' . $line_number . ':</strong> "' . esc_html($line) . '"';
                                        echo '</div>';
                                        $line_number++;
                                    }
                                }
                                
                                // Show combined text
                                echo '<div style="margin-left: 10px; margin-top: 5px; font-weight: bold; color: #333;">';
                                echo '<strong>Combined:</strong> "' . esc_html(implode(' ', $text_lines)) . '"';
                                echo '</div>';

                                // Add font and color info
                                $text_details = array();
                                if (!empty($position['text_font'])) {
                                    $text_details[] = ucfirst($position['text_font']) . ' font';
                                }
                                if (!empty($position['text_color'])) {
                                    $text_details[] = ucfirst($position['text_color']) . ' color';
                                }

                                if (!empty($text_details)) {
                                    echo '<div style="margin-left: 10px; margin-top: 3px; font-style: italic;">';
                                    echo '(' . implode(', ', $text_details) . ')';
                                    echo '</div>';
                                }
                                echo '</div>';
                            }

                            // Add text notes if available
                            if (!empty($position['text_notes'])) {
                                echo '<div class="customization-notes" style="margin-top: 3px; font-size: 10px; color: #888; font-style: italic;">';
                                echo 'Notes: ' . esc_html($position['text_notes']);
                                echo '</div>';
                            }
                        }

                        echo '</div>';
                    }
                } else {
                    // Legacy single customization format
                    $method = ucfirst($data['method'] ?? '');
                    $content_type = ucfirst($data['content_type'] ?? '');

                    echo '<div class="customization-type" style="font-weight: 600; color: #333;">' . esc_html($content_type . ' ' . $method) . '</div>';

                    if (!empty($data['zones'])) {
                        echo '<div class="customization-zones" style="font-size: 11px; color: #666; margin-top: 3px;">' . esc_html(implode(', ', $data['zones'] ?? array())) . '</div>';
                    }

                    // Show logo preview if it's a logo customization
                    if (isset($data['content_type']) && $data['content_type'] === 'logo' && !empty($data['file_path'])) {
                        echo '<div class="customization-logo-preview" style="margin-top: 5px;">';
                        echo '<strong style="font-size: 11px;">' . __('Logo Preview:', 'wc-product-customizer') . '</strong><br>';

                        // Get the file URL
                        $upload_dir = wp_upload_dir();
                        $file_url = $upload_dir['baseurl'] . '/customization-files/' . $data['file_path'];

                        // Check if it's an image file
                        $extension = strtolower(pathinfo($data['file_path'], PATHINFO_EXTENSION));
                        $image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp');

                        if (in_array($extension, $image_extensions)) {
                            echo '<img src="' . esc_url($file_url) . '" alt="' . esc_attr__('Customization Logo', 'wc-product-customizer') . '" style="max-width: 60px; max-height: 60px; margin-top: 3px; border: 1px solid #ddd; border-radius: 3px;">';
                        } else {
                            echo '<div style="background: #f0f0f0; padding: 5px; margin-top: 3px; border-radius: 3px; text-align: center; font-size: 10px;">';
                            echo esc_html(strtoupper($extension)) . ' ' . __('File', 'wc-product-customizer');
                            echo '</div>';
                        }
                        echo '</div>';

                        // Add logo notes if available
                        if (!empty($data['logo_notes'])) {
                            echo '<div class="customization-notes" style="margin-top: 3px; font-size: 10px; color: #888; font-style: italic;">';
                            echo 'Notes: ' . esc_html($data['logo_notes']);
                            echo '</div>';
                        }
                    } elseif (isset($data['content_type']) && $data['content_type'] === 'text') {
                        // Show text content
                        $text_lines = array();
                        if (!empty($data['text_line_1'])) {
                            $text_lines[] = $data['text_line_1'];
                        }
                        if (!empty($data['text_line_2'])) {
                            $text_lines[] = $data['text_line_2'];
                        }
                        if (!empty($data['text_line_3'])) {
                            $text_lines[] = $data['text_line_3'];
                        }

                        if (!empty($text_lines)) {
                            echo '<div class="customization-text" style="margin-top: 5px; font-size: 11px; color: #666;">';
                            echo '<strong>' . __('Text:', 'wc-product-customizer') . '</strong><br>';
                            
                            // Show individual text lines
                            $line_number = 1;
                            foreach ($text_lines as $line) {
                                if (!empty($line)) {
                                    echo '<div style="margin-left: 10px; margin-top: 2px;">';
                                    echo '<strong>Text Line ' . $line_number . ':</strong> "' . esc_html($line) . '"';
                                    echo '</div>';
                                    $line_number++;
                                }
                            }
                            
                            // Show combined text
                            echo '<div style="margin-left: 10px; margin-top: 5px; font-weight: bold; color: #333;">';
                            echo '<strong>Combined:</strong> "' . esc_html(implode(' ', $text_lines)) . '"';
                            echo '</div>';

                            // Add font and color info
                            $text_details = array();
                            if (!empty($data['text_font'])) {
                                $text_details[] = ucfirst($data['text_font']) . ' font';
                            }
                            if (!empty($data['text_color'])) {
                                $text_details[] = ucfirst($data['text_color']) . ' color';
                            }

                            if (!empty($text_details)) {
                                echo '<div style="margin-left: 10px; margin-top: 3px; font-style: italic;">';
                                echo '(' . implode(', ', $text_details) . ')';
                                echo '</div>';
                            }
                            echo '</div>';
                        }

                        // Add text notes if available
                        if (!empty($data['text_notes'])) {
                            echo '<div class="customization-notes" style="margin-top: 3px; font-size: 10px; color: #888; font-style: italic;">';
                            echo 'Notes: ' . esc_html($data['text_notes']);
                            echo '</div>';
                        }
                    }
                }
                ?>
                <div style="margin-top: 10px;">
                    <button type="button" class="button edit-customization-btn" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" data-product-id="<?php echo esc_attr($product_id); ?>" style="background: #007cba; color: white; border: 1px solid #007cba; padding: 6px 12px; margin-right: 8px; border-radius: 4px; font-size: 12px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.2s ease;">Edit Customization</button>
                    <button type="button" class="button remove-customization-btn" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" style="background: #dc3545; color: white; border: 1px solid #dc3545; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.2s ease;">Remove Customization</button>
                </div>
            <?php else: ?>
                <button type="button" class="button add-customization-btn" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" data-product-id="<?php echo esc_attr($product_id); ?>" style="background: #007cba; color: white; border: 1px solid #007cba; padding: 10px 20px; border-radius: 4px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.2s ease;">Add logo to this item</button>
            <?php endif; ?>
                    </div>
        <?php
        $button_html = ob_get_clean();
        
        return $product_name . $button_html;
    }

    /**
     * Global cart item name modifier (higher priority)
     *
     * @param string $product_name
     * @param array $cart_item
     * @param string $cart_item_key
     * @return string
     */
    public function modify_cart_item_name_global($product_name, $cart_item, $cart_item_key)
    {
        $product_id = $cart_item['product_id'];
        
        // Check if product supports customization
        if (!$this->is_customization_enabled($product_id)) {
            return $product_name;
        }
        
        // Check if this is already processed
        if (strpos($product_name, 'customization-actions') !== false) {
            return $product_name;
        }
        
        // Use output buffering to properly render HTML
        ob_start();
        ?>
        <div class="customization-actions" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" data-product-id="<?php echo esc_attr($product_id); ?>" style="margin-top: 8px; font-size: 12px;">
            <?php if (isset($cart_item['customization_data'])): ?>
                <button type="button" class="button edit-customization-btn" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" data-product-id="<?php echo esc_attr($product_id); ?>" style="background: #007cba; color: white; border: 1px solid #007cba; padding: 4px 8px; margin-right: 4px; border-radius: 3px; font-size: 11px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block;">Edit</button>
                <button type="button" class="button remove-customization-btn" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" style="background: #dc3545; color: white; border: 1px solid #dc3545; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block;">Remove</button>
            <?php else: ?>
                <button type="button" class="button add-customization-btn" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" data-product-id="<?php echo esc_attr($product_id); ?>" style="background: #007cba; color: white; border: 1px solid #007cba; padding: 6px 12px; border-radius: 3px; font-size: 12px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block;">Add logo</button>
            <?php endif; ?>
                        </div>
        <?php
        $button_html = ob_get_clean();
        
        return $product_name . $button_html;
    }

    /**
     * Fix escaped HTML in content
     *
     * @param string $content
     * @return string
     */
    public function fix_escaped_html($content)
    {
        // Only process if we're on cart page or if content contains customization HTML
        if (!is_cart() && strpos($content, 'customization-actions') === false) {
            return $content;
        }
        
        // Look for escaped HTML patterns
        $patterns = array(
            '/&lt;div class="customization-actions"[^&]*?&gt;.*?&lt;\/div&gt;/i',
            '/&lt;button[^&]*?customization-actions[^&]*?&gt;.*?&lt;\/button&gt;/i'
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                // Decode the HTML
                $decoded_html = html_entity_decode($matches[0]);
                
                // Replace the escaped version with the decoded version
                $content = str_replace($matches[0], $decoded_html, $content);
            }
        }
        
        return $content;
    }

    /**
     * Modify cart item thumbnail to include customization button
     *
     * @param string $thumbnail
     * @param array $cart_item
     * @param string $cart_item_key
     * @return string
     */
    public function modify_cart_item_thumbnail($thumbnail, $cart_item, $cart_item_key)
    {
        $product_id = $cart_item['product_id'];
        
        // Check if product supports customization
        if (!$this->is_customization_enabled($product_id)) {
            return $thumbnail;
        }
        
        // Prevent duplicate buttons
        static $modified_thumbnails = array();
        if (in_array($cart_item_key, $modified_thumbnails)) {
            return $thumbnail;
        }
        $modified_thumbnails[] = $cart_item_key;
        
        // Add button after thumbnail
        $button_html = '<div class="customization-actions" data-cart-key="' . esc_attr($cart_item_key) . '" data-product-id="' . esc_attr($product_id) . '" style="margin-top: 8px; text-align: center;">';
        
        if (isset($cart_item['customization_data'])) {
            $button_html .= '<button type="button" class="button edit-customization-btn" data-cart-key="' . esc_attr($cart_item_key) . '" data-product-id="' . esc_attr($product_id) . '" style="background: #007cba; color: white; border: 1px solid #007cba; padding: 4px 8px; margin-right: 4px; border-radius: 3px; font-size: 11px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block;">Edit</button>';
            $button_html .= '<button type="button" class="button remove-customization-btn" data-cart-key="' . esc_attr($cart_item_key) . '" style="background: #dc3545; color: white; border: 1px solid #dc3545; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block;">Remove</button>';
        } else {
            $button_html .= '<button type="button" class="button add-customization-btn" data-cart-key="' . esc_attr($cart_item_key) . '" data-product-id="' . esc_attr($product_id) . '" style="background: #007cba; color: white; border: 1px solid #007cba; padding: 6px 12px; border-radius: 3px; font-size: 12px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block;">Add logo</button>';
        }
        
        $button_html .= '</div>';
        
        return $thumbnail . wp_kses($button_html, array(
            'div' => array(
                'class' => array(),
                'data-cart-key' => array(),
                'data-product-id' => array(),
                'style' => array()
            ),
            'button' => array(
                'type' => array(),
                'class' => array(),
                'data-cart-key' => array(),
                'data-product-id' => array(),
                'style' => array()
            )
        ));
    }

    /**
     * Modify cart item subtotal to include customization button
     *
     * @param string $subtotal
     * @param array $cart_item
     * @param string $cart_item_key
     * @return string
     */
    public function modify_cart_item_subtotal($subtotal, $cart_item, $cart_item_key)
    {
        $product_id = $cart_item['product_id'];
        
        // Check if product supports customization
        if (!$this->is_customization_enabled($product_id)) {
            return $subtotal;
        }
        
        // Prevent duplicate buttons
        static $modified_subtotals = array();
        if (in_array($cart_item_key, $modified_subtotals)) {
            return $subtotal;
        }
        $modified_subtotals[] = $cart_item_key;
        
        // Add button after subtotal
        $button_html = '<div class="customization-actions" data-cart-key="' . esc_attr($cart_item_key) . '" data-product-id="' . esc_attr($product_id) . '" style="margin-top: 8px;">';
        
        if (isset($cart_item['customization_data'])) {
            $button_html .= '<button type="button" class="button edit-customization-btn" data-cart-key="' . esc_attr($cart_item_key) . '" data-product-id="' . esc_attr($product_id) . '" style="background: #007cba; color: white; border: 1px solid #007cba; padding: 4px 8px; margin-right: 4px; border-radius: 3px; font-size: 11px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block;">Edit</button>';
            $button_html .= '<button type="button" class="button remove-customization-btn" data-cart-key="' . esc_attr($cart_item_key) . '" style="background: #dc3545; color: white; border: 1px solid #dc3545; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block;">Remove</button>';
        } else {
            $button_html .= '<button type="button" class="button add-customization-btn" data-cart-key="' . esc_attr($cart_item_key) . '" data-product-id="' . esc_attr($product_id) . '" style="background: #007cba; color: white; border: 1px solid #007cba; padding: 6px 12px; border-radius: 3px; font-size: 12px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block;">Add logo</button>';
        }
        
        $button_html .= '</div>';
        
        return $subtotal . wp_kses($button_html, array(
            'div' => array(
                'class' => array(),
                'data-cart-key' => array(),
                'data-product-id' => array(),
                'style' => array()
            ),
            'button' => array(
                'type' => array(),
                'class' => array(),
                'data-cart-key' => array(),
                'data-product-id' => array(),
                'style' => array()
            )
        ));
    }

    /**
     * Modify widget cart item for mini-cart and cart widgets
     *
     * @param string $quantity
     * @param array $cart_item
     * @param string $cart_item_key
     * @return string
     */
    public function modify_widget_cart_item($quantity, $cart_item, $cart_item_key)
    {
        $product_id = $cart_item['product_id'];
        
        // Check if product supports customization
        if (!$this->is_customization_enabled($product_id)) {
            return $quantity;
        }
        
        // Prevent duplicate buttons
        static $modified_widget_items = array();
        if (in_array($cart_item_key, $modified_widget_items)) {
            return $quantity;
        }
        $modified_widget_items[] = $cart_item_key;
        
        // Create button HTML for widget
        $button_html = '<div class="customization-actions" data-cart-key="' . esc_attr($cart_item_key) . '" data-product-id="' . esc_attr($product_id) . '" style="margin-top: 8px; font-size: 12px;">';
        
        if (isset($cart_item['customization_data'])) {
            $button_html .= '<button type="button" class="button edit-customization-btn" data-cart-key="' . esc_attr($cart_item_key) . '" data-product-id="' . esc_attr($product_id) . '" style="background: #007cba; color: white; border: 1px solid #007cba; padding: 4px 8px; margin-right: 4px; border-radius: 3px; font-size: 11px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block;">Edit</button>';
            $button_html .= '<button type="button" class="button remove-customization-btn" data-cart-key="' . esc_attr($cart_item_key) . '" style="background: #dc3545; color: white; border: 1px solid #dc3545; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block;">Remove</button>';
        } else {
            $button_html .= '<button type="button" class="button add-customization-btn" data-cart-key="' . esc_attr($cart_item_key) . '" data-product-id="' . esc_attr($product_id) . '" style="background: #007cba; color: white; border: 1px solid #007cba; padding: 6px 12px; border-radius: 3px; font-size: 12px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block;">Add logo</button>';
        }
        
        $button_html .= '</div>';
        
        return $quantity . wp_kses($button_html, array(
            'div' => array(
                'class' => array(),
                'data-cart-key' => array(),
                'data-product-id' => array(),
                'style' => array()
            ),
            'button' => array(
                'type' => array(),
                'class' => array(),
                'data-cart-key' => array(),
                'data-product-id' => array(),
                'style' => array()
            )
        ));
    }

    /**
     * Start output buffer to modify cart HTML
     */
    public function start_cart_output_buffer()
    {
        if (is_cart()) {
            ob_start(array($this, 'modify_cart_html'));
        }
    }

    /**
     * End output buffer
     */
    public function end_cart_output_buffer()
    {
        if (is_cart()) {
            ob_end_flush();
        }
    }

    /**
     * Modify cart HTML to inject customization buttons
     *
     * @param string $html
     * @return string
     */
    public function modify_cart_html($html)
    {
        if (!is_cart()) {
            return $html;
        }

        // Get cart items with customization enabled
        $cart_items = WC()->cart->get_cart();
        $customizable_items = array();
        
        foreach ($cart_items as $cart_item_key => $cart_item) {
            if ($this->is_customization_enabled($cart_item['product_id'])) {
                $customizable_items[] = array(
                    'cart_key' => $cart_item_key,
                    'product_id' => $cart_item['product_id'],
                    'has_customization' => isset($cart_item['customization_data']),
                    'product_name' => $cart_item['data']->get_name()
                );
            }
        }

        if (empty($customizable_items)) {
            return $html;
        }

        // Inject buttons for each customizable item
        foreach ($customizable_items as $item) {
            $cart_key = $item['cart_key'];
            $product_id = $item['product_id'];
            $has_customization = $item['has_customization'];
            $product_name = $item['product_name'];

        // Use output buffering to properly render HTML
        ob_start();
        ?>
        <div class="customization-actions" data-cart-key="<?php echo esc_attr($cart_key); ?>" data-product-id="<?php echo esc_attr($product_id); ?>" style="margin-top: 10px; padding: 10px 0; border-top: 1px solid #f0f0f0;">
            <?php if ($has_customization): ?>
                <div style="margin-bottom: 8px; font-size: 12px; color: #666; font-weight: 500;">Customization Options:</div>
                <button type="button" class="button edit-customization-btn" data-cart-key="<?php echo esc_attr($cart_key); ?>" data-product-id="<?php echo esc_attr($product_id); ?>" style="background: #007cba; color: white; border: 1px solid #007cba; padding: 8px 16px; margin-right: 8px; border-radius: 4px; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.2s ease;">Edit Customization</button>
                <button type="button" class="button remove-customization-btn" data-cart-key="<?php echo esc_attr($cart_key); ?>" style="background: #dc3545; color: white; border: 1px solid #dc3545; padding: 8px 16px; border-radius: 4px; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.2s ease;">Remove Customization</button>
            <?php else: ?>
                <button type="button" class="button add-customization-btn" data-cart-key="<?php echo esc_attr($cart_key); ?>" data-product-id="<?php echo esc_attr($product_id); ?>" style="background: #007cba; color: white; border: 1px solid #007cba; padding: 10px 20px; border-radius: 4px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.2s ease;">Add logo to this item</button>
            <?php endif; ?>
        </div>
        <?php
        $button_html = ob_get_clean();

        // Try to find and replace the product name with product name + button
        $product_name_escaped = preg_quote($product_name, '/');
        $pattern = '/(<td[^>]*class="[^"]*product[^"]*"[^>]*>.*?' . $product_name_escaped . '.*?<\/td>)/is';
        
        if (preg_match($pattern, $html, $matches)) {
            $replacement = $matches[1] . $button_html;
            $html = str_replace($matches[1], $replacement, $html);
        } else {
            // Fallback: try to find any cell containing the product name
            $pattern = '/(<td[^>]*>.*?' . $product_name_escaped . '.*?<\/td>)/is';
            if (preg_match($pattern, $html, $matches)) {
                $replacement = $matches[1] . $button_html;
                $html = str_replace($matches[1], $replacement, $html);
            }
        }
        }

        return $html;
    }

    /**
     * Add fallback JavaScript for theme compatibility
     * This ensures buttons appear even if theme doesn't support WooCommerce hooks
     */
    public function add_cart_fallback_script()
    {
        // Only run on cart page
        if (!is_cart()) {
            return;
        }
        
        // Get cart items with customization enabled
        $cart_items = WC()->cart->get_cart();
        $customizable_items = array();
        
        foreach ($cart_items as $cart_item_key => $cart_item) {
            if ($this->is_customization_enabled($cart_item['product_id'])) {
                $customizable_items[] = array(
                    'cart_key' => $cart_item_key,
                    'product_id' => $cart_item['product_id'],
                    'has_customization' => isset($cart_item['customization_data']),
                    'product_name' => $cart_item['data']->get_name()
                );
            }
        }
        
        if (empty($customizable_items)) {
            return;
        }
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('Customizer: Fallback script loaded for', <?php echo json_encode($customizable_items); ?>);

                // Helper function to capitalize first letter
                function capitalizeFirstLetter(string) {
                    if (!string) return '';
                    return string.charAt(0).toUpperCase() + string.slice(1);
                }
            
            // Function to add buttons to cart items
            function addCustomizationButtons() {
                console.log('Customizer: Attempting to add buttons...');
                
                <?php foreach ($customizable_items as $item): ?>
                var cartKey = '<?php echo esc_js($item['cart_key']); ?>';
                var productId = <?php echo intval($item['product_id']); ?>;
                var hasCustomization = <?php echo $item['has_customization'] ? 'true' : 'false'; ?>;
                var productName = '<?php echo esc_js($item['product_name']); ?>';
                
                        // Get customization data
                        var customizationData = null;
                        <?php
                        if ($item['has_customization']) {
                            $cart_item = WC()->cart->get_cart_item($item['cart_key']);
                            if ($cart_item && isset($cart_item['customization_data'])) {
                                echo 'customizationData = ' . json_encode($cart_item['customization_data']) . ';';
                            }
                        }
                        ?>

                        console.log('Customizer: Processing item', cartKey, productId, hasCustomization, productName, customizationData);
                
                // More aggressive selectors for printspace theme
                var selectors = [
                    'tr[data-cart-key="' + cartKey + '"]',
                    'tr[data-key="' + cartKey + '"]',
                    '.cart_item[data-cart-key="' + cartKey + '"]',
                    '.woocommerce-cart-form__cart-item[data-cart-key="' + cartKey + '"]',
                    'tr:has(.product-name:contains("' + productName + '"))',
                    'tr:has(.product-title:contains("' + productName + '"))',
                    'tr:has(.woocommerce-cart-form__cart-item:contains("' + productName + '"))',
                    'tr:has(td:contains("' + productName + '"))',
                    'tr:has(.cart_item:contains("' + productName + '"))',
                    'tbody tr:has(td:contains("' + productName + '"))',
                    '.woocommerce-cart-form tbody tr:has(td:contains("' + productName + '"))',
                    'table.cart tbody tr:has(td:contains("' + productName + '"))'
                ];
                
                var $cartItem = null;
                for (var i = 0; i < selectors.length; i++) {
                    try {
                        $cartItem = $(selectors[i]);
                        if ($cartItem.length > 0) {
                            break;
                        }
                            } catch (e) {
                        console.log('Customizer: Selector failed:', selectors[i], e);
                    }
                }
                
                // If still not found, try a more direct approach
                if (!$cartItem || $cartItem.length === 0) {
                    console.log('Customizer: Trying direct DOM search for product:', productName);
                    
                    // Search all table rows for the product name
                    $('tr').each(function() {
                        var $row = $(this);
                        if ($row.text().indexOf(productName) !== -1) {
                            $cartItem = $row;
                            console.log('Customizer: Found cart item by text search');
                            return false; // break
                        }
                    });
                }
                
                if ($cartItem && $cartItem.length > 0 && $cartItem.find('.customization-actions').length === 0) {
                    console.log('Customizer: Adding buttons to cart item');
                    
                    var buttonHtml = '<div class="customization-actions" data-cart-key="' + cartKey + '" data-product-id="' + productId + '" style="margin-top: 10px; padding: 10px 0; border-top: 1px solid #f0f0f0;">';

                            // Generate customization URL
                            var customizeUrl = '<?php echo home_url('/'); ?>?wc_customize=1&product_id=' + productId + '&cart_key=' + cartKey + '&return_url=' + encodeURIComponent('<?php echo wc_get_cart_url(); ?>');
                            var editUrl = customizeUrl + '&edit=1';
                    
                    if (hasCustomization) {
                                console.log('Customizer: Fallback script - hasCustomization is true, building details...');
                                console.log('Customizer: customizationData received:', customizationData);
                                console.log('Customizer: customizationData type:', typeof customizationData);
                                console.log('Customizer: customizationData is array:', Array.isArray(customizationData));
                                if (customizationData) {
                                    console.log('Customizer: customizationData length:', customizationData.length);
                                    console.log('Customizer: customizationData first item:', customizationData[0]);
                                }
                                buttonHtml += '<div style="margin-bottom: 8px; font-size: 12px; color: #666; font-weight: 500;">Customization Details:</div>';

                                // Process customization data in JavaScript
                                if (customizationData) {
                                    console.log('Customizer: Processing customizationData...');
                                    console.log('Customizer: customizationData[0]:', customizationData[0]);
                                    console.log('Customizer: has zone_id property:', customizationData[0].hasOwnProperty('zone_id'));
                                    console.log('Customizer: zone_id value:', customizationData[0].zone_id);
                                    // Check if it's multiple positions (new format) or single customization (legacy)
                                    if (Array.isArray(customizationData) && customizationData.length > 0 && customizationData[0].hasOwnProperty('zone_id')) {
                                        console.log('Customizer: Processing as multiple positions format');
                                        // Multiple positions format
                                        customizationData.forEach(function(position, index) {
                                            console.log('Customizer: Processing position', index, position);
                                            buttonHtml += '<div class="position-customization" style="margin-bottom: 8px; padding: 8px; background: #f8f9fa; border-radius: 4px;">';
                                            buttonHtml += '<div class="customization-type" style="font-weight: 600; color: #333;">' + position.zone_name + ' - ' + capitalizeFirstLetter(position.content_type) + ' ' + capitalizeFirstLetter(position.method) + '</div>';

                                            // Show logo preview if it's a logo customization
                                            if (position.content_type === 'logo' && position.file_path) {
                                                buttonHtml += '<div class="customization-logo-preview" style="margin-top: 5px;">';
                                                buttonHtml += '<strong style="font-size: 11px;">Logo Preview:</strong><br>';

                                                var uploadDir = '<?php echo wp_upload_dir()['baseurl']; ?>';
                                                var fileUrl = uploadDir + '/customization-files/' + position.file_path;

                                                var extension = position.file_path.split('.').pop().toLowerCase();
                                                var imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

                                                if (imageExtensions.includes(extension)) {
                                                    buttonHtml += '<img src="' + fileUrl + '" alt="Customization Logo" style="max-width: 60px; max-height: 60px; margin-top: 3px; border: 1px solid #ddd; border-radius: 3px;">';
                    } else {
                                                    buttonHtml += '<div style="background: #f0f0f0; padding: 5px; margin-top: 3px; border-radius: 3px; text-align: center; font-size: 10px;">' + extension.toUpperCase() + ' File</div>';
                                                }
                                                buttonHtml += '</div>';

                                        // Add logo notes if available
                                        if (position.logo_notes && position.logo_notes.trim() !== '') {
                                            console.log('Customizer: Adding logo notes:', position.logo_notes);
                                            buttonHtml += '<div class="customization-notes" style="margin-top: 3px; font-size: 10px; color: #888; font-style: italic;">';
                                            buttonHtml += 'Notes: ' + position.logo_notes;
                                            buttonHtml += '</div>';
                                        } else {
                                            console.log('Customizer: No logo notes found for position', index, 'value:', position.logo_notes);
                                        }
                                        
                                        // Show logo alternative if present (even when file is uploaded)
                                        if (position.logo_alternative && position.logo_alternative.trim() !== '') {
                                            console.log('Customizer: Adding logo alternative:', position.logo_alternative);
                                            buttonHtml += '<div class="customization-logo-alternative" style="margin-top: 5px; font-size: 11px; color: #666;">';
                                            buttonHtml += '<strong>Logo Option:</strong> ';
                                            
                                            if (position.logo_alternative === 'contact_later') {
                                                buttonHtml += 'Logo to be provided later';
                                            } else if (position.logo_alternative === 'already_have') {
                                                buttonHtml += 'Logo already on file';
                                            } else {
                                                buttonHtml += capitalizeFirstLetter(position.logo_alternative.replace('_', ' '));
                                            }
                                            buttonHtml += '</div>';
                                        } else {
                                            console.log('Customizer: No logo alternative found for position', index, 'value:', position.logo_alternative);
                                        }
                                    } else if (position.content_type === 'logo' && position.logo_alternative && position.logo_alternative.trim() !== '') {
                                        // Show logo alternative when no file is uploaded
                                        buttonHtml += '<div class="customization-logo-alternative" style="margin-top: 5px; font-size: 11px; color: #666;">';
                                        buttonHtml += '<strong>Logo Option:</strong> ';
                                        
                                        if (position.logo_alternative === 'contact_later') {
                                            buttonHtml += 'Logo to be provided later';
                                        } else if (position.logo_alternative === 'already_have') {
                                            buttonHtml += 'Logo already on file';
                                        } else {
                                            buttonHtml += capitalizeFirstLetter(position.logo_alternative.replace('_', ' '));
                                        }
                                        buttonHtml += '</div>';
                                        
                                        // Add logo notes if available
                                        if (position.logo_notes && position.logo_notes.trim() !== '') {
                                            console.log('Customizer: Adding logo notes for alternative:', position.logo_notes);
                                            buttonHtml += '<div class="customization-notes" style="margin-top: 3px; font-size: 10px; color: #888; font-style: italic;">';
                                            buttonHtml += 'Notes: ' + position.logo_notes;
                                            buttonHtml += '</div>';
                                        } else {
                                            console.log('Customizer: No logo notes found for alternative position', index, 'value:', position.logo_notes);
                                        }
                                            } else if (position.content_type === 'text') {
                                        // Show text content
                                        var textLines = [];
                                        if (position.text_line_1 && position.text_line_1.trim() !== '') textLines.push(position.text_line_1);
                                        if (position.text_line_2 && position.text_line_2.trim() !== '') textLines.push(position.text_line_2);
                                        if (position.text_line_3 && position.text_line_3.trim() !== '') textLines.push(position.text_line_3);

                                                if (textLines.length > 0) {
                                                    buttonHtml += '<div class="customization-text" style="margin-top: 5px; font-size: 11px; color: #666;">';
                                                    buttonHtml += '<strong>Text:</strong><br>';
                                                    
                                                    // Show individual text lines
                                                    for (var i = 0; i < textLines.length; i++) {
                                                        buttonHtml += '<div style="margin-left: 10px; margin-top: 2px;">';
                                                        buttonHtml += '<strong>Text Line ' + (i + 1) + ':</strong> "' + textLines[i] + '"';
                                                        buttonHtml += '</div>';
                                                    }
                                                    
                                                    // Show combined text
                                                    buttonHtml += '<div style="margin-left: 10px; margin-top: 5px; font-weight: bold; color: #333;">';
                                                    buttonHtml += '<strong>Combined:</strong> "' + textLines.join(' ') + '"';
                                                    buttonHtml += '</div>';

                                                    var textDetails = [];
                                                    if (position.text_font) textDetails.push(capitalizeFirstLetter(position.text_font) + ' font');
                                                    if (position.text_color) textDetails.push(capitalizeFirstLetter(position.text_color) + ' color');

                                                    if (textDetails.length > 0) {
                                                        buttonHtml += '<div style="margin-left: 10px; margin-top: 3px; font-style: italic;">';
                                                        buttonHtml += '(' + textDetails.join(', ') + ')';
                                                        buttonHtml += '</div>';
                                                    }
                                                    buttonHtml += '</div>';
                                                }

                                        // Add text notes if available
                                        if (position.text_notes && position.text_notes.trim() !== '') {
                                            console.log('Customizer: Adding text notes:', position.text_notes);
                                            buttonHtml += '<div class="customization-notes" style="margin-top: 3px; font-size: 10px; color: #888; font-style: italic;">';
                                            buttonHtml += 'Notes: ' + position.text_notes;
                                            buttonHtml += '</div>';
                                        } else {
                                            console.log('Customizer: No text notes found for position', index, 'value:', position.text_notes);
                                        }
                                            }

                                            buttonHtml += '</div>';
                                        });
                                    } else {
                                        console.log('Customizer: Processing as legacy single customization format');
                                        console.log('Customizer: Legacy data:', customizationData);
                                        // Legacy single customization format
                                        var method = capitalizeFirstLetter(customizationData.method || '');
                                        var contentType = capitalizeFirstLetter(customizationData.content_type || '');

                                        buttonHtml += '<div class="customization-type" style="font-weight: 600; color: #333;">' + contentType + ' ' + method + '</div>';

                                        if (customizationData.zones && customizationData.zones.length > 0) {
                                            buttonHtml += '<div class="customization-zones" style="font-size: 11px; color: #666; margin-top: 3px;">' + customizationData.zones.join(', ') + '</div>';
                                        }

                                        // Show logo preview if it's a logo customization
                                        if (customizationData.content_type === 'logo' && customizationData.file_path) {
                                            buttonHtml += '<div class="customization-logo-preview" style="margin-top: 5px;">';
                                            buttonHtml += '<strong style="font-size: 11px;">Logo Preview:</strong><br>';

                                            var uploadDir = '<?php echo wp_upload_dir()['baseurl']; ?>';
                                            var fileUrl = uploadDir + '/customization-files/' + customizationData.file_path;

                                            var extension = customizationData.file_path.split('.').pop().toLowerCase();
                                            var imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

                                            if (imageExtensions.includes(extension)) {
                                                buttonHtml += '<img src="' + fileUrl + '" alt="Customization Logo" style="max-width: 60px; max-height: 60px; margin-top: 3px; border: 1px solid #ddd; border-radius: 3px;">';
                                            } else {
                                                buttonHtml += '<div style="background: #f0f0f0; padding: 5px; margin-top: 3px; border-radius: 3px; text-align: center; font-size: 10px;">' + extension.toUpperCase() + ' File</div>';
                                            }
                                            buttonHtml += '</div>';

                                        // Add logo notes if available
                                        if (customizationData.logo_notes && customizationData.logo_notes.trim() !== '') {
                                            console.log('Customizer: Adding legacy logo notes:', customizationData.logo_notes);
                                            buttonHtml += '<div class="customization-notes" style="margin-top: 3px; font-size: 10px; color: #888; font-style: italic;">';
                                            buttonHtml += 'Notes: ' + customizationData.logo_notes;
                                            buttonHtml += '</div>';
                                        } else {
                                            console.log('Customizer: No legacy logo notes found, value:', customizationData.logo_notes);
                                        }
                                    } else if (customizationData.content_type === 'logo' && customizationData.logo_alternative && customizationData.logo_alternative.trim() !== '') {
                                        // Show logo alternative when no file is uploaded (legacy format)
                                        buttonHtml += '<div class="customization-logo-alternative" style="margin-top: 5px; font-size: 11px; color: #666;">';
                                        buttonHtml += '<strong>Logo Option:</strong> ';
                                        
                                        if (customizationData.logo_alternative === 'contact_later') {
                                            buttonHtml += 'Logo to be provided later';
                                        } else if (customizationData.logo_alternative === 'already_have') {
                                            buttonHtml += 'Logo already on file';
                                        } else {
                                            buttonHtml += capitalizeFirstLetter(customizationData.logo_alternative.replace('_', ' '));
                                        }
                                        buttonHtml += '</div>';
                                        
                                        // Add logo notes if available
                                        if (customizationData.logo_notes && customizationData.logo_notes.trim() !== '') {
                                            console.log('Customizer: Adding legacy logo notes for alternative:', customizationData.logo_notes);
                                            buttonHtml += '<div class="customization-notes" style="margin-top: 3px; font-size: 10px; color: #888; font-style: italic;">';
                                            buttonHtml += 'Notes: ' + customizationData.logo_notes;
                                            buttonHtml += '</div>';
                                        } else {
                                            console.log('Customizer: No legacy logo notes found for alternative, value:', customizationData.logo_notes);
                                        }
                                        } else if (customizationData.content_type === 'text') {
                                        // Show text content
                                        var textLines = [];
                                        if (customizationData.text_line_1 && customizationData.text_line_1.trim() !== '') textLines.push(customizationData.text_line_1);
                                        if (customizationData.text_line_2 && customizationData.text_line_2.trim() !== '') textLines.push(customizationData.text_line_2);
                                        if (customizationData.text_line_3 && customizationData.text_line_3.trim() !== '') textLines.push(customizationData.text_line_3);

                                            if (textLines.length > 0) {
                                                buttonHtml += '<div class="customization-text" style="margin-top: 5px; font-size: 11px; color: #666;">';
                                                buttonHtml += '<strong>Text:</strong><br>';
                                                
                                                // Show individual text lines
                                                for (var i = 0; i < textLines.length; i++) {
                                                    buttonHtml += '<div style="margin-left: 10px; margin-top: 2px;">';
                                                    buttonHtml += '<strong>Text Line ' + (i + 1) + ':</strong> "' + textLines[i] + '"';
                                                    buttonHtml += '</div>';
                                                }
                                                
                                                // Show combined text
                                                buttonHtml += '<div style="margin-left: 10px; margin-top: 5px; font-weight: bold; color: #333;">';
                                                buttonHtml += '<strong>Combined:</strong> "' + textLines.join(' ') + '"';
                                                buttonHtml += '</div>';

                                                var textDetails = [];
                                                if (customizationData.text_font) textDetails.push(capitalizeFirstLetter(customizationData.text_font) + ' font');
                                                if (customizationData.text_color) textDetails.push(capitalizeFirstLetter(customizationData.text_color) + ' color');

                                                if (textDetails.length > 0) {
                                                    buttonHtml += '<div style="margin-left: 10px; margin-top: 3px; font-style: italic;">';
                                                    buttonHtml += '(' + textDetails.join(', ') + ')';
                                                    buttonHtml += '</div>';
                                                }
                                                buttonHtml += '</div>';
                                            }

                                        // Add text notes if available
                                        if (customizationData.text_notes && customizationData.text_notes.trim() !== '') {
                                            console.log('Customizer: Adding legacy text notes:', customizationData.text_notes);
                                            buttonHtml += '<div class="customization-notes" style="margin-top: 3px; font-size: 10px; color: #888; font-style: italic;">';
                                            buttonHtml += 'Notes: ' + customizationData.text_notes;
                                            buttonHtml += '</div>';
                                        } else {
                                            console.log('Customizer: No legacy text notes found, value:', customizationData.text_notes);
                                        }
                                        }
                                    }
                                }

                                buttonHtml += '<div style="margin-top: 10px;">';
                                buttonHtml += '<a href="' + editUrl + '" class="button wc-customizer-edit-link" style="background: #007cba; color: white; border: 1px solid #007cba; padding: 6px 12px; margin-right: 8px; border-radius: 4px; font-size: 12px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.2s ease;">Edit Customization</a>';
                                buttonHtml += '<button type="button" class="button remove-customization-btn" data-cart-key="' + cartKey + '" style="background: #dc3545; color: white; border: 1px solid #dc3545; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.2s ease;">Remove Customization</button>';
                                buttonHtml += '</div>';
                            } else {
                                buttonHtml += '<a href="' + customizeUrl + '" class="button wc-customizer-add-link" style="background: #007cba; color: white; border: 1px solid #007cba; padding: 10px 20px; border-radius: 4px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.2s ease;">Add logo to this item</a>';
                            }

                    buttonHtml += '</div>';
                    
                    // Try to find the product details cell (usually the second cell)
                    var $productDetailsCell = $cartItem.find('td:nth-child(2)');
                    if ($productDetailsCell.length === 0) {
                        $productDetailsCell = $cartItem.find('td').eq(1);
                    }
                    if ($productDetailsCell.length === 0) {
                        $productDetailsCell = $cartItem.find('td:not(:first-child)').first();
                    }
                    
                    var inserted = false;
                    
                    if ($productDetailsCell.length > 0) {
                        // Try to add after the description or quantity selector
                        var $description = $productDetailsCell.find('p, .product-description, .woocommerce-cart-form__cart-item-description');
                        var $quantity = $productDetailsCell.find('.quantity, .woocommerce-cart-form__cart-item-quantity');
                        var $removeLink = $productDetailsCell.find('a[href*="remove"], .remove');
                        
                        if ($description.length > 0) {
                            $description.after(buttonHtml);
                            console.log('Customizer: Buttons added after description');
                            inserted = true;
                        } else if ($quantity.length > 0) {
                            $quantity.after(buttonHtml);
                            console.log('Customizer: Buttons added after quantity');
                            inserted = true;
                        } else if ($removeLink.length > 0) {
                            $removeLink.before(buttonHtml);
                            console.log('Customizer: Buttons added before remove link');
                            inserted = true;
                        } else {
                            // Add at the end of the product details cell
                            $productDetailsCell.append(buttonHtml);
                            console.log('Customizer: Buttons added to end of product details cell');
                            inserted = true;
                        }
                    }
                    
                    if (!inserted) {
                        // Fallback: try other insertion points
                        var insertionPoints = [
                            $cartItem.find('.product-name'),
                            $cartItem.find('.product-title'),
                            $cartItem.find('.woocommerce-cart-form__cart-item'),
                            $cartItem.find('td:last-child')
                        ];
                        
                        for (var j = 0; j < insertionPoints.length; j++) {
                            if (insertionPoints[j].length > 0) {
                                insertionPoints[j].append(buttonHtml);
                                console.log('Customizer: Buttons added to fallback insertion point', j);
                                inserted = true;
                                break;
                            }
                        }
                    }
                    
                    if (!inserted) {
                        // Last resort: add after the entire row
                        $cartItem.after('<tr><td colspan="100%" style="padding: 10px;">' + buttonHtml + '</td></tr>');
                        console.log('Customizer: Buttons added after row');
                    }
                } else if ($cartItem && $cartItem.length > 0) {
                    console.log('Customizer: Buttons already exist for this item');
                } else {
                    console.log('Customizer: Cart item not found for', productName);
                    console.log('Customizer: Available text in cart:', $('.woocommerce-cart-form').text());
                }
                <?php endforeach; ?>
            }
            
            // Try multiple times with different delays
            addCustomizationButtons();
            setTimeout(addCustomizationButtons, 500);
            setTimeout(addCustomizationButtons, 1000);
            setTimeout(addCustomizationButtons, 2000);
            setTimeout(addCustomizationButtons, 3000);
            setTimeout(addCustomizationButtons, 5000);
            
            // Try again when cart is updated
            $(document.body).on('updated_cart_totals', function() {
                console.log('Customizer: Cart updated, re-adding buttons');
                setTimeout(addCustomizationButtons, 100);
            });
            
            // Also try on window load
            $(window).on('load', function() {
                console.log('Customizer: Window loaded, re-adding buttons');
                setTimeout(addCustomizationButtons, 500);
            });
            
            // Try when DOM is fully ready
            $(document).on('DOMContentLoaded', function() {
                console.log('Customizer: DOM fully loaded, re-adding buttons');
                setTimeout(addCustomizationButtons, 1000);
            });
            
            // Clean up any raw HTML that appears
            function cleanupRawHTML() {
                
                // Find and replace any raw HTML customization actions
                $('*').contents().filter(function() {
                    return this.nodeType === 3; // Text nodes
                }).each(function() {
                    var text = $(this).text();
                    if (text.indexOf('customization-actions') !== -1 && text.indexOf('<div') !== -1) {
                        
                        // Extract the HTML from the text
                        var htmlMatch = text.match(/<div class="customization-actions"[^>]*>.*?<\/div>/);
                        if (htmlMatch) {
                            var htmlContent = htmlMatch[0];
                            var $tempDiv = $('<div>').html(htmlContent);
                            $(this).replaceWith($tempDiv.contents());
                        }
                    }
                });
                
                // Also look for escaped HTML and convert it
                $('*').contents().filter(function() {
                    return this.nodeType === 3; // Text nodes
                }).each(function() {
                    var text = $(this).text();
                    if (text.indexOf('&lt;div class="customization-actions"') !== -1) {
                        // Decode HTML entities
                        var decodedText = text.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"');
                        
                        // Extract the HTML
                        var htmlMatch = decodedText.match(/<div class="customization-actions"[^>]*>.*?<\/div>/);
                        if (htmlMatch) {
                            var htmlContent = htmlMatch[0];
                            var $tempDiv = $('<div>').html(htmlContent);
                            $(this).replaceWith($tempDiv.contents());
                        }
                    }
                });
            }
            
            // Run cleanup multiple times
            cleanupRawHTML();
            setTimeout(cleanupRawHTML, 500);
            setTimeout(cleanupRawHTML, 1000);
            setTimeout(cleanupRawHTML, 2000);
            
            // Also run cleanup when cart is updated
            $(document.body).on('updated_cart_totals updated_wc_div', function() {
                setTimeout(cleanupRawHTML, 100);
            });
            
            // Specific cleanup for mini-cart and popups
            function cleanupMiniCart() {
                
                // Target common mini-cart selectors
                var selectors = [
                    '.woocommerce-mini-cart',
                    '.mini-cart',
                    '.cart-dropdown',
                    '.cart-popup',
                    '.woocommerce-cart-widget',
                    '.widget_shopping_cart',
                    '.cart-widget'
                ];
                
                selectors.forEach(function(selector) {
                    $(selector).each(function() {
                        var $container = $(this);
                        
                        // Find text nodes containing raw HTML
                        $container.find('*').contents().filter(function() {
                            return this.nodeType === 3; // Text nodes
                        }).each(function() {
                            var text = $(this).text();
                            if (text.indexOf('customization-actions') !== -1 && text.indexOf('<div') !== -1) {
                                // Extract and replace the HTML
                                var htmlMatch = text.match(/<div class="customization-actions"[^>]*>.*?<\/div>/);
                                if (htmlMatch) {
                                    var htmlContent = htmlMatch[0];
                                    var $tempDiv = $('<div>').html(htmlContent);
                                    $(this).replaceWith($tempDiv.contents());
                                }
                            }
                        });
                    });
                });
            }
            
            // Run mini-cart cleanup
            cleanupMiniCart();
            setTimeout(cleanupMiniCart, 500);
            setTimeout(cleanupMiniCart, 1000);
            setTimeout(cleanupMiniCart, 2000);
            
            // Run cleanup when mini-cart is opened/updated
            $(document).on('click', '.cart-trigger, .mini-cart-trigger, .cart-icon', function() {
                setTimeout(cleanupMiniCart, 100);
            });
            
            // Run cleanup on any cart-related events
            $(document.body).on('added_to_cart removed_from_cart updated_cart_totals', function() {
                setTimeout(cleanupMiniCart, 100);
            });
            
            // Continuous cleanup every 2 seconds to catch any missed instances
            setInterval(function() {
                cleanupRawHTML();
                cleanupMiniCart();
            }, 2000);
            
            // Also run cleanup on any DOM mutations
            if (window.MutationObserver) {
                var observer = new MutationObserver(function(mutations) {
                    var shouldCleanup = false;
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList' || mutation.type === 'characterData') {
                            shouldCleanup = true;
                        }
                    });
                    if (shouldCleanup) {
                        setTimeout(function() {
                            cleanupRawHTML();
                            cleanupMiniCart();
                        }, 100);
                    }
                });
                
                observer.observe(document.body, {
                    childList: true,
                    subtree: true,
                    characterData: true
                });
            }
        });
        </script>
        <?php
    }
}
