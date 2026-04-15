<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;

class ExamAttempt extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'exam_id',
        'user_id',
        'started_at',
        'submitted_at',
        'remaining_seconds',
        'hoeren_remaining_seconds',
        'hoeren_last_synced_at',
        'schreiben_remaining_seconds',
        'schreiben_last_synced_at',
        'status',
        'respect_time',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'hoeren_last_synced_at' => 'datetime',
            'schreiben_last_synced_at' => 'datetime',
            'respect_time' => 'boolean',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_EXPIRED], true);
    }

    public function expiresAt(): Carbon
    {
        return $this->started_at->copy()->addSeconds($this->remaining_seconds);
    }
}
