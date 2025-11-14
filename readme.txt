=== Ledyer Order Management for WooCommerce ===
Contributors: ledyer
Tags: woocommerce, ledyer, ecommerce, e-commerce, order-management
Donate link: https://ledyer.com
Requires at least: 4.0
Tested up to: 6.8.3
Requires PHP: 7.4
WC requires at least: 5.0.0
WC tested up to: 10.3.5
Stable tag: 1.5.5
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== Changelog ==
= 2025.10.09    - version 1.5.5 =
* Fix           - Implemented a numeric conversion helper for shipping prices to ensure they are always numeric, preventing potential fatal errors during order management when shipping option prices are returned as strings.
* Fix           - Addressed various deprecation warnings from PHP 8.2 and 8.3.

= 2025.05.08    - version 1.5.4 =
* Fix           - Resolve not ready for capture warning

= 2025.05.08    - version 1.5.3 =
* Fix           - Resolve errors during refund

= 2024.10.22    - version 1.5.2 =
* Fix           - Fixed various deprecation warnings.

= 2024.06.10    - version 1.5.1 =
* Fix           - Fix error that could be triggered on cancel order request.
* Fix           - Set failed request order status correct even if plugin settings never have been saved.
