<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class LesenSituationAd extends Model
{
    protected $fillable = [
        'exam_part_id',
        'label',
        'title',
        'body_text',
        'sort_order',
    ];

    public function examPart(): BelongsTo
    {
        return $this->belongsTo(ExamPart::class);
    }
}
