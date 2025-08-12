#!/usr/bin/env bash
set -euo pipefail

log_dir="/workspace/me/_logs"
mkdir -p "$log_dir"
log_file="$log_dir/env_health_check.log"
exec > >(tee -a "$log_file") 2>&1 || true

echo "[env_health_check] $(date -u +%Y-%m-%dT%H:%M:%SZ)"
uname -a || true
free -h || true
df -h / || true
which php || true
php -v | head -n 2 || true
which composer || true
composer -V || true
ls -lah /workspace/scripts/php.d || true