<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\PublicHoliday;
use App\Models\LeaveAuditLog;
use App\Services\LeaveCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EthiopianLeaveController extends Controller
{
    private $calc;
    private $leaveCalc;
    
    public function __construct()
    {
        $this->leaveCalc = new LeaveCalculationService();
    }
    
    public function index(Request $request)
    {
        $query = LeaveRequest::with(['applicant.position', 'leaveType'])->orderBy('created_at', 'desc');
        if ($request->status) $query->where('status', $request->status);
        if ($request->date_from) $query->where('start_date', '>=', $request->date_from);
        $leaveRequests = $query->paginate(15);
        $leaveTypes = LeaveType::where('active', true)->get();
        $applicants = Applicant::orderBy('first_name')->get();
        
        $stats = [
            'pending' => LeaveRequest::where('status', 'pending')->count(),
            'on_leave' => LeaveRequest::where('status', 'approved')->whereDate('start_date', '<=', now())->whereDate('end_date', '>=', now())->count(),
            'total' => LeaveRequest::count(),
            'sick_exhausted' => LeaveRequest::whereHas('leaveType', fn($q) => $q->where('code', 'sick'))->where('status', 'approved')->sum('total_days'),
        ];
        
        $canApprove = in_array(Auth::user()->role ?? '', ['admin', 'editor']);
        return view('leaves.index', compact('leaveRequests', 'leaveTypes', 'applicants', 'stats', 'canApprove'));
    }
    
    public function create()
    {
        $applicants = Applicant::orderBy('first_name')->get();
        $leaveTypes = LeaveType::where('active', true)->get();
        
        $existingLeaves = LeaveRequest::where('status', '!=', 'rejected')
            ->where('status', '!=', 'cancelled')
            ->where('end_date', '>=', now()->subMonths(6))
            ->get()
            ->map(function($leave) {
                return [
                    'applicant_id' => $leave->applicant_id,
                    'start_date' => $leave->start_date->format('Y-m-d'),
                    'end_date' => $leave->end_date->format('Y-m-d'),
                    'leave_type' => $leave->leaveType->name ?? 'N/A',
                    'status' => $leave->status,
                ];
            });
        
        return view('leaves.create', compact('applicants', 'leaveTypes', 'existingLeaves'));
    }
    
    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'applicant_id' => 'required|exists:applicants,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
            'attachment' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);
        
        $applicant = Applicant::findOrFail($request->applicant_id);
        $leaveType = LeaveType::findOrFail($request->leave_type_id);
        
        // Working days calculation
        $workingDays = $this->calculateWorkingDays($request->start_date, $request->end_date);
        
        // Check for overlapping
        $overlappingLeaves = LeaveRequest::where('applicant_id', $request->applicant_id)
            ->where('status', '!=', 'rejected')
            ->where('status', '!=', 'cancelled')
            ->where(function($query) use ($request) {
                $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                    ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                    ->orWhere(function($q) use ($request) {
                        $q->where('start_date', '<=', $request->start_date)
                            ->where('end_date', '>=', $request->end_date);
                    });
            })
            ->exists();
        
        if ($overlappingLeaves) {
            return back()->with('error', '❌ Overlapping leave dates! Choose different dates.')->withInput();
        }
        
        $attachment = null;
        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment')->store('leave_docs', 'public');
        }
        
        LeaveRequest::create([
            'applicant_id' => $applicant->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'total_days' => $workingDays,
            'working_days' => $workingDays,
            'calendar_days' => $workingDays,
            'block_number' => 1,
            'reason' => $request->reason,
            'status' => 'pending',
            'attachment_path' => $attachment,
            'is_paid' => $leaveType->is_paid,
            'pay_tier' => $leaveType->is_paid ? '100' : '0',
            'created_by' => $user->username,
        ]);
        
        return redirect()->route('leaves.index')->with('success', 'Leave request submitted!');
    }
    
    public function approve($id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'editor'])) return back()->with('error', 'Unauthorized.');
        
        $lr = LeaveRequest::findOrFail($id);
        if ($lr->status !== 'pending') return back()->with('error', 'Already processed.');
        
        $lr->update(['status' => 'approved', 'approved_by' => $user->username, 'approved_at' => now()]);
        $this->recalcAll();
        
        return back()->with('success', 'Leave approved!');
    }
    
    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        $lr = LeaveRequest::findOrFail($id);
        $lr->update(['status' => 'rejected', 'rejection_reason' => $request->rejection_reason, 'approved_by' => $user->username, 'approved_at' => now()]);
        return back()->with('success', 'Leave rejected.');
    }
    
    public function cancel($id)
    {
        LeaveRequest::findOrFail($id)->update(['status' => 'cancelled']);
        return back()->with('success', 'Leave cancelled.');
    }
    
    /**
     * Balances with ETHIOPIAN PRO-RATA calculation
     */
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
        return view('leaves.balances', compact('balances', 'applicants'));
    }
    
    public function calendar()
    {
        $leaves = LeaveRequest::with(['applicant', 'leaveType'])->where('status', 'approved')
            ->where('end_date', '>=', now()->subMonth())->orderBy('start_date')->get();
        return view('leaves.calendar', ['leaves' => $leaves]);
    }
    
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
    
    public function breakdown(Request $request)
    {
        $applicants = Applicant::orderBy("first_name")->get();
        return view("leaves.breakdown", compact("applicants"));
    }
    
    // ============================================
    // ETHIOPIAN PRO-RATA BALANCE CALCULATION
    // ============================================
    
    private function recalcAll()
    {
        $annualType = LeaveType::where('code', 'annual')->first();
        if (!$annualType) return;
        
        foreach (Applicant::all() as $applicant) {
            $hireDate = $applicant->registration_date;
            if (!$hireDate) continue;
            
            // ====== ETHIOPIAN PRO-RATA FORMULA ======
            // Accrued = Σ [(Days Worked / Days in Year) × Annual Entitlement]
            $totalAccrued = $this->leaveCalc->calculateAccruedLeave($hireDate);
            
            // ALL used days
            $usedDays = LeaveRequest::where('applicant_id', $applicant->id)
                ->where('leave_type_id', $annualType->id)
                ->where('status', 'approved')
                ->sum('total_days');
            
            $pendingDays = LeaveRequest::where('applicant_id', $applicant->id)
                ->where('leave_type_id', $annualType->id)
                ->where('status', 'pending')
                ->sum('total_days');
            
            $available = max(0, $totalAccrued - $usedDays - $pendingDays);
            
            LeaveBalance::updateOrCreate(
                ['applicant_id' => $applicant->id, 'leave_type_id' => $annualType->id],
                [
                    'total_entitled' => round($totalAccrued, 1),
                    'used_current_year' => $usedDays,
                    'pending_days' => $pendingDays,
                    'available_days' => round($available, 1),
                    'carry_forward_days' => 0,
                    'carry_forward_expiry' => null,
                    'last_calculated_at' => now(),
                ]
            );
            
            // Other leave types
            $otherTypes = LeaveType::where('code', '!=', 'annual')->where('active', true)->get();
            foreach ($otherTypes as $type) {
                $used = LeaveRequest::where('applicant_id', $applicant->id)
                    ->where('leave_type_id', $type->id)
                    ->where('status', 'approved')
                    ->whereYear('start_date', now()->year)
                    ->sum('total_days');
                
                $pending = LeaveRequest::where('applicant_id', $applicant->id)
                    ->where('leave_type_id', $type->id)
                    ->where('status', 'pending')
                    ->whereYear('start_date', now()->year)
                    ->sum('total_days');
                
                $avail = max(0, $type->default_days - $used - $pending);
                
                LeaveBalance::updateOrCreate(
                    ['applicant_id' => $applicant->id, 'leave_type_id' => $type->id],
                    [
                        'total_entitled' => $type->default_days,
                        'used_current_year' => $used,
                        'pending_days' => $pending,
                        'available_days' => $avail,
                        'carry_forward_days' => 0,
                        'carry_forward_expiry' => null,
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
            if ($start->dayOfWeek !== 0) $days++; // Exclude Sundays only
            $start->addDay();
        }
        return $days;
    }
}
