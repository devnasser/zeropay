/**
 * Service Worker للأداء الفائق
 * High-Performance Service Worker
 */

const CACHE_NAME = 'app-cache-v1';
const DYNAMIC_CACHE = 'dynamic-cache-v1';
const OFFLINE_URL = '/offline.html';

// الموارد الأساسية للتخزين المؤقت
const STATIC_ASSETS = [
    '/',
    '/index.html',
    '/offline.html',
    '/css/style.min.css',
    '/js/app.min.js',
    '/img/logo.png',
    '/manifest.json'
];

// أنواع الملفات للتخزين المؤقت الديناميكي
const CACHEABLE_TYPES = [
    'text/html',
    'text/css',
    'text/javascript',
    'application/javascript',
    'application/json',
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/svg+xml',
    'image/webp',
    'font/woff',
    'font/woff2'
];

// التثبيت
self.addEventListener('install', event => {
    console.log('🚀 Service Worker: Installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('📦 Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// التفعيل
self.addEventListener('activate', event => {
    console.log('✅ Service Worker: Activated');
    
    event.waitUntil(
        // حذف الكاش القديم
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(name => name !== CACHE_NAME && name !== DYNAMIC_CACHE)
                    .map(name => caches.delete(name))
            );
        }).then(() => self.clients.claim())
    );
});

// استراتيجية الجلب
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);
    
    // تجاهل الطلبات غير HTTP/HTTPS
    if (!url.protocol.startsWith('http')) {
        return;
    }
    
    // استراتيجية حسب نوع المورد
    if (request.destination === 'document') {
        // HTML: Network First, Cache Fallback
        event.respondWith(networkFirst(request));
    } else if (STATIC_ASSETS.includes(url.pathname)) {
        // Static Assets: Cache First
        event.respondWith(cacheFirst(request));
    } else if (request.destination === 'image') {
        // Images: Cache First with Network Update
        event.respondWith(cacheFirstWithUpdate(request));
    } else {
        // Default: Network First with Cache Fallback
        event.respondWith(networkFirst(request));
    }
});

// استراتيجية Cache First
async function cacheFirst(request) {
    const cache = await caches.open(CACHE_NAME);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        return caches.match(OFFLINE_URL);
    }
}

// استراتيجية Network First
async function networkFirst(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok && isCacheable(networkResponse)) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        return cachedResponse || caches.match(OFFLINE_URL);
    }
}

// استراتيجية Cache First with Update
async function cacheFirstWithUpdate(request) {
    const cache = await caches.open(DYNAMIC_CACHE);
    const cachedResponse = await cache.match(request);
    
    // إرجاع النسخة المخزنة فوراً
    const responsePromise = cachedResponse || fetch(request);
    
    // تحديث الكاش في الخلفية
    fetch(request)
        .then(networkResponse => {
            if (networkResponse.ok) {
                cache.put(request, networkResponse.clone());
            }
        })
        .catch(() => {});
    
    return responsePromise;
}

// التحقق من قابلية التخزين المؤقت
function isCacheable(response) {
    const contentType = response.headers.get('content-type');
    return CACHEABLE_TYPES.some(type => contentType && contentType.includes(type));
}

// مزامنة الخلفية
self.addEventListener('sync', event => {
    console.log('🔄 Background sync triggered');
    
    if (event.tag === 'sync-data') {
        event.waitUntil(syncData());
    }
});

// دفع الإشعارات
self.addEventListener('push', event => {
    const options = {
        body: event.data ? event.data.text() : 'تحديث جديد متاح',
        icon: '/img/icon-192.png',
        badge: '/img/badge-72.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        }
    };
    
    event.waitUntil(
        self.registration.showNotification('تطبيقك', options)
    );
});

// مزامنة البيانات
async function syncData() {
    try {
        // جلب البيانات الجديدة
        const response = await fetch('/api/sync');
        const data = await response.json();
        
        // تحديث الكاش
        const cache = await caches.open(DYNAMIC_CACHE);
        cache.put('/api/data', new Response(JSON.stringify(data)));
        
        return true;
    } catch (error) {
        console.error('Sync failed:', error);
        return false;
    }
}

// تنظيف الكاش الدوري
self.addEventListener('message', event => {
    if (event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    } else if (event.data.type === 'CLEAN_CACHE') {
        cleanOldCache();
    }
});

// تنظيف الكاش القديم
async function cleanOldCache() {
    const cache = await caches.open(DYNAMIC_CACHE);
    const requests = await cache.keys();
    const maxAge = 7 * 24 * 60 * 60 * 1000; // 7 أيام
    
    requests.forEach(async request => {
        const response = await cache.match(request);
        const dateHeader = response.headers.get('date');
        
        if (dateHeader) {
            const date = new Date(dateHeader);
            if (Date.now() - date.getTime() > maxAge) {
                cache.delete(request);
            }
        }
    });
}

console.log('⚡ Service Worker loaded successfully!');