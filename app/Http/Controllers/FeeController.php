<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Models\Student;
use App\Models\FeeType;
use App\Models\Branch;
use App\Models\Classes;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class FeeController extends Controller
{
    /**
     * Display a listing of fees
     */
    public function index(Request $request)
    {
        // Check if it's a mobile request
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileFees($request);
        }
        
        // DataTables AJAX request
        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesFees($request);
        }
        
        // Check specifically for DataTables request (not generic AJAX)
        if ($request->has('draw')) {
            $fees = Fee::with(['student', 'feeType', 'class', 'branch']);

            return DataTables::of($fees)
                ->addIndexColumn()
                ->addColumn('action', function ($fee) {
                    return '
                        <div class="flex items-center gap-2">
                            <a href="' . route('fees.edit', $fee->id) . '" 
                               class="text-blue-600 hover:text-blue-800"
                               title="Edit">
                                <i class="fas fa-edit"></i> edit
                            </a>
                            <button onclick="deleteFee(' . $fee->id . ')" 
                                    class="text-red-600 hover:text-red-800"
                                    title="Delete">
                                <i class="fas fa-trash"></i> delete
                            </button>
                        </div>
                    ';
                })
                ->editColumn('status', function ($fee) {
                    $colors = [
                        'pending' => 'yellow',
                        'partial' => 'blue',
                        'paid' => 'green',
                        'cancelled' => 'red'
                    ];
                    $color = $colors[$fee->status] ?? 'gray';
                    
                    return '<span class="px-2 py-1 text-xs rounded-full bg-' . $color . '-100 text-' . $color . '-800">' 
                           . ucfirst($fee->status) . 
                           '</span>';
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }

        // For initial Inertia page load
        $branches = Branch::active()->get(['id', 'name']);
        $classes = Classes::active()->get(['id', 'name']);
        $feeTypes = FeeType::active()->get(['id', 'name']);
        
        return Inertia::render('Fees/Index', [
            'branches' => $branches,
            'classes' => $classes,
            'feeTypes' => $feeTypes
        ]);
    }

    private function getMobileFees(Request $request)
    {
        $query = Fee::with(['student', 'feeType', 'class', 'branch']);
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('admission_no', 'like', "%{$search}%");
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
        
        // Apply class filter
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        
        // Apply fee type filter
        if ($request->filled('fee_type_id')) {
            $query->where('fee_type_id', $request->fee_type_id);
        }
        
        // Apply month/year filter
        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }
        
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        
        // Paginate
        $perPage = $request->get('per_page', 10);
        $fees = $query->latest()->paginate($perPage);
        
        return response()->json($fees);
    }

    private function getDataTablesFees(Request $request)
    {
        $query = Fee::with(['student', 'feeType', 'class', 'branch']);
        
        // Apply search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->whereHas('student', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('admission_no', 'like', "%{$search}%");
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
        
        if ($request->filled('fee_type_id')) {
            $query->where('fee_type_id', $request->fee_type_id);
        }
        
        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }
        
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        
        // Get total count
        $totalData = $query->count();
        
        // Apply sorting
        $orderColumn = $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = ['id', 'student_id', 'fee_type_id', 'month', 'year', 'total_amount', 'paid_amount', 'status'];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }
        
        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $fees = $query->skip($start)->take($length)->get();
        
        // Format data
        $data = $fees->map(function($fee, $index) use ($start) {
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $fee->id,
                'student_name' => $fee->student ? $fee->student->full_name : '-',
                'admission_no' => $fee->student ? $fee->student->admission_no : '-',
                'fee_type' => $fee->feeType ? $fee->feeType->name : '-',
                'month_year' => date('M Y', strtotime("{$fee->year}-{$fee->month}-01")),
                'class' => $fee->class ? $fee->class->name : '-',
                'total_amount' => 'Rs. ' . number_format((float)($fee->total_amount ?? 0), 2),
                'paid_amount' => 'Rs. ' . number_format((float)($fee->paid_amount ?? 0), 2),
                'balance' => 'Rs. ' . number_format($fee->balance_amount, 2),
                'status' => '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $this->getStatusClass($fee->status) . '">' . ucfirst($fee->status) . '</span>',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <a href="' . route('fees.edit', $fee->id) . '" class="text-blue-600 hover:text-blue-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        <button onclick="deleteFee(' . $fee->id . ')" class="text-red-600 hover:text-red-800">
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
            'pending' => 'bg-yellow-100 text-yellow-800',
            'partial' => 'bg-blue-100 text-blue-800',
            'paid' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Show the form for creating a new fee
     */public function create()
{
    $branches = Branch::active()->get(['id', 'name']);
    $classes = Classes::active()->get(['id', 'name']);
    $feeTypes = FeeType::active()->get(['id', 'name', 'amount']);
    $students = Student::active()
        ->select('id', 'admission_no', 'first_name', 'last_name', 'class_id', 'branch_id')
        ->get()
        ->map(function($student) {
            return [
                'id' => $student->id,
                'admission_no' => $student->admission_no,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'class_id' => $student->class_id,
                'branch_id' => $student->branch_id,
            ];
        });
    
    return Inertia::render('Fees/Create', [
        'branches' => $branches,
        'classes' => $classes,
        'feeTypes' => $feeTypes,
        'students' => $students
    ]);
}

    /**
     * Store a newly created fee
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'class_id' => 'required|exists:classes,id',
            'branch_id' => 'required|exists:branches,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000',
            'academic_year' => 'nullable|string|max:20',
            'total_amount' => 'required|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string',
            'fine_amount' => 'nullable|numeric|min:0',
            'due_date' => 'required|date',
            'status' => 'required|in:pending,partial,paid,cancelled',
            'remarks' => 'nullable|string',
        ]);

        // Calculate discount amount if percent is provided
        if ($validated['discount_percent'] ?? 0 > 0) {
            $validated['discount_amount'] = ($validated['total_amount'] * $validated['discount_percent']) / 100;
        }

        $validated['paid_amount'] = 0;

        $fee = Fee::create($validated);

        return redirect()
            ->route('fees.index')
            ->with('success', 'Fee created successfully!');
    }

    /**
     * Display the specified fee
     */
    public function show(Fee $fee)
    {
        return Inertia::render('Fees/Show', [
            'fee' => $fee->load(['student', 'feeType', 'class', 'branch', 'payments']),
            'stats' => [
                'total_payments' => $fee->payments()->count(),
                'total_paid' => $fee->paid_amount,
                'balance' => $fee->balance_amount,
            ]
        ]);
    }

    /**
     * Show the form for editing the specified fee
     */
    public function edit(Fee $fee)
    {
        $branches = Branch::active()->get(['id', 'name']);
        $classes = Classes::active()->get(['id', 'name']);
        $feeTypes = FeeType::active()->get(['id', 'name', 'amount']);
        $students = Student::active()->get(['id', 'admission_no', 'first_name', 'last_name']);
        
        return Inertia::render('Fees/Edit', [
            'fee' => $fee->load(['student', 'feeType']),
            'branches' => $branches,
            'classes' => $classes,
            'feeTypes' => $feeTypes,
            'students' => $students
        ]);
    }

    /**
     * Update the specified fee
     */
    public function update(Request $request, Fee $fee)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'class_id' => 'required|exists:classes,id',
            'branch_id' => 'required|exists:branches,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000',
            'academic_year' => 'nullable|string|max:20',
            'total_amount' => 'required|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string',
            'fine_amount' => 'nullable|numeric|min:0',
            'due_date' => 'required|date',
            'status' => 'required|in:pending,partial,paid,cancelled',
            'remarks' => 'nullable|string',
        ]);

        // Calculate discount amount if percent is provided
        if ($validated['discount_percent'] ?? 0 > 0) {
            $validated['discount_amount'] = ($validated['total_amount'] * $validated['discount_percent']) / 100;
        }

        $fee->update($validated);

        return redirect()
            ->route('fees.index')
            ->with('success', 'Fee updated successfully!');
    }

    /**
     * Remove the specified fee
     */
    public function destroy(Fee $fee)
    {
        // Check if fee has payments
        if ($fee->payments()->count() > 0) {
            return back()->with('error', 'Cannot delete fee with existing payments!');
        }

        $fee->delete();

        return redirect()
            ->route('fees.index')
            ->with('success', 'Fee deleted successfully!');
    }

    /**
     * Get student fees for dropdown (API endpoint)
     */
    public function dropdown(Request $request)
    {
        $query = Fee::with(['student', 'feeType'])
            ->select('id', 'student_id', 'fee_type_id', 'month', 'year', 'total_amount', 'balance_amount', 'status');
        
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $fees = $query->orderBy('year', 'desc')
                      ->orderBy('month', 'desc')
                      ->get();

        return response()->json($fees);
    }
}