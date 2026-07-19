@extends('layout')

@section('title', 'Leave Report | TNT HR')

@section('content')
<style>
    .report-card { background: #fff; border-radius: 14px; border: 1px solid #dce5ee; overflow: hidden; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); }
    .report-header { background: linear-gradient(135deg, #16324f, #1b7f79); padding: 20px 24px; color: #fff; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .report-header h3 { margin: 0; font-size: 16px; }
    .report-body { padding: 20px 24px; }
    
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 20px; }
    .stat-card { background: #f8fbfd; border-radius: 10px; padding: 16px; text-align: center; border: 1px solid #dce5ee; }
    .stat-card .stat-val { font-size: 26px; font-weight: 700; color: #16324f; }
    .stat-card .stat-lbl { font-size: 11px; color: #627386; text-transform: uppercase; margin-top: 4px; letter-spacing: 0.03em; }
    .stat-card.green { border-left: 4px solid #18794e; }
    .stat-card.blue { border-left: 4px solid #397a9f; }
    .stat-card.orange { border-left: 4px solid #d38b2a; }
    .stat-card.red { border-left: 4px solid #a61b1b; }
    
    .filter-form { display: flex; gap: 10px; flex-wrap: wrap; align-items: end; margin-bottom: 20px; background: #fafbfc; padding: 14px 18px; border-radius: 10px; border: 1px solid #dce5ee; }
    .filter-form .field { display: flex; flex-direction: column; gap: 4px; }
    .filter-form label { font-size: 11px; font-weight: 700; color: #627386; text-transform: uppercase; }
    .filter-form input, .filter-form select { padding: 8px 12px; border: 2px solid #dce5ee; border-radius: 8px; font-size: 13px; min-width: 150px; }
    .filter-form input:focus, .filter-form select:focus { outline: none; border-color: #1b7f79; }
    
    .btn-primary { padding: 9px 18px; background: #1b7f79; color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 13px; }
    .btn-primary:hover { background: #156b66; }
    .btn-export { padding: 9px 18px; background: #d38b2a; color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-block; }
    .btn-export:hover { background: #b87820; }
    .btn-reset { padding: 9px 18px; background: #e9eff5; color: #23384d; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 13px; text-decoration: none; }
    
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    table th { background: #f0f4f8; padding: 10px 14px; font-size: 11px; text-transform: uppercase; color: #627386; text-align: left; border-bottom: 2px solid #dce5ee; white-space: nowrap; }
    table td { padding: 10px 14px; border-bottom: 1px solid #f0f4f8; }
    table tr:hover td { background: #f8fbfd; }
    .num { text-align: center; font-weight: 600; }
    .badge-status { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; }
    .badge-approved { background: #dcf5e7; color: #18794e; }
    .badge-pending { background: #fff5e5; color: #a45100; }
    .badge-rejected { background: #ffeded; color: #a61b1b; }
    
    /* Summary Cards */
    .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 20px; }
    .summary-card { background: #fff; border: 1px solid #dce5ee; border-radius: 10px; padding: 16px; }
    .summary-card h4 { margin: 0 0 8px; font-size: 13px; color: #16324f; }
    .summary-card .bar { height: 8px; background: #e9eff5; border-radius: 4px; margin-top: 6px; overflow: hidden; }
    .summary-card .bar-fill { height: 100%; border-radius: 4px; transition: width 0.5s; }
    .summary-card .row { display: flex; justify-content: space-between; font-size: 12px; margin: 4px 0; }
    .summary-card .row .val { font-weight: 600; }
    
    .info-bar { padding: 10px 24px; font-size: 12px; color: #627386; background: #fafbfc; border-top: 1px solid #dce5ee; }
    
    @media (max-width: 768px) { .filter-form { flex-direction: column; } .stats-grid { grid-template-columns: 1fr 1fr; } }
</style>

<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:16px;">
    <div>
        <h1 style="margin:0;">📈 Leave Report</h1>
        <p style="color:#627386; margin:4px 0 0; font-size:13px;">Generate detailed leave reports with filters</p>
    </div>
    <a href="{{ route('leaves.index') }}" class="btn-reset">← Back to Leaves</a>
</div>

<!-- Stats Overview -->
<div class="stats-grid">
    <div class="stat-card green">
        <div class="stat-val">{{ $reports->count() }}</div>
        <div class="stat-lbl">📋 Total Records</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-val">{{ number_format($reports->sum('total_days'), 1) }}</div>
        <div class="stat-lbl">📊 Total Days</div>
    </div>
    <div class="stat-card orange">
        <div class="stat-val">{{ number_format($reports->count() > 0 ? $reports->avg('total_days') : 0, 1) }}</div>
        <div class="stat-lbl">📐 Avg Days/Leave</div>
    </div>
    <div class="stat-card red">
        <div class="stat-val">{{ $reports->unique('applicant_id')->count() }}</div>
        <div class="stat-lbl">👥 Unique Employees</div>
    </div>
</div>

<!-- Filter Form -->
<form method="GET" action="{{ route('leaves.report') }}" class="filter-form">
    <div class="field">
        <label>From Date</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}">
    </div>
    <div class="field">
        <label>To Date</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}">
    </div>
    <div class="field">
        <label>Leave Type</label>
        <select name="leave_type">
            <option value="">All Types</option>
            @php $types = \App\Models\LeaveType::where('active', true)->get(); @endphp
            @foreach($types as $type)
                <option value="{{ $type->id }}" {{ request('leave_type') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label>Status</label>
        <select name="status">
            <option value="">All Status</option>
            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>✅ Approved</option>
            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>⏳ Pending</option>
            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>❌ Rejected</option>
        </select>
    </div>
    <div class="field">
        <label>&nbsp;</label>
        <div style="display:flex; gap:8px;">
            <button type="submit" class="btn-primary">📊 Generate Report</button>
            <a href="{{ route('leaves.report') }}" class="btn-reset">✖ Reset</a>
        </div>
    </div>
</form>

@if($reports->count() > 0)
    <!-- Summary by Leave Type -->
    <div class="summary-grid">
        @php
            $byType = $reports->groupBy('leave_type_id');
            $typeColors = ['#1b7f79', '#397a9f', '#d38b2a', '#a61b1b', '#18794e', '#4b3bad', '#0d6289'];
        @endphp
        @foreach($byType as $typeId => $items)
            @php
                $typeName = $items->first()->leaveType->name ?? 'Unknown';
                $typeCode = $items->first()->leaveType->code ?? '';
                $totalDays = $items->sum('total_days');
                $count = $items->count();
                $pct = $reports->sum('total_days') > 0 ? ($totalDays / $reports->sum('total_days')) * 100 : 0;
                $color = $typeColors[$loop->index % count($typeColors)];
            @endphp
            <div class="summary-card">
                <h4>
                    @switch($typeCode)
                        @case('annual') 🏖️ @break @case('sick') 🏥 @break
                        @case('maternity') 🤰 @break @case('paternity') 👨‍🍼 @break
                        @case('marriage') 💒 @break @case('bereavement') 🕊️ @break
                        @default 📋
                    @endswitch
                    {{ $typeName }}
                </h4>
                <div class="row"><span>Requests:</span><span class="val">{{ $count }}</span></div>
                <div class="row"><span>Total Days:</span><span class="val">{{ number_format($totalDays, 1) }}</span></div>
                <div class="row"><span>Percentage:</span><span class="val">{{ number_format($pct, 1) }}%</span></div>
                <div class="bar"><div class="bar-fill" style="width:{{ $pct }}%; background:{{ $color }};"></div></div>
            </div>
        @endforeach
    </div>

    <!-- Report Table -->
    <div class="report-card">
        <div class="report-header">
            <h3>📋 Leave Records</h3>
            <a href="{{ route('leaves.report', array_merge(request()->all(), ['export' => 1])) }}" class="btn-export">📥 Export CSV</a>
        </div>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Position</th>
                        <th>Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th class="num">Days</th>
                        <th>Status</th>
                        <th>Reason</th>
                        <th>Approved By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reports as $index => $r)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $r->applicant->full_name ?? 'N/A' }}</strong></td>
                        <td style="font-size:12px; color:#627386;">{{ $r->applicant->position->name ?? 'N/A' }}</td>
                        <td><span style="display:inline-block; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:700; background:#e8f3f2; color:#0c625e;">{{ $r->leaveType->name ?? 'N/A' }}</span></td>
                        <td>{{ $r->start_date->format('M d, Y') }}</td>
                        <td>{{ $r->end_date->format('M d, Y') }}</td>
                        <td class="num">{{ $r->total_days }}</td>
                        <td><span class="badge-status badge-{{ $r->status }}">{{ ucfirst($r->status) }}</span></td>
                        <td>{{ \Illuminate\Support\Str::limit($r->reason, 30) ?: '-' }}</td>
                        <td>{{ $r->approved_by ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="info-bar">
            Showing <strong>{{ $reports->count() }}</strong> records | 
            Total Days: <strong>{{ number_format($reports->sum('total_days'), 1) }}</strong> |
            Average: <strong>{{ number_format($reports->avg('total_days'), 1) }} days</strong>
        </div>
    </div>
@else
    <div class="report-card">
        <div class="report-body" style="text-align:center; padding:60px; color:#627386;">
            <div style="font-size:48px; margin-bottom:12px;">📊</div>
            <div style="font-weight:600; font-size:15px;">No records found</div>
            <div style="font-size:12px; margin-top:4px;">Select filters and click "Generate Report"</div>
        </div>
    </div>
@endif
@endsection
