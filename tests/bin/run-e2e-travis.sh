#!/usr/bin/env bash
if [[ ${RUN_E2E} == 1 ]]; then

	WP_SITE_URL="http://localhost:8080"
	WP_CUSTOMER_USER_NAME=customer
	WP_CUSTOMER_USER_PW=password
	WP_ADMIN_USER_NAME=admin
	WP_ADMIN_USER_PW=password

	# Start xvfb to run the tests
	export WP_BASE_URL="$WP_SITE_URL"
	export WP_CUSTOMER_USER_NAME
	export WP_CUSTOMER_USER_PW
	export WP_ADMIN_USER_NAME
	export WP_ADMIN_USER_PW
	export DISPLAY=:99.0
	sh -e /etc/init.d/xvfb start
	sleep 3

	# Run the tests
	npm run test-e2e
fi
