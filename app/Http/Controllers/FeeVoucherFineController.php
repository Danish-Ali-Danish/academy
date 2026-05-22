<?php

namespace App\Http\Controllers;

use App\Models\FeeFineRule;
use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\FeeType;
use App\Models\FeeVoucher;
use App\Models\FeeVoucherFine;
use App\Models\StudentLedger;
use App\Services\FeeGenerationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FeeVoucherFineController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileFines($request);
        }

        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesFines($request);
        }

        return Inertia::render('FeeVoucherFines/Index', [
            'generatorOptions' => $this->getGeneratorOptions(),
        ]);
    }

    public function create()
    {
        return Inertia::render('FeeVoucherFines/Create', $this->getFormData());
    }

    public function edit(FeeVoucherFine $feeVoucherFine)
    {
        $feeVoucherFine->load(['voucher.studentEnrollment.student', 'voucher.feeType', 'fineRule', 'appliedBy']);

        return Inertia::render('FeeVoucherFines/Edit', array_merge(
            $this->getFormData(true),
            [
                'fine' => [
                    'id' => $feeVoucherFine->id,
                    'voucher_id' => $feeVoucherFine->voucher_id,
                    'voucher' => $this->formatVoucher($feeVoucherFine->voucher),
                    'fine_rule_id' => $feeVoucherFine->fine_rule_id,
                    'days_overdue' => $feeVoucherFine->days_overdue,
                    'fine_type' => $feeVoucherFine->fine_type,
                    'fine_value' => $feeVoucherFine->fine_value,
                    'calculated_amount' => $feeVoucherFine->calculated_amount,
                    'applied_on' => $feeVoucherFine->applied_on?->format('Y-m-d'),
                    'is_waived' => $feeVoucherFine->is_waived,
                    'notes' => $feeVoucherFine->notes,
                ],
            ]
        ));
    }

    public function applyOverdue(FeeGenerationService $feeGenerationService)
    {
        $validated = $this->validateOverdueFilters(request());
        $result = $feeGenerationService->applyFinesToOverdueVouchers($validated);

        if (request()->expectsJson()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message'] ?? 'Overdue fines processed.');
    }

    public function previewOverdue(Request $request, FeeGenerationService $feeGenerationService)
    {
        $validated = $this->validateOverdueFilters($request);

        return response()->json($feeGenerationService->previewOverdueFines($validated));
    }

    public function store(Request $request)
    {
        $validated = $this->validateFine($request);

        DB::transaction(function () use ($validated) {
            $voucher = FeeVoucher::lockForUpdate()->findOrFail($validated['voucher_id']);
            $fineData = $this->prepareFineData($validated, $voucher);

            if (!empty($fineData['fine_rule_id'])) {
                $exists = FeeVoucherFine::where('voucher_id', $voucher->id)
                    ->where('fine_rule_id', $fineData['fine_rule_id'])
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'fine_rule_id' => 'This fine rule has already been applied to this voucher.',
                    ]);
                }
            }

            $fine = FeeVoucherFine::create($fineData);

            if (!$fine->is_waived) {
                $this->applyFineAmountToVoucher($voucher, (float) $fine->calculated_amount, $fine);
            }
        });

        return redirect()->route('fee-voucher-fines.index')
            ->with('success', 'Voucher fine applied successfully.');
    }

    public function update(Request $request, FeeVoucherFine $feeVoucherFine)
    {
        $validated = $this->validateFine($request, $feeVoucherFine);

        $feeVoucherFine->refresh();
        $voucher = FeeVoucher::lockForUpdate()->findOrFail($feeVoucherFine->voucher_id);

        if (($validated['is_waived'] ?? false) && !$feeVoucherFine->is_waived) {
            if (!auth()->user()->hasAnyRole(['Admin', 'Branch Manager', 'Fee Manager'])) {
                \App\Models\FeeApprovalRequest::create([
                    'request_type'          => 'fine_waiver',
                    'student_enrollment_id' => $voucher->student_enrollment_id,
                    'voucher_id'            => $voucher->id,
                    'action_reference_type' => \App\Models\FeeVoucherFine::class,
                    'action_reference_id'   => $feeVoucherFine->id,
                    'requested_amount'      => (float) $feeVoucherFine->calculated_amount,
                    'current_amount'        => (float) $voucher->remaining_amount,
                    'reason'                => $validated['notes'] ?? 'Fine waiver requested',
                    'urgency'               => 'normal',
                    'status'                => 'pending',
                    'requested_by'          => auth()->id() ?? 1,
                    'requested_at'          => now(),
                ]);

                return redirect()->route('fee-voucher-fines.index')
                    ->with('success', 'Your fine waiver request has been submitted for approval.');
            }
        }

        DB::transaction(function () use ($validated, $feeVoucherFine, $voucher) {
            $oldAppliedAmount = $feeVoucherFine->is_waived ? 0 : (float) $feeVoucherFine->calculated_amount;
            $fineData = $this->prepareFineData($validated, $voucher, $feeVoucherFine);
            $newAppliedAmount = ($fineData['is_waived'] ?? false) ? 0 : (float) $fineData['calculated_amount'];
            $delta = round($newAppliedAmount - $oldAppliedAmount, 2);

            $feeVoucherFine->update($fineData);

            if ($delta !== 0.0) {
                $this->applyFineAmountToVoucher($voucher, $delta, $feeVoucherFine);
            }
        });

        return redirect()->route('fee-voucher-fines.index')
            ->with('success', 'Voucher fine updated successfully.');
    }

    public function destroy(FeeVoucherFine $feeVoucherFine)
    {
        if (!auth()->user()->hasAnyRole(['Admin', 'Branch Manager', 'Fee Manager'])) {
            return back()->with('error', 'You do not have permission to delete fines. Please contact an Administrator.');
        }

        DB::transaction(function () use ($feeVoucherFine) {
            $feeVoucherFine->refresh();
            $voucher = FeeVoucher::lockForUpdate()->findOrFail($feeVoucherFine->voucher_id);

            if (!$feeVoucherFine->is_waived && (float) $feeVoucherFine->calculated_amount > 0) {
                $this->applyFineAmountToVoucher($voucher, -1 * (float) $feeVoucherFine->calculated_amount, $feeVoucherFine);
            }

            $feeVoucherFine->delete();
        });

        return back()->with('success', 'Voucher fine removed successfully.');
    }

    private function getFormData(bool $includeAllVouchers = false): array
    {
        $voucherQuery = FeeVoucher::with(['studentEnrollment.student', 'feeType'])
            ->when(!$includeAllVouchers, fn ($q) => $q->whereIn('status', ['pending', 'partial']))
            ->orderBy('id', 'desc');

        return [
            'vouchers' => $voucherQuery->get()->map(fn ($voucher) => $this->formatVoucher($voucher))->values(),
            'fineRules' => FeeFineRule::active()
                ->select('id', 'description', 'fine_type', 'fine_value', 'days_after_due', 'max_fine')
                ->orderBy('days_after_due')
                ->get(),
        ];
    }

    private function getGeneratorOptions(): array
    {
        return [
            'branches' => Branch::select('id', 'branch_name')
                ->where('is_active', true)
                ->orderBy('branch_name')
                ->get(),
            'classes' => Classes::select('id', 'class_name')
                ->where('is_active', true)
                ->orderBy('display_order')
                ->orderBy('class_name')
                ->get(),
            'feeTypes' => FeeType::select('id', 'fee_name')
                ->where('is_active', true)
                ->orderBy('fee_name')
                ->get(),
            'academicYears' => AcademicYear::select('id', 'year_name')
                ->orderBy('start_date', 'desc')
                ->get(),
        ];
    }

    private function validateOverdueFilters(Request $request): array
    {
        return $request->validate([
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'branch_id' => 'nullable|exists:branches,id',
            'class_id' => 'nullable|exists:classes,id',
            'fee_type_id' => 'nullable|exists:fee_types,id',
            'as_of_date' => 'nullable|date',
        ]);
    }

    private function getMobileFines(Request $request)
    {
        $query = $this->fineQuery();
        $this->applyFineSearch($query, $request->search);

        return response()->json($query->latest()->paginate($request->get('per_page', 10)));
    }

    private function getDataTablesFines(Request $request)
    {
        $query = $this->fineQuery();
        $this->applyFineSearch($query, $request->input('search.value'));

        $recordsFiltered = (clone $query)->count();
        $columns = ['id', 'voucher_id', 'days_overdue', 'fine_type', 'fine_value', 'calculated_amount', 'applied_on'];
        $orderColumn = (int) $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        $start = max(0, (int) $request->input('start', 0));
        $length = max(1, (int) $request->input('length', 10));
        $fines = $query->skip($start)->take($length)->get();

        $data = $fines->map(function ($fine, $index) use ($start) {
            $student = $fine->voucher?->studentEnrollment?->student;
            $statusClass = $fine->is_waived ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800';
            $statusText = $fine->is_waived ? 'Waived' : 'Applied';

            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $fine->id,
                'voucher_no' => '<div class="text-left"><div class="font-semibold text-gray-900">' . e($fine->voucher?->voucher_no ?? '-') . '</div><div class="text-xs text-gray-500">' . e($student?->student_name ?? '-') . '</div></div>',
                'days_overdue' => $fine->days_overdue . ' days',
                'fine_type' => $this->fineTypeLabel($fine->fine_type),
                'fine_value' => $this->formatFineValue($fine->fine_type, (float) $fine->fine_value),
                'calculated_amount' => 'Rs ' . number_format((float) $fine->calculated_amount, 2),
                'applied_on' => $fine->applied_on?->format('d M, Y') ?? '-',
                'applied_by' => $fine->appliedBy?->name ?? 'System',
                'is_waived' => '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $statusClass . '">' . $statusText . '</span>',
                'action' => $this->actionButtons($fine),
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => FeeVoucherFine::count(),
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    private function validateFine(Request $request, ?FeeVoucherFine $fine = null): array
    {
        return $request->validate([
            'voucher_id' => [
                'required',
                'exists:fee_vouchers,id',
                Rule::when($fine !== null, Rule::in([$fine?->voucher_id])),
            ],
            'fine_rule_id' => 'nullable|exists:fee_fine_rules,id',
            'days_overdue' => 'required|integer|min:0',
            'fine_type' => 'required_without:fine_rule_id|string|in:fixed,percentage,daily_fixed,daily_percentage',
            'fine_value' => 'required_without:fine_rule_id|numeric|min:0',
            'calculated_amount' => 'nullable|numeric|min:0',
            'applied_on' => 'nullable|date',
            'is_waived' => 'boolean',
            'notes' => 'nullable|string|max:200',
        ]);
    }

    private function prepareFineData(array $validated, FeeVoucher $voucher, ?FeeVoucherFine $existingFine = null): array
    {
        $rule = !empty($validated['fine_rule_id'])
            ? FeeFineRule::active()->findOrFail($validated['fine_rule_id'])
            : null;

        $daysOverdue = (int) $validated['days_overdue'];
        $fineType = $rule?->fine_type ?? $validated['fine_type'];
        $fineValue = (float) ($rule?->fine_value ?? $validated['fine_value']);
        $maxFine = $rule?->max_fine ? (float) $rule->max_fine : null;
        $calculatedAmount = $this->calculateFineAmount($voucher, $fineType, $fineValue, $daysOverdue, $maxFine);
        $isWaived = (bool) ($validated['is_waived'] ?? false);

        return [
            'voucher_id' => $voucher->id,
            'fine_rule_id' => $rule?->id,
            'days_overdue' => $daysOverdue,
            'fine_type' => $fineType,
            'fine_value' => $fineValue,
            'calculated_amount' => $calculatedAmount,
            'applied_on' => $validated['applied_on'] ?? $existingFine?->applied_on?->format('Y-m-d') ?? now()->toDateString(),
            'applied_by' => $existingFine?->applied_by ?? auth()->id(),
            'is_waived' => $isWaived,
            'waived_by' => $isWaived ? auth()->id() : null,
            'notes' => $validated['notes'] ?? null,
        ];
    }

    private function applyFineAmountToVoucher(FeeVoucher $voucher, float $delta, FeeVoucherFine $fine): void
    {
        app(\App\Services\FeeVoucherBalanceService::class)->sync($voucher);

        if ($delta !== 0.0) {
            $balance = $this->studentBalance($voucher->student_enrollment_id);
            StudentLedger::create([
                'student_enrollment_id' => $voucher->student_enrollment_id,
                'transaction_type' => $delta > 0 ? 'debit' : 'credit',
                'amount' => abs($delta),
                'description' => ($delta > 0 ? 'Fine applied' : 'Fine reversed') . ': ' . $voucher->voucher_no,
                'reference_type' => 'voucher_fine',
                'reference_id' => $fine->id,
                'balance_after' => $delta > 0 ? $balance + abs($delta) : $balance - abs($delta),
                'created_by' => auth()->id(),
            ]);
        }
    }

    private function calculateFineAmount(FeeVoucher $voucher, string $fineType, float $fineValue, int $daysOverdue, ?float $maxFine = null): float
    {
        $baseAmount = max(0, (float) $voucher->remaining_amount);

        $amount = match ($fineType) {
            'percentage' => ($baseAmount * $fineValue) / 100,
            'daily_fixed' => $fineValue * max(1, $daysOverdue),
            'daily_percentage' => (($baseAmount * $fineValue) / 100) * max(1, $daysOverdue),
            default => $fineValue,
        };

        if ($maxFine !== null) {
            $amount = min($amount, $maxFine);
        }

        return round(max(0, $amount), 2);
    }

    private function fineQuery()
    {
        return FeeVoucherFine::with([
            'voucher.academicYear',
            'voucher.feeType',
            'voucher.studentEnrollment.student',
            'voucher.studentEnrollment.branch',
            'voucher.studentEnrollment.classSection.branchClass.class',
            'voucher.studentEnrollment.classSection.section',
            'fineRule',
            'appliedBy',
        ]);
    }

    private function applyFineSearch($query, ?string $search): void
    {
        if (!$search) {
            return;
        }

        $query->where(function ($q) use ($search) {
            $q->where('calculated_amount', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%")
                ->orWhereHas('voucher', fn ($voucherQuery) => $voucherQuery->where('voucher_no', 'like', "%{$search}%"))
                ->orWhereHas('voucher.studentEnrollment.student', fn ($studentQuery) => $studentQuery->where('student_name', 'like', "%{$search}%")
                    ->orWhere('roll_no', 'like', "%{$search}%")
                    ->orWhere('admission_no', 'like', "%{$search}%"));
        });
    }

    private function formatVoucher(FeeVoucher $voucher): array
    {
        $dueDate = $voucher->due_date instanceof Carbon ? $voucher->due_date : Carbon::parse($voucher->due_date);
        $daysOverdue = $dueDate->isPast() ? $dueDate->startOfDay()->diffInDays(now()->startOfDay()) : 0;

        return [
            'id' => $voucher->id,
            'voucher_no' => $voucher->voucher_no,
            'student_name' => $voucher->studentEnrollment?->student?->student_name ?? '-',
            'roll_no' => $voucher->studentEnrollment?->student?->roll_no ?? '-',
            'fee_type' => $voucher->feeType?->fee_name ?? '-',
            'net_amount' => $voucher->net_amount,
            'remaining_amount' => $voucher->remaining_amount,
            'fine_amount' => $voucher->fine_amount,
            'due_date' => $voucher->due_date?->format('Y-m-d'),
            'days_overdue' => $daysOverdue,
            'status' => $voucher->status,
        ];
    }

    private function actionButtons(FeeVoucherFine $fine): string
    {
        $fineJson = htmlspecialchars(json_encode($this->formatFineForShow($fine)), ENT_QUOTES, 'UTF-8');

        return '
            <div class="flex items-center justify-center gap-2">
                <button data-fine="' . $fineJson . '" onclick="showFine(JSON.parse(this.dataset.fine))" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">View</button>
                <button onclick=\'editFine(' . json_encode(['id' => $fine->id]) . ')\' class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">Edit</button>
                <button onclick="deleteFine(' . $fine->id . ')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">Delete</button>
            </div>
        ';
    }

    private function formatFineForShow(FeeVoucherFine $fine): array
    {
        $voucher = $fine->voucher;
        $enrollment = $voucher?->studentEnrollment;
        $student = $enrollment?->student;
        $rule = $fine->fineRule;

        return [
            'id' => $fine->id,
            'voucher_no' => $voucher?->voucher_no ?? '-',
            'student_name' => $student?->student_name ?? '-',
            'roll_no' => $enrollment?->roll_number ?? $student?->roll_no ?? '-',
            'admission_no' => $student?->admission_no ?? '-',
            'branch_name' => $enrollment?->branch?->branch_name ?? '-',
            'class_name' => $enrollment?->classSection?->branchClass?->class?->class_name ?? '-',
            'section_name' => $enrollment?->classSection?->section?->section_name ?? '-',
            'academic_year' => $voucher?->academicYear?->year_name ?? '-',
            'fee_type' => $voucher?->feeType?->fee_name ?? '-',
            'voucher_status' => $voucher?->status ?? '-',
            'original_amount' => (float) ($voucher?->original_amount ?? 0),
            'discount_amount' => (float) ($voucher?->discount_amount ?? 0),
            'voucher_fine_amount' => (float) ($voucher?->fine_amount ?? 0),
            'net_amount' => (float) ($voucher?->net_amount ?? 0),
            'paid_amount' => (float) ($voucher?->paid_amount ?? 0),
            'remaining_amount' => (float) ($voucher?->remaining_amount ?? 0),
            'due_date' => $voucher?->due_date?->format('Y-m-d'),
            'days_overdue' => $fine->days_overdue,
            'fine_type' => $this->fineTypeLabel($fine->fine_type),
            'fine_value' => $this->formatFineValue($fine->fine_type, (float) $fine->fine_value),
            'calculated_amount' => (float) $fine->calculated_amount,
            'applied_on' => $fine->applied_on?->format('Y-m-d'),
            'applied_by' => $fine->appliedBy?->name ?? 'System',
            'status' => $fine->is_waived ? 'Waived' : 'Applied',
            'rule_label' => $rule?->description ?: ($rule ? $this->fineTypeLabel($rule->fine_type) . ' ' . $this->formatFineValue($rule->fine_type, (float) $rule->fine_value) . ' after ' . $rule->days_after_due . ' days' : 'Manual Fine'),
            'notes' => $fine->notes,
        ];
    }

    private function formatFineValue(string $type, float $value): string
    {
        return str_contains($type, 'percentage')
            ? number_format($value, 2) . '%'
            : 'Rs ' . number_format($value, 2);
    }

    private function fineTypeLabel(string $type): string
    {
        return ucwords(str_replace('_', ' ', $type));
    }

    private function statusFromAmounts(float $paidAmount, float $remainingAmount): string
    {
        if ($remainingAmount <= 0) {
            return 'paid';
        }

        return $paidAmount > 0 ? 'partial' : 'pending';
    }

    private function studentBalance($enrollmentId): float
    {
        $debits = StudentLedger::where('student_enrollment_id', $enrollmentId)
            ->where('transaction_type', 'debit')
            ->sum('amount');
        $credits = StudentLedger::where('student_enrollment_id', $enrollmentId)
            ->where('transaction_type', 'credit')
            ->sum('amount');

        return (float) $debits - (float) $credits;
    }
}
