<?php

namespace App\Http\Controllers;

use App\Models\StudentFeeStructure;
use App\Models\StudentEnrollment;
use App\Models\FeeStructure;
use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Classes;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentFeeStructureController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('mobile') || ($request->ajax() && $request->has('page'))) {
            return $this->getMobileData($request);
        }
        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesData($request);
        }

        return Inertia::render('StudentFeeStructures/Index', [
            'academicYears' => AcademicYear::select('id', 'year_name')->orderBy('start_date', 'desc')->get(),
            'branches'      => Branch::active()->select('id', 'branch_name')->orderBy('branch_name')->get(),
            'classes'       => Classes::select('id', 'class_name')->orderBy('class_name')->get(),
        ]);
    }

    /**
     * Sync all active fee structures to enrolled students (one-time / manual trigger)
     */
    public function syncAll()
    {
        $structures = FeeStructure::active()->get();
        $total = 0;
        foreach ($structures as $structure) {
            $total += $structure->syncToEnrolledStudents();
        }

        return back()->with('success', "Sync complete! {$total} student-fee assignments created/updated.");
    }

    /**
     * Sync a single fee structure to its enrolled students
     */
    public function syncOne(FeeStructure $feeStructure)
    {
        $count = $feeStructure->syncToEnrolledStudents();
        return back()->with('success', "Fee structure synced to {$count} students.");
    }

    /**
     * Update custom_amount or is_active for a specific student-fee assignment
     */
    public function update(Request $request, StudentFeeStructure $studentFeeStructure)
    {
        $validated = $request->validate([
            'custom_amount' => 'nullable|numeric|min:0',
            'is_active'     => 'boolean',
        ]);

        $studentFeeStructure->update($validated);

        return back()->with('success', 'Student fee assignment updated.');
    }

    /**
     * Remove a student-fee assignment (unlink)
     */
    public function destroy(StudentFeeStructure $studentFeeStructure)
    {
        $studentFeeStructure->delete();
        return back()->with('success', 'Student fee assignment removed.');
    }

    // ─── DataTables ──────────────────────────────────────────────────────────────

    private function getDataTablesData(Request $request)
    {
        $query = StudentFeeStructure::with([
            'studentEnrollment.student',
            'studentEnrollment.academicYear',
            'studentEnrollment.classSection.branchClass.class',
            'studentEnrollment.classSection.branchClass.branch',
            'feeStructure.feeType',
        ]);

        $recordsTotal = StudentFeeStructure::count();

        if ($request->filled('academic_year_id')) {
            $query->whereHas('studentEnrollment', fn($q) => $q->where('academic_year_id', $request->academic_year_id));
        }
        if ($request->filled('branch_id')) {
            $query->whereHas('studentEnrollment', fn($q) => $q->where('branch_id', $request->branch_id));
        }
        if ($request->filled('class_id')) {
            $query->whereHas('studentEnrollment.classSection.branchClass', fn($q) => $q->where('class_id', $request->class_id));
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->whereHas('studentEnrollment.student', fn($sq) =>
                    $sq->where('student_name', 'like', "%{$search}%")
                       ->orWhere('admission_no', 'like', "%{$search}%")
                       ->orWhere('roll_no', 'like', "%{$search}%")
                )
                ->orWhereHas('feeStructure.feeType', fn($sq) =>
                    $sq->where('fee_name', 'like', "%{$search}%")
                );
            });
        }

        $recordsFiltered = $query->count();
        $start  = $request->input('start', 0);
        $length = $request->input('length', 10);
        $rows   = $query->latest()->skip($start)->take($length)->get();

        $data = $rows->map(function ($sfs, $idx) use ($start) {
            $enrollment = $sfs->studentEnrollment;
            $student    = $enrollment?->student;
            $structure  = $sfs->feeStructure;
            $className  = $enrollment?->classSection?->branchClass?->class?->class_name ?? '-';
            $branchName = $enrollment?->classSection?->branchClass?->branch?->branch_name
                       ?? ($enrollment ? Branch::find($enrollment->branch_id)?->branch_name : '-')
                       ?? '-';

            $baseAmount    = number_format((float) $structure?->amount, 2);
            $effectiveAmt  = number_format($sfs->effective_amount, 2);
            $isCustom      = $sfs->custom_amount !== null;

            return [
                'DT_RowIndex'   => $start + $idx + 1,
                'id'            => $sfs->id,
                'student_name'  => $student?->student_name ?? '-',
                'admission_no'  => $student?->admission_no ?? '-',
                'class_name'    => $className,
                'branch_name'   => $branchName,
                'academic_year' => $enrollment?->academicYear?->year_name ?? '-',
                'fee_type'      => $structure?->feeType?->fee_name ?? '-',
                'base_amount'   => 'Rs ' . $baseAmount,
                'custom_amount' => $isCustom ? 'Rs ' . $effectiveAmt : '<span class="text-gray-400 text-xs">—</span>',
                'effective_amount' => 'Rs ' . $effectiveAmt,
                'is_active'     => $sfs->is_active
                    ? '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Active</span>'
                    : '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">Inactive</span>',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <button onclick=\'editStudentFee(' . json_encode(['id' => $sfs->id, 'custom_amount' => $sfs->custom_amount, 'is_active' => $sfs->is_active]) . ')\' class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Edit
                        </button>
                        <button onclick="deleteStudentFee(' . $sfs->id . ')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>Remove
                        </button>
                    </div>',
            ];
        });

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    // ─── Mobile ──────────────────────────────────────────────────────────────────

    private function getMobileData(Request $request)
    {
        $query = StudentFeeStructure::with([
            'studentEnrollment.student',
            'studentEnrollment.academicYear',
            'studentEnrollment.classSection.branchClass.class',
            'feeStructure.feeType',
        ]);

        if ($request->filled('academic_year_id')) {
            $query->whereHas('studentEnrollment', fn($q) => $q->where('academic_year_id', $request->academic_year_id));
        }
        if ($request->filled('class_id')) {
            $query->whereHas('studentEnrollment.classSection.branchClass', fn($q) => $q->where('class_id', $request->class_id));
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('studentEnrollment.student', fn($sq) =>
                    $sq->where('student_name', 'like', "%{$search}%")
                       ->orWhere('admission_no', 'like', "%{$search}%")
                );
            });
        }

        return response()->json($query->latest()->paginate($request->get('per_page', 10)));
    }
}
