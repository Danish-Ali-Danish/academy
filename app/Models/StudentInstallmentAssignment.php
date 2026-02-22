<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentInstallmentAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_enrollment_id', 'installment_plan_id', 'fee_voucher_id',
    ];

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function installmentPlan(): BelongsTo
    {
        return $this->belongsTo(InstallmentPlan::class);
    }

    public function feeVoucher(): BelongsTo
    {
        return $this->belongsTo(FeeVoucher::class, 'fee_voucher_id');
    }

    public function schedule(): HasMany
    {
        return $this->hasMany(InstallmentSchedule::class, 'assignment_id');
    }
}

