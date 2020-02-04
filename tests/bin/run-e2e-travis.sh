#!/usr/bin/env bash
if [[ ${RUN_E2E} == 1 ]]; then
    export WP_BASE_URL="http://localhost:8080"
    export WP_CUSTOMER_USER_NAME=customer
    export WP_CUSTOMER_USER_PW=password
    export WP_ADMIN_USER_NAME=admin
    export WP_ADMIN_USER_PW=password
	export WC_E2E_REST_API_CONSUMER_KEY=wc_rest_api_consumer_key
	export WC_E2E_REST_API_CONSUMER_SECRET=wc_rest_api_consumer_secret
    export WOOCOMMERCE_SERVICES_CI_TEST_MODE=true

fi
