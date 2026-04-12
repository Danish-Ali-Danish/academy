<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== student_enrollments columns ===\n";
$cols = Illuminate\Support\Facades\Schema::getColumnListing('student_enrollments');
echo implode(', ', $cols) . "\n\n";

echo "=== classes columns ===\n";
$cols2 = Illuminate\Support\Facades\Schema::getColumnListing('classes');
echo implode(', ', $cols2) . "\n\n";

echo "=== All Classes ===\n";
$classes = Illuminate\Support\Facades\DB::table('classes')->select('id', 'class_name')->orderBy('id')->get();
foreach ($classes as $c) {
    echo $c->id . ': ' . $c->class_name . "\n";
}

echo "\n=== students columns ===\n";
$cols3 = Illuminate\Support\Facades\Schema::getColumnListing('students');
echo implode(', ', $cols3) . "\n";
