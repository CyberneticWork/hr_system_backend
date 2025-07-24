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
        Schema::create('allowances', function (Blueprint $table) {
            $table->id();
            $table->string('allowance_code')->unique();
            $table->string('allowance_name');


            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('category', ['travel', 'bonus', 'perfomance', 'health', 'other'])->default('other');
            $table->enum('allowance_type', ['fixed', 'variable'])->default('fixed');

            $table->decimal('production_incentive', 10, 2)->default(0);
            $table->decimal('medical_reimbursement', 10, 2)->default(0);

            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('department_id')->constrained('departments');
            $table->decimal('amount', 50, 2)->default(0.00);
            $table->date('fixed_date')->nullable();
            $table->date('variable_from')->nullable();
            $table->date('variable_to')->nullable();
            $table->softDeletes();
            $table->timestamps();


            $table->index('allowance_code');
            $table->index('allowance_name');
            $table->index('company_id');
            $table->index('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allowances');
    }
};
