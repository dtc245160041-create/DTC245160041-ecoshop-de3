#!/usr/bin/env bash
# Read-only check: verify the already-installed EcoShop theme using
# WordPress's canonical Host while contacting localhost Nginx.
# Does not change WordPress, SQL, containers, passwords or Docker volumes.
set -euo pipefail
cd "$(dirname "$0")/.."
host='192.168.254.128:18080'
url='http://127.0.0.1:18080'
html="$(mktemp)"
headers="$(mktemp)"
trap 'rm -f "$html" "$headers"' EXIT

echo 'Checking the LIVE EcoShop storefront (no reinstallation)...'
failed=0
for route in / /san-pham/ /gioi-thieu/ /lien-he/; do
  if status="$(curl -sS --max-time 15 -H "Host: $host" -o "$html" -D "$headers" -w '%{http_code}' "$url$route")"; then
    echo "PAGE: $route HTTP $status"
  else
    echo "FAIL: Cannot reach $route through Nginx" >&2
    failed=1
    continue
  fi
  if [[ "$status" != 200 ]]; then
    echo "FAIL: $route returned HTTP $status" >&2
    grep -i '^location:' "$headers" || true
    failed=1
  fi
  if [[ "$route" == "/" ]]; then
    if grep -Fq 'Đồ dùng tiện ích' "$html" && grep -Fq 'ecoshop-studio' "$html"; then
      echo 'ECOSHOP_HOMEPAGE_VERIFIED'
    else
      echo 'FAIL: WordPress homepage does not contain new EcoShop Studio design.' >&2
      echo 'Check the actual browser screenshot or ask to inspect WordPress template.' >&2
      failed=1
    fi
  fi
done
if (( failed != 0 )); then
  echo 'FRONTEND_CHECK_FAILED – do not restore SQL or delete containers.' >&2
  exit 1
fi
echo 'ECOSHOP_FRONTEND_OK: http://192.168.254.128:18080/'
