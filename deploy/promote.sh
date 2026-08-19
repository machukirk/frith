#!/usr/bin/env bash
#
# Promote the staged release in ~/private_html/frith-release to the live
# directory. Run ON THE SERVER:
#
#   bash ~/private_html/frith-release/deploy/promote.sh
#
# This is the moment frith.community starts serving the real site, so it
# refuses to run until preflight passes. Backs up what it replaces.

set -euo pipefail

STAGE="$HOME/private_html/frith-release"
LIVE="$HOME/public_html"
BACKUP="$HOME/private_html/public_html-backup-$(date +%Y%m%d-%H%M%S).tar.gz"

step() { printf '\n\033[1m▸ %s\033[0m\n' "$1"; }

[ -d "$STAGE" ] || { echo "No staged release at $STAGE" >&2; exit 1; }
[ -f "$STAGE/.env" ] || { echo "No .env in the staged release" >&2; exit 1; }

step "Checking the staged release is actually ready"
# Deliberately blocking. Promoting a release that cannot send email gives you a
# form that collects addresses and silently never confirms any of them, and the
# people affected have no way to tell you.
( cd "$STAGE" && php artisan frith:preflight )

step "Backing up the current live directory"
tar -czf "$BACKUP" -C "$HOME" public_html
echo "  $BACKUP"

step "Backing up the database"
# The file backup above no longer covers everything worth keeping: the page
# copy, the form wording and every option are rows now, and people edit them
# between deploys. Read-only — this writes a file, never the database.
env_value() { grep -E "^$1=" "$LIVE/.env" | head -1 | cut -d= -f2- | tr -d '"'"'"'\r'; }

DB_DUMP="$HOME/private_html/db-backup-$(date +%Y%m%d-%H%M%S).sql.gz"

if MYSQL_PWD="$(env_value DB_PASSWORD)" mysqldump \
        --host="$(env_value DB_HOST)" \
        --user="$(env_value DB_USERNAME)" \
        --single-transaction --quick --no-tablespaces \
        "$(env_value DB_DATABASE)" 2>/dev/null | gzip > "$DB_DUMP"; then
    echo "  $DB_DUMP  ($(du -h "$DB_DUMP" | cut -f1))"
else
    rm -f "$DB_DUMP"
    echo "  ! Could not dump the database. Promoting anyway — this release does"
    echo "    not migrate — but take one from the Cloudways panel before any"
    echo "    deploy that does."
fi

step "Copying the release into place"
# Contents are replaced rather than the directory being moved: public_html is
# provisioned by Cloudways with its own ownership, and recreating it can break
# the vhost.
#
# The excludes matter as much as the copy. --delete against a staged release
# that was uploaded without these would take the live copies with it: an image
# uploaded in the admin panel, everyone's session, and the .env itself.
rsync -a --delete \
    --exclude '.env' \
    --exclude 'storage/app/public/' \
    --exclude 'storage/logs/' \
    --exclude 'storage/framework/' \
    --exclude 'public/storage' \
    "$STAGE/" "$LIVE/"

step "Normalising permissions"
# nginx runs as www-data and has to be able to read the tree. Anything that
# arrived from a developer machine may carry private permissions with it.
find "$LIVE" -type d ! -path "*/storage/*" -exec chmod 755 {} +
find "$LIVE" -type f ! -path "*/storage/*" -exec chmod 644 {} +
chmod -R 775 "$LIVE/storage" "$LIVE/bootstrap/cache"
chmod 600 "$LIVE/.env"
chmod +x "$LIVE/artisan" "$LIVE/deploy/"*.sh

step "Rebuilding caches in the live directory"
cd "$LIVE"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
[ -L public/storage ] || php artisan storage:link
php artisan queue:restart

step "Clearing the Varnish cache"
# Cloudways puts Varnish in front of nginx. It holds the previous bytes until
# told otherwise, so without this a deploy goes out and visitors keep getting
# the old stylesheet — with the old one's Content-Type into the bargain.
curl -fsS -o /dev/null -X BAN -H "Host: frith.community" http://127.0.0.1/ \
    && echo "  banned" \
    || echo "  BAN was refused — clear the cache from the Cloudways panel"

step "Preflight, live"
php artisan frith:preflight

printf '\n\033[32mLive.\033[0m\n'
printf '  Files:    tar -xzf %s -C %s\n' "$BACKUP" "$HOME"
# Guarded: with set -e a bare failing test as the last statement would make an
# otherwise successful promote exit non-zero.
if [ -f "$DB_DUMP" ]; then
    printf '  Database: gunzip -c %s | mysql -u USER -p DATABASE\n' "$DB_DUMP"
fi
