@extends('layout')

@section('title', 'Reports | TNT HR')

@section('content')
<style>
    .page-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
    .page-header h1 { font-size:20px; font-weight:800; color:#111827; margin:0; }
    .page-header p { color:#6b7280; font-size:12px; margin:2px 0 0; }
    
    .card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden; margin-bottom:20px; }
    .card-header { padding:16px 20px; border-bottom:1px solid #f3f4f6; display:flex; justify-content:space-between; align-items:center; }
    .card-header h3 { font-size:15px; font-weight:700; color:#111827; margin:0; }
    .card-body { padding:20px; }
    
    .form-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px; margin-bottom:16px; }
    .form-group { display:flex; flex-direction:column; gap:4px; }
    .form-group label { font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; }
    .form-group input, .form-group select { padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; }
    .form-group input:focus, .form-group select:focus { outline:none; border-color:#4f46e5; box-shadow:0 0 0 3px rgba(79,70,229,0.1); }
    
    .btn { display:inline-flex; align-items:center; gap:6px; padding:12px 24px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; border:none; text-decoration:none; }
    .btn-primary { background:#4f46e5; color:#fff; }
    .btn-primary:hover { background:#4338ca; }
    .btn-outline { background:#fff; color:#374151; border:1px solid #d1d5db; }
    .btn-outline:hover { background:#f9fafb; }
    
    .alert { padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:13px; }
    .alert-info { background:#eef2ff; color:#4f46e5; border:1px solid #c7d2fe; }
    
    /* Multi-Select Dropdown */
    .multi-select { position:relative; }
    .multi-select-btn { 
        width:100%; padding:10px 40px 10px 14px; border:1px solid #d1d5db; border-radius:8px;
        font-size:13px; text-align:left; cursor:pointer; background:#fff; transition:border-color 0.15s;
        position:relative; min-height:42px;
    }
    .multi-select-btn:hover { border-color:#4f46e5; }
    .multi-select-btn .arrow { position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#9ca3af; font-size:10px; }
    .multi-select-btn .placeholder { color:#9ca3af; }
    .multi-select-btn .count-badge { 
        display:inline-block; background:#4f46e5; color:#fff; padding:2px 8px; border-radius:100px; 
        font-size:11px; font-weight:700; margin-left:8px; 
    }
    
    .multi-drop {
        display:none; position:absolute; top:100%; left:0; right:0; max-height:300px; overflow-y:auto;
        background:#fff; border:2px solid #4f46e5; border-top:none; border-radius:0 0 10px 10px;
        z-index:99; box-shadow:0 10px 25px rgba(0,0,0,0.12);
    }
    .multi-drop.open { display:block; }
    .multi-drop .drop-actions {
        display:flex; gap:6px; padding:8px 12px; border-bottom:1px solid #f3f4f6;
        background:#f9fafb; position:sticky; top:0; z-index:2;
    }
    .multi-drop .drop-actions button {
        padding:5px 12px; border-radius:6px; font-size:10px; font-weight:600;
        cursor:pointer; border:1px solid #d1d5db; background:#fff;
    }
    .multi-drop .drop-actions button:hover { background:#eef2ff; border-color:#4f46e5; }
    .multi-drop label {
        display:flex; align-items:center; gap:10px; padding:10px 14px; cursor:pointer;
        border-bottom:1px solid #f3f4f6; font-size:13px; transition:background 0.1s;
    }
    .multi-drop label:hover { background:#f9fafb; }
    .multi-drop label.checked { background:#eef2ff; }
    .multi-drop input[type="checkbox"] { width:17px; height:17px; accent-color:#4f46e5; cursor:pointer; }
    .multi-drop .pname { flex:1; font-weight:500; }
    .multi-drop .pstatus { font-size:10px; padding:2px 10px; border-radius:100px; font-weight:600; }
    .multi-drop .pstatus.Active { background:#ecfdf5; color:#059669; }
    .multi-drop .pstatus.Completed { background:#f3f4f6; color:#6b7280; }
    .multi-drop::-webkit-scrollbar { width:4px; }
    .multi-drop::-webkit-scrollbar-thumb { background:#d1d5db; border-radius:2px; }
    
    .info-bar { display:flex; gap:10px; align-items:center; padding:10px 14px; background:#f9fafb; border-radius:8px; margin-top:12px; flex-wrap:wrap; }
    .info-bar .info-item { font-size:11px; color:#6b7280; }
    .info-bar .info-item strong { color:#111827; }
    
    .sheet-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:10px; }
    .sheet-card { background:#f9fafb; border-radius:8px; padding:14px; border-left:4px solid #4f46e5; }
    .sheet-card h4 { font-size:13px; font-weight:700; margin:0 0 4px; color:#111827; }
    .sheet-card p { font-size:11px; color:#6b7280; margin:0; }
    
    @media (max-width:768px) { .form-row { grid-template-columns:1fr 1fr; } }
    @media (max-width:480px) { .form-row { grid-template-columns:1fr; } }
</style>

<div class="page-header">
    <div>
        <h1>📊 Excel Report Download</h1>
        <p>Generate professional Excel reports with multiple sheets and formatted styling</p>
    </div>
</div>

@if(session('success'))
<div style="background:#ecfdf5;color:#059669;border:1px solid #a7f3d0;padding:12px 16px;border-radius:8px;margin-bottom:16px;">✅ {{ session('success') }}</div>
@endif
@if(session('error'))
<div style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;padding:12px 16px;border-radius:8px;margin-bottom:16px;">❌ {{ session('error') }}</div>
@endif

<!-- Report Parameters -->
<div class="card">
    <div class="card-header"><h3>📋 Report Parameters</h3></div>
    <div class="card-body">
        <form method="GET" action="{{ route('reports.export') }}" id="reportForm">
            <div class="form-row">
                <div class="form-group">
                    <label>📅 From Date <span style="color:red;">*</span></label>
                    <input type="date" name="date_from" value="{{ $firstDate ?? now()->format('Y-m-d') }}" required>
                </div>
                <div class="form-group">
                    <label>📅 To Date <span style="color:red;">*</span></label>
                    <input type="date" name="date_to" value="{{ $lastDate ?? now()->format('Y-m-d') }}" required>
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
                    <label>📊 Report Package</label>
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
            
            <!-- Multi-Select Projects -->
            <div class="form-group" style="margin-bottom:4px;">
                <label>🏗️ Select Projects</label>
                <div class="multi-select" id="multiSelect">
                    <div class="multi-select-btn" onclick="toggleMultiDrop()">
                        <span id="multiText" class="placeholder">Click to select projects...</span>
                        <span id="multiBadge" class="count-badge" style="display:none;">0</span>
                        <span class="arrow">▼</span>
                    </div>
                    <div class="multi-drop" id="multiDrop">
                        <div class="drop-actions">
                            <button type="button" onclick="selectAllProjects()">✅ All</button>
                            <button type="button" onclick="deselectAllProjects()">❌ None</button>
                            <button type="button" onclick="selectActiveProjects()">🟢 Active</button>
                        </div>
                        @foreach($projects as $project)
                        <label class="proj-label" data-status="{{ $project->status ?? 'Active' }}">
                            <input type="checkbox" name="projects[]" value="{{ $project->id }}" class="proj-cb" onchange="updateMultiText()">
                            <span class="pname">{{ $project->name }}</span>
                            <span class="pstatus {{ $project->status ?? 'Active' }}">{{ $project->status ?? 'Active' }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <div class="info-bar" id="infoBar" style="display:none;">
                <span class="info-item">📁 Selected: <strong id="selCount">0</strong> projects</span>
                <span class="info-item">📅 Period: <strong id="selPeriod">-</strong></span>
            </div>
            
            <!-- BUTTONS -->
            <div style="display:flex; gap:10px; margin-top:16px; flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary" onclick="return validateForm()">
                    🚀 Generate and Download Excel
                </button>
                <a href="{{ route('reports.dashboard') }}" class="btn btn-outline">📊 View Dashboard</a>
                <a href="{{ route('reports.standard') }}" class="btn btn-outline">📋 Standard Report</a>
                <button type="reset" class="btn btn-outline" onclick="setTimeout(updateMultiText,100)">🔄 Clear All</button>
            </div>
        </form>
        
        <div class="alert alert-info" style="margin-top:16px;">
            <strong>ℹ️ Professional Excel Report includes:</strong> Multiple sheets with styled headers, auto-sized columns, number formatting with comma separators, grand totals with highlighted rows, and consistent TNT branding.
        </div>
    </div>
</div>

<!-- Sheet Descriptions -->
<div class="card">
    <div class="card-header"><h3>📖 Excel Sheet Descriptions</h3></div>
    <div class="card-body">
        <div class="sheet-grid">
            <div class="sheet-card" style="border-left-color:#1b7f79;"><h4>📊 Management Dashboard</h4><p>KPI cards with latest headcount and daily breakdown.</p></div>
            <div class="sheet-card" style="border-left-color:#397a9f;"><h4>📅 Daily Full Report</h4><p>Complete daily breakdown by project and department.</p></div>
            <div class="sheet-card" style="border-left-color:#d38b2a;"><h4>📆 Weekly Summary</h4><p>Average headcount per week rounded.</p></div>
            <div class="sheet-card" style="border-left-color:#16324f;"><h4>📊 Monthly Summary</h4><p>Average headcount per month rounded.</p></div>
            <div class="sheet-card" style="border-left-color:#a94343;"><h4>🏗️ Project Summary</h4><p>Total headcount by project with grand total.</p></div>
            <div class="sheet-card" style="border-left-color:#4f46e5;"><h4>🏢 Department Summary</h4><p>Headcount by department and employment type.</p></div>
            <div class="sheet-card" style="border-left-color:#059669;"><h4>💼 Job Title Details</h4><p>Detailed breakdown by job title with codes.</p></div>
            <div class="sheet-card" style="border-left-color:#d97706;"><h4>📄 Report Info</h4><p>Report metadata and generation details.</p></div>
        </div>
    </div>
</div>

<script>
var dropOpen = false;

function toggleMultiDrop() {
    dropOpen = !dropOpen;
    document.getElementById('multiDrop').classList.toggle('open', dropOpen);
}

function updateMultiText() {
    var checked = document.querySelectorAll('.proj-cb:checked');
    var count = checked.length;
    var txt = document.getElementById('multiText');
    var badge = document.getElementById('multiBadge');
    var bar = document.getElementById('infoBar');
    
    if (count === 0) {
        txt.textContent = 'Click to select projects...';
        txt.classList.add('placeholder');
        badge.style.display = 'none';
        bar.style.display = 'none';
    } else if (count <= 3) {
        var names = [];
        checked.forEach(function(cb) { names.push(cb.parentElement.querySelector('.pname').textContent); });
        txt.textContent = names.join(', ');
        txt.classList.remove('placeholder');
        badge.textContent = count;
        badge.style.display = 'inline-block';
        bar.style.display = 'flex';
    } else {
        txt.textContent = count + ' projects selected';
        txt.classList.remove('placeholder');
        badge.textContent = count;
        badge.style.display = 'inline-block';
        bar.style.display = 'flex';
    }
    
    document.getElementById('selCount').textContent = count;
    
    var from = document.querySelector('input[name="date_from"]').value;
    var to = document.querySelector('input[name="date_to"]').value;
    document.getElementById('selPeriod').textContent = from + ' to ' + to;
    
    document.querySelectorAll('.proj-label').forEach(function(l) {
        l.classList.toggle('checked', l.querySelector('.proj-cb').checked);
    });
}

function selectAllProjects() { 
    document.querySelectorAll('.proj-cb').forEach(function(cb){cb.checked=true;}); 
    updateMultiText(); 
}
function deselectAllProjects() { 
    document.querySelectorAll('.proj-cb').forEach(function(cb){cb.checked=false;}); 
    updateMultiText(); 
}
function selectActiveProjects() { 
    document.querySelectorAll('.proj-cb').forEach(function(cb){
        cb.checked = (cb.parentElement.dataset.status === 'Active');
    }); 
    updateMultiText(); 
}

function validateForm() {
    var from = document.querySelector('input[name="date_from"]').value;
    var to = document.querySelector('input[name="date_to"]').value;
    if (!from || !to) {
        alert('Please select both From Date and To Date.');
        return false;
    }
    // Show loading state
    var btn = document.querySelector('.btn-primary');
    btn.textContent = '⏳ Generating...';
    btn.disabled = true;
    setTimeout(function(){ btn.textContent = '🚀 Generate and Download Excel'; btn.disabled = false; }, 3000);
    return true;
}

// Close on outside click
document.addEventListener('click', function(e) {
    var m = document.getElementById('multiSelect');
    if (m && !m.contains(e.target)) { 
        dropOpen = false; 
        document.getElementById('multiDrop').classList.remove('open'); 
    }
});

// Update period on date change
document.querySelectorAll('input[name="date_from"], input[name="date_to"]').forEach(function(el) {
    el.addEventListener('change', function() {
        document.getElementById('selPeriod').textContent = 
            document.querySelector('input[name="date_from"]').value + ' to ' + 
            document.querySelector('input[name="date_to"]').value;
    });
});

// Init
updateMultiText();
</script>
@endsection
