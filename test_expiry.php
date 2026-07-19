<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Applicant;
use App\Services\LeaveExpiryService;

$service = new LeaveExpiryService();

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     CARRY FORWARD & EXPIRED LEAVE REPORT                    ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

foreach (Applicant::all() as $app) {
    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ 👤 " . str_pad($app->full_name, 54) . "│\n";
    echo "│ 📅 Hired: " . str_pad($app->registration_date, 50) . "│\n";
    echo "├─────────────────────────────────────────────────────────────┤\n";
    
    // ===== CARRY FORWARD ELIGIBILITY =====
    $cf = $service->calculateEligibleCarryForward($app);
    echo "│ 📦 CARRY FORWARD ELIGIBILITY:                               │\n";
    echo sprintf("│   Previous Year: %-42s │\n", $cf['leave_year']);
    echo sprintf("│   Accrued: %5.1f | Carry Received: %5.1f | Total: %5.1f   │\n", 
        $cf['accrued'], $cf['carry_received'], $cf['total_available']);
    echo sprintf("│   Used: %5.1f | UNUSED: %5.1f days                          │\n", 
        $cf['used'], $cf['unused']);
    
    if ($cf['already_forwarded']) {
        echo "│   ✅ Already Forwarded                                      │\n";
    } elseif ($cf['unused'] > 0) {
        echo sprintf("│   ⏳ Eligible to Forward: %5.1f days (Expires: %s)     │\n", 
            $cf['unused'], $cf['expiry_if_forwarded']);
    } else {
        echo "│   ✅ No unused leave to forward                             │\n";
    }
    
    // ===== EXPIRED LEAVE =====
    $expired = $service->calculateExpiredLeave($app);
    echo "├─────────────────────────────────────────────────────────────┤\n";
    echo "│ ⏰ EXPIRED LEAVE:                                           │\n";
    
    if (count($expired['details']) > 0) {
        foreach ($expired['details'] as $ed) {
            echo sprintf("│   • %s: %.1f days unused → EXPIRED (%s)      │\n",
                $ed['leave_year'], $ed['expired'], $ed['expiry_date']);
        }
        echo sprintf("│   TOTAL EXPIRED: %.1f days (CANNOT be used)                  │\n", $expired['total']);
    } else {
        echo "│   ✅ No expired leave                                       │\n";
    }
    
    echo "└─────────────────────────────────────────────────────────────┘\n\n";
}

echo "══════════════════════════════════════════════════════════════\n";
echo "  Ethiopian Labor Law (Proclamation 1156/2011):\n";
echo "  • Unused leave can be carried forward for MAX 2 years\n";
echo "  • After 2 years from leave year end → EXPIRED permanently\n";
echo "  • Expired leave CANNOT be used or paid out\n";
echo "══════════════════════════════════════════════════════════════\n";
