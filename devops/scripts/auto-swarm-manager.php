<?php
/**
 * مدير السرب الآلي - Automatic Swarm Manager
 * يقوم بتغيير حجم السرب تلقائياً بناءً على المهام والموارد المتاحة
 */

class AutoSwarmManager {
    private $maxUnits;
    private $currentUnits = [];
    private $taskQueue = [];
    private $resourceMonitor;
    private $performanceMetrics = [];
    
    // تخصصات السرب
    const SPECIALIZATIONS = [
        'analysis' => ['name' => 'التحليل', 'min_ratio' => 0.10, 'max_ratio' => 0.30],
        'development' => ['name' => 'التطوير', 'min_ratio' => 0.15, 'max_ratio' => 0.35],
        'testing' => ['name' => 'الاختبار', 'min_ratio' => 0.10, 'max_ratio' => 0.25],
        'optimization' => ['name' => 'التحسين', 'min_ratio' => 0.10, 'max_ratio' => 0.20],
        'automation' => ['name' => 'الأتمتة', 'min_ratio' => 0.05, 'max_ratio' => 0.15],
        'monitoring' => ['name' => 'المراقبة', 'min_ratio' => 0.05, 'max_ratio' => 0.15],
        'coordination' => ['name' => 'التنسيق', 'min_ratio' => 0.03, 'max_ratio' => 0.10]
    ];
    
    public function __construct() {
        $this->resourceMonitor = new ResourceMonitor();
        $this->calculateMaxCapacity();
        $this->initializeSwarm();
    }
    
    /**
     * حساب السعة القصوى للسرب بناءً على الموارد
     */
    private function calculateMaxCapacity() {
        $cpuCores = $this->resourceMonitor->getCpuCores();
        $availableMemory = $this->resourceMonitor->getAvailableMemory();
        $systemLoad = $this->resourceMonitor->getSystemLoad();
        
        // حساب العدد الأقصى بناءً على عوامل مختلفة
        $maxByCpu = $cpuCores * 50; // 50 وحدة لكل نواة (أقصى حد)
        $maxByMemory = floor($availableMemory / 30); // 30MB لكل وحدة (حد أدنى)
        $maxByLoad = floor((100 - $systemLoad) * 2); // بناءً على الحمل
        
        // اختيار القيمة الأقل للأمان مع تحسينات
        $this->maxUnits = min($maxByCpu, $maxByMemory, $maxByLoad);
        
        // تطبيق عامل الأمان 90% للاستقرار
        $this->maxUnits = floor($this->maxUnits * 0.9);
        
        echo "🚀 السعة القصوى المحسوبة: {$this->maxUnits} وحدة\n";
    }
    
    /**
     * تهيئة السرب بالحجم الافتراضي
     */
    private function initializeSwarm() {
        // البدء بـ 50% من السعة القصوى
        $initialSize = floor($this->maxUnits * 0.5);
        
        foreach (self::SPECIALIZATIONS as $type => $spec) {
            $count = floor($initialSize * $spec['min_ratio']);
            for ($i = 0; $i < $count; $i++) {
                $this->currentUnits[] = [
                    'id' => uniqid($type . '_'),
                    'type' => $type,
                    'status' => 'idle',
                    'task' => null,
                    'performance' => 100
                ];
            }
        }
        
        echo "✅ تم تهيئة السرب بـ " . count($this->currentUnits) . " وحدة\n";
    }
    
    /**
     * تحليل المهام وتحديد الاحتياجات
     */
    public function analyzeTaskRequirements($tasks) {
        $requirements = [
            'analysis' => 0,
            'development' => 0,
            'testing' => 0,
            'optimization' => 0,
            'automation' => 0,
            'monitoring' => 0,
            'coordination' => 0
        ];
        
        foreach ($tasks as $task) {
            switch ($task['type']) {
                case 'code_analysis':
                case 'dependency_check':
                    $requirements['analysis'] += $task['complexity'];
                    break;
                    
                case 'feature_development':
                case 'bug_fix':
                    $requirements['development'] += $task['complexity'];
                    $requirements['testing'] += $task['complexity'] * 0.5;
                    break;
                    
                case 'performance_improvement':
                    $requirements['optimization'] += $task['complexity'];
                    break;
                    
                case 'ci_cd_setup':
                case 'script_creation':
                    $requirements['automation'] += $task['complexity'];
                    break;
                    
                case 'system_monitoring':
                case 'log_analysis':
                    $requirements['monitoring'] += $task['complexity'];
                    break;
                    
                default:
                    $requirements['coordination'] += $task['complexity'] * 0.2;
            }
        }
        
        return $requirements;
    }
    
    /**
     * تعديل حجم السرب تلقائياً
     */
    public function autoScale($tasks) {
        $requirements = $this->analyzeTaskRequirements($tasks);
        $totalRequired = array_sum($requirements);
        
        // حساب العدد المطلوب من الوحدات
        $requiredUnits = min($totalRequired, $this->maxUnits);
        $currentCount = count($this->currentUnits);
        
        echo "\n📊 تحليل احتياجات المهام:\n";
        echo "• المهام الحالية: " . count($tasks) . "\n";
        echo "• الوحدات المطلوبة: $requiredUnits\n";
        echo "• الوحدات الحالية: $currentCount\n";
        
        if ($requiredUnits > $currentCount) {
            $this->scaleUp($requiredUnits - $currentCount, $requirements);
        } elseif ($requiredUnits < $currentCount * 0.7) {
            $this->scaleDown($currentCount - $requiredUnits);
        }
        
        $this->rebalanceSpecializations($requirements);
    }
    
    /**
     * زيادة حجم السرب
     */
    private function scaleUp($additionalUnits, $requirements) {
        $added = 0;
        $availableSpace = $this->maxUnits - count($this->currentUnits);
        $toAdd = min($additionalUnits, $availableSpace);
        
        echo "⬆️ زيادة حجم السرب بـ $toAdd وحدة...\n";
        
        // إضافة وحدات بناءً على الاحتياجات
        foreach ($requirements as $type => $need) {
            if ($need > 0 && $added < $toAdd) {
                $ratio = $need / array_sum($requirements);
                $unitsToAdd = ceil($toAdd * $ratio);
                
                for ($i = 0; $i < $unitsToAdd && $added < $toAdd; $i++) {
                    $this->currentUnits[] = [
                        'id' => uniqid($type . '_'),
                        'type' => $type,
                        'status' => 'idle',
                        'task' => null,
                        'performance' => 100
                    ];
                    $added++;
                }
            }
        }
        
        echo "✅ تمت إضافة $added وحدة جديدة\n";
    }
    
    /**
     * تقليل حجم السرب
     */
    private function scaleDown($unitsToRemove) {
        echo "⬇️ تقليل حجم السرب بـ $unitsToRemove وحدة...\n";
        
        // إزالة الوحدات الخاملة أولاً
        $removed = 0;
        $this->currentUnits = array_filter($this->currentUnits, function($unit) use (&$removed, $unitsToRemove) {
            if ($removed >= $unitsToRemove) {
                return true;
            }
            if ($unit['status'] === 'idle') {
                $removed++;
                return false;
            }
            return true;
        });
        
        echo "✅ تمت إزالة $removed وحدة\n";
    }
    
    /**
     * إعادة توازن التخصصات
     */
    private function rebalanceSpecializations($requirements) {
        $currentDistribution = $this->getCurrentDistribution();
        $totalUnits = count($this->currentUnits);
        
        echo "\n🔄 إعادة توازن التخصصات:\n";
        
        foreach (self::SPECIALIZATIONS as $type => $spec) {
            $currentRatio = $currentDistribution[$type] / $totalUnits;
            $requiredRatio = $requirements[$type] / array_sum($requirements);
            
            // التحقق من الحدود الدنيا والعليا
            if ($currentRatio < $spec['min_ratio']) {
                $needed = ceil($totalUnits * $spec['min_ratio']) - $currentDistribution[$type];
                $this->convertUnits($needed, $type);
            } elseif ($currentRatio > $spec['max_ratio']) {
                $excess = $currentDistribution[$type] - ceil($totalUnits * $spec['max_ratio']);
                $this->convertUnits(-$excess, $type);
            }
        }
    }
    
    /**
     * الحصول على التوزيع الحالي
     */
    private function getCurrentDistribution() {
        $distribution = array_fill_keys(array_keys(self::SPECIALIZATIONS), 0);
        
        foreach ($this->currentUnits as $unit) {
            $distribution[$unit['type']]++;
        }
        
        return $distribution;
    }
    
    /**
     * تحويل الوحدات بين التخصصات
     */
    private function convertUnits($count, $toType) {
        if ($count == 0) return;
        
        if ($count > 0) {
            // تحويل وحدات خاملة إلى التخصص المطلوب
            $converted = 0;
            foreach ($this->currentUnits as &$unit) {
                if ($converted >= $count) break;
                if ($unit['status'] === 'idle' && $unit['type'] !== $toType) {
                    $unit['type'] = $toType;
                    $unit['id'] = uniqid($toType . '_');
                    $converted++;
                }
            }
            echo "• تم تحويل $converted وحدة إلى " . self::SPECIALIZATIONS[$toType]['name'] . "\n";
        }
    }
    
    /**
     * تقرير حالة السرب
     */
    public function getSwarmStatus() {
        $distribution = $this->getCurrentDistribution();
        $totalUnits = count($this->currentUnits);
        $activeUnits = count(array_filter($this->currentUnits, fn($u) => $u['status'] !== 'idle'));
        
        $status = [
            'total_units' => $totalUnits,
            'max_capacity' => $this->maxUnits,
            'utilization' => round(($totalUnits / $this->maxUnits) * 100, 2),
            'active_units' => $activeUnits,
            'idle_units' => $totalUnits - $activeUnits,
            'distribution' => []
        ];
        
        foreach ($distribution as $type => $count) {
            $status['distribution'][$type] = [
                'name' => self::SPECIALIZATIONS[$type]['name'],
                'count' => $count,
                'percentage' => round(($count / $totalUnits) * 100, 2)
            ];
        }
        
        return $status;
    }
    
    /**
     * عرض حالة السرب
     */
    public function displayStatus() {
        $status = $this->getSwarmStatus();
        
        echo "\n";
        echo "╔══════════════════════════════════════════════════════╗\n";
        echo "║              🐝 حالة السرب الآلي                    ║\n";
        echo "╠══════════════════════════════════════════════════════╣\n";
        echo "║ السعة القصوى: {$status['max_capacity']} وحدة" . str_repeat(' ', 36 - strlen($status['max_capacity'])) . "║\n";
        echo "║ الوحدات الحالية: {$status['total_units']} ({$status['utilization']}%)" . str_repeat(' ', 29 - strlen($status['total_units']) - strlen($status['utilization'])) . "║\n";
        echo "║ وحدات نشطة: {$status['active_units']}" . str_repeat(' ', 40 - strlen($status['active_units'])) . "║\n";
        echo "║ وحدات خاملة: {$status['idle_units']}" . str_repeat(' ', 39 - strlen($status['idle_units'])) . "║\n";
        echo "╠══════════════════════════════════════════════════════╣\n";
        echo "║                  توزيع التخصصات                      ║\n";
        echo "╠══════════════════════════════════════════════════════╣\n";
        
        foreach ($status['distribution'] as $type => $info) {
            $bar = $this->createProgressBar($info['percentage'], 20);
            $line = "║ {$info['name']}: {$info['count']} وحدة $bar {$info['percentage']}%";
            $padding = 56 - mb_strlen(strip_tags($line)) + 3;
            echo $line . str_repeat(' ', max(1, $padding)) . "║\n";
        }
        
        echo "╚══════════════════════════════════════════════════════╝\n";
    }
    
    /**
     * إنشاء شريط تقدم
     */
    private function createProgressBar($percentage, $width) {
        $filled = round($percentage / 100 * $width);
        $empty = $width - $filled;
        return '[' . str_repeat('█', $filled) . str_repeat('░', $empty) . ']';
    }
}

/**
 * مراقب الموارد
 */
class ResourceMonitor {
    public function getCpuCores() {
        return (int)shell_exec('nproc');
    }
    
    public function getAvailableMemory() {
        $memInfo = shell_exec('free -m | grep Mem');
        preg_match('/Mem:\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+(\d+)/', $memInfo, $matches);
        return isset($matches[1]) ? (int)$matches[1] : 1000;
    }
    
    public function getSystemLoad() {
        $load = sys_getloadavg();
        $cores = $this->getCpuCores();
        return round(($load[0] / $cores) * 100, 2);
    }
}

// محاكاة المهام للاختبار
function generateTestTasks($count) {
    $taskTypes = [
        'code_analysis' => 3,
        'feature_development' => 5,
        'bug_fix' => 2,
        'performance_improvement' => 4,
        'ci_cd_setup' => 3,
        'system_monitoring' => 2
    ];
    
    $tasks = [];
    for ($i = 0; $i < $count; $i++) {
        $type = array_rand($taskTypes);
        $tasks[] = [
            'id' => uniqid('task_'),
            'type' => $type,
            'complexity' => $taskTypes[$type] + rand(-1, 2)
        ];
    }
    
    return $tasks;
}

// تشغيل مدير السرب
echo "🚀 بدء تشغيل مدير السرب الآلي...\n\n";

$swarmManager = new AutoSwarmManager();
$swarmManager->displayStatus();

// محاكاة سيناريوهات مختلفة
echo "\n\n📋 سيناريو 1: مهام قليلة (10 مهام)\n";
$tasks = generateTestTasks(10);
$swarmManager->autoScale($tasks);
$swarmManager->displayStatus();

echo "\n\n📋 سيناريو 2: مهام متوسطة (50 مهمة)\n";
$tasks = generateTestTasks(50);
$swarmManager->autoScale($tasks);
$swarmManager->displayStatus();

echo "\n\n📋 سيناريو 3: مهام كثيرة (200 مهمة) - اختبار السعة القصوى\n";
$tasks = generateTestTasks(200);
$swarmManager->autoScale($tasks);
$swarmManager->displayStatus();

echo "\n\n📋 سيناريو 4: انخفاض المهام (5 مهام)\n";
$tasks = generateTestTasks(5);
$swarmManager->autoScale($tasks);
$swarmManager->displayStatus();

echo "\n\n✅ اكتمل اختبار مدير السرب الآلي!\n";