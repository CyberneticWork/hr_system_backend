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
        Schema::create('rosters', function (Blueprint $table) {
            $table->id();
            $table->string('roster_id'); // e.g., daily, weekly, monthly

            $table->foreignId('shift_code')->constrained('shifts')->onDelete('cascade');

            //Organizational hierarchy for assignment scope
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade')->nullable();
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade')->nullable();
            $table->foreignId('sub_department_id')->constrained('sub_departments')->onDelete('cascade')->nullable();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade')->nullable();

            $table->boolean('is_recurring')->default(true);
            $table->string('recurrence_pattern')->nullable(); // e.g., daily, weekly, monthly
            $table->string('notes')->nullable(); // e.g., end date for

            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('shift_code');
            $table->index('employee_id');
            $table->index('company_id');
            $table->index('department_id');
            $table->index('sub_department_id');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rosters');
    }
};
