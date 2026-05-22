<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionSummary extends Model
{
    use HasFactory;

    protected $table = 'collection_summaries';
    protected $guarded = ['id'];

    protected $casts = [
        'total_target'     => 'decimal:2',
        'total_collected'  => 'decimal:2',
        'total_pending'    => 'decimal:2',
        'online_payments'  => 'decimal:2',
        'cash_payments'    => 'decimal:2',
        'cheque_payments'  => 'decimal:2',
        'bank_transfer'    => 'decimal:2',
        'summary_date'     => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
