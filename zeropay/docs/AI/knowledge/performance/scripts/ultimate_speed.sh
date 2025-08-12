#!/usr/bin/env bash
set -euo pipefail

log_dir="/workspace/me/_logs"
mkdir -p "$log_dir"
log_file="$log_dir/ultimate_speed.log"
exec > >(tee -a "$log_file") 2>&1 || true

echo "[ultimate_speed] start: $(date -u +%Y-%m-%dT%H:%M:%SZ)"

# Git hooks (simple placeholder)
mkdir -p /workspace/.githooks
cat > /workspace/.githooks/pre-commit <<'SH'
#!/usr/bin/env bash
# placeholder: run quick checks here
exit 0
SH
chmod +x /workspace/.githooks/pre-commit

git config core.hooksPath /workspace/.githooks || true

echo "[ultimate_speed] enabled hooksPath at .githooks"

echo "[ultimate_speed] pest parallel flag would be: vendor/bin/pest --parallel" || true

echo "[ultimate_speed] done: $(date -u +%Y-%m-%dT%H:%M:%SZ)"