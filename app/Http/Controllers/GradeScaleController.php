<?php

namespace App\Http\Controllers;

use App\Models\GradeScale;
use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GradeScaleController extends Controller
{
    /**
     * Display a listing of grade scales
     */
    public function index(Request $request)
    {
        // Mobile request
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileGradeScales($request);
        }

        // DataTables AJAX request
        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesGradeScales($request);
        }

        return Inertia::render('GradeScales/Index', [
            'branches' => Branch::active()->select('id', 'name')->get(),
        ]);
    }

    private function getMobileGradeScales(Request $request)
    {
        $query = GradeScale::with('branch:id,name');
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('grade', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        $perPage = $request->get('per_page', 10);
        $gradeScales = $query->orderBy('min_percentage', 'desc')->paginate($perPage);
        
        return response()->json($gradeScales);
    }

    private function getDataTablesGradeScales(Request $request)
    {
        $query = GradeScale::with('branch:id,name');
        
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('grade', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        $totalData = $query->count();
        
        $orderColumn = $request->input('order.0.column', 2);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = ['id', 'name', 'min_percentage', 'max_percentage', 'grade', 'grade_point', 'status'];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }
        
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $gradeScales = $query->skip($start)->take($length)->get();
        
        $data = $gradeScales->map(function($scale, $index) use ($start) {
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $scale->id,
                'name' => $scale->name,
                'branch' => $scale->branch->name ?? 'All Branches',
                'percentage_range' => number_format($scale->min_percentage, 2) . '% - ' . number_format($scale->max_percentage, 2) . '%',
                'grade' => '<span class="px-3 py-1 text-sm font-bold rounded ' . $this->getGradeClass($scale->grade) . '">' . $scale->grade . '</span>',
                'grade_point' => number_format($scale->grade_point, 2),
                'remarks' => $scale->remarks ?? '—',
                'status' => '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $this->getStatusClass($scale->status) . '">' . ucfirst($scale->status) . '</span>',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <a href="' . route('grade-scales.edit', $scale->id) . '" class="text-blue-600 hover:text-blue-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        <button onclick="deleteGradeScale(' . $scale->id . ')" class="text-red-600 hover:text-red-800">
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
            'A+' => 'bg-emerald-500 text-white',
            'A' => 'bg-green-500 text-white',
            'B+' => 'bg-blue-500 text-white',
            'B' => 'bg-cyan-500 text-white',
            'C+' => 'bg-yellow-500 text-white',
            'C' => 'bg-orange-500 text-white',
            'D' => 'bg-red-400 text-white',
            'F' => 'bg-red-600 text-white',
            default => 'bg-gray-500 text-white'
        };
    }
    
    private function getStatusClass($status)
    {
        return match($status) {
            'active' => 'bg-green-100 text-green-800',
            'inactive' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function create()
    {
        return Inertia::render('GradeScales/Create', [
            'branches' => Branch::active()->select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'required|string|max:255',
            'min_percentage' => 'required|numeric|min:0|max:100',
            'max_percentage' => 'required|numeric|min:0|max:100|gte:min_percentage',
            'grade' => 'required|string|max:5',
            'grade_point' => 'required|numeric|min:0|max:10',
            'remarks' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
        ]);

        // Check for overlapping percentage ranges
        $overlapping = GradeScale::where('branch_id', $validated['branch_id'] ?? null)
            ->where('status', 'active')
            ->where(function($query) use ($validated) {
                $query->whereBetween('min_percentage', [$validated['min_percentage'], $validated['max_percentage']])
                    ->orWhereBetween('max_percentage', [$validated['min_percentage'], $validated['max_percentage']])
                    ->orWhere(function($q) use ($validated) {
                        $q->where('min_percentage', '<=', $validated['min_percentage'])
                          ->where('max_percentage', '>=', $validated['max_percentage']);
                    });
            })
            ->exists();

        if ($overlapping) {
            return back()
                ->withInput()
                ->with('error', 'Percentage range overlaps with existing grade scale!');
        }

        GradeScale::create($validated);

        return redirect()
            ->route('grade-scales.index')
            ->with('success', 'Grade scale created successfully!');
    }

    public function show(GradeScale $gradeScale)
    {
        return Inertia::render('GradeScales/Show', [
            'gradeScale' => $gradeScale->load('branch')
        ]);
    }

    public function edit(GradeScale $gradeScale)
    {
        return Inertia::render('GradeScales/Edit', [
            'gradeScale' => $gradeScale,
            'branches' => Branch::active()->select('id', 'name')->get(),
        ]);
    }

    public function update(Request $request, GradeScale $gradeScale)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'required|string|max:255',
            'min_percentage' => 'required|numeric|min:0|max:100',
            'max_percentage' => 'required|numeric|min:0|max:100|gte:min_percentage',
            'grade' => 'required|string|max:5',
            'grade_point' => 'required|numeric|min:0|max:10',
            'remarks' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
        ]);

        // Check for overlapping percentage ranges (excluding current record)
        $overlapping = GradeScale::where('branch_id', $validated['branch_id'] ?? null)
            ->where('status', 'active')
            ->where('id', '!=', $gradeScale->id)
            ->where(function($query) use ($validated) {
                $query->whereBetween('min_percentage', [$validated['min_percentage'], $validated['max_percentage']])
                    ->orWhereBetween('max_percentage', [$validated['min_percentage'], $validated['max_percentage']])
                    ->orWhere(function($q) use ($validated) {
                        $q->where('min_percentage', '<=', $validated['min_percentage'])
                          ->where('max_percentage', '>=', $validated['max_percentage']);
                    });
            })
            ->exists();

        if ($overlapping) {
            return back()
                ->withInput()
                ->with('error', 'Percentage range overlaps with existing grade scale!');
        }

        $gradeScale->update($validated);

        return redirect()
            ->route('grade-scales.index')
            ->with('success', 'Grade scale updated successfully!');
    }

    public function destroy(GradeScale $gradeScale)
    {
        $gradeScale->delete();

        return redirect()
            ->route('grade-scales.index')
            ->with('success', 'Grade scale deleted successfully!');
    }

    /**
     * Get grade for a percentage
     */
    public function getGrade(Request $request)
    {
        $request->validate([
            'percentage' => 'required|numeric|min:0|max:100',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $grade = GradeScale::getGradeForPercentage(
            $request->percentage,
            $request->branch_id ?? null
        );

        return response()->json($grade);
    }

    /**
     * Get active grade scales dropdown
     */
    public function dropdown(Request $request)
    {
        $query = GradeScale::active()
            ->select('id', 'name', 'grade', 'min_percentage', 'max_percentage', 'grade_point')
            ->orderBy('min_percentage', 'desc');

        if ($request->filled('branch_id')) {
            $query->where(function($q) use ($request) {
                $q->where('branch_id', $request->branch_id)
                  ->orWhereNull('branch_id');
            });
        }

        return response()->json($query->get());
    }
}