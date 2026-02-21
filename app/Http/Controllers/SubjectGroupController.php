<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\SubjectGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class SubjectGroupController extends Controller
{
    /**
     * Display a listing of subject groups
     */
    public function index(Request $request)
    {
        // Mobile pagination request (from axios)
        if ($request->ajax() && $request->has('mobile')) {
            $perPage = $request->get('per_page', 10);
            
            $query = SubjectGroup::query()->withCount('classSubjects');

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('group_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Order by name
            $query->orderBy('group_name');

            // Return paginated response
            return response()->json($query->paginate($perPage));
        }

        // DataTables AJAX request
        if ($request->has('draw') && $request->has('columns')) {
            $query = SubjectGroup::query()
                ->withCount('classSubjects');

            // Apply filters from DataTables
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('description', function ($group) {
                    return $group->description 
                        ? '<span class="text-sm text-gray-600">' . Str::limit($group->description, 50) . '</span>'
                        : '<span class="text-gray-400">—</span>';
                })
                // ✅ Show subjects count with breakdown
                ->addColumn('subjects_count', function ($group) {
                    $subjects = $group->subjects();
                    $total = $subjects->count();
                    $compulsory = $subjects->where('is_compulsory', true)->count();
                    $optional = $total - $compulsory;
                    
                    if ($total === 0) {
                        return '<span class="text-gray-400 text-xs">No subjects</span>';
                    }
                    
                    return '
                        <div class="flex flex-col gap-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                ' . $total . ' Total
                            </span>
                            <div class="flex gap-1 text-xs">
                                <span class="text-purple-600">' . $compulsory . ' Compulsory</span>
                                <span class="text-gray-400">|</span>
                                <span class="text-gray-600">' . $optional . ' Optional</span>
                            </div>
                        </div>
                    ';
                })
            
                ->editColumn('is_active', function ($group) {
                    if ($group->is_active) {
                        return '
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3"/>
                                </svg>
                                Active
                            </span>
                        ';
                    } else {
                        return '
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3"/>
                                </svg>
                                Inactive
                            </span>
                        ';
                    }
                })
                ->addColumn('action', function ($group) {
                    // ✅ Get subject IDs as array for edit
                    $subjectIds = [];
                    if ($group->subject_ids) {
                        $subjectIds = is_array($group->subject_ids) 
                            ? $group->subject_ids 
                            : explode(',', $group->subject_ids);
                    }
                    
                    return '
                        <div class="flex items-center gap-2 justify-center">
                            <button onclick="viewGroupSubjects(' . $group->id . ')"
                                    class="text-green-600 hover:text-green-900 transition"
                                    title="View Subjects">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                            <button onclick=\'editSubjectGroup(' . json_encode([
                                'id' => $group->id,
                                'group_name' => $group->group_name,
                                'description' => $group->description,
                                'subject_ids' => $subjectIds,
                                'is_active' => $group->is_active
                            ]) . ')\' 
                               class="text-blue-600 hover:text-blue-900 transition"
                               title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button onclick="deleteSubjectGroup(' . $group->id . ')" 
                                    class="text-red-600 hover:text-red-900 transition"
                                    title="Delete">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['description', 'subjects_count', 'is_active', 'action'])
                ->make(true);
        }

        // ✅ FIX: Pass subjects to Inertia
        $subjects = Subject::active()
            ->select('id', 'subject_name', 'subject_code', 'is_compulsory')
            ->orderBy('is_compulsory', 'desc')
            ->orderBy('subject_name')
            ->get();

        return Inertia::render('SubjectGroups/Index', [
            'subjects' => $subjects
        ]);
    }

    /**
     * Show the form for creating a new subject group (Not used - using index page form)
     */
    public function create()
    {
        // Not used since we're using inline form on index page
        // But keeping for resource controller compatibility
        $subjects = Subject::active()
            ->select('id', 'subject_name', 'subject_code', 'is_compulsory')
            ->orderBy('is_compulsory', 'desc')
            ->orderBy('subject_name')
            ->get();

        return Inertia::render('SubjectGroups/Create', [
            'subjects' => $subjects
        ]);
    }

    /**
     * Store a newly created subject group
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_name' => 'required|string|max:100|unique:subject_groups,group_name',
            'description' => 'nullable|string|max:500',
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'exists:subjects,id',
            'is_active' => 'boolean',
        ], [
            'group_name.required' => 'Group name is required',
            'group_name.max' => 'Group name cannot exceed 100 characters',
            'group_name.unique' => 'This group name already exists',
            'description.max' => 'Description cannot exceed 500 characters',
            'subject_ids.array' => 'Subject IDs must be an array',
            'subject_ids.*.exists' => 'One or more selected subjects do not exist',
        ]);

        // ✅ Convert array to comma-separated string for storage
        if (isset($validated['subject_ids']) && is_array($validated['subject_ids'])) {
            $validated['subject_ids'] = implode(',', $validated['subject_ids']);
        } else {
            $validated['subject_ids'] = null;
        }

        // Set default value for is_active if not provided
        $validated['is_active'] = $validated['is_active'] ?? true;

        SubjectGroup::create($validated);

        return back()->with('success', 'Subject group created successfully!');
    }

    /**
     * Display the specified subject group (Not used)
     */
    public function show(SubjectGroup $subjectGroup)
    {
        // Not used - keeping for resource controller compatibility
        $subjects = $subjectGroup->subjects();
        
        return Inertia::render('SubjectGroups/Show', [
            'subjectGroup' => $subjectGroup,
            'subjects' => $subjects
        ]);
    }

    /**
     * Show the form for editing the specified subject group (Not used - using index page form)
     */
    public function edit(SubjectGroup $subjectGroup)
    {
        // Not used since we're using inline form on index page
        // But keeping for resource controller compatibility
        $subjects = Subject::active()
            ->select('id', 'subject_name', 'subject_code', 'is_compulsory')
            ->orderBy('is_compulsory', 'desc')
            ->orderBy('subject_name')
            ->get();

        // ✅ Convert subject_ids to array for frontend
        $selectedSubjectIds = [];
        if ($subjectGroup->subject_ids) {
            $selectedSubjectIds = is_array($subjectGroup->subject_ids) 
                ? $subjectGroup->subject_ids 
                : explode(',', $subjectGroup->subject_ids);
            $selectedSubjectIds = array_map('intval', $selectedSubjectIds);
        }

        return Inertia::render('SubjectGroups/Edit', [
            'subjectGroup' => [
                'id' => $subjectGroup->id,
                'group_name' => $subjectGroup->group_name,
                'description' => $subjectGroup->description,
                'subject_ids' => $selectedSubjectIds,
                'is_active' => $subjectGroup->is_active,
            ],
            'subjects' => $subjects
        ]);
    }

    /**
     * Update the specified subject group
     */
    public function update(Request $request, SubjectGroup $subjectGroup)
    {
        $validated = $request->validate([
            'group_name' => 'required|string|max:100|unique:subject_groups,group_name,' . $subjectGroup->id,
            'description' => 'nullable|string|max:500',
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'exists:subjects,id',
            'is_active' => 'boolean',
        ], [
            'group_name.required' => 'Group name is required',
            'group_name.max' => 'Group name cannot exceed 100 characters',
            'group_name.unique' => 'This group name already exists',
            'description.max' => 'Description cannot exceed 500 characters',
            'subject_ids.array' => 'Subject IDs must be an array',
            'subject_ids.*.exists' => 'One or more selected subjects do not exist',
        ]);

        // ✅ Convert array to comma-separated string for storage
        if (isset($validated['subject_ids']) && is_array($validated['subject_ids'])) {
            $validated['subject_ids'] = implode(',', $validated['subject_ids']);
        } else {
            $validated['subject_ids'] = null;
        }

        $subjectGroup->update($validated);

        return back()->with('success', 'Subject group updated successfully!');
    }

    /**
     * Remove the specified subject group
     */
    public function destroy(SubjectGroup $subjectGroup)
    {
        // Check if group is assigned to any class subjects
        $subjectsCount = $subjectGroup->classSubjects()->count();
        if ($subjectsCount > 0) {
            return back()->with('error', "Cannot delete group. It has {$subjectsCount} subject(s) assigned!");
        }

        // Check if group is assigned to any student enrollments
        $enrollmentsCount = $subjectGroup->studentEnrollments()->count();
        if ($enrollmentsCount > 0) {
            return back()->with('error', "Cannot delete group. It has {$enrollmentsCount} student(s) enrolled!");
        }

        $subjectGroup->delete();

        return back()->with('success', 'Subject group deleted successfully!');
    }

    /**
     * Get subject groups for dropdown (API endpoint)
     */
    public function dropdown()
    {
        $groups = SubjectGroup::active()
            ->select('id', 'group_name', 'description', 'subject_ids')
            ->orderBy('group_name')
            ->get();

        return response()->json($groups);
    }

    /**
     * ✅ Get subjects for a specific group (API endpoint)
     */
    public function getGroupSubjects($id)
    {
        $group = SubjectGroup::findOrFail($id);
        
        $subjects = $group->subjects();
        
        $compulsorySubjects = $subjects->where('is_compulsory', true)->values();
        $optionalSubjects = $subjects->where('is_compulsory', false)->values();
        
        return response()->json([
            'group' => [
                'id' => $group->id,
                'group_name' => $group->group_name,
                'description' => $group->description,
                'is_active' => $group->is_active,
            ],
            'compulsory_subjects' => $compulsorySubjects,
            'optional_subjects' => $optionalSubjects,
            'all_subjects' => $subjects,
            'subjects_count' => [
                'total' => $subjects->count(),
                'compulsory' => $compulsorySubjects->count(),
                'optional' => $optionalSubjects->count(),
            ]
        ]);
    }
}