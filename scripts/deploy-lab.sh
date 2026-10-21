#!/usr/bin/env bash
# Additional isolated Compose lab; never stop, prune or overwrite the old EcoShop.
# Ubuntu 18.04/headless may have a desktop credential helper configured in ~/.docker/config.json.
# Compose 2.18 may still invoke it even when DOCKER_CONFIG is temporarily overridden.
# Workaround: explicitly pull each public image with docker --config CLEAN_DIR,
# then start Compose with --pull never.
set -euo pipefail
cd "$(dirname "$0")/.."
bash scripts/preflight.sh

clean_config="$(mktemp -d)"
chmod 700 "$clean_config"
printf '{"auths":{}}\n' > "$clean_config/config.json"
chmod 600 "$clean_config/config.json"
cleanup() {
  rm -f -- "$clean_config/config.json"
  rmdir -- "$clean_config" 2>/dev/null || true
}
trap cleanup EXIT

# Obtain the exact pinned image list from validated Compose config.
if ! image_names="$(docker compose config --images)"; then
  echo 'Cannot list Compose images; no services started.' >&2
  exit 1
fi
mapfile -t images < <(printf '%s\n' "$image_names" | sed '/^[[:space:]]*$/d' | sort -u)
if (( ${#images[@]} == 0 )); then
  echo 'No images found in Compose configuration; abort.' >&2
  exit 1
fi
echo "Pulling ${#images[@]} public images using explicit clean Docker CLI config..."
for img in "${images[@]}"; do
  echo "-- Pulling $img"
  # Docker global --config flag selects a config with NO credsStore/credHelpers.
  # sudo root can read the temporary 0700 folder; Docker login state is unchanged.
  if ! sudo docker --config "$clean_config" pull "$img"; then
    echo "PULL_FAILED on image: $img" >&2
    echo 'If D-Bus / credentials still appear, report this single docker pull error.' >&2
    echo 'No Compose services started; old EcoShop volumes/containers untouched.' >&2
    exit 1
  fi
done

echo 'All images present locally. Bootstrapping isolated MySQL and remaining services...'
if ! bash scripts/repair-mysql-init.sh; then
  echo 'START_FAILED: inspect NEW Compose logs/ps. Never run down -v or volume prune.' >&2
  exit 1
fi

echo 'DEPLOY_FINISHED: now run bash scripts/smoke-test.sh'
echo 'Original EcoShop services and volumes were not intentionally stopped or removed.'
