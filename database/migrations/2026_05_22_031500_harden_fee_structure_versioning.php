<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            if (!Schema::hasColumn('fee_structures', 'parent_fee_structure_id')) {
                $table->unsignedBigInteger('parent_fee_structure_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('fee_structures', 'version_no')) {
                $table->unsignedInteger('version_no')->default(1)->after('parent_fee_structure_id');
            }
            if (!Schema::hasColumn('fee_structures', 'version_status')) {
                $table->string('version_status', 30)->default('active')->after('version_no');
            }
            if (!Schema::hasColumn('fee_structures', 'superseded_by_fee_structure_id')) {
                $table->unsignedBigInteger('superseded_by_fee_structure_id')->nullable()->after('version_status');
            }
            if (!Schema::hasColumn('fee_structures', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('fee_structures', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('fee_structures', 'change_request_id')) {
                $table->unsignedBigInteger('change_request_id')->nullable()->after('approved_at');
            }
        });

        if (!Schema::hasTable('fee_structure_versions')) {
            Schema::create('fee_structure_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('request_id')->nullable();
                $table->string('status', 30)->default('approved');
                $table->unsignedBigInteger('fee_structure_id');
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('version_number', 60)->unique();
                $table->string('version_name')->nullable();
                $table->text('version_description')->nullable();
                $table->json('old_structure_data')->nullable();
                $table->json('new_structure_data')->nullable();
                $table->json('changed_fields')->nullable();
                $table->decimal('total_old_amount', 12, 2)->default(0);
                $table->decimal('total_new_amount', 12, 2)->default(0);
                $table->decimal('total_difference', 12, 2)->default(0);
                $table->string('change_type', 50)->default('versioned_change');
                $table->text('change_reason')->nullable();
                $table->unsignedBigInteger('changed_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('effective_date')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('fee_structure_versions', function (Blueprint $table) {
                if (!Schema::hasColumn('fee_structure_versions', 'request_id')) {
                    $table->unsignedBigInteger('request_id')->nullable()->after('id');
                }
                if (!Schema::hasColumn('fee_structure_versions', 'status')) {
                    $table->string('status', 30)->default('approved')->after('request_id');
                }
                if (!Schema::hasColumn('fee_structure_versions', 'approved_by')) {
                    $table->unsignedBigInteger('approved_by')->nullable()->after('changed_by');
                }
                if (!Schema::hasColumn('fee_structure_versions', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('approved_by');
                }
            });
        }

        if (!Schema::hasTable('fee_structure_change_requests')) {
            Schema::create('fee_structure_change_requests', function (Blueprint $table) {
                $table->id();
                $table->string('request_code', 40)->unique();
                $table->unsignedBigInteger('fee_structure_id');
                $table->unsignedBigInteger('proposed_fee_structure_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('class_id')->nullable();
                $table->unsignedBigInteger('fee_type_id')->nullable();
                $table->unsignedBigInteger('academic_year_id')->nullable();
                $table->json('old_values');
                $table->json('proposed_values');
                $table->json('changed_fields')->nullable();
                $table->json('impact_snapshot')->nullable();
                $table->unsignedInteger('affected_students_count')->default(0);
                $table->unsignedInteger('unpaid_vouchers_count')->default(0);
                $table->unsignedInteger('future_vouchers_count')->default(0);
                $table->decimal('estimated_monthly_difference', 12, 2)->default(0);
                $table->text('reason');
                $table->string('status', 30)->default('pending');
                $table->unsignedBigInteger('requested_by')->nullable();
                $table->timestamp('requested_at')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_remarks')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
                $table->index(['fee_structure_id', 'status']);
            });
        }

        Schema::table('fee_structure_change_log', function (Blueprint $table) {
            if (!Schema::hasColumn('fee_structure_change_log', 'request_id')) {
                $table->unsignedBigInteger('request_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('fee_structure_change_log', 'version_number')) {
                $table->string('version_number', 60)->nullable()->after('request_id');
            }
            if (!Schema::hasColumn('fee_structure_change_log', 'old_values')) {
                $table->json('old_values')->nullable()->after('version_number');
            }
            if (!Schema::hasColumn('fee_structure_change_log', 'new_values')) {
                $table->json('new_values')->nullable()->after('old_values');
            }
            if (!Schema::hasColumn('fee_structure_change_log', 'changed_fields')) {
                $table->json('changed_fields')->nullable()->after('new_values');
            }
            if (!Schema::hasColumn('fee_structure_change_log', 'impact_snapshot')) {
                $table->json('impact_snapshot')->nullable()->after('changed_fields');
            }
            if (!Schema::hasColumn('fee_structure_change_log', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('changed_by');
            }
            if (!Schema::hasColumn('fee_structure_change_log', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
        });

        if (!Schema::hasTable('fee_structure_immutable_audits')) {
            Schema::create('fee_structure_immutable_audits', function (Blueprint $table) {
                $table->id();
                $table->string('auditable_type');
                $table->unsignedBigInteger('auditable_id');
                $table->string('event', 80);
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['auditable_type', 'auditable_id']);
            });
        }

        Schema::table('fee_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('fee_vouchers', 'fee_structure_id')) {
                $table->unsignedBigInteger('fee_structure_id')->nullable()->after('academic_year_id');
            }
            if (!Schema::hasColumn('fee_vouchers', 'fee_structure_version_id')) {
                $table->unsignedBigInteger('fee_structure_version_id')->nullable()->after('fee_structure_id');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structure_immutable_audits');
        Schema::dropIfExists('fee_structure_change_requests');

        Schema::table('fee_vouchers', function (Blueprint $table) {
            foreach (['fee_structure_id', 'fee_structure_version_id'] as $column) {
                if (Schema::hasColumn('fee_vouchers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
