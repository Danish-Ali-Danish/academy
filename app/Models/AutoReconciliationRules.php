<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoReconciliationRules extends Model
{
    use HasFactory;

    protected $table = 'auto_reconciliation_rules';
    protected $guarded = ['id'];

    protected $casts = [
        'rule_conditions' => 'array',
        'rule_actions'    => 'array',
        'is_active'       => 'boolean',
        'is_default'      => 'boolean',
    ];
}
