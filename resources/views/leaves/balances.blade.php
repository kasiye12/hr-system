@extends('layout')

@section('title', 'Leave Balances | TNT HR')

@section('content')
<style>
    .page-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
    .page-header h1 { font-size:20px; font-weight:800; color:#111827; margin:0; }
    .page-header p { color:#6b7280; font-size:12px; margin:2px 0 0; }
    
    .stats-row { display:grid; grid-template-columns:repeat(5,1fr); gap:10px; margin-bottom:20px; }
    .stat-card { background:#fff; border-radius:10px; padding:14px 16px; text-align:center; border:1px solid #e5e7eb; box-shadow:0 1px 2px rgba(0,0,0,0.04); }
    .stat-card .val { font-size:22px; font-weight:800; color:#111827; }
    .stat-card .lbl { font-size:10px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-top:2px; }
    
    .card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden; margin-bottom:16px; }
    .card-header { padding:14px 20px; border-bottom:1px solid #f3f4f6; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; }
    .card-header h3 { font-size:14px; font-weight:700; color:#111827; margin:0; }
    
    .formula-bar { padding:10px 20px; background:#fffbeb; border-bottom:1px solid #fde68a; font-size:11px; color:#92400e; text-align:center; }
    
    .filter-bar { padding:12px 20px; border-bottom:1px solid #f3f4f6; background:#fafafa; display:flex; gap:8px; flex-wrap:wrap; align-items:end; }
    .filter-bar select, .filter-bar input { padding:7px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:12px; }
    .filter-bar select:focus, .filter-bar input:focus { outline:none; border-color:#4f46e5; box-shadow:0 0 0 2px rgba(79,70,229,0.1); }
    
    .btn-xs { padding:6px 12px; border-radius:6px; font-weight:600; font-size:11px; cursor:pointer; border:none; text-decoration:none; display:inline-flex; align-items:center; gap:4px; }
    .btn-primary { background:#4f46e5; color:#fff; }
    .btn-outline { background:#fff; color:#374151; border:1px solid #d1d5db; }
    
    .search-box { position:relative; min-width:200px; }
    .search-box input { width:100%!important; padding:7px 30px 7px 10px!important; border:1px solid #d1d5db!important; border-radius:6px!important; font-size:12px!important; }
    .search-box input:focus { border-color:#4f46e5!important; outline:none; }
    .search-box .icon { position:absolute; right:8px; top:50%; transform:translateY(-50%); color:#9ca3af; font-size:13px; }
    .search-drop { display:none; position:absolute; top:100%; left:0; right:0; max-height:200px; overflow-y:auto; background:#fff; border:1px solid #4f46e5; border-top:none; border-radius:0 0 8px 8px; z-index:99; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
    .search-drop.open { display:block; }
    .search-drop div { padding:8px 12px; cursor:pointer; border-bottom:1px solid #f3f4f6; font-size:12px; }
    .search-drop div:hover { background:#eef2ff; }
    .search-drop div.clear { color:#9ca3af; font-style:italic; font-weight:600; }
    
    table { width:100%; border-collapse:collapse; font-size:12px; }
    table th { background:#f9fafb; padding:9px 10px; font-size:9px; text-transform:uppercase; letter-spacing:0.5px; color:#6b7280; font-weight:700; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
    table td { padding:9px 10px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
    table tbody tr:hover td { background:#f9fafb; }
    .num { text-align:center; font-weight:600; font-variant-numeric:tabular-nums; }
    .badge-type { display:inline-block; padding:2px 8px; border-radius:100px; font-size:10px; font-weight:600; background:#eef2ff; color:#4f46e5; }
    .text-green { color:#059669; font-weight:700; }
    .text-red { color:#dc2626; font-weight:700; }
    .text-amber { color:#d97706; }
    .text-gray { color:#6b7280; font-size:10px; }
    .used-info { font-size:9px; color:#6b7280; margin-top:1px; line-height:1.3; }
    
    .info-bar { padding:10px 20px; font-size:11px; color:#6b7280; background:#fafafa; border-top:1px solid #e5e7eb; display:flex; justify-content:space-between; flex-wrap:wrap; gap:8px; }
    
    @media (max-width:768px) { .stats-row { grid-template-columns:repeat(3,1fr); } }
    @media (max-width:480px) { .stats-row { grid-template-columns:1fr 1fr; } }
</style>

<div class="page-header">
    <div>
        <h1>📊 Leave Balances</h1>
        <p>🇪🇹 Ethiopian Pro-rata Daily Accrual · Proclamation 1156/2011</p>
    </div>
    <div style="display:flex; gap:6px; flex-wrap:wrap;">
        <a href="{{ route('leaves.breakdown') }}" class="btn-xs btn-outline">📊 Breakdown</a>
        <a href="{{ route('leaves.carryforward.index') }}" class="btn-xs btn-outline">📦 Carry Fwd</a>
        <a href="{{ route('leaves.index') }}" class="btn-xs btn-outline">← Back</a>
    </div>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-card"><div class="val">{{ $balances->total() }}</div><div class="lbl">Total Records</div></div>
    <div class="stat-card"><div class="val" style="color:#059669;">{{ $balances->where('available_days', '>', 0)->count() }}</div><div class="lbl">✅ With Balance</div></div>
    <div class="stat-card"><div class="val" style="color:#d97706;">{{ $balances->where('available_days', '<=', 0)->count() }}</div><div class="lbl">⚠️ Exhausted</div></div>
    <div class="stat-card"><div class="val" style="color:#4f46e5;">{{ number_format($balances->sum('available_days'), 1) }}</div><div class="lbl">📊 Available</div></div>
    <div class="stat-card"><div class="val" style="color:#d97706;">{{ number_format($balances->sum('carry_forward_days'), 1) }}</div><div class="lbl">📦 Carry Fwd</div></div>
</div>

<div class="card">
    <div class="formula-bar">
        📐 <strong>Available = Accrued − Used − Pending − Expired + Carry Forward</strong> &nbsp;|&nbsp; ⏰ Unused leave expires <strong>2 years</strong> after leave year ends
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('leaves.balances') }}" id="filterForm" class="filter-bar">
        <div class="search-box" id="searchWrap">
            <input type="text" id="empSearch" placeholder="🔍 Search employee..." autocomplete="off"
                   value="{{ request('employee_name') }}"
                   onfocus="document.getElementById('empDrop').classList.add('open')" onkeyup="filterDrop()">
            <span class="icon">🔍</span>
            <div class="search-drop" id="empDrop">
                <div class="clear" onclick="selectEmp('', '')">✖ Show All</div>
                @foreach($applicants as $app)
                    <div class="emp-opt" data-id="{{ $app->id }}" data-name="{{ $app->full_name }}"
                         onclick="selectEmp('{{ $app->id }}', '{{ $app->full_name }}')">
                        <strong>{{ $app->full_name }}</strong>
                    </div>
                @endforeach
            </div>
            <input type="hidden" name="applicant_id" id="empId" value="{{ request('applicant_id') }}">
        </div>

        <select name="leave_type_id" onchange="this.form.submit()">
            <option value="">📋 All Types</option>
            @foreach(\App\Models\LeaveType::where('active', true)->get() as $type)
                <option value="{{ $type->id }}" {{ request('leave_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
            @endforeach
        </select>

        <select name="availability" onchange="this.form.submit()">
            <option value="">📊 All</option>
            <option value="has_balance" {{ request('availability')=='has_balance'?'selected':'' }}>✅ Has Balance</option>
            <option value="exhausted" {{ request('availability')=='exhausted'?'selected':'' }}>⚠️ Exhausted</option>
        </select>

        <button type="submit" class="btn-xs btn-primary">🔍 Filter</button>
        @if(request('applicant_id') || request('leave_type_id') || request('availability'))
            <a href="{{ route('leaves.balances') }}" class="btn-xs btn-outline">✖ Clear</a>
        @endif
    </form>

    <!-- Table -->
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Employee</th>
                    <th>Position</th>
                    <th>Leave Type</th>
                    <th class="num">Accrued</th>
                    <th class="num">Used</th>
                    <th class="num">Pending</th>
                    <th class="num">Expired</th>
                    <th class="num">Carry Fwd</th>
                    <th class="num">Available</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $ethCal = new \App\Services\EthiopianCalendarService();
                    $expiryService = new \App\Services\LeaveExpiryService();
                    $annualType = \App\Models\LeaveType::where('code', 'annual')->first();
                    $leaveDetails = [];
                    if ($annualType) {
                        $leaveDetails = \App\Models\LeaveRequest::with('leaveType')
                            ->where('status', 'approved')
                            ->orderBy('start_date', 'desc')
                            ->get()
                            ->groupBy('applicant_id');
                    }
                @endphp

                @forelse($balances as $index => $b)
                @php
                    $appId = $b->applicant_id;
                    $appLeaves = $leaveDetails[$appId] ?? collect();
                    $typeLeaves = $b->leaveType->code === 'annual' 
                        ? $appLeaves->where('leave_type_id', $b->leave_type_id)
                        : $appLeaves->where('leave_type_id', $b->leave_type_id);
                    
                    $expiredDays = 0;
                    if ($b->leaveType->code === 'annual') {
                        $expiredData = $expiryService->calculateExpiredLeave($b->applicant);
                        $expiredDays = $expiredData['total'];
                    }
                    
                    $ethHireDate = $b->applicant->registration_date 
                        ? $ethCal->gregorianToEthiopian($b->applicant->registration_date) 
                        : null;
                @endphp
                <tr>
                    <td>{{ $balances->firstItem() + $index }}</td>
                    <td>
                        <strong>{{ $b->applicant->full_name ?? 'N/A' }}</strong>
                        <div class="text-gray">📅 GC: {{ $b->applicant->registration_date ?? 'N/A' }}</div>
                        @if($ethHireDate)
                        <div class="text-gray">🇪🇹 {{ $ethHireDate['day'] }} {{ $ethHireDate['month_name'] }} {{ $ethHireDate['year'] }}</div>
                        @endif
                    </td>
                    <td style="font-size:11px;">{{ $b->applicant->position->name ?? 'N/A' }}</td>
                    <td><span class="badge-type">{{ $b->leaveType->name ?? 'N/A' }}</span></td>
                    <td class="num">{{ number_format($b->total_entitled, 1) }}</td>
                    <td class="num">
                        <strong>{{ number_format($b->used_current_year, 1) }}</strong>
                        @if($b->used_current_year > 0 && $typeLeaves->count() > 0)
                            <div class="used-info">
                                @foreach($typeLeaves->take(3) as $leave)
                                    @php $ethStart = $ethCal->gregorianToEthiopian($leave->start_date); @endphp
                                    {{ $leave->start_date->format('M d') }}→{{ $leave->end_date->format('M d') }} 
                                    🇪🇹{{ $ethStart['day'] }}/{{ substr($ethStart['month_name'],0,3) }}
                                    ({{ $leave->total_days }}d)<br>
                                @endforeach
                                @if($typeLeaves->count() > 3)
                                    <em>+{{ $typeLeaves->count() - 3 }} more</em>
                                @endif
                            </div>
                        @endif
                    </td>
                    <td class="num text-amber">{{ number_format($b->pending_days, 1) }}</td>
                    <td class="num text-gray">
                        @if($b->leaveType->code === 'annual')
                            {{ number_format($expiredDays, 1) }}
                        @else - @endif
                    </td>
                    <td class="num" style="color:#d97706;">{{ number_format($b->carry_forward_days ?? 0, 1) }}</td>
                    <td class="num">
                        <span class="{{ $b->available_days > 0 ? 'text-green' : 'text-red' }}" style="font-size:14px;">
                            {{ number_format($b->available_days, 1) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center;padding:40px;color:#6b7280;">📊 No leave balances</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="info-bar">
        <span>Showing <strong>{{ $balances->firstItem() ?? 0 }}</strong>-<strong>{{ $balances->lastItem() ?? 0 }}</strong> of <strong>{{ $balances->total() }}</strong></span>
        <span>Total Available: <strong style="color:#059669;">{{ number_format($balances->sum('available_days'), 1) }} days</strong></span>
    </div>
</div>

@if($balances->hasPages())
<div style="display:flex; justify-content:center; padding:12px 20px;">
    {{ $balances->appends(request()->query())->links('vendor.pagination.bootstrap-5') }}
</div>
@endif

<script>
function filterDrop() {
    var t = document.getElementById('empSearch').value.toLowerCase().trim();
    document.querySelectorAll('.emp-opt').forEach(function(o) {
        o.style.display = (t === '' || o.getAttribute('data-name').toLowerCase().includes(t)) ? 'block' : 'none';
    });
}
function selectEmp(id, name) {
    document.getElementById('empId').value = id;
    document.getElementById('empSearch').value = name;
    document.getElementById('empDrop').classList.remove('open');
    document.getElementById('filterForm').submit();
}
document.addEventListener('click', function(e) {
    var w = document.getElementById('searchWrap');
    if (w && !w.contains(e.target)) document.getElementById('empDrop').classList.remove('open');
});
</script>
@endsection
