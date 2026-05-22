<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Models\CollectionAgent;
use App\Models\PaymentGateway;
use App\Models\ReceiptTemplate;
use App\Models\PaymentBatch;
use App\Models\PaymentBatchItem;
use App\Models\CollectionSummary;
use App\Models\AutoReconciliationRules;
use App\Models\ReconciliationLog;
use App\Models\BulkPayment;
use App\Models\BulkPaymentItem;
use App\Models\Branch;
use App\Models\FeeVoucher;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Inertia\Inertia;

class EnhancedFeeCollectionsController extends Controller
{
    /**
     * Display the fee collections dashboard
     */
    public function index(Request $request)
    {
        $branchId = $request->user()->branch_id ?? null;
        
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        
        // Get dashboard statistics
        $stats = [
            'today_collections' => PaymentTransaction::whereDate('transaction_date', $today)
                ->when($branchId, function($query) use ($branchId) {
                    return $query->where('branch_id', $branchId);
                })
                ->sum('amount'),
            
            'month_collections' => PaymentTransaction::whereMonth('transaction_date', $thisMonth->month)
                ->whereYear('transaction_date', $thisMonth->year)
                ->when($branchId, function($query) use ($branchId) {
                    return $query->where('branch_id', $branchId);
                })
                ->sum('amount'),
            
            'pending_transactions' => PaymentTransaction::where('transaction_status', 'pending')
                ->when($branchId, function($query) use ($branchId) {
                    return $query->where('branch_id', $branchId);
                })
                ->count(),
            
            'collection_agents' => CollectionAgent::when($branchId, function($query) use ($branchId) {
                    return $query->where('branch_id', $branchId);
                })
                ->where('status', 'active')
                ->count(),
            
            'total_transactions' => PaymentTransaction::when($branchId, function($query) use ($branchId) {
                    return $query->where('branch_id', $branchId);
                })
                ->count(),
        ];
        
        // Get recent transactions
        $recentTransactions = PaymentTransaction::with(['student', 'branch'])
            ->when($branchId, function($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get();
        
        // Get collection agents
        $collectionAgents = CollectionAgent::with(['user', 'branch'])
            ->when($branchId, function($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get payment methods breakdown
        $paymentMethods = PaymentTransaction::select('payment_method', DB::raw('SUM(amount) as total_amount'))
            ->when($branchId, function($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->where('transaction_status', 'completed')
            ->whereDate('transaction_date', '>=', $thisMonth)
            ->groupBy('payment_method')
            ->get();
        
        return Inertia::render('EnhancedFeeCollections/Index', [
            'stats' => $stats,
            'recentTransactions' => $recentTransactions,
            'collectionAgents' => $collectionAgents,
            'paymentMethods' => $paymentMethods,
            'filters' => $request->all(),
        ]);
    }
    
    /**
     * Create a new payment transaction
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fee_voucher_id' => 'required|exists:fee_vouchers,id',
            'payment_method' => 'required|string|in:cash,cheque,online,bank_transfer',
            'payment_gateway' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'reference_number' => 'nullable|string|max:200',
            'transaction_date' => 'required|date',
            'student_id' => 'required|exists:students,id',
            'branch_id' => 'required|exists:branches,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        DB::beginTransaction();
        
        try {
            // Generate unique transaction ID
            $transactionId = 'TXN' . date('YmdHis') . rand(1000, 9999);
            
            // Get fee voucher to calculate balance
            $feeVoucher = FeeVoucher::findOrFail($request->fee_voucher_id);
            $previousPayments = PaymentTransaction::where('fee_voucher_id', $feeVoucher->id)
                ->where('transaction_status', 'completed')
                ->sum('amount');
            
            $balanceAmount = $feeVoucher->total_amount - $previousPayments - $request->amount;
            
            // Create payment transaction
            $payment = PaymentTransaction::create([
                'fee_voucher_id' => $request->fee_voucher_id,
                'student_id' => $request->student_id,
                'branch_id' => $request->branch_id,
                'transaction_id' => $transactionId,
                'amount' => $request->amount,
                'balance_amount' => max(0, $balanceAmount),
                'payment_method' => $request->payment_method,
                'payment_gateway' => $request->payment_gateway,
                'transaction_status' => 'pending',
                'reference_number' => $request->reference_number,
                'transaction_date' => $request->transaction_date,
                'transaction_details' => $request->transaction_details,
            ]);
            
            // Handle online payments if gateway is specified
            if ($request->payment_method === 'online' && $request->payment_gateway) {
                $this->processOnlinePayment($payment, $request);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Payment transaction created successfully',
                'data' => $payment
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating payment transaction: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Process online payment through gateway
     */
    private function processOnlinePayment($payment, $request)
    {
        $gateway = PaymentGateway::where('code', $request->payment_gateway)
            ->where('is_active', true)
            ->first();
        
        if (!$gateway) {
            throw new \Exception('Payment gateway not found or inactive');
        }
        
        // Here you would integrate with actual payment gateway API
        // For now, we'll simulate the process
        $payment->transaction_status = 'processing';
        $payment->save();
        
        // Simulate payment processing
        try {
            // Simulate API call to payment gateway
            sleep(2); // Simulate network delay
            
            // Random success/failure for demo purposes
            $success = rand(0, 9) < 8; // 80% success rate
            
            if ($success) {
                $payment->transaction_status = 'completed';
                $payment->receipt_number = 'RCPT' . date('YmdHis') . rand(1000, 9999);
                $payment->payment_metadata = [
                    'gateway_response' => 'success',
                    'transaction_id' => $payment->transaction_id,
                    'processed_at' => now(),
                ];
            } else {
                $payment->transaction_status = 'failed';
                $payment->payment_metadata = [
                    'gateway_response' => 'failed',
                    'error_message' => 'Payment gateway rejected transaction',
                ];
            }
            
            $payment->save();
            
        } catch (\Exception $e) {
            $payment->transaction_status = 'failed';
            $payment->payment_metadata = [
                'gateway_response' => 'error',
                'error_message' => $e->getMessage(),
            ];
            $payment->save();
        }
    }
    
    /**
     * Update payment transaction status
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'transaction_status' => 'required|string|in:pending,completed,failed,cancelled',
            'receipt_number' => 'nullable|string|max:100',
            'receipt_path' => 'nullable|string|max:255',
            'transaction_details' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        DB::beginTransaction();
        
        try {
            $payment = PaymentTransaction::findOrFail($id);
            
            $payment->update($request->only([
                'transaction_status',
                'receipt_number',
                'receipt_path',
                'transaction_details'
            ]));
            
            // If payment is completed, sync voucher balances
            if ($request->transaction_status === 'completed') {
                $this->syncVoucherBalances($payment);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Payment status updated successfully',
                'data' => $payment
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating payment status: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Sync voucher balances using the standard service.
     * Guided by the canonical app pattern: FeeVoucherBalanceService
     * is the single source of truth for voucher balance calculations.
     */
    private function syncVoucherBalances(PaymentTransaction $payment): void
    {
        if ($payment->fee_voucher_id && $payment->feeVoucher) {
            app(\App\Services\FeeVoucherBalanceService::class)->sync($payment->feeVoucher);
        }
    }
    
    /**
     * Generate payment receipt
     */
    public function generateReceipt($id)
    {
        try {
            $payment = PaymentTransaction::with(['student', 'branch', 'feeVoucher'])
                ->findOrFail($id);
            
            // Get receipt template
            $template = ReceiptTemplate::where('is_default', true)
                ->where('branch_id', $payment->branch_id)
                ->first();
            
            if (!$template) {
                $template = ReceiptTemplate::where('is_default', true)->first();
            }
            
            // Generate receipt content
            $receiptContent = $this->generateReceiptContent($payment, $template);
            
            // Save receipt file
            $receiptPath = 'receipts/' . $payment->receipt_number . '.pdf';
            Storage::disk('local')->put($receiptPath, $receiptContent);
            
            // Update payment with receipt path
            $payment->receipt_path = $receiptPath;
            $payment->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Receipt generated successfully',
                'receipt_path' => $receiptPath,
                'receipt_content' => base64_encode($receiptContent)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating receipt: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate receipt content
     */
    private function generateReceiptContent($payment, $template)
    {
        // This would typically use a PDF generation library like DomPDF or TCPDF
        // For now, we'll return HTML content that can be converted to PDF
        
        $html = $template->header_html;
        
        // Add receipt details
        $html .= '<div class="receipt-details">';
        $html .= '<h2>Payment Receipt</h2>';
        $html .= '<p><strong>Receipt Number:</strong> ' . $payment->receipt_number . '</p>';
        $html .= '<p><strong>Transaction ID:</strong> ' . $payment->transaction_id . '</p>';
        $html .= '<p><strong>Date:</strong> ' . $payment->transaction_date->format('Y-m-d H:i:s') . '</p>';
        $html .= '<p><strong>Student Name:</strong> ' . $payment->student->name . '</p>';
        $html .= '<p><strong>Branch:</strong> ' . $payment->branch->name . '</p>';
        $html .= '<p><strong>Fee Voucher:</strong> ' . $payment->feeVoucher->voucher_number . '</p>';
        $html .= '<p><strong>Amount Paid:</strong> Rs. ' . number_format($payment->amount, 2) . '</p>';
        $html .= '<p><strong>Payment Method:</strong> ' . ucfirst($payment->payment_method) . '</p>';
        $html .= '<p><strong>Status:</strong> ' . ucfirst($payment->transaction_status) . '</p>';
        $html .= '</div>';
        
        $html .= $template->body_html;
        $html .= $template->footer_html;
        
        return $html;
    }
    
    /**
     * Process bulk payments
     */
    public function processBulkPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fee_voucher_ids' => 'required|array',
            'fee_voucher_ids.*' => 'exists:fee_vouchers,id',
            'payment_method' => 'required|string|in:cash,cheque,online,bank_transfer',
            'payment_gateway' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        DB::beginTransaction();
        
        try {
            // Generate bulk payment ID
            $bulkPaymentId = 'BULK' . date('YmdHis') . rand(1000, 9999);
            
            // Create bulk payment record
            $bulkPayment = BulkPayment::create([
                'branch_id' => $request->user()->branch_id,
                'processed_by' => $request->user()->id,
                'bulk_payment_id' => $bulkPaymentId,
                'payment_method' => $request->payment_method,
                'total_amount' => $request->amount * count($request->fee_voucher_ids),
                'total_records' => count($request->fee_voucher_ids),
                'batch_status' => 'processing',
            ]);
            
            // Process each voucher
            foreach ($request->fee_voucher_ids as $voucherId) {
                $feeVoucher = FeeVoucher::findOrFail($voucherId);
                $student = Student::findOrFail($feeVoucher->student_id);
                
                // Create payment transaction
                $payment = PaymentTransaction::create([
                    'fee_voucher_id' => $voucherId,
                    'student_id' => $student->id,
                    'branch_id' => $feeVoucher->branch_id,
                    'transaction_id' => 'TXN' . date('YmdHis') . rand(1000, 9999),
                    'amount' => $request->amount,
                    'payment_method' => $request->payment_method,
                    'payment_gateway' => $request->payment_gateway,
                    'transaction_status' => 'completed',
                    'transaction_date' => $request->transaction_date,
                    'transaction_details' => 'Bulk payment processed',
                ]);
                
                // Sync voucher balance via the standard service
                $this->syncVoucherBalances($payment);
                
                // Create bulk payment item
                BulkPaymentItem::create([
                    'bulk_payment_id' => $bulkPayment->id,
                    'fee_voucher_id' => $voucherId,
                    'student_id' => $student->id,
                    'amount' => $request->amount,
                    'item_status' => 'processed',
                ]);
                
                // Sync voucher balance via the standard service
                $this->syncVoucherBalances($payment);
            }
            
            // Update bulk payment status
            $bulkPayment->success_records = count($request->fee_voucher_ids);
            $bulkPayment->batch_status = 'completed';
            $bulkPayment->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Bulk payment processed successfully',
                'data' => $bulkPayment
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error processing bulk payment: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get collection analytics
     */
    public function getAnalytics(Request $request)
    {
        $branchId = $request->user()->branch_id ?? null;
        
        $startDate = $request->start_date ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $endDate = $request->end_date ?? Carbon::now()->format('Y-m-d');
        
        $analytics = PaymentTransaction::select(
            'payment_method',
            DB::raw('COUNT(*) as transaction_count'),
            DB::raw('SUM(amount) as total_amount'),
            DB::raw('DATE(transaction_date) as date')
        )
        ->when($branchId, function($query) use ($branchId) {
            return $query->where('branch_id', $branchId);
        })
        ->whereBetween('transaction_date', [$startDate, $endDate])
        ->where('transaction_status', 'completed')
        ->groupBy('payment_method', 'date')
        ->orderBy('date', 'asc')
        ->get()
        ->groupBy('date');
        
        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }
    
    /**
     * Export payment transactions
     */
    public function export(Request $request)
    {
        $branchId = $request->user()->branch_id ?? null;
        
        $query = PaymentTransaction::with(['student', 'branch', 'feeVoucher'])
            ->when($branchId, function($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            });
        
        // Apply filters
        if ($request->start_date) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }
        
        if ($request->end_date) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }
        
        if ($request->payment_method) {
            $query->where('payment_method', $request->payment_method);
        }
        
        if ($request->transaction_status) {
            $query->where('transaction_status', $request->transaction_status);
        }
        
        $transactions = $query->orderBy('transaction_date', 'desc')->get();
        
        // Generate Excel file
        $fileName = 'payment_transactions_' . date('Y_m_d_His') . '.xlsx';
        
        return (new \Maatwebsite\Excel\Excel)->download(
            new \App\Exports\PaymentTransactionsExport($transactions),
            $fileName
        );
    }
}