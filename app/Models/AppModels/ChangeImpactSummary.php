<?php

namespace App\Models\AppModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeImpactSummary extends Model
{
    use HasFactory;

    protected $table = 'change_impact_summary';

    protected $fillable = [
        'change_request_id', 'branch_id',
        'total_students_impacted', 'total_fee_increase', 'total_fee_decrease',
        'average_change_percentage',
        'high_impact_students', 'medium_impact_students', 'low_impact_students',
        'impact_summary', 'recommendations',
    ];

    protected $casts = [
        'total_students_impacted'   => 'decimal:2',
        'total_fee_increase'        => 'decimal:2',
        'total_fee_decrease'        => 'decimal:2',
        'average_change_percentage' => 'decimal:2',
        'high_impact_students'      => 'integer',
        'medium_impact_students'    => 'integer',
        'low_impact_students'       => 'integer',
    ];

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequests::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }
}
