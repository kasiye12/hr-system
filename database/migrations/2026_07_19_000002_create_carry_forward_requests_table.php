<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('carry_forward_requests')) {
            Schema::create('carry_forward_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('applicant_id')->constrained('applicants')->onDelete('cascade');
                $table->integer('from_year');
                $table->integer('to_year');
                $table->decimal('days', 5, 1);
                $table->string('status')->default('pending'); // pending, approved, rejected
                $table->string('reason')->nullable();
                $table->string('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->date('expiry_date')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('carry_forward_requests');
    }
};
