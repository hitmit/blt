# melt CMS Page

This module extends the [Acquia CMS Page](https://www.drupal.org/project/acquia_cms_page) module in the following ways:
- Adds new SEO fields
- Overrides the `Basic page` content type's form display by grouping together fields by content and seo
- Disables fields on the edit form display that we don't need exposed


## Configuration
This module also relies on the [Config Rewrite](https://www.drupal.org/project/config_rewrite). See the `/config/rewrite` directory to see what config we rewrite.