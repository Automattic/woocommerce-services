=== WooCommerce Shipping & Tax ===
Contributors: woocommerce, automattic, woothemes, allendav, kellychoffman, jkudish, jeffstieler, nabsul, robobot3000, danreylop, mikeyarce, shaunkuschel, orangesareorange, pauldechov, dappermountain, radogeorgiev, bor0, royho, cshultz88, bartoszbudzanowski, harriswong, ferdev, superdav42
Tags: shipping, stamps, usps, woocommerce, taxes, payment, dhl, labels
Requires at least: 4.6
Requires PHP: 5.3
Tested up to: 5.6
Stable tag: 1.25.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WooCommerce Shipping & Tax offers automated tax calculation, shipping label printing, smoother payment setup, and other hosted services for WooCommerce.

== Description ==

WooCommerce Shipping & Tax makes basic eCommerce features like shipping more reliable by taking the burden off of your site’s infrastructure.

With WooCommerce Shipping & Tax, critical services are hosted on Automattic’s best-in-class infrastructure, rather than relying on your store’s hosting. That means your store will be more stable and faster.
To use the features, simply install this plugin and activate the ones you want directly in your dashboard. As we add more services, you’ll see more features available directly in WooCommerce – making setup simpler.

NOTE: This extension was previously referred to as WooCommerce Services.

= Print USPS and DHL shipping labels and save up to 90% =
Ship domestically and internationally right from your WooCommerce dashboard. Print USPS and DHL labels and instantly save up to 90%.

= Collect accurate taxes at checkout =
We've got taxes for you - no need to enter tax rates manually.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Install and activate WooCommerce if you haven't already done so
1. Install, activate and connect Jetpack if you haven't already done so
1. Want to buy shipping labels? First, add your credit card to https://wordpress.com/me/purchases/billing and then print labels for orders right from the Edit Order page
1. Enable automated taxes from WooCommerce > Settings > Tax (make sure "enable taxes" is checked in General settings first)

== Frequently Asked Questions ==

= What services are included? =

* USPS and DHL label purchase/printing
* Automated tax calculation
* PayPal Checkout payment authorization

= Can I buy and print shipping labels for US domestic and international packages? =

Yes! You can buy and print USPS shipping labels for domestic destinations and USPS and DHL shipping labels for international destinations. Shipments need to originate from the U.S.

= This works with WooCommerce, right? =

Yep! WooCommerce version 3.0 or newer, please.

= Why is a Jetpack Connection and WordPress.com account required? =

We use the Jetpack connection to authenticate each request and, if you use the shipping label service, to charge your credit card on file.

= Are there Terms of Service and data usage policies? =

Absolutely! You can read our Terms of Service [here](https://en.wordpress.com/tos) and our data policy [here](https://jetpack.com/support/what-data-does-jetpack-sync/).

= Where can I see the source code for this plugin? =

The source code is freely available [in GitHub](https://github.com/Automattic/woocommerce-services).


== Screenshots ==

1. Buying a USPS shipping label for an order
2. Setting up custom packages
3. Selecting your preferred payment method
4. Enabling automated taxes
5. Checking on the health of WooCommerce Shipping & Tax
6. Checking and exporting the label purchase reports

== Changelog ==

= 1.25.4 - 2020-12-08 =
* Tweak - Remove Stripe connect functionality.
* Tweak - Remove unused method in shipping settings view.
* Fix	- Breaking behavior on account registration page.
* Add	- Allows registration of additional accounts.
* Tweak - Carrier description on dynamic carrier registration form.
* Fix   - Adjust documentation links.

= 1.25.3 - 2020-11-24 =
* Add   - Initial code for WooCommerce.com subscriptions API.
* Add   - Dynamic carrier registration form.
* Fix   - When adding "signature required" to some packages, prices were not updating.
* Add   - DHL Schedule Pickup link within order notes.
* Fix   - UI fix for input validation for package dimensions and weights.
* Fix   - Correct validation for UPS fields in Carrier Account connect form.
* Tweak - Add message to explain automated tax requires tax-exclusive product pricing.
* Fix   - Disable USPS refunds for untracked labels only.

= 1.25.2 - 2020-11-10 =
* Tweak - Add ZIP code validation to UPS(beta) signup form.
* Fix   - Issue with printing labels in some iOS devices through Safari.
* Fix   - Prevents warning when using PHP 5.5 or lesser
* Add   - Add new API end point to retrieve carrier registration requirements.
* Add   - Add composer command to run PHPUnit.
* Tweak - Update readme with DHL information.

= 1.25.1 - 2020-10-28 =
* Tweak - DHL refund days copy adjustment
* Tweak - Stop using deprecated Jetpack method is_development_mode().
* Fix   - Update carrier name in tracking notification email
* Add   - Add pre-commit and pre-push git hooks for linting and unit tests.
* Add   - Disable refunds for USPS letters.

= 1.25.0 - 2020-10-13 =
* Fix   - UPS connect redirect prompt
* Fix   - Allow UPS label purchase without payment method
* Fix   - PHP implode arguments order
* Fix   - Validate insurance value as both string and number
* Tweak - Adjusted messaging on label pointers
* Tweak - Update carrier logo
* Tweak - Plugin rename
* Add   - Link to print the customs form for all shipments that need it

= 1.24.3 - 2020-09-16 =
* Fix   - Asset paths incompatible with some hosts
* Fix   - Select all posts checkbox not working
* Fix   - Use of deprecated jQuery.load
* Tweak - Updating carrier logo and tracking links

= 1.24.2 - 2020-09-03 =
* Fix   - Optional preloading for wc-admin install compatibility
* Fix   - Remove duplicate rate errors
* Fix   - Compatibility with WooCommerce order page install prompt
* Add   - Introduce 'wc_connect_meta_box_payload' filter for modifying order data
* Tweak - Update UPS failed connection error message

= 1.24.1 - 2020-08-19 =
* Tweak - Zip/Postcode/Postal code messaging consistency
* Fix   - Services management CSS table layout
* Fix   - Carrier "disconnect modal" layout
* Fix   - Primary button busy state updated to match color
* Fix   - Remove padding from notice bar
* Fix   - Add missing box in rate step for how much customer paid for shipping
* Tweak - Bump WP tested version to 5.5
* Fix   - Issue with dismiss modal popup blocking access to edit order

= 1.24.0 - 2020-07-30 =
* Fix   - PHP 7.4 notice for taxes at checkout.
* Add   - Carrier logos next to rates.
* Tweak - Remove spinner from create shipping label button
* Add   - Upgrade React to 16.13
* Add   - Optimize bundle
* Fix   - Fix svg images not showing on dev
* Fix   - Fix 404 taxjar.js on new order page
* Add   - Add e2e tests for toggling shipping label
* Add   - Add e2e tests for label refund
* Fix   - Show which nexus automatted taxes will work with and link to doc
* Fix   - Fix localization issues
* Tweak - Improve service listing readability
* Add   - Support UPS as a carrier (beta)
