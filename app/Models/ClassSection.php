<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassSection extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'branch_class_id',
        'section_id',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function branchClass(): BelongsTo
    {
        return $this->belongsTo(BranchClass::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class);
    }

    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    /**
     * Get all subject mappings (pivot table records)
     */
    public function classSectionSubjects(): HasMany
    {
        return $this->hasMany(ClassSectionSubject::class, 'class_section_id');
    }

    /**
     * Get individual subjects directly assigned
     */
    public function individualSubjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'class_section_subjects', 'class_section_id', 'subject_id')
                    ->whereNotNull('class_section_subjects.subject_id');
    }

    /**
     * Get subject groups assigned
     */
    public function subjectGroups(): BelongsToMany
    {
        return $this->belongsToMany(SubjectGroup::class, 'class_section_subjects', 'class_section_id', 'subject_group_id')
                    ->whereNotNull('class_section_subjects.subject_group_id');
    }

    /**
     * Get all subjects (individual + from groups)
     */
    public function getAllSubjects()
    {
        $subjects = collect();

        // Get individual subjects
        $subjects = $subjects->merge($this->individualSubjects);

        // Get subjects from groups
        $this->subjectGroups->each(function($group) use (&$subjects) {
            // Parse subject_ids (stored as comma-separated in subject_groups table)
            $subjectIds = explode(',', $group->subject_ids);
            $groupSubjects = Subject::whereIn('id', $subjectIds)->get();
            $subjects = $subjects->merge($groupSubjects);
        });

        return $subjects->unique('id');
    }

    /**
     * Check if this section has subject groups
     */
    public function hasSubjectGroups(): bool
    {
        return $this->classSectionSubjects()->whereNotNull('subject_group_id')->exists();
    }

    /**
     * Check if this section has individual subjects
     */
    public function hasIndividualSubjects(): bool
    {
        return $this->classSectionSubjects()->whereNotNull('subject_id')->exists();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helpers
    public function getAvailableSeatsAttribute()
    {
        $enrolled = $this->studentEnrollments()->count();
        return $this->capacity - $enrolled;
    }

    public function getIsFullAttribute()
    {
        return $this->available_seats <= 0;
    }
}