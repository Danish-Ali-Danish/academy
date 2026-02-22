<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeType extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'fee_name', 'fee_category', 'is_recurring',
        'recurring_months', 'description', 'display_order', 'is_active',
    ];

    protected $casts = [
        'is_recurring'  => 'boolean',
        'is_active'     => 'boolean',
        'display_order' => 'integer',
    ];

    public function feeStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class);
    }

    public function feeVouchers(): HasMany
    {
        return $this->hasMany(FeeVoucher::class);
    }

    public function studentFeeConcessions(): HasMany
    {
        return $this->hasMany(StudentFeeConcession::class);
    }

    public function feeFinRules(): HasMany
    {
        return $this->hasMany(FeeFineRule::class);
    }

    public function siblingDiscountRules(): HasMany
    {
        return $this->hasMany(SiblingDiscountRule::class, 'applies_to_fee_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    public function scopeOneTime($query)
    {
        return $query->where('is_recurring', false);
    }

    public function scopeForStudent($query, string $studentType)
    {
        // school, academy, both — student ke type ke hisaab se fees filter
        return $query->where(function ($q) use ($studentType) {
            $q->where('fee_category', 'both')
              ->orWhere('fee_category', $studentType);
        });
    }
}

