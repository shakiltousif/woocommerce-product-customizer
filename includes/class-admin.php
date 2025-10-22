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
        $zones = $db->get_customization_zones();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Customization Zones', 'wc-product-customizer'); ?></h1>
            
            <div class="zones-grid">
                <?php foreach ($zones as $zone): ?>
                <div class="zone-card">
                    <h3><?php echo esc_html($zone->name); ?></h3>
                    <p><strong><?php esc_html_e('Group:', 'wc-product-customizer'); ?></strong> <?php echo esc_html($zone->zone_group); ?></p>
                    <p><strong><?php esc_html_e('Methods:', 'wc-product-customizer'); ?></strong> <?php echo esc_html($zone->methods_available); ?></p>
                    <p><strong><?php esc_html_e('Zone Charge:', 'wc-product-customizer'); ?></strong> £<?php echo esc_html($zone->zone_charge); ?></p>
                </div>
                <?php endforeach; ?>
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
     * Pricing page
     */
    public function pricing_page() {
        $db = WC_Product_Customizer_Database::get_instance();
        $embroidery_pricing = $db->get_pricing_tiers('embroidery');
        $print_pricing = $db->get_pricing_tiers('print');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Pricing Configuration', 'wc-product-customizer'); ?></h1>
            
            <div class="pricing-tables">
                <div class="pricing-section">
                    <h2><?php esc_html_e('Embroidery Pricing', 'wc-product-customizer'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Quantity Range', 'wc-product-customizer'); ?></th>
                                <th><?php esc_html_e('Price per Item', 'wc-product-customizer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($embroidery_pricing as $tier): ?>
                            <tr>
                                <td><?php echo esc_html($tier->min_quantity . ' - ' . ($tier->max_quantity == 999999 ? '∞' : $tier->max_quantity)); ?></td>
                                <td>£<?php echo esc_html($tier->price_per_item); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pricing-section">
                    <h2><?php esc_html_e('Print Pricing', 'wc-product-customizer'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Quantity Range', 'wc-product-customizer'); ?></th>
                                <th><?php esc_html_e('Price per Item', 'wc-product-customizer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($print_pricing as $tier): ?>
                            <tr>
                                <td><?php echo esc_html($tier->min_quantity . ' - ' . ($tier->max_quantity == 999999 ? '∞' : $tier->max_quantity)); ?></td>
                                <td>£<?php echo esc_html($tier->price_per_item); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Products page
     */
    public function products_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Product Customization Settings', 'wc-product-customizer'); ?></h1>
            
            <div class="bulk-actions">
                <h2><?php esc_html_e('Bulk Actions', 'wc-product-customizer'); ?></h2>
                <div class="button-container">
                <button type="button" class="button button-primary" id="bulk-enable-all">
                        <span class="button-icon">✓</span>
                    <?php esc_html_e('Enable Customization for All Products', 'wc-product-customizer'); ?>
                </button>
                <button type="button" class="button" id="bulk-disable-all">
                        <span class="button-icon">✕</span>
                    <?php esc_html_e('Disable Customization for All Products', 'wc-product-customizer'); ?>
                </button>
                </div>
            </div>
            
            <p><?php esc_html_e('Individual product customization settings can be configured in the product edit page.', 'wc-product-customizer'); ?></p>
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
        $zones = get_post_meta($post->ID, '_customization_zones', true);
        $methods = get_post_meta($post->ID, '_customization_methods', true);
        
        wp_nonce_field('wc_customization_meta', 'wc_customization_meta_nonce');
        ?>
        <table class="form-table">
            <tr>
                <th><label for="customization_enabled"><?php esc_html_e('Enable Customization', 'wc-product-customizer'); ?></label></th>
                <td>
                    <input type="checkbox" id="customization_enabled" name="customization_enabled" value="1" <?php checked($enabled, '1'); ?> />
                    <span class="description"><?php esc_html_e('Allow customers to customize this product', 'wc-product-customizer'); ?></span>
                </td>
            </tr>
        </table>
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
        update_post_meta($post_id, '_customization_enabled', $enabled);
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
}
