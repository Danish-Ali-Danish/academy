<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_carry_forwards', function (Blueprint $table) {
            $table->id();
            $table->integer('student_enrollment_id');
            $table->integer('branch_id')->nullable();
            $table->integer('academic_year_id');
            $table->integer('from_voucher_id')->nullable();
            $table->string('from_month_name');
            $table->string('to_month_name');
            $table->decimal('original_amount', 10, 2);
            $table->decimal('carry_amount', 10, 2);
            $table->enum('status', ['pending', 'partially_paid', 'cleared'])->default('pending');
            $table->date('cleared_on')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_carry_forwards');
    }
};
