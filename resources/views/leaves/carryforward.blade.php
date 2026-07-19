@extends('layout')

@section('title', 'Carry Forward | TNT HR')

@section('content')
<style>
    .cf-card { background:#fff; border-radius:16px; border:1px solid #dce5ee; overflow:hidden; margin-bottom:20px; box-shadow:0 2px 12px rgba(0,0,0,0.04); }
    .cf-header { background:linear-gradient(135deg,#16324f,#1b7f79); padding:18px 24px; color:#fff; display:flex; justify-content:space-between; align-items:center; }
    .cf-header h3 { margin:0; font-size:16px; }
    .cf-body { padding:20px 24px; }
    table { width:100%; border-collapse:collapse; font-size:13px; }
    table th { background:#f0f4f8; padding:10px 14px; font-size:10px; text-transform:uppercase; color:#627386; text-align:left; border-bottom:2px solid #dce5ee; }
    table td { padding:10px 14px; border-bottom:1px solid #f0f4f8; }
    table tr:hover td { background:#f8fbfd; }
    .num { text-align:center; font-weight:600; }
    .btn { padding:7px 16px; border-radius:7px; font-weight:600; font-size:12px; cursor:pointer; border:none; text-decoration:none; display:inline-block; }
    .btn-approve { background:#18794e; color:#fff; }
    .btn-reject { background:#a61b1b; color:#fff; }
    .btn-forward { background:#d38b2a; color:#fff; }
    .btn-back { background:#e9eff5; color:#23384d; padding:8px 16px; border-radius:8px; font-weight:600; }
    .btn-calc { background:#1b7f79; color:#fff; padding:8px 16px; border-radius:8px; font-weight:600; }
    .badge { display:inline-block; padding:4px 10px; border-radius:12px; font-size:11px; font-weight:700; }
    .badge-pending { background:#fff5e5; color:#a45100; }
    .badge-approved { background:#dcf5e7; color:#18794e; }
    .badge-rejected { background:#ffeded; color:#a61b1b; }
    .info-box { background:#f8fbfd; border:1px solid #dce5ee; border-radius:10px; padding:16px; margin-bottom:16px; font-size:13px; }
    .empty { text-align:center; padding:30px; color:#627386; }
    .section-title { font-size:15px; font-weight:700; color:#16324f; margin-bottom:12px; display:flex; align-items:center; gap:8px; }
</style>

<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:16px;">
    <div>
        <h1 style="margin:0;">📦 Carry Forward Leave</h1>
        <p style="color:#627386; margin:4px 0 0; font-size:13px;">Forward unused annual leave | Admin Approval Required</p>
    </div>
    <div style="display:flex; gap:10px;">
        <form method="post" action="{{ route('leaves.carryforward.recalculate') }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn btn-calc">🔄 Recalculate All</button>
        </form>
        <a href="{{ route('leaves.balances') }}" class="btn-back">← Back</a>
    </div>
</div>

<div class="info-box">
    <strong>📜 Ethiopian Labor Law:</strong> Unused annual leave can be carried forward for max 2 years.<br>
    <strong>👤 HR/Editor:</strong> Submit carry forward request → <strong>🔑 Admin:</strong> Approve or Reject<br>
    <strong>⚠️ Note:</strong> Forwarding is one-time per leave year.
</div>

@php
    use App\Services\LeaveCalculationService;
    use App\Models\LeaveType;
    use App\Models\LeaveRequest;
    use App\Models\CarryForwardRequest;
    
    $calc = new LeaveCalculationService();
    $annualType = LeaveType::where('code', 'annual')->first();
    $today = \Carbon\Carbon::now();
    $isAdmin = auth()->user() && auth()->user()->role === 'admin';
    $isAdmin = auth()->user() && auth()->user()->role === 'admin';
$isHR = auth()->user() && in_array(auth()->user()->role, ['admin', 'editor']);
    
    // Pending requests
    $pendingRequests = CarryForwardRequest::with('applicant')
        ->where('status', 'pending')
        ->orderBy('created_at', 'desc')
        ->get();
    
    // Approved requests
    $approvedRequests = CarryForwardRequest::with('applicant')
        ->where('status', 'approved')
        ->orderBy('approved_at', 'desc')
        ->get();
@endphp

<!-- ============================================ -->
<!-- PENDING APPROVALS (Admin only) -->
<!-- ============================================ -->
@if($isAdmin && $pendingRequests->count() > 0)
<div class="cf-card" style="border:2px solid #d38b2a;">
    <div class="cf-header" style="background:linear-gradient(135deg,#a45100,#d38b2a);">
        <h3>⏳ Pending Approvals ({{ $pendingRequests->count() }})</h3>
    </div>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>From Year</th>
                    <th>To Year</th>
                    <th class="num">Days</th>
                    <th>Requested</th>
                    <th>Expires</th>
                    <th style="text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingRequests as $pr)
                <tr>
                    <td><strong>{{ $pr->applicant->full_name ?? 'N/A' }}</strong></td>
                    <td>{{ $pr->from_year }}</td>
                    <td>{{ $pr->to_year }}</td>
                    <td class="num">{{ number_format($pr->days, 1) }}</td>
                    <td style="font-size:11px;">{{ $pr->created_at->format('M d, Y H:i') }}</td>
                    <td style="font-size:11px;">{{ $pr->expiry_date ? \Carbon\Carbon::parse($pr->expiry_date)->format('M Y') : '-' }}</td>
                    <td style="text-align:center;">
                        <form method="post" action="{{ route('leaves.carryforward.approve', $pr->id) }}" style="display:inline;">
                            @csrf
                            <button class="btn btn-approve" onclick="return confirm('Approve {{ number_format($pr->days,1) }} days?')">✅ Approve</button>
                        </form>
                        <form method="post" action="{{ route('leaves.carryforward.reject', $pr->id) }}" style="display:inline;">
                            @csrf
                            <input type="hidden" name="reason" value="Rejected by admin">
                            <button class="btn btn-reject" onclick="return confirm('Reject?')">❌ Reject</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- ============================================ -->
<!-- ELIGIBLE FOR CARRY FORWARD -->
<!-- ============================================ -->
<div class="cf-card">
    <div class="cf-header">
        <h3>📋 Eligible for Carry Forward (Previous Year Unused)</h3>
    </div>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Leave Year</th>
                    <th class="num">Accrued</th>
                    <th class="num">Used</th>
                    <th class="num">Unused</th>
                    <th>Status</th>
                    @if($isAdmin)<th style="text-align:center;">Action</th>@endif
                </tr>
            </thead>
            <tbody>
                @foreach(\App\Models\Applicant::all() as $app)
                    @php
                        $hd = $app->registration_date;
                        if(!$hd) continue;
                        $hc = \Carbon\Carbon::parse($hd);
                        $ps = \Carbon\Carbon::create($today->year-1, $hc->month, $hc->day);
                        $pe = (clone $ps)->addYear()->subDay();
                        
                        $sa = $calc->calculateAccruedLeave($hd, $ps);
                        $ea = $calc->calculateAccruedLeave($hd, $pe);
                        $acc = round($ea - $sa, 1);
                        
                        $used = LeaveRequest::where('applicant_id',$app->id)
                            ->where('leave_type_id',$annualType->id)->where('status','approved')
                            ->whereBetween('start_date',[$ps,$pe])->sum('total_days');
                        
                        $unused = max(0, round($acc - $used, 1));
                        
                        // Check existing request
                        $existingReq = CarryForwardRequest::where('applicant_id',$app->id)
                            ->where('from_year',$ps->year)
                            ->where('status','!=','rejected')
                            ->first();
                        $isPending = $existingReq && $existingReq->status === 'pending';
                        $isApproved = $existingReq && $existingReq->status === 'approved';
                        $cfDays = $existingReq ? $existingReq->days : 0;
                    @endphp
                    @if($unused > 0)
                    <tr>
                        <td><strong>{{ $app->full_name }}</strong></td>
                        <td style="font-size:12px;">{{ $ps->format('M Y') }} - {{ $pe->format('M Y') }}</td>
                        <td class="num">{{ number_format($acc,1) }}</td>
                        <td class="num">{{ number_format($used,1) }}</td>
                        <td class="num" style="color:#18794e; font-weight:700;">{{ number_format($unused,1) }} days</td>
                        <td>
                            @if($isApproved)
                                <span class="badge badge-approved">✅ Approved ({{ number_format($cfDays,1) }}d)</span>
                            @elseif($isPending)
                                <span class="badge badge-pending">⏳ Pending Approval</span>
                            @else
                                <span class="badge" style="background:#e8f3f2; color:#0c625e;">Available</span>
                            @endif
                        </td>
                        @if($isAdmin)
                        <td style="text-align:center;">
                            @if(!$existingReq)
                            <form method="post" action="{{ route('leaves.carryforward.store') }}" style="display:inline;">
                                @csrf
                                <input type="hidden" name="applicant_id" value="{{ $app->id }}">
                                <input type="hidden" name="days" value="{{ $unused }}">
                                <input type="hidden" name="year" value="{{ $ps->year }}">
                                <button class="btn btn-forward" onclick="return confirm('Submit forward request for {{ number_format($unused,1) }} days?')">
                                    📦 Forward {{ number_format($unused,1) }} Days
                                </button>
                            </form>
                            @elseif($isApproved && $isAdmin)
                            <form method="post" action="{{ route('leaves.carryforward.delete', $existingReq->id) }}" style="display:inline;">
                                @csrf @method('DELETE')
                                <button class="btn btn-reject" onclick="return confirm('Delete this carry forward?')">🔄 Reset</button>
                            </form>
                            @else
                                <span style="font-size:11px; color:#627386;">-</span>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @endif
                @endforeach
                @if(!isset($unused))
                <tr><td colspan="{{ $isHR?'7':'6' }}"><div class="empty">✅ No unused leave to forward</div></td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================ -->
<!-- APPROVED CARRY FORWARD HISTORY -->
<!-- ============================================ -->
<div class="cf-card">
    <div class="cf-header">
        <h3>📊 Approved Carry Forward History</h3>
    </div>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>From Year</th>
                    <th>To Year</th>
                    <th class="num">Days</th>
                    <th>Approved By</th>
                    <th>Approved Date</th>
                    <th>Expires</th>
                    @if($isAdmin)<th style="text-align:center;">Action</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($approvedRequests as $ar)
                <tr>
                    <td><strong>{{ $ar->applicant->full_name ?? 'N/A' }}</strong></td>
                    <td>{{ $ar->from_year }}</td>
                    <td>{{ $ar->to_year }}</td>
                    <td class="num">{{ number_format($ar->days, 1) }}</td>
                    <td>{{ $ar->approved_by ?? '-' }}</td>
                    <td style="font-size:11px;">{{ $ar->approved_at ? \Carbon\Carbon::parse($ar->approved_at)->format('M d, Y') : '-' }}</td>
                    <td style="font-size:11px;">{{ $ar->expiry_date ? \Carbon\Carbon::parse($ar->expiry_date)->format('M Y') : '-' }}</td>
                    @if($isAdmin)
                    <td style="text-align:center;">
                        <form method="post" action="{{ route('leaves.carryforward.delete', $ar->id) }}" style="display:inline;">
                            @csrf @method('DELETE')
                            <button class="btn btn-reject" style="padding:4px 8px; font-size:10px;" onclick="return confirm('Delete?')">🗑️</button>
                        </form>
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="{{ $isAdmin?'8':'7' }}"><div class="empty">No approved carry forwards</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
