@extends('layouts.app')

@section('title', 'Reports - TNT HR')

@section('content')
    <h1>📊 Excel Report Download</h1>
    <div class="subtitle">Generate professional Excel reports with multiple sheets and formatted styling.</div>

    @if(session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert error">{{ session('error') }}</div>
    @endif

    <div class="card" style="max-width:1100px;">
        <h2>Report Parameters</h2>
        <div class="subtitle">Select the parameters for your report. The Excel file will include multiple professionally formatted sheets.</div>
        
        <form method="get" action="{{ route('reports.export') }}" class="report-form">
            <input type="hidden" name="generate" value="1">
            
            <div class="form-grid">
                <div class="field">
                    <label>From Date <span style="color:red;">*</span></label>
                    <input type="date" name="date_from" value="{{ $firstDate }}" required>
                </div>
                
                <div class="field">
                    <label>To Date <span style="color:red;">*</span></label>
                    <input type="date" name="date_to" value="{{ $lastDate }}" required>
                </div>
                
                <div class="field">
                    <label>Project</label>
                    <select name="project">
                        <option value="0">All Projects</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }} ({{ $project->status }})</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="field">
                    <label>Employment Status</label>
                    <select name="employment_type">
                        <option value="All">All</option>
                        <option value="Permanent">Permanent</option>
                        <option value="Contract">Contract</option>
                        <option value="Daily">Daily</option>
                    </select>
                </div>
                
                <div class="field" style="grid-column: span 2;">
                    <label>Report Package</label>
                    <select name="scope">
                        <option value="complete">📋 Complete Report (All Sheets)</option>
                        <option value="daily">📅 Daily Full Report</option>
                        <option value="weekly">📆 Weekly Summary</option>
                        <option value="monthly">📊 Monthly Summary</option>
                        <option value="management">📈 Management Dashboard</option>
                        <option value="job_titles">💼 Job Title Details</option>
                    </select>
                </div>
            </div>
            
            <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap;">
                <button type="submit" style="background:#1b7f79; padding:12px 30px; font-size:16px;">
                    🚀 Generate and Download Excel
                </button>
                <a class="btn light" href="{{ route('reports.dashboard') }}">📊 View Dashboard</a>
                <button type="reset" class="btn light">Clear All</button>
            </div>
        </form>
        
        <div class="alert warning" style="margin-top:18px;">
            <strong>ℹ️ Note:</strong> The report includes professional Excel formatting with:
            <ul style="margin-top:8px; padding-left:20px;">
                <li>Multiple sheets with styled headers and colors</li>
                <li>Auto-sized columns for better readability</li>
                <li>Number formatting with comma separators</li>
                <li>Grand totals with highlighted rows</li>
                <li>Consistent TNT branding</li>
            </ul>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3>📖 Excel Sheet Descriptions</h3>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap:12px; margin-top:12px;">
            <div style="background:#f8fbfd; padding:12px; border-radius:8px; border-left:4px solid #1b7f79;">
                <strong>📊 Management Dashboard</strong>
                <div class="small">KPI cards with latest headcount and daily breakdown table.</div>
            </div>
            <div style="background:#f8fbfd; padding:12px; border-radius:8px; border-left:4px solid #397a9f;">
                <strong>📅 Daily Full Report</strong>
                <div class="small">Complete daily breakdown by project, department, and job title.</div>
            </div>
            <div style="background:#f8fbfd; padding:12px; border-radius:8px; border-left:4px solid #d38b2a;">
                <strong>📆 Weekly Summary</strong>
                <div class="small">Average headcount per week rounded to whole employees.</div>
            </div>
            <div style="background:#f8fbfd; padding:12px; border-radius:8px; border-left:4px solid #16324f;">
                <strong>📊 Monthly Summary</strong>
                <div class="small">Average headcount per month rounded to whole employees.</div>
            </div>
            <div style="background:#f8fbfd; padding:12px; border-radius:8px; border-left:4px solid #a94343;">
                <strong>🏗️ Project Summary</strong>
                <div class="small">Total headcount by project with grand total.</div>
            </div>
            <div style="background:#f8fbfd; padding:12px; border-radius:8px; border-left:4px solid #1b7f79;">
                <strong>🏢 Department Summary</strong>
                <div class="small">Headcount by department and employment type.</div>
            </div>
            <div style="background:#f8fbfd; padding:12px; border-radius:8px; border-left:4px solid #397a9f;">
                <strong>📄 Report Info</strong>
                <div class="small">Report metadata, generation details, and statistics.</div>
            </div>
        </div>
    </div>

    <style>
        .report-form {
            max-width: 100%;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            align-items: end;
        }
        .form-grid .field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .form-grid .field label {
            font-size: 12px;
            color: var(--muted);
            font-weight: 700;
        }
        .form-grid .field input,
        .form-grid .field select {
            width: 100%;
            padding: 9px 10px;
            border: 1px solid #bac8d6;
            border-radius: 6px;
            background: #fff;
            font-size: 13px;
            min-width: 0;
        }
        .form-grid .field input:focus,
        .form-grid .field select:focus {
            outline: none;
            border-color: #1b7f79;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 480px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-grid .field[style*="grid-column"] {
                grid-column: auto !important;
            }
        }
    </style>
@endsection