<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeWaiver extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'voucher_id', 'student_enrollment_id',
        'waived_amount', 'waiver_reason',
        'approved_by', 'approved_on', 'status',
        'reversed_by', 'reversal_reason', 'notes',
    ];

    protected $casts = [
        'waived_amount' => 'decimal:2',
        'approved_on'   => 'date',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(FeeVoucher::class, 'voucher_id');
    }

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}