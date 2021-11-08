#!/usr/bin/env bash

# The sites directory get created by default without write flag set on owner
chmod 755 src/web/sites/default

rm -rf src/web
rm -rf src/drush
rm -rf src/vendor
