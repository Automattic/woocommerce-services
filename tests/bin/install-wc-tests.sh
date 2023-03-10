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
WC_VERSION=${6-"7.4.1"}

export NVM_DIR="$HOME/.nvm"
NVM_PATH=/opt/homebrew/opt/nvm/nvm.sh

# Load nvm.
. $NVM_PATH

install_wc() {
	git clone --depth=1 --branch=$WC_VERSION https://github.com/woocommerce/woocommerce.git /tmp/woocommerce && cd /tmp/woocommerce/plugins/woocommerce && nvm use && composer install && pnpm run build:feature-config && echo "Done unzipping WooCommerce"
}

install_wp() {
	echo "Installing WordPress via WooCommerce install script" && bash /tmp/woocommerce/plugins/woocommerce/tests/bin/install.sh $DB_NAME $DB_USER "$DB_PASS" $DB_HOST $WP_VERSION && echo "Done unzipping WordPress"
}

if [ -z "$TMPDIR" ]; then
	echo "Variable \$TMPDIR is not set. Unable to remove current wordpress-tests-lib directory if needed - this might cause problems."
else
	echo "Removing directories wordpress and wordpress-tests-lib from \$TMPDIR ($TMPDIR)..."
	rm -rf $TMPDIR/wordpress $TMPDIR/wordpress-tests-lib
fi

rm -rf /tmp/woocommerce && install_wc && install_wp

