<?php
/**
 * نظام ضغط الأصول
 * Asset Compression System
 */

class AssetCompressor {
    private $stats = [
        'css_files' => 0,
        'js_files' => 0,
        'image_files' => 0,
        'original_size' => 0,
        'compressed_size' => 0,
        'saved' => 0
    ];
    
    /**
     * ضغط ملفات CSS
     */
    public function compressCSS($content) {
        // إزالة التعليقات
        $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
        
        // إزالة المسافات الزائدة
        $content = str_replace(["\r\n", "\r", "\n", "\t"], '', $content);
        $content = preg_replace('/\s+/', ' ', $content);
        
        // إزالة المسافات حول الرموز
        $content = preg_replace('/\s*([{}:;,])\s*/', '$1', $content);
        
        // إزالة آخر فاصلة منقوطة قبل }
        $content = str_replace(';}', '}', $content);
        
        // تحسين الألوان
        $content = preg_replace('/#([a-f0-9])\1([a-f0-9])\2([a-f0-9])\3/i', '#$1$2$3', $content);
        
        // إزالة الوحدات من القيم الصفرية
        $content = preg_replace('/(?<=[: ])0(?:px|em|rem|%)/i', '0', $content);
        
        return trim($content);
    }
    
    /**
     * ضغط ملفات JavaScript
     */
    public function compressJS($content) {
        // حماية السلاسل النصية
        $strings = [];
        $content = preg_replace_callback(
            '/(["\'`])(?:[^\\\\]|\\\\.)*?\1/',
            function($match) use (&$strings) {
                $placeholder = '___STRING_' . count($strings) . '___';
                $strings[$placeholder] = $match[0];
                return $placeholder;
            },
            $content
        );
        
        // إزالة التعليقات
        $content = preg_replace('/\/\*[\s\S]*?\*\//', '', $content);
        $content = preg_replace('/\/\/.*$/m', '', $content);
        
        // إزالة المسافات الزائدة
        $content = preg_replace('/\s+/', ' ', $content);
        
        // إزالة المسافات حول العمليات
        $content = preg_replace('/\s*([=+\-*\/%&|^!~?:,;{}()\[\]])\s*/', '$1', $content);
        
        // استرجاع السلاسل النصية
        foreach ($strings as $placeholder => $string) {
            $content = str_replace($placeholder, $string, $content);
        }
        
        return trim($content);
    }
    
    /**
     * ضغط الصور باستخدام GD
     */
    public function compressImage($path, $quality = 85) {
        $info = getimagesize($path);
        if (!$info) return false;
        
        $type = $info['mime'];
        
        switch ($type) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($path);
                if ($image) {
                    imagejpeg($image, $path, $quality);
                    imagedestroy($image);
                    return true;
                }
                break;
                
            case 'image/png':
                $image = imagecreatefrompng($path);
                if ($image) {
                    // PNG compression level (0-9)
                    imagepng($image, $path, 9);
                    imagedestroy($image);
                    return true;
                }
                break;
                
            case 'image/gif':
                // GIF لا يحتاج ضغط
                return true;
        }
        
        return false;
    }
    
    /**
     * معالجة مجلد كامل
     */
    public function processDirectory($dir) {
        echo "📁 معالجة المجلد: $dir\n\n";
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $path = $file->getPathname();
                $ext = strtolower($file->getExtension());
                $originalSize = filesize($path);
                
                // تجاهل الملفات المضغوطة مسبقاً
                if (strpos($path, '.min.') !== false) {
                    continue;
                }
                
                $this->stats['original_size'] += $originalSize;
                
                switch ($ext) {
                    case 'css':
                        $this->processCSSFile($path);
                        break;
                        
                    case 'js':
                        $this->processJSFile($path);
                        break;
                        
                    case 'jpg':
                    case 'jpeg':
                    case 'png':
                    case 'gif':
                        $this->processImageFile($path);
                        break;
                }
            }
        }
        
        $this->showStats();
    }
    
    /**
     * معالجة ملف CSS
     */
    private function processCSSFile($path) {
        $content = file_get_contents($path);
        $compressed = $this->compressCSS($content);
        
        // حفظ النسخة المضغوطة
        $minPath = str_replace('.css', '.min.css', $path);
        file_put_contents($minPath, $compressed);
        
        $originalSize = strlen($content);
        $compressedSize = strlen($compressed);
        
        $this->stats['css_files']++;
        $this->stats['compressed_size'] += $compressedSize;
        $this->stats['saved'] += ($originalSize - $compressedSize);
        
        $reduction = round((1 - $compressedSize / $originalSize) * 100, 1);
        echo "🎨 CSS: " . basename($path) . " - توفير $reduction%\n";
    }
    
    /**
     * معالجة ملف JavaScript
     */
    private function processJSFile($path) {
        $content = file_get_contents($path);
        $compressed = $this->compressJS($content);
        
        // حفظ النسخة المضغوطة
        $minPath = str_replace('.js', '.min.js', $path);
        file_put_contents($minPath, $compressed);
        
        $originalSize = strlen($content);
        $compressedSize = strlen($compressed);
        
        $this->stats['js_files']++;
        $this->stats['compressed_size'] += $compressedSize;
        $this->stats['saved'] += ($originalSize - $compressedSize);
        
        $reduction = round((1 - $compressedSize / $originalSize) * 100, 1);
        echo "📜 JS: " . basename($path) . " - توفير $reduction%\n";
    }
    
    /**
     * معالجة ملف صورة
     */
    private function processImageFile($path) {
        $originalSize = filesize($path);
        
        // نسخ احتياطية
        $backupPath = $path . '.backup';
        copy($path, $backupPath);
        
        if ($this->compressImage($path, 85)) {
            $compressedSize = filesize($path);
            
            // إذا كان الضغط جعل الملف أكبر، استرجع الأصلي
            if ($compressedSize >= $originalSize) {
                rename($backupPath, $path);
                $compressedSize = $originalSize;
            } else {
                unlink($backupPath);
            }
            
            $this->stats['image_files']++;
            $this->stats['compressed_size'] += $compressedSize;
            $this->stats['saved'] += ($originalSize - $compressedSize);
            
            $reduction = round((1 - $compressedSize / $originalSize) * 100, 1);
            if ($reduction > 0) {
                echo "🖼️ IMG: " . basename($path) . " - توفير $reduction%\n";
            }
        } else {
            unlink($backupPath);
        }
    }
    
    /**
     * عرض الإحصائيات
     */
    private function showStats() {
        echo "\n📊 === إحصائيات الضغط ===\n";
        echo "• ملفات CSS: {$this->stats['css_files']}\n";
        echo "• ملفات JS: {$this->stats['js_files']}\n";
        echo "• ملفات الصور: {$this->stats['image_files']}\n";
        echo "• الحجم الأصلي: " . $this->formatBytes($this->stats['original_size']) . "\n";
        echo "• الحجم المضغوط: " . $this->formatBytes($this->stats['compressed_size']) . "\n";
        echo "• التوفير: " . $this->formatBytes($this->stats['saved']) . " ";
        
        if ($this->stats['original_size'] > 0) {
            $percentage = round(($this->stats['saved'] / $this->stats['original_size']) * 100, 1);
            echo "($percentage%)\n";
        }
    }
    
    /**
     * تنسيق البايتات
     */
    private function formatBytes($bytes) {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 2) . ' KB';
        return round($bytes / 1048576, 2) . ' MB';
    }
}

// إنشاء ملفات اختبار
echo "🚀 === نظام ضغط الأصول ===\n\n";

// إنشاء مجلد للاختبار
$testDir = '/workspace/system/test-assets';
@mkdir($testDir, 0755, true);

// إنشاء ملف CSS اختباري
$cssContent = '
/* Main Styles */
body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    background-color: #ffffff;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* Header Styles */
.header {
    background-color: #333333;
    color: #ffffff;
    padding: 10px 0px;
}

.header h1 {
    margin: 0;
    font-size: 24px;
}
';
file_put_contents("$testDir/style.css", $cssContent);

// إنشاء ملف JavaScript اختباري
$jsContent = '
// Main JavaScript file
function init() {
    // Initialize the application
    var elements = document.querySelectorAll(".button");
    
    for (var i = 0; i < elements.length; i++) {
        elements[i].addEventListener("click", function(event) {
            event.preventDefault();
            console.log("Button clicked!");
        });
    }
}

// Helper functions
function addClass(element, className) {
    if (element.classList) {
        element.classList.add(className);
    } else {
        element.className += " " + className;
    }
}

window.onload = init;
';
file_put_contents("$testDir/script.js", $jsContent);

// تشغيل الضاغط
$compressor = new AssetCompressor();
$compressor->processDirectory($testDir);

echo "\n✅ اكتمل ضغط الأصول!\n";