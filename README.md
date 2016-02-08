# woocommerce-hydra-client
A WordPress plugin that adds the Hydra client to WooCommerce, to eventually be merged into WooCommerce Actual

# Building the Plugin

* First, install allthethings using

    npm install

* Then, build allthethings using

    webpack

* Next, upload all the things, especially assets/js/bundle.js (you can skip the node_modules folder)
* Then, activate the plugin if you haven't already
* Lastly, navigate to wp-admin/admin.php?page=wc-settings&tab=shipping to see the new methods
* Remember, if you re-webpack, don't forget to upload bundle.js to your remote server
