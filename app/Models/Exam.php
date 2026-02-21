<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'exam_code',
        'exam_type',
        'class_id',
        'academic_year',
        'start_date',
        'end_date',
        'result_date',
        'total_marks',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'result_date' => 'date',
        'total_marks' => 'integer',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function examSubjects(): HasMany
    {
        return $this->hasMany(ExamSubject::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'exam_subjects')
            ->withPivot('exam_date', 'exam_time', 'duration_minutes', 'total_marks', 'theory_marks', 'practical_marks', 'pass_marks')
            ->withTimestamps();
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'scheduled')
                    ->where('start_date', '>', now());
    }

    public function scopeOngoing($query)
    {
        return $query->where('status', 'ongoing')
                    ->whereBetween(now(), ['start_date', 'end_date']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByAcademicYear($query, $academicYear)
    {
        return $query->where('academic_year', $academicYear);
    }

 

    public function isOngoing()
    {
        return now()->between($this->start_date, $this->end_date);
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function getStudentResult($studentId)
    {
        return $this->results()
            ->where('student_id', $studentId)
            ->with('subject')
            ->get();
    }
}
