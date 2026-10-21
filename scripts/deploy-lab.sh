#!/usr/bin/env bash
# Run only on the Ubuntu VM after deciding to start an additional lab stack.
set -euo pipefail
cd "$(dirname "$0")/.."
bash scripts/preflight.sh
echo 'Pulling pinned service images; first download can take time and disk space.'
sudo docker compose pull
sudo docker compose up -d
echo 'Container status:'
sudo docker compose ps
echo 'Now run: bash scripts/smoke-test.sh'
echo 'Original EcoShop Docker-run containers and volumes have not been stopped.'
