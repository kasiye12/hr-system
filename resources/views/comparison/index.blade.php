@extends('layouts.app')

@section('title', 'Flexible Comparison - TNT HR')

@section('content')
    <style>
        .comparison-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .comparison-header h1 {
            margin: 0;
        }
        .comparison-header .subtitle {
            color: #627386;
            font-size: 14px;
        }

        /* Date Selector */
        .date-selector {
            background: #fff;
            border: 1px solid #dce5ee;
            border-radius: 10px;
            padding: 20px 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 7px rgba(21,49,78,0.04);
        }
        .date-selector .form-group {
            display: flex;
            gap: 20px;
            align-items: end;
            flex-wrap: wrap;
        }
        .date-selector .form-group .field {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
            min-width: 180px;
        }
        .date-selector .form-group .field label {
            font-size: 12px;
            color: #627386;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .date-selector .form-group .field select {
            padding: 10px 14px;
            border: 1px solid #bac8d6;
            border-radius: 6px;
            font-size: 14px;
            background: #fff;
            width: 100%;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23627386' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            cursor: pointer;
        }
        .date-selector .form-group .field select:focus {
            outline: none;
            border-color: #1b7f79;
            box-shadow: 0 0 0 3px rgba(27, 127, 121, 0.1);
        }
        .date-selector .form-group .field select:hover {
            border-color: #1b7f79;
        }

        .btn-compare {
            padding: 10px 28px;
            background: #1b7f79;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
            white-space: nowrap;
        }
        .btn-compare:hover {
            background: #156b66;
        }
        .btn-reset {
            padding: 10px 20px;
            background: #e9eff5;
            color: #23384d;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            transition: background 0.2s;
            white-space: nowrap;
        }
        .btn-reset:hover {
            background: #dce5ee;
            text-decoration: none;
            color: #23384d;
        }
        .date-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            padding-bottom: 2px;
        }

        /* KPI Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .kpi-card {
            background: #fff;
            border: 1px solid #dce5ee;
            border-radius: 10px;
            padding: 16px 20px;
            text-align: center;
            transition: all 0.2s;
        }
        .kpi-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        .kpi-card .label {
            font-size: 11px;
            color: #627386;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-weight: 600;
        }
        .kpi-card .value {
            font-size: 26px;
            font-weight: 700;
            color: #16324f;
            margin-top: 4px;
        }
        .kpi-card .change {
            font-size: 13px;
            font-weight: 600;
            margin-top: 2px;
        }
        .kpi-card .change.positive { color: #18794e; }
        .kpi-card .change.negative { color: #a61b1b; }
        .kpi-card .change.neutral { color: #627386; }
        .kpi-card.highlight {
            background: #dfeeea;
            border-color: #1b7f79;
        }
        .kpi-card.highlight .value {
            color: #0c5d58;
        }

        /* Comparison Table */
        .table-wrapper {
            overflow-x: auto;
            margin-top: 16px;
        }
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .comparison-table th {
            background: #eff4f8;
            color: #34495e;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 10px 14px;
            text-align: left;
            border-bottom: 2px solid #dce5ee;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .comparison-table th.num {
            text-align: right;
        }
        .comparison-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
        }
        .comparison-table td.num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        .comparison-table tr:hover td {
            background: #f8fbfd;
        }
        .comparison-table .project-name {
            font-weight: 600;
            color: #16324f;
        }
        .comparison-table .total-row {
            background: #f8fbfd;
            font-weight: 700;
        }
        .comparison-table .total-row td {
            border-top: 2px solid #dce5ee;
            padding: 12px 14px;
        }
        .comparison-table .grand-total-row {
            background: #dfeeea;
            font-weight: 800;
            font-size: 14px;
        }
        .comparison-table .grand-total-row td {
            padding: 14px 14px;
            border-top: 2px solid #1b7f79;
        }
        .comparison-table .grand-total-row .value {
            color: #0c5d58;
        }

        /* Status Badges */
        .badge-status {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-status.increase {
            background: #dcf5e7;
            color: #12643c;
        }
        .badge-status.decrease {
            background: #ffeded;
            color: #a61b1b;
        }
        .badge-status.same {
            background: #eee;
            color: #666;
        }

        /* Progress Bar */
        .progress-bar {
            width: 100%;
            height: 6px;
            background: #eef2f7;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 4px;
        }
        .progress-bar .fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.6s ease;
        }
        .progress-bar .fill.positive { background: #18794e; }
        .progress-bar .fill.negative { background: #a61b1b; }

        /* Summary Cards */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .summary-item {
            background: #f8fbfd;
            border-radius: 8px;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .summary-item .label {
            font-size: 12px;
            color: #627386;
            font-weight: 600;
        }
        .summary-item .value {
            font-size: 16px;
            font-weight: 700;
            color: #16324f;
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
            padding: 16px 20px;
            background: #f8fbfd;
            border-radius: 8px;
            border: 1px solid #dce5ee;
            align-items: center;
        }
        .quick-actions .label {
            font-weight: 600;
            color: #16324f;
            margin-right: 8px;
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .date-selector .form-group {
                flex-direction: column;
                align-items: stretch;
            }
            .date-selector .form-group .field {
                min-width: auto;
                width: 100%;
            }
            .date-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .date-actions .btn-compare,
            .date-actions .btn-reset {
                width: 100%;
                text-align: center;
            }
            .kpi-grid {
                grid-template-columns: 1fr 1fr;
            }
            .summary-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 480px) {
            .kpi-grid {
                grid-template-columns: 1fr;
            }
            .summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <!-- ============================================ -->
    <!-- HEADER -->
    <!-- ============================================ -->
    <div class="comparison-header">
        <div>
            <h1>📊 Flexible Comparison</h1>
            <div class="subtitle">Compare any two reporting dates by project with detailed analysis</div>
        </div>
        <div>
            <span class="badge" style="background:#e8f3f2; color:#0c625e; font-size:12px; padding:4px 12px;">
                📌 {{ count($comparisonData) }} Projects
            </span>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- DATE SELECTOR -->
    <!-- ============================================ -->
    <div class="date-selector">
        <form method="get" action="{{ route('comparison.index') }}">
            <div class="form-group">
                <div class="field">
                    <label>📅 Previous Date</label>
                    <select name="date1" required>
                        @foreach($dates as $date)
                            <option value="{{ $date }}" @if($date == $date1) selected @endif>{{ $date }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>📅 Current Date</label>
                    <select name="date2" required>
                        @foreach($dates as $date)
                            <option value="{{ $date }}" @if($date == $date2) selected @endif>{{ $date }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="date-actions">
                    <button type="submit" class="btn-compare">🔄 Compare</button>
                    <a href="{{ route('comparison.index') }}" class="btn-reset">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <!-- ============================================ -->
    <!-- KPI CARDS -->
    <!-- ============================================ -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="label">💰 Previous Total</div>
            <div class="value">{{ number_format($total1) }}</div>
        </div>
        <div class="kpi-card highlight">
            <div class="label">💰 Current Total</div>
            <div class="value">{{ number_format($total2) }}</div>
        </div>
        <div class="kpi-card">
            <div class="label">📈 Net Change</div>
            <div class="value" style="color: {{ $diffTotal > 0 ? '#18794e' : ($diffTotal < 0 ? '#a61b1b' : '#627386') }}">
                {{ $diffTotal > 0 ? '+' : '' }}{{ number_format($diffTotal) }}
            </div>
            <div class="change {{ $pctTotal > 0 ? 'positive' : ($pctTotal < 0 ? 'negative' : 'neutral') }}">
                {{ $pctTotal > 0 ? '▲' : ($pctTotal < 0 ? '▼' : '•') }}
                {{ number_format(abs($pctTotal), 2) }}%
            </div>
        </div>
        <div class="kpi-card">
            <div class="label">🏆 Highest Current</div>
            <div class="value">{{ number_format($summary['max']) }}</div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- SUMMARY STATS -->
    <!-- ============================================ -->
    <div class="summary-grid">
        <div class="summary-item">
            <span class="label">📈 Increased</span>
            <span class="value" style="color:#18794e;">{{ $summary['increased'] }}</span>
        </div>
        <div class="summary-item">
            <span class="label">📉 Decreased</span>
            <span class="value" style="color:#a61b1b;">{{ $summary['decreased'] }}</span>
        </div>
        <div class="summary-item">
            <span class="label">➖ Unchanged</span>
            <span class="value" style="color:#627386;">{{ $summary['unchanged'] }}</span>
        </div>
        <div class="summary-item">
            <span class="label">📊 Average (Current)</span>
            <span class="value">{{ number_format($summary['avg']) }}</span>
        </div>
        <div class="summary-item">
            <span class="label">📊 Average (Previous)</span>
            <span class="value">{{ number_format($summary['avg_prev']) }}</span>
        </div>
        <div class="summary-item">
            <span class="label">🏆 Highest Previous</span>
            <span class="value">{{ number_format($summary['max_prev']) }}</span>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- COMPARISON TABLE -->
    <!-- ============================================ -->
    <div class="card" style="margin-top:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:12px;">
            <h2 style="margin:0;">📋 Project Comparison Details</h2>
            <span style="font-size:12px; color:#627386;">
                📅 {{ $date1 }} <span style="color:#1b7f79;">⟷</span> {{ $date2 }}
            </span>
        </div>

        @if(count($comparisonData) > 0)
            <div class="table-wrapper">
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th style="min-width:180px;">Project</th>
                            <th class="num" style="min-width:100px;">{{ $date1 }}</th>
                            <th class="num" style="min-width:100px;">{{ $date2 }}</th>
                            <th class="num" style="min-width:120px;">Difference</th>
                            <th class="num" style="min-width:100px;">% Change</th>
                            <th style="min-width:150px;">Trend</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comparisonData as $row)
                            @php
                                $isIncrease = $row['diff'] > 0;
                                $isDecrease = $row['diff'] < 0;
                                $pctDisplay = $row['pct'];
                                $maxTotal = max($total1, $total2);
                            @endphp
                            <tr>
                                <td class="project-name">{{ $row['name'] }}</td>
                                <td class="num">{{ number_format($row['prev']) }}</td>
                                <td class="num">{{ number_format($row['current']) }}</td>
                                <td class="num" style="color: {{ $isIncrease ? '#18794e' : ($isDecrease ? '#a61b1b' : '#627386') }}; font-weight:600;">
                                    {{ $isIncrease ? '+' : '' }}{{ number_format($row['diff']) }}
                                </td>
                                <td class="num" style="color: {{ $isIncrease ? '#18794e' : ($isDecrease ? '#a61b1b' : '#627386') }};">
                                    {{ $row['pct'] > 0 ? '+' : '' }}{{ number_format($pctDisplay, 2) }}%
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <span class="badge-status {{ $row['status'] }}">
                                            {{ $row['status'] == 'increase' ? '▲ Increased' : ($row['status'] == 'decrease' ? '▼ Decreased' : '• Same') }}
                                        </span>
                                        <div class="progress-bar" style="width:80px;">
                                            @php
                                                $barWidth = $maxTotal > 0 ? ($row['current'] / $maxTotal * 100) : 0;
                                                $barColor = $isIncrease ? '#18794e' : ($isDecrease ? '#a61b1b' : '#627386');
                                            @endphp
                                            <div class="fill" style="width: {{ min($barWidth, 100) }}%; background: {{ $barColor }};"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td><strong>Total</strong></td>
                            <td class="num"><strong>{{ number_format($total1) }}</strong></td>
                            <td class="num"><strong>{{ number_format($total2) }}</strong></td>
                            <td class="num" style="color: {{ $diffTotal > 0 ? '#18794e' : ($diffTotal < 0 ? '#a61b1b' : '#627386') }};">
                                <strong>{{ $diffTotal > 0 ? '+' : '' }}{{ number_format($diffTotal) }}</strong>
                            </td>
                            <td class="num" style="color: {{ $pctTotal > 0 ? '#18794e' : ($pctTotal < 0 ? '#a61b1b' : '#627386') }};">
                                <strong>{{ $pctTotal > 0 ? '+' : '' }}{{ number_format($pctTotal, 2) }}%</strong>
                            </td>
                            <td>
                                <span class="badge-status {{ $diffTotal > 0 ? 'increase' : ($diffTotal < 0 ? 'decrease' : 'same') }}">
                                    {{ $diffTotal > 0 ? '▲ Overall Increase' : ($diffTotal < 0 ? '▼ Overall Decrease' : '• No Change') }}
                                </span>
                            </td>
                        </tr>
                        <tr class="grand-total-row">
                            <td colspan="6" style="text-align:center; font-size:13px;">
                                📊 Analysis Period: <strong>{{ $date1 }}</strong> to <strong>{{ $date2 }}</strong>
                                &nbsp;|&nbsp; Total Change: <strong style="color: {{ $diffTotal > 0 ? '#18794e' : ($diffTotal < 0 ? '#a61b1b' : '#627386') }}">
                                    {{ $diffTotal > 0 ? '+' : '' }}{{ number_format($diffTotal) }}
                                </strong>
                                &nbsp;|&nbsp; {{ count($comparisonData) }} Projects Analyzed
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="empty-state" style="padding:40px; text-align:center;">
                <div style="font-size:48px; margin-bottom:12px;">📭</div>
                <h4 style="margin:0; color:#24384c;">No Data Available</h4>
                <p style="color:#627386; font-size:14px;">No comparison data found for the selected dates.</p>
                <a href="{{ route('daily.index') }}" class="btn" style="margin-top:12px;">Go to Daily Input</a>
            </div>
        @endif
    </div>

    <!-- ============================================ -->
    <!-- QUICK ACTIONS -->
    <!-- ============================================ -->
    <div class="quick-actions">
        <span class="label">📌 Quick Actions:</span>
        <a href="{{ route('dashboard') }}" class="btn light" style="padding:4px 12px; font-size:12px;">📊 Dashboard</a>
        <a href="{{ route('reports.dashboard') }}" class="btn light" style="padding:4px 12px; font-size:12px;">📈 Reports</a>
        <a href="{{ route('daily.index') }}" class="btn light" style="padding:4px 12px; font-size:12px;">📝 Daily Input</a>
        <a href="{{ route('reports.export') }}" class="btn light" style="padding:4px 12px; font-size:12px;">📥 Export Report</a>
    </div>

    <!-- ============================================ -->
    <!-- JAVASCRIPT -->
    <!-- ============================================ -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-submit form when dates change
            const dateSelects = document.querySelectorAll('.date-selector select');
            dateSelects.forEach(function(select) {
                select.addEventListener('change', function() {
                    this.closest('form').submit();
                });
            });
        });
    </script>
@endsection