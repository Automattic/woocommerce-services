# Using the Built In Container for End to End Testing

This document provides general instructions for using `@woocommerce/e2e-environment` with the built in hosting container. 

## Prerequisites

Complete the [setup instructions](./README.md) in each project/repository.

## Initialization Requirements

The test sequencer uses a `ready` page to determine that the testing environment is ready for testing. This page is created by the built in initialization. It matches the following spec:

```
wp post create --post_type=page --post_status=publish --post_title='Ready' --post_content='E2E-tests.'
```

### Project Initialization

Each project will have its own begin test state and initialization script. For example, a project might start testing expecting that the [sample products](https://github.com/woocommerce/woocommerce/tree/trunk/sample-data) have already been imported. Below is the WP CLI equivalent of the built in initialization script for WooCommerce Core E2E testing:


```
wp core install --url=http://localhost:8084 --admin_user=admin --admin_password=password --admin_email=wooadmin@example.org
```

Project specific initialization can be added through an executable file at ```pluigns/woocommerce/tests/e2e/docker/initialize.sh```. WooCommerce core's script is:


```
#!/bin/bash

echo "Initializing WooCommerce E2E"

wp plugin activate woocommerce
wp theme install twentynineteen --activate
wp user create customer customer@woocommercecoree2etestsuite.com \
	--user_pass=password \
	--role=subscriber \
	--first_name='Jane' \
	--last_name='Smith' \
	--path=/var/www/html

# we cannot create API keys for the API, so we using basic auth, this plugin allows that.
wp plugin install https://github.com/WP-API/Basic-Auth/archive/master.zip --activate

# install the WP Mail Logging plugin to test emails
wp plugin install wp-mail-logging --activate
```

### Adhoc Initialization

The container build script supports an initialization script parameter

```shell script
pnpx wc-e2e docker:up plugins/woocommerce/tests/e2e/docker/init-wp-beta.sh
```

This script updates WordPress to the latest nightly point release

```shell script
#!/bin/bash

echo "Initializing WooCommerce E2E"

wp plugin install woocommerce --activate
wp theme install twentynineteen --activate
wp user create customer customer@woocommercecoree2etestsuite.com --user_pass=password --role=customer --path=/var/www/html

# we cannot create API keys for the API, so we using basic auth, this plugin allows that.
wp plugin install https://github.com/WP-API/Basic-Auth/archive/master.zip --activate

echo "Updating to WordPress Nightly Point Release"

wp plugin install wordpress-beta-tester --activate
wp core check-update

```


### Container Configuration

The built in container initialization needs to know the particulars of your test install to run the tests. The built in uses the following default settings:

```
{
  "url": "http://localhost:8084/",
  "appName": "{repository-folder-name}",
  "users": {
    "admin": {
      "username": "admin",
      "password": "password",
      "email": "admin@woocommercecoree2etestsuite.com"
    }
  }
}
```

You can override these in `/tests/e2e/config/default.json`.

- The `appName` entry is optional. If present, it is used as a prefix for the docker container names.
- The `customer` entry is not required by the sequencer but is required for the core test suite.
- The test sequencer does not use the user account email addresses.

```
{
  "url": "http://localhost:8089/",
  "users": {
    "admin": {
      "username": "admin",
      "password": "password",
    },
    "customer": {
      "username": "customer",
      "password": "password",
    }
  }
}
```

### Folder Mapping

The built in container defaults to mapping the root folder of the repository to a folder in the `plugins` folder. Use the environment variables `WC_E2E_FOLDER` and `WC_E2E_FOLDER_MAPPING` to override this mapping. The `WC_E2E_FOLDER` is a path relative to the root of the project. For example, in the  `woocommerce` repository this mapping is:

- `WC_E2E_FOLDER=plugins/woocommerce`
- `WC_E2E_FOLDER_MAPPING=/var/www/html/wp-content/plugins/woocommerce`

Other repository examples:

- Storefront Theme - ```WC_E2E_FOLDER_MAPPING=/var/www/html/wp-content/themes/storefront npx wc-e2e docker:up```
- Site Project - ```WC_E2E_FOLDER_MAPPING=/var/www/html/wp-content npx wc-e2e docker:up```

Since the introduction of the WooCommerce Monorepo, a `WC_CORE_PATH` environment variable maps to Core WooCommerce at `plugins/woocommerce`. It can also be overriden in a similar fashion.

### Specifying Server Software versions

The built-in container supports these variables for use locally and in CI environments:

- `WP_VERSION` - WordPress (default `latest`)
- `PHP_VERSION` - PHP (default `latest`)
- `MARIADB_VERSION` - MariaDB (default `latest`)

### Travis CI Supported Versions

Travis CI uses environment variables to allow control of some software versions in the testing environment. The built-in container supports these variables:

- `WP_VERSION` - WordPress (default `latest`)
- `TRAVIS_PHP_VERSION` - PHP (default `latest`)
- `TRAVIS_MARIADB_VERSION` - MariaDB (default `latest`)

### Travis CI

To enable Travis CI testing in your repository add the following to the appropriate sections of your `.travis.yml` config file.

```yaml
version: ~> 1.0

  include:
    - name: "Core E2E Tests"
    php: 7.4
    env: WP_VERSION=latest WP_MULTISITE=0 RUN_E2E=1

....

script:
  - npm install jest --global
  - npx wc-e2e docker:up
  - npx wc-e2e test:e2e

....

after_script:
  - npx wc-e2e docker:down
```
