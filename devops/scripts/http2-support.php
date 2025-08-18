<?php
/**
 * دعم HTTP/2 في PHP
 * HTTP/2 Support for PHP Applications
 */

class HTTP2Support {
    private $pushResources = [];
    
    /**
     * إضافة مورد للدفع
     */
    public function addPushResource($path, $as = 'script') {
        $this->pushResources[] = [
            'path' => $path,
            'as' => $as
        ];
    }
    
    /**
     * إرسال رؤوس Server Push
     */
    public function sendPushHeaders() {
        if (empty($this->pushResources)) {
            return;
        }
        
        $links = [];
        foreach ($this->pushResources as $resource) {
            $links[] = sprintf(
                '<%s>; rel=preload; as=%s',
                $resource['path'],
                $resource['as']
            );
        }
        
        header('Link: ' . implode(', ', $links));
    }
    
    /**
     * تحسين الأداء مع HTTP/2
     */
    public function optimize() {
        // Early Hints (103)
        if (!headers_sent()) {
            header('HTTP/1.1 103 Early Hints');
            $this->sendPushHeaders();
        }
        
        // Enable compression
        if (extension_loaded('zlib')) {
            ob_start('ob_gzhandler');
        }
        
        // Connection optimization
        header('Connection: keep-alive');
        header('Keep-Alive: timeout=30, max=100');
    }
    
    /**
     * التحقق من دعم HTTP/2
     */
    public function isHTTP2() {
        return (
            isset($_SERVER['SERVER_PROTOCOL']) && 
            $_SERVER['SERVER_PROTOCOL'] === 'HTTP/2.0'
        ) || (
            isset($_SERVER['HTTP2']) && 
            $_SERVER['HTTP2'] === 'on'
        );
    }
}

// مثال للاستخدام
$http2 = new HTTP2Support();

// إضافة الموارد للدفع
$http2->addPushResource('/css/style.min.css', 'style');
$http2->addPushResource('/js/app.min.js', 'script');
$http2->addPushResource('/img/logo.webp', 'image');

// تطبيق التحسينات
$http2->optimize();

echo "✅ HTTP/2 Support Enabled!\n";
echo "Protocol: " . ($_SERVER['SERVER_PROTOCOL'] ?? 'Unknown') . "\n";
echo "HTTP/2: " . ($http2->isHTTP2() ? 'Yes' : 'No') . "\n";
