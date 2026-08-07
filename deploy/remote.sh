#!/usr/bin/env bash
#
# Runs on the Cloudways server. Called by deploy/push.sh — you shouldn't
# normally need to run it by hand.
#
# Safe to run repeatedly. Never touches .env or the database contents.

set -euo pipefail
cd "$(dirname "$0")/.."

[ -f artisan ] || { echo "Not an application root — no artisan here." >&2; exit 1; }

step() { printf '\n\033[1m  %s\033[0m\n' "$1"; }

if [ ! -f .env ]; then
    echo "No .env here yet. See deploy/FIRST-RUN.md." >&2
    exit 1
fi

step "Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

step "Migrating"
php artisan migrate --force

step "Seeding page content if absent"
# firstOrCreate — cannot overwrite what an editor has written.
php artisan db:seed --force

step "Linking storage"
[ -L public/storage ] || php artisan storage:link

step "Caching config, routes and views"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

step "Restarting the queue worker"
# Workers hold old code in memory until told to finish the current job and exit.
php artisan queue:restart

step "Preflight"
php artisan frith:preflight
