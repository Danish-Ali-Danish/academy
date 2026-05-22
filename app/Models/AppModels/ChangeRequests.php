<?php

namespace App\Models\AppModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeRequests extends Model
{
    use HasFactory;

    protected $table = 'change_requests';

    protected $fillable = [
        'fee_structure_id', 'branch_id', 'requested_by', 'reviewed_by', 'implemented_by',
        'request_code', 'request_title', 'request_description',
        'proposed_changes', 'estimated_impact', 'affected_students',
        'affected_students_count', 'priority', 'request_status',
        'review_comments', 'reviewed_at', 'implemented_at', 'implementation_details',
    ];

    protected $casts = [
        'proposed_changes'         => 'array',
        'affected_students'        => 'array',
        'estimated_impact'         => 'decimal:2',
        'reviewed_at'              => 'datetime',
        'implemented_at'           => 'datetime',
        'affected_students_count'  => 'integer',
    ];

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(\App\Models\FeeStructure::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }

    public function implementedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'implemented_by');
    }
}
