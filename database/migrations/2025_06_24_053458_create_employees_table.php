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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('attendance_employee_no')->unique();
            $table->integer('epf')->unique();
            $table->string('nic')->unique();
            $table->date('dob');
            $table->enum('gender', ['male', 'female', 'other']);

            $table->string('name_with_initials');
            $table->string('full_name');
            $table->string('display_name')->nullable();

            $table->boolean('is_active');

            $table->foreignId('employment_type_id')->constrained('employment_types')->onDelete('cascade');

            $table->boolean('marital_status')->nullable()->default(false);

            $table->foreignId('spouse_id')->constrained('spouses')->onDelete('cascade');

            $table->string('religion')->nullable();
            $table->string('country_of_birth')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
