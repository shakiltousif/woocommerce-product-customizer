<?php
/**
 * Database operations class
 *
 * @package WooCommerce_Product_Customizer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database class
 */
class WC_Product_Customizer_Database {

    /**
     * Instance
     *
     * @var WC_Product_Customizer_Database
     */
    private static $instance = null;

    /**
     * Database version
     *
     * @var string
     */
    private static $db_version = '1.0.0';

    /**
     * Get instance
     *
     * @return WC_Product_Customizer_Database
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
        add_action('init', array($this, 'check_database_version'));
    }

    /**
     * Check database version and update if needed
     */
    public function check_database_version() {
        $installed_version = get_option('wc_product_customizer_db_version', '0.0.0');
        
        if (version_compare($installed_version, self::$db_version, '<')) {
            self::create_tables();
            update_option('wc_product_customizer_db_version', self::$db_version);
        }
    }

    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Customization Types table
        $table_name = $wpdb->prefix . 'wc_customization_types';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            description text,
            setup_fee decimal(10,2) DEFAULT 0.00,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY name (name)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Customization Zones table
        $table_name = $wpdb->prefix . 'wc_customization_zones';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            zone_group varchar(50),
            zone_charge decimal(10,2) DEFAULT 0.00,
            product_types text,
            methods_available text,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY name (name)
        ) $charset_collate;";

        dbDelta($sql);

        // Customization Orders table
        $table_name = $wpdb->prefix . 'wc_customization_orders';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            order_id int(11) NOT NULL,
            cart_item_key varchar(32) NOT NULL,
            product_id int(11) NOT NULL,
            zone_ids text,
            method varchar(50),
            content_type varchar(20),
            file_path varchar(255),
            text_content text,
            setup_fee decimal(10,2) DEFAULT 0.00,
            application_fee decimal(10,2) DEFAULT 0.00,
            total_cost decimal(10,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY product_id (product_id),
            KEY cart_item_key (cart_item_key)
        ) $charset_collate;";

        dbDelta($sql);

        // Customer Logos table
        $table_name = $wpdb->prefix . 'wc_customization_customer_logos';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            customer_id int(11) NOT NULL,
            file_path varchar(255) NOT NULL,
            original_name varchar(255),
            file_hash varchar(64),
            setup_fee_paid tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY customer_id (customer_id),
            KEY file_hash (file_hash)
        ) $charset_collate;";

        dbDelta($sql);

        // Sessions table
        $table_name = $wpdb->prefix . 'wc_customization_sessions';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            session_id varchar(64) NOT NULL,
            cart_item_key varchar(32),
            step_data longtext,
            expires_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY expires_at (expires_at)
        ) $charset_collate;";

        dbDelta($sql);

        // Pricing table
        $table_name = $wpdb->prefix . 'wc_customization_pricing';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            method varchar(50) NOT NULL,
            min_quantity int(11) NOT NULL,
            max_quantity int(11) NOT NULL,
            price_per_item decimal(10,2) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY method (method)
        ) $charset_collate;";

        dbDelta($sql);

        // Product Compatibility table
        $table_name = $wpdb->prefix . 'wc_customization_products';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            product_id int(11) NOT NULL,
            enabled tinyint(1) DEFAULT 1,
            available_zones text,
            available_methods text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY product_id (product_id)
        ) $charset_collate;";

        dbDelta($sql);

        // Insert default data
        self::insert_default_data();
    }

    /**
     * Insert default data
     */
    private static function insert_default_data() {
        global $wpdb;

        // Insert default customization types
        $types_table = $wpdb->prefix . 'wc_customization_types';
        $wpdb->query("INSERT IGNORE INTO $types_table (name, description, setup_fee) VALUES 
            ('embroidery', 'Embroidery (or stitching) is detailed and durable which is better suited to uniforms.', 0.00),
            ('print', 'The print application method is both vivid and flexible, ideal for general use.', 0.00)");

        // Insert default zones
        $zones_table = $wpdb->prefix . 'wc_customization_zones';
        $wpdb->query("INSERT IGNORE INTO $zones_table (name, zone_group, methods_available) VALUES 
            ('Left Breast', 'Front', 'embroidery,print'),
            ('Right Breast', 'Front', 'embroidery,print'),
            ('Centre of Chest', 'Front', 'embroidery,print'),
            ('Left Sleeve', 'Sleeves', 'embroidery,print'),
            ('Right Sleeve', 'Sleeves', 'embroidery,print'),
            ('Big Front', 'Front', 'print'),
            ('Big Back', 'Back', 'print'),
            ('Nape of Neck', 'Back', 'embroidery,print')");

        // Insert default pricing tiers
        $pricing_table = $wpdb->prefix . 'wc_customization_pricing';
        $wpdb->query("INSERT IGNORE INTO $pricing_table (method, min_quantity, max_quantity, price_per_item) VALUES 
            ('embroidery', 1, 8, 7.99),
            ('embroidery', 9, 24, 6.25),
            ('embroidery', 25, 99, 4.75),
            ('embroidery', 100, 249, 3.75),
            ('embroidery', 250, 999999, 2.75),
            ('print', 1, 8, 7.99),
            ('print', 9, 24, 5.99),
            ('print', 25, 99, 4.50),
            ('print', 100, 249, 3.50),
            ('print', 250, 999999, 2.50)");
    }

    /**
     * Drop all tables
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'wc_customization_types',
            $wpdb->prefix . 'wc_customization_zones',
            $wpdb->prefix . 'wc_customization_orders',
            $wpdb->prefix . 'wc_customization_customer_logos',
            $wpdb->prefix . 'wc_customization_sessions',
            $wpdb->prefix . 'wc_customization_pricing',
            $wpdb->prefix . 'wc_customization_products'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        delete_option('wc_product_customizer_db_version');
    }

    /**
     * Get customization types
     *
     * @return array
     */
    public function get_customization_types() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_types';
        return $wpdb->get_results("SELECT * FROM $table_name WHERE active = 1 ORDER BY name");
    }

    /**
     * Get customization zones
     *
     * @return array
     */
    public function get_customization_zones() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_zones';
        return $wpdb->get_results("SELECT * FROM $table_name WHERE active = 1 ORDER BY zone_group, name");
    }

    /**
     * Get pricing tiers for method
     *
     * @param string $method
     * @return array
     */
    public function get_pricing_tiers($method) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_pricing';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE method = %s ORDER BY min_quantity",
            $method
        ));
    }

    /**
     * Save customization order data
     *
     * @param array $data
     * @return int|false
     */
    public function save_customization_order($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_orders';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'order_id' => $data['order_id'],
                'cart_item_key' => $data['cart_item_key'],
                'product_id' => $data['product_id'],
                'zone_ids' => maybe_serialize($data['zone_ids']),
                'method' => $data['method'],
                'content_type' => $data['content_type'],
                'file_path' => $data['file_path'],
                'text_content' => $data['text_content'],
                'setup_fee' => $data['setup_fee'],
                'application_fee' => $data['application_fee'],
                'total_cost' => $data['total_cost']
            ),
            array('%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f')
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get customization data for order
     *
     * @param int $order_id
     * @return array
     */
    public function get_order_customizations($order_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_orders';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE order_id = %d",
            $order_id
        ));
    }

    /**
     * Clean expired sessions
     */
    public function cleanup_expired_sessions() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_sessions';
        $wpdb->query("DELETE FROM $table_name WHERE expires_at < NOW()");
    }
}
