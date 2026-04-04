<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamPartAsset extends Model
{
    protected $fillable = [
        'exam_part_entry_version_id',
        'asset_type',
        'label',
        'path_or_url',
        'meta_json',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'meta_json' => 'array',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ExamPartEntryVersion::class, 'exam_part_entry_version_id');
    }
}