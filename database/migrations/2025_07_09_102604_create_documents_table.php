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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('national_id')->nullable();
            $table->string('passport')->nullable();
            $table->string('resume')->nullable();
            $table->string('educational_certificates')->nullable();
            $table->string('experience_letters')->nullable();
            $table->string('other_documents')->nullable();

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
        Schema::dropIfExists('documents');
    }
};
