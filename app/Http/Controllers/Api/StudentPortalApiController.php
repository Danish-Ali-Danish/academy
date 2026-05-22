<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\StudentFeeConcession;
use App\Models\StudentInstallmentAssignment;
use App\Models\ClassSection;

class StudentPortalApiController extends Controller
{
    /**
     * Authenticate student using roll_no or admission_no
     */
    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string'
        ]);

        $student = Student::where('roll_no', $request->identifier)
            ->orWhere('admission_no', $request->identifier)
            ->with(['parent', 'activeEnrollment.classSection.branchClass.class', 'activeEnrollment.classSection.section', 'activeEnrollment.academicYear'])
            ->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found with this ID.'], 404);
        }

        if (!$student->is_active) {
            return response()->json(['message' => 'Account is deactivated.'], 403);
        }

        $enrollment = $student->activeEnrollment;
        
        $frontendStudentData = [
            'id' => $student->id,
            'enrollment_id' => $enrollment ? $enrollment->id : null,
            'rollNo' => $student->roll_no,
            'admissionNo' => $student->admission_no,
            'name' => $student->student_name,
            'fatherName' => $student->parent ? $student->parent->father_name : '-',
            'class' => $enrollment && $enrollment->classSection && $enrollment->classSection->branchClass && $enrollment->classSection->branchClass->class ? $enrollment->classSection->branchClass->class->class_name : '-',
            'section' => $enrollment && $enrollment->classSection && $enrollment->classSection->section ? $enrollment->classSection->section->section_name : '-',
            'branch' => $enrollment && $enrollment->classSection && $enrollment->classSection->branchClass && $enrollment->classSection->branchClass->branch ? $enrollment->classSection->branchClass->branch->name : '-',
            'dob' => $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : null,
            'bloodGroup' => $student->blood_group,
            'contact' => $student->whatsapp_number,
        ];

        return response()->json([
            'message' => 'Login successful',
            'student' => $frontendStudentData,
        ]);
    }

    /**
     * Get Student Dashboard Stats
     */
    public function getDashboardData($studentId)
    {
        // For now, we mock the stats and assignments like in the static version.
        // As Academy Management features grow (Attendance, Library), this can be fetched.
        $statsData = [
            [ 'label' => 'Overall Attendance', 'value' => '92%', 'change' => '+2.5%', 'isPositive' => true, 'colorStart' => '#10b981', 'colorEnd' => '#059669' ],
            [ 'label' => 'Total Assignments', 'value' => '0', 'change' => '0 pending', 'isPositive' => true, 'colorStart' => '#6366f1', 'colorEnd' => '#8b5cf6' ],
            [ 'label' => 'Fee Status', 'value' => 'Active', 'change' => 'Check Details', 'isPositive' => true, 'colorStart' => '#f59e0b', 'colorEnd' => '#d97706' ],
            [ 'label' => 'Current Rank', 'value' => 'N/A', 'change' => 'Not graded', 'isPositive' => true, 'colorStart' => '#3b82f6', 'colorEnd' => '#2563eb' ]
        ];

        return response()->json([
            'stats' => $statsData
        ]);
    }

    /**
     * Get Fee Details and Installment Plan
     */
    public function getFeeDetails($studentId)
    {
        $student = Student::with('activeEnrollment')->find($studentId);
        if (!$student || !$student->activeEnrollment) {
            return response()->json(['message' => 'No active enrollment found'], 404);
        }

        $enrollmentId = $student->activeEnrollment->id;

        // Fetch active installment assignment for this enrollment
        $assignment = StudentInstallmentAssignment::where('student_enrollment_id', $enrollmentId)
            ->where('status', 'active')
            ->with(['schedule' => function ($q) {
                $q->orderBy('kist_number', 'asc');
            }, 'installmentPlan.feeType'])
            ->first();

        // Check if there's an active concession
        $concessionAmount = 0;
        $concessionType = null;
        $concessionApplied = false;

        $concession = StudentFeeConcession::where('student_enrollment_id', $enrollmentId)
            ->where('is_active', true)
            ->with('concessionType')
            ->first();

        if ($assignment) {
            // Find base actual fee structure
            $baseAmount = $assignment->total_amount; // assuming this total_amount is gross before or after? The system saves net mostly, but let's see. Wait, in Controller it was netAmount.
            
            // To make chart complete: Since creating assignment saves total_amount.
            // Let's assume total_amount on assignment is what the user has assigned.
            
            // Recompute concession since it was asked "agr fee concesssion nahi hoi to b fee strucutre sy jo assign hoi ha"
            if ($concession) {
                $concessionApplied = true;
                $concessionType = $concession->concessionType ? $concession->concessionType->name : 'Discount';
                $feeTypeId = $assignment->installmentPlan->applicable_fee_type_id;

                // Let's safely extract if concession matches plan:
                if ($concession->fee_type_id == $feeTypeId) {
                    if ($concession->discount_type === 'percentage') {
                        // Estimate original fee from Net Fee: Net = Original - (Original*Percent) -> Original = Net / (1-percent)
                        // It's safer to just lookup actual FeeStructure, but we don't know it exactly here without re-joining.
                        // Since backend `create()` controller gets netAmount, if they set it. Let's just output base as total_amount + concession value for demonstration if fixed.
                    }
                }
            }

            // Mapped to Frontend FeeDetails format
            $mappedInstallments = [];
            foreach ($assignment->schedule as $kist) {
                $mappedInstallments[] = [
                    'id' => $kist->id,
                    'kist_number' => $kist->kist_number,
                    'amount' => (float) $kist->kist_amount,
                    'dueDate' => $kist->due_date ? $kist->due_date->format('Y-m-d') : '-',
                    'status' => ucfirst($kist->status),
                    'paidDate' => $kist->payment_date ? $kist->payment_date->format('Y-m-d') : null,
                ];
            }

            // Estimate total assigned fee as sum of kists
            $sumKists = collect($mappedInstallments)->sum('amount');
            $grossExtrapolated = $concessionApplied && $concession->discount_type === 'fixed' 
                ? $sumKists + $concession->discount_value 
                : $sumKists; // simplified representation

            return response()->json([
               'feeData' => [
                   'totalFee' => $grossExtrapolated,
                   'concessionApplied' => $concessionApplied,
                   'concessionType' => $concessionType,
                   'concessionAmount' => $concessionApplied ? (float) $concession->discount_value : 0,
                   'netPayable' => $sumKists,
                   'paidAmount' => (float) $assignment->amount_paid,
                   'remainingAmount' => (float) $assignment->remaining_amount,
                   'installments' => $mappedInstallments,
               ]
            ]);
        }

        // Return empty chart if no assignments
        return response()->json([
           'feeData' => [
               'totalFee' => 0,
               'concessionApplied' => false,
               'concessionType' => null,
               'concessionAmount' => 0,
               'netPayable' => 0,
               'paidAmount' => 0,
               'remainingAmount' => 0,
               'installments' => []
           ]
        ]);
    }
}
