#!/bin/bash
APP_DIR="`dirname $PWD`" docker-compose -p goat_query_testing up -d --build --remove-orphans --force-recreate
