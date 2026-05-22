<?php

namespace App\Http\Controllers;

use App\Models\StudentInstallmentAssignment;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentFeeConcession;
use App\Models\InstallmentPlan;
use App\Models\InstallmentSchedule;
use App\Models\FeeStructure;
use App\Models\FeeVoucher;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentInstallmentAssignmentController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileAssignments($request);
        }
        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesAssignments($request);
        }
        return Inertia::render('StudentInstallmentAssignments/Index');
    }

    public function create()
    {
        return Inertia::render('StudentInstallmentAssignments/Create', [
            'installmentPlans' => InstallmentPlan::with('feeType:id,fee_name')
                ->select('id', 'plan_name', 'total_installments', 'applicable_fee_type_id')
                ->where('is_active', true)
                ->orderBy('plan_name')
                ->get(),
        ]);
    }

    public function edit(StudentInstallmentAssignment $studentInstallmentAssignment)
    {
        $studentInstallmentAssignment->load([
            'studentEnrollment.student.parent',
            'studentEnrollment.academicYear',
            'studentEnrollment.classSection.branchClass.class',
            'studentEnrollment.classSection.section',
            'installmentPlan',
            'schedule'
        ]);

        $enrollment = $studentInstallmentAssignment->studentEnrollment;
        $studentId = $enrollment?->student_id;
        $amounts = $this->calculateInstallmentAmounts($enrollment, $studentInstallmentAssignment->installmentPlan, (float) $studentInstallmentAssignment->total_amount);
        $effectiveTotal = ($amounts['has_fee_structure'] || $amounts['has_concession'])
            ? $amounts['net_amount']
            : (float) $studentInstallmentAssignment->total_amount;
        $effectiveRemaining = max(0, round($effectiveTotal - (float) $studentInstallmentAssignment->amount_paid, 2));

        return Inertia::render('StudentInstallmentAssignments/Edit', [
            'assignment' => [
                'id'                    => $studentInstallmentAssignment->id,
                'student_id'            => $studentId,
                'student_enrollment_id' => $studentInstallmentAssignment->student_enrollment_id,
                'installment_plan_id'   => $studentInstallmentAssignment->installment_plan_id,
                'total_amount'          => $effectiveTotal,
                'amount_paid'           => $studentInstallmentAssignment->amount_paid,
                'remaining_amount'      => $effectiveRemaining,
                'status'                => $effectiveRemaining <= 0 ? 'completed' : $studentInstallmentAssignment->status,
                'notes'                 => $studentInstallmentAssignment->notes,
            ],
            'installmentPlans' => InstallmentPlan::with('feeType:id,fee_name')
                ->select('id', 'plan_name', 'total_installments', 'applicable_fee_type_id')
                ->where('is_active', true)
                ->orderBy('plan_name')
                ->get(),
            'initialEnrollments' => $studentId
                ? StudentEnrollment::where('student_id', $studentId)
                    ->with(['academicYear:id,year_name', 'classSection.branchClass.class', 'classSection.section'])
                    ->get()
                    ->map(fn($e) => [
                        'id'            => $e->id,
                        'class_name'    => $e->classSection?->branchClass?->class?->class_name ?? '-',
                        'section_name'  => $e->classSection?->section?->section_name ?? '-',
                        'academic_year' => $e->academicYear?->year_name ?? '-',
                    ])
                : [],
            'selectedStudent' => $enrollment?->student ? [
                'id'           => $enrollment->student->id,
                'student_name' => $enrollment->student->student_name,
                'roll_no'      => $enrollment->student->roll_no,
                'admission_no' => $enrollment->student->admission_no,
                'father_name'  => $enrollment->student->parent?->father_name,
            ] : null,
            'existingSchedule' => $studentInstallmentAssignment->schedule
                ->sortBy('kist_number')
                ->values()
                ->map(fn($s) => [
                    'kist_number'  => $s->kist_number,
                    'kist_amount'  => $s->kist_amount,
                    'due_date'     => $s->due_date?->format('Y-m-d'),
                    'paid_amount'  => $s->paid_amount,
                    'payment_date' => $s->payment_date?->format('Y-m-d'),
                    'status'       => $s->status,
                ]),
        ]);
    }

    public function show(StudentInstallmentAssignment $studentInstallmentAssignment)
    {
        $studentInstallmentAssignment->load([
            'studentEnrollment.student',
            'studentEnrollment.academicYear',
            'studentEnrollment.classSection.branchClass.class',
            'studentEnrollment.classSection.section',
            'installmentPlan.feeType',
            'schedule',
            'approvedBy',
        ]);

        $enrollment = $studentInstallmentAssignment->studentEnrollment;
        $plan       = $studentInstallmentAssignment->installmentPlan;

        $amounts = $this->calculateInstallmentAmounts($enrollment, $plan, (float) $studentInstallmentAssignment->total_amount);
        $baseAmount = $amounts['base_amount'];
        $concessionAmount = $amounts['concession_amount'];
        $concessionDetails = $amounts['concession_detail'];
        $effectiveTotal = ($amounts['has_fee_structure'] || $amounts['has_concession'])
            ? $amounts['net_amount']
            : (float) $studentInstallmentAssignment->total_amount;
        $effectiveRemaining = max(0, round($effectiveTotal - (float) $studentInstallmentAssignment->amount_paid, 2));

        return response()->json([
            'id' => $studentInstallmentAssignment->id,
            'student' => [
                'name' => $enrollment?->student?->student_name ?? 'N/A',
                'roll_no' => $enrollment?->student?->roll_no ?? 'N/A',
                'admission_no' => $enrollment?->student?->admission_no ?? 'N/A',
                'father_name' => $enrollment?->student?->father_name ?? 'N/A',
            ],
            'class_info' => [
                'class_name' => $enrollment?->classSection?->branchClass?->class?->class_name ?? 'N/A',
                'section_name' => $enrollment?->classSection?->section?->section_name ?? 'N/A',
                'academic_year' => $enrollment?->academicYear?->year_name ?? 'N/A',
            ],
            'plan' => [
                'plan_name' => $plan?->plan_name ?? 'N/A',
                'total_installments' => $plan?->total_installments ?? 0,
                'fee_type' => $plan?->feeType?->fee_name ?? 'N/A',
            ],
            'fee_breakdown' => [
                'base_amount'       => $baseAmount,
                'concession_amount' => $concessionAmount,
                'concession_detail' => $concessionDetails,
                'has_concession'    => $amounts['has_concession'],
                'net_amount'        => $effectiveTotal,
            ],
            'total_amount' => $effectiveTotal,
            'amount_paid' => $studentInstallmentAssignment->amount_paid,
            'remaining_amount' => $effectiveRemaining,
            'status' => $effectiveRemaining <= 0 ? 'completed' : $studentInstallmentAssignment->status,
            'notes' => $studentInstallmentAssignment->notes,
            'approved_by' => $studentInstallmentAssignment->approvedBy?->name ?? 'N/A',
            'created_at' => $studentInstallmentAssignment->created_at?->format('d M, Y h:i A') ?? 'N/A',
            'schedule' => $studentInstallmentAssignment->schedule
                ->sortBy('kist_number')
                ->values()
                ->map(fn($s) => [
                    'kist_number' => $s->kist_number,
                    'kist_amount' => $s->kist_amount,
                    'due_date' => $s->due_date?->format('d M, Y') ?? 'N/A',
                    'paid_amount' => $s->paid_amount,
                    'payment_date' => $s->payment_date?->format('d M, Y') ?? 'Not Paid',
                    'status' => $s->status,
                    'remaining' => $s->kist_amount - $s->paid_amount,
                ]),
        ]);
    }

    /**
     * API: Get enrollments for a specific student (for cascading dropdown)
     */
    public function enrollmentsByStudent($studentId)
    {
        $enrollments = StudentEnrollment::where('student_id', $studentId)
            ->with(['academicYear:id,year_name', 'classSection.branchClass.class', 'classSection.section'])
            ->get()
            ->map(fn($e) => [
                'id'            => $e->id,
                'class_name'    => $e->classSection?->branchClass?->class?->class_name ?? '-',
                'section_name'  => $e->classSection?->section?->section_name ?? '-',
                'academic_year' => $e->academicYear?->year_name ?? '-',
            ]);

        return response()->json($enrollments);
    }

    /**
     * API: Get fee amount for a given enrollment + installment plan
     * Looks up FeeStructure based on enrollment's branch, class, academic year, and plan's fee type
     */
    public function getFeeForEnrollment(Request $request)
    {
        $enrollmentId = $request->query('enrollment_id');
        $planId = $request->query('plan_id');

        if (!$enrollmentId || !$planId) {
            return response()->json(['amount' => 0, 'fee_type_name' => null, 'message' => 'Missing parameters']);
        }

        $enrollment = StudentEnrollment::with(['classSection.branchClass'])
            ->find($enrollmentId);
        $plan = InstallmentPlan::find($planId);

        if (!$enrollment || !$plan) {
            return response()->json(['amount' => 0, 'fee_type_name' => null, 'message' => 'Not found']);
        }

        $branchClass = $enrollment->classSection?->branchClass;
        $branchId = $branchClass?->branch_id ?? $enrollment->branch_id;
        $classId = $branchClass?->class_id;

        // Look up fee structure
        $feeStructure = FeeStructure::where('academic_year_id', $enrollment->academic_year_id)
            ->where('branch_id', $branchId)
            ->where('class_id', $classId)
            ->where('fee_type_id', $plan->applicable_fee_type_id)
            ->where('is_active', true)
            ->first();

        if (!$feeStructure) {
            return response()->json([
                'amount' => 0,
                'fee_type_name' => $plan->feeType?->fee_name,
                'due_day' => 10,
                'message' => 'No fee structure found for this enrollment and fee type',
            ]);
        }

        $amounts = $this->calculateInstallmentAmounts($enrollment, $plan, 0);

        // Check for student concession — matches specific fee type OR "All Fee Types" (NULL)
        return response()->json([
            'amount' => $amounts['net_amount'],
            'base_amount' => $amounts['base_amount'],
            'concession_amount' => $amounts['concession_amount'],
            'concession_details' => $amounts['concession_detail'],
            'fee_type_name' => $plan->feeType?->fee_name,
            'due_day' => $feeStructure->due_day ?? 10,
            'message' => 'ok',
        ]);
    }

    private function getMobileAssignments(Request $request)
    {
        $query = StudentInstallmentAssignment::with([
            'studentEnrollment.student',
            'studentEnrollment.classSection.branchClass',
            'installmentPlan',
            'feeVoucher',
        ]);
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('studentEnrollment.student', function ($sq) use ($search) {
                    $sq->where('roll_no', 'like', "%{$search}%")
                       ->orWhere('student_name', 'like', "%{$search}%")
                       ->orWhere('admission_no', 'like', "%{$search}%");
                })
                ->orWhere('total_amount', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%");
            });
        }
        if ($request->filled('status')) { $query->where('status', $request->status); }

        $assignments = $query->latest()->paginate($request->get('per_page', 10));
        $assignments->through(function ($assignment) {
            return $this->withEffectiveInstallmentAmounts($assignment);
        });

        return response()->json($assignments);
    }

    private function getDataTablesAssignments(Request $request)
    {
        $query = StudentInstallmentAssignment::with([
            'studentEnrollment.student',
            'studentEnrollment.classSection.branchClass',
            'installmentPlan',
            'feeVoucher',
        ]);
        $recordsTotal = StudentInstallmentAssignment::count();

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->whereHas('studentEnrollment.student', function ($sq) use ($search) {
                    $sq->where('roll_no', 'like', "%{$search}%")
                       ->orWhere('student_name', 'like', "%{$search}%")
                       ->orWhere('admission_no', 'like', "%{$search}%");
                })
                ->orWhere('total_amount', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%");
            });
        }
        if ($request->filled('status')) { $query->where('status', $request->status); }

        $recordsFiltered = $query->count();
        $columns = ['id', 'student_enrollment_id', 'installment_plan_id', 'total_amount', 'status'];
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');
        if (isset($columns[$orderColumn])) { $query->orderBy($columns[$orderColumn], $orderDir); }

        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $assignments = $query->skip($start)->take($length)->get();

        $data = $assignments->map(function ($a, $index) use ($start) {
            $a = $this->withEffectiveInstallmentAmounts($a);
            $displayStatus = $a->effective_status ?? $a->status;
            $statusClass = match($displayStatus) { 'active' => 'bg-green-100 text-green-800', 'completed' => 'bg-blue-100 text-blue-800', 'cancelled' => 'bg-red-100 text-red-800', default => 'bg-gray-100 text-gray-800' };
            return [
                'DT_RowIndex'      => $start + $index + 1, 'id' => $a->id,
                'student_name'     => $a->studentEnrollment?->student?->student_name ?? '-',
                'plan_name'        => $a->installmentPlan?->plan_name ?? '-',
                'total_amount'     => number_format($a->effective_total_amount ?? $a->total_amount, 2),
                'amount_paid'      => number_format($a->amount_paid ?? 0, 2),
                'remaining_amount' => number_format($a->effective_remaining_amount ?? $a->remaining_amount ?? 0, 2),
                'status'         => '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $statusClass . '">' . ucfirst($displayStatus) . '</span>',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <button onclick=\'showAssignment(' . json_encode(['id' => $a->id]) . ')\' class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>View</button>
                        <button onclick=\'editAssignment(' . json_encode(['id' => $a->id]) . ')\' class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Edit</button>
                        <button onclick="deleteAssignment(' . $a->id . ')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>Delete</button>
                    </div>'
            ];
        });
        return response()->json(['draw' => intval($request->input('draw')), 'recordsTotal' => $recordsTotal, 'recordsFiltered' => $recordsFiltered, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_enrollment_id' => 'required|exists:student_enrollments,id',
            'installment_plan_id'   => 'required|exists:installment_plans,id',
            'total_amount'          => 'required|numeric|min:0',
            'status'                => 'nullable|string|in:active,completed,defaulted',
            'notes'                 => 'nullable|string',
            'schedule'              => 'nullable|array',
            'schedule.*.kist_number' => 'required|integer|min:1',
            'schedule.*.kist_amount' => 'required|numeric|min:0',
            'schedule.*.due_date'    => 'required|date',
        ]);

        // Check for duplicate active assignment (same student + enrollment + plan)
        $exists = StudentInstallmentAssignment::where('student_enrollment_id', $validated['student_enrollment_id'])
            ->where('installment_plan_id', $validated['installment_plan_id'])
            ->where('status', 'active')
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'An active installment assignment already exists for this student with the same plan. Please edit the existing record or complete/cancel it first.');
        }

        $enrollment = StudentEnrollment::with(['classSection.branchClass'])->findOrFail($validated['student_enrollment_id']);
        $plan = InstallmentPlan::findOrFail($validated['installment_plan_id']);
        $linkedVoucher = $this->findVoucherForInstallment($enrollment, $plan);
        $amounts = $this->calculateInstallmentAmounts($enrollment, $plan, (float) $validated['total_amount']);
        $totalAmount = ($amounts['has_fee_structure'] || $amounts['has_concession'])
            ? $amounts['net_amount']
            : (float) $validated['total_amount'];
        $schedule = collect($validated['schedule'] ?? [])->values();

        if ($totalAmount > 0 && $schedule->isEmpty()) {
            throw ValidationException::withMessages([
                'schedule' => 'Installment schedule is required when net amount is greater than zero.',
            ]);
        }

        DB::transaction(function () use ($validated, $totalAmount, $schedule, $linkedVoucher) {
            $assignment = StudentInstallmentAssignment::create([
                'student_enrollment_id' => $validated['student_enrollment_id'],
                'installment_plan_id'   => $validated['installment_plan_id'],
                'fee_voucher_id'        => $linkedVoucher?->id,
                'total_amount'          => $totalAmount,
                'remaining_amount'      => $totalAmount,
                'amount_paid'           => 0,
                'status'                => $totalAmount <= 0 ? 'completed' : ($validated['status'] ?? 'active'),
                'approved_by'           => auth()->id(),
                'notes'                 => $validated['notes'] ?? null,
            ]);

            if ($totalAmount > 0) {
                foreach ($schedule as $kist) {
                    InstallmentSchedule::create([
                        'assignment_id' => $assignment->id,
                        'kist_number'   => $kist['kist_number'],
                        'kist_amount'   => $kist['kist_amount'],
                        'due_date'      => $kist['due_date'],
                        'status'        => 'pending',
                    ]);
                }
            }
        });

        return redirect()->route('student-installment-assignments.index')->with('success', 'Installment assigned successfully with ' . ($totalAmount > 0 ? $schedule->count() : 0) . ' kists!');
    }

    public function update(Request $request, StudentInstallmentAssignment $studentInstallmentAssignment)
    {
        $validated = $request->validate([
            'total_amount' => 'required|numeric|min:0',
            'status'       => 'nullable|string|in:active,completed,defaulted',
            'notes'        => 'nullable|string',
        ]);
        $studentInstallmentAssignment->update($validated);
        return redirect()->route('student-installment-assignments.index')->with('success', 'Assignment updated successfully!');
    }

    public function destroy(StudentInstallmentAssignment $studentInstallmentAssignment)
    {
        // Delete schedule first (cascade), then assignment
        $studentInstallmentAssignment->schedule()->delete();
        $studentInstallmentAssignment->delete();
        return back()->with('success', 'Assignment deleted successfully!');
    }

    private function calculateInstallmentAmounts(?StudentEnrollment $enrollment, ?InstallmentPlan $plan, float $fallbackAmount = 0): array
    {
        $branchClass = $enrollment?->classSection?->branchClass;
        $branchId = $branchClass?->branch_id ?? $enrollment?->branch_id;
        $classId = $branchClass?->class_id;
        $feeTypeId = $plan?->applicable_fee_type_id;

        $voucher = $this->findVoucherForInstallment($enrollment, $plan);
        if ($voucher) {
            return [
                'base_amount' => round((float) $voucher->original_amount, 2),
                'concession_amount' => round((float) $voucher->discount_amount, 2),
                'concession_detail' => $voucher->discount_amount > 0 ? 'Voucher discounts applied' : null,
                'net_amount' => round((float) $voucher->remaining_amount, 2),
                'has_concession' => (float) $voucher->discount_amount > 0,
                'has_fee_structure' => true,
            ];
        }

        $feeStructure = ($enrollment && $classId && $feeTypeId)
            ? FeeStructure::where('academic_year_id', $enrollment->academic_year_id)
                ->where('branch_id', $branchId)
                ->where('class_id', $classId)
                ->where('fee_type_id', $feeTypeId)
                ->where('is_active', true)
                ->first()
            : null;

        $baseAmount = (float) ($feeStructure?->amount ?? $fallbackAmount);
        $concession = $enrollment ? StudentFeeConcession::with('concessionType')
            ->where('student_enrollment_id', $enrollment->id)
            ->where(function ($q) use ($feeTypeId) {
                $q->where('fee_type_id', $feeTypeId)
                    ->orWhereNull('fee_type_id');
            })
            ->where('is_active', true)
            ->first() : null;

        $concessionAmount = 0;
        $concessionDetail = null;

        if ($concession && $baseAmount > 0) {
            if ($concession->discount_type === 'percentage') {
                $concessionAmount = round(($baseAmount * (float) $concession->discount_value) / 100, 2);
                $concessionDetail = $concession->discount_value . '% discount (' . ($concession->concessionType?->concession_name ?? 'Concession') . ')';
            } else {
                $concessionAmount = (float) $concession->discount_value;
                $concessionDetail = 'Fixed Rs ' . number_format($concessionAmount, 2) . ' (' . ($concession->concessionType?->concession_name ?? 'Concession') . ')';
            }
        }

        $concessionAmount = min($baseAmount, max(0, $concessionAmount));
        $netAmount = max(0, round($baseAmount - $concessionAmount, 2));

        return [
            'base_amount' => round($baseAmount, 2),
            'concession_amount' => round($concessionAmount, 2),
            'concession_detail' => $concessionDetail,
            'net_amount' => $netAmount,
            'has_concession' => $concession !== null && $concessionAmount > 0,
            'has_fee_structure' => $feeStructure !== null,
        ];
    }

    private function withEffectiveInstallmentAmounts(StudentInstallmentAssignment $assignment): StudentInstallmentAssignment
    {
        if ($assignment->feeVoucher) {
            $effectiveTotal = (float) $assignment->feeVoucher->net_amount;
            $effectiveRemaining = (float) $assignment->feeVoucher->remaining_amount;
            $assignment->setAttribute('effective_total_amount', $effectiveTotal);
            $assignment->setAttribute('effective_remaining_amount', $effectiveRemaining);
            $assignment->setAttribute('effective_status', $effectiveRemaining <= 0 ? 'completed' : $assignment->status);

            return $assignment;
        }

        $amounts = $this->calculateInstallmentAmounts(
            $assignment->studentEnrollment,
            $assignment->installmentPlan,
            (float) $assignment->total_amount
        );

        $effectiveTotal = ($amounts['has_fee_structure'] || $amounts['has_concession'])
            ? $amounts['net_amount']
            : (float) $assignment->total_amount;
        $effectiveRemaining = max(0, round($effectiveTotal - (float) $assignment->amount_paid, 2));

        $assignment->setAttribute('effective_total_amount', $effectiveTotal);
        $assignment->setAttribute('effective_remaining_amount', $effectiveRemaining);
        $assignment->setAttribute('effective_status', $effectiveRemaining <= 0 ? 'completed' : $assignment->status);

        return $assignment;
    }

    private function findVoucherForInstallment(?StudentEnrollment $enrollment, ?InstallmentPlan $plan): ?FeeVoucher
    {
        $feeTypeId = $plan?->applicable_fee_type_id;
        if (!$enrollment || !$feeTypeId) {
            return null;
        }

        return FeeVoucher::where('student_enrollment_id', $enrollment->id)
            ->where('fee_type_id', $feeTypeId)
            ->whereIn('status', ['pending', 'partial', 'paid'])
            ->latest('id')
            ->first();
    }
}
