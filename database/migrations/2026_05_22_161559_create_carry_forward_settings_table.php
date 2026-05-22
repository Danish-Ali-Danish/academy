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
        Schema::create('carry_forward_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('branch_id')->nullable(); // nullable = all branches
            $table->boolean('is_enabled')->default(true);
            $table->enum('scope', ['full', 'fee_only', 'custom'])->default('full');
            $table->integer('max_months')->default(3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carry_forward_settings');
    }
};
