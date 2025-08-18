#!/bin/bash
# بدء التطبيق بدون Docker/NPM

echo "🚀 بدء ZeroPay..."

# التحقق من PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP غير مثبت!"
    exit 1
fi

# التحقق من Composer
if ! command -v composer &> /dev/null; then
    echo "❌ Composer غير مثبت!"
    exit 1
fi

# الانتقال للمجلد
cd /workspace/prod/applications/zeropay-api

# تثبيت المكتبات
echo "📦 تثبيت المكتبات..."
composer install --no-dev --optimize-autoloader

# إعداد Laravel
echo "⚙️ إعداد Laravel..."
cp .env.production .env
php artisan key:generate
php artisan migrate --force
php artisan storage:link

# بدء الخادم
echo "✅ التطبيق جاهز!"
echo "🌐 يمكنك الوصول عبر: http://localhost:8000"
php artisan serve --host=0.0.0.0 --port=8000
