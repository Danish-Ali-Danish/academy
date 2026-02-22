<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiblingDiscountRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'child_number', 'discount_type', 'discount_value',
        'applies_to_fee_type_id', 'description', 'is_active',
    ];

    protected $casts = [
        'child_number'   => 'integer',
        'discount_value' => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class, 'applies_to_fee_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}