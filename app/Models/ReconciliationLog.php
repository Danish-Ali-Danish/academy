<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationLog extends Model
{
    use HasFactory;

    protected $table = 'reconciliation_logs';
    protected $guarded = ['id'];

    protected $casts = [
        'system_total'       => 'decimal:2',
        'bank_total'         => 'decimal:2',
        'difference'         => 'decimal:2',
        'reconciliation_date'=> 'date',
        'adjustments'        => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }
}
