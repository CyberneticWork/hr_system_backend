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
        Schema::create('pay_deductions', function (Blueprint $table) {
            $table->id();
            $table->string('pay_deduction_code')->unique();
            $table->string('pay_deduction_name');
            $table->decimal('pay_deduction_amount', 10, 2)->default(0);

            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_deductions');
    }
};
