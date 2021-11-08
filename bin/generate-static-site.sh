#!/bin/bash

# Exit immediately on errors, and echo commands as they are executed.
set -ex

echo "Generating sitemap"
drush simple-sitemap:generate --uri $1

echo "Exporting static site"
drush tome:static --path-count=1 --uri $1

# Create a robots.txt
cat >src/html/robots.txt <<EOL
Sitemap: $1/sitemap.xml
User-agent: *
Disallow: /user/
Disallow: /taxonomy/
Disallow: /cohesionapi/
Disallow: /ajax_machine_name/
Disallow: /search/node/
Disallow: /jsonapi/
EOL

# Remove content that we don't want published
rm -rf src/html/node
rm -rf src/html/taxonomy
rm -rf src/html/user
rm -rf src/html/rest
rm -rf src/html/cohesionapi
rm -rf src/html/ajax_machine_name
rm -rf src/html/jsonapi
rm -rf src/html/media