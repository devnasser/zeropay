<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ur', 'fa']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Zero Platform') }} - @yield('title', 'منصة التطوير الذكية')</title>

    <!-- Beta-52 Design: RTL/LTR Support with Bootstrap 5 -->
    @if(in_array(app()->getLocale(), ['ar', 'ur', 'fa']))
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    @else
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @endif
    
    <!-- Zero Platform Custom Styles -->
    <style>
        /* LEGEND-AI + Beta-52 Design System */
        :root {
            --zero-primary: #2563eb;
            --zero-secondary: #64748b;
            --zero-success: #059669;
            --zero-warning: #d97706;
            --zero-danger: #dc2626;
            --zero-info: #0891b2;
            --zero-dark: #1e293b;
            --zero-light: #f8fafc;
        }

        .zero-brand {
            background: linear-gradient(135deg, var(--zero-primary), var(--zero-info));
            color: white;
        }

        .zero-sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, var(--zero-dark), var(--zero-secondary));
        }

        .zero-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease-in-out;
        }

        .zero-card:hover {
            transform: translateY(-2px);
        }

        .zero-nav-link {
            color: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin: 0.25rem 0;
        }

        .zero-nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX({{ in_array(app()->getLocale(), ['ar', 'ur', 'fa']) ? '-' : '' }}4px);
        }

        .zero-nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        /* Beta-52 Arabic Typography */
        .arabic-text {
            font-family: 'Segoe UI', 'Cairo', 'Amiri', sans-serif;
            line-height: 1.8;
        }

        /* LEGEND-AI Accessibility Features */
        .zero-focus:focus {
            outline: 3px solid var(--zero-primary);
            outline-offset: 2px;
        }

        /* Gamma-97 Performance Optimization */
        .zero-loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .zero-transition {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>

    @livewireStyles
    @stack('styles')
</head>
<body class="bg-light arabic-text">
    <!-- LEGEND-AI Navigation Strategy -->
    <nav class="navbar navbar-expand-lg zero-brand shadow-sm">
        <div class="container-fluid">
            <!-- Zero Platform Branding -->
            <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ route('zero.dashboard') }}">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor" class="me-2">
                    <path d="M12 2L2 7L12 12L22 7L12 2Z"/>
                    <path d="M2 17L12 22L22 17"/>
                    <path d="M2 12L12 17L22 12"/>
                </svg>
                منصة Zero
            </a>

            <!-- Beta-52 Responsive Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="{{ route('zero.generator') }}">
                            🔧 مولد المنصات
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="{{ route('zero.templates') }}">
                            📋 القوالب
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="{{ route('zero.projects') }}">
                            🏗️ المشاريع
                        </a>
                    </li>
                </ul>

                <!-- User Menu -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                            👤 المستخدم
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('zero.profile') }}">الملف الشخصي</a></li>
                            <li><a class="dropdown-item" href="{{ route('zero.settings') }}">الإعدادات</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="{{ route('logout') }}">تسجيل الخروج</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div class="container-fluid">
        <div class="row">
            <!-- Beta-52 Sidebar (Optional) -->
            @hasSection('sidebar')
                <div class="col-md-3 col-lg-2 zero-sidebar text-white p-0">
                    <div class="p-3">
                        @yield('sidebar')
                    </div>
                </div>
                <div class="col-md-9 col-lg-10">
            @else
                <div class="col-12">
            @endif
                    <!-- Page Header -->
                    @hasSection('header')
                        <div class="bg-white shadow-sm mb-4">
                            <div class="container-fluid py-3">
                                @yield('header')
                            </div>
                        </div>
                    @endif

                    <!-- Flash Messages - Gamma-97 Security -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>✅ نجح!</strong> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>❌ خطأ!</strong> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <strong>⚠️ تنبيه!</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Main Content -->
                    <main class="py-4">
                        @yield('content')
                    </main>
                </div>
        </div>
    </div>

    <!-- LEGEND-AI Footer -->
    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>منصة Zero</h5>
                    <p class="text-muted">مولد المنصات الذكي - تم تطويره بواسطة السرب اللامحدود</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">
                        طُوِّر بتعاون: 
                        <span class="text-primary">👑 LEGEND-AI</span> • 
                        <span class="text-info">🔧 Alpha-51</span> • 
                        <span class="text-success">🎨 Beta-52</span> • 
                        <span class="text-warning">⚙️ Gamma-97</span>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts
    
    <!-- Alpha-51 + Gamma-97 Performance Enhancement -->
    <script>
        // LEGEND-AI Enhancement: Progressive loading
        document.addEventListener('DOMContentLoaded', function() {
            // Beta-52 Accessibility: Focus management
            const focusableElements = document.querySelectorAll('a, button, input, textarea, select');
            focusableElements.forEach(el => el.classList.add('zero-focus'));
            
            // Gamma-97 Performance: Lazy loading for heavy elements
            const lazyElements = document.querySelectorAll('[data-lazy]');
            if ('IntersectionObserver' in window) {
                const lazyObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('zero-transition');
                            lazyObserver.unobserve(entry.target);
                        }
                    });
                });
                lazyElements.forEach(el => lazyObserver.observe(el));
            }
        });

        // Alpha-51 Enhancement: Form validation helpers
        function validateZeroForm(formId) {
            const form = document.getElementById(formId);
            if (form) {
                form.addEventListener('submit', function(e) {
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;
                    
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            field.classList.add('is-invalid');
                            isValid = false;
                        } else {
                            field.classList.remove('is-invalid');
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        alert('يرجى ملء جميع الحقول المطلوبة');
                    }
                });
            }
        }
    </script>

    @stack('scripts')
</body>
</html>
