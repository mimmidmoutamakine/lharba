<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserExamRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'exam_family_id',
        'level',
        'study_goal',
        'daily_minutes',
        'focus_sections',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'focus_sections' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function examFamily(): BelongsTo
    {
        return $this->belongsTo(ExamFamily::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}