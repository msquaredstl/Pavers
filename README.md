# Pavers

A WordPress plugin that adds paver-specific engraving fields to WooCommerce products and optionally collects submissions for a public wall.

## Features
- Custom **Paver Order** post type for managing submissions in the dashboard (if you still collect external requests).
- Front-end shortcode form with validation and nonce protection for non-commerce use.
- Email notifications to a configurable recipient when a new request is received via the shortcode form.
- Optional public "paver wall" display for recent inscriptions.
- Admin settings for notification email, success/error copy, and a form disclaimer.
- WooCommerce integration with a per-product toggle to show engraving fields only on paver products.
- Customers can provide up to three lines of engraving text plus alignment, saved to cart items and order metadata.

## Shortcodes
- `[paver_order_form product_id="123"]` – renders the public request form for a paver-enabled WooCommerce product (uses the current product automatically on single-product pages). Primarily for collecting requests outside WooCommerce; paver products will surface the engraving fields directly on the product page.
- `[paver_wall count="12"]` – outputs a masonry-style grid of recent paver inscriptions; adjust `count` to change the number displayed.

## Installation
1. Copy the plugin folder to your WordPress `wp-content/plugins` directory.
2. Activate **Pavers** from the Plugins screen.
3. Visit **Settings → Pavers** to set the notification recipient and copy.
4. Edit a WooCommerce product and, under **Product data → General**, check **Enable Paver Customization** to allow engraving layout entry on that product page.
5. Add the shortcodes to a page to collect requests and optionally display the wall (the order form will only render for enabled paver products). The WooCommerce add-to-cart form will capture engraving details for enabled products automatically.

## Development notes
- Autoloading is handled via a lightweight PSR-4 style loader in `includes/Autoloader.php`.
- Form submissions are processed through `admin-post.php` using the `pavers_submit_order` action.
- Submissions create pending `paver_order` posts with donor metadata for review and publishing.
