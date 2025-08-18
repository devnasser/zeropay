<?php
/**
 * اختبار الأداء الشامل - Comprehensive Performance Test
 * يقيس الأداء الحالي ويحدد نقاط الضعف
 */

class PerformanceTest {
    private $results = [];
    private $startTime;
    
    public function __construct() {
        $this->startTime = microtime(true);
        echo "\n🔍 === اختبار الأداء الشامل ===\n";
        echo "التاريخ: " . date('Y-m-d H:i:s') . "\n\n";
    }
    
    /**
     * اختبار سرعة PHP
     */
    public function testPHPSpeed() {
        echo "1️⃣ اختبار سرعة PHP:\n";
        echo "─────────────────────\n";
        
        // اختبار OPcache
        $opcacheEnabled = function_exists('opcache_get_status') && opcache_get_status();
        $this->results['opcache_enabled'] = $opcacheEnabled;
        echo "• OPcache: " . ($opcacheEnabled ? "✅ مفعل" : "❌ غير مفعل") . "\n";
        
        // اختبار JIT
        if ($opcacheEnabled) {
            $jitEnabled = opcache_get_status()['jit']['enabled'] ?? false;
            $this->results['jit_enabled'] = $jitEnabled;
            echo "• JIT Compiler: " . ($jitEnabled ? "✅ مفعل" : "❌ غير مفعل") . "\n";
        }
        
        // اختبار سرعة الحلقات
        $start = microtime(true);
        $sum = 0;
        for ($i = 0; $i < 10000000; $i++) {
            $sum += $i;
        }
        $loopTime = microtime(true) - $start;
        $this->results['loop_time'] = $loopTime;
        echo "• حلقة 10M: " . round($loopTime, 3) . " ثانية\n";
        
        // اختبار العمليات الرياضية
        $start = microtime(true);
        for ($i = 0; $i < 1000000; $i++) {
            $x = sqrt($i) * pi() / log($i + 1);
        }
        $mathTime = microtime(true) - $start;
        $this->results['math_time'] = $mathTime;
        echo "• عمليات رياضية 1M: " . round($mathTime, 3) . " ثانية\n";
        
        // اختبار معالجة النصوص
        $start = microtime(true);
        $text = str_repeat("Hello World ", 1000);
        for ($i = 0; $i < 10000; $i++) {
            $result = strtoupper($text);
            $result = str_replace("WORLD", "PHP", $result);
            $result = substr($result, 0, 100);
        }
        $stringTime = microtime(true) - $start;
        $this->results['string_time'] = $stringTime;
        echo "• معالجة نصوص: " . round($stringTime, 3) . " ثانية\n";
        
        // تقييم الأداء
        $score = $this->calculatePHPScore();
        echo "\n📊 تقييم أداء PHP: " . $this->getPerformanceRating($score) . "\n\n";
    }
    
    /**
     * اختبار سرعة I/O
     */
    public function testIOSpeed() {
        echo "2️⃣ اختبار سرعة I/O:\n";
        echo "─────────────────────\n";
        
        $testDir = "/tmp/io_test_" . uniqid();
        mkdir($testDir);
        
        // اختبار الكتابة
        $start = microtime(true);
        $data = str_repeat("A", 1024); // 1KB
        for ($i = 0; $i < 1000; $i++) {
            file_put_contents("$testDir/file_$i.txt", $data);
        }
        $writeTime = microtime(true) - $start;
        $this->results['write_time'] = $writeTime;
        echo "• كتابة 1000 ملف: " . round($writeTime, 3) . " ثانية\n";
        echo "  السرعة: " . round(1000 / $writeTime) . " ملف/ثانية\n";
        
        // اختبار القراءة
        $start = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $content = file_get_contents("$testDir/file_$i.txt");
        }
        $readTime = microtime(true) - $start;
        $this->results['read_time'] = $readTime;
        echo "• قراءة 1000 ملف: " . round($readTime, 3) . " ثانية\n";
        echo "  السرعة: " . round(1000 / $readTime) . " ملف/ثانية\n";
        
        // تنظيف
        array_map('unlink', glob("$testDir/*"));
        rmdir($testDir);
        
        echo "\n";
    }
    
    /**
     * اختبار الذاكرة
     */
    public function testMemory() {
        echo "3️⃣ اختبار الذاكرة:\n";
        echo "─────────────────────\n";
        
        $memLimit = ini_get('memory_limit');
        echo "• حد الذاكرة: $memLimit\n";
        
        // اختبار تخصيص الذاكرة
        $start = microtime(true);
        $arrays = [];
        for ($i = 0; $i < 100; $i++) {
            $arrays[] = range(1, 10000);
        }
        $memTime = microtime(true) - $start;
        $memUsed = memory_get_peak_usage(true) / 1024 / 1024;
        
        $this->results['memory_time'] = $memTime;
        $this->results['memory_used'] = $memUsed;
        
        echo "• تخصيص 100 مصفوفة: " . round($memTime, 3) . " ثانية\n";
        echo "• الذاكرة المستخدمة: " . round($memUsed, 2) . " MB\n";
        
        // اختبار التخزين المؤقت
        if (function_exists('apcu_enabled') && apcu_enabled()) {
            echo "• APCu: ✅ مفعل\n";
            $this->results['apcu_enabled'] = true;
        } else {
            echo "• APCu: ❌ غير مفعل\n";
            $this->results['apcu_enabled'] = false;
        }
        
        echo "\n";
    }
    
    /**
     * اختبار البحث
     */
    public function testSearch() {
        echo "4️⃣ اختبار البحث:\n";
        echo "─────────────────────\n";
        
        // عد الملفات
        $start = microtime(true);
        $phpFiles = shell_exec("find /workspace -name '*.php' -type f 2>/dev/null | wc -l");
        $findTime = microtime(true) - $start;
        
        $this->results['total_files'] = trim($phpFiles);
        $this->results['find_time'] = $findTime;
        
        echo "• عدد ملفات PHP: " . trim($phpFiles) . "\n";
        echo "• وقت البحث: " . round($findTime, 3) . " ثانية\n";
        
        // اختبار grep
        $start = microtime(true);
        exec("grep -r 'function' /workspace --include='*.php' 2>/dev/null | wc -l", $output);
        $grepTime = microtime(true) - $start;
        
        $this->results['grep_time'] = $grepTime;
        echo "• بحث grep: " . round($grepTime, 3) . " ثانية\n";
        
        // التحقق من ripgrep
        $rgExists = shell_exec("which rg") !== null;
        echo "• ripgrep: " . ($rgExists ? "✅ متوفر" : "❌ غير متوفر") . "\n";
        $this->results['ripgrep_available'] = $rgExists;
        
        echo "\n";
    }
    
    /**
     * اختبار قاعدة البيانات
     */
    public function testDatabase() {
        echo "5️⃣ اختبار قاعدة البيانات:\n";
        echo "───────────────────────────\n";
        
        try {
            $db = new SQLite3(':memory:');
            
            // إنشاء جدول
            $start = microtime(true);
            $db->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, data TEXT)');
            
            // إدراج بيانات
            for ($i = 0; $i < 1000; $i++) {
                $db->exec("INSERT INTO test (data) VALUES ('Test data $i')");
            }
            $insertTime = microtime(true) - $start;
            
            // استعلام
            $start = microtime(true);
            $result = $db->query('SELECT COUNT(*) as count FROM test');
            $row = $result->fetchArray();
            $selectTime = microtime(true) - $start;
            
            $this->results['db_insert_time'] = $insertTime;
            $this->results['db_select_time'] = $selectTime;
            
            echo "• إدراج 1000 سجل: " . round($insertTime, 3) . " ثانية\n";
            echo "• استعلام COUNT: " . round($selectTime * 1000, 2) . " ms\n";
            echo "• SQLite: ✅ يعمل بشكل جيد\n";
            
        } catch (Exception $e) {
            echo "• SQLite: ❌ خطأ - " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    /**
     * حساب نقاط أداء PHP
     */
    private function calculatePHPScore() {
        $score = 100;
        
        // OPcache (-30 نقطة إذا غير مفعل)
        if (!$this->results['opcache_enabled']) {
            $score -= 30;
        }
        
        // JIT (-20 نقطة إذا غير مفعل)
        if (!($this->results['jit_enabled'] ?? false)) {
            $score -= 20;
        }
        
        // سرعة الحلقات
        if ($this->results['loop_time'] > 0.5) {
            $score -= 10;
        }
        
        // سرعة العمليات الرياضية
        if ($this->results['math_time'] > 0.3) {
            $score -= 10;
        }
        
        return max(0, $score);
    }
    
    /**
     * تقييم الأداء
     */
    private function getPerformanceRating($score) {
        if ($score >= 90) return "⭐⭐⭐⭐⭐ ممتاز ($score/100)";
        if ($score >= 70) return "⭐⭐⭐⭐ جيد جداً ($score/100)";
        if ($score >= 50) return "⭐⭐⭐ متوسط ($score/100)";
        if ($score >= 30) return "⭐⭐ ضعيف ($score/100)";
        return "⭐ يحتاج تحسين عاجل ($score/100)";
    }
    
    /**
     * عرض الملخص والتوصيات
     */
    public function showSummary() {
        echo "📊 === ملخص النتائج ===\n";
        echo "──────────────────────\n";
        
        $totalTime = microtime(true) - $this->startTime;
        echo "• وقت الاختبار الكلي: " . round($totalTime, 2) . " ثانية\n\n";
        
        echo "🚨 المشاكل المكتشفة:\n";
        $problems = 0;
        
        if (!$this->results['opcache_enabled']) {
            echo "❌ OPcache غير مفعل - خسارة أداء 300%\n";
            $problems++;
        }
        
        if (!($this->results['jit_enabled'] ?? false)) {
            echo "❌ JIT Compiler غير مفعل - خسارة أداء 50%\n";
            $problems++;
        }
        
        if (!$this->results['apcu_enabled']) {
            echo "❌ APCu غير مفعل - لا يوجد تخزين مؤقت في الذاكرة\n";
            $problems++;
        }
        
        if (!$this->results['ripgrep_available']) {
            echo "❌ ripgrep غير مثبت - بحث بطيء\n";
            $problems++;
        }
        
        if ($this->results['find_time'] > 2) {
            echo "❌ البحث بطيء جداً - يحتاج فهرسة\n";
            $problems++;
        }
        
        if ($problems == 0) {
            echo "✅ لا توجد مشاكل كبيرة\n";
        }
        
        echo "\n💡 التوصيات العاجلة:\n";
        echo "1. تفعيل OPcache و JIT للحصول على تسريع 10x\n";
        echo "2. تثبيت APCu للتخزين المؤقت السريع\n";
        echo "3. تثبيت ripgrep للبحث السريع\n";
        echo "4. بناء فهرس SQLite للبحث الفوري\n";
        echo "5. تطبيق المعالجة المتوازية\n";
        
        echo "\n🎯 الأداء المتوقع بعد التحسين:\n";
        echo "• تسريع PHP: 5-10x\n";
        echo "• تسريع البحث: 50-100x\n";
        echo "• تسريع I/O: 3-5x\n";
        echo "• تسريع عام: 10-20x\n";
    }
    
    /**
     * تشغيل جميع الاختبارات
     */
    public function runAll() {
        $this->testPHPSpeed();
        $this->testIOSpeed();
        $this->testMemory();
        $this->testSearch();
        $this->testDatabase();
        $this->showSummary();
        
        // حفظ النتائج
        file_put_contents(
            '/workspace/performance-test-results.json',
            json_encode($this->results, JSON_PRETTY_PRINT)
        );
        
        echo "\n✅ تم حفظ النتائج في: performance-test-results.json\n";
    }
}

// تشغيل الاختبار
$test = new PerformanceTest();
$test->runAll();