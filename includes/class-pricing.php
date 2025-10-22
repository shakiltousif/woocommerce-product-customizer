<?php
/**
 * Pricing engine class
 *
 * @package WooCommerce_Product_Customizer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pricing class
 */
class WC_Product_Customizer_Pricing {

    /**
     * Instance
     *
     * @var WC_Product_Customizer_Pricing
     */
    private static $instance = null;

    /**
     * Setup fees
     *
     * @var array
     */
    private $setup_fees = array();

    /**
     * Pricing tiers
     *
     * @var array
     */
    private $pricing_tiers = array();

    /**
     * Get instance
     *
     * @return WC_Product_Customizer_Pricing
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
        $this->load_pricing_data();
        
        // AJAX handlers for real-time pricing
        add_action('wp_ajax_wc_customizer_calculate_pricing', array($this, 'ajax_calculate_pricing'));
        add_action('wp_ajax_nopriv_wc_customizer_calculate_pricing', array($this, 'ajax_calculate_pricing'));
    }

    /**
     * Load pricing data from settings and database
     */
    private function load_pricing_data() {
        // Load setup fees from customization types
        $this->setup_fees = $this->get_cached_setup_fees();

        // Load pricing tiers from database with caching
        $this->pricing_tiers = $this->get_cached_pricing_tiers();
    }

    /**
     * Get cached pricing tiers or load from database
     *
     * @return array
     */
    private function get_cached_pricing_tiers() {
        $cache_key = 'wc_customizer_pricing_tiers';
        $cached_tiers = get_transient($cache_key);
        
        if (false === $cached_tiers) {
            // Cache miss - load from database
            $db = WC_Product_Customizer_Database::get_instance();
            $embroidery_tiers = $db->get_pricing_tiers('embroidery');
            $print_tiers = $db->get_pricing_tiers('print');
            
            $cached_tiers = array(
                'embroidery' => $embroidery_tiers,
                'print' => $print_tiers
            );
            
            // Cache for 5 minutes
            set_transient($cache_key, $cached_tiers, 5 * MINUTE_IN_SECONDS);
        }
        
        return $cached_tiers;
    }

    /**
     * Get cached setup fees or load from customization types
     *
     * @return array
     */
    private function get_cached_setup_fees() {
        $cache_key = 'wc_customizer_setup_fees';
        $cached_fees = get_transient($cache_key);
        
        if (false === $cached_fees) {
            // Cache miss - load from customization types
            $db = WC_Product_Customizer_Database::get_instance();
            $types = $db->get_all_customization_types(true); // Only active types
            
            $cached_fees = array();
            foreach ($types as $type) {
                $cached_fees[$type->slug . '_text'] = floatval($type->text_setup_fee);
                $cached_fees[$type->slug . '_logo'] = floatval($type->logo_setup_fee);
            }
            
            // Cache for 5 minutes
            set_transient($cache_key, $cached_fees, 5 * MINUTE_IN_SECONDS);
        }
        
        return $cached_fees;
    }

    /**
     * Clear pricing cache
     */
    public function clear_pricing_cache() {
        delete_transient('wc_customizer_pricing_tiers');
        delete_transient('wc_customizer_setup_fees');
        $this->pricing_tiers = $this->get_cached_pricing_tiers();
        $this->setup_fees = $this->get_cached_setup_fees();
    }

    /**
     * Calculate total customization cost
     *
     * @param array $customization_data
     * @param int $quantity
     * @return array
     */
    public function calculate_total_cost($customization_data, $quantity = 1) {
        $costs = array(
            'setup_fee' => 0,
            'application_fee' => 0,
            'zone_charges' => 0,
            'total' => 0
        );

        // Calculate setup fee (one-time)
        $costs['setup_fee'] = $this->get_setup_fee(
            $customization_data['method'] ?? '',
            $customization_data['content_type'] ?? ''
        );

        // Calculate application cost (per item)
        $costs['application_fee'] = $this->get_application_cost(
            $customization_data['method'] ?? '',
            $quantity
        );

        // Calculate zone charges (per position, per item)
        $costs['zone_charges'] = $this->get_zone_charges(
            $customization_data['zones'] ?? array(),
            $quantity
        );

        // Calculate total
        $costs['total'] = $costs['setup_fee'] + ($costs['application_fee'] * $quantity) + $costs['zone_charges'];

        return $costs;
    }

    /**
     * Calculate customization fees for cart
     *
     * @param array $customization_data
     * @param int $quantity
     * @return array
     */
    public function calculate_customization_fees($customization_data, $quantity = 1) {
        $costs = $this->calculate_total_cost($customization_data, $quantity);
        
        return array(
            'setup_fee' => $costs['setup_fee'],
            'application_fee' => $costs['application_fee'],
            'zone_charges' => $costs['zone_charges'],
            'total' => $costs['total']
        );
    }

    /**
     * Get setup fee based on method and content type
     *
     * @param string $method
     * @param string $content_type
     * @return float
     */
    public function get_setup_fee($method, $content_type) {
        $key = $content_type . '_' . $method;
        return $this->setup_fees[$key] ?? 0;
    }

    /**
     * Get application cost based on method and quantity
     *
     * @param string $method
     * @param int $quantity
     * @return float
     */
    public function get_application_cost($method, $quantity) {
        // Find the pricing tier for this method and quantity
        $db = WC_Product_Customizer_Database::get_instance();
        $type = $db->get_customization_type_by_slug($method);
        
        if (!$type) {
            return 0;
        }
        
        $tiers = $db->get_pricing_tiers_by_type_id($type->id);
        
        foreach ($tiers as $tier) {
            if ($quantity >= $tier->min_quantity && $quantity <= $tier->max_quantity) {
                return floatval($tier->price_per_item);
            }
        }

        return 0;
    }

    /**
     * Get zone charges
     *
     * @param array $zones
     * @param int $quantity
     * @return float
     */
    public function get_zone_charges($zones, $quantity) {
        if (empty($zones)) {
            return 0;
        }

        global $wpdb;
        $zones_table = $wpdb->prefix . 'wc_customization_zones';
        
        $zone_ids = array_map('intval', $zones);
        $placeholders = implode(',', array_fill(0, count($zone_ids), '%d'));
        
        $total_zone_charge = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(zone_charge) FROM $zones_table WHERE id IN ($placeholders)",
            ...$zone_ids
        ));

        return floatval($total_zone_charge) * $quantity;
    }

    /**
     * Get pricing breakdown for display
     *
     * @param array $customization_data
     * @param int $quantity
     * @return array
     */
    public function get_pricing_breakdown($customization_data, $quantity = 1) {
        $costs = $this->calculate_total_cost($customization_data, $quantity);
        $method = $customization_data['method'] ?? '';
        $content_type = $customization_data['content_type'] ?? '';
        
        $breakdown = array();

        // Setup fee breakdown
        if ($costs['setup_fee'] > 0) {
            $breakdown[] = array(
                'label' => sprintf(__('%s %s Setup Fee', 'wc-product-customizer'), ucfirst($content_type), ucfirst($method)),
                'cost' => $costs['setup_fee'],
                'type' => 'setup'
            );
        }

        // Application fee breakdown
        if ($costs['application_fee'] > 0) {
            $breakdown[] = array(
                'label' => sprintf(__('%s Application (%d items)', 'wc-product-customizer'), ucfirst($method), $quantity),
                'cost' => $costs['application_fee'] * $quantity,
                'type' => 'application'
            );
        }

        // Zone charges breakdown
        if ($costs['zone_charges'] > 0) {
            $zone_count = count($customization_data['zones'] ?? array());
            $breakdown[] = array(
                'label' => sprintf(__('Zone Charges (%d positions)', 'wc-product-customizer'), $zone_count),
                'cost' => $costs['zone_charges'],
                'type' => 'zones'
            );
        }

        return $breakdown;
    }

    /**
     * Get quantity-based pricing tiers for method
     *
     * @param string $method
     * @return array
     */
    public function get_quantity_tiers($method) {
        if (!isset($this->pricing_tiers[$method])) {
            return array();
        }

        $tiers = array();
        foreach ($this->pricing_tiers[$method] as $tier) {
            $tiers[] = array(
                'min_quantity' => $tier->min_quantity,
                'max_quantity' => $tier->max_quantity,
                'price' => floatval($tier->price_per_item),
                'label' => $this->format_quantity_range($tier->min_quantity, $tier->max_quantity)
            );
        }

        return $tiers;
    }

    /**
     * Format quantity range for display
     *
     * @param int $min
     * @param int $max
     * @return string
     */
    private function format_quantity_range($min, $max) {
        if ($max >= 999999) {
            return $min . '+';
        }
        
        if ($min === $max) {
            return (string) $min;
        }
        
        return $min . '-' . $max;
    }

    /**
     * Check if customer has setup fee paid for logo
     *
     * @param int $customer_id
     * @param string $file_hash
     * @return bool
     */
    public function has_setup_fee_paid($customer_id, $file_hash) {
        if (!$customer_id || !$file_hash) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_customization_customer_logos';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT setup_fee_paid FROM $table_name WHERE customer_id = %d AND file_hash = %s",
            $customer_id,
            $file_hash
        ));

        return (bool) $result;
    }

    /**
     * Apply bulk discount if applicable
     *
     * @param float $total_cost
     * @param int $quantity
     * @return float
     */
    public function apply_bulk_discount($total_cost, $quantity) {
        // Define bulk discount thresholds
        $discounts = array(
            100 => 0.05,  // 5% discount for 100+ items
            250 => 0.10,  // 10% discount for 250+ items
            500 => 0.15,  // 15% discount for 500+ items
            1000 => 0.20  // 20% discount for 1000+ items
        );

        $discount_rate = 0;
        foreach ($discounts as $threshold => $rate) {
            if ($quantity >= $threshold) {
                $discount_rate = $rate;
            }
        }

        if ($discount_rate > 0) {
            $discount_amount = $total_cost * $discount_rate;
            return $total_cost - $discount_amount;
        }

        return $total_cost;
    }

    /**
     * Get pricing summary for display
     *
     * @param array $customization_data
     * @param int $quantity
     * @return array
     */
    public function get_pricing_summary($customization_data, $quantity = 1) {
        $costs = $this->calculate_total_cost($customization_data, $quantity);
        $breakdown = $this->get_pricing_breakdown($customization_data, $quantity);
        
        return array(
            'breakdown' => $breakdown,
            'subtotal' => $costs['total'],
            'bulk_discount' => 0, // Calculate if applicable
            'total' => $costs['total'],
            'per_item_cost' => $quantity > 0 ? $costs['total'] / $quantity : 0
        );
    }

    /**
     * AJAX handler for real-time pricing calculation
     */
    public function ajax_calculate_pricing() {
        check_ajax_referer('wc_customizer_pricing', 'nonce');
        
        $customization_data = $this->sanitize_pricing_data($_POST['customization_data'] ?? array());
        $quantity = intval($_POST['quantity'] ?? 1);
        
        if (empty($customization_data)) {
            wp_send_json_error(array('message' => __('Invalid customization data', 'wc-product-customizer')));
        }

        // Clear cache to ensure fresh pricing data
        $this->clear_pricing_cache();

        $pricing = $this->get_pricing_summary($customization_data, $quantity);
        
        // Get detailed breakdown including tier information
        $detailed_breakdown = $this->get_detailed_pricing_breakdown($customization_data, $quantity);
        
        wp_send_json_success(array(
            'pricing' => $pricing,
            'formatted' => array(
                'setup_fee' => '£' . number_format($this->get_setup_fee($customization_data['method'], $customization_data['content_type']), 2),
                'application_fee' => '£' . number_format($this->get_application_cost($customization_data['method'], $quantity), 2),
                'total' => '£' . number_format($pricing['total'], 2)
            ),
            'detailed_breakdown' => $detailed_breakdown
        ));
    }

    /**
     * Sanitize pricing data
     *
     * @param array $data
     * @return array
     */
    private function sanitize_pricing_data($data) {
        $sanitized = array();
        
        if (isset($data['zones']) && is_array($data['zones'])) {
            $sanitized['zones'] = array_map('intval', $data['zones']);
        }
        
        if (isset($data['method'])) {
            $sanitized['method'] = sanitize_text_field($data['method']);
        }
        
        if (isset($data['content_type'])) {
            $sanitized['content_type'] = sanitize_text_field($data['content_type']);
        }
        
        return $sanitized;
    }

    /**
     * Get detailed pricing breakdown with tier information
     *
     * @param array $customization_data
     * @param int $quantity
     * @return array
     */
    private function get_detailed_pricing_breakdown($customization_data, $quantity) {
        $breakdown = array();
        
        if (isset($customization_data['method']) && isset($customization_data['content_type'])) {
            $method = $customization_data['method'];
            $content_type = $customization_data['content_type'];
            
            $tier = $this->get_pricing_tier_for_quantity($method, $quantity);
            
            if ($tier) {
                $setup_fee = $this->get_setup_fee($method, $content_type);
                $application_cost = $this->get_application_cost($method, $quantity);
                
                $breakdown[] = array(
                    'method' => $method,
                    'content_type' => $content_type,
                    'quantity' => $quantity,
                    'tier_range' => $tier->min_quantity . ' - ' . ($tier->max_quantity == 999999 ? '∞' : $tier->max_quantity),
                    'price_per_item' => $tier->price_per_item,
                    'total_application_cost' => $application_cost,
                    'setup_fee' => $setup_fee,
                    'total_cost' => $setup_fee + $application_cost
                );
            }
        }
        
        return $breakdown;
    }

    /**
     * Get pricing tier for specific quantity and method
     *
     * @param string $method
     * @param int $quantity
     * @return object|null
     */
    private function get_pricing_tier_for_quantity($method, $quantity) {
        if (!isset($this->pricing_tiers[$method])) {
            return null;
        }
        
        foreach ($this->pricing_tiers[$method] as $tier) {
            if ($quantity >= $tier->min_quantity && $quantity <= $tier->max_quantity) {
                return $tier;
            }
        }
        
        return null;
    }

    /**
     * Get all setup fees
     *
     * @return array
     */
    public function get_all_setup_fees() {
        return $this->setup_fees;
    }

    /**
     * Update setup fees
     *
     * @param array $fees
     * @return bool
     */
    public function update_setup_fees($fees) {
        $settings = get_option('wc_product_customizer_settings', array());
        $settings['setup_fees'] = $fees;
        
        $result = update_option('wc_product_customizer_settings', $settings);
        
        if ($result) {
            $this->setup_fees = $fees;
        }
        
        return $result;
    }

    /**
     * Get pricing tier for specific quantity and method
     *
     * @param string $method
     * @param int $quantity
     * @return object|null
     */
    public function get_pricing_tier($method, $quantity) {
        if (!isset($this->pricing_tiers[$method])) {
            return null;
        }

        foreach ($this->pricing_tiers[$method] as $tier) {
            if ($quantity >= $tier->min_quantity && $quantity <= $tier->max_quantity) {
                return $tier;
            }
        }

        return null;
    }

    /**
     * Calculate savings compared to single item pricing
     *
     * @param string $method
     * @param int $quantity
     * @return array
     */
    public function calculate_savings($method, $quantity) {
        $single_price = $this->get_application_cost($method, 1);
        $bulk_price = $this->get_application_cost($method, $quantity);
        
        $single_total = $single_price * $quantity;
        $bulk_total = $bulk_price * $quantity;
        
        $savings = $single_total - $bulk_total;
        $savings_percentage = $single_total > 0 ? ($savings / $single_total) * 100 : 0;
        
        return array(
            'single_price' => $single_price,
            'bulk_price' => $bulk_price,
            'savings_amount' => $savings,
            'savings_percentage' => $savings_percentage,
            'total_saved' => $savings
        );
    }
}
