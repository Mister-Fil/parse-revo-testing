#!/bin/bash

docker-compose exec -u "root:root" "app" composer "$@"
