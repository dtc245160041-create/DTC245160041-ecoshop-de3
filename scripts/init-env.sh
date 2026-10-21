#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
if [[ -e .env ]]; then echo 'STOP: .env da ton tai; khong ghi de.' >&2; exit 1; fi
umask 077
cp .env.example .env
for key in MYSQL_PASSWORD MYSQL_ROOT_PASSWORD MYSQL_EXPORTER_PASSWORD GRAFANA_ADMIN_PASSWORD; do
  value="$(openssl rand -hex 24)"
  sed -i "s/^${key}=GENERATE_BEFORE_DEPLOY$/${key}=${value}/" .env
done
chmod 600 .env
echo 'OK: .env duoc tao bang random hex. KHONG COMMIT .env.'
