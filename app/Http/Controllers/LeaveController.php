<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\Applicant;
use App\Models\PublicHoliday;
use App\Models\CarryForwardRequest;
use App\Services\LeaveCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LeaveController extends Controller
{
    private $leaveCalc;
    
    public function __construct()
    {
        $this->leaveCalc = new LeaveCalculationService();
    }
    
    // ============================================
    // DASHBOARD
    // ============================================
    public function index(Request $request)
    {
        $query = LeaveRequest::with(['applicant.position', 'leaveType'])
            ->orderBy('created_at', 'desc');
        if ($request->status) $query->where('status', $request->status);
        if ($request->date_from) $query->where('start_date', '>=', $request->date_from);
        
        $leaveRequests = $query->paginate(15);
        $leaveTypes = LeaveType::where('active', true)->get();
        $applicants = Applicant::orderBy('first_name')->get();
        
        $stats = [
            'pending' => LeaveRequest::where('status', 'pending')->count(),
            'on_leave' => LeaveRequest::where('status', 'approved')
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())->count(),
            'total' => LeaveRequest::count(),
            'sick_exhausted' => LeaveRequest::whereHas('leaveType', fn($q) => $q->where('code', 'sick'))
                ->where('status', 'approved')->sum('total_days'),
        ];
        
        $canApprove = in_array(Auth::user()->role ?? '', ['admin', 'editor']);
        return view('leaves.index', compact('leaveRequests', 'leaveTypes', 'applicants', 'stats', 'canApprove'));
    }
    
    // ============================================
    // APPLY LEAVE
    // ============================================
    public function create()
    {
        $applicants = Applicant::orderBy('first_name')->get();
        $leaveTypes = LeaveType::where('active', true)->get();
        return view('leaves.create', compact('applicants', 'leaveTypes'));
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'applicant_id' => 'required|exists:applicants,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
            'attachment' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);
        
        $leaveType = LeaveType::find($request->leave_type_id);
        $totalDays = $this->calculateWorkingDays($request->start_date, $request->end_date);
        
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leave_docs', 'public');
        }
        
        LeaveRequest::create([
            'applicant_id' => $request->applicant_id,
            'leave_type_id' => $request->leave_type_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'total_days' => $totalDays,
            'working_days' => $totalDays,
            'calendar_days' => $totalDays,
            'block_number' => 1,
            'reason' => $request->reason,
            'status' => 'pending',
            'attachment_path' => $attachmentPath,
            'is_paid' => $leaveType->is_paid ?? true,
            'pay_tier' => ($leaveType->is_paid ?? true) ? '100' : '0',
            'created_by' => Auth::user()->username ?? 'system',
        ]);
        
        return redirect()->route('leaves.index')->with('success', 'Leave request submitted!');
    }
    
    // ============================================
    // APPROVE / REJECT / CANCEL
    // ============================================
    public function approve($id)
    {
        $lr = LeaveRequest::findOrFail($id);
        $lr->update(['status' => 'approved', 'approved_by' => Auth::user()->username, 'approved_at' => now()]);
        $this->recalcAll();
        return back()->with('success', 'Leave approved!');
    }
    
    public function reject(Request $request, $id)
    {
        $lr = LeaveRequest::findOrFail($id);
        $lr->update(['status' => 'rejected', 'rejection_reason' => $request->rejection_reason]);
        return back()->with('success', 'Leave rejected.');
    }
    
    public function cancel($id)
    {
        LeaveRequest::findOrFail($id)->update(['status' => 'cancelled']);
        return back()->with('success', 'Leave cancelled.');
    }
    
    // ============================================
    // BALANCES - Shows ALL used/pending/available
    // ============================================
    public function balances(Request $request)
    {
        $this->recalcAll();
        
        $query = LeaveBalance::with(['applicant.position', 'leaveType']);
        if ($request->filled('applicant_id')) $query->where('applicant_id', $request->applicant_id);
        if ($request->filled('leave_type_id')) $query->where('leave_type_id', $request->leave_type_id);
        if ($request->availability === 'has_balance') $query->where('available_days', '>', 0);
        elseif ($request->availability === 'exhausted') $query->where('available_days', '<=', 0);
        
        $balances = $query->orderBy('applicant_id')->orderBy('leave_type_id')->paginate(30)->withQueryString();
        $applicants = Applicant::orderBy('first_name')->get();
        
        // Get approved leave details for each balance
        $annualType = LeaveType::where('code', 'annual')->first();
        $leaveDetails = [];
        if ($annualType) {
            $approvedLeaves = LeaveRequest::where('status', 'approved')
                ->where('leave_type_id', $annualType->id)
                ->orderBy('start_date', 'desc')
                ->get()
                ->groupBy('applicant_id');
            
            foreach ($approvedLeaves as $appId => $leaves) {
                $leaveDetails[$appId] = $leaves;
            }
        }
        
        return view('leaves.balances', compact('balances', 'applicants', 'leaveDetails'));
    }
    
    // ============================================
    // CALENDAR
    // ============================================
    public function calendar()
    {
        $leaves = LeaveRequest::with(['applicant', 'leaveType'])
            ->where('status', 'approved')
            ->where('end_date', '>=', now()->subMonth())
            ->orderBy('start_date')->get();
        return view('leaves.calendar', ['leaves' => $leaves]);
    }
    
    // ============================================
    // REPORT
    // ============================================
    public function report(Request $request)
    {
        $query = LeaveRequest::with(['applicant.position', 'leaveType']);
        if ($request->filled('date_from')) $query->where('start_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->where('end_date', '<=', $request->date_to);
        if ($request->filled('leave_type')) $query->where('leave_type_id', $request->leave_type);
        if ($request->filled('status')) $query->where('status', $request->status);
        $reports = $query->orderBy('start_date', 'desc')->get();
        $leaveTypes = LeaveType::where('active', true)->get();
        return view('leaves.report', compact('reports', 'leaveTypes'));
    }
    
    // ============================================
    // BREAKDOWN
    // ============================================
    public function breakdown(Request $request)
    {
        $applicants = Applicant::orderBy('first_name')->get();
        return view('leaves.breakdown', compact('applicants'));
    }
    
    // ============================================
    // CORE: PRO-RATA BALANCE CALCULATION
    // Formula: Accrued = Σ(Days/365 × Entitlement)
    // ============================================
    public function recalcAll()
    {
        $annualType = LeaveType::where('code', 'annual')->first();
        if (!$annualType) return;
        
        foreach (Applicant::all() as $applicant) {
            $hireDate = $applicant->registration_date;
            if (!$hireDate) continue;
            
            // === ETHIOPIAN PRO-RATA ACCRUAL ===
            $totalAccrued = $this->leaveCalc->calculateAccruedLeave($hireDate);
            
            // === ALL APPROVED DAYS (not just current year) ===
            $usedDays = LeaveRequest::where('applicant_id', $applicant->id)
                ->where('leave_type_id', $annualType->id)
                ->where('status', 'approved')
                ->sum('total_days');
            
            $pendingDays = LeaveRequest::where('applicant_id', $applicant->id)
                ->where('leave_type_id', $annualType->id)
                ->where('status', 'pending')
                ->sum('total_days');
            
            // === CARRY FORWARD from carry_forward_requests table ===
            $carryForward = CarryForwardRequest::where('applicant_id', $applicant->id)
                ->where('status', 'approved')
                ->sum('days');
            
            $available = max(0, $totalAccrued - $usedDays - $pendingDays + $carryForward);
            
            LeaveBalance::updateOrCreate(
                ['applicant_id' => $applicant->id, 'leave_type_id' => $annualType->id],
                [
                    'total_entitled' => round($totalAccrued, 1),
                    'used_current_year' => $usedDays,
                    'pending_days' => $pendingDays,
                    'available_days' => round($available, 1),
                    'carry_forward_days' => $carryForward,
                    'last_calculated_at' => now(),
                ]
            );
            
            // === OTHER LEAVE TYPES ===
            foreach (LeaveType::where('code', '!=', 'annual')->where('active', true)->get() as $type) {
                $used = LeaveRequest::where('applicant_id', $applicant->id)
                    ->where('leave_type_id', $type->id)->where('status', 'approved')
                    ->whereYear('start_date', now()->year)->sum('total_days');
                $pending = LeaveRequest::where('applicant_id', $applicant->id)
                    ->where('leave_type_id', $type->id)->where('status', 'pending')
                    ->whereYear('start_date', now()->year)->sum('total_days');
                $avail = max(0, $type->default_days - $used - $pending);
                
                LeaveBalance::updateOrCreate(
                    ['applicant_id' => $applicant->id, 'leave_type_id' => $type->id],
                    [
                        'total_entitled' => $type->default_days,
                        'used_current_year' => $used,
                        'pending_days' => $pending,
                        'available_days' => $avail,
                        'last_calculated_at' => now(),
                    ]
                );
            }
        }
    }
    
    private function calculateWorkingDays($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $days = 0;
        while ($start <= $end) {
            if ($start->dayOfWeek !== 0) $days++; // Exclude Sundays
            $start->addDay();
        }
        return $days;
    }
}
