<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeStructure extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'academic_year_id', 'branch_id', 'class_id', 'fee_type_id',
        'amount', 'due_day', 'effective_from', 'effective_to',
        'is_active', 'created_by',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'due_day'        => 'integer',
        'effective_from' => 'date',
        'effective_to'   => 'date',
        'is_active'      => 'boolean',
    ];

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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
