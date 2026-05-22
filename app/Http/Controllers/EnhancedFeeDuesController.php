<?php

namespace App\Http\Controllers;

use App\Models\DuesCategory;
use App\Models\DuesHistory;
use App\Models\ReminderTemplate;
use App\Models\PenaltyRules;
use App\Models\AdvanceAllocation;
use App\Models\DuesAllocations;
use App\Models\FeeWaiverRequest;
use App\Models\DuesReminders;
use App\Models\CustomPaymentPlan;
use AppModels\PaymentPlanInstallments;
use App\Models\DuesAnalytics;
use App\Models\Branch;
use App\Models\FeeVoucher;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;
use Inertia\Inertia;

class EnhancedFeeDuesController extends Controller
{
    /**
     * Display the fee dues dashboard
     */
    public function index(Request $request)
    {
        $branchId = $request->user()->branch_id ?? null;
        
        $today = Carbon::today();
        
        // Get dashboard statistics
        $stats = [
            'total_dues' => DuesHistory::where('dues_status', '!=', 'paid')
                ->when($branchId, function($query) use ($branchId) {
                    return $query->where('branch_id', $branchId);
                })
                ->sum('current_amount'),
            
            'overdue_dues' => DuesHistory::where('dues_status', 'overdue')
                ->when($branchId, function($query) use ($branchId) {
                    return $query->where('branch_id', $branchId);
                })
                ->sum('current_amount'),
            
            'students_with_dues' => DuesHistory::where('dues_status', '!=', 'paid')
                ->when($branchId, function($query) use ($branchId) {
                    return $query->where('branch_id', $branchId);
                })
                ->distinct('student_id')
                ->count('student_id'),
            
            'aged_30_days' => DuesHistory::where('days_overdue', '>=', 30)
                ->when($branchId, function($query) use ($branchId) {
                    return $query->where('branch_id', $branchId);
                })
                ->sum('current_amount'),
            
            'aged_60_days' => DuesHistory::where('days_overdue', '>=', 60)
                ->when($branchId, function($query) use ($branchId) {
                    return $query->where('branch_id', $branchId);
                })
                ->sum('current_amount'),
            
            'aged_90_days' => DuesHistory::where('days_overdue', '>=', 90)
                ->when($branchId, function($query) use ($branchId) {
                    return $query->where('branch_id', $branchId);
                })
                ->sum('current_amount'),
        ];
        
        // Get dues by category
        $duesByCategory = DuesCategory::withCount(['duesHistory' => function($query) use ($branchId) {
                $query->where('dues_status', '!=', 'paid');
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
            }])
            ->when($branchId, function($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->get();
        
        // Get recent dues history
        $recentDues = DuesHistory::with(['student', 'duesCategory'])
            ->when($branchId, function($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get students with highest dues
        $topDebtors = DuesHistory::with(['student'])
            ->when($branchId, function($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->where('dues_status', '!=', 'paid')
            ->select('student_id', DB::raw('SUM(current_amount) as total_dues'))
            ->groupBy('student_id')
            ->orderBy('total_dues', 'desc')
            ->limit(10)
            ->get();
        
        // Get penalty rules
        $penaltyRules = PenaltyRules::when($branchId, function($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->where('is_active', true)
            ->get();
        
        return Inertia::render('EnhancedFeeDues/Index', [
            'stats' => $stats,
            'duesByCategory' => $duesByCategory,
            'recentDues' => $recentDues,
            'topDebtors' => $topDebtors,
            'penaltyRules' => $penaltyRules,
            'filters' => $request->all(),
        ]);
    }
    
    /**
     * Calculate dues for all students
     */
    public function calculateDues(Request $request)
    {
        $branchId = $request->user()->branch_id;
        
        DB::beginTransaction();
        
        try {
            // Get all active fee vouchers
            $feeVouchers = FeeVoucher::with(['student', 'branch'])
                ->where('branch_id', $branchId)
                ->where('status', 'active')
                ->get();
            
            foreach ($feeVouchers as $voucher) {
                // Calculate existing payments
                $totalPaid = PaymentTransaction::where('fee_voucher_id', $voucher->id)
                    ->where('transaction_status', 'completed')
                    ->sum('amount');
                
                $balance = $voucher->total_amount - $totalPaid;
                
                if ($balance > 0) {
                    // Check if dues history already exists
                    $existingDues = DuesHistory::where('fee_voucher_id', $voucher->id)
                        ->where('student_id', $voucher->student_id)
                        ->first();
                    
                    if ($existingDues) {
                        // Update existing dues
                        $existingDues->update([
                            'current_amount' => $balance,
                            'days_overdue' => $this->calculateDaysOverdue($voucher->due_date),
                            'dues_status' => $this->getDuesStatus($voucher->due_date, $balance),
                        ]);
                    } else {
                        // Create new dues entry
                        DuesHistory::create([
                            'student_id' => $voucher->student_id,
                            'branch_id' => $voucher->branch_id,
                            'fee_voucher_id' => $voucher->id,
                            'original_amount' => $voucher->total_amount,
                            'current_amount' => $balance,
                            'due_date' => $voucher->due_date,
                            'days_overdue' => $this->calculateDaysOverdue($voucher->due_date),
                            'dues_status' => $this->getDuesStatus($voucher->due_date, $balance),
                        ]);
                    }
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Dues calculated successfully for all students'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error calculating dues: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Calculate days overdue
     */
    private function calculateDaysOverdue($dueDate)
    {
        $dueDate = Carbon::parse($dueDate);
        $today = Carbon::today();
        
        return $today->diffInDays($dueDate, false);
    }
    
    /**
     * Get dues status based on due date and amount
     */
    private function getDuesStatus($dueDate, $amount)
    {
        $daysOverdue = $this->calculateDaysOverdue($dueDate);
        
        if ($amount <= 0) {
            return 'paid';
        } elseif ($daysOverdue > 0) {
            return 'overdue';
        } else {
            return 'current';
        }
    }
    
    /**
     * Send dues reminders
     */
    public function sendReminders(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dues_ids' => 'required|array',
            'dues_ids.*' => 'exists:dues_history,id',
            'template_id' => 'required|exists:reminder_templates,id',
            'channel_type' => 'required|string|in:email,sms,whatsapp,notification',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $template = ReminderTemplate::findOrFail($request->template_id);
            $remindersSent = 0;
            
            foreach ($request->dues_ids as $duesId) {
                $dues = DuesHistory::findOrFail($duesId);
                $student = Student::findOrFail($dues->student_id);
                
                // Get recipient address based on channel type
                $recipientAddress = $this->getRecipientAddress($student, $request->channel_type);
                
                if ($recipientAddress) {
                    // Send reminder
                    $reminder = DuesReminders::create([
                        'dues_history_id' => $duesId,
                        'reminder_template_id' => $template->id,
                        'channel_type' => $request->channel_type,
                        'recipient_type' => 'student',
                        'recipient_address' => $recipientAddress,
                        'reminder_status' => 'pending',
                    ]);
                    
                    // Process reminder sending based on channel type
                    $this->processReminder($reminder, $dues, $student);
                    
                    $remindersSent++;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Successfully sent $remindersSent reminders",
                'data' => ['reminders_sent' => $remindersSent]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error sending reminders: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get recipient address based on channel type
     */
    private function getRecipientAddress($student, $channelType)
    {
        switch ($channelType) {
            case 'email':
                return $student->email;
            case 'sms':
                return $student->phone;
            case 'whatsapp':
                return $student->phone;
            case 'notification':
                return $student->id;
            default:
                return null;
        }
    }
    
    /**
     * Process individual reminder
     */
    private function processReminder($reminder, $dues, $student)
    {
        // Generate reminder content
        $content = $this->generateReminderContent($reminder, $dues, $student);
        
        // Send reminder based on channel type
        switch ($reminder->channel_type) {
            case 'email':
                $this->sendEmailReminder($reminder, $student, $content);
                break;
            case 'sms':
                $this->sendSMSReminder($reminder, $student, $content);
                break;
            case 'whatsapp':
                $this->sendWhatsAppReminder($reminder, $student, $content);
                break;
            case 'notification':
                $this->sendInAppNotification($reminder, $student, $content);
                break;
        }
    }
    
    /**
     * Generate reminder content
     */
    private function generateReminderContent($reminder, $dues, $student)
    {
        $template = $reminder->reminderTemplate;
        
        // Replace template variables
        $content = $template->template_content;
        $content = str_replace('{{student_name}}', $student->name, $content);
        $content = str_replace('{{amount}}', number_format($dues->current_amount, 2), $content);
        $content = str_replace('{{due_date}}', $dues->due_date->format('Y-m-d'), $content);
        $content = str_replace('{{days_overdue}}', $dues->days_overdue, $content);
        
        return $content;
    }
    
    /**
     * Send email reminder
     */
    private function sendEmailReminder($reminder, $student, $content)
    {
        try {
            // Send email logic here
            // Mail::to($student->email)->send(new DuesReminderEmail($content));
            
            $reminder->reminder_status = 'sent';
            $reminder->sent_at = now();
            $reminder->sent_details = ['email' => $student->email];
            $reminder->save();
            
        } catch (\Exception $e) {
            $reminder->reminder_status = 'failed';
            $reminder->failure_reason = $e->getMessage();
            $reminder->save();
        }
    }
    
    /**
     * Send SMS reminder
     */
    private function sendSMSReminder($reminder, $student, $content)
    {
        try {
            // SMS sending logic here
            // $smsGateway = new SMSGateway();
            // $smsGateway->send($student->phone, $content);
            
            $reminder->reminder_status = 'sent';
            $reminder->sent_at = now();
            $reminder->sent_details = ['phone' => $student->phone];
            $reminder->save();
            
        } catch (\Exception $e) {
            $reminder->reminder_status = 'failed';
            $reminder->failure_reason = $e->getMessage();
            $reminder->save();
        }
    }
    
    /**
     * Send WhatsApp reminder
     */
    private function sendWhatsAppReminder($reminder, $student, $content)
    {
        try {
            // WhatsApp sending logic here
            // $whatsappGateway = new WhatsAppGateway();
            // $whatsappGateway->send($student->phone, $content);
            
            $reminder->reminder_status = 'sent';
            $reminder->sent_at = now();
            $reminder->sent_details = ['phone' => $student->phone];
            $reminder->save();
            
        } catch (\Exception $e) {
            $reminder->reminder_status = 'failed';
            $reminder->failure_reason = $e->getMessage();
            $reminder->save();
        }
    }
    
    /**
     * Send in-app notification
     */
    private function sendInAppNotification($reminder, $student, $content)
    {
        try {
            // In-app notification logic here
            // Notification::send([$student], new DuesReminderNotification($content));
            
            $reminder->reminder_status = 'sent';
            $reminder->sent_at = now();
            $reminder->sent_details = ['user_id' => $student->id];
            $reminder->save();
            
        } catch (\Exception $e) {
            $reminder->reminder_status = 'failed';
            $reminder->failure_reason = $e->getMessage();
            $reminder->save();
        }
    }
    
    /**
     * Create custom payment plan
     */
    public function createPaymentPlan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'fee_voucher_id' => 'required|exists:fee_vouchers,id',
            'plan_name' => 'required|string|max:100',
            'installment_count' => 'required|integer|min:1',
            'installment_amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        DB::beginTransaction();
        
        try {
            $feeVoucher = FeeVoucher::findOrFail($request->fee_voucher_id);
            $student = Student::findOrFail($request->student_id);
            
            // Generate plan code
            $planCode = 'PLAN' . date('YmdHis') . rand(1000, 9999);
            
            // Create payment plan
            $paymentPlan = CustomPaymentPlan::create([
                'student_id' => $request->student_id,
                'branch_id' => $feeVoucher->branch_id,
                'fee_voucher_id' => $request->fee_voucher_id,
                'plan_name' => $request->plan_name,
                'plan_code' => $planCode,
                'total_amount' => $request->installment_amount * $request->installment_count,
                'installment_count' => $request->installment_count,
                'installment_amount' => $request->installment_amount,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'plan_status' => 'active',
            ]);
            
            // Create payment plan installments
            $currentDate = Carbon::parse($request->start_date);
            $interval = $request->end_date->diffInDays($request->start_date) / ($request->installment_count - 1);
            
            for ($i = 0; $i < $request->installment_count; $i++) {
                PaymentPlanInstallments::create([
                    'custom_payment_plan_id' => $paymentPlan->id,
                    'installment_amount' => $request->installment_amount,
                    'due_date' => $currentDate->copy()->addDays($i * $interval),
                    'installment_status' => 'pending',
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Payment plan created successfully',
                'data' => $paymentPlan
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating payment plan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Apply penalty to dues
     */
    public function applyPenalty(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dues_ids' => 'required|array',
            'dues_ids.*' => 'exists:dues_history,id',
            'penalty_rule_id' => 'required|exists:penalty_rules,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        DB::beginTransaction();
        
        try {
            $penaltyRule = PenaltyRules::findOrFail($request->penalty_rule_id);
            $penaltiesApplied = 0;
            
            foreach ($request->dues_ids as $duesId) {
                $dues = DuesHistory::findOrFail($duesId);
                
                // Check if penalty should be applied
                if ($dues->days_overdue >= $penaltyRule->after_days) {
                    // Calculate penalty
                    $penaltyAmount = $dues->current_amount * ($penaltyRule->penalty_percentage / 100);
                    
                    // Apply penalty with maximum limit
                    if ($penaltyRule->max_penalty_percentage > 0) {
                        $maxPenalty = $dues->current_amount * ($penaltyRule->max_penalty_percentage / 100);
                        $penaltyAmount = min($penaltyAmount, $maxPenalty);
                    }
                    
                    // Update dues with penalty
                    $dues->update([
                        'penalty_applied' => $dues->penalty_applied + $penaltyAmount,
                        'current_amount' => $dues->current_amount + $penaltyAmount,
                    ]);
                    
                    $penaltiesApplied++;
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Successfully applied penalties to $penaltiesApplied dues entries",
                'data' => ['penalties_applied' => $penaltiesApplied]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error applying penalties: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create advance allocation
     */
    public function createAdvanceAllocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_transaction_id' => 'required|exists:payment_transactions,id',
            'expiry_date' => 'required|date|after:today',
            'allocation_details' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        DB::beginTransaction();
        
        try {
            $payment = PaymentTransaction::findOrFail($request->payment_transaction_id);
            
            // Create advance allocation
            $allocation = AdvanceAllocation::create([
                'student_id' => $payment->student_id,
                'branch_id' => $payment->branch_id,
                'payment_transaction_id' => $payment->id,
                'advance_amount' => $payment->amount,
                'allocated_amount' => 0,
                'remaining_amount' => $payment->amount,
                'allocation_status' => 'pending',
                'expiry_date' => $request->expiry_date,
                'allocation_details' => $request->allocation_details,
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Advance allocation created successfully',
                'data' => $allocation
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating advance allocation: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get dues analytics
     */
    public function getAnalytics(Request $request)
    {
        $branchId = $request->user()->branch_id ?? null;
        $date = $request->date ?? Carbon::today()->format('Y-m-d');
        
        $analytics = DuesAnalytics::firstOrCreate(
            ['branch_id' => $branchId, 'analytics_date' => $date],
            [
                'total_dues' => 0,
                'current_dues' => 0,
                'overdue_dues' => 0,
                'aged_30_days' => 0,
                'aged_60_days' => 0,
                'aged_90_days' => 0,
                'total_students_with_dues' => 0,
                'overdue_students_count' => 0,
                'total_penalties' => 0,
                'dues_cases' => 0,
            ]
        );
        
        // Calculate analytics if not exists
        if ($analytics->total_dues == 0) {
            $this->calculateDuesAnalytics($branchId, $date, $analytics);
        }
        
        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }
    
    /**
     * Calculate dues analytics
     */
    private function calculateDuesAnalytics($branchId, $date, &$analytics)
    {
        $duesData = DuesHistory::whereDate('created_at', '<=', $date);
        
        if ($branchId) {
            $duesData->where('branch_id', $branchId);
        }
        
        $analytics->total_dues = $duesData->sum('current_amount');
        $analytics->current_dues = $duesData->where('dues_status', 'current')->sum('current_amount');
        $analytics->overdue_dues = $duesData->where('dues_status', 'overdue')->sum('current_amount');
        $analytics->aged_30_days = $duesData->where('days_overdue', '>=', 30)->sum('current_amount');
        $analytics->aged_60_days = $duesData->where('days_overdue', '>=', 60)->sum('current_amount');
        $analytics->aged_90_days = $duesData->where('days_overdue', '>=', 90)->sum('current_amount');
        $analytics->total_students_with_dues = $duesData->where('dues_status', '!=', 'paid')->distinct('student_id')->count('student_id');
        $analytics->overdue_students_count = $duesData->where('dues_status', 'overdue')->distinct('student_id')->count('student_id');
        $analytics->total_penalties = $duesData->sum('penalty_applied');
        $analytics->dues_cases = $duesData->where('dues_status', 'overdue')->count();
        
        $analytics->save();
    }
}