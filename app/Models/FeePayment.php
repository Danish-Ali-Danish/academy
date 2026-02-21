<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_id',
        'student_id',
        'branch_id',
        'payment_date',
        'amount_paid',
        'payment_method',
        'transaction_id',
        'cheque_number',
        'bank_name',
        'receipt_no',
        'collected_by',
        'notes',
        'is_cancelled',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'amount_paid' => 'decimal:2',
        'is_cancelled' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    // Relationships
    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_cancelled', false);
    }

    public function scopeCancelled($query)
    {
        return $query->where('is_cancelled', true);
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    // Helper Methods
    public function cancel($reason, $userId)
    {
        if ($this->is_cancelled) {
            return false;
        }

        $this->is_cancelled = true;
        $this->cancelled_at = now();
        $this->cancelled_by = $userId;
        $this->cancellation_reason = $reason;
        $this->save();

        // Update fee paid amount
        $fee = $this->fee;
        $fee->paid_amount -= $this->amount_paid;
        
        if ($fee->paid_amount <= 0) {
            $fee->status = 'pending';
        } else {
            $fee->status = 'partial';
        }
        
        $fee->save();

        return true;
    }

    public function generateReceiptNumber()
    {
        $prefix = 'REC';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));
        
        return "{$prefix}-{$date}-{$random}";
    }
}