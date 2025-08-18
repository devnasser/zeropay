<?php

/**
 * Benchmark Script - اختبار الأداء المتقدم
 * يقيس الأداء بدقة عالية ويقارن النتائج
 */

class Benchmark {
    private $results = [];
    
    public function run() {
        echo "\n🚀 === اختبار الأداء المتقدم ===\n\n";
        
        $this->testCachePerformance();
        $this->testDatabasePerformance();
        $this->testFileSystemPerformance();
        $this->testPHPPerformance();
        $this->testSearchPerformance();
        
        $this->printResults();
    }
    
    private function measure($name, callable $callback, $iterations = 1) {
        $start = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $callback();
        }
        
        $time = microtime(true) - $start;
        $avgTime = $time / $iterations;
        
        $this->results[$name] = [
            'total_time' => round($time, 6),
            'avg_time' => round($avgTime, 6),
            'iterations' => $iterations,
            'ops_per_sec' => round($iterations / $time, 2)
        ];
        
        return $avgTime;
    }
    
    private function testCachePerformance() {
        echo "🧠 1. اختبار أداء التخزين المؤقت\n";
        echo "--------------------------------\n";
        
        require_once '/workspace/system/scripts/cache-manager.php';
        $cache = new CacheManager();
        
        // اختبار الكتابة
        $writeTime = $this->measure('Cache Write (1000 keys)', function() use ($cache) {
            for ($i = 0; $i < 1000; $i++) {
                $cache->set("bench_key_$i", [
                    'id' => $i,
                    'data' => str_repeat('test', 100),
                    'timestamp' => time()
                ]);
            }
        });
        echo "✅ كتابة 1000 مفتاح: {$writeTime}ms لكل مفتاح\n";
        
        // اختبار القراءة
        $readTime = $this->measure('Cache Read (1000 keys)', function() use ($cache) {
            for ($i = 0; $i < 1000; $i++) {
                $cache->get("bench_key_$i");
            }
        });
        echo "✅ قراءة 1000 مفتاح: {$readTime}ms لكل مفتاح\n";
        
        // اختبار معدل الإصابة
        $stats = $cache->getStats();
        echo "📊 معدل الإصابة: {$stats['hit_rate']}\n\n";
    }
    
    private function testDatabasePerformance() {
        echo "💾 2. اختبار أداء قاعدة البيانات\n";
        echo "--------------------------------\n";
        
        $db = new PDO('sqlite:/tmp/benchmark.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // إنشاء الجدول
        $db->exec("CREATE TABLE IF NOT EXISTS benchmark (
            id INTEGER PRIMARY KEY,
            name TEXT,
            value TEXT,
            created_at INTEGER
        )");
        
        // اختبار الإدراج
        $insertTime = $this->measure('DB Insert (1000 rows)', function() use ($db) {
            $stmt = $db->prepare("INSERT INTO benchmark (name, value, created_at) VALUES (?, ?, ?)");
            for ($i = 0; $i < 1000; $i++) {
                $stmt->execute(["name_$i", "value_$i", time()]);
            }
        });
        echo "✅ إدراج 1000 سجل: {$insertTime}ms لكل سجل\n";
        
        // اختبار البحث
        $selectTime = $this->measure('DB Select (1000 queries)', function() use ($db) {
            for ($i = 0; $i < 1000; $i++) {
                $db->query("SELECT * FROM benchmark WHERE name = 'name_$i'")->fetch();
            }
        });
        echo "✅ بحث 1000 مرة: {$selectTime}ms لكل بحث\n";
        
        // اختبار التحديث
        $updateTime = $this->measure('DB Update (1000 rows)', function() use ($db) {
            $stmt = $db->prepare("UPDATE benchmark SET value = ? WHERE name = ?");
            for ($i = 0; $i < 1000; $i++) {
                $stmt->execute(["updated_$i", "name_$i"]);
            }
        });
        echo "✅ تحديث 1000 سجل: {$updateTime}ms لكل تحديث\n\n";
        
        unlink('/tmp/benchmark.db');
    }
    
    private function testFileSystemPerformance() {
        echo "📁 3. اختبار أداء نظام الملفات\n";
        echo "-------------------------------\n";
        
        $testDir = '/tmp/benchmark_files';
        @mkdir($testDir);
        
        // اختبار كتابة الملفات
        $writeTime = $this->measure('File Write (100 files)', function() use ($testDir) {
            for ($i = 0; $i < 100; $i++) {
                file_put_contents("$testDir/file_$i.txt", str_repeat("test data\n", 100));
            }
        });
        echo "✅ كتابة 100 ملف: {$writeTime}ms لكل ملف\n";
        
        // اختبار قراءة الملفات
        $readTime = $this->measure('File Read (100 files)', function() use ($testDir) {
            for ($i = 0; $i < 100; $i++) {
                file_get_contents("$testDir/file_$i.txt");
            }
        });
        echo "✅ قراءة 100 ملف: {$readTime}ms لكل ملف\n";
        
        // اختبار البحث في الملفات
        $searchTime = $this->measure('File Search (glob)', function() use ($testDir) {
            glob("$testDir/*.txt");
        }, 100);
        echo "✅ البحث عن الملفات: {$searchTime}ms\n\n";
        
        // تنظيف
        array_map('unlink', glob("$testDir/*"));
        rmdir($testDir);
    }
    
    private function testPHPPerformance() {
        echo "⚡ 4. اختبار أداء PHP\n";
        echo "---------------------\n";
        
        // اختبار العمليات الحسابية
        $mathTime = $this->measure('Math Operations (1M)', function() {
            $sum = 0;
            for ($i = 0; $i < 1000000; $i++) {
                $sum += sqrt($i) * 2;
            }
        });
        echo "✅ عمليات حسابية (1M): " . round($mathTime * 1000, 2) . "ms\n";
        
        // اختبار معالجة النصوص
        $stringTime = $this->measure('String Operations (10K)', function() {
            $text = str_repeat("Hello World ", 1000);
            for ($i = 0; $i < 10000; $i++) {
                strtoupper($text);
                strtolower($text);
                str_replace("Hello", "Hi", $text);
            }
        });
        echo "✅ معالجة النصوص (10K): " . round($stringTime * 1000, 2) . "ms\n";
        
        // اختبار المصفوفات
        $arrayTime = $this->measure('Array Operations (100K)', function() {
            $arr = range(1, 100000);
            array_map(function($x) { return $x * 2; }, $arr);
            array_filter($arr, function($x) { return $x % 2 == 0; });
            array_reduce($arr, function($carry, $item) { return $carry + $item; }, 0);
        });
        echo "✅ عمليات المصفوفات (100K): " . round($arrayTime * 1000, 2) . "ms\n\n";
    }
    
    private function testSearchPerformance() {
        echo "🔍 5. اختبار أداء البحث\n";
        echo "------------------------\n";
        
        // إنشاء بيانات اختبار
        $testData = [];
        for ($i = 0; $i < 10000; $i++) {
            $testData[] = "Line $i: " . str_repeat("test data ", rand(5, 20));
        }
        $haystack = implode("\n", $testData);
        
        // البحث بـ strpos
        $strposTime = $this->measure('strpos Search (1000)', function() use ($haystack) {
            for ($i = 0; $i < 1000; $i++) {
                strpos($haystack, "Line " . rand(0, 9999));
            }
        });
        echo "✅ بحث strpos: {$strposTime}ms\n";
        
        // البحث بـ regex
        $regexTime = $this->measure('Regex Search (1000)', function() use ($haystack) {
            for ($i = 0; $i < 1000; $i++) {
                preg_match("/Line " . rand(0, 9999) . ":.*/", $haystack);
            }
        });
        echo "✅ بحث regex: {$regexTime}ms\n";
        
        // مقارنة
        $improvement = round($regexTime / $strposTime, 2);
        echo "📊 strpos أسرع بـ {$improvement}x من regex\n\n";
    }
    
    private function printResults() {
        echo "📊 === ملخص النتائج ===\n";
        echo "======================\n\n";
        
        // ترتيب حسب السرعة
        uasort($this->results, function($a, $b) {
            return $a['ops_per_sec'] < $b['ops_per_sec'] ? 1 : -1;
        });
        
        $table = "| العملية | الوقت الكلي | متوسط الوقت | عملية/ثانية |\n";
        $table .= "|---------|-------------|--------------|-------------|\n";
        
        foreach ($this->results as $name => $result) {
            $table .= sprintf(
                "| %-20s | %8.3fs | %8.6fs | %10.0f |\n",
                substr($name, 0, 20),
                $result['total_time'],
                $result['avg_time'],
                $result['ops_per_sec']
            );
        }
        
        echo $table;
        
        // التقييم النهائي
        echo "\n🏆 التقييم النهائي:\n";
        echo "==================\n";
        
        $totalOps = array_sum(array_column($this->results, 'ops_per_sec'));
        $avgOps = $totalOps / count($this->results);
        
        if ($avgOps > 10000) {
            echo "✅ الأداء: ممتاز! (" . round($avgOps) . " عملية/ثانية)\n";
        } elseif ($avgOps > 1000) {
            echo "⚡ الأداء: جيد جداً (" . round($avgOps) . " عملية/ثانية)\n";
        } elseif ($avgOps > 100) {
            echo "👍 الأداء: جيد (" . round($avgOps) . " عملية/ثانية)\n";
        } else {
            echo "⚠️ الأداء: يحتاج تحسين (" . round($avgOps) . " عملية/ثانية)\n";
        }
        
        // معلومات النظام
        echo "\n📌 معلومات النظام:\n";
        echo "- PHP: " . PHP_VERSION . "\n";
        echo "- Memory Limit: " . ini_get('memory_limit') . "\n";
        echo "- Max Execution Time: " . ini_get('max_execution_time') . "s\n";
        echo "- OPcache: " . (function_exists('opcache_get_status') ? 'مفعّل' : 'غير مفعّل') . "\n";
    }
}

// تشغيل الاختبار
$benchmark = new Benchmark();
$benchmark->run();