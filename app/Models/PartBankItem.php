<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PartBankItem extends Model
{
    protected $fillable = [
        'title',
        'source_label',
        'level',
        'section_type',
        'part_type',
        'part_title',
        'instruction_text',
        'points',
        'content_json',
        'config_json',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'content_json' => 'array',
            'config_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

