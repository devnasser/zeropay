#!/bin/bash
# سكريبت تثبيت Composer

echo "🎼 تثبيت Composer..."

# التحقق من الاتصال
if ! ping -c 1 google.com &>/dev/null; then
    echo "❌ لا يوجد اتصال بالإنترنت - مطلوب للتثبيت"
    exit 1
fi

# التحقق من وجود PHP
if ! command -v php &>/dev/null; then
    echo "❌ PHP غير مثبت - يجب تثبيت PHP أولاً"
    exit 1
fi

# تحميل Composer
echo "📥 تحميل Composer..."
EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
    echo "❌ فشل التحقق من سلامة الملف"
    rm composer-setup.php
    exit 1
fi

# تثبيت Composer
php composer-setup.php --quiet
rm composer-setup.php

# نقل لمسار عام
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# تكوين Composer
echo "⚙️ تكوين Composer..."
export COMPOSER_HOME="/workspace/.config/composer"
export COMPOSER_CACHE_DIR="/mnt/ramdisk/composer/cache"

composer config --global process-timeout 0
composer config --global optimize-autoloader true
composer config --global classmap-authoritative true
composer config --global apcu-autoloader true
composer config --global prefer-dist true
composer config --global cache-dir /mnt/ramdisk/composer/cache

# التحقق من التثبيت
if composer --version; then
    echo "✅ تم تثبيت Composer بنجاح"
else
    echo "❌ فشل تثبيت Composer"
    exit 1
fi

# تثبيت أدوات مفيدة
echo "📦 تثبيت أدوات Composer العامة..."
composer global require laravel/installer --no-interaction
composer global require phpstan/phpstan --no-interaction

echo "✅ اكتمل تثبيت Composer والأدوات"