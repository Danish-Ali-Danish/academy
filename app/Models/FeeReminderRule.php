<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeReminderRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'rule_name',
        'trigger_type',
        'days_offset',
        'channel',
        'branch_id',
        'fee_type_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'days_offset' => 'integer',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }

    public function templates()
    {
        return $this->hasMany(FeeReminderTemplate::class, 'rule_id');
    }
}
