<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubjectGroup extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'group_name',
        'description',
        'subject_ids',  // ✅ New column added
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'subject_ids' => 'array',  // ✅ Auto convert to array
    ];

    // Relationships
    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class);
    }

    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    // ✅ Get subjects for this group
    public function subjects()
    {
        if (!$this->subject_ids) {
            return collect([]);
        }

        $subjectIds = is_array($this->subject_ids) 
            ? $this->subject_ids 
            : explode(',', $this->subject_ids);

        return \App\Models\Subject::whereIn('id', $subjectIds)
            ->where('is_active', true)
            ->orderBy('is_compulsory', 'desc')
            ->orderBy('subject_name')
            ->get();
    }

    // ✅ Get compulsory subjects only
    public function compulsorySubjects()
    {
        return $this->subjects()->where('is_compulsory', true);
    }

    // ✅ Get optional subjects only
    public function optionalSubjects()
    {
        return $this->subjects()->where('is_compulsory', false);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}