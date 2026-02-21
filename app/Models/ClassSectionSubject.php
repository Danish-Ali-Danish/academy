<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSectionSubject extends Model
{
    use HasFactory;

    protected $table = 'class_section_subjects';

    protected $fillable = [
        'class_section_id',
        'subject_group_id',
        'subject_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the class section
     */
    public function classSection()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id');
    }

    /**
     * Get the subject group (if assigned)
     */
    public function subjectGroup()
    {
        return $this->belongsTo(SubjectGroup::class, 'subject_group_id');
    }

    /**
     * Get the individual subject (if assigned)
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    /**
     * Check if this mapping is for a subject group
     */
    public function isSubjectGroup()
    {
        return !is_null($this->subject_group_id);
    }

    /**
     * Check if this mapping is for an individual subject
     */
    public function isIndividualSubject()
    {
        return !is_null($this->subject_id);
    }

    /**
     * Get all subjects (either from group or individual)
     */
    public function getAllSubjects()
    {
        if ($this->isSubjectGroup()) {
            // Get subjects from the group
            return $this->subjectGroup->subjects;
        } else {
            // Return individual subject as collection
            return collect([$this->subject]);
        }
    }

    /**
     * Scope to get only subject group mappings
     */
    public function scopeSubjectGroups($query)
    {
        return $query->whereNotNull('subject_group_id');
    }

    /**
     * Scope to get only individual subject mappings
     */
    public function scopeIndividualSubjects($query)
    {
        return $query->whereNotNull('subject_id');
    }

    /**
     * Scope to get by class section
     */
    public function scopeByClassSection($query, $classSectionId)
    {
        return $query->where('class_section_id', $classSectionId);
    }
}