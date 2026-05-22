<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStructureChangeRequest extends Model
{
    protected $fillable = [
        'request_code',
        'fee_structure_id',
        'proposed_fee_structure_id',
        'branch_id',
        'class_id',
        'fee_type_id',
        'academic_year_id',
        'old_values',
        'proposed_values',
        'changed_fields',
        'impact_snapshot',
        'affected_students_count',
        'unpaid_vouchers_count',
        'future_vouchers_count',
        'estimated_monthly_difference',
        'reason',
        'status',
        'requested_by',
        'requested_at',
        'reviewed_by',
        'reviewed_at',
        'review_remarks',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'proposed_values' => 'array',
        'changed_fields' => 'array',
        'impact_snapshot' => 'array',
        'affected_students_count' => 'integer',
        'unpaid_vouchers_count' => 'integer',
        'future_vouchers_count' => 'integer',
        'estimated_monthly_difference' => 'decimal:2',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function proposedFeeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class, 'proposed_fee_structure_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
