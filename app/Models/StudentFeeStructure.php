<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentFeeStructure extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'student_enrollment_id',
        'fee_structure_id',
        'custom_amount',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'custom_amount' => 'decimal:2',
        'is_active'     => 'boolean',
    ];

    // ─── Relationships ──────────────────────────────────────────────────────────

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Accessors ───────────────────────────────────────────────────────────────

    /**
     * Effective amount = custom_amount if set, otherwise fee_structure.amount
     */
    public function getEffectiveAmountAttribute(): float
    {
        return (float) ($this->custom_amount ?? $this->feeStructure?->amount ?? 0);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
