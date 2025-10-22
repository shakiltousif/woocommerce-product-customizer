# WooCommerce Product Customizer

A comprehensive product customization solution for WooCommerce that replicates the exact functionality of Workwear Express. This plugin allows customers to add logos and text to products with embroidery and printing options.

## Documentation

For the full documentation set, see the docs folder:
- docs/README.md (hub)
- docs/PLAN_FULL.md (complete plan)
- docs/USER_GUIDE.md, docs/ADMIN_GUIDE.md
- docs/DEVELOPER_GUIDE.md, docs/API_REFERENCE.md

## Features

### 🎨 **3-Step Wizard Interface**
- **Step 1**: Visual zone selection with product images
- **Step 2**: Application method choice (Embroidery/Print)
- **Step 3**: Logo upload with setup cost display
- Mobile-first responsive design
- Touch-friendly interface with swipe navigation

### 🛒 **Complete WooCommerce Integration**
- "Add logo to this item" button in cart
- Seamless cart and checkout integration
- Dynamic fee calculation and display
- Order management with customization details
- Email notifications with customization info

### 💰 **Dynamic Pricing Engine**
- Quantity-based pricing tiers
- One-time setup fees
- Real-time cost calculation
- Zone-specific charges
- Method-specific pricing (embroidery vs print)

### 🔐 **Secure File Management**
- Secure file upload outside web directory
- File type and size validation
- Malicious content scanning
- Customer logo library for returning customers
- Automatic cleanup of temporary files

### 👤 **Customer Account Integration**
- Logo library for returning customers
- "Returning Customer" login functionality
- Previous logo access and reuse
- Setup fee tracking for uploaded logos

### 📊 **Admin Dashboard**
- Comprehensive statistics and analytics
- Zone management with visual mapping
- Pricing tier configuration
- Product compatibility settings
- Bulk operations for products
- Export functionality for production

## Installation

1. Upload the plugin files to `/wp-content/plugins/woocommerce-product-customizer/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Customization > Settings** to configure the plugin
4. Enable customization for products in the product edit page

## Requirements

- WordPress 5.8 or higher
- WooCommerce 6.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Configuration

### Setup Fees
Configure one-time setup fees for different customization types:
- Text Embroidery: £4.95
- Text Print: £4.95
- Logo Embroidery: £8.95
- Logo Print: £8.95

### File Upload Settings
- Maximum file size: 8MB
- Allowed file types: JPG, JPEG, PNG, PDF, AI, EPS
- Secure storage outside web directory

### Pricing Tiers
Quantity-based pricing for both embroidery and print:

**Embroidery:**
- 1-8 items: £7.99 each
- 9-24 items: £6.25 each
- 25-99 items: £4.75 each
- 100-249 items: £3.75 each
- 250+ items: £2.75 each

**Print:**
- 1-8 items: £7.99 each
- 9-24 items: £5.99 each
- 25-99 items: £4.50 each
- 100-249 items: £3.50 each
- 250+ items: £2.50 each

## Usage

### For Customers

1. **Add Product to Cart**: Add any customizable product to your cart
2. **Start Customization**: Click "Add logo to this item" in the cart
3. **Choose Positions**: Select one or more positions for your logo/text
4. **Select Method**: Choose between embroidery or print application
5. **Upload Logo**: Upload your logo file or enter text
6. **Review & Confirm**: Review pricing and confirm your customization

### For Store Owners

1. **Enable Products**: Enable customization for products in the product edit page
2. **Manage Zones**: Configure available customization zones
3. **Set Pricing**: Configure pricing tiers and setup fees
4. **View Orders**: See customization details in order management
5. **Export Data**: Export customization data for production

## Database Tables

The plugin creates the following database tables:

- `wp_wc_customization_types` - Customization methods (embroidery, print)
- `wp_wc_customization_zones` - Available customization zones
- `wp_wc_customization_orders` - Order customization data
- `wp_wc_customization_customer_logos` - Customer logo library
- `wp_wc_customization_sessions` - Session management
- `wp_wc_customization_pricing` - Pricing tiers
- `wp_wc_customization_products` - Product compatibility

## File Structure

```
woocommerce-product-customizer/
├── woocommerce-product-customizer.php    # Main plugin file
├── includes/                             # Core PHP classes
│   ├── class-main.php                   # Main plugin class
│   ├── class-database.php               # Database operations
│   ├── class-admin.php                  # Admin interface
│   ├── class-cart-integration.php       # Cart functionality
│   ├── class-wizard.php                 # Frontend wizard
│   ├── class-pricing.php                # Pricing engine
│   └── class-file-manager.php           # File handling
├── assets/                              # Frontend assets
│   ├── css/                            # Stylesheets
│   ├── js/                             # JavaScript files
│   └── images/                         # Images and icons
├── templates/                           # Template files
├── languages/                           # Translation files
└── README.md                           # This file
```

## Security Features

- **File Upload Security**: Files stored outside web-accessible directory
- **Content Scanning**: Basic malicious content detection
- **Input Sanitization**: All user inputs properly sanitized
- **SQL Injection Prevention**: Prepared statements for all database queries
- **XSS Protection**: Output escaping for all dynamic content
- **CSRF Protection**: Nonce validation for all forms
- **Capability Checks**: Proper permission verification

## Performance Optimization

- **Asset Minification**: Compressed CSS and JavaScript files
- **Lazy Loading**: Wizard steps loaded on demand
- **Database Optimization**: Proper indexing and query optimization
- **Caching**: Object caching for frequently accessed data
- **Background Processing**: Queue heavy operations

## Browser Compatibility

- Chrome 70+
- Firefox 65+
- Safari 12+
- Edge 79+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Support

For support and documentation, please visit:
- Plugin documentation: [Link to documentation]
- Support forum: [Link to support]
- GitHub repository: [Link to GitHub]

## Changelog

### Version 1.0.0
- Initial release
- Complete 3-step wizard interface
- WooCommerce cart and checkout integration
- Dynamic pricing engine
- Secure file management
- Admin dashboard and configuration
- Customer account integration
- Mobile-responsive design

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by [Your Name] - Replicating the exact functionality of Workwear Express customization system.

---

**Note**: This plugin is designed to provide the exact same user experience as the Workwear Express customization system, with complete WooCommerce integration and mobile-first design.
