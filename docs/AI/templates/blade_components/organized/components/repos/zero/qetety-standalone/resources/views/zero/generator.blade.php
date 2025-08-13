@extends('zero.layouts.app')

@section('title', 'مولد المنصات')

@section('header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="h3 mb-0 text-gray-800">🔧 مولد المنصات</h1>
        <p class="mb-0 text-muted">قم بإنشاء منصتك من خلال YAML بسيطة</p>
    </div>
    <div>
        <span class="badge bg-primary fs-6">⚡ تشغيل مباشر</span>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card zero-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">📝 محرر YAML</h5>
                </div>
                <div class="card-body">
                    <form id="generatorForm">
                        <div class="mb-3">
                            <label for="platformName" class="form-label">اسم المنصة</label>
                            <input type="text" class="form-control" id="platformName" value="My Platform" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="yamlContent" class="form-label">محتوى YAML</label>
                            <textarea class="form-control" id="yamlContent" rows="20" required>platform_info:
  name: 'My Platform'
  type: 'web_application'
  description: 'منصة تجريبية'

database_schema:
  tables:
    users:
      columns:
        id:
          type: 'integer'
          primary: true
        name:
          type: 'string'
        email:
          type: 'string'
    
user_interface:
  theme: 'modern'
  rtl_support: true
  accessibility: true</textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success btn-lg" onclick="parseYaml()">
                                <i class="fas fa-check-circle me-2"></i>
                                تحليل YAML
                            </button>
                            <button type="button" class="btn btn-primary btn-lg" onclick="generatePlatform()" disabled id="generateBtn">
                                <i class="fas fa-cogs me-2"></i>
                                توليد المنصة
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card zero-card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">📊 نتائج المعالجة</h5>
                </div>
                <div class="card-body">
                    <div id="results">
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-code fa-3x mb-3"></i>
                            <h5>انتظار محتوى YAML</h5>
                            <p>قم بكتابة YAML واضغط "تحليل YAML" لرؤية النتائج</p>
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
let parsedData = null;

function parseYaml() {
    const yamlContent = document.getElementById('yamlContent').value;
    const resultsDiv = document.getElementById('results');
    
    resultsDiv.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">جاري التحليل...</p></div>';
    
    fetch('/zero/parse-yaml', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            yaml_content: yamlContent
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            parsedData = data.data;
            document.getElementById('generateBtn').disabled = false;
            
            resultsDiv.innerHTML = `
                <div class="alert alert-success">
                    <h6><i class="fas fa-check-circle"></i> تم التحليل بنجاح!</h6>
                </div>
                <h6>📋 معلومات المنصة:</h6>
                <ul class="list-group list-group-flush mb-3">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>الاسم:</span>
                        <strong>${parsedData.platform_info.name}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>النوع:</span>
                        <strong>${parsedData.platform_info.type}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>تم التوليد بواسطة:</span>
                        <strong>${parsedData.platform_info.generated_by}</strong>
                    </li>
                </ul>
                
                <h6>🗄️ قاعدة البيانات:</h6>
                <p class="text-muted">عدد الجداول: ${parsedData.database_schema ? Object.keys(parsedData.database_schema.tables || {}).length : 0}</p>
                
                <h6>🎨 واجهة المستخدم:</h6>
                <p class="text-muted">دعم العربية: ${parsedData.user_interface?.rtl_support ? 'نعم' : 'لا'}</p>
                
                <pre class="bg-light p-3 small">${JSON.stringify(parsedData, null, 2)}</pre>
            `;
        } else {
            resultsDiv.innerHTML = `
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle"></i> خطأ في التحليل</h6>
                    <p class="mb-0">${data.error}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        resultsDiv.innerHTML = `
            <div class="alert alert-danger">
                <h6><i class="fas fa-exclamation-triangle"></i> خطأ في الاتصال</h6>
                <p class="mb-0">${error.message}</p>
            </div>
        `;
    });
}

function generatePlatform() {
    if (!parsedData) {
        alert('يرجى تحليل YAML أولاً');
        return;
    }
    
    const platformName = document.getElementById('platformName').value;
    const yamlContent = document.getElementById('yamlContent').value;
    const resultsDiv = document.getElementById('results');
    
    resultsDiv.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">جاري توليد المنصة...</p></div>';
    
    fetch('/zero/generate-platform', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            yaml_content: yamlContent,
            platform_name: platformName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultsDiv.innerHTML = `
                <div class="alert alert-success">
                    <h6><i class="fas fa-check-circle"></i> تم توليد المنصة بنجاح!</h6>
                </div>
                <h6>📁 مسار الإخراج:</h6>
                <code class="d-block p-2 bg-light">${data.data.output_path}</code>
                
                <h6 class="mt-3">📊 الملفات المولدة:</h6>
                <p class="text-success">عدد الملفات: ${data.data.files_generated.length}</p>
                
                <h6>📋 خطة التوليد:</h6>
                <pre class="bg-light p-3 small">${JSON.stringify(data.data.generation_plan, null, 2)}</pre>
            `;
        } else {
            resultsDiv.innerHTML = `
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle"></i> فشل في توليد المنصة</h6>
                    <p class="mb-0">${data.error}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        resultsDiv.innerHTML = `
            <div class="alert alert-danger">
                <h6><i class="fas fa-exclamation-triangle"></i> خطأ في الاتصال</h6>
                <p class="mb-0">${error.message}</p>
            </div>
        `;
    });
}
</script>
@endpush
