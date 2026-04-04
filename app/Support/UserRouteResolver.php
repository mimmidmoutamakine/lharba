<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserExamAccess;
use App\Models\UserExamRequest;

class UserRouteResolver
{
    public static function routeNameFor(?User $user): string
    {
        if (! $user) {
            return 'login';
        }

        if ($user->isAdmin()) {
            return 'admin.exams.index';
        }

        $hasActiveAccess = UserExamAccess::query()
            ->where('user_id', $user->id)
            ->where('status', UserExamAccess::STATUS_ACTIVE)
            ->exists();

        if ($hasActiveAccess) {
            return 'dashboard';
        }

        $hasPendingRequest = UserExamRequest::query()
            ->where('user_id', $user->id)
            ->where('status', UserExamRequest::STATUS_PENDING)
            ->exists();

        if ($hasPendingRequest) {
            return 'approval.pending';
        }

        return 'setup.show';
    }
}