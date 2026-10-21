#!/bin/bash
# MySQL official image executes this only on first initialization of an empty datadir.
set -Eeuo pipefail
: "${MYSQL_EXPORTER_PASSWORD:?missing MYSQL_EXPORTER_PASSWORD}"
mysql --protocol=socket -uroot -p"${MYSQL_ROOT_PASSWORD}" <<SQL
CREATE USER IF NOT EXISTS 'exporter'@'%' IDENTIFIED BY '${MYSQL_EXPORTER_PASSWORD}' WITH MAX_USER_CONNECTIONS 3;
GRANT PROCESS, REPLICATION CLIENT, SELECT ON *.* TO 'exporter'@'%';
SQL
