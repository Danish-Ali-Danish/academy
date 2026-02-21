<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ExamController extends Controller
{
    /**
     * Display a listing of exams
     */
    public function index(Request $request)
    {
        // Mobile request
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileExams($request);
        }

        // DataTables AJAX request
        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesExams($request);
        }

        // Initial page load
        return Inertia::render('Exams/Index', [
            'branches' => Branch::active()->select('id', 'name')->get(),
            'classes' => Classes::active()->select('id', 'name')->get(),
        ]);
    }

    private function getMobileExams(Request $request)
    {
        $query = Exam::with(['branch:id,name', 'class:id,name']);
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('exam_code', 'like', "%{$search}%")
                  ->orWhere('exam_type', 'like', "%{$search}%");
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
        
        if ($request->filled('exam_type')) {
            $query->where('exam_type', $request->exam_type);
        }
        
        $perPage = $request->get('per_page', 10);
        $exams = $query->latest()->paginate($perPage);
        
        return response()->json($exams);
    }

    private function getDataTablesExams(Request $request)
    {
        $query = Exam::with(['branch:id,name', 'class:id,name']);
        
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('exam_code', 'like', "%{$search}%")
                  ->orWhere('exam_type', 'like', "%{$search}%");
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
        
        $totalData = $query->count();
        
        $orderColumn = $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = ['id', 'name', 'exam_code', 'exam_type', 'start_date', 'end_date', 'status'];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }
        
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $exams = $query->skip($start)->take($length)->get();
        
        $data = $exams->map(function($exam, $index) use ($start) {
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $exam->id,
                'name' => $exam->name,
                'exam_code' => $exam->exam_code,
                'exam_type' => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">' . ucfirst($exam->exam_type) . '</span>',
                'class' => $exam->class->name ?? 'N/A',
                'branch' => $exam->branch->name ?? 'N/A',
                'start_date' => \Carbon\Carbon::parse($exam->start_date)->format('d M Y'),
                'end_date' => \Carbon\Carbon::parse($exam->end_date)->format('d M Y'),
                'status' => '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $this->getStatusClass($exam->status) . '">' . ucfirst($exam->status) . '</span>',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <a href="' . route('exams.show', $exam->id) . '" class="text-green-600 hover:text-green-800" title="View">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </a>
                        <a href="' . route('exams.edit', $exam->id) . '" class="text-blue-600 hover:text-blue-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        <button onclick="deleteExam(' . $exam->id . ')" class="text-red-600 hover:text-red-800">
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
            'scheduled' => 'bg-blue-100 text-blue-800',
            'ongoing' => 'bg-yellow-100 text-yellow-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function create()
    {
        return Inertia::render('Exams/Create', [
            'branches' => Branch::active()->select('id', 'name')->get(),
            'classes' => Classes::active()->select('id', 'name')->get(),
            'subjects' => Subject::active()->select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'class_id' => 'required|exists:classes,id',
            'name' => 'required|string|max:255',
            'exam_code' => 'required|string|max:50|unique:exams,exam_code',
            'exam_type' => 'required|in:monthly,mid_term,final,annual,mock',
            'academic_year' => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'result_date' => 'nullable|date|after:end_date',
            'total_marks' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'status' => 'required|in:scheduled,ongoing,completed,cancelled',
            'subjects' => 'nullable|array',
            'subjects.*.subject_id' => 'required|exists:subjects,id',
            'subjects.*.exam_date' => 'required|date',
            'subjects.*.exam_time' => 'required|date_format:H:i',
            'subjects.*.duration_minutes' => 'required|integer|min:1',
            'subjects.*.total_marks' => 'required|integer|min:1',
            'subjects.*.theory_marks' => 'nullable|integer|min:0',
            'subjects.*.practical_marks' => 'nullable|integer|min:0',
            'subjects.*.pass_marks' => 'required|integer|min:1',
        ]);

        $exam = Exam::create([
            'branch_id' => $validated['branch_id'],
            'class_id' => $validated['class_id'],
            'name' => $validated['name'],
            'exam_code' => $validated['exam_code'],
            'exam_type' => $validated['exam_type'],
            'academic_year' => $validated['academic_year'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'result_date' => $validated['result_date'] ?? null,
            'total_marks' => $validated['total_marks'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'created_by' => Auth::id(),
        ]);

        // Attach subjects
        if (isset($validated['subjects'])) {
            foreach ($validated['subjects'] as $subject) {
                $exam->examSubjects()->create([
                    'subject_id' => $subject['subject_id'],
                    'exam_date' => $subject['exam_date'],
                    'exam_time' => $subject['exam_time'],
                    'duration_minutes' => $subject['duration_minutes'],
                    'total_marks' => $subject['total_marks'],
                    'theory_marks' => $subject['theory_marks'] ?? null,
                    'practical_marks' => $subject['practical_marks'] ?? null,
                    'pass_marks' => $subject['pass_marks'],
                ]);
            }
        }

        return redirect()
            ->route('exams.index')
            ->with('success', 'Exam created successfully!');
    }

    public function show(Exam $exam)
    {
        return Inertia::render('Exams/Show', [
            'exam' => $exam->load([
                'branch',
                'class',
                'examSubjects.subject',
                'results.student',
                'createdBy'
            ]),
            'stats' => [
                'total_subjects' => $exam->examSubjects()->count(),
                'total_students' => $exam->results()->distinct('student_id')->count(),
                'results_entered' => $exam->results()->count(),
            ]
        ]);
    }

    public function edit(Exam $exam)
    {
        return Inertia::render('Exams/Edit', [
            'exam' => $exam->load('examSubjects.subject'),
            'branches' => Branch::active()->select('id', 'name')->get(),
            'classes' => Classes::active()->select('id', 'name')->get(),
            'subjects' => Subject::active()->select('id', 'name')->get(),
        ]);
    }

    public function update(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'class_id' => 'required|exists:classes,id',
            'name' => 'required|string|max:255',
            'exam_code' => 'required|string|max:50|unique:exams,exam_code,' . $exam->id,
            'exam_type' => 'required|in:monthly,mid_term,final,annual,mock',
            'academic_year' => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'result_date' => 'nullable|date|after:end_date',
            'total_marks' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'status' => 'required|in:scheduled,ongoing,completed,cancelled',
        ]);

        $exam->update($validated);

        return redirect()
            ->route('exams.index')
            ->with('success', 'Exam updated successfully!');
    }

    public function destroy(Exam $exam)
    {
        // Check if results exist
        if ($exam->results()->count() > 0) {
            return back()->with('error', 'Cannot delete exam with existing results!');
        }

        $exam->delete();

        return redirect()
            ->route('exams.index')
            ->with('success', 'Exam deleted successfully!');
    }

    public function dropdown()
    {
        $exams = Exam::select('id', 'name', 'exam_code')
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json($exams);
    }
}