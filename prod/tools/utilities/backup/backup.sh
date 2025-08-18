#!/bin/bash
# نظام النسخ الاحتياطي التلقائي

BACKUP_DIR="/backup/zeropay"
DATE=$(date +%Y%m%d_%H%M%S)

echo "🔄 بدء النسخ الاحتياطي..."

# نسخ قواعد البيانات
mysqldump --all-databases > "$BACKUP_DIR/db_$DATE.sql"

# نسخ الملفات
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" /workspace/prod/applications

# نسخ الإعدادات
tar -czf "$BACKUP_DIR/config_$DATE.tar.gz" /workspace/prod/infrastructure

# تنظيف النسخ القديمة (أكثر من 30 يوم)
find "$BACKUP_DIR" -name "*.tar.gz" -mtime +30 -delete
find "$BACKUP_DIR" -name "*.sql" -mtime +30 -delete

echo "✅ تم النسخ الاحتياطي بنجاح"
