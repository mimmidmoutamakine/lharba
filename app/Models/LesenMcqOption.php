<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class LesenMcqOption extends Model
{
    protected $fillable = [
        'lesen_mcq_question_id',
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
        return $this->belongsTo(LesenMcqQuestion::class, 'lesen_mcq_question_id');
    }
}
