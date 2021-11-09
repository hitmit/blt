#!/usr/bin/env bash
# meltmedia - Travis CI - Acquia - Auto-deploy - v1.0.1
set -e

# Setup our variables
ACQUIA_REMOTE="git@github.com:hitmit/blt.git"
# ACQUIA_REMOTE_BRANCH=""
ACQUIA_REMOTE_BRANCH=test3-build
BUILD_BRANCH=${ACQUIA_REMOTE_BRANCH}
BUILD_PATH="deploy"

# Check if we passed a branch to deploy to
if [ $1 ]; then
  TARGET_BRANCH=$1
  BUILD_BRANCH=${TARGET_BRANCH}-build
  ACQUIA_REMOTE_BRANCH=${BUILD_BRANCH}
fi

# Check the Travis branch to determine the build branch
if [ ${TRAVIS_BRANCH} ]; then
  BUILD_BRANCH=${TRAVIS_BRANCH}-build
fi

# Clone the repo locally
echo "Cloning the remote repo, ${ACQUIA_REMOTE}, ${ACQUIA_REMOTE_BRANCH}..."
rm -Rf ${BUILD_PATH}
remote_exists="$(git ls-remote --heads ${ACQUIA_REMOTE} ${ACQUIA_REMOTE_BRANCH} | wc -l)"
if [ "${remote_exists}" -eq "0" ] ; then
  echo "Remote build branch doesn't exist yet, cloning from default branch..."
  git clone --depth 1 ${ACQUIA_REMOTE} ${BUILD_PATH}
else
  git clone --depth 1 ${ACQUIA_REMOTE} -b ${ACQUIA_REMOTE_BRANCH} ${BUILD_PATH}
fi

# Copy all build files
echo "Copying all build files..."
rsync -rtl --delete --exclude=".git" --exclude="src/web/sites"  ./src ./bin .gitignore-deploy composer.json-deploy  ${BUILD_PATH}


# Commit the changes to the build branch or tag
cd ${BUILD_PATH}
echo  "${BUILD_PATH}.."
echo "Switching composer json"
mv composer.json-deploy composer.json

# Composer install
echo "Running composer install..."
composer install --no-dev --no-interaction --optimize-autoloader
echo "Switching src to docroot..."
rm -Rf docroot
mv src/web ./
mv web docroot

rm -Rf docroot/themes/custom
cp -r src/themes docroot/themes/custom

rm -Rf docroot/modules/custom
cp -r src/modules docroot/modules/custom

# # rm -Rf docroot/profiles/custom
# # cp -r src/profiles docroot/profiles/custom


cp -r src/content ./

cp -r src/config ./

# mv src/vendor ./

echo "Committing changes to ${BUILD_BRANCH}..."
git checkout -B ${BUILD_BRANCH}
echo "Switching .gitignore"
mv .gitignore-deploy .gitignore
git add --all
# git add -f vendor
git commit --allow-empty --quiet -m "Build of ${TRAVIS_BRANCH}"

# # Cut a tag if this is a tag build, and do any needed pushes
if [ -n "${TRAVIS_TAG}" ]; then
  echo "Cutting a tag of ${BUILD_BRANCH}..."
  git tag -a "${TRAVIS_TAG}" -m "Build of source tag ${TRAVIS_TAG}"
  echo "Pushing up tag ${TRAVIS_TAG}..."
  git push origin --tags
else
  # Push up the changes
  echo "Pushing up branch..."
  git push origin -f ${BUILD_BRANCH}
fi