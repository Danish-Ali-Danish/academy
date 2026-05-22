<?php

namespace App\Models\AppModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeHistory extends Model
{
    use HasFactory;

    protected $table = 'change_history';

    protected $fillable = [
        'fee_structure_id', 'branch_id', 'changed_by',
        'change_event', 'change_data', 'change_description',
        'source_system', 'additional_metadata',
    ];

    protected $casts = [
        'change_data'           => 'array',
        'additional_metadata'   => 'array',
    ];

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(\App\Models\FeeStructure::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'changed_by');
    }
}
