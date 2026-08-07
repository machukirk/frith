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

step "Copying the release into place"
# Contents are replaced rather than the directory being moved: public_html is
# provisioned by Cloudways with its own ownership, and recreating it can break
# the vhost.
rsync -a --delete "$STAGE/" "$LIVE/"

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

step "Preflight, live"
php artisan frith:preflight

printf '\n\033[32mLive.\033[0m Roll back with: tar -xzf %s -C %s\n' "$BACKUP" "$HOME"
