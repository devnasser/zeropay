#!/bin/bash

# حساب السعة القصوى الممكنة للسرب
# Maximum Swarm Capacity Calculator

echo "╔══════════════════════════════════════════════════════════════╗"
echo "║          🚀 حساب السعة القصوى للسرب                        ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# جمع معلومات النظام
CPU_CORES=$(nproc)
TOTAL_MEM=$(free -m | awk '/^Mem:/{print $2}')
AVAILABLE_MEM=$(free -m | awk '/^Mem:/{print $7}')
LOAD_AVG=$(uptime | awk -F'load average:' '{print $2}' | awk -F',' '{print $1}' | xargs)
LOAD_PERCENT=$(echo "scale=2; ($LOAD_AVG / $CPU_CORES) * 100" | bc 2>/dev/null || echo "10")

# معلومات القرص
DISK_INFO=$(df -h /workspace | tail -1)
DISK_TOTAL=$(echo $DISK_INFO | awk '{print $2}')
DISK_AVAIL=$(echo $DISK_INFO | awk '{print $4}')

echo "📊 موارد النظام الحالية:"
echo "════════════════════════════════════════"
echo "• المعالج: $CPU_CORES أنوية"
echo "• الذاكرة الكلية: ${TOTAL_MEM}MB"
echo "• الذاكرة المتاحة: ${AVAILABLE_MEM}MB"
echo "• حمل النظام: ${LOAD_PERCENT:-10}%"
echo "• القرص المتاح: $DISK_AVAIL"
echo ""

# حساب السعة القصوى بناءً على عوامل مختلفة
echo "📈 حساب السعة القصوى:"
echo "════════════════════════════════════════"

# 1. بناءً على المعالج (50-100 وحدة لكل نواة)
MAX_CPU_CONSERVATIVE=$((CPU_CORES * 50))
MAX_CPU_AGGRESSIVE=$((CPU_CORES * 100))
echo "• بناءً على المعالج:"
echo "  - محافظ (50/نواة): $MAX_CPU_CONSERVATIVE وحدة"
echo "  - متوسط (75/نواة): $((CPU_CORES * 75)) وحدة"
echo "  - جريء (100/نواة): $MAX_CPU_AGGRESSIVE وحدة"

# 2. بناءً على الذاكرة (20-50MB لكل وحدة)
MAX_MEM_CONSERVATIVE=$((AVAILABLE_MEM / 50))
MAX_MEM_AGGRESSIVE=$((AVAILABLE_MEM / 20))
echo ""
echo "• بناءً على الذاكرة:"
echo "  - محافظ (50MB/وحدة): $MAX_MEM_CONSERVATIVE وحدة"
echo "  - متوسط (35MB/وحدة): $((AVAILABLE_MEM / 35)) وحدة"
echo "  - جريء (20MB/وحدة): $MAX_MEM_AGGRESSIVE وحدة"

# 3. بناءً على الحمل الحالي
if [ -n "$LOAD_PERCENT" ]; then
    LOAD_FACTOR=$((100 - ${LOAD_PERCENT%.*}))
    MAX_LOAD=$((LOAD_FACTOR * 3))
    echo ""
    echo "• بناءً على الحمل الحالي:"
    echo "  - عامل الحمل: ${LOAD_FACTOR}%"
    echo "  - السعة المتاحة: $MAX_LOAD وحدة"
fi

echo ""
echo "🎯 التوصيات:"
echo "════════════════════════════════════════"

# حساب السعة المثلى
OPTIMAL_CPU=$((CPU_CORES * 75))
OPTIMAL_MEM=$((AVAILABLE_MEM / 30))
OPTIMAL_LOAD=${MAX_LOAD:-200}

# اختيار الأقل
if [ $OPTIMAL_CPU -le $OPTIMAL_MEM ] && [ $OPTIMAL_CPU -le $OPTIMAL_LOAD ]; then
    OPTIMAL=$OPTIMAL_CPU
    LIMITING="المعالج"
elif [ $OPTIMAL_MEM -le $OPTIMAL_LOAD ]; then
    OPTIMAL=$OPTIMAL_MEM
    LIMITING="الذاكرة"
else
    OPTIMAL=$OPTIMAL_LOAD
    LIMITING="حمل النظام"
fi

# السعة القصوى المطلقة
ABSOLUTE_MAX=$((CPU_CORES * 100))
if [ $((AVAILABLE_MEM / 20)) -lt $ABSOLUTE_MAX ]; then
    ABSOLUTE_MAX=$((AVAILABLE_MEM / 20))
fi

echo "• السعة المثلى الموصى بها: $OPTIMAL وحدة"
echo "• العامل المحدد: $LIMITING"
echo "• السعة القصوى المطلقة: $ABSOLUTE_MAX وحدة"
echo ""

# توزيع مقترح للسعة القصوى
echo "📊 التوزيع المقترح للسعة القصوى ($ABSOLUTE_MAX وحدة):"
echo "════════════════════════════════════════"
echo "• التحليل (20%): $((ABSOLUTE_MAX * 20 / 100)) وحدة"
echo "• التطوير (30%): $((ABSOLUTE_MAX * 30 / 100)) وحدة"
echo "• الاختبار (20%): $((ABSOLUTE_MAX * 20 / 100)) وحدة"
echo "• التحسين (15%): $((ABSOLUTE_MAX * 15 / 100)) وحدة"
echo "• الأتمتة (10%): $((ABSOLUTE_MAX * 10 / 100)) وحدة"
echo "• المراقبة (5%): $((ABSOLUTE_MAX * 5 / 100)) وحدة"
echo ""

# تقييم الأداء المتوقع
echo "⚡ الأداء المتوقع:"
echo "════════════════════════════════════════"
if [ $ABSOLUTE_MAX -ge 500 ]; then
    echo "✅ أداء فائق - يمكن معالجة مشاريع ضخمة بكفاءة عالية"
    echo "✅ تنفيذ متوازي ممتاز لجميع المهام"
    echo "✅ قدرة على التعامل مع أحمال ذروة عالية"
elif [ $ABSOLUTE_MAX -ge 200 ]; then
    echo "✅ أداء ممتاز - مناسب للمشاريع الكبيرة"
    echo "✅ توازي جيد جداً مع كفاءة عالية"
    echo "⚠️ قد يحتاج تحسين في أحمال الذروة"
elif [ $ABSOLUTE_MAX -ge 100 ]; then
    echo "✅ أداء جيد - مناسب للمشاريع المتوسطة"
    echo "⚠️ توازي محدود في المهام الثقيلة"
    echo "⚠️ يُنصح بتحسين الموارد للمشاريع الكبيرة"
else
    echo "⚠️ أداء محدود - مناسب للمشاريع الصغيرة"
    echo "⚠️ قدرة محدودة على التوازي"
    echo "❌ يُنصح بترقية الموارد للأداء الأمثل"
fi

echo ""
echo "💡 نصائح لزيادة السعة:"
echo "════════════════════════════════════════"
echo "1. زيادة الذاكرة RAM للنظام"
echo "2. استخدام معالج بعدد أنوية أكبر"
echo "3. تقليل العمليات الخلفية غير الضرورية"
echo "4. تحسين استخدام الذاكرة في التطبيقات"
echo "5. استخدام SSD لتحسين سرعة I/O"

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║                    ✅ اكتمل التحليل                         ║"
echo "╚══════════════════════════════════════════════════════════════╝"