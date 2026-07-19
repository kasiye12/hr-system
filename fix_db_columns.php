<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

echo "Fixing database columns...\n\n";

// leave_balances
$cols = Schema::getColumnListing('leave_balances');
echo "leave_balances: " . implode(', ', $cols) . PHP_EOL;

if (!in_array('used_current_year', $cols) && in_array('used_days', $cols)) {
    Schema::table('leave_balances', function(Blueprint $table) {
        $table->renameColumn('used_days', 'used_current_year');
    });
    echo "✅ Renamed used_days → used_current_year\n";
}

if (!in_array('carry_forward_days', $cols)) {
    Schema::table('leave_balances', function(Blueprint $table) {
        $table->decimal('carry_forward_days', 5, 1)->default(0)->after('available_days');
        $table->date('carry_forward_expiry')->nullable()->after('carry_forward_days');
    });
    echo "✅ Added carry_forward columns\n";
}

if (!in_array('total_entitled', $cols)) {
    Schema::table('leave_balances', function(Blueprint $table) {
        $table->decimal('total_entitled', 5, 1)->default(0)->after('leave_type_id');
    });
    echo "✅ Added total_entitled\n";
}

if (!in_array('available_days', $cols)) {
    Schema::table('leave_balances', function(Blueprint $table) {
        $table->decimal('available_days', 5, 1)->default(0)->after('pending_days');
    });
    echo "✅ Added available_days\n";
}

if (!in_array('pending_days', $cols)) {
    Schema::table('leave_balances', function(Blueprint $table) {
        $table->decimal('pending_days', 5, 1)->default(0)->after('used_current_year');
    });
    echo "✅ Added pending_days\n";
}

echo "\nFinal columns: " . implode(', ', Schema::getColumnListing('leave_balances')) . PHP_EOL;
