#!/usr/bin/env bash
if [[ ${RUN_E2E} == 1 ]]; then
	export WP_BASE_URL="http://localhost:8080"
	export WP_CUSTOMER_USER_NAME=customer
	export WP_CUSTOMER_USER_PW=password
	export WP_ADMIN_USER_NAME=admin
	export WP_ADMIN_USER_PW=password

	# Run the tests
	./node_modules/jest/bin/jest.js --config=./tests/e2e-tests/config/jest.config.js --rootDir=./ --verbose --runInBand;
fi
