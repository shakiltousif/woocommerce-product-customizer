# WooCommerce Product Customizer - Installation Guide

## Prerequisites

Before installing the plugin, ensure your WordPress site meets these requirements:

### System Requirements
- **WordPress**: 5.8 or higher
- **WooCommerce**: 6.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **Memory Limit**: 256MB minimum (512MB recommended)
- **Max Upload Size**: 8MB minimum (for logo uploads)

### Server Requirements
- **mod_rewrite** enabled (for pretty permalinks)
- **GD Library** or **ImageMagick** (for image processing)
- **cURL** enabled (for external API calls)
- **ZIP extension** enabled (for file handling)

## Installation Steps

### Step 1: Download and Upload

1. **Download** the plugin files
2. **Upload** the entire `woocommerce-product-customizer` folder to `/wp-content/plugins/`
3. **Verify** all files are uploaded correctly

### Step 2: Activate Plugin

1. Go to **WordPress Admin > Plugins**
2. Find "WooCommerce Product Customizer"
3. Click **Activate**
4. Wait for activation to complete

### Step 3: Initial Configuration

After activation, the plugin will:
- Create 7 database tables
- Create upload directory: `/wp-content/customization-files/`
- Insert default data (zones, pricing tiers, customization types)
- Add admin menu under "Customization"

### Step 4: Configure Settings

1. Go to **Customization > Settings**
2. Configure basic settings:
   - **Enable/Disable** plugin functionality
   - **File Upload Settings** (max size, allowed types)
   - **Email Notifications** settings
   - **Security Settings**

### Step 5: Configure Pricing

1. Go to **Customization > Pricing**
2. Review and adjust pricing tiers:
   - **Setup Fees**: Text (£4.95), Logo (£8.95)
   - **Application Costs**: Quantity-based pricing
   - **Zone Charges**: Additional costs per zone

### Step 6: Manage Zones

1. Go to **Customization > Zones**
2. Review default zones:
   - Left Breast, Right Breast, Centre of Chest
   - Left Sleeve, Right Sleeve
   - Big Front, Big Back, Nape of Neck
3. Add/edit zones as needed
4. Configure which methods are available per zone

### Step 7: Enable Products

1. Edit any WooCommerce product
2. In the **Product Data** meta box
3. Check **"Enable Customization"**
4. Save the product

### Step 8: Test Functionality

1. Add a customizable product to cart
2. Click **"Add logo to this item"**
3. Complete the 3-step wizard
4. Verify pricing calculations
5. Test file upload
6. Complete checkout process

## Configuration Options

### File Upload Settings

```php
// Maximum file size (default: 8MB)
$max_file_size = 8388608;

// Allowed file types
$allowed_types = array('jpg', 'jpeg', 'png', 'pdf', 'ai', 'eps');

// Upload directory (relative to wp-content)
$upload_dir = 'customization-files';
```

### Pricing Configuration

```php
// Setup fees
$setup_fees = array(
    'text_embroidery' => 4.95,
    'text_print' => 4.95,
    'logo_embroidery' => 8.95,
    'logo_print' => 8.95
);

// Quantity-based pricing tiers
$pricing_tiers = array(
    array('min' => 1, 'max' => 8, 'embroidery' => 7.99, 'print' => 7.99),
    array('min' => 9, 'max' => 24, 'embroidery' => 6.25, 'print' => 5.99),
    // ... more tiers
);
```

### Security Settings

```php
// File validation
$security_settings = array(
    'scan_uploads' => true,
    'validate_mime_types' => true,
    'check_file_headers' => true,
    'quarantine_suspicious' => true
);
```

## Database Tables

The plugin creates these tables:

| Table | Purpose |
|-------|---------|
| `wp_wc_customization_types` | Customization methods (embroidery, print) |
| `wp_wc_customization_zones` | Available customization zones |
| `wp_wc_customization_orders` | Order customization data |
| `wp_wc_customization_customer_logos` | Customer logo library |
| `wp_wc_customization_sessions` | Session management |
| `wp_wc_customization_pricing` | Pricing tiers |
| `wp_wc_customization_products` | Product compatibility |

## File Structure

After installation, your plugin directory should look like:

```
wp-content/plugins/woocommerce-product-customizer/
├── woocommerce-product-customizer.php
├── includes/
│   ├── class-database.php
│   ├── class-admin.php
│   ├── class-cart-integration.php
│   ├── class-wizard.php
│   ├── class-pricing.php
│   └── class-file-manager.php
├── assets/
│   ├── css/
│   │   ├── wizard.css
│   │   └── admin.css
│   ├── js/
│   │   ├── wizard.js
│   │   └── admin.js
│   └── images/
│       ├── zones/
│       └── methods/
├── templates/
│   └── wizard/
├── languages/
├── README.md
├── INSTALLATION.md
└── PLAN.md
```

## Troubleshooting

### Common Issues

**Plugin won't activate:**
- Check PHP version (7.4+ required)
- Verify WooCommerce is active
- Check for plugin conflicts

**Database tables not created:**
- Check database permissions
- Verify MySQL version
- Check error logs

**File uploads failing:**
- Check upload directory permissions (755)
- Verify max upload size settings
- Check available disk space

**Wizard not loading:**
- Clear browser cache
- Check for JavaScript errors
- Verify asset files are loaded

### Error Messages

**"WooCommerce not detected":**
- Install and activate WooCommerce plugin
- Ensure WooCommerce version 6.0+

**"Database error":**
- Check database connection
- Verify table creation permissions
- Check MySQL error logs

**"File upload failed":**
- Check file size and type
- Verify upload directory permissions
- Check server upload limits

### Debug Mode

Enable debug mode by adding to wp-config.php:

```php
define('WC_CUSTOMIZER_DEBUG', true);
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Performance Optimization

### Recommended Settings

1. **Enable Object Caching**
2. **Use CDN for assets**
3. **Optimize database queries**
4. **Enable GZIP compression**
5. **Minify CSS/JS files**

### Server Configuration

```apache
# .htaccess optimizations
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/svg+xml "access plus 1 month"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

## Security Considerations

### File Upload Security

1. Files stored outside web-accessible directory
2. MIME type validation
3. File header checking
4. Malicious content scanning
5. Unique filename generation

### Data Protection

1. Input sanitization
2. Output escaping
3. SQL injection prevention
4. CSRF protection
5. Capability checks

## Support

For technical support:

1. Check this installation guide
2. Review error logs
3. Test with default theme
4. Disable other plugins
5. Contact support with details

## Uninstallation

To completely remove the plugin:

1. **Deactivate** the plugin
2. **Delete** plugin files
3. **Remove** database tables (optional)
4. **Clean up** upload directory (optional)

**Note**: Uninstalling will remove all customization data permanently.

---

**Installation Complete!** Your WooCommerce Product Customizer is now ready to use.
