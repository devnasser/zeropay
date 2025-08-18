<?php
/**
 * نظام التخزين المؤقت متعدد الطبقات
 * Multi-tier Caching System
 */

class CacheSystem {
    private $memoryCache = [];
    private $sqliteDb;
    private $cacheDir;
    private $stats = [
        'hits' => 0,
        'misses' => 0,
        'memory_hits' => 0,
        'sqlite_hits' => 0,
        'file_hits' => 0
    ];
    
    public function __construct() {
        // إعداد مجلد التخزين المؤقت
        $this->cacheDir = '/workspace/system/cache/file-cache';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        // إعداد SQLite للتخزين المؤقت
        $dbPath = '/workspace/system/cache/app-cache.db';
        $this->sqliteDb = new SQLite3($dbPath);
        
        // تحسينات الأداء
        $this->sqliteDb->exec('PRAGMA journal_mode = WAL');
        $this->sqliteDb->exec('PRAGMA synchronous = NORMAL');
        $this->sqliteDb->exec('PRAGMA cache_size = -64000');
        $this->sqliteDb->exec('PRAGMA temp_store = MEMORY');
        
        // إنشاء جدول التخزين المؤقت
        $this->sqliteDb->exec('
            CREATE TABLE IF NOT EXISTS cache (
                key TEXT PRIMARY KEY,
                value TEXT,
                expiry INTEGER,
                created INTEGER,
                hits INTEGER DEFAULT 0
            )
        ');
        
        // إنشاء فهرس
        $this->sqliteDb->exec('CREATE INDEX IF NOT EXISTS idx_expiry ON cache(expiry)');
        
        echo "✅ نظام التخزين المؤقت جاهز\n";
    }
    
    /**
     * الحصول على قيمة من التخزين المؤقت
     */
    public function get($key) {
        // المستوى 1: الذاكرة
        if (isset($this->memoryCache[$key])) {
            $data = $this->memoryCache[$key];
            if ($data['expiry'] > time()) {
                $this->stats['hits']++;
                $this->stats['memory_hits']++;
                return unserialize($data['value']);
            } else {
                unset($this->memoryCache[$key]);
            }
        }
        
        // المستوى 2: SQLite
        $stmt = $this->sqliteDb->prepare('
            SELECT value, expiry FROM cache 
            WHERE key = :key AND expiry > :time
        ');
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $result = $stmt->execute()->fetchArray();
        
        if ($result) {
            $this->stats['hits']++;
            $this->stats['sqlite_hits']++;
            
            // تحديث عدد الزيارات
            $this->sqliteDb->exec("UPDATE cache SET hits = hits + 1 WHERE key = '$key'");
            
            // حفظ في الذاكرة للوصول السريع
            $this->memoryCache[$key] = [
                'value' => $result['value'],
                'expiry' => $result['expiry']
            ];
            
            return unserialize($result['value']);
        }
        
        // المستوى 3: ملفات
        $filePath = $this->getFilePath($key);
        if (file_exists($filePath)) {
            $data = unserialize(file_get_contents($filePath));
            if ($data['expiry'] > time()) {
                $this->stats['hits']++;
                $this->stats['file_hits']++;
                
                // حفظ في المستويات الأعلى
                $this->saveToUpperLevels($key, $data['value'], $data['expiry'] - time());
                
                return $data['value'];
            } else {
                unlink($filePath);
            }
        }
        
        $this->stats['misses']++;
        return null;
    }
    
    /**
     * حفظ قيمة في التخزين المؤقت
     */
    public function set($key, $value, $ttl = 3600) {
        $serialized = serialize($value);
        $expiry = time() + $ttl;
        
        // حفظ في الذاكرة
        $this->memoryCache[$key] = [
            'value' => $serialized,
            'expiry' => $expiry
        ];
        
        // حفظ في SQLite
        $stmt = $this->sqliteDb->prepare('
            INSERT OR REPLACE INTO cache (key, value, expiry, created)
            VALUES (:key, :value, :expiry, :created)
        ');
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $stmt->bindValue(':value', $serialized, SQLITE3_TEXT);
        $stmt->bindValue(':expiry', $expiry, SQLITE3_INTEGER);
        $stmt->bindValue(':created', time(), SQLITE3_INTEGER);
        $stmt->execute();
        
        // حفظ في الملفات للبيانات الكبيرة
        if (strlen($serialized) > 10240) { // أكبر من 10KB
            $filePath = $this->getFilePath($key);
            file_put_contents($filePath, serialize([
                'value' => $value,
                'expiry' => $expiry
            ]));
        }
        
        return true;
    }
    
    /**
     * حذف قيمة من التخزين المؤقت
     */
    public function delete($key) {
        // حذف من الذاكرة
        unset($this->memoryCache[$key]);
        
        // حذف من SQLite
        $this->sqliteDb->exec("DELETE FROM cache WHERE key = '$key'");
        
        // حذف من الملفات
        $filePath = $this->getFilePath($key);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        return true;
    }
    
    /**
     * مسح التخزين المؤقت بالكامل
     */
    public function flush() {
        // مسح الذاكرة
        $this->memoryCache = [];
        
        // مسح SQLite
        $this->sqliteDb->exec('DELETE FROM cache');
        
        // مسح الملفات
        array_map('unlink', glob($this->cacheDir . '/*.cache'));
        
        return true;
    }
    
    /**
     * تنظيف المدخلات المنتهية
     */
    public function cleanup() {
        $time = time();
        
        // تنظيف SQLite
        $deleted = $this->sqliteDb->exec("DELETE FROM cache WHERE expiry < $time");
        
        // تنظيف الملفات
        $files = glob($this->cacheDir . '/*.cache');
        $filesDeleted = 0;
        foreach ($files as $file) {
            $data = unserialize(file_get_contents($file));
            if ($data['expiry'] < $time) {
                unlink($file);
                $filesDeleted++;
            }
        }
        
        return [
            'sqlite' => $this->sqliteDb->changes(),
            'files' => $filesDeleted
        ];
    }
    
    /**
     * الحصول على إحصائيات التخزين المؤقت
     */
    public function getStats() {
        $totalHits = $this->stats['hits'];
        $totalRequests = $totalHits + $this->stats['misses'];
        $hitRate = $totalRequests > 0 ? round(($totalHits / $totalRequests) * 100, 2) : 0;
        
        // إحصائيات SQLite
        $sqliteStats = $this->sqliteDb->querySingle('
            SELECT COUNT(*) as count, SUM(length(value)) as size 
            FROM cache WHERE expiry > ' . time(), true
        );
        
        // إحصائيات الملفات
        $fileCount = count(glob($this->cacheDir . '/*.cache'));
        
        return [
            'hit_rate' => $hitRate . '%',
            'total_hits' => $totalHits,
            'total_misses' => $this->stats['misses'],
            'memory_hits' => $this->stats['memory_hits'],
            'sqlite_hits' => $this->stats['sqlite_hits'],
            'file_hits' => $this->stats['file_hits'],
            'memory_items' => count($this->memoryCache),
            'sqlite_items' => $sqliteStats['count'],
            'sqlite_size' => round($sqliteStats['size'] / 1048576, 2) . ' MB',
            'file_items' => $fileCount
        ];
    }
    
    /**
     * مساعد: الحصول على مسار الملف
     */
    private function getFilePath($key) {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
    
    /**
     * مساعد: حفظ في المستويات الأعلى
     */
    private function saveToUpperLevels($key, $value, $ttl) {
        $serialized = serialize($value);
        $expiry = time() + $ttl;
        
        // حفظ في الذاكرة
        $this->memoryCache[$key] = [
            'value' => $serialized,
            'expiry' => $expiry
        ];
        
        // حفظ في SQLite إذا كان صغيراً
        if (strlen($serialized) < 10240) {
            $stmt = $this->sqliteDb->prepare('
                INSERT OR REPLACE INTO cache (key, value, expiry, created)
                VALUES (:key, :value, :expiry, :created)
            ');
            $stmt->bindValue(':key', $key, SQLITE3_TEXT);
            $stmt->bindValue(':value', $serialized, SQLITE3_TEXT);
            $stmt->bindValue(':expiry', $expiry, SQLITE3_INTEGER);
            $stmt->bindValue(':created', time(), SQLITE3_INTEGER);
            $stmt->execute();
        }
    }
}

// اختبار النظام
echo "🚀 === نظام التخزين المؤقت متعدد الطبقات ===\n\n";

$cache = new CacheSystem();

// اختبار الأداء
echo "📊 اختبار الأداء...\n";

$testData = [
    'users' => range(1, 1000),
    'products' => array_map(function($i) { 
        return ['id' => $i, 'name' => "Product $i", 'price' => rand(10, 1000)];
    }, range(1, 500)),
    'config' => ['app_name' => 'Test App', 'version' => '1.0', 'features' => range(1, 100)]
];

// اختبار الكتابة
$writeStart = microtime(true);
foreach ($testData as $key => $data) {
    $cache->set($key, $data, 3600);
}
$writeTime = round((microtime(true) - $writeStart) * 1000, 2);
echo "✅ وقت الكتابة: {$writeTime}ms\n";

// اختبار القراءة
$readStart = microtime(true);
for ($i = 0; $i < 100; $i++) {
    $cache->get('users');
    $cache->get('products');
    $cache->get('config');
}
$readTime = round((microtime(true) - $readStart) * 1000, 2);
echo "✅ وقت القراءة (300 عملية): {$readTime}ms\n";
echo "⚡ متوسط وقت القراءة: " . round($readTime / 300, 2) . "ms\n";

// عرض الإحصائيات
echo "\n📈 الإحصائيات:\n";
$stats = $cache->getStats();
foreach ($stats as $key => $value) {
    echo "• $key: $value\n";
}

echo "\n✅ النظام جاهز للاستخدام!\n";