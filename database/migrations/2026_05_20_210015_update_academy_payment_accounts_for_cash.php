<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // ENUM mein cash add karo
        DB::statement("ALTER TABLE `academy_payment_accounts`
            MODIFY COLUMN `payment_method`
            ENUM('jazzcash','easypaisa','bank_transfer','raast','cash') NOT NULL");

        // Cash k liye account_title aur account_number nullable karo
        DB::statement("ALTER TABLE `academy_payment_accounts`
            MODIFY COLUMN `account_title` VARCHAR(150) NULL COMMENT 'Cash k liye optional'");

        DB::statement("ALTER TABLE `academy_payment_accounts`
            MODIFY COLUMN `account_number` VARCHAR(100) NULL COMMENT 'Cash k liye optional'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE `academy_payment_accounts`
            MODIFY COLUMN `payment_method`
            ENUM('jazzcash','easypaisa','bank_transfer','raast') NOT NULL");

        DB::statement("ALTER TABLE `academy_payment_accounts`
            MODIFY COLUMN `account_title` VARCHAR(150) NOT NULL");

        DB::statement("ALTER TABLE `academy_payment_accounts`
            MODIFY COLUMN `account_number` VARCHAR(100) NOT NULL");
    }
};
