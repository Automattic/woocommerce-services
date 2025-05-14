#!/bin/bash
set -e

WC_VERSION=${WC_VERSION:-"7.6.1"}
WP_VERSION=${WP_VERSION:-"6.3.0"}

echo "Cleaning previous WooCommerce clone..."
if [ -d /tmp/woocommerce ]; then
  chmod -R u+w /tmp/woocommerce || true
  rm -rf /tmp/woocommerce
fi

echo "Cloning WooCommerce $WC_VERSION..."
git clone --depth=1 --branch="$WC_VERSION" https://github.com/woocommerce/woocommerce.git /tmp/woocommerce

echo "Installing WooCommerce dependencies..."
cd /tmp/woocommerce/plugins/woocommerce
composer install
php bin/generate-feature-config.php

echo "Setting up WordPress test environment..."
mysql -h mariadb -u root -proot -e "DROP DATABASE IF EXISTS wp_test;"
bash tests/bin/install.sh wp_test root root mariadb "${WP_VERSION}"

if [[ "$WC_VERSION" == "7.5.1" || "$WC_VERSION" == "7.6.1" ]]; then
  echo "Installing PHPUnit 8 for legacy WC versions..."
  composer require -W phpunit/phpunit:^8
fi

echo "Installing plugin dependencies..."
cd /workspace
composer install

echo "Running PHPUnit tests..."
./vendor/bin/phpunit -c phpunit.xml.dist
