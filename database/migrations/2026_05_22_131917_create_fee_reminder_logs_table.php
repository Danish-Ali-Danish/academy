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
        Schema::create('fee_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('voucher_id');
            $table->integer('student_enrollment_id');
            $table->foreignId('template_id')->nullable()->constrained('fee_reminder_templates')->nullOnDelete();
            $table->string('channel');
            $table->string('recipient');
            $table->string('status')->default('queued')->comment('queued, sent, delivered, failed, cancelled');
            $table->text('provider_response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->foreign('voucher_id')->references('id')->on('fee_vouchers')->cascadeOnDelete();
            $table->foreign('student_enrollment_id')->references('id')->on('student_enrollments')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_reminder_logs');
    }
};
