<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classes extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'class_name',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    // Direct Relationships
    public function branchClasses(): HasMany
    {
        return $this->hasMany(BranchClass::class, 'class_id');
    }

    // Through Relationships - Get all class sections through branch classes
    public function classSections(): HasManyThrough
    {
        return $this->hasManyThrough(
            ClassSection::class,
            BranchClass::class,
            'class_id',           // Foreign key on BranchClass table
            'branch_class_id',    // Foreign key on ClassSection table
            'id',                 // Local key on Classes table
            'id'                  // Local key on BranchClass table
        );
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    // Helper Methods
    
    /**
     * Get all unique sections assigned to this class across all branches
     */
    public function getUniqueSectionsAttribute()
    {
        return Section::whereHas('classSections.branchClass', function($query) {
            $query->where('class_id', $this->id);
        })->distinct()->get();
    }

    /**
     * Get total capacity across all sections for this class
     */
    public function getTotalCapacityAttribute()
    {
        return $this->classSections()->sum('capacity');
    }

    /**
     * Get total enrolled students across all sections for this class
     */
    public function getTotalEnrolledAttribute()
    {
        return $this->classSections()
            ->withCount('studentEnrollments')
            ->get()
            ->sum('student_enrollments_count');
    }

    /**
     * Check if class has any sections assigned
     */
    public function hasSections()
    {
        return $this->classSections()->exists();
    }
}