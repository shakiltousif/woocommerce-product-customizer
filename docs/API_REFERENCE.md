# API Reference (Hooks, Filters, AJAX)

This reference lists public integration points. All callbacks must sanitize inputs and escape outputs.

## WordPress/WooCommerce Hooks Used
- `woocommerce_after_cart_item_name` – Adds action buttons next to cart items
- `woocommerce_after_cart` – Prints wizard modal container
- `woocommerce_cart_calculate_fees` – Applies customization fees
- `woocommerce_add_cart_item_data` – Saves `customization_data` to cart lines
- `woocommerce_get_item_data` – Displays a short summary in cart rows
- `woocommerce_checkout_create_order_line_item` – Persists meta to order items
- `woocommerce_order_item_meta_end` – Renders details in order/admin

## Filters Provided
- `wc_customizer_available_zones` (array $zones, int $product_id)
- `wc_customizer_available_methods` (array $methods, int $product_id)
- `wc_customizer_setup_fee` (float $fee, array $data)
- `wc_customizer_application_cost` (float $cost, array $data, int $qty)
- `wc_customizer_zone_charge` (float $charge, int $zone_id, array $data)
- `wc_customizer_pricing_breakdown` (array $breakdown, array $data, int $qty)

## Data Contracts
`customization_data` stored on cart line items:
```
{
  method: "embroidery" | "print",
  content_type: "logo" | "text",
  zones: [1, 2, ...],
  file_path?: "custom_abcd1234.pdf",
  text_content?: "ACME Team",
  total_cost?: 12.50
}
```

## AJAX Endpoints
All endpoints require POST; `ajaxUrl` is the standard `admin-ajax.php`. Nonces:
- General (zones/methods/session): `wc_customizer_nonce` (param: `nonce`)
- Upload: `wc_customizer_upload` (param: `nonce`)
- Cart add/edit: `wc_customizer_cart` (param: `nonce`)
- Pricing: `wc_customizer_pricing` (param: `nonce`)

Endpoints:
- `wc_customizer_get_zones` → `{ success, data: { zones: Zone[] } }`
- `wc_customizer_get_methods` → `{ success, data: { methods: Method[] } }`
- `wc_customizer_save_session` → `{ success }`
- `wc_customizer_load_session` → `{ success, data: { step_data } }`
- `wc_customizer_upload_file` → `{ success, file: { filename, filepath, size } }`
- `wc_customizer_delete_file` → `{ success }`
- `wc_customizer_calculate_pricing` → `{ success, data: { pricing, formatted } }`
- `wc_customizer_add_to_cart` → `{ success }`
- `wc_customizer_edit_customization` → `{ success, data: { customization_data } }`

## Response Objects
- `Zone`: `{ id, name, group, methods: string[], charge }`
- `Method`: `{ id, name, description, setup_fee }`
- `PricingSummary`:
```
{
  breakdown: [
    { label: "Setup fee", cost: 8.95, type: "setup" },
    { label: "Application (x10)", cost: 59.90, type: "application" },
    { label: "Zones (3)", cost: 3.00, type: "zones" }
  ],
  subtotal: 71.85,
  bulk_discount: 0,
  total: 71.85,
  per_item_cost: 7.185
}
```

## Error Handling
Errors respond with `{ success:false, data:{ message } }` and a suitable HTTP code where applicable. The front end shows friendly messages.

## Example: Change setup fee for returning customer
```php
add_filter('wc_customizer_setup_fee', function($fee, $data){
  if (!empty($data['customer_logo_id'])) {
    return 0.0; // setup already paid
  }
  return $fee;
}, 10, 2);
```

