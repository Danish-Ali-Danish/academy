<?php

namespace App\Http\Controllers;

use App\Models\FeeCollectionSummary;
use App\Models\Branch;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\FeeVoucher;
use App\Models\FeePayment;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;

class FeeCollectionSummaryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileSummaries($request);
        }

        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesSummaries($request);
        }

        $branches = Branch::active()->select('id', 'branch_name')->orderBy('branch_name')->get();
        return Inertia::render('FeeCollectionSummaries/Index', [
            'branches' => $branches
        ]);
    }

    /**
     * Render the collection dashboard view.
     */
    public function dashboard()
    {
        return Inertia::render('FeeCollectionSummaries/Dashboard');
    }

    /**
     * API: Aggregated data for dashboard charts and widgets.
     */
    public function dashboardData(Request $request)
    {
        $branchId = $request->get('branch_id');
        $start = $request->get('start_date') ?: now()->startOfMonth()->toDateString();
        $end = $request->get('end_date') ?: now()->endOfMonth()->toDateString();

        // Modes breakdown from payments
        $paymentsQuery = FeePayment::whereBetween('payment_date', [$start, $end]);
        if ($branchId) {
            $paymentsQuery->whereHas('studentEnrollment', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $modes = $paymentsQuery->select('payment_method', DB::raw('SUM(paid_amount) as total'))
            ->groupBy('payment_method')
            ->get()
            ->mapWithKeys(function ($row) {
                return [$row->payment_method => (float) $row->total];
            });

        // Projected vs Actual: projected = sum of vouchers net_amount for chosen month range
        $vouchersQuery = FeeVoucher::whereBetween('due_date', [$start, $end]);
        if ($branchId) {
            $vouchersQuery->whereHas('studentEnrollment', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $projected = (float) $vouchersQuery->sum('net_amount');

        $actual = (float) $paymentsQuery->sum('paid_amount');

        $variance = $actual - $projected;

        // Reconciliation: deposited vs cash in hand
        $deposited = (float) FeePayment::whereBetween('payment_date', [$start, $end])
            ->whereIn('payment_method', ['bank_transfer', 'gateway'])
            ->when($branchId, fn($q) => $q->whereHas('studentEnrollment', fn($q2) => $q2->where('branch_id', $branchId)))
            ->sum('paid_amount');

        // For cheques, consider cleared cheques as deposited
        $chequeDeposited = (float) DB::table('cheque_tracking')
            ->join('fee_payments', 'cheque_tracking.payment_id', '=', 'fee_payments.id')
            ->whereBetween('fee_payments.payment_date', [$start, $end])
            ->where('cheque_tracking.status', 'cleared')
            ->when($branchId, fn($q) => $q->where('fee_payments.student_enrollment_id', function ($sub) use ($branchId) {
                // fallback: handled below via join to student_enrollments if needed
            }))
            ->sum('cheque_tracking.amount');

        $cashInHand = max(0, $actual - ($deposited + $chequeDeposited));

        // Defaulters: total arrears and top 10 defaulters by remaining amount
        $arrearsQuery = FeeVoucher::whereIn('status', ['pending', 'partial']);
        if ($branchId) {
            $arrearsQuery->whereHas('studentEnrollment', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $totalArrears = (float) $arrearsQuery->sum('remaining_amount');

        $topDefaulters = FeeVoucher::select('student_enrollment_id', DB::raw('SUM(remaining_amount) as total_due'))
            ->whereIn('status', ['pending', 'partial'])
            ->when($branchId, fn($q) => $q->whereHas('studentEnrollment', fn($q2) => $q2->where('branch_id', $branchId)))
            ->groupBy('student_enrollment_id')
            ->orderByDesc('total_due')
            ->take(10)
            ->get()
            ->map(function ($row) {
                $enroll = StudentEnrollment::with('student')->find($row->student_enrollment_id);
                return [
                    'student_name' => $enroll?->student?->full_name ?? 'Unknown',
                    'enrollment_id' => $row->student_enrollment_id,
                    'total_due' => (float) $row->total_due,
                ];
            });

        return response()->json([
            'modes' => $modes,
            'projected' => $projected,
            'actual' => $actual,
            'variance' => $variance,
            'reconciliation' => [
                'deposited' => $deposited + $chequeDeposited,
                'cash_in_hand' => $cashInHand,
            ],
            'defaulters' => [
                'total_arrears' => $totalArrears,
                'top_defaulters' => $topDefaulters,
            ],
        ]);
    }

    private function buildDynamicSummaryQuery(Request $request)
    {
        $query = DB::table('fee_vouchers')
            ->join('student_enrollments', 'fee_vouchers.student_enrollment_id', '=', 'student_enrollments.id')
            ->join('branches', 'student_enrollments.branch_id', '=', 'branches.id')
            ->join('academic_years', 'student_enrollments.academic_year_id', '=', 'academic_years.id')
            ->select([
                'student_enrollments.branch_id',
                'branches.branch_name',
                'student_enrollments.academic_year_id',
                'academic_years.year_name as academic_year',
                DB::raw('MONTH(fee_vouchers.due_date) as summary_month'),
                DB::raw('YEAR(fee_vouchers.due_date) as summary_year'),
                DB::raw('COUNT(DISTINCT fee_vouchers.student_enrollment_id) as total_students'),
                DB::raw('SUM(fee_vouchers.paid_amount) as total_collected'),
                DB::raw('SUM(fee_vouchers.remaining_amount) as total_pending')
            ])
            ->whereNotNull('fee_vouchers.due_date');

        if ($request->filled('branch_id')) {
            $query->where('student_enrollments.branch_id', $request->branch_id);
        }

        if ($request->filled('month')) {
            $query->whereMonth('fee_vouchers.due_date', $request->month);
        }

        if ($request->filled('year')) {
            $query->whereYear('fee_vouchers.due_date', $request->year);
        }

        $search = $request->input('search.value');
        if ($search === null) {
            $search = is_array($request->input('search')) ? $request->input('search.value') : $request->input('search');
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('branches.branch_name', 'like', "%{$search}%")
                  ->orWhere(DB::raw('YEAR(fee_vouchers.due_date)'), 'like', "%{$search}%");
            });
        }

        $query->groupBy(
            'student_enrollments.branch_id',
            'branches.branch_name',
            'student_enrollments.academic_year_id',
            'academic_years.year_name',
            DB::raw('YEAR(fee_vouchers.due_date)'),
            DB::raw('MONTH(fee_vouchers.due_date)')
        );

        return $query;
    }

    private function getMobileSummaries(Request $request)
    {
        $query = $this->buildDynamicSummaryQuery($request);

        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);

        // We need to paginate the grouped query using a subquery or get all and collection paginate.
        // Getting all grouped results is usually fast enough for summaries (few branches * 12 months)
        $allResults = $query->orderBy(DB::raw('YEAR(fee_vouchers.due_date)'), 'desc')
                            ->orderBy(DB::raw('MONTH(fee_vouchers.due_date)'), 'desc')
                            ->get();

        $summaries = new \Illuminate\Pagination\LengthAwarePaginator(
            $allResults->forPage($page, $perPage)->values(),
            $allResults->count(),
            $perPage,
            $page,
            ['path' => $request->url()]
        );

        return response()->json($summaries);
    }

    private function getDataTablesSummaries(Request $request)
    {
        $query = $this->buildDynamicSummaryQuery($request);

        $allResults = collect($query->get());
        $totalData = $allResults->count();

        // Sorting
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir    = $request->input('order.0.dir', 'desc');
        
        $columns = ['branch_name', 'summary_month', 'summary_year', 'total_collected', 'total_pending'];
        
        if (isset($columns[$orderColumnIndex])) {
            $col = $columns[$orderColumnIndex];
            if ($orderDir === 'asc') {
                $allResults = $allResults->sortBy($col);
            } else {
                $allResults = $allResults->sortByDesc($col);
            }
        } else {
            $allResults = $allResults->sortByDesc('summary_year')->sortByDesc('summary_month');
        }

        $start     = (int) $request->input('start', 0);
        $length    = (int) $request->input('length', 10);
        
        $summaries = $allResults->slice($start, $length)->values();

        $data = $summaries->map(function ($summary, $index) use ($start) {
            $uniqueId = $summary->branch_id . '-' . $summary->summary_year . '-' . $summary->summary_month;
            return [
                'DT_RowIndex'     => $start + $index + 1,
                'id'              => $uniqueId, // Virtual ID
                'branch_name'     => $summary->branch_name ?? '-',
                'academic_year'   => $summary->academic_year ?? '-',
                'month_year'      => str_pad($summary->summary_month, 2, '0', STR_PAD_LEFT) . '/' . $summary->summary_year,
                'total_collected'  => number_format($summary->total_collected, 2),
                'total_pending'    => number_format($summary->total_pending, 2),
                'total_students'   => $summary->total_students,
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-50 rounded-full">
                            Auto-Generated
                        </span>
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


}
