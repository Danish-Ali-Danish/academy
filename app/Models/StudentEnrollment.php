<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentEnrollment extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'student_id', 'academic_year_id', 'branch_id', 'class_section_id',
        'roll_number', 'admission_date', 'leaving_date',
        'enrollment_type', 'sibling_order', 'status', 'leaving_reason', 'remarks', 'created_by',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'leaving_date'   => 'date',
    ];

    // ─── Boot: Auto-assign fee structures on new enrollment ──────────────────────

    protected static function boot()
    {
        parent::boot();

        static::created(function (StudentEnrollment $enrollment) {
            // Only assign for active enrollments
            if ($enrollment->status === 'active') {
                $enrollment->assignFeeStructures();
            }
        });
    }

    // ─── Relationships ───────────────────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function feeVouchers(): HasMany
    {
        return $this->hasMany(FeeVoucher::class);
    }

    public function feePayments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    public function feeConcessions(): HasMany
    {
        return $this->hasMany(StudentFeeConcession::class);
    }

    public function studentFeeStructures(): HasMany
    {
        return $this->hasMany(StudentFeeStructure::class);
    }

    public function ledger(): HasMany
    {
        return $this->hasMany(StudentLedger::class);
    }

    public function advanceBalance(): HasOne
    {
        return $this->hasOne(StudentAdvanceBalance::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(FeeRefund::class);
    }

    public function installmentAssignments(): HasMany
    {
        return $this->hasMany(StudentInstallmentAssignment::class);
    }

    public function onlinePaymentProofs(): HasMany
    {
        return $this->hasMany(OnlinePaymentProof::class);
    }

    public function studentScholarships(): HasMany
    {
        return $this->hasMany(StudentScholarship::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Assign all active fee structures for this student's class to this enrollment.
     * Called automatically on create. Can also be called manually to re-sync.
     * Returns count of newly created assignments.
     */
    public function assignFeeStructures(): int
    {
        // Load classSection with branchClass to get class_id
        $this->loadMissing('classSection.branchClass');
        $classId = $this->classSection?->branchClass?->class_id;

        if (!$classId) {
            return 0;
        }

        $feeStructureIds = FeeStructure::active()
            ->where('academic_year_id', $this->academic_year_id)
            ->where('branch_id', $this->branch_id)
            ->where('class_id', $classId)
            ->pluck('id');

        $count = 0;
        foreach ($feeStructureIds as $feeStructureId) {
            StudentFeeStructure::firstOrCreate(
                [
                    'student_enrollment_id' => $this->id,
                    'fee_structure_id'      => $feeStructureId,
                ],
                [
                    'is_active'  => true,
                    'created_by' => auth()->id(),
                ]
            );
            $count++;
        }

        return $count;
    }
}
