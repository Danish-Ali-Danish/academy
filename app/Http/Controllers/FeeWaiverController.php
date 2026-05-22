<?php

namespace App\Http\Controllers;

use App\Models\FeeWaiver;
use App\Models\FeeVoucher;
use App\Services\FeeVoucherBalanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeeWaiverController extends Controller
{
    public function __construct(private FeeVoucherBalanceService $voucherBalanceService)
    {
    }

    public function index(Request $request)
    {
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileWaivers($request);
        }

        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesWaivers($request);
        }

        return Inertia::render('FeeWaivers/Index');
    }

    public function create()
    {
        return Inertia::render('FeeWaivers/Create', [
            'vouchers' => $this->voucherOptions(),
        ]);
    }

    public function edit(FeeWaiver $feeWaiver)
    {
        $feeWaiver->load(['voucher', 'studentEnrollment.student', 'approvedBy']);

        return Inertia::render('FeeWaivers/Edit', [
            'waiver' => [
                'id'                    => $feeWaiver->id,
                'voucher_id'            => $feeWaiver->voucher_id,
                'student_enrollment_id' => $feeWaiver->student_enrollment_id,
                'waived_amount'         => $feeWaiver->waived_amount,
                'waiver_reason'         => $feeWaiver->waiver_reason,
                'approved_on'           => $feeWaiver->approved_on?->format('Y-m-d'),
                'status'                => $feeWaiver->status,
                'notes'                 => $feeWaiver->notes,
            ],
            'vouchers' => $this->voucherOptions(),
        ]);
    }

    private function voucherOptions()
    {
        return FeeVoucher::with(['studentEnrollment.student', 'feeType', 'academicYear'])
            ->whereIn('status', ['pending', 'partial'])
            ->orderByDesc('id')
            ->limit(300)
            ->get()
            ->map(function ($voucher) {
                $student = $voucher->studentEnrollment?->student;
                $studentName = $student?->student_name ?? '-';
                $admission = $student?->admission_no ?? $voucher->studentEnrollment?->roll_no ?? '-';
                $feeType = $voucher->feeType?->fee_name ?? '-';

                return [
                    'id' => $voucher->id,
                    'student_enrollment_id' => $voucher->student_enrollment_id,
                    'label' => "{$voucher->voucher_no} - {$studentName}",
                    'subtitle' => "{$feeType} | Adm/Roll: {$admission}",
                    'amount_label' => 'Rs ' . number_format((float) $voucher->remaining_amount, 2),
                    'meta' => ucfirst($voucher->status),
                    'remaining_amount' => (float) $voucher->remaining_amount,
                    'net_amount' => (float) $voucher->net_amount,
                    'paid_amount' => (float) $voucher->paid_amount,
                    'search' => "{$voucher->voucher_no} {$studentName} {$admission} {$feeType}",
                ];
            })
            ->values();
    }

    private function getMobileWaivers(Request $request)
    {
        $query = FeeWaiver::with(['voucher', 'studentEnrollment.student', 'approvedBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('waiver_reason', 'like', "%{$search}%")
                  ->orWhere('waived_amount', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhereHas('voucher', fn($vq) => $vq->where('voucher_no', 'like', "%{$search}%"))
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
        $waivers = $query->latest()->paginate($perPage);

        return response()->json($waivers);
    }

    private function getDataTablesWaivers(Request $request)
    {
        $query = FeeWaiver::with(['voucher', 'studentEnrollment.student', 'approvedBy']);

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('waiver_reason', 'like', "%{$search}%")
                  ->orWhere('waived_amount', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhereHas('voucher', fn($vq) => $vq->where('voucher_no', 'like', "%{$search}%"))
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
        $columns     = ['id', 'voucher_id', 'student_enrollment_id', 'waived_amount', 'status'];

        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        $start   = $request->input('start', 0);
        $length  = $request->input('length', 10);
        $waivers = $query->skip($start)->take($length)->get();

        $data = $waivers->map(function ($waiver, $index) use ($start) {
            $statusClass = match($waiver->status) {
                'approved' => 'bg-green-100 text-green-800',
                'pending'  => 'bg-yellow-100 text-yellow-800',
                'reversed' => 'bg-red-100 text-red-800',
                default    => 'bg-gray-100 text-gray-800',
            };

            return [
                'DT_RowIndex'    => $start + $index + 1,
                'id'             => $waiver->id,
                'voucher_no'     => $waiver->voucher?->voucher_no ?? '-',
                'student_name'   => $waiver->studentEnrollment?->student?->student_name ?? '-',
                'waived_amount'  => 'Rs ' . number_format((float) $waiver->waived_amount, 2),
                'waiver_reason'  => \Illuminate\Support\Str::limit($waiver->waiver_reason, 40),
                'approved_by'    => $waiver->approvedBy?->name ?? '-',
                'approved_on'    => $waiver->approved_on?->format('d M, Y') ?? '-',
                'status'         => '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $statusClass . '">' . ucfirst($waiver->status) . '</span>',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <button onclick=\'editWaiver(' . json_encode(['id' => $waiver->id]) . ')\' class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit
                        </button>
                        <button onclick="deleteWaiver(' . $waiver->id . ')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
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
            'voucher_id'            => 'required|exists:fee_vouchers,id',
            'student_enrollment_id' => 'required|exists:student_enrollments,id',
            'waived_amount'         => 'required|numeric|min:0.01',
            'waiver_reason'         => 'required|string',
            'approved_on'           => 'nullable|date',
            'status'                => 'nullable|string|in:approved,reversed',
            'notes'                 => 'nullable|string',
        ]);

        $validated['approved_by'] = auth()->id();
        $validated['approved_on'] = $validated['approved_on'] ?? now();

        if (!auth()->user()->hasAnyRole(['Admin', 'Branch Manager', 'Fee Manager'])) {
            $voucher = FeeVoucher::findOrFail($validated['voucher_id']);
            \App\Models\FeeApprovalRequest::create([
                'request_type'          => 'fee_waiver',
                'student_enrollment_id' => $validated['student_enrollment_id'],
                'voucher_id'            => $validated['voucher_id'],
                'requested_amount'      => $validated['waived_amount'],
                'current_amount'        => (float) $voucher->remaining_amount,
                'reason'                => $validated['waiver_reason'],
                'supporting_notes'      => $validated['notes'],
                'urgency'               => 'normal',
                'status'                => 'pending',
                'requested_by'          => auth()->id() ?? 1,
                'requested_at'          => now(),
            ]);

            return redirect()->route('fee-waivers.index')
                ->with('success', 'Your fee waiver request has been submitted for approval.');
        }

        $waiver = FeeWaiver::create($validated);
        $this->voucherBalanceService->sync($waiver->voucher);

        return redirect()->route('fee-waivers.index')
            ->with('success', 'Fee waiver created successfully!');
    }

    public function update(Request $request, FeeWaiver $feeWaiver)
    {
        $validated = $request->validate([
            'voucher_id'            => 'required|exists:fee_vouchers,id',
            'student_enrollment_id' => 'required|exists:student_enrollments,id',
            'waived_amount'         => 'required|numeric|min:0.01',
            'waiver_reason'         => 'required|string',
            'approved_on'           => 'nullable|date',
            'status'                => 'nullable|string|in:approved,reversed',
            'reversal_reason'       => 'nullable|string',
            'notes'                 => 'nullable|string',
        ]);

        $oldVoucher = $feeWaiver->voucher;
        $feeWaiver->update($validated);
        if ($oldVoucher) {
            $this->voucherBalanceService->sync($oldVoucher);
        }
        if ((int) $oldVoucher?->id !== (int) $feeWaiver->voucher_id) {
            $this->voucherBalanceService->sync($feeWaiver->voucher);
        }

        return redirect()->route('fee-waivers.index')
            ->with('success', 'Fee waiver updated successfully!');
    }

    public function destroy(FeeWaiver $feeWaiver)
    {
        if (!auth()->user()->hasAnyRole(['Admin', 'Branch Manager', 'Fee Manager'])) {
            return back()->with('error', 'You do not have permission to delete waivers. Please contact an Administrator.');
        }

        $voucher = $feeWaiver->voucher;
        $feeWaiver->delete();
        if ($voucher) {
            $this->voucherBalanceService->sync($voucher);
        }

        return back()->with('success', 'Fee waiver deleted successfully!');
    }
}
