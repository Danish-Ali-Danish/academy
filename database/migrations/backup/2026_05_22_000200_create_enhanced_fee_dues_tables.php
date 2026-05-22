<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnhancedFeeDuesTables extends Migration
{
    public function up()
    {
        // Dues Categories Table
        Schema::create('dues_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('category_name', 100);
            $table->string('category_code', 50)->unique();
            $table->text('description')->nullable();
            $table->integer('days_threshold'); // Days threshold for this category
            $table->string('severity_level', 20); // low, medium, high, critical
            $table->decimal('penalty_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_penalty')->default(true);
            $table->timestamps();
        });

        // Dues History Table
        Schema::create('dues_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('fee_voucher_id')->constrained('fee_vouchers')->onDelete('cascade');
            $table->foreignId('dues_category_id')->nullable()->constrained('dues_categories')->onDelete('set null');
            $table->decimal('original_amount', 10, 2);
            $table->decimal('current_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('penalty_applied', 10, 2)->default(0);
            $table->integer('days_overdue')->default(0);
            $table->date('due_date');
            $table->date('last_reminder_date')->nullable();
            $table->string('dues_status', 20); // current, overdue, paid, waived
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Reminder Templates Table
        Schema::create('reminder_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_name', 100);
            $table->string('template_code', 50)->unique();
            $table->string('channel_type', 50); // email, sms, whatsapp, notification
            $table->string('template_type', 50); // first_reminder, second_reminder, final_reminder, legal_notice
            $table->text('template_content');
            $table->text('variables')->nullable(); // JSON format for template variables
            $table->boolean('is_active')->default(true);
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->timestamps();
        });

        // Penalty Rules Table
        Schema::create('penalty_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('rule_name', 100);
            $table->string('rule_code', 50)->unique();
            $table->integer('after_days'); // After how many days penalty applies
            $table->decimal('penalty_percentage', 5, 2);
            $table->decimal('max_penalty_percentage', 5, 2)->default(100);
            $table->boolean('compound_penalty')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('applicable_fees')->nullable(); // JSON format of fee types
            $table->timestamps();
        });

        // Advance Allocations Table
        Schema::create('advance_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('payment_transaction_id')->constrained('payment_transactions')->onDelete('cascade');
            $table->decimal('advance_amount', 10, 2);
            $table->decimal('allocated_amount', 10, 2)->default(0);
            $table->decimal('remaining_amount', 10, 2)->default(0);
            $table->string('allocation_status', 20); // pending, allocated, fully_allocated, expired
            $table->date('expiry_date');
            $table->text('allocation_details')->nullable();
            $table->timestamps();
        });

        // Dues Allocations Table
        Schema::create('dues_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dues_history_id')->constrained('dues_history')->onDelete('cascade');
            $table->foreignId('advance_allocation_id')->constrained('advance_allocations')->onDelete('cascade');
            $table->decimal('allocated_amount', 10, 2);
            $table->decimal('remaining_amount', 10, 2)->default(0);
            $table->string('allocation_status', 20); // active, completed, cancelled
            $table->text('allocation_details')->nullable();
            $table->timestamps();
        });

        // Fee Waiver Requests Table
        Schema::create('fee_waiver_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('fee_voucher_id')->constrained('fee_vouchers')->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->decimal('requested_amount', 10, 2);
            $table->decimal('approved_amount', 10, 2)->default(0);
            $table->string('waiver_reason', 255);
            $table->string('waiver_type', 50); // full, partial, percentage, fixed_amount
            $text->text('justification')->nullable();
            $table->string('request_status', 20); // pending, approved, rejected, withdrawn
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('approval_comments')->nullable();
            $table->date('request_date');
            $table->date('approval_date')->nullable();
            $table->timestamps();
        });

        // Dues Reminders Table
        Schema::create('dues_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dues_history_id')->constrained('dues_history')->onDelete('cascade');
            $table->foreignId('reminder_template_id')->constrained('reminder_templates')->onDelete('cascade');
            $table->string('channel_type', 50); // email, sms, whatsapp, notification
            $table->string('recipient_type', 50); // student, parent, guardian
            $table->string('recipient_address'); // email address or phone number
            $table->string('reminder_status', 20); // pending, sent, failed, delivered
            $table->text('sent_details')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });

        // Custom Payment Plans Table
        Schema::create('custom_payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('fee_voucher_id')->constrained('fee_vouchers')->onDelete('cascade');
            $table->string('plan_name', 100);
            $table->string('plan_code', 50)->unique();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('remaining_amount', 10, 2)->default(0);
            $integer('installment_count', 10)->default(0);
            $integer('completed_installments', 10)->default(0);
            $decimal('installment_amount', 10, 2);
            $date('start_date');
            $date('end_date');
            $string('plan_status', 20); // active, completed, defaulted, cancelled
            $text('plan_details')->nullable();
            $timestamps();
        });

        // Payment Plan Installments Table
        Schema::create('payment_plan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_payment_plan_id')->constrained('custom_payment_plans')->onDelete('cascade');
            $table->decimal('installment_amount', 10, 2);
            $date('due_date');
            $decimal('paid_amount', 10, 2)->default(0);
            $string('installment_status', 20); // pending, paid, overdue, waived
            $date('paid_date')->nullable();
            $text('installment_details')->nullable();
            $timestamps();
        });

        // Dues Analytics Table
        Schema::create('dues_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $date('analytics_date');
            $decimal('total_dues', 10, 2)->default(0);
            $decimal('current_dues', 10, 2)->default(0);
            $decimal('overdue_dues', 10, 2)->default(0);
            $decimal('aged_30_days', 10, 2)->default(0);
            $decimal('aged_60_days', 10, 2)->default(0);
            $decimal('aged_90_days', 10, 2)->default(0);
            $integer('total_students_with_dues', 11)->default(0);
            $integer('overdue_students_count', 11)->default(0);
            $decimal('total_penalties', 10, 2)->default(0);
            $integer('dues_cases', 11)->default(0);
            $text('analytics_details')->nullable();
            $timestamps();
            
            $table->unique(['branch_id', 'analytics_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('dues_analytics');
        Schema::dropIfExists('payment_plan_installments');
        Schema::dropIfExists('custom_payment_plans');
        Schema::dropIfExists('dues_reminders');
        Schema::dropIfExists('fee_waiver_requests');
        Schema::dropIfExists('dues_allocations');
        Schema::dropIfExists('advance_allocations');
        Schema::dropIfExists('penalty_rules');
        Schema::dropIfExists('reminder_templates');
        Schema::dropIfExists('dues_history');
        Schema::dropIfExists('dues_categories');
    }
}