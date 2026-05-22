<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DuesCategory extends Model
{
    use HasFactory;

    protected $table = 'dues_categories';
    protected $guarded = ['id'];

    protected $casts = [
        'penalty_rate' => 'decimal:2',
        'is_active'    => 'boolean',
        'auto_penalty' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function duesHistory(): HasMany
    {
        return $this->hasMany(DuesHistory::class, 'dues_category_id');
    }
}
