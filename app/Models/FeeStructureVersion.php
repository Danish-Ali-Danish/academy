<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeStructureVersion extends Model
{
    protected $table = 'fee_structure_versions';

    protected $fillable = [
        'request_id', 'status', 'fee_structure_id', 'branch_id', 'version_number', 'version_name', 'version_description',
        'old_structure_data', 'new_structure_data', 'changed_fields', 'total_old_amount', 'total_new_amount',
        'total_difference', 'change_type', 'change_reason', 'changed_by', 'approved_by', 'approved_at', 'effective_date', 'created_at'
    ];

    protected $casts = [
        'old_structure_data' => 'array',
        'new_structure_data' => 'array',
        'changed_fields' => 'array',
        'effective_date' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function feeStructure()
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function request()
    {
        return $this->belongsTo(FeeStructureChangeRequest::class, 'request_id');
    }
}
