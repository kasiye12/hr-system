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
use App\Services\LeaveExpiryService;

$calc = new LeaveCalculationService();
$expiryService = new LeaveExpiryService();
$annualType = LeaveType::where('code', 'annual')->first();

if (!$annualType) die("Annual type not found!\n");

echo "Complete recalculation with Expired + Carry Forward...\n";
echo str_repeat('=', 80) . PHP_EOL;
echo sprintf("%-22s %8s %8s %8s %8s %8s %8s %8s\n", 
    'Employee', 'Accrued', 'Used', 'Pending', 'Carry', 'Expired', 'Avail', 'CF Avail');
echo str_repeat('-', 80) . PHP_EOL;

foreach (Applicant::all() as $app) {
    $hireDate = $app->registration_date;
    if (!$hireDate) continue;
    
    $totalAccrued = $calc->calculateAccruedLeave($hireDate);
    
    $usedDays = LeaveRequest::where('applicant_id', $app->id)
        ->where('leave_type_id', $annualType->id)
        ->where('status', 'approved')
        ->sum('total_days');
    
    $pendingDays = LeaveRequest::where('applicant_id', $app->id)
        ->where('leave_type_id', $annualType->id)
        ->where('status', 'pending')
        ->sum('total_days');
    
    $carryForward = CarryForwardRequest::where('applicant_id', $app->id)
        ->where('status', 'approved')
        ->sum('days');
    
    $expiredData = $expiryService->calculateExpiredLeave($app);
    $totalExpired = $expiredData['total'];
    
    // Available = Accrued - Used - Pending - Expired + Carry Forward
    $available = max(0, $totalAccrued - $usedDays - $pendingDays - $totalExpired + $carryForward);
    
    // Update balance
    LeaveBalance::updateOrCreate(
        ['applicant_id' => $app->id, 'leave_type_id' => $annualType->id],
        [
            'total_entitled' => round($totalAccrued, 1),
            'used_current_year' => $usedDays,
            'pending_days' => $pendingDays,
            'available_days' => round($available, 1),
            'carry_forward_days' => $carryForward,
            'carry_forward_expiry' => $carryForward > 0 ? now()->addYears(2) : null,
            'last_calculated_at' => now(),
        ]
    );
    
    echo sprintf("%-22s %8.1f %8.1f %8.1f %8.1f %8.1f %8.1f %8.1f\n",
        substr($app->full_name, 0, 21),
        $totalAccrued, $usedDays, $pendingDays,
        $carryForward, $totalExpired, $available, $available
    );
    
    // Other leave types
    foreach (LeaveType::where('code', '!=', 'annual')->where('active', true)->get() as $type) {
        $used = LeaveRequest::where('applicant_id', $app->id)
            ->where('leave_type_id', $type->id)->where('status', 'approved')
            ->whereYear('start_date', now()->year)->sum('total_days');
        $pending = LeaveRequest::where('applicant_id', $app->id)
            ->where('leave_type_id', $type->id)->where('status', 'pending')
            ->whereYear('start_date', now()->year)->sum('total_days');
        $avail = max(0, $type->default_days - $used - $pending);
        
        LeaveBalance::updateOrCreate(
            ['applicant_id' => $app->id, 'leave_type_id' => $type->id],
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

echo str_repeat('=', 80) . PHP_EOL;
echo "✅ All balances updated!\n";
echo "Formula: Available = Accrued - Used - Pending - Expired + Carry Forward\n";
