<?php

namespace App\Support;

use App\Models\User;

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

        if (! $user->isApproved()) {
            return 'approval.pending';
        }

        if ($user->needsOnboarding()) {
            return 'setup.show';
        }

        return 'dashboard';
    }
}
