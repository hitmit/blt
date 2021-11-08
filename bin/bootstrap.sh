#!/bin/bash

# Exit immediately on errors, and echo commands as they are executed.
set -ex

# Set colors, because we're fancy.
CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
NC='\033[0m'


printf "${CYAN}### Welcome to melt CMS! ###${NC}\n"
COMPOSER=$(composer -V)
VERSION="Composer version 1"
printf "${CYAN}Composer version:${NC} $COMPOSER\n"
if grep -q "$VERSION" <<< "$COMPOSER"; then
    printf "${YELLOW}This script requires Composer version 2 or later. Go here for instructions to install: https://getcomposer.org${NC}\n";
    exit 0;
fi

# @TODO this breaks on composer's post-create-project-cmd event command.
# DEFAULT_SITE_NAME="melt CMS"
# printf "Please enter the site name for the project, defaults to [${GREEN}${DEFAULT_SITE_NAME}${NC}]: "
# read SITE_NAME
# SITE_NAME="${SITE_NAME:-$DEFAULT_SITE_NAME}"

printf "${CYAN}*** Thanks! Proceeding with the install...${NC}\n"

# echo "Initializing Tome"
echo "Installing new site using Drupal's 'standard' profile"
drush site-install standard --sites-subdir=default -y

echo "Enabling required modules"
drush en tome tome_sync cohesion_tome cohesion_style_guide sitestudio_page_builder -y

echo "Enabling recommended core modules"
drush en  datetime_range  media_library  telephone -y

echo "Enabling recommended contrib modules"
  drush en admin_toolbar_tools \
  bulk_update_fields \
  config_token \
  context_ui \
  dx8_addtoany \
  easy_breadcrumb \
  editor_advanced_link \
  entity_browser \
  entity_clone \
  eu_cookie_compliance \
  field_group \
  linkit \
  media_entity_download \
  menu_link_attributes \
  metatag \
  metatag_facebook \
  metatag_open_graph \
  metatag_segment \
  metatag_twitter_cards \
  metatag_verification \
  pathauto \
  seckit \
  simple_sitemap \
  sitemap \
  upgrade_status \
  cohesion_javascript_element \
  melt_glossify \
  melt_logging \
  melt_modal \
  melt_sticky_drawer \
  tome_static_include_paths \
  tome_static_find_and_replace \
  melt_cms_page \
  melt_cms_article \
  acquia_cms_document \
  acquia_cms_image \
  acquia_cms_toolbar \
  acquia_cms_video \
  acquia_cms_site_studio \
  -y

echo "Exporting content, config and files using Tome"
drush tome:export -y

# Initialize Acquia Cohesion
echo "Initializing Acquia Cohesion"
drush theme:enable cohesion_theme -y
drush config:set system.theme default cohesion_theme -y
drush config:set cohesion.settings api_url 'https://api.cohesiondx.com' -y
drush config:set cohesion.settings api_key 26568a22-c7ed-11e8-a8d5-f2801f1b9fd -y
drush config:set cohesion.settings organization_key meltmedia-2018-5lkhvmgut -y
drush config:set cohesion.settings site_id a6043221-f09e-4795-a3ef-6f42b8d698e7 -y

# Change default admin theme to Acquia's Claro theme
drush theme:enable acquia_claro -y
drush config:set system.theme admin acquia_claro -y

# echo "Importing Acquia Cohesion Default Settings"
drush cohesion:import -y
drush cohesion:rebuild -y

# # Clear Cache.
drush cr

# We need to remove all .git folders from installed modules as some include them and composer don't care
find ./src \( -name ".git" -o -name ".gitmodules" -o -name ".gitattributes" \) -exec rm -rf -- {} +
