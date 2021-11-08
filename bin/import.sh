#!/bin/bash

# Exit immediately on errors, and echo commands as they are executed.
set -ex

# if [[ "$ENVIRONMENT" == "prod" ]];
# then
#   cat >>src/settings.local.php <<EOL
# \$config['config_token.tokens']['replacements']['segment_write_key'] = '';
# EOL
# fi

echo "Importing site from disk"
drush tome:install -y

# @TODO We may not need to do this, but instead just use the simple
# sitemap settings configure the sitemap.xml for each content type
# rather than exposing the fields to the user.
#
# Simple sitemap is broken on config import and has been for a long time
# See: https://www.drupal.org/project/simple_sitemap/issues/2817145
# Force Drush to load sitemap config before generating sitemap
# echo "Fixing simple sitemap config import"
# WORKING_DIR="$(pwd)"
# TMP_CONFIG_DIR="$WORKING_DIR/.tmp-config-import"
# rm -rf $TMP_CONFIG_DIR
# mkdir $TMP_CONFIG_DIR
# cp "$WORKING_DIR/src/config/simple_sitemap.bundle_settings.default.node.page.yml" $TMP_CONFIG_DIR
# drush config:import -y --partial --source $TMP_CONFIG_DIR
# rm -rf $TMP_CONFIG_DIR

echo "Importing common Cohesion assets"
drush cohesion:import -y

echo "Rebuilding Cohesion templates"
drush cohesion:rebuild -y

# We utilize Cohesion's GA analytics tracking UI, but have our own Segment hooks that use the data,
# thus we need to clear out their tracking code.
echo "" > src/web/sites/default/files/cohesion/scripts/analytics/analytics-events.js

# Clear Cache.
drush cr
