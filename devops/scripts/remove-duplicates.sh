#!/bin/bash

# سكريبت إزالة الملفات المكررة
# Duplicate Files Removal Script

echo "🔍 === إزالة الملفات المكررة ==="
echo "التاريخ: $(date)"
echo ""

# المتغيرات
WORKSPACE="/workspace"
LOG_FILE="$WORKSPACE/system/logs/duplicates-removed-$(date +%Y%m%d-%H%M%S).log"
DRY_RUN=true
TOTAL_SAVED=0

# إنشاء مجلد السجلات
mkdir -p "$WORKSPACE/system/logs"

# دالة للعثور على الملفات المكررة
find_duplicates() {
    echo "🔍 البحث عن الملفات المكررة..."
    
    # البحث عن ملفات PHP المكررة
    find "$WORKSPACE" -name "*.php" -type f -exec md5sum {} + | \
    sort | \
    uniq -d -w 32 | \
    while read checksum file; do
        # العثور على جميع الملفات بنفس checksum
        find "$WORKSPACE" -name "*.php" -type f -exec md5sum {} + | \
        grep "^$checksum" | \
        awk '{print $2}'
    done | sort | uniq -c | sort -rn
}

# دالة لإزالة المكررات
remove_duplicates() {
    local extension=$1
    echo "📄 معالجة ملفات $extension..."
    
    # إنشاء قائمة بالملفات وحساب MD5
    find "$WORKSPACE" -name "*.$extension" -type f -exec md5sum {} + | \
    sort | \
    awk '{
        if (seen[$1]++) {
            print $2
        } else {
            original[$1] = $2
        }
    }' | \
    while read duplicate; do
        if [ -f "$duplicate" ]; then
            # حساب الحجم
            size=$(stat -c%s "$duplicate" 2>/dev/null || echo "0")
            TOTAL_SAVED=$((TOTAL_SAVED + size))
            
            echo "🗑️ ملف مكرر: $duplicate ($(numfmt --to=iec-i --suffix=B $size))" | tee -a "$LOG_FILE"
            
            if [ "$DRY_RUN" = false ]; then
                # إزالة الملف المكرر
                rm -f "$duplicate"
                echo "✅ تم الحذف" | tee -a "$LOG_FILE"
            else
                echo "⚠️ (وضع التجربة - لم يتم الحذف)" | tee -a "$LOG_FILE"
            fi
        fi
    done
}

# دالة لتوحيد vendor
unify_vendors() {
    echo "📦 توحيد مجلدات vendor..."
    
    # العثور على جميع مجلدات vendor
    find "$WORKSPACE" -name "vendor" -type d | while read vendor_dir; do
        if [ "$vendor_dir" != "$WORKSPACE/vendor" ]; then
            size=$(du -sh "$vendor_dir" 2>/dev/null | cut -f1)
            echo "📁 vendor موجود: $vendor_dir ($size)" | tee -a "$LOG_FILE"
            
            if [ "$DRY_RUN" = false ]; then
                # نقل المحتوى إلى vendor المركزي
                if [ -d "$vendor_dir" ]; then
                    # دمج المحتويات
                    cp -rn "$vendor_dir/"* "$WORKSPACE/vendor/" 2>/dev/null || true
                    
                    # إزالة vendor القديم
                    rm -rf "$vendor_dir"
                    
                    # إنشاء symbolic link
                    ln -s "$WORKSPACE/vendor" "$vendor_dir"
                    echo "✅ تم التوحيد" | tee -a "$LOG_FILE"
                fi
            fi
        fi
    done
}

# دالة لتنظيف المجلدات الفارغة
clean_empty_dirs() {
    echo "📁 تنظيف المجلدات الفارغة..."
    
    local count=$(find "$WORKSPACE" -type d -empty | wc -l)
    echo "🔍 عدد المجلدات الفارغة: $count" | tee -a "$LOG_FILE"
    
    if [ "$DRY_RUN" = false ]; then
        find "$WORKSPACE" -type d -empty -delete
        echo "✅ تم حذف $count مجلد فارغ" | tee -a "$LOG_FILE"
    else
        echo "⚠️ (وضع التجربة - سيتم حذف $count مجلد)" | tee -a "$LOG_FILE"
    fi
}

# الدالة الرئيسية
main() {
    echo "🚀 بدء عملية التنظيف..."
    echo "⚠️ الوضع: $([ "$DRY_RUN" = true ] && echo "تجربة" || echo "تنفيذ فعلي")"
    echo ""
    
    # معالجة أنواع الملفات المختلفة
    for ext in php js css html md txt json xml yml yaml; do
        remove_duplicates "$ext"
    done
    
    # توحيد vendor
    unify_vendors
    
    # تنظيف المجلدات الفارغة
    clean_empty_dirs
    
    # عرض النتائج
    echo ""
    echo "📊 === النتائج النهائية ==="
    echo "💾 المساحة المحتمل توفيرها: $(numfmt --to=iec-i --suffix=B $TOTAL_SAVED)"
    echo "📄 السجل محفوظ في: $LOG_FILE"
    
    if [ "$DRY_RUN" = true ]; then
        echo ""
        echo "⚠️ هذا كان وضع التجربة. لتنفيذ الحذف الفعلي:"
        echo "DRY_RUN=false $0"
    fi
}

# معالجة المعاملات
if [ "$1" = "--execute" ]; then
    DRY_RUN=false
    echo "⚠️ تحذير: سيتم حذف الملفات فعلياً!"
    read -p "هل أنت متأكد؟ (yes/no): " confirm
    if [ "$confirm" != "yes" ]; then
        echo "❌ تم الإلغاء"
        exit 1
    fi
fi

# تشغيل البرنامج
main

echo ""
echo "✅ اكتملت عملية التنظيف!"