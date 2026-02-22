<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentFeeConcession extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'student_enrollment_id', 'fee_type_id', 'concession_type_id',
        'discount_type', 'discount_value',
        'start_date', 'end_date', 'approved_by', 'remarks', 'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'start_date'     => 'date',
        'end_date'       => 'date',
        'is_active'      => 'boolean',
    ];

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    public function concessionType(): BelongsTo
    {
        return $this->belongsTo(FeeConcessionType::class, 'concession_type_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}