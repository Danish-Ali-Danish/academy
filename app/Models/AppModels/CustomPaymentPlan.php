<?php

namespace App\Models\AppModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CustomPaymentPlan extends Model
{
    use HasFactory;

    protected $table = 'custom_payment_plans';

    protected $fillable = [
        'student_id', 'branch_id', 'fee_voucher_id',
        'plan_name', 'plan_code',
        'total_amount', 'paid_amount', 'remaining_amount',
        'installment_count', 'completed_installments', 'installment_amount',
        'start_date', 'end_date', 'plan_status', 'plan_details',
    ];

    protected $casts = [
        'total_amount'        => 'decimal:2',
        'paid_amount'         => 'decimal:2',
        'remaining_amount'    => 'decimal:2',
        'installment_amount'  => 'decimal:2',
        'installment_count'   => 'integer',
        'completed_installments' => 'integer',
        'start_date'          => 'date',
        'end_date'            => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Student::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function feeVoucher(): BelongsTo
    {
        return $this->belongsTo(\App\Models\FeeVoucher::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(PaymentPlanInstallments::class, 'custom_payment_plan_id');
    }
}
