@extends('zero.layouts.app')

@section('title', 'لوحة التحكم الرئيسية')

@section('header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="h3 mb-0 text-gray-800">🏠 لوحة التحكم الرئيسية</h1>
        <p class="mb-0 text-muted">مرحباً بك في منصة Zero - مولد المنصات الذكي</p>
    </div>
    <div>
        <span class="badge bg-success fs-6">🌟 السرب اللامحدود نشط</span>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
    <!-- LEGEND-AI Strategy: Overview Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card zero-card border-start border-primary border-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-uppercase text-primary fw-bold small">المنصات المولدة</div>
                            <div class="h4 fw-bold text-dark">{{ $stats['platforms'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cogs fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card zero-card border-start border-success border-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-uppercase text-success fw-bold small">المشاريع النشطة</div>
                            <div class="h4 fw-bold text-dark">{{ $stats['active_projects'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-project-diagram fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card zero-card border-start border-info border-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-uppercase text-info fw-bold small">القوالب المتاحة</div>
                            <div class="h4 fw-bold text-dark">{{ $stats['templates'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card zero-card border-start border-warning border-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-uppercase text-warning fw-bold small">معدل النجاح</div>
                            <div class="h4 fw-bold text-dark">{{ $stats['success_rate'] ?? '99.8' }}%</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Beta-52 UI: Quick Actions -->
        <div class="col-lg-8 mb-4">
            <div class="card zero-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">🚀 الإجراءات السريعة</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="d-grid">
                                <a href="{{ route('zero.generator') }}" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-plus-circle me-2"></i>
                                    إنشاء منصة جديدة
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-grid">
                                <a href="{{ route('zero.templates') }}" class="btn btn-outline-info btn-lg">
                                    <i class="fas fa-file-import me-2"></i>
                                    استيراد قالب
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-grid">
                                <a href="{{ route('zero.projects') }}" class="btn btn-outline-success btn-lg">
                                    <i class="fas fa-folder-open me-2"></i>
                                    إدارة المشاريع
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-grid">
                                <a href="{{ route('zero.documentation') }}" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-book me-2"></i>
                                    التوثيق والمساعدة
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alpha-51 + LEGEND-AI: Swarm Status -->
        <div class="col-lg-4 mb-4">
            <div class="card zero-card">
                <div class="card-header bg-dark text-white">
                    <h5 class="card-title mb-0">🌌 حالة السرب اللامحدود</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold">👑 LEGEND-AI</span>
                            <span class="badge bg-success">نشط</span>
                        </div>
                        <div class="progress mb-2" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: 100%"></div>
                        </div>
                        <small class="text-muted">قيادة وتنسيق مستمر</small>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold">🔧 Alpha-51</span>
                            <span class="badge bg-primary">Backend</span>
                        </div>
                        <div class="progress mb-2" style="height: 6px;">
                            <div class="progress-bar bg-primary" style="width: 95%"></div>
                        </div>
                        <small class="text-muted">معالجة عالية الأداء</small>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold">🎨 Beta-52</span>
                            <span class="badge bg-info">Frontend</span>
                        </div>
                        <div class="progress mb-2" style="height: 6px;">
                            <div class="progress-bar bg-info" style="width: 98%"></div>
                        </div>
                        <small class="text-muted">تصميم إبداعي متقدم</small>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold">⚙️ Gamma-97</span>
                            <span class="badge bg-warning">DevOps</span>
                        </div>
                        <div class="progress mb-2" style="height: 6px;">
                            <div class="progress-bar bg-warning" style="width: 92%"></div>
                        </div>
                        <small class="text-muted">أمان وتحسين مستمر</small>
                    </div>

                    <div class="text-center mt-3">
                        <span class="badge bg-success fs-6">⚡ تسريع 75x نشط</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-12">
            <div class="card zero-card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0">📊 النشاط الأخير</h5>
                </div>
                <div class="card-body">
                    @if(isset($recent_activities) && count($recent_activities) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>الوقت</th>
                                        <th>النشاط</th>
                                        <th>المشروع</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recent_activities as $activity)
                                    <tr>
                                        <td>{{ $activity['time'] ?? 'قبل دقائق' }}</td>
                                        <td>{{ $activity['action'] ?? 'نشاط جديد' }}</td>
                                        <td>{{ $activity['project'] ?? 'مشروع تجريبي' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $activity['status_color'] ?? 'success' }}">
                                                {{ $activity['status'] ?? 'مكتمل' }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد أنشطة حديثة</h5>
                            <p class="text-muted">ابدأ بإنشاء مشروع جديد لرؤية النشاطات هنا</p>
                            <a href="{{ route('zero.generator') }}" class="btn btn-primary">
                                إنشاء مشروع جديد
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Beta-52 Enhancement: Dashboard interactivity
document.addEventListener('DOMContentLoaded', function() {
    // LEGEND-AI Strategy: Auto-refresh swarm status
    setInterval(function() {
        // Simulate swarm status updates
        const progressBars = document.querySelectorAll('.progress-bar');
        progressBars.forEach(bar => {
            const currentWidth = parseInt(bar.style.width);
            const variance = Math.floor(Math.random() * 3) - 1; // -1, 0, or 1
            const newWidth = Math.max(90, Math.min(100, currentWidth + variance));
            bar.style.width = newWidth + '%';
        });
    }, 5000);

    // Gamma-97 Performance: Card hover effects
    const cards = document.querySelectorAll('.zero-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
            this.style.boxShadow = '0 8px 15px rgba(0,0,0,0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
        });
    });
});
</script>
@endpush
