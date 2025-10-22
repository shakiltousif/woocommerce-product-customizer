# Security, Performance & Compliance

## Security Model
- Nonces: All AJAX endpoints require the correct nonce (`wc_customizer_*`).
- Capability checks: Admin operations require appropriate roles.
- File upload hardening:
  - Storage outside web root: `wp-content/customization-files/`
  - `.htaccess` and `index.php` placed automatically
  - Extension whitelist and MIME verification
  - Basic content signature scanning and size limits
- Output escaping (HTML attributes, text, JS)
- SQL queries via `$wpdb` with proper placeholders

## Privacy & Compliance
- Uploaded logos may contain personal/company IP; treat as personal data.
- Provide deletion/export on request (map to WooCommerce/WordPress export tools where possible).
- Retention: Define a policy for orphaned files and expired sessions; plugin includes cleanup jobs.

## Performance
- Asset loading only on Cart/Checkout pages.
- Single AJAX round trips per step; results cached in memory during session.
- DB Indexes on frequently queried columns (ids, method, quantity ranges).
- Image assets are SVG for zones to reduce payload.

## Hardening Checklist
- [ ] Disable directory listing on uploads (auto-managed).
- [ ] Use HTTPS everywhere (especially for admin-ajax).
- [ ] Limit max upload size sensibly (e.g., 8–20 MB based on needs).
- [ ] Keep WP, WooCommerce, theme, and this plugin up-to-date.

## Backup & Recovery
- Include all `wp_wc_customization_*` tables and `/wp-content/customization-files/` in backups.
- Document recovery procedures for order files and customer logos.

## Load Testing Ideas
- Simulate 100 concurrent wizards adding to cart.
- Upload 50MB total across 20 sessions and verify cleanup.
- Measure TTFB for `wc_customizer_calculate_pricing` under various quantities.

