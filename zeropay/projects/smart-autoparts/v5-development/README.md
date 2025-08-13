# Smart AutoParts Marketplace v5.0

منصة تجارة إلكترونية متكاملة لقطع غيار السيارات مع نظام متعدد البائعين.

## المميزات الرئيسية

- 🛍️ **نظام متعدد البائعين** مع لوحات تحكم منفصلة
- 💳 **بوابات دفع متعددة** (STC Pay, Tamara, Tabby, Apple Pay, Mada)
- 📦 **إدارة المخزون والطلبات** المتقدمة
- 🌐 **دعم متعدد اللغات** (العربية، الإنجليزية)
- 📱 **تصميم متجاوب** يعمل على جميع الأجهزة
- 🔍 **بحث ذكي** مع فلاتر متقدمة
- 📊 **تحليلات شاملة** للبائعين والإدارة

## متطلبات النظام

- PHP >= 8.4
- Composer
- Node.js & NPM
- SQLite (للتطوير) أو MySQL/PostgreSQL (للإنتاج)

## التثبيت

### 1. استنساخ المشروع
```bash
git clone https://github.com/yourusername/smart-autoparts.git
cd smart-autoparts/v5-development
```

### 2. تثبيت التبعيات
```bash
composer install
npm install
```

### 3. إعداد البيئة
```bash
cp .env.example .env
php artisan key:generate
```

### 4. إعداد قاعدة البيانات
```bash
# للتطوير (SQLite)
touch database/database.sqlite

# تشغيل الهجرات
php artisan migrate

# إدخال البيانات الأولية
php artisan db:seed
```

### 5. بناء الأصول
```bash
npm run build
# أو للتطوير
npm run dev
```

### 6. تشغيل الخادم
```bash
php artisan serve
```

الآن يمكنك زيارة `http://localhost:8000`

## الحسابات التجريبية

### مدير النظام
- **البريد:** admin@smartautoparts.com
- **كلمة المرور:** password

### بائع
- **البريد:** vendor@example.com
- **كلمة المرور:** password

### عميل
- **البريد:** customer@example.com
- **كلمة المرور:** password

## البنية الأساسية

```
app/
├── Http/
│   ├── Controllers/      # المتحكمات
│   ├── Middleware/       # البرمجيات الوسيطة
│   └── Resources/        # موارد API
├── Models/              # نماذج البيانات
├── Services/            # خدمات الأعمال
└── Jobs/               # المهام الخلفية

resources/
├── views/              # واجهات Blade
├── js/                 # JavaScript/Vue
└── css/               # الأنماط

database/
├── migrations/         # هجرات قاعدة البيانات
└── seeders/           # بيانات أولية
```

## الأوامر المفيدة

### تحسين الأداء
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### تنظيف الذاكرة المؤقتة
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### معالجة الطوابير
```bash
php artisan queue:work
```

## واجهات API

### المصادقة
```
POST /api/login
POST /api/register
POST /api/logout
```

### المنتجات
```
GET    /api/products
GET    /api/products/{id}
POST   /api/products          (vendor only)
PUT    /api/products/{id}     (vendor only)
DELETE /api/products/{id}     (vendor only)
```

### الطلبات
```
GET    /api/orders
GET    /api/orders/{id}
POST   /api/orders
PATCH  /api/orders/{id}/status
```

## المساهمة

1. Fork المشروع
2. أنشئ فرع للميزة (`git checkout -b feature/AmazingFeature`)
3. Commit التغييرات (`git commit -m 'Add some AmazingFeature'`)
4. Push إلى الفرع (`git push origin feature/AmazingFeature`)
5. افتح Pull Request

## الترخيص

هذا المشروع مرخص تحت رخصة MIT - انظر ملف [LICENSE](LICENSE) للتفاصيل.

## الدعم

للدعم والاستفسارات:
- 📧 support@smartautoparts.com
- 📱 WhatsApp: +966501234567
- 🌐 [smartautoparts.com](https://smartautoparts.com)

---

صُنع بـ ❤️ بواسطة فريق Smart AutoParts
