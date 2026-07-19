<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use Carbon\Carbon;

class ImprovedLeaveCalculator extends EthiopianLeaveCalculator
{
    /**
     * Calculate base leave for a given year index
     * Year 1: 16 days, Year 2+: 16 + ((year_index - 1) // 2)
     */
    public function calculateBaseForYear($yearIndex)
    {
        if ($yearIndex == 1) return 16;
        return 16 + floor(($yearIndex - 1) / 2);
    }

    /**
     * Get current year index based on hire date
     */
    public function getCurrentYearIndex(Applicant $applicant)
    {
        $hireDate = Carbon::parse($applicant->registration_date);
        $today = Carbon::now();
        return $today->year - $hireDate->year + 1;
    }

    /**
     * Get annual entitlement breakdown using existing tables only
     */
    public function getAnnualEntitlementBreakdown(Applicant $applicant)
    {
        $today = Carbon::now();
        $hireDate = Carbon::parse($applicant->registration_date);
        
        if (!$hireDate || $today->lt($hireDate)) return [];

        $annualType = LeaveType::where('code', 'annual')->first();
        if (!$annualType) return [];

        $currentYear = $today->year;
        $breakdown = [];

        for ($year = $currentYear - 1; $year <= $currentYear; $year++) {
            $yearIndex = $year - $hireDate->year + 1;
            if ($yearIndex < 1) continue;
            
            $baseForYear = $this->calculateBaseForYear($yearIndex);
            
            // Get used days for this year from leave_requests
            $usedDays = LeaveRequest::where('applicant_id', $applicant->id)
                ->where('leave_type_id', $annualType->id)
                ->where('status', 'approved')
                ->whereYear('start_date', $year)
                ->sum('total_days');
            
            // Get pending days
            $pendingDays = LeaveRequest::where('applicant_id', $applicant->id)
                ->where('leave_type_id', $annualType->id)
                ->where('status', 'pending')
                ->whereYear('start_date', $year)
                ->sum('total_days');
            
            // Get carry forward from previous year balance
            $carryForward = 0;
            if ($year == $currentYear) {
                $balance = LeaveBalance::where('applicant_id', $applicant->id)
                    ->where('leave_type_id', $annualType->id)
                    ->first();
                $carryForward = $balance ? floatval($balance->carry_forward_days ?? 0) : 0;
            }
            
            // Prorate if hired mid-year
            $monthsWorked = 12;
            if ($year == $hireDate->year) {
                $monthsWorked = 12 - $hireDate->month + 1;
            }
            $proratedBase = round(($baseForYear / 12.0) * $monthsWorked, 1);
            
            $totalEntitlement = round($proratedBase + $carryForward - $usedDays, 1);
            
            $breakdown[] = [
                'year' => $year,
                'base' => $proratedBase,
                'extra' => 0,
                'forwarding' => $carryForward,
                'used' => floatval($usedDays),
                'pending' => floatval($pendingDays),
                'expired' => 0,
                'total_entitlement' => max(0, $totalEntitlement),
            ];
        }

        return $breakdown;
    }
}
