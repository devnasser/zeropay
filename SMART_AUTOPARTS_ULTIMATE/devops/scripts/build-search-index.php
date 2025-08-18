<?php
/**
 * بناء فهرس البحث السريع
 * Fast Search Index Builder
 */

echo "🔍 === بناء فهرس البحث السريع ===\n";
echo "التاريخ: " . date('Y-m-d H:i:s') . "\n\n";

$startTime = microtime(true);

// إعدادات
$workspacePath = '/workspace';
$indexPath = '/workspace/system/cache/search-index.sqlite';
$excludeDirs = ['.git', 'vendor', 'node_modules', '.cache', 'storage/logs'];

// إنشاء مجلد الكاش إذا لم يكن موجوداً
$cacheDir = dirname($indexPath);
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// حذف الفهرس القديم إذا كان موجوداً
if (file_exists($indexPath)) {
    unlink($indexPath);
}

// إنشاء قاعدة البيانات
$db = new SQLite3($indexPath);

// تحسينات الأداء لـ SQLite
$db->exec('PRAGMA journal_mode = WAL');
$db->exec('PRAGMA synchronous = NORMAL');
$db->exec('PRAGMA cache_size = -64000');
$db->exec('PRAGMA temp_store = MEMORY');
$db->exec('PRAGMA mmap_size = 268435456');

// إنشاء الجداول
echo "📊 إنشاء جداول الفهرس...\n";

$db->exec('
    CREATE TABLE IF NOT EXISTS files (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        path TEXT UNIQUE NOT NULL,
        filename TEXT NOT NULL,
        extension TEXT,
        size INTEGER,
        modified INTEGER,
        content TEXT,
        classes TEXT,
        functions TEXT,
        variables TEXT
    )
');

// إنشاء الفهارس
$db->exec('CREATE INDEX idx_filename ON files(filename)');
$db->exec('CREATE INDEX idx_extension ON files(extension)');
$db->exec('CREATE INDEX idx_modified ON files(modified)');

// إنشاء جدول البحث النصي الكامل
$db->exec('
    CREATE VIRTUAL TABLE file_search USING fts5(
        path, filename, content, classes, functions,
        tokenize="unicode61 remove_diacritics 2"
    )
');

// إعداد العبارات المحضرة
$insertStmt = $db->prepare('
    INSERT INTO files (path, filename, extension, size, modified, content, classes, functions, variables)
    VALUES (:path, :filename, :extension, :size, :modified, :content, :classes, :functions, :variables)
');

$ftsStmt = $db->prepare('
    INSERT INTO file_search (path, filename, content, classes, functions)
    VALUES (:path, :filename, :content, :classes, :functions)
');

// دالة لاستخراج معلومات PHP
function extractPHPInfo($content) {
    $info = [
        'classes' => [],
        'functions' => [],
        'variables' => []
    ];
    
    // استخراج الأصناف
    if (preg_match_all('/class\s+(\w+)/', $content, $matches)) {
        $info['classes'] = array_unique($matches[1]);
    }
    
    // استخراج الدوال
    if (preg_match_all('/function\s+(\w+)/', $content, $matches)) {
        $info['functions'] = array_unique($matches[1]);
    }
    
    // استخراج المتغيرات العامة
    if (preg_match_all('/\$(\w+)\s*=/', $content, $matches)) {
        $info['variables'] = array_unique($matches[1]);
    }
    
    return $info;
}

// دالة للبحث عن الملفات
function findFiles($dir, $excludeDirs, &$db, &$insertStmt, &$ftsStmt) {
    static $fileCount = 0;
    static $totalSize = 0;
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        $path = $file->getPathname();
        
        // تجاهل المجلدات المستثناة
        foreach ($excludeDirs as $exclude) {
            if (strpos($path, '/' . $exclude . '/') !== false) {
                continue 2;
            }
        }
        
        if ($file->isFile()) {
            $fileCount++;
            $filename = $file->getFilename();
            $extension = $file->getExtension();
            $size = $file->getSize();
            $modified = $file->getMTime();
            $totalSize += $size;
            
            // قراءة المحتوى للملفات النصية الصغيرة
            $content = '';
            $classes = '';
            $functions = '';
            $variables = '';
            
            if ($size < 1048576 && in_array($extension, ['php', 'js', 'css', 'html', 'txt', 'md', 'json', 'xml', 'yml', 'yaml'])) {
                $content = @file_get_contents($path);
                
                if ($extension === 'php' && $content) {
                    $info = extractPHPInfo($content);
                    $classes = implode(',', $info['classes']);
                    $functions = implode(',', $info['functions']);
                    $variables = implode(',', $info['variables']);
                }
            }
            
            // إدراج في قاعدة البيانات
            $insertStmt->bindValue(':path', $path, SQLITE3_TEXT);
            $insertStmt->bindValue(':filename', $filename, SQLITE3_TEXT);
            $insertStmt->bindValue(':extension', $extension, SQLITE3_TEXT);
            $insertStmt->bindValue(':size', $size, SQLITE3_INTEGER);
            $insertStmt->bindValue(':modified', $modified, SQLITE3_INTEGER);
            $insertStmt->bindValue(':content', substr($content, 0, 10000), SQLITE3_TEXT);
            $insertStmt->bindValue(':classes', $classes, SQLITE3_TEXT);
            $insertStmt->bindValue(':functions', $functions, SQLITE3_TEXT);
            $insertStmt->bindValue(':variables', $variables, SQLITE3_TEXT);
            $insertStmt->execute();
            
            // إدراج في جدول البحث النصي
            $ftsStmt->bindValue(':path', $path, SQLITE3_TEXT);
            $ftsStmt->bindValue(':filename', $filename, SQLITE3_TEXT);
            $ftsStmt->bindValue(':content', substr($content, 0, 10000), SQLITE3_TEXT);
            $ftsStmt->bindValue(':classes', $classes, SQLITE3_TEXT);
            $ftsStmt->bindValue(':functions', $functions, SQLITE3_TEXT);
            $ftsStmt->execute();
            
            if ($fileCount % 1000 === 0) {
                echo "\r📁 معالجة الملفات: $fileCount (" . round($totalSize / 1048576, 2) . " MB)";
            }
        }
    }
    
    return [$fileCount, $totalSize];
}

// بدء الفهرسة
echo "🚀 بدء فهرسة الملفات...\n";

$db->exec('BEGIN TRANSACTION');
[$fileCount, $totalSize] = findFiles($workspacePath, $excludeDirs, $db, $insertStmt, $ftsStmt);
$db->exec('COMMIT');

echo "\n\n✅ اكتملت الفهرسة!\n";

// تحسين قاعدة البيانات
echo "\n🔧 تحسين قاعدة البيانات...\n";
$db->exec('ANALYZE');
$db->exec('VACUUM');

// إحصائيات
$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

echo "\n📊 === الإحصائيات ===\n";
echo "• عدد الملفات المفهرسة: " . number_format($fileCount) . "\n";
echo "• الحجم الإجمالي: " . round($totalSize / 1048576, 2) . " MB\n";
echo "• حجم الفهرس: " . round(filesize($indexPath) / 1048576, 2) . " MB\n";
echo "• وقت البناء: {$duration} ثانية\n";
echo "• السرعة: " . round($fileCount / $duration) . " ملف/ثانية\n";

// اختبار البحث
echo "\n🔍 اختبار البحث السريع...\n";
$testStart = microtime(true);
$result = $db->querySingle("SELECT COUNT(*) FROM file_search WHERE content MATCH 'function'");
$testEnd = microtime(true);
$searchTime = round(($testEnd - $testStart) * 1000, 2);

echo "• نتائج البحث عن 'function': $result ملف\n";
echo "• وقت البحث: {$searchTime}ms\n";

$db->close();

echo "\n✅ الفهرس جاهز للاستخدام: $indexPath\n";
echo "\n💡 استخدم /workspace/system/scripts/fast-search.sh للبحث السريع\n";