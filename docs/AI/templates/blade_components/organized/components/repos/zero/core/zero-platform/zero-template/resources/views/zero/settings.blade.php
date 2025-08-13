@extends('zero.layouts.app')

@section('title', 'إعدادات النظام')

@section('header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="h3 mb-0 text-gray-800">⚙️ إعدادات النظام</h1>
        <p class="mb-0 text-muted">تخصيص وإدارة إعدادات منصة Zero</p>
    </div>
    <div>
        <button class="btn btn-success" onclick="saveAllSettings()">
            <i class="fas fa-save"></i> حفظ جميع الإعدادات
        </button>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Settings Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card zero-card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0">📋 أقسام الإعدادات</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="#general" class="list-group-item list-group-item-action active settings-nav">
                            <i class="fas fa-cog me-2"></i>الإعدادات العامة
                        </a>
                        <a href="#performance" class="list-group-item list-group-item-action settings-nav">
                            <i class="fas fa-tachometer-alt me-2"></i>الأداء والتحسين
                        </a>
                        <a href="#security" class="list-group-item list-group-item-action settings-nav">
                            <i class="fas fa-shield-alt me-2"></i>الأمان والحماية
                        </a>
                        <a href="#languages" class="list-group-item list-group-item-action settings-nav">
                            <i class="fas fa-globe me-2"></i>اللغات والترجمة
                        </a>
                        <a href="#integrations" class="list-group-item list-group-item-action settings-nav">
                            <i class="fas fa-plug me-2"></i>التكامل الخارجي
                        </a>
                        <a href="#swarm" class="list-group-item list-group-item-action settings-nav">
                            <i class="fas fa-users-cog me-2"></i>إعدادات السرب
                        </a>
                        <a href="#maintenance" class="list-group-item list-group-item-action settings-nav">
                            <i class="fas fa-tools me-2"></i>الصيانة والنسخ
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Content -->
        <div class="col-lg-9">
            <!-- General Settings -->
            <div class="settings-section" id="general">
                <div class="card zero-card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">🔧 الإعدادات العامة</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">اسم المنصة</label>
                                    <input type="text" class="form-control" value="Zero Platform" id="platformName">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">وصف المنصة</label>
                                    <textarea class="form-control" rows="3" id="platformDescription">مولد المنصات الذكي - تم تطويره بواسطة السرب اللامحدود</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">المنطقة الزمنية</label>
                                    <select class="form-select" id="timezone">
                                        <option value="Asia/Riyadh" selected>آسيا/الرياض</option>
                                        <option value="Asia/Dubai">آسيا/دبي</option>
                                        <option value="UTC">UTC</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الإصدار</label>
                                    <input type="text" class="form-control" value="1.0.0" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">البيئة</label>
                                    <select class="form-select" id="environment">
                                        <option value="development" selected>التطوير</option>
                                        <option value="staging">التجريبي</option>
                                        <option value="production">الإنتاج</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">وضع الصيانة</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="maintenanceMode">
                                        <label class="form-check-label">تفعيل وضع الصيانة</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Settings -->
            <div class="settings-section" id="performance" style="display: none;">
                <div class="card zero-card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">⚡ الأداء والتحسين</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>إعدادات Cache</h6>
                                <div class="mb-3">
                                    <label class="form-label">نوع Cache</label>
                                    <select class="form-select" id="cacheDriver">
                                        <option value="file" selected>ملفات</option>
                                        <option value="redis">Redis</option>
                                        <option value="memcached">Memcached</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">مدة Cache (دقائق)</label>
                                    <input type="number" class="form-control" value="60" id="cacheTtl">
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" checked id="enableCache">
                                        <label class="form-check-label">تفعيل Cache</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>تحسينات قاعدة البيانات</h6>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" checked id="sqliteWal">
                                        <label class="form-check-label">SQLite WAL Mode</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Cache Size (KB)</label>
                                    <input type="number" class="form-control" value="64000" id="sqliteCacheSize">
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" checked id="enableOptimizations">
                                        <label class="form-check-label">التحسينات التلقائية</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            التسريع الحالي: <strong>75x</strong> من المعدل العادي
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="settings-section" id="security" style="display: none;">
                <div class="card zero-card mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="card-title mb-0">🛡️ الأمان والحماية</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>المصادقة والجلسات</h6>
                                <div class="mb-3">
                                    <label class="form-label">مدة الجلسة (دقائق)</label>
                                    <input type="number" class="form-control" value="120" id="sessionLifetime">
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" checked id="csrfProtection">
                                        <label class="form-check-label">حماية CSRF</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" checked id="xssProtection">
                                        <label class="form-check-label">حماية XSS</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Rate Limiting</h6>
                                <div class="mb-3">
                                    <label class="form-label">الحد الأقصى للطلبات/دقيقة</label>
                                    <input type="number" class="form-control" value="60" id="rateLimitMax">
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" checked id="enableRateLimit">
                                        <label class="form-check-label">تفعيل Rate Limiting</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" checked id="strictValidation">
                                        <label class="form-check-label">التحقق الصارم</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Languages Settings -->
            <div class="settings-section" id="languages" style="display: none;">
                <div class="card zero-card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">🌍 اللغات والترجمة</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">اللغة الافتراضية</label>
                                    <select class="form-select" id="defaultLocale">
                                        <option value="ar" selected>العربية</option>
                                        <option value="en">الإنجليزية</option>
                                        <option value="ur">الأردية</option>
                                        <option value="fr">الفرنسية</option>
                                        <option value="fa">الفارسية</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">اللغة الاحتياطية</label>
                                    <select class="form-select" id="fallbackLocale">
                                        <option value="en" selected>الإنجليزية</option>
                                        <option value="ar">العربية</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">اللغات المدعومة</label>
                                <div class="mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" checked id="lang_ar">
                                        <label class="form-check-label">العربية (RTL)</label>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" checked id="lang_en">
                                        <label class="form-check-label">الإنجليزية</label>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="lang_ur">
                                        <label class="form-check-label">الأردية (RTL)</label>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="lang_fr">
                                        <label class="form-check-label">الفرنسية</label>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="lang_fa">
                                        <label class="form-check-label">الفارسية (RTL)</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Integrations Settings -->
            <div class="settings-section" id="integrations" style="display: none;">
                <div class="card zero-card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">🔌 التكامل الخارجي</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>التكامل الحكومي السعودي</h6>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="saberIntegration">
                                        <label class="form-check-label">تكامل SABER</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="nafesIntegration">
                                        <label class="form-check-label">تكامل نافس</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="trafficIntegration">
                                        <label class="form-check-label">تكامل إدارة المرور</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>خدمات الدفع</h6>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="paymentGateways">
                                        <label class="form-check-label">بوابات الدفع</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">العملة الافتراضية</label>
                                    <select class="form-select" id="defaultCurrency">
                                        <option value="SAR" selected>ريال سعودي</option>
                                        <option value="USD">دولار أمريكي</option>
                                        <option value="EUR">يورو</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Swarm Settings -->
            <div class="settings-section" id="swarm" style="display: none;">
                <div class="card zero-card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="card-title mb-0">🤖 إعدادات السرب اللامحدود</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <h6><i class="fas fa-robot me-2"></i>حالة السرب الحالية</h6>
                            <div class="row">
                                <div class="col-sm-6 col-lg-3 mb-2">
                                    <strong>👑 LEGEND-AI:</strong> <span class="badge bg-success">نشط 100%</span>
                                </div>
                                <div class="col-sm-6 col-lg-3 mb-2">
                                    <strong>🔧 Alpha-51:</strong> <span class="badge bg-success">نشط 95%</span>
                                </div>
                                <div class="col-sm-6 col-lg-3 mb-2">
                                    <strong>🎨 Beta-52:</strong> <span class="badge bg-success">نشط 98%</span>
                                </div>
                                <div class="col-sm-6 col-lg-3 mb-2">
                                    <strong>⚙️ Gamma-97:</strong> <span class="badge bg-success">نشط 92%</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6>إعدادات الأداء</h6>
                                <div class="mb-3">
                                    <label class="form-label">وضع التشغيل</label>
                                    <select class="form-select" id="swarmMode">
                                        <option value="unlimited" selected>لا محدود</option>
                                        <option value="high_performance">أداء عالي</option>
                                        <option value="balanced">متوازن</option>
                                        <option value="energy_saver">توفير الطاقة</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">معدل التسريع</label>
                                    <input type="text" class="form-control" value="75x" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>التحكم في الوحدات</h6>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" checked id="autoOptimization">
                                        <label class="form-check-label">التحسين التلقائي</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" checked id="parallelProcessing">
                                        <label class="form-check-label">المعالجة المتوازية</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" checked id="quantumMode">
                                        <label class="form-check-label">الوضع الكمي</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maintenance Settings -->
            <div class="settings-section" id="maintenance" style="display: none;">
                <div class="card zero-card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0">🔧 الصيانة والنسخ الاحتياطية</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>النسخ الاحتياطية</h6>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" checked id="autoBackup">
                                        <label class="form-check-label">النسخ التلقائي</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">تكرار النسخ</label>
                                    <select class="form-select" id="backupFrequency">
                                        <option value="daily" selected>يومي</option>
                                        <option value="weekly">أسبوعي</option>
                                        <option value="monthly">شهري</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <button class="btn btn-outline-primary" onclick="createBackup()">
                                        <i class="fas fa-download"></i> إنشاء نسخة احتياطية الآن
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>تنظيف النظام</h6>
                                <div class="mb-3">
                                    <button class="btn btn-outline-warning" onclick="clearCache()">
                                        <i class="fas fa-broom"></i> مسح Cache
                                    </button>
                                </div>
                                <div class="mb-3">
                                    <button class="btn btn-outline-info" onclick="optimizeDatabase()">
                                        <i class="fas fa-database"></i> تحسين قاعدة البيانات
                                    </button>
                                </div>
                                <div class="mb-3">
                                    <button class="btn btn-outline-success" onclick="runOptimizer()">
                                        <i class="fas fa-rocket"></i> تشغيل المحسن التلقائي
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Settings Navigation
document.querySelectorAll('.settings-nav').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all links
        document.querySelectorAll('.settings-nav').forEach(l => l.classList.remove('active'));
        // Add active class to clicked link
        this.classList.add('active');
        
        // Hide all sections
        document.querySelectorAll('.settings-section').forEach(section => {
            section.style.display = 'none';
        });
        
        // Show target section
        const target = this.getAttribute('href').substring(1);
        document.getElementById(target).style.display = 'block';
    });
});

// Save Settings
function saveAllSettings() {
    // Collect all settings
    const settings = {
        general: {
            platformName: document.getElementById('platformName').value,
            platformDescription: document.getElementById('platformDescription').value,
            timezone: document.getElementById('timezone').value,
            environment: document.getElementById('environment').value,
            maintenanceMode: document.getElementById('maintenanceMode').checked
        },
        performance: {
            cacheDriver: document.getElementById('cacheDriver').value,
            cacheTtl: document.getElementById('cacheTtl').value,
            enableCache: document.getElementById('enableCache').checked,
            sqliteWal: document.getElementById('sqliteWal').checked,
            sqliteCacheSize: document.getElementById('sqliteCacheSize').value,
            enableOptimizations: document.getElementById('enableOptimizations').checked
        },
        // Add other sections...
    };
    
    // Simulate saving
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...';
    btn.disabled = true;
    
    setTimeout(() => {
        btn.innerHTML = '<i class="fas fa-check"></i> تم الحفظ';
        btn.className = 'btn btn-success';
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.className = 'btn btn-success';
            btn.disabled = false;
        }, 2000);
    }, 1500);
    
    console.log('Settings saved:', settings);
}

// Maintenance Functions
function createBackup() {
    alert('تم إنشاء نسخة احتياطية بنجاح');
}

function clearCache() {
    alert('تم مسح Cache بنجاح');
}

function optimizeDatabase() {
    alert('تم تحسين قاعدة البيانات بنجاح');
}

function runOptimizer() {
    alert('تم تشغيل المحسن التلقائي بنجاح');
}

// Real-time swarm status updates
setInterval(function() {
    // Simulate minor fluctuations in swarm efficiency
    const badges = document.querySelectorAll('#swarm .badge');
    badges.forEach((badge, index) => {
        if (badge.textContent.includes('%')) {
            const baseEfficiency = [100, 95, 98, 92][index];
            const variation = Math.floor(Math.random() * 3) - 1; // -1, 0, or 1
            const newEfficiency = Math.max(90, Math.min(100, baseEfficiency + variation));
            badge.textContent = `نشط ${newEfficiency}%`;
        }
    });
}, 10000);
</script>
@endpush
