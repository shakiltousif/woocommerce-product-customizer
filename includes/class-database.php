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
    private static $db_version = '1.3.0';

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
            // Run migrations for existing installations
            if (version_compare($installed_version, '1.0.0', '>=')) {
                if (version_compare($installed_version, '1.1.0', '<')) {
                    self::migrate_to_1_1_0();
                }
                if (version_compare($installed_version, '1.2.0', '<')) {
                    self::migrate_to_1_2_0();
                }
                if (version_compare($installed_version, '1.3.0', '<')) {
                    self::migrate_to_1_3_0();
                }
            } else {
                // Fresh installation
            self::create_tables();
            }
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
            slug varchar(50) NOT NULL,
            description text,
            icon varchar(100) DEFAULT '🔧',
            text_setup_fee decimal(10,2) DEFAULT 0.00,
            logo_setup_fee decimal(10,2) DEFAULT 0.00,
            setup_fee decimal(10,2) DEFAULT 0.00,
            active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY name (name),
            UNIQUE KEY slug (slug)
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
            thumbnail_path varchar(255),
            thumbnail_url varchar(255),
            description text,
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
            type_id int(11) NOT NULL,
            method varchar(50) NOT NULL,
            min_quantity int(11) NOT NULL,
            max_quantity int(11) NOT NULL,
            price_per_item decimal(10,2) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type_id (type_id),
            KEY method (method),
            FOREIGN KEY (type_id) REFERENCES {$wpdb->prefix}wc_customization_types(id) ON DELETE CASCADE
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

        // Category Configurations table
        $table_name = $wpdb->prefix . 'wc_customization_category_configs';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            category_id int(11) NOT NULL,
            config_name varchar(100) NOT NULL,
            description text,
            enabled tinyint(1) DEFAULT 1,
            available_zones text,
            available_types text,
            custom_pricing_enabled tinyint(1) DEFAULT 0,
            custom_settings longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY category_config (category_id, config_name),
            KEY category_id (category_id)
        ) $charset_collate;";

        dbDelta($sql);

        // Insert default data
        self::insert_default_data();
    }

    /**
     * Migrate database to version 1.1.0
     */
    public static function migrate_to_1_1_0() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_zones';
        
        // Add new columns if they don't exist
        $columns_to_add = array(
            'thumbnail_path' => 'varchar(255)',
            'thumbnail_url' => 'varchar(255)',
            'description' => 'text'
        );
        
        foreach ($columns_to_add as $column => $type) {
            $column_exists = $wpdb->get_results($wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                DB_NAME, $table_name, $column
            ));
            
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN $column $type");
            }
        }
    }

    /**
     * Migrate to version 1.2.0
     */
    public static function migrate_to_1_2_0() {
        global $wpdb;
        
        // Update customization types table
        $types_table = $wpdb->prefix . 'wc_customization_types';
        
        // Add new columns if they don't exist
        $columns_to_add = array(
            'slug' => 'varchar(50)',
            'icon' => 'varchar(100)',
            'text_setup_fee' => 'decimal(10,2)',
            'logo_setup_fee' => 'decimal(10,2)',
            'sort_order' => 'int(11)'
        );
        
        foreach ($columns_to_add as $column => $type) {
            $column_exists = $wpdb->get_results($wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                DB_NAME, $types_table, $column
            ));
            
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $types_table ADD COLUMN $column $type");
            }
        }
        
        // Update existing types with slugs and icons
        $wpdb->query("UPDATE $types_table SET slug = 'embroidery', icon = '🧵', text_setup_fee = setup_fee, logo_setup_fee = setup_fee, sort_order = 1 WHERE name = 'embroidery'");
        $wpdb->query("UPDATE $types_table SET slug = 'print', icon = '🔥', text_setup_fee = setup_fee, logo_setup_fee = setup_fee, sort_order = 2 WHERE name = 'print'");
        
        // Add unique constraint for slug
        $wpdb->query("ALTER TABLE $types_table ADD UNIQUE KEY slug (slug)");
        
        // Update pricing table
        $pricing_table = $wpdb->prefix . 'wc_customization_pricing';
        
        // Add type_id column if it doesn't exist
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
            DB_NAME, $pricing_table, 'type_id'
        ));
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $pricing_table ADD COLUMN type_id int(11) NOT NULL AFTER id");
            $wpdb->query("ALTER TABLE $pricing_table ADD KEY type_id (type_id)");
        }
        
        // Link existing pricing tiers to types
        $wpdb->query("UPDATE $pricing_table p 
                      INNER JOIN $types_table t ON t.slug = p.method 
                      SET p.type_id = t.id 
                      WHERE p.type_id = 0");
    }

    /**
     * Migrate to version 1.3.0
     */
    public static function migrate_to_1_3_0() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create category configurations table
        $table_name = $wpdb->prefix . 'wc_customization_category_configs';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            category_id int(11) NOT NULL,
            config_name varchar(100) NOT NULL,
            description text,
            enabled tinyint(1) DEFAULT 1,
            available_zones text,
            available_types text,
            custom_pricing_enabled tinyint(1) DEFAULT 0,
            custom_settings longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY category_config (category_id, config_name),
            KEY category_id (category_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Insert default data
     */
    private static function insert_default_data() {
        global $wpdb;

        // Insert default customization types
        $types_table = $wpdb->prefix . 'wc_customization_types';
        $wpdb->query("INSERT IGNORE INTO $types_table (name, slug, description, icon, setup_fee, text_setup_fee, logo_setup_fee, sort_order) VALUES 
            ('Embroidery', 'embroidery', 'Embroidery (or stitching) is detailed and durable which is better suited to uniforms.', '🧵', 0.00, 0.00, 0.00, 1),
            ('Print', 'print', 'The print application method is both vivid and flexible, ideal for general use.', '🔥', 0.00, 0.00, 0.00, 2)");

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
     * Get all customization zones (active and inactive)
     *
     * @return array
     */
    public function get_all_customization_zones() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_zones';
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY zone_group, name");
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
     * Get all pricing tiers
     *
     * @return array
     */
    public function get_all_pricing_tiers() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_pricing';
        return $wpdb->get_results(
            "SELECT p.*, t.name as type_name, t.slug as type_slug, t.icon as type_icon 
             FROM $table_name p 
             LEFT JOIN {$wpdb->prefix}wc_customization_types t ON p.type_id = t.id 
             ORDER BY t.sort_order ASC, p.min_quantity ASC"
        );
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

    /**
     * Get zone by ID
     *
     * @param int $zone_id
     * @return object|null
     */
    public function get_zone_by_id($zone_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_zones';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $zone_id
        ));
    }

    /**
     * Save zone (insert or update)
     *
     * @param array $data
     * @return int|false
     */
    public function save_zone($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_zones';
        
        $zone_data = array(
            'name' => sanitize_text_field($data['name']),
            'zone_group' => sanitize_text_field($data['zone_group']),
            'zone_charge' => floatval($data['zone_charge']),
            'product_types' => sanitize_textarea_field($data['product_types']),
            'methods_available' => sanitize_text_field($data['methods_available']),
            'thumbnail_path' => sanitize_text_field($data['thumbnail_path']),
            'thumbnail_url' => esc_url_raw($data['thumbnail_url']),
            'description' => sanitize_textarea_field($data['description']),
            'active' => isset($data['active']) ? 1 : 0
        );
        
        if (isset($data['id']) && !empty($data['id'])) {
            // Update existing zone
            $result = $wpdb->update(
                $table_name,
                $zone_data,
                array('id' => intval($data['id'])),
                array('%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%d'),
                array('%d')
            );
            return $result !== false ? intval($data['id']) : false;
        } else {
            // Insert new zone
            $result = $wpdb->insert(
                $table_name,
                $zone_data,
                array('%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%d')
            );
            return $result ? $wpdb->insert_id : false;
        }
    }

    /**
     * Delete zone
     *
     * @param int $zone_id
     * @return bool
     */
    public function delete_zone($zone_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_zones';
        
        // Check if zone is used in orders
        if ($this->check_zone_usage($zone_id)) {
            return false; // Cannot delete zone that's in use
        }
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $zone_id),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Check if zone is used in orders
     *
     * @param int $zone_id
     * @return bool
     */
    public function check_zone_usage($zone_id) {
        global $wpdb;
        
        $orders_table = $wpdb->prefix . 'wc_customization_orders';
        
        $usage = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $orders_table WHERE zone_ids LIKE %s",
            '%' . $zone_id . '%'
        ));
        
        return $usage > 0;
    }

    /**
     * Update zone status
     *
     * @param int $zone_id
     * @param int $status
     * @return bool
     */
    public function update_zone_status($zone_id, $status) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_zones';
        
        $result = $wpdb->update(
            $table_name,
            array('active' => intval($status)),
            array('id' => $zone_id),
            array('%d'),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Get customization type by ID
     *
     * @param int $type_id
     * @return object|null
     */
    public function get_customization_type_by_id($type_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_types';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $type_id
        ));
    }

    /**
     * Get customization type by slug
     *
     * @param string $slug
     * @return object|null
     */
    public function get_customization_type_by_slug($slug) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_types';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE slug = %s",
            $slug
        ));
    }

    /**
     * Get all customization types
     *
     * @param bool $active_only
     * @return array
     */
    public function get_all_customization_types($active_only = false) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_types';
        $where_clause = $active_only ? "WHERE active = 1" : "";
        
        return $wpdb->get_results(
            "SELECT * FROM $table_name $where_clause ORDER BY sort_order ASC, name ASC"
        );
    }

    /**
     * Save customization type (insert or update)
     *
     * @param array $data
     * @return int|false
     */
    public function save_customization_type($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_types';
        
        $type_data = array(
            'name' => sanitize_text_field($data['name']),
            'slug' => sanitize_title($data['slug']),
            'description' => sanitize_textarea_field($data['description']),
            'icon' => sanitize_text_field($data['icon']),
            'text_setup_fee' => floatval($data['text_setup_fee']),
            'logo_setup_fee' => floatval($data['logo_setup_fee']),
            'setup_fee' => floatval($data['text_setup_fee']), // Keep for backward compatibility
            'sort_order' => intval($data['sort_order']),
            'active' => isset($data['active']) ? 1 : 0
        );
        
        if (isset($data['id']) && !empty($data['id'])) {
            // Update existing type
            $result = $wpdb->update(
                $table_name,
                $type_data,
                array('id' => intval($data['id'])),
                array('%s', '%s', '%s', '%s', '%f', '%f', '%f', '%d', '%d'),
                array('%d')
            );
            return $result !== false ? intval($data['id']) : false;
        } else {
            // Insert new type
            $result = $wpdb->insert(
                $table_name,
                $type_data,
                array('%s', '%s', '%s', '%s', '%f', '%f', '%f', '%d', '%d')
            );
            return $result ? $wpdb->insert_id : false;
        }
    }

    /**
     * Delete customization type
     *
     * @param int $type_id
     * @return bool
     */
    public function delete_customization_type($type_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_types';
        
        // Check if type is used in orders or has pricing tiers
        if ($this->check_type_usage($type_id)) {
            return false; // Cannot delete type that's in use
        }
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $type_id),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Check if customization type is used
     *
     * @param int $type_id
     * @return bool
     */
    public function check_type_usage($type_id) {
        global $wpdb;
        
        // Check if used in orders
        $orders_table = $wpdb->prefix . 'wc_customization_orders';
        $usage_in_orders = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $orders_table WHERE method = (SELECT slug FROM {$wpdb->prefix}wc_customization_types WHERE id = %d)",
            $type_id
        ));
        
        // Check if has pricing tiers
        $pricing_table = $wpdb->prefix . 'wc_customization_pricing';
        $usage_in_pricing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $pricing_table WHERE type_id = %d",
            $type_id
        ));
        
        return ($usage_in_orders > 0) || ($usage_in_pricing > 0);
    }

    /**
     * Get pricing tiers by type ID
     *
     * @param int $type_id
     * @return array
     */
    public function get_pricing_tiers_by_type_id($type_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_pricing';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE type_id = %d ORDER BY min_quantity ASC",
            $type_id
        ));
    }

    /**
     * Update customization type status
     *
     * @param int $type_id
     * @param int $status
     * @return bool
     */
    public function update_type_status($type_id, $status) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_types';
        
        $result = $wpdb->update(
            $table_name,
            array('active' => intval($status)),
            array('id' => $type_id),
            array('%d'),
            array('%d')
        );
        
        return $result !== false;
    }


    /**
     * Get pricing tier by ID
     *
     * @param int $tier_id
     * @return object|null
     */
    public function get_pricing_tier_by_id($tier_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_pricing';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $tier_id
        ));
    }

    /**
     * Save pricing tier (insert or update)
     *
     * @param array $data
     * @return int|false
     */
    public function save_pricing_tier($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_pricing';
        
        $tier_data = array(
            'type_id' => intval($data['type_id']),
            'method' => sanitize_text_field($data['method']),
            'min_quantity' => intval($data['min_quantity']),
            'max_quantity' => intval($data['max_quantity']),
            'price_per_item' => floatval($data['price_per_item'])
        );
        
        if (isset($data['id']) && !empty($data['id'])) {
            // Update existing tier
            $result = $wpdb->update(
                $table_name,
                $tier_data,
                array('id' => intval($data['id'])),
                array('%d', '%s', '%d', '%d', '%f'),
                array('%d')
            );
            return $result !== false ? intval($data['id']) : false;
        } else {
            // Insert new tier
            $result = $wpdb->insert(
                $table_name,
                $tier_data,
                array('%d', '%s', '%d', '%d', '%f')
            );
            return $result ? $wpdb->insert_id : false;
        }
    }

    /**
     * Delete pricing tier
     *
     * @param int $tier_id
     * @return bool
     */
    public function delete_pricing_tier($tier_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_pricing';
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $tier_id),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Validate pricing ranges for a type
     *
     * @param int $type_id
     * @param int $exclude_id
     * @return array
     */
    public function validate_pricing_ranges($type_id, $exclude_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_pricing';
        
        $where_clause = "type_id = %d";
        $where_values = array($type_id);
        
        if ($exclude_id) {
            $where_clause .= " AND id != %d";
            $where_values[] = $exclude_id;
        }
        
        $tiers = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE $where_clause ORDER BY min_quantity",
            $where_values
        ));
        
        $errors = array();
        $warnings = array();
        
        foreach ($tiers as $tier) {
            // Validate min < max
            if ($tier->min_quantity >= $tier->max_quantity) {
                $errors[] = sprintf(
                    __('Tier %d: Min quantity (%d) must be less than max quantity (%d)', 'wc-product-customizer'),
                    $tier->id,
                    $tier->min_quantity,
                    $tier->max_quantity
                );
            }
            
            // Check for overlaps with other tiers
            foreach ($tiers as $other_tier) {
                if ($tier->id === $other_tier->id) continue;
                
                if (($tier->min_quantity <= $other_tier->max_quantity) && 
                    ($tier->max_quantity >= $other_tier->min_quantity)) {
                    $errors[] = sprintf(
                        __('Tier %d overlaps with tier %d', 'wc-product-customizer'),
                        $tier->id,
                        $other_tier->id
                    );
                }
            }
        }
        
        // Check for gaps (warnings only)
        for ($i = 0; $i < count($tiers) - 1; $i++) {
            $current = $tiers[$i];
            $next = $tiers[$i + 1];
            
            if ($current->max_quantity + 1 < $next->min_quantity) {
                $warnings[] = sprintf(
                    __('Gap between tier %d (max: %d) and tier %d (min: %d)', 'wc-product-customizer'),
                    $current->id,
                    $current->max_quantity,
                    $next->id,
                    $next->min_quantity
                );
            }
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        );
    }

    // ========================================
    // Category Configuration Methods
    // ========================================

    /**
     * Get category configuration by ID
     *
     * @param int $config_id
     * @return object|null
     */
    public function get_category_config_by_id($config_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_category_configs';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $config_id
        ));
    }

    /**
     * Get category configurations by category ID
     *
     * @param int $category_id
     * @param bool $enabled_only
     * @return array
     */
    public function get_category_configs_by_category($category_id, $enabled_only = false) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_category_configs';
        $where_clause = "WHERE category_id = %d";
        $where_values = array($category_id);
        
        if ($enabled_only) {
            $where_clause .= " AND enabled = 1";
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name $where_clause ORDER BY config_name",
            $where_values
        ));
    }

    /**
     * Get all category configurations with category names
     *
     * @param bool $enabled_only
     * @return array
     */
    public function get_all_category_configs($enabled_only = false) {
        global $wpdb;
        
        $configs_table = $wpdb->prefix . 'wc_customization_category_configs';
        $terms_table = $wpdb->terms;
        $term_taxonomy_table = $wpdb->term_taxonomy;
        
        $where_clause = "";
        $where_values = array();
        
        if ($enabled_only) {
            $where_clause = "WHERE cc.enabled = 1";
        }
        
        $query = "SELECT cc.*, 
                         CASE 
                             WHEN t.name IS NOT NULL AND tt.taxonomy = 'product_cat' THEN t.name
                             ELSE 'Unknown Category'
                         END as category_name
                  FROM $configs_table cc
                  LEFT JOIN $terms_table t ON cc.category_id = t.term_id
                  LEFT JOIN $term_taxonomy_table tt ON t.term_id = tt.term_id AND tt.taxonomy = 'product_cat'
                  $where_clause
                  ORDER BY category_name, cc.config_name";
        
        if (!empty($where_values)) {
            return $wpdb->get_results($wpdb->prepare($query, $where_values));
        } else {
            return $wpdb->get_results($query);
        }
    }

    /**
     * Save category configuration
     *
     * @param array $data
     * @return int|false
     */
    public function save_category_config($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_category_configs';
        
        $defaults = array(
            'category_id' => 0,
            'config_name' => '',
            'description' => '',
            'enabled' => 1,
            'available_zones' => '',
            'available_types' => '',
            'custom_pricing_enabled' => 0,
            'custom_settings' => ''
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Serialize arrays
        if (is_array($data['available_zones'])) {
            $data['available_zones'] = maybe_serialize($data['available_zones']);
        }
        if (is_array($data['available_types'])) {
            $data['available_types'] = maybe_serialize($data['available_types']);
        }
        if (is_array($data['custom_settings'])) {
            $data['custom_settings'] = maybe_serialize($data['custom_settings']);
        }
        
        if (isset($data['id']) && $data['id'] > 0) {
            // Update existing
            $config_id = $data['id'];
            unset($data['id']);
            
            $result = $wpdb->update(
                $table_name,
                $data,
                array('id' => $config_id),
                array('%d', '%s', '%s', '%d', '%s', '%s', '%d', '%s'),
                array('%d')
            );
            
            return $result !== false ? $config_id : false;
        } else {
            // Insert new
            unset($data['id']);
            
            $result = $wpdb->insert(
                $table_name,
                $data,
                array('%d', '%s', '%s', '%d', '%s', '%s', '%d', '%s')
            );
            
            return $result ? $wpdb->insert_id : false;
        }
    }

    /**
     * Delete category configuration
     *
     * @param int $config_id
     * @return bool
     */
    public function delete_category_config($config_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_category_configs';
        
        return $wpdb->delete(
            $table_name,
            array('id' => $config_id),
            array('%d')
        ) !== false;
    }

    /**
     * Get product customization configuration
     * Checks product override first, then falls back to category
     *
     * @param int $product_id
     * @return object|null
     */
    public function get_product_customization_config($product_id) {
        // First check if product has a specific config override
        $product_config_id = get_post_meta($product_id, '_customization_config_id', true);
        
        if ($product_config_id) {
            return $this->get_category_config_by_id($product_config_id);
        }
        
        // Fall back to category-based config
        $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
        
        if (empty($product_categories)) {
            return null;
        }
        
        // Get the primary category (first one)
        $primary_category_id = $product_categories[0];
        
        // Get the default config for this category
        $configs = $this->get_category_configs_by_category($primary_category_id, true);
        
        return !empty($configs) ? $configs[0] : null;
    }

    /**
     * Get WooCommerce product categories
     *
     * @return array
     */
    public function get_product_categories() {
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        return is_wp_error($categories) ? array() : $categories;
    }

    /**
     * Get zones by IDs
     *
     * @param array $zone_ids
     * @return array
     */
    public function get_zones_by_ids($zone_ids) {
        global $wpdb;
        
        if (empty($zone_ids) || !is_array($zone_ids)) {
            return array();
        }
        
        $table_name = $wpdb->prefix . 'wc_customization_zones';
        $placeholders = implode(',', array_fill(0, count($zone_ids), '%d'));
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id IN ($placeholders) AND active = 1 ORDER BY name",
            $zone_ids
        ));
    }

    /**
     * Get customization types by IDs
     *
     * @param array $type_ids
     * @return array
     */
    public function get_types_by_ids($type_ids) {
        global $wpdb;
        
        if (empty($type_ids) || !is_array($type_ids)) {
            return array();
        }
        
        $table_name = $wpdb->prefix . 'wc_customization_types';
        $placeholders = implode(',', array_fill(0, count($type_ids), '%d'));
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id IN ($placeholders) AND active = 1 ORDER BY sort_order, name",
            $type_ids
        ));
    }
}
