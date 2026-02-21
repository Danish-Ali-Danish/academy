<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'admission_no',
        'roll_no',
        'first_name',
        'last_name',
        'father_name',
        'father_cnic',
        'father_phone',
        'father_occupation',
        'mother_name',
        'mother_phone',
        'date_of_birth',
        'gender',
        'blood_group',
        'religion',
        'nationality',
        'class_id',
        'section_id',
        'previous_school',
        'address',
        'city',
        'state',
        'postal_code',
        'emergency_contact',
        'email',
        'photo',
        'medical_info',
        'admission_date',
        'leaving_date',
        'tc_number',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date',
        'leaving_date' => 'date',
    ];

    protected $appends = ['full_name', 'age'];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(ParentModel::class, 'student_parent', 'student_id', 'parent_id')
            ->withPivot('relation', 'is_primary', 'can_pickup', 'can_view_grades', 'can_receive_sms')
            ->withTimestamps();
    }

    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class);
    }

    public function feePayments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    public function assignmentSubmissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeBySection($query, $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    public function getAgeAttribute()
    {
        if (!$this->date_of_birth) {
            return null;
        }
        return \Carbon\Carbon::parse($this->date_of_birth)->age;
    }

    public function getPendingFeesAttribute()
    {
        return $this->fees()->where('status', 'pending')->sum('balance_amount');
    }

    public function getAttendancePercentage($startDate = null, $endDate = null)
    {
        $query = $this->attendance();
        
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }
        
        $total = $query->count();
        $present = $query->where('status', 'present')->count();
        
        return $total > 0 ? round(($present / $total) * 100, 2) : 0;
    }
}
