<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_finance_blocks')) {
            Schema::table('student_finance_blocks', function (Blueprint $table) {
                $table->unique('student_enrollment_id', 'student_finance_blocks_enrollment_unique');
            });

            return;
        }

        Schema::create('student_finance_blocks', function (Blueprint $table) {
            $table->id();
            $table->integer('student_enrollment_id')->unique();
            $table->boolean('is_blocked')->default(true);
            $table->boolean('block_portal')->default(true);
            $table->boolean('block_result')->default(true);
            $table->boolean('block_tc')->default(false);
            $table->boolean('block_exam')->default(true);
            $table->string('reason')->nullable();
            $table->integer('blocked_by')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->integer('unblocked_by')->nullable();
            $table->timestamp('unblocked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_finance_blocks');
    }
};
