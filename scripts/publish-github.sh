#!/usr/bin/env bash
# For a new local clone or fork. GitHub repository already exists.
set -euo pipefail
cd "$(dirname "$0")/.."
repo="https://github.com/dtc245160041-create/DTC245160041-ecoshop-de3.git"
if git remote get-url origin >/dev/null 2>&1; then
  echo "Existing origin: $(git remote get-url origin)"
else
  git remote add origin "$repo"
fi
echo 'Repository exists. Push only after authenticating to GitHub: git push -u origin main'
