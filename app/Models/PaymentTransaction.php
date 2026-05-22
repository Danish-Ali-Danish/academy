<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $table = 'payment_transactions';
    protected $guarded = ['id'];

    protected $casts = [
        'amount'          => 'decimal:2',
        'balance_amount'  => 'decimal:2',
        'transaction_date'=> 'datetime',
        'payment_metadata'=> 'array',
    ];

    public function feeVoucher(): BelongsTo
    {
        return $this->belongsTo(FeeVoucher::class, 'fee_voucher_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
