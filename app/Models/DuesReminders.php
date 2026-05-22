<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuesReminders extends Model
{
    use HasFactory;

    protected $table = 'dues_reminders';
    protected $guarded = ['id'];

    protected $casts = [
        'sent_at'        => 'datetime',
        'delivered_at'   => 'datetime',
    ];

    public function duesHistory(): BelongsTo
    {
        return $this->belongsTo(DuesHistory::class, 'dues_history_id');
    }

    public function reminderTemplate(): BelongsTo
    {
        return $this->belongsTo(ReminderTemplate::class, 'reminder_template_id');
    }
}
