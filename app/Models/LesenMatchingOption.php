<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class LesenMatchingOption extends Model
{
    protected $fillable = [
        'exam_part_id',
        'option_key',
        'option_text',
        'sort_order',
    ];

    public function examPart(): BelongsTo
    {
        return $this->belongsTo(ExamPart::class);
    }

    public function usedInCorrectAnswer(): HasOne
    {
        return $this->hasOne(LesenMatchingAnswer::class, 'correct_option_id');
    }
}
