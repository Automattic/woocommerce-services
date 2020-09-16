=== WooCommerce Services ===
Contributors: woocommerce, automattic, woothemes, allendav, kellychoffman, jkudish, jeffstieler, nabsul, robobot3000, danreylop, mikeyarce, shaunkuschel, orangesareorange, pauldechov, dappermountain, radogeorgiev, bor0, royho, cshultz88, bartoszbudzanowski, harriswong, ferdev, superdav42
Tags: shipping, stamps, usps, woocommerce, taxes, payment, stripe
Requires at least: 4.6
Requires PHP: 5.3
Tested up to: 5.5
Stable tag: 1.24.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Hosted services for WooCommerce including automated tax calculation, shipping label printing, and smoother payment setup.

== Description ==

WooCommerce Services makes basic eCommerce features like shipping more reliable by taking the burden off of your site’s infrastructure.

With WooCommerce Services, critical services are hosted on Automattic’s best-in-class infrastructure, rather than relying on your store’s hosting. That means your store will be more stable and faster.

To use the features, simply install this plugin and activate the ones you want directly in your dashboard. As we add more services, you’ll see more features available directly in WooCommerce - making setup simpler.

= Print shipping labels for USPS at a discounted rate =
Give customers lower rates on their shipping. Create ready-to-print shipping labels for USPS directly in WooCommerce and take advantage of our special discount rate.

= Collect accurate taxes at checkout =
We've got taxes for you - no need to enter tax rates manually.

= Be ready to accept payments instantly =
Have a Stripe account created on your behalf or accept PayPal Checkout payments without having to setup an account.

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

* USPS label purchase/printing
* Automated tax calculation
* Stripe account provisioning (through WooCommerce setup wizard)
* PayPal Checkout payment authorization

= Can I buy and print shipping labels for US domestic and international packages? =

Yes! You can buy and print USPS shipping labels for domestic and international destinations.

= This works with WooCommerce, right? =

Yep! WooCommerce version 3.0 or newer, please.

= Why is a Jetpack Connection and WordPress.com account required? =

We use the Jetpack connection to authenticate each request and, if you use the shipping label service, to charge your credit card on file.

= Are there Terms of Service and data usage policies? =

Absolutely! You can read our Terms of Service [here](https://en.wordpress.com/tos) and our data policy [here](https://jetpack.com/support/what-data-does-jetpack-sync/).

= Where can I see the source code for this plugin? =

The source code is freely available [in GitHub](https://github.com/Automattic/woocommerce-services).

= Can I show shipping rates at checkout? =

As of the WooCommerce 3.5 release, WooCommerce Services no longer provides shipping rates for new stores. If you're already using shipping rates in WooCommerce Services, they will continue to work.

== Screenshots ==

1. Buying a USPS shipping label for an order
2. Setting up custom packages
3. Selecting your preferred payment method
4. Enabling automated taxes
5. Creating a Stripe account from the setup wizard
6. Checking on the health of WooCommerce Services
7. Checking and exporting the label purchase reports

== Changelog ==

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

= 1.23.2 - 2020-06-12 =
* Fix   - Refund not possible on order page.

= 1.23.1 - 2020-06-10 =
* Tweak - Update WooCommerce compatibility to 4.2
* Fix	- Taxjar broken in admin's new order page
* Fix   - Tax recalculation on admin order screen missing street address.

= 1.23.0 - 2020-04-08 =
* Fix   - Hide paper selection until valid payment method is selected.
* Tweak - Shipping banner wording improvements.
* Add   - Link to carrier's schedule pickup page.
* Add   - Improved shipping service feature descriptions.
* Add   - Option to mark order complete when label is printed.

= 1.22.5 - 2020-03-17 =
* Add   - Admin asset API endpoint.
* Fix   - GB support for WC 4.0.
* Fix   - Jetpack staging check for PHP 5.3.
* Tweak - Bump WP tested version to 5.4.

= 1.22.4 - 2020-03-02 =
* Fix   - Stop using deprecated method Jetpack::is_staging_site() when Jetpack
8.1 is installed.

= 1.22.3 - 2020-01-22 =
* Add   - Preselect rate when there is only one rate available for given shipping configuration.
* Add   - Paper size selection into purchase modal sidebar and reprint modal which was previously removed.
* Add   - Show notice when WooCommerce is not installed or activated.
* Fix   - Use correct URL for variation products in Packages section.

= 1.22.2 - 2019-12-10 =
* Fix   - Packages weight total value formatting.
* Fix   - Allow fulfillment flow redo.

= 1.22.1 =
* Fix   - Remove nuisance admin notification.
