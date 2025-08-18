#!/bin/bash
# ⚔️ منظم PHP النظيف - بدون Docker/NPM/NodeJS ⚔️
# Clean PHP Organizer - No Docker/NPM/NodeJS

echo "⚔️ تنظيف وتحديث مجلد الإنتاج - PHP فقط ⚔️"
echo "==========================================="
echo ""

PROD_DIR="/workspace/prod"

# تنظيف الملفات غير المطلوبة
echo "🧹 إزالة ملفات Docker/NPM/NodeJS..."

# حذف ملفات Docker
rm -rf "$PROD_DIR/tools/deployment/docker" 2>/dev/null
rm -f "$PROD_DIR/Dockerfile" 2>/dev/null
rm -f "$PROD_DIR/docker-compose.yml" 2>/dev/null
rm -f "$PROD_DIR/tools/deployment/docker/docker-compose.yml" 2>/dev/null
echo "  ✓ تم حذف ملفات Docker"

# حذف ملفات NPM/NodeJS
find "$PROD_DIR" -name "package.json" -delete 2>/dev/null
find "$PROD_DIR" -name "package-lock.json" -delete 2>/dev/null
find "$PROD_DIR" -name "node_modules" -type d -exec rm -rf {} + 2>/dev/null
find "$PROD_DIR" -name "*.js" ! -name "*.min.js" ! -path "*/public/*" -delete 2>/dev/null
echo "  ✓ تم حذف ملفات NPM/NodeJS"

# حذف ملفات Kubernetes (تعتمد على Docker)
rm -rf "$PROD_DIR/tools/deployment/kubernetes" 2>/dev/null
echo "  ✓ تم حذف ملفات Kubernetes"

# تحديث البنية للـ PHP فقط
echo -e "\n📁 إعادة تنظيم للـ PHP..."

# إنشاء مجلد للنشر التقليدي
mkdir -p "$PROD_DIR/tools/deployment/traditional"

# إنشاء سكريبت النشر التقليدي
cat > "$PROD_DIR/tools/deployment/traditional/deploy.sh" << 'EOF'
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
EOF
chmod +x "$PROD_DIR/tools/deployment/traditional/deploy.sh"
echo "  ✓ سكريبت النشر التقليدي"

# إنشاء إعدادات Apache
cat > "$PROD_DIR/infrastructure/apache/zeropay.conf" << 'EOF'
<VirtualHost *:80>
    ServerName zeropay.com
    DocumentRoot /var/www/zeropay/public
    
    <Directory /var/www/zeropay/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # تحسينات الأداء
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/css application/javascript
    </IfModule>
    
    # التخزين المؤقت
    <IfModule mod_expires.c>
        ExpiresActive On
        ExpiresByType image/jpg "access plus 1 year"
        ExpiresByType image/jpeg "access plus 1 year"
        ExpiresByType image/png "access plus 1 year"
        ExpiresByType text/css "access plus 1 month"
    </IfModule>
    
    ErrorLog ${APACHE_LOG_DIR}/zeropay_error.log
    CustomLog ${APACHE_LOG_DIR}/zeropay_access.log combined
</VirtualHost>

<VirtualHost *:443>
    ServerName zeropay.com
    DocumentRoot /var/www/zeropay/public
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/zeropay.crt
    SSLCertificateKeyFile /etc/ssl/private/zeropay.key
    
    <Directory /var/www/zeropay/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
EOF
echo "  ✓ إعدادات Apache"

# تحديث إعدادات PHP
cat > "$PROD_DIR/infrastructure/php.ini" << 'EOF'
; إعدادات PHP للإنتاج
memory_limit = 256M
max_execution_time = 60
max_input_time = 60
post_max_size = 20M
upload_max_filesize = 20M

; الأمان
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

; OPcache
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 64
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0
opcache.save_comments = 0
opcache.fast_shutdown = 1

; Session
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_only_cookies = 1
EOF
echo "  ✓ إعدادات PHP"

# إنشاء سكريبت بدء بسيط
cat > "$PROD_DIR/start.sh" << 'EOF'
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
EOF
chmod +x "$PROD_DIR/start.sh"
echo "  ✓ سكريبت البدء البسيط"

# تحديث الوثائق
echo -e "\n📝 تحديث الوثائق..."

cat > "$PROD_DIR/README_PHP_ONLY.md" << 'EOF'
# 🚀 ZeroPay - بيئة PHP النقية

## المتطلبات
- PHP 8.0+
- Composer 2.0+
- Apache/Nginx
- SQLite/MySQL/PostgreSQL

## التثبيت

### 1. نسخ الملفات
```bash
cp -r /workspace/prod /var/www/zeropay
```

### 2. تثبيت المكتبات
```bash
cd /var/www/zeropay
composer install --no-dev
```

### 3. إعداد البيئة
```bash
cp .env.production .env
php artisan key:generate
```

### 4. إعداد قاعدة البيانات
```bash
php artisan migrate
php artisan db:seed
```

### 5. تعيين الصلاحيات
```bash
chown -R www-data:www-data /var/www/zeropay
chmod -R 755 /var/www/zeropay
chmod -R 775 storage bootstrap/cache
```

### 6. إعداد Apache
```bash
cp infrastructure/apache/zeropay.conf /etc/apache2/sites-available/
a2ensite zeropay
a2enmod rewrite
service apache2 reload
```

## البدء السريع (للتطوير)
```bash
./start.sh
```

## النشر للإنتاج
```bash
./tools/deployment/traditional/deploy.sh
```

---
⚔️ نمط الأسطورة - PHP النقي ⚔️
EOF
echo "  ✓ تم تحديث README"

# تحديث composer.json الرئيسي
cat > "$PROD_DIR/composer.json" << 'EOF'
{
    "name": "zeropay/production",
    "type": "project",
    "description": "ZeroPay Production Environment - Pure PHP",
    "require": {
        "php": "^8.0",
        "laravel/framework": "^11.0",
        "laravel/sanctum": "^4.0",
        "predis/predis": "^2.0",
        "guzzlehttp/guzzle": "^7.8"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "applications/zeropay-api/app/",
            "Services\\": "services/",
            "Packages\\": "packages/"
        }
    },
    "scripts": {
        "post-install-cmd": [
            "@php artisan optimize"
        ]
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true
    }
}
EOF
echo "  ✓ تم تحديث composer.json"

# حذف المراجع لـ Docker/NPM من الدليل الشامل
sed -i '/Docker/d' "$PROD_DIR/COMPLETE_PRODUCTION_GUIDE.md" 2>/dev/null
sed -i '/docker/d' "$PROD_DIR/COMPLETE_PRODUCTION_GUIDE.md" 2>/dev/null
sed -i '/npm/d' "$PROD_DIR/COMPLETE_PRODUCTION_GUIDE.md" 2>/dev/null
sed -i '/node/d' "$PROD_DIR/COMPLETE_PRODUCTION_GUIDE.md" 2>/dev/null
sed -i '/Kubernetes/d' "$PROD_DIR/COMPLETE_PRODUCTION_GUIDE.md" 2>/dev/null

# التقرير النهائي
echo -e "\n📊 التحديث النهائي..."

FINAL_FILES=$(find "$PROD_DIR" -type f | wc -l)
FINAL_SIZE=$(du -sh "$PROD_DIR" | cut -f1)
PHP_FILES=$(find "$PROD_DIR" -name "*.php" | wc -l)

echo ""
echo "=========================================="
echo "✅ تم التنظيف والتحديث بنجاح!"
echo ""
echo "📊 الإحصائيات المحدثة:"
echo "   - إجمالي الملفات: $FINAL_FILES"
echo "   - ملفات PHP: $PHP_FILES"
echo "   - الحجم النهائي: $FINAL_SIZE"
echo ""
echo "🎯 ما تم:"
echo "   ✓ حذف جميع ملفات Docker"
echo "   ✓ حذف جميع ملفات NPM/NodeJS"
echo "   ✓ إنشاء نظام نشر تقليدي"
echo "   ✓ إعدادات Apache/PHP جاهزة"
echo "   ✓ سكريبتات PHP نقية"
echo ""
echo "📍 للبدء:"
echo "   ./start.sh"
echo ""
echo "⚔️ نمط الأسطورة - بيئة PHP نقية جاهزة ⚔️"