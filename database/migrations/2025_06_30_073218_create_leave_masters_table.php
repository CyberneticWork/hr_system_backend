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

            $table->date('reporting_date');
            $table->string('leave_type');
            $table->date('leave_date')->nullable();
            $table->date('leave_from')->nullable();
            $table->date('leave_to')->nullable();
            $table->string('period')->nullable();

            $table->text('reason')->nullable();
            $table->enum('status', ['Pending', 'Approved', 'HR_Approved', 'Rejected'])->default('Pending');

            $table->softDeletes();
            $table->timestamps();

            $table->index('employee_id');

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
