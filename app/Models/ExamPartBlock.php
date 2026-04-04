<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamPartBlock extends Model
{
    protected $fillable = [
        'exam_part_entry_version_id',
        'block_group',
        'block_type',
        'block_key',
        'parent_block_key',
        'label',
        'title',
        'body_text',
        'extra_json',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'extra_json' => 'array',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ExamPartEntryVersion::class, 'exam_part_entry_version_id');
    }
}