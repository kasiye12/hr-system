@extends('layout')

@section('title', 'Leave Management | Ethiopian Labor Law 1156/2019')

@section('content')
<style>
:root {
    --eth-green: #1b7f79;
    --eth-gold: #d38b2a;
    --eth-red: #a61b1b;
    --eth-navy: #16324f;
    --eth-gray: #627386;
    --eth-light: #f4f7fb;
    --eth-card: #ffffff;
    --eth-border: #dce5ee;
}
.eth-header {
    background: linear-gradient(135deg, var(--eth-navy) 0%, #0f2235 100%);
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 24px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}
.eth-header h1 { margin: 0; font-size: 24px; font-weight: 700; }
.eth-header .eth-subtitle { font-size: 13px; opacity: 0.8; margin-top: 4px; }
.eth-header .eth-badge {
    background: rgba(255,255,255,0.15);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.eth-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.eth-stat {
    background: #fff;
    border-radius: 14px;
    padding: 20px 24px;
    border: 1px solid var(--eth-border);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform 0.2s, box-shadow 0.2s;
}
.eth-stat:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
.eth-stat-icon {
    width: 50px; height: 50px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
}
.eth-stat-icon.pending { background: #fff5e5; color: #a45100; }
.eth-stat-icon.onleave { background: #dff3ff; color: #0d6289; }
.eth-stat-icon.total { background: #e8f3f2; color: #0c625e; }
.eth-stat-icon.sick { background: #ffeded; color: #a61b1b; }
.eth-stat-value { font-size: 26px; font-weight: 700; color: var(--eth-navy); }
.eth-stat-label { font-size: 12px; color: var(--eth-gray); text-transform: uppercase; letter-spacing: 0.05em; }
.eth-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid var(--eth-border);
    overflow: hidden;
}
.eth-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--eth-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}
.eth-card-header h3 { margin: 0; font-size: 16px; }
.eth-card-body { padding: 20px 24px; }
.eth-table { width: 100%; border-collapse: collapse; }
.eth-table th {
    background: #f8fafc;
    padding: 12px 16px;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--eth-gray);
    font-weight: 700;
    text-align: left;
    border-bottom: 2px solid var(--eth-border);
}
.eth-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f0f4f8;
    font-size: 13px;
}
.eth-table tr:hover td { background: #f8fbfd; }
.eth-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}
.eth-badge-success { background: #dcf5e7; color: #18794e; }
.eth-badge-warning { background: #fff5e5; color: #a45100; }
.eth-badge-danger { background: #ffeded; color: #a61b1b; }
.eth-badge-info { background: #e8f3f2; color: #0c625e; }
.eth-badge-muted { background: #f0f0f0; color: #666; }
.eth-law-ref {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    background: #e8f3f2;
    color: #0c625e;
    margin-left: 6px;
}
.eth-actions { display: flex; gap: 6px; }
.eth-btn {
    padding: 8px 18px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.eth-btn-primary { background: var(--eth-green); color: #fff; }
.eth-btn-primary:hover { background: #156b66; }
.eth-btn-secondary { background: #e9eff5; color: #23384d; }
.eth-btn-secondary:hover { background: #d5dde6; }
.eth-btn-sm { padding: 5px 10px; font-size: 11px; }
.eth-btn-xs { padding: 3px 8px; font-size: 10px; border-radius: 6px; }
@media (max-width: 768px) {
    .eth-stats { grid-template-columns: 1fr 1fr; }
    .eth-header { flex-direction: column; text-align: center; }
}
@media (max-width: 480px) {
    .eth-stats { grid-template-columns: 1fr; }
}
</style>

<!-- Header -->
<div class="eth-header">
    <div>
        <h1>🇪🇹 Leave Management System</h1>
        <div class="eth-subtitle">Fully Compliant with Ethiopian Labor Proclamation No. 1156/2019</div>
    </div>
    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <span class="eth-badge">📜 Art. 27-36</span>
        <span class="eth-badge">🏖️ Anniversary-Based</span>
        <a href="{{ route('leaves.create') }}" class="eth-btn eth-btn-primary">➕ Apply Leave</a>
    </div>
</div>

<!-- Stats -->
<div class="eth-stats">
    <div class="eth-stat">
        <div class="eth-stat-icon pending">⏳</div>
        <div><div class="eth-stat-value">{{ $stats['pending'] ?? 0 }}</div><div class="eth-stat-label">Pending Approvals</div></div>
    </div>
    <div class="eth-stat">
        <div class="eth-stat-icon onleave">👤</div>
        <div><div class="eth-stat-value">{{ $stats['on_leave'] ?? 0 }}</div><div class="eth-stat-label">On Leave Today</div></div>
    </div>
    <div class="eth-stat">
        <div class="eth-stat-icon total">📋</div>
        <div><div class="eth-stat-value">{{ $stats['total'] ?? 0 }}</div><div class="eth-stat-label">Total Requests</div></div>
    </div>
    <div class="eth-stat">
        <div class="eth-stat-icon sick">🏥</div>
        <div><div class="eth-stat-value">{{ number_format($stats['sick_exhausted'] ?? 0) }}</div><div class="eth-stat-label">Sick Leave Days Used</div></div>
    </div>
</div>

<!-- Quick Links -->
<div style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap;">
    <a href="{{ route('leaves.balances') }}" class="eth-btn eth-btn-secondary">📊 View Balances</a>
    <a href="{{ route('leaves.calendar') }}" class="eth-btn eth-btn-secondary">📅 Leave Calendar</a>
    <a href="{{ route('leaves.create') }}" class="eth-btn eth-btn-secondary">➕ New Application</a>
    <a href="{{ route('leaves.report') }}" class="eth-btn eth-btn-secondary">📈 Report</a>
    <a href="{{ route('leaves.import.index') }}" class="eth-btn eth-btn-secondary">📥 Import</a>
            <a href="{{ route('leaves.carryforward.index') }}" class="eth-btn eth-btn-secondary">📦 Carry Forward</a>
    @if(auth()->user() && auth()->user()->role === 'admin')
        <a href="{{ route('leaves.settings.index') }}" class="eth-btn eth-btn-secondary">⚙️ Settings</a>
    @endif
</div>

<!-- Reminders & Alerts -->
<div class="eth-card" style="margin-bottom:16px;">
    <div class="eth-card-header">
        <h3>🔔 Reminders & Alerts</h3>
    </div>
    <div class="eth-card-body" style="padding:16px;">
        @php
            $expiringCarryForward = collect([]);
            if (Schema::hasColumn("leave_balances", "carry_forward_expiry")) {
                $expiringCarryForward = \App\Models\LeaveBalance::where("carry_forward_expiry", ">=", now())
                    ->where("carry_forward_expiry", "<=", now()->addMonths(3))
                    ->where("carry_forward_days", ">", 0)
                    ->with(["applicant", "leaveType"])
                    ->get();
            }
            
            $pendingRequests = \App\Models\LeaveRequest::where("status", "pending")
                ->with(["applicant", "leaveType"])
                ->orderBy("created_at", "desc")
                ->limit(5)
                ->get();
            
            $onLeaveToday = \App\Models\LeaveRequest::where("status", "approved")
                ->whereDate("start_date", "<=", now())
                ->whereDate("end_date", ">=", now())
                ->with(["applicant", "leaveType"])
                ->get();
        @endphp
        
        @if($expiringCarryForward->count() > 0)
            <div style="background:#fff5e5; border:1px solid #f1d3a3; border-radius:8px; padding:12px 16px; margin-bottom:12px;">
                <strong style="color:#a45100;">⚠️ Carry Forward Expiring Soon ({{ $expiringCarryForward->count() }})</strong>
                <ul style="margin:8px 0 0; padding-left:20px; font-size:12px;">
                    @foreach($expiringCarryForward as $cf)
                        <li>{{ $cf->applicant->full_name ?? "N/A" }} - {{ number_format($cf->carry_forward_days,1) }} days (Expires: {{ \Carbon\Carbon::parse($cf->carry_forward_expiry)->format("M Y") }})</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        @if($pendingRequests->count() > 0 && $canApprove)
            <div style="background:#fff5e5; border:1px solid #f1d3a3; border-radius:8px; padding:12px 16px; margin-bottom:12px;">
                <strong style="color:#a45100;">⏳ Pending Approvals ({{ $pendingRequests->count() }})</strong>
                <ul style="margin:8px 0 0; padding-left:20px; font-size:12px;">
                    @foreach($pendingRequests as $pr)
                        <li>{{ $pr->applicant->full_name ?? "N/A" }} - {{ $pr->leaveType->name ?? "N/A" }} ({{ $pr->total_days }} days)</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        @if($onLeaveToday->count() > 0)
            <div style="background:#dff3ff; border:1px solid #b8d8f0; border-radius:8px; padding:12px 16px;">
                <strong style="color:#0d6289;">👤 On Leave Today ({{ $onLeaveToday->count() }})</strong>
                <ul style="margin:8px 0 0; padding-left:20px; font-size:12px;">
                    @foreach($onLeaveToday as $ol)
                        <li>{{ $ol->applicant->full_name ?? "N/A" }} - {{ $ol->leaveType->name ?? "N/A" }} (Returns: {{ $ol->end_date->format("M d, Y") }})</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        @if($expiringCarryForward->count() == 0 && $pendingRequests->count() == 0 && $onLeaveToday->count() == 0)
            <div style="text-align:center; padding:20px; color:#627386;">
                ✅ No alerts at this time
            </div>
        @endif
    </div>
</div>

<!-- Leave Requests Table -->
<div class="eth-card">
    <div class="eth-card-header">
        <h3>📋 Leave Requests</h3>
        <form method="get" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <select name="status" style="padding:8px 12px; border:1px solid #dce5ee; border-radius:8px; font-size:12px;">
                <option value="">All Status</option>
                <option value="pending" {{ request('status')=='pending'?'selected':'' }}>⏳ Pending</option>
                <option value="approved" {{ request('status')=='approved'?'selected':'' }}>✅ Approved</option>
                <option value="rejected" {{ request('status')=='rejected'?'selected':'' }}>❌ Rejected</option>
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" style="padding:8px 12px; border:1px solid #dce5ee; border-radius:8px; font-size:12px;">
            <button type="submit" class="eth-btn eth-btn-secondary eth-btn-sm">🔍</button>
        </form>
    </div>
    <div class="eth-card-body" style="padding:0; overflow-x:auto;">
        <table class="eth-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Leave Type</th>
                    <th>Period (GC / EC)</th>
                    <th style="text-align:center;">Days</th>
                    <th style="text-align:center;">Pay</th>
                    <th style="text-align:center;">Status</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaveRequests as $lr)
                <tr>
                    <td>
                        <strong>{{ $lr->applicant->full_name ?? 'N/A' }}</strong>
                        <div style="font-size:11px; color:#627386;">{{ $lr->applicant->position->name ?? '' }}</div>
                    </td>
                    <td>
                        <span class="eth-badge eth-badge-info">
                            @switch($lr->leaveType->code ?? '')
                                @case('annual') 🏖️ Annual @break
                                @case('sick') 🏥 Sick @break
                                @case('maternity') 🤰 Maternity @break
                                @case('paternity') 👨‍🍼 Paternity @break
                                @case('marriage') 💒 Marriage @break
                                @case('bereavement') 🕊️ Bereavement @break
                                @case('unpaid') 📋 Urgent Family @break
                                @default 📌 {{ $lr->leaveType->name ?? 'N/A' }}
                            @endswitch
                        </span>
                        @if($lr->leaveType->legal_reference)
                            <span class="eth-law-ref">{{ $lr->leaveType->legal_reference }}</span>
                        @endif
                    </td>
                    <td>
                        <div>
                            <strong>{{ $lr->start_date->format('M d, Y') }}</strong>
                            <span style="font-size:10px; color:#627386; display:block;">
                                🇪🇹 {{ App\Helpers\EthiopianDateHelper::ethDate($lr->start_date) }}
                            </span>
                        </div>
                        <div style="font-size:11px; color:#627386;">
                            to {{ $lr->end_date->format('M d, Y') }}
                            <span style="font-size:10px; color:#627386; display:block;">
                                🇪🇹 {{ App\Helpers\EthiopianDateHelper::ethDate($lr->end_date) }}
                            </span>
                        </div>
                    </td>
                    <td style="text-align:center; font-weight:600;">{{ $lr->total_days }}</td>
                    <td style="text-align:center;">
                        @php
                            $isPaidLeave = $lr->leaveType->is_paid ?? $lr->is_paid;
                            $payLabel = 'Paid';
                            $payClass = 'eth-badge-success';
                            
                            if ($lr->pay_tier == '50') {
                                $payLabel = '50%';
                                $payClass = 'eth-badge-warning';
                            } elseif ($lr->pay_tier == '0' || !$isPaidLeave) {
                                $payLabel = 'Unpaid';
                                $payClass = 'eth-badge-muted';
                            } elseif ($lr->pay_tier == '100' || $isPaidLeave) {
                                $payLabel = 'Paid';
                                $payClass = 'eth-badge-success';
                            }
                        @endphp
                        <span class="eth-badge {{ $payClass }}">{{ $payLabel }}</span>
                    </td>
                    <td style="text-align:center;">
                        <span class="eth-badge {{ match($lr->status) {
                            'approved' => 'eth-badge-success',
                            'rejected' => 'eth-badge-danger',
                            'cancelled' => 'eth-badge-muted',
                            default => 'eth-badge-warning'
                        } }}">
                            {{ ucfirst($lr->status) }}
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <div class="eth-actions" style="justify-content:center;">
                            @if($lr->status === 'pending' && $canApprove)
                                <form method="post" action="{{ route('leaves.approve', $lr->id) }}" style="display:inline;">
                                    @csrf
                                    <button class="eth-btn eth-btn-sm" style="background:#18794e; color:#fff;" title="Approve">✅</button>
                                </form>
                                <button class="eth-btn eth-btn-sm" style="background:#a61b1b; color:#fff;" title="Reject" onclick="rejectLeave({{ $lr->id }})">❌</button>
                            @endif
                            @if($lr->attachment_path)
                                <a href="{{ asset('storage/'.$lr->attachment_path) }}" class="eth-btn eth-btn-secondary eth-btn-xs" target="_blank">📎</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center; padding:40px; color:#627386;">
                    <div style="font-size:40px;">📭</div>
                    <div style="font-weight:600;">No leave requests found</div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($leaveRequests->hasPages())
    <div style="padding:16px 24px; border-top:1px solid #dce5ee;">
        {{ $leaveRequests->appends(request()->query())->links('vendor.pagination.bootstrap-5') }}
    </div>
    @endif
</div>

<!-- Reject Modal -->
<div id="rejectModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:28px; max-width:420px; width:90%;">
        <h3 style="margin:0 0 16px;">❌ Reject Leave Request</h3>
        <form id="rejectForm" method="post">
            @csrf
            <div style="margin-bottom:16px;">
                <label style="font-weight:600; font-size:12px; color:#627386;">Rejection Reason</label>
                <textarea name="rejection_reason" rows="3" required style="width:100%; padding:10px; border:1px solid #dce5ee; border-radius:8px; margin-top:4px;" placeholder="Provide reason..."></textarea>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="eth-btn eth-btn-secondary" onclick="document.getElementById('rejectModal').style.display='none'">Cancel</button>
                <button type="submit" class="eth-btn" style="background:#a61b1b; color:#fff;">Reject</button>
            </div>
        </form>
    </div>
</div>

<script>
function rejectLeave(id) {
    document.getElementById('rejectForm').action = '/leaves/' + id + '/reject';
    document.getElementById('rejectModal').style.display = 'flex';
}
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
@endsection
