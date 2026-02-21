<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\BranchClass;
use App\Models\Classes;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class BranchClassController extends Controller
{
    /**
     * Display a listing of branch classes
     */
    public function index(Request $request)
    {
        // Check if it's a mobile request
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileBranchClasses($request);
        }
        
        // DataTables AJAX request
        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesBranchClasses($request);
        }

        // For initial Inertia page load
        $branches = Branch::active()->select('id', 'branch_name')->orderBy('branch_name')->get();
        $academicYears = AcademicYear::select('id', 'year_name')->orderBy('year_name', 'desc')->get();
        $classes = Classes::active()->select('id', 'class_name')->ordered()->get();

        return Inertia::render('BranchClasses/Index', [
            'branches' => $branches,
            'academicYears' => $academicYears,
            'classes' => $classes,
        ]);
    }

    private function getMobileBranchClasses(Request $request)
    {
        $query = BranchClass::with(['branch', 'academicYear', 'class']);
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('branch', function($q) use ($search) {
                $q->where('branch_name', 'like', "%{$search}%");
            })->orWhereHas('class', function($q) use ($search) {
                $q->where('class_name', 'like', "%{$search}%");
            });
        }
        
        // Apply branch filter
        if ($request->filled('branch_id')) {
            $query->forBranch($request->branch_id);
        }
        
        // Apply academic year filter
        if ($request->filled('academic_year_id')) {
            $query->forYear($request->academic_year_id);
        }
        
        // Apply class filter
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        
        // Apply is_active filter
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        // Paginate
        $perPage = $request->get('per_page', 10);
        $branchClasses = $query->latest()->paginate($perPage);
        
        return response()->json($branchClasses);
    }

    private function getDataTablesBranchClasses(Request $request)
    {
        $query = BranchClass::with(['branch', 'academicYear', 'class'])
            ->withCount(['classSections', 'classSubjects', 'studentEnrollments']);
        
        // Apply search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->whereHas('branch', function($branchQuery) use ($search) {
                    $branchQuery->where('branch_name', 'like', "%{$search}%");
                })
                ->orWhereHas('class', function($classQuery) use ($search) {
                    $classQuery->where('class_name', 'like', "%{$search}%");
                })
                ->orWhereHas('academicYear', function($yearQuery) use ($search) {
                    $yearQuery->where('year_name', 'like', "%{$search}%");
                });
            });
        }
        
        // Apply filters
        if ($request->filled('branch_id')) {
            $query->forBranch($request->branch_id);
        }
        
        if ($request->filled('academic_year_id')) {
            $query->forYear($request->academic_year_id);
        }
        
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        // Get total count
        $totalData = $query->count();
        
        // Apply sorting
        $orderColumn = $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = ['id', 'branch_id', 'academic_year_id', 'class_id', 'is_active', 'created_at'];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }
        
        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $branchClasses = $query->skip($start)->take($length)->get();
        
        // Format data
        $data = $branchClasses->map(function($branchClass, $index) use ($start) {
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $branchClass->id,
                'branch' => '<div class="font-medium text-gray-900">' . $branchClass->branch->branch_name . '</div>
                            <div class="text-sm text-gray-500">' . $branchClass->branch->location . '</div>',
                'academic_year' => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">' 
                                   . $branchClass->academicYear->year_name . '</span>',
                'class' => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800">' 
                           . $branchClass->class->class_name . '</span>',
                'stats' => '<div class="flex flex-col gap-1 text-xs">
                            <span class="text-gray-600">Sections: <strong>' . $branchClass->class_sections_count . '</strong></span>
                            <span class="text-gray-600">Subjects: <strong>' . $branchClass->class_subjects_count . '</strong></span>
                            <span class="text-gray-600">Students: <strong>' . $branchClass->student_enrollments_count . '</strong></span>
                            </div>',
                'is_active' => $branchClass->is_active 
                    ? '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Active</span>' 
                    : '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">Inactive</span>',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <a href="' . route('branch-classes.show', $branchClass->id) . '" class="text-gray-600 hover:text-gray-800" title="View">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </a>
                        <a href="' . route('branch-classes.edit', $branchClass->id) . '" class="text-blue-600 hover:text-blue-800" title="Edit">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        <button onclick="deleteBranchClass(' . $branchClass->id . ')" class="text-red-600 hover:text-red-800" title="Delete">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                '
            ];
        });
        
        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalData,
            'data' => $data
        ]);
    }

    /**
     * Show the form for creating a new branch class
     */
    public function create()
    {
        $branches = Branch::active()->select('id', 'branch_name', 'location')->orderBy('branch_name')->get();
        $academicYears = AcademicYear::select('id', 'year_name', 'is_active')->orderBy('year_name', 'desc')->get();
        $classes = Classes::active()->select('id', 'class_name', 'display_order')->ordered()->get();

        return Inertia::render('BranchClasses/Create', [
            'branches' => $branches,
            'academicYears' => $academicYears,
            'classes' => $classes,
        ]);
    }

    /**
     * Store a newly created branch class
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'is_active' => 'boolean',
        ], [
            'branch_id.required' => 'Branch is required',
            'branch_id.exists' => 'Selected branch does not exist',
            'academic_year_id.required' => 'Academic year is required',
            'academic_year_id.exists' => 'Selected academic year does not exist',
            'class_id.required' => 'Class is required',
            'class_id.exists' => 'Selected class does not exist',
        ]);

        // Check for duplicate entry
        $exists = BranchClass::where('branch_id', $validated['branch_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('class_id', $validated['class_id'])
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['error' => 'This class already exists for the selected branch and academic year!'])
                ->withInput();
        }

        BranchClass::create($validated);

        return redirect()
            ->route('branch-classes.index')
            ->with('success', 'Branch class created successfully!');
    }

    /**
     * Display the specified branch class
     */
    public function show(BranchClass $branchClass)
    {
        $branchClass->load([
            'branch',
            'academicYear',
            'class',
            'classSections.section',
            'classSubjects.subject',
            'studentEnrollments.student'
        ]);

        return Inertia::render('BranchClasses/Show', [
            'branchClass' => $branchClass,
            'stats' => [
                'total_sections' => $branchClass->classSections()->count(),
                'total_subjects' => $branchClass->classSubjects()->count(),
                'total_students' => $branchClass->studentEnrollments()->count(),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified branch class
     */
    public function edit(BranchClass $branchClass)
    {
        $branches = Branch::active()->select('id', 'branch_name', 'location')->orderBy('branch_name')->get();
        $academicYears = AcademicYear::select('id', 'year_name', 'is_active')->orderBy('year_name', 'desc')->get();
        $classes = Classes::active()->select('id', 'class_name', 'display_order')->ordered()->get();

        return Inertia::render('BranchClasses/Edit', [
            'branchClass' => $branchClass->load(['branch', 'academicYear', 'class']),
            'branches' => $branches,
            'academicYears' => $academicYears,
            'classes' => $classes,
        ]);
    }

    /**
     * Update the specified branch class
     */
    public function update(Request $request, BranchClass $branchClass)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'is_active' => 'boolean',
        ], [
            'branch_id.required' => 'Branch is required',
            'branch_id.exists' => 'Selected branch does not exist',
            'academic_year_id.required' => 'Academic year is required',
            'academic_year_id.exists' => 'Selected academic year does not exist',
            'class_id.required' => 'Class is required',
            'class_id.exists' => 'Selected class does not exist',
        ]);

        // Check for duplicate entry (excluding current record)
        $exists = BranchClass::where('branch_id', $validated['branch_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('class_id', $validated['class_id'])
            ->where('id', '!=', $branchClass->id)
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['error' => 'This class already exists for the selected branch and academic year!'])
                ->withInput();
        }

        $branchClass->update($validated);

        return redirect()
            ->route('branch-classes.index')
            ->with('success', 'Branch class updated successfully!');
    }

    /**
     * Remove the specified branch class
     */
    public function destroy(BranchClass $branchClass)
    {
        // Check for related records
        $sectionCount = $branchClass->classSections()->count();
        $subjectCount = $branchClass->classSubjects()->count();
        $studentCount = $branchClass->studentEnrollments()->count();

        if ($sectionCount > 0 || $subjectCount > 0 || $studentCount > 0) {
            $message = 'Cannot delete this branch class. It has ';
            $parts = [];
            
            if ($sectionCount > 0) {
                $parts[] = "{$sectionCount} section(s)";
            }
            if ($subjectCount > 0) {
                $parts[] = "{$subjectCount} subject(s)";
            }
            if ($studentCount > 0) {
                $parts[] = "{$studentCount} student(s)";
            }
            
            $message .= implode(', ', $parts) . '!';
            
            return back()->with('error', $message);
        }

        $branchClass->delete();

        return redirect()
            ->route('branch-classes.index')
            ->with('success', 'Branch class deleted successfully!');
    }

    /**
     * Get branch classes for dropdown (API endpoint)
     */
    public function dropdown(Request $request)
    {
        $query = BranchClass::active()
            ->with(['branch', 'class', 'academicYear']);

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->forBranch($request->branch_id);
        }

        // Filter by academic year
        if ($request->filled('academic_year_id')) {
            $query->forYear($request->academic_year_id);
        } else {
            // Default to current year if not specified
            $query->currentYear();
        }

        // Filter by class
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        $branchClasses = $query->get()->map(function($branchClass) {
            return [
                'id' => $branchClass->id,
                'branch_name' => $branchClass->branch->branch_name,
                'class_name' => $branchClass->class->class_name,
                'year_name' => $branchClass->academicYear->year_name,
                'display_name' => $branchClass->branch->branch_name . ' - ' . $branchClass->class->class_name . ' (' . $branchClass->academicYear->year_name . ')',
            ];
        });

        return response()->json($branchClasses);
    }

    /**
     * Get classes for a specific branch and academic year
     */
    public function getClassesByBranchAndYear(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $branchClasses = BranchClass::active()
            ->forBranch($request->branch_id)
            ->forYear($request->academic_year_id)
            ->with('class')
            ->get()
            ->map(function($branchClass) {
                return [
                    'id' => $branchClass->id,
                    'class_id' => $branchClass->class_id,
                    'class_name' => $branchClass->class->class_name,
                ];
            });

        return response()->json($branchClasses);
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(BranchClass $branchClass)
    {
        $branchClass->update([
            'is_active' => !$branchClass->is_active
        ]);

        $status = $branchClass->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Branch class {$status} successfully!",
            'is_active' => $branchClass->is_active
        ]);
    }
}