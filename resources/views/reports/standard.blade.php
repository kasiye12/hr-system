@extends('layout')

@section('title', 'Standard HR Report | TNT HR')

@section('content')
<style>
    .page-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
    .page-header h1 { font-size:20px; font-weight:800; color:#111827; margin:0; }
    .page-header p { color:#6b7280; font-size:12px; margin:2px 0 0; }
    
    .card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden; margin-bottom:20px; }
    .card-header { padding:16px 20px; border-bottom:1px solid #f3f4f6; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; }
    .card-header h3 { font-size:15px; font-weight:700; color:#111827; margin:0; }
    .card-body { padding:20px; }
    
    .form-row { display:flex; gap:10px; flex-wrap:wrap; align-items:end; margin-bottom:16px; }
    .form-group { display:flex; flex-direction:column; gap:4px; }
    .form-group label { font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; }
    .form-group input, .form-group select { padding:9px 12px; border:1px solid #d1d5db; border-radius:7px; font-size:13px; }
    
    .btn { display:inline-flex; align-items:center; gap:5px; padding:10px 20px; border-radius:7px; font-weight:600; font-size:13px; cursor:pointer; border:none; text-decoration:none; }
    .btn-primary { background:#4f46e5; color:#fff; }
    .btn-primary:hover { background:#4338ca; }
    .btn-success { background:#059669; color:#fff; }
    .btn-outline { background:#fff; color:#374151; border:1px solid #d1d5db; }
    
    /* Multi-Select Dropdown */
    .multi-select { position:relative; min-width:250px; }
    .multi-select-btn { width:100%; padding:9px 40px 9px 14px; border:1px solid #d1d5db; border-radius:7px; font-size:13px; cursor:pointer; background:#fff; text-align:left; position:relative; }
    .multi-select-btn:hover { border-color:#4f46e5; }
    .multi-select-btn .arrow { position:absolute; right:12px; top:50%; transform:translateY(-50%); color:#9ca3af; }
    .multi-select-btn .count { background:#4f46e5; color:#fff; padding:2px 8px; border-radius:100px; font-size:10px; font-weight:700; margin-left:6px; }
    .multi-drop { display:none; position:absolute; top:100%; left:0; right:0; max-height:300px; overflow-y:auto; background:#fff; border:2px solid #4f46e5; border-top:none; border-radius:0 0 10px 10px; z-index:99; box-shadow:0 10px 25px rgba(0,0,0,0.12); min-width:350px; }
    .multi-drop.open { display:block; }
    .multi-drop .drop-actions { display:flex; gap:6px; padding:8px 12px; border-bottom:1px solid #f3f4f6; background:#f9fafb; position:sticky; top:0; }
    .multi-drop .drop-actions button { padding:5px 12px; border-radius:6px; font-size:10px; font-weight:600; cursor:pointer; border:1px solid #d1d5db; background:#fff; }
    .multi-drop .drop-actions button:hover { background:#eef2ff; }
    .multi-drop label { display:flex; align-items:center; gap:10px; padding:10px 14px; cursor:pointer; border-bottom:1px solid #f3f4f6; font-size:13px; }
    .multi-drop label:hover { background:#f9fafb; }
    .multi-drop label.checked { background:#eef2ff; }
    .multi-drop input[type="checkbox"] { width:17px; height:17px; accent-color:#4f46e5; }
    .multi-drop .pname { flex:1; }
    .multi-drop .pstatus { font-size:10px; padding:2px 10px; border-radius:100px; font-weight:600; }
    .multi-drop .pstatus.Active { background:#ecfdf5; color:#059669; }
    
    .report-type-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:10px; margin-top:12px; }
    .report-type-card { border:1px solid #e5e7eb; border-radius:10px; padding:16px; cursor:pointer; transition:all 0.15s; }
    .report-type-card:hover { border-color:#4f46e5; box-shadow:0 4px 12px rgba(79,70,229,0.1); }
    .report-type-card.selected { border-color:#4f46e5; background:#eef2ff; }
    .report-type-card h4 { font-size:14px; font-weight:700; margin:0 0 4px; }
    .report-type-card p { font-size:11px; color:#6b7280; margin:0; }
    
    @media (max-width:768px) { .form-row { flex-direction:column; } }
</style>

<div class="page-header">
    <div>
        <h1>📊 Standard HR Report</h1>
        <p>Multi-project cross-tabulation report by department and job title</p>
    </div>
</div>

<!-- Report Parameters -->
<div class="card">
    <div class="card-header"><h3>📋 Report Parameters</h3></div>
    <div class="card-body">
        <form method="GET" action="{{ route('reports.standard.export') }}" id="reportForm">
            <div class="form-row">
                <div class="form-group"><label>📅 Report Date</label><input type="date" name="date" value="{{ request('date', $latestDate ?? now()->format('Y-m-d')) }}" required></div>
                
                <!-- Multi-Select Projects -->
                <div class="form-group">
                    <label>🏗️ Select Projects</label>
                    <div class="multi-select" id="multiSelect">
                        <div class="multi-select-btn" onclick="toggleDrop()">
                            <span id="multiText">Click to select projects...</span>
                            <span class="count" id="multiBadge" style="display:none;">0</span>
                            <span class="arrow">▼</span>
                        </div>
                        <div class="multi-drop" id="multiDrop">
                            <div class="drop-actions">
                                <button type="button" onclick="selectAll()">✅ All</button>
                                <button type="button" onclick="clearAll()">❌ None</button>
                            </div>
                            @foreach($projects as $project)
                            <label class="proj-label">
                                <input type="checkbox" name="project_ids[]" value="{{ $project->id }}" class="proj-cb" onchange="updateText()">
                                <span class="pname">{{ $project->name }}</span>
                                <span class="pstatus {{ $project->status ?? 'Active' }}">{{ $project->status ?? 'Active' }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                <div class="form-group"><label>📊 Report Type</label>
                    <select name="report_type" style="min-width:160px;">
                        <option value="standard">Standard HR Report</option>
                        <option value="summary">Management Summary</option>
                        <option value="department">Department Summary</option>
                        <option value="daily">Daily Report</option>
                        <option value="weekly">Weekly Average</option>
                        <option value="monthly">Monthly Average</option>
                    </select>
                </div>
            </div>
            
            <div style="display:flex; gap:10px; margin-top:12px;">
                <button type="submit" class="btn btn-primary">📥 Generate Excel Report</button>
                <a href="{{ route('reports.index') }}" class="btn btn-outline">← Back to Reports</a>
            </div>
        </form>
    </div>
</div>

<!-- Report Type Descriptions -->
<div class="card">
    <div class="card-header"><h3>📖 Available Report Types</h3></div>
    <div class="card-body">
        <div class="report-type-grid">
            <div class="report-type-card">
                <h4>📊 Standard HR Report</h4>
                <p>Cross-tabulation by department & job title across selected projects with totals</p>
            </div>
            <div class="report-type-card">
                <h4>📈 Management Summary</h4>
                <p>KPI dashboard with permanent/contract/daily breakdown and project comparison</p>
            </div>
            <div class="report-type-card">
                <h4>🏢 Department Summary</h4>
                <p>Headcount aggregated by department with employment type breakdown</p>
            </div>
            <div class="report-type-card">
                <h4>📅 Daily Report</h4>
                <p>Single day snapshot with all job titles and headcounts per project</p>
            </div>
            <div class="report-type-card">
                <h4>📆 Weekly Average</h4>
                <p>7-day rolling average headcount per project (rounded)</p>
            </div>
            <div class="report-type-card">
                <h4>📊 Monthly Average</h4>
                <p>30-day rolling average headcount per project (rounded)</p>
            </div>
        </div>
    </div>
</div>

<script>
var dropOpen = false;
function toggleDrop() { dropOpen = !dropOpen; document.getElementById('multiDrop').classList.toggle('open', dropOpen); }
function updateText() {
    var checked = document.querySelectorAll('.proj-cb:checked');
    var txt = document.getElementById('multiText');
    var badge = document.getElementById('multiBadge');
    if (checked.length === 0) { txt.textContent = 'Click to select projects...'; badge.style.display = 'none'; }
    else if (checked.length <= 3) { var names=[]; checked.forEach(cb=>names.push(cb.parentElement.querySelector('.pname').textContent)); txt.textContent=names.join(', '); badge.textContent=checked.length; badge.style.display='inline-block'; }
    else { txt.textContent = checked.length + ' projects selected'; badge.textContent=checked.length; badge.style.display='inline-block'; }
    document.querySelectorAll('.proj-label').forEach(l=>l.classList.toggle('checked',l.querySelector('.proj-cb').checked));
}
function selectAll() { document.querySelectorAll('.proj-cb').forEach(cb=>cb.checked=true); updateText(); }
function clearAll() { document.querySelectorAll('.proj-cb').forEach(cb=>cb.checked=false); updateText(); }
document.addEventListener('click',function(e){var m=document.getElementById('multiSelect');if(m&&!m.contains(e.target)){dropOpen=false;document.getElementById('multiDrop').classList.remove('open');}});
updateText();
</script>
@endsection
