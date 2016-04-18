# Contributing to the WooCommerce Connect Client

Hi! Thank you for your interest in contributing to the WooCommerce Connect Client, we really appreciate it.

Although what you see today is a plugin, the destination for the WooCommerce Connect Client is as a fully integrated part of WooCommerce core. To get us from feature plugin to there, WooCommerce Connect Client development is now in ALPHA - this means that you should expect frequent and sometimes large changes as features are added to the client. During ALPHA testing, we'll be fixing problems and working to launch a feature-complete BETA. Successful BETA will culminate in a PR for inclusion into WooCommerce 2.7.

We do not recommend you use this ALPHA software on a production site.

The emphasis for this initial release of WooCommerce Connect is shipping simplified. We are providing a Shipping Zones compatible USPS shipping method and, coming soon, a Shipping Zones compatible Canada Post shipping method as well.  Shipping Zones are an exciting new feature of WooCommerce 2.6.

Our USPS shipping method fetches rates for your customer's carts in real time from the USPS server.  No USPS account is needed to use this shipping method - you can use ours if you'd like.

There are many ways to contribute – reporting bugs, feature suggestions, and fixing bugs. Because we're focused on getting the BETA together, we're not ready for pull requests yet, but we'll let you know when we are.

## Reporting Bugs, Asking Questions, Sending Suggestions

Just [file a GitHub issue](https://github.com/Automattic/woocommerce-connect-client/issues/), that’s all. If you want to prefix the title with a “Question:”, “Bug:”, or the general area of the application, that would be helpful, but by no means mandatory. If you have write access, add the appropriate labels.

If you’re filing a bug, specific steps to reproduce are helpful. Please include what you expected to see and what happened instead.

## Setting up USPS shipping with the WooCommerce Connect Client

1. Install or update to [WordPress 4.5](https://wordpress.org/download/) or higher.
2. Install and activate WooCommerce 2.6 or higher. The WooCommerce Connect Client will NOT work with WooCommerce 2.5 or older.  A plugin ZIP will be available soon, but for now you will have to clone the WooCommerce plugin from its [repository on GitHub](https://github.com/woothemes/woocommerce).
3. Install and activate [Jetpack 3.9.6 or higher](https://wordpress.org/plugins/jetpack/).
4. Connect your Jetpack to WordPress.com. Although there is no specific module you'll need to activate, the WooCommerce Connect Client requires the Jetpack connection to authenticate with the WooCommerce Connect server.
5. Install and activate this feature plugin.
6. Add at least one product with weight and dimensions.
7. Add at least one shipping zone and add the WooCommerce Connect USPS shipping method to it.
8. Configure the USPS shipping method origin ZIP code and select at least one service.
9. USPS rates will automatically display during checkout once a destination address is given.

## License

The WooCommerce Connect Client is licensed under [GNU General Public License v2 (or later)](/LICENSE.md).
