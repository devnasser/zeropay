<?php
/**
 * نظام التحميل الكسول
 * Lazy Loading System
 */

class LazyLoader {
    private static $instance = null;
    private $registry = [];
    private $loaded = [];
    private $stats = [
        'registered' => 0,
        'loaded' => 0,
        'memory_saved' => 0,
        'time_saved' => 0
    ];
    
    private function __construct() {
        spl_autoload_register([$this, 'autoload']);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * تسجيل صنف للتحميل الكسول
     */
    public function register($class, $file = null, $callback = null) {
        $this->registry[$class] = [
            'file' => $file,
            'callback' => $callback,
            'size' => $file ? filesize($file) : 0
        ];
        $this->stats['registered']++;
    }
    
    /**
     * تسجيل مجلد كامل
     */
    public function registerDirectory($directory, $namespace = '') {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace($directory . '/', '', $file->getPathname());
                $class = $namespace . str_replace(['/', '.php'], ['\\', ''], $relativePath);
                $this->register($class, $file->getPathname());
            }
        }
    }
    
    /**
     * التحميل التلقائي
     */
    public function autoload($class) {
        if (isset($this->loaded[$class])) {
            return true;
        }
        
        if (isset($this->registry[$class])) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage();
            
            $info = $this->registry[$class];
            
            if ($info['callback']) {
                call_user_func($info['callback'], $class);
            } elseif ($info['file'] && file_exists($info['file'])) {
                require_once $info['file'];
            }
            
            $this->loaded[$class] = true;
            $this->stats['loaded']++;
            $this->stats['time_saved'] += microtime(true) - $startTime;
            $this->stats['memory_saved'] += memory_get_usage() - $startMemory;
            
            return true;
        }
        
        return false;
    }
    
    /**
     * تحميل كسول للموارد
     */
    public function lazyLoadResource($name, $loader) {
        static $resources = [];
        
        if (!isset($resources[$name])) {
            $resources[$name] = new LazyResource($loader);
        }
        
        return $resources[$name];
    }
    
    /**
     * الإحصائيات
     */
    public function getStats() {
        return [
            'registered_classes' => $this->stats['registered'],
            'loaded_classes' => $this->stats['loaded'],
            'lazy_percentage' => $this->stats['registered'] > 0 
                ? round((1 - $this->stats['loaded'] / $this->stats['registered']) * 100, 2) 
                : 0,
            'memory_saved' => $this->formatBytes($this->stats['memory_saved']),
            'time_saved' => round($this->stats['time_saved'] * 1000, 2) . 'ms'
        ];
    }
    
    private function formatBytes($bytes) {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 2) . ' KB';
        return round($bytes / 1048576, 2) . ' MB';
    }
}

/**
 * مورد كسول
 */
class LazyResource {
    private $loader;
    private $resource = null;
    private $loaded = false;
    
    public function __construct($loader) {
        $this->loader = $loader;
    }
    
    public function __get($name) {
        $this->load();
        return $this->resource->$name;
    }
    
    public function __set($name, $value) {
        $this->load();
        $this->resource->$name = $value;
    }
    
    public function __call($method, $args) {
        $this->load();
        return call_user_func_array([$this->resource, $method], $args);
    }
    
    private function load() {
        if (!$this->loaded) {
            $this->resource = call_user_func($this->loader);
            $this->loaded = true;
        }
    }
}

/**
 * نظام تحميل كسول للصور
 */
class LazyImageLoader {
    private $placeholder = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMSIgaGVpZ2h0PSIxIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9IiNlZWUiLz48L3N2Zz4=';
    
    /**
     * إنشاء صورة كسولة
     */
    public function createLazyImage($src, $alt = '', $class = '') {
        $id = uniqid('lazy_');
        
        return sprintf(
            '<img id="%s" src="%s" data-src="%s" alt="%s" class="lazy %s" loading="lazy">',
            $id,
            $this->placeholder,
            $src,
            htmlspecialchars($alt),
            htmlspecialchars($class)
        );
    }
    
    /**
     * JavaScript للتحميل الكسول
     */
    public function getLoaderScript() {
        return <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', function() {
    var lazyImages = [].slice.call(document.querySelectorAll('img.lazy'));
    
    if ('IntersectionObserver' in window) {
        let lazyImageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    let lazyImage = entry.target;
                    lazyImage.src = lazyImage.dataset.src;
                    lazyImage.classList.remove('lazy');
                    lazyImageObserver.unobserve(lazyImage);
                }
            });
        });
        
        lazyImages.forEach(function(lazyImage) {
            lazyImageObserver.observe(lazyImage);
        });
    } else {
        // Fallback for older browsers
        let active = false;
        
        const lazyLoad = function() {
            if (active === false) {
                active = true;
                
                setTimeout(function() {
                    lazyImages.forEach(function(lazyImage) {
                        if ((lazyImage.getBoundingClientRect().top <= window.innerHeight && lazyImage.getBoundingClientRect().bottom >= 0) && getComputedStyle(lazyImage).display !== 'none') {
                            lazyImage.src = lazyImage.dataset.src;
                            lazyImage.classList.remove('lazy');
                            
                            lazyImages = lazyImages.filter(function(image) {
                                return image !== lazyImage;
                            });
                            
                            if (lazyImages.length === 0) {
                                document.removeEventListener('scroll', lazyLoad);
                                window.removeEventListener('resize', lazyLoad);
                                window.removeEventListener('orientationchange', lazyLoad);
                            }
                        }
                    });
                    
                    active = false;
                }, 200);
            }
        };
        
        document.addEventListener('scroll', lazyLoad);
        window.addEventListener('resize', lazyLoad);
        window.addEventListener('orientationchange', lazyLoad);
    }
});
</script>
JS;
    }
}

// مثال للاستخدام
echo "🚀 === نظام التحميل الكسول ===\n\n";

// تهيئة النظام
$loader = LazyLoader::getInstance();

// تسجيل أصناف للتحميل الكسول
echo "📝 تسجيل الأصناف...\n";

// مثال: تسجيل أصناف وهمية
for ($i = 1; $i <= 100; $i++) {
    $loader->register("App\\Models\\Model$i", null, function($class) {
        // محاكاة تحميل صنف
        eval("class $class { public function getName() { return '$class'; } }");
    });
}

echo "✅ تم تسجيل 100 صنف\n\n";

// استخدام بعض الأصناف فقط
echo "🔄 استخدام بعض الأصناف...\n";
for ($i = 1; $i <= 10; $i++) {
    $class = "App\\Models\\Model$i";
    $obj = new $class();
    echo "• تم تحميل: " . $obj->getName() . "\n";
}

// عرض الإحصائيات
echo "\n📊 الإحصائيات:\n";
$stats = $loader->getStats();
foreach ($stats as $key => $value) {
    echo "• $key: $value\n";
}

// مثال للصور الكسولة
echo "\n\n🖼️ === تحميل الصور الكسول ===\n";

$imageLoader = new LazyImageLoader();

// إنشاء صور كسولة
$images = [];
for ($i = 1; $i <= 5; $i++) {
    $images[] = $imageLoader->createLazyImage(
        "/images/photo$i.jpg",
        "صورة $i",
        "gallery-image"
    );
}

echo "✅ تم إنشاء " . count($images) . " صورة كسولة\n";
echo "📝 مثال HTML:\n";
echo htmlspecialchars($images[0]) . "\n";

// عرض سكريبت التحميل
echo "\n📜 سكريبت التحميل:\n";
echo "تم إنشاء JavaScript للتحميل الكسول (IntersectionObserver)\n";

echo "\n✅ نظام التحميل الكسول جاهز للاستخدام!\n";