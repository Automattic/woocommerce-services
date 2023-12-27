#!/bin/bash
echo "Initializing WooCommerce Services E2E"

WP_CORE_DIR="/var/www/html"
WCS_DIR="$WP_CORE_DIR/wp-content/plugins/woocommerce-services"

# Update WP to the latest version
wp core update --quiet

# install default theme
wp theme install storefront --activate

wp plugin install woocommerce --activate

## adding basic settings and configuration
wp option set woocommerce_store_address "60 29th Street"
wp option set woocommerce_store_address_2 "#343"
wp option set woocommerce_store_city "San Francisco"
wp option set woocommerce_default_country "US:CA"
wp option set woocommerce_store_postcode "94110"
wp option set woocommerce_currency "USD"
wp option set woocommerce_weight_unit "oz"
wp option set woocommerce_dimension_unit "in"
wp option set woocommerce_product_type "both"
wp option set woocommerce_allow_tracking "no"
wp option update woocommerce_onboarding_profile '{"skipped": true }' --format=json

wp config set JETPACK_DEV_DEBUG true --raw

# create credentials for REST API
wp eval-file "$WCS_DIR/tests/bin/wc_rest_api_credentials.php"

# mock for Jetpack
cp "$WCS_DIR/tests/e2e/config/travis/wc-services-testing-helper.php" "$WP_CORE_DIR/wp-content/plugins/"

# finally activate WCS
wp config set WOOCOMMERCE_SERVICES_LOCAL_TEST_MODE true --raw
wp config set WOOCOMMERCE_CONNECT_FREQUENT_FETCH true --raw
wp config set WOOCOMMERCE_CONNECT_SERVER_URL http://host.docker.internal:5000/
wp plugin activate woocommerce-services
wp plugin activate wc-services-testing-helper
wp option update jetpack_tos_agreed 1
wp option update wc_connect_options '{"tos_accepted": true }' --format=json
wp transient set wcc_is_new_label_user false

# set permalink settings to post name to enable REST API
wp rewrite structure '/%postname%/'
