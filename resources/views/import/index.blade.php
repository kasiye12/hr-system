@extends('layouts.app')

@section('title', 'Import Data - TNT HR')

@section('content')
    <style>
        .import-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .import-card {
            background: #fff;
            border: 1px solid #dce5ee;
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 7px rgba(21,49,78,0.04);
        }
        .import-card h2 {
            margin: 0 0 8px 0;
            font-size: 18px;
            color: #16324f;
        }
        .import-card .subtitle {
            color: #627386;
            font-size: 13px;
            margin-bottom: 16px;
        }
        .import-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .import-form .field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .import-form .field label {
            font-size: 12px;
            color: #627386;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .import-form .field input,
        .import-form .field select {
            padding: 10px 14px;
            border: 1px solid #bac8d6;
            border-radius: 6px;
            font-size: 14px;
            background: #fff;
            width: 100%;
        }
        .import-form .field input:focus,
        .import-form .field select:focus {
            outline: none;
            border-color: #1b7f79;
            box-shadow: 0 0 0 3px rgba(27, 127, 121, 0.1);
        }
        .import-form .field input[type="file"] {
            padding: 8px;
            border: 2px dashed #bac8d6;
            background: #f8fbfd;
            cursor: pointer;
        }
        .import-form .field input[type="file"]:hover {
            border-color: #1b7f79;
            background: #f0f7f6;
        }
        .import-form .field .helper-text {
            font-size: 12px;
            color: #627386;
            margin-top: 4px;
        }
        .btn-import {
            padding: 12px 30px;
            background: #1b7f79;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            font-size: 15px;
            transition: background 0.2s;
        }
        .btn-import:hover {
            background: #156b66;
        }
        .btn-import:disabled {
            background: #bac8d6;
            cursor: not-allowed;
        }
        .btn-template {
            padding: 8px 16px;
            background: #e9eff5;
            color: #23384d;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            transition: background 0.2s;
            display: inline-block;
        }
        .btn-template:hover {
            background: #dce5ee;
            text-decoration: none;
            color: #23384d;
        }
        .template-section {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 8px;
        }
        .requirements {
            background: #f8fbfd;
            border-radius: 8px;
            padding: 16px;
            margin-top: 12px;
        }
        .requirements ul {
            margin: 4px 0 0 20px;
            font-size: 13px;
        }
        .requirements ul li {
            margin-bottom: 4px;
        }
        .requirements .note {
            color: #a61b1b;
            font-weight: 600;
        }
        .file-info {
            background: #f0f7f6;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 13px;
            color: #0c5d58;
            display: none;
        }
        .file-info.show {
            display: block;
        }
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 4px;
            flex-wrap: wrap;
        }
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 400;
            font-size: 14px;
            color: #24384c;
            text-transform: none;
            cursor: pointer;
        }
        .radio-group input[type="radio"] {
            width: 16px;
            height: 16px;
            accent-color: #1b7f79;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 8px;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .btn-group {
                flex-direction: column;
            }
            .btn-group .btn-import,
            .btn-group .btn-template {
                width: 100%;
                text-align: center;
            }
        }
    </style>

    <div class="import-container">
        <h1>📥 Import Data from Excel</h1>
        <div class="subtitle">Upload Excel files to import headcount data or job title structure</div>

        @if(session('success'))
            <div class="alert success">{!! session('success') !!}</div>
        @endif
        @if(session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif

        <!-- Import Type Selection -->
        <div class="import-card">
            <h2>📋 Select Import Type</h2>
            <div class="subtitle">Choose the type of data you want to import</div>
            
            <form method="POST" action="{{ route('import.upload') }}" enctype="multipart/form-data" class="import-form" id="importForm">
                @csrf
                
                <div class="field">
                    <label>Import Type</label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="import_type" value="headcount" checked onchange="toggleImportType()">
                            📊 Headcount Data
                        </label>
                        <label>
                            <input type="radio" name="import_type" value="structure" onchange="toggleImportType()">
                            🏗️ Job Title Structure
                        </label>
                    </div>
                </div>

                <!-- Headcount Fields -->
                <div id="headcountFields">
                    <div class="form-row">
                        <div class="field">
                            <label>Select Project <span style="color:red;">*</span></label>
                            <select name="project_id" required>
                                <option value="">-- Select Project --</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="field">
                            <label>Date Column Name <span style="color:red;">*</span></label>
                            <input type="text" name="date_column" value="Date" placeholder="e.g., Date, Report Date" required>
                            <div class="helper-text">Enter the exact column header name that contains the dates</div>
                        </div>
                    </div>

                    <div class="field">
                        <label>Upload Excel File <span style="color:red;">*</span></label>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required onchange="showFileInfo(this)">
                        <div class="helper-text">Supported formats: .xlsx, .xls, .csv (Max 5MB)</div>
                    </div>
                </div>

                <!-- Structure Fields -->
                <div id="structureFields" style="display:none;">
                    <div class="field">
                        <label>Upload Excel File <span style="color:red;">*</span></label>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required onchange="showFileInfo(this)">
                        <div class="helper-text">Supported formats: .xlsx, .xls, .csv (Max 5MB)</div>
                    </div>
                    
                    <div class="requirements">
                        <strong>📌 File Requirements:</strong>
                        <ul>
                            <li>Columns must include: <strong>Department, Code, Job Title, Employment Type</strong></li>
                            <li>Employment Type must be: <strong>Permanent, Contract, or Daily</strong></li>
                            <li>Code must be unique</li>
                        </ul>
                        <div class="note">⚠️ Existing job titles with the same code will be skipped</div>
                    </div>
                </div>

                <div id="fileInfo" class="file-info"></div>

                <div class="btn-group">
                    <button type="submit" class="btn-import" id="importBtn">
                        🚀 Upload and Import
                    </button>
                    <button type="reset" class="btn-template">Clear Form</button>
                </div>
            </form>
        </div>

        <!-- Templates Section -->
        <div class="import-card">
            <h2>📄 Download Templates</h2>
            <div class="subtitle">Download Excel templates to ensure correct format</div>
            
            <div class="template-section">
                <a href="{{ route('import.template', ['type' => 'headcount']) }}" class="btn-template">
                    📊 Headcount Template
                </a>
                <a href="{{ route('import.template', ['type' => 'structure']) }}" class="btn-template">
                    🏗️ Structure Template
                </a>
            </div>

            <div class="requirements" style="margin-top:12px;">
                <strong>📖 Template Instructions:</strong>
                <ul>
                    <li><strong>Headcount Template:</strong> First column should be "Date", subsequent columns are job title codes/names</li>
                    <li><strong>Structure Template:</strong> Columns: Department, Code, Job Title, Employment Type</li>
                    <li>Use the exact column headers as shown in the template</li>
                    <li>Save your file before uploading</li>
                </ul>
            </div>
        </div>

        <!-- Recent Imports -->
        <div class="import-card">
            <h2>📋 Recent Imports</h2>
            <div class="subtitle">View your recent import activity</div>
            
            <div style="overflow-x:auto;">
                <table style="width:100%; font-size:13px;">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Type</th>
                            <th>Details</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $imports = \App\Models\AuditLog::whereIn('action', ['import_headcount', 'import_structure'])
                                ->orderBy('logged_at', 'desc')
                                ->limit(10)
                                ->get();
                        @endphp
                        @forelse($imports as $import)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($import->logged_at)->format('Y-m-d H:i') }}</td>
                                <td>
                                    <span class="badge" style="background:#e8f3f2; color:#0c625e;">
                                        {{ $import->action == 'import_headcount' ? 'Headcount' : 'Structure' }}
                                    </span>
                                </td>
                                <td>{{ $import->details ?? '-' }}</td>
                                <td>{{ $import->username }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty" style="text-align:center; padding:20px;">
                                    No imports found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleImportType() {
            const type = document.querySelector('input[name="import_type"]:checked').value;
            const headcountFields = document.getElementById('headcountFields');
            const structureFields = document.getElementById('structureFields');
            const importBtn = document.getElementById('importBtn');
            
            if (type === 'headcount') {
                headcountFields.style.display = 'block';
                structureFields.style.display = 'none';
                importBtn.textContent = '🚀 Upload and Import Headcount';
            } else {
                headcountFields.style.display = 'none';
                structureFields.style.display = 'block';
                importBtn.textContent = '🚀 Upload and Import Structure';
            }
        }

        function showFileInfo(input) {
            const fileInfo = document.getElementById('fileInfo');
            if (input.files && input.files.length > 0) {
                const file = input.files[0];
                const sizeKB = (file.size / 1024).toFixed(1);
                fileInfo.innerHTML = `
                    📎 <strong>${file.name}</strong> (${sizeKB} KB)
                `;
                fileInfo.className = 'file-info show';
            } else {
                fileInfo.className = 'file-info';
            }
        }

        // Form validation
        document.getElementById('importForm').addEventListener('submit', function(e) {
            const fileInput = this.querySelector('input[type="file"]');
            if (!fileInput.files || fileInput.files.length === 0) {
                e.preventDefault();
                alert('Please select a file to upload.');
                return false;
            }
            
            const importType = document.querySelector('input[name="import_type"]:checked').value;
            if (importType === 'headcount') {
                const projectSelect = this.querySelector('select[name="project_id"]');
                if (!projectSelect.value) {
                    e.preventDefault();
                    alert('Please select a project.');
                    return false;
                }
                const dateColumn = this.querySelector('input[name="date_column"]');
                if (!dateColumn.value.trim()) {
                    e.preventDefault();
                    alert('Please enter the date column name.');
                    return false;
                }
            }
            
            // Show loading state
            const btn = document.getElementById('importBtn');
            btn.textContent = '⏳ Importing...';
            btn.disabled = true;
        });

        // Set initial state
        document.addEventListener('DOMContentLoaded', function() {
            toggleImportType();
        });
    </script>
@endsection