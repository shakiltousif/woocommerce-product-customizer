<?php
/**
 * File manager class for secure file handling
 *
 * @package WooCommerce_Product_Customizer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * File Manager class
 */
class WC_Product_Customizer_File_Manager {

    /**
     * Instance
     *
     * @var WC_Product_Customizer_File_Manager
     */
    private static $instance = null;

    /**
     * Upload directory
     *
     * @var string
     */
    private $upload_dir;

    /**
     * Upload URL
     *
     * @var string
     */
    private $upload_url;

    /**
     * Allowed file types
     *
     * @var array
     */
    private $allowed_types = array('image/jpeg', 'image/png', 'application/pdf', 'application/postscript', 'application/illustrator');

    /**
     * Maximum file size (8MB)
     *
     * @var int
     */
    private $max_file_size = 8388608;

    /**
     * Get instance
     *
     * @return WC_Product_Customizer_File_Manager
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
        // Use WordPress uploads directory instead of custom directory
        $upload_dir = wp_upload_dir();
        $this->upload_dir = $upload_dir['basedir'] . '/customization-files/';
        $this->upload_url = $upload_dir['baseurl'] . '/customization-files/';
        $this->ensure_upload_directory();
        $this->load_settings();
        
        // AJAX handlers
        add_action('wp_ajax_wc_customizer_upload_file', array($this, 'ajax_upload_file'));
        add_action('wp_ajax_nopriv_wc_customizer_upload_file', array($this, 'ajax_upload_file'));
        add_action('wp_ajax_wc_customizer_delete_file', array($this, 'ajax_delete_file'));
        add_action('wp_ajax_nopriv_wc_customizer_delete_file', array($this, 'ajax_delete_file'));
        
        // Cleanup hooks
        add_action('wp_scheduled_delete', array($this, 'cleanup_temp_files'));
        add_action('wc_customizer_cleanup_files', array($this, 'cleanup_expired_files'));
        
        // Schedule cleanup if not already scheduled
        if (!wp_next_scheduled('wc_customizer_cleanup_files')) {
            wp_schedule_event(time(), 'daily', 'wc_customizer_cleanup_files');
        }
    }

    /**
     * Load settings
     */
    private function load_settings() {
        $settings = get_option('wc_product_customizer_settings', array());
        
        if (isset($settings['file_upload']['max_size'])) {
            $this->max_file_size = intval($settings['file_upload']['max_size']);
        }
        
        if (isset($settings['file_upload']['allowed_types'])) {
            $this->allowed_types = $this->map_extensions_to_mime_types($settings['file_upload']['allowed_types']);
        }
    }

    /**
     * Map file extensions to MIME types
     *
     * @param array $extensions
     * @return array
     */
    private function map_extensions_to_mime_types($extensions) {
        // Ensure $extensions is an array
        if (is_string($extensions)) {
            $extensions = explode(',', $extensions);
            $extensions = array_map('trim', $extensions);
        }
        
        if (!is_array($extensions)) {
            return array();
        }
        
        $mime_map = array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            'ai' => 'application/illustrator',
            'eps' => 'application/postscript'
        );
        
        $mime_types = array();
        foreach ($extensions as $ext) {
            if (isset($mime_map[$ext])) {
                $mime_types[] = $mime_map[$ext];
            }
        }
        
        return $mime_types;
    }

    /**
     * Ensure upload directory exists and is secure
     */
    private function ensure_upload_directory() {
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
        }
        
        // Create .htaccess file for security (allow images, deny scripts)
        $htaccess_file = $this->upload_dir . '.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "Options -Indexes\n\n";
            $htaccess_content .= "# Allow access to image files\n";
            $htaccess_content .= "<FilesMatch \"\\.(jpg|jpeg|png|gif|bmp|webp|svg)$\">\n";
            $htaccess_content .= "    Order Allow,Deny\n";
            $htaccess_content .= "    Allow from all\n";
            $htaccess_content .= "</FilesMatch>\n\n";
            $htaccess_content .= "# Deny access to other file types for security\n";
            $htaccess_content .= "<FilesMatch \"\\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$\">\n";
            $htaccess_content .= "    Order Deny,Allow\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</FilesMatch>\n";
            file_put_contents($htaccess_file, $htaccess_content);
        }
        
        // Create index.php file
        $index_file = $this->upload_dir . 'index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }
    }

    /**
     * Handle file upload
     *
     * @param array $file
     * @param string $session_id
     * @return array
     */
    public function handle_upload($file, $session_id = null) {
        // Generate session ID if not provided
        if (!$session_id) {
            $session_id = $this->generate_session_id();
        }
        
        // Validate file
        $validation = $this->validate_file($file);
        if (!$validation['valid']) {
            return array('success' => false, 'error' => $validation['error']);
        }
        
        // Generate secure filename
        $filename = $this->generate_secure_filename($file['name'], $session_id);
        $filepath = $this->upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Additional security: scan file content
            try {
                $this->scan_file_content($filepath);
            } catch (Exception $e) {
                unlink($filepath);
                return array('success' => false, 'error' => $e->getMessage());
            }
            
            // Generate file hash for duplicate detection
            $file_hash = $this->generate_file_hash($filepath);
            
            return array(
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'url' => $this->upload_url . $filename,
                'file_hash' => $file_hash,
                'original_name' => $file['name'],
                'file_size' => filesize($filepath),
                'session_id' => $session_id
            );
        }
        
        return array('success' => false, 'error' => __('Upload failed', 'wc-product-customizer'));
    }

    /**
     * Validate uploaded file
     *
     * @param array $file
     * @return array
     */
    private function validate_file($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array('valid' => false, 'error' => $this->get_upload_error_message($file['error']));
        }
        
        // Check file size
        if ($file['size'] > $this->max_file_size) {
            $max_size_mb = $this->max_file_size / 1048576;
            return array('valid' => false, 'error' => sprintf(__('File too large (max %dMB)', 'wc-product-customizer'), $max_size_mb));
        }
        
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $this->allowed_types)) {
            return array('valid' => false, 'error' => __('Invalid file type', 'wc-product-customizer'));
        }
        
        // Check for malicious content
        if ($this->contains_malicious_content($file['tmp_name'])) {
            return array('valid' => false, 'error' => __('File contains suspicious content', 'wc-product-customizer'));
        }
        
        return array('valid' => true);
    }

    /**
     * Get upload error message
     *
     * @param int $error_code
     * @return string
     */
    private function get_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return __('File is too large', 'wc-product-customizer');
            case UPLOAD_ERR_PARTIAL:
                return __('File was only partially uploaded', 'wc-product-customizer');
            case UPLOAD_ERR_NO_FILE:
                return __('No file was uploaded', 'wc-product-customizer');
            case UPLOAD_ERR_NO_TMP_DIR:
                return __('Missing temporary folder', 'wc-product-customizer');
            case UPLOAD_ERR_CANT_WRITE:
                return __('Failed to write file to disk', 'wc-product-customizer');
            case UPLOAD_ERR_EXTENSION:
                return __('File upload stopped by extension', 'wc-product-customizer');
            default:
                return __('Unknown upload error', 'wc-product-customizer');
        }
    }

    /**
     * Generate secure filename
     *
     * @param string $original_name
     * @param string $session_id
     * @return string
     */
    private function generate_secure_filename($original_name, $session_id) {
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $timestamp = time();
        $random = wp_generate_password(8, false);
        
        return "custom_{$session_id}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Generate session ID
     *
     * @return string
     */
    private function generate_session_id() {
        return wp_generate_password(16, false);
    }

    /**
     * Generate file hash for duplicate detection
     *
     * @param string $filepath
     * @return string
     */
    private function generate_file_hash($filepath) {
        return hash_file('sha256', $filepath);
    }

    /**
     * Scan file content for security threats
     *
     * @param string $filepath
     * @throws Exception
     */
    private function scan_file_content($filepath) {
        // Basic content scanning for security
        $content = file_get_contents($filepath, false, null, 0, 1024);
        
        // Check for script tags, PHP code, etc.
        $malicious_patterns = array(
            '/<script/i',
            '/<\?php/i',
            '/eval\(/i',
            '/base64_decode/i',
            '/exec\(/i',
            '/system\(/i',
            '/shell_exec/i'
        );
        
        foreach ($malicious_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new Exception(__('Malicious content detected', 'wc-product-customizer'));
            }
        }
    }

    /**
     * Check for malicious content in file
     *
     * @param string $filepath
     * @return bool
     */
    private function contains_malicious_content($filepath) {
        try {
            $this->scan_file_content($filepath);
            return false;
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * Delete file
     *
     * @param string $filename
     * @return bool
     */
    public function delete_file($filename) {
        $filepath = $this->upload_dir . $filename;
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }

    /**
     * Get file info
     *
     * @param string $filename
     * @return array|false
     */
    public function get_file_info($filename) {
        $filepath = $this->upload_dir . $filename;
        
        if (!file_exists($filepath)) {
            return false;
        }
        
        return array(
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => filesize($filepath),
            'modified' => filemtime($filepath),
            'hash' => $this->generate_file_hash($filepath)
        );
    }

    /**
     * Save customer logo to library
     *
     * @param int $customer_id
     * @param string $filepath
     * @param string $original_name
     * @return bool
     */
    public function save_customer_logo($customer_id, $filepath, $original_name) {
        global $wpdb;
        
        $file_hash = $this->generate_file_hash($filepath);
        $table_name = $wpdb->prefix . 'wc_customization_customer_logos';
        
        return $wpdb->insert(
            $table_name,
            array(
                'customer_id' => $customer_id,
                'file_path' => $filepath,
                'original_name' => $original_name,
                'file_hash' => $file_hash,
                'setup_fee_paid' => true,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s')
        );
    }

    /**
     * Get customer logos
     *
     * @param int $customer_id
     * @return array
     */
    public function get_customer_logos($customer_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_customization_customer_logos';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE customer_id = %d ORDER BY created_at DESC",
            $customer_id
        ));
    }

    /**
     * Cleanup temporary files
     */
    public function cleanup_temp_files() {
        $files = glob($this->upload_dir . 'temp_*');
        $current_time = time();
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $file_time = filemtime($file);
                // Delete files older than 24 hours
                if (($current_time - $file_time) > 86400) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Cleanup expired files
     */
    public function cleanup_expired_files() {
        global $wpdb;
        
        // Clean up expired sessions
        $sessions_table = $wpdb->prefix . 'wc_customization_sessions';
        $expired_sessions = $wpdb->get_results(
            "SELECT * FROM $sessions_table WHERE expires_at < NOW()"
        );
        
        foreach ($expired_sessions as $session) {
            $step_data = maybe_unserialize($session->step_data);
            if (isset($step_data['file_path'])) {
                $this->delete_file(basename($step_data['file_path']));
            }
        }
        
        // Delete expired session records
        $wpdb->query("DELETE FROM $sessions_table WHERE expires_at < NOW()");
        
        // Clean up orphaned files (files not referenced in any order or session)
        $this->cleanup_orphaned_files();
    }

    /**
     * Cleanup orphaned files
     */
    private function cleanup_orphaned_files() {
        global $wpdb;
        
        $files = glob($this->upload_dir . 'custom_*');
        $current_time = time();
        
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            
            $filename = basename($file);
            $file_time = filemtime($file);
            
            // Skip files newer than 7 days
            if (($current_time - $file_time) < 604800) {
                continue;
            }
            
            // Check if file is referenced in orders
            $orders_table = $wpdb->prefix . 'wc_customization_orders';
            $order_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $orders_table WHERE file_path LIKE %s",
                '%' . $filename
            ));
            
            // Check if file is referenced in customer logos
            $logos_table = $wpdb->prefix . 'wc_customization_customer_logos';
            $logo_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $logos_table WHERE file_path LIKE %s",
                '%' . $filename
            ));
            
            // Check if file is referenced in active sessions
            $sessions_table = $wpdb->prefix . 'wc_customization_sessions';
            $session_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $sessions_table WHERE step_data LIKE %s AND expires_at > NOW()",
                '%' . $filename . '%'
            ));
            
            // Delete if not referenced anywhere
            if ($order_count == 0 && $logo_count == 0 && $session_count == 0) {
                unlink($file);
            }
        }
    }

    /**
     * AJAX handler for file upload
     */
    public function ajax_upload_file() {
        check_ajax_referer('wc_customizer_upload', 'nonce');
        
        if (!isset($_FILES['file'])) {
            wp_send_json_error(array('message' => __('No file uploaded', 'wc-product-customizer')));
        }
        
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $result = $this->handle_upload($_FILES['file'], $session_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX handler for file deletion
     */
    public function ajax_delete_file() {
        check_ajax_referer('wc_customizer_upload', 'nonce');
        
        $filename = sanitize_file_name($_POST['filename'] ?? '');
        
        if (empty($filename)) {
            wp_send_json_error(array('message' => __('Invalid filename', 'wc-product-customizer')));
        }
        
        $result = $this->delete_file($filename);
        
        if ($result) {
            wp_send_json_success(array('message' => __('File deleted successfully', 'wc-product-customizer')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete file', 'wc-product-customizer')));
        }
    }

    /**
     * Get upload directory URL (for admin access only)
     *
     * @return string
     */
    public function get_upload_url() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/customization-files/';
    }

    /**
     * Get allowed file types for display
     *
     * @return array
     */
    public function get_allowed_file_types() {
        $settings = get_option('wc_product_customizer_settings', array());
        return $settings['file_upload']['allowed_types'] ?? array('jpg', 'jpeg', 'png', 'pdf', 'ai', 'eps');
    }

    /**
     * Get maximum file size for display
     *
     * @return int
     */
    public function get_max_file_size() {
        return $this->max_file_size;
    }

    /**
     * Get maximum file size in MB
     *
     * @return float
     */
    public function get_max_file_size_mb() {
        return $this->max_file_size / 1048576;
    }
}
