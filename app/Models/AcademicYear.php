<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'year_name',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Scope to get only active years
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Relationships
     */
    public function branchClasses()
    {
        return $this->hasMany(\App\Models\BranchClass::class, 'academic_year_id');
    }

    public function studentEnrollments()
    {
        return $this->hasMany(\App\Models\StudentEnrollment::class, 'academic_year_id');
    }
}