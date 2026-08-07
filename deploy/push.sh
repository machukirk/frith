#!/usr/bin/env bash
#
# Push Frith to Cloudways. Run from your machine, in the project root:
#
#   ./deploy/push.sh                 # deploy to the live directory
#   ./deploy/push.sh --to-staging    # upload to ~/frith-release instead, changing nothing public
#
# CSS is built here, not on the server: Cloudways is on Node 18 and Vite needs
# 20 or newer. The built files ship with the code.
#
# .env, storage/ and the database are never touched.

set -euo pipefail
cd "$(dirname "$0")/.."

SSH_HOST="${FRITH_SSH_HOST:-mk_frith@167.99.200.56}"
SSH_KEY="${FRITH_SSH_KEY:-$HOME/.ssh/id_rsa}"
REMOTE_APP="public_html"

if [ "${1:-}" = "--to-staging" ]; then
    REMOTE_APP="frith-release"
fi

SSH=(ssh -i "$SSH_KEY" -o BatchMode=yes)

step() { printf '\n\033[1m▸ %s\033[0m\n' "$1"; }

step "Running the test suite"
# Nothing ships that doesn't pass. This is the last cheap place to catch it.
if command -v ddev >/dev/null && ddev describe >/dev/null 2>&1; then
    ddev artisan test
else
    php artisan test
fi

step "Building CSS"
npm run build

step "Uploading to ~/$REMOTE_APP"
# vendor/ is excluded and installed on the server, so the platform's own PHP
# decides what gets built. public/build IS shipped, because the server can't.
rsync -az --delete \
    -e "ssh -i $SSH_KEY -o BatchMode=yes" \
    --exclude '.git' \
    --exclude '.ddev' \
    --exclude 'node_modules' \
    --exclude 'vendor' \
    --exclude '.env' \
    --exclude 'storage/logs/*' \
    --exclude 'storage/framework/cache/*' \
    --exclude 'storage/framework/sessions/*' \
    --exclude 'storage/framework/views/*' \
    --exclude 'storage/app/public/*' \
    --exclude 'public/storage' \
    --exclude 'public/build/.vite' \
    --exclude 'database/database.sqlite' \
    ./ "$SSH_HOST:$REMOTE_APP/"

step "Running the remote deploy"
"${SSH[@]}" "$SSH_HOST" "cd ~/$REMOTE_APP && bash deploy/remote.sh"

printf '\n\033[32mPushed to ~/%s\033[0m\n' "$REMOTE_APP"
