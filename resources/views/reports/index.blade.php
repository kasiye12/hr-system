@extends('layout')

@section('title', 'Reports | TNT HR')

@section('content')
<style>
    .page-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:24px; }
    .page-header h1 { font-size:22px; font-weight:800; color:#111827; margin:0; }
    .page-header p { color:#6b7280; font-size:12px; margin:2px 0 0; }
    
    .card { background:#fff; border-radius:14px; border:1px solid #e5e7eb; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden; margin-bottom:20px; }
    .card-header { padding:18px 24px; border-bottom:1px solid #f3f4f6; display:flex; justify-content:space-between; align-items:center; }
    .card-header h3 { font-size:16px; font-weight:700; color:#111827; margin:0; }
    .card-body { padding:24px; }
    
    .form-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px; margin-bottom:20px; }
    .form-group { display:flex; flex-direction:column; gap:5px; }
    .form-group label { font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; }
    .form-group input, .form-group select { padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; transition:all 0.15s; }
    .form-group input:focus, .form-group select:focus { outline:none; border-color:#4f46e5; box-shadow:0 0 0 3px rgba(79,70,229,0.1); }
    
    .btn { display:inline-flex; align-items:center; gap:6px; padding:10px 20px; border-radius:8px; font-weight:600; font-size:13px; cursor:pointer; border:none; text-decoration:none; transition:all 0.15s; }
    .btn-primary { background:#4f46e5; color:#fff; }
    .btn-primary:hover { background:#4338ca; }
    .btn-success { background:#059669; color:#fff; }
    .btn-outline { background:#fff; color:#374151; border:1px solid #d1d5db; }
    .btn-outline:hover { background:#f9fafb; }
    
    /* Report Cards Grid */
    .report-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px; }
    .report-card {
        background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px 24px;
        cursor:pointer; transition:all 0.2s; position:relative; overflow:hidden;
        text-decoration:none; display:block;
    }
    .report-card:hover { border-color:#4f46e5; box-shadow:0 8px 25px rgba(79,70,229,0.1); transform:translateY(-2px); }
    .report-card::before { content:''; position:absolute; top:0; left:0; right:0; height:4px; }
    .report-card:nth-child(1)::before { background:#4f46e5; }
    .report-card:nth-child(2)::before { background:#059669; }
    .report-card:nth-child(3)::before { background:#d97706; }
    .report-card:nth-child(4)::before { background:#dc2626; }
    .report-card:nth-child(5)::before { background:#7c3aed; }
    .report-card:nth-child(6)::before { background:#0891b2; }
    .report-card .report-icon { font-size:32px; margin-bottom:10px; }
    .report-card h4 { font-size:15px; font-weight:700; color:#111827; margin:0 0 6px; }
    .report-card p { font-size:12px; color:#6b7280; margin:0; line-height:1.5; }
    .report-card .badge-new { position:absolute; top:12px; right:12px; background:#4f46e5; color:#fff; font-size:9px; padding:3px 8px; border-radius:100px; font-weight:700; }
    
    /* Multi-Select */
    .multi-select { position:relative; }
    .multi-select-btn { width:100%; padding:10px 40px 10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; cursor:pointer; background:#fff; text-align:left; position:relative; }
    .multi-select-btn:hover { border-color:#4f46e5; }
    .multi-select-btn .arrow { position:absolute; right:12px; top:50%; transform:translateY(-50%); color:#9ca3af; }
    .multi-select-btn .badge-count { background:#4f46e5; color:#fff; padding:2px 8px; border-radius:100px; font-size:10px; font-weight:700; margin-left:6px; }
    .multi-drop { display:none; position:absolute; top:100%; left:0; right:0; max-height:280px; overflow-y:auto; background:#fff; border:2px solid #4f46e5; border-top:none; border-radius:0 0 10px 10px; z-index:99; box-shadow:0 10px 25px rgba(0,0,0,0.12); }
    .multi-drop.open { display:block; }
    .multi-drop .drop-actions { display:flex; gap:6px; padding:8px 12px; border-bottom:1px solid #f3f4f6; background:#f9fafb; position:sticky; top:0; }
    .multi-drop .drop-actions button { padding:5px 12px; border-radius:6px; font-size:10px; font-weight:600; cursor:pointer; border:1px solid #d1d5db; background:#fff; }
    .multi-drop .drop-actions button:hover { background:#eef2ff; }
    .multi-drop label { display:flex; align-items:center; gap:10px; padding:10px 14px; cursor:pointer; border-bottom:1px solid #f3f4f6; font-size:13px; }
    .multi-drop label:hover { background:#f9fafb; }
    .multi-drop label.checked { background:#eef2ff; }
    .multi-drop input[type="checkbox"] { width:17px; height:17px; accent-color:#4f46e5; }
    .multi-drop .pname { flex:1; }
    
    .info-bar { display:flex; gap:12px; align-items:center; padding:12px 16px; background:#f9fafb; border-radius:8px; margin-top:16px; flex-wrap:wrap; }
    .info-bar span { font-size:11px; color:#6b7280; }
    .info-bar strong { color:#111827; }
    
    @media (max-width:768px) { .form-row { grid-template-columns:1fr 1fr; } }
    @media (max-width:480px) { .form-row { grid-template-columns:1fr; } .report-grid { grid-template-columns:1fr; } }
</style>

<div class="page-header">
    <div>
        <h1>📊 HR Reports</h1>
        <p>Generate professional Excel reports with multiple sheets</p>
    </div>
</div>

<!-- QUICK REPORT CARDS -->
<div class="report-grid" style="margin-bottom:24px;">
    <a href="{{ route('reports.standard') }}" class="report-card">
        <div class="report-icon">📋</div>
        <h4>Standard HR Report</h4>
        <p>Cross-tabulation by department & job title across multiple projects with totals</p>
    </a>
    <a href="#" onclick="setScope('complete'); document.getElementById('reportForm').submit(); return false;" class="report-card">
        <div class="report-icon">📚</div>
        <h4>Complete Report Workbook</h4>
        <p>All sheets: Dashboard, Daily, Weekly, Monthly, Project & Department summaries</p>
    </a>
    <a href="#" onclick="setScope('daily'); document.getElementById('reportForm').submit(); return false;" class="report-card">
        <div class="report-icon">📅</div>
        <h4>Daily Report Only</h4>
        <p>Full daily breakdown by project, department, and job title</p>
    </a>
    <a href="#" onclick="setScope('weekly'); document.getElementById('reportForm').submit(); return false;" class="report-card">
        <div class="report-icon">📆</div>
        <h4>Weekly Summary</h4>
        <p>Average headcount per week rounded to whole employees</p>
    </a>
    <a href="#" onclick="setScope('monthly'); document.getElementById('reportForm').submit(); return false;" class="report-card">
        <div class="report-icon">📊</div>
        <h4>Monthly Summary</h4>
        <p>Average headcount per month rounded to whole employees</p>
    </a>
    <a href="#" onclick="setScope('management'); document.getElementById('reportForm').submit(); return false;" class="report-card">
        <div class="report-icon">📈</div>
        <h4>Management Summary Only</h4>
        <p>KPI dashboard with latest headcount and trend data</p>
    </a>
</div>

<!-- REPORT PARAMETERS -->
<div class="card">
    <div class="card-header"><h3>⚙️ Report Parameters</h3></div>
    <div class="card-body">
        <form method="GET" action="{{ route('reports.export') }}" id="reportForm">
            <div class="form-row">
                <div class="form-group">
                    <label>📅 From Date</label>
                    <input type="date" name="date_from" value="{{ request('date_from', $firstDate ?? now()->subDays(30)->format('Y-m-d')) }}" required>
                </div>
                <div class="form-group">
                    <label>📅 To Date</label>
                    <input type="date" name="date_to" value="{{ request('date_to', $lastDate ?? now()->format('Y-m-d')) }}" required>
                </div>
                <div class="form-group">
                    <label>👔 Employment Type</label>
                    <select name="employment_type">
                        <option value="All">All Types</option>
                        <option value="Permanent">Permanent</option>
                        <option value="Contract">Contract</option>
                        <option value="Daily">Daily</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>📊 Report Scope</label>
                    <select name="scope" id="scopeSelect">
                        <option value="complete">📚 Complete Report Workbook</option>
                        <option value="daily">📅 Daily Report Only</option>
                        <option value="weekly">📆 Weekly Summary</option>
                        <option value="monthly">📊 Monthly Summary</option>
                        <option value="management">📈 Management Summary Only</option>
                        <option value="job_titles">💼 Job Title Details</option>
                    </select>
                </div>
            </div>
            
            <!-- Multi-Select Projects -->
            <div class="form-group" style="margin-bottom:16px;">
                <label>🏗️ Select Projects</label>
                <div class="multi-select" id="multiSelect">
                    <div class="multi-select-btn" onclick="toggleDrop()">
                        <span id="multiText">Click to select projects...</span>
                        <span class="badge-count" id="multiBadge" style="display:none;">0</span>
                        <span class="arrow">▼</span>
                    </div>
                    <div class="multi-drop" id="multiDrop">
                        <div class="drop-actions">
                            <button type="button" onclick="selectAllProjects()">✅ Select All</button>
                            <button type="button" onclick="clearAllProjects()">❌ Clear All</button>
                        </div>
                        @foreach($projects as $project)
                        <label class="proj-label">
                            <input type="checkbox" name="projects[]" value="{{ $project->id }}" class="proj-cb" onchange="updateMultiText()">
                            <span class="pname">{{ $project->name }}</span>
                            <span style="font-size:10px; color:#6b7280;">{{ $project->status ?? 'Active' }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <div class="info-bar" id="infoBar" style="display:none;">
                <span>📁 Selected: <strong id="selCount">0</strong> projects</span>
                <span>📅 Period: <strong id="selPeriod">-</strong></span>
                <span>📊 Type: <strong id="selType">-</strong></span>
            </div>
            
            <div style="display:flex; gap:10px; margin-top:12px; flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary">📥 Generate & Download Excel</button>
                <a href="{{ route('reports.dashboard') }}" class="btn btn-outline">📊 View Dashboard</a>
                <a href="{{ route('reports.standard') }}" class="btn btn-outline">📋 Standard Report</a>
                <button type="reset" class="btn btn-outline" onclick="setTimeout(updateMultiText,100)">🔄 Reset</button>
            </div>
        </form>
    </div>
</div>

<script>
var dropOpen = false;
function toggleDrop() { dropOpen = !dropOpen; document.getElementById('multiDrop').classList.toggle('open', dropOpen); }

function updateMultiText() {
    var checked = document.querySelectorAll('.proj-cb:checked');
    var txt = document.getElementById('multiText');
    var badge = document.getElementById('multiBadge');
    var bar = document.getElementById('infoBar');
    
    if (checked.length === 0) {
        txt.textContent = 'Click to select projects...';
        badge.style.display = 'none'; bar.style.display = 'none';
    } else if (checked.length <= 3) {
        var names = []; checked.forEach(cb => names.push(cb.parentElement.querySelector('.pname').textContent));
        txt.textContent = names.join(', '); badge.textContent = checked.length; badge.style.display = 'inline-block'; bar.style.display = 'flex';
    } else {
        txt.textContent = checked.length + ' projects selected'; badge.textContent = checked.length; badge.style.display = 'inline-block'; bar.style.display = 'flex';
    }
    document.getElementById('selCount').textContent = checked.length;
    document.getElementById('selPeriod').textContent = document.querySelector('input[name="date_from"]').value + ' to ' + document.querySelector('input[name="date_to"]').value;
    document.getElementById('selType').textContent = document.getElementById('scopeSelect').options[document.getElementById('scopeSelect').selectedIndex].text;
    document.querySelectorAll('.proj-label').forEach(l => l.classList.toggle('checked', l.querySelector('.proj-cb').checked));
}

function selectAllProjects() { document.querySelectorAll('.proj-cb').forEach(cb => cb.checked = true); updateMultiText(); }
function clearAllProjects() { document.querySelectorAll('.proj-cb').forEach(cb => cb.checked = false); updateMultiText(); }
function setScope(scope) { document.getElementById('scopeSelect').value = scope; }

document.addEventListener('click', function(e) { var m = document.getElementById('multiSelect'); if (m && !m.contains(e.target)) { dropOpen = false; document.getElementById('multiDrop').classList.remove('open'); } });
document.querySelectorAll('input[name="date_from"], input[name="date_to"]').forEach(el => el.addEventListener('change', function() { document.getElementById('selPeriod').textContent = document.querySelector('input[name="date_from"]').value + ' to ' + document.querySelector('input[name="date_to"]').value; }));
document.getElementById('scopeSelect').addEventListener('change', function() { document.getElementById('selType').textContent = this.options[this.selectedIndex].text; });

updateMultiText();
</script>
@endsection
