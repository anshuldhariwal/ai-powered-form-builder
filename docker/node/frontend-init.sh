#!/bin/sh

set -eu

mkdir -p node_modules storage/framework
chown -R node:node node_modules storage/framework

runuser --user node -- npm ci --no-audit --no-fund
