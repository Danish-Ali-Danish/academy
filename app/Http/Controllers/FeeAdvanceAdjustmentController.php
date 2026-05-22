<?php

namespace App\Http\Controllers;

use App\Models\FeeAdvanceAdjustment;
use App\Models\FeePayment;
use App\Models\FeeVoucher;
use App\Models\StudentAdvanceBalance;
use App\Models\StudentLedger;
use App\Services\FeeVoucherBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class FeeAdvanceAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileAdjustments($request);
        }

        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesAdjustments($request);
        }

        return Inertia::render('FeeAdvanceAdjustments/Index');
    }

    public function create()
    {
        return Inertia::render('FeeAdvanceAdjustments/Create', $this->getFormData());
    }

    public function edit(FeeAdvanceAdjustment $feeAdvanceAdjustment)
    {
        $feeAdvanceAdjustment->load(['studentEnrollment.student', 'fromPayment', 'toVoucher.feeType']);

        return Inertia::render('FeeAdvanceAdjustments/Edit', array_merge($this->getFormData($feeAdvanceAdjustment), [
            'adjustment' => [
                'id'                    => $feeAdvanceAdjustment->id,
                'student_enrollment_id' => $feeAdvanceAdjustment->student_enrollment_id,
                'from_payment_id'       => $feeAdvanceAdjustment->from_payment_id,
                'to_voucher_id'         => $feeAdvanceAdjustment->to_voucher_id,
                'adjusted_amount'       => (float) $feeAdvanceAdjustment->adjusted_amount,
                'adjusted_at'           => $feeAdvanceAdjustment->adjusted_at?->format('Y-m-d'),
                'notes'                 => $feeAdvanceAdjustment->notes,
            ],
        ]));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);

        DB::transaction(function () use ($validated) {
            $advancePayment = FeePayment::whereKey($validated['from_payment_id'])->lockForUpdate()->firstOrFail();
            $voucher = FeeVoucher::whereKey($validated['to_voucher_id'])->lockForUpdate()->firstOrFail();
            $this->guardAdjustment($advancePayment, $voucher, (float) $validated['adjusted_amount']);

            $adjustment = FeeAdvanceAdjustment::create([
                'student_enrollment_id' => $voucher->student_enrollment_id,
                'from_payment_id'       => $advancePayment->id,
                'to_voucher_id'         => $voucher->id,
                'adjusted_amount'       => $validated['adjusted_amount'],
                'adjusted_by'           => auth()->id() ?? 1,
                'adjusted_at'           => $validated['adjusted_at'] ?? now()->toDateString(),
                'notes'                 => $validated['notes'] ?? null,
            ]);

            $appliedPayment = $this->createAppliedPayment($adjustment, $voucher);
            $adjustment->update(['applied_payment_id' => $appliedPayment->id]);
            $this->writeAdvanceLedger($adjustment);
            $this->refreshAdvanceBalance($voucher->student_enrollment_id);
            app(FeeVoucherBalanceService::class)->sync($voucher);
        });

        return redirect()->route('fee-advance-adjustments.index')
            ->with('success', 'Advance adjusted to voucher successfully.');
    }

    public function update(Request $request, FeeAdvanceAdjustment $feeAdvanceAdjustment)
    {
        $validated = $this->validatedData($request);

        DB::transaction(function () use ($feeAdvanceAdjustment, $validated) {
            $feeAdvanceAdjustment->load(['appliedPayment', 'toVoucher']);
            $oldVoucher = $feeAdvanceAdjustment->toVoucher;
            $oldEnrollmentId = $feeAdvanceAdjustment->student_enrollment_id;

            $advancePayment = FeePayment::whereKey($validated['from_payment_id'])->lockForUpdate()->firstOrFail();
            $voucher = FeeVoucher::whereKey($validated['to_voucher_id'])->lockForUpdate()->firstOrFail();
            $this->guardAdjustment($advancePayment, $voucher, (float) $validated['adjusted_amount'], $feeAdvanceAdjustment->id, (float) $feeAdvanceAdjustment->adjusted_amount, (int) $feeAdvanceAdjustment->to_voucher_id);

            $feeAdvanceAdjustment->update([
                'student_enrollment_id' => $voucher->student_enrollment_id,
                'from_payment_id'       => $advancePayment->id,
                'to_voucher_id'         => $voucher->id,
                'adjusted_amount'       => $validated['adjusted_amount'],
                'adjusted_at'           => $validated['adjusted_at'] ?? now()->toDateString(),
                'notes'                 => $validated['notes'] ?? null,
            ]);

            $this->upsertAppliedPayment($feeAdvanceAdjustment->fresh(), $voucher);
            $this->rewriteAdvanceLedger($feeAdvanceAdjustment);
            $this->refreshAdvanceBalance($oldEnrollmentId);
            $this->refreshAdvanceBalance($voucher->student_enrollment_id);

            if ($oldVoucher) {
                app(FeeVoucherBalanceService::class)->sync($oldVoucher);
            }
            app(FeeVoucherBalanceService::class)->sync($voucher);
        });

        return redirect()->route('fee-advance-adjustments.index')
            ->with('success', 'Advance adjustment updated successfully.');
    }

    public function destroy(FeeAdvanceAdjustment $feeAdvanceAdjustment)
    {
        DB::transaction(function () use ($feeAdvanceAdjustment) {
            $feeAdvanceAdjustment->load(['appliedPayment', 'toVoucher']);
            $voucher = $feeAdvanceAdjustment->toVoucher;
            $enrollmentId = $feeAdvanceAdjustment->student_enrollment_id;

            if ($feeAdvanceAdjustment->appliedPayment) {
                $feeAdvanceAdjustment->appliedPayment->delete();
            }

            StudentLedger::where('reference_type', 'advance_adjustment')
                ->where('reference_id', $feeAdvanceAdjustment->id)
                ->delete();

            $feeAdvanceAdjustment->delete();
            $this->refreshAdvanceBalance($enrollmentId);

            if ($voucher) {
                app(FeeVoucherBalanceService::class)->sync($voucher);
            }
        });

        return back()->with('success', 'Advance adjustment deleted and voucher balance refreshed.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'from_payment_id' => 'required|exists:fee_payments,id',
            'to_voucher_id'   => 'required|exists:fee_vouchers,id',
            'adjusted_amount' => 'required|numeric|min:0.01',
            'adjusted_at'     => 'nullable|date',
            'notes'           => 'nullable|string',
        ]);
    }

    private function guardAdjustment(FeePayment $advancePayment, FeeVoucher $voucher, float $amount, ?int $ignoreAdjustmentId = null, float $currentAmount = 0.0, ?int $currentVoucherId = null): void
    {
        if (!$advancePayment->is_advance) {
            throw ValidationException::withMessages([
                'from_payment_id' => 'Selected payment is not marked as advance.',
            ]);
        }

        if ((int) $advancePayment->student_enrollment_id !== (int) $voucher->student_enrollment_id) {
            throw ValidationException::withMessages([
                'to_voucher_id' => 'Advance payment and target voucher must belong to the same student enrollment.',
            ]);
        }

        $available = $this->availableAdvanceAmount($advancePayment, $ignoreAdjustmentId);
        if ($amount > $available) {
            throw ValidationException::withMessages([
                'adjusted_amount' => 'Adjusted amount cannot exceed available advance Rs. ' . number_format($available, 2) . '.',
            ]);
        }

        $allowedVoucherRemaining = (float) $voucher->remaining_amount;
        if ($currentVoucherId && (int) $voucher->id === (int) $currentVoucherId) {
            $allowedVoucherRemaining += $currentAmount;
        }

        if ($amount > $allowedVoucherRemaining) {
            throw ValidationException::withMessages([
                'adjusted_amount' => 'Adjusted amount cannot exceed voucher remaining Rs. ' . number_format($allowedVoucherRemaining, 2) . '.',
            ]);
        }
    }

    private function createAppliedPayment(FeeAdvanceAdjustment $adjustment, FeeVoucher $voucher): FeePayment
    {
        return FeePayment::create([
            'receipt_no'            => $this->generateReceiptNo(),
            'voucher_id'            => $voucher->id,
            'student_enrollment_id' => $voucher->student_enrollment_id,
            'paid_amount'           => $adjustment->adjusted_amount,
            'payment_date'          => $adjustment->adjusted_at,
            'payment_method'        => 'advance_adjusted',
            'received_by'           => auth()->id() ?? 1,
            'is_advance'            => false,
            'notes'                 => 'Advance adjustment #' . $adjustment->id . ' from receipt ' . ($adjustment->fromPayment?->receipt_no ?? $adjustment->from_payment_id),
        ]);
    }

    private function upsertAppliedPayment(FeeAdvanceAdjustment $adjustment, FeeVoucher $voucher): void
    {
        $payment = $adjustment->appliedPayment ?: null;

        if (!$payment) {
            $payment = $this->createAppliedPayment($adjustment, $voucher);
            $adjustment->update(['applied_payment_id' => $payment->id]);
            return;
        }

        $payment->update([
            'voucher_id'            => $voucher->id,
            'student_enrollment_id' => $voucher->student_enrollment_id,
            'paid_amount'           => $adjustment->adjusted_amount,
            'payment_date'          => $adjustment->adjusted_at,
            'payment_method'        => 'advance_adjusted',
            'is_advance'            => false,
            'notes'                 => 'Advance adjustment #' . $adjustment->id . ' from receipt ' . ($adjustment->fromPayment?->receipt_no ?? $adjustment->from_payment_id),
        ]);
    }

    private function writeAdvanceLedger(FeeAdvanceAdjustment $adjustment): void
    {
        StudentLedger::create([
            'student_enrollment_id' => $adjustment->student_enrollment_id,
            'transaction_type'      => 'debit',
            'amount'                => $adjustment->adjusted_amount,
            'description'           => 'Advance adjusted to voucher ' . ($adjustment->toVoucher?->voucher_no ?? $adjustment->to_voucher_id),
            'reference_type'        => 'advance_adjustment',
            'reference_id'          => $adjustment->id,
            'balance_after'         => $this->calculatedAdvanceBalance($adjustment->student_enrollment_id),
            'created_by'            => auth()->id() ?? 1,
        ]);
    }

    private function rewriteAdvanceLedger(FeeAdvanceAdjustment $adjustment): void
    {
        StudentLedger::where('reference_type', 'advance_adjustment')
            ->where('reference_id', $adjustment->id)
            ->delete();

        $this->writeAdvanceLedger($adjustment->fresh(['toVoucher']));
    }

    private function refreshAdvanceBalance(int $studentEnrollmentId): void
    {
        $balance = $this->calculatedAdvanceBalance($studentEnrollmentId);

        StudentAdvanceBalance::updateOrCreate(
            ['student_enrollment_id' => $studentEnrollmentId],
            [
                'balance' => $balance,
                'last_transaction_id' => null,
                'last_updated' => now(),
            ]
        );
    }

    private function calculatedAdvanceBalance(int $studentEnrollmentId): float
    {
        $advancePaid = (float) FeePayment::where('student_enrollment_id', $studentEnrollmentId)
            ->where('is_advance', true)
            ->sum('paid_amount');

        $adjusted = (float) FeeAdvanceAdjustment::where('student_enrollment_id', $studentEnrollmentId)
            ->sum('adjusted_amount');

        return max(0, round($advancePaid - $adjusted, 2));
    }

    private function availableAdvanceAmount(FeePayment $payment, ?int $ignoreAdjustmentId = null): float
    {
        $query = FeeAdvanceAdjustment::where('from_payment_id', $payment->id);
        if ($ignoreAdjustmentId) {
            $query->where('id', '!=', $ignoreAdjustmentId);
        }

        return max(0, round((float) $payment->paid_amount - (float) $query->sum('adjusted_amount'), 2));
    }

    private function getFormData(?FeeAdvanceAdjustment $current = null): array
    {
        $advancePayments = FeePayment::with(['studentEnrollment.student', 'advanceAdjustments'])
            ->where('is_advance', true)
            ->orderBy('payment_date', 'desc')
            ->get()
            ->map(function (FeePayment $payment) use ($current) {
                $available = $this->availableAdvanceAmount($payment, $current?->id);
                $student = $payment->studentEnrollment?->student;

                return [
                    'id' => $payment->id,
                    'label' => $payment->receipt_no . ' - ' . ($student?->student_name ?? 'Unknown student'),
                    'subtitle' => 'Advance paid on ' . ($payment->payment_date?->format('d M Y') ?? '-') . ' - Admission ' . ($student?->admission_no ?? '-'),
                    'amount_label' => 'Available Rs ' . number_format($available, 2),
                    'meta' => 'Original Rs ' . number_format((float) $payment->paid_amount, 2),
                    'student_enrollment_id' => $payment->student_enrollment_id,
                    'available_amount' => $available,
                    'search' => implode(' ', [$payment->receipt_no, $student?->student_name, $student?->admission_no, $student?->roll_no]),
                ];
            })
            ->filter(fn ($payment) => $payment['available_amount'] > 0 || (int) $payment['id'] === (int) $current?->from_payment_id)
            ->values();

        $vouchers = FeeVoucher::with(['studentEnrollment.student', 'feeType'])
            ->whereIn('status', ['pending', 'partial'])
            ->orWhere('id', $current?->to_voucher_id ?? 0)
            ->orderBy('due_date')
            ->get()
            ->map(function (FeeVoucher $voucher) {
                $student = $voucher->studentEnrollment?->student;

                return [
                    'id' => $voucher->id,
                    'label' => $voucher->voucher_no . ' - ' . ($student?->student_name ?? 'Unknown student'),
                    'subtitle' => ($voucher->feeType?->fee_name ?? '-') . ' - ' . ($voucher->generated_for ?? ($voucher->month . '/' . $voucher->year)),
                    'amount_label' => 'Remaining Rs ' . number_format((float) $voucher->remaining_amount, 2),
                    'meta' => ucfirst($voucher->status),
                    'student_enrollment_id' => $voucher->student_enrollment_id,
                    'remaining_amount' => (float) $voucher->remaining_amount,
                    'search' => implode(' ', [$voucher->voucher_no, $student?->student_name, $student?->admission_no, $student?->roll_no, $voucher->feeType?->fee_name]),
                ];
            })
            ->values();

        return [
            'advancePayments' => $advancePayments,
            'vouchers' => $vouchers,
        ];
    }

    private function getMobileAdjustments(Request $request)
    {
        $query = $this->baseQuery($request);
        return response()->json($query->latest()->paginate($request->get('per_page', 10)));
    }

    private function getDataTablesAdjustments(Request $request)
    {
        $query = $this->baseQuery($request);
        $recordsFiltered = (clone $query)->count();

        $columns = ['id', 'student_enrollment_id', 'from_payment_id', 'to_voucher_id', 'adjusted_amount', 'adjusted_at'];
        $orderColumn = (int) $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $adjustments = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => FeeAdvanceAdjustment::count(),
            'recordsFiltered' => $recordsFiltered,
            'data' => $adjustments->map(fn ($adj, $index) => $this->formatRow($adj, $start + $index + 1)),
        ]);
    }

    private function baseQuery(Request $request)
    {
        $query = FeeAdvanceAdjustment::with([
            'studentEnrollment.student',
            'fromPayment',
            'toVoucher.feeType',
            'appliedPayment',
            'adjustedBy',
        ]);

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('adjusted_amount', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('fromPayment', fn ($sq) => $sq->where('receipt_no', 'like', "%{$search}%"))
                    ->orWhereHas('toVoucher', fn ($sq) => $sq->where('voucher_no', 'like', "%{$search}%"))
                    ->orWhereHas('studentEnrollment.student', function ($sq) use ($search) {
                        $sq->where('student_name', 'like', "%{$search}%")
                            ->orWhere('admission_no', 'like', "%{$search}%")
                            ->orWhere('roll_no', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    private function formatRow(FeeAdvanceAdjustment $adj, int $index): array
    {
        $student = $adj->studentEnrollment?->student;
        $payload = [
            'id' => $adj->id,
            'student_name' => $student?->student_name ?? '-',
            'admission_no' => $student?->admission_no ?? '-',
            'from_payment' => $adj->fromPayment?->receipt_no ?? '-',
            'to_voucher' => $adj->toVoucher?->voucher_no ?? '-',
            'fee_type' => $adj->toVoucher?->feeType?->fee_name ?? '-',
            'adjusted_amount' => number_format((float) $adj->adjusted_amount, 2),
            'adjusted_at' => $adj->adjusted_at?->format('d M, Y') ?? '-',
            'adjusted_by' => $adj->adjustedBy?->name ?? '-',
            'notes' => $adj->notes ?? '-',
            'applied_receipt' => $adj->appliedPayment?->receipt_no ?? '-',
        ];

        return array_merge($payload, [
            'DT_RowIndex' => $index,
            'student' => '<div class="text-left"><div class="font-semibold text-gray-900">' . e($payload['student_name']) . '</div><div class="text-xs text-gray-500">' . e($payload['admission_no']) . '</div></div>',
            'from_payment_html' => '<span class="font-mono text-xs text-indigo-700">' . e($payload['from_payment']) . '</span>',
            'to_voucher_html' => '<div><div class="font-semibold text-gray-900">' . e($payload['to_voucher']) . '</div><div class="text-xs text-gray-500">' . e($payload['fee_type']) . '</div></div>',
            'amount_html' => '<span class="font-semibold text-green-700">Rs ' . e($payload['adjusted_amount']) . '</span>',
            'action' => '
                <div class="flex items-center justify-center gap-2">
                    <button onclick=\'viewAdjustment(' . json_encode($payload) . ')\' class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-600 bg-green-50 rounded-lg hover:bg-green-100">View</button>
                    <button onclick=\'editAdjustment(' . json_encode(['id' => $adj->id]) . ')\' class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100">Edit</button>
                    <button onclick="deleteAdjustment(' . $adj->id . ')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100">Delete</button>
                </div>',
        ]);
    }

    private function generateReceiptNo(): string
    {
        $year = now()->format('Y');
        $lastPayment = FeePayment::withTrashed()
            ->where('receipt_no', 'like', "RCP-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastPayment && preg_match('/RCP-\d{4}-(\d+)/', $lastPayment->receipt_no, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }

        return sprintf('RCP-%s-%05d', $year, $nextNumber);
    }
}
