<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReminderTemplate extends Model
{
    use HasFactory;

    protected $table = 'reminder_templates';
    protected $guarded = ['id'];

    protected $casts = [
        'variables'    => 'array',
        'is_active'    => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function duesReminders(): HasMany
    {
        return $this->hasMany(DuesReminders::class, 'reminder_template_id');
    }
}
