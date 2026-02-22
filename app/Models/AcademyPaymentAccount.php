<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademyPaymentAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_title', 'payment_method', 'account_number',
        'bank_name', 'branch_name', 'iban', 'instructions', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function onlinePaymentProofs(): HasMany
    {
        return $this->hasMany(OnlinePaymentProof::class, 'academy_account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

