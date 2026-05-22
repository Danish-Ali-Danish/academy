<?php

namespace App\Models\AppModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeNotifications extends Model
{
    use HasFactory;

    protected $table = 'change_notifications';

    protected $fillable = [
        'change_request_id', 'notification_type', 'recipient_type',
        'recipients', 'notification_status', 'notification_content',
        'sent_at', 'delivered_at', 'failure_reason',
    ];

    protected $casts = [
        'recipients'          => 'array',
        'notification_status' => 'string',
        'sent_at'             => 'datetime',
        'delivered_at'        => 'datetime',
    ];

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequests::class, 'change_request_id');
    }
}
