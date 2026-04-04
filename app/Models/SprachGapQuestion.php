<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SprachGapQuestion extends Model
{
    protected $fillable = [
        'exam_part_id',
        'gap_number',
        'sort_order',
    ];

    public function examPart(): BelongsTo
    {
        return $this->belongsTo(ExamPart::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(SprachGapOption::class, 'sprach_gap_question_id')->orderBy('sort_order');
    }
}

