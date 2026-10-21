#!/usr/bin/env bash
# Restore ONLY into new lab volumes, never mutate originals.
# Usage: scripts/restore-lab.sh /home/hoc/ecoshop-wordpress-recovered.sql ecoshop-wp-restored
set -euo pipefail
cd "$(dirname "$0")/.."
if [[ $# -ne 2 ]]; then echo 'Usage: scripts/restore-lab.sh /absolute/path/wordpress.sql source_wordpress_volume' >&2; exit 2; fi
sql="$1"; source_volume="$2"
[[ -s "$sql" ]] || { echo "Invalid SQL backup: $sql" >&2; exit 1; }
[[ -f .env ]] || { echo 'Missing .env; run scripts/init-env.sh' >&2; exit 1; }
source_volume_found="$(sudo docker volume inspect "$source_volume" --format '{{.Name}}' 2>/dev/null || true)"
[[ "$source_volume_found" == "$source_volume" ]] || { echo "Missing source volume: $source_volume" >&2; exit 1; }
if [[ "$source_volume" == de3_wp_data || "$source_volume" == de3_mysql_data ]]; then echo 'Refusing to use lab destination as source'; exit 1; fi
sudo docker compose config -q
sudo docker compose up -d mysql
sudo docker compose exec -T mysql sh -c 'mysqladmin ping -h127.0.0.1 -uroot -p"$MYSQL_ROOT_PASSWORD" --silent'
count="$(sudo docker compose exec -T mysql sh -c 'mysql -N -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()"' | tr -d '\r')"
[[ "$count" == 0 ]] || { echo 'STOP: destination DB is not empty. No overwrite.' >&2; exit 1; }
echo 'Importing ONLY into de3_mysql_data...'
sudo docker compose exec -T mysql sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD"' < "$sql"
echo 'SQL import completed.'
sudo docker volume create de3_wp_data >/dev/null
sudo docker run --rm -v de3_wp_data:/target:ro busybox:1.36 sh -c 'test -z "$(ls -A /target)"' || { echo 'STOP: destination de3_wp_data is not empty'; exit 1; }
sudo docker run --rm -v "$source_volume:/source:ro" -v de3_wp_data:/target busybox:1.36 sh -eu -c 'test -d /source/wp-content; cp -a /source/. /target/; test -d /target/wp-content'
echo 'WordPress files copied from the source volume clone; originals untouched.'
echo 'Verify wp-config.php reads WORDPRESS_DB_* and update home/siteurl ONLY in lab before running WordPress.'
