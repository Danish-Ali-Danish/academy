<?php

namespace App\Models\AppModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalWorkflow extends Model
{
    use HasFactory;

    protected $table = 'approval_workflow';

    protected $fillable = [
        'workflow_name', 'workflow_code', 'workflow_steps',
        'total_steps', 'workflow_type', 'is_active', 'is_default', 'branch_id',
    ];

    protected $casts = [
        'workflow_steps' => 'array',
        'is_active'      => 'boolean',
        'is_default'     => 'boolean',
        'total_steps'    => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }
}
