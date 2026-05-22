<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeReminderLog extends Model
{
    protected $fillable = [
        'voucher_id',
        'student_enrollment_id',
        'template_id',
        'channel',
        'recipient',
        'status',
        'provider_response',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function voucher()
    {
        return $this->belongsTo(FeeVoucher::class, 'voucher_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function template()
    {
        return $this->belongsTo(FeeReminderTemplate::class, 'template_id');
    }

    public function followups()
    {
        return $this->hasMany(FeeReminderFollowup::class, 'reminder_log_id');
    }
}
