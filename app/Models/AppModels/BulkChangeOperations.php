<?php

namespace App\Models\AppModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BulkChangeOperations extends Model
{
    use HasFactory;

    protected $table = 'bulk_change_operations';

    protected $fillable = [
        'branch_id', 'initiated_by',
        'operation_type', 'operation_code', 'operation_details',
        'total_records', 'success_records', 'failed_records',
        'success_amount', 'failed_amount',
        'operation_status', 'operation_results', 'error_details',
    ];

    protected $casts = [
        'operation_details'   => 'array',
        'operation_results'   => 'array',
        'total_records'       => 'integer',
        'success_records'     => 'integer',
        'failed_records'      => 'integer',
        'success_amount'      => 'decimal:2',
        'failed_amount'       => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'initiated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BulkChangeItems::class, 'bulk_change_operation_id');
    }
}
