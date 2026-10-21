#!/usr/bin/env bash
# Correct read access for PUBLIC config bind mounts on a restrictive-umask host.
# No changes to credentials, SQL databases, volume contents or original EcoShop.
set -euo pipefail
cd "$(dirname "$0")/.."

# Directory traversal is required by non-root containers (Grafana, Loki, Promtail, Prometheus).
dirs=(
  nginx prometheus loki promtail
  grafana grafana/provisioning
  grafana/provisioning/datasources grafana/provisioning/dashboards
  grafana/dashboards
)
files=(
  nginx/default.conf
  prometheus/prometheus.yml
  loki/config.yml
  promtail/config.yml
  grafana/provisioning/datasources/datasources.yml
  grafana/provisioning/dashboards/dashboards.yml
  grafana/dashboards/ecoshop-de3.json
)
for dir in "${dirs[@]}"; do
  if [[ ! -d "$dir" || -L "$dir" ]]; then
    echo "STOP: expected regular configuration directory: $dir" >&2
    exit 1
  fi
  chmod 755 -- "$dir"
done
for f in "${files[@]}"; do
  if [[ ! -f "$f" || -L "$f" ]]; then
    echo "STOP: expected regular public config file: $f" >&2
    exit 1
  fi
  chmod 644 -- "$f"
done

# NEVER publish the local credentials to GitHub or broaden their file mode.
if [[ -f .env ]]; then chmod 600 -- .env; fi

echo 'Public read-only configs are now readable by non-root containers.'
stat -c '%a %n' prometheus/prometheus.yml loki/config.yml promtail/config.yml
echo 'The .env file remains private (0600) and all data volumes are unchanged.'

if [[ "${1:-}" == "--permissions-only" ]]; then
  echo 'CONFIG_PERMISSIONS_PREPARED'
  exit 0
fi

echo 'Restarting ONLY the NEW Compose lab services using these public configs...'
for svc in prometheus loki grafana promtail nginx; do
  id="$(sudo docker compose ps -a -q "$svc" 2>/dev/null || true)"
  if [[ -n "$id" ]]; then
    sudo docker compose restart "$svc"
  else
    echo "SKIP: $svc is not created yet."
  fi
done
echo 'New lab status:'
sudo docker compose ps -a
echo 'Prometheus recent logs:'
sudo docker compose logs --tail=15 prometheus
echo 'CONFIG_PERMISSIONS_REPAIR_DONE'
