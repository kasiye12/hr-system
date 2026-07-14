@extends('layouts.app')

@section('title', 'Management Dashboard - TNT HR')

@section('content')
    <h1>📊 HR Manpower Dashboard</h1>
    <div class="subtitle">Permanent, Contract and Daily Labor figures update automatically from saved job-title entries.</div>

    <!-- Filter Form -->
    <div class="card">
        <form class="report-filter" method="get" action="{{ route('reports.dashboard') }}">
            <div class="field">
                <label>From Date</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" required>
            </div>
            <div class="field">
                <label>To Date</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" required>
            </div>
            <div class="field">
                <label>Project</label>
                <select name="project">
                    <option value="0">All Projects</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" @if($projectId == $project->id) selected @endif>
                            {{ $project->name }} ({{ $project->status }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Employment Type</label>
                <select name="employment_type">
                    <option value="All" @if($employmentType == 'All') selected @endif>All</option>
                    <option value="Permanent" @if($employmentType == 'Permanent') selected @endif>Permanent</option>
                    <option value="Contract" @if($employmentType == 'Contract') selected @endif>Contract</option>
                    <option value="Daily" @if($employmentType == 'Daily') selected @endif>Daily</option>
                </select>
            </div>
            <div>
                <button type="submit">🔄 Update Dashboard</button>
                <a class="btn light" href="{{ route('reports.index') }}">📋 Full Reports</a>
            </div>
        </form>
    </div>

    <!-- KPI Cards -->
    <div class="grid kpis" style="margin-top:16px;">
        <div class="card kpi">
            <div class="label">👔 Permanent</div>
            <div class="value">{{ number_format($dashboardData['latest']['permanent']) }}</div>
        </div>
        <div class="card kpi">
            <div class="label">📄 Contract</div>
            <div class="value">{{ number_format($dashboardData['latest']['contract']) }}</div>
        </div>
        <div class="card kpi">
            <div class="label">🔧 Daily Labor</div>
            <div class="value">{{ number_format($dashboardData['latest']['daily']) }}</div>
        </div>
        <div class="card kpi" style="background:#dfeeea; border-color:#1b7f79;">
            <div class="label">🏆 Total Manpower</div>
            <div class="value" style="color:#0c5d58;">{{ number_format($dashboardData['latest']['total']) }}</div>
        </div>
    </div>

    <!-- Management Dashboard Table -->
    <div class="card" style="margin-top:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:16px;">
            <h2 style="margin:0;">📋 Management Dashboard</h2>
            <div style="font-size:12px; color:var(--muted);">
                Generated: {{ now()->format('Y-m-d H:i:s') }}
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Report Date</th>
                        <th class="num">Permanent</th>
                        <th class="num">Contract</th>
                        <th class="num">Daily Labor</th>
                        <th class="num">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dashboardData['data'] as $row)
                        <tr>
                            <td>{{ $row['date'] }}</td>
                            <td class="num">{{ number_format($row['permanent']) }}</td>
                            <td class="num">{{ number_format($row['contract']) }}</td>
                            <td class="num">{{ number_format($row['daily']) }}</td>
                            <td class="num"><strong>{{ number_format($row['total']) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No data available for the selected criteria.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="grand-total-row">
                        <td><strong>Total</strong></td>
                        <td class="num"><strong>{{ number_format($dashboardData['totals']['Permanent']) }}</strong></td>
                        <td class="num"><strong>{{ number_format($dashboardData['totals']['Contract']) }}</strong></td>
                        <td class="num"><strong>{{ number_format($dashboardData['totals']['Daily']) }}</strong></td>
                        <td class="num"><strong>{{ number_format($dashboardData['grand_total']) }}</strong></td>
                    </tr>
                    <tr>
                        <td colspan="5" style="font-size:12px; color:var(--muted); text-align:right; border-bottom:none;">
                            {{ $dashboardData['count'] }} reporting days
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Project & Department Summary -->
    <div class="grid two" style="margin-top:16px;">
        <div class="card">
            <h3>📊 Project Summary</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th class="num">Permanent</th>
                            <th class="num">Contract</th>
                            <th class="num">Daily</th>
                            <th class="num">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($projectSummary as $row)
                            <tr>
                                <td>{{ $row->project }}</td>
                                <td class="num">{{ number_format($row->permanent) }}</td>
                                <td class="num">{{ number_format($row->contract) }}</td>
                                <td class="num">{{ number_format($row->daily) }}</td>
                                <td class="num"><strong>{{ number_format($row->total) }}</strong></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty">No data available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h3>🏢 Department Summary</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Type</th>
                            <th class="num">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departmentSummary as $row)
                            <tr>
                                <td>{{ $row->department }}</td>
                                <td><span class="badge" style="background:#e8f3f2; color:#0c625e;">{{ $row->employment_type }}</span></td>
                                <td class="num"><strong>{{ number_format($row->total) }}</strong></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="empty">No data available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .report-filter {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        .report-filter .field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .report-filter .field label {
            font-size: 12px;
            color: var(--muted);
            font-weight: 700;
        }
        .report-filter .field input,
        .report-filter .field select {
            padding: 8px 12px;
            border: 1px solid #bac8d6;
            border-radius: 6px;
            background: #fff;
            font-size: 13px;
            min-width: 140px;
        }
        .report-filter .field input:focus,
        .report-filter .field select:focus {
            outline: none;
            border-color: #1b7f79;
        }
        .report-filter div {
            display: flex;
            gap: 8px;
        }

        .dashboard-table th {
            position: sticky;
            top: 0;
            background: #eff4f8;
            z-index: 5;
        }
        .dashboard-table .num {
            font-variant-numeric: tabular-nums;
        }
        .dashboard-table tr:hover td {
            background: #f8fbfd;
        }
        .grand-total-row td {
            background: #dfeeea !important;
            color: #0c5d58;
            font-size: 15px;
            padding: 12px 10px;
        }

        @media (max-width: 768px) {
            .report-filter {
                flex-direction: column;
                align-items: stretch;
            }
            .report-filter .field input,
            .report-filter .field select {
                min-width: auto;
                width: 100%;
            }
            .report-filter div {
                flex-direction: column;
            }
            .report-filter div button,
            .report-filter div a {
                width: 100%;
                text-align: center;
            }
        }
    </style>
@endsection