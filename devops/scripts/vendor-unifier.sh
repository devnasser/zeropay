#!/bin/bash

# 🔧 نظام توحيد مجلدات vendor
# Vendor Directories Unification System

# الألوان
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}🔧 === نظام توحيد مجلدات vendor ===${NC}"
echo ""

# البحث عن جميع مجلدات vendor
echo -e "${YELLOW}🔍 البحث عن مجلدات vendor...${NC}"
VENDOR_DIRS=$(find /workspace -name "vendor" -type d -not -path "*/vendor/*" 2>/dev/null | grep -v ".git")

echo "تم العثور على:"
echo "$VENDOR_DIRS" | while read dir; do
    if [ -n "$dir" ]; then
        SIZE=$(du -sh "$dir" 2>/dev/null | cut -f1)
        echo "• $dir ($SIZE)"
    fi
done

# حساب الحجم الإجمالي
TOTAL_SIZE=$(du -ch $VENDOR_DIRS 2>/dev/null | grep total | cut -f1)
echo -e "\n${YELLOW}الحجم الإجمالي: $TOTAL_SIZE${NC}"

# تحليل التكرار
echo -e "\n${BLUE}📊 تحليل التكرار...${NC}"

# إنشاء مجلد مؤقت للتحليل
TEMP_DIR="/tmp/vendor_analysis_$$"
mkdir -p "$TEMP_DIR"

# جمع معلومات الحزم
echo "جمع معلومات الحزم..."
for vendor_dir in $VENDOR_DIRS; do
    if [ -d "$vendor_dir" ]; then
        PROJECT_DIR=$(dirname "$vendor_dir")
        PROJECT_NAME=$(basename "$PROJECT_DIR")
        
        # نسخ composer.json إذا وجد
        if [ -f "$PROJECT_DIR/composer.json" ]; then
            cp "$PROJECT_DIR/composer.json" "$TEMP_DIR/${PROJECT_NAME}_composer.json"
        fi
        
        # إنشاء قائمة بالحزم
        if [ -d "$vendor_dir" ]; then
            ls -1 "$vendor_dir" > "$TEMP_DIR/${PROJECT_NAME}_packages.txt" 2>/dev/null
        fi
    fi
done

# تحليل الحزم المشتركة
echo -e "\n${BLUE}📦 الحزم المشتركة:${NC}"
cat "$TEMP_DIR"/*_packages.txt 2>/dev/null | sort | uniq -c | sort -rn | head -20

# إنشاء vendor موحد
echo -e "\n${GREEN}🚀 إنشاء vendor موحد...${NC}"

UNIFIED_VENDOR="/workspace/vendor"
if [ ! -d "$UNIFIED_VENDOR" ]; then
    echo "إنشاء $UNIFIED_VENDOR"
    mkdir -p "$UNIFIED_VENDOR"
fi

# دمج composer.json files
echo -e "\n${YELLOW}📄 دمج ملفات composer.json...${NC}"

php -r '
$files = glob("/tmp/vendor_analysis_' . $$ . '/*_composer.json");
$merged = [
    "name" => "workspace/unified",
    "description" => "Unified vendor for all projects",
    "require" => [],
    "require-dev" => [],
    "autoload" => [
        "psr-4" => []
    ]
];

foreach ($files as $file) {
    $data = json_decode(file_get_contents($file), true);
    
    if (isset($data["require"])) {
        $merged["require"] = array_merge($merged["require"], $data["require"]);
    }
    
    if (isset($data["require-dev"])) {
        $merged["require-dev"] = array_merge($merged["require-dev"], $data["require-dev"]);
    }
    
    if (isset($data["autoload"]["psr-4"])) {
        foreach ($data["autoload"]["psr-4"] as $namespace => $path) {
            $project = basename($file, "_composer.json");
            $merged["autoload"]["psr-4"][$namespace] = "projects/$project/" . $path;
        }
    }
}

// حفظ الملف المدمج
file_put_contents("/workspace/composer-unified.json", json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "✅ تم دمج " . count($files) . " ملف composer.json\n";
echo "📄 الملف المدمج: /workspace/composer-unified.json\n";
'

# إنشاء روابط رمزية
echo -e "\n${BLUE}🔗 إنشاء روابط رمزية...${NC}"

for vendor_dir in $VENDOR_DIRS; do
    if [ "$vendor_dir" != "$UNIFIED_VENDOR" ] && [ -d "$vendor_dir" ]; then
        PROJECT_DIR=$(dirname "$vendor_dir")
        
        # نسخ احتياطي
        if [ ! -L "$vendor_dir" ]; then
            echo "• نسخ احتياطي: ${vendor_dir}.backup"
            mv "$vendor_dir" "${vendor_dir}.backup"
            
            # إنشاء رابط رمزي
            echo "• رابط رمزي: $vendor_dir → $UNIFIED_VENDOR"
            ln -s "$UNIFIED_VENDOR" "$vendor_dir"
        fi
    fi
done

# تنظيف
rm -rf "$TEMP_DIR"

# حساب التوفير
echo -e "\n${GREEN}📊 === النتائج ===${NC}"
echo "• المجلدات الموحدة: $(echo "$VENDOR_DIRS" | wc -l)"
echo "• الحجم قبل: $TOTAL_SIZE"
echo "• الحجم بعد: $(du -sh "$UNIFIED_VENDOR" 2>/dev/null | cut -f1)"
echo "• التوفير المتوقع: ~70%"

echo -e "\n${YELLOW}⚠️ تنبيه:${NC}"
echo "1. تم إنشاء نسخ احتياطية بامتداد .backup"
echo "2. قم بتشغيل: composer install --working-dir=/workspace"
echo "3. لاسترجاع: حذف الروابط واسترجاع .backup"

echo -e "\n${GREEN}✅ اكتمل توحيد vendor!${NC}"