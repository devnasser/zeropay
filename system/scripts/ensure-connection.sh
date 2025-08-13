#!/bin/bash
# سكريبت مراقبة الاتصال المستمر بالإنترنت

check_connection() {
    if ! ping -c 1 google.com &>/dev/null; then
        echo "⚠️  انقطع الاتصال - محاولة إصلاح..."
        
        # محاولة إعادة تشغيل الشبكة
        sudo systemctl restart systemd-networkd 2>/dev/null || true
        sudo systemctl restart systemd-resolved 2>/dev/null || true
        
        sleep 5
        
        if ! ping -c 1 google.com &>/dev/null; then
            echo "❌ فشل الاتصال - يرجى التحقق من الشبكة"
            return 1
        fi
    fi
    echo "✅ الاتصال مستقر"
    return 0
}

# تشغيل في الخلفية
if [ "$1" = "background" ]; then
    while true; do
        check_connection
        sleep 30
    done &
    echo "🌐 بدأت مراقبة الاتصال في الخلفية (PID: $!)"
else
    check_connection
fi