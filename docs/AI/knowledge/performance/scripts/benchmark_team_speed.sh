#!/usr/bin/env bash
set -euo pipefail

log_dir="/workspace/me/_logs"
mkdir -p "$log_dir"
log_file="$log_dir/benchmark_team_speed.log"
exec > >(tee -a "$log_file") 2>&1 || true

echo "[benchmark_team_speed] start: $(date -u +%Y-%m-%dT%H:%M:%SZ)"

start=$(date +%s)
# Placeholder: run something representative if exists
if [ -f artisan ]; then
  php artisan route:list >/dev/null 2>&1 || true
fi
end=$(date +%s)

echo "[benchmark_team_speed] elapsed=$((end-start))s"