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
        Schema::create('fee_reminder_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_name');
            $table->string('trigger_type')->comment('before_due, on_due, after_due');
            $table->integer('days_offset')->default(0)->comment('Number of days offset from due date');
            $table->string('channel')->default('whatsapp')->comment('sms, whatsapp, email');
            $table->integer('branch_id')->nullable();
            $table->integer('fee_type_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('fee_type_id')->references('id')->on('fee_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_reminder_rules');
    }
};
