<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

$columns = Schema::getColumnListing('leave_requests');
echo "Current columns: " . implode(', ', $columns) . PHP_EOL;

$added = [];

if (!in_array('working_days', $columns)) {
    Schema::table('leave_requests', function(Blueprint $table) {
        $table->decimal('working_days', 5, 1)->default(0)->after('total_days');
    });
    $added[] = 'working_days';
}

if (!in_array('calendar_days', $columns)) {
    Schema::table('leave_requests', function(Blueprint $table) {
        $table->decimal('calendar_days', 5, 1)->default(0)->after('working_days');
    });
    $added[] = 'calendar_days';
}

if (!in_array('block_number', $columns)) {
    Schema::table('leave_requests', function(Blueprint $table) {
        $table->integer('block_number')->default(1)->after('calendar_days');
    });
    $added[] = 'block_number';
}

if (!in_array('pay_tier', $columns)) {
    Schema::table('leave_requests', function(Blueprint $table) {
        $table->string('pay_tier')->nullable()->after('status');
    });
    $added[] = 'pay_tier';
}

if (!in_array('is_paid', $columns)) {
    Schema::table('leave_requests', function(Blueprint $table) {
        $table->boolean('is_paid')->default(true)->after('status');
    });
    $added[] = 'is_paid';
}

if (empty($added)) {
    echo "All columns already exist.\n";
} else {
    echo "Added: " . implode(', ', $added) . PHP_EOL;
}

$columns = Schema::getColumnListing('leave_requests');
echo "Final columns: " . implode(', ', $columns) . PHP_EOL;
