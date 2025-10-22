# Changelog

All notable changes to this project will be documented here.

## [1.0.5] - 2025-01-13
### Fixed
- **CRITICAL**: Fixed raw HTML appearing as text in mini-cart and popup overlays
- Fixed customization buttons showing as raw HTML code instead of rendered elements
- Fixed theme compatibility issues where buttons appeared as escaped HTML entities
- Fixed output buffer issues causing HTML to be displayed as plain text

### Enhanced
- **JavaScript-Only Solution**: Completely disabled problematic PHP hooks that caused raw HTML issues
- **Continuous HTML Cleanup**: Added aggressive JavaScript cleanup that runs every 2 seconds
- **Mutation Observer**: Real-time DOM monitoring to catch and fix any raw HTML immediately
- **Multi-Pattern Detection**: Enhanced cleanup to handle both raw HTML and escaped HTML entities
- **Event-Driven Cleanup**: Triggers cleanup on cart updates, mini-cart interactions, and DOM changes

### Technical
- **Disabled Problematic Hooks**: Commented out all PHP filters that were causing raw HTML display
- **Enhanced JavaScript Cleanup**: Added comprehensive text node scanning and HTML conversion
- **Continuous Monitoring**: SetInterval and MutationObserver for persistent cleanup
- **Pattern Matching**: Regex patterns to detect and convert both raw and escaped HTML
- **Fallback Mechanisms**: Multiple cleanup strategies to ensure no raw HTML persists

## [1.0.4] - 2025-01-13
### Fixed
- Fixed theme compatibility issues with printspace and other WooCommerce themes
- Fixed "Add logo to this item" button not appearing in cart with certain themes
- Fixed button positioning appearing to the left of product image instead of with product details
- Fixed awkward button styling that looked disconnected from the cart design

### Enhanced
- **Multi-Layer Theme Compatibility**: Added 9 different WooCommerce hooks for maximum theme support
- **Smart Button Positioning**: Buttons now appear in product details area after description/quantity
- **Professional Styling**: Redesigned buttons with clean, integrated appearance
- **JavaScript Fallback**: Robust DOM manipulation for themes that don't support standard hooks
- **Output Buffer Modification**: Direct HTML modification for stubborn themes

### Technical
- **Enhanced JavaScript Injection**: 12 different selectors to find cart items with fallback text search
- **Improved CSS Styling**: Better button appearance with hover effects and proper spacing
- **Debug Tools**: Added test scripts for product customization and cart integration debugging
- **Better Error Handling**: Comprehensive logging and fallback mechanisms
- **Theme-Agnostic Design**: Works with virtually any WooCommerce theme

## [1.0.3] - 2025-01-13
### Added
- **Text Input Option**: Users can now choose between uploading a logo or typing text directly
- **Content Type Selection**: Radio button interface to choose between "Upload Logo" or "Add Text"
- **Live Text Preview**: Real-time preview of text as user types with character counter (100 char limit)
- **Dynamic Pricing**: Different setup fees for logo (£8.95) vs text (£2.95) content
- **Text Content Display**: Text content shows in cart, orders, and admin with proper formatting
- **Responsive Text Interface**: Mobile-friendly text input with professional styling

### Enhanced
- **Step 3 UI**: Completely redesigned with content type selection and dual input options
- **Summary Display**: Dynamic summary that shows either logo preview or quoted text content
- **Validation Logic**: Smart validation that checks for logo upload OR text input based on selection
- **User Experience**: Seamless switching between logo and text input modes

### Technical
- **JavaScript Architecture**: Added content type handling and text input management
- **Data Structure**: Enhanced customization data to support both logo and text content
- **CSS Styling**: New styles for content type selection, text input, and text preview
- **Backend Integration**: Leveraged existing text_content support in database and cart integration

## [1.0.2] - 2025-01-13
### Fixed
- Fixed "Invalid product ID" error when clicking Edit from cart page
- Fixed selected items not highlighting in edit modal when accessed from cart
- Fixed TypeError: fee.toFixed is not a function in updateSetupFee method
- Fixed remove button functionality in cart - added JavaScript handler and AJAX endpoint
- Fixed race condition where UI updates happened before zones were rendered
- Fixed blank methods step when editing from cart
- Fixed product details not showing in wizard modal when editing from cart
- Fixed zone cards not highlighting when editing existing customization from cart
- Fixed method availability filtering based on selected zones
- Fixed setup fee calculation with proper error handling and data validation

### Added
- Developer credit footer to admin pages with Shakil Ahamed and website URL
- Comprehensive error handling for AJAX calls
- Robust data validation and type checking for PHP 8+ compatibility
- Enhanced debugging logs for troubleshooting
- Multiple selection strategies for zone and method matching
- Delayed fallback mechanism for UI highlighting
- Confirmation dialog for customization removal
- Loading states and user feedback for all operations

### Improved
- Enhanced modal responsiveness and modern UI design
- Better mobile layout with proper grid and height management
- Professional placeholder images for zones and methods
- Improved file upload security and accessibility
- Better error messages and user experience
- More robust cart integration and order display

## [1.0.1] - 2025-10-09
### Fixed
- Standardized nonce names across PHP and JS to resolve `admin-ajax.php` 400 errors for zones/methods requests
- Verified and documented AJAX endpoints; added tests for methods/zones
- Minor documentation improvements

## [1.0.0] - 2025-10-08
### Added
- Initial release with 3-step wizard (Positions → Method → Logo)
- WooCommerce integration (cart buttons, fees, order meta)
- Pricing engine with setup fees and quantity tiers
- Secure file upload and logo library
- Admin screens for configuration and product enablement

