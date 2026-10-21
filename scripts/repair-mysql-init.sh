#!/usr/bin/env bash
# Repair ONLY the additional EcoShop De3 Compose MySQL container.
# The old EcoShop MariaDB and all Docker volumes are preserved.
set -euo pipefail
cd "$(dirname "$0")/.."

echo 'Validating the updated Compose configuration...'
sudo docker compose config -q
if sudo docker compose config | grep -q '/docker-entrypoint-initdb.d:'; then
  echo 'ERROR: stale MySQL init bind mount still found. Run git pull --ff-only first.' >&2
  exit 1
fi

echo 'Recreating ONLY the new Compose MySQL container using existing image and volume...'
sudo docker compose up -d --no-deps --force-recreate --pull never mysql

mysql_id="$(sudo docker compose ps -a -q mysql)"
if [[ -z "$mysql_id" ]]; then
  echo 'MySQL container not found after recreate; inspect Compose logs.' >&2
  exit 1
fi

echo 'Waiting up to 150 seconds for MySQL to be healthy...'
health=''
for i in $(seq 1 50); do
  health="$(sudo docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$mysql_id" 2>/dev/null || true)"
  if [[ "$health" == "healthy" ]]; then
    echo 'MYSQL_HEALTHY'
    break
  fi
  if [[ "$health" == "unhealthy" || "$health" == "exited" || "$health" == "dead" ]]; then
    echo "MySQL state is $health. Logs:" >&2
    sudo docker compose logs --tail=50 mysql
    exit 1
  fi
  sleep 3
done
if [[ "$health" != "healthy" ]]; then
  echo "MYSQL_NOT_READY (last status: $health); recent logs:" >&2
  sudo docker compose logs --tail=50 mysql
  exit 1
fi

bash scripts/setup-mysql-exporter.sh

echo 'Starting the remaining NEW Compose services from existing local images...'
sudo docker compose up -d --pull never

echo 'Current NEW lab services:'
sudo docker compose ps -a
echo 'REPAIR_FINISHED – run bash scripts/smoke-test.sh'
echo 'No Docker volume was removed. Old EcoShop services were not intentionally touched.'
