<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class LesenMatchingAnswer extends Model
{
    protected $fillable = [
        'exam_part_id',
        'lesen_matching_text_id',
        'correct_option_id',
    ];

    public function examPart(): BelongsTo
    {
        return $this->belongsTo(ExamPart::class);
    }

    public function text(): BelongsTo
    {
        return $this->belongsTo(LesenMatchingText::class, 'lesen_matching_text_id');
    }

    public function correctOption(): BelongsTo
    {
        return $this->belongsTo(LesenMatchingOption::class, 'correct_option_id');
    }
}
