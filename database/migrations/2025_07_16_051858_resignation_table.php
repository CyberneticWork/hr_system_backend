<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('resignations', function (Blueprint $table) {
            $table->id();
            
            // Employee details
            $table->foreignId('employee_id')->constrained('employees');
            
            // Resignation details
            $table->date('resigning_date');
            $table->date('last_working_day');
            $table->text('resignation_reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            
            // Document tracking
            $table->string('exit_interview_form_path')->nullable();
            $table->string('clearance_form_path')->nullable();
            
            // Audit fields
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->timestamp('processed_at')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes
            $table->index('employee_id');
            $table->index('status');
        });

        // Separate table for multiple documents
        Schema::create('resignation_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resignation_id')->constrained('resignations')->onDelete('cascade');
            $table->string('document_name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
            
            $table->softDeletes();
       
            $table->index('resignation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resignation_documents');
        Schema::dropIfExists('resignations');
    }
};