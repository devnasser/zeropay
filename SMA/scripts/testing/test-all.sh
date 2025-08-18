#!/bin/bash
# اختبار شامل للتطبيق

echo "🧪 بدء الاختبارات الشاملة..."

# اختبار البيئة
echo "1. فحص البيئة..."
php -v
composer --version

# اختبار قاعدة البيانات
echo "2. فحص قاعدة البيانات..."
php artisan migrate:status

# اختبار المسارات
echo "3. فحص المسارات..."
php artisan route:list --compact

# اختبار الوحدات
echo "4. اختبارات الوحدة..."
./vendor/bin/phpunit --testsuite=Unit

# اختبار المميزات
echo "5. اختبارات المميزات..."
./vendor/bin/phpunit --testsuite=Feature

# اختبار الأداء
echo "6. اختبار الأداء..."
php artisan test:performance

echo "✅ اكتملت جميع الاختبارات"
