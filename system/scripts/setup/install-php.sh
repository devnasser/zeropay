#!/bin/bash
# سكريبت تثبيت PHP 8.4 مع كل الإضافات

echo "🐘 تثبيت PHP 8.4..."

# التحقق من الاتصال
if ! ping -c 1 google.com &>/dev/null; then
    echo "❌ لا يوجد اتصال بالإنترنت - مطلوب للتثبيت"
    exit 1
fi

# تحديث المستودعات
echo "📦 تحديث المستودعات..."
sudo apt-get update

# تثبيت الأدوات المطلوبة
sudo apt-get install -y software-properties-common

# إضافة مستودع PHP
echo "📦 إضافة مستودع PHP..."
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update

# تثبيت PHP 8.4 مع الإضافات
echo "📦 تثبيت PHP 8.4 والإضافات..."
sudo apt-get install -y \
    php8.4-cli \
    php8.4-fpm \
    php8.4-common \
    php8.4-mysql \
    php8.4-sqlite3 \
    php8.4-pgsql \
    php8.4-zip \
    php8.4-gd \
    php8.4-mbstring \
    php8.4-curl \
    php8.4-xml \
    php8.4-bcmath \
    php8.4-intl \
    php8.4-readline \
    php8.4-opcache \
    php8.4-tokenizer \
    php8.4-fileinfo \
    php8.4-dom

# التحقق من التثبيت
if php -v | grep -q "PHP 8.4"; then
    echo "✅ تم تثبيت PHP 8.4 بنجاح"
    php -v
else
    echo "❌ فشل تثبيت PHP 8.4"
    exit 1
fi

# إنشاء ملف معلومات PHP
echo "<?php phpinfo(); ?>" > /workspace/phpinfo.php
echo "✅ تم إنشاء ملف phpinfo.php"