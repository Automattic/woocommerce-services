#!/usr/bin/env bash
if [[ ${RUN_E2E} == 1 ]]; then
    export WP_BASE_URL="http://localhost:8080"
    export WP_ADMIN_USER_NAME=admin
    export WP_ADMIN_USER_PW=password
	export WC_E2E_REST_API_CONSUMER_KEY=ck_wc_rest_api_consumer_key
	export WC_E2E_REST_API_CONSUMER_SECRET=cs_wc_rest_api_consumer_secret

fi
