#!/bin/bash
# سكريبت مراقبة النظام الشامل

# ألوان للعرض
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

monitor_system() {
    clear
    echo -e "${BLUE}╔════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║       📊 مراقبة النظام - $(date +"%Y-%m-%d %H:%M:%S")       ║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════╝${NC}"
    
    # معلومات النظام
    echo -e "\n${YELLOW}💻 النظام:${NC}"
    echo "  • Uptime: $(uptime -p)"
    echo "  • Load: $(uptime | awk -F'load average:' '{print $2}')"
    
    # الذاكرة
    echo -e "\n${YELLOW}💾 الذاكرة:${NC}"
    free -h | grep -E "^Mem|^Swap" | while read line; do
        echo "  • $line"
    done
    
    # RAM Disk
    if [ -d "/mnt/ramdisk" ]; then
        echo -e "\n${YELLOW}💿 RAM Disk:${NC}"
        df -h /mnt/ramdisk | tail -1 | awk '{printf "  • الحجم: %s | المستخدم: %s (%s)\n", $2, $3, $5}'
        echo "  • الملفات: $(find /mnt/ramdisk -type f 2>/dev/null | wc -l)"
    fi
    
    # PHP (إذا كان مثبتاً)
    if command -v php &>/dev/null; then
        echo -e "\n${YELLOW}🐘 PHP:${NC}"
        php -v | head -1 | sed 's/^/  • /'
        
        # OPcache (إذا كان مفعلاً)
        php -r 'if(function_exists("opcache_get_status")) {
            $s = opcache_get_status();
            if($s) {
                printf("  • OPcache: %.1fMB / %.1fMB (%.1f%% used)\n",
                    $s["memory_usage"]["used_memory"]/1024/1024,
                    ($s["memory_usage"]["used_memory"] + $s["memory_usage"]["free_memory"])/1024/1024,
                    $s["memory_usage"]["current_wasted_percentage"]
                );
                printf("  • Scripts: %d cached\n", $s["opcache_statistics"]["num_cached_scripts"]);
            }
        }' 2>/dev/null
    fi
    
    # العمليات الأكثر استهلاكاً
    echo -e "\n${YELLOW}⚡ أكثر العمليات استهلاكاً:${NC}"
    ps aux --sort=-%cpu | head -4 | tail -3 | awk '{printf "  • %-15s CPU: %5s%%  MEM: %5s%%\n", substr($11,1,15), $3, $4}'
    
    # الاتصال
    echo -e "\n${YELLOW}🌐 الاتصال:${NC}"
    if ping -c 1 google.com &>/dev/null; then
        echo -e "  • الإنترنت: ${GREEN}✅ متصل${NC}"
    else
        echo -e "  • الإنترنت: ${RED}❌ منقطع${NC}"
    fi
    
    # المساحة
    echo -e "\n${YELLOW}💿 المساحة:${NC}"
    df -h / | tail -1 | awk '{printf "  • القرص الرئيسي: %s / %s (%s مستخدم)\n", $3, $2, $5}'
}

# تشغيل مستمر إذا لم يتم تمرير معامل
if [ "$1" != "once" ]; then
    while true; do
        monitor_system
        sleep 5
    done
else
    monitor_system
fi