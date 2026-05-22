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
        Schema::create('fee_reminder_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reminder_log_id')->constrained('fee_reminder_logs')->cascadeOnDelete();
            $table->string('outcome')->comment('promised_to_pay, no_response, paid, call_back');
            $table->date('promised_pay_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_reminder_followups');
    }
};
