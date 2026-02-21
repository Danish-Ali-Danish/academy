<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeType extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'amount',
        'description',
        'frequency',
        'is_mandatory',
        'applicable_from',
        'applicable_to',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_mandatory' => 'boolean',
        'applicable_from' => 'date',
        'applicable_to' => 'date',
    ];

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeByFrequency($query, $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    // Helper Methods
    public function isApplicableNow()
    {
        $now = now();
        
        if ($this->applicable_from && $now->lt($this->applicable_from)) {
            return false;
        }
        
        if ($this->applicable_to && $now->gt($this->applicable_to)) {
            return false;
        }
        
        return true;
    }
}