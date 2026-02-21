<?php

namespace App\Http\Controllers;

use App\Models\FeeType;
use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class FeeTypeController extends Controller
{
    /**
     * Display a listing of fee types
     */
    public function index(Request $request)
    {
        // Check if it's a mobile request
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobileFeeTypes($request);
        }
        
        // DataTables AJAX request
        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesFeeTypes($request);
        }
        
        // Check specifically for DataTables request (not generic AJAX)
        if ($request->has('draw')) {
            $feeTypes = FeeType::with(['branch']);

            return DataTables::of($feeTypes)
                ->addIndexColumn()
                ->addColumn('action', function ($feeType) {
                    return '
                        <div class="flex items-center gap-2">
                            <a href="' . route('fee-types.edit', $feeType->id) . '" 
                               class="text-blue-600 hover:text-blue-800"
                               title="Edit">
                                <i class="fas fa-edit"></i> edit
                            </a>
                            <button onclick="deleteFeeType(' . $feeType->id . ')" 
                                    class="text-red-600 hover:text-red-800"
                                    title="Delete">
                                <i class="fas fa-trash"></i> delete
                            </button>
                        </div>
                    ';
                })
                ->editColumn('status', function ($feeType) {
                    $colors = [
                        'active' => 'green',
                        'inactive' => 'gray'
                    ];
                    $color = $colors[$feeType->status] ?? 'gray';
                    
                    return '<span class="px-2 py-1 text-xs rounded-full bg-' . $color . '-100 text-' . $color . '-800">' 
                           . ucfirst($feeType->status) . 
                           '</span>';
                })
                ->editColumn('is_mandatory', function ($feeType) {
                    return $feeType->is_mandatory 
                        ? '<span class="text-green-600">✓ Yes</span>' 
                        : '<span class="text-gray-400">No</span>';
                })
                ->rawColumns(['action', 'status', 'is_mandatory'])
                ->make(true);
        }

        // For initial Inertia page load
        $branches = Branch::active()->get(['id', 'name']);
        
        return Inertia::render('FeeTypes/Index', [
            'branches' => $branches
        ]);
    }

    private function getMobileFeeTypes(Request $request)
    {
        $query = FeeType::with(['branch']);
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
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
        
        // Apply frequency filter
        if ($request->filled('frequency')) {
            $query->where('frequency', $request->frequency);
        }
        
        // Paginate
        $perPage = $request->get('per_page', 10);
        $feeTypes = $query->latest()->paginate($perPage);
        
        return response()->json($feeTypes);
    }

    private function getDataTablesFeeTypes(Request $request)
    {
        $query = FeeType::with(['branch']);
        
        // Apply search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        if ($request->filled('frequency')) {
            $query->where('frequency', $request->frequency);
        }
        
        // Get total count
        $totalData = $query->count();
        
        // Apply sorting
        $orderColumn = $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir', 'asc');
        $columns = ['id', 'name', 'code', 'amount', 'frequency', 'is_mandatory', 'status'];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }
        
        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $feeTypes = $query->skip($start)->take($length)->get();
        
        // Format data
        $data = $feeTypes->map(function($feeType, $index) use ($start) {
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $feeType->id,
                'name' => $feeType->name,
                'code' => $feeType->code,
                'amount' => 'Rs. ' . number_format((float)$feeType->amount, 2),
                'frequency' => ucfirst($feeType->frequency),
                'branch' => $feeType->branch ? $feeType->branch->name : '-',
                'is_mandatory' => $feeType->is_mandatory 
                    ? '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Yes</span>' 
                    : '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">No</span>',
                'status' => '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $this->getStatusClass($feeType->status) . '">' . ucfirst($feeType->status) . '</span>',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <a href="' . route('fee-types.edit', $feeType->id) . '" class="text-blue-600 hover:text-blue-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        <button onclick="deleteFeeType(' . $feeType->id . ')" class="text-red-600 hover:text-red-800">
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
     * Show the form for creating a new fee type
     */
    public function create()
    {
        $branches = Branch::active()->get(['id', 'name']);
        
        return Inertia::render('FeeTypes/Create', [
            'branches' => $branches
        ]);
    }

    /**
     * Store a newly created fee type
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:fee_types,code',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'frequency' => 'required|in:monthly,quarterly,half_yearly,yearly,one_time',
            'is_mandatory' => 'boolean',
            'applicable_from' => 'nullable|date',
            'applicable_to' => 'nullable|date|after_or_equal:applicable_from',
            'status' => 'required|in:active,inactive',
        ]);

        $feeType = FeeType::create($validated);

        return redirect()
            ->route('fee-types.index')
            ->with('success', 'Fee type created successfully!');
    }

    /**
     * Display the specified fee type
     */
    public function show(FeeType $feeType)
    {
        return Inertia::render('FeeTypes/Show', [
            'feeType' => $feeType->load(['branch', 'fees']),
            'stats' => [
                'total_fees' => $feeType->fees()->count(),
                'pending_fees' => $feeType->fees()->pending()->count(),
                'paid_fees' => $feeType->fees()->paid()->count(),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified fee type
     */
    public function edit(FeeType $feeType)
    {
        $branches = Branch::active()->get(['id', 'name']);
        
        return Inertia::render('FeeTypes/Edit', [
            'feeType' => $feeType,
            'branches' => $branches
        ]);
    }

    /**
     * Update the specified fee type
     */
    public function update(Request $request, FeeType $feeType)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:fee_types,code,' . $feeType->id,
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'frequency' => 'required|in:monthly,quarterly,half_yearly,yearly,one_time',
            'is_mandatory' => 'boolean',
            'applicable_from' => 'nullable|date',
            'applicable_to' => 'nullable|date|after_or_equal:applicable_from',
            'status' => 'required|in:active,inactive',
        ]);

        $feeType->update($validated);

        return redirect()
            ->route('fee-types.index')
            ->with('success', 'Fee type updated successfully!');
    }

    /**
     * Remove the specified fee type
     */
    public function destroy(FeeType $feeType)
    {
        // Check if fee type has fees
        if ($feeType->fees()->count() > 0) {
            return back()->with('error', 'Cannot delete fee type with existing fee records!');
        }

        $feeType->delete();

        return redirect()
            ->route('fee-types.index')
            ->with('success', 'Fee type deleted successfully!');
    }

    /**
     * Get fee types for dropdown (API endpoint)
     */
    public function dropdown(Request $request)
    {
        $query = FeeType::active()
            ->select('id', 'name', 'code', 'amount', 'frequency');
        
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        $feeTypes = $query->orderBy('name')->get();

        return response()->json($feeTypes);
    }
}