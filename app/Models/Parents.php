<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parents extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'parents';

    protected $fillable = [
        'father_name', 'father_cnic', 'father_phone', 'father_occupation',
        'mother_name', 'mother_cnic', 'mother_phone',
        'address', 'city',
        'emergency_contact_name', 'emergency_contact_phone',
        'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function students()
    {
        return $this->hasMany(Student::class, 'parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}