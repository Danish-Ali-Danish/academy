<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $table = 'payment_gateways';
    protected $guarded = ['id'];

    protected $casts = [
        'service_charge' => 'decimal:2',
        'is_active'      => 'boolean',
        'test_mode'      => 'boolean',
        'config'         => 'array',
    ];
}
