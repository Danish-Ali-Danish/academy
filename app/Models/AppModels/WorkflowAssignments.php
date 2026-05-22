<?php

namespace App\Models\AppModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowAssignments extends Model
{
    use HasFactory;

    protected $table = 'workflow_assignments';

    protected $fillable = [
        'change_request_id', 'workflow_id',
        'current_step', 'assignment_status', 'assignment_details',
    ];

    protected $casts = [
        'current_step' => 'integer',
    ];

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequests::class, 'change_request_id');
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }
}
