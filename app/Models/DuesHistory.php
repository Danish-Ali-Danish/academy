<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuesHistory extends Model
{
    use HasFactory;

    protected $table = 'dues_history';
    protected $guarded = ['id'];

    protected $casts = [
        'original_amount'   => 'decimal:2',
        'current_amount'    => 'decimal:2',
        'paid_amount'       => 'decimal:2',
        'penalty_applied'   => 'decimal:2',
        'days_overdue'      => 'integer',
        'due_date'          => 'date',
        'last_reminder_date'=> 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function feeVoucher(): BelongsTo
    {
        return $this->belongsTo(FeeVoucher::class);
    }

    public function duesCategory(): BelongsTo
    {
        return $this->belongsTo(DuesCategory::class, 'dues_category_id');
    }
}
