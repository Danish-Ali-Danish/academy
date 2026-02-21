<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class SectionController extends Controller
{
    /**
     * Display a listing of sections
     */
    public function index(Request $request)
    {
        // Check if it's a mobile request
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileSections($request);
        }

        // DataTables AJAX request
        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesSections($request);
        }

        // For initial Inertia page load
        return Inertia::render('Sections/Index');
    }

    private function getMobileSections(Request $request)
    {
        $query = Section::withCount(['classSections']);
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('section_name', 'like', "%{$search}%");
        }
        
        // Apply is_active filter
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        // Order by section_name
        $query->orderBy('section_name');
        
        // Paginate
        $perPage = $request->get('per_page', 10);
        $sections = $query->paginate($perPage);
        
        return response()->json($sections);
    }

    private function getDataTablesSections(Request $request)
    {
        $query = Section::withCount(['classSections']);
        
        // Apply search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where('section_name', 'like', "%{$search}%");
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
        $columns = ['id', 'section_name', 'is_active'];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }
        
        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $sections = $query->skip($start)->take($length)->get();
        
        // Format data
        $data = $sections->map(function($section, $index) use ($start) {
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $section->id,
                'section_name' => $section->section_name,
                'class_sections_count' => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">' . $section->class_sections_count . ' classes</span>',
                'is_active' => $section->is_active 
                    ? '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Active</span>' 
                    : '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">Inactive</span>',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <button onclick=\'editSection(' . json_encode([
                            'id' => $section->id,
                            'section_name' => $section->section_name,
                            'is_active' => $section->is_active
                        ]) . ')\' class="text-blue-600 hover:text-blue-800" title="Edit">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button onclick="deleteSection(' . $section->id . ')" class="text-red-600 hover:text-red-800" title="Delete">
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
     * Show the form for creating a new section
     */
    public function create()
    {
        return Inertia::render('Sections/Create');
    }

    /**
     * Store a newly created section
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_name' => 'required|string|max:255|unique:sections,section_name',
            'is_active' => 'boolean',
        ], [
            'section_name.required' => 'Section name is required',
            'section_name.unique' => 'This section name already exists',
            'section_name.max' => 'Section name cannot exceed 255 characters',
        ]);

        Section::create($validated);

        return back()->with('success', 'Section created successfully!');
    }

    /**
     * Display the specified section
     */
    public function show(Section $section)
    {
        $section->load(['classSections.branchClass']);

        return Inertia::render('Sections/Show', [
            'section' => $section,
            'stats' => [
                'total_classes' => $section->classSections()->count(),
          
            ]
        ]);
    }

    /**
     * Show the form for editing the specified section
     */
    public function edit(Section $section)
    {
        return Inertia::render('Sections/Edit', [
            'section' => $section
        ]);
    }

    /**
     * Update the specified section
     */
    public function update(Request $request, Section $section)
    {
        $validated = $request->validate([
            'section_name' => 'required|string|max:255|unique:sections,section_name,' . $section->id,
            'is_active' => 'boolean',
        ], [
            'section_name.required' => 'Section name is required',
            'section_name.unique' => 'This section name already exists',
        ]);

        $section->update($validated);

        return back()->with('success', 'Section updated successfully!');
    }

    /**
     * Remove the specified section
     */
    public function destroy(Section $section)
    {
        // Check if section has class sections
        $classSectionCount = $section->classSections()->count();
        if ($classSectionCount > 0) {
            return back()->with('error', "Cannot delete section. It is assigned to {$classSectionCount} class(es)!");
        }

        // Check if section has student enrollments
        // $enrollmentCount = $section->studentEnrollments()->count();
        // if ($enrollmentCount > 0) {
        //     return back()->with('error', "Cannot delete section. It has {$enrollmentCount} student enrollment(s)!");
        // }

        $section->delete();

        return back()->with('success', 'Section deleted successfully!');
    }

    /**
     * Get sections for dropdown (API endpoint)
     */
    public function dropdown(Request $request)
    {
        $query = Section::active();

        $sections = $query
            ->select('id', 'section_name')
            ->orderBy('section_name')
            ->get();

        return response()->json($sections);
    }
}