<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BranchClass extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'branch_id',
        'class_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    public function classSections(): HasMany
    {
        return $this->hasMany(ClassSection::class);
    }
    public function classSubjects(): HasMany
{
    return $this->hasMany(ClassSubject::class);
}


    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithActiveRelations($query)
    {
        return $query->with([
            'branch' => function($q) {
                $q->where('is_active', true);
            },
            'class' => function($q) {
                $q->where('is_active', true);
            }
        ]);
    }

    // Helper Methods
    
    /**
     * Get all sections assigned to this branch-class combination
     */
    public function getAssignedSectionsAttribute()
    {
        return $this->classSections()
            ->with('section')
            ->get()
            ->pluck('section');
    }

    /**
     * Get total capacity for all sections in this branch-class
     */
    public function getTotalCapacityAttribute()
    {
        return $this->classSections()->sum('capacity');
    }

    /**
     * Get total enrolled students in this branch-class
     */
    public function getTotalEnrolledAttribute()
    {
        return $this->classSections()
            ->withCount('studentEnrollments')
            ->get()
            ->sum('student_enrollments_count');
    }

    /**
     * Check if a specific section is assigned to this branch-class
     */
    public function hasSection($sectionId)
    {
        return $this->classSections()
            ->where('section_id', $sectionId)
            ->exists();
    }

    /**
     * Get available seats across all sections
     */
    public function getAvailableSeatsAttribute()
    {
        return $this->total_capacity - $this->total_enrolled;
    }
}