# Admin & Operations Guide

This guide explains how to configure, operate, and maintain the plugin in production.

## Installation
1. Upload the folder `woocommerce-product-customizer` to `wp-content/plugins/`.
2. Activate in WP Admin → Plugins.
3. Go to Customization → Settings and complete initial configuration.

## Quick Start Checklist
- [ ] Confirm WooCommerce is active.
- [ ] Configure setup fees and allowed file types.
- [ ] Verify pricing tiers (Embroidery and Print tables).
- [ ] Enable customization on selected products.
- [ ] Test the cart wizard end-to-end.

## Settings
- Setup Fees: One-time fees for Text and Logo by method.
- File Upload: Max size, allowed extensions (JPG, JPEG, PNG, PDF, AI, EPS).
- Logo Library: Allow customers to reuse approved logos.
- Security: Nonce, MIME validation, storage outside web root (auto-managed).

## Product Enablement
1. Edit a product in WooCommerce.
2. Enable the toggle "Enable Customization".
3. Optionally restrict available zones or methods for this product.

## Zones & Methods
- Zones: Named positions like Left Breast, Right Sleeve, Big Back; can be grouped (Front, Back, Sleeves).
- Methods: Embroidery, Print (each with pricing tiers and setup fee context).

## Pricing
- Quantity tiers per method.
- Per-zone fixed charges (optional) can be applied cumulatively.
- One-time setup fee applies when a new logo is uploaded.

## Orders & Fulfilment
- Order line items display customization summary (zones, method, logo ref, pricing).
- Export tools (CSV/JSON) can be added to integrate with production.

## File Management
- Uploads are stored at `wp-content/customization-files/` with a protective `.htaccess` and `index.php`.
- Old temporary files are cleaned up automatically.

## Email & Proofing
- Optional emails can include customization details and proofing steps.
- Best practice: Send a proof link before production, capture customer approval.

## Maintenance
- Regularly review the logo library for unused/orphaned files.
- Rotate pricing tiers during promotions.
- Test wizard after theme or WooCommerce updates.

## Troubleshooting (quick)
- Button not showing in cart: ensure product enabled; clear theme cart template overrides; confirm hooks fire.
- Modal shows but no steps: check browser console for AJAX 400; verify nonces and that admin-ajax is reachable.
- Files won’t upload: check max size and allowed types; confirm `/wp-content/customization-files/` is writable.
- Wrong prices: verify tiers in settings and any product-level overrides.

## Data Protection
- Uploaded artwork is treated as customer personal data; follow your GDPR/CCPA policy.
- Provide removal/export on request.

## Backups
- Database tables beginning with `wp_wc_customization_` should be included in backups.

