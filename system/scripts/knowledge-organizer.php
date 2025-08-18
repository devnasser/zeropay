<?php
/**
 * ⚔️ منظم المعرفة - نمط الأسطورة ⚔️
 * Knowledge Organizer - Legend Mode
 */

class KnowledgeOrganizer {
    private $workspace = '/workspace';
    private $knowledgeBase = '/workspace/docs/AI/knowledge_base';
    private $humanDocs = '/workspace/docs/human';
    private $statistics = [];
    
    public function __construct() {
        echo "⚔️ بدء تنظيم المعرفة بنمط الأسطورة ⚔️\n";
        echo "==========================================\n\n";
    }
    
    public function organize() {
        // 1. مسح وجمع المعرفة
        $this->scanKnowledge();
        
        // 2. تصنيف المعرفة
        $this->categorizeKnowledge();
        
        // 3. بناء الفهارس
        $this->buildIndexes();
        
        // 4. إنشاء الملخصات
        $this->createSummaries();
        
        // 5. توليد التقرير
        $this->generateReport();
    }
    
    private function scanKnowledge() {
        echo "🔍 مسح المعرفة المتناثرة...\n";
        
        $patterns = [
            'security' => ['security', 'auth', 'permission', 'vulnerability'],
            'performance' => ['performance', 'optimization', 'cache', 'speed'],
            'architecture' => ['architecture', 'structure', 'design', 'pattern'],
            'services' => ['service', 'api', 'integration', 'middleware'],
            'best-practices' => ['best', 'practice', 'standard', 'guideline']
        ];
        
        foreach ($patterns as $category => $keywords) {
            $this->statistics[$category] = 0;
            $files = $this->findFiles($keywords);
            
            foreach ($files as $file) {
                $this->processFile($file, $category);
            }
            
            echo "  ✓ $category: {$this->statistics[$category]} ملف\n";
        }
    }
    
    private function findFiles($keywords) {
        $files = [];
        $cmd = "find {$this->workspace} -type f \\( -name '*.md' -o -name '*.php' \\) | head -100";
        exec($cmd, $output);
        
        foreach ($output as $file) {
            $content = @file_get_contents($file);
            if (!$content) continue;
            
            foreach ($keywords as $keyword) {
                if (stripos($content, $keyword) !== false) {
                    $files[] = $file;
                    break;
                }
            }
        }
        
        return array_unique($files);
    }
    
    private function processFile($file, $category) {
        $content = file_get_contents($file);
        $basename = basename($file);
        
        // استخراج المعرفة المهمة
        $knowledge = $this->extractKnowledge($content, $category);
        
        if (!empty($knowledge)) {
            $targetDir = "{$this->knowledgeBase}/$category";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            
            $targetFile = "$targetDir/" . preg_replace('/\.[^.]+$/', '', $basename) . "_extracted.md";
            file_put_contents($targetFile, $knowledge);
            $this->statistics[$category]++;
        }
    }
    
    private function extractKnowledge($content, $category) {
        $extracted = "# معرفة مستخرجة - $category\n\n";
        $extracted .= "تاريخ الاستخراج: " . date('Y-m-d H:i:s') . "\n\n";
        
        // استخراج الأقسام المهمة
        $sections = $this->extractSections($content);
        
        foreach ($sections as $section) {
            $extracted .= "## $section[title]\n\n";
            $extracted .= $section['content'] . "\n\n";
        }
        
        return strlen($extracted) > 100 ? $extracted : '';
    }
    
    private function extractSections($content) {
        $sections = [];
        $lines = explode("\n", $content);
        $currentSection = null;
        $currentContent = [];
        
        foreach ($lines as $line) {
            if (preg_match('/^#+\s+(.+)/', $line, $matches)) {
                if ($currentSection) {
                    $sections[] = [
                        'title' => $currentSection,
                        'content' => implode("\n", array_slice($currentContent, 0, 20))
                    ];
                }
                $currentSection = $matches[1];
                $currentContent = [];
            } else {
                $currentContent[] = $line;
            }
        }
        
        return $sections;
    }
    
    private function categorizeKnowledge() {
        echo "\n📁 تصنيف المعرفة...\n";
        // يتم التصنيف أثناء المعالجة
        echo "  ✓ تم التصنيف تلقائياً\n";
    }
    
    private function buildIndexes() {
        echo "\n📚 بناء الفهارس...\n";
        
        $index = [
            'version' => '1.0',
            'generated' => date('c'),
            'categories' => []
        ];
        
        foreach ($this->statistics as $category => $count) {
            $categoryPath = "{$this->knowledgeBase}/$category";
            if (is_dir($categoryPath)) {
                $files = glob("$categoryPath/*.md");
                $index['categories'][$category] = [
                    'count' => count($files),
                    'files' => array_map('basename', $files)
                ];
            }
        }
        
        file_put_contents("{$this->knowledgeBase}/index.json", json_encode($index, JSON_PRETTY_PRINT));
        echo "  ✓ تم بناء الفهرس الرئيسي\n";
    }
    
    private function createSummaries() {
        echo "\n📝 إنشاء الملخصات...\n";
        
        $summary = "# ملخص قاعدة المعرفة\n\n";
        $summary .= "تاريخ التحديث: " . date('Y-m-d H:i:s') . "\n\n";
        $summary .= "## الإحصائيات\n\n";
        
        $total = array_sum($this->statistics);
        $summary .= "- إجمالي الملفات المعالجة: $total\n";
        
        foreach ($this->statistics as $category => $count) {
            $summary .= "- $category: $count ملف\n";
        }
        
        file_put_contents("{$this->knowledgeBase}/SUMMARY.md", $summary);
        echo "  ✓ تم إنشاء الملخص العام\n";
    }
    
    private function generateReport() {
        echo "\n📊 توليد التقرير...\n\n";
        echo "=== تقرير تنظيم المعرفة ===\n";
        echo "التاريخ: " . date('Y-m-d H:i:s') . "\n\n";
        
        $total = array_sum($this->statistics);
        echo "📈 الإحصائيات:\n";
        echo "- إجمالي الملفات المعالجة: $total\n";
        echo "- الفئات المنظمة: " . count($this->statistics) . "\n\n";
        
        echo "📁 التوزيع حسب الفئة:\n";
        foreach ($this->statistics as $category => $count) {
            $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
            echo "- $category: $count ملف ($percentage%)\n";
        }
        
        echo "\n✅ تم تنظيم المعرفة بنجاح!\n";
        echo "⚔️ نمط الأسطورة - المعرفة منظمة ⚔️\n";
    }
}

// تشغيل المنظم
$organizer = new KnowledgeOrganizer();
$organizer->organize();