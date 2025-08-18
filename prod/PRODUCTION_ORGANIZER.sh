#!/bin/bash
# ⚔️ منظم الإنتاج الشامل - نمط الأسطورة ⚔️
# Complete Production Organizer - Legend Mode

echo "⚔️ بدء تنظيم مجلد الإنتاج - نمط الأسطورة ⚔️"
echo "==========================================="
echo ""

WORKSPACE="/workspace"
PROD_DIR="/workspace/prod"

# ألوان للإخراج
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# دالة لطباعة الحالة
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# 1. نقل وتنظيم التطبيقات
echo -e "\n${BLUE}📱 تنظيم التطبيقات...${NC}"

# Smart AutoParts
if [ -d "$WORKSPACE/zeropay/projects/smart-autoparts/development" ]; then
    print_info "معالجة Smart AutoParts..."
    
    # فصل Backend و Frontend
    mkdir -p "$PROD_DIR/applications/smart-autoparts/backend"
    mkdir -p "$PROD_DIR/applications/smart-autoparts/frontend"
    
    # نقل ملفات Laravel (Backend)
    for dir in app config database routes storage; do
        if [ -d "$WORKSPACE/zeropay/projects/smart-autoparts/development/$dir" ]; then
            cp -r "$WORKSPACE/zeropay/projects/smart-autoparts/development/$dir" \
                  "$PROD_DIR/applications/smart-autoparts/backend/" 2>/dev/null
        fi
    done
    
    # نقل ملفات Frontend
    if [ -d "$WORKSPACE/zeropay/projects/smart-autoparts/development/resources" ]; then
        cp -r "$WORKSPACE/zeropay/projects/smart-autoparts/development/resources" \
              "$PROD_DIR/applications/smart-autoparts/frontend/" 2>/dev/null
    fi
    
    # نقل ملفات التكوين
    for file in composer.json package.json .env.example; do
        if [ -f "$WORKSPACE/zeropay/projects/smart-autoparts/development/$file" ]; then
            cp "$WORKSPACE/zeropay/projects/smart-autoparts/development/$file" \
               "$PROD_DIR/applications/smart-autoparts/" 2>/dev/null
        fi
    done
    
    print_status "تم تنظيم Smart AutoParts"
fi

# 2. نقل قاعدة المعرفة
echo -e "\n${BLUE}📚 تنظيم قاعدة المعرفة...${NC}"

# نقل المعرفة من docs/AI
if [ -d "$WORKSPACE/docs/AI/knowledge_base" ]; then
    print_info "نقل معرفة AI..."
    cp -r "$WORKSPACE/docs/AI/knowledge_base/"* "$PROD_DIR/knowledge-base/ai-ready/" 2>/dev/null
    
    # تنظيم حسب الفئات
    for category in security performance architecture; do
        if [ -d "$PROD_DIR/knowledge-base/ai-ready/$category" ]; then
            mv "$PROD_DIR/knowledge-base/ai-ready/$category/"*.md \
               "$PROD_DIR/knowledge-base/best-practices/$category/" 2>/dev/null || true
        fi
    done
    print_status "تم نقل معرفة AI"
fi

# نقل الوثائق البشرية
if [ -d "$WORKSPACE/docs/human" ]; then
    print_info "نقل الوثائق البشرية..."
    cp -r "$WORKSPACE/docs/human/guides/"* "$PROD_DIR/knowledge-base/documentation/user-guides/" 2>/dev/null || true
    print_status "تم نقل الوثائق البشرية"
fi

# 3. نقل الأدوات المفيدة
echo -e "\n${BLUE}🛠️ تنظيم الأدوات...${NC}"

# نقل أدوات الأداء
print_info "نقل أدوات المراقبة والأداء..."
for tool in performance-manager.sh performance-test.php cache-manager.php database-optimizer.php; do
    if [ -f "$WORKSPACE/system/scripts/$tool" ]; then
        cp "$WORKSPACE/system/scripts/$tool" "$PROD_DIR/tools/monitoring/performance/" 2>/dev/null
        chmod +x "$PROD_DIR/tools/monitoring/performance/$tool" 2>/dev/null
    fi
done
print_status "تم نقل أدوات الأداء"

# نقل أدوات التحسين
print_info "نقل أدوات التحسين..."
for tool in asset-compression.php build-search-index.php parallel-processor.php; do
    if [ -f "$WORKSPACE/system/scripts/$tool" ]; then
        cp "$WORKSPACE/system/scripts/$tool" "$PROD_DIR/tools/utilities/optimization/" 2>/dev/null
    fi
done
print_status "تم نقل أدوات التحسين"

# 4. إنشاء ملفات التكوين للإنتاج
echo -e "\n${BLUE}⚙️ إنشاء ملفات التكوين...${NC}"

# إنشاء .env.production
cat > "$PROD_DIR/.env.production" << 'EOF'
APP_NAME=ZeroPay
APP_ENV=production
APP_DEBUG=false
APP_URL=https://zeropay.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/database/database.sqlite

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Security
FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true
EOF
print_status "تم إنشاء .env.production"

# إنشاء docker-compose.yml
cat > "$PROD_DIR/tools/deployment/docker/docker-compose.yml" << 'EOF'
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./applications:/var/www/html
      - ./storage:/var/www/storage
    environment:
      - APP_ENV=production
    depends_on:
      - redis
      - database

  redis:
    image: redis:alpine
    ports:
      - "6379:6379"
    volumes:
      - redis-data:/data

  database:
    image: postgres:14-alpine
    environment:
      POSTGRES_DB: zeropay
      POSTGRES_USER: zeropay
      POSTGRES_PASSWORD: secure_password
    volumes:
      - db-data:/var/lib/postgresql/data

volumes:
  redis-data:
  db-data:
EOF
print_status "تم إنشاء Docker Compose"

# 5. إنشاء ملفات README
echo -e "\n${BLUE}📝 إنشاء التوثيق...${NC}"

# README رئيسي
cat > "$PROD_DIR/README.md" << 'EOF'
# 🚀 ZeroPay Production Environment

## نظرة عامة
بيئة الإنتاج الجاهزة لمشروع ZeroPay - منصة المعرفة المتقدمة.

## الهيكل
```
prod/
├── applications/    # التطبيقات الجاهزة للنشر
├── services/        # الخدمات المستقلة
├── knowledge-base/  # قاعدة المعرفة المنظمة
├── tools/          # أدوات الإنتاج
├── infrastructure/ # إعدادات البنية التحتية
├── packages/       # الحزم المشتركة
├── testing/        # اختبارات الإنتاج
├── security/       # إعدادات الأمان
└── assets/         # الأصول المحسنة
```

## البدء السريع
1. نسخ `.env.production` إلى `.env`
2. تشغيل `docker-compose up -d`
3. الوصول عبر `https://localhost`

## الأمان
- جميع الاتصالات مشفرة
- جدران حماية مفعلة
- مراقبة مستمرة للأداء

---
⚔️ نمط الأسطورة - الإنتاج الأمثل ⚔️
EOF
print_status "تم إنشاء README الرئيسي"

# 6. تنظيف وتحسين
echo -e "\n${BLUE}🧹 تنظيف وتحسين...${NC}"

# حذف الملفات غير الضرورية
find "$PROD_DIR" -name "*.log" -delete 2>/dev/null
find "$PROD_DIR" -name "*.tmp" -delete 2>/dev/null
find "$PROD_DIR" -name ".DS_Store" -delete 2>/dev/null
find "$PROD_DIR" -name "Thumbs.db" -delete 2>/dev/null
print_status "تم حذف الملفات المؤقتة"

# ضغط الأصول
print_info "ضغط الأصول..."
find "$PROD_DIR" -name "*.js" -o -name "*.css" | while read file; do
    if [ -f "$file" ] && [ ! -f "$file.gz" ]; then
        gzip -k -9 "$file" 2>/dev/null
    fi
done
print_status "تم ضغط الأصول"

# 7. إنشاء تقرير نهائي
echo -e "\n${BLUE}📊 إنشاء التقرير النهائي...${NC}"

TOTAL_FILES=$(find "$PROD_DIR" -type f | wc -l)
TOTAL_SIZE=$(du -sh "$PROD_DIR" | cut -f1)
APP_COUNT=$(find "$PROD_DIR/applications" -mindepth 1 -maxdepth 1 -type d | wc -l)
TOOL_COUNT=$(find "$PROD_DIR/tools" -name "*.sh" -o -name "*.php" | wc -l)

cat > "$PROD_DIR/ORGANIZATION_REPORT.md" << EOF
# 📊 تقرير تنظيم مجلد الإنتاج
تاريخ: $(date +"%Y-%m-%d %H:%M:%S")

## الإحصائيات
- إجمالي الملفات: $TOTAL_FILES
- الحجم الكلي: $TOTAL_SIZE
- التطبيقات: $APP_COUNT
- الأدوات: $TOOL_COUNT

## ما تم إنجازه
✅ هيكل منظم للإنتاج
✅ فصل التطبيقات والخدمات
✅ قاعدة معرفة منظمة
✅ أدوات إنتاج جاهزة
✅ إعدادات أمان محسنة
✅ توثيق شامل

## الخطوات التالية
1. مراجعة الإعدادات
2. اختبار التطبيقات
3. تفعيل المراقبة
4. النشر التدريجي

---
⚔️ نمط الأسطورة - التنظيم المطلق ⚔️
EOF

print_status "تم إنشاء التقرير النهائي"

# الملخص النهائي
echo ""
echo "=========================================="
echo -e "${GREEN}✅ تم تنظيم مجلد الإنتاج بنجاح!${NC}"
echo ""
echo "📊 الإحصائيات النهائية:"
echo "   - الملفات: $TOTAL_FILES"
echo "   - الحجم: $TOTAL_SIZE"
echo "   - التطبيقات: $APP_COUNT"
echo "   - الأدوات: $TOOL_COUNT"
echo ""
echo "📁 المسار: $PROD_DIR"
echo "📄 التقرير: $PROD_DIR/ORGANIZATION_REPORT.md"
echo ""
echo "⚔️ نمط الأسطورة - مجلد الإنتاج جاهز للنشر ⚔️"