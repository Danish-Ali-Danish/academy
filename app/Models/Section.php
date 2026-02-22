<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = ['section_name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function classSections()
    {
        return $this->hasMany(ClassSection::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
