# Testing Strategy

This document outlines how to validate the plugin in development and staging.

## Unit/Integration (PHP)
- Database creation and seed data
- Pricing engine calculations
- File manager validation and path security
- Cart integration: add/edit/remove customization, fee calculation

## Frontend (E2E)
- Wizard renders on Cart page
- Step transitions and validations
- AJAX flows: zones, methods, pricing, upload, add-to-cart
- Edit flow from cart; remove customization

## Manual Test Checklist
- [ ] Product without customization does NOT show the button
- [ ] Customizable product shows button in cart
- [ ] Zones load and can be multi-selected
- [ ] Methods load with descriptions and tiers
- [ ] Upload accepts allowed types and rejects others
- [ ] Pricing summary updates correctly per quantity tier
- [ ] Add to cart attaches data and recalculates fees
- [ ] Checkout persists customization to order line items
- [ ] Order screen displays summary

## CLI Smoke Tests
From plugin directory:

- `php test-cart-integration.php`  hooks/assets/product meta
- `php test-database.php`  tables and records
- `php test-ajax.php`  AJAX handlers registration
- `php test-methods.php`  methods endpoint
- `php test-nonce.php`  nonce verification

## Browser Matrix
- Latest Chrome/Firefox/Safari/Edge
- iOS Safari, Chrome Mobile

## Performance Checks
- Measure admin-ajax responses: <300ms typical for zones/methods; pricing <500ms
- Profile pricing calculations for large quantities

