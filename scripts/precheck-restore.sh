#!/usr/bin/env bash
# READ-ONLY precheck before restoring the previous EcoShop to fresh DE3 lab data.
# Does not stop/restart containers or change any Docker volume.
set -euo pipefail
cd "$(dirname "$0")/.."
sql="${1:-/home/hoc/ecoshop-wordpress-recovered.sql}"
src="${2:-wordpress_up_data}"

[[ -s "$sql" ]] || { echo "MISSING_SQL_BACKUP: $sql" >&2; exit 1; }
[[ "$src" =~ ^[A-Za-z0-9][A-Za-z0-9_.-]*$ ]] || { echo 'INVALID_SOURCE_VOLUME' >&2; exit 1; }
case "$src" in de3_* ) echo "REFUSE_LAB_VOLUME_AS_SOURCE: $src" >&2; exit 1;; esac
echo "SQL_BACKUP_FOUND: $sql ($(wc -c < "$sql") bytes)"
if ! grep -Eiq 'CREATE TABLE|INSERT INTO' "$sql"; then
  echo 'SQL_BACKUP_UNEXPECTED_FORMAT: inspect the file before import' >&2
  exit 1
fi

sudo docker volume inspect "$src" --format 'SOURCE_VOLUME_FOUND: {{.Name}}' >/dev/null
echo "SOURCE_VOLUME_FOUND: $src"
# Existing BusyBox image, no network or write mount.
sudo docker run --rm --network none --pull never -v "$src:/source:ro" busybox:1.36 sh -ec '
  test -d /source/wp-content
  ls -ld /source/wp-content
  echo SOURCE_WP_CONTENT_OK
'

[[ -f .env ]] || { echo 'MISSING_ENV' >&2; exit 1; }
sudo docker compose config -q
# Verify schema still empty: WordPress installer screenshot indicates it has not been installed.
table_count="$(sudo docker compose exec -T mysql sh -c 'mysql -N -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()"' | tr -d '[:space:]')"
[[ "$table_count" =~ ^[0-9]+$ ]] || { echo "INVALID_TABLE_COUNT: $table_count" >&2; exit 1; }
echo "LAB_TABLE_COUNT: $table_count"
if [[ "$table_count" != "0" ]]; then
  echo 'STOP: Lab database is not empty. Do not restore automatically.' >&2
  exit 1
fi

echo 'RESTORE_PREFLIGHT_OK – old database/volumes untouched; lab database empty.'
