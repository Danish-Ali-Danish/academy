<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeReminderTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'rule_id',
        'channel',
        'template_body',
        'language',
        'branch_id',
    ];

    public function rule()
    {
        return $this->belongsTo(FeeReminderRule::class, 'rule_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
