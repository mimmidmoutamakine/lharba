<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class LesenSituationAnswer extends Model
{
    protected $fillable = [
        'exam_part_id',
        'lesen_situation_id',
        'correct_ad_id',
        'is_no_match',
    ];

    protected function casts(): array
    {
        return [
            'is_no_match' => 'boolean',
        ];
    }

    public function examPart(): BelongsTo
    {
        return $this->belongsTo(ExamPart::class);
    }

    public function situation(): BelongsTo
    {
        return $this->belongsTo(LesenSituation::class, 'lesen_situation_id');
    }

    public function correctAd(): BelongsTo
    {
        return $this->belongsTo(LesenSituationAd::class, 'correct_ad_id');
    }
}
