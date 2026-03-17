<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class LesenMatchingText extends Model
{
    protected $fillable = [
        'exam_part_id',
        'label',
        'body_text',
        'sort_order',
    ];

    public function examPart(): BelongsTo
    {
        return $this->belongsTo(ExamPart::class);
    }

    public function correctAnswer(): HasOne
    {
        return $this->hasOne(LesenMatchingAnswer::class);
    }
}
