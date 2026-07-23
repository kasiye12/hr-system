@extends('layout')

@section('title', 'Leave Management | TNT HR')

@section('content')
<style>
    .page-header { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:24px; }
    .page-header h1 { font-size:22px; font-weight:800; color:#111827; margin:0; }
    .page-header p { color:#6b7280; font-size:13px; margin:4px 0 0; }
    
    .quick-actions { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
    .quick-actions a { 
        padding:9px 16px; border-radius:8px; font-weight:600; font-size:12px; text-decoration:none;
        background:#fff; border:1px solid #e5e7eb; color:#374151; transition:all 0.15s;
    }
    .quick-actions a:hover { background:#f9fafb; border-color:#d1d5db; }
    .quick-actions a.primary { background:#4f46e5; color:#fff; border-color:#4f46e5; }
    .quick-actions a.primary:hover { background:#4338ca; }
    
    .leave-table td { vertical-align:middle; }
    .leave-type-badge {
        display:inline-flex; align-items:center; gap:4px;
        padding:4px 10px; border-radius:100px; font-size:11px; font-weight:600;
        background:#eef2ff; color:#4f46e5;
    }
    .status-pending { background:#fffbeb; color:#d97706; }
    .status-approved { background:#ecfdf5; color:#059669; }
    .status-rejected { background:#fef2f2; color:#dc2626; }
    .pay-badge { display:inline-block; padding:3px 8px; border-radius:100px; font-size:10px; font-weight:700; }
    .pay-paid { background:#ecfdf5; color:#059669; }
    .pay-unpaid { background:#fef2f2; color:#dc2626; }
</style>

<div class="page-header">
    <div>
        <h1>🏖️ Leave Management</h1>
        <p>Ethiopian Labor Proclamation 1156/2011 · Pro-rata Daily Accrual</p>
    </div>
    <div style="display:flex; gap:8px;">
        <a href="{{ route('leaves.create') }}" class="btn btn-primary">➕ Apply Leave</a>
        <a href="{{ route('leaves.balances') }}" class="btn btn-outline">📊 Balances</a>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card amber"><div class="stat-value">{{ $stats['pending'] ?? 0 }}</div><div class="stat-label">⏳ Pending</div></div>
    <div class="stat-card purple"><div class="stat-value">{{ $stats['on_leave'] ?? 0 }}</div><div class="stat-label">👤 On Leave Today</div></div>
    <div class="stat-card green"><div class="stat-value">{{ $stats['total'] ?? 0 }}</div><div class="stat-label">📋 Total Requests</div></div>
    <div class="stat-card red"><div class="stat-value">{{ number_format($stats['sick_exhausted'] ?? 0) }}</div><div class="stat-label">🏥 Sick Days Used</div></div>
</div>

<!-- Quick Links -->
<div class="quick-actions">
    <a href="{{ route('leaves.create') }}" class="primary">➕ New Application</a>
    <a href="{{ route('leaves.balances') }}">📊 View Balances</a>
    <a href="{{ route('leaves.calendar') }}">📅 Calendar</a>
    <a href="{{ route('leaves.report') }}">📈 Report</a>
    <a href="{{ route('leaves.import.index') }}">📥 Import</a>
    <a href="{{ route('leaves.carryforward.index') }}">📦 Carry Forward</a>
    @if(auth()->user() && auth()->user()->role === 'admin')
    <a href="{{ route('leaves.settings.index') }}">⚙️ Settings</a>
    @endif
</div>

<!-- Filter -->
<div class="card" style="padding:14px 20px;">
    <form method="get" style="display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
        <div class="form-group"><label>Status</label>
            <select name="status" style="min-width:120px;">
                <option value="">All</option>
                <option value="pending" {{ request('status')=='pending'?'selected':'' }}>⏳ Pending</option>
                <option value="approved" {{ request('status')=='approved'?'selected':'' }}>✅ Approved</option>
                <option value="rejected" {{ request('status')=='rejected'?'selected':'' }}>❌ Rejected</option>
            </select>
        </div>
        <div class="form-group"><label>From</label><input type="date" name="date_from" value="{{ request('date_from') }}" style="min-width:140px;"></div>
        <button type="submit" class="btn btn-primary btn-sm">🔍 Filter</button>
        <a href="{{ route('leaves.index') }}" class="btn btn-outline btn-sm">Clear</a>
    </form>
</div>

<!-- Table -->
<div class="card" style="padding:0; overflow:hidden;">
    <div class="table-wrap" style="border:none; border-radius:0;">
        <table class="leave-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Type</th>
                    <th>Period</th>
                    <th style="text-align:center;">Days</th>
                    <th style="text-align:center;">Pay</th>
                    <th style="text-align:center;">Status</th>
                    <th style="text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaveRequests as $lr)
                <tr>
                    <td>
                        <strong>{{ $lr->applicant->full_name ?? 'N/A' }}</strong>
                        <div style="font-size:11px; color:#6b7280;">{{ $lr->applicant->position->name ?? '' }}</div>
                    </td>
                    <td>
                        <span class="leave-type-badge">
                            @switch($lr->leaveType->code ?? '')
                                @case('annual') 🏖️ @break @case('sick') 🏥 @break
                                @case('maternity') 🤰 @break @case('paternity') 👨‍🍼 @break
                                @default 📋
                            @endswitch
                            {{ $lr->leaveType->name ?? 'N/A' }}
                        </span>
                    </td>
                    <td>
                        <div>{{ $lr->start_date->format('M d, Y') }}</div>
                        <div style="font-size:11px; color:#6b7280;">→ {{ $lr->end_date->format('M d, Y') }}</div>
                    </td>
                    <td style="text-align:center; font-weight:600;">{{ $lr->total_days }}</td>
                    <td style="text-align:center;">
                        <span class="pay-badge {{ $lr->is_paid ? 'pay-paid' : 'pay-unpaid' }}">
                            {{ $lr->is_paid ? 'Paid' : 'Unpaid' }}
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <span class="badge {{ match($lr->status){'approved'=>'badge-success','rejected'=>'badge-danger',default=>'badge-warning'} }}">
                            {{ ucfirst($lr->status) }}
                        </span>
                    </td>
                    <td style="text-align:center;">
                        @if($lr->status === 'pending' && $canApprove)
                            <form method="post" action="{{ route('leaves.approve', $lr->id) }}" style="display:inline;">
                                @csrf
                                <button class="btn btn-success btn-xs">✅</button>
                            </form>
                            <form method="post" action="{{ route('leaves.reject', $lr->id) }}" style="display:inline;">
                                @csrf
                                <input type="hidden" name="rejection_reason" value="Not approved">
                                <button class="btn btn-danger btn-xs">❌</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center; padding:40px; color:#6b7280;">📭 No leave requests</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($leaveRequests->hasPages())
    <div style="padding:14px 20px; border-top:1px solid #e5e7eb;">
        {{ $leaveRequests->links('vendor.pagination.bootstrap-5') }}
    </div>
    @endif
</div>
@endsection
