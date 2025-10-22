# Full Implementation Plan (Final, As Delivered)

This is the complete, refined plan agreed for the Workwear‑style WooCommerce product customization plugin. It reflects the screenshots and the cart‑first, 3‑step wizard flow.

## Executive Summary
- Replicate a 3‑step customization experience tightly integrated with WooCommerce cart and checkout
- Provide visual position (zone) selection, method choice (Embroidery/Print), and logo upload
- Implement a robust pricing engine with setup fees and quantity tiers
- Ensure security (uploads outside web root, nonces), performance, and admin UX

## Corrected Workflow (from screenshots)
1) Cart‑first: User adds a product to cart, then clicks “Add logo to this item” on the Cart page
2) Step 1 – Choose Position(s): select one or more zones
3) Step 2 – Choose Application Method: embroidery or print with comparison
4) Step 3 – Add Logo: upload/choose existing logo; show one‑time setup fee
5) Summary & Pricing: live breakdown
6) Add to Cart / Edit Later: customization bound to the specific cart line

## Scope & Deliverables
- Complete WordPress plugin
- Admin screens for settings, pricing, zones, and product enablement
- 3‑step wizard (responsive, mobile‑first)
- Dynamic pricing engine (setup fees + quantity tiers + per‑zone charges)
- Secure upload pipeline and logo library
- Full WooCommerce integration (cart → checkout → order → email)
- Documentation set, tests, and troubleshooting tools

## Technical Stack
- PHP 7.4+ (WordPress + WooCommerce)
- Vanilla JS/jQuery on the frontend wizard for compatibility
- CSS Grid/Flex for responsive UI
- WordPress Settings API for admin
- WooCommerce hooks for pricing/meta/order integration

## Data Model (Tables)
- wc_customization_types (id, name, description, setup_fee, active)
- wc_customization_zones (id, name, group, zone_charge, methods_available, active)
- wc_customization_pricing (method, min_quantity, max_quantity, price_per_item)
- wc_customization_products (product_id, enabled, allowed zones/methods)
- wc_customization_sessions (session‑level wizard state)
- wc_customization_customer_logos (saved artwork & setup status)
- wc_customization_orders (per‑order customization snapshot)

## Pricing Model
- Setup fees (text/logo by method) e.g., £4.95 / £8.95
- Tiered per‑item charges by method:
  Embroidery: 1–8 £7.99; 9–24 £6.25; 25–99 £4.75; 100–249 £3.75; 250+ £2.75
  Print: 1–8 £7.99; 9–24 £5.99; 25–99 £4.50; 100–249 £3.50; 250+ £2.50
- Optional per‑zone flat charge

## WooCommerce Integration Points
- Cart UI: woocommerce_after_cart_item_name (buttons) and woocommerce_after_cart (modal)
- Cart Meta: woocommerce_add_cart_item_data / woocommerce_get_item_data
- Fees: woocommerce_cart_calculate_fees
- Orders: woocommerce_checkout_create_order_line_item / woocommerce_order_item_meta_end
- Emails: woocommerce_order_item_meta_start

## Frontend Wizard
- Step 1 Zones: grid of selectable cards with SVG preview for common zones
- Step 2 Methods: side‑by‑side comparison; highlights suitability
- Step 3 Upload: drag‑and‑drop + file chooser; progress; previous‑logo reuse if logged in
- Summary: live pricing + clear breakdown; add/edit/remove customization

## Security & Compliance
- Files saved to wp‑content/customization‑files/ with .htaccess and index.php
- Nonces on all AJAX; sanitized inputs; escaped outputs
- GDPR/CCPA aligned: provide delete/export; retention policy for orphaned files

## Performance
- Assets only on Cart/Checkout
- Minimal payloads; SVG zone assets; indexed queries
- Optional object caching for configuration reads

## QA & UAT Plan
- CLI tests for hooks, DB, AJAX, nonces
- Browser tests across Chrome/Firefox/Safari/Edge + mobile
- Merchant UAT checklist (enable products, test zones/methods/uploads/pricing/checkout)

## Rollout & Monitoring
- Stage → UAT → Production
- Monitor admin‑ajax response times, file permissions, error logs
- Provide fallback path if theme overrides cause cart‑hook conflicts

## Future Enhancements (Roadmap)
- Text‑only design builder (fonts, colors)
- Artwork approval portal with commenting
- Team ordering (multiple names/numbers)
- Coach marks/onboarding tooltips in wizard
- REST API endpoints for headless storefronts

