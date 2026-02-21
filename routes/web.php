<?php
// routes/web.php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SubjectGroupController;
use App\Http\Controllers\SectionController;

use App\Http\Controllers\ClassSectionSubjectController;
use App\Http\Controllers\ClassSectionController;
use App\Http\Controllers\ClassController;

use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\FeeTypeController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\FeePaymentController;
use App\Http\Controllers\ExamSubjectController;
use App\Http\Controllers\GradeScaleController;
use App\Http\Controllers\ExamResultController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AcademicYearController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;




Route::resource('academic-years', controller: AcademicYearController::class);


Route::post('academic-years/{academicYear}/activate', [AcademicYearController::class, 'activate'])->name('academic-years.activate');
Route::post('academic-years/{academicSession}/deactivate', [AcademicYearController::class, 'deactivate'])->name('academic-years.deactivate');
Route::post('academic-years/{academicSession}/complete', [AcademicYearController::class, 'complete'])->name('academic-years.complete');
Route::get('api/academic-years/dropdown', [AcademicYearController::class, 'dropdown'])->name('academic-years.dropdown');
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard/Index', [
            'stats' => [
                'total_branches' => \App\Models\Branch::count(),
            
             
            ]
        ]);
    })->name('dashboard');

    // ==========================================
    // MANAGEMENT ROUTES
    // ==========================================
// ✅ CORRECT - Put custom routes BEFORE resource routes
Route::get('api/branches/dropdown', [BranchController::class, 'dropdown'])->name('branches.dropdown');

Route::get('api/branches/{id}/classes', [BranchController::class, 'branchClasses'])
    ->name('branches.branch-classes'); // ✅ Exactly ye name
Route::get('api/branches/{branch}/get-classes', [BranchController::class, 'getClasses'])->name('branches.get-classes');

// Resource routes AFTER custom routes
Route::resource('branches', BranchController::class);

    // Classes
    Route::resource('classes', ClassController::class);
    Route::get('api/classes/dropdown', [ClassController::class, 'dropdown'])
        ->name('classes.dropdown');
  // API endpoints (must be before resource routes)
    Route::get('/class-sections/branches', [ClassSectionController::class, 'getBranches'])
        ->name('class-sections.branches');
    Route::get('/class-sections/classes-by-branch', [ClassSectionController::class, 'getClassesByBranch'])
        ->name('class-sections.classes-by-branch');
    Route::get('/class-sections/available-sections', [ClassSectionController::class, 'getAvailableSections'])
        ->name('class-sections.available-sections');
    Route::get('/class-sections/sections-by-branch-class', [ClassSectionController::class, 'getSectionsByBranchClass'])
        ->name('class-sections.sections-by-branch-class');
    Route::post('/class-sections/{classSection}/toggle-status', [ClassSectionController::class, 'toggleStatus'])
        ->name('class-sections.toggle-status');
    Route::post('/class-sections/{classSection}/update-capacity', [ClassSectionController::class, 'updateCapacity'])
        ->name('class-sections.update-capacity');
    Route::get('/class-sections/capacity-stats', [ClassSectionController::class, 'capacityStats'])
        ->name('class-sections.capacity-stats');
    
    // Resource routes
    Route::resource('class-sections', ClassSectionController::class);





    // Subjects
    Route::resource('subjects', SubjectController::class);
    Route::get('api/subjects/dropdown', [SubjectController::class, 'dropdown'])
        ->name('subjects.dropdown');
// Class Subjects Routes
// Update in routes/web.php or routes/api.php
// In routes/web.php or routes/api.php
// routes/web.php
// Class Section Subjects Routes
Route::resource('class-section-subjects', ClassSectionSubjectController::class);
Route::get('class-section-subjects/api/branches', [ClassSectionSubjectController::class, 'getBranches'])->name('class-section-subjects.api.branches');
Route::get('class-section-subjects/api/classes-by-branch', [ClassSectionSubjectController::class, 'getClassesByBranch'])->name('class-section-subjects.api.classes-by-branch');
Route::get('class-section-subjects/api/class-sections-by-branch-class', [ClassSectionSubjectController::class, 'getClassSectionsByBranchClass'])->name('class-section-subjects.api.class-sections-by-branch-class');
Route::get('class-section-subjects/api/subjects', [ClassSectionSubjectController::class, 'getSubjects'])->name('class-section-subjects.api.subjects');
Route::get('class-section-subjects/api/subject-groups', [ClassSectionSubjectController::class, 'getSubjectGroups'])->name('class-section-subjects.api.subject-groups');
Route::get('class-section-subjects/api/assigned-subjects', [ClassSectionSubjectController::class, 'getAssignedSubjects'])->name('class-section-subjects.api.assigned-subjects');
Route::get('class-section-subjects/api/subject-stats', [ClassSectionSubjectController::class, 'subjectStats'])->name('class-section-subjects.api.subject-stats');
Route::delete('class-section-subjects/api/bulk-delete', [ClassSectionSubjectController::class, 'bulkDelete'])->name('class-section-subjects.api.bulk-delete');

Route::get('class-section-subjects-api/classes-by-branch', [ClassSectionSubjectController::class, 'getClassesByBranch'])
    ->name('class-section-subjects.classes-by-branch');

Route::get('class-section-subjects-api/sections-by-class', [ClassSectionSubjectController::class, 'getClassSectionsByBranchClass'])
    ->name('class-section-subjects.sections-by-class');

Route::get('class-section-subjects-api/subjects', [ClassSectionSubjectController::class, 'getSubjects'])
    ->name('class-section-subjects.subjects');

Route::get('class-section-subjects-api/subject-groups', [ClassSectionSubjectController::class, 'getSubjectGroups'])
    ->name('class-section-subjects.subject-groups');

Route::get('class-section-subjects-api/assigned-subjects', [ClassSectionSubjectController::class, 'getAssignedSubjects'])
    ->name('class-section-subjects.assigned-subjects');

Route::resource('subject-groups', SubjectGroupController::class);
Route::get('subject-groups/{id}/group-subjects', [SubjectGroupController::class, 'getGroupSubjects'])
    ->name('subject-groups.group-subjects');
Route::get('/subject-groups/subjects', [SubjectGroupController::class, 'getAllSubjects'])
    ->name('subject-groups.subjects');
Route::get('/subject-groups/{subjectGroup}/group-subjects', [SubjectGroupController::class, 'getGroupSubjects'])
    ->name('subject-groups.group-subjects');




    // Sections

    Route::resource('sections', SectionController::class);
    Route::get('api/sections/by-class/{classId}', [SectionController::class, 'byClass'])
        ->name('sections.by-class');

    // ==========================================
    // PEOPLE ROUTES
    // ==========================================

    // Students
    
    

    // Teachers
    Route::resource('teachers', TeacherController::class);
    Route::get('api/teachers/dropdown', [TeacherController::class, 'dropdown'])
        ->name('teachers.dropdown');

    // Parents
    Route::resource('parents', ParentController::class);
    Route::get('api/parents/dropdown', [ParentController::class, 'dropdown'])
        ->name('parents.dropdown');

    // ==========================================
    // FEE MANAGEMENT ROUTES
    // ==========================================

    // Fee Types
    Route::resource('fee-types', FeeTypeController::class);
    Route::get('api/fee-types/dropdown', [FeeTypeController::class, 'dropdown'])
        ->name('fee-types.dropdown');

    // Fees
    Route::resource('fees', FeeController::class);
    Route::get('api/fees/dropdown', [FeeController::class, 'dropdown'])
        ->name('fees.dropdown');

    // Fee Payments
    Route::resource('fee-payments', FeePaymentController::class)->except(['edit', 'update']);
    Route::post('fee-payments/{feePayment}/cancel', [FeePaymentController::class, 'cancel'])
        ->name('fee-payments.cancel');
    Route::get('api/fee-payments/dropdown', [FeePaymentController::class, 'dropdown'])
        ->name('fee-payments.dropdown');

    // ==========================================
    // ACADEMIC SYSTEM ROUTES
    // ==========================================

    // Attendance
    Route::resource('attendance', AttendanceController::class);
    Route::get('api/attendance/students-by-section', [AttendanceController::class, 'getStudentsBySection'])
        ->name('attendance.students-by-section');
    Route::get('api/attendance/report', [AttendanceController::class, 'report'])
        ->name('attendance.report');

    // Exams
    Route::resource('exams', ExamController::class);
    Route::get('api/exams/dropdown', [ExamController::class, 'dropdown'])
        ->name('exams.dropdown');

    // Exam Subjects
    Route::resource('exam-subjects', ExamSubjectController::class);
    Route::get('api/exam-subjects/by-exam', [ExamSubjectController::class, 'getByExam'])
        ->name('exam-subjects.by-exam');

    // Exam Results
    Route::resource('exam-results', ExamResultController::class);
    Route::get('api/exam-results/report-card', [ExamResultController::class, 'reportCard'])
        ->name('exam-results.report-card');
    Route::get('api/exam-results/students-by-exam', [ExamResultController::class, 'getStudentsByExam'])
        ->name('exam-results.students-by-exam');

    // Grade Scales
    Route::resource('grade-scales', GradeScaleController::class);
    Route::get('api/grade-scales/get-grade', [GradeScaleController::class, 'getGrade'])
        ->name('grade-scales.get-grade');
    Route::get('api/grade-scales/dropdown', [GradeScaleController::class, 'dropdown'])
        ->name('grade-scales.dropdown');

    // ==========================================
    // PROFILE ROUTES
    // ==========================================

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';