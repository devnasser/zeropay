#!/bin/bash

# 🔍 نظام البحث السريع
# Fast Search System using SQLite Index

# الألوان
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

# المسارات
INDEX_DB="/workspace/system/cache/search-index.sqlite"

# التحقق من وجود الفهرس
if [ ! -f "$INDEX_DB" ]; then
    echo -e "${RED}❌ الفهرس غير موجود. قم بتشغيل:${NC}"
    echo "php /workspace/system/scripts/build-search-index.php"
    exit 1
fi

# دالة المساعدة
show_help() {
    echo -e "${BLUE}🔍 === نظام البحث السريع ===${NC}"
    echo ""
    echo "الاستخدام:"
    echo "  $0 [خيارات] <نص البحث>"
    echo ""
    echo "الخيارات:"
    echo "  -f, --files      البحث في أسماء الملفات فقط"
    echo "  -c, --content    البحث في المحتوى فقط"
    echo "  -e, --ext TYPE   البحث في نوع ملف معين (php, js, css...)"
    echo "  -p, --php        البحث في ملفات PHP (classes, functions)"
    echo "  -l, --limit N    عدد النتائج (افتراضي: 20)"
    echo "  -h, --help       عرض المساعدة"
    echo ""
    echo "أمثلة:"
    echo "  $0 'function login'     # بحث عن 'function login'"
    echo "  $0 -f controller        # بحث في أسماء الملفات"
    echo "  $0 -e php -p User       # بحث عن User في ملفات PHP"
    echo "  $0 -c 'SELECT * FROM'   # بحث في المحتوى"
}

# معالجة المعاملات
SEARCH_TYPE="all"
FILE_EXT=""
LIMIT=20
SEARCH_QUERY=""

while [[ $# -gt 0 ]]; do
    case $1 in
        -f|--files)
            SEARCH_TYPE="files"
            shift
            ;;
        -c|--content)
            SEARCH_TYPE="content"
            shift
            ;;
        -e|--ext)
            FILE_EXT="$2"
            shift 2
            ;;
        -p|--php)
            SEARCH_TYPE="php"
            shift
            ;;
        -l|--limit)
            LIMIT="$2"
            shift 2
            ;;
        -h|--help)
            show_help
            exit 0
            ;;
        *)
            SEARCH_QUERY="$1"
            shift
            ;;
    esac
done

# التحقق من وجود نص البحث
if [ -z "$SEARCH_QUERY" ]; then
    show_help
    exit 1
fi

# بناء استعلام SQL
build_query() {
    local query="$1"
    local type="$2"
    local ext="$3"
    
    case $type in
        "files")
            SQL="SELECT path, filename, size, datetime(modified, 'unixepoch', 'localtime') as mod_date 
                 FROM files 
                 WHERE filename LIKE '%${query}%'"
            ;;
        "content")
            SQL="SELECT path, snippet(file_search, 2, '<b>', '</b>', '...', 50) as snippet 
                 FROM file_search 
                 WHERE content MATCH '${query}'"
            ;;
        "php")
            SQL="SELECT DISTINCT f.path, f.classes, f.functions 
                 FROM files f 
                 WHERE (f.classes LIKE '%${query}%' OR f.functions LIKE '%${query}%') 
                 AND f.extension = 'php'"
            ;;
        *)
            SQL="SELECT path, snippet(file_search, 2, '<b>', '</b>', '...', 30) as snippet 
                 FROM file_search 
                 WHERE file_search MATCH '${query}'"
            ;;
    esac
    
    # إضافة فلتر النوع
    if [ -n "$ext" ]; then
        if [[ $SQL == *"WHERE"* ]]; then
            SQL="${SQL} AND extension = '${ext}'"
        else
            SQL="${SQL} WHERE extension = '${ext}'"
        fi
    fi
    
    SQL="${SQL} LIMIT ${LIMIT}"
}

# تنفيذ البحث
echo -e "${BLUE}🔍 البحث عن: ${YELLOW}$SEARCH_QUERY${NC}"
echo ""

START_TIME=$(date +%s%N)

# بناء الاستعلام
build_query "$SEARCH_QUERY" "$SEARCH_TYPE" "$FILE_EXT"

# تنفيذ الاستعلام
RESULTS=$(sqlite3 -separator "|" "$INDEX_DB" "$SQL" 2>/dev/null)

END_TIME=$(date +%s%N)
ELAPSED=$((($END_TIME - $START_TIME) / 1000000))

# عرض النتائج
if [ -z "$RESULTS" ]; then
    echo -e "${YELLOW}لا توجد نتائج${NC}"
else
    COUNT=$(echo "$RESULTS" | wc -l)
    echo -e "${GREEN}✅ تم العثور على ${COUNT} نتيجة في ${ELAPSED}ms${NC}"
    echo ""
    
    # عرض النتائج حسب النوع
    case $SEARCH_TYPE in
        "files")
            echo "$RESULTS" | while IFS='|' read -r path filename size mod_date; do
                size_kb=$((size / 1024))
                echo -e "${BLUE}📄 ${filename}${NC}"
                echo "   المسار: $path"
                echo "   الحجم: ${size_kb}KB | التعديل: $mod_date"
                echo ""
            done
            ;;
        "php")
            echo "$RESULTS" | while IFS='|' read -r path classes functions; do
                echo -e "${BLUE}🐘 $path${NC}"
                [ -n "$classes" ] && echo "   Classes: $classes"
                [ -n "$functions" ] && echo "   Functions: $functions"
                echo ""
            done
            ;;
        *)
            echo "$RESULTS" | while IFS='|' read -r path snippet; do
                echo -e "${BLUE}📄 $path${NC}"
                echo "   $snippet" | sed 's/<b>/\\033[1;33m/g' | sed 's/<\/b>/\\033[0m/g'
                echo ""
            done
            ;;
    esac
fi

# إحصائيات
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "⚡ وقت البحث: ${YELLOW}${ELAPSED}ms${NC}"

# نصيحة
if [ $ELAPSED -gt 100 ]; then
    echo -e "${YELLOW}💡 نصيحة: قم بتحديث الفهرس لتحسين الأداء${NC}"
fi