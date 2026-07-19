<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\CarryForwardRequest;
use Carbon\Carbon;

class LeaveExpiryService
{
    private $leaveCalc;
    
    public function __construct()
    {
        $this->leaveCalc = new LeaveCalculationService();
    }
    
    /**
     * Calculate expired leave for an employee
     * Ethiopian Law: Unused leave expires 2 years after the end of the leave year
     */
    public function calculateExpiredLeave(Applicant $applicant)
    {
        $hireDate = $applicant->registration_date;
        if (!$hireDate) return ['total' => 0, 'details' => []];
        
        $hireCarbon = Carbon::parse($hireDate);
        $today = Carbon::now();
        $annualType = LeaveType::where('code', 'annual')->first();
        
        if (!$annualType) return ['total' => 0, 'details' => []];
        
        $totalExpired = 0;
        $expiredDetails = [];
        
        // Check each leave year from hire date to today
        for ($year = $hireCarbon->year; $year <= $today->year; $year++) {
            // Leave year: anniversary-based
            $leaveYearStart = Carbon::create($year, $hireCarbon->month, $hireCarbon->day);
            $leaveYearEnd = (clone $leaveYearStart)->addYear()->subDay();
            
            // Expiry date: 2 years after leave year ends
            $expiryDate = (clone $leaveYearEnd)->addYears(2);
            
            // Skip if not yet expired (still within 2-year window)
            if ($expiryDate->gt($today)) continue;
            
            // Calculate accrued in this leave year (pro-rata)
            $accruedStart = $this->leaveCalc->calculateAccruedLeave($hireDate, $leaveYearStart);
            $accruedEnd = $this->leaveCalc->calculateAccruedLeave($hireDate, $leaveYearEnd);
            $yearAccrued = round($accruedEnd - $accruedStart, 1);
            
            // Used in this leave year
            $yearUsed = LeaveRequest::where('applicant_id', $applicant->id)
                ->where('leave_type_id', $annualType->id)
                ->where('status', 'approved')
                ->whereBetween('start_date', [$leaveYearStart, $leaveYearEnd])
                ->sum('total_days');
            
            // Carry forward received in this year (from previous year)
            $carryForwardReceived = CarryForwardRequest::where('applicant_id', $applicant->id)
                ->where('status', 'approved')
                ->where('to_year', $year)
                ->sum('days');
            
            // Total available this year = Accrued + Carry Forward Received
            $totalAvailable = $yearAccrued + $carryForwardReceived;
            
            // Unused = Available - Used
            $yearUnused = max(0, round($totalAvailable - $yearUsed, 1));
            
            if ($yearUnused > 0.05) {
                $expiredDetails[] = [
                    'leave_year' => $leaveYearStart->format('M Y') . ' - ' . $leaveYearEnd->format('M Y'),
                    'year_number' => $year - $hireCarbon->year + 1,
                    'accrued' => $yearAccrued,
                    'carry_received' => $carryForwardReceived,
                    'total_available' => round($totalAvailable, 1),
                    'used' => $yearUsed,
                    'unused' => $yearUnused,
                    'expired' => $yearUnused,
                    'expiry_date' => $expiryDate->format('M d, Y'),
                    'status' => 'EXPIRED',
                ];
                $totalExpired += $yearUnused;
            }
        }
        
        return [
            'total' => round($totalExpired, 1),
            'details' => $expiredDetails,
        ];
    }
    
    /**
     * Calculate eligible carry forward (unused from previous year)
     */
    public function calculateEligibleCarryForward(Applicant $applicant)
    {
        $hireDate = $applicant->registration_date;
        if (!$hireDate) return ['unused' => 0, 'details' => []];
        
        $hireCarbon = Carbon::parse($hireDate);
        $today = Carbon::now();
        $annualType = LeaveType::where('code', 'annual')->first();
        
        if (!$annualType) return ['unused' => 0, 'details' => []];
        
        // Previous leave year
        $prevYearStart = Carbon::create($today->year - 1, $hireCarbon->month, $hireCarbon->day);
        $prevYearEnd = (clone $prevYearStart)->addYear()->subDay();
        
        // Accrued in previous year
        $accruedStart = $this->leaveCalc->calculateAccruedLeave($hireDate, $prevYearStart);
        $accruedEnd = $this->leaveCalc->calculateAccruedLeave($hireDate, $prevYearEnd);
        $prevYearAccrued = round($accruedEnd - $accruedStart, 1);
        
        // Carry forward received in previous year
        $carryReceived = CarryForwardRequest::where('applicant_id', $applicant->id)
            ->where('status', 'approved')
            ->where('to_year', $prevYearStart->year)
            ->sum('days');
        
        // Used in previous year
        $prevYearUsed = LeaveRequest::where('applicant_id', $applicant->id)
            ->where('leave_type_id', $annualType->id)
            ->where('status', 'approved')
            ->whereBetween('start_date', [$prevYearStart, $prevYearEnd])
            ->sum('total_days');
        
        $totalAvailable = $prevYearAccrued + $carryReceived;
        $unused = max(0, round($totalAvailable - $prevYearUsed, 1));
        
        // Check if already forwarded
        $alreadyForwarded = CarryForwardRequest::where('applicant_id', $applicant->id)
            ->where('from_year', $prevYearStart->year)
            ->where('status', 'approved')
            ->exists();
        
        return [
            'leave_year' => $prevYearStart->format('M Y') . ' - ' . $prevYearEnd->format('M Y'),
            'from_year' => $prevYearStart->year,
            'accrued' => $prevYearAccrued,
            'carry_received' => $carryReceived,
            'total_available' => round($totalAvailable, 1),
            'used' => $prevYearUsed,
            'unused' => $unused,
            'already_forwarded' => $alreadyForwarded,
            'expiry_if_forwarded' => (clone $prevYearEnd)->addYears(2)->format('M d, Y'),
        ];
    }
}
