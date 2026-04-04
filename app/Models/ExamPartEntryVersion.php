<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamPartEntryVersion extends Model
{
    protected $fillable = [
        'exam_part_entry_id',
        'version_name',
        'version_kind',
        'is_active',
        'source_payload_json',
        'normalized_payload_json',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'source_payload_json' => 'array',
            'normalized_payload_json' => 'array',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(ExamPartEntry::class, 'exam_part_entry_id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(ExamPartBlock::class)->orderBy('sort_order');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(ExamPartMapping::class)->orderBy('sort_order');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(ExamPartAsset::class)->orderBy('sort_order');
    }
}