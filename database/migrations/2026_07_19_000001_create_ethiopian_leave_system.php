<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Company Leave Configuration
        Schema::create('company_leave_config', function (Blueprint $table) {
            $table->id();
            $table->string('calendar_system')->default('gregorian'); // gregorian or ethiopian
            $table->string('accrual_method')->default('anniversary'); // anniversary or fiscal_year
            $table->integer('working_days_per_week')->default(6); // Ethiopian default: 6 days
            $table->integer('max_weekly_hours')->default(48);
            $table->integer('annual_leave_blocks_max')->default(2); // Max 2 blocks per year
            $table->integer('carry_forward_max_years')->default(2); // Max 2 years carry-over
            $table->integer('sick_leave_probation_days')->default(45); // 45-day probation
            $table->integer('sick_leave_medical_cert_threshold')->default(1); // Certificate required after 1 day
            $table->integer('urgent_family_max_per_year')->default(2); // Max 2x per budget year
            $table->boolean('allow_micro_leaves')->default(false); // Block 1-2 day frequent leaves
            $table->timestamps();
        });

        // Leave Types with Ethiopian Law Compliance
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->integer('default_days')->default(0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_calendar_days')->default(false); // true for maternity, false for working days
            $table->boolean('requires_document')->default(false);
            $table->boolean('accrues_over_time')->default(false); // true for annual leave
            $table->boolean('resets_annually')->default(true);
            $table->string('legal_reference')->nullable(); // Proclamation article reference
            $table->json('pay_tiers')->nullable(); // For sick leave: 100%/50%/0%
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Employee Leave Year Tracking (based on anniversary date)
        Schema::create('employee_leave_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->onDelete('cascade');
            $table->date('leave_year_start');
            $table->date('leave_year_end');
            $table->integer('years_of_service_at_start');
            $table->decimal('annual_leave_entitled', 5, 1);
            $table->decimal('annual_leave_used', 5, 1)->default(0);
            $table->decimal('carry_forward_from_previous', 5, 1)->default(0);
            $table->decimal('carry_forward_expiry_date', 5, 1)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Leave Requests
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');
            $table->foreignId('leave_year_id')->nullable()->constrained('employee_leave_years')->onDelete('set null');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 5, 1);
            $table->decimal('working_days', 5, 1);
            $table->decimal('calendar_days', 5, 1);
            $table->integer('block_number')->default(1); // For annual leave block tracking
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
            $table->string('attachment_path')->nullable();
            $table->string('document_type')->nullable(); // medical_certificate, marriage_cert, etc.
            $table->text('rejection_reason')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('created_by')->nullable();
            
            // Payroll integration fields
            $table->boolean('is_paid')->default(true);
            $table->string('pay_tier')->nullable(); // 100, 50, 0
            $table->decimal('daily_rate_at_request', 10, 2)->nullable();
            $table->decimal('total_payable', 10, 2)->nullable();
            
            $table->timestamps();
        });

        // Leave Balances (real-time)
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');
            $table->decimal('total_entitled', 5, 1)->default(0);
            $table->decimal('used_current_year', 5, 1)->default(0);
            $table->decimal('pending_days', 5, 1)->default(0);
            $table->decimal('available_days', 5, 1)->default(0);
            $table->decimal('carry_forward_days', 5, 1)->default(0);
            $table->date('carry_forward_expiry')->nullable();
            $table->decimal('sick_leave_used_12m', 5, 1)->default(0); // Rolling 12-month sick leave
            $table->date('last_calculated_at')->nullable();
            $table->timestamps();
        });

        // Public Holidays
        Schema::create('public_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('gregorian_date');
            $table->string('ethiopian_date')->nullable();
            $table->string('calendar_type')->default('gregorian');
            $table->boolean('is_fixed_date')->default(false); // true for fixed, false for variable
            $table->boolean('recurring_yearly')->default(true);
            $table->string('religion_type')->nullable(); // orthodox, islamic, national
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Holiday Override Log (for variable holidays)
        Schema::create('holiday_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('public_holiday_id')->constrained('public_holidays')->onDelete('cascade');
            $table->integer('year');
            $table->date('actual_date');
            $table->string('updated_by');
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        // Leave Audit Log (immutable)
        Schema::create('leave_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->nullable()->constrained('leave_requests')->onDelete('set null');
            $table->string('action'); // created, approved, rejected, cancelled, balance_modified
            $table->string('performed_by');
            $table->string('manager_id')->nullable();
            $table->string('reason_code')->nullable();
            $table->text('details')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('performed_at')->useCurrent();
            $table->timestamps();
        });

        // Termination Leave Payout
        Schema::create('leave_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->onDelete('cascade');
            $table->date('termination_date');
            $table->decimal('outstanding_annual_leave', 5, 1)->default(0);
            $table->decimal('daily_rate', 10, 2);
            $table->decimal('total_payout_amount', 12, 2);
            $table->boolean('paid')->default(false);
            $table->date('paid_date')->nullable();
            $table->string('processed_by');
            $table->timestamps();
        });

        // Ministry of Labor Report Log (Article 6 Register)
        Schema::create('labor_register_exports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type'); // article_6_register
            $table->date('report_period_start');
            $table->date('report_period_end');
            $table->string('generated_by');
            $table->string('file_path');
            $table->json('report_data')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('labor_register_exports');
        Schema::dropIfExists('leave_payouts');
        Schema::dropIfExists('leave_audit_logs');
        Schema::dropIfExists('holiday_overrides');
        Schema::dropIfExists('public_holidays');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('employee_leave_years');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('company_leave_config');
    }
};
