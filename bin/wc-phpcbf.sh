#!/bin/sh

# phpcbf returns exit-code as 1 even if all errors were fixed.
# https://github.com/squizlabs/PHP_CodeSniffer/issues/3057#issuecomment-919794895

# Run phpcbf directly on the passed files instead of using `composer run phpcbf`
# which hardcodes `.` as the scan path and would format the entire directory
# (including JS files) regardless of the file list lint-staged provides.
./vendor/bin/phpcbf --standard=WooCommerce-Core,WordPress-Core,WordPress-Extra "$@"

status=$?

[ $status -eq 1 ] && exit 0 || exit $status