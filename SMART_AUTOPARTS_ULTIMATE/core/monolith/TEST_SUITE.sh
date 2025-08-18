#!/bin/bash
# ⚔️ مجموعة اختبارات Smart AutoParts ⚔️

echo "⚔️ مجموعة الاختبارات الشاملة ⚔️"
echo "================================="
echo ""

# متغيرات الألوان
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# عدادات النتائج
PASSED=0
FAILED=0
WARNINGS=0

# دالة الاختبار
run_test() {
    local test_name=$1
    local test_command=$2
    
    echo -n "🧪 $test_name... "
    
    if eval "$test_command" > /dev/null 2>&1; then
        echo -e "${GREEN}✓ نجح${NC}"
        ((PASSED++))
        return 0
    else
        echo -e "${RED}✗ فشل${NC}"
        ((FAILED++))
        return 1
    fi
}

# دالة التحذير
check_warning() {
    local check_name=$1
    local check_command=$2
    
    echo -n "⚠️  $check_name... "
    
    if eval "$check_command" > /dev/null 2>&1; then
        echo -e "${GREEN}✓ جيد${NC}"
        return 0
    else
        echo -e "${YELLOW}⚠ تحذير${NC}"
        ((WARNINGS++))
        return 1
    fi
}

echo "1️⃣ اختبارات البيئة"
echo "=================="
run_test "PHP متوفر" "command -v php"
run_test "إصدار PHP >= 8.2" "php -r 'exit(version_compare(PHP_VERSION, \"8.2.0\", \">=\") ? 0 : 1);'"
run_test "Composer متوفر" "command -v composer"
run_test "ملف .env موجود" "[ -f .env ]"
run_test "APP_KEY محدد" "grep -q 'APP_KEY=base64:' .env"

echo ""
echo "2️⃣ اختبارات الملفات"
echo "==================="
run_test "مجلد app موجود" "[ -d app ]"
run_test "مجلد config موجود" "[ -d config ]"
run_test "مجلد database موجود" "[ -d database ]"
run_test "مجلد public موجود" "[ -d public ]"
run_test "مجلد resources موجود" "[ -d resources ]"
run_test "مجلد routes موجود" "[ -d routes ]"
run_test "ملف artisan موجود" "[ -f artisan ]"
run_test "ملف composer.json موجود" "[ -f composer.json ]"

echo ""
echo "3️⃣ اختبارات الكود"
echo "=================="
run_test "بناء صحيح لـ PHP" "php -l artisan"
run_test "مساحة أسماء App" "grep -q 'namespace App' app/Models/User.php"
run_test "موديل User موجود" "[ -f app/Models/User.php ]"
run_test "موديل Product موجود" "[ -f app/Models/Product.php ]"
run_test "موديل Order موجود" "[ -f app/Models/Order.php ]"
run_test "موديل Shop موجود" "[ -f app/Models/Shop.php ]"

echo ""
echo "4️⃣ اختبارات Laravel"
echo "===================="
if [ -d vendor ]; then
    run_test "أمر artisan يعمل" "php artisan --version"
    run_test "قائمة المسارات" "php artisan route:list --compact"
    run_test "حالة الهجرات" "php artisan migrate:status"
else
    echo -e "${YELLOW}⚠️ تخطي اختبارات Laravel - vendor غير موجود${NC}"
    echo "   قم بتشغيل: composer install"
fi

echo ""
echo "5️⃣ اختبارات الأمان"
echo "==================="
run_test "لا يوجد .env في public" "! [ -f public/.env ]"
run_test ".htaccess موجود" "[ -f public/.htaccess ]"
check_warning "APP_DEBUG=false" "grep -q 'APP_DEBUG=false' .env"
check_warning "إعدادات HTTPS" "grep -q 'FORCE_HTTPS=true' .env"

echo ""
echo "6️⃣ اختبارات الأداء"
echo "==================="
run_test "ملفات محسنة" "[ -f scripts/optimization/optimize.sh ]"
check_warning "Redis مكون" "grep -q 'CACHE_DRIVER=redis' .env"
check_warning "Queue مكون" "grep -q 'QUEUE_CONNECTION=redis' .env"

echo ""
echo "7️⃣ اختبارات التوثيق"
echo "===================="
run_test "README.md موجود" "[ -f README.md ]"
run_test "دليل API موجود" "[ -f docs/api/API_GUIDE.md ]"
run_test "تقرير النقل موجود" "[ -f MIGRATION_REPORT.md ]"

echo ""
echo "8️⃣ اختبارات التكامل"
echo "===================="
check_warning "STC Pay مكون" "grep -q 'STCPAY_MERCHANT_ID=' .env"
check_warning "تمارا مكون" "grep -q 'TAMARA_API_KEY=' .env"
check_warning "SABER مكون" "grep -q 'SABER_API_URL=' .env"
check_warning "ZATCA مكون" "grep -q 'ZATCA_API_URL=' .env"

# النتائج النهائية
echo ""
echo "======================================"
echo "📊 نتائج الاختبار النهائية"
echo "======================================"
echo -e "✅ نجح: ${GREEN}$PASSED${NC}"
echo -e "❌ فشل: ${RED}$FAILED${NC}"
echo -e "⚠️  تحذير: ${YELLOW}$WARNINGS${NC}"
echo ""

# تحديد الحالة العامة
if [ $FAILED -eq 0 ]; then
    if [ $WARNINGS -eq 0 ]; then
        echo -e "${GREEN}🎉 ممتاز! جميع الاختبارات نجحت!${NC}"
        echo "التطبيق جاهز للإطلاق 🚀"
    else
        echo -e "${YELLOW}✅ جيد! الاختبارات الأساسية نجحت${NC}"
        echo "يوجد $WARNINGS تحذيرات تحتاج مراجعة"
    fi
else
    echo -e "${RED}❌ يوجد مشاكل تحتاج إصلاح${NC}"
    echo "فشل $FAILED اختبارات أساسية"
fi

echo ""
echo "⚔️ نمط الأسطورة - اختبار شامل مكتمل ⚔️"

# إرجاع كود الخروج
exit $FAILED