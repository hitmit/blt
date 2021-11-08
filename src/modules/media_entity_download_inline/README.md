# Media Entity Download Inline

This module extends the [Media Entity Download](https://www.drupal.org/project/media_entity_download) module by opening all `MediaEntityDownloadInlineSubscriber::INLINE_EXTENSIONS` in the browser rather than triggering a download.

Currently, if you want media items such as PDF's to open in the browser, you need to manually add an `inline` query parameter to the URL, e.g. `https://www.example.com/example.pdf?inline`. However, enabling this module will do that for you.