<?php

namespace App\Http\Controllers;

use App\Models\ChequeTracking;
use App\Models\FeePayment;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ChequeTrackingController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileData($request);
        }

        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesData($request);
        }

        return Inertia::render('ChequeTracking/Index');
    }

    public function create()
    {
        return Inertia::render('ChequeTracking/Create', $this->getFormData());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_id' => 'nullable|exists:fee_payments,id',
            'student_enrollment_id' => 'required|exists:student_enrollments,id',
            'cheque_no' => 'required|string|max:100',
            'cheque_date' => 'required|date',
            'bank_name' => 'required|string|max:100',
            'branch_name' => 'nullable|string|max:100',
            'account_title' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'received_date' => 'required|date',
            'expected_clearance_date' => 'nullable|date',
            'status' => 'required|in:Pending,Cleared,Bounced',
            'notes' => 'nullable|string',
        ]);

        ChequeTracking::create($validated);

        return redirect()->route('cheque-tracking.index')
            ->with('success', 'Cheque record created successfully!');
    }

    public function edit(ChequeTracking $chequeTracking)
    {
        $chequeTracking->load(['studentEnrollment.student', 'payment']);

        return Inertia::render('ChequeTracking/Edit', array_merge(
            $this->getFormData(),
            [
                'cheque' => [
                    'id' => $chequeTracking->id,
                    'payment_id' => $chequeTracking->payment_id,
                    'student_enrollment_id' => $chequeTracking->student_enrollment_id,
                    'cheque_no' => $chequeTracking->cheque_no,
                    'cheque_date' => $chequeTracking->cheque_date?->format('Y-m-d'),
                    'bank_name' => $chequeTracking->bank_name,
                    'branch_name' => $chequeTracking->branch_name,
                    'account_title' => $chequeTracking->account_title,
                    'amount' => $chequeTracking->amount,
                    'received_date' => $chequeTracking->received_date?->format('Y-m-d'),
                    'expected_clearance_date' => $chequeTracking->expected_clearance_date?->format('Y-m-d'),
                    'status' => $chequeTracking->status,
                    'cleared_on' => $chequeTracking->cleared_on?->format('Y-m-d'),
                    'bounced_on' => $chequeTracking->bounced_on?->format('Y-m-d'),
                    'bounce_reason' => $chequeTracking->bounce_reason,
                    'bounce_reason_detail' => $chequeTracking->bounce_reason_detail,
                    'notes' => $chequeTracking->notes,
                ]
            ]
        ));
    }

    public function update(Request $request, ChequeTracking $chequeTracking)
    {
        $validated = $request->validate([
            'payment_id' => 'nullable|exists:fee_payments,id',
            'student_enrollment_id' => 'required|exists:student_enrollments,id',
            'cheque_no' => 'required|string|max:100',
            'cheque_date' => 'required|date',
            'bank_name' => 'required|string|max:100',
            'branch_name' => 'nullable|string|max:100',
            'account_title' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'received_date' => 'required|date',
            'expected_clearance_date' => 'nullable|date',
            'status' => 'required|in:Pending,Cleared,Bounced',
            'cleared_on' => 'nullable|date|required_if:status,Cleared',
            'bounced_on' => 'nullable|date|required_if:status,Bounced',
            'bounce_reason' => 'nullable|string|required_if:status,Bounced',
            'bounce_reason_detail' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validated['status'] === 'Cleared' && !$chequeTracking->cleared_confirmed_by) {
            $validated['cleared_confirmed_by'] = auth()->id();
        }

        $chequeTracking->update($validated);

        return redirect()->route('cheque-tracking.index')
            ->with('success', 'Cheque record updated successfully!');
    }

    public function destroy(ChequeTracking $chequeTracking)
    {
        $chequeTracking->delete();

        return redirect()->route('cheque-tracking.index')
            ->with('success', 'Cheque record deleted successfully!');
    }

    private function getMobileData(Request $request)
    {
        $query = ChequeTracking::with(['studentEnrollment.student', 'payment']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('cheque_no', 'like', "%{$search}%")
                  ->orWhere('bank_name', 'like', "%{$search}%")
                  ->orWhereHas('studentEnrollment.student', function ($sq) use ($search) {
                      $sq->where('student_name', 'like', "%{$search}%");
                  });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->get('per_page', 10);
        $data = $query->latest('received_date')->paginate($perPage);

        return response()->json($data);
    }

    private function getDataTablesData(Request $request)
    {
        $query = ChequeTracking::with(['studentEnrollment.student', 'payment']);

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('cheque_no', 'like', "%{$search}%")
                  ->orWhere('bank_name', 'like', "%{$search}%")
                  ->orWhereHas('studentEnrollment.student', function ($sq) use ($search) {
                      $sq->where('student_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $totalData = $query->count();

        $orderColumn = $request->input('order.0.column', 0);
        $orderDir    = $request->input('order.0.dir', 'desc');
        $columns     = ['id', 'cheque_no', 'student_enrollment_id', 'bank_name', 'amount', 'status', 'received_date'];

        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        $start    = $request->input('start', 0);
        $length   = $request->input('length', 10);
        $cheques  = $query->skip($start)->take($length)->get();

        $data = $cheques->map(function ($cheque, $index) use ($start) {
            $statusColors = [
                'Pending' => 'bg-yellow-100 text-yellow-800',
                'Cleared' => 'bg-green-100 text-green-800',
                'Bounced' => 'bg-red-100 text-red-800',
            ];
            $statusClass = $statusColors[$cheque->status] ?? 'bg-gray-100 text-gray-800';

            return [
                'DT_RowIndex'   => $start + $index + 1,
                'id'            => $cheque->id,
                'cheque_no'     => '<span class="font-mono text-sm font-medium">' . $cheque->cheque_no . '</span>',
                'student_name'  => $cheque->studentEnrollment?->student?->student_name ?? '-',
                'admission_no'  => $cheque->studentEnrollment?->student?->admission_no ?? '-',
                'bank_name'     => $cheque->bank_name,
                'amount'        => '<span class="font-semibold text-gray-900">Rs. ' . number_format((float)($cheque->amount ?? 0), 0) . '</span>',
                'received_date' => $cheque->received_date?->format('d M, Y') ?? '-',
                'status'        => '<span class="px-2.5 py-0.5 text-xs font-medium rounded-full ' . $statusClass . '">' . $cheque->status . '</span>',
                'action'        => '
                    <div class="flex items-center justify-center gap-2">
                        <button onclick=\'editCheque(' . json_encode(['id' => $cheque->id]) . ')\' class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit
                        </button>
                        <button onclick="deleteCheque(' . $cheque->id . ')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
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

    private function getFormData(): array
    {
        return [
            // Returning empty or minimal dataset initially to be populated via Select2 API calls for large data like students
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
}
