<?php

use App\Models\StudentEnrollment;
use App\Models\FeeStructure;

// Replace with the enrollment ID from the screenshot if possible, 
// or search by student name/year.
$enrollment = StudentEnrollment::whereHas('student', function($q) {
    $q->where('student_name', 'like', '%Ivory Obrien%');
})->with(['classSection.branchClass', 'academicYear'])->first();

if (!$enrollment) {
    echo "Enrollment not found for Ivory Obrien\n";
    exit;
}

echo "Enrollment ID: " . $enrollment->id . "\n";
echo "Academic Year ID: " . $enrollment->academic_year_id . " (" . ($enrollment->academicYear->year_name ?? 'N/A') . ")\n";
echo "Branch ID: " . $enrollment->branch_id . "\n";

$branchClass = $enrollment->classSection?->branchClass;
$classId = $branchClass?->class_id;
echo "Class ID: " . ($classId ?? 'NULL') . "\n";

$feeStructures = FeeStructure::where('academic_year_id', $enrollment->academic_year_id)
    ->where('branch_id', $enrollment->branch_id)
    ->where('class_id', $classId)
    ->get();

echo "Matching Fee Structures count: " . $feeStructures->count() . "\n";
foreach ($feeStructures as $fs) {
    echo "- Fee Type ID: " . $fs->fee_type_id . ", Amount: " . $fs->amount . ", Active: " . $fs->is_active . "\n";
}

// All active fee structures for this academic year and branch to see what exists
$allFs = FeeStructure::where('academic_year_id', $enrollment->academic_year_id)
    ->where('branch_id', $enrollment->branch_id)
    ->get();
echo "\nAll Fee Structures for Year " . $enrollment->academic_year_id . " and Branch " . $enrollment->branch_id . ":\n";
foreach ($allFs as $fs) {
    echo "- Class ID: " . $fs->class_id . ", Fee Type ID: " . $fs->fee_type_id . ", Amount: " . $fs->amount . "\n";
}
