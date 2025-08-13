#!/bin/bash
# سكريبت تحسين النظام العام

echo "⚡ بدء تحسين النظام..."

# تحسين إعدادات النواة
echo "🔧 تحسين إعدادات النواة..."
sudo sysctl -w vm.swappiness=10 2>/dev/null
sudo sysctl -w vm.vfs_cache_pressure=50 2>/dev/null
sudo sysctl -w vm.dirty_ratio=15 2>/dev/null
sudo sysctl -w vm.dirty_background_ratio=5 2>/dev/null

# تحسين حدود الملفات
echo "📁 تحسين حدود الملفات..."
ulimit -n 65536

# تحسين القراءة المسبقة
echo "💾 تحسين القراءة المسبقة..."
for disk in /sys/block/*/queue/read_ahead_kb; do
    echo 4096 | sudo tee "$disk" > /dev/null 2>&1
done

# تنظيف الذاكرة
echo "🧹 تنظيف الذاكرة..."
sync
echo 1 | sudo tee /proc/sys/vm/drop_caches > /dev/null

# تحسين Python
echo "🐍 تحسين Python..."
export PYTHONOPTIMIZE=2
export PYTHONDONTWRITEBYTECODE=1
export PYTHONUNBUFFERED=1
export PYTHONPYCACHEPREFIX=/mnt/ramdisk/python/cache

# حفظ التحسينات في .bashrc
echo "💾 حفظ التحسينات..."
{
    echo "# تحسينات النظام"
    echo "export PYTHONOPTIMIZE=2"
    echo "export PYTHONDONTWRITEBYTECODE=1"
    echo "export PYTHONUNBUFFERED=1"
    echo "export PYTHONPYCACHEPREFIX=/mnt/ramdisk/python/cache"
    echo "ulimit -n 65536"
} >> ~/.bashrc

# عرض الحالة
echo -e "\n✅ اكتمل تحسين النظام!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 الحالة الحالية:"
echo "  • Swappiness: $(cat /proc/sys/vm/swappiness)"
echo "  • Open files limit: $(ulimit -n)"
echo "  • Python optimization: $PYTHONOPTIMIZE"
echo "  • RAM Disk: $(df -h /mnt/ramdisk 2>/dev/null | tail -1 | awk '{print $5 " used"}')"