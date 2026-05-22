<?php

namespace App\Http\Controllers;

use App\Models\FeeRefund;
use App\Models\FeePayment;
use App\Services\FeeVoucherBalanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class FeeRefundController extends Controller
{
    public function __construct(private FeeVoucherBalanceService $balanceService)
    {
    }

    public function index(Request $request)
    {
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileRefunds($request);
        }

        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesRefunds($request);
        }

        return Inertia::render('FeeRefunds/Index');
    }

    public function create()
    {
        return Inertia::render('FeeRefunds/Create', [
            'payments' => $this->paymentOptions(true),
        ]);
    }

    public function edit(FeeRefund $feeRefund)
    {
        $feeRefund->load(['studentEnrollment.student', 'payment.voucher.feeType', 'refundedBy']);

        return Inertia::render('FeeRefunds/Edit', [
            'refund' => [
                'id'                    => $feeRefund->id,
                'student_enrollment_id' => $feeRefund->student_enrollment_id,
                'payment_id'            => $feeRefund->payment_id,
                'refund_amount'         => $feeRefund->refund_amount,
                'refund_date'           => $feeRefund->refund_date?->format('Y-m-d'),
                'reason'                => $feeRefund->reason,
                'refund_method'         => $feeRefund->refund_method,
                'bank_details'          => $feeRefund->bank_details,
                'status'                => $feeRefund->status,
                'notes'                 => $feeRefund->notes,
            ],
            'payments' => $this->paymentOptions(null, $feeRefund->payment_id),
        ]);
    }

    private function paymentOptions(?bool $refundableOnly = false, ?int $includePaymentId = null)
    {
        return FeePayment::with(['studentEnrollment.student', 'voucher.feeType', 'refund'])
            ->orderByDesc('id')
            ->limit(300)
            ->get()
            ->map(function ($payment) use ($includePaymentId) {
                $student = $payment->studentEnrollment?->student;
                $studentName = $student?->student_name ?? '-';
                $admission = $student?->admission_no ?? $payment->studentEnrollment?->roll_no ?? '-';
                $feeType = $payment->voucher?->feeType?->fee_name ?? 'Advance/General Payment';
                $refunded = $this->refundedAmount($payment, $includePaymentId === $payment->id ? null : 0);
                $refundable = max(0, round((float) $payment->paid_amount - $refunded, 2));

                return [
                    'id' => $payment->id,
                    'student_enrollment_id' => $payment->student_enrollment_id,
                    'voucher_id' => $payment->voucher_id,
                    'voucher_no' => $payment->voucher?->voucher_no ?? '-',
                    'label' => "{$payment->receipt_no} - {$studentName}",
                    'subtitle' => "{$feeType} | {$payment->voucher?->voucher_no} | Adm/Roll: {$admission}",
                    'amount_label' => 'Refundable Rs ' . number_format($refundable, 2),
                    'meta' => ucfirst(str_replace('_', ' ', $payment->payment_method)),
                    'paid_amount' => (float) $payment->paid_amount,
                    'refunded_amount' => $refunded,
                    'refundable_amount' => $refundable,
                    'payment_date' => optional($payment->payment_date)->format('Y-m-d'),
                    'payment_method' => $payment->payment_method,
                    'fee_type' => $feeType,
                    'search' => "{$payment->receipt_no} {$payment->voucher?->voucher_no} {$studentName} {$admission} {$feeType}",
                ];
            })
            ->filter(fn ($payment) => !$refundableOnly || $payment['refundable_amount'] > 0 || $payment['id'] === $includePaymentId)
            ->values();
    }

    private function getMobileRefunds(Request $request)
    {
        $query = FeeRefund::with(['studentEnrollment.student', 'payment.voucher.feeType', 'refundedBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhere('refund_amount', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhereHas('payment', fn($pq) => $pq->where('receipt_no', 'like', "%{$search}%"))
                  ->orWhereHas('studentEnrollment.student', fn($sq) => $sq
                      ->where('student_name', 'like', "%{$search}%")
                      ->orWhere('admission_no', 'like', "%{$search}%")
                      ->orWhere('roll_no', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->get('per_page', 10);
        $refunds = $query->latest()->paginate($perPage);

        return response()->json($refunds);
    }

    private function getDataTablesRefunds(Request $request)
    {
        $query = FeeRefund::with(['studentEnrollment.student', 'payment.voucher.feeType', 'refundedBy']);

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhere('refund_amount', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhereHas('payment', fn($pq) => $pq->where('receipt_no', 'like', "%{$search}%"))
                  ->orWhereHas('studentEnrollment.student', fn($sq) => $sq
                      ->where('student_name', 'like', "%{$search}%")
                      ->orWhere('admission_no', 'like', "%{$search}%")
                      ->orWhere('roll_no', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $totalData = $query->count();

        $orderColumn = $request->input('order.0.column', 0);
        $orderDir    = $request->input('order.0.dir', 'desc');
        $columns     = ['id', 'student_enrollment_id', 'refund_amount', 'refund_date', 'status'];

        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        $start   = $request->input('start', 0);
        $length  = $request->input('length', 10);
        $refunds = $query->skip($start)->take($length)->get();

        $data = $refunds->map(function ($refund, $index) use ($start) {
            $status = strtolower((string) $refund->status);
            $statusClass = match($status) {
                'approved'  => 'bg-green-100 text-green-800',
                'pending'   => 'bg-yellow-100 text-yellow-800',
                'rejected'  => 'bg-red-100 text-red-800',
                default     => 'bg-gray-100 text-gray-800',
            };
            $payload = [
                'id' => $refund->id,
                'student' => $refund->studentEnrollment?->student?->student_name ?? '-',
                'admission' => $refund->studentEnrollment?->student?->admission_no ?? $refund->studentEnrollment?->roll_no ?? '-',
                'receipt_no' => $refund->payment?->receipt_no ?? ('#' . $refund->payment_id),
                'voucher_no' => $refund->payment?->voucher?->voucher_no ?? '-',
                'fee_type' => $refund->payment?->voucher?->feeType?->fee_name ?? '-',
                'paid_amount' => number_format((float) $refund->payment?->paid_amount, 2),
                'refund_amount' => number_format((float) $refund->refund_amount, 2),
                'refund_date' => $refund->refund_date?->format('d M, Y') ?? '-',
                'method' => ucfirst(str_replace('_', ' ', $refund->refund_method ?? '-')),
                'status' => ucfirst($status ?: '-'),
                'reason' => $refund->reason ?: '-',
                'notes' => $refund->notes ?: '-',
                'refunded_by' => $refund->refundedBy?->name ?? '-',
            ];

            return [
                'DT_RowIndex'    => $start + $index + 1,
                'id'             => $refund->id,
                'student_name'   => $refund->studentEnrollment?->student?->student_name ?? '-',
                'payment_id'     => $refund->payment?->receipt_no ?? ('#' . $refund->payment_id),
                'refund_amount'  => 'Rs ' . number_format((float) $refund->refund_amount, 2),
                'refund_date'    => $refund->refund_date?->format('d M, Y') ?? '-',
                'voucher_no'     => $refund->payment?->voucher?->voucher_no ?? '-',
                'refund_method'  => ucfirst(str_replace('_', ' ', $refund->refund_method ?? '-')),
                'status'         => '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $statusClass . '">' . ucfirst($status) . '</span>',
                'refunded_by'    => $refund->refundedBy?->name ?? '-',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <button onclick=\'viewRefund(' . json_encode($payload) . ')\' class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-600 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                            View
                        </button>
                        <button onclick=\'editRefund(' . json_encode(['id' => $refund->id]) . ')\' class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit
                        </button>
                        <button onclick="deleteRefund(' . $refund->id . ')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete
                        </button>
                    </div>
                '
            ];
        });

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $totalData,
            'recordsFiltered' => $totalData,
            'data'            => $data
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_enrollment_id' => 'required|exists:student_enrollments,id',
            'payment_id'            => 'required|exists:fee_payments,id',
            'refund_amount'         => 'required|numeric|min:0.01',
            'refund_date'           => 'required|date',
            'reason'                => 'required|string',
            'refund_method'         => 'nullable|string|max:100',
            'bank_details'          => 'nullable|string',
            'status'                => 'nullable|string|in:pending,approved,rejected',
            'notes'                 => 'nullable|string',
        ]);

        $payment = FeePayment::with('voucher')->findOrFail($validated['payment_id']);
        $validated['student_enrollment_id'] = $payment->student_enrollment_id;
        $validated['status'] = strtolower($validated['status'] ?? 'pending');
        $this->ensureRefundIsAllowed($payment, (float) $validated['refund_amount']);
        $validated['refunded_by'] = auth()->id();

        if (!auth()->user()->hasAnyRole(['Admin', 'Branch Manager', 'Fee Manager'])) {
            \App\Models\FeeApprovalRequest::create([
                'request_type'          => 'fee_refund',
                'student_enrollment_id' => $validated['student_enrollment_id'],
                'voucher_id'            => $payment->voucher_id,
                'action_reference_type' => \App\Models\FeePayment::class,
                'action_reference_id'   => $payment->id,
                'requested_amount'      => $validated['refund_amount'],
                'current_amount'        => (float) $payment->paid_amount,
                'reason'                => $validated['reason'],
                'supporting_notes'      => $validated['notes'],
                'urgency'               => 'normal',
                'status'                => 'pending',
                'requested_by'          => auth()->id() ?? 1,
                'requested_at'          => now(),
            ]);

            return redirect()->route('fee-refunds.index')
                ->with('success', 'Your fee refund request has been submitted for approval.');
        }

        FeeRefund::create($validated);
        $this->syncPaymentVoucher($payment);

        return redirect()->route('fee-refunds.index')
            ->with('success', 'Fee refund created successfully!');
    }

    public function update(Request $request, FeeRefund $feeRefund)
    {
        $validated = $request->validate([
            'student_enrollment_id' => 'required|exists:student_enrollments,id',
            'payment_id'            => 'required|exists:fee_payments,id',
            'refund_amount'         => 'required|numeric|min:0.01',
            'refund_date'           => 'required|date',
            'reason'                => 'required|string',
            'refund_method'         => 'nullable|string|max:100',
            'bank_details'          => 'nullable|string',
            'status'                => 'nullable|string|in:pending,approved,rejected',
            'notes'                 => 'nullable|string',
        ]);

        $payment = FeePayment::with('voucher')->findOrFail($validated['payment_id']);
        $oldPayment = $feeRefund->payment()->with('voucher')->first();
        $validated['student_enrollment_id'] = $payment->student_enrollment_id;
        $validated['status'] = strtolower($validated['status'] ?? 'pending');
        $this->ensureRefundIsAllowed($payment, (float) $validated['refund_amount'], $feeRefund->id);

        $feeRefund->update($validated);
        $this->syncPaymentVoucher($payment);
        if ($oldPayment && $oldPayment->id !== $payment->id) {
            $this->syncPaymentVoucher($oldPayment);
        }

        return redirect()->route('fee-refunds.index')
            ->with('success', 'Fee refund updated successfully!');
    }

    public function destroy(FeeRefund $feeRefund)
    {
        if (!auth()->user()->hasAnyRole(['Admin', 'Branch Manager', 'Fee Manager'])) {
            return back()->with('error', 'You do not have permission to delete refunds. Please contact an Administrator.');
        }

        $payment = $feeRefund->payment()->with('voucher')->first();
        $feeRefund->delete();
        if ($payment) {
            $this->syncPaymentVoucher($payment);
        }

        return back()->with('success', 'Fee refund deleted successfully!');
    }

    private function refundedAmount(FeePayment $payment, ?int $ignoreRefundId = null): float
    {
        return (float) FeeRefund::query()
            ->where('payment_id', $payment->id)
            ->when($ignoreRefundId, fn ($query) => $query->where('id', '!=', $ignoreRefundId))
            ->whereRaw('LOWER(status) != ?', ['rejected'])
            ->sum('refund_amount');
    }

    private function ensureRefundIsAllowed(FeePayment $payment, float $amount, ?int $ignoreRefundId = null): void
    {
        $available = max(0, round((float) $payment->paid_amount - $this->refundedAmount($payment, $ignoreRefundId), 2));
        if ($amount > $available) {
            throw ValidationException::withMessages([
                'refund_amount' => 'Refund amount cannot be greater than refundable amount Rs ' . number_format($available, 2) . '.',
            ]);
        }
    }

    private function syncPaymentVoucher(FeePayment $payment): void
    {
        if ($payment->voucher) {
            $this->balanceService->sync($payment->voucher);
        }
    }
}
