#!/bin/bash

# Exit immediately on errors, and echo commands as they are executed.
set -e

prep_term()
{
  unset term_child_pid
  unset term_kill_needed
  trap 'handle_term' TERM INT
}

handle_term()
{
  if [ "${term_child_pid}" ]; then
    echo "HANDLE TERM SIGNAL HERE"
    kill -TERM "${term_child_pid}" 2>/dev/null
  else
    term_kill_needed="yes"
  fi
}

wait_term()
{

  if [ "${term_kill_needed}" ]; then
    kill -TERM "${term_child_pid}" 2>/dev/null
  fi
  wait ${term_child_pid}
  trap - TERM INT
  wait ${term_child_pid}
}

prep_term
drush runserver 0.0.0.0:8080&
term_child_pid=$!
drush uli --uri 0.0.0.0:8080
wait_term
