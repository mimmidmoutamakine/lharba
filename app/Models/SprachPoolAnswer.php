<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SprachPoolAnswer extends Model
{
    protected $fillable = [
        'exam_part_id',
        'sprach_pool_gap_id',
        'correct_option_id',
    ];

    public function examPart(): BelongsTo
    {
        return $this->belongsTo(ExamPart::class);
    }

    public function gap(): BelongsTo
    {
        return $this->belongsTo(SprachPoolGap::class, 'sprach_pool_gap_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(SprachPoolOption::class, 'correct_option_id');
    }
}

