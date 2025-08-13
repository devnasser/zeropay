<?php
/**
 * نظام تحسين قاعدة البيانات
 * Database Optimization System
 */

class DatabaseOptimizer {
    private $connections = [];
    private $queryCache = [];
    private $stats = [
        'queries_analyzed' => 0,
        'indexes_suggested' => 0,
        'queries_optimized' => 0,
        'cache_hits' => 0,
        'total_time_saved' => 0
    ];
    
    /**
     * تحليل استعلام SQL
     */
    public function analyzeQuery($query) {
        $this->stats['queries_analyzed']++;
        $analysis = [
            'original' => $query,
            'optimized' => $query,
            'suggestions' => [],
            'estimated_improvement' => 0
        ];
        
        // تحليل SELECT
        if (preg_match('/^SELECT/i', $query)) {
            $analysis = $this->analyzeSelectQuery($query);
        }
        
        // تحليل JOIN
        if (stripos($query, 'JOIN') !== false) {
            $analysis['suggestions'][] = $this->analyzeJoins($query);
        }
        
        // تحليل WHERE
        if (stripos($query, 'WHERE') !== false) {
            $analysis['suggestions'][] = $this->analyzeWhereClause($query);
        }
        
        return $analysis;
    }
    
    /**
     * تحليل استعلام SELECT
     */
    private function analyzeSelectQuery($query) {
        $suggestions = [];
        
        // تحقق من SELECT *
        if (preg_match('/SELECT\s+\*/i', $query)) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => 'تجنب استخدام SELECT * - حدد الأعمدة المطلوبة فقط',
                'impact' => 'high'
            ];
        }
        
        // تحقق من LIMIT
        if (!preg_match('/LIMIT\s+\d+/i', $query)) {
            $suggestions[] = [
                'type' => 'suggestion',
                'message' => 'أضف LIMIT لتحديد عدد النتائج',
                'impact' => 'medium'
            ];
        }
        
        // تحقق من ORDER BY مع LIMIT
        if (preg_match('/ORDER\s+BY/i', $query) && !preg_match('/LIMIT/i', $query)) {
            $suggestions[] = [
                'type' => 'warning',
                'message' => 'ORDER BY بدون LIMIT قد يكون بطيئاً',
                'impact' => 'high'
            ];
        }
        
        return [
            'original' => $query,
            'optimized' => $this->optimizeSelectQuery($query),
            'suggestions' => $suggestions,
            'estimated_improvement' => count($suggestions) * 15
        ];
    }
    
    /**
     * تحسين استعلام SELECT
     */
    private function optimizeSelectQuery($query) {
        $optimized = $query;
        
        // إضافة LIMIT إذا لم يكن موجوداً
        if (!preg_match('/LIMIT/i', $optimized)) {
            $optimized .= ' LIMIT 100';
        }
        
        $this->stats['queries_optimized']++;
        return $optimized;
    }
    
    /**
     * تحليل JOINs
     */
    private function analyzeJoins($query) {
        $joinCount = substr_count(strtoupper($query), 'JOIN');
        
        if ($joinCount > 3) {
            return [
                'type' => 'warning',
                'message' => "عدد كبير من JOINs ($joinCount) - فكر في تقسيم الاستعلام",
                'impact' => 'high'
            ];
        }
        
        return [
            'type' => 'info',
            'message' => "عدد JOINs: $joinCount",
            'impact' => 'low'
        ];
    }
    
    /**
     * تحليل WHERE clause
     */
    private function analyzeWhereClause($query) {
        // استخراج الأعمدة في WHERE
        preg_match_all('/WHERE\s+(\w+)\s*=/i', $query, $matches);
        
        if (!empty($matches[1])) {
            $columns = array_unique($matches[1]);
            $this->stats['indexes_suggested'] += count($columns);
            
            return [
                'type' => 'suggestion',
                'message' => 'تأكد من وجود فهارس على: ' . implode(', ', $columns),
                'impact' => 'high'
            ];
        }
        
        return [
            'type' => 'info',
            'message' => 'WHERE clause محلل',
            'impact' => 'low'
        ];
    }
    
    /**
     * نظام التخزين المؤقت للاستعلامات
     */
    public function cacheQuery($query, $result, $ttl = 300) {
        $key = md5($query);
        $this->queryCache[$key] = [
            'result' => $result,
            'expiry' => time() + $ttl,
            'hits' => 0
        ];
    }
    
    /**
     * الحصول على نتيجة من التخزين المؤقت
     */
    public function getCachedQuery($query) {
        $key = md5($query);
        
        if (isset($this->queryCache[$key])) {
            $cache = $this->queryCache[$key];
            
            if ($cache['expiry'] > time()) {
                $this->queryCache[$key]['hits']++;
                $this->stats['cache_hits']++;
                return $cache['result'];
            } else {
                unset($this->queryCache[$key]);
            }
        }
        
        return null;
    }
    
    /**
     * إنشاء نص SQL لإنشاء الفهارس المقترحة
     */
    public function generateIndexSQL($table, $columns) {
        $indexName = 'idx_' . $table . '_' . implode('_', $columns);
        $columnList = implode(', ', array_map(function($col) {
            return "`$col`";
        }, $columns));
        
        return "CREATE INDEX `$indexName` ON `$table` ($columnList);";
    }
    
    /**
     * تحليل الأداء الإجمالي
     */
    public function getPerformanceReport() {
        return [
            'queries_analyzed' => $this->stats['queries_analyzed'],
            'indexes_suggested' => $this->stats['indexes_suggested'],
            'queries_optimized' => $this->stats['queries_optimized'],
            'cache_hits' => $this->stats['cache_hits'],
            'cache_hit_rate' => $this->stats['queries_analyzed'] > 0 
                ? round(($this->stats['cache_hits'] / $this->stats['queries_analyzed']) * 100, 2) . '%'
                : '0%'
        ];
    }
}

/**
 * نظام Connection Pooling
 */
class ConnectionPool {
    private static $instance = null;
    private $connections = [];
    private $maxConnections = 10;
    private $activeConnections = 0;
    
    private function __construct() {}
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * الحصول على اتصال
     */
    public function getConnection($config) {
        $key = md5(serialize($config));
        
        // البحث عن اتصال متاح
        if (isset($this->connections[$key])) {
            foreach ($this->connections[$key] as &$conn) {
                if (!$conn['in_use']) {
                    $conn['in_use'] = true;
                    $conn['last_used'] = time();
                    return $conn['connection'];
                }
            }
        }
        
        // إنشاء اتصال جديد إذا لم نصل للحد الأقصى
        if ($this->activeConnections < $this->maxConnections) {
            $connection = $this->createConnection($config);
            
            if (!isset($this->connections[$key])) {
                $this->connections[$key] = [];
            }
            
            $this->connections[$key][] = [
                'connection' => $connection,
                'in_use' => true,
                'created' => time(),
                'last_used' => time()
            ];
            
            $this->activeConnections++;
            return $connection;
        }
        
        // الانتظار حتى يتوفر اتصال
        return $this->waitForConnection($config);
    }
    
    /**
     * إرجاع اتصال للمجموعة
     */
    public function releaseConnection($connection) {
        foreach ($this->connections as &$pool) {
            foreach ($pool as &$conn) {
                if ($conn['connection'] === $connection) {
                    $conn['in_use'] = false;
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * إنشاء اتصال جديد (محاكاة)
     */
    private function createConnection($config) {
        // في الواقع، هنا يتم إنشاء اتصال حقيقي بقاعدة البيانات
        return new stdClass(); // محاكاة
    }
    
    /**
     * الانتظار حتى يتوفر اتصال
     */
    private function waitForConnection($config) {
        // في الواقع، يجب تنفيذ آلية انتظار
        usleep(100000); // انتظار 100ms
        return $this->getConnection($config);
    }
    
    /**
     * تنظيف الاتصالات القديمة
     */
    public function cleanup() {
        $now = time();
        $timeout = 300; // 5 دقائق
        
        foreach ($this->connections as $key => &$pool) {
            foreach ($pool as $i => $conn) {
                if (!$conn['in_use'] && ($now - $conn['last_used']) > $timeout) {
                    unset($pool[$i]);
                    $this->activeConnections--;
                }
            }
            
            if (empty($pool)) {
                unset($this->connections[$key]);
            }
        }
    }
}

// اختبار النظام
echo "🚀 === نظام تحسين قاعدة البيانات ===\n\n";

$optimizer = new DatabaseOptimizer();

// أمثلة على استعلامات للتحليل
$queries = [
    "SELECT * FROM users WHERE status = 'active'",
    "SELECT id, name, email FROM users WHERE created_at > '2024-01-01' ORDER BY created_at DESC",
    "SELECT u.*, p.* FROM users u JOIN profiles p ON u.id = p.user_id JOIN orders o ON u.id = o.user_id JOIN payments pay ON o.id = pay.order_id WHERE u.status = 'active'",
    "SELECT COUNT(*) FROM products WHERE category_id = 5 AND price < 100",
    "SELECT * FROM logs ORDER BY created_at DESC"
];

echo "📊 تحليل الاستعلامات:\n";
echo "─────────────────────\n\n";

foreach ($queries as $i => $query) {
    echo "🔍 استعلام #" . ($i + 1) . ":\n";
    echo "الأصلي: " . substr($query, 0, 60) . "...\n";
    
    $analysis = $optimizer->analyzeQuery($query);
    
    if ($analysis['optimized'] !== $analysis['original']) {
        echo "المحسّن: " . substr($analysis['optimized'], 0, 60) . "...\n";
    }
    
    if (!empty($analysis['suggestions'])) {
        echo "💡 الاقتراحات:\n";
        foreach ($analysis['suggestions'] as $suggestion) {
            if (is_array($suggestion) && isset($suggestion['message'])) {
                echo "   • " . $suggestion['message'] . " (التأثير: " . $suggestion['impact'] . ")\n";
            }
        }
    }
    
    echo "⚡ التحسين المتوقع: " . $analysis['estimated_improvement'] . "%\n\n";
}

// اختبار Connection Pool
echo "\n🔗 === اختبار Connection Pool ===\n";

$pool = ConnectionPool::getInstance();

// محاكاة طلبات متعددة
echo "إنشاء اتصالات...\n";
$connections = [];
for ($i = 0; $i < 5; $i++) {
    $conn = $pool->getConnection(['host' => 'localhost', 'db' => 'test']);
    $connections[] = $conn;
    echo "• اتصال #" . ($i + 1) . " تم إنشاؤه\n";
}

// إرجاع بعض الاتصالات
echo "\nإرجاع الاتصالات...\n";
for ($i = 0; $i < 3; $i++) {
    $pool->releaseConnection($connections[$i]);
    echo "• اتصال #" . ($i + 1) . " تم إرجاعه\n";
}

// تقرير الأداء
echo "\n📈 === تقرير الأداء ===\n";
$report = $optimizer->getPerformanceReport();
foreach ($report as $key => $value) {
    echo "• $key: $value\n";
}

// اقتراح إنشاء فهرس
echo "\n🔧 === SQL لإنشاء الفهارس المقترحة ===\n";
echo $optimizer->generateIndexSQL('users', ['status', 'created_at']) . "\n";
echo $optimizer->generateIndexSQL('products', ['category_id', 'price']) . "\n";

echo "\n✅ نظام تحسين قاعدة البيانات جاهز!\n";