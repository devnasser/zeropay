#!/usr/bin/env bash
set -euo pipefail

log_dir="/workspace/me/_logs"
mkdir -p "$log_dir"
log_file="$log_dir/extra_speed_boost.log"
exec > >(tee -a "$log_file") 2>&1 || true

echo "[extra_speed_boost] start: $(date -u +%Y-%m-%dT%H:%M:%SZ)"

if [ -f composer.json ]; then
  echo "[extra_speed_boost] composer optimize-autoloader"
  composer install --no-interaction --prefer-dist --no-progress || true
  composer dump-autoload -o || true
fi

echo "[extra_speed_boost] done: $(date -u +%Y-%m-%dT%H:%M:%SZ)"