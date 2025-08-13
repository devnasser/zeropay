#!/bin/bash

# Fast Search System - بديل مجاني لـ Elasticsearch
# يستخدم ripgrep للبحث السريع و fzf للواجهة التفاعلية

WORKSPACE_ROOT="/workspace"
CACHE_DIR="$WORKSPACE_ROOT/.search-cache"
INDEX_FILE="$CACHE_DIR/file-index.txt"

# إنشاء مجلد التخزين المؤقت
mkdir -p "$CACHE_DIR"

# الوظائف المساعدة
update_index() {
    echo "🔄 تحديث فهرس الملفات..."
    find "$WORKSPACE_ROOT" -type f \
        -not -path "*/node_modules/*" \
        -not -path "*/vendor/*" \
        -not -path "*/.git/*" \
        -not -path "*/storage/logs/*" \
        -not -name "*.log" \
        -not -name "*.cache" \
        > "$INDEX_FILE"
    
    # إنشاء فهرس للأسماء
    find "$WORKSPACE_ROOT" -type f -name "*.php" -o -name "*.js" -o -name "*.ts" \
        | xargs -I {} basename {} | sort -u > "$CACHE_DIR/names-index.txt"
    
    echo "✅ تم تحديث الفهرس - $(wc -l < "$INDEX_FILE") ملف"
}

# بحث في المحتوى
search_content() {
    local query="$1"
    echo "🔍 البحث عن: $query"
    
    rg --color=always \
       --line-number \
       --no-heading \
       --smart-case \
       --max-columns=150 \
       --max-columns-preview \
       "$query" "$WORKSPACE_ROOT" \
       --glob '!node_modules' \
       --glob '!vendor' \
       --glob '!*.log' \
       | fzf --ansi \
           --delimiter ':' \
           --preview 'bat --color=always --style=numbers --line-range={2}:+50 {1}' \
           --preview-window 'right:50%:wrap' \
           --bind 'enter:execute(code {1}:{2})'
}

# بحث في أسماء الملفات
search_files() {
    local query="$1"
    echo "📁 البحث في أسماء الملفات..."
    
    cat "$INDEX_FILE" | \
    fzf --query="$query" \
        --preview 'bat --color=always --style=numbers {}' \
        --preview-window 'right:50%:wrap' \
        --bind 'enter:execute(code {})'
}

# بحث الرموز (functions, classes, etc)
search_symbols() {
    local query="$1"
    echo "🏷️ البحث عن الرموز..."
    
    # PHP Classes & Functions
    rg --color=always \
       --line-number \
       --no-heading \
       "^(class|function|interface|trait)\s+$query" \
       "$WORKSPACE_ROOT" \
       --glob '*.php' \
       --glob '!vendor' \
    | fzf --ansi \
          --delimiter ':' \
          --preview 'bat --color=always --style=numbers --line-range={2}:+20 {1}' \
          --preview-window 'right:50%:wrap'
}

# القائمة الرئيسية
main_menu() {
    while true; do
        echo -e "\n🚀 نظام البحث السريع\n"
        echo "1) 🔍 بحث في المحتوى"
        echo "2) 📁 بحث في أسماء الملفات"
        echo "3) 🏷️ بحث عن الرموز (Classes/Functions)"
        echo "4) 🔄 تحديث الفهرس"
        echo "5) 🚪 خروج"
        
        read -p "اختر خيار [1-5]: " choice
        
        case $choice in
            1)
                read -p "أدخل كلمة البحث: " query
                search_content "$query"
                ;;
            2)
                read -p "أدخل اسم الملف: " query
                search_files "$query"
                ;;
            3)
                read -p "أدخل اسم الرمز: " query
                search_symbols "$query"
                ;;
            4)
                update_index
                ;;
            5)
                echo "👋 وداعاً!"
                exit 0
                ;;
            *)
                echo "❌ خيار غير صحيح"
                ;;
        esac
    done
}

# التحقق من الأدوات المطلوبة
check_dependencies() {
    local missing=()
    
    command -v rg >/dev/null || missing+=("ripgrep")
    command -v fzf >/dev/null || missing+=("fzf")
    command -v bat >/dev/null || missing+=("bat")
    
    if [ ${#missing[@]} -ne 0 ]; then
        echo "❌ الأدوات التالية مفقودة: ${missing[*]}"
        echo "📦 يرجى تثبيتها أولاً:"
        echo "   sudo apt-get install ripgrep fzf bat"
        exit 1
    fi
}

# البدء
check_dependencies

# تحديث الفهرس إذا لم يكن موجوداً
if [ ! -f "$INDEX_FILE" ]; then
    update_index
fi

# معالجة الوسائط
if [ $# -eq 0 ]; then
    main_menu
else
    search_content "$*"
fi