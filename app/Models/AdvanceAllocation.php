<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvanceAllocation extends Model
{
    use HasFactory;

    protected $table = 'advance_allocations';
    protected $guarded = ['id'];

    protected $casts = [
        'advance_amount'      => 'decimal:2',
        'allocated_amount'    => 'decimal:2',
        'remaining_amount'    => 'decimal:2',
        'expiry_date'         => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
