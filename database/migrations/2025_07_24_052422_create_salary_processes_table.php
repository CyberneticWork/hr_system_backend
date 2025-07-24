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
        Schema::create('salary_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('process_date');
            $table->decimal('basic', 10, 2);
            $table->decimal('basic_salary', 10, 2);

            $table->foreignId('no_pay_records_id')->constrained('no_pay_records')->onDelete('cascade');
            $table->foreignId('over_times_id')->constrained('over_times')->onDelete('cascade');
            $table->foreignId('allowances_id')->constrained('allowances')->onDelete('cascade');
            $table->foreignId('loans_id')->constrained('loans')->onDelete('cascade')->nullable();
            $table->foreignId('deductions_id')->constrained('deductions')->onDelete('cascade')->nullable();


            $table->decimal('gross_amount', 10, 2)->default(0);
            $table->decimal('salary_advance', 10, 2)->default(0);
            $table->decimal('net_salary', 10, 2)->default(0);
            $table->enum('status', ['Pending', 'Processed'])->default('Pending');
            $table->foreignId('processed_by')->nullable()->constrained('users');

            $table->softDeletes();
            $table->timestamps();

            $table->index(['employee_id', 'process_date']);
            $table->index(['status']);
            $table->index(['processed_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_processes');
    }
};
