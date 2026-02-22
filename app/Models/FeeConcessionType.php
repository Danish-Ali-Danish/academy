<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeConcessionType extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'concession_name', 'discount_type', 'default_discount_value',
        'applies_to', 'description', 'is_active',
    ];

    protected $casts = [
        'default_discount_value' => 'decimal:2',
        'is_active'              => 'boolean',
    ];

    public function studentFeeConcessions(): HasMany
    {
        return $this->hasMany(StudentFeeConcession::class, 'concession_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}