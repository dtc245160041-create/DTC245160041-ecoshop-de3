#!/usr/bin/env bash
# Apply ONLY revised EcoShop theme design to the already installed DE3 lab.
# Does not execute SQL, create posts, restart containers or touch old EcoShop.
set -euo pipefail
cd "$(dirname "$0")/.."

[[ -f site/ecoshop-studio/style.css && -f site/ecoshop-studio/front-page.php ]] || {
  echo 'MISSING_THEME_SOURCE: git pull --ff-only first.' >&2
  exit 1
}
[[ -f .env ]] || { echo 'MISSING_LAB_ENV' >&2; exit 1; }
sudo docker compose config -q

wpvolume="$(sudo docker compose config --format json | python3 -c 'import json,sys; print(json.load(sys.stdin)["volumes"]["wp_data"]["name"])')"
dbvolume="$(sudo docker compose config --format json | python3 -c 'import json,sys; print(json.load(sys.stdin)["volumes"]["mysql_data"]["name"])')"
if [[ "$wpvolume" != de3_wp_data || "$dbvolume" != de3_mysql_data ]]; then
  echo 'REFUSING_NON_LAB_VOLUMES: existing EcoShop must remain untouched.' >&2
  exit 1
fi
id="$(sudo docker compose ps -q wordpress)"
if [[ -z "$id" || "$(sudo docker inspect -f '{{.State.Running}}' "$id" 2>/dev/null || true)" != true ]]; then
  echo 'WORDPRESS_LAB_NOT_RUNNING' >&2
  exit 1
fi

echo 'Validating NEW theme PHP files with existing WordPress image before copying...'
for file in functions.php header.php footer.php front-page.php page.php single-eco_product.php index.php inc/product-admin.php; do
  sudo docker run --rm --network none --pull never \
    --entrypoint php \
    -v "$PWD/site/ecoshop-studio:/source:ro" \
    wordpress:php8.3-apache -l "/source/$file" >/dev/null
done
echo 'THEME_PHP_LINT_OK'

umask 077
mkdir -p private-backups
backup_name="ecoshop-theme-before-$(date +%Y%m%d-%H%M%S).tgz"
echo 'Backing up the existing NEW lab theme, then updating styles and templates...'
sudo docker run --rm --network none --pull never \
  -e BACKUP_NAME="$backup_name" \
  -v "$PWD/site/ecoshop-studio:/source:ro" \
  -v de3_wp_data:/target \
  -v "$PWD/private-backups:/backup" \
  busybox:1.36 sh -eu -c '
    test -f /target/wp-content/themes/ecoshop-studio/style.css
    test -f /source/style.css
    tar -czf "/backup/$BACKUP_NAME" -C /target/wp-content/themes ecoshop-studio
    test -s "/backup/$BACKUP_NAME"
    cp -a /source/. /target/wp-content/themes/ecoshop-studio/
    chown -R 33:33 /target/wp-content/themes/ecoshop-studio
    find /target/wp-content/themes/ecoshop-studio -type d -exec chmod 755 {} \;
    find /target/wp-content/themes/ecoshop-studio -type f -exec chmod 644 {} \;
    echo ECOSHOP_THEME_UPDATED
  '

echo "THEME_BACKUP_CREATED: private-backups/$backup_name"
# No database operations or container restarts are involved.
html="$(curl -fsS --max-time 20 http://127.0.0.1:18080/)"
if [[ "$html" == *"Đồ dùng tiện ích"* ]]; then
  echo 'DESIGN_UPDATE_OK'
else
  echo 'Theme files copied, but new headline not confirmed yet. Check WordPress cache and active theme.' >&2
  exit 1
fi
echo 'Open http://192.168.254.128:18080 and press Ctrl+F5 to refresh the fonts.'
