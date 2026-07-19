<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Leave Types Table
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // annual, sick, maternity, paternity, marriage, bereavement, unpaid
            $table->text('description')->nullable();
            $table->integer('default_days')->default(0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_medical_certificate')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Leave Entitlements (per employee per year)
        Schema::create('leave_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');
            $table->integer('year');
            $table->decimal('total_days', 5, 1)->default(0);
            $table->decimal('used_days', 5, 1)->default(0);
            $table->decimal('remaining_days', 5, 1)->default(0);
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        // Leave Requests Table
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 5, 1);
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
            $table->string('attachment_path')->nullable(); // For medical certificates
            $table->text('rejection_reason')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        // Leave Balances (real-time tracking)
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');
            $table->decimal('total_entitled', 5, 1)->default(0);
            $table->decimal('used_days', 5, 1)->default(0);
            $table->decimal('pending_days', 5, 1)->default(0);
            $table->decimal('available_days', 5, 1)->default(0);
            $table->date('last_calculated_at')->nullable();
            $table->timestamps();
        });

        // Leave Settings (company policies)
        Schema::create('leave_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Public Holidays
        Schema::create('public_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date');
            $table->string('calendar_type')->default('gregorian'); // gregorian or ethiopian
            $table->boolean('recurring_yearly')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Leave Audit Log
        Schema::create('leave_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->nullable()->constrained('leave_requests')->onDelete('set null');
            $table->string('action'); // created, approved, rejected, cancelled, updated
            $table->string('performed_by');
            $table->text('details')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_audit_logs');
        Schema::dropIfExists('public_holidays');
        Schema::dropIfExists('leave_settings');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_entitlements');
        Schema::dropIfExists('leave_types');
    }
};
