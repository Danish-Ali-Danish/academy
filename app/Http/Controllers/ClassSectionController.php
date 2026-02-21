<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchClass;
use App\Models\ClassSection;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ClassSectionController extends Controller
{
    /**
     * Display a listing of class sections
     */
    public function index(Request $request)
    {
        // Check if it's a mobile request
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileClassSections($request);
        }
        
        // DataTables AJAX request
        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesClassSections($request);
        }

        // For initial Inertia page load - only need branches for filters
        $branches = Branch::active()
            ->select('id', 'branch_name', 'location')
            ->orderBy('branch_name')
            ->get();

        return Inertia::render('ClassSections/Index', [
            'branches' => $branches,
        ]);
    }

    /**
     * Show the form for creating a new class section
     */
    public function create()
    {
        $branches = Branch::active()
            ->select('id', 'branch_name', 'location')
            ->orderBy('branch_name')
            ->get();

        return Inertia::render('ClassSections/Create', [
            'branches' => $branches,
        ]);
    }

    /**
     * Show the form for editing the specified class section
     */
    public function edit(ClassSection $classSection)
    {
        // Load relationships
        $classSection->load([
            'branchClass.branch:id,branch_name,location',
            'branchClass.class:id,class_name',
            'section:id,section_name'
        ]);

        $branches = Branch::active()
            ->select('id', 'branch_name', 'location')
            ->orderBy('branch_name')
            ->get();

        // Get classes for the branch
        $branchClasses = BranchClass::active()
            ->where('branch_id', $classSection->branchClass->branch_id)
            ->with('class:id,class_name')
            ->get()
            ->map(function($bc) {
                return [
                    'id' => $bc->id,
                    'class_id' => $bc->class_id,
                    'class_name' => $bc->class->class_name,
                ];
            });

        // Get all sections (for changing section in edit mode)
        $sections = Section::active()
            ->select('id', 'section_name')
            ->orderBy('section_name')
            ->get();

        return Inertia::render('ClassSections/Edit', [
            'classSection' => [
                'id' => $classSection->id,
                'branch_id' => $classSection->branchClass->branch_id,
                'branch_class_id' => $classSection->branch_class_id,
                'section_id' => $classSection->section_id,
                'capacity' => $classSection->capacity,
                'is_active' => $classSection->is_active,
                'branch_name' => $classSection->branchClass->branch->branch_name,
                'class_name' => $classSection->branchClass->class->class_name,
                'section_name' => $classSection->section->section_name,
            ],
            'branches' => $branches,
            'branchClasses' => $branchClasses,
            'sections' => $sections,
        ]);
    }

    /**
     * Get mobile paginated data
     */
    private function getMobileClassSections(Request $request)
    {
        $query = ClassSection::with([
            'branchClass.branch:id,branch_name',
            'branchClass.class:id,class_name',
            'section:id,section_name'
        ]);
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('branchClass.branch', function($branchQuery) use ($search) {
                    $branchQuery->where('branch_name', 'like', "%{$search}%");
                })
                ->orWhereHas('branchClass.class', function($classQuery) use ($search) {
                    $classQuery->where('class_name', 'like', "%{$search}%");
                })
                ->orWhereHas('section', function($sectionQuery) use ($search) {
                    $sectionQuery->where('section_name', 'like', "%{$search}%");
                });
            });
        }
        
        // Apply branch filter
        if ($request->filled('branch_id')) {
            $query->whereHas('branchClass', function($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }
        
        // Apply branch class filter
        if ($request->filled('branch_class_id')) {
            $query->where('branch_class_id', $request->branch_class_id);
        }
        
        // Apply section filter
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }
        
        // Apply is_active filter
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        // Paginate
        $perPage = $request->get('per_page', 10);
        $classSections = $query->latest()->paginate($perPage);
        
        return response()->json($classSections);
    }

    /**
     * Get DataTables data
     */
    private function getDataTablesClassSections(Request $request)
    {
        $query = ClassSection::with([
            'branchClass.branch:id,branch_name',
            'branchClass.class:id,class_name',
            'section:id,section_name'
        ]);
        
        // Apply search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->whereHas('branchClass.branch', function($branchQuery) use ($search) {
                    $branchQuery->where('branch_name', 'like', "%{$search}%");
                })
                ->orWhereHas('branchClass.class', function($classQuery) use ($search) {
                    $classQuery->where('class_name', 'like', "%{$search}%");
                })
                ->orWhereHas('section', function($sectionQuery) use ($search) {
                    $sectionQuery->where('section_name', 'like', "%{$search}%");
                })
                ->orWhere('capacity', 'like', "%{$search}%");
            });
        }
        
        // Apply filters
        if ($request->filled('branch_id')) {
            $query->whereHas('branchClass', function($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }

        if ($request->filled('branch_class_id')) {
            $query->where('branch_class_id', $request->branch_class_id);
        }
        
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }
        
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        // Get total count
        $totalData = $query->count();
        
        // Apply sorting
        $orderColumn = $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = ['id', 'branch_class_id', 'section_id', 'capacity', 'is_active', 'created_at'];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }
        
        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $classSections = $query->skip($start)->take($length)->get();
        
        // Format data
        $data = $classSections->map(function($classSection, $index) use ($start) {
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $classSection->id,
                'branch' => $classSection->branchClass->branch->branch_name,
                'class' => $classSection->branchClass->class->class_name,
                'branch_class' => '<div class="font-medium text-gray-900">' . $classSection->branchClass->branch->branch_name . '</div>
                                  <div class="text-sm text-gray-500">' . $classSection->branchClass->class->class_name . '</div>',
                'section' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">' 
                            . $classSection->section->section_name . '</span>',
                'capacity' => '<span class="px-3 py-1.5 text-sm font-medium rounded-lg bg-blue-100 text-blue-800">' 
                            . $classSection->capacity . ' seats</span>',
                'is_active' => $classSection->is_active 
                    ? '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Active</span>' 
                    : '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">Inactive</span>',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <a href="' . route('class-sections.edit', $classSection->id) . '" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors" title="Edit">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit
                        </a>
                        <button onclick="deleteClassSection(' . $classSection->id . ')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors" title="Delete">
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
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalData,
            'data' => $data
        ]);
    }

    /**
     * Store a newly created class section (supports multiple sections)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_class_id' => 'required|exists:branch_classes,id',
            'section_ids' => 'required|array|min:1',
            'section_ids.*' => 'required|exists:sections,id',
            'capacity' => 'required|integer|min:1|max:200',
            'is_active' => 'boolean',
        ], [
            'branch_class_id.required' => 'Branch class is required',
            'branch_class_id.exists' => 'Selected branch class does not exist',
            'section_ids.required' => 'At least one section is required',
            'section_ids.min' => 'Please select at least one section',
            'section_ids.*.exists' => 'One or more selected sections do not exist',
            'capacity.required' => 'Capacity is required',
            'capacity.integer' => 'Capacity must be a number',
            'capacity.min' => 'Capacity must be at least 1',
            'capacity.max' => 'Capacity cannot exceed 200',
        ]);

        DB::beginTransaction();
        try {
            $created = 0;
            $skipped = 0;
            $skippedSections = [];

            foreach ($validated['section_ids'] as $sectionId) {
                // Check for duplicate entry
                $exists = ClassSection::where('branch_class_id', $validated['branch_class_id'])
                    ->where('section_id', $sectionId)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    $section = Section::find($sectionId);
                    $skippedSections[] = $section->section_name;
                } else {
                    ClassSection::create([
                        'branch_class_id' => $validated['branch_class_id'],
                        'section_id' => $sectionId,
                        'capacity' => $validated['capacity'],
                        'is_active' => $validated['is_active'] ?? true,
                    ]);
                    $created++;
                }
            }

            DB::commit();

            $messages = [];
            if ($created > 0) {
                $messages[] = "{$created} section(s) assigned successfully!";
            }
            if ($skipped > 0) {
                $messages[] = "{$skipped} section(s) already existed: " . implode(', ', $skippedSections);
            }

            return redirect()->route('class-sections.index')->with('success', implode(' ', $messages));
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to assign sections: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Update the specified class section
     */
    public function update(Request $request, ClassSection $classSection)
    {
        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'capacity' => 'required|integer|min:1|max:200',
            'is_active' => 'boolean',
        ], [
            'section_id.required' => 'Section is required',
            'capacity.required' => 'Capacity is required',
            'capacity.min' => 'Capacity must be at least 1',
            'capacity.max' => 'Capacity cannot exceed 200',
        ]);

        DB::beginTransaction();
        try {
            // Check for duplicate entry (excluding current record)
            $exists = ClassSection::where('branch_class_id', $classSection->branch_class_id)
                ->where('section_id', $validated['section_id'])
                ->where('id', '!=', $classSection->id)
                ->exists();

            if ($exists) {
                return back()
                    ->withErrors(['section_id' => 'This section already exists for the selected class!'])
                    ->withInput();
            }

            $classSection->update($validated);

            DB::commit();
            return redirect()->route('class-sections.index')->with('success', 'Class section updated successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update class section: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified class section
     */
    public function destroy(ClassSection $classSection)
    {
        DB::beginTransaction();
        try {
            $classSection->delete();

            DB::commit();
            return back()->with('success', 'Class section deleted successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete class section: ' . $e->getMessage());
        }
    }

    /**
     * Get branches for dropdown (API endpoint)
     */
    public function getBranches()
    {
        $branches = Branch::active()
            ->select('id', 'branch_name', 'location')
            ->orderBy('branch_name')
            ->get();

        return response()->json($branches);
    }

    /**
     * Get classes by branch (API endpoint)
     */
    public function getClassesByBranch(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        $branchClasses = BranchClass::active()
            ->where('branch_id', $request->branch_id)
            ->with('class:id,class_name')
            ->get()
            ->map(function($bc) {
                return [
                    'id' => $bc->id,
                    'class_id' => $bc->class_id,
                    'class_name' => $bc->class->class_name,
                ];
            });

        return response()->json($branchClasses);
    }

    /**
     * Get available sections for a branch class (excluding already assigned)
     */
    public function getAvailableSections(Request $request)
    {
        $request->validate([
            'branch_class_id' => 'required|exists:branch_classes,id',
        ]);

        // Get all active sections
        $allSections = Section::active()
            ->select('id', 'section_name')
            ->orderBy('section_name')
            ->get();

        // Get already assigned sections for this branch class
        $assignedSectionIds = ClassSection::where('branch_class_id', $request->branch_class_id)
            ->pluck('section_id')
            ->toArray();

        // Filter out assigned sections
        $availableSections = $allSections->filter(function($section) use ($assignedSectionIds) {
            return !in_array($section->id, $assignedSectionIds);
        })->values();

        return response()->json([
            'available' => $availableSections,
            'assigned' => $assignedSectionIds
        ]);
    }

    /**
     * Get sections by branch class (API endpoint) - for edit mode
     */
    public function getSectionsByBranchClass(Request $request)
    {
        $request->validate([
            'branch_class_id' => 'required|exists:branch_classes,id',
        ]);

        $sections = Section::active()
            ->select('id', 'section_name')
            ->orderBy('section_name')
            ->get();

        return response()->json($sections);
    }

    /**
     * Toggle active status (API endpoint)
     */
    public function toggleStatus(ClassSection $classSection)
    {
        $classSection->update([
            'is_active' => !$classSection->is_active
        ]);

        $status = $classSection->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Class section {$status} successfully!",
            'is_active' => $classSection->is_active
        ]);
    }

    /**
     * Update capacity (API endpoint)
     */
    public function updateCapacity(Request $request, ClassSection $classSection)
    {
        $validated = $request->validate([
            'capacity' => 'required|integer|min:1|max:200',
        ], [
            'capacity.required' => 'Capacity is required',
            'capacity.min' => 'Capacity must be at least 1',
            'capacity.max' => 'Capacity cannot exceed 200',
        ]);

        $classSection->update(['capacity' => $validated['capacity']]);

        return response()->json([
            'success' => true,
            'message' => 'Capacity updated successfully!',
            'capacity' => $classSection->capacity,
        ]);
    }

    /**
     * Get capacity statistics (API endpoint)
     */
    public function capacityStats(Request $request)
    {
        $query = ClassSection::active();

        if ($request->filled('branch_class_id')) {
            $query->where('branch_class_id', $request->branch_class_id);
        }

        $sections = $query->get();
        $totalCapacity = $sections->sum('capacity');
        $totalSections = $sections->count();

        return response()->json([
            'total_capacity' => $totalCapacity,
            'total_sections' => $totalSections,
            'average_capacity' => $totalSections > 0 ? round($totalCapacity / $totalSections, 2) : 0,
        ]);
    }
}