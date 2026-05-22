<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $this->dropForeignIfExists('cheque_tracking', 'ct_payment_fk');

        DB::statement("
            UPDATE cheque_tracking
            SET status = CASE status
                WHEN 'pending_clearance' THEN 'Pending'
                WHEN 'cleared' THEN 'Cleared'
                WHEN 'bounced' THEN 'Bounced'
                ELSE status
            END
        ");

        DB::statement("ALTER TABLE cheque_tracking MODIFY payment_id INT(11) NULL COMMENT 'FK -> fee_payments - linked payment record'");
        DB::statement("ALTER TABLE cheque_tracking MODIFY status VARCHAR(30) NOT NULL DEFAULT 'Pending'");
        DB::statement("ALTER TABLE cheque_tracking ADD CONSTRAINT ct_payment_fk FOREIGN KEY (payment_id) REFERENCES fee_payments(id) ON DELETE SET NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $this->dropForeignIfExists('cheque_tracking', 'ct_payment_fk');

        DB::statement("
            UPDATE cheque_tracking
            SET status = CASE status
                WHEN 'Pending' THEN 'pending_clearance'
                WHEN 'Cleared' THEN 'cleared'
                WHEN 'Bounced' THEN 'bounced'
                ELSE status
            END
        ");

        DB::statement("ALTER TABLE cheque_tracking MODIFY payment_id INT(11) NOT NULL COMMENT 'FK -> fee_payments - linked payment record'");
        DB::statement("ALTER TABLE cheque_tracking MODIFY status ENUM('pending_clearance','cleared','bounced') NOT NULL DEFAULT 'pending_clearance'");
        DB::statement("ALTER TABLE cheque_tracking ADD CONSTRAINT ct_payment_fk FOREIGN KEY (payment_id) REFERENCES fee_payments(id)");
    }

    private function dropForeignIfExists(string $table, string $constraint): void
    {
        $exists = DB::selectOne(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table, $constraint]
        );

        if ($exists) {
            DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$constraint}");
        }
    }
};
