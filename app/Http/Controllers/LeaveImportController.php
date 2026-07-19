<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LeaveImportController extends Controller
{
    public function index()
    {
        $applicants = Applicant::orderBy('first_name')->get();
        $leaveTypes = LeaveType::where('active', true)->get();
        
        // Get recent imports
        $recentImports = LeaveRequest::with(['applicant', 'leaveType'])
            ->where('reason', 'like', '%[Imported%')
            ->orWhere('reason', 'like', '%[Bulk Import]%')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
        
        return view('leaves.import', compact('applicants', 'leaveTypes', 'recentImports'));
    }

    public function storeSingle(Request $request)
    {
        $request->validate([
            'applicant_id' => 'required|exists:applicants,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'total_days' => 'required|numeric|min:0.5',
            'status' => 'required|in:approved,rejected,cancelled,pending',
            'reason' => 'nullable|string',
        ]);

        $leaveType = LeaveType::find($request->leave_type_id);
        
        LeaveRequest::create([
            'applicant_id' => $request->applicant_id,
            'leave_type_id' => $request->leave_type_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'total_days' => $request->total_days,
            'working_days' => $request->total_days,
            'calendar_days' => $request->total_days,
            'block_number' => 1,
            'reason' => $request->reason ?: '[Imported Data]',
            'status' => $request->status,
            'is_paid' => $leaveType->is_paid ?? true,
            'pay_tier' => ($leaveType->is_paid ?? true) ? '100' : '0',
            'approved_by' => $request->status === 'approved' ? (Auth::user()->username ?? 'system') : null,
            'approved_at' => $request->status === 'approved' ? now() : null,
            'created_by' => 'import_' . (Auth::user()->username ?? 'system'),
        ]);

        return back()->with('success', 'Leave record imported!');
    }

    public function storeMultiple(Request $request)
    {
        $request->validate([
            'applicant_id' => 'required|exists:applicants,id',
            'import_data' => 'required|string',
        ]);

        $applicant = Applicant::findOrFail($request->applicant_id);
        $lines = explode("\n", trim($request->import_data));
        $imported = 0;
        $errors = [];

        foreach ($lines as $i => $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $parts = str_getcsv($line);
            if (count($parts) < 5) {
                $errors[] = "Line " . ($i + 1) . ": Invalid format";
                continue;
            }

            $leaveCode = trim($parts[0]);
            $leaveType = LeaveType::where('code', $leaveCode)->first();
            if (!$leaveType) {
                $errors[] = "Line " . ($i + 1) . ": Type '{$leaveCode}' not found";
                continue;
            }

            try {
                LeaveRequest::create([
                    'applicant_id' => $applicant->id,
                    'leave_type_id' => $leaveType->id,
                    'start_date' => Carbon::parse(trim($parts[1])),
                    'end_date' => Carbon::parse(trim($parts[2])),
                    'total_days' => floatval(trim($parts[3])),
                    'working_days' => floatval(trim($parts[3])),
                    'calendar_days' => floatval(trim($parts[3])),
                    'block_number' => 1,
                    'reason' => ($parts[5] ?? '[Bulk Import]') . ' [Imported]',
                    'status' => strtolower(trim($parts[4])),
                    'is_paid' => $leaveType->is_paid ?? true,
                    'pay_tier' => ($leaveType->is_paid ?? true) ? '100' : '0',
                    'created_by' => 'import_' . (Auth::user()->username ?? 'system'),
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Line " . ($i + 1) . ": " . $e->getMessage();
            }
        }

        $msg = "{$imported} records imported!";
        if (count($errors) > 0) $msg .= " " . count($errors) . " errors.";
        
        return back()->with(count($errors) > 0 ? 'warning' : 'success', $msg);
    }

    public function downloadTemplate()
    {
        $csv = "LeaveCode,StartDate,EndDate,Days,Status,Reason\n";
        $csv .= "annual,2024-01-15,2024-01-30,12,approved,Annual leave\n";
        $csv .= "sick,2024-03-10,2024-03-12,3,approved,Medical\n";
        
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="leave_template.csv"',
        ]);
    }
    
    public function destroy($id)
    {
        LeaveRequest::findOrFail($id)->delete();
        return back()->with('success', 'Record deleted.');
    }
}
