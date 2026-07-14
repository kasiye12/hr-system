<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // ============================================
        // 1. SESSIONS TABLE
        // ============================================
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // ============================================
        // 2. CACHE TABLE
        // ============================================
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        // ============================================
        // 3. JOBS TABLE
        // ============================================
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // ============================================
        // 4. USERS TABLE
        // ============================================
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('password_hash', 255);
            $table->string('role', 50)->default('viewer');
            $table->boolean('active')->default(true);
            $table->boolean('must_change')->default(true);
            $table->timestamps();
        });

        // ============================================
        // 5. ORGANIZATIONS TABLE
        // ============================================
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // ============================================
        // 6. PROJECTS TABLE
        // ============================================
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->unique();
            $table->integer('slot')->nullable();
            $table->string('status', 50)->default('Active');
            $table->timestamps();
        });

        // ============================================
        // 7. CATEGORIES TABLE
        // ============================================
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 200);
            $table->string('department', 200);
            $table->string('employment_type', 50);
            $table->integer('row_order')->default(1);
            $table->boolean('is_reconciliation')->default(false);
            $table->timestamps();
        });

        // ============================================
        // 8. DAILY ENTRIES TABLE
        // ============================================
        Schema::create('daily_entries', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->integer('headcount')->default(0);
            $table->timestamps();
            
            $table->unique(['report_date', 'project_id', 'category_id'], 'uniq_daily_entry');
            $table->index('report_date', 'idx_daily_date');
            $table->index('project_id', 'idx_daily_project');
            $table->index('category_id', 'idx_daily_category');
        });

        // ============================================
        // 9. APPLICANT POSITIONS TABLE
        // ============================================
        Schema::create('applicant_positions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->unique();
            $table->boolean('active')->default(true);
            $table->string('grade', 100)->nullable();
            $table->string('salary', 100)->nullable();
            $table->string('requirement_type', 100)->nullable();
            $table->text('criteria')->nullable();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->timestamps();
            
            $table->index('organization_id', 'idx_app_pos_org');
            $table->index('active', 'idx_app_pos_active');
        });

        // ============================================
        // 10. APPLICANTS TABLE
        // ============================================
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained('applicant_positions')->onDelete('cascade');
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('surname', 100);
            $table->date('registration_date');
            $table->integer('experience_years')->default(0);
            $table->integer('experience_months')->default(0);
            $table->string('academic_level', 50);
            $table->string('academic_detail', 500)->nullable();
            $table->string('academic_field', 200)->nullable();
            $table->date('graduation_date')->nullable();
            $table->string('phone_primary', 50)->nullable();
            $table->string('phone_secondary', 50)->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();

            $table->index('registration_date', 'idx_applicants_date');
            $table->index('position_id', 'idx_applicants_pos');
            $table->index('academic_level', 'idx_applicants_academic');
        });

        // ============================================
        // 11. APPLICANT WORK EXPERIENCES TABLE
        // ============================================
        Schema::create('applicant_work_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->onDelete('cascade');
            $table->string('company', 200);
            $table->string('job_title', 200);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('specific_to_position')->default(false);
            $table->string('specific_position_title', 200)->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();

            $table->index('applicant_id', 'idx_work_exp_applicant');
            $table->index('specific_to_position', 'idx_work_exp_specific');
        });

        // ============================================
        // 12. SELECTION CRITERIA TABLE
        // ============================================
        Schema::create('selection_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('position_id')->nullable()->constrained('applicant_positions')->onDelete('cascade');
            $table->string('criterion_name', 200);
            $table->decimal('weight', 10, 2)->default(0);
            $table->decimal('max_score', 10, 2)->default(100);
            $table->decimal('pass_mark', 10, 2)->default(0);
            $table->integer('display_order')->default(1);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['organization_id', 'position_id', 'display_order'], 'idx_criteria_org_pos_order');
            $table->index('organization_id', 'idx_criteria_org');
            $table->index('position_id', 'idx_criteria_pos');
        });

        // ============================================
        // 13. APPLICANT SELECTIONS TABLE
        // ============================================
        Schema::create('applicant_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->unique()->constrained('applicants')->onDelete('cascade');
            $table->decimal('screening_score', 10, 2)->default(0);
            $table->decimal('written_score', 10, 2)->default(0);
            $table->decimal('interview_score', 10, 2)->default(0);
            $table->decimal('practical_score', 10, 2)->default(0);
            $table->decimal('final_score', 10, 2)->default(0);
            $table->integer('rank_no')->default(0);
            $table->string('decision', 50)->default('Pending');
            $table->date('selection_date')->nullable();
            $table->date('review_date')->nullable();
            $table->string('committee_members', 500)->nullable();
            $table->text('remarks')->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->index('decision', 'idx_app_sel_decision');
            $table->index('final_score', 'idx_app_sel_score');
            $table->index('rank_no', 'idx_app_sel_rank');
        });

        // ============================================
        // 14. APPLICANT CRITERION SCORES TABLE
        // ============================================
        Schema::create('applicant_criterion_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->onDelete('cascade');
            $table->foreignId('criterion_id')->constrained('selection_criteria')->onDelete('cascade');
            $table->decimal('score', 10, 2)->default(0);
            $table->text('comment')->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->unique(['applicant_id', 'criterion_id'], 'uniq_app_criterion');
            $table->index(['applicant_id', 'criterion_id'], 'idx_app_criterion');
            $table->index('applicant_id', 'idx_app_crit_applicant');
            $table->index('criterion_id', 'idx_app_crit_criterion');
        });

        // ============================================
        // 15. AUDIT LOGS TABLE
        // ============================================
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->string('action', 100);
            $table->text('details')->nullable();
            $table->timestamp('logged_at')->useCurrent();

            $table->index('username', 'idx_audit_username');
            $table->index('action', 'idx_audit_action');
            $table->index('logged_at', 'idx_audit_logged_at');
        });

        // ============================================
        // 16. PASSWORD RESET TOKENS TABLE
        // ============================================
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('applicant_criterion_scores');
        Schema::dropIfExists('applicant_selections');
        Schema::dropIfExists('selection_criteria');
        Schema::dropIfExists('applicant_work_experiences');
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('applicant_positions');
        Schema::dropIfExists('daily_entries');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('organizations');
        Schema::dropIfExists('users');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
    }
};