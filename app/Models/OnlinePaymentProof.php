<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnlinePaymentProof extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'voucher_id', 'student_enrollment_id', 'academy_account_id',
        'payment_method', 'sender_name', 'sender_number', 'transaction_id',
        'amount_sent', 'payment_datetime', 'screenshot_path',
        'submitted_by', 'submission_notes',
        'verification_status', 'verified_by', 'verified_at',
        'rejection_reason', 'fee_payment_id',
    ];

    protected $casts = [
        'amount_sent'      => 'decimal:2',
        'payment_datetime' => 'datetime',
        'verified_at'      => 'datetime',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(FeeVoucher::class, 'voucher_id');
    }

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function academyAccount(): BelongsTo
    {
        return $this->belongsTo(AcademyPaymentAccount::class, 'academy_account_id');
    }

    public function feePayment(): BelongsTo
    {
        return $this->belongsTo(FeePayment::class, 'fee_payment_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopePending($query)
    {
        return $query->where('verification_status', 'pending');
    }
}

