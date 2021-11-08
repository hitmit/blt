# Private Packages

WARNING!! DO NOT delete OR modify any code in this directory. This is done via Composer when needed.

Users who have permissions to access meltmedia's private Github Drupal repos should be the ONLY ones to update/remove/add packages to this directory.
### Configuration

There are 3 areas in the `composer.json` file that will need to be updated when adding/removing/updating one of meltmedia's private packages.

1. The `repositories` array/object
   - Composer will look in all your repositories to find the packages your project requires
1. The `require` object
   - Tells composer the package name and version to pull in to the project
1. The `extra.installer-paths.src/modules/private-packages/{$name}`
   - Is an array of packages that should be stored in the `./src/private-package` directory