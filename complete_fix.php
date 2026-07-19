<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Applicant;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\CarryForwardRequest;
use App\Services\LeaveCalculationService;
use App\Services\EthiopianCalendarService;
use Carbon\Carbon;

$calc = new LeaveCalculationService();
$ethCal = new EthiopianCalendarService();
$annualType = LeaveType::where('code', 'annual')->first();

if (!$annualType) die("Annual leave type not found!\n");

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     COMPLETE LEAVE DATA RECALCULATION                       ║\n";
echo "║     Ethiopian Pro-rata + Carry Forward + Expired + Used     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// First, clear all balances to start fresh
LeaveBalance::truncate();
echo "✅ Old balances cleared\n\n";

foreach (Applicant::all() as $app) {
    $hireDate = $app->registration_date;
    if (!$hireDate) continue;
    
    $hireCarbon = Carbon::parse($hireDate);
    $today = Carbon::now();
    $yearsOfService = $hireCarbon->diffInYears($today);
    
    // ============================================
    // 1. PRO-RATA ACCRUED (Total lifetime)
    // ============================================
    $totalAccrued = $calc->calculateAccruedLeave($hireDate);
    
    // ============================================
    // 2. USED DAYS (All approved annual leave)
    // ============================================
    $usedDays = LeaveRequest::where('applicant_id', $app->id)
        ->where('leave_type_id', $annualType->id)
        ->where('status', 'approved')
        ->sum('total_days');
    
    // ============================================
    // 3. PENDING DAYS
    // ============================================
    $pendingDays = LeaveRequest::where('applicant_id', $app->id)
        ->where('leave_type_id', $annualType->id)
        ->where('status', 'pending')
        ->sum('total_days');
    
    // ============================================
    // 4. CARRY FORWARD (Approved by admin)
    // ============================================
    $carryForward = CarryForwardRequest::where('applicant_id', $app->id)
        ->where('status', 'approved')
        ->sum('days');
    
    // ============================================
    // 5. EXPIRED LEAVE (Unused from 2+ years ago)
    // ============================================
    $totalExpired = 0;
    $expiredDetails = [];
    
    for ($year = $hireCarbon->year; $year <= $today->year; $year++) {
        $leaveStart = Carbon::create($year, $hireCarbon->month, $hireCarbon->day);
        $leaveEnd = (clone $leaveStart)->addYear()->subDay();
        $expiryDate = (clone $leaveEnd)->addYears(2);
        
        // Skip if not yet expired
        if ($expiryDate->gt($today)) continue;
        
        // Accrued in this year
        $startAcc = $calc->calculateAccruedLeave($hireDate, $leaveStart);
        $endAcc = $calc->calculateAccruedLeave($hireDate, $leaveEnd);
        $yearAccrued = round($endAcc - $startAcc, 1);
        
        // Used in this year
        $yearUsed = LeaveRequest::where('applicant_id', $app->id)
            ->where('leave_type_id', $annualType->id)
            ->where('status', 'approved')
            ->whereBetween('start_date', [$leaveStart, $leaveEnd])
            ->sum('total_days');
        
        $yearUnused = max(0, $yearAccrued - $yearUsed);
        if ($yearUnused > 0.05) {
            $expiredDetails[] = [
                'year' => $year,
                'period' => $leaveStart->format('M Y').' - '.$leaveEnd->format('M Y'),
                'accrued' => $yearAccrued,
                'used' => $yearUsed,
                'unused' => $yearUnused,
                'expired' => $yearUnused,
                'expiry' => $expiryDate->format('M d, Y'),
            ];
            $totalExpired += $yearUnused;
        }
    }
    
    // ============================================
    // 6. AVAILABLE = Accrued - Used - Pending - Expired + Carry
    // ============================================
    $available = max(0, $totalAccrued - $usedDays - $pendingDays - $totalExpired + $carryForward);
    
    // ============================================
    // UPDATE BALANCE
    // ============================================
    LeaveBalance::create([
        'applicant_id' => $app->id,
        'leave_type_id' => $annualType->id,
        'total_entitled' => round($totalAccrued, 1),
        'used_current_year' => $usedDays,
        'pending_days' => $pendingDays,
        'available_days' => round($available, 1),
        'carry_forward_days' => $carryForward,
        'carry_forward_expiry' => $carryForward > 0 ? Carbon::now()->addYears(2) : null,
        'last_calculated_at' => now(),
    ]);
    
    // Display
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ 👤 " . str_pad($app->full_name, 54) . "│\n";
    echo "│ 📅 Hired: " . str_pad($hireDate . " (" . $yearsOfService . " yrs)", 50) . "│\n";
    echo "├─────────────────────────────────────────────────────────────┤\n";
    echo sprintf("│ 📐 Total Accrued:    %8.1f days                          │\n", $totalAccrued);
    echo sprintf("│ ➖ Used:             %8.1f days                          │\n", $usedDays);
    echo sprintf("│ ⏳ Pending:          %8.1f days                          │\n", $pendingDays);
    echo sprintf("│ 📦 Carry Forward:    %8.1f days                          │\n", $carryForward);
    echo sprintf("│ ⏰ Expired:          %8.1f days                          │\n", $totalExpired);
    echo sprintf("│ ✅ AVAILABLE:        %8.1f days                          │\n", $available);
    
    // Show used dates
    $leaves = LeaveRequest::where('applicant_id', $app->id)
        ->where('leave_type_id', $annualType->id)
        ->where('status', 'approved')
        ->orderBy('start_date', 'desc')
        ->get();
    
    if ($leaves->count() > 0) {
        echo "├─────────────────────────────────────────────────────────────┤\n";
        echo "│ 📋 Used Leave Details:                                      │\n";
        foreach ($leaves as $l) {
            $ethStart = $ethCal->gregorianToEthiopian($l->start_date);
            echo sprintf("│   • %s - %s (%s days) [🇪🇹 %s %s %s]              │\n",
                $l->start_date->format('M d, Y'),
                $l->end_date->format('M d, Y'),
                $l->total_days,
                $ethStart['day'],
                $ethStart['month_name'],
                $ethStart['year']
            );
        }
    }
    
    // Show expired details
    if (count($expiredDetails) > 0) {
        echo "├─────────────────────────────────────────────────────────────┤\n";
        echo "│ ⏰ Expired Leave Details:                                    │\n";
        foreach ($expiredDetails as $ed) {
            echo sprintf("│   • %s: %.1f days (Expired: %s)     │\n",
                $ed['period'], $ed['expired'], $ed['expiry']
            );
        }
    }
    
    echo "└─────────────────────────────────────────────────────────────┘\n\n";
    
    // ============================================
    // OTHER LEAVE TYPES
    // ============================================
    foreach (LeaveType::where('code', '!=', 'annual')->where('active', true)->get() as $type) {
        $used = LeaveRequest::where('applicant_id', $app->id)
            ->where('leave_type_id', $type->id)->where('status', 'approved')
            ->whereYear('start_date', now()->year)->sum('total_days');
        $pending = LeaveRequest::where('applicant_id', $app->id)
            ->where('leave_type_id', $type->id)->where('status', 'pending')
            ->whereYear('start_date', now()->year)->sum('total_days');
        $avail = max(0, $type->default_days - $used - $pending);
        
        LeaveBalance::create([
            'applicant_id' => $app->id,
            'leave_type_id' => $type->id,
            'total_entitled' => $type->default_days,
            'used_current_year' => $used,
            'pending_days' => $pending,
            'available_days' => $avail,
            'last_calculated_at' => now(),
        ]);
    }
}

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  ✅ ALL DONE! Balances, Used Dates, Expired, Carry Forward  ║\n";
echo "║  Visit: http://127.0.0.1:8000/leaves/balances               ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
