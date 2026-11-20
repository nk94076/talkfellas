#!/usr/bin/env bash
# Run ON THE SERVER as site user: builds the blog and publishes to CloudPanel htdocs.
set -e
APP=/home/talkfellas/app
WEB=/home/talkfellas/htdocs/talkfellas.com
cd "$APP"
git pull
npm ci
npm run build
rsync -a --delete dist/ "$WEB"/
echo "Deployed."
