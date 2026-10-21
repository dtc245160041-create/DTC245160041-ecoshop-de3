#!/usr/bin/env bash
# Create/update ONLY the exporter monitoring user in NEW de3_mysql_data.
# Never touch MySQL volumes or the previous EcoShop database.
set -euo pipefail
cd "$(dirname "$0")/.."
echo 'Configuring MySQL exporter user inside NEW Compose MySQL...'
sudo docker compose exec -T mysql sh -s <<'MYSQL_SCRIPT'
set -eu
: "${MYSQL_ROOT_PASSWORD:?Root password missing in the container}"
: "${MYSQL_EXPORTER_PASSWORD:?Exporter password missing in the container}"
case "$MYSQL_EXPORTER_PASSWORD" in
  *[!0-9a-fA-F]*)
    echo 'Expected randomly generated hex exporter password; refusing unsafe SQL input' >&2
    exit 1
    ;;
esac
mysql --protocol=socket --user=root --password="$MYSQL_ROOT_PASSWORD" <<SQL
CREATE USER IF NOT EXISTS 'exporter'@'%' IDENTIFIED BY '$MYSQL_EXPORTER_PASSWORD' WITH MAX_USER_CONNECTIONS 3;
ALTER USER 'exporter'@'%' IDENTIFIED BY '$MYSQL_EXPORTER_PASSWORD';
GRANT PROCESS, REPLICATION CLIENT, SELECT ON *.* TO 'exporter'@'%';
SQL
MYSQL_SCRIPT
echo 'MYSQL_EXPORTER_USER_READY'
