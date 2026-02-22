<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentScholarship extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_enrollment_id', 'scholarship_id', 'academic_year_id',
        'awarded_on', 'expiry_date', 'status', 'notes',
    ];

    protected $casts = [
        'awarded_on'  => 'date',
        'expiry_date' => 'date',
    ];

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function scholarship(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
