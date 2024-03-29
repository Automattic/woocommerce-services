#!/usr/bin/env bash
# see https://github.com/wp-cli/wp-cli/blob/master/templates/install-wp-tests.sh

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}

WP_TESTS_DIR=${WP_TESTS_DIR-/tmp/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-/tmp/wordpress/}

download() {
	if [ `which curl` ]; then
		curl -s "$1" > "$2";
	elif [ `which wget` ]; then
		wget -nv -O "$2" "$1"
	fi
}

if [[ $WP_VERSION =~ [0-9]+\.[0-9]+(\.[0-9]+)? ]]; then
	WP_TESTS_TAG="tags/$WP_VERSION"
else
	# http serves a single offer, whereas https serves multiple. we only want one
	download http://api.wordpress.org/core/version-check/1.7/ /tmp/wp-latest.json
	grep '[0-9]+\.[0-9]+(\.[0-9]+)?' /tmp/wp-latest.json
	LATEST_VERSION=$(grep -o '"version":"[^"]*' /tmp/wp-latest.json | sed 's/"version":"//')
	if [[ -z "$LATEST_VERSION" ]]; then
		echo "Latest WordPress version could not be found"
		exit 1
	fi
	WP_TESTS_TAG="tags/$LATEST_VERSION"
fi

set -ex

install_wp() {

	if [ -d $WP_CORE_DIR ]; then
		return;
	fi

	mkdir -p $WP_CORE_DIR

	if [ $WP_VERSION == 'latest' ]; then
		local ARCHIVE_NAME='latest'
	else
		local ARCHIVE_NAME="wordpress-$WP_VERSION"
	fi

	download https://wordpress.org/${ARCHIVE_NAME}.tar.gz  /tmp/wordpress.tar.gz
	tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C $WP_CORE_DIR

	download https://raw.github.com/markoheijnen/wp-mysqli/master/db.php $WP_CORE_DIR/wp-content/db.php
}

get_latest_release() {
	curl --silent "https://api.github.com/repos/woocommerce/woocommerce/releases/latest" |
	grep '"tag_name":' |
	sed -E 's/.*"([^"]+)".*/\1/'
}

install_woocommerce() {
	cd $TRAVIS_BUILD_DIR
	cd ..
	git clone https://github.com/woocommerce/woocommerce.git
	cd woocommerce

	if [ $WC_VERSION == 'latest' ]; then
		local WC_VERSION=$(get_latest_release)
	else
		local WC_VERSION=$WC_VERSION
	fi

	git checkout $WC_VERSION
	cd -
}

install_test_suite() {
	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local ioption='-i .bak'
	else
		local ioption='-i'
	fi

	# set up testing suite if it doesn't yet exist
	if [ ! -d $WP_TESTS_DIR ]; then
		# set up testing suite
		mkdir -p $WP_TESTS_DIR
		svn co --quiet https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/ $WP_TESTS_DIR/includes
	fi

	cd $WP_TESTS_DIR

	if [ ! -f wp-tests-config.php ]; then
		download https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR':" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s|localhost|${DB_HOST}|" "$WP_TESTS_DIR"/wp-tests-config.php
	fi

}

install_db() {
	# parse DB_HOST for port or socket references
	local PARTS=(${DB_HOST//\:/ })
	local DB_HOSTNAME=${PARTS[0]};
	local DB_SOCK_OR_PORT=${PARTS[1]};
	local EXTRA=""

	if ! [ -z $DB_HOSTNAME ] ; then
		if [ $(echo $DB_SOCK_OR_PORT | grep -e '^[0-9]\{1,\}$') ]; then
			EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
		elif ! [ -z $DB_SOCK_OR_PORT ] ; then
			EXTRA=" --socket=$DB_SOCK_OR_PORT"
		elif ! [ -z $DB_HOSTNAME ] ; then
			EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
		fi
	fi

	# create database
	mysqladmin create $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA
}

install_e2e_site() {

	if [[ ${RUN_E2E} == 1 ]]; then

		# Script Variables
		CONFIG_DIR="./tests/e2e-tests/config/travis"
		WP_CORE_DIR="$HOME/wordpress"
		NGINX_DIR="$HOME/nginx"
		PHP_FPM_BIN="$HOME/.phpenv/versions/$TRAVIS_PHP_VERSION/sbin/php-fpm"
		PHP_FPM_CONF="$NGINX_DIR/php-fpm.conf"
		WP_SITE_URL="http://localhost:8080"
		BRANCH=$TRAVIS_BRANCH
		REPO=$TRAVIS_REPO_SLUG
		WORKING_DIR="$PWD"

		if [ "$TRAVIS_PULL_REQUEST_BRANCH" != "" ]; then
			BRANCH=$TRAVIS_PULL_REQUEST_BRANCH
			REPO=$TRAVIS_PULL_REQUEST_SLUG
		fi

		set -ev
		npm install
		export NODE_CONFIG_DIR="./tests/e2e-tests/config"

		# Set up nginx to run the server
		mkdir -p "$WP_CORE_DIR"
		mkdir -p "$NGINX_DIR"
		mkdir -p "$NGINX_DIR/sites-enabled"
		mkdir -p "$NGINX_DIR/var"

		cd woocommerce-services

		cp "$CONFIG_DIR/travis_php-fpm.conf" "$PHP_FPM_CONF"

		# Start php-fpm
		"$PHP_FPM_BIN" --fpm-config "$PHP_FPM_CONF"

		# Copy the default nginx config files
		cp "$CONFIG_DIR/travis_nginx.conf" "$NGINX_DIR/nginx.conf"
		cp "$CONFIG_DIR/travis_fastcgi.conf" "$NGINX_DIR/fastcgi.conf"
		cp "$CONFIG_DIR/travis_default-site.conf" "$NGINX_DIR/sites-enabled/default-site.conf"

		# Start nginx.
		nginx -c "$NGINX_DIR/nginx.conf"

		# Set up WordPress using wp-cli
		cd "$WP_CORE_DIR"

		curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
		php wp-cli.phar core download --version=$WP_VERSION
		php wp-cli.phar core config --dbname=$DB_NAME --dbuser=$DB_USER --dbpass=$DB_PASS --dbhost=$DB_HOST --dbprefix=wp_ --extra-php <<PHP
/* Change WP_MEMORY_LIMIT to increase the memory limit for public pages. */
define('WP_MEMORY_LIMIT', '256M');
define('SCRIPT_DEBUG', true);
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
PHP
		php wp-cli.phar core install --url="$WP_SITE_URL" --title="E2E Woo Services Test Site" --admin_user=admin --admin_password=password --admin_email=admin@e2ewootestsite.com --path=$WP_CORE_DIR
		php wp-cli.phar plugin install woocommerce --activate
		php wp-cli.phar user create customer customer@e2ewootestsite.com --user_pass=customer_password --role=customer --path=$WP_CORE_DIR

		# Copying contents of services branch manually, since unable to download a private repo zip
		cp -r $WORKING_DIR/woocommerce-services $WP_CORE_DIR/wp-content/plugins/

		# Creates the WC REST API credentials
		php wp-cli.phar eval-file "$WP_CORE_DIR/wp-content/plugins/woocommerce-services/tests/bin/wc_rest_api_credentials.php"

		cd $WP_CORE_DIR/wp-content/plugins/woocommerce-services

		# Copy testing helper plugin to wordpress plugins folder
		cp "$CONFIG_DIR/wc-services-testing-helper.php" $WP_CORE_DIR/wp-content/plugins/

		# Run `npm run dist` in order to compile JS and CSS files before activating services
		npm run dist

		cd "$WP_CORE_DIR"
		php wp-cli.phar plugin activate woocommerce-services
		php wp-cli.phar plugin activate wc-services-testing-helper

		php wp-cli.phar option update jetpack_tos_agreed 1
		php wp-cli.phar option set woocommerce_store_address "60 29th Street"
        php wp-cli.phar option set woocommerce_store_address_2 "#343"
        php wp-cli.phar option set woocommerce_store_city "San Francisco"
        php wp-cli.phar option set woocommerce_default_country "US:CA"
        php wp-cli.phar option set woocommerce_store_postcode "94110"
        php wp-cli.phar option set woocommerce_currency "USD"
        php wp-cli.phar config set JETPACK_DEV_DEBUG true --raw
        php wp-cli.phar config set WOOCOMMERCE_SERVICES_LOCAL_TEST_MODE true --raw
        php wp-cli.phar config set WOOCOMMERCE_CONNECT_FREQUENT_FETCH true --raw
        php wp-cli.phar config set WOOCOMMERCE_CONNECT_SERVER_URL http://host.docker.internal:5000/

		php wp-cli.phar option update wc_connect_options '{"tos_accepted": true }' --format=json

		php wp-cli.phar transient set wcc_is_new_label_user false

		cd "$WORKING_DIR"

	fi
}

install_wp
install_test_suite
if [ "$TRAVIS" == true ]; then
	install_woocommerce
fi
install_db
install_e2e_site
