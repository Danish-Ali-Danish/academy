<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Classes;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use App\Models\BranchClass;
use App\Models\Subject;
use App\Models\SubjectGroup;
use App\Models\Section;
use App\Models\Student;
use App\Models\Parents;
use App\Models\StudentEnrollment;
use App\Models\FeeType;
use App\Models\FeeStructure;
use App\Models\StudentFeeStructure;
use App\Models\FeeConcessionType;
use App\Models\StudentFeeConcession;
use App\Models\FeeFineRule;
use App\Models\SiblingDiscountRule;
use App\Models\InstallmentPlan;
use App\Models\StudentInstallmentAssignment;
use App\Models\Scholarship;
use App\Models\StudentScholarship;
use App\Models\FeeWaiver;
use App\Models\FeeRefund;
use App\Models\FeeVoucher;
use App\Models\FeePayment;
use App\Models\OnlinePaymentProof;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RecycleBinController extends Controller
{
    protected array $models = [
        'branches' => Branch::class,
        'classes' => Classes::class,
        'class-sections' => ClassSection::class,
        'class-subjects' => ClassSubject::class,
        'branch-classes' => BranchClass::class,
        'subjects' => Subject::class,
        'subject-groups' => SubjectGroup::class,
        'sections' => Section::class,
        'students' => Student::class,
        'parents' => Parents::class,
        'student-enrollments' => StudentEnrollment::class,
        'fee-types' => FeeType::class,
        'fee-structures' => FeeStructure::class,
        'student-fee-structures' => StudentFeeStructure::class,
        'fee-concession-types' => FeeConcessionType::class,
        'student-fee-concessions' => StudentFeeConcession::class,
        'fee-fine-rules' => FeeFineRule::class,
        'sibling-discount-rules' => SiblingDiscountRule::class,
        'installment-plans' => InstallmentPlan::class,
        'student-installment-assignments' => StudentInstallmentAssignment::class,
        'scholarships' => Scholarship::class,
        'student-scholarships' => StudentScholarship::class,
        'fee-waivers' => FeeWaiver::class,
        'fee-refunds' => FeeRefund::class,
        'fee-vouchers' => FeeVoucher::class,
        'fee-payments' => FeePayment::class,
        'online-payment-proofs' => OnlinePaymentProof::class,
    ];

    protected array $labels = [
        'branches' => 'Branches',
        'classes' => 'Classes',
        'class-sections' => 'Class Sections',
        'class-subjects' => 'Class Subjects',
        'branch-classes' => 'Branch Classes',
        'subjects' => 'Subjects',
        'subject-groups' => 'Subject Groups',
        'sections' => 'Sections',
        'students' => 'Students',
        'parents' => 'Parents',
        'student-enrollments' => 'Student Enrollments',
        'fee-types' => 'Fee Types',
        'fee-structures' => 'Fee Structures',
        'student-fee-structures' => 'Student Fee Structures',
        'fee-concession-types' => 'Fee Concession Types',
        'student-fee-concessions' => 'Student Fee Concessions',
        'fee-fine-rules' => 'Fee Fine Rules',
        'sibling-discount-rules' => 'Sibling Discount Rules',
        'installment-plans' => 'Installment Plans',
        'student-installment-assignments' => 'Student Installment Assignments',
        'scholarships' => 'Scholarships',
        'student-scholarships' => 'Student Scholarships',
        'fee-waivers' => 'Fee Waivers',
        'fee-refunds' => 'Fee Refunds',
        'fee-vouchers' => 'Fee Vouchers',
        'fee-payments' => 'Fee Payments',
        'online-payment-proofs' => 'Online Payment Proofs',
    ];

    public function index(Request $request)
    {
        $type = $request->get('type', 'students');
        
        if (!isset($this->models[$type])) {
            $type = 'students';
        }

        $modelClass = $this->models[$type];
        
        $items = $modelClass::onlyTrashed()
            ->orderBy('deleted_at', 'desc')
            ->get()
            ->map(function ($item) use ($type) {
                return [
                    'id' => $item->id,
                    'deleted_at' => $item->deleted_at,
                    'label' => $this->getLabel($item, $type),
                ];
            });

        $counts = [];
        foreach ($this->models as $key => $model) {
            $counts[$key] = $model::onlyTrashed()->count();
        }

        return Inertia::render('RecycleBin/Index', [
            'items' => $items,
            'type' => $type,
            'label' => $this->labels[$type] ?? $type,
            'counts' => $counts,
            'types' => array_keys($this->models),
        ]);
    }

    protected function getLabel($item, string $type): string
    {
        return match ($type) {
            'branches' => $item->name ?? 'Branch #' . $item->id,
            'classes' => $item->class_name ?? 'Class #' . $item->id,
            'class-sections' => ($item->branchClass?->class_name ?? '') . ' - ' . ($item->section?->section_name ?? ''),
            'class-subjects' => $item->subject?->subject_name ?? 'Subject #' . $item->id,
            'branch-classes' => $item->class_name ?? 'Branch Class #' . $item->id,
            'subjects' => $item->subject_name ?? 'Subject #' . $item->id,
            'subject-groups' => $item->group_name ?? 'Subject Group #' . $item->id,
            'sections' => $item->section_name ?? 'Section #' . $item->id,
            'students' => $item->first_name . ' ' . $item->last_name ?? 'Student #' . $item->id,
            'parents' => $item->father_name ?? 'Parent #' . $item->id,
            'student-enrollments' => 'Enrollment #' . $item->id . ' - ' . ($item->student?->first_name ?? ''),
            'fee-types' => $item->fee_name ?? 'Fee Type #' . $item->id,
            'fee-structures' => $item->structure_name ?? 'Fee Structure #' . $item->id,
            'student-fee-structures' => 'Student Fee #' . $item->id,
            'fee-concession-types' => $item->concession_name ?? 'Concession Type #' . $item->id,
            'student-fee-concessions' => 'Student Concession #' . $item->id,
            'fee-fine-rules' => $item->rule_name ?? 'Fine Rule #' . $item->id,
            'sibling-discount-rules' => $item->rule_name ?? 'Sibling Discount #' . $item->id,
            'installment-plans' => $item->plan_name ?? 'Installment Plan #' . $item->id,
            'student-installment-assignments' => 'Installment Assignment #' . $item->id,
            'scholarships' => $item->scholarship_name ?? 'Scholarship #' . $item->id,
            'student-scholarships' => 'Student Scholarship #' . $item->id,
            'fee-waivers' => 'Fee Waiver #' . $item->id,
            'fee-refunds' => 'Fee Refund #' . $item->id,
            'fee-vouchers' => 'Voucher #' . $item->voucher_number ?? $item->id,
            'fee-payments' => 'Payment #' . $item->id,
            'online-payment-proofs' => 'Payment Proof #' . $item->id,
            default => 'Item #' . $item->id,
        };
    }

    public function restore(Request $request)
    {
        $type = $request->get('type');
        $id = $request->get('id');

        if (!isset($this->models[$type])) {
            return back()->with('error', 'Invalid type');
        }

        $modelClass = $this->models[$type];
        $item = $modelClass::onlyTrashed()->find($id);

        if (!$item) {
            return back()->with('error', 'Item not found');
        }

        $item->restore();

        return back()->with('success', 'Item restored successfully');
    }

    public function restoreMultiple(Request $request)
    {
        $type = $request->get('type');
        $ids = $request->get('ids', []);

        if (!isset($this->models[$type])) {
            return back()->with('error', 'Invalid type');
        }

        $modelClass = $this->models[$type];
        $modelClass::onlyTrashed()->whereIn('id', $ids)->restore();

        return back()->with('success', count($ids) . ' items restored successfully');
    }

    public function destroy(Request $request)
    {
        $type = $request->get('type');
        $id = $request->get('id');

        if (!isset($this->models[$type])) {
            return back()->with('error', 'Invalid type');
        }

        $modelClass = $this->models[$type];
        $item = $modelClass::onlyTrashed()->find($id);

        if (!$item) {
            return back()->with('error', 'Item not found');
        }

        $item->forceDelete();

        return back()->with('success', 'Item permanently deleted');
    }

    public function destroyMultiple(Request $request)
    {
        $type = $request->get('type');
        $ids = $request->get('ids', []);

        if (!isset($this->models[$type])) {
            return back()->with('error', 'Invalid type');
        }

        $modelClass = $this->models[$type];
        $modelClass::onlyTrashed()->whereIn('id', $ids)->forceDelete();

        return back()->with('success', count($ids) . ' items permanently deleted');
    }

    public function emptyType(Request $request)
    {
        $type = $request->get('type');

        if (!isset($this->models[$type])) {
            return back()->with('error', 'Invalid type');
        }

        $modelClass = $this->models[$type];
        $count = $modelClass::onlyTrashed()->count();
        
        $modelClass::onlyTrashed()->forceDelete();

        return back()->with('success', $count . ' items permanently deleted');
    }

    public function emptyAll()
    {
        $total = 0;
        foreach ($this->models as $model) {
            $total += $model::onlyTrashed()->count();
            $model::onlyTrashed()->forceDelete();
        }

        return back()->with('success', $total . ' items permanently deleted');
    }
}