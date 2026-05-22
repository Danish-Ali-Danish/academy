<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnhancedFeeStructureChangeLogs extends Migration
{
    public function up(): void
    {
        // Fee Structure Versions Table
        Schema::create('fee_structure_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('version_number', 20)->unique();
            $table->string('version_name', 100);
            $table->text('version_description')->nullable();
            $table->json('old_structure_data'); // Complete structure data before change
            $table->json('new_structure_data'); // Complete structure data after change
            $table->json('changed_fields')->nullable(); // Specific fields that changed
            $table->decimal('total_old_amount', 10, 2)->default(0);
            $table->decimal('total_new_amount', 10, 2)->default(0);
            $table->decimal('total_difference', 10, 2)->default(0);
            $table->string('change_type', 50); // update, create, delete, rollback
            $table->string('change_reason', 255);
            $table->foreignId('changed_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('effective_date');
            $table->timestamp('created_at');
            $table->timestamps();
        });

        // Change Requests Table
        Schema::create('change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->string('request_code', 50)->unique();
            $table->string('request_title', 100);
            $table->text('request_description');
            $table->json('proposed_changes'); // JSON format of proposed changes
            $table->decimal('estimated_impact', 10, 2)->default(0);
            $table->json('affected_students')->nullable(); // List of affected student IDs
            $table->integer('affected_students_count')->default(0);
            $table->string('priority', 20); // low, medium, high, urgent
            $table->string('request_status', 20); // draft, pending, approved, rejected, implemented
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('review_comments')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('implemented_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('implemented_at')->nullable();
            $table->json('implementation_details')->nullable();
            $table->timestamps();
        });

        // Change Request Approvals Table
        Schema::create('change_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained('change_requests')->onDelete('cascade');
            $table->foreignId('approved_by')->constrained('users')->onDelete('cascade');
            $table->string('approval_level', 20); // level_1, level_2, level_3, final
            $table->string('approval_status', 20); // pending, approved, rejected
            $table->text('approval_comments')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // Impact Analysis Table
        Schema::create('impact_analysis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained('change_requests')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->decimal('old_total_fee', 10, 2)->default(0);
            $table->decimal('new_total_fee', 10, 2)->default(0);
            $table->decimal('fee_difference', 10, 2)->default(0);
            $table->decimal('percentage_change', 5, 2)->default(0);
            $table->string('impact_level', 20); // none, minimal, moderate, significant
            $table->text('impact_details')->nullable();
            $table->timestamps();
        });

        // Approval Workflow Table
        Schema::create('approval_workflow', function (Blueprint $table) {
            $table->id();
            $table->string('workflow_name', 100);
            $table->string('workflow_code', 50)->unique();
            $table->json('workflow_steps'); // JSON format defining workflow steps
            $table->integer('total_steps')->default(0);
            $table->string('workflow_type', 50); // fee_structure, fee_waiver, fee_refund
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->timestamps();
        });

        // Workflow Assignments Table
        Schema::create('workflow_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained('change_requests')->onDelete('cascade');
            $table->foreignId('workflow_id')->constrained('approval_workflow')->onDelete('cascade');
            $table->integer('current_step')->default(1);
            $table->string('assignment_status', 20); // pending, in_progress, completed, rejected
            $table->text('assignment_details')->nullable();
            $table->timestamps();
        });

        // Change Notifications Table
        Schema::create('change_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained('change_requests')->onDelete('cascade');
            $table->string('notification_type', 50); // email, sms, notification, system
            $table->string('recipient_type', 50); // student, parent, staff, admin
            $table->json('recipients'); // Array of recipient IDs
            $table->string('notification_status', 20); // pending, sent, failed, delivered
            $table->text('notification_content')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });

        // Change History Table
        Schema::create('change_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('change_event', 100); // created, updated, deleted, rollback
            $table->json('change_data'); // Complete data at the time of change
            $table->foreignId('changed_by')->constrained('users')->onDelete('cascade');
            $table->text('change_description')->nullable();
            $table->string('source_system', 50); // manual, automated, api
            $table->json('additional_metadata')->nullable();
            $table->timestamps();
        });

        // Change Impact Summary Table
        Schema::create('change_impact_summary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained('change_requests')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $decimal('total_students_impacted', 10, 2)->default(0);
            $decimal('total_fee_increase', 10, 2)->default(0);
            $decimal('total_fee_decrease', 10, 2)->default(0);
            $decimal('average_change_percentage', 5, 2)->default(0);
            $integer('high_impact_students', 11)->default(0);
            $integer('medium_impact_students', 11)->default(0);
            $integer('low_impact_students', 11)->default(0);
            $text('impact_summary')->nullable();
            $text('recommendations')->nullable();
            $timestamps();
            
            $table->unique(['change_request_id', 'branch_id']);
        });

        // Audit Trail Table
        Schema::create('fee_structure_audit_trail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('action_type', 50); // create, read, update, delete, approve, reject
            $table->string('entity_type', 50); // fee_structure, fee_type, student
            $table->bigInteger('entity_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $timestamps();
        });

        // Bulk Change Operations Table
        Schema::create('bulk_change_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('initiated_by')->constrained('users')->onDelete('cascade');
            $table->string('operation_type', 50); // update_fee_structures, apply_changes, rollback_changes
            $table->string('operation_code', 50)->unique();
            $table->json('operation_details'); // JSON format of operation parameters
            $table->integer('total_records')->default(0);
            $table->integer('success_records')->default(0);
            $table->integer('failed_records')->default(0);
            $string('operation_status', 20); // pending, processing, completed, failed
            $text('operation_results')->nullable();
            $text('error_details')->nullable();
            $timestamps();
        });

        // Bulk Change Items Table
        Schema::create('bulk_change_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulk_change_operation_id')->constrained('bulk_change_operations')->onDelete('cascade');
            $table->string('entity_type', 50); // fee_structure, fee_type
            $table->bigInteger('entity_id');
            $table->json('change_data'); // Specific change data for this item
            $table->string('item_status', 20); // pending, processed, failed, skipped
            $text('error_message')->nullable();
            $text('item_details')->nullable();
            $timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bulk_change_items');
        Schema::dropIfExists('bulk_change_operations');
        Schema::dropIfExists('fee_structure_audit_trail');
        Schema::dropIfExists('change_impact_summary');
        Schema::dropIfExists('change_history');
        Schema::dropIfExists('change_notifications');
        Schema::dropIfExists('workflow_assignments');
        Schema::dropIfExists('approval_workflow');
        Schema::dropIfExists('impact_analysis');
        Schema::dropIfExists('change_request_approvals');
        Schema::dropIfExists('change_requests');
        Schema::dropIfExists('fee_structure_versions');
    }
}