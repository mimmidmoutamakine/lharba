<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDifficultyRating extends Model
{
    protected $fillable = ['user_id', 'part_bank_item_id', 'rating'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function partBankItem(): BelongsTo
    {
        return $this->belongsTo(PartBankItem::class);
    }
}
