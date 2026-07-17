@extends('layouts.app')

@section('title', 'Dashboard - TNT HR')

@section('content')
    <style>
        /* ============================================
           DASHBOARD STYLES
           ============================================ */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .dashboard-header h1 {
            margin: 0;
            font-size: 26px;
        }
        .dashboard-header .subtitle {
            color: var(--muted);
            font-size: 14px;
            margin-top: 2px;
        }
        .dashboard-header .date-badge {
            background: #e8f3f2;
            color: #0c625e;
            padding: 8px 18px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Filter Bar */
        .filter-bar {
            background: #fff;
            border: 1px solid #dce5ee;
            border-radius: 12px;
            padding: 18px 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(21,49,78,0.05);
        }
        .filter-bar form {
            display: flex;
            gap: 16px;
            align-items: end;
            flex-wrap: wrap;
        }
        .filter-bar .field {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
            min-width: 140px;
        }
        .filter-bar .field label {
            font-size: 11px;
            color: var(--muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .filter-bar .field input,
        .filter-bar .field select {
            padding: 9px 14px;
            border: 1px solid #dce5ee;
            border-radius: 8px;
            font-size: 13px;
            background: #fafcfd;
            transition: all 0.2s;
            width: 100%;
        }
        .filter-bar .field input:focus,
        .filter-bar .field select:focus {
            outline: none;
            border-color: #1b7f79;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(27, 127, 121, 0.08);
        }
        .filter-bar .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        /* KPI Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .kpi-card {
            background: #fff;
            border: 1px solid #dce5ee;
            border-radius: 12px;
            padding: 20px 24px;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(21,49,78,0.04);
            position: relative;
            overflow: hidden;
        }
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }
        .kpi-card:nth-child(1)::before { background: #1b7f79; }
        .kpi-card:nth-child(2)::before { background: #397a9f; }
        .kpi-card:nth-child(3)::before { background: #d38b2a; }
        .kpi-card:nth-child(4)::before { background: #16324f; }

        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(21,49,78,0.1);
        }
        .kpi-card .kpi-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .kpi-card .icon {
            font-size: 28px;
            width: 48px;
            height: 48px;
            background: #f8fbfd;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .kpi-card .label {
            font-size: 12px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-weight: 600;
            margin-top: 8px;
        }
        .kpi-card .value {
            font-size: 30px;
            font-weight: 700;
            color: #16324f;
            margin-top: 2px;
        }
        .kpi-card .change {
            font-size: 12px;
            margin-top: 6px;
            padding: 2px 10px;
            border-radius: 999px;
            display: inline-block;
            font-weight: 600;
        }
        .kpi-card .change.positive { background: #dcf5e7; color: #12643c; }
        .kpi-card .change.negative { background: #ffeded; color: #a61b1b; }
        .kpi-card .change.neutral { background: #eef2f7; color: #627386; }
        .kpi-card .kpi-footer {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eef2f7;
            font-size: 12px;
            color: var(--muted);
        }
        .kpi-card.highlight {
            border-color: #1b7f79;
            background: #f8fbfd;
        }
        .kpi-card.highlight .value {
            color: #0c5d58;
        }
        .kpi-card.highlight::before {
            background: #1b7f79;
            height: 4px;
        }

        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        .chart-card {
            background: #fff;
            border: 1px solid #dce5ee;
            border-radius: 12px;
            padding: 20px 24px;
            box-shadow: 0 2px 8px rgba(21,49,78,0.04);
        }
        .chart-card .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .chart-card .chart-header h3 {
            margin: 0;
            font-size: 15px;
            color: #16324f;
            font-weight: 700;
        }
        .chart-card .chart-header .badge-count {
            background: #e8f3f2;
            color: #0c625e;
            padding: 2px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }
        .chart-card .chart-container {
            min-height: 180px;
        }

        /* Progress Bar */
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #eef2f7;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-bar .fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.8s ease;
        }
        .progress-bar .fill.permanent { background: linear-gradient(90deg, #1b7f79, #2a9d8f); }
        .progress-bar .fill.contract { background: linear-gradient(90deg, #397a9f, #5a9fc4); }
        .progress-bar .fill.daily { background: linear-gradient(90deg, #d38b2a, #e9a84a); }

        .bar-item {
            margin-bottom: 12px;
        }
        .bar-item:last-child {
            margin-bottom: 0;
        }
        .bar-item .bar-label {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 4px;
        }
        .bar-item .bar-label .name {
            font-weight: 600;
            color: #16324f;
        }
        .bar-item .bar-label .value {
            font-weight: 700;
            color: #16324f;
        }

        /* Pie Chart */
        .pie-container {
            display: flex;
            align-items: center;
            gap: 30px;
            justify-content: center;
            padding: 10px 0;
            flex-wrap: wrap;
        }
        .pie-svg {
            width: 160px;
            height: 160px;
        }
        .pie-legend {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .pie-legend .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }
        .pie-legend .legend-item .color-dot {
            width: 14px;
            height: 14px;
            border-radius: 4px;
            flex-shrink: 0;
        }
        .pie-legend .legend-item .label-text {
            color: #16324f;
        }
        .pie-legend .legend-item .value-text {
            font-weight: 700;
            color: #16324f;
            margin-left: auto;
        }
        .pie-legend .legend-total {
            border-top: 1px solid #dce5ee;
            padding-top: 8px;
            margin-top: 4px;
            font-weight: 700;
            color: #16324f;
            display: flex;
            justify-content: space-between;
        }

        /* Table Section */
        .table-card {
            background: #fff;
            border: 1px solid #dce5ee;
            border-radius: 12px;
            padding: 20px 24px;
            box-shadow: 0 2px 8px rgba(21,49,78,0.04);
        }
        .table-card .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .table-card .table-header h3 {
            margin: 0;
            font-size: 15px;
            color: #16324f;
            font-weight: 700;
        }
        .table-card .table-header .records-count {
            font-size: 12px;
            color: var(--muted);
            background: #f8fbfd;
            padding: 4px 12px;
            border-radius: 999px;
        }

        .table-wrap {
            overflow-x: auto;
        }
        .table-wrap table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .table-wrap th {
            background: #f8fbfd;
            color: #34495e;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 12px 14px;
            text-align: left;
            border-bottom: 2px solid #dce5ee;
            font-weight: 700;
        }
        .table-wrap th.num {
            text-align: right;
        }
        .table-wrap td {
            padding: 12px 14px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
        }
        .table-wrap td.num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        .table-wrap tr:last-child td {
            border-bottom: none;
        }
        .table-wrap tr:hover td {
            background: #f8fbfd;
        }
        .table-wrap .grand-total {
            background: #dfeeea !important;
            font-weight: 800;
        }
        .table-wrap .grand-total td {
            border-top: 2px solid #1b7f79;
            padding: 14px;
        }
        .table-wrap .grand-total .value {
            color: #0c5d58;
        }
        .badge-status {
            display: inline-block;
            padding: 2px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-status.active {
            background: #dcf5e7;
            color: #12643c;
        }
        .badge-status.inactive {
            background: #eee;
            color: #666;
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
            padding: 16px 20px;
            background: #f8fbfd;
            border-radius: 10px;
            border: 1px solid #dce5ee;
            align-items: center;
        }
        .quick-actions .label {
            font-weight: 600;
            color: #16324f;
            margin-right: 8px;
            font-size: 13px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: #627386;
        }
        .empty-state .icon {
            font-size: 40px;
            margin-bottom: 8px;
        }
        .empty-state h4 {
            margin: 0 0 4px;
            color: #24384c;
        }
        .empty-state p {
            margin: 0;
            font-size: 13px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 768px) {
            .filter-bar form {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-bar .field {
                min-width: auto;
                width: 100%;
            }
            .filter-bar .actions {
                flex-direction: column;
            }
            .filter-bar .actions button,
            .filter-bar .actions a {
                width: 100%;
                text-align: center;
            }
            .kpi-grid {
                grid-template-columns: 1fr 1fr;
            }
            .dashboard-header {
                flex-direction: column;
                align-items: start;
                gap: 10px;
            }
            .pie-container {
                flex-direction: column;
                gap: 16px;
            }
        }
        @media (max-width: 480px) {
            .kpi-grid {
                grid-template-columns: 1fr;
            }
            .kpi-card .value {
                font-size: 24px;
            }
            .quick-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .quick-actions .label {
                text-align: center;
            }
            .quick-actions a {
                text-align: center;
            }
        }
    </style>

    <!-- ============================================ -->
    <!-- DASHBOARD HEADER -->
    <!-- ============================================ -->
    <div class="dashboard-header">
        <div>
            <h1>HR Manpower Dashboard</h1>
            <div class="subtitle">Permanent, Contract and Daily Labor figures update automatically from saved job-title entries</div>
        </div>
        <div class="date-badge">
            Date: {{ \Carbon\Carbon::parse($selectedDate)->format('F d, Y') }}
        </div>
    </div>

    <!-- ============================================ -->
    <!-- FILTER BAR -->
    <!-- ============================================ -->
    <div class="filter-bar">
        <form method="get" action="{{ route('dashboard') }}">
            <div class="field">
                <label>Report Date</label>
                <select name="date">
                    @foreach($dates as $date)
                        <option value="{{ $date }}" @if($date == $selectedDate) selected @endif>
                            {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Project</label>
                <select name="project">
                    <option value="0">All Projects</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" @if($project->id == $selectedProjectId) selected @endif>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Project Status</label>
                <select name="project_status">
                    <option value="All" @if($selectedProjectStatus == 'All') selected @endif>All Statuses</option>
                    <option value="Active" @if($selectedProjectStatus == 'Active') selected @endif>Active</option>
                    <option value="Inactive" @if($selectedProjectStatus == 'Inactive') selected @endif>Inactive</option>
                </select>
            </div>
            <div class="field">
                <label>Employee Type</label>
                <select name="type">
                    <option value="All" @if($selectedType == 'All') selected @endif>All Types</option>
                    <option value="Permanent" @if($selectedType == 'Permanent') selected @endif>Permanent</option>
                    <option value="Contract" @if($selectedType == 'Contract') selected @endif>Contract</option>
                    <option value="Daily" @if($selectedType == 'Daily') selected @endif>Daily</option>
                </select>
            </div>
            <div class="actions">
                <button type="submit">Update</button>
                <a class="btn light" href="{{ route('dashboard') }}">Reset</a>
                <a class="btn secondary" href="{{ route('reports.dashboard') }}">Full Report</a>
            </div>
        </form>
    </div>

    <!-- ============================================ -->
    <!-- KPI CARDS -->
    <!-- ============================================ -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-top">
                <div>
                    <div class="label">Permanent</div>
                    <div class="value">{{ number_format($permanent) }}</div>
                </div>
                <div class="icon">P</div>
            </div>
            <div class="kpi-footer">
                <span class="change neutral">Current headcount</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-top">
                <div>
                    <div class="label">Contract</div>
                    <div class="value">{{ number_format($contract) }}</div>
                </div>
                <div class="icon">C</div>
            </div>
            <div class="kpi-footer">
                <span class="change neutral">Current headcount</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-top">
                <div>
                    <div class="label">Daily Labor</div>
                    <div class="value">{{ number_format($daily) }}</div>
                </div>
                <div class="icon">D</div>
            </div>
            <div class="kpi-footer">
                <span class="change neutral">Current headcount</span>
            </div>
        </div>

        <div class="kpi-card highlight">
            <div class="kpi-top">
                <div>
                    <div class="label">Total Manpower</div>
                    <div class="value">{{ number_format($grandTotal) }}</div>
                </div>
                <div class="icon">T</div>
            </div>
            <div class="kpi-footer">
                <span class="change neutral">Total headcount</span>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- CHARTS SECTION -->
    <!-- ============================================ -->
    <div class="charts-grid">
        <!-- Pie Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <h3>Employment Status Distribution</h3>
                <span class="badge-count">{{ $selectedDate }}</span>
            </div>
            <div class="chart-container">
                @php
                    $total = $permanent + $contract + $daily;
                    $permanentPct = $total > 0 ? round(($permanent / $total) * 100, 1) : 0;
                    $contractPct = $total > 0 ? round(($contract / $total) * 100, 1) : 0;
                    $dailyPct = $total > 0 ? round(($daily / $total) * 100, 1) : 0;
                @endphp

                @if($total > 0)
                    <div class="pie-container">
                        <div class="pie-svg">
                            <svg viewBox="0 0 100 100" style="width:100%; height:100%; transform:rotate(-90deg);">
                                <circle cx="50" cy="50" r="40" fill="none" stroke="#eef2f7" stroke-width="18"/>
                                @if($permanent > 0)
                                    <circle cx="50" cy="50" r="40" fill="none" stroke="#1b7f79" stroke-width="18"
                                        stroke-dasharray="{{ $permanentPct * 2.51 }} 251"
                                        stroke-dashoffset="0"/>
                                @endif
                                @if($contract > 0)
                                    <circle cx="50" cy="50" r="40" fill="none" stroke="#397a9f" stroke-width="18"
                                        stroke-dasharray="{{ $contractPct * 2.51 }} 251"
                                        stroke-dashoffset="{{ -($permanentPct * 2.51) }}"/>
                                @endif
                                @if($daily > 0)
                                    <circle cx="50" cy="50" r="40" fill="none" stroke="#d38b2a" stroke-width="18"
                                        stroke-dasharray="{{ $dailyPct * 2.51 }} 251"
                                        stroke-dashoffset="{{ -(($permanentPct + $contractPct) * 2.51) }}"/>
                                @endif
                            </svg>
                        </div>
                        <div class="pie-legend">
                            <div class="legend-item">
                                <span class="color-dot" style="background:#1b7f79;"></span>
                                <span class="label-text">Permanent</span>
                                <span class="value-text">{{ number_format($permanent) }} ({{ $permanentPct }}%)</span>
                            </div>
                            <div class="legend-item">
                                <span class="color-dot" style="background:#397a9f;"></span>
                                <span class="label-text">Contract</span>
                                <span class="value-text">{{ number_format($contract) }} ({{ $contractPct }}%)</span>
                            </div>
                            <div class="legend-item">
                                <span class="color-dot" style="background:#d38b2a;"></span>
                                <span class="label-text">Daily Labor</span>
                                <span class="value-text">{{ number_format($daily) }} ({{ $dailyPct }}%)</span>
                            </div>
                            <div class="legend-total">
                                <span>Total</span>
                                <span>{{ number_format($total) }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="empty-state">
                        <div class="icon">-</div>
                        <h4>No data available</h4>
                        <p>No entries found for the selected date.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Bar Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <h3>Manpower by Project</h3>
                <span class="badge-count">{{ $graphProjects->count() }} projects</span>
            </div>
            <div class="chart-container">
                @if($graphProjects->count() > 0)
                    @php
                        $maxTotal = max($graphProjects->pluck('total')->toArray()) ?: 1;
                    @endphp

                    @foreach($graphProjects as $project)
                        @php
                            $width = ($project->total / $maxTotal) * 100;
                            $count = (int) $project->total;
                            $color = $count > 0 ? 'permanent' : 'contract';
                        @endphp
                        <div class="bar-item">
                            <div class="bar-label">
                                <span class="name">{{ $project->name }}</span>
                                <span class="value">{{ number_format($count) }}</span>
                            </div>
                            <div class="progress-bar">
                                <div class="fill {{ $color }}" style="width: {{ max($width, 2) }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="empty-state">
                        <div class="icon">-</div>
                        <h4>No data available</h4>
                        <p>No projects found for the selected date.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- MANAGEMENT REPORT TABLE -->
    <!-- ============================================ -->
    <div class="table-card">
        <div class="table-header">
            <h3>Dynamic Management Report by Project</h3>
            <span class="records-count">{{ $tableData->count() }} projects</span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="min-width:180px;">Project</th>
                        <th style="width:120px;">Status</th>
                        <th class="num" style="width:120px;">Permanent</th>
                        <th class="num" style="width:120px;">Contract</th>
                        <th class="num" style="width:120px;">Daily</th>
                        <th class="num" style="width:120px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalP = 0;
                        $totalC = 0;
                        $totalD = 0;
                    @endphp

                    @forelse($tableData as $row)
                        @php
                            $p = round($row->permanent);
                            $c = round($row->contract);
                            $d = round($row->daily);
                            $total = $p + $c + $d;
                            $totalP += $p;
                            $totalC += $c;
                            $totalD += $d;
                        @endphp
                        <tr>
                            <td><strong>{{ $row->project_name }}</strong></td>
                            <td>
                                <span class="badge-status {{ strtolower($row->project_status) }}">
                                    {{ $row->project_status }}
                                </span>
                            </td>
                            <td class="num">{{ number_format($p) }}</td>
                            <td class="num">{{ number_format($c) }}</td>
                            <td class="num">{{ number_format($d) }}</td>
                            <td class="num"><strong>{{ number_format($total) }}</strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="icon">-</div>
                                    <h4>No data available</h4>
                                    <p>No entries found for the selected filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    @if($tableData->count() > 0)
                        <tr class="grand-total">
                            <td colspan="2"><strong>GRAND TOTAL</strong></td>
                            <td class="num value"><strong>{{ number_format($totalP) }}</strong></td>
                            <td class="num value"><strong>{{ number_format($totalC) }}</strong></td>
                            <td class="num value"><strong>{{ number_format($totalD) }}</strong></td>
                            <td class="num value"><strong>{{ number_format($totalP + $totalC + $totalD) }}</strong></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- QUICK ACTIONS -->
    <!-- ============================================ -->
    <div class="quick-actions">
        <span class="label">Quick Actions:</span>
        <a href="{{ route('daily.index') }}" class="btn light" style="padding:6px 14px; font-size:12px;">Daily Input</a>
        <a href="{{ route('reports.dashboard') }}" class="btn light" style="padding:6px 14px; font-size:12px;">Full Reports</a>
        <a href="{{ route('comparison.index') }}" class="btn light" style="padding:6px 14px; font-size:12px;">Comparison</a>
        <a href="{{ route('reports.export') }}" class="btn light" style="padding:6px 14px; font-size:12px;">Export Report</a>
        <a href="{{ route('import.index') }}" class="btn light" style="padding:6px 14px; font-size:12px;">Import Data</a>
    </div>
@endsection