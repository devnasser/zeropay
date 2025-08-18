#!/bin/bash

# Speed Test Script - اختبار السرعة الشامل
# يقيس الأداء على جميع المحاور

WORKSPACE_ROOT="/workspace"
RESULTS_FILE="$WORKSPACE_ROOT/system/logs/speed-test-results.txt"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

# الألوان
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

# إنشاء مجلد السجلات
mkdir -p "$(dirname "$RESULTS_FILE")"

# بدء التسجيل
echo "=== اختبار السرعة الشامل - $TIMESTAMP ===" | tee "$RESULTS_FILE"
echo "" | tee -a "$RESULTS_FILE"

# دالة قياس الوقت
measure_time() {
    local start=$(date +%s.%N)
    eval "$1"
    local end=$(date +%s.%N)
    echo "scale=3; $end - $start" | bc
}

echo -e "${BLUE}🔍 1. اختبار سرعة البحث${NC}" | tee -a "$RESULTS_FILE"
echo "------------------------" | tee -a "$RESULTS_FILE"

# البحث بـ ripgrep
echo -n "البحث عن 'function' بـ ripgrep: " | tee -a "$RESULTS_FILE"
RG_TIME=$(measure_time "rg 'function' $WORKSPACE_ROOT --count --no-messages 2>/dev/null | wc -l")
echo -e "${GREEN}$RG_TIME ثانية${NC}" | tee -a "$RESULTS_FILE"

# البحث بـ grep عادي للمقارنة
echo -n "البحث عن 'function' بـ grep عادي: " | tee -a "$RESULTS_FILE"
GREP_TIME=$(measure_time "grep -r 'function' $WORKSPACE_ROOT 2>/dev/null | wc -l")
echo -e "${YELLOW}$GREP_TIME ثانية${NC}" | tee -a "$RESULTS_FILE"

# حساب التحسين
SEARCH_IMPROVEMENT=$(echo "scale=2; $GREP_TIME / $RG_TIME" | bc)
echo -e "التحسين: ${GREEN}${SEARCH_IMPROVEMENT}x أسرع${NC}" | tee -a "$RESULTS_FILE"
echo "" | tee -a "$RESULTS_FILE"

echo -e "${BLUE}📁 2. اختبار سرعة الوصول للملفات${NC}" | tee -a "$RESULTS_FILE"
echo "------------------------------------" | tee -a "$RESULTS_FILE"

# قائمة الملفات بـ fd
echo -n "سرد جميع ملفات PHP: " | tee -a "$RESULTS_FILE"
FD_TIME=$(measure_time "find $WORKSPACE_ROOT -name '*.php' -type f 2>/dev/null | wc -l")
echo -e "${GREEN}$FD_TIME ثانية${NC}" | tee -a "$RESULTS_FILE"

# عد الملفات
FILE_COUNT=$(find $WORKSPACE_ROOT -type f 2>/dev/null | wc -l)
echo "إجمالي الملفات: $FILE_COUNT" | tee -a "$RESULTS_FILE"
echo "" | tee -a "$RESULTS_FILE"

echo -e "${BLUE}💾 3. اختبار سرعة قاعدة البيانات${NC}" | tee -a "$RESULTS_FILE"
echo "-----------------------------------" | tee -a "$RESULTS_FILE"

# اختبار SQLite
SQLITE_DB="$WORKSPACE_ROOT/.search-cache/test.db"
echo -n "إنشاء جدول وإدراج 10000 سجل: " | tee -a "$RESULTS_FILE"
SQLITE_INSERT_TIME=$(measure_time "sqlite3 $SQLITE_DB 'CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY, data TEXT); BEGIN; INSERT INTO test (data) VALUES (\"test\"); INSERT INTO test (data) SELECT data FROM test; INSERT INTO test (data) SELECT data FROM test; INSERT INTO test (data) SELECT data FROM test; INSERT INTO test (data) SELECT data FROM test; INSERT INTO test (data) SELECT data FROM test; INSERT INTO test (data) SELECT data FROM test; INSERT INTO test (data) SELECT data FROM test; INSERT INTO test (data) SELECT data FROM test; INSERT INTO test (data) SELECT data FROM test; INSERT INTO test (data) SELECT data FROM test; INSERT INTO test (data) SELECT data FROM test; INSERT INTO test (data) SELECT data FROM test; INSERT INTO test (data) SELECT data FROM test; COMMIT;' 2>/dev/null")
echo -e "${GREEN}$SQLITE_INSERT_TIME ثانية${NC}" | tee -a "$RESULTS_FILE"

echo -n "البحث في 10000+ سجل: " | tee -a "$RESULTS_FILE"
SQLITE_SEARCH_TIME=$(measure_time "sqlite3 $SQLITE_DB 'SELECT COUNT(*) FROM test WHERE data LIKE \"%test%\";' 2>/dev/null")
echo -e "${GREEN}$SQLITE_SEARCH_TIME ثانية${NC}" | tee -a "$RESULTS_FILE"

# تنظيف
rm -f "$SQLITE_DB"
echo "" | tee -a "$RESULTS_FILE"

echo -e "${BLUE}🧠 4. اختبار التخزين المؤقت${NC}" | tee -a "$RESULTS_FILE"
echo "------------------------------" | tee -a "$RESULTS_FILE"

# اختبار PHP Cache
cat > /tmp/cache_test.php << 'EOF'
<?php
require_once '/workspace/system/scripts/cache-manager.php';

$cache = new CacheManager();
$start = microtime(true);

// كتابة 1000 مفتاح
for ($i = 0; $i < 1000; $i++) {
    $cache->set("key_$i", "value_$i");
}

$write_time = microtime(true) - $start;
echo "كتابة 1000 مفتاح: " . round($write_time, 3) . " ثانية\n";

$start = microtime(true);

// قراءة 1000 مفتاح
for ($i = 0; $i < 1000; $i++) {
    $cache->get("key_$i");
}

$read_time = microtime(true) - $start;
echo "قراءة 1000 مفتاح: " . round($read_time, 3) . " ثانية\n";

// الإحصائيات
print_r($cache->getStats());
EOF

php /tmp/cache_test.php | tee -a "$RESULTS_FILE"
rm -f /tmp/cache_test.php
echo "" | tee -a "$RESULTS_FILE"

echo -e "${BLUE}⚡ 5. اختبار أداء PHP${NC}" | tee -a "$RESULTS_FILE"
echo "----------------------" | tee -a "$RESULTS_FILE"

# اختبار سرعة PHP
cat > /tmp/php_bench.php << 'EOF'
<?php
$start = microtime(true);
$sum = 0;
for ($i = 0; $i < 1000000; $i++) {
    $sum += $i;
}
$time = microtime(true) - $start;
echo "حلقة مليون تكرار: " . round($time, 3) . " ثانية\n";

// اختبار معالجة السلاسل
$start = microtime(true);
$text = str_repeat("Hello World ", 10000);
$result = strtoupper($text);
$result = strtolower($result);
$result = str_replace("hello", "hi", $result);
$time = microtime(true) - $start;
echo "معالجة النصوص: " . round($time, 3) . " ثانية\n";
EOF

php /tmp/php_bench.php | tee -a "$RESULTS_FILE"
rm -f /tmp/php_bench.php
echo "" | tee -a "$RESULTS_FILE"

echo -e "${BLUE}📦 6. اختبار سرعة Composer${NC}" | tee -a "$RESULTS_FILE"
echo "------------------------------" | tee -a "$RESULTS_FILE"

echo -n "تحميل autoload: " | tee -a "$RESULTS_FILE"
COMPOSER_TIME=$(measure_time "cd $WORKSPACE_ROOT && composer dump-autoload --optimize --quiet 2>/dev/null")
echo -e "${GREEN}$COMPOSER_TIME ثانية${NC}" | tee -a "$RESULTS_FILE"
echo "" | tee -a "$RESULTS_FILE"

echo -e "${BLUE}🔧 7. اختبار Git${NC}" | tee -a "$RESULTS_FILE"
echo "-----------------" | tee -a "$RESULTS_FILE"

echo -n "Git status: " | tee -a "$RESULTS_FILE"
GIT_STATUS_TIME=$(measure_time "cd $WORKSPACE_ROOT && git status --porcelain 2>/dev/null | wc -l")
echo -e "${GREEN}$GIT_STATUS_TIME ثانية${NC}" | tee -a "$RESULTS_FILE"

echo -n "Git log (آخر 100): " | tee -a "$RESULTS_FILE"
GIT_LOG_TIME=$(measure_time "cd $WORKSPACE_ROOT && git log --oneline -100 2>/dev/null | wc -l")
echo -e "${GREEN}$GIT_LOG_TIME ثانية${NC}" | tee -a "$RESULTS_FILE"
echo "" | tee -a "$RESULTS_FILE"

echo -e "${BLUE}💻 8. اختبار موارد النظام${NC}" | tee -a "$RESULTS_FILE"
echo "---------------------------" | tee -a "$RESULTS_FILE"

# استخدام CPU
CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk '{print 100 - $1}')
echo "استخدام المعالج: ${CPU_USAGE}%" | tee -a "$RESULTS_FILE"

# استخدام الذاكرة
MEM_INFO=$(free -m | grep "^Mem")
MEM_TOTAL=$(echo $MEM_INFO | awk '{print $2}')
MEM_USED=$(echo $MEM_INFO | awk '{print $3}')
MEM_PERCENT=$(echo "scale=2; $MEM_USED * 100 / $MEM_TOTAL" | bc)
echo "استخدام الذاكرة: ${MEM_PERCENT}% ($MEM_USED MB / $MEM_TOTAL MB)" | tee -a "$RESULTS_FILE"

# مساحة القرص
DISK_USAGE=$(df -h $WORKSPACE_ROOT | tail -1 | awk '{print $5}')
echo "استخدام القرص: $DISK_USAGE" | tee -a "$RESULTS_FILE"

# عدد العمليات
PROC_COUNT=$(ps aux | wc -l)
echo "عدد العمليات: $PROC_COUNT" | tee -a "$RESULTS_FILE"
echo "" | tee -a "$RESULTS_FILE"

echo -e "${BLUE}🚀 9. اختبار البناء المتوازي${NC}" | tee -a "$RESULTS_FILE"
echo "------------------------------" | tee -a "$RESULTS_FILE"

# محاكاة بناء متعدد المشاريع
echo -n "بناء متوازي (محاكاة): " | tee -a "$RESULTS_FILE"
PARALLEL_TIME=$(measure_time "
    (sleep 0.1 && echo 'Project 1 built') &
    (sleep 0.1 && echo 'Project 2 built') &
    (sleep 0.1 && echo 'Project 3 built') &
    (sleep 0.1 && echo 'Project 4 built') &
    wait
" 2>&1 | grep -c "built")
echo -e "${GREEN}$PARALLEL_TIME ثانية${NC}" | tee -a "$RESULTS_FILE"

echo -n "بناء تسلسلي (محاكاة): " | tee -a "$RESULTS_FILE"
SERIAL_TIME=$(measure_time "
    sleep 0.1 && echo 'Project 1 built'
    sleep 0.1 && echo 'Project 2 built'
    sleep 0.1 && echo 'Project 3 built'
    sleep 0.1 && echo 'Project 4 built'
" 2>&1 | grep -c "built")
echo -e "${YELLOW}$SERIAL_TIME ثانية${NC}" | tee -a "$RESULTS_FILE"
echo "" | tee -a "$RESULTS_FILE"

echo -e "${BLUE}📊 10. ملخص النتائج${NC}" | tee -a "$RESULTS_FILE"
echo "===================" | tee -a "$RESULTS_FILE"

# حساب النقاط
SCORE=0

# نقاط البحث
if (( $(echo "$RG_TIME < 0.5" | bc -l) )); then
    SEARCH_SCORE=10
elif (( $(echo "$RG_TIME < 1" | bc -l) )); then
    SEARCH_SCORE=7
else
    SEARCH_SCORE=5
fi
SCORE=$((SCORE + SEARCH_SCORE))
echo "سرعة البحث: $SEARCH_SCORE/10" | tee -a "$RESULTS_FILE"

# نقاط الموارد
if (( $(echo "$CPU_USAGE < 50" | bc -l) )); then
    RESOURCE_SCORE=10
elif (( $(echo "$CPU_USAGE < 70" | bc -l) )); then
    RESOURCE_SCORE=7
else
    RESOURCE_SCORE=5
fi
SCORE=$((SCORE + RESOURCE_SCORE))
echo "استخدام الموارد: $RESOURCE_SCORE/10" | tee -a "$RESULTS_FILE"

# نقاط Git
if (( $(echo "$GIT_STATUS_TIME < 0.5" | bc -l) )); then
    GIT_SCORE=10
elif (( $(echo "$GIT_STATUS_TIME < 1" | bc -l) )); then
    GIT_SCORE=7
else
    GIT_SCORE=5
fi
SCORE=$((SCORE + GIT_SCORE))
echo "أداء Git: $GIT_SCORE/10" | tee -a "$RESULTS_FILE"

echo "" | tee -a "$RESULTS_FILE"
echo -e "${GREEN}النقاط الإجمالية: $SCORE/30${NC}" | tee -a "$RESULTS_FILE"

# التقييم النهائي
if [ $SCORE -ge 27 ]; then
    echo -e "${GREEN}🏆 الأداء: ممتاز!${NC}" | tee -a "$RESULTS_FILE"
elif [ $SCORE -ge 21 ]; then
    echo -e "${GREEN}✅ الأداء: جيد جداً${NC}" | tee -a "$RESULTS_FILE"
elif [ $SCORE -ge 15 ]; then
    echo -e "${YELLOW}⚡ الأداء: جيد${NC}" | tee -a "$RESULTS_FILE"
else
    echo -e "${RED}⚠️ الأداء: يحتاج تحسين${NC}" | tee -a "$RESULTS_FILE"
fi

echo "" | tee -a "$RESULTS_FILE"
echo "تم حفظ النتائج في: $RESULTS_FILE" | tee -a "$RESULTS_FILE"
echo "=== انتهى الاختبار - $(date '+%Y-%m-%d %H:%M:%S') ===" | tee -a "$RESULTS_FILE"