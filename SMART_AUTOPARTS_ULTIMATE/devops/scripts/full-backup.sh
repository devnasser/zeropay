#!/bin/bash

# 🛡️ نظام النسخ الاحتياطي الشامل
# Comprehensive Backup System
# تم تطويره بناءً على التحليل العميق

set -e  # إيقاف عند أي خطأ

# الإعدادات
WORKSPACE="/workspace"
BACKUP_ROOT="/workspace/backups"
BACKUP_DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="$BACKUP_ROOT/full_backup_$BACKUP_DATE"
BACKUP_ARCHIVE="$BACKUP_DIR.tar.gz"

# الألوان للإخراج
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# دالة لطباعة الرسائل
log() {
    echo -e "${GREEN}[$(date '+%H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[خطأ]${NC} $1" >&2
    exit 1
}

warning() {
    echo -e "${YELLOW}[تحذير]${NC} $1"
}

# التحقق من المساحة المتاحة
check_space() {
    log "🔍 التحقق من المساحة المتاحة..."
    
    WORKSPACE_SIZE=$(du -sb "$WORKSPACE" | cut -f1)
    AVAILABLE_SPACE=$(df "$BACKUP_ROOT" | tail -1 | awk '{print $4}')
    AVAILABLE_SPACE=$((AVAILABLE_SPACE * 1024))
    
    # نحتاج ضعف المساحة على الأقل (للأمان)
    REQUIRED_SPACE=$((WORKSPACE_SIZE * 2))
    
    if [ $AVAILABLE_SPACE -lt $REQUIRED_SPACE ]; then
        error "المساحة غير كافية! مطلوب: $((REQUIRED_SPACE / 1024 / 1024))MB، متاح: $((AVAILABLE_SPACE / 1024 / 1024))MB"
    fi
    
    log "✅ المساحة كافية: $((AVAILABLE_SPACE / 1024 / 1024))MB متاحة"
}

# إنشاء بنية المجلدات
create_structure() {
    log "📁 إنشاء بنية المجلدات..."
    
    mkdir -p "$BACKUP_DIR"/{files,databases,configs,git,system}
    mkdir -p "$BACKUP_ROOT/logs"
}

# نسخ الملفات الرئيسية
backup_files() {
    log "📄 نسخ الملفات..."
    
    # استخدام rsync للنسخ الذكي
    rsync -av --progress \
        --exclude='*.tmp' \
        --exclude='*.temp' \
        --exclude='*.log' \
        --exclude='node_modules/' \
        --exclude='.git/objects/' \
        --exclude='vendor/' \
        --exclude='cache/' \
        --exclude='storage/logs/' \
        "$WORKSPACE/" "$BACKUP_DIR/files/" 2>&1 | \
        grep -E '^[^/]|/$' | \
        while read line; do
            echo -ne "\r📁 نسخ: $line                    "
        done
    
    echo ""
    log "✅ تم نسخ الملفات"
}

# نسخ قواعد البيانات
backup_databases() {
    log "🗄️ البحث عن قواعد البيانات..."
    
    # البحث عن ملفات SQLite
    find "$WORKSPACE" \( -name "*.sqlite" -o -name "*.db" -o -name "*.sqlite3" \) -type f | while read db; do
        if [ -f "$db" ]; then
            DB_NAME=$(basename "$db")
            log "  💾 نسخ: $DB_NAME"
            
            # نسخ مع الحفاظ على البنية
            cp --parents "$db" "$BACKUP_DIR/databases/" 2>/dev/null || \
            cp "$db" "$BACKUP_DIR/databases/$DB_NAME"
            
            # التحقق من سلامة قاعدة البيانات
            if command -v sqlite3 &> /dev/null; then
                sqlite3 "$db" "PRAGMA integrity_check;" > /dev/null 2>&1 || \
                warning "قاعدة البيانات $DB_NAME قد تكون تالفة"
            fi
        fi
    done
    
    log "✅ تم نسخ قواعد البيانات"
}

# نسخ إعدادات Git
backup_git() {
    log "🔧 نسخ معلومات Git..."
    
    # حفظ معلومات المستودعات
    find "$WORKSPACE" -name ".git" -type d | while read gitdir; do
        REPO_PATH=$(dirname "$gitdir")
        REPO_NAME=$(basename "$REPO_PATH")
        
        # معلومات المستودع
        {
            echo "Repository: $REPO_NAME"
            echo "Path: $REPO_PATH"
            echo "Remote URLs:"
            cd "$REPO_PATH" && git remote -v
            echo -e "\nLast 10 commits:"
            git log --oneline -10
            echo -e "\nBranches:"
            git branch -a
            echo -e "\nStatus:"
            git status
        } > "$BACKUP_DIR/git/${REPO_NAME}_info.txt"
        
        # نسخ الإعدادات المهمة
        cp -r "$gitdir/config" "$BACKUP_DIR/git/${REPO_NAME}_config"
        cp -r "$gitdir/hooks" "$BACKUP_DIR/git/${REPO_NAME}_hooks" 2>/dev/null || true
    done
    
    log "✅ تم نسخ معلومات Git"
}

# حفظ معلومات النظام
save_system_info() {
    log "📊 حفظ معلومات النظام..."
    
    {
        echo "=== معلومات النسخة الاحتياطية ==="
        echo "التاريخ: $(date)"
        echo "المستخدم: $(whoami)"
        echo "المضيف: $(hostname)"
        echo ""
        
        echo "=== معلومات المشروع ==="
        echo "حجم المشروع: $(du -sh "$WORKSPACE" | cut -f1)"
        echo "عدد الملفات: $(find "$WORKSPACE" -type f | wc -l)"
        echo "عدد المجلدات: $(find "$WORKSPACE" -type d | wc -l)"
        echo ""
        
        echo "=== معلومات PHP ==="
        php -v
        echo ""
        echo "الإضافات المثبتة:"
        php -m
        echo ""
        
        echo "=== معلومات النظام ==="
        uname -a
        echo ""
        echo "الذاكرة:"
        free -h
        echo ""
        echo "القرص:"
        df -h "$WORKSPACE"
        echo ""
        
        echo "=== المتغيرات البيئية ==="
        env | grep -E "(PHP|COMPOSER|NODE|PATH)" | sort
        
    } > "$BACKUP_DIR/system/system_info.txt"
    
    # حفظ قائمة الحزم المثبتة
    if command -v composer &> /dev/null; then
        find "$WORKSPACE" -name "composer.json" -type f | while read composer_file; do
            DIR=$(dirname "$composer_file")
            PROJECT=$(basename "$DIR")
            cp "$composer_file" "$BACKUP_DIR/system/composer_${PROJECT}.json"
            [ -f "$DIR/composer.lock" ] && cp "$DIR/composer.lock" "$BACKUP_DIR/system/composer_${PROJECT}.lock"
        done
    fi
    
    log "✅ تم حفظ معلومات النظام"
}

# ضغط وتشفير النسخة
compress_backup() {
    log "🔐 ضغط النسخة الاحتياطية..."
    
    cd "$BACKUP_ROOT"
    
    # ضغط مع شريط التقدم
    tar -czf "$BACKUP_ARCHIVE" \
        --checkpoint=1000 \
        --checkpoint-action=dot \
        "$(basename "$BACKUP_DIR")" 2>&1 | \
        while read line; do
            echo -ne "\r🗜️ ضغط الملفات..."
        done
    
    echo ""
    
    # حساب المجموع الاختباري
    log "🔏 حساب المجموع الاختباري..."
    sha256sum "$BACKUP_ARCHIVE" > "$BACKUP_ARCHIVE.sha256"
    
    # حذف المجلد غير المضغوط لتوفير المساحة
    rm -rf "$BACKUP_DIR"
    
    log "✅ تم ضغط النسخة: $(du -h "$BACKUP_ARCHIVE" | cut -f1)"
}

# التحقق من النسخة
verify_backup() {
    log "🔍 التحقق من سلامة النسخة..."
    
    # التحقق من المجموع الاختباري
    if sha256sum -c "$BACKUP_ARCHIVE.sha256" > /dev/null 2>&1; then
        log "✅ النسخة سليمة"
    else
        error "النسخة تالفة!"
    fi
    
    # التحقق من إمكانية فك الضغط
    if tar -tzf "$BACKUP_ARCHIVE" > /dev/null 2>&1; then
        log "✅ يمكن فك ضغط النسخة"
    else
        error "لا يمكن فك ضغط النسخة!"
    fi
}

# إنشاء سجل النسخ
create_backup_log() {
    log "📝 إنشاء سجل النسخ..."
    
    LOG_FILE="$BACKUP_ROOT/logs/backup_history.log"
    
    {
        echo "========================"
        echo "تاريخ: $(date)"
        echo "ملف النسخة: $BACKUP_ARCHIVE"
        echo "الحجم: $(du -h "$BACKUP_ARCHIVE" | cut -f1)"
        echo "المجموع الاختباري: $(cat "$BACKUP_ARCHIVE.sha256" | cut -d' ' -f1)"
        echo "========================"
    } >> "$LOG_FILE"
}

# تنظيف النسخ القديمة (اختياري)
cleanup_old_backups() {
    # الاحتفاظ بآخر 5 نسخ فقط
    KEEP_BACKUPS=5
    
    log "🧹 تنظيف النسخ القديمة..."
    
    BACKUP_COUNT=$(ls -1 "$BACKUP_ROOT"/full_backup_*.tar.gz 2>/dev/null | wc -l)
    
    if [ $BACKUP_COUNT -gt $KEEP_BACKUPS ]; then
        ls -1t "$BACKUP_ROOT"/full_backup_*.tar.gz | tail -n +$((KEEP_BACKUPS + 1)) | while read old_backup; do
            warning "حذف نسخة قديمة: $(basename "$old_backup")"
            rm -f "$old_backup" "$old_backup.sha256"
        done
    fi
}

# عرض الملخص
show_summary() {
    echo ""
    echo "╔══════════════════════════════════════════════════════╗"
    echo "║              ✅ اكتمل النسخ الاحتياطي               ║"
    echo "╠══════════════════════════════════════════════════════╣"
    echo "║ الملف: $(basename "$BACKUP_ARCHIVE")"
    echo "║ الحجم: $(du -h "$BACKUP_ARCHIVE" | cut -f1)"
    echo "║ المسار: $BACKUP_ARCHIVE"
    echo "║ المجموع الاختباري: $(cat "$BACKUP_ARCHIVE.sha256" | cut -d' ' -f1 | cut -c1-16)..."
    echo "╚══════════════════════════════════════════════════════╝"
    echo ""
    echo "💡 لاسترجاع النسخة:"
    echo "   tar -xzf $BACKUP_ARCHIVE"
    echo ""
}

# التنفيذ الرئيسي
main() {
    echo "🛡️ === نظام النسخ الاحتياطي الشامل ==="
    echo "التاريخ: $(date)"
    echo ""
    
    # التحقق من الصلاحيات
    if [ ! -w "$BACKUP_ROOT" ]; then
        mkdir -p "$BACKUP_ROOT" || error "لا يمكن إنشاء مجلد النسخ الاحتياطي"
    fi
    
    # تنفيذ خطوات النسخ
    check_space
    create_structure
    backup_files
    backup_databases
    backup_git
    save_system_info
    compress_backup
    verify_backup
    create_backup_log
    cleanup_old_backups
    show_summary
    
    log "🎉 تمت جميع العمليات بنجاح!"
}

# تشغيل البرنامج
main "$@"