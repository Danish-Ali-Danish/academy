<?php

namespace App\Http\Controllers;

use App\Models\FeePayment;
use App\Models\FeeVoucher;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\FeePaymentService;
use App\Services\FeeVoucherBalanceService;
use Illuminate\Support\Facades\DB;

class FeePaymentController extends Controller
{
    protected $feePaymentService;

    public function __construct(FeePaymentService $feePaymentService)
    {
        $this->feePaymentService = $feePaymentService;
    }

    public function index(Request $request)
    {
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobilePayments($request);
        }

        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesPayments($request);
        }

        return Inertia::render('FeePayments/Index');
    }

    public function create()
    {
        return Inertia::render('FeePayments/Create', $this->getFormData());
    }

    public function edit(FeePayment $feePayment)
    {
        $feePayment->load(['voucher.feeType', 'studentEnrollment.student', 'receivedBy']);

        return Inertia::render('FeePayments/Edit', array_merge(
            $this->getFormData(),
            [
                'payment' => [
                    'id'                    => $feePayment->id,
                    'receipt_no'            => $feePayment->receipt_no,
                    'voucher_id'            => $feePayment->voucher_id,
                    'voucher_no'            => $feePayment->voucher?->voucher_no,
                    'student_enrollment_id' => $feePayment->student_enrollment_id,
                    'student_name'          => $feePayment->studentEnrollment?->student?->student_name,
                    'admission_no'          => $feePayment->studentEnrollment?->student?->admission_no,
                    'fee_type'              => $feePayment->voucher?->feeType?->fee_name,
                    'net_amount'            => $feePayment->voucher?->net_amount,
                    'voucher_status'        => $feePayment->voucher?->status,
                    'paid_amount'           => $feePayment->paid_amount,
                    'payment_date'          => $feePayment->payment_date?->format('Y-m-d'),
                    'payment_method'        => $feePayment->payment_method,
                    'bank_name'             => $feePayment->bank_name,
                    'transaction_ref'       => $feePayment->transaction_ref,
                    'is_advance'            => $feePayment->is_advance,
                    'notes'                 => $feePayment->notes,
                    'received_by'           => $feePayment->receivedBy?->name,
                    'created_at_display'    => $feePayment->created_at?->format('d M Y, h:i A'),
                ]
            ]
        ));
    }

    public function show(FeePayment $feePayment)
    {
        $feePayment->load([
            'voucher.feeType',
            'studentEnrollment.student',
            'studentEnrollment.classSection.branchClass.branch',
            'receivedBy',
            'refund',
        ]);

        if ($feePayment->voucher) {
            app(FeeVoucherBalanceService::class)->sync($feePayment->voucher);
        }

        return Inertia::render('FeePayments/Show', [
            'payment' => $feePayment
        ]);
    }

    private function getMobilePayments(Request $request)
    {
        $query = FeePayment::with(['voucher.feeType', 'studentEnrollment.student', 'receivedBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('receipt_no', 'like', "%{$search}%")
                  ->orWhere('transaction_ref', 'like', "%{$search}%")
                  ->orWhereHas('studentEnrollment.student', function ($sq) use ($search) {
                      $sq->where('student_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
        }

        $perPage = $request->get('per_page', 10);
        $payments = $query->latest('payment_date')->paginate($perPage);

        return response()->json($payments);
    }

    private function getDataTablesPayments(Request $request)
    {
        $query = FeePayment::with([
            'voucher.feeType',
            'voucher.payments' => function ($q) {
                $q->select('id', 'voucher_id', 'paid_amount', 'payment_date', 'created_at')
                    ->orderBy('payment_date')
                    ->orderBy('id');
            },
            'studentEnrollment.student',
            'receivedBy',
        ]);

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('receipt_no', 'like', "%{$search}%")
                  ->orWhere('transaction_ref', 'like', "%{$search}%")
                  ->orWhereHas('studentEnrollment.student', function ($sq) use ($search) {
                      $sq->where('student_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
        }

        $totalData = $query->count();

        $orderColumn = $request->input('order.0.column', 0);
        $orderDir    = $request->input('order.0.dir', 'desc');
        $columns     = ['id', 'receipt_no', 'student_enrollment_id', 'paid_amount', 'payment_date', 'payment_method'];

        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        $start    = $request->input('start', 0);
        $length   = $request->input('length', 10);
        $payments = $query->skip($start)->take($length)->get();

        $data = $payments->map(function ($payment, $index) use ($start) {
            $methodLabels = [
                'cash'          => 'Cash',
                'bank_transfer' => 'Bank Transfer',
                'cheque'        => 'Cheque',
                'online'        => 'Online',
                'jazzcash'      => 'JazzCash',
                'easypaisa'     => 'Easypaisa',
                'raast'         => 'Raast',
                'advance_adjusted' => 'Advance',
            ];
            $totalPaidHtml = '-';
            $remainingHtml = '-';
            if ($payment->voucher) {
                [$total, $remaining] = $this->calculateVoucherTotalsForPayment($payment);
                $totalPaidHtml = '<span class="font-semibold text-gray-900">Rs. ' . number_format($total, 0) . '</span>';
                $remainingHtml = '<span class="text-red-600 font-semibold">Rs. ' . number_format($remaining, 0) . '</span>';
            }

            return [
                'DT_RowIndex'    => $start + $index + 1,
                'id'             => $payment->id,
                'receipt_no'     => '<span class="font-mono text-xs font-semibold text-indigo-700">' . $payment->receipt_no . '</span>',
                'student_name'   => $payment->studentEnrollment?->student?->student_name ?? '-',
                'admission_no'   => $payment->studentEnrollment?->student?->admission_no ?? '-',
                'fee_type'       => $payment->voucher?->feeType?->fee_name ?? '-',
                'payment_date'   => $payment->payment_date?->format('d M, Y') ?? '-',
                'amount_paid'    => '<span class="font-semibold text-gray-900">Rs. ' . number_format((float)($payment->paid_amount ?? 0), 0) . '</span>',
                'total_paid'     => $totalPaidHtml,
                'remaining_amount'=> $remainingHtml,
                'payment_method' => $methodLabels[$payment->payment_method] ?? ucfirst(str_replace('_', ' ', $payment->payment_method)),
                'collected_by'   => $payment->receivedBy?->name ?? '-',
                'is_cancelled'   => $payment->is_advance
                    ? '<span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Advance</span>'
                    : '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Regular</span>',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <button onclick=\'viewPayment(' . json_encode(['id' => $payment->id]) . ')\' class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            View
                        </button>
                        <button onclick=\'editPayment(' . json_encode(['id' => $payment->id]) . ')\' class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit
                        </button>
                        <button onclick="deletePayment(' . $payment->id . ')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
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

    private function calculateVoucherTotalsForPayment(FeePayment $payment): array
    {
        if (!$payment->voucher) {
            return [0.0, 0.0];
        }

        $paidAmount = 0.0;
        $payments = $payment->voucher->payments ?? collect();

        foreach ($payments as $voucherPayment) {
            $paidAmount += (float) $voucherPayment->paid_amount;

            if ($voucherPayment->id === $payment->id) {
                break;
            }
        }

        $remainingAmount = max(0, (float) $payment->voucher->net_amount - $paidAmount);

        return [$paidAmount, $remainingAmount];
    }

    public function storeMultiple(Request $request)
    {
        $request->validate([
            'student_enrollment_id' => 'required|exists:student_enrollments,id',
            'vouchers'              => 'required|array|min:1',
            'vouchers.*.voucher_id' => 'required|exists:fee_vouchers,id',
            'vouchers.*.paid_amount'=> 'required|numeric|min:0.01',
            'payment_date'          => 'required|date',
            'payment_method'        => 'required|in:cash,bank_transfer,cheque,online,jazzcash,easypaisa,sadapay,raast',
            'bank_name'             => 'nullable|string|max:100',
            'transaction_ref'       => 'nullable|string|max:100',
            'notes'                 => 'nullable|string',
        ]);

        $receipts = [];
        $errors   = [];

        foreach ($request->vouchers as $item) {
            $voucher = FeeVoucher::with('payments')->find($item['voucher_id']);

            if (!$voucher || (int) $voucher->student_enrollment_id !== (int) $request->student_enrollment_id) {
                $errors[] = 'Selected voucher does not belong to this student.';
                continue;
            }

            $actualPaid = (float) $voucher->payments->sum('paid_amount');
            $actualRemaining = max(0, (float) $voucher->net_amount - $actualPaid);

            if ((float) $item['paid_amount'] > $actualRemaining) {
                $errors[] = 'Payment for voucher ' . $voucher->voucher_no . ' cannot exceed remaining Rs. ' . number_format($actualRemaining, 0) . '.';
                continue;
            }

            $result = $this->feePaymentService->processPayment([
                'voucher_id'            => $item['voucher_id'],
                'student_enrollment_id' => $request->student_enrollment_id,
                'paid_amount'           => $item['paid_amount'],
                'payment_date'          => $request->payment_date,
                'payment_method'        => $request->payment_method,
                'bank_name'             => $request->bank_name,
                'transaction_ref'       => $request->transaction_ref,
                'is_advance'            => false,
                'notes'                 => $request->notes,
            ]);

            if ($result['success']) {
                $receipts[] = $result['payment']->receipt_no;
            } else {
                $errors[] = $result['message'];
            }
        }

        if (!empty($errors) && empty($receipts)) {
            return back()->withInput()->with('error', 'Payment failed: ' . implode(', ', $errors));
        }

        $msg = count($receipts) . ' payment(s) recorded. Receipts: ' . implode(', ', $receipts);
        if (!empty($errors)) {
            $msg .= ' | Errors: ' . implode(', ', $errors);
        }

        return redirect()->route('fee-payments.index')->with('success', $msg);
    }

    public function store(Request $request)
    {
        $isAdvance = filter_var($request->input('is_advance', false), FILTER_VALIDATE_BOOLEAN);

        $validated = $request->validate([
            'voucher_id'            => $isAdvance ? 'nullable|integer' : 'required|exists:fee_vouchers,id',
            'student_enrollment_id' => 'required|exists:student_enrollments,id',
            'paid_amount'           => 'required|numeric|min:0.01',
            'payment_date'          => 'required|date',
            'payment_method'        => 'required|in:cash,bank_transfer,cheque,online,jazzcash,easypaisa,sadapay,raast,advance_adjusted',
            'bank_name'             => 'nullable|string|max:100',
            'transaction_ref'       => 'nullable|string|max:100',
            'is_advance'            => 'boolean',
            'notes'                 => 'nullable|string',
        ]);

        $validated['is_advance'] = $isAdvance;

        $result = $this->feePaymentService->processPayment($validated);

        if ($result['success']) {
            return redirect()
                ->route('fee-payments.index')
                ->with('success', $result['message']);
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', $result['message']);
    }

    public function update(Request $request, FeePayment $feePayment)
    {
        $validated = $request->validate([
            'voucher_id'            => 'required|exists:fee_vouchers,id',
            'student_enrollment_id' => 'required|exists:student_enrollments,id',
            'paid_amount'           => 'required|numeric|min:0.01',
            'payment_date'          => 'required|date',
            'payment_method'        => 'required|in:cash,bank_transfer,cheque,online,jazzcash,easypaisa,sadapay,raast,advance_adjusted',
            'bank_name'             => 'nullable|string|max:100',
            'transaction_ref'       => 'nullable|string|max:100',
            'is_advance'            => 'boolean',
            'notes'                 => 'nullable|string',
        ]);

        DB::transaction(function () use ($feePayment, $validated) {
            $oldVoucher = $feePayment->voucher_id
                ? FeeVoucher::whereKey($feePayment->voucher_id)->lockForUpdate()->first()
                : null;

            $validated['is_advance'] = (bool) ($validated['is_advance'] ?? false);
            $newVoucher = FeeVoucher::whereKey($validated['voucher_id'])->lockForUpdate()->firstOrFail();

            if (!$validated['is_advance']) {
                $validated['student_enrollment_id'] = $newVoucher->student_enrollment_id;
            }

            $feePayment->update($validated);

            if ($oldVoucher) {
                app(\App\Services\FeeVoucherBalanceService::class)->sync($oldVoucher);
            }

            if (!$validated['is_advance']) {
                app(\App\Services\FeeVoucherBalanceService::class)->sync($newVoucher);
            }
        });

        return redirect()->route('fee-payments.index')
            ->with('success', 'Payment updated successfully!');
    }

    public function destroy(FeePayment $feePayment)
    {
        $voucher = (!$feePayment->is_advance && $feePayment->voucher) ? $feePayment->voucher : null;

        $feePayment->delete();

        if ($voucher) {
            app(\App\Services\FeeVoucherBalanceService::class)->sync($voucher);
        }

        return redirect()->route('fee-payments.index')
            ->with('success', 'Payment deleted successfully!');
    }

    private function generateReceiptNumber()
    {
        $year = now()->format('Y');
        $lastPayment = FeePayment::where('receipt_no', 'like', "RCP-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPayment && preg_match('/RCP-\d{4}-(\d+)/', $lastPayment->receipt_no, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf("RCP-%s-%05d", $year, $nextNumber);
    }

    private function getFormData(): array
    {
        return [
            'enrollments' => \App\Models\StudentEnrollment::with('student:id,student_name,admission_no,roll_no')
                ->select('id', 'student_id')
                ->where('status', 'active')
                ->get(),
            'vouchers' => \App\Models\FeeVoucher::select('id', 'voucher_no', 'net_amount', 'status')
                ->orderBy('id', 'desc')
                ->get(),
        ];
    }

    public function searchStudents(Request $request)
    {
        $search = $request->get('q', '');
        $enrollments = \App\Models\StudentEnrollment::with([
            'student:id,student_name,admission_no,roll_no',
            'academicYear:id,year_name',
            'classSection.branchClass.class:id,class_name',
            'classSection.branchClass.branch:id,branch_name',
        ])
        ->where('status', 'active')
        ->whereHas('student', function ($q) use ($search) {
            $q->where('student_name', 'like', "%{$search}%")
              ->orWhere('admission_no', 'like', "%{$search}%")
              ->orWhere('roll_no', 'like', "%{$search}%");
        })
        ->limit(15)
        ->get()
        ->map(fn($e) => [
            'id'           => $e->id,
            'student_name' => $e->student?->student_name,
            'admission_no' => $e->student?->admission_no,
            'roll_no'      => $e->student?->roll_no,
            'class_name'   => $e->classSection?->branchClass?->class?->class_name ?? '-',
            'branch_name'  => $e->classSection?->branchClass?->branch?->branch_name ?? '-',
            'year_name'    => $e->academicYear?->year_name ?? '-',
        ]);

        return response()->json($enrollments);
    }

    public function pendingVouchers(int $enrollmentId)
    {
        $vouchers = \App\Models\FeeVoucher::with([
            'feeType:id,fee_name',
            'payments:id,voucher_id,paid_amount,created_at',
        ])
            ->where('student_enrollment_id', $enrollmentId)
            ->orderBy('due_date')
            ->get()
            ->map(function ($v) {
                $paidAmount = (float) ($v->payments?->sum('paid_amount') ?? 0);
                $netAmount = (float) $v->net_amount;
                $remainingAmount = max(0, $netAmount - $paidAmount);
                $status = $remainingAmount <= 0
                    ? 'paid'
                    : ($paidAmount > 0 ? 'partial' : 'pending');

                return [
                    'id'               => $v->id,
                    'voucher_no'       => $v->voucher_no,
                    'fee_type'         => $v->feeType?->fee_name ?? '-',
                    'generated_for'    => $v->generated_for,
                    'original_amount'  => (float) $v->original_amount,
                    'discount_amount'  => (float) $v->discount_amount,
                    'fine_amount'      => (float) $v->fine_amount,
                    'net_amount'       => $netAmount,
                    'paid_amount'      => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'due_date'         => $v->due_date?->format('Y-m-d'),
                    'due_date_display' => $v->due_date?->format('d M Y'),
                    'status'           => $status,
                    'is_overdue'       => $v->due_date && $v->due_date->isPast(),
                ];
            })
            ->filter(fn ($v) => $v['remaining_amount'] > 0)
            ->values();

        return response()->json($vouchers);
    }

    public function dropdown(Request $request)
    {
        $query = FeePayment::with(['voucher.feeType', 'studentEnrollment.student'])
            ->select('id', 'receipt_no', 'voucher_id', 'student_enrollment_id', 'paid_amount', 'payment_date');

        if ($request->filled('student_enrollment_id')) {
            $query->where('student_enrollment_id', $request->student_enrollment_id);
        }

        if ($request->filled('voucher_id')) {
            $query->where('voucher_id', $request->voucher_id);
        }

        $payments = $query->orderBy('payment_date', 'desc')->get();

        return response()->json($payments);
    }
}
