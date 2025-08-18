#!/bin/bash
# سكريبت النشر التقليدي - PHP/Apache

echo "🚀 بدء النشر التقليدي..."

# المتغيرات
APP_DIR="/var/www/zeropay"
BACKUP_DIR="/backup/zeropay"
DATE=$(date +%Y%m%d_%H%M%S)

# النسخ الاحتياطي
echo "💾 عمل نسخة احتياطية..."
mkdir -p "$BACKUP_DIR"
tar -czf "$BACKUP_DIR/backup_$DATE.tar.gz" "$APP_DIR" 2>/dev/null || true

# نسخ الملفات الجديدة
echo "📦 نسخ الملفات..."
rsync -av --exclude='.git' --exclude='storage/logs/*' /workspace/prod/ "$APP_DIR/"

# تعيين الصلاحيات
echo "🔐 تعيين الصلاحيات..."
chown -R www-data:www-data "$APP_DIR"
chmod -R 755 "$APP_DIR"
chmod -R 775 "$APP_DIR/storage"
chmod -R 775 "$APP_DIR/bootstrap/cache"

# تحديث Composer
echo "📦 تحديث المكتبات..."
cd "$APP_DIR"
composer install --no-dev --optimize-autoloader

# تنظيف وتحسين Laravel
echo "⚡ تحسين Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# إعادة تشغيل Apache
echo "🔄 إعادة تشغيل Apache..."
service apache2 reload

echo "✅ تم النشر بنجاح!"
