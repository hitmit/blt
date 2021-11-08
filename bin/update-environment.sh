#!/bin/bash

# The purpose of this file is to update the current environment's
# without wiping the database.

# Exit immediately on errors, and echo commands as they are executed.
set -ex

echo "Removing directories to be re-installed"
rm -rf src/vendor src/web/core src/web/modules/contrib src/web/profiles/contrib src/web/themes/contrib

echo "Re-installing composer dependencies"
composer install

# We need to run `tome:import` twice because the first time, it errors out due to
# aliases not importing. However, running it the second time resolves the issue.
echo "Re-importing content and config"
drush tome:import -y || drush tome:import -y

echo "Importing common Site Studio assets"
drush cohesion:import -y

echo "Rebuilding Site Studio templates"
drush cohesion:rebuild -y

# We utilize Cohesion's GA analytics tracking UI, but have our own Segment hooks that use the data,
# thus we need to clear out their tracking code.
echo "" > src/web/sites/default/files/cohesion/scripts/analytics/analytics-events.js

# Clear Cache.
drush cr
