<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        // Mobile pagination request
        if ($request->ajax() && $request->has('mobile')) {
            $perPage = $request->get('per_page', 10);
            
            $query = Subject::query()->select('subjects.*');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('subject_name', 'like', "%{$search}%")
                      ->orWhere('subject_code', 'like', "%{$search}%");
                });
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // ✅ Filter by compulsory
            if ($request->filled('is_compulsory')) {
                $query->where('is_compulsory', $request->is_compulsory);
            }

            $query->orderBy('is_compulsory', 'desc')  // ✅ Compulsory first
                  ->orderBy('subject_name');

            return response()->json($query->paginate($perPage));
        }

        // DataTables AJAX request
        if ($request->has('draw') && $request->has('columns')) {
            $query = Subject::query()->select('subjects.*');

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // ✅ Filter by compulsory
            if ($request->filled('is_compulsory')) {
                $query->where('is_compulsory', $request->is_compulsory);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('subject_code', function ($subject) {
                    return $subject->subject_code 
                        ? '<span class="font-mono text-sm text-gray-700">' . $subject->subject_code . '</span>'
                        : '<span class="text-gray-400">—</span>';
                })
                // ✅ Add compulsory badge
                ->addColumn('is_compulsory', function ($subject) {
                    if ($subject->is_compulsory) {
                        return '
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Compulsory
                            </span>
                        ';
                    } else {
                        return '
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                Optional
                            </span>
                        ';
                    }
                })
                ->editColumn('is_active', function ($subject) {
                    if ($subject->is_active) {
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
                ->addColumn('action', function ($subject) {
                    return '
                        <div class="flex items-center gap-2 justify-center">
                            <button onclick=\'editSubject(' . json_encode([
                                'id' => $subject->id,
                                'subject_name' => $subject->subject_name,
                                'subject_code' => $subject->subject_code,
                                'is_compulsory' => $subject->is_compulsory,  // ✅ Added
                                'is_active' => $subject->is_active
                            ]) . ')\' 
                               class="text-blue-600 hover:text-blue-900 transition"
                               title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button onclick="deleteSubject(' . $subject->id . ')" 
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
                ->rawColumns(['subject_code', 'is_compulsory', 'is_active', 'action'])  // ✅ Added is_compulsory
                ->make(true);
        }

        return Inertia::render('Subjects/Index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_name' => 'required|string|max:100',
            'subject_code' => 'nullable|string|max:20|unique:subjects,subject_code',
            'is_compulsory' => 'boolean',  // ✅ Added
            'is_active' => 'boolean',
        ], [
            'subject_name.required' => 'Subject name is required',
            'subject_name.max' => 'Subject name cannot exceed 100 characters',
            'subject_code.unique' => 'This subject code is already in use',
            'subject_code.max' => 'Subject code cannot exceed 20 characters',
        ]);

        $validated['is_compulsory'] = $validated['is_compulsory'] ?? false;  // ✅ Default false
        $validated['is_active'] = $validated['is_active'] ?? true;

        Subject::create($validated);

        return back()->with('success', 'Subject created successfully!');
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'subject_name' => 'required|string|max:100',
            'subject_code' => 'nullable|string|max:20|unique:subjects,subject_code,' . $subject->id,
            'is_compulsory' => 'boolean',  // ✅ Added
            'is_active' => 'boolean',
        ], [
            'subject_name.required' => 'Subject name is required',
            'subject_name.max' => 'Subject name cannot exceed 100 characters',
            'subject_code.unique' => 'This subject code is already in use',
            'subject_code.max' => 'Subject code cannot exceed 20 characters',
        ]);

        $subject->update($validated);

        return back()->with('success', 'Subject updated successfully!');
    }

    public function destroy(Subject $subject)
    {
        $classCount = $subject->classSubjects()->count();
        if ($classCount > 0) {
            return back()->with('error', "Cannot delete subject. It is assigned to {$classCount} class(es)!");
        }

        $subject->delete();

        return back()->with('success', 'Subject deleted successfully!');
    }

    public function dropdown()
    {
        $subjects = Subject::active()
            ->select('id', 'subject_name', 'subject_code', 'is_compulsory')  // ✅ Added is_compulsory
            ->orderBy('is_compulsory', 'desc')  // ✅ Compulsory subjects first
            ->orderBy('subject_name')
            ->get();

        return response()->json($subjects);
    }
}