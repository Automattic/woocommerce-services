=== Connect for WooCommerce ===
Contributors: automattic, woothemes, allendav, kellychoffman, jkudish, jeffstieler, nabsul, robobot3000, danreylop, mikeyarce, shaunkuschel
Tags: canada-post, shipping, stamps, usps, woocommerce
Requires at least: 4.6.1
Tested up to: 4.6.1
Stable tag: 0.9.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Hosted services for WooCommerce, including free real-time USPS and Canada Post rates and discounted USPS shipping labels. (BETA)

== Description ==

**This is a BETA release. Please carefully test and make sure it meets your needs before considering for a production site.**

Connect for WooCommerce makes basic eCommerce features like shipping more reliable by taking the burden off of your site’s infrastructure.

With Connect, critical services are hosted on Automattic’s best-in-class infrastructure, rather than relying on your store’s hosting. That means your store will be more stable and faster.

To use the features, simply install this plugin and activate the ones you want directly in your dashboard. As we add more services, you’ll see more features available directly in WooCommerce - making setup simpler.

*Show live rates for USPS and Canada Post*
Show your customers accurate shipping rates automatically for both USPS, the largest delivery network in the US, and Canada Post.


*Print shipping labels for USPS at a discounted rate*
Give customers lower rates on their shipping. Create ready-to-print shipping labels for USPS directly in WooCommerce and take advantage of our special discount rate.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Accept the terms of service
1. Install and activate WooCommerce if you haven't already done so
1. Install, activate and connect Jetpack if you haven't already done so
1. Add a USPS or Canada Post shipping method instance to any shipping zone
1. Want to buy shipping labels? First, add your credit card to wordpress.com/me/billing and then print labels for orders right from the Edit Order page

== Frequently Asked Questions ==

= What services are included in Connect? =

For this first release, we’re including free real-time USPS and Canada Post Shipping rates in checkout. We are also including discounted USPS shipping labels for domestic packages. More services will roll out over upcoming releases.

= Are Real-Time Rates in Checkout Free? =

Yes, real-time rates in checkout are totally free.

= Can I buy and print shipping labels for US domestic and international packages? =

For this first release, you can buy and print USPS shipping labels for domestic packages only. International shipping is not yet supported.

= This works with WooCommerce, right? =

Yep! WooCommerce version 2.6 or newer, please.

= Why is a Jetpack Connection and WordPress.com account required? =

We use the Jetpack connection to authenticate each request and, if you use the shipping label service, to charge your credit card on file.

= Are there Terms of Service and data usage policies? =

Absolutely! You can read our Terms of Service [here](https://woocommerce.com/terms-conditions/) and our data policy [here](https://woocommerce.com/terms-conditions/connect-privacy).

== Screenshots ==

1. Buying a USPS shipping label for a package
2. Setting up real-time USPS rates
3. Setting up real-time Canada Post rates
4. Real-time rate display in checkout
5. Setting up custom packages
6. Selecting your preferred payment method
7. Managing your saved cards on WordPress.com
8. Checking on the health of Connect for WooCommerce

== Changelog ==

= 0.9.3 =
* Improvements for phone validation error messages
* Increase the remote request timeout
* Adds USPS flat rate packages to the packaging manager
* Store the origin address when the label is purchased
* Clean-up a few React errors in the console
* Tweak the Jetpack status message
* Auto-accept address normalizations in labels UI if they are trivial
* Make sure notices appear on top of (not beneath) the labels UI
* CSS clean-up
* Fix an error where clicking outside the labels UI modal did not always close it
* Fix incorrect service counts in shipping method instance group headers
* Remove inappropriate target blank in certain anchors
* Fix timestamp presentation for label refunds
* Fix empty dimension handling and dimension casting

= 0.9.2 =
* Fix a fatal error that could happen on activation if WooCommerce or Jetpack were not present
* Send order shipping information with label rate requests to allow for pre-selection

= 0.9.1 =
* Update contributors to include a few we missed
* Add USPS flat-rate packaging support to the packaging manager
* Improvements to label preview
* Ensure self-help works even if connect is unable to download the service schemas
* Switch to PDF based multi-page labels

= 0.9.0 =
* Beta release

== Upgrade Notice ==

= 0.9.0 =
Beta release
