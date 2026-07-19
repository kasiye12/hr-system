<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use App\Models\PublicHoliday;
use App\Models\LeaveSetting;
use Illuminate\Http\Request;

class LeaveSettingsController extends Controller
{
    public function index()
    {
        $leaveTypes = LeaveType::orderBy('code')->get();
        $holidays = PublicHoliday::orderBy('date')->get();
        $settings = LeaveSetting::all()->pluck('value', 'key');
        return view('leaves.settings', compact('leaveTypes', 'holidays', 'settings'));
    }

    public function storeLeaveType(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:leave_types,code',
            'default_days' => 'required|integer|min:1',
        ]);

        LeaveType::create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'default_days' => $request->default_days,
            'is_paid' => $request->boolean('is_paid'),
            'is_calendar_days' => $request->boolean('is_calendar_days'),
            'requires_document' => $request->boolean('requires_document'),
            'requires_medical_certificate' => $request->boolean('requires_document'),
            'accrues_over_time' => $request->boolean('accrues_over_time'),
            'resets_annually' => $request->boolean('resets_annually', true),
            'legal_reference' => $request->legal_reference,
            'active' => true,
        ]);

        return back()->with('success', 'Leave type added!');
    }

    public function toggleLeaveType($id)
    {
        $type = LeaveType::findOrFail($id);
        $type->active = !$type->active;
        $type->save();
        return back()->with('success', 'Status updated!');
    }

    public function deleteLeaveType($id)
    {
        $type = LeaveType::findOrFail($id);
        if ($type->leaveRequests()->count() > 0) {
            return back()->with('error', 'Cannot delete: has existing requests.');
        }
        $type->delete();
        return back()->with('success', 'Deleted!');
    }

    public function storeHoliday(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'date' => 'required|date',
        ]);
        PublicHoliday::create([
            'name' => $request->name,
            'date' => $request->date,
            'calendar_type' => 'gregorian',
            'recurring_yearly' => $request->boolean('recurring_yearly', true),
            'description' => $request->description,
        ]);
        return back()->with('success', 'Holiday added!');
    }

    public function deleteHoliday($id)
    {
        PublicHoliday::findOrFail($id)->delete();
        return back()->with('success', 'Holiday deleted!');
    }

    public function updateSettings(Request $request)
    {
        $settings = [
            'working_days_per_week' => $request->working_days_per_week ?? 6,
            'annual_leave_blocks_max' => $request->annual_leave_blocks_max ?? 2,
            'carry_forward_max_years' => $request->carry_forward_max_years ?? 2,
            'sick_leave_probation_days' => $request->sick_leave_probation_days ?? 45,
        ];

        foreach ($settings as $key => $value) {
            LeaveSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return back()->with('success', 'Settings updated!');
    }
}
