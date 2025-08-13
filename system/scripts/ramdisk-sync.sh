#!/bin/bash
# سكريبت مزامنة RAM Disk التلقائية

RAMDISK="/mnt/ramdisk"
BACKUP="/workspace/.ramdisk-backup"

# إنشاء مجلد النسخ الاحتياطي
mkdir -p "$BACKUP"

sync_data() {
    echo "🔄 مزامنة RAM Disk..."
    
    # مزامنة البيانات المهمة فقط (تجاهل الملفات المؤقتة)
    rsync -av --delete \
        --exclude="*.tmp" \
        --exclude="*.lock" \
        --exclude="*.pid" \
        --exclude="opcache/*" \
        --exclude="sessions/*" \
        --exclude="*.log" \
        "$RAMDISK/" "$BACKUP/" 2>/dev/null
    
    echo "✅ تمت المزامنة في: $(date)"
}

# تشغيل المزامنة مرة واحدة
sync_data

# إذا كان المعامل "background" موجود، شغل في الخلفية
if [ "$1" = "background" ]; then
    while true; do
        sleep 300  # كل 5 دقائق
        sync_data
    done &
    echo "🔄 بدأت المزامنة التلقائية في الخلفية (PID: $!)"
fi