#!/usr/bin/env bash
if [[ ${RUN_E2E} == 1 ]]; then
	# Set env variables for jest
	WP_BASE_URL="http://localhost:8080"
	WP_CUSTOMER_USER_NAME=customer
	WP_CUSTOMER_USER_PW=password
	WP_ADMIN_USER_NAME=admin
	WP_ADMIN_USER_PW=password
	
	# Run the tests
	./node_modules/jest/bin/jest.js --config=./tests/e2e-tests/config/jest.config.js --rootDir=./ --verbose --runInBand;
fi
