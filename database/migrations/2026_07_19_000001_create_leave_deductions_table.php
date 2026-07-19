<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('leave_deductions')) {
            Schema::create('leave_deductions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('applicant_id')->constrained('applicants')->onDelete('cascade');
                $table->foreignId('leave_request_id')->unique()->constrained('leave_requests')->onDelete('cascade');
                $table->decimal('days', 5, 1);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('leave_deductions');
    }
};
