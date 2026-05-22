<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenaltyRules extends Model
{
    use HasFactory;

    protected $table = 'penalty_rules';
    protected $guarded = ['id'];

    protected $casts = [
        'penalty_percentage'       => 'decimal:2',
        'max_penalty_percentage'   => 'decimal:2',
        'compound_penalty'         => 'boolean',
        'is_active'                => 'boolean',
        'after_days'               => 'integer',
        'effective_from'           => 'date',
        'effective_to'             => 'date',
        'applicable_fees'          => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
