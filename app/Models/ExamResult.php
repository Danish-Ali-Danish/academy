<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'student_id',
        'subject_id',
        'branch_id',
        'theory_marks',
        'practical_marks',
        'obtained_marks',
        'total_marks',
        'grade',
        'grade_point',
        'rank_in_class',
        'remarks',
        'is_absent',
        'entered_by',
    ];

    protected $casts = [
        'theory_marks' => 'decimal:2',
        'practical_marks' => 'decimal:2',
        'obtained_marks' => 'decimal:2',
        'total_marks' => 'integer',
        'grade_point' => 'decimal:2',
        'rank_in_class' => 'integer',
        'is_absent' => 'boolean',
    ];

    protected $appends = ['percentage', 'is_pass'];

    // Relationships
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    // Accessors
    public function getPercentageAttribute()
    {
        return $this->total_marks > 0 
            ? round(($this->obtained_marks / $this->total_marks) * 100, 2) 
            : 0;
    }

    public function getIsPassAttribute()
    {
        $passPercentage = 33; // Can be dynamic based on subject
        return $this->percentage >= $passPercentage;
    }

    // Scopes
    public function scopeByExam($query, $examId)
    {
        return $query->where('exam_id', $examId);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopePassed($query)
    {
        return $query->whereRaw('(obtained_marks / total_marks) * 100 >= 33');
    }

    public function scopeFailed($query)
    {
        return $query->whereRaw('(obtained_marks / total_marks) * 100 < 33');
    }

    // Helper Methods
    public function calculateGrade()
    {
        $gradeScale = GradeScale::getGradeForPercentage(
            $this->percentage, 
            $this->branch_id
        );

        if ($gradeScale) {
            $this->grade = $gradeScale->grade;
            $this->grade_point = $gradeScale->grade_point;
            $this->save();
        }

        return $this;
    }

    public static function calculateClassRanks($examId, $classId)
    {
        $results = self::where('exam_id', $examId)
            ->whereHas('student', function($q) use ($classId) {
                $q->where('class_id', $classId);
            })
            ->selectRaw('student_id, SUM(obtained_marks) as total_obtained')
            ->groupBy('student_id')
            ->orderByDesc('total_obtained')
            ->get();

        $rank = 1;
        foreach ($results as $result) {
            self::where('exam_id', $examId)
                ->where('student_id', $result->student_id)
                ->update(['rank_in_class' => $rank]);
            $rank++;
        }
    }
}