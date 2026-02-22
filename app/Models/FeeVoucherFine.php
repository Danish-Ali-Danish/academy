<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeVoucherFine extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_id', 'fine_rule_id', 'days_overdue',
        'fine_type', 'fine_value', 'calculated_amount',
        'applied_on', 'applied_by', 'is_waived', 'waived_by', 'notes',
    ];

    protected $casts = [
        'days_overdue'      => 'integer',
        'fine_value'        => 'decimal:2',
        'calculated_amount' => 'decimal:2',
        'applied_on'        => 'date',
        'is_waived'         => 'boolean',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(FeeVoucher::class, 'voucher_id');
    }

    public function fineRule(): BelongsTo
    {
        return $this->belongsTo(FeeFineRule::class, 'fine_rule_id');
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function waivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waived_by');
    }
}
