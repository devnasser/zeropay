#!/usr/bin/env bash
set -euo pipefail

log_dir="/workspace/me/_logs"
mkdir -p "$log_dir"
log_file="$log_dir/fast_env_boost.log"
exec > >(tee -a "$log_file") 2>&1 || true

echo "[fast_env_boost] start: $(date -u +%Y-%m-%dT%H:%M:%SZ)"

HAS_SUDO=0
if command -v sudo >/dev/null 2>&1; then HAS_SUDO=1; fi

pkg_install() {
  if command -v apt-get >/dev/null 2>&1; then
    if [[ $HAS_SUDO -eq 1 ]]; then sudo apt-get update -y || true; else apt-get update -y || true; fi
    if [[ $HAS_SUDO -eq 1 ]]; then sudo apt-get install -y "$@" || true; else apt-get install -y "$@" || true; fi
  else
    echo "[fast_env_boost] apt-get not available; skipping package install"
  fi
}

# Ensure PHP & Composer
if ! command -v php >/dev/null 2>&1; then
  echo "[fast_env_boost] installing php-cli and extensions"
  pkg_install php-cli php-zip php-opcache || true
else
  php -v | head -n 1 || true
fi

if ! command -v composer >/dev/null 2>&1; then
  echo "[fast_env_boost] installing composer"
  pkg_install composer || true
else
  composer -V || true
fi

# Local PHP config for CLI OPcache & JIT (workspace-local)
php_ini_dir="/workspace/scripts/php.d"
mkdir -p "$php_ini_dir"
php_ini="$php_ini_dir/opcache-cli.ini"
cat > "$php_ini" <<'INI'
[opcache]
opcache.enable=1
opcache.enable_cli=1
opcache.jit=tracing
opcache.jit_buffer_size=128M
opcache.memory_consumption=256
opcache.max_accelerated_files=100000
opcache.validate_timestamps=0
INI

echo "[fast_env_boost] generated local php ini at $php_ini (use with: php -c $php_ini your.php)"

# Workspace cache dirs
mkdir -p /workspace/.cache/composer /workspace/.cache/php || true

# Optional RAM-disk (skip if not permitted)
if mount | grep -q "/mnt/cache"; then
  echo "[fast_env_boost] /mnt/cache already mounted"
else
  echo "[fast_env_boost] attempting to create tmpfs /mnt/cache (may require privileges)"
  mkdir -p /mnt/cache || true
  if [[ $HAS_SUDO -eq 1 ]]; then sudo mount -t tmpfs -o size=512M tmpfs /mnt/cache || true; fi
fi

echo "[fast_env_boost] done: $(date -u +%Y-%m-%dT%H:%M:%SZ)"