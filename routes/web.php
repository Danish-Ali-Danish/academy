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
use App\Http\Controllers\ParentController;
use App\Http\Controllers\FeeTypeController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\FeeVoucherController;
use App\Http\Controllers\FeeVoucherFineController;
use App\Http\Controllers\FeeFineRuleController;
use App\Http\Controllers\FeeRefundController;
use App\Http\Controllers\FeeWaiverController;
use App\Http\Controllers\FeeAdvanceAdjustmentController;
use App\Http\Controllers\FeeCollectionSummaryController;
use App\Http\Controllers\FeePaymentController;
use App\Http\Controllers\FeeConcessionTypeController;
use App\Http\Controllers\ScholarshipController;
use App\Http\Controllers\SiblingDiscountRuleController;
use App\Http\Controllers\InstallmentPlanController;
use App\Http\Controllers\AcademyPaymentAccountController;
use App\Http\Controllers\ExamSubjectController;
use App\Http\Controllers\GradeScaleController;
use App\Http\Controllers\ExamResultController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\StudentEnrollmentController;
use App\Http\Controllers\StudentFeeConcessionController;
use App\Http\Controllers\StudentFeeStructureController;
use App\Http\Controllers\StudentScholarshipController;
use App\Http\Controllers\StudentInstallmentAssignmentController;
use App\Http\Controllers\FeeReminderController;
use App\Http\Controllers\FeeReminderRuleController;
use App\Http\Controllers\FeeReminderTemplateController;
use App\Http\Controllers\CarryForwardController;
use App\Http\Controllers\Report\FeeDemandRegisterController;
use App\Http\Controllers\FeeApprovalRequestController;
use App\Http\Controllers\FeeStructureChangeLogController;
use App\Http\Controllers\StudentLedgerController;
use App\Http\Controllers\DefaulterManagementController;
use App\Http\Controllers\PreviousYearBalanceController;
use App\Http\Controllers\EnhancedFeeCollectionsController;
use App\Http\Controllers\EnhancedFeeDuesController;
use App\Http\Controllers\EnhancedFeeStructureChangeLogsController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;




Route::resource('academic-years', controller: AcademicYearController::class);


Route::post('academic-years/{academicYear}/activate', [AcademicYearController::class, 'activate'])->name('academic-years.activate');
Route::post('academic-years/{academicSession}/deactivate', [AcademicYearController::class, 'deactivate'])->name('academic-years.deactivate');
Route::post('academic-years/{academicSession}/complete', [AcademicYearController::class, 'complete'])->name('academic-years.complete');
Route::get('api/academic-years/dropdown', [AcademicYearController::class, 'dropdown'])->name('academic-years.dropdown');

// ==========================================
// STUDENT PORTAL API ROUTES
// ==========================================
Route::prefix('api/student-portal')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\StudentPortalApiController::class, 'login']);
    Route::get('/{studentId}/dashboard', [\App\Http\Controllers\Api\StudentPortalApiController::class, 'getDashboardData']);
    Route::get('/{studentId}/fees', [\App\Http\Controllers\Api\StudentPortalApiController::class, 'getFeeDetails']);
});

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
// API Custom Endpoints
// ==========================================
Route::get('api/students/search', [\App\Http\Controllers\StudentController::class, 'search'])->name('students.search');

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




    // Sections

    Route::resource('sections', SectionController::class);
    Route::get('api/sections/by-class/{classId}', [SectionController::class, 'byClass'])
        ->name('sections.by-class');

    // ==========================================
    // PEOPLE ROUTES
    // ==========================================

    // Students
    Route::resource('students', StudentController::class);
    Route::get('api/students/dropdown', [StudentController::class, 'dropdown'])
        ->name('students.dropdown');


    // Parents
    Route::resource('parents', ParentController::class);
    Route::get('api/parents/dropdown', [ParentController::class, 'dropdown'])
        ->name('parents.dropdown');

    // ==========================================
    // ENROLLMENT ROUTES
    // ==========================================

    // Student Enrollments
    Route::resource('student-enrollments', StudentEnrollmentController::class);
    Route::get('api/student-enrollments/dropdown', [StudentEnrollmentController::class, 'dropdown'])
        ->name('student-enrollments.dropdown');
    Route::get('api/student-enrollments/classes-by-branch/{branchId}', [StudentEnrollmentController::class, 'classesByBranch'])
        ->name('student-enrollments.classes-by-branch');
    Route::get('api/student-enrollments/sections-by-branch-class/{branchClassId}', [StudentEnrollmentController::class, 'sectionsByBranchClass'])
        ->name('student-enrollments.sections-by-branch-class');

    // Student Fee Structures (per-student fee assignments)
    Route::post('student-fee-structures/sync-all', [StudentFeeStructureController::class, 'syncAll'])
        ->name('student-fee-structures.sync-all');
    Route::resource('student-fee-structures', StudentFeeStructureController::class)
        ->only(['index', 'update', 'destroy']);

    // Student Fee Concessions
    Route::resource('student-fee-concessions', StudentFeeConcessionController::class);
    Route::get('api/student-fee-concessions/enrollments-by-student/{studentId}', [StudentFeeConcessionController::class, 'enrollmentsByStudent'])
        ->name('student-fee-concessions.enrollments-by-student');
    Route::get('api/student-fee-concessions/fee-structure-amount', [StudentFeeConcessionController::class, 'getFeeStructureAmount'])
        ->name('student-fee-concessions.fee-structure-amount');

    // Student Scholarships
    Route::resource('student-scholarships', StudentScholarshipController::class);
    Route::get('api/student-scholarships/enrollments-by-student/{studentId}', [StudentScholarshipController::class, 'enrollmentsByStudent'])
        ->name('student-scholarships.enrollments-by-student');

    // Student Installment Assignments
    Route::resource('student-installment-assignments', StudentInstallmentAssignmentController::class);
    Route::get('api/student-installment-assignments/enrollments-by-student/{studentId}', [StudentInstallmentAssignmentController::class, 'enrollmentsByStudent'])
        ->name('student-installment-assignments.enrollments-by-student');
    Route::get('api/student-installment-assignments/fee-for-enrollment', [StudentInstallmentAssignmentController::class, 'getFeeForEnrollment'])
        ->name('student-installment-assignments.fee-for-enrollment');

    // Installment schedules are managed inside Student Installment Assignments.
    Route::redirect('installment-schedules', 'student-installment-assignments')
        ->name('installment-schedules.index');
    Route::redirect('installment-schedules/create', 'student-installment-assignments/create')
        ->name('installment-schedules.create');
    Route::redirect('installment-schedules/{installmentSchedule}/edit', 'student-installment-assignments')
        ->name('installment-schedules.edit');
    Route::redirect('installment-schedules/{installmentSchedule}', 'student-installment-assignments')
        ->name('installment-schedules.show');

    // ==========================================
    // FEE MANAGEMENT ROUTES
    // ==========================================

    // Fee Types
    Route::resource('fee-types', FeeTypeController::class);
    Route::get('api/fee-types/dropdown', [FeeTypeController::class, 'dropdown'])
        ->name('fee-types.dropdown');

    // Fee Structures
    Route::post('fee-structures/sync-all-to-students', [FeeStructureController::class, 'syncAllToStudents'])
        ->name('fee-structures.sync-all-to-students');
    Route::post('fee-structures/{feeStructure}/impact-preview', [FeeStructureController::class, 'impactPreview'])
        ->name('fee-structures.impact-preview');
    Route::post('fee-structure-change-requests/{changeRequest}/approve', [FeeStructureController::class, 'approveChange'])
        ->name('fee-structure-change-requests.approve');
    Route::post('fee-structure-change-requests/{changeRequest}/reject', [FeeStructureController::class, 'rejectChange'])
        ->name('fee-structure-change-requests.reject');
    Route::resource('fee-structures', FeeStructureController::class);
    Route::get('api/fee-structures/dropdown', [FeeStructureController::class, 'dropdown'])
        ->name('fee-structures.dropdown');
    Route::post('fee-structures/{feeStructure}/sync-to-students', [FeeStructureController::class, 'syncToStudents'])
        ->name('fee-structures.sync-to-students');

    // Fee Vouchers
    Route::post('fee-vouchers/generate-preview', [FeeVoucherController::class, 'previewMonthlyVouchers'])
        ->name('fee-vouchers.generate-preview');
    Route::post('fee-vouchers/generate-monthly', [FeeVoucherController::class, 'generateMonthlyVouchers'])
        ->name('fee-vouchers.generate-monthly');
    Route::get('api/fee-vouchers/enrollments-by-student/{studentId}', [FeeVoucherController::class, 'enrollmentsByStudent'])
        ->name('fee-vouchers.enrollments-by-student');
    Route::resource('fee-vouchers', FeeVoucherController::class);
    Route::get('api/fee-vouchers/dropdown', [FeeVoucherController::class, 'dropdown'])
        ->name('fee-vouchers.dropdown');

    // Fee Payments
    Route::resource('fee-payments', FeePaymentController::class);
    Route::get('api/fee-payments/dropdown', [FeePaymentController::class, 'dropdown'])
        ->name('fee-payments.dropdown');
    Route::get('api/fee-payments/pending-vouchers/{enrollmentId}', [FeePaymentController::class, 'pendingVouchers'])
        ->name('fee-payments.pending-vouchers');
    Route::get('api/fee-payments/search-students', [FeePaymentController::class, 'searchStudents'])
        ->name('fee-payments.search-students');
    Route::post('fee-payments/store-multiple', [FeePaymentController::class, 'storeMultiple'])
        ->name('fee-payments.store-multiple');

    // Fee Voucher Fines
    Route::post('fee-voucher-fines/apply-overdue', [FeeVoucherFineController::class, 'applyOverdue'])
        ->name('fee-voucher-fines.apply-overdue');
    Route::post('fee-voucher-fines/preview-overdue', [FeeVoucherFineController::class, 'previewOverdue'])
        ->name('fee-voucher-fines.preview-overdue');
    Route::resource('fee-voucher-fines', FeeVoucherFineController::class);

    // Fee Fine Rules
    Route::resource('fee-fine-rules', FeeFineRuleController::class);
    Route::get('api/fee-fine-rules/dropdown', [FeeFineRuleController::class, 'dropdown'])
        ->name('fee-fine-rules.dropdown');

    // Fee Refunds
    Route::resource('fee-refunds', FeeRefundController::class);

    // Fee Waivers
    Route::resource('fee-waivers', FeeWaiverController::class);

    // Fee Advance Adjustments
    Route::resource('fee-advance-adjustments', FeeAdvanceAdjustmentController::class);

    // Fee Collection Summaries
    Route::get('fee-collection-summaries/dashboard', [FeeCollectionSummaryController::class, 'dashboard'])->name('fee-collection-summaries.dashboard');
    Route::get('api/fee-collection-summaries/dashboard', [FeeCollectionSummaryController::class, 'dashboardData'])->name('fee-collection-summaries.dashboard-data');
    Route::get('fee-collection-summaries', [FeeCollectionSummaryController::class, 'index'])->name('fee-collection-summaries.index');

    // Fee Concession Types
    Route::resource('fee-concession-types', FeeConcessionTypeController::class);
    Route::get('api/fee-concession-types/dropdown', [FeeConcessionTypeController::class, 'dropdown'])
        ->name('fee-concession-types.dropdown');

    // Scholarships
    Route::resource('scholarships', ScholarshipController::class);
    Route::get('api/scholarships/dropdown', [ScholarshipController::class, 'dropdown'])
        ->name('scholarships.dropdown');

    // Sibling Discount Rules
    Route::resource('sibling-discount-rules', SiblingDiscountRuleController::class);
    Route::get('api/sibling-discount-rules/dropdown', [SiblingDiscountRuleController::class, 'dropdown'])
        ->name('sibling-discount-rules.dropdown');

    // Installment Plans
    Route::resource('installment-plans', InstallmentPlanController::class);
    Route::get('api/installment-plans/dropdown', [InstallmentPlanController::class, 'dropdown'])
        ->name('installment-plans.dropdown');

    // Academy Payment Accounts
    Route::resource('academy-payment-accounts', AcademyPaymentAccountController::class);
    Route::get('api/academy-payment-accounts/dropdown', [AcademyPaymentAccountController::class, 'dropdown'])
        ->name('academy-payment-accounts.dropdown');

    // Missing Modules added dynamically
    Route::get('api/cheque-tracking/students', [\App\Http\Controllers\ChequeTrackingController::class, 'searchStudents'])
        ->name('cheque-tracking.students');
    Route::resource('cheque-tracking', \App\Http\Controllers\ChequeTrackingController::class);
    Route::resource('online-payment-proofs', \App\Http\Controllers\OnlinePaymentProofController::class);
    Route::resource('fee-voucher-edit-history', \App\Http\Controllers\FeeVoucherEditHistoryController::class);
    Route::resource('voucher-discount-breakdowns', \App\Http\Controllers\VoucherDiscountBreakdownController::class);

    // Fee Reminders
    Route::resource('fee-reminders', FeeReminderController::class);
    Route::post('fee-reminders/{fee_reminder}/followups', [FeeReminderController::class, 'storeFollowup'])->name('fee-reminders.followups.store');
    
    Route::resource('fee-reminder-rules', FeeReminderRuleController::class)->except(['create', 'show', 'edit']);
    Route::resource('fee-reminder-templates', FeeReminderTemplateController::class)->except(['create', 'show', 'edit']);
    
    // Carry Forward
    Route::get('carry-forwards', [CarryForwardController::class, 'index'])->name('carry-forwards.index');
    Route::post('carry-forwards/settings', [CarryForwardController::class, 'updateSettings'])->name('carry-forwards.settings.update');
    
    // Fee Demand Register Report
    Route::get('reports/fee-demand-register', [FeeDemandRegisterController::class, 'index'])->name('reports.fee-demand-register');
    Route::get('api/reports/fee-demand-register/drill-down', [FeeDemandRegisterController::class, 'drillDown'])->name('api.reports.fee-demand-register.drill-down');
    
    Route::get('api/fee-reminders/enrollments-by-student/{studentId}', [FeeReminderController::class, 'getEnrollmentsByStudent'])
        ->name('fee-reminders.enrollments-by-student');
    Route::get('api/fee-reminders/unpaid-vouchers/{enrollmentId}', [FeeReminderController::class, 'getUnpaidVouchers'])
        ->name('fee-reminders.unpaid-vouchers');

    // Student Ledger and Defaulter Management
    Route::get('student-ledgers', [StudentLedgerController::class, 'index'])->name('student-ledgers.index');
    Route::get('api/student-ledgers/data', [StudentLedgerController::class, 'data'])->name('student-ledgers.data');
    Route::post('api/student-ledgers/manual-entry', [StudentLedgerController::class, 'manualEntry'])->name('student-ledgers.manual-entry');

    Route::get('defaulters', [DefaulterManagementController::class, 'index'])->name('defaulters.index');
    Route::get('api/defaulters/data', [DefaulterManagementController::class, 'data'])->name('defaulters.data');
    Route::get('api/defaulters/export', [DefaulterManagementController::class, 'export'])->name('defaulters.export');
    Route::post('api/defaulters/reminders', [DefaulterManagementController::class, 'sendReminders'])->name('defaulters.reminders');
    Route::post('api/defaulters/block', [DefaulterManagementController::class, 'block'])->name('defaulters.block');
    Route::post('api/defaulters/unblock', [DefaulterManagementController::class, 'unblock'])->name('defaulters.unblock');

    // Fee Approval Requests
    Route::resource('fee-approval-requests', FeeApprovalRequestController::class);
    Route::post('fee-approval-requests/{feeApprovalRequest}/approve', [FeeApprovalRequestController::class, 'approve'])
        ->name('fee-approval-requests.approve');
    Route::post('fee-approval-requests/{feeApprovalRequest}/reject', [FeeApprovalRequestController::class, 'reject'])
        ->name('fee-approval-requests.reject');
    Route::get('api/fee-approval-requests/enrollments-by-student/{studentId}', [FeeApprovalRequestController::class, 'getEnrollmentsByStudent'])
        ->name('fee-approval-requests.enrollments-by-student');
    Route::get('api/fee-approval-requests/vouchers-by-enrollment/{enrollmentId}', [FeeApprovalRequestController::class, 'getVouchersByEnrollment'])
        ->name('fee-approval-requests.vouchers-by-enrollment');
    Route::get('api/fee-approval-requests/my-requests', [FeeApprovalRequestController::class, 'myRequests'])
        ->name('fee-approval-requests.my-requests');
    Route::get('api/fee-approval-requests/pending-approval', [FeeApprovalRequestController::class, 'pendingForApproval'])
        ->name('fee-approval-requests.pending-approval');

    // Fee Structure Change Log
    Route::resource('fee-structure-change-logs', FeeStructureChangeLogController::class);
    Route::get('api/fee-structure-change-logs/structure-details/{structureId}', [FeeStructureChangeLogController::class, 'getFeeStructureDetails'])
        ->name('fee-structure-change-logs.structure-details');
    Route::get('api/fee-structure-change-logs/history-by-structure/{structureId}', [FeeStructureChangeLogController::class, 'historyByStructure'])
        ->name('fee-structure-change-logs.history-by-structure');
    Route::get('api/fee-structure-change-logs/recent-changes', [FeeStructureChangeLogController::class, 'recentChanges'])
        ->name('fee-structure-change-logs.recent-changes');

    // Previous Year Balances
    Route::resource('previous-year-balances', PreviousYearBalanceController::class);
    Route::get('api/previous-year-balances/enrollments-by-student/{studentId}', [PreviousYearBalanceController::class, 'getEnrollmentsByStudent'])
        ->name('previous-year-balances.enrollments-by-student');
    Route::get('api/previous-year-balances/student-balances/{studentId}', [PreviousYearBalanceController::class, 'studentBalances'])
        ->name('previous-year-balances.student-balances');
    Route::post('previous-year-balances/carry-forward-bulk', [PreviousYearBalanceController::class, 'carryForwardBulk'])
        ->name('previous-year-balances.carry-forward-bulk');
    Route::post('previous-year-balances/{previousYearBalance}/record-recovery', [PreviousYearBalanceController::class, 'recordRecovery'])
        ->name('previous-year-balances.record-recovery');
    Route::get('api/previous-year-balances/summary', [PreviousYearBalanceController::class, 'summary'])
        ->name('previous-year-balances.summary');

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

    // ==========================================
    // RECYCLE BIN ROUTES
    // ==========================================

    Route::get('/recycle-bin', [\App\Http\Controllers\RecycleBinController::class, 'index'])->name('recycle-bin.index');
    Route::post('/recycle-bin/restore', [\App\Http\Controllers\RecycleBinController::class, 'restore'])->name('recycle-bin.restore');
    Route::post('/recycle-bin/restore-multiple', [\App\Http\Controllers\RecycleBinController::class, 'restoreMultiple'])->name('recycle-bin.restore-multiple');
    Route::delete('/recycle-bin/destroy', [\App\Http\Controllers\RecycleBinController::class, 'destroy'])->name('recycle-bin.destroy');
    Route::delete('/recycle-bin/destroy-multiple', [\App\Http\Controllers\RecycleBinController::class, 'destroyMultiple'])->name('recycle-bin.destroy-multiple');
    Route::delete('/recycle-bin/empty-type', [\App\Http\Controllers\RecycleBinController::class, 'emptyType'])->name('recycle-bin.empty-type');
    Route::delete('/recycle-bin/empty-all', [\App\Http\Controllers\RecycleBinController::class, 'emptyAll'])->name('recycle-bin.empty-all');

    // ==========================================
    // ENHANCED FINANCE APIs
    // ==========================================
    // Enhanced Fee Collections  (Payment Transactions, Billing, Reconciliation)
    Route::prefix('api/enhanced/collections')->group(function () {
        Route::get('/', [EnhancedFeeCollectionsController::class, 'index'])->name('enhanced-collections.index');
        Route::post('/', [EnhancedFeeCollectionsController::class, 'store'])->name('enhanced-collections.store');
        Route::patch('{id}/status', [EnhancedFeeCollectionsController::class, 'updateStatus'])->name('enhanced-collections.update-status');
        Route::get('{id}/receipt', [EnhancedFeeCollectionsController::class, 'generateReceipt'])->name('enhanced-collections.receipt');
        Route::post('bulk', [EnhancedFeeCollectionsController::class, 'processBulkPayment'])->name('enhanced-collections.bulk');
        Route::get('analytics', [EnhancedFeeCollectionsController::class, 'getAnalytics'])->name('enhanced-collections.analytics');
        Route::get('export', [EnhancedFeeCollectionsController::class, 'export'])->name('enhanced-collections.export');
    });

    // Enhanced Fee Dues
    Route::prefix('api/enhanced/dues')->group(function () {
        Route::get('/', [EnhancedFeeDuesController::class, 'index'])->name('enhanced-dues.index');
    });

    // Enhanced Fee Structure Change Logs  (Workflow, Impact, Rollback)
    Route::prefix('api/enhanced/change-logs')->group(function () {
        Route::get('/', [EnhancedFeeStructureChangeLogsController::class, 'index'])->name('enhanced-change-logs.index');
        Route::post('/change-request', [EnhancedFeeStructureChangeLogsController::class, 'createChangeRequest'])->name('enhanced-change-logs.request');
        Route::post('/change-request/{id}/approve', [EnhancedFeeStructureChangeLogsController::class, 'approveChangeRequest'])->name('enhanced-change-logs.approve');
        Route::post('/change-request/{id}/reject', [EnhancedFeeStructureChangeLogsController::class, 'rejectChangeRequest'])->name('enhanced-change-logs.reject');
        Route::get('/change-request/{id}', [EnhancedFeeStructureChangeLogsController::class, 'getChangeRequestDetails'])->name('enhanced-change-logs.details');
        Route::get('/versions/{feeStructureId}', [EnhancedFeeStructureChangeLogsController::class, 'getFeeStructureVersions'])->name('enhanced-change-logs.versions');
        Route::post('/rollback/{versionId}', [EnhancedFeeStructureChangeLogsController::class, 'rollbackFeeStructure'])->name('enhanced-change-logs.rollback');
    });
});
require __DIR__.'/auth.php';
