=== WooCommerce Services ===
Contributors: automattic, woothemes, allendav, kellychoffman, jkudish, jeffstieler, nabsul, robobot3000, danreylop, mikeyarce, shaunkuschel, orangesareorange, pauldechov, dappermountain, radogeorgiev, bor0
Tags: shipping, stamps, usps, woocommerce, taxes, payment, stripe
Requires at least: 4.6
Requires PHP: 5.3
Tested up to: 5.3
Stable tag: 1.22.1
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

= 1.22.1 =
* Fix   - Remove nuisance admin notification.

= 1.22.0 =

* Add   - Display notices when base country/currency mismatches.
* Add   - Friendlier error codes when the API service is down.
* Add   - Introduce custom package setting on the label screen.
* Add   - Shipping Summary to the sidebar on the label screen.
* Add   - Shipping rates additional data - tracking, delivery time, signatures.
* Add   - Tracking modal.
* Add   - Ability to recreate labels for individual packages that have been refunded in a multi-label package scenario.
* Fix   - Deprecation notices for PHP 7.4.
* Fix   - Quantity is not being taken into account for the notice "n items are ready for shipment".
* Tweak - Credit card no longer required to configure shipping label.
* Tweak - Moved "Create Shipping Label" button from sidebar to the top of the order screen.
* Tweak - Preselect last used package on the label screen.
* Tweak - Remove rates from the dropdown and list them on the page after a package is selected.
* Tweak - Remove redundant "Paper Size" from the label screen since it's configurable from the Settings screen.
* Tweak - Show create label button in 'busy' state until data loads.
* Tweak - UX improvements for the label screen.

= 1.21.1 =

* Update WooCommerce compatibility to 3.7
* Support namespaced Jetpack methods

= 1.21.0 =

* Update WordPress compatibility to 5.2
* When there's only one credit card available, select it as the default for purchases
* Add ability to specify payment method during label purchase to enable choosing a credit card during purchase in the future

= 1.20.0 =

* Update WooCommerce compatibility to 3.6
* Improved wording for the Stripe Connect UI when disconnected
* Improve detection of when a Stripe account is connected
* Avoid overwriting Stripe keys after they've already been entered, and WooCommerce Services is connected for the first time
* Enable notices about WooCommerce Services to be displayed, for example when taxes were incorrectly calculated
* Update the 'print label' button text and style

= 1.19.0 =

* Communicate HTTPS requirement when connecting to Stripe
* Avoid showing 'Shipping options' at the bottom of the WooCommerce Services shipping settings page
* Fix a small misalignment of checkmarks in our checkboxes
* Fix the WC native tax override function by adding a missing return statement

= 1.18.0 =

* Add compatibility with WordPress 5.0
* Add compatibility with the WordPress.com eCommerce plan
* Add packing logs to the front-end (with debug enabled) and back-end (order detail screens)
* When purchasing a shipping label, allow addresses to be entered without verification
* Make the shipping label purchase process more robust, allowing retries when the label image failed to download
* UI improvements to the shipping label address form
* Allow connecting a Stripe account directly from the Stripe settings page
* Updated behavior of the shipping phone field in order to prevent conflicts with other plugins
