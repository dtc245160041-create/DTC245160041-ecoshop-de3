#!/usr/bin/env bash
# Install ONLY the Docker Compose v2 CLI plugin on Ubuntu; preserve existing Docker Engine and volumes.
set -euo pipefail
if docker compose version >/dev/null 2>&1; then docker compose version; exit 0; fi
[[ -f /etc/os-release ]] || { echo 'OS metadata not found; install Compose v2 manually.' >&2; exit 1; }
. /etc/os-release
[[ "${ID:-}" == ubuntu ]] || { echo "Expected Ubuntu, got ${ID:-unknown}"; exit 1; }
echo 'Installing docker-compose-plugin; does NOT run docker prune or remove volumes.'
sudo apt-get update
if ! sudo apt-get install -y docker-compose-plugin; then
  echo 'Plugin absent from configured apt repositories. Adding official Docker apt repository.'
  sudo apt-get install -y ca-certificates curl
  sudo install -m 0755 -d /etc/apt/keyrings
  sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
  sudo chmod a+r /etc/apt/keyrings/docker.asc
  arch="$(dpkg --print-architecture)"
  codename="${UBUNTU_CODENAME:-${VERSION_CODENAME}}"
  echo "deb [arch=${arch} signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu ${codename} stable" | sudo tee /etc/apt/sources.list.d/docker.list >/dev/null
  sudo apt-get update
  sudo apt-get install -y docker-compose-plugin
fi
docker compose version || { echo 'Compose installation failed; no lab started.' >&2; exit 1; }
