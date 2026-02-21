<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'subject_name',
        'subject_code',
        'is_compulsory',  // ✅ New column added
        'is_active',
    ];

    protected $casts = [
        'is_compulsory' => 'boolean',  // ✅ New cast added
        'is_active' => 'boolean',
    ];

    // Relationships
    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCompulsory($query)  // ✅ New scope
    {
        return $query->where('is_compulsory', true);
    }

    public function scopeOptional($query)  // ✅ New scope
    {
        return $query->where('is_compulsory', false);
    }
}