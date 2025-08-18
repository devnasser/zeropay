#!/bin/bash

# بديل ripgrep - بحث سريع متوازي
# Fast parallel search alternative to ripgrep

# الألوان
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

# الإعدادات
THREADS=4
MAX_DEPTH=10
EXCLUDE_DIRS=".git|vendor|node_modules|.cache|storage/logs"

# دالة البحث المتوازي
parallel_search() {
    local pattern="$1"
    local path="${2:-/workspace}"
    local file_type="${3:-}"
    
    echo -e "${BLUE}🔍 بحث متوازي عن: ${YELLOW}$pattern${NC}"
    echo ""
    
    # بناء أمر find
    local find_cmd="find $path -type f"
    
    # إضافة فلتر النوع
    if [ -n "$file_type" ]; then
        find_cmd="$find_cmd -name '*.$file_type'"
    fi
    
    # استثناء المجلدات
    find_cmd="$find_cmd -regextype posix-extended -not -regex '.*/(${EXCLUDE_DIRS})/.*'"
    
    # قياس الوقت
    local start_time=$(date +%s%N)
    
    # البحث المتوازي باستخدام xargs و grep
    eval "$find_cmd" | \
    xargs -P $THREADS -I {} grep -Hn --color=always "$pattern" {} 2>/dev/null | \
    head -100
    
    local end_time=$(date +%s%N)
    local elapsed=$(( (end_time - start_time) / 1000000 ))
    
    echo ""
    echo -e "${GREEN}⚡ وقت البحث: ${elapsed}ms${NC}"
}

# دالة البحث السريع في الفهرس
indexed_search() {
    local pattern="$1"
    local db="/workspace/system/cache/search-index.sqlite"
    
    if [ ! -f "$db" ]; then
        echo -e "${RED}❌ الفهرس غير موجود${NC}"
        return 1
    fi
    
    echo -e "${BLUE}🚀 بحث مفهرس عن: ${YELLOW}$pattern${NC}"
    echo ""
    
    # استخدام PHP للبحث في SQLite (أسرع)
    php -r "
        \$db = new SQLite3('$db');
        \$start = microtime(true);
        
        \$stmt = \$db->prepare('
            SELECT path, snippet(file_search, 2, \"<b>\", \"</b>\", \"...\", 30) as snippet
            FROM file_search 
            WHERE content MATCH :pattern
            LIMIT 50
        ');
        \$stmt->bindValue(':pattern', '$pattern', SQLITE3_TEXT);
        \$result = \$stmt->execute();
        
        \$count = 0;
        while (\$row = \$result->fetchArray()) {
            echo \"\033[0;34m\" . \$row['path'] . \"\033[0m\n\";
            echo \"  \" . str_replace(['<b>', '</b>'], ['\033[1;33m', '\033[0m'], \$row['snippet']) . \"\n\n\";
            \$count++;
        }
        
        \$elapsed = round((microtime(true) - \$start) * 1000, 2);
        echo \"\n\033[0;32m✅ تم العثور على \$count نتيجة في {\$elapsed}ms\033[0m\n\";
    "
}

# دالة البحث عن الرموز
symbol_search() {
    local symbol="$1"
    local type="${2:-all}" # class, function, variable, all
    
    echo -e "${BLUE}🏷️ بحث عن الرمز: ${YELLOW}$symbol${NC}"
    echo ""
    
    case $type in
        class)
            pattern="^[[:space:]]*(abstract[[:space:]]+)?class[[:space:]]+$symbol"
            ;;
        function)
            pattern="^[[:space:]]*function[[:space:]]+$symbol"
            ;;
        variable)
            pattern="\\\$$symbol[[:space:]]*="
            ;;
        *)
            pattern="(class|function|trait|interface)[[:space:]]+$symbol|\\\$$symbol"
            ;;
    esac
    
    find /workspace -name "*.php" -type f \
        -not -path "*/vendor/*" \
        -not -path "*/.git/*" | \
    xargs -P $THREADS grep -Hn -E "$pattern" 2>/dev/null | \
    while IFS=: read -r file line content; do
        echo -e "${BLUE}$file:$line${NC}"
        echo "  $content"
        echo ""
    done
}

# دالة عرض المساعدة
show_help() {
    echo -e "${BLUE}🚀 === نظام البحث السريع المتوازي ===${NC}"
    echo ""
    echo "الاستخدام:"
    echo "  $0 <نص البحث>              # بحث عام"
    echo "  $0 -i <نص>                  # بحث مفهرس (الأسرع)"
    echo "  $0 -t <نوع> <نص>           # بحث في نوع معين"
    echo "  $0 -s <رمز>                 # بحث عن رمز PHP"
    echo "  $0 -sc <class>              # بحث عن صنف"
    echo "  $0 -sf <function>           # بحث عن دالة"
    echo ""
    echo "أمثلة:"
    echo "  $0 'SELECT * FROM'          # بحث عام"
    echo "  $0 -i 'function login'      # بحث مفهرس سريع"
    echo "  $0 -t php 'User'            # بحث في ملفات PHP"
    echo "  $0 -sc UserController       # بحث عن صنف"
}

# معالجة الأوامر
case "$1" in
    -h|--help)
        show_help
        ;;
    -i|--indexed)
        indexed_search "$2"
        ;;
    -t|--type)
        parallel_search "$3" "/workspace" "$2"
        ;;
    -s|--symbol)
        symbol_search "$2"
        ;;
    -sc|--class)
        symbol_search "$2" "class"
        ;;
    -sf|--function)
        symbol_search "$2" "function"
        ;;
    *)
        if [ -z "$1" ]; then
            show_help
        else
            # البحث الافتراضي - محاولة المفهرس أولاً
            if [ -f "/workspace/system/cache/search-index.sqlite" ]; then
                indexed_search "$1"
            else
                parallel_search "$1"
            fi
        fi
        ;;
esac