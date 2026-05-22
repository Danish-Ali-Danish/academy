<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeReminderFollowup extends Model
{
    protected $fillable = [
        'reminder_log_id',
        'outcome',
        'promised_pay_date',
        'notes',
        'handled_by',
    ];

    protected $casts = [
        'promised_pay_date' => 'date',
    ];

    public function log()
    {
        return $this->belongsTo(FeeReminderLog::class, 'reminder_log_id');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
