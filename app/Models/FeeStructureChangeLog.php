<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeStructureChangeLog extends Model
{
    protected $table = 'fee_structure_change_log';

    public $timestamps = false; // Based on schema

    protected $fillable = [
        'fee_structure_id',
        'branch_id',
        'class_id',
        'fee_type_id',
        'academic_year_id',
        'old_amount',
        'new_amount',
        'old_due_day',
        'new_due_day',
        'change_reason',
        'effective_from',
        'affects_existing_vouchers',
        'changed_by',
        'changed_at',
        'request_id',
        'version_number',
        'old_values',
        'new_values',
        'changed_fields',
        'impact_snapshot',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'changed_at' => 'datetime',
        'affects_existing_vouchers' => 'boolean',
        'old_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_fields' => 'array',
        'impact_snapshot' => 'array',
    ];

    public function feeStructure()
    {
        return $this->belongsTo(FeeStructure::class, 'fee_structure_id')->withTrashed();
    }

    public function feeType()
    {
        return $this->belongsTo(FeeType::class, 'fee_type_id')->withTrashed();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id')->withTrashed();
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id')->withTrashed();
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function request()
    {
        return $this->belongsTo(FeeStructureChangeRequest::class, 'request_id');
    }
}
