<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'student_id',
        'submission_date',
        'submitted_files',
        'content',
        'obtained_marks',
        'grade',
        'feedback',
        'is_late',
        'penalty_applied',
        'graded_by',
        'graded_at',
        'status',
    ];

    protected $casts = [
        'submission_date' => 'datetime',
        'submitted_files' => 'array',
        'obtained_marks' => 'decimal:2',
        'penalty_applied' => 'decimal:2',
        'is_late' => 'boolean',
        'graded_at' => 'datetime',
    ];

    // Relationships
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSubmitted($query)
    {
        return $query->whereIn('status', ['submitted', 'late']);
    }

    public function scopeGraded($query)
    {
        return $query->where('status', 'graded');
    }

    // Helper Methods
    public function submit($files = [], $content = null)
    {
        $this->submission_date = now();
        $this->submitted_files = $files;
        $this->content = $content;
        
        $deadline = $this->assignment->submission_date;
        
        if (now()->gt($deadline)) {
            $this->is_late = true;
            $this->status = 'late';
        } else {
            $this->is_late = false;
            $this->status = 'submitted';
        }
        
        $this->save();
        
        return $this;
    }
}