# WooCommerce Services

WooCommerce Services makes basic eCommerce features like shipping more reliable by taking the burden off of your site’s infrastructure.

With WooCommerce Services, critical services are hosted on Automattic’s best-in-class infrastructure, rather than relying on your store’s hosting. That means your store will be more stable and faster.

The emphasis for initial release of WooCommerce Services is shipping simplified. We are providing Shipping Zones-compatible USPS and Canada Post shipping methods and USPS shipping labels (stamps). Shipping Zones are [an exciting new feature of WooCommerce 2.6](https://woocommerce.wordpress.com/2016/02/10/shipping-zones-to-ship-with-2-6/). We are also allowing to print discounted USPS shipping labels.

To use the features, simply install this plugin and activate the ones you want directly in your dashboard. As we add more services, you’ll see more features available directly in WooCommerce - making setup simpler.

Our USPS shipping method pulls rates for customers' carts in real time from the USPS server. No USPS account is needed - use the default one if you like.

There are many ways to contribute – reporting bugs, feature suggestions and fixing bugs. For full details, please see [CONTRIBUTING.md](./CONTRIBUTING.md)

## Getting Started
You need:
* A WordPress site
* WooCommerce 2.6+
* Installation of [Jetpack](https://wordpress.org/plugins/jetpack/), activate and connect it to your WordPress.com account

### Using the plugin

If you'd just like to check out the latest release and not contribute code back, then [download the latest release](https://wordpress.org/plugins/woocommerce-services/) and install as a plugin on your WordPress site.

### Working with `master`

If you'd just like to check out the latest code and/or wish to contribute code, then perform the following:

* Ensure you have `git`, `node`, and `npm` installed on the target machine/server. For maximum compatibility we recommend `node` version `6.9.0` and `npm` version 3+
* Clone this repository into the `plugins` folder of the WordPress installation.
* Run `npm install` to set up all the dependencies
* You now have two choices:
    * For Development: Add the following to your `wp-config.php` file: `define( 'WOOCOMMERCE_CONNECT_DEV_SERVER_URL', 'http://localhost:8085/' );` and then Run `npm start` which will start a webpack dev server at `localhost:8085`, complete with hot reloading whenever you make changes. You will need to keep the `npm start` task running while developing. You can press `ctrl+c` to exit this process.
    * For testing or pre-production use: Run `npm run dist` which will build the files into the `dist` folder, and will be loaded by the plugin without any additional configuration

## Security

Need to report a security vulnerability? Go to [https://automattic.com/security/](https://automattic.com/security/) or directly to our security bug bounty site [https://hackerone.com/automattic](https://hackerone.com/automattic).

## Browser Support

We support the latest two versions of all major browsers, except IE, where we currently only support 11 and Edge.  (see [Browse Happy](http://browsehappy.com) for current latest versions).

## License

WooCommerce Services is licensed under [GNU General Public License v2 (or later)](./LICENSE.md).
