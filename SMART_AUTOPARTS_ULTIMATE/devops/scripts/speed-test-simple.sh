#!/bin/bash

# Simple Speed Test - اختبار سرعة مبسط
# يعمل بدون الحاجة لـ bc

echo "=== اختبار السرعة الشامل - $(date '+%Y-%m-%d %H:%M:%S') ==="
echo ""

# قياس الوقت بطريقة بسيطة
time_start() {
    START_TIME=$(date +%s)
}

time_end() {
    END_TIME=$(date +%s)
    ELAPSED=$((END_TIME - START_TIME))
    echo "$ELAPSED"
}

echo "🔍 1. اختبار سرعة البحث"
echo "------------------------"

# البحث السريع
echo -n "البحث عن 'function' في جميع الملفات: "
time_start
COUNT=$(grep -r "function" /workspace 2>/dev/null | wc -l)
TIME=$(time_end)
echo "$TIME ثانية (وجد $COUNT نتيجة)"

echo ""
echo "📁 2. اختبار سرعة الوصول للملفات"
echo "------------------------------------"

echo -n "عد جميع الملفات: "
time_start
FILE_COUNT=$(find /workspace -type f 2>/dev/null | wc -l)
TIME=$(time_end)
echo "$TIME ثانية (المجموع: $FILE_COUNT ملف)"

echo -n "عد ملفات PHP: "
time_start
PHP_COUNT=$(find /workspace -name "*.php" -type f 2>/dev/null | wc -l)
TIME=$(time_end)
echo "$TIME ثانية (المجموع: $PHP_COUNT ملف)"

echo ""
echo "💾 3. اختبار قاعدة البيانات"
echo "---------------------------"

# SQLite
DB="/tmp/test.db"
echo -n "إنشاء وملء جدول بـ 1000 سجل: "
time_start
sqlite3 $DB "CREATE TABLE test (id INTEGER PRIMARY KEY, data TEXT);" 2>/dev/null
for i in {1..1000}; do
    sqlite3 $DB "INSERT INTO test (data) VALUES ('test data $i');" 2>/dev/null
done
TIME=$(time_end)
echo "$TIME ثانية"

echo -n "البحث في الجدول: "
time_start
RESULTS=$(sqlite3 $DB "SELECT COUNT(*) FROM test WHERE data LIKE '%test%';" 2>/dev/null)
TIME=$(time_end)
echo "$TIME ثانية (النتائج: $RESULTS)"
rm -f $DB

echo ""
echo "🧠 4. اختبار التخزين المؤقت"
echo "------------------------------"

php -r '
require_once "/workspace/system/scripts/cache-manager.php";
$cache = new CacheManager();

// كتابة
$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    $cache->set("key_$i", "value_$i");
}
$write_time = round(microtime(true) - $start, 3);

// قراءة
$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    $cache->get("key_$i");
}
$read_time = round(microtime(true) - $start, 3);

echo "كتابة 100 مفتاح: $write_time ثانية\n";
echo "قراءة 100 مفتاح: $read_time ثانية\n";

$stats = $cache->getStats();
echo "معدل الإصابة: " . $stats["hit_rate"] . "\n";
'

echo ""
echo "⚡ 5. اختبار أداء PHP"
echo "----------------------"

php -r '
// اختبار حلقة
$start = microtime(true);
$sum = 0;
for ($i = 0; $i < 100000; $i++) {
    $sum += $i;
}
$loop_time = round(microtime(true) - $start, 3);

// اختبار معالجة نصوص
$start = microtime(true);
$text = str_repeat("Hello World ", 1000);
$result = strtoupper($text);
$result = strtolower($result);
$string_time = round(microtime(true) - $start, 3);

echo "حلقة 100K تكرار: $loop_time ثانية\n";
echo "معالجة النصوص: $string_time ثانية\n";
'

echo ""
echo "🔧 6. اختبار Git"
echo "-----------------"

echo -n "Git status: "
time_start
git -C /workspace status --porcelain 2>/dev/null | wc -l
TIME=$(time_end)
echo "$TIME ثانية"

echo -n "Git log (آخر 50): "
time_start
git -C /workspace log --oneline -50 2>/dev/null | wc -l
TIME=$(time_end)
echo "$TIME ثانية"

echo ""
echo "💻 7. موارد النظام"
echo "-------------------"

# CPU
CPU_IDLE=$(top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | cut -d'.' -f1)
CPU_USAGE=$((100 - CPU_IDLE))
echo "استخدام المعالج: $CPU_USAGE%"

# Memory
MEM_TOTAL=$(free -m | grep "^Mem" | awk '{print $2}')
MEM_USED=$(free -m | grep "^Mem" | awk '{print $3}')
MEM_PERCENT=$((MEM_USED * 100 / MEM_TOTAL))
echo "استخدام الذاكرة: $MEM_PERCENT% ($MEM_USED MB / $MEM_TOTAL MB)"

# Disk
DISK_USAGE=$(df -h /workspace | tail -1 | awk '{print $5}')
echo "استخدام القرص: $DISK_USAGE"

# Processes
PROC_COUNT=$(ps aux | wc -l)
echo "عدد العمليات: $PROC_COUNT"

echo ""
echo "📊 8. اختبار سريع للأدوات المثبتة"
echo "------------------------------------"

# ripgrep
if command -v rg &> /dev/null; then
    echo -n "✅ ripgrep: "
    time_start
    rg --version | head -1
    TIME=$(time_end)
else
    echo "❌ ripgrep غير مثبت"
fi

# pnpm
if command -v pnpm &> /dev/null; then
    echo -n "✅ pnpm: "
    pnpm --version
else
    echo "❌ pnpm غير مثبت"
fi

# turbo
if [ -f /workspace/node_modules/.bin/turbo ]; then
    echo "✅ turborepo: مثبت"
else
    echo "❌ turborepo غير مثبت"
fi

echo ""
echo "🏁 9. ملخص الأداء"
echo "==================="

# تقييم بسيط
SCORE=0

# البحث
if [ "$FILE_COUNT" -gt 0 ]; then
    if [ $TIME -lt 5 ]; then
        echo "✅ سرعة البحث: ممتازة"
        SCORE=$((SCORE + 3))
    elif [ $TIME -lt 10 ]; then
        echo "⚡ سرعة البحث: جيدة"
        SCORE=$((SCORE + 2))
    else
        echo "⚠️ سرعة البحث: بطيئة"
        SCORE=$((SCORE + 1))
    fi
fi

# الموارد
if [ $CPU_USAGE -lt 30 ]; then
    echo "✅ استخدام المعالج: ممتاز"
    SCORE=$((SCORE + 3))
elif [ $CPU_USAGE -lt 60 ]; then
    echo "⚡ استخدام المعالج: جيد"
    SCORE=$((SCORE + 2))
else
    echo "⚠️ استخدام المعالج: مرتفع"
    SCORE=$((SCORE + 1))
fi

# الذاكرة
if [ $MEM_PERCENT -lt 50 ]; then
    echo "✅ استخدام الذاكرة: ممتاز"
    SCORE=$((SCORE + 3))
elif [ $MEM_PERCENT -lt 75 ]; then
    echo "⚡ استخدام الذاكرة: جيد"
    SCORE=$((SCORE + 2))
else
    echo "⚠️ استخدام الذاكرة: مرتفع"
    SCORE=$((SCORE + 1))
fi

echo ""
echo "النقاط: $SCORE/9"

if [ $SCORE -ge 8 ]; then
    echo "🏆 التقييم النهائي: ممتاز!"
elif [ $SCORE -ge 6 ]; then
    echo "✅ التقييم النهائي: جيد جداً"
elif [ $SCORE -ge 4 ]; then
    echo "⚡ التقييم النهائي: جيد"
else
    echo "⚠️ التقييم النهائي: يحتاج تحسين"
fi

echo ""
echo "=== انتهى الاختبار - $(date '+%Y-%m-%d %H:%M:%S') ==="