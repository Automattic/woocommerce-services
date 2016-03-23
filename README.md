# woocommerce-hydra-client
A WordPress plugin that adds the Hydra client to WooCommerce, to eventually be merged into WooCommerce Actual

# Getting started

* `npm install`
* For Development: `npm start` (will start a webpack dev server at localhost:8085) and add the following to your `wp-config.php` file: `define( 'WOOCOMMERCE_CONNECT_DEV_SERVER_URL', 'http://localhost:8085/' );`
* OR for production use: `npm run dist` will build the files into the `dist` folder ready to be distributed
