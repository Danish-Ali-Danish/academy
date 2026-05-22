<?php

namespace App\Http\Controllers;

use App\Models\FeeVoucherEditHistory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeeVoucherEditHistoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return response()->json($this->baseQuery($request)->latest('edited_at')->paginate($request->get('per_page', 10)));
        }

        if ($request->ajax() && $request->has('draw')) {
            return $this->dataTable($request);
        }

        return Inertia::render('FeeVoucherEditHistory/Index');
    }

    private function dataTable(Request $request)
    {
        $query = $this->baseQuery($request);
        $recordsFiltered = (clone $query)->count();

        $columns = ['id', 'voucher_id', 'student_enrollment_id', 'edited_at', 'edited_by'];
        $orderColumn = (int) $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $items = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => FeeVoucherEditHistory::count(),
            'recordsFiltered' => $recordsFiltered,
            'data' => $items->map(fn ($history, $index) => $this->formatRow($history, $start + $index + 1)),
        ]);
    }

    private function baseQuery(Request $request)
    {
        $query = FeeVoucherEditHistory::with([
            'voucher.feeType',
            'studentEnrollment.student',
            'editedBy',
            'approvalRequest',
        ]);

        $search = $request->input('search.value');
        if ($search === null) {
            $search = is_array($request->input('search')) ? $request->input('search.value') : $request->input('search');
        }
        
        if (!empty($search) && is_string($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('edit_reason', 'like', "%{$search}%")
                    ->orWhereHas('voucher', fn ($sq) => $sq->where('voucher_no', 'like', "%{$search}%"))
                    ->orWhereHas('studentEnrollment.student', function ($sq) use ($search) {
                        $sq->where('student_name', 'like', "%{$search}%")
                            ->orWhere('admission_no', 'like', "%{$search}%")
                            ->orWhere('roll_no', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    private function formatRow(FeeVoucherEditHistory $history, int $index): array
    {
        $student = $history->studentEnrollment?->student;
        $changes = collect($history->changes ?? [])->map(function ($change, $field) {
            $oldValue = is_array($change) && array_key_exists('old', $change) ? $change['old'] : '-';
            $newValue = is_array($change) && array_key_exists('new', $change) ? $change['new'] : $change;

            return [
                'field' => ucfirst(str_replace('_', ' ', $field)),
                'old' => $oldValue,
                'new' => $newValue,
            ];
        })->values();

        $payload = [
            'id' => $history->id,
            'voucher_no' => $history->voucher?->voucher_no ?? '-',
            'student_name' => $student?->student_name ?? '-',
            'admission_no' => $student?->admission_no ?? '-',
            'fee_type' => $history->voucher?->feeType?->fee_name ?? '-',
            'reason' => $history->edit_reason,
            'edited_by' => $history->editedBy?->name ?? '-',
            'edited_at' => $history->edited_at?->format('d M, Y h:i A') ?? '-',
            'requires_approval' => $history->requires_approval ? 'Yes' : 'No',
            'approval_status' => $history->approvalRequest?->status ?? '-',
            'changes' => $changes,
        ];

        return array_merge($payload, [
            'DT_RowIndex' => $index,
            'voucher' => '<div><div class="font-semibold text-gray-900">' . e($payload['voucher_no']) . '</div><div class="text-xs text-gray-500">' . e($payload['fee_type']) . '</div></div>',
            'student' => '<div><div class="font-semibold text-gray-900">' . e($payload['student_name']) . '</div><div class="text-xs text-gray-500">' . e($payload['admission_no']) . '</div></div>',
            'change_count' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-50 text-indigo-700">' . $changes->count() . ' change(s)</span>',
            'approval' => $history->requires_approval
                ? '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Required</span>'
                : '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Not Required</span>',
            'action' => '<button onclick=\'viewHistory(' . json_encode($payload) . ')\' class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-600 bg-green-50 rounded-lg hover:bg-green-100">View</button>',
        ]);
    }
}
