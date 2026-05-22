<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentCarryForward extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_enrollment_id', 'branch_id', 'academic_year_id',
        'from_voucher_id', 'from_month_name', 'to_month_name',
        'original_amount', 'carry_amount', 'status', 'cleared_on'
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'carry_amount' => 'decimal:2',
        'cleared_on' => 'date',
    ];

    public function studentEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function fromVoucher()
    {
        return $this->belongsTo(FeeVoucher::class, 'from_voucher_id');
    }
}
