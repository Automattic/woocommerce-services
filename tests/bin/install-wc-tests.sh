#!/usr/bin/env bash

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [wc-version]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
WC_VERSION=${6-"5.0.0"}

install_wc() {
	git clone --depth=1 --branch=$WC_VERSION https://github.com/woocommerce/woocommerce.git /tmp/woocommerce
	composer install -d /tmp/woocommerce/plugins/woocommerce
	echo "Done unzipping WooCommerce\n"
}

install_wp() {
	echo "Installing WordPress via WooCommerce install script\n"
	bash /tmp/woocommerce/plugins/woocommerce/tests/bin/install.sh $DB_NAME $DB_USER "$DB_PASS" $DB_HOST $WP_VERSION
	echo "Done unzipping WordPress\n"
}

install_wc
install_wp
