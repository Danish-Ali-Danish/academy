<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BulkPayment extends Model
{
    use HasFactory;

    protected $table = 'bulk_payments';
    protected $guarded = ['id'];

    protected $casts = [
        'total_amount'     => 'decimal:2',
        'success_amount'   => 'decimal:2',
        'failed_amount'    => 'decimal:2',
        'processing_details' => 'array',
        'failure_details'  => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BulkPaymentItem::class, 'bulk_payment_id');
    }
}
