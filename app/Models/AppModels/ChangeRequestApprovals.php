<?php

namespace App\Models\AppModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeRequestApprovals extends Model
{
    use HasFactory;

    protected $table = 'change_request_approvals';

    protected $fillable = [
        'change_request_id', 'approved_by',
        'approval_level', 'approval_status', 'approval_comments', 'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequests::class, 'change_request_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}
