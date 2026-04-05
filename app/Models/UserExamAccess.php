<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserExamAccess extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'exam_family_id',
        'level',
        'status',
        'granted_at',
        'granted_by',
        'expires_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActiveNow(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }
}