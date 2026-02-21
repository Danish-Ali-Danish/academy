<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'class_id',
        'section_id',
        'subject_id',
        'teacher_id',
        'title',
        'description',
        'attachments',
        'total_marks',
        'issue_date',
        'submission_date',
        'late_submission_allowed',
        'late_penalty_percent',
        'status',
    ];

    protected $casts = [
        'attachments' => 'array',
        'total_marks' => 'integer',
        'issue_date' => 'date',
        'submission_date' => 'date',
        'late_submission_allowed' => 'boolean',
        'late_penalty_percent' => 'decimal:2',
    ];

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('submission_date', '<', now())
                    ->where('status', 'active');
    }

    // Helper Methods


    
    public function getSubmissionStats()
    {
        $total = $this->section->students()->count();
        $submitted = $this->submissions()->where('status', '!=', 'pending')->count();
        $pending = $total - $submitted;
        $graded = $this->submissions()->where('status', 'graded')->count();

        return [
            'total' => $total,
            'submitted' => $submitted,
            'pending' => $pending,
            'graded' => $graded,
        ];
    }
}