<?php

namespace App\Http\Controllers;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\Applicant;
use App\Models\LeaveType;
use App\Models\CarryForwardRequest;
use App\Services\LeaveCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CarryForwardController extends Controller
{
    private $leaveCalc;
    
    public function __construct()
    {
        $this->leaveCalc = new LeaveCalculationService();
    }
    
    public function index()
    {
        $applicants = Applicant::orderBy('first_name')->get();
        $annualType = LeaveType::where('code', 'annual')->first();
        $today = Carbon::now();
        
        // Get approved carry forward requests
        $carryForwards = CarryForwardRequest::with('applicant')
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get pending requests
        $pendingRequests = CarryForwardRequest::with('applicant')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('leaves.carryforward', compact('applicants', 'annualType', 'today', 'carryForwards', 'pendingRequests'));
    }
    
    /**
     * Submit carry forward request (HR)
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'editor'])) {
            return back()->with('error', 'Only HR/Admin can forward leave.');
        }
        
        $applicant = Applicant::findOrFail($request->applicant_id);
        $days = (float)$request->days;
        $fromYear = (int)$request->year;
        $toYear = $fromYear + 1;
        
        // Check if already requested
        $existing = CarryForwardRequest::where('applicant_id', $applicant->id)
            ->where('from_year', $fromYear)
            ->where('status', '!=', 'rejected')
            ->first();
        
        if ($existing) {
            return back()->with('error', "Already requested/approved for year {$fromYear}.");
        }
        
        // Auto-approve if admin
        $status = $user->role === 'admin' ? 'approved' : 'pending';
        
        $cf = CarryForwardRequest::create([
            'applicant_id' => $applicant->id,
            'from_year' => $fromYear,
            'to_year' => $toYear,
            'days' => $days,
            'status' => $status,
            'approved_by' => $status === 'approved' ? $user->username : null,
            'approved_at' => $status === 'approved' ? now() : null,
            'expiry_date' => Carbon::now()->addYears(2),
        ]);
        
        // If approved, update balance immediately
        if ($status === 'approved') {
            $this->applyToBalance($cf);
        }
        
        $msg = $status === 'approved' ? 'approved & applied' : 'submitted for approval';
        return back()->with('success', "{$days} days {$msg} for {$applicant->full_name}!");
    }
    
    /**
     * Approve pending request (Admin)
     */
    public function approve($id)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') return back()->with('error', 'Admin only.');
        
        $cf = CarryForwardRequest::findOrFail($id);
        $cf->update([
            'status' => 'approved',
            'approved_by' => $user->username,
            'approved_at' => now(),
        ]);
        
        $this->applyToBalance($cf);
        
        return back()->with('success', 'Carry forward approved!');
    }
    
    /**
     * Apply carry forward to employee balance
     */
    private function applyToBalance($cf)
    {
        $annualType = LeaveType::where('code', 'annual')->first();
        if (!$annualType) return;
        
        $balance = LeaveBalance::where('applicant_id', $cf->applicant_id)
            ->where('leave_type_id', $annualType->id)->first();
        
        if ($balance) {
            $balance->update([
                'carry_forward_days' => ($balance->carry_forward_days ?? 0) + $cf->days,
                'available_days' => ($balance->available_days ?? 0) + $cf->days,
                'carry_forward_expiry' => $cf->expiry_date,
            ]);
        }
    }
    
    /**
     * Delete carry forward record
     */
    public function clear($id)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') return back()->with('error', 'Admin only.');
        
        $cf = CarryForwardRequest::findOrFail($id);
        
        // Deduct from balance if was approved
        if ($cf->status === 'approved') {
            $annualType = LeaveType::where('code', 'annual')->first();
            if ($annualType) {
                $balance = LeaveBalance::where('applicant_id', $cf->applicant_id)
                    ->where('leave_type_id', $annualType->id)->first();
                if ($balance) {
                    $balance->update([
                        'carry_forward_days' => max(0, ($balance->carry_forward_days ?? 0) - $cf->days),
                        'available_days' => max(0, ($balance->available_days ?? 0) - $cf->days),
                    ]);
                }
            }
        }
        
        $cf->delete();
        return back()->with('success', 'Carry forward record deleted.');
    }
    
    /**
     * Recalculate all balances
     */
    public function recalculate()
    {
        $controller = new LeaveController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('recalcAll');
        $method->setAccessible(true);
        $method->invoke($controller);
        
        return back()->with('success', 'All balances recalculated!');
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role !== "admin") return back()->with("error", "Admin only.");
        
        $cf = CarryForwardRequest::findOrFail($id);
        $cf->update([
            "status" => "rejected",
            "rejection_reason" => $request->reason,
            "approved_by" => $user->username,
            "approved_at" => now(),
        ]);
        
        return back()->with("success", "Carry forward rejected.");
    }

    public function destroy($id)
    {
        return $this->clear($id);
    }
}