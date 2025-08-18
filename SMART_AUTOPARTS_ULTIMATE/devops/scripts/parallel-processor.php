<?php
/**
 * نظام المعالجة المتوازية
 * Parallel Processing System
 */

class ParallelProcessor {
    private $maxProcesses = 4;
    private $activeProcesses = [];
    private $results = [];
    private $startTime;
    
    public function __construct($maxProcesses = null) {
        if ($maxProcesses) {
            $this->maxProcesses = $maxProcesses;
        } else {
            // استخدام عدد الأنوية المتاحة
            $this->maxProcesses = $this->getCPUCores();
        }
        
        $this->startTime = microtime(true);
        
        echo "🚀 نظام المعالجة المتوازية\n";
        echo "• الأنوية المتاحة: {$this->maxProcesses}\n\n";
    }
    
    /**
     * الحصول على عدد أنوية المعالج
     */
    private function getCPUCores() {
        $cores = 1;
        
        if (PHP_OS_FAMILY === 'Linux') {
            $cores = (int)shell_exec('nproc');
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            $cores = (int)shell_exec('sysctl -n hw.ncpu');
        }
        
        return max(1, $cores);
    }
    
    /**
     * تنفيذ مهام متوازية
     */
    public function execute($tasks, $workerFunction) {
        $totalTasks = count($tasks);
        $completed = 0;
        $taskIndex = 0;
        
        echo "📋 عدد المهام: $totalTasks\n";
        echo "🔄 بدء المعالجة المتوازية...\n\n";
        
        // إنشاء أنبوب للتواصل
        $pipes = [];
        
        while ($taskIndex < $totalTasks || count($this->activeProcesses) > 0) {
            // إطلاق عمليات جديدة إذا كان هناك مجال
            while (count($this->activeProcesses) < $this->maxProcesses && $taskIndex < $totalTasks) {
                $task = $tasks[$taskIndex];
                
                // إنشاء أنبوب للتواصل مع العملية الفرعية
                $pipe = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
                
                $pid = pcntl_fork();
                
                if ($pid == -1) {
                    die("❌ فشل في إنشاء عملية فرعية\n");
                } elseif ($pid == 0) {
                    // عملية فرعية
                    fclose($pipe[0]); // إغلاق طرف القراءة
                    
                    // تنفيذ المهمة
                    $result = call_user_func($workerFunction, $task, $taskIndex);
                    
                    // إرسال النتيجة
                    fwrite($pipe[1], serialize([
                        'index' => $taskIndex,
                        'result' => $result,
                        'pid' => getmypid()
                    ]));
                    fclose($pipe[1]);
                    
                    exit(0);
                } else {
                    // العملية الأصلية
                    fclose($pipe[1]); // إغلاق طرف الكتابة
                    
                    $this->activeProcesses[$pid] = [
                        'index' => $taskIndex,
                        'pipe' => $pipe[0],
                        'start_time' => microtime(true)
                    ];
                    
                    $pipes[$pid] = $pipe[0];
                    
                    echo "🚀 بدء العملية $pid للمهمة #$taskIndex\n";
                    $taskIndex++;
                }
            }
            
            // التحقق من العمليات المكتملة
            if (count($this->activeProcesses) > 0) {
                $pid = pcntl_wait($status, WNOHANG);
                
                if ($pid > 0) {
                    // قراءة النتيجة
                    $pipe = $this->activeProcesses[$pid]['pipe'];
                    $data = '';
                    while (!feof($pipe)) {
                        $data .= fread($pipe, 4096);
                    }
                    fclose($pipe);
                    
                    if ($data) {
                        $result = unserialize($data);
                        $this->results[$result['index']] = $result['result'];
                    }
                    
                    $duration = round(microtime(true) - $this->activeProcesses[$pid]['start_time'], 2);
                    echo "✅ اكتملت العملية $pid (المهمة #{$this->activeProcesses[$pid]['index']}) في {$duration}s\n";
                    
                    unset($this->activeProcesses[$pid]);
                    $completed++;
                    
                    // عرض التقدم
                    $progress = round(($completed / $totalTasks) * 100);
                    echo "📊 التقدم: $completed/$totalTasks ($progress%)\n\n";
                }
                
                // تأخير صغير لتجنب استهلاك CPU
                usleep(10000); // 10ms
            }
        }
        
        // ترتيب النتائج
        ksort($this->results);
        
        $totalTime = round(microtime(true) - $this->startTime, 2);
        echo "✅ اكتملت جميع المهام في {$totalTime}s\n";
        
        return array_values($this->results);
    }
    
    /**
     * معالجة متوازية للملفات
     */
    public function processFiles($files, $processor) {
        return $this->execute($files, function($file, $index) use ($processor) {
            $startTime = microtime(true);
            $result = call_user_func($processor, $file);
            $duration = round(microtime(true) - $startTime, 3);
            
            return [
                'file' => $file,
                'result' => $result,
                'duration' => $duration,
                'process_id' => getmypid()
            ];
        });
    }
    
    /**
     * معالجة متوازية للبيانات
     */
    public function processData($dataChunks, $processor) {
        return $this->execute($dataChunks, function($chunk, $index) use ($processor) {
            return call_user_func($processor, $chunk, $index);
        });
    }
}

// مثال: معالج ملفات PHP
function analyzePHPFile($file) {
    if (!file_exists($file) || !is_readable($file)) {
        return ['error' => 'Cannot read file'];
    }
    
    $content = file_get_contents($file);
    
    // تحليل بسيط
    $stats = [
        'lines' => substr_count($content, "\n") + 1,
        'size' => strlen($content),
        'classes' => preg_match_all('/class\s+\w+/', $content),
        'functions' => preg_match_all('/function\s+\w+/', $content),
        'includes' => preg_match_all('/(?:include|require)(?:_once)?\s*\(/', $content)
    ];
    
    // محاكاة معالجة ثقيلة
    usleep(rand(100000, 300000)); // 100-300ms
    
    return $stats;
}

// مثال: معالج بيانات
function processDataChunk($data, $index) {
    $sum = 0;
    foreach ($data as $value) {
        $sum += $value;
        // محاكاة معالجة ثقيلة
        usleep(100);
    }
    
    return [
        'chunk' => $index,
        'count' => count($data),
        'sum' => $sum,
        'average' => $sum / count($data)
    ];
}

// اختبار النظام
echo "\n📊 === اختبار المعالجة المتوازية ===\n\n";

$processor = new ParallelProcessor();

// اختبار 1: معالجة ملفات
echo "🔍 اختبار معالجة الملفات المتوازية:\n";
echo "─────────────────────────────────\n";

$phpFiles = glob('/workspace/system/scripts/*.php');
$phpFiles = array_slice($phpFiles, 0, 8); // أول 8 ملفات للاختبار

$results = $processor->processFiles($phpFiles, 'analyzePHPFile');

echo "\n📋 النتائج:\n";
foreach ($results as $i => $result) {
    $file = basename($result['file']);
    echo "• $file: ";
    echo "{$result['result']['lines']} سطر، ";
    echo "{$result['result']['classes']} صنف، ";
    echo "{$result['result']['functions']} دالة ";
    echo "(معالج {$result['process_id']}, {$result['duration']}s)\n";
}

// اختبار 2: معالجة بيانات
echo "\n\n🔢 اختبار معالجة البيانات المتوازية:\n";
echo "────────────────────────────────────\n";

// إنشاء بيانات اختبارية
$bigData = [];
for ($i = 0; $i < 4; $i++) {
    $bigData[] = range(1, 10000);
}

$processor2 = new ParallelProcessor();
$results2 = $processor2->processData($bigData, 'processDataChunk');

echo "\n📊 نتائج معالجة البيانات:\n";
foreach ($results2 as $result) {
    echo "• القطعة {$result['chunk']}: ";
    echo "مجموع = " . number_format($result['sum']) . ", ";
    echo "متوسط = " . round($result['average'], 2) . "\n";
}

// مقارنة الأداء
echo "\n\n⚡ === مقارنة الأداء ===\n";

// معالجة متسلسلة
$sequentialStart = microtime(true);
foreach ($phpFiles as $file) {
    analyzePHPFile($file);
}
$sequentialTime = round(microtime(true) - $sequentialStart, 2);

echo "• المعالجة المتسلسلة: {$sequentialTime}s\n";
echo "• المعالجة المتوازية: " . round(microtime(true) - $processor->startTime, 2) . "s\n";
echo "• التسريع: " . round($sequentialTime / (microtime(true) - $processor->startTime), 1) . "x\n";

echo "\n✅ نظام المعالجة المتوازية يعمل بنجاح!\n";