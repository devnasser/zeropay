<?php

/**
 * نظام الفهرسة - يبني فهرس SQLite للبحث السريع
 * بديل مجاني وخفيف لـ Elasticsearch
 */

class FileIndexer {
    private $db;
    private $workspaceRoot;
    private $excludePatterns = [
        '/node_modules/',
        '/vendor/',
        '/.git/',
        '/storage/logs/',
        '/storage/framework/',
        '/.cache/',
        '/dist/',
        '/build/'
    ];
    
    private $includeExtensions = [
        'php', 'js', 'ts', 'jsx', 'tsx', 'vue',
        'json', 'md', 'txt', 'yaml', 'yml',
        'env', 'config', 'html', 'css', 'scss'
    ];
    
    public function __construct($workspaceRoot) {
        $this->workspaceRoot = rtrim($workspaceRoot, '/');
        $this->initDatabase();
    }
    
    private function initDatabase() {
        $dbPath = $this->workspaceRoot . '/.search-cache/search-index.db';
        $dir = dirname($dbPath);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $this->db = new PDO('sqlite:' . $dbPath);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // إنشاء الجداول
        $this->createTables();
    }
    
    private function createTables() {
        // جدول الملفات
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS files (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                path TEXT UNIQUE NOT NULL,
                filename TEXT NOT NULL,
                extension TEXT,
                size INTEGER,
                modified INTEGER,
                content TEXT,
                indexed_at INTEGER DEFAULT (strftime('%s', 'now'))
            )
        ");
        
        // جدول الرموز (Classes, Functions, etc)
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS symbols (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                file_id INTEGER,
                name TEXT NOT NULL,
                type TEXT NOT NULL,
                line INTEGER,
                context TEXT,
                FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE
            )
        ");
        
        // فهارس للبحث السريع
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_files_path ON files(path)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_files_filename ON files(filename)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_symbols_name ON symbols(name)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_symbols_type ON symbols(type)");
        
        // Full-text search
        $this->db->exec("
            CREATE VIRTUAL TABLE IF NOT EXISTS files_fts USING fts5(
                path, filename, content,
                content=files,
                content_rowid=id
            )
        ");
    }
    
    public function indexWorkspace() {
        echo "🔄 بدء فهرسة المجلد...\n";
        
        $startTime = microtime(true);
        $fileCount = 0;
        $symbolCount = 0;
        
        // مسح الفهرس القديم
        $this->db->exec("DELETE FROM files");
        $this->db->exec("DELETE FROM symbols");
        
        // البدء بالفهرسة
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->workspaceRoot),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $this->shouldIndex($file)) {
                $fileCount++;
                $this->indexFile($file);
                
                if ($fileCount % 100 == 0) {
                    echo "📄 تمت فهرسة $fileCount ملف...\n";
                }
            }
        }
        
        // تحديث FTS
        $this->db->exec("INSERT INTO files_fts(path, filename, content) SELECT path, filename, content FROM files");
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        echo "\n✅ اكتملت الفهرسة!\n";
        echo "📊 الإحصائيات:\n";
        echo "   - الملفات: $fileCount\n";
        echo "   - الوقت: {$duration} ثانية\n";
        echo "   - السرعة: " . round($fileCount / $duration, 0) . " ملف/ثانية\n";
    }
    
    private function shouldIndex($file) {
        $path = $file->getPathname();
        
        // التحقق من الاستثناءات
        foreach ($this->excludePatterns as $pattern) {
            if (strpos($path, $pattern) !== false) {
                return false;
            }
        }
        
        // التحقق من الامتدادات
        $ext = strtolower($file->getExtension());
        return in_array($ext, $this->includeExtensions);
    }
    
    private function indexFile($file) {
        $path = $file->getPathname();
        $relativePath = str_replace($this->workspaceRoot . '/', '', $path);
        
        try {
            $content = file_get_contents($path);
            
            // إدراج الملف
            $stmt = $this->db->prepare("
                INSERT OR REPLACE INTO files (path, filename, extension, size, modified, content)
                VALUES (:path, :filename, :extension, :size, :modified, :content)
            ");
            
            $stmt->execute([
                ':path' => $relativePath,
                ':filename' => $file->getFilename(),
                ':extension' => $file->getExtension(),
                ':size' => $file->getSize(),
                ':modified' => $file->getMTime(),
                ':content' => $content
            ]);
            
            $fileId = $this->db->lastInsertId();
            
            // استخراج الرموز
            if ($file->getExtension() == 'php') {
                $this->extractPHPSymbols($fileId, $content);
            }
            
        } catch (Exception $e) {
            echo "⚠️ خطأ في فهرسة: $relativePath - " . $e->getMessage() . "\n";
        }
    }
    
    private function extractPHPSymbols($fileId, $content) {
        // استخراج Classes
        preg_match_all('/^(class|interface|trait)\s+(\w+)/m', $content, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[2] as $i => $match) {
            $name = $match[0];
            $type = $matches[1][$i][0];
            $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
            
            $this->insertSymbol($fileId, $name, $type, $line);
        }
        
        // استخراج Functions
        preg_match_all('/^function\s+(\w+)/m', $content, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[1] as $match) {
            $name = $match[0];
            $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
            
            $this->insertSymbol($fileId, $name, 'function', $line);
        }
    }
    
    private function insertSymbol($fileId, $name, $type, $line) {
        $stmt = $this->db->prepare("
            INSERT INTO symbols (file_id, name, type, line)
            VALUES (:file_id, :name, :type, :line)
        ");
        
        $stmt->execute([
            ':file_id' => $fileId,
            ':name' => $name,
            ':type' => $type,
            ':line' => $line
        ]);
    }
}

// تشغيل الفهرسة
$workspaceRoot = $_SERVER['argv'][1] ?? '/workspace';
$indexer = new FileIndexer($workspaceRoot);
$indexer->indexWorkspace();