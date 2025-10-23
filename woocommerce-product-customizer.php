<?php
/**
 * Plugin Name: WooCommerce Product Customizer
 * Plugin URI: https://github.com/your-username/woocommerce-product-customizer
 * Description: Complete product customization solution for WooCommerce with embroidery and printing options. Replicates Workwear Express functionality.
 * Version: 1.0.5
 * Author: Shakil Ahamed
 * Author URI: https://shakilahamed.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-product-customizer
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 *
 * @package WooCommerce_Product_Customizer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_PRODUCT_CUSTOMIZER_VERSION', '1.0.5');
define('WC_PRODUCT_CUSTOMIZER_PLUGIN_FILE', __FILE__);
define('WC_PRODUCT_CUSTOMIZER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_PRODUCT_CUSTOMIZER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_PRODUCT_CUSTOMIZER_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class WooCommerce_Product_Customizer {

    /**
     * Plugin instance
     *
     * @var WooCommerce_Product_Customizer
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return WooCommerce_Product_Customizer
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
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'load_textdomain'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('WooCommerce_Product_Customizer', 'uninstall'));
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Load plugin classes
        $this->load_classes();
        
        // Initialize components
        $this->init_components();
    }

    /**
     * Check if WooCommerce is active
     *
     * @return bool
     */
    private function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('WooCommerce Product Customizer requires WooCommerce to be installed and active.', 'wc-product-customizer'); ?></p>
        </div>
        <?php
    }

    /**
     * Load plugin classes
     */
    private function load_classes() {
        // Autoload classes
        spl_autoload_register(array($this, 'autoload'));
        
        // Include required files
        require_once WC_PRODUCT_CUSTOMIZER_PLUGIN_DIR . 'includes/class-database.php';
        require_once WC_PRODUCT_CUSTOMIZER_PLUGIN_DIR . 'includes/class-admin.php';
        require_once WC_PRODUCT_CUSTOMIZER_PLUGIN_DIR . 'includes/class-cart-integration.php';
        require_once WC_PRODUCT_CUSTOMIZER_PLUGIN_DIR . 'includes/class-wizard.php';
        require_once WC_PRODUCT_CUSTOMIZER_PLUGIN_DIR . 'includes/class-pricing.php';
        require_once WC_PRODUCT_CUSTOMIZER_PLUGIN_DIR . 'includes/class-file-manager.php';
        require_once WC_PRODUCT_CUSTOMIZER_PLUGIN_DIR . 'includes/class-customization-page.php';
    }

    /**
     * Autoload classes
     *
     * @param string $class_name
     */
    public function autoload($class_name) {
        if (strpos($class_name, 'WC_Product_Customizer_') === 0) {
            $file_name = 'class-' . str_replace('_', '-', strtolower(substr($class_name, 22))) . '.php';
            $file_path = WC_PRODUCT_CUSTOMIZER_PLUGIN_DIR . 'includes/' . $file_name;
            
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }

    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize database
        WC_Product_Customizer_Database::get_instance();
        
        // Initialize admin interface (needed for AJAX handlers)
        WC_Product_Customizer_Admin::get_instance();
        
        // Initialize wizard (needed for AJAX handlers)
        WC_Product_Customizer_Wizard::get_instance();
        
        // Initialize cart integration (both admin and frontend)
        WC_Product_Customizer_Cart_Integration::get_instance();
        
        // Initialize pricing engine
        WC_Product_Customizer_Pricing::get_instance();
        
        // Initialize file manager
        WC_Product_Customizer_File_Manager::get_instance();
        
        // Initialize customization page
        WC_Product_Customizer_Page::get_instance();
    }

    /**
     * Load text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wc-product-customizer',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Check WordPress and PHP versions
        if (!$this->check_requirements()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('WooCommerce Product Customizer requires WordPress 5.8+ and PHP 7.4+', 'wc-product-customizer'));
        }

        // Create database tables
        require_once WC_PRODUCT_CUSTOMIZER_PLUGIN_DIR . 'includes/class-database.php';
        WC_Product_Customizer_Database::create_tables();
        
        // Create upload directory
        $this->create_upload_directory();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up temporary files
        $this->cleanup_temp_files();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Remove database tables
        require_once WC_PRODUCT_CUSTOMIZER_PLUGIN_DIR . 'includes/class-database.php';
        WC_Product_Customizer_Database::drop_tables();
        
        // Remove options
        delete_option('wc_product_customizer_version');
        delete_option('wc_product_customizer_settings');
        
        // Remove upload directory
        self::remove_upload_directory();
    }

    /**
     * Check plugin requirements
     *
     * @return bool
     */
    private function check_requirements() {
        global $wp_version;
        
        return (
            version_compare(PHP_VERSION, '7.4', '>=') &&
            version_compare($wp_version, '5.8', '>=')
        );
    }

    /**
     * Create upload directory
     */
    private function create_upload_directory() {
        $upload_dir = WP_CONTENT_DIR . '/customization-files/';
        
        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
            
            // Create .htaccess file for security
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "deny from all\n";
            file_put_contents($upload_dir . '.htaccess', $htaccess_content);
            
            // Create index.php file
            file_put_contents($upload_dir . 'index.php', '<?php // Silence is golden');
        }
    }

    /**
     * Set default options
     */
    private function set_default_options() {
        $default_settings = array(
            'setup_fees' => array(
                'text_embroidery' => 4.95,
                'text_print' => 4.95,
                'logo_embroidery' => 8.95,
                'logo_print' => 8.95
            ),
            'file_upload' => array(
                'max_size' => 8388608, // 8MB
                'allowed_types' => array('jpg', 'jpeg', 'png', 'pdf', 'ai', 'eps')
            ),
            'wizard' => array(
                'enabled' => true,
                'modal_mode' => true
            )
        );
        
        add_option('wc_product_customizer_settings', $default_settings);
        add_option('wc_product_customizer_version', WC_PRODUCT_CUSTOMIZER_VERSION);
    }

    /**
     * Clean up temporary files
     */
    private function cleanup_temp_files() {
        $upload_dir = WP_CONTENT_DIR . '/customization-files/';
        
        if (is_dir($upload_dir)) {
            $files = glob($upload_dir . 'temp_*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Remove upload directory
     */
    private static function remove_upload_directory() {
        $upload_dir = WP_CONTENT_DIR . '/customization-files/';
        
        if (is_dir($upload_dir)) {
            $files = array_diff(scandir($upload_dir), array('.', '..'));
            foreach ($files as $file) {
                $file_path = $upload_dir . $file;
                if (is_file($file_path)) {
                    unlink($file_path);
                }
            }
            rmdir($upload_dir);
        }
    }
}

// Initialize plugin
WooCommerce_Product_Customizer::get_instance();
