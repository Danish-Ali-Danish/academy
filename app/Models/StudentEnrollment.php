<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentEnrollment extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'student_id',
        'branch_class_id',
        'section_id',
        'subject_group_id',
        'academic_year_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function branchClass(): BelongsTo
    {
        return $this->belongsTo(BranchClass::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subjectGroup(): BelongsTo
    {
        return $this->belongsTo(SubjectGroup::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForYear($query, $yearId)
    {
        return $query->where('academic_year_id', $yearId);
    }

    public function scopeCurrentYear($query)
    {
        return $query->whereHas('academicYear', function($q) {
            $q->where('is_active', true);
        });
    }

    // Helper - Get student's all subjects
    public function getSubjects()
    {
        return ClassSubject::where('branch_class_id', $this->branch_class_id)
            ->where(function($query) {
                $query->where('is_compulsory', true)
                      ->orWhere('subject_group_id', $this->subject_group_id);
            })
            ->with('subject')
            ->get();
    }
}