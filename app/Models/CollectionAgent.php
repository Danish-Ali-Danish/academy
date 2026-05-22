<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionAgent extends Model
{
    use HasFactory;

    protected $table = 'collection_agents';
    protected $guarded = ['id'];

    protected $casts = [
        'collection_target'   => 'decimal:2',
        'collected_amount'    => 'decimal:2',
        'commission_rate'     => 'decimal:2',
        'total_commission'    => 'decimal:2',
        'joining_date'        => 'date',
        'termination_date'    => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
