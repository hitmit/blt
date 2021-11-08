#!/bin/bash

if [ ! -z "$ECS_CONTAINER_NAME" ]
then

  # Export current state of app
  drush tome:export -y

  # Current state of git
  git status

  timestamp="$(date +"%T")"

  BRANCH_NAME="stash_$APP-$ENVIRONMENT-$timestamp"

  git checkout -b "$BRANCH_NAME"
  git add -A
  git commit -m "Stashing uncommited changes from $APP-$ENVIRONMENT due to app shutdown"
  git push --set-upstream origin "$BRANCH_NAME"

fi
