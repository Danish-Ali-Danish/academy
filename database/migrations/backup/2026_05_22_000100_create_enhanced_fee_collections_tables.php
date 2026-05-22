<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnhancedFeeCollectionsTables extends Migration
{
    public function up()
    {
        // Payment Transactions Table
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_voucher_id')->constrained('fee_vouchers')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('transaction_id', 100)->unique();
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_amount', 10, 2)->default(0);
            $table->string('payment_method', 50); // cash, cheque, online, bank_transfer
            $table->string('payment_gateway', 100)->nullable(); // jazzcash, easypaisa, bank_name
            $table->string('transaction_status', 50)->default('pending'); // pending, completed, failed, cancelled
            $table->string('reference_number', 200)->nullable();
            $table->timestamp('transaction_date');
            $table->text('transaction_details')->nullable();
            $table->string('receipt_number', 100)->nullable();
            $table->string('receipt_path', 255)->nullable();
            $table->json('payment_metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Collection Agents Table
        Schema::create('collection_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('agent_code', 50)->unique();
            $table->string('designation', 100);
            $table->decimal('collection_target', 10, 2)->default(0);
            $table->decimal('collected_amount', 10, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->decimal('total_commission', 10, 2)->default(0);
            $table->string('status', 20)->default('active'); // active, inactive, suspended
            $table->date('joining_date');
            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Payment Gateways Table
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->string('provider', 100); // jazzcash, easypaisa, bank_name
            $table->string('api_key', 255)->nullable();
            $table->string('api_secret', 255)->nullable();
            $table->string('callback_url', 255)->nullable();
            $table->decimal('service_charge', 5, 2)->default(0);
            $table->string('currency', 10)->default('PKR');
            $table->boolean('is_active')->default(true);
            $table->boolean('test_mode')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
        });

        // Receipt Templates Table
        Schema::create('receipt_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('template_code', 50)->unique();
            $table->text('header_html');
            $table->text('body_html');
            $table->text('footer_html');
            $table->string('watermark_text', 100)->nullable();
            $table->string('qr_code_url', 255)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->timestamps();
        });

        // Payment Batch Table
        Schema::create('payment_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('collection_agent_id')->nullable()->constrained('collection_agents')->onDelete('set null');
            $table->string('batch_number', 50)->unique();
            $table->string('batch_type', 50); // daily, weekly, monthly, custom
            $table->decimal('total_amount', 10, 2);
            $table->decimal('processed_amount', 10, 2)->default(0);
            $table->decimal('failed_amount', 10, 2)->default(0);
            $table->string('batch_status', 20)->default('pending'); // pending, processing, completed, failed
            $table->text('batch_details')->nullable();
            $table->timestamp('batch_date');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        // Payment Batch Items Table
        Schema::create('payment_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_batch_id')->constrained('payment_batches')->onDelete('cascade');
            $table->foreignId('fee_voucher_id')->constrained('fee_vouchers')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->decimal('processed_amount', 10, 2)->default(0);
            $table->string('item_status', 20)->default('pending'); // pending, processed, failed, skipped
            $table->text('failure_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        // Collection Summary Table
        Schema::create('collection_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('summary_type', 50); // daily, weekly, monthly, custom
            $table->date('summary_date');
            $table->decimal('total_target', 10, 2)->default(0);
            $table->decimal('total_collected', 10, 2)->default(0);
            $table->decimal('total_pending', 10, 2)->default(0);
            $table->decimal('online_payments', 10, 2)->default(0);
            $table->decimal('cash_payments', 10, 2)->default(0);
            $table->decimal('cheque_payments', 10, 2)->default(0);
            $table->decimal('bank_transfer', 10, 2)->default(0);
            $table->integer('total_transactions')->default(0);
            $table->integer('unique_students')->default(0);
            $table->text('summary_details')->nullable();
            $table->timestamps();
            
            $table->unique(['branch_id', 'summary_type', 'summary_date']);
        });

        // Auto Reconciliation Rules
        Schema::create('auto_reconciliation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_name', 100);
            $table->string('rule_code', 50)->unique();
            $table->text('rule_conditions'); // JSON format for conditions
            $table->text('rule_actions'); // JSON format for actions
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('priority')->default(0);
            $table->timestamps();
        });

        // Reconciliation Logs
        Schema::create('reconciliation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('reconciliation_type', 50); // daily, weekly, monthly, manual
            $table->date('reconciliation_date');
            $table->decimal('system_total', 10, 2);
            $table->decimal('bank_total', 10, 2);
            $table->decimal('difference', 10, 2);
            $table->string('status', 20); // matched, mismatched, pending
            $table->text('reconciliation_details')->nullable();
            $table->text('adjustments')->nullable(); // JSON format for adjustments
            $table->foreignId('reconciled_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Bulk Payment Processing
        Schema::create('bulk_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('processed_by')->constrained('users')->onDelete('cascade');
            $table->string('bulk_payment_id', 100)->unique();
            $table->string('payment_method', 50);
            $table->decimal('total_amount', 10, 2);
            $table->integer('total_records')->default(0);
            $table->integer('success_records')->default(0);
            $table->integer('failed_records')->default(0);
            $table->decimal('success_amount', 10, 2)->default(0);
            $table->decimal('failed_amount', 10, 2)->default(0);
            $table->string('batch_status', 20)->default('pending'); // pending, processing, completed, failed
            $table->text('processing_details')->nullable();
            $table->text('failure_details')->nullable();
            $table->timestamps();
        });

        // Bulk Payment Items
        Schema::create('bulk_payment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulk_payment_id')->constrained('bulk_payments')->onDelete('cascade');
            $table->foreignId('fee_voucher_id')->constrained('fee_vouchers')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('item_status', 20)->default('pending'); // pending, processed, failed
            $table->string('error_message')->nullable();
            $table->text('item_details')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bulk_payment_items');
        Schema::dropIfExists('bulk_payments');
        Schema::dropIfExists('reconciliation_logs');
        Schema::dropIfExists('auto_reconciliation_rules');
        Schema::dropIfExists('collection_summaries');
        Schema::dropIfExists('payment_batch_items');
        Schema::dropIfExists('payment_batches');
        Schema::dropIfExists('receipt_templates');
        Schema::dropIfExists('payment_gateways');
        Schema::dropIfExists('collection_agents');
        Schema::dropIfExists('payment_transactions');
    }
}