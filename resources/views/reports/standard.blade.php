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
    .btn-outline { background:#fff; color:#374151; border:1px solid #d1d5db; }
    
    /* Project Selector Button */
    .project-btn {
        padding:9px 14px; border:1px solid #d1d5db; border-radius:7px; font-size:13px;
        cursor:pointer; background:#fff; text-align:left; min-width:250px;
        display:flex; justify-content:space-between; align-items:center; gap:8px;
    }
    .project-btn:hover { border-color:#4f46e5; }
    .project-btn .count { background:#4f46e5; color:#fff; padding:2px 8px; border-radius:100px; font-size:10px; font-weight:700; }
    
    /* MODAL POPUP */
    .modal-overlay { display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:999; justify-content:center; align-items:center; }
    .modal-overlay.show { display:flex; }
    .modal-box { background:#fff; border-radius:14px; width:90%; max-width:600px; max-height:80vh; overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,0.3); display:flex; flex-direction:column; }
    .modal-header { padding:16px 20px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center; background:#f9fafb; }
    .modal-header h4 { margin:0; font-size:16px; font-weight:700; }
    .modal-header .close { font-size:24px; cursor:pointer; color:#6b7280; background:none; border:none; }
    .modal-header .close:hover { color:#111827; }
    .modal-actions { padding:10px 20px; border-bottom:1px solid #e5e7eb; display:flex; gap:8px; background:#fff; }
    .modal-actions button { padding:5px 12px; border-radius:6px; font-size:10px; font-weight:600; cursor:pointer; border:1px solid #d1d5db; background:#fff; }
    .modal-actions button:hover { background:#eef2ff; }
    .modal-body { flex:1; overflow-y:auto; padding:0; }
    .modal-body label { display:flex; align-items:center; gap:10px; padding:12px 20px; cursor:pointer; border-bottom:1px solid #f3f4f6; font-size:13px; }
    .modal-body label:hover { background:#f9fafb; }
    .modal-body label.selected { background:#eef2ff; }
    .modal-body input[type="checkbox"] { width:17px; height:17px; accent-color:#4f46e5; }
    .modal-body .pname { flex:1; font-weight:500; }
    .modal-body .pstatus { font-size:10px; padding:2px 10px; border-radius:100px; font-weight:600; }
    .modal-body .pstatus.Active { background:#ecfdf5; color:#059669; }
    .modal-footer { padding:12px 20px; border-top:1px solid #e5e7eb; display:flex; justify-content:flex-end; gap:8px; background:#f9fafb; }
    .modal-footer button { padding:8px 20px; border-radius:7px; font-weight:600; font-size:12px; cursor:pointer; border:none; }
    .btn-apply { background:#4f46e5; color:#fff; }
    .btn-cancel { background:#fff; color:#374151; border:1px solid #d1d5db; }
    
    .report-type-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:10px; margin-top:12px; }
    .report-type-card { border:1px solid #e5e7eb; border-radius:10px; padding:16px; }
    .report-type-card h4 { font-size:14px; font-weight:700; margin:0 0 4px; }
    .report-type-card p { font-size:11px; color:#6b7280; margin:0; }
    
    @media (max-width:768px) { .form-row { flex-direction:column; } .modal-box { width:95%; } }
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
                <div class="form-group"><label>📅 Report Date</label>
                    <input type="date" name="date" value="{{ request('date', $latestDate ?? now()->format('Y-m-d')) }}" required>
                    <span style="font-size:10px; color:#6b7280;">📅 Data available up to: <strong>{{ $latestDate ?? 'None' }}</strong></span>
                </div>
                
                <!-- PROJECT SELECTOR BUTTON -->
                <div class="form-group">
                    <label>🏗️ Select Projects</label>
                    <div class="project-btn" onclick="openModal()">
                        <span id="projectLabel">Click to select projects...</span>
                        <span class="count" id="projectBadge" style="display:none;">0</span>
                        <span style="color:#9ca3af;">▼</span>
                    </div>
                </div>
            </div>
            
            <div style="display:flex; gap:10px; margin-top:8px;">
                <button type="submit" class="btn btn-primary">📥 Generate Excel Report</button>
                <a href="{{ route('reports.index') }}" class="btn btn-outline">← Back</a>
            </div>
        </form>
    </div>
</div>

<!-- MODAL POPUP FOR PROJECT SELECTION -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal-box">
        <div class="modal-header">
            <h4>🏗️ Select Projects for Report</h4>
            <button class="close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-actions">
            <button type="button" onclick="selectAllProjects()">✅ Select All</button>
            <button type="button" onclick="clearAllProjects()">❌ Clear All</button>
            <span style="font-size:11px; color:#6b7280; margin-left:auto;" id="modalCount">0 selected</span>
        </div>
        <div class="modal-body">
            @foreach($projects as $project)
            <label class="proj-label">
                <input type="checkbox" name="project_ids[]" value="{{ $project->id }}" class="proj-cb" onchange="updateSelection()">
                <span class="pname">{{ $project->name }}</span>
                <span class="pstatus {{ $project->status ?? 'Active' }}">{{ $project->status ?? 'Active' }}</span>
            </label>
            @endforeach
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
            <button class="btn-apply" onclick="applySelection()">✅ Apply Selection</button>
        </div>
    </div>
</div>

<!-- Report Type Info -->
<div class="card">
    <div class="card-header"><h3>📖 Report Details</h3></div>
    <div class="card-body">
        <div class="report-type-grid">
            <div class="report-type-card">
                <h4>📊 Cross-Tabulation</h4>
                <p>Job titles (rows) × Projects (columns) with department grouping and subtotals</p>
            </div>
            <div class="report-type-card">
                <h4>🏢 Department Groups</h4>
                <p>Engineering, Equipment, Administration, Finance, Purchasing, Technical Audit</p>
            </div>
            <div class="report-type-card">
                <h4>👔 Employment Types</h4>
                <p>Permanent Staff, Contract Employees, Skilled & Unskilled Labourers breakdown</p>
            </div>
            <div class="report-type-card">
                <h4>📐 Grand Totals</h4>
                <p>Per-project totals and overall grand total with professional formatting</p>
            </div>
        </div>
    </div>
</div>

<script>
function openModal() { document.getElementById('modalOverlay').classList.add('show'); updateModalCount(); }
function closeModal() { document.getElementById('modalOverlay').classList.remove('show'); }
function selectAllProjects() { document.querySelectorAll('.proj-cb').forEach(cb => cb.checked = true); updateSelection(); }
function clearAllProjects() { document.querySelectorAll('.proj-cb').forEach(cb => cb.checked = false); updateSelection(); }

function updateSelection() {
    document.querySelectorAll('.proj-label').forEach(l => l.classList.toggle('selected', l.querySelector('.proj-cb').checked));
    updateModalCount();
}
function updateModalCount() {
    var c = document.querySelectorAll('.proj-cb:checked').length;
    document.getElementById('modalCount').textContent = c + ' selected';
}

function applySelection() {
    var checked = document.querySelectorAll('.proj-cb:checked');
    var label = document.getElementById('projectLabel');
    var badge = document.getElementById('projectBadge');
    
    if (checked.length === 0) {
        label.textContent = 'Click to select projects...';
        badge.style.display = 'none';
    } else if (checked.length <= 3) {
        var names = [];
        checked.forEach(cb => names.push(cb.parentElement.querySelector('.pname').textContent));
        label.textContent = names.join(', ');
        badge.textContent = checked.length;
        badge.style.display = 'inline-block';
    } else {
        label.textContent = checked.length + ' projects selected';
        badge.textContent = checked.length;
        badge.style.display = 'inline-block';
    }
    closeModal();
}

// Init
updateSelection();
</script>
@endsection
