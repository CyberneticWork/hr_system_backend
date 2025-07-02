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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id'); // Foreign key
            $table->decimal('loan_amount', 10, 2);
            $table->decimal('interest_rate_per_annum', 5, 2)->default(0);
            $table->decimal('installment_amount', 10, 2);
            $table->date('start_from');
            $table->boolean('with_interest')->default(false);

            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
