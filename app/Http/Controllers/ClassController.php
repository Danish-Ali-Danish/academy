<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class ClassController extends Controller
{
    /**
     * Display a listing of classes
     */
    public function index(Request $request)
    {
        // Check if it's a mobile request
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileClasses($request);
        }

        // DataTables AJAX request
        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesClasses($request);
        }

        // For initial Inertia page load
        return Inertia::render('Classes/Index');
    }

    private function getMobileClasses(Request $request)
    {
        $query = Classes::withCount('branchClasses');
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('class_name', 'like', "%{$search}%");
        }
        
        // Apply is_active filter
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        // Order by display_order
        $query->ordered();
        
        // Paginate
        $perPage = $request->get('per_page', 10);
        $classes = $query->paginate($perPage);
        
        return response()->json($classes);
    }

    private function getDataTablesClasses(Request $request)
    {
        $query = Classes::withCount('branchClasses');
        
        // Apply search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where('class_name', 'like', "%{$search}%");
        }
        
        // Apply filters
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        // Get total count
        $totalData = $query->count();
        
        // Apply sorting
        $orderColumn = $request->input('order.0.column', 2);
        $orderDir = $request->input('order.0.dir', 'asc');
        $columns = ['id', 'class_name', 'display_order', 'is_active'];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        } else {
            $query->ordered();
        }
        
        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $classes = $query->skip($start)->take($length)->get();
        
        // Format data
        $data = $classes->map(function($class, $index) use ($start) {
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $class->id,
                'class_name' => $class->class_name,
                'display_order' => $class->display_order ?? '-',
                'branch_classes_count' => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">' . $class->branch_classes_count . ' branches</span>',
                'is_active' => $class->is_active 
                    ? '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Active</span>' 
                    : '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">Inactive</span>',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <button onclick=\'editClass(' . json_encode([
                            'id' => $class->id,
                            'class_name' => $class->class_name,
                            'display_order' => $class->display_order,
                            'is_active' => $class->is_active
                        ]) . ')\' class="text-blue-600 hover:text-blue-800" title="Edit">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button onclick="deleteClass(' . $class->id . ')" class="text-red-600 hover:text-red-800" title="Delete">
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
     * Show the form for creating a new class
     */
    public function create()
    {
        return Inertia::render('Classes/Create');
    }

    /**
     * Store a newly created class
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_name' => 'required|string|max:255|unique:classes,class_name',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ], [
            'class_name.required' => 'Class name is required',
            'class_name.unique' => 'This class name already exists',
            'class_name.max' => 'Class name cannot exceed 255 characters',
            'display_order.integer' => 'Display order must be a number',
            'display_order.min' => 'Display order cannot be negative',
        ]);

        Classes::create($validated);

        return back()->with('success', 'Class created successfully!');
    }

    /**
     * Display the specified class
     */
    public function show(Classes $class)
    {
        $class->load('branchClasses.branch');

        return Inertia::render('Classes/Show', [
            'class' => $class,
            'stats' => [
                'total_branches' => $class->branchClasses()->count(),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified class
     */
    public function edit(Classes $class)
    {
        return Inertia::render('Classes/Edit', [
            'class' => $class
        ]);
    }

    /**
     * Update the specified class
     */
    public function update(Request $request, Classes $class)
    {
        $validated = $request->validate([
            'class_name' => 'required|string|max:255|unique:classes,class_name,' . $class->id,
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ], [
            'class_name.required' => 'Class name is required',
            'class_name.unique' => 'This class name already exists',
            'display_order.integer' => 'Display order must be a number',
        ]);

        $class->update($validated);

        return back()->with('success', 'Class updated successfully!');
    }

    /**
     * Remove the specified class
     */
    public function destroy(Classes $class)
    {
        // Check if class has branch classes
        $branchClassCount = $class->branchClasses()->count();
        if ($branchClassCount > 0) {
            return back()->with('error', "Cannot delete class. It is assigned to {$branchClassCount} branch(es)!");
        }

        $class->delete();

        return back()->with('success', 'Class deleted successfully!');
    }

    /**
     * Get classes for dropdown (API endpoint)
     */
    public function dropdown(Request $request)
    {
        $query = Classes::active();

        $classes = $query
            ->select('id', 'class_name', 'display_order')
            ->ordered()
            ->get();

        return response()->json($classes);
    }

    /**
     * Reorder classes
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:classes,id',
            'orders.*.display_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['orders'] as $order) {
            Classes::where('id', $order['id'])
                ->update(['display_order' => $order['display_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Classes reordered successfully!'
        ]);
    }
}