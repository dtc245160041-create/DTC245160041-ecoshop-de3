#!/usr/bin/env bash
# Safely restore a SQL dump and wp-content from a previous WordPress volume into NEW lab volumes only.
# Will not touch the source volume or old MariaDB server. Single-use per fresh lab.
set -euo pipefail
cd "$(dirname "$0")/.."
if [[ $# -ne 2 ]]; then
  echo 'Usage: scripts/restore-lab.sh /home/hoc/ecoshop-wordpress-recovered.sql SOURCE_WP_VOLUME' >&2
  exit 2
fi
sql="$1"; source_volume="$2"
[[ -s "$sql" && -f .env ]] || { echo 'Missing SQL backup or .env.' >&2; exit 1; }
[[ "$source_volume" =~ ^[a-zA-Z0-9][a-zA-Z0-9_.-]+$ ]] || { echo 'Invalid Docker volume name.' >&2; exit 1; }
case "$source_volume" in de3_*|grafana-storage|ecoshop-loki-data) echo 'Refusing protected or lab volume'; exit 1;; esac
sudo docker volume inspect "$source_volume" --format '{{.Name}}' >/dev/null
sudo docker compose config -q
sudo docker compose up -d mysql wordpress
# WordPress php/wp-config.php is intentionally NEVER copied from the old environment.
n="$(sudo docker compose exec -T mysql sh -c 'mysql -N -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()"' | tr -d '\r[:space:]')"
[[ "$n" == "0" ]] || { echo "STOP: lab database already contains $n tables. No import attempted." >&2; exit 1; }
echo 'Importing SQL into ONLY de3_mysql_data ...'
sudo docker compose exec -T mysql sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD"' < "$sql"
# Lab WordPress core exists in the brand-new volume, but preserve its generated wp-config.php.
sudo docker run --rm \
  -v "$source_volume:/source:ro" \
  -v de3_wp_data:/target \
  busybox:1.36 sh -eu -c '
    test -d /source/wp-content
    mkdir -p /target/wp-content
    if [ -d /target/wp-content/uploads ] && [ -n "$(ls -A /target/wp-content/uploads)" ]; then
      echo "STOP: lab uploads already populated" >&2; exit 1
    fi
    cp -a /source/wp-content/. /target/wp-content/
    chown -R 33:33 /target/wp-content
  '
# URL update applies ONLY to the newly imported MySQL lab, never source database.
sql_update="UPDATE wp_options SET option_value='http://192.168.254.128:18080' WHERE option_name IN ('home','siteurl');"
sudo docker compose exec -T mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "$1"' sh "$sql_update"
echo 'LAB_RESTORE_DONE: check website port 18080 and tables in localhost:18081.'
echo 'If imported SQL references MariaDB collations or fails, stop and inspect; originals untouched.'
