#!/usr/bin/env bash
set -euo pipefail

echo "[status] $(date -u +%Y-%m-%dT%H:%M:%SZ)"
branch=$(git branch --show-current 2>/dev/null || echo '-')
echo "git branch: $branch"

if [ -f /workspace/me/.knowledge_sync.pid ] && ps -p "$(cat /workspace/me/.knowledge_sync.pid 2>/dev/null)" >/dev/null 2>&1; then
  echo "knowledge_sync: RUNNING (pid=$(cat /workspace/me/.knowledge_sync.pid))"
else
  echo "knowledge_sync: STOPPED"
fi

for f in /workspace/me/INDEX.md /workspace/me/SUMMARY.md /workspace/me/knowledge.json; do
  if [ -f "$f" ]; then echo "exists: $f"; fi
done