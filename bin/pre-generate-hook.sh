#!/bin/bash

# Exit immediately on errors, and echo commands as they are executed.
set -ex

echo "Generating sitemap"
drush simple-sitemap:generate --uri $1
