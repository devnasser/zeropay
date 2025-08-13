@extends('zero.layouts.app')

@section('title', 'قوالب المنصات')

@section('header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="h3 mb-0 text-gray-800">📋 قوالب المنصات</h1>
        <p class="mb-0 text-muted">قوالب جاهزة لإنشاء منصات متنوعة بسرعة</p>
    </div>
    <div>
        <a href="{{ route('zero.generator') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> إنشاء قالب جديد
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Template Categories -->
        <div class="col-lg-3 mb-4">
            <div class="card zero-card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0">🗂️ فئات القوالب</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-group-item-action active">
                            <i class="fas fa-car me-2"></i>جميع القوالب
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-shopping-cart me-2"></i>التجارة الإلكترونية
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-utensils me-2"></i>المطاعم والطعام
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-graduation-cap me-2"></i>التعليم
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-home me-2"></i>الخدمات المنزلية
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-heart me-2"></i>الصحة والعافية
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Templates Grid -->
        <div class="col-lg-9">
            <div class="row">
                <!-- Auto Parts Template -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card zero-card h-100">
                        <div class="card-header bg-primary text-white text-center">
                            <i class="fas fa-car fa-2x mb-2"></i>
                            <h5 class="card-title mb-0">سوق قطع الغيار</h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">منصة متكاملة لبيع وشراء قطع غيار السيارات مع دعم البحث الصوتي والذكي</p>
                            <ul class="list-unstyled small">
                                <li><i class="fas fa-check text-success me-1"></i>بحث ذكي متقدم</li>
                                <li><i class="fas fa-check text-success me-1"></i>واجهة صوتية</li>
                                <li><i class="fas fa-check text-success me-1"></i>تكامل حكومي</li>
                                <li><i class="fas fa-check text-success me-1"></i>دعم متعدد اللغات</li>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <div class="d-grid gap-2">
                                <button class="btn btn-success btn-sm" onclick="useTemplate('auto_parts')">
                                    <i class="fas fa-download me-1"></i>استخدام القالب
                                </button>
                                <button class="btn btn-outline-info btn-sm" onclick="previewTemplate('auto_parts')">
                                    <i class="fas fa-eye me-1"></i>معاينة
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Restaurant Template -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card zero-card h-100">
                        <div class="card-header bg-warning text-dark text-center">
                            <i class="fas fa-utensils fa-2x mb-2"></i>
                            <h5 class="card-title mb-0">منصة المطاعم</h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">منصة شاملة لإدارة المطاعم مع نظام الطلبات والتوصيل</p>
                            <ul class="list-unstyled small">
                                <li><i class="fas fa-check text-success me-1"></i>إدارة القوائم</li>
                                <li><i class="fas fa-check text-success me-1"></i>نظام الطلبات</li>
                                <li><i class="fas fa-check text-success me-1"></i>إدارة التوصيل</li>
                                <li><i class="fas fa-check text-success me-1"></i>تقارير المبيعات</li>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <div class="d-grid gap-2">
                                <button class="btn btn-warning btn-sm" onclick="useTemplate('restaurant')">
                                    <i class="fas fa-download me-1"></i>استخدام القالب
                                </button>
                                <button class="btn btn-outline-info btn-sm" onclick="previewTemplate('restaurant')">
                                    <i class="fas fa-eye me-1"></i>معاينة
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Education Template -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card zero-card h-100">
                        <div class="card-header bg-info text-white text-center">
                            <i class="fas fa-graduation-cap fa-2x mb-2"></i>
                            <h5 class="card-title mb-0">منصة التعليم</h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">نظام إدارة تعلم متكامل مع دعم الفصول الافتراضية</p>
                            <ul class="list-unstyled small">
                                <li><i class="fas fa-check text-success me-1"></i>إدارة الدورات</li>
                                <li><i class="fas fa-check text-success me-1"></i>فصول افتراضية</li>
                                <li><i class="fas fa-check text-success me-1"></i>نظام التقييم</li>
                                <li><i class="fas fa-check text-success me-1"></i>تتبع التقدم</li>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <div class="d-grid gap-2">
                                <button class="btn btn-info btn-sm" onclick="useTemplate('education')">
                                    <i class="fas fa-download me-1"></i>استخدام القالب
                                </button>
                                <button class="btn btn-outline-info btn-sm" onclick="previewTemplate('education')">
                                    <i class="fas fa-eye me-1"></i>معاينة
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Home Services Template -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card zero-card h-100">
                        <div class="card-header bg-success text-white text-center">
                            <i class="fas fa-home fa-2x mb-2"></i>
                            <h5 class="card-title mb-0">الخدمات المنزلية</h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">منصة ربط مقدمي الخدمات المنزلية بالعملاء مع نظام الحجز</p>
                            <ul class="list-unstyled small">
                                <li><i class="fas fa-check text-success me-1"></i>حجز الخدمات</li>
                                <li><i class="fas fa-check text-success me-1"></i>تقييم مقدمي الخدمة</li>
                                <li><i class="fas fa-check text-success me-1"></i>تتبع الطلبات</li>
                                <li><i class="fas fa-check text-success me-1"></i>نظام الدفع</li>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <div class="d-grid gap-2">
                                <button class="btn btn-success btn-sm" onclick="useTemplate('home_services')">
                                    <i class="fas fa-download me-1"></i>استخدام القالب
                                </button>
                                <button class="btn btn-outline-info btn-sm" onclick="previewTemplate('home_services')">
                                    <i class="fas fa-eye me-1"></i>معاينة
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Coming Soon Templates -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card zero-card h-100">
                        <div class="card-header bg-light text-dark text-center">
                            <i class="fas fa-plus-circle fa-2x mb-2"></i>
                            <h5 class="card-title mb-0">قوالب إضافية</h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">المزيد من القوالب قادمة قريباً...</p>
                            <ul class="list-unstyled small text-muted">
                                <li><i class="fas fa-clock me-1"></i>منصة الصحة والعافية</li>
                                <li><i class="fas fa-clock me-1"></i>منصة العقارات</li>
                                <li><i class="fas fa-clock me-1"></i>منصة التوظيف</li>
                                <li><i class="fas fa-clock me-1"></i>منصة السفر والسياحة</li>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <div class="d-grid">
                                <button class="btn btn-outline-secondary btn-sm" disabled>
                                    <i class="fas fa-hourglass-half me-1"></i>قريباً
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template Preview Modal -->
<div class="modal fade" id="templatePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">معاينة القالب</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="templatePreviewContent">
                    <!-- Template preview will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary" id="useTemplateFromPreview">استخدام هذا القالب</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentTemplate = null;

function useTemplate(templateName) {
    // Get template YAML
    const templates = {
        'auto_parts': `platform_info:
  name: 'منصة قطع الغيار'
  type: 'marketplace'
  description: 'سوق إلكتروني لقطع غيار السيارات'
  
database_schema:
  tables:
    users:
      columns:
        id: {type: 'integer', primary: true}
        name: {type: 'string'}
        email: {type: 'string'}
        phone: {type: 'string'}
        type: {type: 'enum', values: ['buyer', 'seller', 'admin']}
    categories:
      columns:
        id: {type: 'integer', primary: true}
        name: {type: 'string'}
        name_ar: {type: 'string'}
        parent_id: {type: 'integer', nullable: true}
    products:
      columns:
        id: {type: 'integer', primary: true}
        name: {type: 'string'}
        name_ar: {type: 'string'}
        description: {type: 'text'}
        price: {type: 'decimal'}
        category_id: {type: 'integer'}
        seller_id: {type: 'integer'}
        sku: {type: 'string'}
        stock: {type: 'integer'}
    orders:
      columns:
        id: {type: 'integer', primary: true}
        user_id: {type: 'integer'}
        total: {type: 'decimal'}
        status: {type: 'enum', values: ['pending', 'confirmed', 'shipped', 'delivered']}
        
user_interface:
  theme: 'automotive'
  rtl_support: true
  accessibility: true
  voice_search: true
  multi_language: ['ar', 'en']
  
business_logic:
  services:
    - SearchService
    - RecommendationService
    - VoiceSearchService
    - PaymentService
  features:
    - smart_search
    - voice_interface
    - government_integration
    - multi_language_support`,
        
        'restaurant': `platform_info:
  name: 'منصة المطاعم'
  type: 'restaurant_management'
  description: 'نظام إدارة شامل للمطاعم'`,
        
        'education': `platform_info:
  name: 'منصة التعليم'
  type: 'learning_management'
  description: 'نظام إدارة تعلم متكامل'`,
        
        'home_services': `platform_info:
  name: 'الخدمات المنزلية'
  type: 'service_marketplace'
  description: 'منصة ربط مقدمي الخدمات بالعملاء'`
    };
    
    // Redirect to generator with template
    const template = templates[templateName];
    if (template) {
        // Store in session storage and redirect
        sessionStorage.setItem('selectedTemplate', template);
        window.location.href = '{{ route("zero.generator") }}';
    }
}

function previewTemplate(templateName) {
    currentTemplate = templateName;
    
    const previews = {
        'auto_parts': `
            <div class="row">
                <div class="col-md-6">
                    <h6>📊 معلومات القالب</h6>
                    <ul class="list-group">
                        <li class="list-group-item">النوع: سوق إلكتروني</li>
                        <li class="list-group-item">الجداول: 5 جداول أساسية</li>
                        <li class="list-group-item">اللغات: العربية والإنجليزية</li>
                        <li class="list-group-item">الميزات: 12 ميزة متقدمة</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>🎯 الميزات الرئيسية</h6>
                    <ul class="list-group">
                        <li class="list-group-item">✅ بحث ذكي وصوتي</li>
                        <li class="list-group-item">✅ تكامل مع SABER</li>
                        <li class="list-group-item">✅ نظام توصيات AI</li>
                        <li class="list-group-item">✅ دعم متعدد اللغات</li>
                    </ul>
                </div>
            </div>
            <div class="mt-3">
                <h6>📋 هيكل قاعدة البيانات</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>الجدول</th><th>الأعمدة</th><th>الوصف</th></tr></thead>
                        <tbody>
                            <tr><td>users</td><td>5</td><td>بيانات المستخدمين</td></tr>
                            <tr><td>categories</td><td>4</td><td>تصنيفات المنتجات</td></tr>
                            <tr><td>products</td><td>9</td><td>منتجات قطع الغيار</td></tr>
                            <tr><td>orders</td><td>5</td><td>الطلبات والمبيعات</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>`,
        'restaurant': `
            <div class="text-center">
                <h5>🍽️ منصة المطاعم</h5>
                <p>قالب شامل لإدارة المطاعم مع نظام الطلبات والتوصيل</p>
                <div class="alert alert-info">
                    هذا القالب قيد التطوير - سيكون متاحاً قريباً
                </div>
            </div>`,
        'education': `
            <div class="text-center">
                <h5>🎓 منصة التعليم</h5>
                <p>نظام إدارة تعلم متكامل مع دعم الفصول الافتراضية</p>
                <div class="alert alert-info">
                    هذا القالب قيد التطوير - سيكون متاحاً قريباً
                </div>
            </div>`,
        'home_services': `
            <div class="text-center">
                <h5>🏠 الخدمات المنزلية</h5>
                <p>منصة ربط مقدمي الخدمات المنزلية بالعملاء</p>
                <div class="alert alert-info">
                    هذا القالب قيد التطوير - سيكون متاحاً قريباً
                </div>
            </div>`
    };
    
    document.getElementById('templatePreviewContent').innerHTML = previews[templateName];
    new bootstrap.Modal(document.getElementById('templatePreviewModal')).show();
}

document.getElementById('useTemplateFromPreview').addEventListener('click', function() {
    if (currentTemplate) {
        useTemplate(currentTemplate);
    }
});

// Load template from session storage if available
document.addEventListener('DOMContentLoaded', function() {
    // This will be used by generator page to load selected template
});
</script>
@endpush
