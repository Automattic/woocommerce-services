# woocommerce-connect-client
A WordPress feature plugin that adds the WooCommerce Connect Client, to eventually be merged into WooCommerce itself.

# Getting started

* You'll need Node 5.10+ with npm 3+
* `npm install`
* For Development: `npm start` (will start a webpack dev server at localhost:8085) and add the following to your `wp-config.php` file: `define( 'WOOCOMMERCE_CONNECT_DEV_SERVER_URL', 'http://localhost:8085/' );`
* OR for production use: `npm run dist` will build the files into the `dist` folder ready to be distributed
