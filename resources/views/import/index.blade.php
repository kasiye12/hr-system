@extends('layouts.app')

@section('title', 'Import Data - TNT HR')

@section('content')
<style>
    /* ============================================
       IMPORT PAGE STYLES
       ============================================ */
    .import-container {
        max-width: 1100px;
        margin: 0 auto;
    }

    .import-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 24px;
    }
    .import-header h1 {
        margin: 0;
        font-size: 26px;
        color: #16324f;
    }
    .import-header .subtitle {
        color: #627386;
        font-size: 14px;
        margin-top: 2px;
    }
    .import-header .badge-version {
        background: #e8f3f2;
        color: #0c625e;
        padding: 4px 14px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
    }

    /* Tabs */
    .import-tabs {
        display: flex;
        gap: 4px;
        background: #f0f4f8;
        border-radius: 10px;
        padding: 4px;
        margin-bottom: 24px;
    }
    .import-tabs .tab-btn {
        padding: 10px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
        background: transparent;
        color: #627386;
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1;
        justify-content: center;
    }
    .import-tabs .tab-btn:hover {
        background: rgba(255,255,255,0.6);
        color: #16324f;
    }
    .import-tabs .tab-btn.active {
        background: #fff;
        color: #16324f;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .import-tabs .tab-btn .tab-icon {
        font-size: 18px;
    }
    .import-tabs .tab-btn .tab-badge {
        background: #1b7f79;
        color: #fff;
        font-size: 10px;
        padding: 1px 8px;
        border-radius: 999px;
        margin-left: 4px;
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }
    .tab-content.active {
        display: block;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Cards */
    .import-card {
        background: #fff;
        border: 1px solid #dce5ee;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(21,49,78,0.05);
    }
    .import-card .card-title {
        font-size: 16px;
        font-weight: 700;
        color: #16324f;
        margin: 0 0 4px 0;
    }
    .import-card .card-subtitle {
        color: #627386;
        font-size: 13px;
        margin-bottom: 16px;
    }

    /* Form */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    .form-grid .field {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .form-grid .field label {
        font-size: 12px;
        color: #627386;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .form-grid .field input,
    .form-grid .field select {
        padding: 10px 14px;
        border: 1px solid #bac8d6;
        border-radius: 6px;
        font-size: 14px;
        background: #fff;
        width: 100%;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-grid .field input:focus,
    .form-grid .field select:focus {
        outline: none;
        border-color: #1b7f79;
        box-shadow: 0 0 0 3px rgba(27, 127, 121, 0.08);
    }
    .form-grid .field .helper-text {
        font-size: 12px;
        color: #627386;
        margin-top: 2px;
    }
    .form-grid .field .helper-text .highlight {
        color: #1b7f79;
        font-weight: 600;
    }

    /* File Upload */
    .upload-zone {
        border: 2px dashed #dce5ee;
        border-radius: 10px;
        padding: 40px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        background: #fafcfd;
        position: relative;
    }
    .upload-zone:hover {
        border-color: #1b7f79;
        background: #f0f7f6;
    }
    .upload-zone.dragover {
        border-color: #1b7f79;
        background: #e8f3f2;
    }
    .upload-zone .upload-icon {
        font-size: 48px;
        color: #1b7f79;
        margin-bottom: 12px;
    }
    .upload-zone .upload-text {
        font-size: 16px;
        font-weight: 600;
        color: #16324f;
    }
    .upload-zone .upload-subtext {
        font-size: 13px;
        color: #627386;
        margin-top: 4px;
    }
    .upload-zone .upload-formats {
        font-size: 12px;
        color: #627386;
        margin-top: 8px;
    }
    .upload-zone .upload-formats .format-badge {
        display: inline-block;
        padding: 2px 10px;
        background: #e9eff5;
        border-radius: 4px;
        margin: 0 4px;
        font-weight: 600;
        font-size: 11px;
        color: #23384d;
    }
    .upload-zone input[type="file"] {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }
    .upload-zone .file-selected {
        display: none;
        margin-top: 12px;
        padding: 12px 16px;
        background: #dcf5e7;
        border-radius: 6px;
        color: #12643c;
        font-weight: 600;
    }
    .upload-zone .file-selected.show {
        display: block;
    }

    /* Requirements */
    .requirements-box {
        background: #f8fbfd;
        border-radius: 8px;
        padding: 16px;
        margin-top: 16px;
        border-left: 4px solid #1b7f79;
    }
    .requirements-box .req-title {
        font-weight: 700;
        font-size: 13px;
        color: #16324f;
        margin-bottom: 6px;
    }
    .requirements-box ul {
        margin: 0;
        padding-left: 20px;
        font-size: 13px;
        color: #627386;
    }
    .requirements-box ul li {
        margin-bottom: 4px;
    }
    .requirements-box .req-error {
        color: #a61b1b;
        font-weight: 600;
    }

    /* Buttons */
    .btn-import {
        padding: 12px 32px;
        background: #1b7f79;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-weight: 700;
        cursor: pointer;
        font-size: 15px;
        transition: background 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-import:hover {
        background: #156b66;
    }
    .btn-import:disabled {
        background: #bac8d6;
        cursor: not-allowed;
    }
    .btn-import .btn-spinner {
        display: none;
    }
    .btn-import.loading .btn-spinner {
        display: inline-block;
        animation: spin 1s linear infinite;
    }
    .btn-import.loading .btn-text {
        display: none;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .btn-secondary {
        padding: 10px 20px;
        background: #e9eff5;
        color: #23384d;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        font-size: 13px;
        transition: background 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-secondary:hover {
        background: #dce5ee;
        text-decoration: none;
        color: #23384d;
    }

    .btn-group {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #dce5ee;
    }

    /* Templates */
    .template-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-top: 12px;
    }
    .template-item {
        background: #f8fbfd;
        border: 1px solid #dce5ee;
        border-radius: 10px;
        padding: 16px 20px;
        transition: all 0.2s;
        text-decoration: none;
        color: #16324f;
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .template-item:hover {
        border-color: #1b7f79;
        background: #f0f7f6;
        text-decoration: none;
        color: #16324f;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }
    .template-item .template-icon {
        font-size: 28px;
    }
    .template-item .template-info {
        flex: 1;
    }
    .template-item .template-info .template-name {
        font-weight: 700;
        font-size: 14px;
    }
    .template-item .template-info .template-desc {
        font-size: 12px;
        color: #627386;
    }
    .template-item .template-download {
        color: #1b7f79;
        font-size: 18px;
    }

    /* Recent Imports */
    .recent-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .recent-table th {
        background: #f0f4f8;
        padding: 10px 14px;
        font-size: 11px;
        text-transform: uppercase;
        color: #627386;
        font-weight: 700;
        border-bottom: 2px solid #dce5ee;
        text-align: left;
    }
    .recent-table td {
        padding: 10px 14px;
        border-bottom: 1px solid #f0f4f8;
        vertical-align: middle;
    }
    .recent-table tr:hover td {
        background: #f8fbfd;
    }
    .badge-import {
        display: inline-block;
        padding: 2px 12px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
    }
    .badge-import.headcount {
        background: #dcf5e7;
        color: #12643c;
    }
    .badge-import.structure {
        background: #dff3ff;
        color: #0d6289;
    }
    .badge-import.success {
        background: #dcf5e7;
        color: #12643c;
    }
    .badge-import.failed {
        background: #ffeded;
        color: #a61b1b;
    }

    /* Status Messages */
    .alert-import {
        padding: 14px 18px;
        border-radius: 8px;
        margin-bottom: 16px;
    }
    .alert-import.success {
        background: #dcf5e7;
        border: 1px solid #b9e3ca;
        color: #12643c;
    }
    .alert-import.error {
        background: #ffeded;
        border: 1px solid #efbbbb;
        color: #a61b1b;
    }
    .alert-import .alert-title {
        font-weight: 700;
        font-size: 14px;
    }
    .alert-import .alert-body {
        font-size: 13px;
        margin-top: 4px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        .import-tabs {
            flex-direction: column;
        }
        .import-tabs .tab-btn {
            justify-content: flex-start;
        }
        .template-grid {
            grid-template-columns: 1fr;
        }
        .import-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        .upload-zone {
            padding: 24px 16px;
        }
        .btn-group {
            flex-direction: column;
        }
        .btn-group .btn-import,
        .btn-group .btn-secondary {
            width: 100%;
            justify-content: center;
        }
    }
    @media (max-width: 480px) {
        .import-card {
            padding: 16px;
        }
        .recent-table {
            font-size: 12px;
        }
        .recent-table th,
        .recent-table td {
            padding: 6px 10px;
        }
    }
</style>

<!-- ============================================ -->
<!-- HEADER -->
<!-- ============================================ -->
<div class="import-container">
    <div class="import-header">
        <div>
            <h1>📥 Import Data</h1>
            <div class="subtitle">Upload Excel files to import headcount data or job title structure</div>
        </div>
        <div>
            <span class="badge-version">📊 v2.0</span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert-import success">
            <div class="alert-title">✅ Success</div>
            <div class="alert-body">{!! session('success') !!}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="alert-import error">
            <div class="alert-title">❌ Error</div>
            <div class="alert-body">{!! session('error') !!}</div>
        </div>
    @endif
    @if($errors->any())
        <div class="alert-import error">
            <div class="alert-title">❌ Validation Error</div>
            <div class="alert-body">
                @foreach($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        </div>
    @endif

    <!-- ============================================ -->
    <!-- TABS -->
    <!-- ============================================ -->
    <div class="import-tabs">
        <button class="tab-btn active" onclick="switchTab('headcount')">
            <span class="tab-icon">📊</span>
            Headcount Data
            <span class="tab-badge">Daily</span>
        </button>
        <button class="tab-btn" onclick="switchTab('structure')">
            <span class="tab-icon">🏗️</span>
            Job Title Structure
            <span class="tab-badge">Setup</span>
        </button>
        <button class="tab-btn" onclick="switchTab('history')">
            <span class="tab-icon">📋</span>
            Import History
        </button>
    </div>

    <!-- ============================================ -->
    <!-- TAB 1: HEADCOUNT -->
    <!-- ============================================ -->
    <div id="tab-headcount" class="tab-content active">
        <form method="POST" action="{{ route('import.upload') }}" enctype="multipart/form-data" id="headcountForm">
            @csrf
            <input type="hidden" name="import_type" value="headcount">

            <div class="import-card">
                <div class="card-title">📊 Import Headcount Data</div>
                <div class="card-subtitle">Upload daily manpower data with dates and job title counts</div>

                <div class="form-grid">
                    <div class="field">
                        <label>📅 Select Project <span style="color:red;">*</span></label>
                        <select name="project_id" required>
                            <option value="">-- Select Project --</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="field">
                        <label>📅 Date Column Name <span style="color:red;">*</span></label>
                        <input type="text" name="date_column" value="Date" placeholder="e.g., Date, Report Date" required>
                        <div class="helper-text">Enter the exact column header name that contains the dates</div>
                    </div>
                </div>

                <div class="upload-zone" id="uploadZoneHeadcount">
                    <div class="upload-icon">📤</div>
                    <div class="upload-text">Drop your file here or click to browse</div>
                    <div class="upload-subtext">Upload Excel or CSV files</div>
                    <div class="upload-formats">
                        <span class="format-badge">.xlsx</span>
                        <span class="format-badge">.xls</span>
                        <span class="format-badge">.csv</span>
                        <span style="color:#a61b1b;">Max 5MB</span>
                    </div>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required onchange="showFileName(this, 'fileInfoHeadcount')">
                    <div class="file-selected" id="fileInfoHeadcount">
                        📎 <span id="fileNameHeadcount"></span>
                    </div>
                </div>

                <div class="requirements-box">
                    <div class="req-title">📌 File Requirements</div>
                    <ul>
                        <li>First column must be <strong>Date</strong> in <strong>YYYY-MM-DD</strong> format</li>
                        <li>Column headers must match <strong>Job Title Codes</strong> or <strong>Names</strong></li>
                        <li>Headcount values must be <strong>whole numbers</strong> (0 or positive)</li>
                        <li>Sample rows are provided for reference</li>
                    </ul>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn-import" id="headcountSubmitBtn">
                        <span class="btn-text">🚀 Import Headcount Data</span>
                        <span class="btn-spinner">⏳</span>
                    </button>
                    <button type="reset" class="btn-secondary">🔄 Clear Form</button>
                </div>
            </div>
        </form>
    </div>

    <!-- ============================================ -->
    <!-- TAB 2: STRUCTURE -->
    <!-- ============================================ -->
    <div id="tab-structure" class="tab-content">
        <form method="POST" action="{{ route('import.upload') }}" enctype="multipart/form-data" id="structureForm">
            @csrf
            <input type="hidden" name="import_type" value="structure">

            <div class="import-card">
                <div class="card-title">🏗️ Import Job Title Structure</div>
                <div class="card-subtitle">Upload job titles, departments, codes, and employment types</div>

                <div class="upload-zone" id="uploadZoneStructure">
                    <div class="upload-icon">📤</div>
                    <div class="upload-text">Drop your file here or click to browse</div>
                    <div class="upload-subtext">Upload Excel or CSV files</div>
                    <div class="upload-formats">
                        <span class="format-badge">.xlsx</span>
                        <span class="format-badge">.xls</span>
                        <span class="format-badge">.csv</span>
                        <span style="color:#a61b1b;">Max 5MB</span>
                    </div>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required onchange="showFileName(this, 'fileInfoStructure')">
                    <div class="file-selected" id="fileInfoStructure">
                        📎 <span id="fileNameStructure"></span>
                    </div>
                </div>

                <div class="requirements-box">
                    <div class="req-title">📌 File Requirements</div>
                    <ul>
                        <li>Columns must include: <strong>Department, Code, Job Title, Employment Type</strong></li>
                        <li>Employment Type must be: <strong>Permanent, Contract, or Daily</strong></li>
                        <li>Code must be <strong>unique</strong> (existing codes will be skipped)</li>
                    </ul>
                    <div class="req-error">⚠️ Existing job titles with the same code will be skipped</div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn-import" id="structureSubmitBtn">
                        <span class="btn-text">🚀 Import Job Titles</span>
                        <span class="btn-spinner">⏳</span>
                    </button>
                    <button type="reset" class="btn-secondary">🔄 Clear Form</button>
                </div>
            </div>
        </form>
    </div>

    <!-- ============================================ -->
    <!-- TAB 3: HISTORY -->
    <!-- ============================================ -->
    <div id="tab-history" class="tab-content">
        <div class="import-card">
            <div class="card-title">📋 Import History</div>
            <div class="card-subtitle">View your recent import activity</div>

            <div style="overflow-x:auto;">
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Type</th>
                            <th>Details</th>
                            <th>Status</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentImports as $import)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($import->logged_at)->format('Y-m-d H:i') }}</td>
                                <td>
                                    <span class="badge-import {{ $import->action == 'import_headcount' ? 'headcount' : 'structure' }}">
                                        {{ $import->action == 'import_headcount' ? '📊 Headcount' : '🏗️ Structure' }}
                                    </span>
                                </td>
                                <td>{{ $import->details ?? '-' }}</td>
                                <td>
                                    <span class="badge-import success">✅ Success</span>
                                </td>
                                <td>{{ $import->username }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align:center; padding:30px; color:#627386;">
                                    <div style="font-size:36px; margin-bottom:8px;">📭</div>
                                    No imports found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- TEMPLATES SECTION -->
    <!-- ============================================ -->
    <div class="import-card">
        <div class="card-title">📄 Download Templates</div>
        <div class="card-subtitle">Download Excel templates to ensure correct format</div>

        <div class="template-grid">
            <a href="{{ route('import.template', ['type' => 'headcount']) }}" class="template-item">
                <span class="template-icon">📊</span>
                <div class="template-info">
                    <div class="template-name">Headcount Template</div>
                    <div class="template-desc">For daily manpower data</div>
                </div>
                <span class="template-download">⬇️</span>
            </a>
            <a href="{{ route('import.template', ['type' => 'structure']) }}" class="template-item">
                <span class="template-icon">🏗️</span>
                <div class="template-info">
                    <div class="template-name">Structure Template</div>
                    <div class="template-desc">For job titles setup</div>
                </div>
                <span class="template-download">⬇️</span>
            </a>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
    // ============================================
    // TAB SWITCHING
    // ============================================
    function switchTab(tabId) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(el => {
            el.classList.remove('active');
        });
        
        // Show selected tab
        const target = document.getElementById('tab-' + tabId);
        if (target) {
            target.classList.add('active');
        }
        
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(el => {
            el.classList.remove('active');
        });
        
        // Find and activate the clicked button
        const buttons = document.querySelectorAll('.tab-btn');
        const tabMap = {
            'headcount': 0,
            'structure': 1,
            'history': 2
        };
        if (tabMap[tabId] !== undefined) {
            buttons[tabMap[tabId]].classList.add('active');
        }
    }

    // ============================================
    // FILE UPLOAD HANDLING
    // ============================================
    function showFileName(input, infoId) {
        const fileInfo = document.getElementById(infoId);
        const fileName = document.getElementById(infoId.replace('fileInfo', 'fileName'));
        
        if (input.files && input.files.length > 0) {
            const file = input.files[0];
            const sizeKB = (file.size / 1024).toFixed(1);
            fileName.textContent = file.name + ' (' + sizeKB + ' KB)';
            fileInfo.classList.add('show');
        } else {
            fileInfo.classList.remove('show');
        }
    }

    // ============================================
    // DRAG AND DROP
    // ============================================
    document.querySelectorAll('.upload-zone').forEach(zone => {
        zone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        zone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const input = this.querySelector('input[type="file"]');
                input.files = files;
                // Trigger change event
                const event = new Event('change', { bubbles: true });
                input.dispatchEvent(event);
            }
        });
    });

    // ============================================
    // FORM SUBMISSION
    // ============================================
    document.getElementById('headcountForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('headcountSubmitBtn');
        btn.classList.add('loading');
        btn.disabled = true;
    });

    document.getElementById('structureForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('structureSubmitBtn');
        btn.classList.add('loading');
        btn.disabled = true;
    });

    // ============================================
    // KEYBOARD SHORTCUTS
    // ============================================
    document.addEventListener('keydown', function(e) {
        // Ctrl+1, Ctrl+2, Ctrl+3 for tabs
        if (e.ctrlKey && e.key >= '1' && e.key <= '3') {
            e.preventDefault();
            const tabs = ['headcount', 'structure', 'history'];
            switchTab(tabs[parseInt(e.key) - 1]);
        }
    });
</script>
@endsection