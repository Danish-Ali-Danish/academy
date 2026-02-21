<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display a listing of attendance records
     */
    public function index(Request $request)
    {
        // Mobile request
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileAttendance($request);
        }

        // DataTables AJAX request
        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesAttendance($request);
        }

        // Initial Inertia page load
        return Inertia::render('Attendance/Index', [
            'branches' => Branch::active()->select('id', 'name')->get(),
            'classes' => Classes::active()->select('id', 'name')->get(),
        ]);
    }

    private function getMobileAttendance(Request $request)
    {
        $query = Attendance::with([
            'student:id,first_name,last_name,roll_number',
            'class:id,name',
            'section:id,name',
            'branch:id,name'
        ]);
        
        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('roll_number', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }
        
        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }
        
        $perPage = $request->get('per_page', 10);
        $attendance = $query->latest('date')->paginate($perPage);
        
        return response()->json($attendance);
    }

    private function getDataTablesAttendance(Request $request)
    {
        $query = Attendance::with([
            'student:id,first_name,last_name,roll_number',
            'class:id,name',
            'section:id,name',
            'branch:id,name'
        ]);
        
        // Apply search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->whereHas('student', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('roll_number', 'like', "%{$search}%");
            });
        }
        
        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        
        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }
        
        $totalData = $query->count();
        
        // Sorting
        $orderColumn = $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = ['id', 'date', 'student_id', 'status', 'check_in_time', 'check_out_time'];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }
        
        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $attendance = $query->skip($start)->take($length)->get();
        
        // Format data
        $data = $attendance->map(function($record, $index) use ($start) {
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $record->id,
                'date' => \Carbon\Carbon::parse($record->date)->format('d M Y'),
                'student' => $record->student->first_name . ' ' . $record->student->last_name,
                'roll_number' => $record->student->roll_number,
                'class' => $record->class->name ?? 'N/A',
                'section' => $record->section->name ?? 'N/A',
                'branch' => $record->branch->name ?? 'N/A',
                'check_in' => $record->check_in_time ? $record->check_in_time->format('h:i A') : '—',
                'check_out' => $record->check_out_time ? $record->check_out_time->format('h:i A') : '—',
                'status' => '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $this->getStatusClass($record->status) . '">' . ucfirst($record->status) . '</span>',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <a href="' . route('attendance.edit', $record->id) . '" class="text-blue-600 hover:text-blue-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        <button onclick="deleteAttendance(' . $record->id . ')" class="text-red-600 hover:text-red-800">
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
            'present' => 'bg-green-100 text-green-800',
            'absent' => 'bg-red-100 text-red-800',
            'late' => 'bg-yellow-100 text-yellow-800',
            'leave' => 'bg-blue-100 text-blue-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Show the form for marking attendance
     */
    public function create()
    {
        return Inertia::render('Attendance/Create', [
            'branches' => Branch::active()->select('id', 'name')->get(),
            'classes' => Classes::active()->select('id', 'name')->get(),
        ]);
    }

    /**
     * Store attendance records
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*.student_id' => 'required|exists:students,id',
            'attendance.*.status' => 'required|in:present,absent,late,leave',
            'attendance.*.check_in_time' => 'nullable|date_format:H:i',
            'attendance.*.check_out_time' => 'nullable|date_format:H:i',
            'attendance.*.leave_type' => 'nullable|string',
            'attendance.*.remarks' => 'nullable|string|max:500',
        ]);

        foreach ($validated['attendance'] as $record) {
            Attendance::updateOrCreate(
                [
                    'student_id' => $record['student_id'],
                    'date' => $validated['date'],
                ],
                [
                    'class_id' => $validated['class_id'],
                    'section_id' => $validated['section_id'],
                    'branch_id' => $validated['branch_id'],
                    'status' => $record['status'],
                    'check_in_time' => $record['check_in_time'] ?? null,
                    'check_out_time' => $record['check_out_time'] ?? null,
                    'leave_type' => $record['leave_type'] ?? null,
                    'remarks' => $record['remarks'] ?? null,
                    'marked_by' => Auth::id(),
                ]
            );
        }

        return redirect()
            ->route('attendance.index')
            ->with('success', 'Attendance marked successfully!');
    }

    /**
     * Display the specified attendance
     */
    public function show(Attendance $attendance)
    {
        return Inertia::render('Attendance/Show', [
            'attendance' => $attendance->load([
                'student',
                'class',
                'section',
                'branch',
                'markedBy'
            ])
        ]);
    }

    /**
     * Show the form for editing attendance
     */
    public function edit(Attendance $attendance)
    {
        return Inertia::render('Attendance/Edit', [
            'attendance' => $attendance->load(['student', 'class', 'section', 'branch']),
            'branches' => Branch::active()->select('id', 'name')->get(),
        ]);
    }

    /**
     * Update the specified attendance
     */
    public function update(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'status' => 'required|in:present,absent,late,leave',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'leave_type' => 'nullable|string',
            'remarks' => 'nullable|string|max:500',
        ]);

        $attendance->update($validated);

        return redirect()
            ->route('attendance.index')
            ->with('success', 'Attendance updated successfully!');
    }

    /**
     * Remove the specified attendance
     */
    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        return redirect()
            ->route('attendance.index')
            ->with('success', 'Attendance deleted successfully!');
    }

    /**
     * Get students by class and section for attendance marking
     */
    public function getStudentsBySection(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'date' => 'required|date',
        ]);

        $students = Student::where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('status', 'active')
            ->select('id', 'first_name', 'last_name', 'roll_number')
            ->orderBy('roll_number')
            ->get();

        // Check if attendance already marked for this date
        $existingAttendance = Attendance::where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('date', $request->date)
            ->get()
            ->keyBy('student_id');

        $studentsWithAttendance = $students->map(function($student) use ($existingAttendance) {
            $attendance = $existingAttendance->get($student->id);
            return [
                'id' => $student->id,
                'name' => $student->first_name . ' ' . $student->last_name,
                'roll_number' => $student->roll_number,
                'status' => $attendance->status ?? 'present',
                'check_in_time' => $attendance ? optional($attendance->check_in_time)->format('H:i') : null,
                'check_out_time' => $attendance ? optional($attendance->check_out_time)->format('H:i') : null,
                'remarks' => $attendance->remarks ?? null,
            ];
        });

        return response()->json($studentsWithAttendance);
    }

    /**
     * Get attendance report
     */
    public function report(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'class_id' => 'nullable|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
        ]);

        $query = Attendance::byDateRange($request->start_date, $request->end_date);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        $stats = [
            'total' => $query->count(),
            'present' => (clone $query)->present()->count(),
            'absent' => (clone $query)->absent()->count(),
            'late' => (clone $query)->where('status', 'late')->count(),
            'leave' => (clone $query)->leave()->count(),
        ];

        return response()->json($stats);
    }
}