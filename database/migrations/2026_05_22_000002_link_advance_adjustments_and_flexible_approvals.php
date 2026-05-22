<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('fee_advance_adjustments', 'applied_payment_id')) {
            DB::statement("ALTER TABLE fee_advance_adjustments ADD applied_payment_id INT(11) NULL AFTER to_voucher_id");
            DB::statement("ALTER TABLE fee_advance_adjustments ADD INDEX fee_adv_adj_applied_payment_idx (applied_payment_id)");
            DB::statement("ALTER TABLE fee_advance_adjustments ADD CONSTRAINT fee_adv_adj_applied_payment_fk FOREIGN KEY (applied_payment_id) REFERENCES fee_payments(id) ON DELETE SET NULL");
        }

        DB::statement("ALTER TABLE fee_approval_requests MODIFY request_type VARCHAR(50) NOT NULL");
        DB::statement("ALTER TABLE fee_approval_requests MODIFY urgency VARCHAR(20) NULL DEFAULT 'medium'");
        DB::statement("ALTER TABLE fee_approval_requests MODIFY status VARCHAR(30) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (Schema::hasColumn('fee_advance_adjustments', 'applied_payment_id')) {
            DB::statement("ALTER TABLE fee_advance_adjustments DROP FOREIGN KEY fee_adv_adj_applied_payment_fk");
            DB::statement("ALTER TABLE fee_advance_adjustments DROP INDEX fee_adv_adj_applied_payment_idx");
            DB::statement("ALTER TABLE fee_advance_adjustments DROP COLUMN applied_payment_id");
        }
    }
};
