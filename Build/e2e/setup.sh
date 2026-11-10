#!/usr/bin/env bash
#
# Builds the throwaway TYPO3 instance the browser tests drive and prints the
# environment they need. Idempotent — running it again re-seeds the same tree.
#
#   Build/e2e/setup.sh [port]
#
set -euo pipefail

port="${1:-8099}"
here="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$here"

echo "==> composer install"
composer install --no-progress --no-interaction

echo "==> typo3 setup (sqlite)"
TYPO3_DB_DRIVER=sqlite \
TYPO3_SETUP_ADMIN_USERNAME="${TYPO3_ADMIN_USERNAME:-admin}" \
TYPO3_SETUP_ADMIN_PASSWORD="${TYPO3_ADMIN_PASSWORD:-Playwright!2026x}" \
TYPO3_SETUP_ADMIN_EMAIL="admin@example.com" \
TYPO3_PROJECT_NAME="Inquiry E2E" \
    vendor/bin/typo3 setup \
        --no-interaction \
        --create-site="http://127.0.0.1:${port}/" \
        --server-type=other \
        --force

# The port may differ from the one the site was created with on a re-run.
sed -i "s|^base: .*|base: 'http://127.0.0.1:${port}/'|" config/sites/*/config.yaml

echo "==> seeding the page tree"
# The first pass fixes the sqlite path when the instance was set up elsewhere,
# which only takes effect in the next process.
php seed.php >/dev/null 2>&1 || true
php seed.php

vendor/bin/typo3 cache:flush

cat <<EOF

Instance ready. Serve it with:

    php -S 127.0.0.1:${port} -t ${here}/public ${here}/router.php

and run the tests with:

    INQUIRY_BASE_URL=http://127.0.0.1:${port} \\
    INQUIRY_PRODUCT_PATH=/product \\
    INQUIRY_LIST_PATH=/inquiry-list \\
    npm test --prefix ../../Tests/E2E
EOF
