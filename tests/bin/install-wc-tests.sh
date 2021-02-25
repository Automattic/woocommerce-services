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
	# TODO: fetch the latest tag
	curl -Lo /tmp/woocommerce.zip https://github.com/woocommerce/woocommerce/releases/download/5.0.0/woocommerce.zip
	unzip /tmp/woocommerce.zip -d /tmp/woocommerce
}

install_wp() {
	bash /tmp/woocommerce/tests/bin/install.sh $DB_NAME $DB_USER "$DB_PASS" $DB_HOST $WP_VERSION
}

install_wc
install_wp
