# Connect for WooCommerce

What you see today is a plugin, though the goal for  Connect for WooCommerce is to be a fully integrated part of WooCommerce core. To get us from feature plugin to core, Connect for WooCommerce development is now in ALPHA - this means that you should expect frequent and sometimes large changes as features are added. During ALPHA testing, we'll be fixing problems and working to launch a feature-complete BETA. Successful BETA will culminate in a PR for inclusion into a future WooCommerce release.

**We do not recommend you use this ALPHA software on a production site.**

The emphasis for initial release of Connect for WooCommerce is shipping simplified. We are providing a Shipping Zones-compatible USPS shipping method and, coming soon, a Shipping Zones compatible Canada Post shipping method. Shipping Zones are [an exciting new feature of WooCommerce 2.6](https://woocommerce.wordpress.com/2016/02/10/shipping-zones-to-ship-with-2-6/).

Our USPS shipping method pulls rates for customers' carts in real time from the USPS server. No USPS account is needed - use the default one if you like.

There are many ways to contribute – reporting bugs, feature suggestions and fixing bugs. For full details, please see [CONTRIBUTING.md](./CONTRIBUTING.md)

## Getting Started
You need:
* A WordPress site
* WooCommerce 2.6+
* Installation of [Jetpack](https://wordpress.org/plugins/jetpack/), activate and connect it to your WordPress.com account

### Working with alpha

If you'd just like to check out the latest alpha release and not contribute code back, then [download the latest release](https://github.com/Automattic/woocommerce-connect-client/releases) and install as a plugin on your WordPress site.

### Working with `master`

If you'd just like to check out the latest code and/or wish to contribute code, then perform the following:

* Ensure you have `git`, `node`, and `npm` installed on the target machine/server. For maximum compatibility we recommend `node` version `5.11.1` and `npm` version 3+
* Clone this repository into the `plugins` folder of the WordPress installation.
* Run `npm install` to set up all the dependencies
* You now have two choices:
    * For Development: Add the following to your `wp-config.php` file: `define( 'WOOCOMMERCE_CONNECT_DEV_SERVER_URL', 'http://localhost:8085/' );` and then Run `npm start` which will start a webpack dev server at `localhost:8085`, complete with hot reloading whenever you make changes. You will need to keep the `npm start` task running while developing. You can press `ctrl+c` to exit this process.
    * For testing or pre-production use: Run `npm run dist` which will build the files into the `dist` folder, and will be loaded by the plugin without any additional configuration

## Security

Need to report a security vulnerability? Go to [https://automattic.com/security/](https://automattic.com/security/) or directly to our security bug bounty site [https://hackerone.com/automattic](https://hackerone.com/automattic).

## Browser Support

We support the latest two versions of all major browsers, except  IE, where we currently only support 11 and Edge.  (see [Browse Happy](http://browsehappy.com) for current latest versions).

## License

Connect for WooCommerce is licensed under [GNU General Public License v2 (or later)](./LICENSE.md).
