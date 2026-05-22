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
        Schema::create('fee_reminder_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->nullable()->constrained('fee_reminder_rules')->nullOnDelete();
            $table->string('channel')->default('whatsapp')->comment('sms, whatsapp, email');
            $table->text('template_body');
            $table->string('language')->default('en');
            $table->integer('branch_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_reminder_templates');
    }
};
