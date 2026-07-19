<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('leave_carry_forwards')) {
            Schema::create('leave_carry_forwards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('applicant_id')->constrained('applicants')->onDelete('cascade');
                $table->integer('year');
                $table->integer('days')->default(0);
                $table->boolean('is_expired')->default(false);
                $table->timestamps();
                $table->unique(['applicant_id', 'year']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('leave_carry_forwards');
    }
};
