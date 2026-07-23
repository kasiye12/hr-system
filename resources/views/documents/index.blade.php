@extends('layout')

@section('title', 'Employee Documents | TNT HR')

@section('content')
<style>
    .page-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
    .page-header h1 { font-size:20px; font-weight:800; color:#111827; margin:0; }
    .page-header p { color:#6b7280; font-size:12px; margin:2px 0 0; }
    
    .card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden; margin-bottom:16px; }
    .card-header { padding:14px 20px; border-bottom:1px solid #f3f4f6; }
    .card-header h3 { font-size:14px; font-weight:700; color:#111827; margin:0; }
    .card-body { padding:20px; }
    
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .form-group { display:flex; flex-direction:column; gap:4px; }
    .form-group label { font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; }
    .form-group input, .form-group select { padding:9px 12px; border:1px solid #d1d5db; border-radius:7px; font-size:13px; }
    .form-group input:focus, .form-group select:focus { outline:none; border-color:#4f46e5; box-shadow:0 0 0 2px rgba(79,70,229,0.1); }
    
    .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:10px; margin-bottom:16px; }
    .stat-card { background:#f9fafb; border-radius:10px; padding:14px; text-align:center; border:1px solid #e5e7eb; }
    .stat-card .val { font-size:22px; font-weight:800; color:#111827; }
    .stat-card .lbl { font-size:10px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-top:2px; }
    
    /* EMPLOYEE SEARCH DROPDOWN */
    .search-box { position:relative; }
    .search-box input { width:100%!important; padding:9px 35px 9px 12px!important; border:1px solid #d1d5db!important; border-radius:7px!important; font-size:13px!important; }
    .search-box input:focus { border-color:#4f46e5!important; outline:none; }
    .search-box .sicon { position:absolute; right:10px; top:50%; transform:translateY(-50%); color:#9ca3af; }
    .search-drop { display:none; position:absolute; top:100%; left:0; right:0; max-height:250px; overflow-y:auto; background:#fff; border:1px solid #4f46e5; border-top:none; border-radius:0 0 8px 8px; z-index:999; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
    .search-drop.open { display:block; }
    .search-drop .opt { padding:9px 14px; cursor:pointer; border-bottom:1px solid #f3f4f6; font-size:13px; }
    .search-drop .opt:hover { background:#eef2ff; }
    .search-drop .opt.clear { color:#9ca3af; font-style:italic; font-weight:600; background:#f9fafb; position:sticky; top:0; }
    .search-drop .opt.selected { background:#d4edda; border-left:3px solid #059669; }
    .highlight { background:#fff3cd; padding:1px 2px; border-radius:2px; }
    .search-drop::-webkit-scrollbar { width:4px; }
    .search-drop::-webkit-scrollbar-thumb { background:#d1d5db; border-radius:2px; }
    
    .filter-bar { display:flex; gap:8px; flex-wrap:wrap; align-items:end; }
    .filter-bar select, .filter-bar input { padding:8px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:12px; }
    
    table { width:100%; border-collapse:collapse; font-size:12px; }
    table th { background:#f9fafb; padding:9px 10px; font-size:9px; text-transform:uppercase; letter-spacing:0.5px; color:#6b7280; font-weight:700; border-bottom:1px solid #e5e7eb; }
    table td { padding:9px 10px; border-bottom:1px solid #f3f4f6; }
    table tbody tr:hover td { background:#f9fafb; }
    .num { text-align:center; font-weight:600; font-variant-numeric:tabular-nums; }
    
    .badge { display:inline-block; padding:2px 8px; border-radius:100px; font-size:10px; font-weight:600; }
    .badge-success { background:#ecfdf5; color:#059669; }
    .badge-muted { background:#f3f4f6; color:#6b7280; }
    
    .btn { display:inline-flex; align-items:center; gap:5px; padding:8px 14px; border-radius:7px; font-weight:600; font-size:12px; cursor:pointer; border:none; text-decoration:none; }
    .btn-primary { background:#4f46e5; color:#fff; }
    .btn-outline { background:#fff; color:#374151; border:1px solid #d1d5db; }
    .btn-danger { background:#dc2626; color:#fff; }
    .btn-xs { padding:4px 8px; font-size:10px; }
    
    .alert { padding:12px 16px; border-radius:8px; margin-top:12px; font-size:12px; }
    .alert-error { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
    .empty { text-align:center; padding:40px; color:#6b7280; }
    
    @media (max-width:768px) { .form-row { grid-template-columns:1fr; } }
</style>

<div class="page-header">
    <div>
        <h1>📄 Employee Documents</h1>
        <p>Upload and manage employee documents linked to applicants</p>
    </div>
</div>

<!-- Upload Form -->
<div class="card">
    <div class="card-header"><h3>📤 Upload New Document</h3></div>
    <div class="card-body">
        <form action="{{ route('documents.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-row">
                <!-- SEARCHABLE EMPLOYEE SELECT - KEEP ORIGINAL FUNCTIONALITY -->
                <div class="form-group">
                    <label>🔍 Search & Select Employee</label>
                    <div class="search-box" id="searchWrap">
                        <input type="text" id="employee_search" placeholder="Type employee name to search..." autocomplete="off"
                               onfocus="showDropdown()" onkeyup="filterEmployees()">
                        <span class="sicon">🔍</span>
                        <div class="search-drop" id="employee_dropdown">
                            <div class="opt clear" onclick="clearEmployeeSelection()">✖ Clear Selection (Manual Entry)</div>
                            @foreach($applicants as $applicant)
                            <div class="opt employee-option" 
                                 data-id="{{ $applicant->id }}"
                                 data-name="{{ $applicant->first_name }} {{ $applicant->surname }}"
                                 data-position="{{ $applicant->position->name ?? 'N/A' }}"
                                 onclick="selectEmployee(this)">
                                <strong>{{ $applicant->first_name }} {{ $applicant->surname }}</strong>
                                <span style="font-size:10px;color:#6b7280;"> — {{ $applicant->position->name ?? 'N/A' }} @if($applicant->phone_primary)· 📱 {{ $applicant->phone_primary }}@endif</span>
                            </div>
                            @endforeach
                        </div>
                        <input type="hidden" id="applicant_id" name="applicant_id" value="{{ old('applicant_id') }}">
                    </div>
                    <small style="font-size:10px; color:#6b7280; margin-top:3px;"><span id="selected_count">{{ count($applicants) }}</span> employees · Type to search</small>
                </div>
                <div class="form-group">
                    <label>Employee Name *</label>
                    <input type="text" id="employee_name" name="employee_name" value="{{ old('employee_name') }}" required placeholder="Auto-filled from selection">
                </div>
                <div class="form-group">
                    <label>Document Type *</label>
                    <select name="document_type" required>
                        <option value="">Select Type</option>
                        <option value="Contract">📝 Contract</option>
                        <option value="ID Card">🪪 ID Card</option>
                        <option value="Passport">🛂 Passport</option>
                        <option value="Visa">🛂 Visa</option>
                        <option value="Certificate">📜 Certificate</option>
                        <option value="CV">📄 CV/Resume</option>
                        <option value="Medical">🏥 Medical</option>
                        <option value="Insurance">🛡️ Insurance</option>
                        <option value="Other">📎 Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>File * (Max 10MB)</label>
                    <input type="file" name="document" required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.txt,.zip,.rar">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" value="{{ old('description') }}" placeholder="Optional">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top:12px;">📤 Upload Document</button>
        </form>
        @if($errors->any())
        <div class="alert alert-error">@foreach($errors->all() as $e)<div>⚠️ {{ $e }}</div>@endforeach</div>
        @endif
    </div>
</div>

<!-- Filters -->
<div class="card">
    <div class="card-header"><h3>🔍 Filter Documents</h3></div>
    <div class="card-body">
        <form action="{{ route('documents.index') }}" method="GET" class="filter-bar">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search employee..." style="min-width:180px;">
            <select name="type">
                <option value="">All Types</option>
                <option value="Contract">Contract</option><option value="ID Card">ID Card</option>
                <option value="Passport">Passport</option><option value="Visa">Visa</option>
                <option value="Certificate">Certificate</option><option value="CV">CV</option>
                <option value="Medical">Medical</option><option value="Insurance">Insurance</option>
            </select>
            <select name="applicant_id">
                <option value="">All Employees</option>
                @foreach($applicants as $app)
                <option value="{{ $app->id }}" {{ request('applicant_id')==$app->id?'selected':'' }}>{{ $app->first_name }} {{ $app->surname }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary" style="padding:8px 12px; font-size:11px;">🔍 Filter</button>
            <a href="{{ route('documents.index') }}" class="btn btn-outline" style="padding:8px 12px; font-size:11px;">✖ Clear</a>
        </form>
    </div>
</div>

<!-- Stats -->
<div class="card">
    <div class="card-header"><h3>📊 Document Statistics</h3></div>
    <div class="card-body">
        <div class="stats-grid">
            <div class="stat-card"><div class="val">{{ $stats['total'] }}</div><div class="lbl">Total Documents</div></div>
            <div class="stat-card"><div class="val">{{ $stats['total_size']>=1048576 ? number_format($stats['total_size']/1048576,2).' MB' : number_format($stats['total_size']/1024,2).' KB' }}</div><div class="lbl">Total Size</div></div>
            @foreach($stats['by_type'] as $t)
            <div class="stat-card"><div class="val">{{ $t->count }}</div><div class="lbl">{{ $t->document_type }}</div></div>
            @endforeach
        </div>
    </div>
</div>

<!-- Documents Table -->
<div class="card" style="padding:0;">
    <div class="card-header" style="border-bottom:1px solid #e5e7eb;"><h3>📁 Uploaded Documents</h3></div>
    @if($documents->count()>0)
    <div style="overflow-x:auto;">
        <table>
            <thead><tr><th>Document</th><th>Employee</th><th>Linked</th><th>Type</th><th class="num">Size</th><th>Uploaded</th><th style="text-align:center;">Action</th></tr></thead>
            <tbody>
                @foreach($documents as $doc)
                <tr>
                    <td><strong>{{ $doc->document_name }}</strong>@if($doc->description)<br><span style="font-size:10px;color:#6b7280;">{{ $doc->description }}</span>@endif</td>
                    <td>{{ $doc->employee_name }}</td>
                    <td>@if($doc->applicant)<span class="badge badge-success">👤 {{ $doc->applicant->first_name }}</span>@else<span class="badge badge-muted">Not linked</span>@endif</td>
                    <td><span class="badge badge-success">{{ $doc->document_type }}</span></td>
                    <td class="num">{{ $doc->file_size_formatted }}</td>
                    <td style="font-size:11px;">{{ $doc->uploaded_at->format('M d, Y') }}</td>
                    <td style="text-align:center;">
                        <a href="{{ route('documents.download', $doc->id) }}" class="btn btn-outline btn-xs">⬇️</a>
                        <form action="{{ route('documents.destroy', $doc->id) }}" method="POST" style="display:inline;">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-xs" onclick="return confirm('Delete?')">🗑️</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="padding:12px 20px;">{{ $documents->links('vendor.pagination.bootstrap-5') }}</div>
    @else
    <div class="empty">📁 No documents found</div>
    @endif
</div>

<script>
// ============================================
// EMPLOYEE SEARCH DROPDOWN - Full Functionality
// ============================================
let selectedEmployeeId = {{ old('applicant_id', 'null') }};

function showDropdown() {
    document.getElementById('employee_dropdown').classList.add('open');
    filterEmployees();
}

function filterEmployees() {
    var t = document.getElementById('employee_search').value.toLowerCase().trim();
    var opts = document.querySelectorAll('.employee-option');
    var visible = 0;
    opts.forEach(function(o) {
        var name = o.getAttribute('data-name').toLowerCase();
        var pos = o.getAttribute('data-position').toLowerCase();
        if (t === '' || name.includes(t) || pos.includes(t)) {
            o.style.display = 'block'; visible++;
            if (t !== '') {
                var nameEl = o.querySelector('strong');
                var origName = o.getAttribute('data-name');
                nameEl.innerHTML = origName.replace(new RegExp('('+t.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')+')','gi'), '<span class="highlight">$1</span>');
            } else {
                o.querySelector('strong').textContent = o.getAttribute('data-name');
            }
        } else { o.style.display = 'none'; }
    });
    document.getElementById('selected_count').textContent = visible;
}

function selectEmployee(el) {
    var id = el.getAttribute('data-id');
    var name = el.getAttribute('data-name');
    document.getElementById('applicant_id').value = id;
    document.getElementById('employee_search').value = name;
    document.getElementById('employee_name').value = name;
    document.getElementById('employee_name').readOnly = true;
    selectedEmployeeId = id;
    document.querySelectorAll('.employee-option').forEach(function(o) { o.classList.remove('selected'); });
    el.classList.add('selected');
    document.getElementById('employee_dropdown').classList.remove('open');
}

function clearEmployeeSelection() {
    document.getElementById('applicant_id').value = '';
    document.getElementById('employee_search').value = '';
    document.getElementById('employee_name').value = '';
    document.getElementById('employee_name').readOnly = false;
    selectedEmployeeId = null;
    document.querySelectorAll('.employee-option').forEach(function(o) { o.classList.remove('selected'); });
    document.getElementById('employee_dropdown').classList.remove('open');
}

document.addEventListener('click', function(e) {
    var w = document.getElementById('searchWrap');
    if (w && !w.contains(e.target)) document.getElementById('employee_dropdown').classList.remove('open');
});

// Keyboard navigation
document.getElementById('employee_search').addEventListener('keydown', function(e) {
    var drop = document.getElementById('employee_dropdown');
    var visible = Array.from(document.querySelectorAll('.employee-option')).filter(function(o) { return o.style.display !== 'none'; });
    if (e.key === 'Escape') { drop.classList.remove('open'); this.blur(); }
    else if (e.key === 'Enter') { e.preventDefault(); var sel = drop.querySelector('.employee-option.selected'); if (sel) selectEmployee(sel); }
    else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault(); drop.classList.add('open');
        var cur = visible.findIndex(function(o) { return o.classList.contains('selected'); });
        var next = e.key === 'ArrowDown' ? (cur < visible.length-1 ? cur+1 : 0) : (cur > 0 ? cur-1 : visible.length-1);
        visible.forEach(function(o) { o.classList.remove('selected'); });
        if (visible[next]) { visible[next].classList.add('selected'); visible[next].scrollIntoView({block:'nearest'}); }
    }
});

// Pre-select if editing
@if(old('applicant_id'))
window.addEventListener('DOMContentLoaded', function() {
    var opt = document.querySelector('.employee-option[data-id="{{ old('applicant_id') }}"]');
    if (opt) selectEmployee(opt);
});
@endif
</script>
@endsection
