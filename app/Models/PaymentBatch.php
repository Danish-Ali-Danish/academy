<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentBatch extends Model
{
    use HasFactory;

    protected $table = 'payment_batches';
    protected $guarded = ['id'];

    protected $casts = [
        'total_amount'        => 'decimal:2',
        'processed_amount'    => 'decimal:2',
        'failed_amount'       => 'decimal:2',
        'batch_date'          => 'datetime',
        'processed_at'        => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function collectionAgent(): BelongsTo
    {
        return $this->belongsTo(CollectionAgent::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PaymentBatchItem::class, 'payment_batch_id');
    }
}
