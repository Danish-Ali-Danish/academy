<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassSubject extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'class_section_id',
        'subject_id',        // For individual subjects (choti classes)
        'subject_group_id',  // For subject groups (bari classes)
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ✅ Relationships
    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function subjectGroup(): BelongsTo
    {
        return $this->belongsTo(SubjectGroup::class);
    }

    // ✅ Get all subjects (whether individual or from group)
    public function getSubjectsAttribute()
    {
        if ($this->subject_id) {
            // Individual subject (choti classes)
            return collect([$this->subject]);
        }
        
        if ($this->subject_group_id) {
            // Group subjects (bari classes)
            return $this->subjectGroup->subjects;
        }
        
        return collect([]);
    }

    // ✅ Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeIndividual($query)
    {
        return $query->whereNotNull('subject_id');
    }

    public function scopeGrouped($query)
    {
        return $query->whereNotNull('subject_group_id');
    }

    public function scopeByGroup($query, $groupId)
    {
        return $query->where('subject_group_id', $groupId);
    }

    public function scopeBySection($query, $sectionId)
    {
        return $query->where('class_section_id', $sectionId);
    }

    public function scopeByClass($query, $classId)
    {
        return $query->whereHas('classSection', function($q) use ($classId) {
            $q->where('branch_class_id', $classId);
        });
    }

    // ✅ Helper methods
    public function getClassAttribute()
    {
        return $this->classSection->branchClass ?? null;
    }

    public function getBranchAttribute()
    {
        return $this->classSection->branchClass->branch ?? null;
    }
}