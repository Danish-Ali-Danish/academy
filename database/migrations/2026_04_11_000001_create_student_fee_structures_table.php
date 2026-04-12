<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fee_structures', function (Blueprint $table) {
            $table->id();
            // Use unsignedInteger to match legacy int(11) primary keys on
            // student_enrollments and fee_structures tables
            $table->unsignedInteger('student_enrollment_id');
            $table->unsignedInteger('fee_structure_id');
            $table->decimal('custom_amount', 12, 2)->nullable()->comment('Override fee structure amount for this student only');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['student_enrollment_id', 'fee_structure_id'], 'sfs_enrollment_structure_unique');
        });

        // Add FK constraints manually to match legacy int(11) PK types
        Schema::table('student_fee_structures', function (Blueprint $table) {
            $table->foreign('student_enrollment_id', 'sfs_enrollment_fk')
                ->references('id')->on('student_enrollments')->onDelete('cascade');
            $table->foreign('fee_structure_id', 'sfs_fee_structure_fk')
                ->references('id')->on('fee_structures')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fee_structures');
    }
};
