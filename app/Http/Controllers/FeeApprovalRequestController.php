<?php

namespace App\Http\Controllers;

use App\Models\FeeApprovalRequest;
use App\Models\StudentEnrollment;
use App\Models\FeeVoucher;
use App\Models\Student;
use App\Models\User;
use App\Models\FeeWaiver;
use App\Models\FeeVoucherFine;
use App\Services\FeeVoucherBalanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class FeeApprovalRequestController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileRequests($request);
        }

        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesRequests($request);
        }

        return Inertia::render('FeeApprovalRequests/Index');
    }

    public function create()
    {
        $enrollments = StudentEnrollment::with(['student:id,student_name,admission_no,roll_no', 'academicYear:id,year_name'])
            ->where('status', 'active')
            ->orderBy('id', 'desc')
            ->get();

        $approvers = User::whereHas('role', function ($query) {
            $query->whereIn('role_name', ['Admin', 'Branch Manager', 'Fee Manager']);
        })
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('FeeApprovalRequests/Create', [
            'enrollments' => $this->formatEnrollmentOptions($enrollments),
            'vouchers' => $this->formatVoucherOptions(),
            'approvers' => $approvers,
        ]);
    }

    public function edit(FeeApprovalRequest $feeApprovalRequest)
    {
        $feeApprovalRequest->load(['studentEnrollment.student', 'voucher', 'requestedBy', 'reviewedBy']);

        $students = Student::select('id', 'student_name', 'admission_no', 'roll_no')
            ->orderBy('student_name')
            ->get();

        $approvers = User::role(['Admin', 'Branch Manager', 'Fee Manager'])
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('FeeApprovalRequests/Edit', [
            'request' => [
                'id'                      => $feeApprovalRequest->id,
                'request_type'            => $feeApprovalRequest->request_type,
                'student_enrollment_id'   => $feeApprovalRequest->student_enrollment_id,
                'voucher_id'              => $feeApprovalRequest->voucher_id,
                'requested_amount'        => $feeApprovalRequest->requested_amount,
                'requested_percent'       => $feeApprovalRequest->requested_percent,
                'current_amount'          => $feeApprovalRequest->current_amount,
                'reason'                  => $feeApprovalRequest->reason,
                'supporting_notes'        => $feeApprovalRequest->supporting_notes,
                'urgency'                 => $feeApprovalRequest->urgency,
                'status'                  => $feeApprovalRequest->status,
                'reviewer_remarks'        => $feeApprovalRequest->reviewer_remarks,
                'requested_at'            => $feeApprovalRequest->requested_at?->format('Y-m-d H:i'),
                'reviewed_at'             => $feeApprovalRequest->reviewed_at?->format('Y-m-d H:i'),
            ],
            'enrollments' => $this->formatEnrollmentOptions(StudentEnrollment::with(['student:id,student_name,admission_no,roll_no', 'academicYear:id,year_name'])->where('status', 'active')->get()),
            'vouchers' => $this->formatVoucherOptions(),
            'students'  => $students,
            'approvers' => $approvers,
        ]);
    }

    public function show(FeeApprovalRequest $feeApprovalRequest)
    {
        $feeApprovalRequest->load(['studentEnrollment.student', 'voucher.feeType', 'requestedBy', 'reviewedBy']);

        return Inertia::render('FeeApprovalRequests/Show', [
            'request' => $feeApprovalRequest,
        ]);
    }

    private function getMobileRequests(Request $request)
    {
        $query = FeeApprovalRequest::with(['studentEnrollment.student', 'voucher.feeType', 'requestedBy', 'reviewedBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhere('request_type', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhereHas('voucher', fn ($voucher) => $voucher->where('voucher_no', 'like', "%{$search}%"))
                  ->orWhereHas('studentEnrollment.student', function ($student) use ($search) {
                      $student->where('student_name', 'like', "%{$search}%")
                          ->orWhere('admission_no', 'like', "%{$search}%")
                          ->orWhere('roll_no', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('request_type')) {
            $query->where('request_type', $request->request_type);
        }

        $perPage = $request->get('per_page', 10);
        $requests = $query->latest()->paginate($perPage);

        return response()->json($requests);
    }

    private function getDataTablesRequests(Request $request)
    {
        $query = FeeApprovalRequest::with(['studentEnrollment.student', 'voucher', 'requestedBy', 'reviewedBy']);

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhere('request_type', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('request_type')) {
            $query->where('request_type', $request->request_type);
        }

        $totalData = $query->count();

        $orderColumn = $request->input('order.0.column', 0);
        $orderDir    = $request->input('order.0.dir', 'desc');
        $columns     = ['id', 'request_type', 'student_enrollment_id', 'requested_amount', 'status', 'requested_at'];

        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        $start    = $request->input('start', 0);
        $length   = $request->input('length', 10);
        $requests = $query->skip($start)->take($length)->get();

        $data = $requests->map(function ($req, $index) use ($start) {
            $statusClass = match ($req->status) {
                'pending'   => 'bg-yellow-100 text-yellow-800',
                'approved'  => 'bg-green-100 text-green-800',
                'rejected'  => 'bg-red-100 text-red-800',
                'processed' => 'bg-blue-100 text-blue-800',
                default     => 'bg-gray-100 text-gray-800',
            };

            $urgencyClass = match ($req->urgency) {
                'high'   => 'bg-red-100 text-red-800',
                'medium' => 'bg-yellow-100 text-yellow-800',
                'low'    => 'bg-green-100 text-green-800',
                default  => 'bg-gray-100 text-gray-800',
            };

            $student = $req->studentEnrollment?->student;
            $voucher = $req->voucher;

            return [
                'DT_RowIndex'       => $start + $index + 1,
                'id'                => $req->id,
                'request_type'      => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">' . ucfirst(str_replace('_', ' ', $req->request_type)) . '</span>',
                'type_text'         => ucfirst(str_replace('_', ' ', $req->request_type)),
                'student_name'      => $student?->student_name ?? '-',
                'admission_no'      => $student?->admission_no ?? '-',
                'student_html'      => '<div class="text-left"><div class="font-semibold text-gray-900">' . e($student?->student_name ?? '-') . '</div><div class="text-xs text-gray-500">' . e($student?->admission_no ?? '-') . '</div></div>',
                'voucher_no'        => $voucher?->voucher_no ?? '-',
                'voucher_html'      => '<div class="text-left"><div class="font-semibold text-gray-900">' . e($voucher?->voucher_no ?? '-') . '</div><div class="text-xs text-gray-500">' . e($voucher?->feeType?->fee_name ?? '-') . '</div></div>',
                'requested_amount'  => number_format($req->requested_amount, 2),
                'requested_percent' => $req->requested_percent ? $req->requested_percent . '%' : '-',
                'current_amount'    => number_format($req->current_amount, 2),
                'amount_html'       => '<div class="text-right"><div class="font-bold text-indigo-700">Rs ' . number_format((float) $req->requested_amount, 2) . '</div><div class="text-xs text-gray-500">Current Rs ' . number_format((float) $req->current_amount, 2) . '</div></div>',
                'urgency'           => '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $urgencyClass . '">' . ucfirst($req->urgency ?? '-') . '</span>',
                'status'            => '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $statusClass . '">' . ucfirst($req->status) . '</span>',
                'requested_by'      => $req->requestedBy?->name ?? '-',
                'reviewed_by'       => $req->reviewedBy?->name ?? '-',
                'requested_at'      => $req->requested_at?->format('d M, Y h:i A') ?? '-',
                'reason'            => \Illuminate\Support\Str::limit($req->reason ?? '-', 40),
                'view_request'      => [
                    'id' => $req->id,
                    'request_type' => ucfirst(str_replace('_', ' ', $req->request_type)),
                    'status' => ucfirst($req->status),
                    'urgency' => ucfirst($req->urgency ?? '-'),
                    'student_name' => $student?->student_name ?? '-',
                    'admission_no' => $student?->admission_no ?? '-',
                    'voucher_no' => $voucher?->voucher_no ?? '-',
                    'fee_type' => $voucher?->feeType?->fee_name ?? '-',
                    'current_amount' => number_format((float) $req->current_amount, 2),
                    'requested_amount' => number_format((float) $req->requested_amount, 2),
                    'requested_percent' => $req->requested_percent ? $req->requested_percent . '%' : '-',
                    'requested_by' => $req->requestedBy?->name ?? '-',
                    'reviewed_by' => $req->reviewedBy?->name ?? '-',
                    'requested_at' => $req->requested_at?->format('d M, Y h:i A') ?? '-',
                    'reviewed_at' => $req->reviewed_at?->format('d M, Y h:i A') ?? '-',
                    'reason' => $req->reason ?? '-',
                    'supporting_notes' => $req->supporting_notes ?? '-',
                    'reviewer_remarks' => $req->reviewer_remarks ?? '-',
                ],
                'action'            => '
                    <div class="flex items-center justify-center gap-2">
                        <button onclick=\'viewRequest(' . json_encode([
                            'id' => $req->id,
                            'request_type' => ucfirst(str_replace('_', ' ', $req->request_type)),
                            'status' => ucfirst($req->status),
                            'urgency' => ucfirst($req->urgency ?? '-'),
                            'student_name' => $student?->student_name ?? '-',
                            'admission_no' => $student?->admission_no ?? '-',
                            'voucher_no' => $voucher?->voucher_no ?? '-',
                            'fee_type' => $voucher?->feeType?->fee_name ?? '-',
                            'current_amount' => number_format((float) $req->current_amount, 2),
                            'requested_amount' => number_format((float) $req->requested_amount, 2),
                            'requested_percent' => $req->requested_percent ? $req->requested_percent . '%' : '-',
                            'requested_by' => $req->requestedBy?->name ?? '-',
                            'reviewed_by' => $req->reviewedBy?->name ?? '-',
                            'requested_at' => $req->requested_at?->format('d M, Y h:i A') ?? '-',
                            'reviewed_at' => $req->reviewed_at?->format('d M, Y h:i A') ?? '-',
                            'reason' => $req->reason ?? '-',
                            'supporting_notes' => $req->supporting_notes ?? '-',
                            'reviewer_remarks' => $req->reviewer_remarks ?? '-',
                        ]) . ')\' class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            View
                        </button>
                        ' . ($req->status === 'pending' ? '
                        <button onclick=\'approveRequest(' . json_encode(['id' => $req->id]) . ')\' class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-600 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Approve
                        </button>
                        <button onclick=\'rejectRequest(' . json_encode(['id' => $req->id]) . ')\' class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Reject
                        </button>
                        ' : '') . '
                        <button onclick=\'editRequest(' . json_encode(['id' => $req->id]) . ')\' class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit
                        </button>
                        <button onclick="deleteRequest(' . $req->id . ')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete
                        </button>
                    </div>
                ',
            ];
        });

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $totalData,
            'recordsFiltered' => $totalData,
            'data'            => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_type'            => 'required|string|in:fee_concession,fee_waiver,fine_waiver,installment_plan,fee_refund,discount,waiver,refund,late_fee_waiver,fee_edit',
            'student_enrollment_id'   => 'required|exists:student_enrollments,id',
            'voucher_id'              => 'nullable|exists:fee_vouchers,id',
            'requested_amount'        => 'required|numeric|min:0',
            'requested_percent'       => 'nullable|numeric|min:0|max:100',
            'current_amount'          => 'required|numeric|min:0',
            'reason'                  => 'required|string',
            'supporting_notes'        => 'nullable|string',
            'urgency'                 => 'required|string|in:low,medium,high,normal,urgent',
            'status'                  => 'nullable|string|in:pending,approved,rejected,processed',
            'reviewer_remarks'        => 'nullable|string',
        ]);

        $validated['requested_by'] = auth()->id() ?? 1;
        $validated['requested_at'] = now();
        $validated['status'] = $validated['status'] ?? 'pending';

        $this->guardVoucherEnrollment($validated);
        FeeApprovalRequest::create($validated);

        return redirect()->route('fee-approval-requests.index')
            ->with('success', 'Fee approval request created successfully!');
    }

    public function update(Request $request, FeeApprovalRequest $feeApprovalRequest)
    {
        $validated = $request->validate([
            'request_type'            => 'required|string|in:fee_concession,fee_waiver,fine_waiver,installment_plan,fee_refund,discount,waiver,refund,late_fee_waiver,fee_edit',
            'student_enrollment_id'   => 'required|exists:student_enrollments,id',
            'voucher_id'              => 'nullable|exists:fee_vouchers,id',
            'requested_amount'        => 'required|numeric|min:0',
            'requested_percent'       => 'nullable|numeric|min:0|max:100',
            'current_amount'          => 'required|numeric|min:0',
            'reason'                  => 'required|string',
            'supporting_notes'        => 'nullable|string',
            'urgency'                 => 'required|string|in:low,medium,high,normal,urgent',
            'status'                  => 'nullable|string|in:pending,approved,rejected,processed',
            'reviewer_remarks'        => 'nullable|string',
        ]);

        $this->guardVoucherEnrollment($validated);
        $feeApprovalRequest->update($validated);

        return redirect()->route('fee-approval-requests.index')
            ->with('success', 'Fee approval request updated successfully!');
    }

    public function approve(Request $request, FeeApprovalRequest $feeApprovalRequest)
    {
        $validated = $request->validate([
            'reviewer_remarks' => 'nullable|string',
        ]);

        DB::transaction(function () use ($feeApprovalRequest, $validated) {
            $feeApprovalRequest->update([
                'status'        => 'approved',
                'reviewed_by'   => auth()->id() ?? 1,
                'reviewed_at'   => now(),
                'reviewer_remarks' => $validated['reviewer_remarks'] ?? 'Approved',
            ]);

            // Take action based on request type
            $this->takeAction($feeApprovalRequest);
        });

        return back()->with('success', 'Fee approval request approved successfully!');
    }

    public function reject(Request $request, FeeApprovalRequest $feeApprovalRequest)
    {
        $validated = $request->validate([
            'reviewer_remarks' => 'required|string',
        ]);

        $feeApprovalRequest->update([
            'status'         => 'rejected',
            'reviewed_by'    => auth()->id() ?? 1,
            'reviewed_at'    => now(),
            'reviewer_remarks' => $validated['reviewer_remarks'],
        ]);

        return back()->with('success', 'Fee approval request rejected.');
    }

    private function takeAction(FeeApprovalRequest $request)
    {
        // This method would implement the actual action based on request_type
        // For example, if request_type is 'fee_concession', create a StudentFeeConcession
        // This is a placeholder for the business logic

        $action = match ($request->request_type) {
            'fee_concession' => $this->createFeeConcession($request),
            'fee_waiver', 'waiver' => $this->createFeeWaiver($request),
            'fine_waiver', 'late_fee_waiver', 'bounce_penalty_waiver' => $this->createFineWaiver($request),
            'installment_plan' => $this->createInstallmentAssignment($request),
            'fee_refund', 'refund' => $this->createFeeRefund($request),
            default => null,
        };

        if ($action) {
            $request->update([
                'status' => 'processed',
                'action_reference_type' => $action::class,
                'action_reference_id' => $action->id,
                'action_taken_at' => now(),
            ]);
        }
    }

    private function createFeeConcession(FeeApprovalRequest $request)
    {
        // Implementation for creating fee concession
    }

    private function createFeeWaiver(FeeApprovalRequest $request)
    {
        if (!$request->voucher_id || $request->requested_amount <= 0) {
            return null;
        }

        $voucher = FeeVoucher::whereKey($request->voucher_id)->lockForUpdate()->firstOrFail();
        $waiver = FeeWaiver::create([
            'voucher_id' => $voucher->id,
            'student_enrollment_id' => $voucher->student_enrollment_id,
            'waived_amount' => min((float) $request->requested_amount, (float) $voucher->remaining_amount),
            'waiver_reason' => $request->reason,
            'approved_by' => auth()->id() ?? 1,
            'approved_on' => now()->toDateString(),
            'status' => 'approved',
            'notes' => 'Created from approval request #' . $request->id,
        ]);

        app(FeeVoucherBalanceService::class)->sync($voucher);

        return $waiver;
    }

    private function createFineWaiver(FeeApprovalRequest $request)
    {
        if (!$request->voucher_id) {
            return null;
        }

        $voucher = FeeVoucher::whereKey($request->voucher_id)->lockForUpdate()->firstOrFail();

        // If a specific fine was targeted during the intercept
        if ($request->action_reference_type === \App\Models\FeeVoucherFine::class && $request->action_reference_id) {
            $fine = FeeVoucherFine::find($request->action_reference_id);
            if ($fine && !$fine->is_waived) {
                $fine->update([
                    'is_waived' => true,
                    'waived_by' => auth()->id() ?? 1,
                    'notes' => trim(($fine->notes ? $fine->notes . "\n" : '') . 'Waived from approval request #' . $request->id . ': ' . $request->reason),
                ]);
                app(FeeVoucherBalanceService::class)->sync($voucher);
                return $fine;
            }
        }

        // Fallback: Waive any active fines up to the requested amount
        $remaining = (float) ($request->requested_amount ?: $voucher->fine_amount);
        $lastFine = null;

        $fines = FeeVoucherFine::where('voucher_id', $voucher->id)
            ->where('is_waived', false)
            ->orderBy('applied_on')
            ->get();

        foreach ($fines as $fine) {
            if ($remaining <= 0) {
                break;
            }

            $fine->update([
                'is_waived' => true,
                'waived_by' => auth()->id() ?? 1,
                'notes' => trim(($fine->notes ? $fine->notes . "\n" : '') . 'Waived from approval request #' . $request->id . ': ' . $request->reason),
            ]);
            $remaining -= (float) $fine->calculated_amount;
            $lastFine = $fine;
        }

        app(FeeVoucherBalanceService::class)->sync($voucher);

        return $lastFine;
    }

    private function createInstallmentAssignment(FeeApprovalRequest $request)
    {
        // Implementation for creating installment assignment
    }

    private function createFeeRefund(FeeApprovalRequest $request)
    {
        if ($request->action_reference_type === \App\Models\FeePayment::class && $request->action_reference_id) {
            $payment = \App\Models\FeePayment::with('voucher')->findOrFail($request->action_reference_id);
            $refund = \App\Models\FeeRefund::create([
                'student_enrollment_id' => $request->student_enrollment_id,
                'payment_id'            => $payment->id,
                'refund_amount'         => $request->requested_amount,
                'refund_date'           => now()->toDateString(),
                'reason'                => $request->reason,
                'status'                => 'approved',
                'notes'                 => trim('Created from approval request #' . $request->id . "\n" . $request->supporting_notes),
                'refunded_by'           => auth()->id() ?? 1,
            ]);

            if ($payment->voucher) {
                app(\App\Services\FeeVoucherBalanceService::class)->sync($payment->voucher);
            }

            return $refund;
        }

        return null;
    }

    private function guardVoucherEnrollment(array $data): void
    {
        if (empty($data['voucher_id'])) {
            return;
        }

        $voucher = FeeVoucher::findOrFail($data['voucher_id']);
        if ((int) $voucher->student_enrollment_id !== (int) $data['student_enrollment_id']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'voucher_id' => 'Selected voucher does not belong to the selected enrollment.',
            ]);
        }
    }

    private function formatEnrollmentOptions($enrollments)
    {
        return $enrollments->map(function ($enrollment) {
            $student = $enrollment->student;
            return [
                'id' => $enrollment->id,
                'label' => ($student?->student_name ?? 'Unknown student') . ' - ' . ($student?->admission_no ?? '-'),
                'subtitle' => 'Roll ' . ($student?->roll_no ?? '-') . ' - ' . ($enrollment->academicYear?->year_name ?? '-'),
                'search' => implode(' ', [$student?->student_name, $student?->admission_no, $student?->roll_no]),
            ];
        })->values();
    }

    private function formatVoucherOptions()
    {
        return FeeVoucher::with(['studentEnrollment.student:id,student_name,admission_no,roll_no', 'feeType:id,fee_name'])
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($voucher) {
                $student = $voucher->studentEnrollment?->student;
                return [
                    'id' => $voucher->id,
                    'label' => $voucher->voucher_no . ' - ' . ($student?->student_name ?? 'Unknown student'),
                    'subtitle' => ($voucher->feeType?->fee_name ?? '-') . ' - Remaining Rs ' . number_format((float) $voucher->remaining_amount, 2),
                    'amount_label' => 'Rs ' . number_format((float) $voucher->remaining_amount, 2),
                    'student_enrollment_id' => $voucher->student_enrollment_id,
                    'remaining_amount' => (float) $voucher->remaining_amount,
                    'net_amount' => (float) $voucher->net_amount,
                    'search' => implode(' ', [$voucher->voucher_no, $student?->student_name, $student?->admission_no, $student?->roll_no, $voucher->feeType?->fee_name]),
                ];
            })
            ->values();
    }

    public function destroy(FeeApprovalRequest $feeApprovalRequest)
    {
        if ($feeApprovalRequest->status !== 'pending') {
            return back()->with('error', 'Cannot delete a processed approval request.');
        }

        $feeApprovalRequest->delete();

        return back()->with('success', 'Fee approval request deleted successfully!');
    }

    public function getEnrollmentsByStudent($studentId)
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

    public function getVouchersByEnrollment($enrollmentId)
    {
        $vouchers = FeeVoucher::where('student_enrollment_id', $enrollmentId)
            ->select('id', 'voucher_no', 'net_amount', 'paid_amount', 'remaining_amount', 'status')
            ->orderBy('due_date')
            ->get();

        return response()->json($vouchers);
    }

    public function myRequests(Request $request)
    {
        $query = FeeApprovalRequest::where('requested_by', auth()->id() ?? 1)
            ->with(['studentEnrollment.student', 'voucher', 'reviewedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->paginate(10);

        return response()->json($requests);
    }

    public function pendingForApproval(Request $request)
    {
        $query = FeeApprovalRequest::where('status', 'pending')
            ->with(['studentEnrollment.student', 'voucher', 'requestedBy']);

        if ($request->filled('request_type')) {
            $query->where('request_type', $request->request_type);
        }

        if ($request->filled('urgency')) {
            $query->where('urgency', $request->urgency);
        }

        $requests = $query->orderBy('urgency', 'desc')->latest()->paginate(10);

        return response()->json($requests);
    }
}
