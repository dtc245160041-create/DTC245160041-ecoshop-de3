#!/usr/bin/env bash
# Install professional EcoShop demo theme + pages on the NEW Compose lab only.
# No removal of any Docker volume, old EcoShop container, or existing posts.
# Back up NEW lab DB before making changes.
set -euo pipefail
cd "$(dirname "$0")/.."

[[ -d site/ecoshop-studio ]] || { echo 'THEME_FILES_MISSING – run git pull --ff-only'; exit 1; }
[[ -f site/ecoshop-studio/functions.php && -f site/ecoshop-studio/style.css ]] || { echo 'THEME_INCOMPLETE'; exit 1; }
[[ -s .env ]] || { echo 'PRIVATE_ENV_MISSING'; exit 1; }
sudo docker compose config -q

# Hard-stop if current Compose volume is not the ISOLATED lab volume.
wpvolume="$(sudo docker compose config --format json | python3 -c 'import json,sys; print(json.load(sys.stdin)["volumes"]["wp_data"]["name"])')"
dbvolume="$(sudo docker compose config --format json | python3 -c 'import json,sys; print(json.load(sys.stdin)["volumes"]["mysql_data"]["name"])')"
if [[ "$wpvolume" != 'de3_wp_data' || "$dbvolume" != 'de3_mysql_data' ]]; then
  echo "REFUSING_NON_LAB_VOLUMES: wp=$wpvolume mysql=$dbvolume" >&2
  exit 1
fi

for service in wordpress mysql nginx; do
  id="$(sudo docker compose ps -q "$service" 2>/dev/null || true)"
  if [[ -z "$id" ]] || [[ "$(sudo docker inspect -f '{{.State.Running}}' "$id" 2>/dev/null || true)" != true ]]; then
    echo "REQUIRED_LAB_SERVICE_NOT_RUNNING: $service" >&2
    exit 1
  fi
done

umask 077
mkdir -p private-backups
timestamp="$(date +%Y%m%d-%H%M%S)"
backup="private-backups/de3-before-theme-${timestamp}.sql"
echo 'Backing up NEW lab MySQL before changing WordPress theme/pages...'
if ! sudo docker compose exec -T mysql sh -c 'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction "$MYSQL_DATABASE"' > "$backup"; then
  rm -f "$backup"
  echo 'LAB_DB_BACKUP_FAILED; no site changes performed.' >&2
  exit 1
fi
if [[ ! -s "$backup" ]]; then
  echo 'EMPTY_DB_BACKUP; stopped before installing theme.' >&2
  exit 1
fi
chmod 600 "$backup"
echo "LAB_BACKUP_OK: $backup"

echo 'Copying theme files into ONLY de3_wp_data...'
sudo docker run --rm --network none --pull never \
  -v "$PWD/site/ecoshop-studio:/src:ro" \
  -v de3_wp_data:/target \
  busybox:1.36 sh -eu -c '
    test -f /src/style.css
    test -f /src/functions.php
    test -d /target/wp-content
    mkdir -p /target/wp-content/themes/ecoshop-studio
    cp -a /src/. /target/wp-content/themes/ecoshop-studio/
    chown -R 33:33 /target/wp-content/themes/ecoshop-studio
    find /target/wp-content/themes/ecoshop-studio -type d -exec chmod 755 {} \;
    find /target/wp-content/themes/ecoshop-studio -type f -exec chmod 644 {} \;
    echo THEME_FILES_INSTALLED
  '

echo 'Creating/reusing WordPress pages and sample products in NEW Compose lab...'
sudo docker compose exec -T wordpress php < scripts/apply-demo-site.php

echo 'Checking the new home page through Nginx port 18080...'
homepage_html="$(curl -fsS --max-time 20 http://127.0.0.1:18080/)"
if [[ "$homepage_html" == *"Những điều nhỏ"* ]]; then
  echo 'ECOSHOP_HOMEPAGE_VERIFIED'
else
  echo 'Theme script ran, but homepage text not verified. Check Nginx/WordPress logs.' >&2
  exit 1
fi
echo 'ECOSHOP_STUDIO_READY: http://192.168.254.128:18080'
echo 'Original EcoShop containers and volumes were not modified.'
