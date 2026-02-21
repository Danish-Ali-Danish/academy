<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fee extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'fee_type_id',
        'class_id',
        'branch_id',
        'month',
        'year',
        'academic_year',
        'total_amount',
        'discount_percent',
        'discount_amount',
        'discount_reason',
        'fine_amount',
        'paid_amount',
        'due_date',
        'status',
        'is_waived',
        'waived_by',
        'waiver_reason',
        'remarks',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'fine_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'year' => 'integer',
        'is_waived' => 'boolean',
    ];

    protected $appends = ['net_amount', 'balance_amount'];

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function waivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waived_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePartial($query)
    {
        return $query->where('status', 'partial');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereIn('status', ['pending', 'partial']);
    }

    public function scopeByMonth($query, $month, $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    public function scopeByAcademicYear($query, $academicYear)
    {
        return $query->where('academic_year', $academicYear);
    }

    // Accessors
    public function getNetAmountAttribute()
    {
        return $this->total_amount - $this->discount_amount + $this->fine_amount;
    }

    public function getBalanceAmountAttribute()
    {
        return $this->net_amount - $this->paid_amount;
    }

    // Helper Methods

    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function makePayment($amount, $paymentData)
    {
        $this->paid_amount += $amount;
        
        if ($this->paid_amount >= $this->net_amount) {
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial';
        }
        
        $this->save();
        
        return $this->payments()->create(array_merge($paymentData, [
            'amount_paid' => $amount,
        ]));
    }
}