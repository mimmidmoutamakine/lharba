<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SprachGapOption extends Model
{
    protected $fillable = [
        'sprach_gap_question_id',
        'option_key',
        'option_text',
        'is_correct',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SprachGapQuestion::class, 'sprach_gap_question_id');
    }
}

