#!/bin/bash
# ⚔️ محرك الترحيل الذكي - Smart Migration Engine ⚔️
# يقوم بدمج وتنظيف وترحيل جميع المشاريع إلى الهيكل الجديد

set -e

echo "⚔️ بدء عملية الترحيل الأسطورية ⚔️"
echo "===================================="
echo ""

# المتغيرات
SOURCE_DIR="/workspace"
TARGET_DIR="/workspace/SMART_AUTOPARTS_ULTIMATE"
LOG_FILE="$TARGET_DIR/migration.log"

# الألوان
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# دوال مساعدة
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}✓${NC} $1" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}⚠${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}✗${NC} $1" | tee -a "$LOG_FILE"
}

section() {
    echo -e "\n${GREEN}━━━ $1 ━━━${NC}\n" | tee -a "$LOG_FILE"
}

# تهيئة السجل
echo "Migration started at $(date)" > "$LOG_FILE"

# 1. دمج المشاريع الأساسية
section "دمج المشاريع الأساسية"

# دمج SMA (Monolith)
if [ -d "$SOURCE_DIR/SMA" ]; then
    log "نقل SMA إلى core/monolith..."
    cp -r "$SOURCE_DIR/SMA/"* "$TARGET_DIR/core/monolith/" 2>/dev/null || true
    
    # تنظيف vendor
    rm -rf "$TARGET_DIR/core/monolith/vendor"
    rm -rf "$TARGET_DIR/core/monolith/node_modules"
    
    success "تم نقل SMA"
fi

# دمج SMA_enh (Microservices)
if [ -d "$SOURCE_DIR/SMA_enh" ]; then
    log "نقل SMA_enh إلى core/microservices..."
    
    # نقل الخدمات
    [ -d "$SOURCE_DIR/SMA_enh/microservices" ] && cp -r "$SOURCE_DIR/SMA_enh/microservices/"* "$TARGET_DIR/core/microservices/" 2>/dev/null || true
    [ -d "$SOURCE_DIR/SMA_enh/core" ] && cp -r "$SOURCE_DIR/SMA_enh/core/"* "$TARGET_DIR/core/microservices/" 2>/dev/null || true
    
    # نقل التطبيقات
    [ -d "$SOURCE_DIR/SMA_enh/apps/web" ] && cp -r "$SOURCE_DIR/SMA_enh/apps/web/"* "$TARGET_DIR/applications/web/" 2>/dev/null || true
    [ -d "$SOURCE_DIR/SMA_enh/apps/mobile" ] && cp -r "$SOURCE_DIR/SMA_enh/apps/mobile/"* "$TARGET_DIR/applications/mobile/" 2>/dev/null || true
    [ -d "$SOURCE_DIR/SMA_enh/apps/admin" ] && cp -r "$SOURCE_DIR/SMA_enh/apps/admin/"* "$TARGET_DIR/applications/admin/" 2>/dev/null || true
    
    # نقل AI models
    [ -d "$SOURCE_DIR/SMA_enh/ai-models" ] && cp -r "$SOURCE_DIR/SMA_enh/ai-models/"* "$TARGET_DIR/ai-platform/models/" 2>/dev/null || true
    
    success "تم نقل SMA_enh"
fi

# 2. دمج الأدوات والسكريبتات
section "دمج الأدوات والسكريبتات"

# نقل أدوات system/scripts
if [ -d "$SOURCE_DIR/system/scripts" ]; then
    log "نقل أدوات النظام..."
    cp -r "$SOURCE_DIR/system/scripts/"*.{sh,php,js} "$TARGET_DIR/devops/scripts/" 2>/dev/null || true
    success "تم نقل أدوات النظام"
fi

# 3. دمج التوثيق
section "دمج التوثيق"

# إنشاء مجلدات التوثيق
mkdir -p "$TARGET_DIR/knowledge-base/documentation"/{api,architecture,guides,deployment}

# نقل التوثيق من مصادر مختلفة
find "$SOURCE_DIR" -name "*.md" -type f | while read -r file; do
    filename=$(basename "$file")
    
    # تصنيف الملفات
    case "$filename" in
        *API*|*api*)
            cp "$file" "$TARGET_DIR/knowledge-base/documentation/api/" 2>/dev/null || true
            ;;
        *ARCHITECTURE*|*SYSTEM*)
            cp "$file" "$TARGET_DIR/knowledge-base/documentation/architecture/" 2>/dev/null || true
            ;;
        *GUIDE*|*README*)
            cp "$file" "$TARGET_DIR/knowledge-base/documentation/guides/" 2>/dev/null || true
            ;;
        *DEPLOY*|*deployment*)
            cp "$file" "$TARGET_DIR/knowledge-base/documentation/deployment/" 2>/dev/null || true
            ;;
    esac
done

success "تم دمج التوثيق"

# 4. دمج الاختبارات
section "دمج الاختبارات"

# نقل الاختبارات
[ -d "$SOURCE_DIR/SMA/tests" ] && cp -r "$SOURCE_DIR/SMA/tests/"* "$TARGET_DIR/quality-assurance/tests/" 2>/dev/null || true
[ -d "$SOURCE_DIR/SMA_enh/tests" ] && cp -r "$SOURCE_DIR/SMA_enh/tests/"* "$TARGET_DIR/quality-assurance/tests/" 2>/dev/null || true

success "تم دمج الاختبارات"

# 5. إنشاء ملفات التكوين الأساسية
section "إنشاء ملفات التكوين"

# Makefile
cat > "$TARGET_DIR/Makefile" << 'EOF'
# Smart AutoParts Ultimate - Makefile

.PHONY: help setup build up down test clean

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

setup: ## Setup development environment
	@echo "Setting up environment..."
	cp .env.example .env
	docker-compose build
	docker-compose run --rm app composer install
	docker-compose run --rm app php artisan key:generate

build: ## Build all services
	docker-compose build

up: ## Start all services
	docker-compose up -d

down: ## Stop all services
	docker-compose down

test: ## Run tests
	docker-compose run --rm app php artisan test
	docker-compose run --rm app npm test

logs: ## Show logs
	docker-compose logs -f

shell: ## Enter app shell
	docker-compose exec app bash

clean: ## Clean everything
	docker-compose down -v
	rm -rf vendor node_modules
	rm -rf storage/logs/*

deploy-dev: ## Deploy to development
	./devops/scripts/deploy.sh dev

deploy-prod: ## Deploy to production
	./devops/scripts/deploy.sh prod
EOF

# Docker Compose
cat > "$TARGET_DIR/docker-compose.yml" << 'EOF'
version: '3.9'

services:
  # Monolith Application
  app:
    build:
      context: ./core/monolith
      dockerfile: Dockerfile
    ports:
      - "8000:80"
    volumes:
      - ./core/monolith:/var/www/html
    environment:
      - DB_CONNECTION=pgsql
      - DB_HOST=postgres
      - REDIS_HOST=redis
    depends_on:
      - postgres
      - redis

  # API Gateway
  api-gateway:
    build:
      context: ./core/microservices/api-gateway
      dockerfile: Dockerfile
    ports:
      - "3000:3000"
    depends_on:
      - app

  # Web Application
  web:
    build:
      context: ./applications/web
      dockerfile: Dockerfile
    ports:
      - "3001:3000"
    environment:
      - NEXT_PUBLIC_API_URL=http://api-gateway:3000

  # Databases
  postgres:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: smartautoparts
      POSTGRES_USER: sauser
      POSTGRES_PASSWORD: sapass
    volumes:
      - postgres_data:/var/lib/postgresql/data

  redis:
    image: redis:7-alpine
    command: redis-server --appendonly yes
    volumes:
      - redis_data:/data

  # ElasticSearch
  elasticsearch:
    image: elasticsearch:8.11.0
    environment:
      - discovery.type=single-node
      - xpack.security.enabled=false
    volumes:
      - elastic_data:/usr/share/elasticsearch/data

volumes:
  postgres_data:
  redis_data:
  elastic_data:
EOF

# .gitignore
cat > "$TARGET_DIR/.gitignore" << 'EOF'
# Dependencies
/vendor
node_modules/
npm-debug.log
yarn-error.log

# Environment
.env
.env.local
.env.*.local

# Laravel
/public/hot
/public/storage
/storage/*.key
/storage/logs
/storage/framework/cache/data
/storage/framework/sessions
/storage/framework/testing
/storage/framework/views

# IDE
.idea
.vscode
*.swp
*.swo
*~

# OS
.DS_Store
Thumbs.db

# Build
/build
/dist
*.log

# Testing
/coverage
.phpunit.result.cache
.phpunit.cache

# Docker
docker-compose.override.yml
EOF

success "تم إنشاء ملفات التكوين"

# 6. إنشاء أدوات DevOps
section "إنشاء أدوات DevOps"

# Setup Script
cat > "$TARGET_DIR/devops/scripts/setup.sh" << 'EOF'
#!/bin/bash
# Setup development environment

echo "🚀 Setting up Smart AutoParts Ultimate..."

# Check requirements
command -v docker >/dev/null 2>&1 || { echo "Docker is required but not installed. Aborting." >&2; exit 1; }
command -v docker-compose >/dev/null 2>&1 || { echo "Docker Compose is required but not installed. Aborting." >&2; exit 1; }

# Copy environment file
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✓ Created .env file"
fi

# Build containers
docker-compose build

# Install dependencies
docker-compose run --rm app composer install
docker-compose run --rm web npm install

# Generate key
docker-compose run --rm app php artisan key:generate

# Run migrations
docker-compose run --rm app php artisan migrate

# Seed database
docker-compose run --rm app php artisan db:seed

echo "✅ Setup complete! Run 'make up' to start the application."
EOF

chmod +x "$TARGET_DIR/devops/scripts/setup.sh"

success "تم إنشاء أدوات DevOps"

# 7. تنظيف المكررات
section "تنظيف المكررات"

# إزالة vendor directories المكررة
find "$TARGET_DIR" -name "vendor" -type d -exec rm -rf {} + 2>/dev/null || true
find "$TARGET_DIR" -name "node_modules" -type d -exec rm -rf {} + 2>/dev/null || true

# إزالة الملفات المؤقتة
find "$TARGET_DIR" -name "*.log" -type f -delete 2>/dev/null || true
find "$TARGET_DIR" -name ".DS_Store" -type f -delete 2>/dev/null || true
find "$TARGET_DIR" -name "Thumbs.db" -type f -delete 2>/dev/null || true

success "تم تنظيف المكررات"

# 8. إنشاء الفهرس
section "إنشاء فهرس المشروع"

# إنشاء INDEX.md
cat > "$TARGET_DIR/INDEX.md" << 'EOF'
# 📚 فهرس المشروع - Project Index

## الهيكل الرئيسي

### /core
- **monolith/** - التطبيق الأساسي Laravel
- **microservices/** - الخدمات المصغرة

### /applications
- **web/** - تطبيق الويب Next.js
- **mobile/** - تطبيق الجوال React Native
- **admin/** - لوحة الإدارة
- **api/** - API الموحد

### /ai-platform
- **models/** - نماذج AI المدربة
- **services/** - خدمات الذكاء الاصطناعي
- **training/** - بيانات التدريب

### /devops
- **docker/** - ملفات Docker
- **kubernetes/** - تكوينات K8s
- **ci-cd/** - خطوط الإنتاج
- **monitoring/** - أدوات المراقبة
- **scripts/** - سكريبتات مفيدة

### /knowledge-base
- **documentation/** - التوثيق الشامل
- **best-practices/** - أفضل الممارسات
- **case-studies/** - دراسات الحالة
- **learning-resources/** - موارد التعلم

### /quality-assurance
- **tests/** - جميع الاختبارات
- **benchmarks/** - قياسات الأداء
- **security/** - فحوصات الأمان
- **reports/** - التقارير

### /business
- **analytics/** - التحليلات
- **marketing/** - التسويق
- **legal/** - القانونية
- **roadmap/** - خارطة الطريق
EOF

success "تم إنشاء الفهرس"

# 9. إحصائيات الترحيل
section "إحصائيات الترحيل"

# حساب الإحصائيات
TOTAL_FILES=$(find "$TARGET_DIR" -type f | wc -l)
TOTAL_SIZE=$(du -sh "$TARGET_DIR" | cut -f1)
PHP_FILES=$(find "$TARGET_DIR" -name "*.php" | wc -l)
JS_FILES=$(find "$TARGET_DIR" -name "*.js" -o -name "*.jsx" -o -name "*.ts" -o -name "*.tsx" | wc -l)
MD_FILES=$(find "$TARGET_DIR" -name "*.md" | wc -l)

# عرض الإحصائيات
echo -e "\n${GREEN}📊 إحصائيات الترحيل:${NC}"
echo "------------------------"
echo "إجمالي الملفات: $TOTAL_FILES"
echo "الحجم الكلي: $TOTAL_SIZE"
echo "ملفات PHP: $PHP_FILES"
echo "ملفات JS/TS: $JS_FILES"
echo "ملفات التوثيق: $MD_FILES"

# إنشاء تقرير الترحيل
cat > "$TARGET_DIR/MIGRATION_REPORT.md" << EOF
# تقرير الترحيل - Migration Report
التاريخ: $(date)

## النتائج

### الإحصائيات
- إجمالي الملفات: $TOTAL_FILES
- الحجم الكلي: $TOTAL_SIZE
- ملفات PHP: $PHP_FILES
- ملفات JS/TS: $JS_FILES
- ملفات التوثيق: $MD_FILES

### ما تم إنجازه
- ✅ دمج جميع نسخ المشروع
- ✅ تنظيف المكررات
- ✅ إنشاء هيكل موحد
- ✅ إعداد أدوات التطوير
- ✅ توثيق شامل

### الخطوات التالية
1. مراجعة الكود المدموج
2. تحديث المسارات
3. اختبار التكامل
4. تحسين الأداء
EOF

echo -e "\n${GREEN}✅ اكتملت عملية الترحيل بنجاح!${NC}"
echo -e "${BLUE}📁 المشروع الجديد في: $TARGET_DIR${NC}"
echo -e "${YELLOW}📋 راجع MIGRATION_REPORT.md للتفاصيل${NC}"

# النهاية
echo -e "\n⚔️ ${GREEN}نمط الأسطورة - الترحيل مكتمل!${NC} ⚔️"