<?php

namespace App\Models\AppModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentPlanInstallments extends Model
{
    use HasFactory;

    protected $table = 'payment_plan_installments';

    protected $fillable = [
        'custom_payment_plan_id', 'installment_amount',
        'due_date', 'paid_amount', 'installment_status', 'paid_date',
        'installment_details',
    ];

    protected $casts = [
        'installment_amount'  => 'decimal:2',
        'paid_amount'         => 'decimal:2',
        'due_date'            => 'date',
        'paid_date'           => 'date',
        'installment_status'  => 'string',
    ];

    public function customPaymentPlan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\AppModels\CustomPaymentPlan::class, 'custom_payment_plan_id');
    }
}
