<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchClass;
use App\Models\ClassSection;
use App\Models\ClassSectionSubject;
use App\Models\Subject;
use App\Models\SubjectGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ClassSectionSubjectController extends Controller
{
    /**
     * Display a listing of class section subjects
     */
    public function index(Request $request)
    {
        // Check if it's a mobile request
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileClassSectionSubjects($request);
        }
        
        // DataTables AJAX request
        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesClassSectionSubjects($request);
        }

        // For initial Inertia page load - only need branches for filters
        $branches = Branch::active()
            ->select('id', 'branch_name', 'location')
            ->orderBy('branch_name')
            ->get();

        return Inertia::render('ClassSectionSubjects/Index', [
            'branches' => $branches,
        ]);
    }

    /**
     * Show the form for creating a new class section subject assignment
     */
    public function create()
    {
        $branches = Branch::active()
            ->select('id', 'branch_name', 'location')
            ->orderBy('branch_name')
            ->get();

        return Inertia::render('ClassSectionSubjects/Create', [
            'branches' => $branches,
        ]);
    }

    /**
     * Show the form for editing the specified class section subject
     */
    public function edit(ClassSectionSubject $classSectionSubject)
    {
        // Load relationships
        $classSectionSubject->load([
            'classSection.branchClass.branch:id,branch_name,location',
            'classSection.branchClass.class:id,class_name',
            'classSection.section:id,section_name',
            'subject:id,subject_name,subject_code',
            'subjectGroup:id,group_name,subject_ids'
        ]);

        $branches = Branch::active()
            ->select('id', 'branch_name', 'location')
            ->orderBy('branch_name')
            ->get();

        // Get the branch and class info
        $branchId = $classSectionSubject->classSection->branchClass->branch_id;
        $branchClassId = $classSectionSubject->classSection->branch_class_id;

        // Get classes for the branch
        $branchClasses = BranchClass::active()
            ->where('branch_id', $branchId)
            ->with('class:id,class_name')
            ->get()
            ->map(function($bc) {
                return [
                    'id' => $bc->id,
                    'class_id' => $bc->class_id,
                    'class_name' => $bc->class->class_name,
                ];
            });

        // Get class sections for the selected branch class
        $classSections = ClassSection::active()
            ->where('branch_class_id', $branchClassId)
            ->with(['branchClass.class:id,class_name', 'section:id,section_name'])
            ->get()
            ->map(function($cs) {
                return [
                    'id' => $cs->id,
                    'display_name' => $cs->branchClass->class->class_name . ' - ' . $cs->section->section_name,
                    'capacity' => $cs->capacity,
                ];
            });

        // Get all subjects and subject groups
        $subjects = Subject::active()
            ->select('id', 'subject_name', 'subject_code', 'is_compulsory')
            ->orderBy('subject_name')
            ->get();

        $subjectGroups = SubjectGroup::active()
            ->select('id', 'group_name', 'description', 'subject_ids')
            ->orderBy('group_name')
            ->get();

        return Inertia::render('ClassSectionSubjects/Edit', [
            'classSectionSubject' => [
                'id' => $classSectionSubject->id,
                'class_section_id' => $classSectionSubject->class_section_id,
                'subject_id' => $classSectionSubject->subject_id,
                'subject_group_id' => $classSectionSubject->subject_group_id,
                'assignment_type' => $classSectionSubject->subject_group_id ? 'group' : 'individual',
                'branch_id' => $branchId,
                'branch_class_id' => $branchClassId,
                'class_section_name' => $classSectionSubject->classSection->branchClass->class->class_name . ' - ' . $classSectionSubject->classSection->section->section_name,
            ],
            'branches' => $branches,
            'branchClasses' => $branchClasses,
            'classSections' => $classSections,
            'subjects' => $subjects,
            'subjectGroups' => $subjectGroups,
        ]);
    }

    /**
     * Get mobile paginated data
     */
    private function getMobileClassSectionSubjects(Request $request)
    {
        $query = ClassSectionSubject::with([
            'classSection.branchClass.branch:id,branch_name',
            'classSection.branchClass.class:id,class_name',
            'classSection.section:id,section_name',
            'subject:id,subject_name,subject_code',
            'subjectGroup:id,group_name'
        ]);
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('classSection.branchClass.branch', function($branchQuery) use ($search) {
                    $branchQuery->where('branch_name', 'like', "%{$search}%");
                })
                ->orWhereHas('classSection.branchClass.class', function($classQuery) use ($search) {
                    $classQuery->where('class_name', 'like', "%{$search}%");
                })
                ->orWhereHas('subject', function($subjectQuery) use ($search) {
                    $subjectQuery->where('subject_name', 'like', "%{$search}%")
                                 ->orWhere('subject_code', 'like', "%{$search}%");
                })
                ->orWhereHas('subjectGroup', function($groupQuery) use ($search) {
                    $groupQuery->where('group_name', 'like', "%{$search}%");
                });
            });
        }
        
        // Apply branch filter
        if ($request->filled('branch_id')) {
            $query->whereHas('classSection.branchClass', function($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }
        
        // Apply class section filter
        if ($request->filled('class_section_id')) {
            $query->where('class_section_id', $request->class_section_id);
        }
        
        // Apply assignment type filter
        if ($request->filled('assignment_type')) {
            if ($request->assignment_type === 'individual') {
                $query->whereNotNull('subject_id');
            } elseif ($request->assignment_type === 'group') {
                $query->whereNotNull('subject_group_id');
            }
        }
        
        // Paginate
        $perPage = $request->get('per_page', 10);
        $classSectionSubjects = $query->latest()->paginate($perPage);
        
        return response()->json($classSectionSubjects);
    }

    /**
     * Get DataTables data
     */
    private function getDataTablesClassSectionSubjects(Request $request)
    {
        $query = ClassSectionSubject::with([
            'classSection.branchClass.branch:id,branch_name',
            'classSection.branchClass.class:id,class_name',
            'classSection.section:id,section_name',
            'subject:id,subject_name,subject_code',
            'subjectGroup:id,group_name'
        ]);
        
        // Apply search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->whereHas('classSection.branchClass.branch', function($branchQuery) use ($search) {
                    $branchQuery->where('branch_name', 'like', "%{$search}%");
                })
                ->orWhereHas('classSection.branchClass.class', function($classQuery) use ($search) {
                    $classQuery->where('class_name', 'like', "%{$search}%");
                })
                ->orWhereHas('subject', function($subjectQuery) use ($search) {
                    $subjectQuery->where('subject_name', 'like', "%{$search}%")
                                 ->orWhere('subject_code', 'like', "%{$search}%");
                })
                ->orWhereHas('subjectGroup', function($groupQuery) use ($search) {
                    $groupQuery->where('group_name', 'like', "%{$search}%");
                });
            });
        }
        
        // Apply filters
        if ($request->filled('branch_id')) {
            $query->whereHas('classSection.branchClass', function($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }

        if ($request->filled('class_section_id')) {
            $query->where('class_section_id', $request->class_section_id);
        }
        
        if ($request->filled('assignment_type')) {
            if ($request->assignment_type === 'individual') {
                $query->whereNotNull('subject_id');
            } elseif ($request->assignment_type === 'group') {
                $query->whereNotNull('subject_group_id');
            }
        }
        
        // Get total count
        $totalData = $query->count();
        
        // Apply sorting
        $orderColumn = $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = ['id', 'class_section_id', 'subject_id', 'subject_group_id', 'created_at'];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }
        
        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $classSectionSubjects = $query->skip($start)->take($length)->get();
        
        // Format data
        $data = $classSectionSubjects->map(function($assignment, $index) use ($start) {
            $classSection = $assignment->classSection;
            $branch = $classSection->branchClass->branch->branch_name;
            $class = $classSection->branchClass->class->class_name;
            $section = $classSection->section->section_name;
            
            // Determine assignment type and details
            if ($assignment->subject_id) {
                $assignmentType = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Individual</span>';
                $assignmentDetails = '<div class="font-medium text-gray-900">' . $assignment->subject->subject_name . '</div>
                                     <div class="text-sm text-gray-500">Code: ' . $assignment->subject->subject_code . '</div>';
            } else {
                $assignmentType = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">Group</span>';
                $subjectIds = explode(',', $assignment->subjectGroup->subject_ids);
                $subjectCount = count($subjectIds);
                $assignmentDetails = '<div class="font-medium text-gray-900">' . $assignment->subjectGroup->group_name . '</div>
                                     <div class="text-sm text-gray-500">' . $subjectCount . ' subjects</div>';
            }
            
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $assignment->id,
                'class_section' => '<div class="font-medium text-gray-900">' . $branch . '</div>
                                   <div class="text-sm text-gray-500">' . $class . ' - ' . $section . '</div>',
                'assignment_type' => $assignmentType,
                'assignment_details' => $assignmentDetails,
                'created_at' => $assignment->created_at->format('d M, Y'),
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <a href="' . route('class-section-subjects.edit', $assignment->id) . '" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors" title="Edit">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit
                        </a>
                        <button onclick="deleteClassSectionSubject(' . $assignment->id . ')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors" title="Delete">
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
     * Store a newly created class section subject assignment
     * Supports: Individual subjects (multiple) OR Subject Group (single)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'assignment_type' => 'required|in:individual,group',
            'subject_ids' => 'required_if:assignment_type,individual|array',
            'subject_ids.*' => 'exists:subjects,id',
            'subject_group_id' => 'nullable|required_if:assignment_type,group|exists:subject_groups,id',
        ], [
            'class_section_id.required' => 'Class section is required',
            'class_section_id.exists' => 'Selected class section does not exist',
            'assignment_type.required' => 'Assignment type is required',
            'assignment_type.in' => 'Invalid assignment type',
            'subject_ids.required_if' => 'At least one subject is required for individual assignment',
            'subject_ids.array' => 'Subjects must be an array',
            'subject_ids.*.exists' => 'One or more selected subjects do not exist',
            'subject_group_id.required_if' => 'Subject group is required for group assignment',
            'subject_group_id.exists' => 'Selected subject group does not exist',
        ]);

        DB::beginTransaction();
        try {
            $created = 0;
            $skipped = 0;
            $skippedItems = [];

            if ($validated['assignment_type'] === 'individual') {
                // Individual subjects - create multiple records
                foreach ($validated['subject_ids'] as $subjectId) {
                    // Check for duplicate entry
                    $exists = ClassSectionSubject::where('class_section_id', $validated['class_section_id'])
                        ->where('subject_id', $subjectId)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        $subject = Subject::find($subjectId);
                        $skippedItems[] = $subject->subject_name;
                    } else {
                        ClassSectionSubject::create([
                            'class_section_id' => $validated['class_section_id'],
                            'subject_id' => $subjectId,
                            'subject_group_id' => null,
                        ]);
                        $created++;
                    }
                }
            } else {
                // Subject group - create single record
                $exists = ClassSectionSubject::where('class_section_id', $validated['class_section_id'])
                    ->where('subject_group_id', $validated['subject_group_id'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    $group = SubjectGroup::find($validated['subject_group_id']);
                    $skippedItems[] = $group->group_name;
                } else {
                    ClassSectionSubject::create([
                        'class_section_id' => $validated['class_section_id'],
                        'subject_id' => null,
                        'subject_group_id' => $validated['subject_group_id'],
                    ]);
                    $created++;
                }
            }

            DB::commit();

            $messages = [];
            if ($created > 0) {
                $type = $validated['assignment_type'] === 'individual' ? 'subject(s)' : 'subject group';
                $messages[] = "{$created} {$type} assigned successfully!";
            }
            if ($skipped > 0) {
                $messages[] = "{$skipped} already existed: " . implode(', ', $skippedItems);
            }

            return redirect()->route('class-section-subjects.index')->with('success', implode(' ', $messages));
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to assign subjects: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Update the specified class section subject assignment
     */
    public function update(Request $request, ClassSectionSubject $classSectionSubject)
    {
        $validated = $request->validate([
            'assignment_type' => 'required|in:individual,group',
            'subject_id' => 'required_if:assignment_type,individual|exists:subjects,id',
            'subject_group_id' => 'required_if:assignment_type,group|exists:subject_groups,id',
        ], [
            'assignment_type.required' => 'Assignment type is required',
            'subject_id.required_if' => 'Subject is required for individual assignment',
            'subject_group_id.required_if' => 'Subject group is required for group assignment',
        ]);

        DB::beginTransaction();
        try {
            // Check for duplicate entry (excluding current record)
            if ($validated['assignment_type'] === 'individual') {
                $exists = ClassSectionSubject::where('class_section_id', $classSectionSubject->class_section_id)
                    ->where('subject_id', $validated['subject_id'])
                    ->where('id', '!=', $classSectionSubject->id)
                    ->exists();

                if ($exists) {
                    return back()
                        ->withErrors(['subject_id' => 'This subject is already assigned to this class section!'])
                        ->withInput();
                }

                $classSectionSubject->update([
                    'subject_id' => $validated['subject_id'],
                    'subject_group_id' => null,
                ]);
            } else {
                $exists = ClassSectionSubject::where('class_section_id', $classSectionSubject->class_section_id)
                    ->where('subject_group_id', $validated['subject_group_id'])
                    ->where('id', '!=', $classSectionSubject->id)
                    ->exists();

                if ($exists) {
                    return back()
                        ->withErrors(['subject_group_id' => 'This subject group is already assigned to this class section!'])
                        ->withInput();
                }

                $classSectionSubject->update([
                    'subject_id' => null,
                    'subject_group_id' => $validated['subject_group_id'],
                ]);
            }

            DB::commit();
            return redirect()->route('class-section-subjects.index')->with('success', 'Subject assignment updated successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update subject assignment: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified class section subject assignment
     */
    public function destroy(ClassSectionSubject $classSectionSubject)
    {
        DB::beginTransaction();
        try {
            $classSectionSubject->delete();

            DB::commit();
            return back()->with('success', 'Subject assignment deleted successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete subject assignment: ' . $e->getMessage());
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
     * Get class sections by branch class (API endpoint)
     */
    public function getClassSectionsByBranchClass(Request $request)
    {
        $request->validate([
            'branch_class_id' => 'required|exists:branch_classes,id',
        ]);

        $classSections = ClassSection::active()
            ->where('branch_class_id', $request->branch_class_id)
            ->with(['branchClass.class:id,class_name', 'section:id,section_name'])
            ->get()
            ->map(function($cs) {
                return [
                    'id' => $cs->id,
                    'display_name' => $cs->branchClass->class->class_name . ' - ' . $cs->section->section_name,
                    'capacity' => $cs->capacity,
                ];
            });

        return response()->json($classSections);
    }

    /**
     * Get all active subjects (API endpoint)
     */
    public function getSubjects()
    {
        $subjects = Subject::active()
            ->select('id', 'subject_name', 'subject_code', 'is_compulsory')
            ->orderBy('subject_name')
            ->get();

        return response()->json($subjects);
    }

    /**
     * Get all active subject groups (API endpoint)
     */
    public function getSubjectGroups()
    {
        $subjectGroups = SubjectGroup::active()
            ->select('id', 'group_name', 'description', 'subject_ids')
            ->orderBy('group_name')
            ->get()
            ->map(function($group) {
                // Get subject names for display
                $subjectIds = explode(',', $group->subject_ids);
                $subjects = Subject::whereIn('id', $subjectIds)
                    ->pluck('subject_name')
                    ->toArray();
                
                return [
                    'id' => $group->id,
                    'group_name' => $group->group_name,
                    'description' => $group->description,
                    'subject_ids' => $group->subject_ids,
                    'subject_count' => count($subjectIds),
                    'subjects' => $subjects,
                ];
            });

        return response()->json($subjectGroups);
    }

    /**
     * Get already assigned subjects/groups for a class section (API endpoint)
     */
    public function getAssignedSubjects(Request $request)
    {
        $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
        ]);

        $assignments = ClassSectionSubject::where('class_section_id', $request->class_section_id)
            ->with(['subject:id,subject_name', 'subjectGroup:id,group_name'])
            ->get();

        $assignedSubjectIds = $assignments->where('subject_id', '!=', null)->pluck('subject_id')->toArray();
        $assignedGroupIds = $assignments->where('subject_group_id', '!=', null)->pluck('subject_group_id')->toArray();

        return response()->json([
            'assigned_subject_ids' => $assignedSubjectIds,
            'assigned_group_ids' => $assignedGroupIds,
            'has_group' => count($assignedGroupIds) > 0,
            'has_subjects' => count($assignedSubjectIds) > 0,
        ]);
    }

    /**
     * Get subject statistics for a class section (API endpoint)
     */
    public function subjectStats(Request $request)
    {
        $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
        ]);

        $classSection = ClassSection::find($request->class_section_id);
        $allSubjects = $classSection->getAllSubjects();

        $individualCount = $classSection->classSectionSubjects()->whereNotNull('subject_id')->count();
        $groupCount = $classSection->classSectionSubjects()->whereNotNull('subject_group_id')->count();

        return response()->json([
            'total_subjects' => $allSubjects->count(),
            'individual_assignments' => $individualCount,
            'group_assignments' => $groupCount,
            'has_mixed_assignment' => $individualCount > 0 && $groupCount > 0,
        ]);
    }

    /**
     * Bulk delete assignments for a class section (API endpoint)
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'class_section_id' => 'required|exists:class_sections,id',
            'assignment_type' => 'required|in:individual,group,all',
        ]);

        DB::beginTransaction();
        try {
            $query = ClassSectionSubject::where('class_section_id', $validated['class_section_id']);

            if ($validated['assignment_type'] === 'individual') {
                $query->whereNotNull('subject_id');
            } elseif ($validated['assignment_type'] === 'group') {
                $query->whereNotNull('subject_group_id');
            }

            $count = $query->count();
            $query->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$count} assignment(s) deleted successfully!",
                'deleted_count' => $count,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete assignments: ' . $e->getMessage(),
            ], 500);
        }
    }
}
