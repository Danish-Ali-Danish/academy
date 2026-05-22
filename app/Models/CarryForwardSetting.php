<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarryForwardSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id', 'is_enabled', 'scope', 'max_months'
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'max_months' => 'integer',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
