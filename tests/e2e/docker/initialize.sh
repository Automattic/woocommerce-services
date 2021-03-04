 #!/bin/bash
echo "Initializing WooCommerce Services E2E"

WP_CORE_DIR="/var/www/html"
WCS_DIR="$WP_CORE_DIR/wp-content/plugins/woocommerce-services"

# install default theme
wp theme install twentynineteen --activate
# install woocommerce, jetpack
wp plugin install woocommerce --activate
wp plugin install jetpack --activate
# we cannot create API keys for the API, so we using basic auth, this plugin allows that.
wp plugin install https://github.com/WP-API/Basic-Auth/archive/master.zip --activate
wp eval-file "$WCS_DIR/tests/bin/wc_rest_api_credentials.php"
# mock for Jetpack
cp "$WCS_DIR/tests/e2e/config/travis/wc-services-testing-helper.php" "$WP_CORE_DIR/wp-content/plugins/"

# finally activate WCS
wp plugin activate woocommerce-services
wp plugin activate wc-services-testing-helper
wp option update jetpack_tos_agreed 1
wp option update wc_connect_options '{"tos_accepted": true }' --format=json
wp transient set wcc_is_new_label_user false
