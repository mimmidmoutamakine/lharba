<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HoerenTrueFalseQuestion extends Model
{
    protected $fillable = [
        'exam_part_id',
        'statement_text',
        'is_true_correct',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_true_correct' => 'boolean',
        ];
    }

    public function examPart(): BelongsTo
    {
        return $this->belongsTo(ExamPart::class);
    }
}

