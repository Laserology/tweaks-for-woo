# tweaks-for-woo

A simple plugin designed for simple tweaking the very popular e-commerce platform.

Please note that this is only a tool that aims to format your store in a way that works for you. In no way does it ensure compliance with taxes or any other regulatory requirements. By using this software you entirely agree that you understand this and will not attempt to hold us liable for _your_ mistakes or any issues that arrise from the use of this plugin.

Absolutely do not use this software in a real sales environment without proper testing first, and do not assume that any wording within this plugin implies safety or compliance.

## Included tweaks

All tweaks are toggled from **WooCommerce → Settings → Tweaks**.

- **Always fill tax rate table** — fills the WooCommerce tax rate table from WooCommerce Tax (TaxJar) lookups without enabling automated taxes. Requires **WooCommerce Tax** (shipped as **WooCommerce Shipping & Tax**) to be installed, active, and connected. When it is missing, the toggle is disabled and no tax hooks are registered.
- **Apply Store Base Address to Blank Orders** — fills blank billing/shipping addresses on orders created by administrators with the store base address.
- **Prevent price adjustment by location** — stops WooCommerce from changing display prices when prices are shown including tax.
- **Block Internal Agent order access** — denies the `Internal Agent` account access to the WooCommerce orders REST API. Enabled by default, matching the previous hardcoded behavior.

## Tax rate filling

On order creation or update, if the order has a usable billing/shipping address and at least one line item, the plugin asks the WooCommerce Tax service for the destination's rates and writes the returned components into:

- `wp_woocommerce_tax_rates`
- `wp_woocommerce_tax_rate_locations`

It does not touch order totals, tax classes, price display, or the `woocommerce_tax_based_on` / `woocommerce_prices_include_tax` / `woocommerce_tax_display_*` options.

### Requirements

- WooCommerce.
- WooCommerce Tax (the plugin that provides `WC_Connect_TaxJar_Integration`; currently shipped as **WooCommerce Shipping & Tax**, formerly also packaged as **WooCommerce Tax**), active.
- That plugin must be connected to WooCommerce.com/Jetpack. The connection is the "connect your site" step; this plugin reuses its API client and authentication.
- Its **Automated taxes** toggle can stay off. Leaving it off is the point: this plugin never calls the integration's `init()`, so none of its cart, checkout, order, or settings hooks are registered.

If the tax service plugin is missing, an admin notice appears, the settings toggle is unavailable, and nothing is filled.

### Behavior details

- Address side follows the store's existing `woocommerce_tax_based_on` setting (`shipping`, `billing`, or `base`).
- Both admin-created orders (once addresses are saved) and customer checkout orders trigger a fill.
- The admin **Recalculate** button also triggers a fill, using the address core is about to calculate taxes with (before the order is saved), so the recalculation can use the newly written rows.
- Adding a product to an admin order also fills the rate table first, for the location `wc_get_price_excluding_tax()` will use. With "Prices entered with tax" enabled, this lets WooCommerce back the tax out of the product price so the line total is net and the order total stays equal to the entered price.
- The rate lookup uses the order's own line items, so tax classes and product tax codes match the order.
- A per-order `_lstrw_filled_hash` meta prevents duplicate lookups for unchanged address/item combinations.
- The triggering order is not retroactively retaxed by the order-creation hooks: that path fills the table for future calculations. The Recalculate path is the exception, because it writes before core recalculates.

### Caveats

- Uses internal classes and a protected method of the tax plugin (`get_backend_line_items`). A major change to that plugin could break the integration.
- The WooCommerce Tax service only returns rates where the store has nexus (for US stores, its base state). Other destinations produce no rows.
- The rate fill runs at the end of order creation, so the very first order to a new destination will not have been taxed from the newly written row.

### Filters

- `lstrw_should_fill_rates` (bool, `WC_Order`) — return false to skip filling for an order.
- `lstrw_tax_address` (array, `WC_Order`) — change the address sent for the lookup.
