#!/usr/bin/env bash
# Export SQL and WordPress volume locally; never include backups in GitHub.
set -euo pipefail
cd "$(dirname "$0")/.."
umask 077
mkdir -p ./private-backups
stamp="$(date +%Y%m%d-%H%M%S)"
sudo docker compose exec -T mysql sh -c 'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --databases "$MYSQL_DATABASE"' > "./private-backups/mysql-${stamp}.sql"
sudo docker run --rm -v de3_wp_data:/data:ro -v "$PWD/private-backups:/backup" busybox:1.36 tar -czf "/backup/wordpress-${stamp}.tgz" -C /data .
chmod 600 "./private-backups/mysql-${stamp}.sql"
echo 'Backup created in private-backups/. Copy OFF the VM; never push to GitHub.'
