<?php
/**
 * Admin interface class
 *
 * @package WooCommerce_Product_Customizer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin class
 */
class WC_Product_Customizer_Admin {

    /**
     * Instance
     *
     * @var WC_Product_Customizer_Admin
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return WC_Product_Customizer_Admin
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
        // Always register AJAX handlers
        add_action('wp_ajax_wc_customizer_save_settings', array($this, 'save_settings'));
        add_action('wp_ajax_wc_customizer_bulk_enable_products', array($this, 'bulk_enable_products'));
        add_action('wp_ajax_wc_customizer_bulk_disable_products', array($this, 'bulk_disable_products'));
        
        // Zone management AJAX handlers
        add_action('wp_ajax_wc_customizer_save_zone', array($this, 'ajax_save_zone'));
        add_action('wp_ajax_wc_customizer_delete_zone', array($this, 'ajax_delete_zone'));
        add_action('wp_ajax_wc_customizer_toggle_zone_status', array($this, 'ajax_toggle_zone_status'));
        
        // Customization types management AJAX handlers
        add_action('wp_ajax_wc_customizer_save_customization_type', array($this, 'ajax_save_customization_type'));
        add_action('wp_ajax_wc_customizer_delete_customization_type', array($this, 'ajax_delete_customization_type'));
        add_action('wp_ajax_wc_customizer_toggle_type_status', array($this, 'ajax_toggle_type_status'));
        
        // Category configuration AJAX handlers
        add_action('wp_ajax_wc_customizer_save_category_config', array($this, 'ajax_save_category_config'));
        add_action('wp_ajax_wc_customizer_delete_category_config', array($this, 'ajax_delete_category_config'));
        add_action('wp_ajax_wc_customizer_toggle_config_status', array($this, 'ajax_toggle_config_status'));
        add_action('wp_ajax_wc_customizer_get_category_config', array($this, 'ajax_get_category_config'));
        
        // Pricing management AJAX handlers
        add_action('wp_ajax_wc_customizer_save_pricing_tier', array($this, 'ajax_save_pricing_tier'));
        add_action('wp_ajax_wc_customizer_delete_pricing_tier', array($this, 'ajax_delete_pricing_tier'));
        add_action('wp_ajax_wc_customizer_validate_pricing_ranges', array($this, 'ajax_validate_pricing_ranges'));
        
        // Only add admin-specific hooks when in admin context
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
            add_action('admin_init', array($this, 'register_settings'));
            
            // Add product meta boxes
            add_action('add_meta_boxes', array($this, 'add_product_meta_boxes'));
            add_action('save_post', array($this, 'save_product_meta'));
            
            // Add order meta display
            add_action('woocommerce_admin_order_item_headers', array($this, 'add_order_item_header'));
            add_action('woocommerce_admin_order_item_values', array($this, 'display_order_item_customization'), 10, 3);
            
            // Handle CSV export
            add_action('admin_init', array($this, 'handle_csv_export'));
        }
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Product Customization', 'wc-product-customizer'),
            __('Customization', 'wc-product-customizer'),
            'manage_woocommerce',
            'wc-customization',
            array($this, 'admin_dashboard_page'),
            'dashicons-art',
            56
        );

        add_submenu_page(
            'wc-customization',
            __('Dashboard', 'wc-product-customizer'),
            __('Dashboard', 'wc-product-customizer'),
            'manage_woocommerce',
            'wc-customization',
            array($this, 'admin_dashboard_page')
        );

        add_submenu_page(
            'wc-customization',
            __('Settings', 'wc-product-customizer'),
            __('Settings', 'wc-product-customizer'),
            'manage_woocommerce',
            'wc-customization-settings',
            array($this, 'settings_page')
        );

        add_submenu_page(
            'wc-customization',
            __('Zones', 'wc-product-customizer'),
            __('Zones', 'wc-product-customizer'),
            'manage_woocommerce',
            'wc-customization-zones',
            array($this, 'zones_page')
        );

        add_submenu_page(
            'wc-customization',
            __('Add Zone', 'wc-product-customizer'),
            __('Add Zone', 'wc-product-customizer'),
            'manage_woocommerce',
            'wc-customization-add-zone',
            array($this, 'add_zone_page')
        );

        add_submenu_page(
            'wc-customization',
            __('Customization Types', 'wc-product-customizer'),
            __('Customization Types', 'wc-product-customizer'),
            'manage_woocommerce',
            'wc-customization-types',
            array($this, 'customization_types_page')
        );

        add_submenu_page(
            'wc-customization',
            __('Add Customization Type', 'wc-product-customizer'),
            __('Add Customization Type', 'wc-product-customizer'),
            'manage_woocommerce',
            'wc-customization-add-type',
            array($this, 'add_customization_type_page')
        );

        add_submenu_page(
            'wc-customization',
            __('Category Configurations', 'wc-product-customizer'),
            __('Category Configurations', 'wc-product-customizer'),
            'manage_woocommerce',
            'wc-customization-category-configs',
            array($this, 'category_configs_page')
        );

        add_submenu_page(
            'wc-customization',
            __('Add Category Config', 'wc-product-customizer'),
            __('Add Category Config', 'wc-product-customizer'),
            'manage_woocommerce',
            'wc-customization-add-category-config',
            array($this, 'add_category_config_page')
        );

        add_submenu_page(
            'wc-customization',
            __('Add Pricing Tier', 'wc-product-customizer'),
            __('Add Pricing Tier', 'wc-product-customizer'),
            'manage_woocommerce',
            'wc-customization-add-pricing-tier',
            array($this, 'add_pricing_tier_page')
        );

        add_submenu_page(
            'wc-customization',
            __('Pricing', 'wc-product-customizer'),
            __('Pricing', 'wc-product-customizer'),
            'manage_woocommerce',
            'wc-customization-pricing',
            array($this, 'pricing_page')
        );

        add_submenu_page(
            'wc-customization',
            __('Products', 'wc-product-customizer'),
            __('Products', 'wc-product-customizer'),
            'manage_woocommerce',
            'wc-customization-products',
            array($this, 'products_page')
        );

        add_submenu_page(
            'wc-customization',
            __('Reports', 'wc-product-customizer'),
            __('Reports', 'wc-product-customizer'),
            'manage_woocommerce',
            'wc-customization-reports',
            array($this, 'reports_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wc-customization') === false) {
            return;
        }

        wp_enqueue_style(
            'wc-customizer-admin',
            WC_PRODUCT_CUSTOMIZER_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WC_PRODUCT_CUSTOMIZER_VERSION
        );

        wp_enqueue_script(
            'wc-customizer-admin',
            WC_PRODUCT_CUSTOMIZER_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            WC_PRODUCT_CUSTOMIZER_VERSION,
            true
        );

        // Add Chart.js for reports page
        if (strpos($hook, 'wc-customizer-reports') !== false) {
            wp_enqueue_script(
                'chart-js',
                'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
                array(),
                '3.9.1',
                true
            );
            
            wp_enqueue_script(
                'wc-customizer-reports',
                WC_PRODUCT_CUSTOMIZER_PLUGIN_URL . 'assets/js/reports.js',
                array('jquery', 'chart-js'),
                WC_PRODUCT_CUSTOMIZER_VERSION,
                true
            );
        }

        wp_localize_script('wc-customizer-admin', 'wcCustomizerAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_customizer_admin'),
            'strings' => array(
                'saving' => __('Saving...', 'wc-product-customizer'),
                'saved' => __('Saved!', 'wc-product-customizer'),
                'error' => __('Error occurred', 'wc-product-customizer')
            )
        ));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('wc_customizer_settings', 'wc_product_customizer_settings');
    }

    /**
     * Admin dashboard page
     */
    public function admin_dashboard_page() {
        $stats = $this->get_customization_stats();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Product Customization Dashboard', 'wc-product-customizer'); ?></h1>
            
            <div class="wc-customizer-dashboard">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php esc_html_e('Total Customizations', 'wc-product-customizer'); ?></h3>
                        <div class="stat-number"><?php echo esc_html($stats['total_customizations']); ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3><?php esc_html_e('Total Revenue', 'wc-product-customizer'); ?></h3>
                        <div class="stat-number">£<?php echo esc_html(number_format($stats['total_revenue'], 2)); ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3><?php esc_html_e('Active Products', 'wc-product-customizer'); ?></h3>
                        <div class="stat-number"><?php echo esc_html($stats['active_products']); ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3><?php esc_html_e('Available Zones', 'wc-product-customizer'); ?></h3>
                        <div class="stat-number"><?php echo esc_html($stats['available_zones']); ?></div>
                    </div>
                </div>

                <div class="dashboard-sections">
                    <div class="section">
                        <h2><?php esc_html_e('Popular Zones', 'wc-product-customizer'); ?></h2>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Zone', 'wc-product-customizer'); ?></th>
                                    <th><?php esc_html_e('Usage Count', 'wc-product-customizer'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['popular_zones'] as $zone): ?>
                                <tr>
                                    <td><?php echo esc_html($zone->zone_name); ?></td>
                                    <td><?php echo esc_html($zone->usage_count); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="section">
                        <h2><?php esc_html_e('Popular Methods', 'wc-product-customizer'); ?></h2>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Method', 'wc-product-customizer'); ?></th>
                                    <th><?php esc_html_e('Usage Count', 'wc-product-customizer'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['popular_methods'] as $method): ?>
                                <tr>
                                    <td><?php echo esc_html(ucfirst($method->method)); ?></td>
                                    <td><?php echo esc_html($method->usage_count); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Settings page
     */
    public function settings_page() {
        $settings = get_option('wc_product_customizer_settings', array());
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Customization Settings', 'wc-product-customizer'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('wc_customizer_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Setup Fees', 'wc-product-customizer'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <?php esc_html_e('Text Embroidery:', 'wc-product-customizer'); ?>
                                    <input type="number" step="0.01" name="wc_product_customizer_settings[setup_fees][text_embroidery]" 
                                           value="<?php echo esc_attr($settings['setup_fees']['text_embroidery'] ?? 4.95); ?>" />
                                </label><br>
                                
                                <label>
                                    <?php esc_html_e('Text Print:', 'wc-product-customizer'); ?>
                                    <input type="number" step="0.01" name="wc_product_customizer_settings[setup_fees][text_print]" 
                                           value="<?php echo esc_attr($settings['setup_fees']['text_print'] ?? 4.95); ?>" />
                                </label><br>
                                
                                <label>
                                    <?php esc_html_e('Logo Embroidery:', 'wc-product-customizer'); ?>
                                    <input type="number" step="0.01" name="wc_product_customizer_settings[setup_fees][logo_embroidery]" 
                                           value="<?php echo esc_attr($settings['setup_fees']['logo_embroidery'] ?? 8.95); ?>" />
                                </label><br>
                                
                                <label>
                                    <?php esc_html_e('Logo Print:', 'wc-product-customizer'); ?>
                                    <input type="number" step="0.01" name="wc_product_customizer_settings[setup_fees][logo_print]" 
                                           value="<?php echo esc_attr($settings['setup_fees']['logo_print'] ?? 8.95); ?>" />
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('File Upload Settings', 'wc-product-customizer'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <?php esc_html_e('Maximum File Size (MB):', 'wc-product-customizer'); ?>
                                    <input type="number" name="wc_product_customizer_settings[file_upload][max_size_mb]" 
                                           value="<?php echo esc_attr(($settings['file_upload']['max_size'] ?? 8388608) / 1048576); ?>" />
                                </label><br>
                                
                                <label>
                                    <?php esc_html_e('Allowed File Types:', 'wc-product-customizer'); ?>
                                    <input type="text" name="wc_product_customizer_settings[file_upload][allowed_types]" 
                                           value="<?php 
                                           $allowed_types = $settings['file_upload']['allowed_types'] ?? array('jpg', 'jpeg', 'png', 'pdf', 'ai', 'eps');
                                           if (is_array($allowed_types)) {
                                               echo esc_attr(implode(',', $allowed_types));
                                           } else {
                                               echo esc_attr($allowed_types);
                                           }
                                           ?>" 
                                           placeholder="jpg,jpeg,png,pdf,ai,eps" />
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <!-- Developer Credit Footer -->
            <div class="developer-credit" style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: center; border-left: 4px solid #007cba;">
                <p style="margin: 0; color: #666; font-size: 14px;">
                    <?php esc_html_e('Plugin designed and developed by', 'wc-product-customizer'); ?> 
                    <a href="https://shakilahamed.com" target="_blank" style="color: #007cba; text-decoration: none; font-weight: 600;">
                        Shakil Ahamed
                    </a>
                </p>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 12px;">
                    <a href="https://shakilahamed.com" target="_blank" style="color: #999; text-decoration: none;">
                        https://shakilahamed.com
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Zones page
     */
    public function zones_page() {
        $db = WC_Product_Customizer_Database::get_instance();
        $zones = $db->get_all_customization_zones();
        
        // Handle actions
        if (isset($_GET['action']) && isset($_GET['zone_id'])) {
            $action = sanitize_text_field($_GET['action']);
            $zone_id = intval($_GET['zone_id']);
            
            switch ($action) {
                case 'edit':
                    $this->edit_zone_page($zone_id);
                    return;
                case 'delete':
                    $this->handle_delete_zone($zone_id);
                    break;
                case 'toggle':
                    $this->handle_toggle_zone_status($zone_id);
                    break;
            }
        }
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php esc_html_e('Customization Zones', 'wc-product-customizer'); ?>
            </h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-add-zone')); ?>" class="page-title-action">
                <?php esc_html_e('Add New Zone', 'wc-product-customizer'); ?>
            </a>
            <hr class="wp-header-end">
            
            <!-- Status Filter Buttons -->
            <div class="zones-filter-tabs" style="margin: 20px 0;">
                <button type="button" class="button filter-btn active" data-status="all">
                    <?php esc_html_e('All', 'wc-product-customizer'); ?> 
                    <span class="count">(<?php echo count($zones); ?>)</span>
                </button>
                <button type="button" class="button filter-btn" data-status="active">
                    <?php esc_html_e('Active', 'wc-product-customizer'); ?> 
                    <span class="count">(<?php echo count(array_filter($zones, function($zone) { return $zone->active; })); ?>)</span>
                </button>
                <button type="button" class="button filter-btn" data-status="inactive">
                    <?php esc_html_e('Inactive', 'wc-product-customizer'); ?> 
                    <span class="count">(<?php echo count(array_filter($zones, function($zone) { return !$zone->active; })); ?>)</span>
                </button>
            </div>
            
            <div class="zones-list-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-thumbnail"><?php esc_html_e('Thumbnail', 'wc-product-customizer'); ?></th>
                            <th scope="col" class="manage-column column-name"><?php esc_html_e('Zone Name', 'wc-product-customizer'); ?></th>
                            <th scope="col" class="manage-column column-group"><?php esc_html_e('Group', 'wc-product-customizer'); ?></th>
                            <th scope="col" class="manage-column column-methods"><?php esc_html_e('Methods', 'wc-product-customizer'); ?></th>
                            <th scope="col" class="manage-column column-product-types"><?php esc_html_e('Product Types', 'wc-product-customizer'); ?></th>
                            <th scope="col" class="manage-column column-charge"><?php esc_html_e('Charge', 'wc-product-customizer'); ?></th>
                            <th scope="col" class="manage-column column-status"><?php esc_html_e('Status', 'wc-product-customizer'); ?></th>
                            <th scope="col" class="manage-column column-actions"><?php esc_html_e('Actions', 'wc-product-customizer'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($zones)): ?>
                        <tr>
                            <td colspan="8" class="no-zones">
                                <?php esc_html_e('No zones found. Add your first zone to get started.', 'wc-product-customizer'); ?>
                            </td>
                        </tr>
                        <?php else: ?>
                <?php foreach ($zones as $zone): ?>
                        <tr class="zone-row" data-status="<?php echo ($zone->active ?? 0) ? 'active' : 'inactive'; ?>">
                            <td class="column-thumbnail">
                                <?php if (!empty($zone->thumbnail_url)): ?>
                                    <img src="<?php echo esc_url($zone->thumbnail_url); ?>" alt="<?php echo esc_attr($zone->name ?? ''); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                    <div class="no-thumbnail" style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #999;">
                                        <span class="dashicons dashicons-format-image"></span>
                </div>
                                <?php endif; ?>
                            </td>
                            <td class="column-name">
                                <strong><?php echo esc_html($zone->name ?? 'Unnamed Zone'); ?></strong>
                                <?php if (!empty($zone->description)): ?>
                                    <br><small style="color: #666;"><?php echo esc_html($zone->description ?? ''); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="column-group"><?php echo esc_html($zone->zone_group ?? ''); ?></td>
                            <td class="column-methods">
                                <?php 
                                $methods = explode(',', $zone->methods_available ?? '');
                                foreach ($methods as $method): 
                                    $method = trim($method ?? '');
                                    if (!empty($method)) {
                                        $icon = $method === 'embroidery' ? '🧵' : '🔥';
                                        echo '<span class="method-badge method-' . esc_attr($method) . '">' . $icon . ' ' . esc_html(ucfirst($method)) . '</span> ';
                                    }
                                endforeach; 
                                ?>
                            </td>
                            <td class="column-product-types">
                                <?php echo esc_html($zone->product_types ?? __('All Products', 'wc-product-customizer')); ?>
                            </td>
                            <td class="column-charge">£<?php echo esc_html($zone->zone_charge ?? '0.00'); ?></td>
                            <td class="column-status">
                                <span class="status-badge status-<?php echo ($zone->active ?? 0) ? 'active' : 'inactive'; ?>">
                                    <?php echo ($zone->active ?? 0) ? __('Active', 'wc-product-customizer') : __('Inactive', 'wc-product-customizer'); ?>
                                </span>
                            </td>
                            <td class="column-actions">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-zones&action=edit&zone_id=' . ($zone->id ?? ''))); ?>" class="button button-small edit-zone-btn" data-zone-id="<?php echo esc_attr($zone->id ?? ''); ?>">
                                    <?php esc_html_e('Edit', 'wc-product-customizer'); ?>
                                </a>
                                <button type="button" class="button button-small toggle-zone-status-btn" data-zone-id="<?php echo esc_attr($zone->id ?? ''); ?>" data-status="<?php echo esc_attr($zone->active ?? 0); ?>">
                                    <?php echo ($zone->active ?? 0) ? __('Deactivate', 'wc-product-customizer') : __('Activate', 'wc-product-customizer'); ?>
                                </button>
                                <button type="button" class="button button-small button-link-delete delete-zone-btn" data-zone-id="<?php echo esc_attr($zone->id ?? ''); ?>">
                                    <?php esc_html_e('Delete', 'wc-product-customizer'); ?>
                                </button>
                            </td>
                        </tr>
                <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Developer Credit Footer -->
            <div class="developer-credit" style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: center; border-left: 4px solid #007cba;">
                <p style="margin: 0; color: #666; font-size: 14px;">
                    <?php esc_html_e('Plugin designed and developed by', 'wc-product-customizer'); ?> 
                    <a href="https://shakilahamed.com" target="_blank" style="color: #007cba; text-decoration: none; font-weight: 600;">
                        Shakil Ahamed
                    </a>
                </p>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 12px;">
                    <a href="https://shakilahamed.com" target="_blank" style="color: #999; text-decoration: none;">
                        https://shakilahamed.com
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Add zone page
     */
    public function add_zone_page() {
        $this->render_zone_form();
    }

    /**
     * Edit zone page
     */
    public function edit_zone_page($zone_id) {
        $db = WC_Product_Customizer_Database::get_instance();
        $zone = $db->get_zone_by_id($zone_id);
        
        if (!$zone) {
            wp_die(__('Zone not found.', 'wc-product-customizer'));
        }
        
        $this->render_zone_form($zone);
    }

    /**
     * Render zone form (add/edit)
     */
    private function render_zone_form($zone = null) {
        $is_edit = !is_null($zone);
        $db = WC_Product_Customizer_Database::get_instance();
        $categories = $db->get_product_categories();
        ?>
        <div class="wrap">
            <h1>
                <?php echo $is_edit ? esc_html__('Edit Zone', 'wc-product-customizer') : esc_html__('Add New Zone', 'wc-product-customizer'); ?>
            </h1>
            
            <form id="zone-form" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('wc_customizer_zone_form', 'zone_nonce'); ?>
                <input type="hidden" name="zone_id" value="<?php echo $is_edit ? esc_attr($zone->id) : ''; ?>">
                
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="zone_name"><?php esc_html_e('Zone Name', 'wc-product-customizer'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input type="text" id="zone_name" name="zone_name" value="<?php echo $is_edit ? esc_attr($zone->name ?? '') : ''; ?>" class="regular-text" required>
                                <p class="description"><?php esc_html_e('The name that will be displayed to customers in the frontend.', 'wc-product-customizer'); ?></p>
                            </td>
                            </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="zone_group"><?php esc_html_e('Zone Group', 'wc-product-customizer'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="zone_group" name="zone_group" value="<?php echo $is_edit ? esc_attr($zone->zone_group ?? '') : ''; ?>" class="regular-text" placeholder="<?php esc_attr_e('e.g., Front, Back, Sleeves', 'wc-product-customizer'); ?>">
                                <p class="description"><?php esc_html_e('Group zones together for better organization.', 'wc-product-customizer'); ?></p>
                            </td>
                            </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="zone_description"><?php esc_html_e('Description', 'wc-product-customizer'); ?></label>
                            </th>
                            <td>
                                <textarea id="zone_description" name="zone_description" rows="3" class="large-text"><?php echo $is_edit ? esc_textarea($zone->description ?? '') : ''; ?></textarea>
                                <p class="description"><?php esc_html_e('Optional description for this zone.', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="zone_charge"><?php esc_html_e('Zone Charge', 'wc-product-customizer'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="zone_charge" name="zone_charge" value="<?php echo $is_edit ? esc_attr($zone->zone_charge ?? '0.00') : '0.00'; ?>" step="0.01" min="0" class="small-text">
                                <span class="description"><?php esc_html_e('Additional charge for this zone (optional).', 'wc-product-customizer'); ?></span>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label><?php esc_html_e('Thumbnail Image', 'wc-product-customizer'); ?></label>
                            </th>
                            <td>
                                <div class="thumbnail-upload-container">
                                    <div class="thumbnail-preview" id="thumbnail-preview">
                                        <?php if ($is_edit && !empty($zone->thumbnail_url)): ?>
                                            <img src="<?php echo esc_url($zone->thumbnail_url); ?>" alt="<?php echo esc_attr($zone->name ?? ''); ?>" style="max-width: 150px; max-height: 150px;">
                                            <input type="hidden" name="current_thumbnail_url" value="<?php echo esc_url($zone->thumbnail_url); ?>">
                                        <?php else: ?>
                                            <div class="no-thumbnail" style="width: 150px; height: 150px; background: #f0f0f0; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #999;">
                                                <span class="dashicons dashicons-format-image" style="font-size: 48px;"></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="button" id="upload-thumbnail-btn">
                                        <?php esc_html_e('Choose Image', 'wc-product-customizer'); ?>
                                    </button>
                                    <button type="button" class="button" id="remove-thumbnail-btn" style="display: none;">
                                        <?php esc_html_e('Remove Image', 'wc-product-customizer'); ?>
                                    </button>
                                    <input type="hidden" name="thumbnail_url" id="thumbnail_url" value="<?php echo $is_edit ? esc_attr($zone->thumbnail_url ?? '') : ''; ?>">
                                    <p class="description"><?php esc_html_e('Upload a thumbnail image for this zone. Recommended size: 150x150px.', 'wc-product-customizer'); ?></p>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label><?php esc_html_e('Methods Available', 'wc-product-customizer'); ?></label>
                            </th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="methods[]" value="embroidery" <?php echo $is_edit && strpos($zone->methods_available ?? '', 'embroidery') !== false ? 'checked' : ''; ?>>
                                        🧵 <?php esc_html_e('Embroidery', 'wc-product-customizer'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="methods[]" value="print" <?php echo $is_edit && strpos($zone->methods_available ?? '', 'print') !== false ? 'checked' : ''; ?>>
                                        🔥 <?php esc_html_e('Print', 'wc-product-customizer'); ?>
                                    </label>
                                </fieldset>
                                <p class="description"><?php esc_html_e('Select which customization methods are available for this zone.', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="product_types"><?php esc_html_e('Product Types (Free Text)', 'wc-product-customizer'); ?></label>
                            </th>
                            <td>
                                <textarea id="product_types" name="product_types" rows="3" class="large-text" placeholder="<?php esc_attr_e('e.g., T-shirts, Hoodies, Caps, Beanies', 'wc-product-customizer'); ?>"><?php echo $is_edit ? esc_textarea($zone->product_types ?? '') : ''; ?></textarea>
                                <p class="description"><?php esc_html_e('Enter product types that can use this zone (comma-separated).', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="product_categories"><?php esc_html_e('Product Categories', 'wc-product-customizer'); ?></label>
                            </th>
                            <td>
                                <select id="product_categories" name="product_categories[]" multiple class="large-text" style="height: 100px;">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo esc_attr($category->term_id); ?>" 
                                                <?php echo $is_edit && strpos($zone->product_types ?? '', $category->name) !== false ? 'selected' : ''; ?>>
                                            <?php echo esc_html($category->name); ?>
                                        </option>
                            <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e('Select WooCommerce product categories that can use this zone.', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="zone_active"><?php esc_html_e('Status', 'wc-product-customizer'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="zone_active" name="zone_active" value="1" <?php echo $is_edit ? ($zone->active ? 'checked' : '') : 'checked'; ?>>
                                    <?php esc_html_e('Active', 'wc-product-customizer'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Inactive zones will not be available for selection in the frontend.', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary" id="save-zone-btn">
                        <?php echo $is_edit ? esc_html__('Update Zone', 'wc-product-customizer') : esc_html__('Add Zone', 'wc-product-customizer'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-zones')); ?>" class="button">
                        <?php esc_html_e('Cancel', 'wc-product-customizer'); ?>
                    </a>
                </p>
            </form>
                </div>
        <?php
    }

    /**
     * Handle delete zone
     */
    private function handle_delete_zone($zone_id) {
        $db = WC_Product_Customizer_Database::get_instance();
        
        // Check if zone is used in orders
        if ($db->check_zone_usage($zone_id)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Cannot delete zone: It is being used in existing orders.', 'wc-product-customizer') . '</p></div>';
            });
            return;
        }
        
        if ($db->delete_zone($zone_id)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>' . esc_html__('Zone deleted successfully.', 'wc-product-customizer') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Error deleting zone.', 'wc-product-customizer') . '</p></div>';
            });
        }
    }

    /**
     * Handle toggle zone status
     */
    private function handle_toggle_zone_status($zone_id) {
        $db = WC_Product_Customizer_Database::get_instance();
        $zone = $db->get_zone_by_id($zone_id);
        
        if ($zone) {
            $new_status = $zone->active ? 0 : 1;
            if ($db->update_zone_status($zone_id, $new_status)) {
                $status_text = $new_status ? __('activated', 'wc-product-customizer') : __('deactivated', 'wc-product-customizer');
                add_action('admin_notices', function() use ($status_text) {
                    echo '<div class="notice notice-success"><p>' . sprintf(esc_html__('Zone %s successfully.', 'wc-product-customizer'), $status_text) . '</p></div>';
                });
            }
        }
    }

    /**
     * Pricing page
     */
    public function pricing_page() {
        $db = WC_Product_Customizer_Database::get_instance();
        $customization_types = $db->get_all_customization_types(true); // Only active types
        
        // Handle form submissions
        if (isset($_POST['pricing_tier_nonce']) && wp_verify_nonce($_POST['pricing_tier_nonce'], 'wc_customizer_pricing_tier_form')) {
            $this->handle_pricing_tier_form_submission();
            return;
        }
        
        // Handle actions
        if (isset($_GET['action']) && isset($_GET['tier_id'])) {
            $action = sanitize_text_field($_GET['action']);
            $tier_id = intval($_GET['tier_id']);
            
            switch ($action) {
                case 'edit':
                    $this->edit_pricing_tier_page($tier_id);
                    return;
                case 'delete':
                    $this->handle_delete_pricing_tier($tier_id);
                    break;
            }
        }
        ?>
        <div class="wrap">
            <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Pricing tier updated successfully.', 'wc-product-customizer'); ?></p>
            </div>
            <?php endif; ?>
            
            <h1 class="wp-heading-inline">
                <?php esc_html_e('Pricing Configuration', 'wc-product-customizer'); ?>
            </h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-types')); ?>" class="page-title-action">
                <?php esc_html_e('Manage Customization Types', 'wc-product-customizer'); ?>
            </a>
            <hr class="wp-header-end">
            
            <div class="pricing-tables">
                <?php if (empty($customization_types)): ?>
                <div class="notice notice-info">
                    <p><?php esc_html_e('No customization types found. Please add some customization types first.', 'wc-product-customizer'); ?></p>
                </div>
                <?php else: ?>
                <?php foreach ($customization_types as $type): ?>
                <?php
                $pricing_tiers = $db->get_pricing_tiers_by_type_id($type->id);
                ?>
                <div class="pricing-section">
                    <div class="pricing-section-header">
                        <h2><?php echo esc_html($type->icon . ' ' . $type->name . ' ' . __('Pricing', 'wc-product-customizer')); ?></h2>
                        <div class="pricing-section-actions">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-add-pricing-tier&type_id=' . $type->id)); ?>" class="button button-primary">
                                <?php printf(__('Add %s Tier', 'wc-product-customizer'), $type->name); ?>
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-types&action=manage-pricing&type_id=' . $type->id)); ?>" class="button">
                                <?php esc_html_e('Manage Pricing', 'wc-product-customizer'); ?>
                            </a>
                        </div>
                    </div>
                    <table class="wp-list-table widefat fixed striped pricing-tiers-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Quantity Range', 'wc-product-customizer'); ?></th>
                                <th><?php esc_html_e('Price per Item', 'wc-product-customizer'); ?></th>
                                <th><?php esc_html_e('Actions', 'wc-product-customizer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pricing_tiers)): ?>
                            <tr>
                                <td colspan="3" class="no-tiers">
                                    <?php printf(__('No pricing tiers found for %s. Add your first tier to get started.', 'wc-product-customizer'), $type->name); ?>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($pricing_tiers as $tier): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($tier->min_quantity . ' - ' . ($tier->max_quantity == 999999 ? '∞' : $tier->max_quantity)); ?></strong>
                                </td>
                                <td>
                                    <span class="price-display">£<?php echo esc_html($tier->price_per_item); ?></span>
                                </td>
                                <td class="pricing-actions">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-pricing&action=edit&tier_id=' . $tier->id)); ?>" class="button button-small">
                                        <?php esc_html_e('Edit', 'wc-product-customizer'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-pricing&action=delete&tier_id=' . $tier->id)); ?>" 
                                       class="button button-small button-link-delete delete-pricing-tier-btn" 
                                       data-tier-id="<?php echo esc_attr($tier->id); ?>"
                                       onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this pricing tier? This action cannot be undone.', 'wc-product-customizer'); ?>')">
                                        <?php esc_html_e('Delete', 'wc-product-customizer'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Developer Credit Footer -->
            <div class="developer-credit" style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: center; border-left: 4px solid #007cba;">
                <p style="margin: 0; color: #666; font-size: 14px;">
                    <?php esc_html_e('Plugin designed and developed by', 'wc-product-customizer'); ?> 
                    <a href="https://shakilahamed.com" target="_blank" style="color: #007cba; text-decoration: none; font-weight: 600;">
                        Shakil Ahamed
                    </a>
                </p>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 12px;">
                    <a href="https://shakilahamed.com" target="_blank" style="color: #999; text-decoration: none;">
                        https://shakilahamed.com
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Products page
     */
    public function products_page() {
        $db = WC_Product_Customizer_Database::get_instance();
        $configs = $db->get_all_category_configs();
        $categories = $db->get_product_categories();
        ?>
        <div class="wrap">
            <h1>
                <?php esc_html_e('Product Customization Settings', 'wc-product-customizer'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-add-category-config')); ?>" class="page-title-action">
                    <?php esc_html_e('Add Category Config', 'wc-product-customizer'); ?>
                </a>
            </h1>
            
            <div class="category-configs-overview">
                <h2><?php esc_html_e('Category Configurations', 'wc-product-customizer'); ?></h2>
                <p><?php esc_html_e('Configure customization settings for different product categories. Products will automatically inherit from their primary category.', 'wc-product-customizer'); ?></p>
                
                <?php if (empty($configs)): ?>
                    <div class="notice notice-info">
                        <p>
                            <?php esc_html_e('No category configurations found.', 'wc-product-customizer'); ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-add-category-config')); ?>">
                                <?php esc_html_e('Create your first configuration', 'wc-product-customizer'); ?>
                            </a>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="configs-grid">
                        <?php foreach ($configs as $config): ?>
                            <?php
                            $zones = maybe_unserialize($config->available_zones);
                            $types = maybe_unserialize($config->available_types);
                            $zones_count = is_array($zones) ? count($zones) : 0;
                            $types_count = is_array($types) ? count($types) : 0;
                            ?>
                            <div class="config-card">
                                <div class="config-header">
                                    <h3><?php echo esc_html($config->config_name); ?></h3>
                                    <span class="config-category"><?php echo esc_html($config->category_name ?: __('Unknown Category', 'wc-product-customizer')); ?></span>
                                </div>
                                
                                <?php if ($config->description): ?>
                                    <p class="config-description"><?php echo esc_html($config->description); ?></p>
                                <?php endif; ?>
                                
                                <div class="config-stats">
                                    <div class="stat">
                                        <span class="stat-number"><?php echo esc_html($zones_count); ?></span>
                                        <span class="stat-label"><?php esc_html_e('Zones', 'wc-product-customizer'); ?></span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-number"><?php echo esc_html($types_count); ?></span>
                                        <span class="stat-label"><?php esc_html_e('Types', 'wc-product-customizer'); ?></span>
                                    </div>
                                    <div class="stat">
                                        <?php if ($config->custom_pricing_enabled): ?>
                                            <span class="stat-badge enabled"><?php esc_html_e('Custom Pricing', 'wc-product-customizer'); ?></span>
                                        <?php else: ?>
                                            <span class="stat-badge disabled"><?php esc_html_e('Global Pricing', 'wc-product-customizer'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="config-actions">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-add-category-config&config_id=' . $config->id)); ?>" class="button button-small">
                                        <?php esc_html_e('Edit', 'wc-product-customizer'); ?>
                                    </a>
                                    <button type="button" class="button button-small toggle-config-status-btn" data-config-id="<?php echo esc_attr($config->id); ?>">
                                        <?php echo $config->enabled ? esc_html__('Deactivate', 'wc-product-customizer') : esc_html__('Activate', 'wc-product-customizer'); ?>
                </button>
                </div>
                                
                                <div class="config-status">
                                    <?php if ($config->enabled): ?>
                                        <span class="status-enabled"><?php esc_html_e('Active', 'wc-product-customizer'); ?></span>
                                    <?php else: ?>
                                        <span class="status-disabled"><?php esc_html_e('Inactive', 'wc-product-customizer'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="product-assignment-info">
                <h2><?php esc_html_e('Product Assignment', 'wc-product-customizer'); ?></h2>
                <p><?php esc_html_e('Products automatically inherit customization settings from their primary category. You can override this in individual product edit pages.', 'wc-product-customizer'); ?></p>
                
                <div class="info-box">
                    <h4><?php esc_html_e('How it works:', 'wc-product-customizer'); ?></h4>
                    <ol>
                        <li><?php esc_html_e('Create category configurations above', 'wc-product-customizer'); ?></li>
                        <li><?php esc_html_e('Assign products to categories in WooCommerce', 'wc-product-customizer'); ?></li>
                        <li><?php esc_html_e('Products automatically use their category\'s configuration', 'wc-product-customizer'); ?></li>
                        <li><?php esc_html_e('Override individual products in the product edit page if needed', 'wc-product-customizer'); ?></li>
                    </ol>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Reports page
     */
    public function reports_page() {
        $stats = $this->get_customization_stats();
        $date_range = $this->get_date_range();
        $recent_customizations = $this->get_recent_customizations();
        $revenue_data = $this->get_revenue_data($date_range);
        $zone_usage_data = $this->get_zone_usage_data($date_range);
        $method_usage_data = $this->get_method_usage_data($date_range);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Customization Reports', 'wc-product-customizer'); ?></h1>
            
            <!-- Date Range Filter -->
            <div class="reports-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="wc-customizer-reports">
                    <label for="date_from"><?php esc_html_e('From:', 'wc-product-customizer'); ?></label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($date_range['from']); ?>">
                    <label for="date_to"><?php esc_html_e('To:', 'wc-product-customizer'); ?></label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($date_range['to']); ?>">
                    <button type="submit" class="button button-primary"><?php esc_html_e('Filter', 'wc-product-customizer'); ?></button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customizer-reports')); ?>" class="button"><?php esc_html_e('Reset', 'wc-product-customizer'); ?></a>
                </form>
            </div>

            <!-- Key Metrics Cards -->
            <div class="reports-dashboard">
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-icon">📊</div>
                        <div class="metric-content">
                            <h3><?php echo esc_html($stats['total_customizations']); ?></h3>
                            <p><?php esc_html_e('Total Customizations', 'wc-product-customizer'); ?></p>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon">💰</div>
                        <div class="metric-content">
                            <h3>£<?php echo esc_html(number_format($stats['total_revenue'], 2)); ?></h3>
                            <p><?php esc_html_e('Total Revenue', 'wc-product-customizer'); ?></p>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon">🎯</div>
                        <div class="metric-content">
                            <h3><?php echo esc_html($stats['active_products']); ?></h3>
                            <p><?php esc_html_e('Active Products', 'wc-product-customizer'); ?></p>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon">📍</div>
                        <div class="metric-content">
                            <h3><?php echo esc_html($stats['available_zones']); ?></h3>
                            <p><?php esc_html_e('Available Zones', 'wc-product-customizer'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="charts-grid">
                    <div class="chart-container">
                        <h3><?php esc_html_e('Revenue Over Time', 'wc-product-customizer'); ?></h3>
                        <canvas id="revenueChart" width="400" height="200"></canvas>
                    </div>
                    <div class="chart-container">
                        <h3><?php esc_html_e('Zone Usage Distribution', 'wc-product-customizer'); ?></h3>
                        <canvas id="zoneChart" width="400" height="200"></canvas>
                    </div>
                    <div class="chart-container">
                        <h3><?php esc_html_e('Method Usage Distribution', 'wc-product-customizer'); ?></h3>
                        <canvas id="methodChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- Popular Zones and Methods -->
                <div class="tables-grid">
                    <div class="table-container">
                        <h3><?php esc_html_e('Most Popular Zones', 'wc-product-customizer'); ?></h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Zone', 'wc-product-customizer'); ?></th>
                                    <th><?php esc_html_e('Usage Count', 'wc-product-customizer'); ?></th>
                                    <th><?php esc_html_e('Percentage', 'wc-product-customizer'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['popular_zones'] as $zone): ?>
                                <tr>
                                    <td><?php echo esc_html($zone->zone_name); ?></td>
                                    <td><?php echo esc_html($zone->usage_count); ?></td>
                                    <td><?php echo esc_html(round(($zone->usage_count / max($stats['total_customizations'], 1)) * 100, 1)); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="table-container">
                        <h3><?php esc_html_e('Most Popular Methods', 'wc-product-customizer'); ?></h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Method', 'wc-product-customizer'); ?></th>
                                    <th><?php esc_html_e('Usage Count', 'wc-product-customizer'); ?></th>
                                    <th><?php esc_html_e('Percentage', 'wc-product-customizer'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['popular_methods'] as $method): ?>
                                <tr>
                                    <td><?php echo esc_html(ucfirst($method->method)); ?></td>
                                    <td><?php echo esc_html($method->usage_count); ?></td>
                                    <td><?php echo esc_html(round(($method->usage_count / max($stats['total_customizations'], 1)) * 100, 1)); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Customizations -->
                <div class="recent-customizations">
                    <h3><?php esc_html_e('Recent Customizations', 'wc-product-customizer'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Order ID', 'wc-product-customizer'); ?></th>
                                <th><?php esc_html_e('Product', 'wc-product-customizer'); ?></th>
                                <th><?php esc_html_e('Zones', 'wc-product-customizer'); ?></th>
                                <th><?php esc_html_e('Method', 'wc-product-customizer'); ?></th>
                                <th><?php esc_html_e('Total Cost', 'wc-product-customizer'); ?></th>
                                <th><?php esc_html_e('Date', 'wc-product-customizer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_customizations as $customization): ?>
                            <tr>
                                <td>#<?php echo esc_html($customization->order_id); ?></td>
                                <td><?php echo esc_html($customization->product_name); ?></td>
                                <td><?php echo esc_html($customization->zones); ?></td>
                                <td><?php echo esc_html(ucfirst($customization->method)); ?></td>
                                <td>£<?php echo esc_html(number_format($customization->total_cost, 2)); ?></td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($customization->created_at))); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Export Section -->
                <div class="export-section">
                    <h3><?php esc_html_e('Export Data', 'wc-product-customizer'); ?></h3>
                    <p><?php esc_html_e('Download your customization data in CSV format for further analysis.', 'wc-product-customizer'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customizer-reports&action=export_csv&date_from=' . $date_range['from'] . '&date_to=' . $date_range['to'])); ?>" class="button button-primary">
                        <?php esc_html_e('Export CSV', 'wc-product-customizer'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Developer Credit Footer -->
            <div class="developer-credit" style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: center; border-left: 4px solid #007cba;">
                <p style="margin: 0; color: #666; font-size: 14px;">
                    <?php esc_html_e('Plugin designed and developed by', 'wc-product-customizer'); ?> 
                    <a href="https://shakilahamed.com" target="_blank" style="color: #007cba; text-decoration: none; font-weight: 600;">
                        Shakil Ahamed
                    </a>
                </p>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 12px;">
                    <a href="https://shakilahamed.com" target="_blank" style="color: #999; text-decoration: none;">
                        https://shakilahamed.com
                    </a>
                </p>
            </div>
        </div>

        <script>
        // Chart.js data
        const revenueData = <?php echo json_encode($revenue_data); ?>;
        const zoneData = <?php echo json_encode($zone_usage_data); ?>;
        const methodData = <?php echo json_encode($method_usage_data); ?>;
        </script>
        <?php
    }

    /**
     * Get customization statistics
     *
     * @return array
     */
    private function get_customization_stats() {
        global $wpdb;

        $orders_table = $wpdb->prefix . 'wc_customization_orders';
        $zones_table = $wpdb->prefix . 'wc_customization_zones';
        $products_table = $wpdb->prefix . 'wc_customization_products';

        $stats = array(
            'total_customizations' => $wpdb->get_var("SELECT COUNT(*) FROM $orders_table"),
            'total_revenue' => $wpdb->get_var("SELECT SUM(total_cost) FROM $orders_table") ?: 0,
            'active_products' => $wpdb->get_var("SELECT COUNT(*) FROM $products_table WHERE enabled = 1"),
            'available_zones' => $wpdb->get_var("SELECT COUNT(*) FROM $zones_table WHERE active = 1"),
            'popular_zones' => $wpdb->get_results("
                SELECT z.name as zone_name, COUNT(o.id) as usage_count 
                FROM $zones_table z 
                LEFT JOIN $orders_table o ON FIND_IN_SET(z.id, o.zone_ids) 
                WHERE z.active = 1 
                GROUP BY z.id 
                ORDER BY usage_count DESC 
                LIMIT 5
            "),
            'popular_methods' => $wpdb->get_results("
                SELECT method, COUNT(*) as usage_count 
                FROM $orders_table 
                GROUP BY method 
                ORDER BY usage_count DESC 
                LIMIT 5
            ")
        );

        return $stats;
    }

    /**
     * Get date range for reports
     *
     * @return array
     */
    private function get_date_range() {
        $from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-01');
        $to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');
        
        return array(
            'from' => $from,
            'to' => $to
        );
    }

    /**
     * Get recent customizations
     *
     * @return array
     */
    private function get_recent_customizations() {
        global $wpdb;
        
        $orders_table = $wpdb->prefix . 'wc_customization_orders';
        
        return $wpdb->get_results("
            SELECT 
                co.order_id,
                p.post_title as product_name,
                co.zone_ids as zones,
                co.method,
                co.total_cost,
                co.created_at
            FROM $orders_table co
            LEFT JOIN {$wpdb->posts} p ON co.product_id = p.ID
            ORDER BY co.created_at DESC
            LIMIT 10
        ");
    }

    /**
     * Get revenue data for charts
     *
     * @param array $date_range
     * @return array
     */
    private function get_revenue_data($date_range) {
        global $wpdb;
        
        $orders_table = $wpdb->prefix . 'wc_customization_orders';
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(created_at) as date,
                SUM(total_cost) as revenue,
                COUNT(*) as count
            FROM $orders_table
            WHERE DATE(created_at) BETWEEN %s AND %s
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", $date_range['from'], $date_range['to']));
        
        $data = array(
            'labels' => array(),
            'revenue' => array(),
            'count' => array()
        );
        
        foreach ($results as $result) {
            $data['labels'][] = date('M j', strtotime($result->date));
            $data['revenue'][] = floatval($result->revenue);
            $data['count'][] = intval($result->count);
        }
        
        return $data;
    }

    /**
     * Get zone usage data for charts
     *
     * @param array $date_range
     * @return array
     */
    private function get_zone_usage_data($date_range) {
        global $wpdb;
        
        $zones_table = $wpdb->prefix . 'wc_customization_zones';
        $orders_table = $wpdb->prefix . 'wc_customization_orders';
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                z.name as zone_name,
                COUNT(co.id) as usage_count
            FROM $zones_table z
            LEFT JOIN $orders_table co ON FIND_IN_SET(z.id, co.zones) 
                AND DATE(co.created_at) BETWEEN %s AND %s
            WHERE z.active = 1
            GROUP BY z.id
            ORDER BY usage_count DESC
            LIMIT 10
        ", $date_range['from'], $date_range['to']));
        
        $data = array(
            'labels' => array(),
            'data' => array()
        );
        
        foreach ($results as $result) {
            $data['labels'][] = $result->zone_name;
            $data['data'][] = intval($result->usage_count);
        }
        
        return $data;
    }

    /**
     * Get method usage data for charts
     *
     * @param array $date_range
     * @return array
     */
    private function get_method_usage_data($date_range) {
        global $wpdb;
        
        $orders_table = $wpdb->prefix . 'wc_customization_orders';
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                method,
                COUNT(*) as usage_count
            FROM $orders_table
            WHERE DATE(created_at) BETWEEN %s AND %s
            GROUP BY method
            ORDER BY usage_count DESC
        ", $date_range['from'], $date_range['to']));
        
        $data = array(
            'labels' => array(),
            'data' => array()
        );
        
        foreach ($results as $result) {
            $data['labels'][] = ucfirst($result->method);
            $data['data'][] = intval($result->usage_count);
        }
        
        return $data;
    }

    /**
     * Handle CSV export
     */
    public function handle_csv_export() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'export_csv') {
            return;
        }
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wc-product-customizer'));
        }
        
        $date_range = $this->get_date_range();
        $customizations = $this->get_export_data($date_range);
        
        $filename = 'customization-reports-' . $date_range['from'] . '-to-' . $date_range['to'] . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            'Order ID',
            'Product Name',
            'Zones',
            'Method',
            'Content Type',
            'Setup Fee',
            'Application Fee',
            'Total Cost',
            'Created Date'
        ));
        
        // CSV data
        foreach ($customizations as $customization) {
            fputcsv($output, array(
                $customization->order_id,
                $customization->product_name,
                $customization->zones,
                ucfirst($customization->method),
                ucfirst($customization->content_type),
                $customization->setup_fee,
                $customization->application_fee,
                $customization->total_cost,
                $customization->created_at
            ));
        }
        
        fclose($output);
        exit;
    }

    /**
     * Get data for CSV export
     *
     * @param array $date_range
     * @return array
     */
    private function get_export_data($date_range) {
        global $wpdb;
        
        $orders_table = $wpdb->prefix . 'wc_customization_orders';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                co.order_id,
                p.post_title as product_name,
                co.zone_ids as zones,
                co.method,
                co.content_type,
                co.setup_fee,
                co.application_fee,
                co.total_cost,
                co.created_at
            FROM $orders_table co
            LEFT JOIN {$wpdb->posts} p ON co.product_id = p.ID
            WHERE DATE(co.created_at) BETWEEN %s AND %s
            ORDER BY co.created_at DESC
        ", $date_range['from'], $date_range['to']));
    }

    /**
     * Add product meta boxes
     */
    public function add_product_meta_boxes() {
        add_meta_box(
            'wc-customization-settings',
            __('Product Customization', 'wc-product-customizer'),
            array($this, 'product_customization_meta_box'),
            'product',
            'normal',
            'default'
        );
    }

    /**
     * Product customization meta box
     */
    public function product_customization_meta_box($post) {
        $enabled = get_post_meta($post->ID, '_customization_enabled', true);
        $auto_inherit = get_post_meta($post->ID, '_customization_auto_inherit', true);
        $config_id = get_post_meta($post->ID, '_customization_config_id', true);
        
        // Get current category configuration
        $db = WC_Product_Customizer_Database::get_instance();
        $current_config = $db->get_product_customization_config($post->ID);
        $available_configs = $db->get_all_category_configs(true);
        
        // Default to auto-inherit if not set
        if ($auto_inherit === '') {
            $auto_inherit = '1';
        }
        
        wp_nonce_field('wc_customization_meta', 'wc_customization_meta_nonce');
        ?>
        <div class="product-customization-meta">
        <table class="form-table">
            <tr>
                <th><label for="customization_enabled"><?php esc_html_e('Enable Customization', 'wc-product-customizer'); ?></label></th>
                <td>
                    <input type="checkbox" id="customization_enabled" name="customization_enabled" value="1" <?php checked($enabled, '1'); ?> />
                    <span class="description"><?php esc_html_e('Allow customers to customize this product', 'wc-product-customizer'); ?></span>
                </td>
            </tr>
                
                <tr>
                    <th><label for="customization_auto_inherit"><?php esc_html_e('Configuration Source', 'wc-product-customizer'); ?></label></th>
                    <td>
                        <label>
                            <input type="radio" id="customization_auto_inherit" name="customization_config_source" value="auto" <?php checked($auto_inherit, '1'); ?> />
                            <?php esc_html_e('Auto-inherit from category', 'wc-product-customizer'); ?>
                        </label><br>
                        <label>
                            <input type="radio" name="customization_config_source" value="manual" <?php checked($auto_inherit, '0'); ?> />
                            <?php esc_html_e('Manual configuration', 'wc-product-customizer'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Choose whether to automatically inherit settings from the product category or manually select a configuration.', 'wc-product-customizer'); ?></p>
                    </td>
                </tr>
                
                <tr id="manual-config-row" style="<?php echo $auto_inherit ? 'display: none;' : ''; ?>">
                    <th><label for="customization_config_id"><?php esc_html_e('Customization Configuration', 'wc-product-customizer'); ?></label></th>
                    <td>
                        <select id="customization_config_id" name="customization_config_id" class="regular-text">
                            <option value=""><?php esc_html_e('Select a configuration...', 'wc-product-customizer'); ?></option>
                            <?php foreach ($available_configs as $config): ?>
                                <option value="<?php echo esc_attr($config->id); ?>" <?php selected($config_id, $config->id); ?>>
                                    <?php echo esc_html($config->category_name . ' - ' . $config->config_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Select a specific configuration for this product.', 'wc-product-customizer'); ?></p>
                    </td>
                </tr>
        </table>
            
            <?php if ($current_config): ?>
                <div class="current-config-info">
                    <h4><?php esc_html_e('Current Configuration', 'wc-product-customizer'); ?></h4>
                    <div class="config-details">
                        <p><strong><?php esc_html_e('Source:', 'wc-product-customizer'); ?></strong> 
                           <?php echo $auto_inherit ? esc_html__('Category Auto-inherit', 'wc-product-customizer') : esc_html__('Manual Override', 'wc-product-customizer'); ?>
                        </p>
                        <p><strong><?php esc_html_e('Configuration:', 'wc-product-customizer'); ?></strong> 
                           <?php echo esc_html($current_config->config_name); ?>
                        </p>
                        
                        <?php
                        $zones = maybe_unserialize($current_config->available_zones);
                        $types = maybe_unserialize($current_config->available_types);
                        $has_zones = !empty($zones) && is_array($zones);
                        $has_types = !empty($types) && is_array($types);
                        ?>
                        
                        <?php if ($has_zones): ?>
                            <p><strong><?php esc_html_e('Available Zones:', 'wc-product-customizer'); ?></strong></p>
                            <ul>
                                <?php foreach ($zones as $zone_id): ?>
                                    <?php $zone = $db->get_zone_by_id($zone_id); ?>
                                    <?php if ($zone): ?>
                                        <li><?php echo esc_html($zone->name); ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="notice notice-warning inline">
                                <p><strong><?php esc_html_e('Warning:', 'wc-product-customizer'); ?></strong> 
                                   <?php esc_html_e('No zones configured. Customers will not be able to select customization positions.', 'wc-product-customizer'); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($has_types): ?>
                            <p><strong><?php esc_html_e('Available Types:', 'wc-product-customizer'); ?></strong></p>
                            <ul>
                                <?php foreach ($types as $type_id): ?>
                                    <?php $type = $db->get_customization_type_by_id($type_id); ?>
                                    <?php if ($type): ?>
                                        <li><?php echo esc_html($type->icon . ' ' . $type->name); ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="notice notice-warning inline">
                                <p><strong><?php esc_html_e('Warning:', 'wc-product-customizer'); ?></strong> 
                                   <?php esc_html_e('No customization types configured. Customers will not be able to select application methods.', 'wc-product-customizer'); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($current_config->custom_pricing_enabled): ?>
                            <p><strong><?php esc_html_e('Pricing:', 'wc-product-customizer'); ?></strong> 
                               <span class="custom-pricing"><?php esc_html_e('Custom pricing enabled', 'wc-product-customizer'); ?></span>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($enabled === '1' && (!$has_zones || !$has_types)): ?>
                            <div class="notice notice-error inline">
                                <p><strong><?php esc_html_e('Configuration Error:', 'wc-product-customizer'); ?></strong> 
                                   <?php esc_html_e('Customization is enabled but the configuration is incomplete. Customers will not be able to customize this product.', 'wc-product-customizer'); ?>
                                   <br>
                                   <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-category-configs')); ?>" class="button button-small">
                                       <?php esc_html_e('Manage Category Configurations', 'wc-product-customizer'); ?>
                                   </a>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-config-info">
                    <div class="notice notice-warning inline">
                        <p><strong><?php esc_html_e('No Configuration Found:', 'wc-product-customizer'); ?></strong> 
                           <?php esc_html_e('No customization configuration found for this product. Please ensure the product is assigned to a category with a configuration, or select a manual configuration above.', 'wc-product-customizer'); ?>
                        </p>
                        <p>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-add-category-config')); ?>" class="button button-primary">
                                <?php esc_html_e('Create Category Configuration', 'wc-product-customizer'); ?>
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-category-configs')); ?>" class="button">
                                <?php esc_html_e('Manage Configurations', 'wc-product-customizer'); ?>
                            </a>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('input[name="customization_config_source"]').change(function() {
                if ($(this).val() === 'auto') {
                    $('#manual-config-row').hide();
                } else {
                    $('#manual-config-row').show();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Save product meta
     */
    public function save_product_meta($post_id) {
        if (!isset($_POST['wc_customization_meta_nonce']) || !wp_verify_nonce($_POST['wc_customization_meta_nonce'], 'wc_customization_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $enabled = isset($_POST['customization_enabled']) ? '1' : '0';
        $config_source = sanitize_text_field($_POST['customization_config_source'] ?? 'auto');
        $auto_inherit = ($config_source === 'auto') ? '1' : '0';
        $config_id = ($config_source === 'manual') ? intval($_POST['customization_config_id'] ?? 0) : 0;
        
        update_post_meta($post_id, '_customization_enabled', $enabled);
        update_post_meta($post_id, '_customization_auto_inherit', $auto_inherit);
        update_post_meta($post_id, '_customization_config_id', $config_id);
    }

    /**
     * Add order item header
     */
    public function add_order_item_header() {
        ?>
        <th class="item_customization sortable" data-sort="customization">
            <?php esc_html_e('Customization', 'wc-product-customizer'); ?>
        </th>
        <?php
    }

    /**
     * Display order item customization
     */
    public function display_order_item_customization($product, $item, $item_id) {
        $customization_data = $item->get_meta('_customization_data');
        ?>
        <td class="item_customization">
            <?php if ($customization_data): ?>
                <div class="customization-details">
                    <strong><?php echo esc_html(ucfirst($customization_data['method'])); ?></strong><br>
                    <?php if (!empty($customization_data['zones'])): ?>
                        <small><?php 
                        if (is_array($customization_data['zones'])) {
                            echo esc_html(implode(', ', $customization_data['zones']));
                        } else {
                            echo esc_html($customization_data['zones']);
                        }
                        ?></small><br>
                    <?php endif; ?>
                    <?php if (!empty($customization_data['file_path'])): ?>
                        <small><?php esc_html_e('File uploaded', 'wc-product-customizer'); ?></small>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <span class="na">&ndash;</span>
            <?php endif; ?>
        </td>
        <?php
    }

    /**
     * Save settings via AJAX
     */
    public function save_settings() {
        check_ajax_referer('wc_customizer_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wc-product-customizer'));
        }

        // Handle settings save
        wp_send_json_success(array('message' => __('Settings saved successfully', 'wc-product-customizer')));
    }

    /**
     * Bulk enable products via AJAX
     */
    public function bulk_enable_products() {
        check_ajax_referer('wc_customizer_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wc-product-customizer'));
        }

        // Get all products
        $products = get_posts(array(
            'post_type' => 'product',
            'numberposts' => -1,
            'post_status' => 'publish'
        ));

        $count = 0;
        foreach ($products as $product) {
            update_post_meta($product->ID, '_customization_enabled', '1');
            $count++;
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Customization enabled for %d products', 'wc-product-customizer'), $count)
        ));
    }

    /**
     * Bulk disable products via AJAX
     */
    public function bulk_disable_products() {
        check_ajax_referer('wc_customizer_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wc-product-customizer'));
        }

        // Get all products
        $products = get_posts(array(
            'post_type' => 'product',
            'numberposts' => -1,
            'post_status' => 'publish'
        ));

        $count = 0;
        foreach ($products as $product) {
            update_post_meta($product->ID, '_customization_enabled', '0');
            $count++;
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Customization disabled for %d products', 'wc-product-customizer'), $count)
        ));
    }

    /**
     * AJAX handler for saving zone
     */
    public function ajax_save_zone() {
        check_ajax_referer('wc_customizer_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'wc-product-customizer')));
        }
        
        // Handle methods - could be array or comma-separated string
        $methods = $_POST['methods'] ?? array();
        if (is_string($methods)) {
            $methods = explode(',', $methods);
        }
        $methods = array_map('sanitize_text_field', $methods);
        
        $zone_data = array(
            'name' => sanitize_text_field($_POST['zone_name']),
            'zone_group' => sanitize_text_field($_POST['zone_group']),
            'zone_charge' => floatval($_POST['zone_charge']),
            'description' => sanitize_textarea_field($_POST['zone_description']),
            'thumbnail_url' => esc_url_raw($_POST['thumbnail_url']),
            'methods_available' => implode(',', $methods),
            'product_types' => sanitize_textarea_field($_POST['product_types']),
            'active' => isset($_POST['zone_active']) ? 1 : 0
        );
        
        // Add ID for updates
        if (!empty($_POST['zone_id'])) {
            $zone_data['id'] = intval($_POST['zone_id']);
        }
        
        try {
            $db = WC_Product_Customizer_Database::get_instance();
            $zone_id = $db->save_zone($zone_data);
            
            if ($zone_id) {
                wp_send_json_success(array(
                    'message' => empty($_POST['zone_id']) ? __('Zone created successfully.', 'wc-product-customizer') : __('Zone updated successfully.', 'wc-product-customizer'),
                    'zone_id' => $zone_id
                ));
            } else {
                wp_send_json_error(array('message' => __('Error saving zone.', 'wc-product-customizer')));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Error saving zone: ', 'wc-product-customizer') . $e->getMessage()));
        }
    }

    /**
     * AJAX handler for deleting zone
     */
    public function ajax_delete_zone() {
        check_ajax_referer('wc_customizer_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'wc-product-customizer')));
        }
        
        $zone_id = intval($_POST['zone_id']);
        
        $db = WC_Product_Customizer_Database::get_instance();
        
        // Check if zone is used in orders
        if ($db->check_zone_usage($zone_id)) {
            wp_send_json_error(array('message' => __('Cannot delete zone: It is being used in existing orders.', 'wc-product-customizer')));
        }
        
        if ($db->delete_zone($zone_id)) {
            wp_send_json_success(array('message' => __('Zone deleted successfully.', 'wc-product-customizer')));
        } else {
            wp_send_json_error(array('message' => __('Error deleting zone.', 'wc-product-customizer')));
        }
    }

    /**
     * AJAX handler for toggling zone status
     */
    public function ajax_toggle_zone_status() {
        check_ajax_referer('wc_customizer_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'wc-product-customizer')));
        }
        
        $zone_id = intval($_POST['zone_id']);
        $status = intval($_POST['status']);
        
        $db = WC_Product_Customizer_Database::get_instance();
        
        if ($db->update_zone_status($zone_id, $status)) {
            $status_text = $status ? __('activated', 'wc-product-customizer') : __('deactivated', 'wc-product-customizer');
            wp_send_json_success(array('message' => sprintf(__('Zone %s successfully.', 'wc-product-customizer'), $status_text)));
        } else {
            wp_send_json_error(array('message' => __('Error updating zone status.', 'wc-product-customizer')));
        }
    }

    /**
     * Add pricing tier page
     */
    public function add_pricing_tier_page() {
        $type_id = isset($_GET['type_id']) ? intval($_GET['type_id']) : null;
        $this->render_pricing_tier_form(null, null, $type_id);
    }

    /**
     * Edit pricing tier page
     */
    public function edit_pricing_tier_page($tier_id) {
        $db = WC_Product_Customizer_Database::get_instance();
        $tier = $db->get_pricing_tier_by_id($tier_id);
        
        if (!$tier) {
            wp_die(__('Pricing tier not found.', 'wc-product-customizer'));
        }
        
        $this->render_pricing_tier_form($tier, null, $tier->type_id);
    }

    /**
     * Render pricing tier form (add/edit)
     */
    private function render_pricing_tier_form($tier = null, $method = null, $type_id = null) {
        $is_edit = !is_null($tier);
        ?>
        <div class="wrap">
            <h1>
                <?php echo $is_edit ? esc_html__('Edit Pricing Tier', 'wc-product-customizer') : esc_html__('Add New Pricing Tier', 'wc-product-customizer'); ?>
            </h1>
            
            <form id="pricing-tier-form" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('wc_customizer_pricing_tier_form', 'pricing_tier_nonce'); ?>
                <input type="hidden" name="tier_id" value="<?php echo $is_edit ? esc_attr($tier->id) : ''; ?>">
                
                <table class="form-table">
                    <tbody>
                        <?php
                        $db = WC_Product_Customizer_Database::get_instance();
                        $customization_types = $db->get_all_customization_types(true);
                        $selected_type_id = $is_edit ? $tier->type_id : $type_id;
                        ?>
                        <tr>
                            <th scope="row">
                                <label for="type_id"><?php esc_html_e('Customization Type', 'wc-product-customizer'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <select id="type_id" name="type_id" class="regular-text" required>
                                    <option value=""><?php esc_html_e('Select a customization type...', 'wc-product-customizer'); ?></option>
                                    <?php foreach ($customization_types as $type): ?>
                                    <option value="<?php echo esc_attr($type->id); ?>" <?php selected($selected_type_id, $type->id); ?>>
                                        <?php echo esc_html($type->icon . ' ' . $type->name); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e('Select the customization type for this pricing tier.', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="min_quantity"><?php esc_html_e('Minimum Quantity', 'wc-product-customizer'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input type="number" id="min_quantity" name="min_quantity" value="<?php echo $is_edit ? esc_attr($tier->min_quantity) : '1'; ?>" min="1" class="small-text quantity-range-input" required>
                                <p class="description"><?php esc_html_e('Minimum quantity for this pricing tier.', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="max_quantity"><?php esc_html_e('Maximum Quantity', 'wc-product-customizer'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input type="number" id="max_quantity" name="max_quantity" value="<?php echo $is_edit ? esc_attr($tier->max_quantity) : '10'; ?>" min="1" class="small-text quantity-range-input" required>
                                <p class="description"><?php esc_html_e('Maximum quantity for this pricing tier. Use 999999 for unlimited.', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="price_per_item"><?php esc_html_e('Price per Item', 'wc-product-customizer'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <div class="price-input-container">
                                    <span class="currency-symbol">£</span>
                                    <input type="number" id="price_per_item" name="price_per_item" value="<?php echo $is_edit ? esc_attr($tier->price_per_item) : '0.00'; ?>" step="0.01" min="0" class="price-input" required>
                                </div>
                                <p class="description"><?php esc_html_e('Price per item for this quantity range.', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary" id="save-pricing-tier-btn">
                        <?php echo $is_edit ? esc_html__('Update Pricing Tier', 'wc-product-customizer') : esc_html__('Add Pricing Tier', 'wc-product-customizer'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-pricing')); ?>" class="button">
                        <?php esc_html_e('Cancel', 'wc-product-customizer'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Handle delete pricing tier
     */
    private function handle_delete_pricing_tier($tier_id) {
        $db = WC_Product_Customizer_Database::get_instance();
        
        if ($db->delete_pricing_tier($tier_id)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>' . esc_html__('Pricing tier deleted successfully.', 'wc-product-customizer') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Error deleting pricing tier.', 'wc-product-customizer') . '</p></div>';
            });
        }
    }

    /**
     * Handle pricing tier form submission
     */
    private function handle_pricing_tier_form_submission() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions.', 'wc-product-customizer'));
        }
        
        $type_id = intval($_POST['type_id']);
        $db = WC_Product_Customizer_Database::get_instance();
        $type = $db->get_customization_type_by_id($type_id);
        
        if (!$type) {
            wp_die(__('Invalid customization type selected.', 'wc-product-customizer'));
        }
        
        $tier_data = array(
            'type_id' => $type_id,
            'method' => $type->slug, // Use slug for backward compatibility
            'min_quantity' => intval($_POST['min_quantity']),
            'max_quantity' => intval($_POST['max_quantity']),
            'price_per_item' => floatval($_POST['price_per_item'])
        );
        
        // Add ID for updates
        if (!empty($_POST['tier_id'])) {
            $tier_data['id'] = intval($_POST['tier_id']);
        }
        
        // Validate data
        if ($tier_data['min_quantity'] >= $tier_data['max_quantity']) {
            wp_die(__('Minimum quantity must be less than maximum quantity.', 'wc-product-customizer'));
        }
        
        if ($tier_data['price_per_item'] < 0) {
            wp_die(__('Price per item cannot be negative.', 'wc-product-customizer'));
        }
        
        // Validate ranges for overlaps
        $validation = $db->validate_pricing_ranges($tier_data['type_id'], $tier_data['id'] ?? null);
        
        if (!$validation['valid']) {
            $error_message = implode('<br>', $validation['errors']);
            wp_die($error_message);
        }
        
        try {
            $tier_id = $db->save_pricing_tier($tier_data);
            
            if ($tier_id) {
                $message = empty($_POST['tier_id']) ? 
                    __('Pricing tier created successfully.', 'wc-product-customizer') : 
                    __('Pricing tier updated successfully.', 'wc-product-customizer');
                
                // Redirect to pricing page with success message
                wp_redirect(admin_url('admin.php?page=wc-customization-pricing&updated=1'));
                exit;
            } else {
                wp_die(__('Error saving pricing tier.', 'wc-product-customizer'));
            }
        } catch (Exception $e) {
            wp_die(__('Error saving pricing tier: ', 'wc-product-customizer') . $e->getMessage());
        }
    }

    /**
     * AJAX handler for saving pricing tier
     */
    public function ajax_save_pricing_tier() {
        check_ajax_referer('wc_customizer_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'wc-product-customizer')));
        }
        
        $type_id = intval($_POST['type_id']);
        $db = WC_Product_Customizer_Database::get_instance();
        $type = $db->get_customization_type_by_id($type_id);
        
        if (!$type) {
            wp_send_json_error(array('message' => __('Invalid customization type selected.', 'wc-product-customizer')));
        }
        
        $tier_data = array(
            'type_id' => $type_id,
            'method' => $type->slug, // Use slug for backward compatibility
            'min_quantity' => intval($_POST['min_quantity']),
            'max_quantity' => intval($_POST['max_quantity']),
            'price_per_item' => floatval($_POST['price_per_item'])
        );
        
        // Add ID for updates
        if (!empty($_POST['tier_id'])) {
            $tier_data['id'] = intval($_POST['tier_id']);
        }
        
        // Validate data
        if ($tier_data['min_quantity'] >= $tier_data['max_quantity']) {
            wp_send_json_error(array('message' => __('Minimum quantity must be less than maximum quantity.', 'wc-product-customizer')));
        }
        
        if ($tier_data['price_per_item'] <= 0) {
            wp_send_json_error(array('message' => __('Price per item must be greater than 0.', 'wc-product-customizer')));
        }
        
        // Validate ranges for overlaps
        $validation = $db->validate_pricing_ranges($tier_data['type_id'], $tier_data['id'] ?? null);
        
        if (!$validation['valid']) {
            wp_send_json_error(array('message' => implode(', ', $validation['errors'])));
        }
        
        $tier_id = $db->save_pricing_tier($tier_data);
        
        if ($tier_id) {
            // Clear pricing cache
            delete_transient('wc_customizer_pricing_tiers');
            
            wp_send_json_success(array(
                'message' => empty($_POST['tier_id']) ? __('Pricing tier created successfully.', 'wc-product-customizer') : __('Pricing tier updated successfully.', 'wc-product-customizer'),
                'tier_id' => $tier_id,
                'warnings' => $validation['warnings']
            ));
        } else {
            wp_send_json_error(array('message' => __('Error saving pricing tier.', 'wc-product-customizer')));
        }
    }

    /**
     * AJAX handler for deleting pricing tier
     */
    public function ajax_delete_pricing_tier() {
        check_ajax_referer('wc_customizer_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'wc-product-customizer')));
        }
        
        $tier_id = intval($_POST['tier_id']);
        
        $db = WC_Product_Customizer_Database::get_instance();
        
        if ($db->delete_pricing_tier($tier_id)) {
            // Clear pricing cache
            delete_transient('wc_customizer_pricing_tiers');
            
            wp_send_json_success(array('message' => __('Pricing tier deleted successfully.', 'wc-product-customizer')));
        } else {
            wp_send_json_error(array('message' => __('Error deleting pricing tier.', 'wc-product-customizer')));
        }
    }

    /**
     * AJAX handler for validating pricing ranges
     */
    public function ajax_validate_pricing_ranges() {
        check_ajax_referer('wc_customizer_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'wc-product-customizer')));
        }
        
        $method = sanitize_text_field($_POST['method']);
        $exclude_id = !empty($_POST['exclude_id']) ? intval($_POST['exclude_id']) : null;
        
        $db = WC_Product_Customizer_Database::get_instance();
        $validation = $db->validate_pricing_ranges($method, $exclude_id);
        
        wp_send_json_success($validation);
    }

    /**
     * Customization types page
     */
    public function customization_types_page() {
        $db = WC_Product_Customizer_Database::get_instance();
        $types = $db->get_all_customization_types();
        
        // Handle actions
        if (isset($_GET['action']) && isset($_GET['type_id'])) {
            $action = sanitize_text_field($_GET['action']);
            $type_id = intval($_GET['type_id']);
            
            switch ($action) {
                case 'edit':
                    $this->edit_customization_type_page($type_id);
                    return;
                case 'manage-pricing':
                    $this->manage_type_pricing_page($type_id);
                    return;
                case 'delete':
                    $this->handle_delete_customization_type($type_id);
                    break;
                case 'toggle':
                    $this->handle_toggle_type_status($type_id);
                    break;
            }
        }
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php esc_html_e('Customization Types', 'wc-product-customizer'); ?>
            </h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-add-type')); ?>" class="page-title-action">
                <?php esc_html_e('Add New', 'wc-product-customizer'); ?>
            </a>
            <hr class="wp-header-end">
            
            <!-- Status Filter Buttons -->
            <div class="types-filter-tabs" style="margin: 20px 0;">
                <button type="button" class="button filter-btn active" data-status="all">
                    <?php esc_html_e('All', 'wc-product-customizer'); ?> 
                    <span class="count">(<?php echo count($types); ?>)</span>
                </button>
                <button type="button" class="button filter-btn" data-status="active">
                    <?php esc_html_e('Active', 'wc-product-customizer'); ?> 
                    <span class="count">(<?php echo count(array_filter($types, function($type) { return $type->active; })); ?>)</span>
                </button>
                <button type="button" class="button filter-btn" data-status="inactive">
                    <?php esc_html_e('Inactive', 'wc-product-customizer'); ?> 
                    <span class="count">(<?php echo count(array_filter($types, function($type) { return !$type->active; })); ?>)</span>
                </button>
            </div>
            
            <div class="customization-types-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th class="column-icon"><?php esc_html_e('Icon', 'wc-product-customizer'); ?></th>
                            <th class="column-name"><?php esc_html_e('Name', 'wc-product-customizer'); ?></th>
                            <th class="column-slug"><?php esc_html_e('Slug', 'wc-product-customizer'); ?></th>
                            <th class="column-setup-fees"><?php esc_html_e('Setup Fees', 'wc-product-customizer'); ?></th>
                            <th class="column-pricing-tiers"><?php esc_html_e('Pricing Tiers', 'wc-product-customizer'); ?></th>
                            <th class="column-status"><?php esc_html_e('Status', 'wc-product-customizer'); ?></th>
                            <th class="column-actions"><?php esc_html_e('Actions', 'wc-product-customizer'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($types)): ?>
                        <tr>
                            <td colspan="7" class="no-items">
                                <?php esc_html_e('No customization types found.', 'wc-product-customizer'); ?>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($types as $type): ?>
                        <tr class="type-row" data-status="<?php echo $type->active ? 'active' : 'inactive'; ?>">
                            <td class="column-icon">
                                <span class="type-icon-display"><?php echo esc_html($type->icon); ?></span>
                            </td>
                            <td class="column-name">
                                <strong><?php echo esc_html($type->name); ?></strong>
                                <?php if (!empty($type->description)): ?>
                                <br><small class="description"><?php echo esc_html($type->description); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="column-slug">
                                <code><?php echo esc_html($type->slug); ?></code>
                            </td>
                            <td class="column-setup-fees">
                                <div class="type-setup-fees">
                                    <div><?php printf(__('Text: £%.2f', 'wc-product-customizer'), $type->text_setup_fee); ?></div>
                                    <div><?php printf(__('Logo: £%.2f', 'wc-product-customizer'), $type->logo_setup_fee); ?></div>
                                </div>
                            </td>
                            <td class="column-pricing-tiers">
                                <?php
                                $pricing_tiers = $db->get_pricing_tiers_by_type_id($type->id);
                                $tier_count = count($pricing_tiers);
                                ?>
                                <span class="type-pricing-count"><?php echo $tier_count; ?> <?php echo $tier_count === 1 ? __('tier', 'wc-product-customizer') : __('tiers', 'wc-product-customizer'); ?></span>
                            </td>
                            <td class="column-status">
                                <span class="status-<?php echo $type->active ? 'active' : 'inactive'; ?>">
                                    <?php echo $type->active ? __('Active', 'wc-product-customizer') : __('Inactive', 'wc-product-customizer'); ?>
                                </span>
                            </td>
                            <td class="column-actions">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-types&action=edit&type_id=' . $type->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Edit', 'wc-product-customizer'); ?>
                                </a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-types&action=manage-pricing&type_id=' . $type->id)); ?>" class="button button-small manage-pricing-btn">
                                    <?php esc_html_e('Manage Pricing', 'wc-product-customizer'); ?>
                                </a>
                                <button type="button" class="button button-small toggle-type-status-btn" data-type-id="<?php echo esc_attr($type->id); ?>" data-status="<?php echo esc_attr($type->active); ?>">
                                    <?php echo $type->active ? __('Deactivate', 'wc-product-customizer') : __('Activate', 'wc-product-customizer'); ?>
                                </button>
                                <button type="button" class="button button-small button-link-delete delete-type-btn" data-type-id="<?php echo esc_attr($type->id); ?>">
                                    <?php esc_html_e('Delete', 'wc-product-customizer'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Add customization type page
     */
    public function add_customization_type_page() {
        $this->render_customization_type_form();
    }

    /**
     * Edit customization type page
     */
    public function edit_customization_type_page($type_id) {
        $db = WC_Product_Customizer_Database::get_instance();
        $type = $db->get_customization_type_by_id($type_id);
        
        if (!$type) {
            wp_die(__('Customization type not found.', 'wc-product-customizer'));
        }
        
        $this->render_customization_type_form($type);
    }

    /**
     * Manage type pricing page
     */
    public function manage_type_pricing_page($type_id) {
        $db = WC_Product_Customizer_Database::get_instance();
        $type = $db->get_customization_type_by_id($type_id);
        
        if (!$type) {
            wp_die(__('Customization type not found.', 'wc-product-customizer'));
        }
        
        $pricing_tiers = $db->get_pricing_tiers_by_type_id($type_id);
        
        // Handle actions
        if (isset($_GET['tier_action']) && isset($_GET['tier_id'])) {
            $action = sanitize_text_field($_GET['tier_action']);
            $tier_id = intval($_GET['tier_id']);
            
            switch ($action) {
                case 'edit':
                    $this->edit_pricing_tier_page($tier_id, $type_id);
                    return;
                case 'delete':
                    $this->handle_delete_pricing_tier($tier_id);
                    break;
            }
        }
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php printf(__('Manage Pricing for %s %s', 'wc-product-customizer'), $type->icon, esc_html($type->name)); ?>
            </h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-add-pricing-tier&type_id=' . $type_id)); ?>" class="page-title-action">
                <?php esc_html_e('Add Pricing Tier', 'wc-product-customizer'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-types')); ?>" class="page-title-action">
                <?php esc_html_e('← Back to Types', 'wc-product-customizer'); ?>
            </a>
            <hr class="wp-header-end">
            
            <div class="pricing-tiers-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th class="column-quantity"><?php esc_html_e('Quantity Range', 'wc-product-customizer'); ?></th>
                            <th class="column-price"><?php esc_html_e('Price per Item', 'wc-product-customizer'); ?></th>
                            <th class="column-actions"><?php esc_html_e('Actions', 'wc-product-customizer'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pricing_tiers)): ?>
                        <tr>
                            <td colspan="3" class="no-items">
                                <?php esc_html_e('No pricing tiers found for this customization type.', 'wc-product-customizer'); ?>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($pricing_tiers as $tier): ?>
                        <tr>
                            <td class="column-quantity">
                                <?php printf(__('%d - %d items', 'wc-product-customizer'), $tier->min_quantity, $tier->max_quantity); ?>
                            </td>
                            <td class="column-price">
                                <strong>£<?php echo number_format($tier->price_per_item, 2); ?></strong>
                            </td>
                            <td class="column-actions">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-types&action=manage-pricing&type_id=' . $type_id . '&tier_action=edit&tier_id=' . $tier->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Edit', 'wc-product-customizer'); ?>
                                </a>
                                <button type="button" class="button button-small button-link-delete delete-pricing-tier-btn" data-tier-id="<?php echo esc_attr($tier->id); ?>">
                                    <?php esc_html_e('Delete', 'wc-product-customizer'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Render customization type form
     */
    private function render_customization_type_form($type = null) {
        $is_edit = !empty($type);
        $type_id = $is_edit ? $type->id : '';
        
        ?>
        <div class="wrap">
            <h1><?php echo $is_edit ? esc_html__('Edit Customization Type', 'wc-product-customizer') : esc_html__('Add New Customization Type', 'wc-product-customizer'); ?></h1>
            
            <form id="customization-type-form" method="post">
                <?php wp_nonce_field('wc_customizer_admin', 'nonce'); ?>
                <input type="hidden" name="type_id" value="<?php echo esc_attr($type_id); ?>">
                
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="type_name"><?php esc_html_e('Type Name', 'wc-product-customizer'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input type="text" id="type_name" name="type_name" class="regular-text" value="<?php echo $is_edit ? esc_attr($type->name) : ''; ?>" required>
                                <p class="description"><?php esc_html_e('The display name for this customization type.', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="type_slug"><?php esc_html_e('Slug', 'wc-product-customizer'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input type="text" id="type_slug" name="type_slug" class="regular-text" value="<?php echo $is_edit ? esc_attr($type->slug) : ''; ?>" required>
                                <p class="description"><?php esc_html_e('URL-friendly identifier (lowercase, hyphens only).', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="type_icon"><?php esc_html_e('Icon/Emoji', 'wc-product-customizer'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="type_icon" name="type_icon" class="regular-text" value="<?php echo $is_edit ? esc_attr($type->icon) : '🔧'; ?>" maxlength="10">
                                <p class="description"><?php esc_html_e('Emoji or icon to display for this customization type.', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="type_description"><?php esc_html_e('Description', 'wc-product-customizer'); ?></label>
                            </th>
                            <td>
                                <textarea id="type_description" name="type_description" class="large-text" rows="3"><?php echo $is_edit ? esc_textarea($type->description) : ''; ?></textarea>
                                <p class="description"><?php esc_html_e('Optional description for this customization type.', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="text_setup_fee"><?php esc_html_e('Text Setup Fee', 'wc-product-customizer'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="text_setup_fee" name="text_setup_fee" class="small-text" step="0.01" min="0" value="<?php echo $is_edit ? esc_attr($type->text_setup_fee) : '0.00'; ?>">
                                <span class="description"><?php esc_html_e('Setup fee for text customization (in £).', 'wc-product-customizer'); ?></span>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="logo_setup_fee"><?php esc_html_e('Logo Setup Fee', 'wc-product-customizer'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="logo_setup_fee" name="logo_setup_fee" class="small-text" step="0.01" min="0" value="<?php echo $is_edit ? esc_attr($type->logo_setup_fee) : '0.00'; ?>">
                                <span class="description"><?php esc_html_e('Setup fee for logo customization (in £).', 'wc-product-customizer'); ?></span>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="sort_order"><?php esc_html_e('Sort Order', 'wc-product-customizer'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="sort_order" name="sort_order" class="small-text" value="<?php echo $is_edit ? esc_attr($type->sort_order) : '0'; ?>">
                                <p class="description"><?php esc_html_e('Display order (lower numbers appear first).', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php esc_html_e('Status', 'wc-product-customizer'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="type_active" value="1" <?php checked($is_edit ? $type->active : true); ?>>
                                    <?php esc_html_e('Active', 'wc-product-customizer'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Only active types are shown to customers.', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php echo $is_edit ? esc_html__('Update Customization Type', 'wc-product-customizer') : esc_html__('Add Customization Type', 'wc-product-customizer'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-types')); ?>" class="button">
                        <?php esc_html_e('Cancel', 'wc-product-customizer'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Handle delete customization type
     */
    private function handle_delete_customization_type($type_id) {
        $db = WC_Product_Customizer_Database::get_instance();
        
        if ($db->delete_customization_type($type_id)) {
            echo '<div class="notice notice-success"><p>' . esc_html__('Customization type deleted successfully.', 'wc-product-customizer') . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Cannot delete customization type. It may be in use.', 'wc-product-customizer') . '</p></div>';
        }
    }

    /**
     * Handle toggle type status
     */
    private function handle_toggle_type_status($type_id) {
        $db = WC_Product_Customizer_Database::get_instance();
        $type = $db->get_customization_type_by_id($type_id);
        
        if ($type) {
            $new_status = $type->active ? 0 : 1;
            if ($db->update_type_status($type_id, $new_status)) {
                echo '<div class="notice notice-success"><p>' . esc_html__('Customization type status updated successfully.', 'wc-product-customizer') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__('Error updating customization type status.', 'wc-product-customizer') . '</p></div>';
            }
        }
    }

    /**
     * AJAX handler for saving customization type
     */
    public function ajax_save_customization_type() {
        check_ajax_referer('wc_customizer_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'wc-product-customizer')));
        }
        
        $type_data = array(
            'id' => !empty($_POST['type_id']) ? intval($_POST['type_id']) : '',
            'name' => sanitize_text_field($_POST['type_name']),
            'slug' => sanitize_title($_POST['type_slug']),
            'description' => sanitize_textarea_field($_POST['type_description']),
            'icon' => sanitize_text_field($_POST['type_icon']),
            'text_setup_fee' => floatval($_POST['text_setup_fee']),
            'logo_setup_fee' => floatval($_POST['logo_setup_fee']),
            'sort_order' => intval($_POST['sort_order']),
            'active' => isset($_POST['type_active']) ? 1 : 0
        );
        
        try {
            $db = WC_Product_Customizer_Database::get_instance();
            $type_id = $db->save_customization_type($type_data);
            
            if ($type_id) {
                wp_send_json_success(array(
                    'message' => empty($_POST['type_id']) ? __('Customization type created successfully.', 'wc-product-customizer') : __('Customization type updated successfully.', 'wc-product-customizer'),
                    'type_id' => $type_id
                ));
            } else {
                wp_send_json_error(array('message' => __('Error saving customization type.', 'wc-product-customizer')));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Error saving customization type: ', 'wc-product-customizer') . $e->getMessage()));
        }
    }

    /**
     * AJAX handler for deleting customization type
     */
    public function ajax_delete_customization_type() {
        check_ajax_referer('wc_customizer_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'wc-product-customizer')));
        }
        
        $type_id = intval($_POST['type_id']);
        $db = WC_Product_Customizer_Database::get_instance();
        
        if ($db->delete_customization_type($type_id)) {
            wp_send_json_success(array('message' => __('Customization type deleted successfully.', 'wc-product-customizer')));
        } else {
            wp_send_json_error(array('message' => __('Cannot delete customization type. It may be in use.', 'wc-product-customizer')));
        }
    }

    /**
     * AJAX handler for toggling type status
     */
    public function ajax_toggle_type_status() {
        check_ajax_referer('wc_customizer_admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'wc-product-customizer')));
        }
        
        $type_id = intval($_POST['type_id']);
        $db = WC_Product_Customizer_Database::get_instance();
        $type = $db->get_customization_type_by_id($type_id);
        
        if ($type) {
            $new_status = $type->active ? 0 : 1;
            if ($db->update_type_status($type_id, $new_status)) {
                wp_send_json_success(array(
                    'message' => __('Customization type status updated successfully.', 'wc-product-customizer'),
                    'new_status' => $new_status
                ));
            } else {
                wp_send_json_error(array('message' => __('Error updating customization type status.', 'wc-product-customizer')));
            }
        } else {
            wp_send_json_error(array('message' => __('Customization type not found.', 'wc-product-customizer')));
        }
    }

    // ========================================
    // Category Configuration Methods
    // ========================================

    /**
     * Category configurations page
     */
    public function category_configs_page() {
        $db = WC_Product_Customizer_Database::get_instance();
        $configs = $db->get_all_category_configs();
        
        // Handle actions
        if (isset($_GET['action']) && isset($_GET['config_id'])) {
            $action = sanitize_text_field($_GET['action']);
            $config_id = intval($_GET['config_id']);
            
            switch ($action) {
                case 'delete':
                    $this->handle_delete_category_config($config_id);
                    break;
                case 'toggle':
                    $this->handle_toggle_config_status($config_id);
                    break;
            }
        }
        
        // Refresh configs after action
        $configs = $db->get_all_category_configs();
        ?>
        <div class="wrap">
            <h1>
                <?php esc_html_e('Category Configurations', 'wc-product-customizer'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-add-category-config')); ?>" class="page-title-action">
                    <?php esc_html_e('Add New', 'wc-product-customizer'); ?>
                </a>
            </h1>
            
            <!-- Status Filter Buttons -->
            <div class="configs-filter-tabs" style="margin: 20px 0;">
                <button type="button" class="button filter-btn active" data-status="all">
                    <?php esc_html_e('All', 'wc-product-customizer'); ?> 
                    <span class="count">(<?php echo count($configs); ?>)</span>
                </button>
                <button type="button" class="button filter-btn" data-status="active">
                    <?php esc_html_e('Active', 'wc-product-customizer'); ?> 
                    <span class="count">(<?php echo count(array_filter($configs, function($config) { return $config->enabled; })); ?>)</span>
                </button>
                <button type="button" class="button filter-btn" data-status="inactive">
                    <?php esc_html_e('Inactive', 'wc-product-customizer'); ?> 
                    <span class="count">(<?php echo count(array_filter($configs, function($config) { return !$config->enabled; })); ?>)</span>
                </button>
            </div>
            
            <div class="category-configs-list">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('Category', 'wc-product-customizer'); ?></th>
                            <th scope="col"><?php esc_html_e('Config Name', 'wc-product-customizer'); ?></th>
                            <th scope="col"><?php esc_html_e('Zones', 'wc-product-customizer'); ?></th>
                            <th scope="col"><?php esc_html_e('Types', 'wc-product-customizer'); ?></th>
                            <th scope="col"><?php esc_html_e('Custom Pricing', 'wc-product-customizer'); ?></th>
                            <th scope="col"><?php esc_html_e('Status', 'wc-product-customizer'); ?></th>
                            <th scope="col"><?php esc_html_e('Actions', 'wc-product-customizer'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($configs)): ?>
                            <tr>
                                <td colspan="7" class="no-items">
                                    <?php esc_html_e('No category configurations found.', 'wc-product-customizer'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($configs as $config): ?>
                                <?php
                                $zones = maybe_unserialize($config->available_zones);
                                $types = maybe_unserialize($config->available_types);
                                $zones_count = is_array($zones) ? count($zones) : 0;
                                $types_count = is_array($types) ? count($types) : 0;
                                ?>
                                <tr class="config-row" data-status="<?php echo $config->enabled ? 'active' : 'inactive'; ?>">
                                    <td>
                                        <strong><?php echo esc_html($config->category_name ?: __('Unknown Category', 'wc-product-customizer')); ?></strong>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($config->config_name); ?></strong>
                                        <?php if ($config->description): ?>
                                            <br><small class="description"><?php echo esc_html($config->description); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="count"><?php echo esc_html($zones_count); ?></span>
                                    </td>
                                    <td>
                                        <span class="count"><?php echo esc_html($types_count); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($config->custom_pricing_enabled): ?>
                                            <span class="dashicons dashicons-yes" style="color: #46b450;"></span>
                                            <?php esc_html_e('Yes', 'wc-product-customizer'); ?>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-no" style="color: #dc3232;"></span>
                                            <?php esc_html_e('No', 'wc-product-customizer'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($config->enabled): ?>
                                            <span class="status-enabled"><?php esc_html_e('Active', 'wc-product-customizer'); ?></span>
                                        <?php else: ?>
                                            <span class="status-disabled"><?php esc_html_e('Inactive', 'wc-product-customizer'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-add-category-config&config_id=' . $config->id)); ?>" class="button button-small">
                                            <?php esc_html_e('Edit', 'wc-product-customizer'); ?>
                                        </a>
                                        <button type="button" class="button button-small toggle-config-status-btn" data-config-id="<?php echo esc_attr($config->id); ?>">
                                            <?php echo $config->enabled ? esc_html__('Deactivate', 'wc-product-customizer') : esc_html__('Activate', 'wc-product-customizer'); ?>
                                        </button>
                                        <button type="button" class="button button-small button-link-delete delete-config-btn" data-config-id="<?php echo esc_attr($config->id); ?>">
                                            <?php esc_html_e('Delete', 'wc-product-customizer'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Add/Edit category configuration page
     */
    public function add_category_config_page() {
        $is_edit = isset($_GET['config_id']) && $_GET['config_id'];
        $config = null;
        
        if ($is_edit) {
            $db = WC_Product_Customizer_Database::get_instance();
            $config = $db->get_category_config_by_id(intval($_GET['config_id']));
            
            if (!$config) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Category configuration not found.', 'wc-product-customizer') . '</p></div>';
                return;
            }
        }
        
        $db = WC_Product_Customizer_Database::get_instance();
        $categories = $db->get_product_categories();
        $zones = $db->get_customization_zones();
        $types = $db->get_all_customization_types(true);
        
        $selected_zones = $is_edit ? maybe_unserialize($config->available_zones) : array();
        $selected_types = $is_edit ? maybe_unserialize($config->available_types) : array();
        
        if (!is_array($selected_zones)) $selected_zones = array();
        if (!is_array($selected_types)) $selected_types = array();
        ?>
        <div class="wrap">
            <h1>
                <?php echo $is_edit ? esc_html__('Edit Category Configuration', 'wc-product-customizer') : esc_html__('Add Category Configuration', 'wc-product-customizer'); ?>
            </h1>
            
            <form id="category-config-form" method="post">
                <?php wp_nonce_field('wc_customizer_category_config', 'wc_customizer_category_config_nonce'); ?>
                <input type="hidden" name="config_id" value="<?php echo $is_edit ? esc_attr($config->id) : ''; ?>">
                
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="category_id"><?php esc_html_e('Product Category', 'wc-product-customizer'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <select id="category_id" name="category_id" class="regular-text" required>
                                    <option value=""><?php esc_html_e('Select a category...', 'wc-product-customizer'); ?></option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo esc_attr($category->term_id); ?>" <?php selected($is_edit ? $config->category_id : '', $category->term_id); ?>>
                                            <?php echo esc_html($category->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e('Select the product category for this configuration.', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="config_name"><?php esc_html_e('Configuration Name', 'wc-product-customizer'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input type="text" id="config_name" name="config_name" value="<?php echo $is_edit ? esc_attr($config->config_name) : ''; ?>" class="regular-text" required>
                                <p class="description"><?php esc_html_e('A descriptive name for this configuration.', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="description"><?php esc_html_e('Description', 'wc-product-customizer'); ?></label>
                            </th>
                            <td>
                                <textarea id="description" name="description" rows="3" class="large-text"><?php echo $is_edit ? esc_textarea($config->description) : ''; ?></textarea>
                                <p class="description"><?php esc_html_e('Optional description for this configuration.', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php esc_html_e('Available Zones', 'wc-product-customizer'); ?></th>
                            <td>
                                <fieldset>
                                    <?php foreach ($zones as $zone): ?>
                                        <label>
                                            <input type="checkbox" name="available_zones[]" value="<?php echo esc_attr($zone->id); ?>" 
                                                   <?php checked(in_array($zone->id, $selected_zones)); ?>>
                                            <?php echo esc_html($zone->name); ?>
                                            <?php if ($zone->zone_group): ?>
                                                <small>(<?php echo esc_html($zone->zone_group); ?>)</small>
                                            <?php endif; ?>
                                        </label><br>
                                    <?php endforeach; ?>
                                </fieldset>
                                <p class="description"><?php esc_html_e('Select which zones are available for this category.', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php esc_html_e('Available Customization Types', 'wc-product-customizer'); ?></th>
                            <td>
                                <fieldset>
                                    <?php foreach ($types as $type): ?>
                                        <label>
                                            <input type="checkbox" name="available_types[]" value="<?php echo esc_attr($type->id); ?>" 
                                                   <?php checked(in_array($type->id, $selected_types)); ?>>
                                            <?php echo esc_html($type->icon . ' ' . $type->name); ?>
                                        </label><br>
                                    <?php endforeach; ?>
                                </fieldset>
                                <p class="description"><?php esc_html_e('Select which customization types are available for this category.', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php esc_html_e('Custom Pricing', 'wc-product-customizer'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="custom_pricing_enabled" value="1" 
                                           <?php checked($is_edit ? $config->custom_pricing_enabled : false); ?>>
                                    <?php esc_html_e('Enable custom pricing for this category', 'wc-product-customizer'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('When enabled, this category can have different pricing than the global settings.', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php esc_html_e('Status', 'wc-product-customizer'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enabled" value="1" 
                                           <?php checked($is_edit ? $config->enabled : true); ?>>
                                    <?php esc_html_e('Active', 'wc-product-customizer'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Only active configurations are available for products.', 'wc-product-customizer'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php echo $is_edit ? esc_html__('Update Configuration', 'wc-product-customizer') : esc_html__('Add Configuration', 'wc-product-customizer'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-customization-category-configs')); ?>" class="button">
                        <?php esc_html_e('Cancel', 'wc-product-customizer'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Handle delete category configuration
     */
    private function handle_delete_category_config($config_id) {
        $db = WC_Product_Customizer_Database::get_instance();
        
        if ($db->delete_category_config($config_id)) {
            echo '<div class="notice notice-success"><p>' . esc_html__('Category configuration deleted successfully.', 'wc-product-customizer') . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Cannot delete category configuration. It may be in use.', 'wc-product-customizer') . '</p></div>';
        }
    }

    /**
     * Handle toggle config status
     */
    private function handle_toggle_config_status($config_id) {
        $db = WC_Product_Customizer_Database::get_instance();
        $config = $db->get_category_config_by_id($config_id);
        
        if ($config) {
            $new_status = $config->enabled ? 0 : 1;
            $data = array(
                'id' => $config_id,
                'enabled' => $new_status
            );
            
            if ($db->save_category_config($data)) {
                echo '<div class="notice notice-success"><p>' . esc_html__('Configuration status updated successfully.', 'wc-product-customizer') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__('Error updating configuration status.', 'wc-product-customizer') . '</p></div>';
            }
        }
    }

    /**
     * AJAX: Save category configuration
     */
    public function ajax_save_category_config() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wc_customizer_admin')) {
            wp_send_json_error(array('message' => __('Security check failed', 'wc-product-customizer')));
        }

        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wc-product-customizer')));
        }

        $db = WC_Product_Customizer_Database::get_instance();
        
        $data = array(
            'category_id' => intval($_POST['category_id']),
            'config_name' => sanitize_text_field($_POST['config_name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'available_zones' => array_map('intval', $_POST['available_zones'] ?? array()),
            'available_types' => array_map('intval', $_POST['available_types'] ?? array()),
            'custom_pricing_enabled' => isset($_POST['custom_pricing_enabled']) ? 1 : 0,
            'enabled' => isset($_POST['enabled']) ? 1 : 0
        );
        
        if (isset($_POST['config_id']) && $_POST['config_id']) {
            $data['id'] = intval($_POST['config_id']);
        }
        
        $config_id = $db->save_category_config($data);
        
        if ($config_id) {
            wp_send_json_success(array(
                'message' => __('Configuration saved successfully.', 'wc-product-customizer'),
                'config_id' => $config_id,
                'redirect_url' => admin_url('admin.php?page=wc-customization-category-configs')
            ));
        } else {
            wp_send_json_error(array('message' => __('Error saving configuration.', 'wc-product-customizer')));
        }
    }

    /**
     * AJAX: Delete category configuration
     */
    public function ajax_delete_category_config() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wc_customizer_admin')) {
            wp_send_json_error(array('message' => __('Security check failed', 'wc-product-customizer')));
        }

        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wc-product-customizer')));
        }

        $config_id = intval($_POST['config_id']);
        $db = WC_Product_Customizer_Database::get_instance();
        
        if ($db->delete_category_config($config_id)) {
            wp_send_json_success(array('message' => __('Configuration deleted successfully.', 'wc-product-customizer')));
        } else {
            wp_send_json_error(array('message' => __('Cannot delete configuration. It may be in use.', 'wc-product-customizer')));
        }
    }

    /**
     * AJAX: Toggle config status
     */
    public function ajax_toggle_config_status() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wc_customizer_admin')) {
            wp_send_json_error(array('message' => __('Security check failed', 'wc-product-customizer')));
        }

        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wc-product-customizer')));
        }

        $config_id = intval($_POST['config_id']);
        $db = WC_Product_Customizer_Database::get_instance();
        $config = $db->get_category_config_by_id($config_id);
        
        if ($config) {
            $new_status = $config->enabled ? 0 : 1;
            $data = array(
                'id' => $config_id,
                'enabled' => $new_status
            );
            
            if ($db->save_category_config($data)) {
                wp_send_json_success(array(
                    'message' => __('Configuration status updated successfully.', 'wc-product-customizer'),
                    'new_status' => $new_status
                ));
            } else {
                wp_send_json_error(array('message' => __('Error updating configuration status.', 'wc-product-customizer')));
            }
        } else {
            wp_send_json_error(array('message' => __('Configuration not found.', 'wc-product-customizer')));
        }
    }

    /**
     * AJAX: Get category configuration
     */
    public function ajax_get_category_config() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wc_customizer_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'wc-product-customizer')));
        }

        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wc-product-customizer')));
        }

        $config_id = intval($_POST['config_id']);
        $db = WC_Product_Customizer_Database::get_instance();
        $config = $db->get_category_config_by_id($config_id);
        
        if ($config) {
            wp_send_json_success(array('config' => $config));
        } else {
            wp_send_json_error(array('message' => __('Configuration not found.', 'wc-product-customizer')));
        }
    }
}
