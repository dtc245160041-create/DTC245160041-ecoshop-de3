#!/usr/bin/env bash
# Read-only checks before launching a NEW isolated lab; does not stop old containers.
set -euo pipefail
cd "$(dirname "$0")/.."
command -v docker >/dev/null || { echo 'Docker is not installed.' >&2; exit 1; }
docker compose version >/dev/null || { echo 'Docker Compose v2 plugin is required. docker-compose 1.x is not supported.' >&2; exit 1; }
[[ -s .env ]] || { echo 'Missing .env; run bash scripts/init-env.sh first.' >&2; exit 1; }
grep -q '^MYSQL_PASSWORD=' .env || { echo 'Incomplete .env' >&2; exit 1; }
if grep -q '=GENERATE_BEFORE_DEPLOY' .env; then echo 'Refusing placeholder passwords' >&2; exit 1; fi
free_kb="$(df -Pk . | awk 'NR==2 {print $4}')"
if (( free_kb < 4194304 )); then echo "Need at least 4GiB free disk; available: $((free_kb/1024))MiB." >&2; exit 1; fi
for port in 18080 18081 13000 19090; do
  if command -v ss >/dev/null && ss -lnt "( sport = :$port )" | grep -q LISTEN; then
    echo "Host port $port already in use; abort before making changes." >&2
    exit 1
  fi
done
docker compose config -q
python3 scripts/verify-config.py
echo 'PREFLIGHT_OK – no existing volumes or containers touched'
