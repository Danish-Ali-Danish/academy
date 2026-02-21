<?php

namespace App\Http\Controllers;

use App\Models\FeePayment;
use App\Models\Fee;
use App\Models\Student;
use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class FeePaymentController extends Controller
{
    /**
     * Display a listing of fee payments
     */
    public function index(Request $request)
    {
        // Check if it's a mobile request
        if ($request->has('mobile') || ($request->ajax() && $request->get('page'))) {
            return $this->getMobilePayments($request);
        }
        
        // DataTables AJAX request
        if ($request->ajax() && $request->has('draw')) {
            return $this->getDataTablesPayments($request);
        }
        
        // Check specifically for DataTables request (not generic AJAX)
        if ($request->has('draw')) {
            $payments = FeePayment::with(['fee', 'student', 'branch', 'collectedBy']);

            return DataTables::of($payments)
                ->addIndexColumn()
                ->addColumn('action', function ($payment) {
                    return '
                        <div class="flex items-center gap-2">
                            <a href="' . route('fee-payments.show', $payment->id) . '" 
                               class="text-blue-600 hover:text-blue-800"
                               title="View">
                                <i class="fas fa-eye"></i> view
                            </a>
                            <button onclick="cancelPayment(' . $payment->id . ')" 
                                    class="text-red-600 hover:text-red-800"
                                    title="Cancel">
                                <i class="fas fa-times"></i> cancel
                            </button>
                        </div>
                    ';
                })
                ->editColumn('is_cancelled', function ($payment) {
                    return $payment->is_cancelled 
                        ? '<span class="text-red-600">✗ Cancelled</span>' 
                        : '<span class="text-green-600">✓ Active</span>';
                })
                ->rawColumns(['action', 'is_cancelled'])
                ->make(true);
        }

        // For initial Inertia page load
        $branches = Branch::active()->get(['id', 'name']);
        
        return Inertia::render('FeePayments/Index', [
            'branches' => $branches
        ]);
    }

    private function getMobilePayments(Request $request)
    {
        $query = FeePayment::with(['fee.feeType', 'student', 'branch', 'collectedBy']);
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('receipt_no', 'like', "%{$search}%")
                  ->orWhere('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('student', function($sq) use ($search) {
                      $sq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('admission_no', 'like', "%{$search}%");
                  });
            });
        }
        
        // Apply branch filter
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        // Apply payment method filter
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        
        // Apply status filter (cancelled or active)
        if ($request->filled('is_cancelled')) {
            $query->where('is_cancelled', $request->is_cancelled);
        }
        
        // Apply date range filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
        }
        
        // Paginate
        $perPage = $request->get('per_page', 10);
        $payments = $query->latest('payment_date')->paginate($perPage);
        
        return response()->json($payments);
    }

    private function getDataTablesPayments(Request $request)
    {
        $query = FeePayment::with(['fee.feeType', 'student', 'branch', 'collectedBy']);
        
        // Apply search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function($q) use ($search) {
                $q->where('receipt_no', 'like', "%{$search}%")
                  ->orWhere('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('student', function($sq) use ($search) {
                      $sq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('admission_no', 'like', "%{$search}%");
                  });
            });
        }
        
        // Apply filters
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        
        if ($request->filled('is_cancelled')) {
            $query->where('is_cancelled', $request->is_cancelled);
        }
        
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
        }
        
        // Get total count
        $totalData = $query->count();
        
        // Apply sorting
        $orderColumn = $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = ['id', 'receipt_no', 'payment_date', 'amount_paid', 'payment_method', 'is_cancelled'];
        
        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        }
        
        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $payments = $query->skip($start)->take($length)->get();
        
        // Format data
        $data = $payments->map(function($payment, $index) use ($start) {
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $payment->id,
                'receipt_no' => $payment->receipt_no,
                'student_name' => $payment->student ? $payment->student->full_name : '-',
                'admission_no' => $payment->student ? $payment->student->admission_no : '-',
                'fee_type' => $payment->fee && $payment->fee->feeType ? $payment->fee->feeType->name : '-',
                'payment_date' => $payment->payment_date->format('d M Y'),
                'amount_paid' => 'Rs. ' . number_format((float)($payment->amount_paid ?? 0), 2),
                'payment_method' => ucfirst(str_replace('_', ' ', $payment->payment_method)),
                'collected_by' => $payment->collectedBy ? $payment->collectedBy->name : '-',
                'is_cancelled' => $payment->is_cancelled 
                    ? '<span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Cancelled</span>' 
                    : '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Active</span>',
                'action' => '
                    <div class="flex items-center justify-center gap-2">
                        <a href="' . route('fee-payments.show', $payment->id) . '" class="text-blue-600 hover:text-blue-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </a>
                        ' . (!$payment->is_cancelled ? '
                        <button onclick="cancelPayment(' . $payment->id . ')" class="text-red-600 hover:text-red-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        ' : '') . '
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
     * Show the form for creating a new payment
     */
    public function create()
    {
        $branches = Branch::active()->get(['id', 'name']);
        $students = Student::active()->get(['id', 'admission_no', 'first_name', 'last_name']);
        
        return Inertia::render('FeePayments/Create', [
            'branches' => $branches,
            'students' => $students
        ]);
    }

    /**
     * Store a newly created payment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fee_id' => 'required|exists:fees,id',
            'student_id' => 'required|exists:students,id',
            'branch_id' => 'required|exists:branches,id',
            'payment_date' => 'required|date',
            'amount_paid' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,cheque,bank_transfer,online,card',
            'transaction_id' => 'nullable|string|max:100',
            'cheque_number' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'collected_by' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        // Generate receipt number
        $validated['receipt_no'] = $this->generateReceiptNumber();

        $payment = FeePayment::create($validated);

        // Update fee paid amount
        $fee = Fee::find($validated['fee_id']);
        $fee->makePayment($validated['amount_paid'], $validated);

        return redirect()
            ->route('fee-payments.index')
            ->with('success', 'Payment recorded successfully!');
    }

    /**
     * Display the specified payment
     */
    public function show(FeePayment $feePayment)
    {
        return Inertia::render('FeePayments/Show', [
            'payment' => $feePayment->load([
                'fee.feeType',
                'student',
                'branch',
                'collectedBy',
                'cancelledBy'
            ])
        ]);
    }

    /**
     * Cancel the specified payment
     */
    public function cancel(Request $request, FeePayment $feePayment)
    {
        $validated = $request->validate([
            'cancellation_reason' => 'required|string',
            'cancelled_by' => 'required|exists:users,id',
        ]);

        $success = $feePayment->cancel(
            $validated['cancellation_reason'],
            $validated['cancelled_by']
        );

        if ($success) {
            return back()->with('success', 'Payment cancelled successfully!');
        }

        return back()->with('error', 'Payment is already cancelled!');
    }

    /**
     * Generate receipt number
     */
    private function generateReceiptNumber()
    {
        $prefix = 'REC';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));
        
        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Get payments for dropdown (API endpoint)
     */
    public function dropdown(Request $request)
    {
        $query = FeePayment::active()
            ->with(['student', 'fee.feeType'])
            ->select('id', 'receipt_no', 'student_id', 'fee_id', 'amount_paid', 'payment_date');
        
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        $payments = $query->orderBy('payment_date', 'desc')->get();

        return response()->json($payments);
    }
}