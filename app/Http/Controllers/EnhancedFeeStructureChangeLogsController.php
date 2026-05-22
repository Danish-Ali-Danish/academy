<?php

namespace App\Http\Controllers;

use App\Models\FeeStructure;
use AppModels\FeeStructureVersions;
use AppModels\ChangeRequests;
use AppModels\ChangeRequestApprovals;
use AppModels\ImpactAnalysis;
use AppModels\ApprovalWorkflow;
use AppModels\WorkflowAssignments;
use AppModels\ChangeNotifications;
use AppModels\ChangeHistory;
use AppModels\ChangeImpactSummary;
use AppModels\FeeStructureAuditTrail;
use AppModels\BulkChangeOperations;
use AppModels\BulkChangeItems;
use App\Models\Branch;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Inertia\Inertia;

class EnhancedFeeStructureChangeLogsController extends Controller
{
    /**
     * Display the fee structure change logs dashboard
     */
    public function index(Request $request)
    {
        $branchId = $request->user()->branch_id ?? null;
        
        // Get dashboard statistics
        $stats = [
            'total_structures' => FeeStructure::when($branchId, function($query) use ($branchId) {
                    return $query->where('branch_id', $branchId);
                })->count(),
            
            'total_changes' => FeeStructureVersions::when($branchId, function($query) use ($branchId) {
                    return $query->where('branch_id', $branchId);
                })->count(),
            
            'pending_requests' => ChangeRequests::when($branchId, function($query) use ($branchId) {
                    return $query->where('branch_id', $branchId);
                })->where('request_status', 'pending')->count(),
            
            'approved_requests' => ChangeRequests::when($branchId, function($query) use ($branchId) {
                    return $query->where('branch_id', $branchId);
                })->where('request_status', 'approved')->count(),
            
            'rejected_requests' => ChangeRequests::when($branchId, function($query) use ($branchId) {
                    return $query->where('branch_id', $branchId);
                })->where('request_status', 'rejected')->count(),
        ];
        
        // Get recent changes
        $recentChanges = FeeStructureVersions::with(['feeStructure', 'branch', 'user'])
            ->when($branchId, function($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get pending change requests
        $pendingRequests = ChangeRequests::with(['feeStructure', 'requestedBy', 'branch'])
            ->when($branchId, function($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->where('request_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get approval workflows
        $approvalWorkflows = ApprovalWorkflow::when($branchId, function($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->where('is_active', true)
            ->get();
        
        return Inertia::render('EnhancedFeeStructureChangeLogs/Index', [
            'stats' => $stats,
            'recentChanges' => $recentChanges,
            'pendingRequests' => $pendingRequests,
            'approvalWorkflows' => $approvalWorkflows,
            'filters' => $request->all(),
        ]);
    }
    
    /**
     * Create a new change request
     */
    public function createChangeRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fee_structure_id' => 'required|exists:fee_structures,id',
            'request_title' => 'required|string|max:100',
            'request_description' => 'required|string',
            'proposed_changes' => 'required|array',
            'priority' => 'required|string|in:low,medium,high,urgent',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        DB::beginTransaction();
        
        try {
            $feeStructure = FeeStructure::findOrFail($request->fee_structure_id);
            
            // Generate unique request code
            $requestCode = 'CRQ' . date('YmdHis') . rand(1000, 9999);
            
            // Calculate estimated impact
            $estimatedImpact = $this->calculateEstimatedImpact($request->fee_structure_id, $request->proposed_changes);
            
            // Get affected students
            $affectedStudents = $this->getAffectedStudents($request->fee_structure_id, $request->proposed_changes);
            
            // Create change request
            $changeRequest = ChangeRequests::create([
                'fee_structure_id' => $request->fee_structure_id,
                'branch_id' => $feeStructure->branch_id,
                'requested_by' => $request->user()->id,
                'request_code' => $requestCode,
                'request_title' => $request->request_title,
                'request_description' => $request->request_description,
                'proposed_changes' => $request->proposed_changes,
                'estimated_impact' => $estimatedImpact,
                'affected_students' => $affectedStudents,
                'affected_students_count' => count($affectedStudents),
                'priority' => $request->priority,
                'request_status' => 'pending',
                'request_date' => now(),
            ]);
            
            // Create workflow assignment
            $this->createWorkflowAssignment($changeRequest);
            
            // Send notifications
            $this->sendChangeRequestNotifications($changeRequest);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Change request created successfully',
                'data' => $changeRequest
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating change request: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Calculate estimated impact of changes
     */
    private function calculateEstimatedImpact($feeStructureId, $proposedChanges)
    {
        $totalImpact = 0;
        
        foreach ($proposedChanges as $change) {
            if (isset($change['fee_type_id']) && isset($change['amount_change'])) {
                // Get student count for this fee type
                $studentCount = \App\Models\StudentFeeStructure::where('fee_structure_id', $feeStructureId)
                    ->where('fee_type_id', $change['fee_type_id'])
                    ->count();
                
                $totalImpact += abs($change['amount_change'] * $studentCount);
            }
        }
        
        return $totalImpact;
    }
    
    /**
     * Get affected students by changes
     */
    private function getAffectedStudents($feeStructureId, $proposedChanges)
    {
        $affectedStudents = [];
        
        foreach ($proposedChanges as $change) {
            if (isset($change['fee_type_id'])) {
                $students = \App\Models\StudentFeeStructure::where('fee_structure_id', $feeStructureId)
                    ->where('fee_type_id', $change['fee_type_id'])
                    ->pluck('student_id')
                    ->toArray();
                
                $affectedStudents = array_merge($affectedStudents, $students);
            }
        }
        
        // Remove duplicates
        $affectedStudents = array_unique($affectedStudents);
        
        return array_values($affectedStudents);
    }
    
    /**
     * Create workflow assignment
     */
    private function createWorkflowAssignment($changeRequest)
    {
        // Get default approval workflow
        $workflow = ApprovalWorkflow::where('is_default', true)
            ->where('workflow_type', 'fee_structure')
            ->first();
        
        if (!$workflow) {
            $workflow = ApprovalWorkflow::where('workflow_type', 'fee_structure')
                ->where('is_active', true)
                ->first();
        }
        
        if ($workflow) {
            WorkflowAssignments::create([
                'change_request_id' => $changeRequest->id,
                'workflow_id' => $workflow->id,
                'current_step' => 1,
                'assignment_status' => 'pending',
            ]);
        }
    }
    
    /**
     * Send change request notifications
     */
    private function sendChangeRequestNotifications($changeRequest)
    {
        // Get approvers based on workflow
        $approvers = $this->getApproversForRequest($changeRequest);
        
        foreach ($approvers as $approver) {
            ChangeNotifications::create([
                'change_request_id' => $changeRequest->id,
                'notification_type' => 'notification',
                'recipient_type' => 'staff',
                'recipients' => [$approver->id],
                'notification_status' => 'pending',
            ]);
        }
    }
    
    /**
     * Get approvers for change request
     */
    private function getApproversForRequest($changeRequest)
    {
        // Get approvers based on priority and workflow
        $query = User::role(['admin', 'finance_manager', 'branch_manager'])
            ->where('branch_id', $changeRequest->branch_id);
        
        // Add priority-based filtering
        switch ($changeRequest->priority) {
            case 'urgent':
                return $query->get();
            case 'high':
                return $query->limit(5)->get();
            case 'medium':
                return $query->limit(3)->get();
            default:
                return $query->limit(2)->get();
        }
    }
    
    /**
     * Approve change request
     */
    public function approveChangeRequest(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'approval_level' => 'required|string|in:level_1,level_2,level_3,final',
            'approval_comments' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        DB::beginTransaction();
        
        try {
            $changeRequest = ChangeRequests::findOrFail($id);
            
            // Create approval record
            $approval = ChangeRequestApprovals::create([
                'change_request_id' => $changeRequest->id,
                'approved_by' => $request->user()->id,
                'approval_level' => $request->approval_level,
                'approval_status' => 'approved',
                'approval_comments' => $request->approval_comments,
                'approved_at' => now(),
            ]);
            
            // Update change request status if final approval
            if ($request->approval_level === 'final') {
                $changeRequest->request_status = 'approved';
                $changeRequest->approved_by = $request->user()->id;
                $changeRequest->approval_date = now();
                $changeRequest->save();
                
                // Implement the changes
                $this->implementChangeRequest($changeRequest);
            }
            
            // Update workflow assignment
            $this->updateWorkflowAssignment($changeRequest, $request->approval_level);
            
            // Send notifications
            $this->sendApprovalNotifications($changeRequest, $approval);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Change request approved successfully',
                'data' => $changeRequest
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error approving change request: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Implement approved change request
     */
    private function implementChangeRequest($changeRequest)
    {
        $feeStructure = FeeStructure::findOrFail($changeRequest->fee_structure_id);
        
        // Get current structure data
        $currentStructure = $feeStructure->toArray();
        
        // Apply proposed changes
        foreach ($changeRequest->proposed_changes as $change) {
            if (isset($change['fee_type_id']) && isset($change['amount_change'])) {
                // Update fee structure
                $feeTypeFee = \App\Models\FeeStructure::where('id', $changeRequest->fee_structure_id)
                    ->where('fee_type_id', $change['fee_type_id'])
                    ->first();
                
                if ($feeTypeFee) {
                    $feeTypeFee->amount += $change['amount_change'];
                    $feeTypeFee->save();
                }
            }
        }
        
        // Create version record
        FeeStructureVersions::create([
            'fee_structure_id' => $changeRequest->fee_structure_id,
            'branch_id' => $feeStructure->branch_id,
            'version_number' => 'v' . (FeeStructureVersions::where('fee_structure_id', $changeRequest->fee_structure_id)->count() + 1),
            'version_name' => 'Version ' . (FeeStructureVersions::where('fee_structure_id', $changeRequest->fee_structure_id)->count() + 1),
            'version_description' => $changeRequest->request_title,
            'old_structure_data' => $currentStructure,
            'new_structure_data' => $feeStructure->fresh()->toArray(),
            'changed_fields' => $changeRequest->proposed_changes,
            'total_old_amount' => array_sum(array_column($currentStructure['fee_types'] ?? [], 'amount')),
            'total_new_amount' => array_sum(array_column($feeStructure->fresh()->fee_types ?? [], 'amount')),
            'total_difference' => $changeRequest->estimated_impact,
            'change_type' => 'update',
            'change_reason' => $changeRequest->request_description,
            'changed_by' => $changeRequest->requested_by,
            'effective_date' => now(),
            'created_at' => now(),
        ]);
        
        // Create impact analysis records
        $this->createImpactAnalysisRecords($changeRequest);
        
        // Create change history record
        ChangeHistory::create([
            'fee_structure_id' => $changeRequest->fee_structure_id,
            'branch_id' => $feeStructure->branch_id,
            'user_id' => $changeRequest->requested_by,
            'action_type' => 'update',
            'entity_type' => 'fee_structure',
            'entity_id' => $changeRequest->fee_structure_id,
            'old_values' => $currentStructure,
            'new_values' => $feeStructure->fresh()->toArray(),
            'change_description' => $changeRequest->request_title,
            'source_system' => 'manual',
        ]);
    }
    
    /**
     * Create impact analysis records
     */
    private function createImpactAnalysisRecords($changeRequest)
    {
        foreach ($changeRequest->affected_students as $studentId) {
            $student = Student::findOrFail($studentId);
            
            // Calculate impact for this student
            $oldTotal = 0;
            $newTotal = 0;
            
            foreach ($changeRequest->proposed_changes as $change) {
                if (isset($change['fee_type_id']) && isset($change['amount_change'])) {
                    $oldTotal += abs($change['amount_change']);
                    $newTotal += $change['amount_change'];
                }
            }
            
            ImpactAnalysis::create([
                'change_request_id' => $changeRequest->id,
                'student_id' => $studentId,
                'old_total_fee' => $oldTotal,
                'new_total_fee' => $newTotal,
                'fee_difference' => $newTotal - $oldTotal,
                'percentage_change' => $oldTotal > 0 ? (($newTotal - $oldTotal) / $oldTotal) * 100 : 0,
                'impact_level' => $this->getImpactLevel(abs($newTotal - $oldTotal)),
                'impact_details' => "Fee structure change affects student's fee structure",
            ]);
        }
    }
    
    /**
     * Get impact level based on difference amount
     */
    private function getImpactLevel($difference)
    {
        if ($difference == 0) {
            return 'none';
        } elseif ($difference < 1000) {
            return 'low';
        } elseif ($difference < 5000) {
            return 'medium';
        } else {
            return 'high';
        }
    }
    
    /**
     * Update workflow assignment
     */
    private function updateWorkflowAssignment($changeRequest, $approvalLevel)
    {
        $workflowAssignment = WorkflowAssignments::where('change_request_id', $changeRequest->id)
            ->first();
        
        if ($workflowAssignment) {
            $currentStep = $workflowAssignment->current_step;
            $totalSteps = json_decode($workflowAssignment->workflow->workflow_steps)->total_steps ?? 3;
            
            if ($approvalLevel === 'final') {
                $workflowAssignment->assignment_status = 'completed';
            } else {
                $workflowAssignment->current_step = $currentStep + 1;
            }
            
            $workflowAssignment->save();
        }
    }
    
    /**
     * Send approval notifications
     */
    private function sendApprovalNotifications($changeRequest, $approval)
    {
        // Notify requester
        ChangeNotifications::create([
            'change_request_id' => $changeRequest->id,
            'notification_type' => 'notification',
            'recipient_type' => 'staff',
            'recipients' => [$changeRequest->requested_by],
            'notification_status' => 'pending',
        ]);
        
        // Notify next approvers if not final
        if ($approval->approval_level !== 'final') {
            $nextApprovers = $this->getNextApprovers($changeRequest, $approval->approval_level);
            
            foreach ($nextApprovers as $approver) {
                ChangeNotifications::create([
                    'change_request_id' => $changeRequest->id,
                    'notification_type' => 'notification',
                    'recipient_type' => 'staff',
                    'recipients' => [$approver->id],
                    'notification_status' => 'pending',
                ]);
            }
        }
    }
    
    /**
     * Get next approvers
     */
    private function getNextApprovers($changeRequest, $currentLevel)
    {
        $nextLevel = '';
        switch ($currentLevel) {
            case 'level_1':
                $nextLevel = 'level_2';
                break;
            case 'level_2':
                $nextLevel = 'level_3';
                break;
            case 'level_3':
                $nextLevel = 'final';
                break;
        }
        
        if ($nextLevel) {
            return User::role(['admin', 'finance_manager', 'branch_manager'])
                ->where('branch_id', $changeRequest->branch_id)
                ->limit(3)
                ->get();
        }
        
        return collect();
    }
    
    /**
     * Reject change request
     */
    public function rejectChangeRequest(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        DB::beginTransaction();
        
        try {
            $changeRequest = ChangeRequests::findOrFail($id);
            
            // Update change request status
            $changeRequest->request_status = 'rejected';
            $changeRequest->approved_by = $request->user()->id;
            $changeRequest->approval_date = now();
            $changeRequest->save();
            
            // Create approval record
            ChangeRequestApprovals::create([
                'change_request_id' => $changeRequest->id,
                'approved_by' => $request->user()->id,
                'approval_level' => 'final',
                'approval_status' => 'rejected',
                'approval_comments' => $request->rejection_reason,
                'approved_at' => now(),
            ]);
            
            // Send rejection notifications
            $this->sendRejectionNotifications($changeRequest, $request->rejection_reason);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Change request rejected successfully',
                'data' => $changeRequest
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error rejecting change request: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Send rejection notifications
     */
    private function sendRejectionNotifications($changeRequest, $reason)
    {
        // Notify requester
        ChangeNotifications::create([
            'change_request_id' => $changeRequest->id,
            'notification_type' => 'notification',
            'recipient_type' => 'staff',
            'recipients' => [$changeRequest->requested_by],
            'notification_status' => 'pending',
            'notification_content' => "Your change request has been rejected. Reason: $reason",
        ]);
    }
    
    /**
     * Get change request details
     */
    public function getChangeRequestDetails($id)
    {
        $changeRequest = ChangeRequests::with([
            'feeStructure',
            'branch',
            'requestedBy',
            'approvals',
            'workflowAssignments',
            'impactAnalysis',
            'changeNotifications'
        ])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $changeRequest
        ]);
    }
    
    /**
     * Get fee structure versions
     */
    public function getFeeStructureVersions($feeStructureId)
    {
        $versions = FeeStructureVersions::with(['user'])
            ->where('fee_structure_id', $feeStructureId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $versions
        ]);
    }
    
    /**
     * Rollback fee structure to previous version
     */
    public function rollbackFeeStructure(Request $request, $versionId)
    {
        $validator = Validator::make($request->all(), [
            'rollback_reason' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        DB::beginTransaction();
        
        try {
            $version = FeeStructureVersions::findOrFail($versionId);
            $feeStructure = FeeStructure::findOrFail($version->fee_structure_id);
            
            // Revert to old structure data
            $oldStructure = $version->old_structure_data;
            
            // Update fee structure with old data
            foreach ($oldStructure['fee_types'] as $feeType) {
                $currentFeeType = \App\Models\FeeStructure::where('id', $version->fee_structure_id)
                    ->where('fee_type_id', $feeType['fee_type_id'])
                    ->first();
                
                if ($currentFeeType) {
                    $currentFeeType->amount = $feeType['amount'];
                    $currentFeeType->save();
                }
            }
            
            // Create rollback version
            FeeStructureVersions::create([
                'fee_structure_id' => $version->fee_structure_id,
                'branch_id' => $feeStructure->branch_id,
                'version_number' => 'v' . (FeeStructureVersions::where('fee_structure_id', $version->fee_structure_id)->count() + 1),
                'version_name' => 'Rollback to ' . $version->version_number,
                'version_description' => 'Rollback: ' . $request->rollback_reason,
                'old_structure_data' => $feeStructure->fresh()->toArray(),
                'new_structure_data' => $oldStructure,
                'changed_fields' => ['rollback' => true],
                'total_old_amount' => array_sum(array_column($feeStructure->fresh()->fee_types ?? [], 'amount')),
                'total_new_amount' => array_sum(array_column($oldStructure['fee_types'] ?? [], 'amount')),
                'total_difference' => $version->total_difference,
                'change_type' => 'rollback',
                'change_reason' => $request->rollback_reason,
                'changed_by' => $request->user()->id,
                'effective_date' => now(),
                'created_at' => now(),
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Fee structure rolled back successfully',
                'data' => $version
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error rolling back fee structure: ' . $e->getMessage()
            ], 500);
        }
    }
}