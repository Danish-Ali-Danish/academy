<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_installment_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_enrollment_id');
            $table->unsignedBigInteger('installment_plan_id');
            $table->unsignedBigInteger('fee_voucher_id')->nullable();
            
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('remaining_amount', 10, 2)->default(0);
            
            $table->enum('status', ['active', 'completed', 'cancelled', 'defaulted'])->default('active');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('installment_schedule', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->integer('kist_number');
            $table->decimal('kist_amount', 10, 2)->default(0);
            $table->date('due_date');
            
            $table->decimal('paid_amount', 10, 2)->nullable();
            $table->date('payment_date')->nullable();
            
            $table->enum('status', ['pending', 'paid', 'overdue', 'partial'])->default('pending');
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();

            $table->foreign('assignment_id')->references('id')->on('student_installment_assignments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_schedule');
        Schema::dropIfExists('student_installment_assignments');
    }
};
