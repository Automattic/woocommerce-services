#!/bin/sh

# phpcbf returns exit-code as 1 even if all errors were fixed.
# https://github.com/squizlabs/PHP_CodeSniffer/issues/3057#issuecomment-919794895

# Use --filter=GitStaged so phpcbf only processes files staged in git.
# Use --extensions=php to skip JS/CSS (those are handled by ESLint via lint-staged).
./vendor/bin/phpcbf . --standard=WooCommerce-Core,WordPress-Core,WordPress-Extra --filter=GitStaged --extensions=php

status=$?

[ $status -eq 1 ] && exit 0 || exit $status