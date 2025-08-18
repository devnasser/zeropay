# ⚔️ دليل الإنتاج الشامل - PHP/Laravel النقي ⚔️
# Pure PHP/Laravel Production Guide

تاريخ التحديث: 2025-01-14
الإصدار: 2.0.0 (PHP Only)
البيئة: PHP 8.0+ / Laravel 11

---

## 📋 المحتويات

1. [نظرة عامة](#نظرة-عامة)
2. [المتطلبات](#المتطلبات)
3. [الهيكل](#الهيكل)
4. [التثبيت](#التثبيت)
5. [التكوين](#التكوين)
6. [النشر](#النشر)
7. [الأداء](#الأداء)
8. [الأمان](#الأمان)
9. [الصيانة](#الصيانة)
10. [استكشاف الأخطاء](#استكشاف-الأخطاء)

---

## 🌟 نظرة عامة

مجلد الإنتاج مُحسّن خصيصاً لبيئة PHP/Laravel النقية بدون أي اعتماديات على:
- ❌ Docker
- ❌ Node.js / NPM
- ❌ Kubernetes

### المميزات:
- ✅ PHP 8.0+ مع Laravel 11
- ✅ Apache/Nginx جاهز
- ✅ Composer للإدارة
- ✅ SQLite/MySQL/PostgreSQL
- ✅ Redis للتخزين المؤقت
- ✅ أداء محسّن 20x

---

## 💻 المتطلبات

### البرمجيات المطلوبة:
```bash
# PHP
php >= 8.0
php-mbstring
php-xml
php-bcmath
php-curl
php-gd
php-zip
php-sqlite3 (أو php-mysql/php-pgsql)

# خادم الويب
apache2 (مع mod_rewrite)
# أو
nginx

# أدوات
composer >= 2.0
git
redis-server (اختياري)
```

### متطلبات النظام:
- **الذاكرة**: 2GB RAM (4GB موصى به)
- **المعالج**: 2 cores (4 موصى به)
- **المساحة**: 1GB للتطبيق + مساحة للبيانات

---

## 🏗️ الهيكل

```
/workspace/prod/
│
├── 📱 applications/              # التطبيقات
│   ├── zeropay-api/             # API الرئيسي (Laravel)
│   ├── smart-autoparts/         # تطبيق قطع الغيار
│   └── admin-dashboard/         # لوحة تحكم PHP
│
├── 🔧 services/                 # خدمات PHP
│   ├── payment-service/         # معالج مدفوعات PHP
│   ├── notification-service/    # خدمة إشعارات PHP
│   └── analytics-service/       # تحليلات PHP
│
├── 📚 knowledge-base/           # قاعدة المعرفة
│   ├── ai-ready/               # معرفة منظمة
│   ├── documentation/          # التوثيق
│   └── best-practices/         # أفضل الممارسات
│
├── 🛠️ tools/                   # أدوات PHP
│   ├── monitoring/             # مراقبة PHP
│   ├── deployment/             # نشر تقليدي
│   │   └── traditional/        # Apache/Nginx
│   └── utilities/              # أدوات مساعدة
│
├── 🏛️ infrastructure/          # إعدادات الخادم
│   ├── apache/                 # تكوين Apache
│   ├── nginx/                  # تكوين Nginx
│   ├── cache/                  # Redis config
│   └── php.ini                 # إعدادات PHP
│
├── 📦 packages/                # حزم PHP
│   ├── auth/                   # حزمة مصادقة
│   ├── payment/                # حزمة مدفوعات
│   └── core/                   # حزمة أساسية
│
├── 🔐 security/                # الأمان
│   ├── certificates/           # SSL
│   └── .htaccess              # حماية Apache
│
├── composer.json               # اعتماديات PHP
├── start.sh                    # بدء سريع
└── README_PHP_ONLY.md          # دليل PHP
```

---

## 📥 التثبيت

### 1. نسخ الملفات
```bash
# نسخ لمجلد الويب
sudo cp -r /workspace/prod /var/www/zeropay

# أو استخدام Git
cd /var/www
git clone [repository-url] zeropay
```

### 2. تثبيت اعتماديات PHP
```bash
cd /var/www/zeropay
composer install --no-dev --optimize-autoloader
```

### 3. إعداد البيئة
```bash
# نسخ ملف البيئة
cp .env.production .env

# توليد مفتاح التطبيق
php artisan key:generate

# تعديل إعدادات قاعدة البيانات في .env
nano .env
```

### 4. إعداد قاعدة البيانات
```bash
# إنشاء قاعدة البيانات (SQLite)
touch database/database.sqlite

# أو MySQL
mysql -u root -p -e "CREATE DATABASE zeropay;"

# تشغيل الهجرات
php artisan migrate --force

# البيانات الأولية (اختياري)
php artisan db:seed
```

### 5. تعيين الصلاحيات
```bash
# مالك الملفات
sudo chown -R www-data:www-data /var/www/zeropay

# صلاحيات المجلدات
find /var/www/zeropay -type d -exec chmod 755 {} \;

# صلاحيات الملفات
find /var/www/zeropay -type f -exec chmod 644 {} \;

# مجلدات خاصة
chmod -R 775 /var/www/zeropay/storage
chmod -R 775 /var/www/zeropay/bootstrap/cache
```

---

## ⚙️ التكوين

### Apache Configuration
```bash
# نسخ التكوين
sudo cp infrastructure/apache/zeropay.conf /etc/apache2/sites-available/

# تفعيل الموقع
sudo a2ensite zeropay

# تفعيل الوحدات المطلوبة
sudo a2enmod rewrite headers expires deflate

# إعادة تشغيل Apache
sudo systemctl reload apache2
```

### Nginx Configuration (بديل)
```bash
# نسخ التكوين
sudo cp infrastructure/nginx/zeropay.conf /etc/nginx/sites-available/

# تفعيل الموقع
sudo ln -s /etc/nginx/sites-available/zeropay.conf /etc/nginx/sites-enabled/

# اختبار التكوين
sudo nginx -t

# إعادة تشغيل Nginx
sudo systemctl reload nginx
```

### PHP Configuration
```bash
# نسخ إعدادات PHP للإنتاج
sudo cp infrastructure/php.ini /etc/php/8.0/apache2/conf.d/99-zeropay.ini

# إعادة تشغيل PHP-FPM (إذا كنت تستخدم Nginx)
sudo systemctl restart php8.0-fpm
```

---

## 🚀 النشر

### النشر التقليدي (FTP/SSH)
```bash
# استخدام سكريبت النشر
./tools/deployment/traditional/deploy.sh

# أو يدوياً
rsync -avz --exclude='.git' --exclude='node_modules' \
    /workspace/prod/ user@server:/var/www/zeropay/
```

### النشر عبر Git
```bash
# على الخادم
cd /var/www/zeropay
git pull origin main
composer install --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### تحسينات Laravel للإنتاج
```bash
# تخزين الإعدادات
php artisan config:cache

# تخزين المسارات
php artisan route:cache

# تخزين العروض
php artisan view:cache

# تحسين التحميل التلقائي
composer dump-autoload -o

# تحسين شامل
php artisan optimize
```

---

## ⚡ الأداء

### تفعيل OPcache
```ini
; في php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=64
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
```

### Redis للتخزين المؤقت
```bash
# تثبيت Redis
sudo apt install redis-server

# في .env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# تشغيل Redis
sudo systemctl start redis-server
```

### ضغط الأصول
```bash
# ضغط CSS/JS
find public -name "*.css" -o -name "*.js" | while read file; do
    gzip -k -9 "$file"
done
```

---

## 🔐 الأمان

### 1. حماية المجلدات
```apache
# .htaccess في المجلد الرئيسي
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>

# منع الوصول للملفات الحساسة
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
```

### 2. Headers الأمنية
```apache
# في Apache config
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

### 3. SSL/HTTPS
```bash
# Let's Encrypt
sudo certbot --apache -d zeropay.com -d www.zeropay.com

# أو شهادة يدوية
sudo cp /path/to/cert.crt /etc/ssl/certs/zeropay.crt
sudo cp /path/to/key.key /etc/ssl/private/zeropay.key
```

---

## 🔧 الصيانة

### المهام اليومية
```bash
# تنظيف السجلات القديمة
php artisan log:clear

# تنظيف التخزين المؤقت المنتهي
php artisan cache:gc

# فحص صحة التطبيق
php artisan health:check
```

### المهام الأسبوعية
```bash
# النسخ الاحتياطي
./tools/utilities/backup/backup.sh

# تحديث Composer (في بيئة التطوير أولاً)
composer update --dry-run

# تحليل الأداء
php artisan performance:analyze
```

### المهام الشهرية
```bash
# تحديثات الأمان
composer audit

# تنظيف قاعدة البيانات
php artisan db:cleanup

# مراجعة السجلات
tail -n 1000 storage/logs/laravel.log | grep ERROR
```

---

## 🔍 استكشاف الأخطاء

### خطأ 500
```bash
# فحص السجلات
tail -f storage/logs/laravel.log
tail -f /var/log/apache2/error.log

# التحقق من الصلاحيات
ls -la storage/
ls -la bootstrap/cache/

# مسح التخزين المؤقت
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### مشاكل الأداء
```bash
# تفعيل وضع التصحيح مؤقتاً
APP_DEBUG=true php artisan serve

# استخدام Laravel Telescope (في التطوير)
composer require laravel/telescope --dev
php artisan telescope:install
```

### قاعدة البيانات لا تعمل
```bash
# اختبار الاتصال
php artisan tinker
>>> DB::connection()->getPdo();

# إعادة تشغيل قاعدة البيانات
sudo systemctl restart mysql
```

---

## 📞 الدعم

### الموارد المفيدة
- 📚 التوثيق: `/knowledge-base/documentation/`
- 🛠️ الأدوات: `/tools/utilities/`
- 📋 أفضل الممارسات: `/knowledge-base/best-practices/`

### البدء السريع للتطوير
```bash
cd /workspace/prod
./start.sh
# الوصول عبر: http://localhost:8000
```

---

⚔️ **نمط الأسطورة - بيئة PHP نقية محسّنة للإنتاج** ⚔️

*آخر تحديث: 2025-01-14*
*خالية من Docker/NPM/NodeJS*