<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Classes;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    /**
     * Display a listing of branches
     */
    public function index(Request $request)
    {
        // Check if it's a mobile request
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileBranches($request);
        }
        
        // DataTables AJAX request
        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesBranches($request);
        }

        // For initial Inertia page load
        return Inertia::render('Branches/Index');
    }

    /**
     * Show the form for creating a new branch
     */
    public function create()
    {
        $classes = Classes::active()
            ->ordered()
            ->select('id', 'class_name')
            ->get();

        return Inertia::render('Branches/Create', [
            'classes' => $classes
        ]);
    }

    /**
     * Show the form for editing the specified branch
     */
    public function edit(Branch $branch)
    {
        // Load classes for the dropdown
        $classes = Classes::active()
            ->ordered()
            ->select('id', 'class_name')
            ->get();

        // Load branch with its classes
        $branch->load(['classes' => function($q) {
            $q->select('classes.id', 'class_name')
              ->wherePivot('is_active', true);
        }]);

        // Prepare branch data with class_ids
        $branchData = [
            'id' => $branch->id,
            'branch_name' => $branch->branch_name,
            'location' => $branch->location,
            'phone' => $branch->phone,
            'is_active' => $branch->is_active,
            'class_ids' => $branch->classes->pluck('id')->toArray()
        ];

        return Inertia::render('Branches/Edit', [
            'branch' => $branchData,
            'classes' => $classes
        ]);
    }

    private function getMobileBranches(Request $request)
    {
        $query = Branch::with(['classes' => function($q) {
            $q->select('classes.id', 'class_name')
              ->wherePivot('is_active', true);
        }]);
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('branch_name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Apply is_active filter
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        // Paginate
        $perPage = $request->get('per_page', 10);
        $branches = $query->latest()->paginate($perPage);
        
        // Add class_ids for mobile
        $branches->getCollection()->transform(function ($branch) {
            $branch->class_ids = $branch->classes->pluck('id')->toArray();
            return $branch;
        });
        
        return response()->json($branches);
    }

    private function getDataTablesBranches(Request $request)
    {
        $query = Branch::with(['classes' => function($q) {
            $q->select('classes.id', 'class_name')
              ->wherePivot('is_active', true);
        }]);
        
        // Apply search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->where('branch_name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Apply filters
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        // Get total count
        $totalData = $query->count();
        
        // Apply sorting
        $orderColumn = $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir', 'asc');
        $columns = ['id', 'branch_name', 'location', 'phone', 'is_active'];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }
        
        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $branches = $query->skip($start)->take($length)->get();
        
        // Format data
        $data = $branches->map(function($branch, $index) use ($start) {
            $classCount = $branch->classes->count();
            
            // Format classes count with view button
            $classesCountHtml = '<div class="flex items-center justify-center gap-2">';
            $classesCountHtml .= '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">';
            $classesCountHtml .= $classCount . ' ' . ($classCount === 1 ? 'Class' : 'Classes');
            $classesCountHtml .= '</span>';
            
            if ($classCount > 0) {
                $classesCountHtml .= '<button onclick="viewBranchClasses(' . $branch->id . ')" class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-600 bg-green-50 rounded hover:bg-green-100 transition-colors">';
                $classesCountHtml .= '<svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                $classesCountHtml .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>';
                $classesCountHtml .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
                $classesCountHtml .= '</svg>';
                $classesCountHtml .= 'View';
                $classesCountHtml .= '</button>';
            }
            
            $classesCountHtml .= '</div>';

            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $branch->id,
                'branch_name' => $branch->branch_name,
                'location' => $branch->location,
                'phone' => $branch->phone,
                'classes_count' => $classesCountHtml,
                'is_active' => $branch->is_active 
                    ? '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Active</span>' 
                    : '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">Inactive</span>',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <button onclick=\'editBranch(' . json_encode([
                            'id' => $branch->id
                        ]) . ')\' class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors" title="Edit">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit
                        </button>
                        <button onclick="deleteBranch(' . $branch->id . ')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors" title="Delete">
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
     * Get branch classes for view modal
     */
    public function branchClasses($id)
    {
        $branch = Branch::with(['classes' => function($q) {
            $q->select('classes.id', 'class_name')
              ->wherePivot('is_active', true)
              ->orderBy('class_name');
        }])->findOrFail($id);
        
        return response()->json([
            'branch' => [
                'id' => $branch->id,
                'branch_name' => $branch->branch_name,
                'location' => $branch->location,
                'phone' => $branch->phone,
            ],
            'classes' => $branch->classes,
            'classes_count' => $branch->classes->count(),
        ]);
    }

    /**
     * Store a newly created branch with classes
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'is_active' => 'boolean',
            'class_ids' => 'nullable|array',
            'class_ids.*' => 'exists:classes,id'
        ]);

        DB::beginTransaction();
        try {
            // Create branch
            $branch = Branch::create([
                'branch_name' => $validated['branch_name'],
                'location' => $validated['location'],
                'phone' => $validated['phone'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Attach classes if provided
            if (!empty($validated['class_ids'])) {
                $branch->classes()->attach($validated['class_ids'], [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();
            
            return redirect()->route('branches.index')
                ->with('success', 'Branch created successfully with ' . count($validated['class_ids'] ?? []) . ' classes!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create branch: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified branch with classes
     */
    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'branch_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'is_active' => 'boolean',
            'class_ids' => 'nullable|array',
            'class_ids.*' => 'exists:classes,id'
        ]);

        DB::beginTransaction();
        try {
            // Update branch
            $branch->update([
                'branch_name' => $validated['branch_name'],
                'location' => $validated['location'],
                'phone' => $validated['phone'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Sync classes (remove old, add new)
            if (isset($validated['class_ids'])) {
                $syncData = [];
                foreach ($validated['class_ids'] as $classId) {
                    $syncData[$classId] = [
                        'is_active' => true,
                        'updated_at' => now()
                    ];
                }
                $branch->classes()->sync($syncData);
            } else {
                // If no classes selected, remove all
                $branch->classes()->detach();
            }

            DB::commit();
            
            return redirect()->route('branches.index')
                ->with('success', 'Branch updated successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update branch: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified branch
     */
    public function destroy(Branch $branch)
    {
        DB::beginTransaction();
        try {
            // First detach all classes
            $branch->classes()->detach();
            
            // Then delete branch (soft delete)
            $branch->delete();

            DB::commit();
            return back()->with('success', 'Branch deleted successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete branch: ' . $e->getMessage());
        }
    }

    /**
     * Get branches for dropdown (API endpoint)
     */
    public function dropdown()
    {
        $branches = Branch::active()
            ->with(['classes' => function($q) {
                $q->select('classes.id', 'class_name')
                  ->wherePivot('is_active', true);
            }])
            ->select('id', 'branch_name', 'location')
            ->orderBy('branch_name')
            ->get();

        return response()->json($branches);
    }

    /**
     * Get classes for a specific branch (API endpoint)
     */
    public function getClasses(Branch $branch)
    {
        $classes = $branch->classes()
            ->wherePivot('is_active', true)
            ->select('classes.id', 'class_name', 'display_order')
            ->orderBy('display_order')
            ->get();

        return response()->json($classes);
    }
}