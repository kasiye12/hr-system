<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_documents', 'applicant_id')) {
                $table->unsignedBigInteger('applicant_id')->nullable()->after('id');
                $table->foreign('applicant_id')->references('id')->on('applicants')->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            if (Schema::hasColumn('employee_documents', 'applicant_id')) {
                $table->dropForeign(['applicant_id']);
                $table->dropColumn('applicant_id');
            }
        });
    }
};
