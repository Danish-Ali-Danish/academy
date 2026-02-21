<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'title',
        'content',
        'image',
        'target_audience',
        'target_class_id',
        'priority',
        'start_date',
        'end_date',
        'show_on_dashboard',
        'send_notification',
        'send_sms',
        'send_email',
        'status',
        'views_count',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'show_on_dashboard' => 'boolean',
        'send_notification' => 'boolean',
        'send_sms' => 'boolean',
        'send_email' => 'boolean',
        'views_count' => 'integer',
    ];

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function targetClass(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'target_class_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeActive($query)
    {
        return $query->published()
                    ->where('start_date', '<=', now())
                    ->where(function($q) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', now());
                    });
    }

    // Helper Methods
    public function incrementViews()
    {
        $this->increment('views_count');
    }
}