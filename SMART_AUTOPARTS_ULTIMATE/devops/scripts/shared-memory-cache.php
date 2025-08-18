<?php
/**
 * نظام التخزين المؤقت بالذاكرة المشتركة
 * Shared Memory Cache System (APCu Alternative)
 */

class SharedMemoryCache {
    private $shmId;
    private $semId;
    private $shmSize = 67108864; // 64MB
    private $maxKeys = 10000;
    private $keyFile = '/workspace/system/cache/shm.key';
    
    public function __construct() {
        // إنشاء مفتاح فريد
        if (!file_exists($this->keyFile)) {
            @mkdir(dirname($this->keyFile), 0755, true);
            file_put_contents($this->keyFile, uniqid());
        }
        
        $key = ftok($this->keyFile, 'c');
        
        // إنشاء أو فتح الذاكرة المشتركة
        $this->shmId = @shmop_open($key, "c", 0644, $this->shmSize);
        if (!$this->shmId) {
            $this->shmId = shmop_open($key, "w", 0644, $this->shmSize);
        }
        
        // إنشاء semaphore للتزامن
        $this->semId = sem_get($key, 1);
        
        // تهيئة الذاكرة إذا كانت فارغة
        $this->initialize();
    }
    
    /**
     * تهيئة الذاكرة المشتركة
     */
    private function initialize() {
        sem_acquire($this->semId);
        
        $data = shmop_read($this->shmId, 0, 100);
        if (substr($data, 0, 5) !== 'CACHE') {
            // كتابة الهيدر
            $header = [
                'magic' => 'CACHE',
                'version' => '1.0',
                'items' => 0,
                'index' => []
            ];
            $this->writeData(0, serialize($header));
        }
        
        sem_release($this->semId);
    }
    
    /**
     * الحصول على قيمة من الذاكرة المشتركة
     */
    public function get($key) {
        sem_acquire($this->semId);
        
        try {
            $header = $this->getHeader();
            
            if (!isset($header['index'][$key])) {
                return null;
            }
            
            $meta = $header['index'][$key];
            
            // التحقق من انتهاء الصلاحية
            if ($meta['expiry'] > 0 && $meta['expiry'] < time()) {
                unset($header['index'][$key]);
                $header['items']--;
                $this->writeData(0, serialize($header));
                return null;
            }
            
            // قراءة البيانات
            $data = shmop_read($this->shmId, $meta['offset'], $meta['size']);
            $value = unserialize($data);
            
            // تحديث عدد الزيارات
            $header['index'][$key]['hits']++;
            $this->writeData(0, serialize($header));
            
            return $value;
            
        } finally {
            sem_release($this->semId);
        }
    }
    
    /**
     * حفظ قيمة في الذاكرة المشتركة
     */
    public function set($key, $value, $ttl = 3600) {
        sem_acquire($this->semId);
        
        try {
            $header = $this->getHeader();
            $serialized = serialize($value);
            $size = strlen($serialized);
            
            // البحث عن مساحة فارغة
            $offset = $this->findFreeSpace($size);
            
            if ($offset === false) {
                // تنظيف الذاكرة وإعادة المحاولة
                $this->gc();
                $offset = $this->findFreeSpace($size);
                
                if ($offset === false) {
                    throw new Exception("لا توجد مساحة كافية في الذاكرة المشتركة");
                }
            }
            
            // كتابة البيانات
            $this->writeData($offset, $serialized);
            
            // تحديث الفهرس
            $header['index'][$key] = [
                'offset' => $offset,
                'size' => $size,
                'expiry' => $ttl > 0 ? time() + $ttl : 0,
                'created' => time(),
                'hits' => 0
            ];
            
            if (!isset($header['index'][$key])) {
                $header['items']++;
            }
            
            $this->writeData(0, serialize($header));
            
            return true;
            
        } finally {
            sem_release($this->semId);
        }
    }
    
    /**
     * حذف قيمة
     */
    public function delete($key) {
        sem_acquire($this->semId);
        
        try {
            $header = $this->getHeader();
            
            if (isset($header['index'][$key])) {
                unset($header['index'][$key]);
                $header['items']--;
                $this->writeData(0, serialize($header));
                return true;
            }
            
            return false;
            
        } finally {
            sem_release($this->semId);
        }
    }
    
    /**
     * مسح جميع البيانات
     */
    public function flush() {
        sem_acquire($this->semId);
        
        try {
            $header = [
                'magic' => 'CACHE',
                'version' => '1.0',
                'items' => 0,
                'index' => []
            ];
            $this->writeData(0, serialize($header));
            
            return true;
            
        } finally {
            sem_release($this->semId);
        }
    }
    
    /**
     * الحصول على الإحصائيات
     */
    public function stats() {
        sem_acquire($this->semId);
        
        try {
            $header = $this->getHeader();
            
            $totalSize = 0;
            $totalHits = 0;
            $expired = 0;
            
            foreach ($header['index'] as $key => $meta) {
                $totalSize += $meta['size'];
                $totalHits += $meta['hits'];
                
                if ($meta['expiry'] > 0 && $meta['expiry'] < time()) {
                    $expired++;
                }
            }
            
            return [
                'items' => $header['items'],
                'expired' => $expired,
                'size' => round($totalSize / 1048576, 2) . ' MB',
                'usage' => round(($totalSize / $this->shmSize) * 100, 2) . '%',
                'hits' => $totalHits,
                'shm_size' => round($this->shmSize / 1048576, 2) . ' MB'
            ];
            
        } finally {
            sem_release($this->semId);
        }
    }
    
    /**
     * جمع القمامة (حذف المنتهية)
     */
    private function gc() {
        $header = $this->getHeader();
        $time = time();
        
        foreach ($header['index'] as $key => $meta) {
            if ($meta['expiry'] > 0 && $meta['expiry'] < $time) {
                unset($header['index'][$key]);
                $header['items']--;
            }
        }
        
        $this->writeData(0, serialize($header));
    }
    
    /**
     * البحث عن مساحة فارغة
     */
    private function findFreeSpace($size) {
        $header = $this->getHeader();
        $headerSize = strlen(serialize($header)) + 1000; // مع هامش أمان
        
        // ترتيب العناصر حسب الموضع
        $items = $header['index'];
        uasort($items, function($a, $b) {
            return $a['offset'] - $b['offset'];
        });
        
        // البحث عن فجوة كافية
        $lastEnd = $headerSize;
        
        foreach ($items as $item) {
            $gap = $item['offset'] - $lastEnd;
            if ($gap >= $size) {
                return $lastEnd;
            }
            $lastEnd = $item['offset'] + $item['size'];
        }
        
        // التحقق من المساحة في النهاية
        if ($lastEnd + $size < $this->shmSize) {
            return $lastEnd;
        }
        
        return false;
    }
    
    /**
     * قراءة الهيدر
     */
    private function getHeader() {
        $data = shmop_read($this->shmId, 0, 100000);
        $end = strpos($data, "\0");
        if ($end !== false) {
            $data = substr($data, 0, $end);
        }
        return unserialize($data);
    }
    
    /**
     * كتابة البيانات
     */
    private function writeData($offset, $data) {
        $data .= "\0"; // null terminator
        shmop_write($this->shmId, $data, $offset);
    }
    
    public function __destruct() {
        // لا نغلق الذاكرة المشتركة لأنها مشتركة بين العمليات
    }
}

// اختبار النظام
echo "🚀 === نظام التخزين المؤقت بالذاكرة المشتركة ===\n\n";

try {
    $cache = new SharedMemoryCache();
    
    // اختبار الكتابة
    echo "📝 اختبار الكتابة...\n";
    $startWrite = microtime(true);
    
    for ($i = 0; $i < 100; $i++) {
        $cache->set("key_$i", [
            'id' => $i,
            'data' => str_repeat('A', 1000),
            'time' => time()
        ], 3600);
    }
    
    $writeTime = round((microtime(true) - $startWrite) * 1000, 2);
    echo "✅ كتابة 100 عنصر: {$writeTime}ms\n";
    
    // اختبار القراءة
    echo "\n📖 اختبار القراءة...\n";
    $startRead = microtime(true);
    $hits = 0;
    
    for ($i = 0; $i < 1000; $i++) {
        $key = "key_" . rand(0, 99);
        $value = $cache->get($key);
        if ($value !== null) $hits++;
    }
    
    $readTime = round((microtime(true) - $startRead) * 1000, 2);
    echo "✅ قراءة 1000 عملية: {$readTime}ms\n";
    echo "⚡ متوسط القراءة: " . round($readTime / 1000, 2) . "ms\n";
    echo "📊 معدل الإصابة: " . round(($hits / 1000) * 100, 2) . "%\n";
    
    // عرض الإحصائيات
    echo "\n📈 الإحصائيات:\n";
    $stats = $cache->stats();
    foreach ($stats as $key => $value) {
        echo "• $key: $value\n";
    }
    
    echo "\n✅ النظام يعمل بنجاح!\n";
    
} catch (Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
}