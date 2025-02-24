#!/bin/bash

usage() {
  echo "$(tput setaf 2)Usage : $0 ([--admin] )"
  echo
  echo -e "\t --admin: user superuser"
}

admin=0
for arg; do
  shift
  [ "$arg" = "--admin" ] && admin=1 && continue
  set -- "$@" "$arg"
done

# This is just a convenient script to run command in container
if [[ $admin == 1 ]]; then
  docker compose exec backend "$@"
else
  docker compose exec -u "$(id -u)" backend "$@"
fi
