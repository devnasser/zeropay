#!/bin/bash
# تحسين أداء التطبيق

echo "⚡ بدء تحسين الأداء..."

# تحسين Composer
composer install --no-dev --optimize-autoloader

# تحسين Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# تحسين قاعدة البيانات
php artisan migrate --force
php artisan db:seed --force

# تنظيف التخزين المؤقت القديم
php artisan cache:clear
php artisan queue:restart

# ضغط الأصول
find public/css -name "*.css" -exec gzip -k {} \;
find public/js -name "*.js" -exec gzip -k {} \;

echo "✅ تم تحسين الأداء"
