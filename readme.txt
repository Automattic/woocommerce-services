=== WooCommerce Services ===
Contributors: automattic, woothemes, allendav, kellychoffman, jkudish, jeffstieler, nabsul, robobot3000, danreylop, mikeyarce, shaunkuschel, orangesareorange, pauldechov
Tags: canada-post, shipping, stamps, usps, woocommerce
Requires at least: 4.6
Tested up to: 4.8
Stable tag: 1.6.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Hosted services for WooCommerce, including free real-time USPS and Canada Post rates and discounted USPS shipping labels.

== Description ==

WooCommerce Services makes basic eCommerce features like shipping more reliable by taking the burden off of your site’s infrastructure.

With WooCommerce Services, critical services are hosted on Automattic’s best-in-class infrastructure, rather than relying on your store’s hosting. That means your store will be more stable and faster.

To use the features, simply install this plugin and activate the ones you want directly in your dashboard. As we add more services, you’ll see more features available directly in WooCommerce - making setup simpler.

= Show live rates for USPS and Canada Post =
Show your customers accurate shipping rates automatically for both USPS, the largest delivery network in the US, and Canada Post.


= Print shipping labels for USPS at a discounted rate =
Give customers lower rates on their shipping. Create ready-to-print shipping labels for USPS directly in WooCommerce and take advantage of our special discount rate.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Accept the terms of service
1. Install and activate WooCommerce if you haven't already done so
1. Install, activate and connect Jetpack if you haven't already done so
1. Add a USPS or Canada Post shipping method instance to any shipping zone
1. Want to buy shipping labels? First, add your credit card to https://wordpress.com/me/purchases/billing and then print labels for orders right from the Edit Order page

== Frequently Asked Questions ==

= What services are included? =

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

= Where can I see the source code for this plugin? =

The source code is freely available [in GitHub](https://github.com/Automattic/woocommerce-services).

== Screenshots ==

1. Buying a USPS shipping label for a package
2. Setting up real-time USPS rates
3. Setting up real-time Canada Post rates
4. Real-time rate display in checkout
5. Setting up custom packages
6. Selecting your preferred payment method
7. Managing your saved cards on WordPress.com
8. Checking on the health of WooCommerce Services

== Changelog ==

= 1.6.2 =
* Fix spacing bug in purchased label display
* Fix bug with "create label" flow on WooCommerce 3.1.0

= 1.6.1 =
* Better UX when selecting shipping label payment method

= 1.6.0 =
* New streamlined onboarding process for plugin dependencies
* Better packaging workflow for orders not using live rates at checkout
* Improved discovery for label printing
* Fix bug with test label printing on status page

= 1.5.0 =
* Handle decoding errors retrieving older package data from orders
* Code cleanup - React 16 prep, better i18n support
* Fix PHP error when order has no packaging info
* Updated shipping label settings to include paper size
* Fix missing item bug with individually packaged products
* Fix display bug in "saved for later" item list
* Fix PHP error when using Jetpack in development mode

= 1.4.1 =
* Fix deleted product bug in labels UI
* Tweak Accept-Language header

= 1.4.0 =
* WooCommerce 3.0 compatibility
* Add test label print to system status page
* Remove shipping label preview
* Better handling of logged out users
* Add contextual plugin link to WooCommerce.com support form
* Fix bug when purchasing additional shipping labels without a page refresh
* Better NUX flow when Jetpack missing or inactive
* Temporarily remove label support for military addresses (requires customs form)
* Fix bug where predefined USPS packages weren't being sent in requests

= 1.3.2 =
* Hide destination address normalized order meta
* Log rate retrieval failures as errors with admin notice

= 1.3.1 =
* Fix compatibility bug with `mod_security`
* Update shipping address on order when corrected by API

= 1.3.0 =
* Fix label PDF viewing in IE, Microsoft Edge, and Firefox
* Fix label modal width issue in Internet Explorer 11
* Don't initially show red error highlights for missing label destination address
* Labels: Fix escaped HTML showing in product variation names
* Remove unused icon font, reducing CSS size
* Fix tax rate calculation for shipping
* Fix WP Super Cache label printing bug

= 1.2.0 =
* Improve label printing flow when origin or destination addresses need modification
* More descriptive text in pointer notice for setting up a WCS shipping method
* Remove plugin options during uninstall
* Prevent a label rates request when prerequisite data needs modification
* Include more information when JSON parsing fails
* Fix compatibility issues with Internet Explorer 11 and Microsoft Edge browsers
* Add version to plugin script and style includes
* Reduce size of CSS and JS files
* Fix incompatibility with existing WooCommerce USPS extension

= 1.1.1 =
* Update plugin support documentation link
* Fix bug related to tracking Terms of Service acceptance

= 1.1.0 =
* Fetch service schemas earlier - fix bug where no shipping methods displayed
* Fix bug related to tracking Terms of Service acceptance too early
* Display label number relative to order instead of ID
* Clear shipping rates cache when shipping settings change
* Ensure only the Jetpack connection owner can accept the Terms of Service
* Better visiblity into which shipping services are selected
* Add hint for first shipping method instance configuration

= 1.0.0 =
* Improve logging for rates request errors
* Fix a rates bug with individually packaged items
* Add an easier UI to select grouped shipping services
* Fix a database table prefix bug
* Fix a bug allowing an errant double-purchase of labels
* Always retrieve a refunded label's status
* Save the shipping label origin address for reuse earlier
* Add a fallback rate for USPS
* Recommend checking logs when errors occur
* Fix bug showing the wrong label purchase amount when requesting refunds
* Add tooltip explaining what shipping service price adjustments are
* Notify admins when products are missing dimensions when retrieving rates
* Improve terms of service message
* Improve user experience with form validation
* Move the settings under WooCommerce > Shipping
* Automatically enable flat rate packages when their corresponding service is enabled
* Improve the on-boarding experience for new users
* Show the card information being used to purchase labels
* Don't allow the labels flow to be entered without a selected payment method

= 0.9.5 =
* Add non-flat-rate predefined USPS packaging
* Add enhanced labels metabox with tracking info, reprint button, and refunds
* Allow payment methods to be managed in Jetpack "Developer Mode"
* Add messaging in labels dialog when no packages are configured
* Fix label preview display bug
* Fix a bug with variable product dimensions

= 0.9.4 =
* Fix a bug that caused a packages error in the labels UI when checkout used free shipping
* Tweak to the refund request success message
* Fix to avoid a WP_User error notice when editing payment methods
* Fix a React version error that was happening when editing shipping settings
* Only allow the primary Jetpack user to select payment method

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
