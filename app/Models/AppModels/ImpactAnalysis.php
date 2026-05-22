<?php

namespace App\Models\AppModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImpactAnalysis extends Model
{
    use HasFactory;

    protected $table = 'impact_analysis';

    protected $fillable = [
        'change_request_id', 'student_id',
        'old_total_fee', 'new_total_fee', 'fee_difference', 'percentage_change',
        'impact_level', 'impact_details',
    ];

    protected $casts = [
        'old_total_fee'      => 'decimal:2',
        'new_total_fee'      => 'decimal:2',
        'fee_difference'     => 'decimal:2',
        'percentage_change'  => 'decimal:2',
    ];

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequests::class, 'change_request_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Student::class);
    }
}
