#!/usr/bin/env bash
# Live acceptance test for NEW Compose lab; exits nonzero on failed critical checks.
set -euo pipefail
cd "$(dirname "$0")/.."
fail=0
check_http() {
  local label="$1" url="$2" code
  code="$(curl -L -sS --max-time 15 -o /dev/null -w '%{http_code}' "$url")" || code="000"
  printf '%-24s HTTP %s\n' "$label" "$code"
  case "$code" in 200|301|302) ;; *) fail=1;; esac
}
check_http "EcoShop Nginx" "http://127.0.0.1:18080/"
check_http "phpMyAdmin" "http://127.0.0.1:18081/"
check_http "Grafana" "http://127.0.0.1:13000/login"
check_http "Prometheus" "http://127.0.0.1:19090/-/ready"

echo 'Checking WordPress / DB queries against NEW Compose database only:'
if ! sudo docker compose exec -T mysql sh -c 'exec mysql -N -uroot -p"$MYSQL_ROOT_PASSWORD" -e "SELECT 1"' | grep -qx 1; then fail=1; fi

echo 'Checking Prometheus target states:'
if ! curl -fsS --max-time 15 http://127.0.0.1:19090/api/v1/targets |
 python3 -c 'import json,sys; d=json.load(sys.stdin); t=d["data"]["activeTargets"]; print("Targets:",[(x.get("labels",{}).get("job"),x["health"]) for x in t]); sys.exit(0 if len(t)>=5 and all(x["health"]=="up" for x in t) else 1)' ; then fail=1; fi

echo 'Required containers:'
sudo docker compose ps
echo 'Do NOT report LogQL/SMTP/hardening as verified without screenshots and email receipt.'
if (( fail )); then echo 'SMOKE_TEST_FAILED – inspect logs; DO NOT delete volumes.'; exit 1; fi
echo 'SMOKE_TEST_BASIC_OK – review LogQL + headers + screenshots manually.'
