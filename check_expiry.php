<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Applicant;
use App\Services\LeaveExpiryService;
use Carbon\Carbon;

$service = new LeaveExpiryService();
$today = Carbon::now();

echo "Today: " . $today->format('Y-m-d') . "\n\n";
echo "EXPIRY RULE: Leave expires 2 years AFTER the end of the leave year\n";
echo str_repeat('=', 70) . PHP_EOL;

foreach (Applicant::all() as $app) {
    $hireDate = $app->registration_date;
    if (!$hireDate) continue;
    
    $hireCarbon = Carbon::parse($hireDate);
    
    echo "\n👤 " . $app->full_name . " (Hired: " . $hireDate . ")\n";
    echo str_repeat('-', 70) . PHP_EOL;
    echo sprintf("%-30s %-15s %-15s %s\n", 'Leave Year', 'Expiry Date', 'Status', 'Days');
    echo str_repeat('-', 70) . PHP_EOL;
    
    $totalExpired = 0;
    
    for ($year = $hireCarbon->year; $year <= $today->year; $year++) {
        $leaveStart = Carbon::create($year, $hireCarbon->month, $hireCarbon->day);
        $leaveEnd = (clone $leaveStart)->addYear()->subDay();
        $expiryDate = (clone $leaveEnd)->addYears(2);
        
        $isExpired = $expiryDate->lt($today);
        
        echo sprintf("%-30s %-15s %-15s",
            $leaveStart->format('M Y') . ' - ' . $leaveEnd->format('M Y'),
            $expiryDate->format('M d, Y'),
            $isExpired ? '❌ EXPIRED' : '✅ ACTIVE'
        );
        
        if ($isExpired) {
            $expiredData = $service->calculateExpiredLeave($app);
            foreach ($expiredData['details'] as $ed) {
                if (strpos($ed['leave_year'], $leaveStart->format('M Y')) !== false) {
                    echo sprintf(" %.1f days", $ed['expired']);
                    $totalExpired += $ed['expired'];
                }
            }
        }
        echo PHP_EOL;
    }
    
    echo str_repeat('-', 70) . PHP_EOL;
    echo "TOTAL EXPIRED: " . number_format($totalExpired, 1) . " days\n";
}

echo "\n" . str_repeat('=', 70) . PHP_EOL;
echo "RULE SUMMARY:\n";
echo "• Leave year 2023-2024 → Expires: ~Sep 2026 (2 years after leave year ends)\n";
echo "• Leave year 2024-2025 → Expires: ~Sep 2027\n";
echo "• Leave year 2025-2026 → Expires: ~Sep 2028 (NOT expired yet!)\n";
echo "• Only leave years where expiry date < TODAY are expired\n";
