<?php

/**
 * Monitoring Dashboard - لوحة المراقبة المجانية
 * تعرض معلومات النظام والأداء في الوقت الفعلي
 */

class SystemMonitor {
    private $logDir;
    private $cacheDir;
    
    public function __construct() {
        $this->logDir = '/workspace/system/logs';
        $this->cacheDir = '/workspace/.cache';
        
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    // معلومات النظام
    public function getSystemInfo(): array {
        return [
            'hostname' => gethostname(),
            'os' => php_uname('s') . ' ' . php_uname('r'),
            'php_version' => PHP_VERSION,
            'server_time' => date('Y-m-d H:i:s'),
            'uptime' => $this->getUptime()
        ];
    }
    
    // استخدام الموارد
    public function getResourceUsage(): array {
        // CPU
        $cpuUsage = $this->getCpuUsage();
        
        // Memory
        $memInfo = $this->getMemoryInfo();
        
        // Disk
        $diskInfo = $this->getDiskInfo();
        
        // Process Count
        $processCount = intval(shell_exec('ps aux | wc -l'));
        
        return [
            'cpu' => $cpuUsage,
            'memory' => $memInfo,
            'disk' => $diskInfo,
            'processes' => $processCount
        ];
    }
    
    // معلومات المشاريع
    public function getProjectsInfo(): array {
        $projects = [];
        
        // Laravel Projects
        $laravelProjects = glob('/workspace/zeropay/projects/*/artisan');
        foreach ($laravelProjects as $artisan) {
            $projectDir = dirname($artisan);
            $projectName = basename($projectDir);
            
            $projects[] = [
                'name' => $projectName,
                'type' => 'Laravel',
                'path' => $projectDir,
                'size' => $this->getDirectorySize($projectDir),
                'last_modified' => date('Y-m-d H:i:s', filemtime($projectDir))
            ];
        }
        
        return $projects;
    }
    
    // سجلات الأخطاء الأخيرة
    public function getRecentErrors(): array {
        $errors = [];
        $logFiles = glob($this->logDir . '/*.log');
        
        foreach ($logFiles as $logFile) {
            $lines = $this->tailFile($logFile, 10);
            foreach ($lines as $line) {
                if (stripos($line, 'error') !== false || stripos($line, 'exception') !== false) {
                    $errors[] = [
                        'file' => basename($logFile),
                        'message' => $line,
                        'time' => date('Y-m-d H:i:s')
                    ];
                }
            }
        }
        
        return array_slice($errors, -20); // آخر 20 خطأ
    }
    
    // الإحصائيات
    public function getStatistics(): array {
        return [
            'total_files' => intval(shell_exec('find /workspace -type f | wc -l')),
            'total_directories' => intval(shell_exec('find /workspace -type d | wc -l')),
            'cache_size' => $this->getDirectorySize($this->cacheDir),
            'log_size' => $this->getDirectorySize($this->logDir),
            'git_repos' => intval(shell_exec('find /workspace -name ".git" -type d | wc -l'))
        ];
    }
    
    // وظائف مساعدة
    private function getUptime(): string {
        $uptime = shell_exec('uptime -p');
        return trim($uptime) ?: 'Unknown';
    }
    
    private function getCpuUsage(): array {
        $load = sys_getloadavg();
        $cpuCount = intval(shell_exec('nproc'));
        
        return [
            'load_1min' => $load[0],
            'load_5min' => $load[1],
            'load_15min' => $load[2],
            'cores' => $cpuCount,
            'usage_percent' => round(($load[0] / $cpuCount) * 100, 2)
        ];
    }
    
    private function getMemoryInfo(): array {
        $memInfo = file_get_contents('/proc/meminfo');
        preg_match('/MemTotal:\s+(\d+)/', $memInfo, $totalMatch);
        preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $availMatch);
        
        $total = intval($totalMatch[1]) * 1024;
        $available = intval($availMatch[1]) * 1024;
        $used = $total - $available;
        
        return [
            'total' => $this->formatBytes($total),
            'used' => $this->formatBytes($used),
            'free' => $this->formatBytes($available),
            'usage_percent' => round(($used / $total) * 100, 2)
        ];
    }
    
    private function getDiskInfo(): array {
        $df = shell_exec('df -B1 /workspace | tail -1');
        $parts = preg_split('/\s+/', $df);
        
        return [
            'total' => $this->formatBytes($parts[1]),
            'used' => $this->formatBytes($parts[2]),
            'free' => $this->formatBytes($parts[3]),
            'usage_percent' => intval($parts[4])
        ];
    }
    
    private function getDirectorySize(string $dir): string {
        if (!is_dir($dir)) return '0B';
        
        $size = intval(shell_exec("du -sb '$dir' 2>/dev/null | cut -f1"));
        return $this->formatBytes($size);
    }
    
    private function formatBytes(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    private function tailFile(string $file, int $lines = 10): array {
        $result = [];
        $fp = fopen($file, 'r');
        
        if (!$fp) return $result;
        
        fseek($fp, -1, SEEK_END);
        $pos = ftell($fp);
        $lastLine = '';
        
        while ($pos > 0 && count($result) < $lines) {
            $c = fgetc($fp);
            
            if ($c === "\n") {
                $result[] = $lastLine;
                $lastLine = '';
            } else {
                $lastLine = $c . $lastLine;
            }
            
            fseek($fp, $pos--);
            $pos = ftell($fp);
        }
        
        fclose($fp);
        return array_reverse($result);
    }
    
    // توليد تقرير HTML
    public function generateHTMLReport(): string {
        $system = $this->getSystemInfo();
        $resources = $this->getResourceUsage();
        $projects = $this->getProjectsInfo();
        $stats = $this->getStatistics();
        $errors = $this->getRecentErrors();
        
        $html = <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة المراقبة - Monitoring Dashboard</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Tahoma, Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card h3 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }
        .metric {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
        }
        .metric-label {
            color: #7f8c8d;
        }
        .metric-value {
            font-weight: bold;
            color: #2c3e50;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #ecf0f1;
            border-radius: 10px;
            overflow: hidden;
            margin: 5px 0;
        }
        .progress-fill {
            height: 100%;
            background: #3498db;
            transition: width 0.3s;
        }
        .progress-fill.warning {
            background: #f39c12;
        }
        .progress-fill.danger {
            background: #e74c3c;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: right;
            padding: 8px;
            border-bottom: 1px solid #ecf0f1;
        }
        th {
            background: #ecf0f1;
            font-weight: bold;
        }
        .error-log {
            background: #fee;
            padding: 5px;
            margin: 5px 0;
            border-radius: 4px;
            font-size: 0.9em;
        }
        .auto-refresh {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 لوحة مراقبة النظام</h1>
            <p>آخر تحديث: {$system['server_time']}</p>
        </div>
        
        <div class="grid">
            <!-- System Info -->
            <div class="card">
                <h3>📊 معلومات النظام</h3>
                <div class="metric">
                    <span class="metric-label">النظام:</span>
                    <span class="metric-value">{$system['os']}</span>
                </div>
                <div class="metric">
                    <span class="metric-label">PHP:</span>
                    <span class="metric-value">{$system['php_version']}</span>
                </div>
                <div class="metric">
                    <span class="metric-label">وقت التشغيل:</span>
                    <span class="metric-value">{$system['uptime']}</span>
                </div>
            </div>
            
            <!-- CPU Usage -->
            <div class="card">
                <h3>💻 استخدام المعالج</h3>
                <div class="metric">
                    <span class="metric-label">الاستخدام:</span>
                    <span class="metric-value">{$resources['cpu']['usage_percent']}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {$resources['cpu']['usage_percent']}%"></div>
                </div>
                <div class="metric">
                    <span class="metric-label">الأنوية:</span>
                    <span class="metric-value">{$resources['cpu']['cores']}</span>
                </div>
                <div class="metric">
                    <span class="metric-label">التحميل:</span>
                    <span class="metric-value">{$resources['cpu']['load_1min']} / {$resources['cpu']['load_5min']} / {$resources['cpu']['load_15min']}</span>
                </div>
            </div>
            
            <!-- Memory Usage -->
            <div class="card">
                <h3>🧠 استخدام الذاكرة</h3>
                <div class="metric">
                    <span class="metric-label">الاستخدام:</span>
                    <span class="metric-value">{$resources['memory']['usage_percent']}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {$resources['memory']['usage_percent']}%"></div>
                </div>
                <div class="metric">
                    <span class="metric-label">المستخدم:</span>
                    <span class="metric-value">{$resources['memory']['used']}</span>
                </div>
                <div class="metric">
                    <span class="metric-label">المتاح:</span>
                    <span class="metric-value">{$resources['memory']['free']}</span>
                </div>
            </div>
            
            <!-- Disk Usage -->
            <div class="card">
                <h3>💾 استخدام القرص</h3>
                <div class="metric">
                    <span class="metric-label">الاستخدام:</span>
                    <span class="metric-value">{$resources['disk']['usage_percent']}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {$resources['disk']['usage_percent']}%"></div>
                </div>
                <div class="metric">
                    <span class="metric-label">المستخدم:</span>
                    <span class="metric-value">{$resources['disk']['used']}</span>
                </div>
                <div class="metric">
                    <span class="metric-label">المتاح:</span>
                    <span class="metric-value">{$resources['disk']['free']}</span>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="card">
                <h3>📈 الإحصائيات</h3>
                <div class="metric">
                    <span class="metric-label">الملفات:</span>
                    <span class="metric-value">{$stats['total_files']}</span>
                </div>
                <div class="metric">
                    <span class="metric-label">المجلدات:</span>
                    <span class="metric-value">{$stats['total_directories']}</span>
                </div>
                <div class="metric">
                    <span class="metric-label">مستودعات Git:</span>
                    <span class="metric-value">{$stats['git_repos']}</span>
                </div>
                <div class="metric">
                    <span class="metric-label">العمليات:</span>
                    <span class="metric-value">{$resources['processes']}</span>
                </div>
            </div>
        </div>
        
        <!-- Projects Table -->
        <div class="card" style="margin-top: 20px;">
            <h3>📁 المشاريع</h3>
            <table>
                <tr>
                    <th>اسم المشروع</th>
                    <th>النوع</th>
                    <th>الحجم</th>
                    <th>آخر تعديل</th>
                </tr>
HTML;
        
        foreach ($projects as $project) {
            $html .= "<tr>
                <td>{$project['name']}</td>
                <td>{$project['type']}</td>
                <td>{$project['size']}</td>
                <td>{$project['last_modified']}</td>
            </tr>";
        }
        
        $html .= <<<HTML
            </table>
        </div>
        
        <!-- Recent Errors -->
        <div class="card" style="margin-top: 20px;">
            <h3>⚠️ آخر الأخطاء</h3>
HTML;
        
        if (empty($errors)) {
            $html .= "<p style='color: green;'>✅ لا توجد أخطاء حديثة</p>";
        } else {
            foreach (array_slice($errors, -5) as $error) {
                $html .= "<div class='error-log'>{$error['message']}</div>";
            }
        }
        
        $html .= <<<HTML
        </div>
    </div>
    
    <div class="auto-refresh" onclick="location.reload()">
        🔄 تحديث
    </div>
    
    <script>
        // تحديث تلقائي كل 30 ثانية
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>
HTML;
        
        return $html;
    }
}

// تشغيل اللوحة
if (php_sapi_name() === 'cli') {
    $monitor = new SystemMonitor();
    $reportPath = '/workspace/system/monitoring-report.html';
    
    file_put_contents($reportPath, $monitor->generateHTMLReport());
    echo "✅ تم إنشاء تقرير المراقبة: $reportPath\n";
} else {
    // عرض اللوحة مباشرة في المتصفح
    $monitor = new SystemMonitor();
    echo $monitor->generateHTMLReport();
}