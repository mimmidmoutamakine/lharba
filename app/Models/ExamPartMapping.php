<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamPartMapping extends Model
{
    protected $fillable = [
        'exam_part_entry_version_id',
        'mapping_type',
        'from_block_key',
        'to_block_key',
        'answer_value',
        'is_correct',
        'extra_json',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'extra_json' => 'array',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ExamPartEntryVersion::class, 'exam_part_entry_version_id');
    }
}