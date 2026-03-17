<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class ExamSection extends Model
{
    public const TYPE_LESEN = 'lesen';
    public const TYPE_SPRACHBAUSTEINE = 'sprachbausteine';
    public const TYPE_HOEREN = 'hoeren';
    public const TYPE_SCHREIBEN = 'schreiben';

    protected $fillable = [
        'exam_id',
        'type',
        'title',
        'sort_order',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_LESEN => 'Lesen',
            self::TYPE_SPRACHBAUSTEINE => 'Sprachbausteine',
            self::TYPE_HOEREN => 'Horen',
            self::TYPE_SCHREIBEN => 'Schreiben',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(ExamPart::class)->orderBy('sort_order');
    }
}
