<?php

namespace App\Models\AppModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStructureVersions extends Model
{
    use HasFactory;

    protected $table = 'fee_structure_versions';

    protected $fillable = [
        'fee_structure_id', 'branch_id', 'version_number', 'version_name', 'version_description',
        'old_structure_data', 'new_structure_data', 'changed_fields',
        'total_old_amount', 'total_new_amount', 'total_difference',
        'change_type', 'change_reason', 'changed_by', 'effective_date',
    ];

    protected $casts = [
        'old_structure_data'    => 'array',
        'new_structure_data'    => 'array',
        'changed_fields'        => 'array',
        'effective_date'        => 'datetime',
        'total_old_amount'      => 'decimal:2',
        'total_new_amount'      => 'decimal:2',
        'total_difference'      => 'decimal:2',
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
