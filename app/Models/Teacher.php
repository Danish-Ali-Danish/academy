<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'employee_id',
        'first_name',
        'last_name',
        'father_name',
        'cnic',
        'date_of_birth',
        'gender',
        'phone',
        'emergency_contact',
        'email',
        'address',
        'city',
        'qualification',
        'experience_years',
        'specialization',
        'joining_date',
        'resignation_date',
        'basic_salary',
        'allowances',
        'bank_account',
        'photo',
        'documents',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'resignation_date' => 'date',
        'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'experience_years' => 'integer',
        'documents' => 'array',
    ];

    protected $appends = ['full_name', 'total_salary'];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function classesAsClassTeacher(): HasMany
    {
        return $this->hasMany(Classes::class, 'class_teacher_id');
    }

    public function sectionsAsSectionTeacher(): HasMany
    {
        return $this->hasMany(Section::class, 'section_teacher_id');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'teacher_subject')
            ->withPivot('class_id', 'section_id', 'academic_year', 'status')
            ->withTimestamps();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function timetable(): HasMany
    {
        return $this->hasMany(Timetable::class);
    }

    public function markedAttendance(): HasMany
    {
        return $this->hasMany(Attendance::class, 'marked_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getTotalSalaryAttribute()
    {
        return $this->basic_salary + $this->allowances;
    }

    // Helper Methods
    public function getAssignedClasses()
    {
        return $this->subjects()
            ->with('classes')
            ->get()
            ->pluck('classes')
            ->flatten()
            ->unique('id');
    }
}
