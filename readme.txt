=== WooCommerce Shipping & Tax ===
Contributors: woocommerce, automattic, woothemes, allendav, kellychoffman, jkudish, jeffstieler, nabsul, robobot3000, danreylop, mikeyarce, shaunkuschel, orangesareorange, pauldechov, dappermountain, radogeorgiev, bor0, royho, cshultz88, bartoszbudzanowski, harriswong, ferdev, superdav42
Tags: shipping, stamps, usps, woocommerce, taxes, payment, dhl, labels
Requires at least: 4.6
Requires PHP: 5.3
Tested up to: 5.9
Stable tag: 1.25.28
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

= 1.25.28 - 2022-05-12 =
* Fix   - Notice: Undefined index: 'from_country' when validating TaxJar request.

= 1.25.27 - 2022-05-03 =
* Fix   - Cart with non-taxable product still calculate the tax.
* Tweak - Validate the TaxJar request before calling the api and cache 404 and 400 TaxJar response error for 5 minutes.

= 1.25.26 - 2022-04-19 =
* Fix   - Display error on cart block and checkout block from WC Blocks plugin.
* Fix   - TaxJar does not calculate Quebec Sales Tax when shipping from Canadian address.

= 1.25.25 - 2022-03-29 =
* Fix   - TaxJar does not get the tax if the cart has non-taxable on the first item.
* Tweak - Use regex to check on WC Rest API route for WooCommerce Blocks compatibility.

= 1.25.24 - 2022-03-17 =
* Fix - Empty document is opened when Firefox is set to open PDF file using another program.
* Fix - Label purchase modal sections getting cut off.

= 1.25.23 - 2022-02-10 =
* Tweak - Make "Name" field optional if "Company" field is not empty.
* Fix   - Added "Delete California tax rates" tool.
* Fix   - Extract WC_Connect_TaxJar_Integration::backup_existing_tax_rates() for re-usability.

= 1.25.22 - 2022-02-02 =
* Fix   - TaxJar does not get the tax if the cart has non-taxable item.
* Tweak - Bump WP tested version to 5.9 and WC tested version to 6.1.

= 1.25.21 - 2022-01-26 =
* Fix - Use 'native' pdf support feature for Firefox version 94 or later.
* Fix - Only call WC Subscriptions API when "access_token_secret" value is saved in database.
* Fix - Add name field to fields sent for EasyPost API address verification.
* Fix - Display company name under origin and destination address when create shipping label.
* Fix - Don't override general "Enable Tax" setting with WC Services Automated Taxes setting.

= 1.25.20 - 2021-11-15 =
* Fix - Hide "Shipping Label" and "Shipment Tracking" metabox when the label setting is disabled.
* Fix - Wrap TaxJar API zipcodes with wc_normalize_postcode() before inserting into the database.
* Fix - Update shipping label to only show non-refunded order line items.
* Fix - Added 3 digits currency code on shipping label price for non USD.

= 1.25.19 - 2021-10-14 =
* Add - Notice about tax nexus in settings.
* Fix - Country drop down list no longer showing currency name.

= 1.25.18 - 2021-08-16 =
* Add   - Added "Automated Taxes" health item on status page.
* Fix   - Show error when missing required destination phone for international shipments.
* Fix   - Prevent PHP notice when a label's `commercial_invoice_url` value is `null`.
* Fix   - Prevent fatal error when viewing draft order.
* Tweak - Bump WP tested version to 5.8.
* Tweak	- Bump WC Tested version to 5.5.

= 1.25.17 - 2021-07-13 =
* Tweak - Replace Calypso FormCheckbox with CheckboxControl.

= 1.25.16 - 2021-07-09 =
* Tweak - Replace components with @wordpress/components.

= 1.25.15 - 2021-06-30 =
* Fix   - Ensure shipping label metabox is displayed to users with the correct capabilities.
* Add   - Added `wcship_user_can_manage_labels` filter to check permissions to print shipping labels.
* Add   - Added `wcship_manage_labels` capability to check permissions to print shipping labels.

= 1.25.14 - 2021-06-15 =
* Fix   - Issue with printing blank label in Safari.
* Fix   - DHL Express labels - require customs form when shipping to Puerto Rico.
* Fix   - Update DHL Express pickup link.

= 1.25.13 - 2021-05-20 =
* Fix   - Prevent new sites from retrying failed connections.
* Fix   - Data encoding when entities are part of order meta.
* Tweak - Update WC version support in headers.
* Fix   - Plugin deletion when WooCommerce core is not present.
* Tweak - Rename automatic tax names for US.
* Fix   - Check Jetpack constant defined by name.
* Fix   - Sometimes taxes charged on shipping when they should not.

= 1.25.12 - 2021-04-21 =
* Fix   - UPS account connection form retry on invalid submission.
* Fix   - Fix PHP 5.6 compatibility issue.
* Tweak - Update plugin author name.
* Fix   - Removes unnecessary subscription debug error logs.

= 1.25.11 - 2021-04-06 =
* Fix	- Ensure status page is displayed on new WC navigation menu.
* Add   - Run phpcbf as a pre-commit rule.
* Fix   - Fix PHPUnit tests. Rename `test_` to `test-` to match our phpcs rules. Remove travis and move to github action.
* Tweak - Updated .nvmrc to use 10.16.0
* Tweak - Update the shipping label status endpoint to accept and return multiple ids.
* Tweak	- Display spinner icon during service data refresh.
* Add	- Adds Dockerized E2E tests with GitHub Action integration.
* Fix   - Handle DHL live rates notice creation and deletion errors.

= 1.25.10 - 2021-03-24 =
* Add   - Add an endpoint for shipping label creation eligibility and share code for store eligibility.
* Fix   - Shipping validation notice shown when no address entered.
* Tweak - Stop retrying to fetch /services when authentication fails on connect server.

= 1.25.9 - 2021-03-17 =
* Add   - WC Admin notice about DHL live rates.
* Add	- Live rates section in settings page.
* Tweak - Cleanup stripe functionality.
* Tweak - Display better errors on checkout page when address fields are missing / invalid.
* Tweak - Refresh on status page does not reload page.
* Fix   - UPS invoice number allows numbers and letters.
* Add 	- Tracks shipping services used at checkout.
* Add   - Update the existing endpoint `POST /connect/packages` to create shipping label packages, and add an endpoint `PUT /connect/packages` to update shipping label packages.
* Fix   - Only display shipping validation errors on the cart or checkout pages.
* Tweak - Removes deprecated Jetpack constant JETPACK_MASTER_USER
* Fix   - Revert radio button dot offset in the "Create shipping label" modal.

= 1.25.8 - 2021-03-02 =
* Tweak - Add support for new Jetpack 9.5 data connection.
* Tweak - Change minimum Jetpack version support to Jetpack 7.5.

= 1.25.7 - 2021-02-09 =
* Fix   - Prevent error notices on checkout page load.
* Tweak - Highlight rate call usage over limit on WooCommerce Shipping settings page.
* Fix   - Connect carrier account link broken on subdirectory installs.
* Fix   - Position dot in the center of radio buttons in "Create shipping label".
* Fix   - Adjust radio button dot style in "Create shipping label" in high contrast mode on Windows.

= 1.25.6 - 2021-01-26 =
* Fix 	- Refreshes shipping methods after registering or removing carrier accounts.
* Tweak	- Changed rates response caching method from cache to transient.

= 1.25.5 - 2021-01-11 =
* Fix	- Redux DevTools usage update.
* Add	- Display subscriptions usage.
* Add	- Subscription activation.
* Add 	- Uses same DHL logo for all registered DHL accounts.
* Tweak - Adds WCCom access token and site ID to connect server request headers.

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
