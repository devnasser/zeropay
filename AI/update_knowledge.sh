#!/bin/bash

# 🔄 سكريبت تحديث قاعدة المعرفة
# يتم تشغيله عند إضافة معرفة جديدة

echo "⚔️ بدء تحديث قاعدة المعرفة - نمط الأسطورة ⚔️"

# تحديث التاريخ في index.json
current_date=$(date +%Y-%m-%d)
jq --arg date "$current_date" '.last_updated = $date' /workspace/AI/metadata/index.json > tmp.json && mv tmp.json /workspace/AI/metadata/index.json

# عد الملفات
total_files=$(find /workspace/AI/knowledge_base -type f | wc -l)
echo "📊 إجمالي ملفات المعرفة: $total_files"

# إنشاء تقرير
echo "📝 إنشاء تقرير التحديث..."
cat > /workspace/AI/last_update.md << EOF
# 📊 تقرير آخر تحديث

- **التاريخ:** $current_date
- **عدد الملفات:** $total_files
- **الحالة:** ✅ محدث

## 📁 المحتوى:
$(tree /workspace/AI/knowledge_base -I '__pycache__|*.pyc' 2>/dev/null || find /workspace/AI/knowledge_base -type f)
EOF

echo "✅ تم التحديث بنجاح!"