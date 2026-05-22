<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeWaiverRequest extends Model
{
    use HasFactory;

    protected $table = 'fee_waiver_requests';
    protected $guarded = ['id'];

    protected $casts = [
        'requested_amount'  => 'decimal:2',
        'approved_amount'   => 'decimal:2',
        'request_date'      => 'date',
        'approval_date'     => 'date',
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

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
