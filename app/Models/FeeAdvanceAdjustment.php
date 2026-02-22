<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeAdvanceAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_enrollment_id', 'from_payment_id', 'to_voucher_id',
        'adjusted_amount', 'adjusted_by', 'adjusted_at', 'notes',
    ];

    protected $casts = [
        'adjusted_amount' => 'decimal:2',
        'adjusted_at'     => 'date',
    ];

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function fromPayment(): BelongsTo
    {
        return $this->belongsTo(FeePayment::class, 'from_payment_id');
    }

    public function toVoucher(): BelongsTo
    {
        return $this->belongsTo(FeeVoucher::class, 'to_voucher_id');
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}
