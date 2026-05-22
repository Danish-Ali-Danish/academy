<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\FeeVoucher;
use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\FeeType;
use App\Models\Classes;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class FeeDemandRegisterController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->parseFilters($request);

        // Fetch Class-Wise Data
        $classWiseQuery = FeeVoucher::with(['studentEnrollment.classSection.branchClass.class'])
            ->select(
                'student_enrollments.class_section_id',
                DB::raw('COUNT(DISTINCT fee_vouchers.student_enrollment_id) as total_students'),
                DB::raw('SUM(fee_vouchers.net_amount) as demand_amount'),
                DB::raw('SUM(fee_vouchers.paid_amount) as collected_amount'),
                DB::raw('SUM(fee_vouchers.remaining_amount) as outstanding_amount')
            )
            ->join('student_enrollments', 'fee_vouchers.student_enrollment_id', '=', 'student_enrollments.id')
            ->whereNull('fee_vouchers.deleted_at');

        $classWiseQuery = $this->applyFilters($classWiseQuery, $filters);
        
        $classWiseData = $classWiseQuery->groupBy('student_enrollments.class_section_id')
            ->get()
            ->map(function ($item) {
                $cs = \App\Models\ClassSection::with(['branchClass.class', 'section'])->find($item->class_section_id);
                $percentage = $item->demand_amount > 0 ? ($item->collected_amount / $item->demand_amount) * 100 : 0;
                
                return [
                    'class_section_id' => $item->class_section_id,
                    'class_name' => $cs ? ($cs->branchClass->class->class_name . ' - ' . $cs->section->section_name) : 'Unknown',
                    'total_students' => $item->total_students,
                    'demand_amount' => $item->demand_amount,
                    'collected_amount' => $item->collected_amount,
                    'outstanding_amount' => $item->outstanding_amount,
                    'collection_percentage' => round($percentage, 2),
                ];
            });

        // Fetch Fee Type-Wise Data
        $feeTypeQuery = FeeVoucher::with('feeType')
            ->select(
                'fee_type_id',
                DB::raw('SUM(fee_vouchers.net_amount) as demand_amount'),
                DB::raw('SUM(fee_vouchers.paid_amount) as collected_amount'),
                DB::raw('SUM(fee_vouchers.remaining_amount) as outstanding_amount')
            )
            ->join('student_enrollments', 'fee_vouchers.student_enrollment_id', '=', 'student_enrollments.id')
            ->whereNull('fee_vouchers.deleted_at');

        $feeTypeQuery = $this->applyFilters($feeTypeQuery, $filters);

        $feeTypeData = $feeTypeQuery->groupBy('fee_type_id')
            ->get()
            ->map(function ($item) {
                $ft = \App\Models\FeeType::find($item->fee_type_id);
                $percentage = $item->demand_amount > 0 ? ($item->collected_amount / $item->demand_amount) * 100 : 0;
                
                return [
                    'fee_type_id' => $item->fee_type_id,
                    'fee_type_name' => $ft ? $ft->fee_name : 'Unknown',
                    'demand_amount' => $item->demand_amount,
                    'collected_amount' => $item->collected_amount,
                    'outstanding_amount' => $item->outstanding_amount,
                    'collection_percentage' => round($percentage, 2),
                ];
            });

        // Summary Totals
        $totalDemand = $classWiseData->sum('demand_amount');
        $totalCollected = $classWiseData->sum('collected_amount');
        $totalOutstanding = $classWiseData->sum('outstanding_amount');
        $totalPercentage = $totalDemand > 0 ? ($totalCollected / $totalDemand) * 100 : 0;

        return Inertia::render('Reports/FeeDemandRegister', [
            'summary' => [
                'demand' => $totalDemand,
                'collected' => $totalCollected,
                'outstanding' => $totalOutstanding,
                'percentage' => round($totalPercentage, 2),
            ],
            'classWiseData' => $classWiseData,
            'feeTypeData' => $feeTypeData,
            'filters' => $filters,
            'dropdowns' => [
                'branches' => Branch::all(),
                'academicYears' => AcademicYear::all(),
                'feeTypes' => FeeType::all(),
                'classes' => Classes::all(),
            ]
        ]);
    }

    public function drillDown(Request $request)
    {
        $filters = $this->parseFilters($request);
        $classSectionId = $request->class_section_id;

        $query = FeeVoucher::with(['studentEnrollment.student'])
            ->join('student_enrollments', 'fee_vouchers.student_enrollment_id', '=', 'student_enrollments.id')
            ->where('student_enrollments.class_section_id', $classSectionId)
            ->whereNull('fee_vouchers.deleted_at');

        $query = $this->applyFilters($query, $filters);

        $students = $query->get()->map(function ($voucher) {
            return [
                'student_name' => $voucher->studentEnrollment->student->student_name ?? 'Unknown',
                'roll_no' => $voucher->studentEnrollment->student->roll_no ?? '-',
                'demand' => $voucher->net_amount,
                'paid' => $voucher->paid_amount,
                'outstanding' => $voucher->remaining_amount,
                'status' => $voucher->status,
            ];
        });

        return response()->json($students);
    }

    private function parseFilters(Request $request)
    {
        return [
            'month' => $request->month ?? date('n'),
            'year' => $request->year ?? date('Y'),
            'academic_year_id' => $request->academic_year_id,
            'branch_id' => $request->branch_id,
            'fee_type_id' => $request->fee_type_id,
            'class_id' => $request->class_id,
        ];
    }

    private function applyFilters($query, array $filters)
    {
        if (!empty($filters['month'])) {
            $query->where('fee_vouchers.month', $filters['month']);
        }
        if (!empty($filters['year'])) {
            $query->where('fee_vouchers.year', $filters['year']);
        }
        if (!empty($filters['academic_year_id'])) {
            $query->where('fee_vouchers.academic_year_id', $filters['academic_year_id']);
        }
        if (!empty($filters['branch_id'])) {
            $query->where('student_enrollments.branch_id', $filters['branch_id']);
        }
        if (!empty($filters['fee_type_id'])) {
            $query->where('fee_vouchers.fee_type_id', $filters['fee_type_id']);
        }
        if (!empty($filters['class_id'])) {
            $query->whereHas('studentEnrollment.classSection.branchClass', function($q) use ($filters) {
                $q->where('class_id', $filters['class_id']);
            });
        }

        return $query;
    }
}
