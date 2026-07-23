@extends('layout')

@section('title', 'Dashboard | TNT HR')

@section('content')
<style>
    .page-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
    .page-header h1 { font-size:22px; font-weight:800; color:#111827; margin:0; }
    .page-header .date-badge { background:#eef2ff; color:#4f46e5; padding:6px 16px; border-radius:100px; font-size:12px; font-weight:600; }
    
    .card { background:#fff; border-radius:12px; border:1px solid #e5e7eb; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden; margin-bottom:20px; }
    .card-header { padding:16px 20px; border-bottom:1px solid #f3f4f6; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; }
    .card-header h3 { font-size:15px; font-weight:700; color:#111827; margin:0; }
    .card-body { padding:20px; }
    
    .filter-bar { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px 20px; margin-bottom:20px; display:flex; gap:10px; flex-wrap:wrap; align-items:end; }
    .filter-bar .form-group { display:flex; flex-direction:column; gap:4px; }
    .filter-bar label { font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; }
    .filter-bar select, .filter-bar input { padding:9px 12px; border:1px solid #d1d5db; border-radius:7px; font-size:13px; min-width:150px; }
    .filter-bar select:focus, .filter-bar input:focus { outline:none; border-color:#4f46e5; }
    
    .btn { display:inline-flex; align-items:center; gap:5px; padding:9px 16px; border-radius:7px; font-weight:600; font-size:12px; cursor:pointer; border:none; text-decoration:none; }
    .btn-primary { background:#4f46e5; color:#fff; }
    .btn-outline { background:#fff; color:#374151; border:1px solid #d1d5db; }
    
    .kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:20px; }
    .kpi-card { background:#fff; border-radius:12px; padding:18px 20px; border:1px solid #e5e7eb; position:relative; overflow:hidden; }
    .kpi-card::before { content:''; position:absolute; top:0; left:0; right:0; height:4px; }
    .kpi-card:nth-child(1)::before { background:#4f46e5; }
    .kpi-card:nth-child(2)::before { background:#059669; }
    .kpi-card:nth-child(3)::before { background:#d97706; }
    .kpi-card:nth-child(4)::before { background:#111827; }
    .kpi-card .kpi-icon { font-size:24px; margin-bottom:4px; }
    .kpi-card .kpi-val { font-size:28px; font-weight:800; color:#111827; }
    .kpi-card .kpi-lbl { font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; }
    .kpi-card.highlight { border-color:#4f46e5; background:#fafaff; }
    .kpi-card.highlight .kpi-val { color:#4f46e5; }
    
    .charts-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; }
    .chart-box { background:#fff; border-radius:12px; padding:20px; border:1px solid #e5e7eb; }
    .chart-box h3 { font-size:14px; font-weight:700; color:#111827; margin:0 0 16px; }
    
    .bar-item { margin-bottom:10px; }
    .bar-item .bar-lbl { display:flex; justify-content:space-between; font-size:12px; margin-bottom:3px; }
    .bar-item .bar-lbl .name { font-weight:500; }
    .bar-item .bar-lbl .val { font-weight:700; }
    .bar-fill { height:8px; background:#e5e7eb; border-radius:4px; overflow:hidden; }
    .bar-fill div { height:100%; border-radius:4px; transition:width 0.5s; }
    
    .pie-wrap { display:flex; align-items:center; gap:20px; justify-content:center; flex-wrap:wrap; }
    .pie-svg { width:140px; height:140px; }
    .pie-leg { display:flex; flex-direction:column; gap:6px; }
    .pie-leg .leg-item { display:flex; align-items:center; gap:8px; font-size:12px; }
    .pie-leg .dot { width:12px; height:12px; border-radius:3px; }
    
    table { width:100%; border-collapse:collapse; font-size:13px; }
    table th { background:#f9fafb; padding:10px 14px; font-size:10px; text-transform:uppercase; letter-spacing:0.5px; color:#6b7280; text-align:left; border-bottom:1px solid #e5e7eb; }
    table td { padding:10px 14px; border-bottom:1px solid #f3f4f6; }
    table .num { text-align:center; font-weight:600; }
    table tr:hover td { background:#f9fafb; }
    .grand-row { background:#eef2ff !important; font-weight:700; }
    .grand-row td { border-top:2px solid #4f46e5; }
    
    .quick-links { display:flex; gap:8px; flex-wrap:wrap; padding:14px 18px; background:#f9fafb; border-radius:10px; border:1px solid #e5e7eb; }
    .quick-links a { padding:7px 14px; border-radius:6px; font-size:12px; font-weight:600; text-decoration:none; background:#fff; color:#374151; border:1px solid #d1d5db; }
    .quick-links a:hover { background:#eef2ff; border-color:#4f46e5; }
    
    .badge { display:inline-block; padding:2px 10px; border-radius:100px; font-size:10px; font-weight:600; }
    .badge-success { background:#ecfdf5; color:#059669; }
    .badge-muted { background:#f3f4f6; color:#6b7280; }
    
    @media (max-width:1024px) { .kpi-grid{grid-template-columns:1fr 1fr;} .charts-row{grid-template-columns:1fr;} }
    @media (max-width:768px) { .filter-bar{flex-direction:column;} .kpi-grid{grid-template-columns:1fr 1fr;} }
    @media (max-width:480px) { .kpi-grid{grid-template-columns:1fr;} }
</style>

<div class="page-header">
    <div>
        <h1>📊 HR Manpower Dashboard</h1>
        <p style="color:#6b7280; font-size:12px; margin:2px 0 0;">Permanent, Contract and Daily Labor figures update automatically</p>
    </div>
    <div class="date-badge">📅 {{ \Carbon\Carbon::parse($selectedDate)->format('F d, Y') }}</div>
</div>

<!-- FILTERS -->
<div class="filter-bar">
    <form method="get" action="{{ route('dashboard') }}" style="display:flex; gap:10px; flex-wrap:wrap; align-items:end; width:100%;">
        <div class="form-group"><label>📅 Date</label>
            <select name="date">
                @foreach($dates as $date)
                    <option value="{{ $date }}" @if($date == $selectedDate) selected @endif>{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group"><label>🏗️ Project</label>
            <select name="project">
                <option value="0">All Projects</option>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}" @if($project->id == $selectedProjectId) selected @endif>{{ $project->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group"><label>📊 Status</label>
            <select name="project_status">
                <option value="All" @if($selectedProjectStatus == 'All') selected @endif>All Statuses</option>
                <option value="Active" @if($selectedProjectStatus == 'Active') selected @endif>Active</option>
                <option value="Completed" @if($selectedProjectStatus == 'Completed') selected @endif>Completed</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">🔄 Update</button>
        <a href="{{ route('dashboard') }}" class="btn btn-outline">Reset</a>
        <a href="{{ route('reports.index') }}" class="btn btn-outline">📊 Reports</a>
    </form>
</div>

<!-- KPI -->
<div class="kpi-grid">
    <div class="kpi-card"><div class="kpi-icon">👔</div><div class="kpi-val">{{ number_format($permanent) }}</div><div class="kpi-lbl">Permanent</div></div>
    <div class="kpi-card"><div class="kpi-icon">📄</div><div class="kpi-val">{{ number_format($contract) }}</div><div class="kpi-lbl">Contract</div></div>
    <div class="kpi-card"><div class="kpi-icon">🔧</div><div class="kpi-val">{{ number_format($daily) }}</div><div class="kpi-lbl">Daily Labor</div></div>
    <div class="kpi-card highlight"><div class="kpi-icon">🏆</div><div class="kpi-val">{{ number_format($grandTotal) }}</div><div class="kpi-lbl">Total Manpower</div></div>
</div>

<!-- CHARTS -->
<div class="charts-row">
    <!-- Pie Chart -->
    <div class="chart-box">
        <h3>📊 Distribution</h3>
        @php
            $total = $permanent + $contract + $daily;
            $pPct = $total > 0 ? round(($permanent/$total)*100) : 0;
            $cPct = $total > 0 ? round(($contract/$total)*100) : 0;
            $dPct = $total > 0 ? round(($daily/$total)*100) : 0;
        @endphp
        @if($total > 0)
        <div class="pie-wrap">
            <div class="pie-svg">
                <svg viewBox="0 0 100 100" style="transform:rotate(-90deg);">
                    <circle cx="50" cy="50" r="40" fill="none" stroke="#e5e7eb" stroke-width="18"/>
                    @if($permanent > 0)
                    <circle cx="50" cy="50" r="40" fill="none" stroke="#4f46e5" stroke-width="18"
                        stroke-dasharray="{{ $pPct * 2.51 }} 251" stroke-dashoffset="0"/>
                    @endif
                    @if($contract > 0)
                    <circle cx="50" cy="50" r="40" fill="none" stroke="#059669" stroke-width="18"
                        stroke-dasharray="{{ $cPct * 2.51 }} 251" stroke-dashoffset="{{ -($pPct * 2.51) }}"/>
                    @endif
                    @if($daily > 0)
                    <circle cx="50" cy="50" r="40" fill="none" stroke="#d97706" stroke-width="18"
                        stroke-dasharray="{{ $dPct * 2.51 }} 251" stroke-dashoffset="{{ -(($pPct+$cPct) * 2.51) }}"/>
                    @endif
                </svg>
            </div>
            <div class="pie-leg">
                <div class="leg-item"><span class="dot" style="background:#4f46e5;"></span> Permanent: {{ number_format($permanent) }} ({{ $pPct }}%)</div>
                <div class="leg-item"><span class="dot" style="background:#059669;"></span> Contract: {{ number_format($contract) }} ({{ $cPct }}%)</div>
                <div class="leg-item"><span class="dot" style="background:#d97706;"></span> Daily: {{ number_format($daily) }} ({{ $dPct }}%)</div>
                <div class="leg-item" style="font-weight:700; border-top:1px solid #e5e7eb; padding-top:6px; margin-top:4px;">Total: {{ number_format($total) }}</div>
            </div>
        </div>
        @else
        <p style="text-align:center; color:#6b7280; padding:30px;">No data for selected date</p>
        @endif
    </div>

    <!-- Bar Chart -->
    <div class="chart-box">
        <h3>📈 By Project</h3>
        @if($tableData->count() > 0)
            @php $maxVal = max($tableData->map(fn($r)=>$r->permanent+$r->contract+$r->daily)->toArray()) ?: 1; @endphp
            @foreach($tableData->take(8) as $row)
                @php $t = $row->permanent + $row->contract + $row->daily; $w = ($t/$maxVal)*100; @endphp
                <div class="bar-item">
                    <div class="bar-lbl"><span class="name">{{ $row->project_name }}</span><span class="val">{{ number_format($t) }}</span></div>
                    <div class="bar-fill"><div style="width:{{ max($w,2) }}%; background:#4f46e5;"></div></div>
                </div>
            @endforeach
        @else
        <p style="text-align:center; color:#6b7280; padding:30px;">No data</p>
        @endif
    </div>
</div>

<!-- TABLE -->
<div class="card" style="padding:0;">
    <div class="card-header">
        <h3>📋 Project Breakdown</h3>
        <span style="font-size:11px; color:#6b7280;">{{ $tableData->count() }} projects</span>
    </div>
    <div style="overflow-x:auto;">
        <table>
            <thead><tr><th>Project</th><th>Status</th><th class="num">Permanent</th><th class="num">Contract</th><th class="num">Daily</th><th class="num">Total</th></tr></thead>
            <tbody>
                @php $tp=0;$tc=0;$td=0; @endphp
                @forelse($tableData as $row)
                    @php $p=round($row->permanent);$c=round($row->contract);$d=round($row->daily);$t=$p+$c+$d;$tp+=$p;$tc+=$c;$td+=$d; @endphp
                    <tr>
                        <td><strong>{{ $row->project_name }}</strong></td>
                        <td><span class="badge {{ $row->project_status=='Active'?'badge-success':'badge-muted' }}">{{ $row->project_status }}</span></td>
                        <td class="num">{{ number_format($p) }}</td>
                        <td class="num">{{ number_format($c) }}</td>
                        <td class="num">{{ number_format($d) }}</td>
                        <td class="num"><strong>{{ number_format($t) }}</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;padding:30px;color:#6b7280;">No data</td></tr>
                @endforelse
                @if($tableData->count()>0)
                <tr class="grand-row">
                    <td colspan="2"><strong>GRAND TOTAL</strong></td>
                    <td class="num"><strong>{{ number_format($tp) }}</strong></td>
                    <td class="num"><strong>{{ number_format($tc) }}</strong></td>
                    <td class="num"><strong>{{ number_format($td) }}</strong></td>
                    <td class="num"><strong>{{ number_format($tp+$tc+$td) }}</strong></td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Links -->
<div class="quick-links" style="margin-top:16px;">
    <span style="font-size:12px; font-weight:600; color:#374151;">📌 Quick Actions:</span>
    <a href="{{ route('daily.index') }}">📝 Daily Input</a>
    <a href="{{ route('reports.index') }}">📊 Reports</a>
    <a href="{{ route('applicants.index') }}">👤 Applicants</a>
    <a href="{{ route('leaves.index') }}">🏖️ Leave Mgmt</a>
    <a href="{{ route('import.index') }}">📥 Import</a>
</div>
@endsection
