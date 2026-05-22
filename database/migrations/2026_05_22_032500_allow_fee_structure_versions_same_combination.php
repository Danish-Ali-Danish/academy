<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasOldUnique = collect(DB::select('SHOW INDEX FROM fee_structures'))
            ->contains(fn ($index) => $index->Key_name === 'unique_fee_structure');

        if ($hasOldUnique) {
            Schema::table('fee_structures', function (Blueprint $table) {
                $table->dropUnique('unique_fee_structure');
            });
        }

        $hasLookupIndex = collect(DB::select('SHOW INDEX FROM fee_structures'))
            ->contains(fn ($index) => $index->Key_name === 'fee_structures_version_lookup_index');

        if (!$hasLookupIndex) {
            Schema::table('fee_structures', function (Blueprint $table) {
                $table->index(
                    ['academic_year_id', 'branch_id', 'class_id', 'fee_type_id', 'is_active', 'version_status'],
                    'fee_structures_version_lookup_index'
                );
            });
        }
    }

    public function down(): void
    {
        $hasLookupIndex = collect(DB::select('SHOW INDEX FROM fee_structures'))
            ->contains(fn ($index) => $index->Key_name === 'fee_structures_version_lookup_index');

        if ($hasLookupIndex) {
            Schema::table('fee_structures', function (Blueprint $table) {
                $table->dropIndex('fee_structures_version_lookup_index');
            });
        }
    }
};
