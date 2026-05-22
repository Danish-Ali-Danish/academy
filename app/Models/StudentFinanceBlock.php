<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentFinanceBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_enrollment_id',
        'is_blocked',
        'block_portal',
        'block_result',
        'block_tc',
        'block_exam',
        'reason',
        'blocked_by',
        'blocked_at',
        'unblocked_by',
        'unblocked_at',
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
        'block_portal' => 'boolean',
        'block_result' => 'boolean',
        'block_tc' => 'boolean',
        'block_exam' => 'boolean',
        'blocked_at' => 'datetime',
        'unblocked_at' => 'datetime',
    ];

    public function studentEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function blockedBy()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function unblockedBy()
    {
        return $this->belongsTo(User::class, 'unblocked_by');
    }
}
