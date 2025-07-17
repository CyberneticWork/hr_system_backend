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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();

            $table->string('shift_code')->unique();
            $table->string('shift_description');

            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->time('morning_ot_start')->nullable();
            $table->time('special_ot_start')->nullable();
            $table->time('late_deduction')->nullable();
            $table->string('break_time')->nullable();

            $table->boolean('midnight_roster')->default(false);
            $table->decimal('nopay_hour_halfday', 4, 2)->nullable();

            $table->softDeletes();
            $table->timestamps();


            $table->index('shift_code');
            $table->index('start_time');
            $table->index('end_time');
            $table->index('morning_ot_start');
            $table->index('special_ot_start');
            $table->index('late_deduction');
         
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
