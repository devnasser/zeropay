#!/bin/bash
# سكريبت النشر الآمن

echo "🚀 بدء عملية النشر..."

# التحقق من البيئة
if [ "$APP_ENV" != "production" ]; then
    echo "⚠️ تحذير: البيئة ليست production"
    read -p "هل تريد المتابعة؟ (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# النسخ الاحتياطي
echo "💾 عمل نسخة احتياطية..."
./scripts/maintenance/backup.sh

# وضع الصيانة
echo "🔧 تفعيل وضع الصيانة..."
php artisan down --message="نقوم بتحديث النظام" --retry=60

# تحديث الكود
echo "📦 تحديث الكود..."
git pull origin main

# تحديث المكتبات
echo "📚 تحديث المكتبات..."
composer install --no-dev --optimize-autoloader

# تشغيل الهجرات
echo "🗄️ تحديث قاعدة البيانات..."
php artisan migrate --force

# تحسين الأداء
echo "⚡ تحسين الأداء..."
./scripts/optimization/optimize.sh

# إنهاء وضع الصيانة
echo "✅ إنهاء وضع الصيانة..."
php artisan up

echo "🎉 تم النشر بنجاح!"
