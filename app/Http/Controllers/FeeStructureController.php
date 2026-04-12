<?php

namespace App\Http\Controllers;

use App\Models\FeeStructure;
use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\FeeType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeeStructureController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileStructures($request);
        }

        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesStructures($request);
        }

        return Inertia::render('FeeStructures/Index');
    }

    public function create()
    {
        $academicYears = AcademicYear::select('id', 'year_name')->orderBy('start_date', 'desc')->get();
        $branches = Branch::active()->select('id', 'branch_name')->orderBy('branch_name')->get();
        $feeTypes = FeeType::select('id', 'fee_name')->orderBy('fee_name')->get();
        $existingStructures = FeeStructure::select('id', 'academic_year_id', 'branch_id', 'class_id', 'fee_type_id')->get();

        return Inertia::render('FeeStructures/Create', [
            'academicYears'      => $academicYears,
            'branches'           => $branches,
            'feeTypes'           => $feeTypes,
            'existingStructures' => $existingStructures,
        ]);
    }

    public function edit(FeeStructure $feeStructure)
    {
        $academicYears = AcademicYear::select('id', 'year_name')->orderBy('start_date', 'desc')->get();
        $branches = Branch::active()->select('id', 'branch_name')->orderBy('branch_name')->get();
        $feeTypes = FeeType::select('id', 'fee_name')->orderBy('fee_name')->get();

        return Inertia::render('FeeStructures/Edit', [
            'feeStructure' => [
                'id'               => $feeStructure->id,
                'academic_year_id' => $feeStructure->academic_year_id,
                'branch_id'        => $feeStructure->branch_id,
                'class_id'         => $feeStructure->class_id,
                'fee_type_id'      => $feeStructure->fee_type_id,
                'amount'           => $feeStructure->amount,
                'due_day'          => $feeStructure->due_day,
                'effective_from'   => $feeStructure->effective_from?->format('Y-m-d'),
                'effective_to'     => $feeStructure->effective_to?->format('Y-m-d'),
                'is_active'        => $feeStructure->is_active,
            ],
            'academicYears' => $academicYears,
            'branches'      => $branches,
            'feeTypes'      => $feeTypes,
        ]);
    }

    private function getMobileStructures(Request $request)
    {
        $query = FeeStructure::with(['academicYear', 'branch', 'class', 'feeType']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('amount', 'like', "%{$search}%")
                  ->orWhereHas('feeType', function ($sq) use ($search) {
                      $sq->where('fee_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('branch', function ($sq) use ($search) {
                      $sq->where('branch_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $perPage = $request->get('per_page', 10);
        $structures = $query->latest()->paginate($perPage);

        return response()->json($structures);
    }

    private function getDataTablesStructures(Request $request)
    {
        $query = FeeStructure::with(['academicYear', 'branch', 'class', 'feeType']);

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('amount', 'like', "%{$search}%")
                  ->orWhereHas('feeType', function ($sq) use ($search) {
                      $sq->where('fee_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $totalData = $query->count();

        $orderColumn = $request->input('order.0.column', 0);
        $orderDir    = $request->input('order.0.dir', 'desc');
        $columns     = ['id', 'academic_year_id', 'branch_id', 'class_id', 'fee_type_id', 'amount', 'is_active'];

        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }

        $start      = $request->input('start', 0);
        $length     = $request->input('length', 10);
        $structures = $query->skip($start)->take($length)->get();

        $data = $structures->map(function ($struct, $index) use ($start) {
            return [
                'DT_RowIndex'   => $start + $index + 1,
                'id'            => $struct->id,
                'academic_year' => $struct->academicYear?->year_name ?? '-',
                'branch_name'   => $struct->branch?->branch_name ?? '-',
                'class_name'    => $struct->class?->class_name ?? '-',
                'fee_type'      => $struct->feeType?->fee_name ?? '-',
                'amount'        => number_format($struct->amount, 2),
                'due_day'       => $struct->due_day ? 'Day ' . $struct->due_day : '-',
                'is_active'     => $struct->is_active
                    ? '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Active</span>'
                    : '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">Inactive</span>',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <button onclick=\'editStructure(' . json_encode(['id' => $struct->id]) . ')\' class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit
                        </button>
                        <button onclick="deleteStructure(' . $struct->id . ')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
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
        try {
            $validated = $request->validate([
                'academic_year_id' => 'required|exists:academic_years,id',
                'branch_id'        => 'required|exists:branches,id',
                'class_id'         => 'required|exists:classes,id',
                'fee_type_id'      => 'required|exists:fee_types,id',
                'amount'           => 'required|numeric|min:0',
                'due_day'          => 'nullable|integer|min:1|max:31',
                'effective_from'   => 'nullable|date',
                'effective_to'     => 'nullable|date|after_or_equal:effective_from',
                'is_active'        => 'boolean',
            ], [
                'academic_year_id.required' => 'Please select an academic year',
                'branch_id.required' => 'Please select a branch',
                'class_id.required' => 'Please select a class',
                'fee_type_id.required' => 'Please select a fee type',
                'amount.required' => 'Amount is required',
                'amount.numeric' => 'Amount must be a valid number',
                'due_day.integer' => 'Due day must be a number',
                'effective_to.after_or_equal' => 'Effective to date must be equal to or after effective from date',
            ]);

            // Check for duplicate before creating
            $exists = FeeStructure::where('academic_year_id', $validated['academic_year_id'])
                ->where('branch_id', $validated['branch_id'])
                ->where('class_id', $validated['class_id'])
                ->where('fee_type_id', $validated['fee_type_id'])
                ->exists();

            if ($exists) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'A fee structure already exists for this combination (Academic Year + Branch + Class + Fee Type). Please edit the existing record instead.');
            }

            $validated['created_by'] = auth()->id();

            FeeStructure::create($validated);

            return redirect()->route('fee-structures.index')
                ->with('success', 'Fee structure created successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', 'Please correct the errors below.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create fee structure: ' . $e->getMessage());
        }
    }

    public function update(Request $request, FeeStructure $feeStructure)
    {
        try {
            $validated = $request->validate([
                'academic_year_id' => 'required|exists:academic_years,id',
                'branch_id'        => 'required|exists:branches,id',
                'class_id'         => 'required|exists:classes,id',
                'fee_type_id'      => 'required|exists:fee_types,id',
                'amount'           => 'required|numeric|min:0',
                'due_day'          => 'nullable|integer|min:1|max:31',
                'effective_from'   => 'nullable|date',
                'effective_to'     => 'nullable|date|after_or_equal:effective_from',
                'is_active'        => 'boolean',
            ], [
                'academic_year_id.required' => 'Please select an academic year',
                'branch_id.required' => 'Please select a branch',
                'class_id.required' => 'Please select a class',
                'fee_type_id.required' => 'Please select a fee type',
                'amount.required' => 'Amount is required',
                'amount.numeric' => 'Amount must be a valid number',
                'due_day.integer' => 'Due day must be a number',
                'effective_to.after_or_equal' => 'Effective to date must be equal to or after effective from date',
            ]);

            $feeStructure->update($validated);

            return redirect()->route('fee-structures.index')
                ->with('success', 'Fee structure updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', 'Please correct the errors below.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update fee structure: ' . $e->getMessage());
        }
    }

    public function destroy(FeeStructure $feeStructure)
    {
        try {
            $feeStructure->delete();

            return back()->with('success', 'Fee structure deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete fee structure: ' . $e->getMessage());
        }
    }

    /**
     * Sync a single fee structure to all currently enrolled students in its class.
     * Called manually (e.g. for existing fee structures created before this feature).
     */
    public function syncToStudents(FeeStructure $feeStructure)
    {
        try {
            $count = $feeStructure->syncToEnrolledStudents();
            return back()->with('success', "Fee structure synced to {$count} students successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Sync ALL active fee structures to their enrolled students.
     * Run once after deploying this feature to backfill existing data.
     */
    public function syncAllToStudents()
    {
        try {
            $structures = FeeStructure::active()->get();
            $total = 0;
            foreach ($structures as $structure) {
                $total += $structure->syncToEnrolledStudents();
            }
            return back()->with('success', "All fee structures synced! {$total} student-fee assignments created/updated.");
        } catch (\Exception $e) {
            return back()->with('error', 'Sync all failed: ' . $e->getMessage());
        }
    }

    public function dropdown(Request $request)
    {
        $query = FeeStructure::active()
            ->with(['feeType:id,fee_name', 'class:id,class_name'])
            ->select('id', 'fee_type_id', 'class_id', 'amount');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        return response()->json($query->get());
    }
}
