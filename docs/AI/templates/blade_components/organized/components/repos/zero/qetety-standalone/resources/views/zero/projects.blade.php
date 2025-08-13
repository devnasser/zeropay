@extends('zero.layouts.app')

@section('title', 'إدارة المشاريع')

@section('header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="h3 mb-0 text-gray-800">🏗️ إدارة المشاريع</h1>
        <p class="mb-0 text-muted">جميع المشاريع المولدة والتحكم بها</p>
    </div>
    <div>
        <a href="{{ route('zero.generator') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> مشروع جديد
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Project Statistics -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card zero-card border-start border-primary border-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-uppercase text-primary fw-bold small">المشاريع النشطة</div>
                            <div class="h4 fw-bold text-dark">{{ $stats['active'] ?? 3 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-play fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card zero-card border-start border-success border-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-uppercase text-success fw-bold small">مكتمل</div>
                            <div class="h4 fw-bold text-dark">{{ $stats['completed'] ?? 7 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card zero-card border-start border-warning border-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-uppercase text-warning fw-bold small">قيد التطوير</div>
                            <div class="h4 fw-bold text-dark">{{ $stats['development'] ?? 2 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-code fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card zero-card border-start border-info border-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-uppercase text-info fw-bold small">إجمالي المشاريع</div>
                            <div class="h4 fw-bold text-dark">{{ $stats['total'] ?? 12 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-folder fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Projects Filter and Search -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card zero-card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" placeholder="البحث في المشاريع..." id="projectSearch">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="statusFilter">
                                <option value="">جميع الحالات</option>
                                <option value="active">نشط</option>
                                <option value="completed">مكتمل</option>
                                <option value="development">قيد التطوير</option>
                                <option value="paused">متوقف مؤقتاً</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="typeFilter">
                                <option value="">جميع الأنواع</option>
                                <option value="marketplace">سوق إلكتروني</option>
                                <option value="restaurant">مطعم</option>
                                <option value="education">تعليمي</option>
                                <option value="services">خدمات</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                                <i class="fas fa-undo"></i> إعادة تعيين
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Projects Grid -->
    <div class="row" id="projectsGrid">
        <!-- Auto Parts Marketplace Project -->
        <div class="col-lg-4 col-md-6 mb-4 project-card" data-status="active" data-type="marketplace">
            <div class="card zero-card h-100">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">🚗 سوق قطع الغيار</h6>
                        <span class="badge bg-success">نشط</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-text">منصة متكاملة لبيع وشراء قطع غيار السيارات مع الذكاء الاصطناعي</p>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small">
                            <span>التقدم</span>
                            <span>85%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: 85%"></div>
                        </div>
                    </div>
                    <div class="row text-center">
                        <div class="col-4">
                            <small class="text-muted">الملفات</small>
                            <div class="fw-bold">247</div>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">الجداول</small>
                            <div class="fw-bold">12</div>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">الصفحات</small>
                            <div class="fw-bold">18</div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="btn-group w-100" role="group">
                        <button class="btn btn-outline-primary btn-sm" onclick="editProject('auto_parts')">
                            <i class="fas fa-edit"></i> تحرير
                        </button>
                        <button class="btn btn-outline-success btn-sm" onclick="deployProject('auto_parts')">
                            <i class="fas fa-rocket"></i> نشر
                        </button>
                        <button class="btn btn-outline-info btn-sm" onclick="viewProject('auto_parts')">
                            <i class="fas fa-eye"></i> عرض
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Restaurant Platform Project -->
        <div class="col-lg-4 col-md-6 mb-4 project-card" data-status="development" data-type="restaurant">
            <div class="card zero-card h-100">
                <div class="card-header bg-warning text-dark">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">🍽️ منصة المطاعم</h6>
                        <span class="badge bg-warning">قيد التطوير</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-text">نظام إدارة شامل للمطاعم مع الطلبات والتوصيل</p>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small">
                            <span>التقدم</span>
                            <span>45%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-warning" style="width: 45%"></div>
                        </div>
                    </div>
                    <div class="row text-center">
                        <div class="col-4">
                            <small class="text-muted">الملفات</small>
                            <div class="fw-bold">112</div>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">الجداول</small>
                            <div class="fw-bold">8</div>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">الصفحات</small>
                            <div class="fw-bold">12</div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="btn-group w-100" role="group">
                        <button class="btn btn-outline-primary btn-sm" onclick="editProject('restaurant')">
                            <i class="fas fa-edit"></i> تحرير
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" disabled>
                            <i class="fas fa-pause"></i> متوقف
                        </button>
                        <button class="btn btn-outline-info btn-sm" onclick="viewProject('restaurant')">
                            <i class="fas fa-eye"></i> عرض
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Education Platform Project -->
        <div class="col-lg-4 col-md-6 mb-4 project-card" data-status="completed" data-type="education">
            <div class="card zero-card h-100">
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">🎓 منصة التعليم</h6>
                        <span class="badge bg-light text-dark">مكتمل</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-text">نظام إدارة تعلم متكامل مع الفصول الافتراضية</p>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small">
                            <span>التقدم</span>
                            <span>100%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: 100%"></div>
                        </div>
                    </div>
                    <div class="row text-center">
                        <div class="col-4">
                            <small class="text-muted">الملفات</small>
                            <div class="fw-bold">189</div>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">الجداول</small>
                            <div class="fw-bold">15</div>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">الصفحات</small>
                            <div class="fw-bold">24</div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="btn-group w-100" role="group">
                        <button class="btn btn-outline-primary btn-sm" onclick="editProject('education')">
                            <i class="fas fa-edit"></i> تحرير
                        </button>
                        <button class="btn btn-success btn-sm" onclick="launchProject('education')">
                            <i class="fas fa-rocket"></i> تشغيل
                        </button>
                        <button class="btn btn-outline-info btn-sm" onclick="viewProject('education')">
                            <i class="fas fa-eye"></i> عرض
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- More projects can be added here -->
    </div>

    <!-- Empty State -->
    <div class="row" id="emptyState" style="display: none;">
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">لا توجد مشاريع</h5>
                <p class="text-muted">ابدأ بإنشاء مشروعك الأول</p>
                <a href="{{ route('zero.generator') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> إنشاء مشروع جديد
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Project Actions Modal -->
<div class="modal fade" id="projectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="projectModalTitle">إجراءات المشروع</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="projectModalBody">
                <!-- Dynamic content -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="projectModalAction">تنفيذ</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Search and Filter functionality
document.getElementById('projectSearch').addEventListener('input', filterProjects);
document.getElementById('statusFilter').addEventListener('change', filterProjects);
document.getElementById('typeFilter').addEventListener('change', filterProjects);

function filterProjects() {
    const searchTerm = document.getElementById('projectSearch').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    const typeFilter = document.getElementById('typeFilter').value;
    
    const projectCards = document.querySelectorAll('.project-card');
    let visibleCount = 0;
    
    projectCards.forEach(card => {
        const title = card.querySelector('.card-title').textContent.toLowerCase();
        const status = card.getAttribute('data-status');
        const type = card.getAttribute('data-type');
        
        const matchesSearch = searchTerm === '' || title.includes(searchTerm);
        const matchesStatus = statusFilter === '' || status === statusFilter;
        const matchesType = typeFilter === '' || type === typeFilter;
        
        if (matchesSearch && matchesStatus && matchesType) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show empty state if no projects visible
    document.getElementById('emptyState').style.display = visibleCount === 0 ? 'block' : 'none';
}

function resetFilters() {
    document.getElementById('projectSearch').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('typeFilter').value = '';
    filterProjects();
}

// Project Actions
function editProject(projectName) {
    alert(`تحرير مشروع: ${projectName}`);
    // Redirect to generator with project data
}

function deployProject(projectName) {
    showProjectModal('نشر المشروع', `هل تريد نشر مشروع ${projectName}؟`, 'نشر', function() {
        alert(`تم نشر مشروع: ${projectName}`);
    });
}

function viewProject(projectName) {
    // Open project in new tab
    window.open(`/projects/${projectName}`, '_blank');
}

function launchProject(projectName) {
    showProjectModal('تشغيل المشروع', `هل تريد تشغيل مشروع ${projectName}؟`, 'تشغيل', function() {
        alert(`تم تشغيل مشروع: ${projectName}`);
    });
}

function showProjectModal(title, body, actionText, actionCallback) {
    document.getElementById('projectModalTitle').textContent = title;
    document.getElementById('projectModalBody').innerHTML = `<p>${body}</p>`;
    document.getElementById('projectModalAction').textContent = actionText;
    
    const modal = new bootstrap.Modal(document.getElementById('projectModal'));
    modal.show();
    
    // Set action callback
    document.getElementById('projectModalAction').onclick = function() {
        actionCallback();
        modal.hide();
    };
}

// Real-time updates simulation
setInterval(function() {
    // Simulate progress updates
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        if (!bar.style.width.includes('100%')) {
            const currentWidth = parseInt(bar.style.width);
            if (Math.random() > 0.8) { // 20% chance to update
                const newWidth = Math.min(100, currentWidth + Math.floor(Math.random() * 3));
                bar.style.width = newWidth + '%';
                bar.parentElement.previousElementSibling.querySelector('span:last-child').textContent = newWidth + '%';
            }
        }
    });
}, 5000);
</script>
@endpush
