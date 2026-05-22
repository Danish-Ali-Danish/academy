<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeStructure extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'academic_year_id', 'branch_id', 'class_id', 'fee_type_id',
        'amount', 'due_day', 'effective_from', 'effective_to',
        'is_active', 'created_by',
        'parent_fee_structure_id', 'version_no', 'version_status',
        'superseded_by_fee_structure_id', 'approved_by', 'approved_at',
        'change_request_id',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'due_day'        => 'integer',
        'effective_from' => 'date',
        'effective_to'   => 'date',
        'is_active'      => 'boolean',
        'version_no'      => 'integer',
        'approved_at'     => 'datetime',
    ];

    // ─── Boot: Auto-assign to students on save ───────────────────────────────────

    protected static function boot()
    {
        parent::boot();

        static::saved(function (FeeStructure $feeStructure) {
            if ($feeStructure->is_active) {
                // Assign to all currently enrolled students in this class
                $feeStructure->syncToEnrolledStudents();
            } else {
                // Deactivate student links when fee structure is deactivated
                StudentFeeStructure::where('fee_structure_id', $feeStructure->id)
                    ->update(['is_active' => false]);
            }
        });
    }

    // ─── Relationships ───────────────────────────────────────────────────────────

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    public function studentFeeStructures(): HasMany
    {
        return $this->hasMany(StudentFeeStructure::class);
    }

    public function parentFeeStructure(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_fee_structure_id');
    }

    public function childVersions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_fee_structure_id');
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseded_by_fee_structure_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(FeeStructureVersion::class);
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(FeeStructureChangeRequest::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Sync this fee structure to all active enrolled students in its class.
     * Uses firstOrCreate so existing records are not duplicated.
     * Returns count of newly created assignments.
     */
    public function syncToEnrolledStudents(): int
    {
        $enrollmentIds = StudentEnrollment::where('academic_year_id', $this->academic_year_id)
            ->where('branch_id', $this->branch_id)
            ->where('status', 'active')
            ->whereHas('classSection.branchClass', function ($q) {
                $q->where('class_id', $this->class_id);
            })
            ->pluck('id');

        $count = 0;
        foreach ($enrollmentIds as $enrollmentId) {
            $created = StudentFeeStructure::firstOrCreate(
                [
                    'student_enrollment_id' => $enrollmentId,
                    'fee_structure_id'      => $this->id,
                ],
                [
                    'is_active'  => true,
                    'created_by' => auth()->id(),
                ]
            );
            // Re-activate if it was previously deactivated
            if (!$created->wasRecentlyCreated && !$created->is_active) {
                $created->update(['is_active' => true]);
            }
            $count++;
        }

        return $count;
    }
}
