<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Schema::disableForeignKeyConstraints();
DB::statement('DROP TABLE IF EXISTS fee_reminder_followups, fee_reminder_logs, fee_reminder_templates, fee_reminder_rules');
Schema::enableForeignKeyConstraints();

DB::table('migrations')->where('migration', 'like', '2026_05_22_13191%')->delete();
echo "Cleaned up DB!\n";
