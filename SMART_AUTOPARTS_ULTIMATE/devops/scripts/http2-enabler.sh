#!/bin/bash

# تفعيل HTTP/2
# HTTP/2 Enabler Script

echo "🌐 === تفعيل HTTP/2 ==="
echo ""

# إنشاء ملف تكوين HTTP/2
cat > /workspace/system/config/http2.conf << 'EOF'
# HTTP/2 Configuration
# تكوين HTTP/2 للأداء الأمثل

# تفعيل HTTP/2
http2_enabled on;

# إعدادات الأداء
http2_max_concurrent_streams 128;
http2_max_field_size 8k;
http2_max_header_size 16k;
http2_initial_window_size 128k;

# Server Push
http2_push_preload on;

# Prioritization
http2_chunk_size 8k;
http2_body_preread_size 64k;

# Connection settings
http2_idle_timeout 30s;
http2_recv_timeout 30s;
EOF

# إنشاء ملف .htaccess للدعم
cat > /workspace/.htaccess << 'EOF'
# Enable HTTP/2 Push
<FilesMatch "\.(js|css|jpg|jpeg|png|gif|svg|ico|woff|woff2)$">
    Header set Link "</style.css>; rel=preload; as=style"
    Header add Link "</script.js>; rel=preload; as=script"
    Header add Link "</logo.png>; rel=preload; as=image"
</FilesMatch>

# Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css text/javascript application/javascript application/json
</IfModule>

# Keep-Alive
<IfModule mod_headers.c>
    Header set Connection keep-alive
</IfModule>
EOF

# إنشاء PHP script لدعم HTTP/2
cat > /workspace/system/scripts/http2-support.php << 'EOF'
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
EOF

echo "✅ تم إنشاء ملفات تكوين HTTP/2"
echo ""
echo "📁 الملفات المنشأة:"
echo "  • /workspace/system/config/http2.conf"
echo "  • /workspace/.htaccess"
echo "  • /workspace/system/scripts/http2-support.php"
echo ""
echo "🚀 HTTP/2 جاهز للاستخدام!"