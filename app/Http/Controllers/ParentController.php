<?php

namespace App\Http\Controllers;

use App\Models\ParentModel;
use App\Models\Branch;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class ParentController extends Controller
{
    /**
     * Display a listing of parents
     */
    public function index(Request $request)
    {
        // Check if it's a mobile request
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileParents($request);
        }
        
        // DataTables AJAX request
        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesParents($request);
        }
        
        // Check specifically for DataTables request (not generic AJAX)
        if ($request->has('draw')) {
            $parents = ParentModel::with(['branch']);

            return DataTables::of($parents)
                ->addIndexColumn()
                ->addColumn('action', function ($parent) {
                    return '
                        <div class="flex items-center gap-2">
                            <a href="' . route('parents.edit', $parent->id) . '" 
                               class="text-blue-600 hover:text-blue-800"
                               title="Edit">
                                <i class="fas fa-edit"></i> edit
                            </a>
                            <button onclick="deleteParent(' . $parent->id . ')" 
                                    class="text-red-600 hover:text-red-800"
                                    title="Delete">
                                <i class="fas fa-trash"></i> delete
                            </button>
                        </div>
                    ';
                })
                ->editColumn('status', function ($parent) {
                    $colors = [
                        'active' => 'green',
                        'inactive' => 'gray'
                    ];
                    $color = $colors[$parent->status] ?? 'gray';
                    
                    return '<span class="px-2 py-1 text-xs rounded-full bg-' . $color . '-100 text-' . $color . '-800">' 
                           . ucfirst(str_replace('_', ' ', $parent->status)) . 
                           '</span>';
                })
                ->addColumn('total_children', function ($parent) {
                    return $parent->total_children;
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }

        // For initial Inertia page load
        $branches = Branch::active()->get(['id', 'name']);
        
        return Inertia::render('Parents/Index', [
            'branches' => $branches
        ]);
    }

    private function getMobileParents(Request $request)
    {
        $query = ParentModel::with(['branch', 'students']);
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('father_name', 'like', "%{$search}%")
                  ->orWhere('mother_name', 'like', "%{$search}%")
                  ->orWhere('father_phone', 'like', "%{$search}%")
                  ->orWhere('father_email', 'like', "%{$search}%")
                  ->orWhere('father_cnic', 'like', "%{$search}%");
            });
        }
        
        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Apply branch filter
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        // Paginate
        $perPage = $request->get('per_page', 10);
        $parents = $query->latest()->paginate($perPage);
        
        return response()->json($parents);
    }

    private function getDataTablesParents(Request $request)
    {
        $query = ParentModel::with(['branch', 'students']);
        
        // Apply search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->where('father_name', 'like', "%{$search}%")
                  ->orWhere('mother_name', 'like', "%{$search}%")
                  ->orWhere('father_phone', 'like', "%{$search}%")
                  ->orWhere('father_email', 'like', "%{$search}%")
                  ->orWhere('father_cnic', 'like', "%{$search}%");
            });
        }
        
        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        // Get total count
        $totalData = $query->count();
        
        // Apply sorting
        $orderColumn = $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir', 'asc');
        $columns = ['id', 'father_name', 'father_phone', 'father_email', 'city', 'status'];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }
        
        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $parents = $query->skip($start)->take($length)->get();
        
        // Format data
        $data = $parents->map(function($parent, $index) use ($start) {
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $parent->id,
                'father_name' => $parent->father_name,
                'mother_name' => $parent->mother_name ?? '-',
                'father_phone' => $parent->father_phone,
                'father_email' => $parent->father_email ?? '-',
                'city' => $parent->city,
                'branch' => $parent->branch ? $parent->branch->name : '-',
                'total_children' => $parent->students()->count(),
                'status' => '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $this->getStatusClass($parent->status) . '">' . ucfirst($parent->status) . '</span>',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <a href="' . route('parents.edit', $parent->id) . '" class="text-blue-600 hover:text-blue-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        <button onclick="deleteParent(' . $parent->id . ')" class="text-red-600 hover:text-red-800">
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
    
    private function getStatusClass($status)
    {
        return match($status) {
            'active' => 'bg-green-100 text-green-800',
            'inactive' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Show the form for creating a new parent
     */
    public function create()
    {
        $branches = Branch::active()->get(['id', 'name']);
        $students = Student::active()->get(['id', 'admission_no', 'first_name', 'last_name']);
        
        return Inertia::render('Parents/Create', [
            'branches' => $branches,
            'students' => $students
        ]);
    }

    /**
     * Store a newly created parent
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'father_name' => 'required|string|max:100',
            'father_phone' => 'required|string|max:20',
            'father_email' => 'nullable|email|max:255',
            'father_occupation' => 'nullable|string|max:100',
            'father_cnic' => 'required|string|max:20|unique:parents,father_cnic',
            'father_income' => 'nullable|numeric|min:0',
            'mother_name' => 'nullable|string|max:100',
            'mother_phone' => 'nullable|string|max:20',
            'mother_email' => 'nullable|email|max:255',
            'mother_occupation' => 'nullable|string|max:100',
            'mother_cnic' => 'nullable|string|max:20',
            'guardian_name' => 'nullable|string|max:100',
            'guardian_phone' => 'nullable|string|max:20',
            'guardian_relation' => 'nullable|string|max:50',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'preferred_contact' => 'required|in:father,mother,guardian',
            'status' => 'required|in:active,inactive',
        ]);

        $parent = ParentModel::create($validated);

        // Attach students if provided
        if ($request->has('students') && is_array($request->students)) {
            foreach ($request->students as $studentData) {
                $parent->students()->attach($studentData['student_id'], [
                    'relation' => $studentData['relation'] ?? 'father',
                    'is_primary' => $studentData['is_primary'] ?? false,
                    'can_pickup' => $studentData['can_pickup'] ?? true,
                    'can_view_grades' => $studentData['can_view_grades'] ?? true,
                    'can_receive_sms' => $studentData['can_receive_sms'] ?? true,
                ]);
            }
        }

        return redirect()
            ->route('parents.index')
            ->with('success', 'Parent created successfully!');
    }

    /**
     * Display the specified parent
     */
    public function show(ParentModel $parent)
    {
        return Inertia::render('Parents/Show', [
            'parent' => $parent->load([
                'branch',
                'students.class',
                'students.section'
            ]),
            'stats' => [
                'total_children' => $parent->students()->count(),
                'active_children' => $parent->students()->active()->count(),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified parent
     */
    public function edit(ParentModel $parent)
    {
        $branches = Branch::active()->get(['id', 'name']);
        $students = Student::active()->get(['id', 'admission_no', 'first_name', 'last_name']);
        $parentStudents = $parent->students()->get()->map(function($student) {
            return [
                'student_id' => $student->id,
                'relation' => $student->pivot->relation,
                'is_primary' => $student->pivot->is_primary,
                'can_pickup' => $student->pivot->can_pickup,
                'can_view_grades' => $student->pivot->can_view_grades,
                'can_receive_sms' => $student->pivot->can_receive_sms,
            ];
        });
        
        return Inertia::render('Parents/Edit', [
            'parent' => $parent,
            'branches' => $branches,
            'students' => $students,
            'parentStudents' => $parentStudents
        ]);
    }

    /**
     * Update the specified parent
     */
    public function update(Request $request, ParentModel $parent)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'father_name' => 'required|string|max:100',
            'father_phone' => 'required|string|max:20',
            'father_email' => 'nullable|email|max:255',
            'father_occupation' => 'nullable|string|max:100',
            'father_cnic' => 'required|string|max:20|unique:parents,father_cnic,' . $parent->id,
            'father_income' => 'nullable|numeric|min:0',
            'mother_name' => 'nullable|string|max:100',
            'mother_phone' => 'nullable|string|max:20',
            'mother_email' => 'nullable|email|max:255',
            'mother_occupation' => 'nullable|string|max:100',
            'mother_cnic' => 'nullable|string|max:20',
            'guardian_name' => 'nullable|string|max:100',
            'guardian_phone' => 'nullable|string|max:20',
            'guardian_relation' => 'nullable|string|max:50',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'preferred_contact' => 'required|in:father,mother,guardian',
            'status' => 'required|in:active,inactive',
        ]);

        $parent->update($validated);

        // Sync students if provided
        if ($request->has('students') && is_array($request->students)) {
            $syncData = [];
            foreach ($request->students as $studentData) {
                $syncData[$studentData['student_id']] = [
                    'relation' => $studentData['relation'] ?? 'father',
                    'is_primary' => $studentData['is_primary'] ?? false,
                    'can_pickup' => $studentData['can_pickup'] ?? true,
                    'can_view_grades' => $studentData['can_view_grades'] ?? true,
                    'can_receive_sms' => $studentData['can_receive_sms'] ?? true,
                ];
            }
            $parent->students()->sync($syncData);
        }

        return redirect()
            ->route('parents.index')
            ->with('success', 'Parent updated successfully!');
    }

    /**
     * Remove the specified parent
     */
    public function destroy(ParentModel $parent)
    {
        // Check if parent has students
        if ($parent->students()->count() > 0) {
            return back()->with('error', 'Cannot delete parent with existing children!');
        }

        $parent->delete();

        return redirect()
            ->route('parents.index')
            ->with('success', 'Parent deleted successfully!');
    }

    /**
     * Get parents for dropdown (API endpoint)
     */
    public function dropdown(Request $request)
    {
        $query = ParentModel::active()
            ->select('id', 'father_name', 'father_phone', 'mother_name');
        
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        $parents = $query->orderBy('father_name')->get();

        return response()->json($parents);
    }

    /**
     * Get parent's children (API endpoint)
     */
    public function getChildren(ParentModel $parent)
    {
        $students = $parent->students()
            ->with(['class', 'section'])
            ->get();

        return response()->json($students);
    }
}