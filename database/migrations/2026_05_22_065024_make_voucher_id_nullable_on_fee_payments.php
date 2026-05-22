<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // voucher_id is already nullable from previous partial run
        // FK was already dropped - no need to re-add (advance payments have null voucher_id)
    }

    public function down(): void
    {
        //
    }
};
