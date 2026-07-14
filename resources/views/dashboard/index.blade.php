@extends('layouts.app')

@section('title', 'Dashboard - TNT HR Reporting')

@section('content')
    <h1>HR Manpower Dashboard</h1>
    <div class="subtitle">Permanent, Contract and Daily Labor figures update automatically from saved job-title entries.</div>

    <div class="card">
        <form class="inline" method="get" action="{{ route('dashboard') }}">
            <div class="field">
                <label>Report Date</label>
                <select name="date">
                    @foreach($dates as $date)
                        <option value="{{ $date }}" @if($date == $selectedDate) selected @endif>{{ $date }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Project</label>
                <select name="project">
                    <option value="0">All Projects</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" @if($project->id == $selectedProjectId) selected @endif>
                            {{ $project->name }} ({{ $project->status }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Project Status</label>
                <select name="project_status">
                    <option value="All" @if($selectedProjectStatus == 'All') selected @endif>All Project Statuses</option>
                    <option value="Active" @if($selectedProjectStatus == 'Active') selected @endif>Active Projects</option>
                    <option value="Inactive" @if($selectedProjectStatus == 'Inactive') selected @endif>Inactive Projects</option>
                </select>
            </div>
            <div class="field">
                <label>Graph Employee Type</label>
                <select name="type">
                    <option value="All" @if($selectedType == 'All') selected @endif>All Employee Types</option>
                    <option value="Permanent" @if($selectedType == 'Permanent') selected @endif>Permanent</option>
                    <option value="Contract" @if($selectedType == 'Contract') selected @endif>Contract</option>
                    <option value="Daily" @if($selectedType == 'Daily') selected @endif>Daily Labor</option>
                </select>
            </div>
            <button type="submit">Update Dashboard</button>
            <a class="btn light" href="{{ route('reports.index') }}">Prepare Excel Report</a>
        </form>
    </div>

    <div class="grid kpis" style="margin-top:16px">
        <div class="card kpi">
            <div class="label">Permanent</div>
            <div class="value">{{ number_format($permanent) }}</div>
        </div>
        <div class="card kpi">
            <div class="label">Contract</div>
            <div class="value">{{ number_format($contract) }}</div>
        </div>
        <div class="card kpi">
            <div class="label">Daily Labor</div>
            <div class="value">{{ number_format($daily) }}</div>
        </div>
        <div class="card kpi">
            <div class="label">Total Manpower</div>
            <div class="value">{{ number_format($grandTotal) }}</div>
        </div>
    </div>

    <div class="grid two">
        <section class="card">
            <h2>Employment Status Pie Chart · {{ $selectedDate }}</h2>
            <div style="text-align:center;padding:20px;">
                <div style="display:inline-block;text-align:left;">
                    <div><span style="display:inline-block;width:16px;height:16px;background:#1b7f79;border-radius:4px;margin-right:8px;"></span> Permanent: {{ number_format($permanent) }}</div>
                    <div><span style="display:inline-block;width:16px;height:16px;background:#397a9f;border-radius:4px;margin-right:8px;"></span> Contract: {{ number_format($contract) }}</div>
                    <div><span style="display:inline-block;width:16px;height:16px;background:#d38b2a;border-radius:4px;margin-right:8px;"></span> Daily Labor: {{ number_format($daily) }}</div>
                    <div style="margin-top:8px;font-weight:700;">Total: {{ number_format($grandTotal) }}</div>
                </div>
            </div>
        </section>
        <section class="card">
            <h2>Manpower Trend</h2>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="num">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($trendData as $row)
                            <tr>
                                <td>{{ $row->report_date }}</td>
                                <td class="num">{{ number_format($row->total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="empty">No data available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="card" style="margin-top:16px">
        <h2>Manpower by Project · {{ $selectedDate }}</h2>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Project</th>
                        <th class="num">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($graphProjects as $project)
                        <tr>
                            <td>{{ $project->name }}</td>
                            <td class="num">{{ number_format($project->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="empty">No data available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:16px">
        <h2>Dynamic Management Report by Project and Status</h2>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Status</th>
                        <th class="num">Permanent</th>
                        <th class="num">Contract</th>
                        <th class="num">Daily Labor</th>
                        <th class="num">Total</th>
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
                            <td>{{ $row->project_name }}</td>
                            <td><span class="badge {{ strtolower($row->project_status) }}">{{ $row->project_status }}</span></td>
                            <td class="num">{{ number_format($p) }}</td>
                            <td class="num">{{ number_format($c) }}</td>
                            <td class="num">{{ number_format($d) }}</td>
                            <td class="num"><b>{{ number_format($total) }}</b></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">No report data is available for the selected filters.</td></tr>
                    @endforelse
                    @if($tableData->count() > 0)
                        <tr class="grand-total-row">
                            <td>All Selected Projects</td>
                            <td></td>
                            <td class="num">{{ number_format($totalP) }}</td>
                            <td class="num">{{ number_format($totalC) }}</td>
                            <td class="num">{{ number_format($totalD) }}</td>
                            <td class="num">{{ number_format($totalP + $totalC + $totalD) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
@endsection