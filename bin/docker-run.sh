#!/usr/bin/env bash

#
# This file is ment to run ONLY in a AWS ECS environment configured by melt's CDK constructs
#

set -e

commit_changes()
{
  # Current state of git
  STATUS=$(git status --porcelain)

  if [ ${#STATUS} -eq 0 ]; then
    echo "No changes to commit, exiting."
  else
    echo "Uncommited changes..."

    timestamp=$(date +%Y%m%d_%H%M%S)

    BRANCH_NAME="stash_$APP-$ENVIRONMENT-$timestamp"
    echo "Saving to branch: $BRANCH_NAME"

    git checkout -b "$BRANCH_NAME"
    git add -A
    git commit -m "Stashing uncommited changes from $APP-$ENVIRONMENT due to app shutdown"
    git push --set-upstream origin "$BRANCH_NAME"

    echo "Changes saved, exiting."
  fi
}

prep_term()
{
  unset term_child_pid
  unset term_kill_needed
  trap 'handle_term' TERM INT
}

handle_term()
{
  if [ "${term_child_pid}" ]; then
    commit_changes
    kill -TERM "${term_child_pid}" 2>/dev/null
  else
    term_kill_needed="yes"
  fi
}

wait_term()
{
  term_child_pid=$!
  if [ "${term_kill_needed}" ]; then
    commit_changes
    kill -TERM "${term_child_pid}" 2>/dev/null
  fi
  wait ${term_child_pid}
  trap - TERM INT
  wait ${term_child_pid}
}

echo "Use this URL to login as admin once the server is started:"
drush uli -l "https://$CANONICAL_DOMAIN_NAME"

# If running in ECS with Cognito configured, enable it for auth
if [ ! -z "$COGNITO_CLIENT_ID" ];
then
cat >>src/settings.local.php <<EOL
\$config['alb_auth.settings']['enabled'] = TRUE;
\$config['alb_auth.settings']['cognito']['base_url'] = 'https://'.'$COGNITO_BASE_URL';
\$config['alb_auth.settings']['cognito']['client_id'] = '$COGNITO_CLIENT_ID';
EOL
fi

# Add dynamic settings for the Static Publish module. This will override any settings added
# to in the UI of the "Static" environment.
cat >>src/settings.local.php <<EOL
\$config['static_publish.settings']['environments']['static']['git_branch'] = '$ENVIRONMENT'.'-save';
\$config['static_publish.settings']['environments']['static']['base_url'] = 'https://$STATIC_DOMAIN_NAME';
\$config['static_publish.settings']['environments']['static']['s3_bucket_name'] = '$SOURCE_S3_BUCKET';
\$config['static_publish.settings']['environments']['static']['cloudfront_id'] = '$DISTRIBUTION_ID';

\$settings['static_publish_enabled'] = TRUE;
\$settings['static_publish_base_directory'] = '/var/www/site';
\$settings['static_publish_user_home_directory'] = '/var/www/site';
EOL

# Run the server, and catch TERM/INT signals to ensure we save our work to github before we shutdown
prep_term
apache2-foreground &
wait_term
