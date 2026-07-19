@extends('layout')

@section('title', 'Leave Calendar | TNT HR')

@section('content')
<style>
    .cal-card { background: #fff; border-radius: 16px; border: 1px solid #dce5ee; overflow: hidden; }
    .cal-header { background: linear-gradient(135deg, #16324f, #1b7f79); padding: 24px 28px; color: #fff; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .cal-header h2 { margin: 0; font-size: 20px; }
    .cal-legend { display: flex; gap: 16px; flex-wrap: wrap; }
    .cal-legend span { font-size: 12px; padding: 4px 12px; border-radius: 12px; font-weight: 600; }
    .legend-active { background: #dcf5e7; color: #18794e; }
    .legend-upcoming { background: #dff3ff; color: #0d6289; }
    .legend-completed { background: #f0f0f0; color: #666; }
    .eth-date { font-size: 10px; color: #627386; display: block; }
</style>

<div class="cal-card">
    <div class="cal-header">
        <div>
            <h2>📅 Leave Calendar</h2>
            <p style="margin:4px 0 0; opacity:0.8;">Approved Leaves · Dual Calendar Display (GC/EC)</p>
        </div>
        <div class="cal-legend">
            <span class="legend-active">🟢 Active</span>
            <span class="legend-upcoming">🔵 Upcoming</span>
            <span class="legend-completed">⚫ Completed</span>
        </div>
    </div>
    <div style="overflow-x:auto; padding: 20px;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:14px 16px; font-size:11px; text-transform:uppercase; color:#627386;">Employee</th>
                    <th style="padding:14px 16px; font-size:11px; text-transform:uppercase; color:#627386;">Leave Type</th>
                    <th style="padding:14px 16px; font-size:11px; text-transform:uppercase; color:#627386;">Start Date</th>
                    <th style="padding:14px 16px; font-size:11px; text-transform:uppercase; color:#627386;">End Date</th>
                    <th style="padding:14px 16px; font-size:11px; text-transform:uppercase; color:#627386; text-align:center;">Days</th>
                    <th style="padding:14px 16px; font-size:11px; text-transform:uppercase; color:#627386; text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaves as $l)
                @php
                    $now = now();
                    $isActive = $l->start_date <= $now && $l->end_date >= $now;
                    $isPast = $l->end_date < $now;
                @endphp
                <tr style="border-bottom:1px solid #f0f4f8; {{ $isActive ? 'background:#f0fdf4;' : '' }}">
                    <td style="padding:14px 16px;">
                        <strong>{{ $l->applicant->full_name ?? 'N/A' }}</strong>
                        <div style="font-size:11px; color:#627386;">{{ $l->applicant->position->name ?? '' }}</div>
                    </td>
                    <td style="padding:14px 16px;">
                        <span style="display:inline-block; padding:5px 12px; border-radius:12px; font-size:11px; font-weight:700; background:#e8f3f2; color:#0c625e;">
                            @switch($l->leaveType->code ?? '')
                                @case('annual') 🏖️ Annual @break
                                @case('sick') 🏥 Sick @break
                                @case('maternity') 🤰 Maternity @break
                                @case('paternity') 👨‍🍼 Paternity @break
                                @case('marriage') 💒 Marriage @break
                                @case('bereavement') 🕊️ Bereavement @break
                                @default 📋 {{ $l->leaveType->name ?? 'N/A' }}
                            @endswitch
                        </span>
                    </td>
                    <td style="padding:14px 16px;">
                        <strong>{{ $l->start_date->format('M d, Y') }}</strong>
                        <span class="eth-date">🇪🇹 {{ \App\Helpers\EthiopianDateHelper::ethDate($l->start_date) }}</span>
                    </td>
                    <td style="padding:14px 16px;">
                        <strong>{{ $l->end_date->format('M d, Y') }}</strong>
                        <span class="eth-date">🇪🇹 {{ \App\Helpers\EthiopianDateHelper::ethDate($l->end_date) }}</span>
                    </td>
                    <td style="padding:14px 16px; text-align:center; font-weight:700;">{{ $l->total_days }}</td>
                    <td style="padding:14px 16px; text-align:center;">
                        @if($isActive)
                            <span style="background:#dcf5e7; color:#18794e; padding:5px 12px; border-radius:12px; font-size:11px; font-weight:700;">🟢 Active</span>
                        @elseif($isPast)
                            <span style="background:#f0f0f0; color:#666; padding:5px 12px; border-radius:12px; font-size:11px;">Completed</span>
                        @else
                            <span style="background:#dff3ff; color:#0d6289; padding:5px 12px; border-radius:12px; font-size:11px; font-weight:700;">🔵 Upcoming</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:60px 20px; color:#627386;">
                        <div style="font-size:48px; margin-bottom:12px;">📅</div>
                        <div style="font-weight:600; font-size:15px;">No approved leaves found</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
