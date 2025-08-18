#!/bin/bash
# ⚔️ البدء السريع لـ Smart AutoParts ⚔️

echo "⚔️ البدء السريع لـ Smart AutoParts ⚔️"
echo "========================================"
echo ""

# التحقق من PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP غير مثبت!"
    echo "📌 لتثبيت PHP:"
    echo "   Ubuntu/Debian: sudo apt install php8.2"
    echo "   CentOS/RHEL: sudo yum install php82"
    echo "   macOS: brew install php"
    exit 1
fi

# التحقق من Composer
if ! command -v composer &> /dev/null; then
    echo "❌ Composer غير مثبت!"
    echo "📌 لتثبيت Composer:"
    echo "   curl -sS https://getcomposer.org/installer | php"
    echo "   sudo mv composer.phar /usr/local/bin/composer"
    exit 1
fi

echo "✅ البيئة جاهزة"
echo ""

# إعداد البيئة
echo "⚙️ إعداد البيئة..."
if [ ! -f .env ]; then
    cp .env.production .env
    echo "  ✓ تم إنشاء .env"
fi

# توليد المفتاح
if ! grep -q "APP_KEY=base64:" .env; then
    php artisan key:generate
    echo "  ✓ تم توليد مفتاح التطبيق"
fi

# تثبيت المكتبات
echo ""
echo "📦 تثبيت المكتبات..."
composer install --no-dev --optimize-autoloader

# إعداد قاعدة البيانات
echo ""
echo "🗄️ إعداد قاعدة البيانات..."
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
    echo "  ✓ تم إنشاء قاعدة البيانات"
fi

# تشغيل الهجرات
php artisan migrate --force

# إنشاء رابط التخزين
if [ ! -L public/storage ]; then
    php artisan storage:link
    echo "  ✓ تم إنشاء رابط التخزين"
fi

# تحسين الأداء
echo ""
echo "⚡ تحسين الأداء..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# عرض معلومات النظام
echo ""
echo "📊 معلومات النظام:"
echo "===================="
php artisan about

echo ""
echo "✅ التطبيق جاهز للتشغيل!"
echo ""
echo "🚀 لبدء الخادم:"
echo "   php artisan serve"
echo ""
echo "🌐 ثم افتح: http://localhost:8000"
echo ""
echo "📱 حسابات تجريبية:"
echo "   مدير: admin@smartautoparts.sa / password"
echo "   متجر: shop@smartautoparts.sa / password"
echo "   عميل: customer@smartautoparts.sa / password"
echo ""
echo "⚔️ نمط الأسطورة - جاهز للإطلاق! ⚔️"