<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AttemptAnswer extends Model
{
    protected $fillable = [
        'exam_attempt_id',
        'exam_part_id',
        'question_reference_type',
        'question_reference_id',
        'answer_value',
        'answer_json',
        'is_correct',
    ];

    protected function casts(): array
    {
        return [
            'answer_json' => 'array',
            'is_correct' => 'boolean',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(ExamPart::class, 'exam_part_id');
    }
}
