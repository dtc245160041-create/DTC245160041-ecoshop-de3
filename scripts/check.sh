#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
sudo docker compose ps
printf '\nNGINX response: '
curl -sS -o /dev/null -w '%{http_code}\n' "http://127.0.0.1:${LAB_HTTP_PORT:-18080}/" || true
printf '\nPrometheus targets JSON: '
curl -fsS "http://127.0.0.1:${LAB_PROMETHEUS_PORT:-19090}/api/v1/targets" | head -c 300 || true
printf '\nGrafana health JSON: '
curl -fsS "http://127.0.0.1:${LAB_GRAFANA_PORT:-13000}/api/health" || true
printf '\nDisk: '
df -h / | tail -1
