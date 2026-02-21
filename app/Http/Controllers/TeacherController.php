<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Branch;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;


class TeacherController extends Controller
{
    /**
     * Display a listing of teachers
     */
    public function index(Request $request)
    {
        // Check if it's a mobile request
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileTeachers($request);
        }
        
        // DataTables AJAX request
        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesTeachers($request);
        }
        
        // Check specifically for DataTables request (not generic AJAX)
        if ($request->has('draw')) {
            $teachers = Teacher::with(['branch']);

            return DataTables::of($teachers)
                ->addIndexColumn()
                ->addColumn('action', function ($teacher) {
                    return '
                        <div class="flex items-center gap-2">
                            <a href="' . route('teachers.edit', $teacher->id) . '" 
                               class="text-blue-600 hover:text-blue-800"
                               title="Edit">
                                <i class="fas fa-edit"></i> edit
                            </a>
                            <button onclick="deleteTeacher(' . $teacher->id . ')" 
                                    class="text-red-600 hover:text-red-800"
                                    title="Delete">
                                <i class="fas fa-trash"></i> delete
                            </button>
                        </div>
                    ';
                })
                ->editColumn('status', function ($teacher) {
                    $colors = [
                        'active' => 'green',
                        'inactive' => 'gray',
                        'on_leave' => 'yellow',
                        'resigned' => 'red'
                    ];
                    $color = $colors[$teacher->status] ?? 'gray';
                    
                    return '<span class="px-2 py-1 text-xs rounded-full bg-' . $color . '-100 text-' . $color . '-800">' 
                           . ucfirst(str_replace('_', ' ', $teacher->status)) . 
                           '</span>';
                })
                ->addColumn('full_name', function ($teacher) {
                    return $teacher->full_name;
                })
                ->addColumn('total_salary', function ($teacher) {
                    return number_format($teacher->total_salary, 2);
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }

        // For initial Inertia page load
        $branches = Branch::active()->get(['id', 'name']);
        
        return Inertia::render('Teachers/Index', [
            'branches' => $branches
        ]);
    }

    private function getMobileTeachers(Request $request)
    {
        $query = Teacher::with(['branch']);
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
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
        $teachers = $query->latest()->paginate($perPage);
        
        return response()->json($teachers);
    }

    private function getDataTablesTeachers(Request $request)
    {
        $query = Teacher::with(['branch']);
        
        // Apply search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
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
        $columns = ['id', 'employee_id', 'first_name', 'phone', 'qualification', 'basic_salary', 'status'];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }
        
        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $teachers = $query->skip($start)->take($length)->get();
        
        // Format data
        $data = $teachers->map(function($teacher, $index) use ($start) {
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $teacher->id,
                'employee_id' => $teacher->employee_id,
                'full_name' => $teacher->full_name,
                'phone' => $teacher->phone,
                'email' => $teacher->email,
                'qualification' => $teacher->qualification,
                'branch' => $teacher->branch ? $teacher->branch->name : '-',
                'total_salary' => 'Rs. ' . number_format($teacher->total_salary, 2),
                'status' => '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $this->getStatusClass($teacher->status) . '">' . ucfirst(str_replace('_', ' ', $teacher->status)) . '</span>',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <a href="' . route('teachers.edit', $teacher->id) . '" class="text-blue-600 hover:text-blue-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        <button onclick="deleteTeacher(' . $teacher->id . ')" class="text-red-600 hover:text-red-800">
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
            'on_leave' => 'bg-yellow-100 text-yellow-800',
            'resigned' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Show the form for creating a new teacher
     */
    public function create()
    {
        $branches = Branch::active()->get(['id', 'name']);
        $subjects = Subject::active()->get(['id', 'name']);
        
        return Inertia::render('Teachers/Create', [
            'branches' => $branches,
            'subjects' => $subjects
        ]);
    }

    /**
     * Store a newly created teacher
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'employee_id' => 'required|string|max:50|unique:teachers,employee_id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'father_name' => 'nullable|string|max:100',
            'cnic' => 'required|string|max:20|unique:teachers,cnic',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'phone' => 'required|string|max:20',
            'emergency_contact' => 'nullable|string|max:20',
            'email' => 'required|email|max:255|unique:teachers,email',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'qualification' => 'required|string|max:255',
            'experience_years' => 'nullable|integer|min:0',
            'specialization' => 'nullable|string|max:255',
            'joining_date' => 'required|date',
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'bank_account' => 'nullable|string|max:50',
            'photo' => 'nullable|image|max:2048',
            'documents' => 'nullable|array',
            'status' => 'required|in:active,inactive,on_leave,resigned',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('teachers', 'public');
        }

        $teacher = Teacher::create($validated);

        // Attach subjects if provided
        if ($request->has('subjects') && is_array($request->subjects)) {
            $teacher->subjects()->attach($request->subjects);
        }

        return redirect()
            ->route('teachers.index')
            ->with('success', 'Teacher created successfully!');
    }

    /**
     * Display the specified teacher
     */
    public function show(Teacher $teacher)
    {
        return Inertia::render('Teachers/Show', [
            'teacher' => $teacher->load([
                'branch',
                'subjects',
                'classesAsClassTeacher',
                'sectionsAsSectionTeacher'
            ]),
            'stats' => [
                'total_subjects' => $teacher->subjects()->count(),
                'total_classes' => $teacher->classesAsClassTeacher()->count(),
                'total_sections' => $teacher->sectionsAsSectionTeacher()->count(),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified teacher
     */
    public function edit(Teacher $teacher)
    {
        $branches = Branch::active()->get(['id', 'name']);
        $subjects = Subject::active()->get(['id', 'name']);
        $teacherSubjects = $teacher->subjects->pluck('id')->toArray();
        
        return Inertia::render('Teachers/Edit', [
            'teacher' => $teacher,
            'branches' => $branches,
            'subjects' => $subjects,
            'teacherSubjects' => $teacherSubjects
        ]);
    }

    /**
     * Update the specified teacher
     */
    public function update(Request $request, Teacher $teacher)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'employee_id' => 'required|string|max:50|unique:teachers,employee_id,' . $teacher->id,
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'father_name' => 'nullable|string|max:100',
            'cnic' => 'required|string|max:20|unique:teachers,cnic,' . $teacher->id,
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'phone' => 'required|string|max:20',
            'emergency_contact' => 'nullable|string|max:20',
            'email' => 'required|email|max:255|unique:teachers,email,' . $teacher->id,
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'qualification' => 'required|string|max:255',
            'experience_years' => 'nullable|integer|min:0',
            'specialization' => 'nullable|string|max:255',
            'joining_date' => 'required|date',
            'resignation_date' => 'nullable|date',
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'bank_account' => 'nullable|string|max:50',
            'photo' => 'nullable|image|max:2048',
            'documents' => 'nullable|array',
            'status' => 'required|in:active,inactive,on_leave,resigned',
        ]);

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($teacher->photo) {
                Storage::disk('public')->delete($teacher->photo);
            }
            $validated['photo'] = $request->file('photo')->store('teachers', 'public');
        }

        $teacher->update($validated);

        // Sync subjects if provided
        if ($request->has('subjects') && is_array($request->subjects)) {
            $teacher->subjects()->sync($request->subjects);
        }

        return redirect()
            ->route('teachers.index')
            ->with('success', 'Teacher updated successfully!');
    }

    /**
     * Remove the specified teacher
     */
    public function destroy(Teacher $teacher)
    {
        // Check if teacher has assignments or is class teacher
        if ($teacher->assignments()->count() > 0) {
            return back()->with('error', 'Cannot delete teacher with existing assignments!');
        }

        if ($teacher->classesAsClassTeacher()->count() > 0) {
            return back()->with('error', 'Cannot delete teacher who is a class teacher!');
        }

        // Delete photo if exists
        if ($teacher->photo) {
            Storage::disk('public')->delete($teacher->photo);
        }

        $teacher->delete();

        return redirect()
            ->route('teachers.index')
            ->with('success', 'Teacher deleted successfully!');
    }

    /**
     * Get teachers for dropdown (API endpoint)
     */
    public function dropdown(Request $request)
    {
        $query = Teacher::active()
            ->select('id', 'employee_id', 'first_name', 'last_name');
        
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        $teachers = $query->orderBy('first_name')->get();

        return response()->json($teachers);
    }
}