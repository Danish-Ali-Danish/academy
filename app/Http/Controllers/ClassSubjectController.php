<?php

namespace App\Http\Controllers;

use App\Models\ClassSubject;
use App\Models\ClassSection;
use App\Models\Subject;
use App\Models\SubjectGroup;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ClassSubjectController extends Controller
{
    /**
     * Display a listing of class subjects
     */
    public function index(Request $request)
    {
        // Check if it's a mobile request
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileClassSubjects($request);
        }
        
        // DataTables AJAX request
        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesClassSubjects($request);
        }

        // For initial Inertia page load
        return Inertia::render('ClassSubjects/Index');
    }

    /**
     * Show the form for creating a new class subject mapping
     */
    public function create()
    {
        // Get all active class sections with their branch and class info
        $classSections = ClassSection::with([
            'branchClass.branch',
            'branchClass.class',
            'section'
        ])
            ->active()
            ->get()
            ->map(function ($cs) {
                return [
                    'id' => $cs->id,
                    'label' => $cs->branchClass->branch->branch_name . ' - ' . 
                              $cs->branchClass->class->class_name . ' - ' . 
                              $cs->section->section_name,
                    'branch_name' => $cs->branchClass->branch->branch_name,
                    'class_name' => $cs->branchClass->class->class_name,
                    'section_name' => $cs->section->section_name,
                ];
            });

        // Get active subjects for individual selection
        $subjects = Subject::active()
            ->select('id', 'subject_name', 'subject_code', 'is_compulsory')
            ->orderBy('is_compulsory', 'desc')
            ->orderBy('subject_name')
            ->get();

        // Get active subject groups
        $subjectGroups = SubjectGroup::active()
            ->select('id', 'group_name', 'description')
            ->orderBy('group_name')
            ->get();

        return Inertia::render('ClassSubjects/Create', [
            'classSections' => $classSections,
            'subjects' => $subjects,
            'subjectGroups' => $subjectGroups
        ]);
    }

    /**
     * Show the form for editing the specified class subject
     */
    public function edit(ClassSubject $classSubject)
    {
        // Load class sections
        $classSections = ClassSection::with([
            'branchClass.branch',
            'branchClass.class',
            'section'
        ])
            ->active()
            ->get()
            ->map(function ($cs) {
                return [
                    'id' => $cs->id,
                    'label' => $cs->branchClass->branch->branch_name . ' - ' . 
                              $cs->branchClass->class->class_name . ' - ' . 
                              $cs->section->section_name,
                    'branch_name' => $cs->branchClass->branch->branch_name,
                    'class_name' => $cs->branchClass->class->class_name,
                    'section_name' => $cs->section->section_name,
                ];
            });

        // Load subjects
        $subjects = Subject::active()
            ->select('id', 'subject_name', 'subject_code', 'is_compulsory')
            ->orderBy('is_compulsory', 'desc')
            ->orderBy('subject_name')
            ->get();

        // Load subject groups
        $subjectGroups = SubjectGroup::active()
            ->select('id', 'group_name', 'description')
            ->orderBy('group_name')
            ->get();

        // Load class subject with relationships
        $classSubject->load([
            'classSection.branchClass.branch',
            'classSection.branchClass.class',
            'classSection.section',
            'subject',
            'subjectGroup'
        ]);

        // Prepare class subject data
        $classSubjectData = [
            'id' => $classSubject->id,
            'class_section_id' => $classSubject->class_section_id,
            'subject_id' => $classSubject->subject_id,
            'subject_group_id' => $classSubject->subject_group_id,
            'is_active' => $classSubject->is_active,
            'mapping_type' => $classSubject->subject_id ? 'individual' : 'group',
        ];

        return Inertia::render('ClassSubjects/Edit', [
            'classSubject' => $classSubjectData,
            'classSections' => $classSections,
            'subjects' => $subjects,
            'subjectGroups' => $subjectGroups
        ]);
    }

    /**
     * Get mobile class subjects list
     */
    private function getMobileClassSubjects(Request $request)
    {
        $query = ClassSubject::with([
            'classSection.branchClass.branch',
            'classSection.branchClass.class',
            'classSection.section',
            'subject',
            'subjectGroup'
        ]);
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('classSection.branchClass.branch', function($q2) use ($search) {
                    $q2->where('branch_name', 'like', "%{$search}%");
                })
                ->orWhereHas('classSection.branchClass.class', function($q2) use ($search) {
                    $q2->where('class_name', 'like', "%{$search}%");
                })
                ->orWhereHas('subject', function($q2) use ($search) {
                    $q2->where('subject_name', 'like', "%{$search}%");
                })
                ->orWhereHas('subjectGroup', function($q2) use ($search) {
                    $q2->where('group_name', 'like', "%{$search}%");
                });
            });
        }
        
        // Apply is_active filter
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Apply class section filter
        if ($request->filled('class_section_id')) {
            $query->where('class_section_id', $request->class_section_id);
        }
        
        // Paginate
        $perPage = $request->get('per_page', 10);
        $classSubjects = $query->latest()->paginate($perPage);
        
        return response()->json($classSubjects);
    }

    /**
     * Get DataTables class subjects
     */
    private function getDataTablesClassSubjects(Request $request)
    {
        $query = ClassSubject::with([
            'classSection.branchClass.branch',
            'classSection.branchClass.class',
            'classSection.section',
            'subject',
            'subjectGroup'
        ]);
        
        // Apply search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->whereHas('classSection.branchClass.branch', function($q2) use ($search) {
                    $q2->where('branch_name', 'like', "%{$search}%");
                })
                ->orWhereHas('classSection.branchClass.class', function($q2) use ($search) {
                    $q2->where('class_name', 'like', "%{$search}%");
                })
                ->orWhereHas('classSection.section', function($q2) use ($search) {
                    $q2->where('section_name', 'like', "%{$search}%");
                })
                ->orWhereHas('subject', function($q2) use ($search) {
                    $q2->where('subject_name', 'like', "%{$search}%");
                })
                ->orWhereHas('subjectGroup', function($q2) use ($search) {
                    $q2->where('group_name', 'like', "%{$search}%");
                });
            });
        }
        
        // Apply filters
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('class_section_id')) {
            $query->where('class_section_id', $request->class_section_id);
        }

        if ($request->filled('mapping_type')) {
            if ($request->mapping_type === 'individual') {
                $query->whereNotNull('subject_id');
            } else if ($request->mapping_type === 'group') {
                $query->whereNotNull('subject_group_id');
            }
        }
        
        // Get total count
        $totalData = $query->count();
        
        // Apply sorting
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');
        
        if ($orderColumn == 1) { // Branch/Class/Section
            $query->orderBy('class_section_id', $orderDir);
        } else {
            $query->orderBy('id', $orderDir);
        }
        
        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $classSubjects = $query->skip($start)->take($length)->get();
        
        // Format data
        $data = $classSubjects->map(function($cs, $index) use ($start) {
            $branch = $cs->classSection->branchClass->branch->branch_name ?? 'N/A';
            $class = $cs->classSection->branchClass->class->class_name ?? 'N/A';
            $section = $cs->classSection->section->section_name ?? 'N/A';
            
            $classInfo = '<div class="flex flex-col gap-1">';
            $classInfo .= '<span class="text-xs text-gray-500">' . $branch . '</span>';
            $classInfo .= '<span class="font-medium">' . $class . ' - ' . $section . '</span>';
            $classInfo .= '</div>';

            // Mapping type and subjects/group
            if ($cs->subject_id) {
                $mappingType = '<span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">Individual Subjects</span>';
                $subjectsHtml = '<span class="text-sm font-medium">' . ($cs->subject->subject_name ?? 'N/A') . '</span>';
                $subjectsHtml .= '<br><span class="text-xs text-gray-500">Code: ' . ($cs->subject->subject_code ?? 'N/A') . '</span>';
            } else {
                $mappingType = '<span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Subject Group</span>';
                
                $groupName = $cs->subjectGroup->group_name ?? 'N/A';
                $subjectsCount = $cs->subjectGroup ? $cs->subjectGroup->subjects()->count() : 0;
                
                $subjectsHtml = '<div class="flex items-center gap-2">';
                $subjectsHtml .= '<span class="text-sm font-medium">' . $groupName . '</span>';
                $subjectsHtml .= '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">';
                $subjectsHtml .= $subjectsCount . ' subjects';
                $subjectsHtml .= '</span>';
                
                if ($subjectsCount > 0) {
                    $subjectsHtml .= '<button onclick="viewGroupSubjects(' . $cs->id . ')" class="text-xs text-blue-600 hover:text-blue-800" title="View subjects">';
                    $subjectsHtml .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                    $subjectsHtml .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>';
                    $subjectsHtml .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
                    $subjectsHtml .= '</svg>';
                    $subjectsHtml .= '</button>';
                }
                
                $subjectsHtml .= '</div>';
            }

            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $cs->id,
                'class_section' => $classInfo,
                'mapping_type' => $mappingType,
                'subjects' => $subjectsHtml,
                'is_active' => $cs->is_active 
                    ? '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Active</span>' 
                    : '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">Inactive</span>',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <button onclick=\'editClassSubject(' . json_encode([
                            'id' => $cs->id
                        ]) . ')\' class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors" title="Edit">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit
                        </button>
                        <button onclick="deleteClassSubject(' . $cs->id . ')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors" title="Delete">
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
     * Get group subjects for view modal
     */
    public function groupSubjects($id)
    {
        $classSubject = ClassSubject::with([
            'subjectGroup',
            'classSection.branchClass.branch',
            'classSection.branchClass.class',
            'classSection.section'
        ])->findOrFail($id);
        
        if (!$classSubject->subject_group_id) {
            return response()->json(['error' => 'This is not a group mapping'], 400);
        }

        $subjects = $classSubject->subjectGroup->subjects();
        
        return response()->json([
            'class_subject' => [
                'id' => $classSubject->id,
                'branch' => $classSubject->classSection->branchClass->branch->branch_name,
                'class' => $classSubject->classSection->branchClass->class->class_name,
                'section' => $classSubject->classSection->section->section_name,
            ],
            'subject_group' => [
                'id' => $classSubject->subjectGroup->id,
                'group_name' => $classSubject->subjectGroup->group_name,
                'description' => $classSubject->subjectGroup->description,
            ],
            'subjects' => $subjects,
            'subjects_count' => $subjects->count(),
            'compulsory_count' => $subjects->where('is_compulsory', true)->count(),
            'optional_count' => $subjects->where('is_compulsory', false)->count(),
        ]);
    }

    /**
     * Store a newly created class subject mapping
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'mapping_type' => 'required|in:individual,group',
            'subject_id' => 'required_if:mapping_type,individual|nullable|exists:subjects,id',
            'subject_group_id' => 'required_if:mapping_type,group|nullable|exists:subject_groups,id',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // Check for duplicate mapping
            $exists = ClassSubject::where('class_section_id', $validated['class_section_id'])
                ->where(function($q) use ($validated) {
                    if ($validated['mapping_type'] === 'individual') {
                        $q->where('subject_id', $validated['subject_id']);
                    } else {
                        $q->where('subject_group_id', $validated['subject_group_id']);
                    }
                })
                ->exists();

            if ($exists) {
                return back()->with('error', 'This mapping already exists for the selected section!');
            }

            // Create class subject mapping
            $classSubject = ClassSubject::create([
                'class_section_id' => $validated['class_section_id'],
                'subject_id' => $validated['mapping_type'] === 'individual' ? $validated['subject_id'] : null,
                'subject_group_id' => $validated['mapping_type'] === 'group' ? $validated['subject_group_id'] : null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            DB::commit();
            
            $mappingInfo = $validated['mapping_type'] === 'individual' 
                ? 'subject' 
                : 'subject group';
                
            return redirect()->route('class-subjects.index')
                ->with('success', "Class subject mapping created successfully with {$mappingInfo}!");
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create mapping: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified class subject mapping
     */
    public function update(Request $request, ClassSubject $classSubject)
    {
        $validated = $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'mapping_type' => 'required|in:individual,group',
            'subject_id' => 'required_if:mapping_type,individual|nullable|exists:subjects,id',
            'subject_group_id' => 'required_if:mapping_type,group|nullable|exists:subject_groups,id',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // Check for duplicate mapping (excluding current record)
            $exists = ClassSubject::where('id', '!=', $classSubject->id)
                ->where('class_section_id', $validated['class_section_id'])
                ->where(function($q) use ($validated) {
                    if ($validated['mapping_type'] === 'individual') {
                        $q->where('subject_id', $validated['subject_id']);
                    } else {
                        $q->where('subject_group_id', $validated['subject_group_id']);
                    }
                })
                ->exists();

            if ($exists) {
                return back()->with('error', 'This mapping already exists for the selected section!');
            }

            // Update class subject mapping
            $classSubject->update([
                'class_section_id' => $validated['class_section_id'],
                'subject_id' => $validated['mapping_type'] === 'individual' ? $validated['subject_id'] : null,
                'subject_group_id' => $validated['mapping_type'] === 'group' ? $validated['subject_group_id'] : null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            DB::commit();
            
            return redirect()->route('class-subjects.index')
                ->with('success', 'Class subject mapping updated successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update mapping: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified class subject mapping
     */
    public function destroy(ClassSubject $classSubject)
    {
        DB::beginTransaction();
        try {
            // Delete mapping (soft delete)
            $classSubject->delete();

            DB::commit();
            return back()->with('success', 'Class subject mapping deleted successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete mapping: ' . $e->getMessage());
        }
    }

    /**
     * Get class sections for dropdown (API endpoint)
     */
    public function getClassSections()
    {
        $classSections = ClassSection::with([
            'branchClass.branch',
            'branchClass.class',
            'section'
        ])
            ->active()
            ->get()
            ->map(function ($cs) {
                return [
                    'id' => $cs->id,
                    'label' => $cs->branchClass->branch->branch_name . ' - ' . 
                              $cs->branchClass->class->class_name . ' - ' . 
                              $cs->section->section_name,
                    'branch_id' => $cs->branchClass->branch_id,
                    'class_id' => $cs->branchClass->class_id,
                    'section_id' => $cs->section_id,
                ];
            });

        return response()->json($classSections);
    }

    /**
     * Get subjects for a class section (API endpoint)
     */
    public function getSectionSubjects(ClassSection $classSection)
    {
        $classSubjects = $classSection->classSubjects()
            ->with(['subject', 'subjectGroup'])
            ->where('is_active', true)
            ->get()
            ->map(function($cs) {
                if ($cs->subject_id) {
                    return [
                        'type' => 'individual',
                        'subject' => $cs->subject,
                    ];
                } else {
                    return [
                        'type' => 'group',
                        'group' => $cs->subjectGroup,
                        'subjects' => $cs->subjectGroup->subjects(),
                    ];
                }
            });

        return response()->json($classSubjects);
    }
}