<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherDiscountBreakdown extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'voucher_id', 'discount_source', 'source_id', 'source_label',
        'discount_type', 'discount_value', 'calculated_amount', 'applied_by',
    ];

    protected $casts = [
        'discount_value'    => 'decimal:2',
        'calculated_amount' => 'decimal:2',
        'created_at'        => 'datetime',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(FeeVoucher::class, 'voucher_id');
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }
}

