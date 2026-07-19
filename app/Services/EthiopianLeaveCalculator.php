<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\PublicHoliday;
use Carbon\Carbon;

class EthiopianLeaveCalculator
{
    private $ethCalendar;
    
    public function __construct()
    {
        $this->ethCalendar = new EthiopianCalendarService();
    }
    
    /**
     * Calculate annual leave based on Proclamation 1156/2019
     */
    public function calculateAnnualLeave(Applicant $applicant, $asOfDate = null)
    {
        $asOf = $asOfDate ? Carbon::parse($asOfDate) : Carbon::now();
        $hireDate = Carbon::parse($applicant->registration_date);
        $yearsOfService = $hireDate->diffInYears($asOf);
        $days = 16 + ($yearsOfService >= 1 ? floor($yearsOfService / 2) : 0);
        return min($days, 30);
    }
    
    /**
     * Get leave year based on anniversary date
     */
    public function getLeaveYear(Applicant $applicant, $asOfDate = null)
    {
        $asOf = $asOfDate ? Carbon::parse($asOfDate) : Carbon::now();
        $hireDate = Carbon::parse($applicant->registration_date);
        $anniversary = Carbon::create($asOf->year, $hireDate->month, $hireDate->day);
        if ($anniversary->gt($asOf)) $anniversary->subYear();
        
        $ethStart = $this->ethCalendar->gregorianToEthiopian($anniversary);
        $ethEnd = $this->ethCalendar->gregorianToEthiopian((clone $anniversary)->addYear()->subDay());
        
        return [
            'start' => $anniversary,
            'end' => (clone $anniversary)->addYear()->subDay(),
            'years_of_service' => $hireDate->diffInYears($anniversary),
            'entitled' => $this->calculateAnnualLeave($applicant, $anniversary),
            'ethiopian_start' => $ethStart,
            'ethiopian_end' => $ethEnd,
        ];
    }
    
    /**
     * Calculate working days (excludes Sundays and holidays)
     */
    public function calculateWorkingDays($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $days = 0;
        
        $holidays = PublicHoliday::whereBetween('date', [
            $start->format('Y-m-d'), 
            $end->format('Y-m-d')
        ])->pluck('date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))->toArray();
        
        while ($start <= $end) {
            if ($start->dayOfWeek !== 0 && !in_array($start->format('Y-m-d'), $holidays)) {
                $days++;
            }
            $start->addDay();
        }
        return $days;
    }
    
    public function calculateCalendarDays($startDate, $endDate)
    {
        return Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
    }
    
    public function getSickLeavePayTier(Applicant $applicant, $requestedDays = 0)
    {
        $sickType = LeaveType::where('code', 'sick')->first();
        if (!$sickType) return ['tier' => 1, 'pay' => 100, 'label' => 'Full Pay (100%)'];
        
        $used = LeaveRequest::where('applicant_id', $applicant->id)
            ->where('leave_type_id', $sickType->id)
            ->where('status', 'approved')
            ->where('start_date', '>=', now()->subMonths(12))
            ->sum('total_days');
        
        $total = $used + $requestedDays;
        
        if ($total <= 30) return ['tier' => 1, 'pay' => 100, 'label' => 'Full Pay (100%)'];
        if ($total <= 90) return ['tier' => 2, 'pay' => 50, 'label' => 'Half Pay (50%)'];
        if ($total <= 180) return ['tier' => 3, 'pay' => 0, 'label' => 'Unpaid'];
        return ['tier' => 4, 'pay' => 0, 'label' => 'EXHAUSTED'];
    }
    
    public function isWithinProbation(Applicant $applicant)
    {
        return Carbon::parse($applicant->registration_date)->diffInDays(now()) <= 45;
    }
    
    public function getAnnualBlocksUsed(Applicant $applicant)
    {
        $leaveYear = $this->getLeaveYear($applicant);
        $annualType = LeaveType::where('code', 'annual')->first();
        if (!$annualType) return 0;
        
        return LeaveRequest::where('applicant_id', $applicant->id)
            ->where('leave_type_id', $annualType->id)
            ->where('status', '!=', 'cancelled')
            ->where('start_date', '>=', $leaveYear['start'])
            ->count();
    }
    
    public function isMicroLeavePattern(Applicant $applicant, $days)
    {
        if ($days > 2) return false;
        return LeaveRequest::where('applicant_id', $applicant->id)
            ->where('status', 'approved')
            ->where('total_days', '<=', 2)
            ->where('start_date', '>=', now()->subMonths(3))
            ->count() >= 2;
    }
    
    public function calculateTerminationPayout(Applicant $applicant, $monthlySalary)
    {
        $annualType = LeaveType::where('code', 'annual')->first();
        if (!$annualType) return ['days' => 0, 'rate' => 0, 'payout' => 0];
        
        $balance = LeaveBalance::where('applicant_id', $applicant->id)
            ->where('leave_type_id', $annualType->id)->first();
        
        $days = $balance ? $balance->available_days + ($balance->carry_forward_days ?? 0) : 0;
        $rate = $monthlySalary / 26;
        
        return ['days' => $days, 'rate' => round($rate, 2), 'payout' => round($days * $rate, 2)];
    }
    
    /**
     * Get date in both calendars for display
     */
    public function getDualCalendarDate($gregorianDate)
    {
        $eth = $this->ethCalendar->gregorianToEthiopian($gregorianDate);
        return [
            'gregorian' => Carbon::parse($gregorianDate)->format('M d, Y'),
            'ethiopian' => $eth['day'] . ' ' . $eth['month_name'] . ' ' . $eth['year'],
            'ethiopian_amharic' => $eth['day'] . ' ' . $eth['month_name_amharic'] . ' ' . $eth['year'],
        ];
    }
    
    /**
     * Get Ethiopian calendar service
     */
    public function getEthiopianCalendar()
    {
        return $this->ethCalendar;
    }
}
