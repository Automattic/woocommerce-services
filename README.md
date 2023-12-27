# WooCommerce Shipping & Tax

WooCommerce Shipping & Tax makes basic eCommerce features like shipping more reliable by taking the burden off of your site’s infrastructure.

With WooCommerce Shipping & Tax, critical services are hosted on Automattic’s best-in-class infrastructure, rather than relying on your store’s hosting. That means your store will be more stable and faster.

The current emphasis for WooCommerce Shipping & Tax is to provide "everything you need" to start selling. When combined with the WooCommerce 3.5 setup wizard, your store can have shipping label printing, automated tax calculation, and a ready-to-go Stripe payment account with just a few clicks.

To use the features if you've already installed WooCommerce, simply install this plugin and activate the ones you want directly in your dashboard. As we add more services, you’ll see more features available directly in WooCommerce - making setup simpler.

There are many ways to contribute – reporting bugs, feature suggestions and fixing bugs. For full details, please see [CONTRIBUTING.md](./CONTRIBUTING.md)

## Getting Started
You need:
* A WordPress site
* WooCommerce 8.0+
* Installation of [Jetpack](https://wordpress.org/plugins/jetpack/), connected to your WordPress.com account

### Using the plugin

If you'd just like to check out the latest release and not contribute code back, then [download the latest release](https://wordpress.org/plugins/woocommerce-services/) and install as a plugin on your WordPress site.

### Working with `trunk`

If you'd just like to check out the latest code and/or wish to contribute code, then perform the following:

* Ensure you have `git`, `node`, and `npm` installed on the target machine/server. For maximum compatibility we recommend `node` version `10.16.0` and `npm` version 6+
* Clone this repository into the `plugins` folder of the WordPress installation.
* This project uses [Git submodules](https://git-scm.com/book/en/v2/Git-Tools-Submodules). You'll need to run `git submodule update --init`, or enable `submodule.recurse` ([#](https://git-scm.com/docs/git-config#Documentation/git-config.txt-submodulerecurse)) like this: `git config --global submodule.recurse true`
* Run `npm install && composer install` to set up all the dependencies
* You now have two choices:
    * For Development: run `npm run up`, let the process finish, connect your site to Jetpack using Jurassic Tube or ngrok.
    * For testing or pre-production use: Run `npm run dist` which will build the files into the `dist` folder, and will be loaded by the plugin without any additional configuration

## Security

Need to report a security vulnerability? Go to [https://automattic.com/security/](https://automattic.com/security/) or directly to our security bug bounty site [https://hackerone.com/automattic](https://hackerone.com/automattic).

## Browser Support

We support the latest two versions of all major browsers, except IE, where we currently only support 11 and Edge.  (see [Browse Happy](http://browsehappy.com) for current latest versions).

## License

WooCommerce Shipping & Tax is licensed under [GNU General Public License v2 (or later)](./LICENSE.md).
