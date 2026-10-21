#!/usr/bin/env bash
# Deploy an ADDITIONAL isolated Compose lab without modifying old EcoShop volumes.
# Public container images do not require login. Bypass a desktop-only secretservice
# credential helper which fails on headless Ubuntu with no DBus / X11 DISPLAY.
set -euo pipefail
cd "$(dirname "$0")/.."

bash scripts/preflight.sh

# Never edit ~/.docker/config.json or /root/.docker/config.json.
# Use a temporary isolated Docker CLI config with NO credentials for public pulls.
public_docker_config="$(mktemp -d)"
chmod 700 "$public_docker_config"
printf '{"auths":{}}\n' > "$public_docker_config/config.json"
chmod 600 "$public_docker_config/config.json"
cleanup() {
  rm -f -- "$public_docker_config/config.json"
  rmdir -- "$public_docker_config" 2>/dev/null || true
}
trap cleanup EXIT

echo 'Pulling public images without GUI Docker credential helpers...'
if ! sudo env DOCKER_CONFIG="$public_docker_config" docker compose pull; then
  echo 'PULL_FAILED: containers were not started. Check registry/network/disk; old volumes were not touched.' >&2
  exit 1
fi

echo 'Starting the NEW isolated Compose lab...'
if ! sudo env DOCKER_CONFIG="$public_docker_config" docker compose up -d; then
  echo 'START_FAILED: some lab containers may exist; inspect docker compose ps/logs. DO NOT delete volumes.' >&2
  exit 1
fi

echo 'New lab container status:'
sudo docker compose ps
echo 'DEPLOY_FINISHED: next run bash scripts/smoke-test.sh'
echo 'Original EcoShop Docker-run containers and volumes were not removed or stopped.'
