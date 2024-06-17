#!/bin/sh

# phpcbf returns exit-code as 1 even if all errors were fixed.
# https://github.com/squizlabs/PHP_CodeSniffer/issues/3057#issuecomment-919794895

composer run phpcbf $@

status=$?

[ $status -eq 1 ] && exit 0 || exit $status