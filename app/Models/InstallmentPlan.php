<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstallmentPlan extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'plan_name', 'total_installments',
        'applicable_fee_type_id', 'description', 'is_active', 'created_by',
    ];

    protected $casts = [
        'total_installments' => 'integer',
        'is_active'          => 'boolean',
    ];

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class, 'applicable_fee_type_id');
    }

    public function studentAssignments(): HasMany
    {
        return $this->hasMany(StudentInstallmentAssignment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
