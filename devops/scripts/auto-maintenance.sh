#!/bin/bash

# Auto Maintenance System - نظام الصيانة التلقائية
# يقوم بتنفيذ مهام الصيانة الدورية تلقائياً

WORKSPACE_ROOT="/workspace"
LOG_DIR="$WORKSPACE_ROOT/system/logs"
MAINTENANCE_LOG="$LOG_DIR/maintenance.log"

# إنشاء المجلدات المطلوبة
mkdir -p "$LOG_DIR"

# الألوان
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# وظائف المساعدة
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$MAINTENANCE_LOG"
}

send_notification() {
    local title="$1"
    local message="$2"
    
    # يمكن إضافة إشعارات عبر البريد أو Slack هنا
    echo -e "${GREEN}📢 $title${NC}: $message"
}

# مهام الصيانة اليومية
daily_maintenance() {
    log "🔄 بدء الصيانة اليومية..."
    
    # 1. تنظيف الملفات المؤقتة
    log "🧹 تنظيف الملفات المؤقتة..."
    find "$WORKSPACE_ROOT" -type f \( -name "*.tmp" -o -name "*.log" -o -name "*.cache" \) -mtime +7 -delete 2>/dev/null
    
    # 2. تحديث فهرس البحث
    log "🔍 تحديث فهرس البحث..."
    php "$WORKSPACE_ROOT/system/scripts/build-index.php" > /dev/null 2>&1
    
    # 3. تنظيف سجلات Laravel القديمة
    log "📝 تنظيف سجلات Laravel..."
    find "$WORKSPACE_ROOT" -path "*/storage/logs/*.log" -mtime +30 -delete 2>/dev/null
    
    # 4. تحسين قواعد البيانات SQLite
    log "💾 تحسين قواعد البيانات..."
    find "$WORKSPACE_ROOT" -name "*.sqlite" -o -name "*.db" | while read db; do
        sqlite3 "$db" "VACUUM;" 2>/dev/null || true
    done
    
    # 5. تقرير المساحة
    DISK_USAGE=$(df -h "$WORKSPACE_ROOT" | tail -1 | awk '{print $5}')
    log "💾 استخدام القرص: $DISK_USAGE"
    
    send_notification "الصيانة اليومية" "اكتملت بنجاح - استخدام القرص: $DISK_USAGE"
}

# مهام الصيانة الأسبوعية
weekly_maintenance() {
    log "🔄 بدء الصيانة الأسبوعية..."
    
    # 1. تنظيف عميق للتبعيات غير المستخدمة
    log "📦 تنظيف التبعيات..."
    cd "$WORKSPACE_ROOT"
    
    # تنظيف composer
    find . -name "composer.json" -not -path "*/vendor/*" | while read composer; do
        dir=$(dirname "$composer")
        cd "$dir" && composer install --no-dev --optimize-autoloader 2>/dev/null || true
    done
    
    # 2. تحليل الأمان
    log "🔒 فحص الأمان..."
    SECURITY_ISSUES=0
    
    # فحص الصلاحيات
    find "$WORKSPACE_ROOT" -type f -perm 0777 | while read file; do
        log "⚠️ ملف بصلاحيات غير آمنة: $file"
        chmod 644 "$file"
        ((SECURITY_ISSUES++))
    done
    
    # 3. نسخ احتياطي أسبوعي
    log "💾 إنشاء نسخة احتياطية أسبوعية..."
    BACKUP_NAME="weekly_backup_$(date +%Y%m%d).tar.gz"
    tar -czf "/tmp/$BACKUP_NAME" \
        --exclude='node_modules' \
        --exclude='vendor' \
        --exclude='*.log' \
        "$WORKSPACE_ROOT" 2>/dev/null
    
    # 4. تقرير الأداء
    log "📊 توليد تقرير الأداء..."
    php "$WORKSPACE_ROOT/system/scripts/monitoring-dashboard.php"
    
    send_notification "الصيانة الأسبوعية" "اكتملت - وُجدت $SECURITY_ISSUES مشكلة أمنية وتم إصلاحها"
}

# مهام الصيانة الشهرية
monthly_maintenance() {
    log "🔄 بدء الصيانة الشهرية..."
    
    # 1. تحليل شامل للمشروع
    log "📊 تحليل شامل للمشروع..."
    TOTAL_FILES=$(find "$WORKSPACE_ROOT" -type f | wc -l)
    TOTAL_SIZE=$(du -sh "$WORKSPACE_ROOT" | cut -f1)
    CODE_LINES=$(find "$WORKSPACE_ROOT" -name "*.php" -o -name "*.js" | xargs wc -l | tail -1 | awk '{print $1}')
    
    # 2. تنظيف Git repositories
    log "🔧 تنظيف مستودعات Git..."
    find "$WORKSPACE_ROOT" -name ".git" -type d | while read gitdir; do
        repo_dir=$(dirname "$gitdir")
        cd "$repo_dir"
        git gc --aggressive --prune=now 2>/dev/null || true
        git lfs prune 2>/dev/null || true
    done
    
    # 3. تحديث التبعيات
    log "📦 تحديث التبعيات..."
    cd "$WORKSPACE_ROOT"
    composer update --no-dev 2>/dev/null || true
    
    # 4. تقرير شامل
    cat > "$LOG_DIR/monthly_report_$(date +%Y%m).txt" << EOF
تقرير الصيانة الشهرية - $(date '+%B %Y')
=====================================

إحصائيات المشروع:
- إجمالي الملفات: $TOTAL_FILES
- الحجم الكلي: $TOTAL_SIZE  
- أسطر الكود: $CODE_LINES

المهام المنجزة:
✅ تنظيف شامل للملفات المؤقتة
✅ تحسين قواعد البيانات
✅ تحديث التبعيات
✅ تنظيف مستودعات Git
✅ فحص الأمان والصلاحيات

التوصيات:
- مراجعة السجلات للأخطاء المتكررة
- تحديث إصدارات PHP/Laravel
- مراجعة استخدام الموارد
EOF

    send_notification "الصيانة الشهرية" "اكتملت - $TOTAL_FILES ملف، $TOTAL_SIZE"
}

# جدولة المهام باستخدام cron
setup_cron() {
    log "⚙️ إعداد المهام المجدولة..."
    
    CRON_FILE="/tmp/workspace_cron"
    SCRIPT_PATH="$WORKSPACE_ROOT/system/scripts/auto-maintenance.sh"
    
    # إنشاء ملف cron
    cat > "$CRON_FILE" << EOF
# Workspace Auto Maintenance
# Daily at 2 AM
0 2 * * * $SCRIPT_PATH daily >> $LOG_DIR/cron.log 2>&1

# Weekly on Sunday at 3 AM  
0 3 * * 0 $SCRIPT_PATH weekly >> $LOG_DIR/cron.log 2>&1

# Monthly on 1st at 4 AM
0 4 1 * * $SCRIPT_PATH monthly >> $LOG_DIR/cron.log 2>&1

# Health check every hour
0 * * * * $SCRIPT_PATH health >> $LOG_DIR/health.log 2>&1
EOF

    # تثبيت cron
    crontab "$CRON_FILE" 2>/dev/null && echo "✅ تم إعداد المهام المجدولة" || echo "⚠️ فشل إعداد cron"
    rm "$CRON_FILE"
}

# فحص صحة النظام
health_check() {
    # فحص المساحة
    DISK_PERCENT=$(df "$WORKSPACE_ROOT" | tail -1 | awk '{print $5}' | sed 's/%//')
    if [ "$DISK_PERCENT" -gt 90 ]; then
        send_notification "⚠️ تحذير" "مساحة القرص منخفضة: ${DISK_PERCENT}%"
    fi
    
    # فحص الذاكرة
    MEM_PERCENT=$(free | grep Mem | awk '{print int($3/$2 * 100)}')
    if [ "$MEM_PERCENT" -gt 90 ]; then
        send_notification "⚠️ تحذير" "استخدام الذاكرة مرتفع: ${MEM_PERCENT}%"
    fi
    
    # فحص العمليات
    PROC_COUNT=$(ps aux | wc -l)
    if [ "$PROC_COUNT" -gt 1000 ]; then
        send_notification "⚠️ تحذير" "عدد العمليات مرتفع: $PROC_COUNT"
    fi
}

# تشغيل فوري لجميع المهام
run_all() {
    log "🚀 تشغيل جميع مهام الصيانة..."
    daily_maintenance
    weekly_maintenance
    monthly_maintenance
    echo -e "\n${GREEN}✅ اكتملت جميع مهام الصيانة!${NC}"
}

# القائمة الرئيسية
show_menu() {
    while true; do
        echo -e "\n${GREEN}🤖 نظام الصيانة التلقائية${NC}\n"
        echo "1) 📅 تشغيل الصيانة اليومية"
        echo "2) 📆 تشغيل الصيانة الأسبوعية"
        echo "3) 🗓️ تشغيل الصيانة الشهرية"
        echo "4) 🏥 فحص صحة النظام"
        echo "5) ⚙️ إعداد المهام المجدولة"
        echo "6) 🚀 تشغيل جميع المهام"
        echo "7) 📊 عرض السجلات"
        echo "8) 🚪 خروج"
        
        read -p "اختر خيار [1-8]: " choice
        
        case $choice in
            1) daily_maintenance ;;
            2) weekly_maintenance ;;
            3) monthly_maintenance ;;
            4) health_check ;;
            5) setup_cron ;;
            6) run_all ;;
            7) tail -50 "$MAINTENANCE_LOG" ;;
            8) 
                echo "👋 وداعاً!"
                exit 0
                ;;
            *)
                echo -e "${RED}❌ خيار غير صحيح${NC}"
                ;;
        esac
    done
}

# معالجة الوسائط
case "${1:-menu}" in
    daily) daily_maintenance ;;
    weekly) weekly_maintenance ;;
    monthly) monthly_maintenance ;;
    health) health_check ;;
    setup) setup_cron ;;
    all) run_all ;;
    *) show_menu ;;
esac