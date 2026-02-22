<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeFineRule extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'branch_id', 'fee_type_id', 'applies_to_all_fee_types',
        'days_after_due', 'fine_type', 'fine_value', 'max_fine',
        'description', 'is_active',
    ];

    protected $casts = [
        'applies_to_all_fee_types' => 'boolean',
        'days_after_due'           => 'integer',
        'fine_value'               => 'decimal:2',
        'max_fine'                 => 'decimal:2',
        'is_active'                => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    public function feeVoucherFines(): HasMany
    {
        return $this->hasMany(FeeVoucherFine::class, 'fine_rule_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
