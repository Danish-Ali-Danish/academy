<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeScale extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'min_percentage',
        'max_percentage',
        'grade',
        'grade_point',
        'remarks',
        'status',
    ];

    protected $casts = [
        'min_percentage' => 'decimal:2',
        'max_percentage' => 'decimal:2',
        'grade_point' => 'decimal:2',
    ];

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Helper Methods
    public static function getGradeForPercentage($percentage, $branchId = null)
    {
        $query = self::active()
            ->where('min_percentage', '<=', $percentage)
            ->where('max_percentage', '>=', $percentage);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->first();
    }
}
