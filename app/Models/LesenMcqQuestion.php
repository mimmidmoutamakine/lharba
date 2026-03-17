<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class LesenMcqQuestion extends Model
{
    protected $fillable = [
        'exam_part_id',
        'question_text',
        'sort_order',
    ];

    public function examPart(): BelongsTo
    {
        return $this->belongsTo(ExamPart::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(LesenMcqOption::class)->orderBy('sort_order');
    }
}
