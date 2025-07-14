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
        Schema::create('leave_masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');

            $table->string('attendance_no')->nullable();
            $table->string('epf_no')->nullable();
            $table->string('employee_name')->nullable();
            $table->string('department')->nullable();

            $table->date('join_date')->nullable();
            $table->date('reporting_date');
            $table->string('leave_type');

            $table->date('leave_from');
            $table->date('leave_to');
            $table->enum('leave_duration', ['Full Day', 'Half Day', 'Hour Leave'])->default('Full Day');

            $table->date('cancel_from')->nullable();
            $table->date('cancel_to')->nullable();
            $table->text('reason')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('attendance_no');
            $table->index('epf_no');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_masters');
    }
};
