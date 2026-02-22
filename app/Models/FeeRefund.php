<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeRefund extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'student_enrollment_id', 'payment_id',
        'refund_amount', 'refund_date', 'reason',
        'refund_method', 'bank_details',
        'refunded_by', 'status', 'notes',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'refund_date'   => 'date',
    ];

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(FeePayment::class, 'payment_id');
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }
}