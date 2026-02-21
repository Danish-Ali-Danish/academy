<?php

namespace App\Http\Controllers;

use App\Models\ExamResult;
use App\Models\Exam;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamResultController extends Controller
{
    /**
     * Display a listing of exam results
     */
    public function index(Request $request)
    {
        // Mobile request
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileResults($request);
        }

        // DataTables AJAX request
        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesResults($request);
        }

        return Inertia::render('ExamResults/Index', [
            'exams' => Exam::select('id', 'name', 'exam_code')->latest()->get(),
        ]);
    }

    private function getMobileResults(Request $request)
    {
        $query = ExamResult::with([
            'exam:id,name,exam_code',
            'student:id,first_name,last_name,roll_number',
            'subject:id,name',
        ]);
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('roll_number', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }
        
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        
        $perPage = $request->get('per_page', 10);
        $results = $query->latest()->paginate($perPage);
        
        return response()->json($results);
    }

    private function getDataTablesResults(Request $request)
    {
        $query = ExamResult::with([
            'exam:id,name,exam_code',
            'student:id,first_name,last_name,roll_number',
            'subject:id,name',
        ]);
        
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->whereHas('student', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('roll_number', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }
        
        $totalData = $query->count();
        
        $orderColumn = $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = ['id', 'exam_id', 'student_id', 'subject_id', 'obtained_marks', 'grade'];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }
        
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $results = $query->skip($start)->take($length)->get();
        
        $data = $results->map(function($result, $index) use ($start) {
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $result->id,
                'exam' => $result->exam->name ?? 'N/A',
                'student' => $result->student->first_name . ' ' . $result->student->last_name,
                'roll_number' => $result->student->roll_number,
                'subject' => $result->subject->name ?? 'N/A',
                'theory_marks' => $result->theory_marks ?? '—',
                'practical_marks' => $result->practical_marks ?? '—',
                'obtained_marks' => $result->obtained_marks,
                'total_marks' => $result->total_marks,
                'percentage' => number_format($result->percentage, 2) . '%',
                'grade' => '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $this->getGradeClass($result->grade) . '">' . ($result->grade ?? 'N/A') . '</span>',
                'status' => $result->is_pass 
                    ? '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Pass</span>'
                    : '<span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Fail</span>',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <a href="' . route('exam-results.edit', $result->id) . '" class="text-blue-600 hover:text-blue-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        <button onclick="deleteResult(' . $result->id . ')" class="text-red-600 hover:text-red-800">
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
    
    private function getGradeClass($grade)
    {
        return match($grade) {
            'A+', 'A' => 'bg-green-100 text-green-800',
            'B+', 'B' => 'bg-blue-100 text-blue-800',
            'C+', 'C' => 'bg-yellow-100 text-yellow-800',
            'D', 'F' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function create()
    {
        return Inertia::render('ExamResults/Create', [
            'exams' => Exam::with('class')->select('id', 'name', 'exam_code', 'class_id')->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'results' => 'required|array',
            'results.*.student_id' => 'required|exists:students,id',
            'results.*.subject_id' => 'required|exists:subjects,id',
            'results.*.theory_marks' => 'nullable|numeric|min:0',
            'results.*.practical_marks' => 'nullable|numeric|min:0',
            'results.*.obtained_marks' => 'required|numeric|min:0',
            'results.*.total_marks' => 'required|integer|min:1',
            'results.*.is_absent' => 'boolean',
            'results.*.remarks' => 'nullable|string|max:500',
        ]);

        $exam = Exam::findOrFail($validated['exam_id']);

        DB::beginTransaction();
        try {
            foreach ($validated['results'] as $resultData) {
                $result = ExamResult::updateOrCreate(
                    [
                        'exam_id' => $validated['exam_id'],
                        'student_id' => $resultData['student_id'],
                        'subject_id' => $resultData['subject_id'],
                    ],
                    [
                        'branch_id' => $exam->branch_id,
                        'theory_marks' => $resultData['theory_marks'] ?? null,
                        'practical_marks' => $resultData['practical_marks'] ?? null,
                        'obtained_marks' => $resultData['obtained_marks'],
                        'total_marks' => $resultData['total_marks'],
                        'is_absent' => $resultData['is_absent'] ?? false,
                        'remarks' => $resultData['remarks'] ?? null,
                        'entered_by' => Auth::id(),
                    ]
                );

                // Calculate and assign grade
                $result->calculateGrade();
            }

            // Calculate class ranks
            ExamResult::calculateClassRanks($validated['exam_id'], $exam->class_id);

            DB::commit();

            return redirect()
                ->route('exam-results.index')
                ->with('success', 'Results entered successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to save results: ' . $e->getMessage());
        }
    }

    public function show(ExamResult $examResult)
    {
        return Inertia::render('ExamResults/Show', [
            'result' => $examResult->load([
                'exam',
                'student',
                'subject',
                'branch',
                'enteredBy'
            ])
        ]);
    }

    public function edit(ExamResult $examResult)
    {
        return Inertia::render('ExamResults/Edit', [
            'result' => $examResult->load(['exam', 'student', 'subject']),
        ]);
    }

    public function update(Request $request, ExamResult $examResult)
    {
        $validated = $request->validate([
            'theory_marks' => 'nullable|numeric|min:0',
            'practical_marks' => 'nullable|numeric|min:0',
            'obtained_marks' => 'required|numeric|min:0',
            'total_marks' => 'required|integer|min:1',
            'is_absent' => 'boolean',
            'remarks' => 'nullable|string|max:500',
        ]);

        $examResult->update($validated);
        $examResult->calculateGrade();

        // Recalculate ranks for the class
        $exam = $examResult->exam;
        ExamResult::calculateClassRanks($exam->id, $exam->class_id);

        return redirect()
            ->route('exam-results.index')
            ->with('success', 'Result updated successfully!');
    }

    public function destroy(ExamResult $examResult)
    {
        $examResult->delete();

        return redirect()
            ->route('exam-results.index')
            ->with('success', 'Result deleted successfully!');
    }

    /**
     * Get student report card
     */
    public function reportCard(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'student_id' => 'required|exists:students,id',
        ]);

        $exam = Exam::with(['class', 'branch'])->findOrFail($request->exam_id);
        $student = Student::with(['class', 'section'])->findOrFail($request->student_id);
        
        $results = ExamResult::where('exam_id', $request->exam_id)
            ->where('student_id', $request->student_id)
            ->with('subject')
            ->get();

        $totalObtained = $results->sum('obtained_marks');
        $totalMarks = $results->sum('total_marks');
        $percentage = $totalMarks > 0 ? ($totalObtained / $totalMarks) * 100 : 0;

        return Inertia::render('ExamResults/ReportCard', [
            'exam' => $exam,
            'student' => $student,
            'results' => $results,
            'summary' => [
                'total_obtained' => $totalObtained,
                'total_marks' => $totalMarks,
                'percentage' => round($percentage, 2),
                'rank' => $results->first()->rank_in_class ?? null,
            ]
        ]);
    }

    /**
     * Get students by exam for result entry
     */
    public function getStudentsByExam(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
        ]);

        $exam = Exam::with('examSubjects.subject')->findOrFail($request->exam_id);
        
        $students = Student::where('class_id', $exam->class_id)
            ->where('status', 'active')
            ->select('id', 'first_name', 'last_name', 'roll_number', 'section_id')
            ->orderBy('roll_number')
            ->get();

        return response()->json([
            'students' => $students,
            'subjects' => $exam->examSubjects,
        ]);
    }
}