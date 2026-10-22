#!/usr/bin/env bash
# Install demo cart + COD-checkout UI on NEW EcoShop Compose lab.
# Real money transfer NOT supported. No data/volume deletion or container restart.
set -euo pipefail
cd "$(dirname "$0")/.."

[[ -s .env && -f scripts/enable-demo-commerce.php && -f site/ecoshop-studio/inc/cart-checkout.php ]] || {
  echo 'REQUIRED_FILES_MISSING: run git pull --ff-only; ensure existing lab .env' >&2
  exit 1
}
sudo docker compose config -q
wpvolume="$(sudo docker compose config --format json | python3 -c 'import json,sys; print(json.load(sys.stdin)["volumes"]["wp_data"]["name"])')"
dbvolume="$(sudo docker compose config --format json | python3 -c 'import json,sys; print(json.load(sys.stdin)["volumes"]["mysql_data"]["name"])')"
if [[ "$wpvolume" != de3_wp_data || "$dbvolume" != de3_mysql_data ]]; then
  echo 'REFUSING_NON_LAB_VOLUMES' >&2
  exit 1
fi
for svc in wordpress mysql nginx; do
  id="$(sudo docker compose ps -q "$svc")"
  [[ -n "$id" && "$(sudo docker inspect -f '{{.State.Running}}' "$id")" == true ]] || {
    echo "REQUIRED_SERVICE_NOT_RUNNING: $svc" >&2
    exit 1
  }
done

umask 077
mkdir -p private-backups
backup="private-backups/de3-before-cart-$(date +%Y%m%d-%H%M%S).sql"
echo 'Backing up ONLY NEW MySQL lab before adding checkout pages...'
if ! sudo docker compose exec -T mysql sh -c 'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction "$MYSQL_DATABASE"' > "$backup"; then
  rm -f "$backup"
  echo 'MYSQL_LAB_BACKUP_FAILED: no modifications made.' >&2
  exit 1
fi
if [[ ! -s "$backup" ]]; then
  echo 'EMPTY_SQL_BACKUP: refusing installation.' >&2
  exit 1
fi
chmod 600 "$backup"
echo "MYSQL_LAB_BACKUP_OK: $backup"

# Existing design-update script lints all theme PHP and backs up the old theme.
bash scripts/update-ecoshop-design.sh
echo 'Creating/reusing only 3 cart/checkout pages in NEW WordPress lab...'
sudo docker compose exec -T wordpress php < scripts/enable-demo-commerce.php

echo 'Checking shopping pages through Nginx with WordPress canonical Host...'
for page in gio-hang thanh-toan dat-hang-thanh-cong; do
  status="$(curl -sS --max-time 18 -H 'Host: 192.168.254.128:18080' \
    -o /dev/null -w '%{http_code}' "http://127.0.0.1:18080/$page/" || true)"
  echo "ECOSHOP_SHOP_PAGE: /$page/ HTTP $status"
  if [[ "$status" != 200 ]]; then
    echo "CHECKOUT_ROUTE_FAILED: /$page/ - inspect permalink settings; no data deleted." >&2
    exit 1
  fi
done
echo 'ECOSHOP_CART_CHECKOUT_READY'
echo 'Open http://192.168.254.128:18080/san-pham/ to test add to cart.'
echo 'Demo checkout never captures actual payment, card or bank credentials.'
