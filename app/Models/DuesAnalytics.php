<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DuesAnalytics extends Model
{
    use HasFactory;

    protected $table = 'dues_analytics';
    protected $guarded = ['id'];

    protected $casts = [
        'analytics_date'    => 'date',
        'total_dues'        => 'decimal:2',
        'current_dues'      => 'decimal:2',
        'overdue_dues'      => 'decimal:2',
        'aged_30_days'      => 'decimal:2',
        'aged_60_days'      => 'decimal:2',
        'aged_90_days'      => 'decimal:2',
        'total_penalties'   => 'decimal:2',
        'dues_cases'        => 'integer',
    ];

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
