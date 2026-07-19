@extends('layout')

@section('title', 'Leave Balances | TNT HR')

@section('content')
<style>
    .bal-card { background:#fff; border-radius:16px; border:1px solid #dce5ee; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,0.04); }
    .bal-header { background:linear-gradient(135deg, #16324f, #1b7f79); padding:20px 24px; color:#fff; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; }
    .bal-header h2 { margin:0; font-size:18px; }
    .bal-header p { margin:2px 0 0; opacity:0.8; font-size:12px; }
    .bal-header a { color:#fff; text-decoration:none; padding:6px 14px; border-radius:6px; font-size:12px; font-weight:600; background:rgba(255,255,255,0.15); }
    
    .stats-row { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; padding:16px 24px; border-bottom:1px solid #dce5ee; }
    .stat-box { text-align:center; padding:14px; background:#f8fbfd; border-radius:10px; }
    .stat-box .val { font-size:22px; font-weight:700; color:#16324f; }
    .stat-box .lbl { font-size:11px; color:#627386; text-transform:uppercase; margin-top:2px; }
    
    .filter-row { padding:12px 24px; border-bottom:1px solid #dce5ee; background:#fafbfc; display:flex; gap:10px; flex-wrap:wrap; align-items:end; }
    .filter-row select, .filter-row input { padding:8px 12px; border:2px solid #dce5ee; border-radius:8px; font-size:13px; min-width:160px; }
    .filter-row select:focus { outline:none; border-color:#1b7f79; }
    .btn-sm { padding:8px 16px; border-radius:8px; font-weight:600; font-size:12px; cursor:pointer; border:none; text-decoration:none; display:inline-block; }
    .btn-go { background:#1b7f79; color:#fff; }
    .btn-reset { background:#e9eff5; color:#23384d; }
    
    .search-wrap { position:relative; min-width:220px; }
    .search-wrap input { width:100%!important; padding:8px 35px 8px 12px!important; border:2px solid #dce5ee!important; border-radius:8px!important; font-size:13px!important; }
    .search-wrap input:focus { border-color:#1b7f79!important; outline:none; }
    .search-wrap .sicon { position:absolute; right:10px; top:50%; transform:translateY(-50%); color:#627386; pointer-events:none; }
    .search-drop { position:absolute; top:100%; left:0; right:0; max-height:220px; overflow-y:auto; background:#fff; border:2px solid #1b7f79; border-top:none; border-radius:0 0 10px 10px; z-index:999; box-shadow:0 8px 20px rgba(0,0,0,0.15); display:none; }
    .search-drop.open { display:block; }
    .search-drop .opt { padding:9px 14px; cursor:pointer; border-bottom:1px solid #f0f4f8; font-size:13px; }
    .search-drop .opt:hover { background:#e8f3f2; }
    .search-drop .opt.clear { color:#627386; font-style:italic; font-weight:600; background:#f8f9fa; }
    
    table { width:100%; border-collapse:collapse; font-size:13px; }
    table th { background:#f0f4f8; padding:10px 10px; font-size:10px; text-transform:uppercase; color:#627386; text-align:left; border-bottom:2px solid #dce5ee; white-space:nowrap; }
    table td { padding:10px 10px; border-bottom:1px solid #f0f4f8; vertical-align:middle; }
    table tbody tr:hover td { background:#f8fbfd; }
    .num { text-align:center; font-weight:600; }
    .badge-type { display:inline-block; padding:3px 8px; border-radius:12px; font-size:10px; font-weight:700; background:#e8f3f2; color:#0c625e; }
    .avail-green { color:#18794e; font-weight:700; font-size:14px; }
    .avail-red { color:#a61b1b; font-weight:700; font-size:14px; }
    .used-detail { font-size:10px; color:#627386; margin-top:2px; line-height:1.4; }
    .eth-date { font-size:10px; color:#627386; }
    .info-bar { padding:10px 24px; font-size:12px; color:#627386; background:#fafbfc; border-top:1px solid #dce5ee; display:flex; justify-content:space-between; flex-wrap:wrap; gap:8px; }
    .formula-note { padding:10px 24px; background:#fffdf5; border-top:1px solid #f1d3a3; font-size:11px; color:#a45100; text-align:center; }
    
    @media (max-width:768px) { .stats-row { grid-template-columns:1fr 1fr 1fr; } .filter-row { flex-direction:column; } }
</style>

<div class="bal-card">
    <div class="bal-header">
        <div>
            <h2>📊 Leave Balances</h2>
            <p>🇪🇹 Ethiopian Pro-rata Daily Accrual · Proclamation 1156/2011</p>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="{{ route('leaves.breakdown') }}">📊 Breakdown</a>
            <a href="{{ route('leaves.carryforward.index') }}">📦 Carry Fwd</a>
            <a href="{{ route('leaves.index') }}">← Back</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-box"><div class="val">{{ $balances->total() }}</div><div class="lbl">Total Records</div></div>
        <div class="stat-box"><div class="val" style="color:#18794e;">{{ $balances->where('available_days', '>', 0)->count() }}</div><div class="lbl">✅ With Balance</div></div>
        <div class="stat-box"><div class="val" style="color:#a45100;">{{ $balances->where('available_days', '<=', 0)->count() }}</div><div class="lbl">⚠️ Exhausted</div></div>
        <div class="stat-box"><div class="val" style="color:#397a9f;">{{ number_format($balances->sum('available_days'), 1) }}</div><div class="lbl">📊 Available</div></div>
        <div class="stat-box"><div class="val" style="color:#d38b2a;">{{ number_format($balances->sum('carry_forward_days'), 1) }}</div><div class="lbl">📦 Carry Fwd</div></div>
    </div>

    <!-- Formula Note -->
    <div class="formula-note">
        📐 <strong>Available = Accrued − Used − Pending − Expired + Carry Forward</strong> &nbsp;|&nbsp;
        ⏰ Unused leave expires <strong>2 years</strong> after leave year ends
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('leaves.balances') }}" id="filterForm" class="filter-row">
        <div class="search-wrap" id="searchWrap">
            <input type="text" id="empSearch" placeholder="🔍 Search employee..." autocomplete="off"
                   value="{{ request('employee_name') }}"
                   onfocus="document.getElementById('empDrop').classList.add('open')" onkeyup="filterDrop()">
            <span class="sicon">🔍</span>
            <div class="search-drop" id="empDrop">
                <div class="opt clear" onclick="selectEmp('', '')">✖ Show All</div>
                @foreach($applicants as $app)
                    <div class="opt emp-opt" data-id="{{ $app->id }}" data-name="{{ $app->full_name }}"
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

        <button type="submit" class="btn-sm btn-go">🔍 Filter</button>
        @if(request('applicant_id') || request('leave_type_id') || request('availability'))
            <a href="{{ route('leaves.balances') }}" class="btn-sm btn-reset">✖ Clear</a>
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
                    
                    // Calculate expired for annual leave
                    $expiredDays = 0;
                    if ($b->leaveType->code === 'annual') {
                        $expiredData = $expiryService->calculateExpiredLeave($b->applicant);
                        $expiredDays = $expiredData['total'];
                    }
                    
                    // Ethiopian date
                    $ethHireDate = $b->applicant->registration_date 
                        ? $ethCal->gregorianToEthiopian($b->applicant->registration_date) 
                        : null;
                @endphp
                <tr>
                    <td>{{ $balances->firstItem() + $index }}</td>
                    <td>
                        <strong>{{ $b->applicant->full_name ?? 'N/A' }}</strong>
                        <div style="font-size:10px; color:#627386;">📅 GC: {{ $b->applicant->registration_date ?? 'N/A' }}</div>
                        @if($ethHireDate)
                        <div class="eth-date">🇪🇹 {{ $ethHireDate['day'] }} {{ $ethHireDate['month_name'] }} {{ $ethHireDate['year'] }}</div>
                        @endif
                    </td>
                    <td style="font-size:11px;">{{ $b->applicant->position->name ?? 'N/A' }}</td>
                    <td><span class="badge-type">{{ $b->leaveType->name ?? 'N/A' }}</span></td>
                    <td class="num">{{ number_format($b->total_entitled, 1) }}</td>
                    <td class="num">
                        <strong>{{ number_format($b->used_current_year, 1) }}</strong>
                        @if($b->used_current_year > 0 && $typeLeaves->count() > 0)
                            <div class="used-detail">
                                @foreach($typeLeaves->take(3) as $leave)
                                    @php $ethStart = $ethCal->gregorianToEthiopian($leave->start_date); @endphp
                                    {{ $leave->start_date->format('M d') }}→{{ $leave->end_date->format('M d') }} 
                                    <span style="font-size:9px;">🇪🇹{{ $ethStart['day'] }}/{{ substr($ethStart['month_name'],0,3) }}</span>
                                    ({{ $leave->total_days }}d)<br>
                                @endforeach
                                @if($typeLeaves->count() > 3)
                                    <em>+{{ $typeLeaves->count() - 3 }} more</em>
                                @endif
                            </div>
                        @endif
                    </td>
                    <td class="num" style="color:#a45100;">{{ number_format($b->pending_days, 1) }}</td>
                    <td class="num" style="color:#666;">
                        @if($b->leaveType->code === 'annual')
                            {{ number_format($expiredDays, 1) }}
                            @if($expiredDays > 0)
                                <span style="font-size:9px; color:#a61b1b;">⏰</span>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td class="num" style="color:#d38b2a;">{{ number_format($b->carry_forward_days ?? 0, 1) }}</td>
                    <td class="num">
                        <span class="{{ $b->available_days > 0 ? 'avail-green' : 'avail-red' }}">
                            {{ number_format($b->available_days, 1) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center;padding:50px;color:#627386;">
                    <div style="font-size:40px;">📊</div><div style="font-weight:600;">No leave balances</div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="info-bar">
        <span>Showing <strong>{{ $balances->firstItem() ?? 0 }}</strong>-<strong>{{ $balances->lastItem() ?? 0 }}</strong> of <strong>{{ $balances->total() }}</strong></span>
        <span>Total Available: <strong>{{ number_format($balances->sum('available_days'), 1) }} days</strong></span>
    </div>

    @if($balances->hasPages())
    <div style="padding:14px 24px; border-top:1px solid #dce5ee; display:flex; justify-content:center;">
        {{ $balances->appends(request()->query())->links('vendor.pagination.bootstrap-5') }}
    </div>
    @endif
</div>

<script>
function filterDrop() {
    var term = document.getElementById('empSearch').value.toLowerCase().trim();
    document.querySelectorAll('.emp-opt').forEach(function(o) {
        o.style.display = (term === '' || o.getAttribute('data-name').toLowerCase().includes(term)) ? 'block' : 'none';
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
