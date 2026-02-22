<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scholarship extends Model
{
    use HasFactory;

    protected $fillable = [
        'scholarship_name', 'discount_type', 'discount_value',
        'applicable_fee_type_id', 'description', 'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class, 'applicable_fee_type_id');
    }

    public function studentScholarships(): HasMany
    {
        return $this->hasMany(StudentScholarship::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

