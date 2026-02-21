<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'branch_name',
        'location',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ✅ Many-to-Many Relationship
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(
            Classes::class,           // Related model
            'branch_classes',         // Pivot table name
            'branch_id',              // Foreign key in pivot table
            'class_id'                // Related key in pivot table
        )
        ->withPivot('is_active')      // Extra pivot columns access
        ->withTimestamps();           // Pivot table timestamps
    }

    // ✅ Ya agar sirf active classes chahiye
    public function activeClasses(): BelongsToMany
    {
        return $this->classes()->wherePivot('is_active', true);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}