#!/bin/bash

[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"  # This loads nvm
nvm use 12
yarn --cwd ./src/web/core install
yarn --cwd ./src/web/core test:nightwatch --tag $1