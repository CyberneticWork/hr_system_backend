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
        Schema::create('completed_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('loan_id')->constrained('loans')->onDelete('cascade');
            $table->decimal('loan_amount', 10, 2);
            $table->decimal('interest_rate_per_annum', 5, 2)->default(0);
            $table->boolean('with_interest')->default(false);
            $table->integer('installment_count')->nullable();
            $table->date('end_date');
            $table->timestamps();

            $table->index('employee_id');
            $table->index('loan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('completed_loans');
    }
};
