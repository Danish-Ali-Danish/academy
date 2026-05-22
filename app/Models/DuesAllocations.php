<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuesAllocations extends Model
{
    use HasFactory;

    protected $table = 'dues_allocations';
    protected $guarded = ['id'];

    protected $casts = [
        'allocated_amount'   => 'decimal:2',
        'remaining_amount'   => 'decimal:2',
    ];

    public function duesHistory(): BelongsTo
    {
        return $this->belongsTo(DuesHistory::class, 'dues_history_id');
    }

    public function advanceAllocation(): BelongsTo
    {
        return $this->belongsTo(AdvanceAllocation::class, 'advance_allocation_id');
    }
}
