<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ACCESS_PENDING = 'pending';
    public const ACCESS_APPROVED = 'approved';
    public const ACCESS_REJECTED = 'rejected';

    public const SUBSCRIPTION_NONE = 'none';
    public const SUBSCRIPTION_PENDING_REVIEW = 'pending_review';
    public const SUBSCRIPTION_ACTIVE = 'active';
    public const SUBSCRIPTION_EXPIRED = 'expired';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'preferred_level',
        'study_goal',
        'daily_minutes',
        'focus_sections',
        'onboarding_completed_at',
        'access_status',
        'approved_at',
        'approved_by',
        'approval_note',
        'subscription_status',
        'subscription_plan_name',
        'subscription_started_at',
        'subscription_expires_at',
        'subscription_note',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'focus_sections' => 'array',
            'onboarding_completed_at' => 'datetime',
            'approved_at' => 'datetime',
            'subscription_started_at' => 'datetime',
            'subscription_expires_at' => 'datetime',
        ];
    }

    public function approver()
    {
        return $this->belongsTo(self::class, 'approved_by');
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isApproved(): bool
    {
        return $this->access_status === self::ACCESS_APPROVED;
    }

    public function isPendingApproval(): bool
    {
        return ! $this->isAdmin()
            && ($this->access_status === self::ACCESS_PENDING || $this->access_status === null);
    }

    public function isRejected(): bool
    {
        return $this->access_status === self::ACCESS_REJECTED;
    }

    public function needsOnboarding(): bool
    {
        return ! $this->isAdmin() && $this->onboarding_completed_at === null;
    }

    public function hasActiveSubscription(): bool
    {
        if ($this->subscription_status !== self::SUBSCRIPTION_ACTIVE) {
            return false;
        }

        return $this->subscription_expires_at === null || $this->subscription_expires_at->isFuture();
    }
}
