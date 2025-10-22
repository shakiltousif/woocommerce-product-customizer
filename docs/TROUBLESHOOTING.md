# Troubleshooting & Support Playbook

Use this playbook to diagnose issues quickly. Check browser console and WooCommerce logs.

## Cart UI / Button Missing
- Ensure the product has customization enabled.
- Check theme overrides: `woocommerce/cart/cart.php` must include `woocommerce_after_cart_item_name`.
- Confirm plugin hooks are active: run `test-cart-integration.php` from plugin folder (CLI).

## Modal Opens But No Steps
- Open Console → look for 400 errors to `admin-ajax.php`.
- Confirm `wcCustomizerWizard` is defined in the console.
- Likely cause: nonce mismatch. Clear cache and check nonces in localized data and PHP.

## 400 Bad Request on AJAX
- Verify `nonce` param matches server `wp_verify_nonce()` for the expected action.
- Ensure `ajaxUrl` points to `/wp-admin/admin-ajax.php`.
- Disable security/firewall plugins temporarily to rule out blocking.

## File Upload Fails
- Check `wp-content/customization-files/` is writable by the web server.
- Validate file size and allowed types.
- Inspect server error logs (upload_tmp_dir, permissions).

## Wrong Prices
- Review tier tables under settings; ensure method names exactly match `embroidery` and `print`.
- Confirm quantity passed to pricing equals line item quantity.

## Orders Missing Customization Data
- Ensure `woocommerce_checkout_create_order_line_item` hook runs (no theme conflicts).
- Verify cart line contains `customization_data` before checkout.

## Diagnostics Tools Included
- `test-cart-integration.php`  verifies hooks, assets and product meta
- `test-database.php`  verifies tables and seed data
- `test-ajax.php`, `test-methods.php`, `test-nonce.php`  validate endpoints and nonces

## Getting Help
- Provide: WP/WC versions, theme name/version, active plugins, console logs, server PHP version, Apache/Nginx error logs.

