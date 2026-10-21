#!/usr/bin/env bash
# Repair access to the PUBLIC MySQL initialization scripts only.
# Never delete or modify any Docker volume, .env, DB data, or old EcoShop service.
set -euo pipefail
cd "$(dirname "$0")/.."
[[ -d db/init && -f db/init/10-exporter.sh ]] || { echo 'Missing db/init/10-exporter.sh' >&2; exit 1; }
echo 'Before:'
stat -c '%a %U:%G %n' db db/init db/init/10-exporter.sh

# MySQL official entrypoint switches from root to mysql UID before listing
# /docker-entrypoint-initdb.d, which is a read-only bind of ./db/init.
# Repo checkout under restrictive umask may create directory mode 0700.
sudo chmod 755 db db/init
sudo chmod 755 db/init/10-exporter.sh

echo 'After (must allow directory search and read by mysql UID):'
stat -c '%a %U:%G %n' db db/init db/init/10-exporter.sh
echo 'Restarting ONLY new lab MySQL container (NO volume removal):'
sudo docker compose restart mysql
echo 'MySQL state:'
sudo docker compose ps -a mysql
echo 'Last 35 MySQL log lines:'
sudo docker compose logs --tail=35 mysql
echo 'If logs show permission denied, inspect host directory, mount and security policy; do not delete volumes.'
