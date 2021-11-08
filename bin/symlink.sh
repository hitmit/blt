#!/usr/bin/env bash

# The sites directory get created by default without write flag set on owner
chmod 755 src/web/sites/default

# Link the settings file at the root of our src path to where Drupal wants it
if [ ! -L src/web/sites/default/settings.php ] && [ ! -e src/web/sites/default/settings.php ]; then
    ln -s ../../../settings.php src/web/sites/default/settings.php
fi

# Link our local settings file at the root of our src path to where Drupal wants it
if [ ! -L src/web/sites/default/settings.local.php ] && [ ! -e src/web/sites/default/settings.local.php ]; then
  ln -s ../../../settings.local.php src/web/sites/default/settings.local.php
fi

# Link our local services file at the root of our src path to where Drupal wants it
if [ -e src/services.yml ] && [ ! -L src/web/sites/default/services.yml ] && [ ! -e src/web/sites/default/services.yml ]; then
  ln -s ../../../services.yml src/web/sites/default/services.yml
fi

mkdir -p src/web/themes
mkdir -p src/web/modules
mkdir -p src/web/profiles
mkdir -p src/web/sites/default

if [ ! -L src/web/themes/custom ] && [ ! -e src/web/themes/custom ]; then
  ln -s ../../themes src/web/themes/custom
fi

if [ ! -L src/web/modules/custom ] && [ ! -e src/web/modules/custom ]; then
  ln -s ../../modules src/web/modules/custom
fi

if [ ! -L src/web/profiles/custom ] && [ ! -e src/web/profiles/custom ]; then
  ln -s ../../profiles src/web/profiles/custom
fi
