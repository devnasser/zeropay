# 📤 دليل رفع المشروع على GitHub

## الخطوة 1: إنشاء مستودع جديد على GitHub

1. اذهب إلى https://github.com/new
2. أدخل المعلومات التالية:
   - **Repository name**: `smart-autoparts-ultimate`
   - **Description**: "Smart AutoParts Ultimate - The unified platform for auto parts with AI, AR, and blockchain"
   - **Visibility**: Public أو Private حسب رغبتك
   - **لا تختر** "Initialize this repository with a README"

3. اضغط على "Create repository"

## الخطوة 2: دفع الكود

بعد إنشاء المستودع، نفذ الأوامر التالية:

```bash
cd /workspace/SMART_AUTOPARTS_ULTIMATE

# إذا كان المستودع جديد
git remote add origin https://github.com/YOUR_USERNAME/smart-autoparts-ultimate.git
git push -u origin main
```

أو إذا كنت تريد استخدام SSH:

```bash
git remote set-url origin git@github.com:YOUR_USERNAME/smart-autoparts-ultimate.git
git push -u origin main
```

## الخطوة 3: البدائل

### البديل 1: الدفع إلى مستودع موجود في فرع جديد

```bash
cd /workspace/SMART_AUTOPARTS_ULTIMATE

# إزالة الـ remote الحالي
git remote remove origin

# إضافة المستودع الموجود
git remote add origin https://github.com/devnasser/zeropay.git

# دفع إلى فرع جديد
git push -u origin main:smart-autoparts-ultimate
```

### البديل 2: إنشاء مستودع جديد عبر GitHub CLI

إذا كان لديك GitHub CLI مثبت:

```bash
gh repo create smart-autoparts-ultimate --public --source=. --remote=origin --push
```

### البديل 3: الدفع كـ ZIP

```bash
cd /workspace
zip -r SMART_AUTOPARTS_ULTIMATE.zip SMART_AUTOPARTS_ULTIMATE/
# ثم ارفع الملف يدوياً على GitHub
```

## معلومات المشروع للـ README على GitHub

بعد الرفع، أضف هذه المعلومات:

**About Section:**
- Description: Smart AutoParts Ultimate - AI-powered auto parts platform
- Website: https://smartautoparts.sa (قريباً)
- Topics: `laravel`, `react-native`, `ai`, `microservices`, `ecommerce`, `saudi-arabia`

**Settings:**
- Default branch: main
- Enable Issues
- Enable Projects
- Enable Wiki (اختياري)

## الأوامر المفيدة بعد الرفع

```bash
# إضافة tag للإصدار
git tag -a v3.0.0 -m "Initial release - Smart AutoParts Ultimate"
git push origin v3.0.0

# إنشاء فرع للتطوير
git checkout -b develop
git push -u origin develop

# حماية الفرع الرئيسي
# اذهب إلى Settings > Branches > Add rule
# Branch name pattern: main
# Enable: Require pull request reviews
```

## للمساعدة

إذا واجهت مشاكل:

1. تحقق من صلاحيات الوصول
2. تأكد من تسجيل الدخول: `git config --global user.name` و `git config --global user.email`
3. استخدم Personal Access Token بدلاً من كلمة المرور

---

⚔️ **نمط الأسطورة - المشروع جاهز للعالم!** ⚔️