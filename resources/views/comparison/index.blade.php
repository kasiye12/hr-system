@extends('layouts.app')

@section('title', 'Flexible Comparison - TNT HR Reporting')

@section('content')
    <h1>Flexible Comparison</h1>
    <div class="subtitle">Compare any two reporting dates by project.</div>

    <div class="card">
        <form class="inline" method="get" action="{{ route('comparison.index') }}">
            <div class="field">
                <label>Previous Date</label>
                <select name="date1">
                    @foreach($dates as $date)
                        <option value="{{ $date }}" @if($date == $date1) selected @endif>{{ $date }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Current Date</label>
                <select name="date2">
                    @foreach($dates as $date)
                        <option value="{{ $date }}" @if($date == $date2) selected @endif>{{ $date }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit">Compare</button>
        </form>
    </div>

    <div class="grid kpis" style="margin-top:16px">
        <div class="card kpi">
            <div class="label">Previous Total</div>
            <div class="value">{{ number_format($total1) }}</div>
        </div>
        <div class="card kpi">
            <div class="label">Current Total</div>
            <div class="value">{{ number_format($total2) }}</div>
        </div>
        <div class="card kpi">
            <div class="label">Net Change</div>
            <div class="value">{{ number_format($diffTotal) }}</div>
        </div>
        <div class="card kpi">
            <div class="label">% Change</div>
            <div class="value">{{ number_format($pctTotal, 2) }}%</div>
        </div>
    </div>

    <div class="card">
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Project</th>
                        <th class="num">{{ $date1 }}</th>
                        <th class="num">{{ $date2 }}</th>
                        <th class="num">Difference</th>
                        <th class="num">% Change</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($comparisonData as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="num">{{ number_format($row['prev']) }}</td>
                            <td class="num">{{ number_format($row['current']) }}</td>
                            <td class="num">{{ number_format($row['diff']) }}</td>
                            <td class="num">{{ number_format($row['pct'], 2) }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No data available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection