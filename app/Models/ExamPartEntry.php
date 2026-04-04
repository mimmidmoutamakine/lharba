<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamPartEntry extends Model
{
    protected $fillable = [
        'exam_part_id',
        'legacy_part_bank_item_id',
        'external_exam_id',
        'external_part_id',
        'source_label',
        'exam_title',
        'entry_title',
        'arabic_title',
        'level',
        'visibility',
        'is_pro',
        'import_order',
        'max_points',
        'weight',
        'note_text',
        'status',
        'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'is_pro' => 'boolean',
            'weight' => 'decimal:2',
            'meta_json' => 'array',
        ];
    }

    public function examPart(): BelongsTo
    {
        return $this->belongsTo(ExamPart::class, 'exam_part_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ExamPartEntryVersion::class)->latest('id');
    }

    // public function activeVersion(): BelongsTo
    // {
    //     return $this->belongsTo(ExamPartEntryVersion::class, 'active_version_id');
    // }
}