#!/bin/bash

# Performance Manager - نظام إدارة الأداء والعمليات
# يستخدم أدوات مجانية لتحسين الأداء

WORKSPACE_ROOT="/workspace"
LOG_DIR="$WORKSPACE_ROOT/system/logs"
CACHE_DIR="$WORKSPACE_ROOT/.cache"

# إنشاء المجلدات المطلوبة
mkdir -p "$LOG_DIR" "$CACHE_DIR"

# الألوان للإخراج
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# وظائف المساعدة
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_DIR/performance.log"
}

# فحص استخدام الموارد
check_resources() {
    echo -e "${YELLOW}📊 فحص استخدام الموارد${NC}"
    
    # المعالج
    echo -e "\n${GREEN}CPU Usage:${NC}"
    top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk '{print "Usage: " 100 - $1"%"}'
    
    # الذاكرة
    echo -e "\n${GREEN}Memory Usage:${NC}"
    free -h | grep "^Mem" | awk '{print "Total: " $2 " | Used: " $3 " | Free: " $4}'
    
    # القرص
    echo -e "\n${GREEN}Disk Usage:${NC}"
    df -h "$WORKSPACE_ROOT" | tail -1 | awk '{print "Used: " $3 " / " $2 " (" $5 ")"}'
    
    # العمليات الثقيلة
    echo -e "\n${GREEN}Top Heavy Processes:${NC}"
    ps aux --sort=-%cpu,-%mem | head -n 6 | awk 'NR>1 {printf "%-8s %5s %5s %s\n", $1, $3"%", $4"%", $11}'
}

# تنظيف الذاكرة
clean_memory() {
    echo -e "${YELLOW}🧹 تنظيف الذاكرة${NC}"
    
    # تنظيف page cache
    sync && echo 1 > /proc/sys/vm/drop_caches 2>/dev/null || echo "⚠️ يتطلب صلاحيات root"
    
    # تنظيف swap إذا لزم الأمر
    if [ $(swapon -s | wc -l) -gt 1 ]; then
        swapoff -a && swapon -a 2>/dev/null || echo "⚠️ يتطلب صلاحيات root لإدارة swap"
    fi
    
    log "تم تنظيف الذاكرة"
}

# تحسين PHP
optimize_php() {
    echo -e "${YELLOW}🐘 تحسين PHP${NC}"
    
    # إنشاء ملف تكوين OPcache محسّن
    cat > "$WORKSPACE_ROOT/system/config/php-opcache.ini" << EOF
; OPcache تحسينات
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.fast_shutdown=1
opcache.preload_user=www-data

; JIT تحسينات
opcache.jit=tracing
opcache.jit_buffer_size=100M

; أداء إضافي
realpath_cache_size=4M
realpath_cache_ttl=600
EOF
    
    echo "✅ تم إنشاء تكوين OPcache محسّن"
    log "تم تحسين إعدادات PHP"
}

# إدارة العمليات
manage_processes() {
    echo -e "${YELLOW}⚙️ إدارة العمليات${NC}"
    
    # إيقاف العمليات غير الضرورية
    local processes_to_stop=(
        "tracker-"  # GNOME tracker
        "evolution" # Evolution mail
        "gnome-software" # GNOME Software
    )
    
    for proc in "${processes_to_stop[@]}"; do
        pkill -f "$proc" 2>/dev/null && echo "✅ تم إيقاف: $proc"
    done
    
    # تقليل أولوية العمليات الثقيلة
    renice +10 -p $(pgrep -f "chrome|firefox|code") 2>/dev/null
    
    log "تمت إدارة العمليات"
}

# تحسين Git
optimize_git() {
    echo -e "${YELLOW}🔧 تحسين Git${NC}"
    
    # تكوين Git للأداء الأفضل
    git config --global core.preloadindex true
    git config --global core.fscache true
    git config --global gc.auto 256
    git config --global pack.threads 0
    
    # تنظيف مستودعات Git
    find "$WORKSPACE_ROOT" -name ".git" -type d | while read gitdir; do
        repo_dir=$(dirname "$gitdir")
        echo "تنظيف: $repo_dir"
        cd "$repo_dir" && git gc --aggressive --prune=now 2>/dev/null
    done
    
    log "تم تحسين Git"
}

# إعداد التخزين المؤقت
setup_cache() {
    echo -e "${YELLOW}💾 إعداد التخزين المؤقت${NC}"
    
    # إعداد APCu للتطبيقات
    cat > "$WORKSPACE_ROOT/system/config/apcu.ini" << EOF
; APCu Configuration
apc.enabled=1
apc.shm_size=128M
apc.ttl=7200
apc.enable_cli=1
apc.serializer=php
EOF
    
    # إعداد Redis (إذا كان متاحاً)
    if command -v redis-cli >/dev/null; then
        redis-cli CONFIG SET maxmemory 256mb
        redis-cli CONFIG SET maxmemory-policy allkeys-lru
        echo "✅ تم تكوين Redis"
    fi
    
    log "تم إعداد التخزين المؤقت"
}

# المراقبة المستمرة
monitor_continuous() {
    echo -e "${YELLOW}📈 بدء المراقبة المستمرة${NC}"
    
    while true; do
        clear
        echo -e "${GREEN}=== مراقبة الأداء - $(date) ===${NC}\n"
        
        # CPU و Memory
        echo -e "${YELLOW}CPU & Memory:${NC}"
        top -bn1 | head -5
        
        # أهم العمليات
        echo -e "\n${YELLOW}Top Processes:${NC}"
        ps aux --sort=-%cpu | head -5
        
        # مساحة القرص
        echo -e "\n${YELLOW}Disk Space:${NC}"
        df -h "$WORKSPACE_ROOT" | grep -E "^/|Filesystem"
        
        # اتصالات الشبكة
        echo -e "\n${YELLOW}Network Connections:${NC}"
        netstat -tuln 2>/dev/null | grep LISTEN | wc -l | xargs echo "Open Ports:"
        
        sleep 5
    done
}

# القائمة الرئيسية
show_menu() {
    while true; do
        echo -e "\n${GREEN}🚀 نظام إدارة الأداء${NC}\n"
        echo "1) 📊 فحص الموارد"
        echo "2) 🧹 تنظيف الذاكرة"
        echo "3) 🐘 تحسين PHP"
        echo "4) ⚙️ إدارة العمليات"
        echo "5) 🔧 تحسين Git"
        echo "6) 💾 إعداد التخزين المؤقت"
        echo "7) 📈 مراقبة مستمرة"
        echo "8) 🔄 تنفيذ جميع التحسينات"
        echo "9) 🚪 خروج"
        
        read -p "اختر خيار [1-9]: " choice
        
        case $choice in
            1) check_resources ;;
            2) clean_memory ;;
            3) optimize_php ;;
            4) manage_processes ;;
            5) optimize_git ;;
            6) setup_cache ;;
            7) monitor_continuous ;;
            8) 
                check_resources
                clean_memory
                optimize_php
                manage_processes
                optimize_git
                setup_cache
                echo -e "\n${GREEN}✅ تمت جميع التحسينات!${NC}"
                ;;
            9) 
                echo "👋 وداعاً!"
                exit 0
                ;;
            *)
                echo -e "${RED}❌ خيار غير صحيح${NC}"
                ;;
        esac
    done
}

# البدء
echo -e "${GREEN}🌟 مرحباً بك في نظام إدارة الأداء${NC}"
log "بدء نظام إدارة الأداء"

# معالجة الوسائط
case "${1:-menu}" in
    check) check_resources ;;
    clean) clean_memory ;;
    optimize) 
        optimize_php
        manage_processes
        optimize_git
        setup_cache
        ;;
    monitor) monitor_continuous ;;
    *) show_menu ;;
esac