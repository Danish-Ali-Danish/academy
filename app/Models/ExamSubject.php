<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'subject_id',
        'exam_date',
        'exam_time',
        'duration_minutes',
        'total_marks',
        'theory_marks',
        'practical_marks',
        'pass_marks',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'exam_time' => 'datetime',
        'duration_minutes' => 'integer',
        'total_marks' => 'integer',
        'theory_marks' => 'integer',
        'practical_marks' => 'integer',
        'pass_marks' => 'integer',
    ];

    // Relationships
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
