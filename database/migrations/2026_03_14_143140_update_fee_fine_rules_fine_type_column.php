<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Workaround for modifying enum columns in older Laravel / Doctrine DBAL
        DB::statement("ALTER TABLE fee_fine_rules MODIFY COLUMN fine_type VARCHAR(50) DEFAULT 'fixed'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE fee_fine_rules MODIFY COLUMN fine_type ENUM('fixed', 'percentage') DEFAULT 'fixed'");
    }
};
