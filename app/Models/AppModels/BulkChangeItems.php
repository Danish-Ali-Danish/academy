<?php

namespace App\Models\AppModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkChangeItems extends Model
{
    use HasFactory;

    protected $table = 'bulk_change_items';

    protected $fillable = [
        'bulk_change_operation_id', 'entity_type', 'entity_id',
        'change_data', 'item_status', 'error_message', 'item_details',
    ];

    protected $casts = [
        'change_data' => 'array',
    ];

    public function bulkChangeOperation(): BelongsTo
    {
        return $this->belongsTo(BulkChangeOperations::class, 'bulk_change_operation_id');
    }
}
