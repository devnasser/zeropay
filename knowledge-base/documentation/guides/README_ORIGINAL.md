# Smart AutoParts - سوق قطع الغيار الذكي

## 📋 نظرة عامة
منصة متقدمة لبيع وشراء قطع غيار السيارات في المملكة العربية السعودية، مع دعم كامل للغات المتعددة والواجهات الصوتية للمستخدمين الأميين.

## ⚡ المميزات الرئيسية

### 🌐 دعم اللغات المتعددة
- العربية (الافتراضية)
- الإنجليزية
- الأردو
- الفرنسية
- الفارسية

### 🎯 أنواع المستخدمين
- **العملاء**: للشراء والتصفح
- **أصحاب المتاجر**: لإدارة المنتجات والطلبات
- **الفنيين**: لتقديم الخدمات
- **السائقين**: لتوصيل الطلبات
- **المدراء**: لإدارة المنصة

### 🤖 الذكاء الاصطناعي
- **نظام توصيات ذكي** بـ 9 أنواع مختلفة:
  1. توصيات شخصية
  2. منتجات مماثلة
  3. توصيات موسمية
  4. توصيات حسب الموقع
  5. توصيات خاصة بالسيارة
  6. حزم ذكية
  7. تخفيضات الأسعار
  8. منتجات تكميلية
  9. توصيات AI متقدمة

### 🔊 الواجهات الصوتية
- دعم كامل للأوامر الصوتية
- قراءة المحتوى بالصوت
- مساعد صوتي ذكي للأميين

### 🏢 التكاملات الحكومية
- نظام SABER
- هيئة الزكاة والضريبة والجمارك (ZATCA)
- نفاذ للهوية الرقمية

### 💳 بوابات الدفع
- STC Pay
- تمارا (الدفع بالتقسيط)
- تابي
- Apple Pay
- مدى

### 🚚 شركات الشحن
- سمسا
- أرامكس
- البريد السعودي

## 🛠️ التقنيات المستخدمة

- **Backend**: Laravel 12, PHP 8.4
- **Frontend**: Livewire 3, Alpine.js, Tailwind CSS
- **Database**: SQLite (Development), MySQL/PostgreSQL (Production)
- **Cache**: Redis
- **Queue**: Database/Redis
- **Search**: Laravel Scout with Meilisearch
- **Realtime**: Laravel Echo with Pusher/Soketi

## 📦 التثبيت

### المتطلبات
- PHP >= 8.4
- Composer
- Node.js & NPM (للتطوير فقط)
- SQLite/MySQL/PostgreSQL

### خطوات التثبيت

1. **استنساخ المشروع**
```bash
git clone https://github.com/devnasser/smart-autoparts.git
cd smart-autoparts
```

2. **تثبيت الحزم**
```bash
composer install
npm install
```

3. **إعداد البيئة**
```bash
cp .env.example .env
php artisan key:generate
```

4. **إعداد قاعدة البيانات**
```bash
php artisan migrate --seed
```

5. **تشغيل المشروع**
```bash
php artisan serve
npm run dev
```

## 🏗️ هيكل المشروع

```
smart_autoparts_v1/
├── app/
│   ├── Models/          # نماذج البيانات
│   ├── Services/        # خدمات الأعمال
│   ├── Http/
│   │   ├── Controllers/ # المتحكمات
│   │   └── Livewire/    # مكونات Livewire
│   └── Traits/          # الخصائص المشتركة
├── database/
│   ├── migrations/      # هجرات قاعدة البيانات
│   ├── factories/       # مصانع البيانات
│   └── seeders/         # بذور البيانات
├── resources/
│   ├── views/           # واجهات Blade
│   ├── css/             # ملفات CSS
│   └── js/              # ملفات JavaScript
└── routes/              # مسارات التطبيق
```

## 🔧 الإعدادات

### إعدادات البيئة الأساسية
```env
APP_NAME="Smart AutoParts"
APP_ENV=local
APP_URL=http://localhost:8000
APP_LOCALE=ar
APP_TIMEZONE=Asia/Riyadh

# دعم اللغات
SUPPORTED_LOCALES=ar,en,ur,fr,fa

# إعدادات المشروع
DEFAULT_CURRENCY=SAR
VAT_RATE=15
ENABLE_VOICE_INTERFACE=true
ENABLE_AI_RECOMMENDATIONS=true
```

## 📱 واجهات برمجة التطبيقات (API)

### نقاط النهاية الرئيسية
- `GET /api/products` - قائمة المنتجات
- `GET /api/categories` - قائمة التصنيفات
- `GET /api/shops` - قائمة المتاجر
- `POST /api/cart` - إضافة للسلة
- `POST /api/orders` - إنشاء طلب

### المصادقة
يستخدم المشروع Laravel Sanctum للمصادقة في API.

## 🧪 الاختبار

```bash
# تشغيل جميع الاختبارات
php artisan test

# اختبارات الوحدة
php artisan test --testsuite=Unit

# اختبارات الميزات
php artisan test --testsuite=Feature
```

## 🚀 النشر

### باستخدام Laravel Forge
1. إنشاء خادم جديد
2. ربط المستودع
3. تكوين البيئة
4. تشغيل النشر

### يدوياً
```bash
# على الخادم
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

## 📊 الأداء

- **سرعة التحميل**: < 100ms
- **معالجة الطلبات**: 1000+ طلب/ثانية
- **التخزين المؤقت**: متعدد المستويات
- **تحسين الصور**: تلقائي

## 🔒 الأمان

- تشفير HTTPS إلزامي
- حماية CSRF
- تشفير البيانات الحساسة
- مصادقة ثنائية
- سجلات الأمان

## 👥 الفريق

- **المطور الرئيسي**: ناصر العنزي
- **البريد الإلكتروني**: dev-na@outlook.com
- **الهاتف**: +966 50 848 0715

## 📄 الرخصة

هذا المشروع مرخص تحت رخصة MIT. انظر ملف [LICENSE](LICENSE) للتفاصيل.

## 🤝 المساهمة

نرحب بالمساهمات! يرجى قراءة [CONTRIBUTING.md](CONTRIBUTING.md) للتفاصيل.

---

© 2024 Smart AutoParts. جميع الحقوق محفوظة.
