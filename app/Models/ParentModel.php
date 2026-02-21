<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ParentModel extends Model
{
    use HasFactory;

    protected $table = 'parents';

    protected $fillable = [
        'user_id',
        'branch_id',
        'father_name',
        'father_phone',
        'father_email',
        'father_occupation',
        'father_cnic',
        'father_income',
        'mother_name',
        'mother_phone',
        'mother_email',
        'mother_occupation',
        'mother_cnic',
        'guardian_name',
        'guardian_phone',
        'guardian_relation',
        'address',
        'city',
        'postal_code',
        'preferred_contact',
        'status',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_parent', 'parent_id', 'student_id')
            ->withPivot('relation', 'is_primary', 'can_pickup', 'can_view_grades', 'can_receive_sms')
            ->withTimestamps();
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

    // Helper Methods
    public function getPrimaryContactAttribute()
    {
        switch ($this->preferred_contact) {
            case 'father':
                return $this->father_phone;
            case 'mother':
                return $this->mother_phone;
            case 'guardian':
                return $this->guardian_phone;
            default:
                return $this->father_phone;
        }
    }

    public function getPrimaryEmailAttribute()
    {
        switch ($this->preferred_contact) {
            case 'father':
                return $this->father_email;
            case 'mother':
                return $this->mother_email;
            default:
                return $this->father_email;
        }
    }

    public function getTotalChildrenAttribute()
    {
        return $this->students()->count();
    }
}