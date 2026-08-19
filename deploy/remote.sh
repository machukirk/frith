#!/usr/bin/env bash
#
# Runs on the Cloudways server. Called by deploy/push.sh — you shouldn't
# normally need to run it by hand.
#
# Safe to run repeatedly. Never touches .env or the database contents.

set -euo pipefail
cd "$(dirname "$0")/.."

# The database holds content that real people are editing, so nothing here
# writes to it unless asked. Seeding is opt-in even though every seeder is
# firstOrCreate: "it cannot overwrite anything" is a claim about today's code,
# and the middle of a deploy is the wrong place to be relying on that.
#
#   --no-db   run no migrations either
#   --seed    add any pages, screens or options new in this release
RUN_MIGRATIONS=1
RUN_SEED=0

for arg in "$@"; do
    case "$arg" in
        --no-db) RUN_MIGRATIONS=0 ;;
        --seed) RUN_SEED=1 ;;
    esac
done

[ -f artisan ] || { echo "Not an application root — no artisan here." >&2; exit 1; }

step() { printf '\n\033[1m  %s\033[0m\n' "$1"; }

if [ ! -f .env ]; then
    echo "No .env here yet. See deploy/FIRST-RUN.md." >&2
    exit 1
fi

step "Normalising permissions"
# Whatever uploaded this may have carried a developer machine's private
# permissions with it, and nginx runs as www-data. Done here rather than with
# rsync --chmod, which macOS's openrsync does not support.
find . -type d ! -path "./storage/*" -exec chmod 755 {} +
find . -type f ! -path "./storage/*" -exec chmod 644 {} +
chmod -R 775 storage bootstrap/cache
chmod 600 .env
chmod +x artisan deploy/*.sh

step "Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

if [ "$RUN_MIGRATIONS" = 1 ]; then
    step "Migrating"
    php artisan migrate --force
else
    step "Leaving the database alone (--no-db)"
    # A silent skip is worse than no skip. If the code that just landed expects
    # a column that is not there, say so here rather than in a 500 later.
    pending=$(php artisan migrate:status 2>/dev/null | grep -c "Pending" || true)
    if [ "${pending:-0}" -gt 0 ]; then
        echo "  ! $pending migration(s) pending and NOT run."
        echo "    This release expects a schema the database does not have."
        echo "    Run when the coast is clear:  php artisan migrate --force"
    else
        echo "  Nothing was pending, so nothing was missed."
    fi
fi

if [ "$RUN_SEED" = 1 ]; then
    step "Seeding"
    php artisan db:seed --force
else
    step "Not seeding"
    echo "  Content is left exactly as the editors left it."
    echo "  Pass --seed to add pages, screens or options new in this release."
fi

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
