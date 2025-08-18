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
