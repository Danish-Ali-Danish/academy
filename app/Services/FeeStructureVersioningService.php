<?php

namespace App\Services;

use App\Models\FeeStructure;
use App\Models\FeeStructureChangeLog;
use App\Models\FeeStructureChangeRequest;
use App\Models\FeeStructureImmutableAudit;
use App\Models\FeeStructureVersion;
use App\Models\FeeVoucher;
use App\Models\StudentEnrollment;
use App\Models\StudentFeeStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class FeeStructureVersioningService
{
    public function previewImpact(FeeStructure $feeStructure, array $proposedValues): array
    {
        $feeStructure->loadMissing(['academicYear', 'branch', 'class', 'feeType']);

        $old = $this->snapshot($feeStructure);
        $new = array_merge($old, $this->normalizeProposedValues($proposedValues, $feeStructure));
        $changedFields = $this->changedFields($old, $new);

        $affectedEnrollmentQuery = StudentEnrollment::query()
            ->where('academic_year_id', $feeStructure->academic_year_id)
            ->where('branch_id', $feeStructure->branch_id)
            ->where('status', 'active')
            ->whereHas('classSection.branchClass', fn ($query) => $query->where('class_id', $feeStructure->class_id));

        $affectedStudents = (clone $affectedEnrollmentQuery)->count();

        $unpaidVoucherQuery = FeeVoucher::unpaid()
            ->where('academic_year_id', $feeStructure->academic_year_id)
            ->where('fee_type_id', $feeStructure->fee_type_id)
            ->whereHas('studentEnrollment', function ($query) use ($feeStructure) {
                $query->where('branch_id', $feeStructure->branch_id)
                    ->whereHas('classSection.branchClass', fn ($classQuery) => $classQuery->where('class_id', $feeStructure->class_id));
            });

        $unpaidVouchers = (clone $unpaidVoucherQuery)->count();
        $futureVouchers = (clone $unpaidVoucherQuery)->whereDate('due_date', '>=', now()->toDateString())->count();
        $amountDifference = round((float) ($new['amount'] ?? 0) - (float) ($old['amount'] ?? 0), 2);

        return [
            'changed_fields' => $changedFields,
            'affected_students_count' => $affectedStudents,
            'unpaid_vouchers_count' => $unpaidVouchers,
            'future_vouchers_count' => $futureVouchers,
            'estimated_monthly_difference' => round($amountDifference * $affectedStudents, 2),
            'old_values' => $old,
            'proposed_values' => $new,
            'student_sample' => (clone $affectedEnrollmentQuery)
                ->with('student:id,student_name,roll_no')
                ->limit(5)
                ->get()
                ->map(fn ($enrollment) => [
                    'id' => $enrollment->id,
                    'name' => $enrollment->student?->student_name ?? '-',
                    'roll_no' => $enrollment->student?->roll_no ?? '-',
                ])
                ->values()
                ->all(),
        ];
    }

    public function requestChange(FeeStructure $feeStructure, array $proposedValues, string $reason, ?Request $request = null): FeeStructureChangeRequest
    {
        $impact = $this->previewImpact($feeStructure, $proposedValues);

        if (empty($impact['changed_fields'])) {
            throw new RuntimeException('No fee structure changes were detected.');
        }

        return DB::transaction(function () use ($feeStructure, $impact, $reason, $request) {
            $pendingExists = FeeStructureChangeRequest::where('fee_structure_id', $feeStructure->id)
                ->where('status', 'pending')
                ->exists();

            if ($pendingExists) {
                throw new RuntimeException('This fee structure already has a pending approval request.');
            }

            $changeRequest = FeeStructureChangeRequest::create([
                'request_code' => $this->nextRequestCode(),
                'fee_structure_id' => $feeStructure->id,
                'branch_id' => $feeStructure->branch_id,
                'class_id' => $feeStructure->class_id,
                'fee_type_id' => $feeStructure->fee_type_id,
                'academic_year_id' => $feeStructure->academic_year_id,
                'old_values' => $impact['old_values'],
                'proposed_values' => $impact['proposed_values'],
                'changed_fields' => $impact['changed_fields'],
                'impact_snapshot' => $impact,
                'affected_students_count' => $impact['affected_students_count'],
                'unpaid_vouchers_count' => $impact['unpaid_vouchers_count'],
                'future_vouchers_count' => $impact['future_vouchers_count'],
                'estimated_monthly_difference' => $impact['estimated_monthly_difference'],
                'reason' => $reason,
                'status' => 'pending',
                'requested_by' => auth()->id(),
                'requested_at' => now(),
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);

            $this->audit($changeRequest, 'requested', null, $changeRequest->toArray(), [
                'fee_structure_id' => $feeStructure->id,
            ], $request);

            return $changeRequest;
        });
    }

    public function approve(FeeStructureChangeRequest $changeRequest, string $remarks, ?Request $request = null): FeeStructure
    {
        return DB::transaction(function () use ($changeRequest, $remarks, $request) {
            $changeRequest = FeeStructureChangeRequest::lockForUpdate()->findOrFail($changeRequest->id);

            if ($changeRequest->status !== 'pending') {
                throw new RuntimeException('Only pending fee structure change requests can be approved.');
            }

            $oldStructure = FeeStructure::lockForUpdate()->findOrFail($changeRequest->fee_structure_id);
            $newValues = $changeRequest->proposed_values;
            $nextVersionNo = ((int) ($oldStructure->version_no ?? 1)) + 1;

            $newStructure = FeeStructure::create([
                'parent_fee_structure_id' => $oldStructure->parent_fee_structure_id ?: $oldStructure->id,
                'version_no' => $nextVersionNo,
                'version_status' => 'active',
                'academic_year_id' => $newValues['academic_year_id'],
                'branch_id' => $newValues['branch_id'],
                'class_id' => $newValues['class_id'],
                'fee_type_id' => $newValues['fee_type_id'],
                'amount' => $newValues['amount'],
                'due_day' => $newValues['due_day'] ?? null,
                'effective_from' => $newValues['effective_from'] ?? null,
                'effective_to' => $newValues['effective_to'] ?? null,
                'is_active' => (bool) ($newValues['is_active'] ?? true),
                'created_by' => $oldStructure->created_by,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'change_request_id' => $changeRequest->id,
            ]);

            $oldStructure->update([
                'is_active' => false,
                'version_status' => 'superseded',
                'superseded_by_fee_structure_id' => $newStructure->id,
            ]);

            StudentFeeStructure::where('fee_structure_id', $oldStructure->id)
                ->update(['is_active' => false]);

            if ($newStructure->is_active) {
                $newStructure->syncToEnrolledStudents();
            }

            $versionNumber = 'FS' . $oldStructure->id . '-V' . $nextVersionNo;
            $version = FeeStructureVersion::create([
                'request_id' => $changeRequest->id,
                'status' => 'approved',
                'fee_structure_id' => $newStructure->id,
                'branch_id' => $newStructure->branch_id,
                'version_number' => $versionNumber,
                'version_name' => 'Version ' . $nextVersionNo,
                'version_description' => $changeRequest->reason,
                'old_structure_data' => $changeRequest->old_values,
                'new_structure_data' => $changeRequest->proposed_values,
                'changed_fields' => $changeRequest->changed_fields,
                'total_old_amount' => $changeRequest->old_values['amount'] ?? 0,
                'total_new_amount' => $changeRequest->proposed_values['amount'] ?? 0,
                'total_difference' => ($changeRequest->proposed_values['amount'] ?? 0) - ($changeRequest->old_values['amount'] ?? 0),
                'change_type' => 'approved_fee_structure_change',
                'change_reason' => $changeRequest->reason,
                'changed_by' => $changeRequest->requested_by,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'effective_date' => $newStructure->effective_from ?: now(),
            ]);

            FeeStructureChangeLog::create([
                'request_id' => $changeRequest->id,
                'version_number' => $versionNumber,
                'fee_structure_id' => $newStructure->id,
                'branch_id' => $newStructure->branch_id,
                'class_id' => $newStructure->class_id,
                'fee_type_id' => $newStructure->fee_type_id,
                'academic_year_id' => $newStructure->academic_year_id,
                'old_amount' => $changeRequest->old_values['amount'] ?? 0,
                'new_amount' => $changeRequest->proposed_values['amount'] ?? 0,
                'old_due_day' => $changeRequest->old_values['due_day'] ?? null,
                'new_due_day' => $changeRequest->proposed_values['due_day'] ?? null,
                'old_values' => $changeRequest->old_values,
                'new_values' => $changeRequest->proposed_values,
                'changed_fields' => $changeRequest->changed_fields,
                'impact_snapshot' => $changeRequest->impact_snapshot,
                'change_reason' => $changeRequest->reason,
                'effective_from' => $newStructure->effective_from ?: now(),
                'affects_existing_vouchers' => false,
                'changed_by' => auth()->id(),
                'changed_at' => now(),
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);

            $changeRequest->update([
                'proposed_fee_structure_id' => $newStructure->id,
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_remarks' => $remarks,
            ]);

            $this->audit($newStructure, 'approved_version_created', $changeRequest->old_values, $changeRequest->proposed_values, [
                'request_id' => $changeRequest->id,
                'version_id' => $version->id,
                'old_fee_structure_id' => $oldStructure->id,
            ], $request);

            return $newStructure;
        });
    }

    public function reject(FeeStructureChangeRequest $changeRequest, string $remarks, ?Request $request = null): void
    {
        DB::transaction(function () use ($changeRequest, $remarks, $request) {
            $changeRequest = FeeStructureChangeRequest::lockForUpdate()->findOrFail($changeRequest->id);

            if ($changeRequest->status !== 'pending') {
                throw new RuntimeException('Only pending fee structure change requests can be rejected.');
            }

            $changeRequest->update([
                'status' => 'rejected',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_remarks' => $remarks,
            ]);

            $this->audit($changeRequest, 'rejected', null, $changeRequest->fresh()->toArray(), [], $request);
        });
    }

    public function currentVersionId(?FeeStructure $feeStructure): ?int
    {
        if (!$feeStructure) {
            return null;
        }

        return FeeStructureVersion::where('fee_structure_id', $feeStructure->id)
            ->where('status', 'approved')
            ->latest('id')
            ->value('id');
    }

    private function normalizeProposedValues(array $values, FeeStructure $feeStructure): array
    {
        return [
            'academic_year_id' => (int) ($values['academic_year_id'] ?? $feeStructure->academic_year_id),
            'branch_id' => (int) ($values['branch_id'] ?? $feeStructure->branch_id),
            'class_id' => (int) ($values['class_id'] ?? $feeStructure->class_id),
            'fee_type_id' => (int) ($values['fee_type_id'] ?? $feeStructure->fee_type_id),
            'amount' => round((float) ($values['amount'] ?? $feeStructure->amount), 2),
            'due_day' => array_key_exists('due_day', $values)
                ? ($values['due_day'] === '' ? null : $values['due_day'])
                : $feeStructure->due_day,
            'effective_from' => $values['effective_from'] ?? optional($feeStructure->effective_from)->format('Y-m-d'),
            'effective_to' => $values['effective_to'] ?? optional($feeStructure->effective_to)->format('Y-m-d'),
            'is_active' => (bool) ($values['is_active'] ?? $feeStructure->is_active),
        ];
    }

    private function snapshot(FeeStructure $feeStructure): array
    {
        return [
            'academic_year_id' => (int) $feeStructure->academic_year_id,
            'branch_id' => (int) $feeStructure->branch_id,
            'class_id' => (int) $feeStructure->class_id,
            'fee_type_id' => (int) $feeStructure->fee_type_id,
            'amount' => round((float) $feeStructure->amount, 2),
            'due_day' => $feeStructure->due_day,
            'effective_from' => optional($feeStructure->effective_from)->format('Y-m-d'),
            'effective_to' => optional($feeStructure->effective_to)->format('Y-m-d'),
            'is_active' => (bool) $feeStructure->is_active,
            'version_no' => (int) ($feeStructure->version_no ?? 1),
            'version_status' => $feeStructure->version_status ?? 'active',
        ];
    }

    private function changedFields(array $old, array $new): array
    {
        $fields = [];
        foreach ($new as $key => $value) {
            if (in_array($key, ['version_no', 'version_status'], true)) {
                continue;
            }
            if (($old[$key] ?? null) != $value) {
                $fields[$key] = [
                    'old' => $old[$key] ?? null,
                    'new' => $value,
                ];
            }
        }

        return $fields;
    }

    private function nextRequestCode(): string
    {
        return 'FSCR-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }

    private function audit($auditable, string $event, ?array $oldValues, ?array $newValues, array $metadata = [], ?Request $request = null): void
    {
        FeeStructureImmutableAudit::create([
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'user_id' => auth()->id(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
