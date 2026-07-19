<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->string('employee_name', 200);
            $table->string('employee_id', 50)->nullable();
            $table->string('document_type', 50);
            $table->string('document_name', 255);
            $table->string('file_path', 500);
            $table->bigInteger('file_size');
            $table->string('file_type', 100);
            $table->string('uploaded_by', 100);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_documents');
    }
};