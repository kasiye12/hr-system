<?php

namespace App\Services;

use Carbon\Carbon;

class LeaveCalculationService
{
    /**
     * Ethiopian Pro-rata Daily Accrual
     * Formula: Accrued = Σ [(Days Worked / Days in Year) × Annual Entitlement]
     */
    public function calculateAccruedLeave($hireDateGregorian, $targetDateGregorian = null)
    {
        $hireDate = Carbon::parse($hireDateGregorian)->startOfDay();
        $targetDate = $targetDateGregorian ? Carbon::parse($targetDateGregorian)->startOfDay() : Carbon::now()->startOfDay();
        
        if ($targetDate->lt($hireDate)) return 0.0;

        $totalAccrued = 0.0;
        $currentStart = $hireDate->copy();

        while ($currentStart->lt($targetDate)) {
            $yearsOfService = $hireDate->diffInYears($currentStart);
            $annualEntitlement = 16 + (int) floor($yearsOfService / 2);
            $currentEnd = $currentStart->copy()->addYear();
            $endCompute = $targetDate->lt($currentEnd) ? $targetDate : $currentEnd;
            $daysWorked = $currentStart->diffInDays($endCompute);
            $totalDays = $currentStart->diffInDays($currentEnd);
            $periodAccrual = ($daysWorked / $totalDays) * $annualEntitlement;
            $totalAccrued += $periodAccrual;
            $currentStart->addYear();
        }

        return round($totalAccrued, 4);
    }

    public function calculateAnnualEntitlement($yearsOfService)
    {
        return 16 + (int) floor($yearsOfService / 2);
    }

    public function getAccrualBreakdown($hireDateGregorian, $targetDateGregorian = null)
    {
        $hireDate = Carbon::parse($hireDateGregorian)->startOfDay();
        $targetDate = $targetDateGregorian ? Carbon::parse($targetDateGregorian)->startOfDay() : Carbon::now()->startOfDay();
        
        if ($targetDate->lt($hireDate)) return [];

        $breakdown = [];
        $currentStart = $hireDate->copy();
        $yearNumber = 1;
        $runningTotal = 0.0;

        while ($currentStart->lt($targetDate)) {
            $yearsOfService = $hireDate->diffInYears($currentStart);
            $annualEntitlement = $this->calculateAnnualEntitlement($yearsOfService);
            $currentEnd = $currentStart->copy()->addYear();
            $endCompute = $targetDate->lt($currentEnd) ? $targetDate : $currentEnd;
            $daysWorked = $currentStart->diffInDays($endCompute);
            $totalDays = $currentStart->diffInDays($currentEnd);
            $periodAccrual = round(($daysWorked / $totalDays) * $annualEntitlement, 4);
            $runningTotal += $periodAccrual;

            $breakdown[] = [
                'year' => $yearNumber,
                'period_start' => $currentStart->format('Y-m-d'),
                'period_end' => $currentEnd->format('Y-m-d'),
                'computation_end' => $endCompute->format('Y-m-d'),
                'days_worked' => $daysWorked,
                'total_days_in_year' => $totalDays,
                'annual_entitlement' => $annualEntitlement,
                'period_accrual' => $periodAccrual,
                'running_total' => round($runningTotal, 4),
                'daily_rate' => round($annualEntitlement / $totalDays, 6),
            ];

            $currentStart->addYear();
            $yearNumber++;
        }

        return $breakdown;
    }
}
